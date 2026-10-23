<?php

namespace Database\Seeders;

use App\Models\Election;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Demo Election Officer
        |--------------------------------------------------------------------------
        */
        $officer = User::firstOrCreate(
            ['email' => 'officer@example.com'],
            [
                'name' => 'Demo Election Officer',
                'password' => Hash::make('Officer123!'),
                'role' => User::ROLE_ELECTION_OFFICER,
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Demo Observer
        |--------------------------------------------------------------------------
        */
        User::firstOrCreate(
            ['email' => 'observer@example.com'],
            [
                'name' => 'Demo Observer',
                'password' => Hash::make('Observer123!'),
                'role' => User::ROLE_OBSERVER,
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Demo Election
        |--------------------------------------------------------------------------
        */
        $electionIds = Election::query()
            ->whereIn('year', [2023])
            ->pluck('id');

        $officer->assignedElections()->syncWithoutDetaching($electionIds);
    }
}
