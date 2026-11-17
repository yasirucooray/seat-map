<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;

class GoogleSheetsService
{
    protected $client;

    protected $service;

    protected $spreadsheetId;

    protected $cachedSheetId = null;

    public function __construct()
    {
        $this->spreadsheetId = env('GOOGLE_SPREADSHEET_ID', '1s-HWBzNyYW3reliy7tS8_QHrz7pIcgoreUFV9T-2p7c');

        $this->client = new Client;
        $this->client->setApplicationName('Kalabara Seating');
        $this->client->setScopes([Sheets::SPREADSHEETS]);

        // Set fast connect and read timeouts so slow Google API never blocks web requests indefinitely
        $httpClient = new \GuzzleHttp\Client([
            'connect_timeout' => 3.5,
            'timeout' => 5.0,
        ]);
        $this->client->setHttpClient($httpClient);

        $credentialsPath = env('GOOGLE_SERVICE_ACCOUNT_JSON');
        if (! $credentialsPath) {
            $credentialsPath = storage_path('keys/potent-airfoil-399806-569635b7afda.json');
        }

        if (str_starts_with(trim($credentialsPath), '{')) {
            $this->client->setAuthConfig(json_decode($credentialsPath, true));
        } else {
            $this->client->setAuthConfig($credentialsPath);
        }

        $this->service = new Sheets($this->client);
    }

    public function getRangeForMapType($mapType = 'v1')
    {
        return $mapType === 'v2' ? 'kalabara-indoor!A:H' : env('GOOGLE_SHEET_RANGE', 'kalabara!A:H');
    }

    public function getSheetTitleForMapType($mapType = 'v1')
    {
        return $mapType === 'v2' ? 'kalabara-indoor' : 'kalabara';
    }

    protected function getTargetSheetId($sheetTitle = null)
    {
        if ($sheetTitle === null) {
            $range = env('GOOGLE_SHEET_RANGE', 'kalabara!A:H');
            $sheetTitle = str_contains($range, '!') ? explode('!', $range)[0] : 'kalabara';
        }

        try {
            $spreadsheet = $this->service->spreadsheets->get($this->spreadsheetId);
            foreach ($spreadsheet->getSheets() as $sheet) {
                if (strcasecmp($sheet->getProperties()->getTitle(), $sheetTitle) === 0) {
                    return $sheet->getProperties()->getSheetId();
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("GoogleSheets getTargetSheetId failed for {$sheetTitle}: " . $e->getMessage());
        }

        return 0;
    }

    public function appendReservations(array $reservations)
    {
        if (empty($reservations)) {
            return;
        }

        try {
            $values = [];
            foreach ($reservations as $reservation) {
                $isV2 = ($reservation->map_type ?? 'v1') === 'v2';
                if ($isV2) {
                    $seatDetail = $reservation->category === 'standing'
                        ? 'Standing'
                        : ($reservation->is_full_table ? 'Full Table' : "Seat {$reservation->seat_number}");
                    $tableNumber = $reservation->table_number ? "Table {$reservation->table_number}" : 'N/A';
                    $sideLabel = $reservation->category === 'standing' ? ucfirst($reservation->side ?? 'Left') : 'Stage Map (v2)';
                } else {
                    $seatDetail = $reservation->category === 'standing' ? 'Standing' : ($reservation->is_full_table ? 'Full Table' : "Seat {$reservation->seat_number}");
                    $tableNumber = $reservation->table_number ? strtoupper(substr($reservation->side, 0, 1)).$reservation->table_number : 'N/A';
                    $sideLabel = ucfirst($reservation->side ?? 'Center');
                }

                $values[] = [
                    now()->toDateTimeString(),
                    ucfirst($reservation->category),
                    $sideLabel,
                    $tableNumber,
                    $seatDetail,
                    $reservation->name,
                    $reservation->email,
                    $reservation->ticket_number,
                ];
            }

            $body = new Sheets\ValueRange([
                'values' => $values,
            ]);

            $firstRes = $reservations[0];
            $isV2Batch = ($firstRes->map_type ?? 'v1') === 'v2';
            $range = $this->getRangeForMapType($isV2Batch ? 'v2' : 'v1');
            $sheetTitle = $this->getSheetTitleForMapType($isV2Batch ? 'v2' : 'v1');

            $response = null;
            try {
                $response = $this->service->spreadsheets_values->append(
                    $this->spreadsheetId,
                    $range,
                    $body,
                    ['valueInputOption' => 'RAW']
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Failed appending to {$range}, trying fallback Sheet1!A:H: " . $e->getMessage());
                try {
                    $fallbackRange = 'Sheet1!A:H';
                    $response = $this->service->spreadsheets_values->append(
                        $this->spreadsheetId,
                        $fallbackRange,
                        $body,
                        ['valueInputOption' => 'RAW']
                    );
                } catch (\Exception $e2) {
                    \Illuminate\Support\Facades\Log::error("Fallback Sheet1 append also failed: " . $e2->getMessage());
                }
            }

            // Style the appended rows in batch
            if ($response && !empty($reservations)) {
                try {
                    $updatedRange = $response->getUpdates()->getUpdatedRange();
                    if (preg_match('/!A([0-9]+):/', $updatedRange, $matches)) {
                        $startRowIndex = intval($matches[1]) - 1; // 0-based index
                        $endRowIndex = $startRowIndex + count($reservations);
                        $this->styleRowsRange($startRowIndex, $endRowIndex, $reservations[0]->category, $sheetTitle);
                    }
                } catch (\Exception $e) {
                    // Fail silently so sheet insertion still works if formatting fails
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("GoogleSheets appendReservations exception: " . $e->getMessage());
        }
    }

    public function appendReservation($reservation)
    {
        $this->appendReservations([$reservation]);
    }

    protected function styleRowsRange($startRowIndex, $endRowIndex, $category, $sheetTitle = null)
    {
        $red = 1.0;
        $green = 1.0;
        $blue = 1.0;

        if ($category === 'platinum') {
            $red = 0.88;
            $green = 0.95;
            $blue = 1.0; // Soft Light Blue
        } elseif ($category === 'gold') {
            $red = 1.0;
            $green = 0.94;
            $blue = 0.84; // Soft Light Orange/Yellow
        } elseif ($category === 'standing') {
            $red = 0.96;
            $green = 0.91;
            $blue = 1.0; // Soft Light Purple
        }

        $sheetId = $this->getTargetSheetId($sheetTitle);

        $request = new BatchUpdateSpreadsheetRequest([
            'requests' => [
                [
                    'repeatCell' => [
                        'range' => [
                            'sheetId' => $sheetId,
                            'startRowIndex' => $startRowIndex,
                            'endRowIndex' => $endRowIndex,
                            'startColumnIndex' => 0,
                            'endColumnIndex' => 8,
                        ],
                        'cell' => [
                            'userEnteredFormat' => [
                                'backgroundColor' => [
                                    'red' => $red,
                                    'green' => $green,
                                    'blue' => $blue,
                                ],
                                'textFormat' => [
                                    'foregroundColor' => [
                                        'red' => 0.1,
                                        'green' => 0.1,
                                        'blue' => 0.1,
                                    ],
                                ],
                            ],
                        ],
                        'fields' => 'userEnteredFormat(backgroundColor,textFormat(foregroundColor))',
                    ],
                ],
            ],
        ]);

        $this->service->spreadsheets->batchUpdate($this->spreadsheetId, $request);
    }

    public function deleteReservation($reservation)
    {
        try {
            $isV2 = ($reservation->map_type ?? 'v1') === 'v2';
            $range = $this->getRangeForMapType($isV2 ? 'v2' : 'v1');
            $activeSheetTitle = $this->getSheetTitleForMapType($isV2 ? 'v2' : 'v1');
            $values = null;

            try {
                $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
                $values = $response->getValues();
            } catch (\Exception $e) {
                try {
                    $activeSheetTitle = 'Sheet1';
                    $response = $this->service->spreadsheets_values->get($this->spreadsheetId, 'Sheet1!A:H');
                    $values = $response->getValues();
                } catch (\Exception $e2) {
                    \Illuminate\Support\Facades\Log::error("Fallback Sheet1 get also failed: " . $e2->getMessage());
                }
            }

            $rowIndex = -1;
            if ($values) {
                foreach ($values as $index => $row) {
                    if (isset($row[7]) && strcasecmp(trim($row[7]), trim($reservation->ticket_number)) === 0) {
                        $rowIndex = $index;
                        break;
                    }
                }
            }

            if ($rowIndex !== -1) {
                $sheetId = $this->getTargetSheetId($activeSheetTitle);
                $request = new BatchUpdateSpreadsheetRequest([
                    'requests' => [
                        [
                            'deleteDimension' => [
                                'range' => [
                                    'sheetId' => $sheetId,
                                    'dimension' => 'ROWS',
                                    'startIndex' => $rowIndex,
                                    'endIndex' => $rowIndex + 1,
                                ],
                            ],
                        ],
                    ],
                ]);
                $this->service->spreadsheets->batchUpdate($this->spreadsheetId, $request);
                \Illuminate\Support\Facades\Log::info("Deleted row {$rowIndex} for ticket {$reservation->ticket_number} from sheet {$activeSheetTitle}");
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("GoogleSheets deleteReservation exception: " . $e->getMessage());
        }
    }

    public function updateReservation($reservation, $oldTicketNumber = null)
    {
        $this->updateReservationsBatch([$reservation], $oldTicketNumber);
    }

    public function updateReservationsBatch(array $reservations, $singleOldTicket = null)
    {
        if (empty($reservations)) {
            return;
        }

        try {
            $first = $reservations[0];
            $isV2 = ($first->map_type ?? 'v1') === 'v2';
            $range = $this->getRangeForMapType($isV2 ? 'v2' : 'v1');
            $activeSheetTitle = $this->getSheetTitleForMapType($isV2 ? 'v2' : 'v1');

            $values = null;
            try {
                $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
                $values = $response->getValues();
            } catch (\Exception $e) {
                try {
                    $activeSheetTitle = 'Sheet1';
                    $response = $this->service->spreadsheets_values->get($this->spreadsheetId, 'Sheet1!A:H');
                    $values = $response->getValues();
                } catch (\Exception $e2) {
                    \Illuminate\Support\Facades\Log::error("Get sheet values failed: " . $e2->getMessage());
                    return;
                }
            }

            if (empty($values)) {
                return;
            }

            // Index tickets in sheet
            $ticketToRow = [];
            foreach ($values as $index => $row) {
                if (isset($row[7])) {
                    $t = strtolower(trim((string)$row[7]));
                    if ($t !== '') {
                        $ticketToRow[$t] = $index;
                    }
                }
            }

            $updateData = [];
            foreach ($reservations as $reservation) {
                $ticketToFind = strtolower(trim((string)($singleOldTicket ?? $reservation->ticket_number)));
                if (!isset($ticketToRow[$ticketToFind])) {
                    $curr = strtolower(trim((string)$reservation->ticket_number));
                    if (!isset($ticketToRow[$curr])) {
                        continue;
                    }
                    $rowIndex = $ticketToRow[$curr];
                } else {
                    $rowIndex = $ticketToRow[$ticketToFind];
                }

                $rowNumber = $rowIndex + 1; // 1-based row index in sheet

                if ($isV2) {
                    $seatDetail = $reservation->category === 'standing'
                        ? 'Standing'
                        : ($reservation->is_full_table ? 'Full Table' : "Seat {$reservation->seat_number}");
                    $tableNumber = $reservation->table_number ? "Table {$reservation->table_number}" : 'N/A';
                    $sideLabel = $reservation->category === 'standing' ? ucfirst($reservation->side ?? 'Left') : 'Stage Map (v2)';
                } else {
                    $seatDetail = $reservation->category === 'standing' ? 'Standing' : ($reservation->is_full_table ? 'Full Table' : "Seat {$reservation->seat_number}");
                    $tableNumber = $reservation->table_number ? strtoupper(substr($reservation->side, 0, 1)).$reservation->table_number : 'N/A';
                    $sideLabel = ucfirst($reservation->side ?? 'Center');
                }

                $updateData[] = new Sheets\ValueRange([
                    'range' => "{$activeSheetTitle}!A{$rowNumber}:H{$rowNumber}",
                    'values' => [
                        [
                            now()->toDateTimeString(),
                            ucfirst($reservation->category),
                            $sideLabel,
                            $tableNumber,
                            $seatDetail,
                            $reservation->name,
                            $reservation->email,
                            $reservation->ticket_number,
                        ],
                    ],
                ]);
            }

            if (!empty($updateData)) {
                $batchBody = new Sheets\BatchUpdateValuesRequest([
                    'valueInputOption' => 'RAW',
                    'data' => $updateData,
                ]);
                $this->service->spreadsheets_values->batchUpdate($this->spreadsheetId, $batchBody);
                \Illuminate\Support\Facades\Log::info("Batch updated " . count($updateData) . " row(s) in sheet {$activeSheetTitle}");
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("GoogleSheets updateReservationsBatch exception: " . $e->getMessage());
        }
    }
}
