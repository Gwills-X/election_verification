<?php

namespace Database\Seeders;

use App\Models\Election;
use App\Models\ElectionType;
use Illuminate\Database\Seeder;

class ElectionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([ElectionType::PRESIDENTIAL, ElectionType::GOVERNORSHIP] as $code) {
            $type = ElectionType::where('code', $code)->firstOrFail();

            Election::firstOrCreate(
                [
                    'election_type_id' => $type->id,
                    'year' => 2023,
                ],
                ['status' => Election::STATUS_ACTIVE]
            );
        }
    }
}
