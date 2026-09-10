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

    protected function getTargetSheetId()
    {
        if ($this->cachedSheetId !== null) {
            return $this->cachedSheetId;
        }

        $range = env('GOOGLE_SHEET_RANGE', 'kalabara!A:H');
        $sheetTitle = str_contains($range, '!') ? explode('!', $range)[0] : 'kalabara';

        try {
            $spreadsheet = $this->service->spreadsheets->get($this->spreadsheetId);
            foreach ($spreadsheet->getSheets() as $sheet) {
                if (strcasecmp($sheet->getProperties()->getTitle(), $sheetTitle) === 0) {
                    $this->cachedSheetId = $sheet->getProperties()->getSheetId();
                    return $this->cachedSheetId;
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("GoogleSheets getTargetSheetId failed: " . $e->getMessage());
        }

        $this->cachedSheetId = 0;
        return $this->cachedSheetId;
    }

    public function appendReservations(array $reservations)
    {
        if (empty($reservations)) {
            return;
        }

        try {
            $values = [];
            foreach ($reservations as $reservation) {
                $seatDetail = $reservation->category === 'standing' ? 'Standing' : ($reservation->is_full_table ? 'Full Table' : "Seat {$reservation->seat_number}");
                $tableNumber = $reservation->table_number ? strtoupper(substr($reservation->side, 0, 1)).$reservation->table_number : 'N/A';

                $values[] = [
                    now()->toDateTimeString(),
                    ucfirst($reservation->category),
                    ucfirst($reservation->side ?? 'Center'),
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

            $range = env('GOOGLE_SHEET_RANGE', 'kalabara!A:H');

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
                        $this->styleRowsRange($startRowIndex, $endRowIndex, $reservations[0]->category);
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

    protected function styleRowsRange($startRowIndex, $endRowIndex, $category)
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

        $sheetId = $this->getTargetSheetId();

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
            $range = env('GOOGLE_SHEET_RANGE', 'kalabara!A:H');
            $values = null;
            $activeSheetTitle = str_contains($range, '!') ? explode('!', $range)[0] : 'kalabara';

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
                $sheetId = $this->getTargetSheetId();
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
        try {
            $ticketToFind = $oldTicketNumber ?? $reservation->ticket_number;
            $range = env('GOOGLE_SHEET_RANGE', 'kalabara!A:H');
            $values = null;
            $activeSheetTitle = str_contains($range, '!') ? explode('!', $range)[0] : 'kalabara';

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
                }
            }

            $rowIndex = -1;
            if ($values) {
                foreach ($values as $index => $row) {
                    if (isset($row[7]) && strcasecmp(trim($row[7]), trim($ticketToFind)) === 0) {
                        $rowIndex = $index;
                        break;
                    }
                }
            }

            if ($rowIndex !== -1) {
                $seatDetail = $reservation->category === 'standing' ? 'Standing' : ($reservation->is_full_table ? 'Full Table' : "Seat {$reservation->seat_number}");
                $tableNumber = $reservation->table_number ? strtoupper(substr($reservation->side, 0, 1)).$reservation->table_number : 'N/A';
                $rowNumber = $rowIndex + 1; // 1-based row index in sheet

                $updateValues = [
                    [
                        now()->toDateTimeString(),
                        ucfirst($reservation->category),
                        ucfirst($reservation->side ?? 'Center'),
                        $tableNumber,
                        $seatDetail,
                        $reservation->name,
                        $reservation->email,
                        $reservation->ticket_number,
                    ],
                ];

                $body = new Sheets\ValueRange([
                    'values' => $updateValues,
                ]);

                $updateRange = "{$activeSheetTitle}!A{$rowNumber}:H{$rowNumber}";
                $this->service->spreadsheets_values->update(
                    $this->spreadsheetId,
                    $updateRange,
                    $body,
                    ['valueInputOption' => 'RAW']
                );

                $this->styleRowsRange($rowIndex, $rowIndex + 1, $reservation->category);
                \Illuminate\Support\Facades\Log::info("Updated row {$rowNumber} for ticket {$reservation->ticket_number} in sheet {$activeSheetTitle}");
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("GoogleSheets updateReservation exception: " . $e->getMessage());
        }
    }
}
