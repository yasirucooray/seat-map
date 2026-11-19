<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::updateOrCreate(
    ['email' => 'volunteer.kalabara@gmail.com'],
    [
        'name' => 'Kalabara Volunteer',
        'password' => Illuminate\Support\Facades\Hash::make('volunteer@2026')
    ]
);

echo "User created/updated: " . $u->email . " (ID: " . $u->id . ")" . PHP_EOL;
echo "isVolunteer: " . ($u->isVolunteer() ? 'YES' : 'NO') . PHP_EOL;
echo "canEdit: " . ($u->canEdit() ? 'YES' : 'NO') . PHP_EOL;

