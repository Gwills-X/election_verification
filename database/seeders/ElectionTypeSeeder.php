<?php

namespace Database\Seeders;

use App\Models\ElectionType;
use App\Models\State;
use Illuminate\Database\Seeder;

class ElectionTypeSeeder extends Seeder
{
    public function run(): void
    {
        $bauchi = State::where('code', 'BA')->firstOrFail();

        ElectionType::updateOrCreate(
            ['code' => ElectionType::PRESIDENTIAL],
            [
                'name' => 'Presidential',
                'scope' => 'NATIONAL',
                'scope_state_id' => null,
            ]
        );

        ElectionType::updateOrCreate(
            ['code' => ElectionType::GOVERNORSHIP],
            [
                'name' => 'Governorship',
                'scope' => 'STATE',
                'scope_state_id' => $bauchi->id,
            ]
        );
    }
}
