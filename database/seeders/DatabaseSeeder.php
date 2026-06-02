<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'lucien@valicare.nl'],
            [
                'name' => 'Lucien Valicare',
                'role' => UserRole::SuperAdmin,
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $this->call([
            HistoricalFactSeeder::class,
            DemoTournamentSeeder::class,
        ]);
    }
}
