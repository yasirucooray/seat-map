<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
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
}
