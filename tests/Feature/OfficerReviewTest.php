<?php

namespace Tests\Feature;

use App\Models\Election;
use App\Models\ElectionResult;
use App\Models\ElectionType;
use App\Models\LocalGovernment;
use App\Models\PollingUnit;
use App\Models\State;
use App\Models\User;
use App\Models\Ward;
use Database\Seeders\ElectionTypeSeeder;
use Database\Seeders\GeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficerReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([GeographySeeder::class, ElectionTypeSeeder::class]);
    }

    public function test_election_officer_can_review_only_assigned_results(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $officer = User::factory()->create(['role' => User::ROLE_ELECTION_OFFICER]);
        $election = Election::create([
            'election_type_id' => ElectionType::where('code', ElectionType::PRESIDENTIAL)->value('id'),
            'year' => 2023,
            'status' => Election::STATUS_ACTIVE,
        ]);

        $bauchi = State::where('code', 'BA')->firstOrFail();
        $lga = LocalGovernment::where('state_id', $bauchi->id)
            ->where('name', 'Alkaleri')->firstOrFail();
        $ward = Ward::create([
            'local_government_id' => $lga->id,
            'name' => 'Ward 1',
        ]);
        $pollingUnit = PollingUnit::create([
            'code' => 'BA/01/01/001',
            'ward_id' => $ward->id,
            'name' => 'PU 1',
        ]);

        $result = ElectionResult::create([
            'election_id' => $election->id,
            'polling_unit_id' => $pollingUnit->id,
            'registered_voters' => 500,
            'accredited_voters' => 300,
            'rejected_votes' => 5,
            'total_votes' => 225,
            'validation_status' => 'FLAGGED_FOR_REVIEW',
            'source_type' => 'REAL',
        ]);

        $election->assignedOfficers()->attach($officer->id);

        $this->actingAs($officer)
            ->get("/officer/results/{$result->id}")
            ->assertOk();

        $this->actingAs($officer)
            ->post("/officer/results/{$result->id}/review", [
                'review_note' => 'Result reviewed and accepted.',
                'decision' => 'VALID',
            ])
            ->assertRedirect();

        $this->assertNotNull(ElectionResult::find($result->id)->reviewed_at);
        $this->assertSame('VALID', ElectionResult::find($result->id)->validation_status);

        $otherUser = User::factory()->create(['role' => User::ROLE_ELECTION_OFFICER]);
        $otherElection = Election::create([
            'election_type_id' => ElectionType::where('code', ElectionType::PRESIDENTIAL)->value('id'),
            'year' => 2024,
            'status' => Election::STATUS_ACTIVE,
        ]);

        $kano = State::where('code', 'KN')->firstOrFail();
        $kanoLga = LocalGovernment::firstOrCreate([
            'state_id' => $kano->id,
            'name' => 'Ajingi',
        ]);
        $kanoWard = Ward::create([
            'local_government_id' => $kanoLga->id,
            'name' => 'Ward 2',
        ]);

        $otherResult = ElectionResult::create([
            'election_id' => $otherElection->id,
            'polling_unit_id' => PollingUnit::create([
                'code' => 'KN/02/02/002',
                'ward_id' => $kanoWard->id,
                'name' => 'PU 2',
            ])->id,
            'registered_voters' => 500,
            'accredited_voters' => 300,
            'rejected_votes' => 5,
            'total_votes' => 275,
            'validation_status' => 'FLAGGED_FOR_REVIEW',
            'source_type' => 'REAL',
        ]);

        $this->actingAs($otherUser)
            ->get("/officer/results/{$otherResult->id}")
            ->assertForbidden();
    }
}
