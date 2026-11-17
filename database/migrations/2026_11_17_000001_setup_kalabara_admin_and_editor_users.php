<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        // Remove old default admin@rawaya.com if present and create/update the two official accounts
        User::where('email', 'admin@rawaya.com')->delete();

        User::updateOrCreate(
            ['email' => 'kalabara@gmail.com'],
            [
                'name' => 'Kalabara Admin',
                'password' => Hash::make('kalabara@edge'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'editor2026.kalabara@gmail.com'],
            [
                'name' => 'Kalabara Editor',
                'password' => Hash::make('kalabara@2026'),
            ]
        );
    }

    public function down(): void
    {
        User::whereIn('email', [
            'kalabara@gmail.com',
            'editor2026.kalabara@gmail.com',
        ])->delete();
    }
};

