<?php

namespace Tests\Feature;

use App\Models\Election;
use App\Models\ElectionResult;
use App\Models\ElectionType;
use App\Models\LocalGovernment;
use App\Models\Party;
use App\Models\PollingUnit;
use App\Models\ElectionResultCandidateVote;
use App\Models\State;
use App\Models\User;
use App\Models\Ward;
use Database\Seeders\ElectionTypeSeeder;
use Database\Seeders\GeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([GeographySeeder::class, ElectionTypeSeeder::class]);
    }

    public function test_role_navigation_routes_resolve_to_existing_pages(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $officer = User::factory()->create([
            'role' => User::ROLE_ELECTION_OFFICER,
        ]);
        $observer = User::factory()->create(['role' => User::ROLE_OBSERVER]);
        $election = $this->createElection(Election::STATUS_ACTIVE);
        $election->assignedOfficers()->attach($officer->id);

        $this->assertRolePages($admin, [
            ['admin.dashboard', 'Admin/Dashboard'],
            ['admin.elections.index', 'Admin/Election/Index'],
            ['admin.import', 'Admin/Import'],
            ['admin.results', 'Admin/Results'],
            ['admin.flagged', 'Admin/Results'],
            ['admin.reports.index', 'Admin/Report'],
            ['admin.audit', 'Admin/AuditLogs'],
            ['admin.users.index', 'Admin/Users/Index'],
        ]);

        $this->assertRolePages($officer, [
            ['officer.dashboard', 'Officer/Dashboard'],
            ['officer.elections', 'Officer/Election'],
            ['officer.results', 'Officer/Results'],
            ['officer.queue', 'Officer/Queue'],
            ['officer.reports.index', 'Admin/Report'],
        ]);

        $this->assertRolePages($observer, [
            ['observer.dashboard', 'Observer/Dashboard'],
            ['observer.elections', 'Observer/Elections'],
            ['observer.results', 'Observer/Results'],
        ]);
    }

    public function test_observer_data_is_published_only_and_unassigned_officer_reports_are_empty(): void
    {
        $observer = User::factory()->create(['role' => User::ROLE_OBSERVER]);
        $officer = User::factory()->create([
            'role' => User::ROLE_ELECTION_OFFICER,
        ]);
        $publishedElection = $this->createElection(Election::STATUS_ACTIVE);
        $draftElection = $this->createElection(Election::STATUS_DRAFT, 2024);
        $publishedResult = $this->createResult($publishedElection, 'PUB/01');
        $draftResult = $this->createResult($draftElection, 'DRF/01');

        $this->actingAs($observer)
            ->get(route('observer.results'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Observer/Results')
                ->has('results.data', 1)
                ->where('results.data.0.id', $publishedResult->id));

        $this->actingAs($observer)
            ->get(route('observer.results.show', $draftResult))
            ->assertNotFound();

        $this->get(route('verify.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Verify')
                ->has('results.data', 1)
                ->where('results.data.0.id', $publishedResult->id));

        $this->actingAs($observer)
            ->get(route('observer.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total', 1));

        $this->actingAs($officer)
            ->get(route('officer.reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.election_summary', 0)
                ->where('stats.invalid_records', 0));

        $this->actingAs($officer)
            ->get(route('officer.reports.export', [
                'report' => 'invalid_records',
            ]))
            ->assertForbidden();
    }

    public function test_public_verify_search_hides_results_when_only_draft_elections_exist(): void
    {
        $draftElection = $this->createElection(Election::STATUS_DRAFT);
        $this->createResult($draftElection, 'DRF/ONLY');

        $this->get(route('verify.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Verify')
                ->has('results.data', 0));
    }

    private function assertRolePages(User $user, array $pages): void
    {
        $this->actingAs($user);

        foreach ($pages as [$routeName, $component]) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component($component));
        }
    }

    private function createElection(string $status, int $year = 2023): Election
    {
        return Election::create([
            'election_type_id' => ElectionType::where('code', ElectionType::PRESIDENTIAL)->value('id'),
            'year' => $year,
            'status' => $status,
        ]);
    }

    private function createResult(Election $election, string $code): ElectionResult
    {
        $bauchi = State::where('code', 'BA')->firstOrFail();
        $localGovernment = LocalGovernment::where('state_id', $bauchi->id)
            ->where('name', 'Alkaleri')->firstOrFail();
        $ward = Ward::firstOrCreate([
            'local_government_id' => $localGovernment->id,
            'name' => 'Ward 1',
        ]);
        $pollingUnit = PollingUnit::create([
            'code' => $code,
            'ward_id' => $ward->id,
            'name' => "Polling Unit {$code}",
        ]);

        $result = ElectionResult::create([
            'election_id' => $election->id,
            'polling_unit_id' => $pollingUnit->id,
            'registered_voters' => 100,
            'accredited_voters' => 60,
            'rejected_votes' => 2,
            'total_votes' => 52,
            'validation_status' => 'FLAGGED_FOR_REVIEW',
            'source_type' => 'REAL',
        ]);

        $party = Party::firstOrCreate(['code' => 'APC'], ['name' => 'APC']);
        ElectionResultCandidateVote::create([
            'election_result_id' => $result->id,
            'party_id' => $party->id,
            'votes' => 20,
        ]);

        return $result;
    }
}
