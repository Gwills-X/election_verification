<?php

namespace Tests\Feature;

use App\Models\DatasetImport;
use App\Models\Election;
use App\Models\ElectionResult;
use App\Models\ElectionResultCandidateVote;
use App\Models\ElectionType;
use App\Models\LocalGovernment;
use App\Models\Party;
use App\Models\PollingUnit;
use App\Models\State;
use App\Models\User;
use App\Models\ValidationResult;
use App\Models\Ward;
use Database\Seeders\ElectionSeeder;
use Database\Seeders\ElectionTypeSeeder;
use Database\Seeders\GeographySeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ElectionStructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([GeographySeeder::class, ElectionTypeSeeder::class]);
    }

    public function test_only_the_two_supported_election_types_are_seeded(): void
    {
        $this->assertSame(2, ElectionType::count());
        $codes = ElectionType::pluck('code')->all();
        sort($codes);
        $this->assertSame(['GOVERNORSHIP', 'PRESIDENTIAL'], $codes);
    }

    public function test_admin_creates_generated_presidential_and_governorship_names(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post(route('admin.elections.store'), [
                'election_type_id' => $this->typeId(ElectionType::PRESIDENTIAL),
                'year' => 2023,
            ])
            ->assertSessionHasNoErrors();

        $presidential = Election::where('election_type_id', $this->typeId(ElectionType::PRESIDENTIAL))
            ->where('year', 2023)
            ->firstOrFail();
        $this->assertSame('2023 Presidential Election', $presidential->name);

        $this->actingAs($admin)
            ->post(route('admin.elections.store'), [
                'election_type_id' => $this->typeId(ElectionType::GOVERNORSHIP),
                'year' => 2023,
            ])
            ->assertSessionHasNoErrors();

        $governorship = Election::where('election_type_id', $this->typeId(ElectionType::GOVERNORSHIP))
            ->where('year', 2023)
            ->firstOrFail();
        $this->assertSame('2023 Governorship Election — Bauchi', $governorship->name);
    }

    public function test_duplicate_type_and_year_elections_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        foreach ([ElectionType::PRESIDENTIAL, ElectionType::GOVERNORSHIP] as $typeCode) {
            $payload = [
                'election_type_id' => $this->typeId($typeCode),
                'year' => 2023,
            ];

            $this->actingAs($admin)
                ->post(route('admin.elections.store'), $payload)
                ->assertSessionHasNoErrors();

            $this->actingAs($admin)
                ->post(route('admin.elections.store'), $payload)
                ->assertSessionHasErrors('year');
        }
    }

    public function test_presidential_scope_is_national_and_governorship_scope_is_bauchi(): void
    {
        $presidentialType = ElectionType::where('code', ElectionType::PRESIDENTIAL)->firstOrFail();
        $governorshipType = ElectionType::with('scopeState')
            ->where('code', ElectionType::GOVERNORSHIP)
            ->firstOrFail();

        $this->assertSame('NATIONAL', $presidentialType->scope);
        $this->assertNull($presidentialType->scope_state_id);
        $this->assertSame('STATE', $governorshipType->scope);
        $this->assertSame('Bauchi', $governorshipType->scopeState->name);
    }

    public function test_location_filters_follow_election_scope_and_cascade_without_result_rows(): void
    {
        $presidential = $this->createElection(ElectionType::PRESIDENTIAL, 2023);
        $governorship = $this->createElection(ElectionType::GOVERNORSHIP, 2023);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $officer = User::factory()->create([
            'role' => User::ROLE_ELECTION_OFFICER,
        ]);
        $governorship->assignedOfficers()->attach($officer->id);

        $this->get(route('verify.index', ['election_id' => $presidential->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Verify')
                ->has('states', 37)
                ->where('states.0', 'Abia')
                ->has('results.data', 0));

        $this->get(route('verify.index', ['election_id' => $governorship->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Verify')
                ->has('states', 1)
                ->where('states.0', 'Bauchi')
                ->has('lgas', 20));

        $this->actingAs($admin)
            ->get(route('admin.results', ['election_id' => $governorship->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('states', 1)
                ->where('states.0', 'Bauchi')
                ->has('lgas', 20));

        $this->actingAs($officer)
            ->get(route('officer.results', ['election_id' => $governorship->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('states', 1)
                ->where('states.0', 'Bauchi')
                ->has('lgas', 20));

        $this->pollingUnit('BA', 'BA/FILTER/001', 'Alkaleri', 'Ward 1');

        $this->get(route('verify.index', [
            'election_id' => $presidential->id,
            'state' => 'Bauchi',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('states', 37)
                ->has('lgas', 20)
                ->where('filters.state', 'Bauchi'));

        $this->get(route('verify.index', [
            'election_id' => $presidential->id,
            'state' => 'Bauchi',
            'lga' => 'Alkaleri',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('lgas', 20)
                ->has('wards', 1)
                ->where('wards.0', 'Ward 1'));
    }

    public function test_result_uniqueness_is_per_election_and_party_votes_belong_to_a_result(): void
    {
        $presidential = $this->createElection(ElectionType::PRESIDENTIAL, 2023);
        $governorship = $this->createElection(ElectionType::GOVERNORSHIP, 2023);
        $pollingUnit = $this->pollingUnit('BA', 'BA/01/01/001', 'Alkaleri', 'Ward 1');

        $presidentialResult = $this->createResult($presidential, $pollingUnit);
        $governorshipResult = $this->createResult($governorship, $pollingUnit);

        $this->assertSame(2, ElectionResult::where('polling_unit_id', $pollingUnit->id)->count());

        try {
            $this->createResult($presidential, $pollingUnit);
            $this->fail('A second result in the same election should violate the unique constraint.');
        } catch (QueryException) {
            $this->assertSame(2, ElectionResult::count());
        }

        $party = Party::create(['name' => 'Labour Party', 'code' => 'LP']);
        $vote = ElectionResultCandidateVote::create([
            'election_result_id' => $presidentialResult->id,
            'party_id' => $party->id,
            'votes' => 12,
        ]);

        $this->assertSame($presidentialResult->id, $vote->electionResult->id);
        $this->assertSame($governorshipResult->id, $governorshipResult->fresh()->id);
        $this->assertSame(12, (int) $presidentialResult->candidateVotes()->value('votes'));
    }

    public function test_presidential_accepts_any_nigerian_state_but_governorship_rejects_non_bauchi(): void
    {
        $presidential = $this->createElection(ElectionType::PRESIDENTIAL, 2023);
        $governorship = $this->createElection(ElectionType::GOVERNORSHIP, 2023);
        $kanoUnit = $this->pollingUnit('KN', 'KN/01/01/001', 'Ajingi', 'Ward 1');

        $this->createResult($presidential, $kanoUnit);

        try {
            $this->createResult($governorship, $kanoUnit);
            $this->fail('A Governorship result outside Bauchi should be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('geographic scope', $exception->getMessage());
        }

        $this->assertSame(1, ElectionResult::count());
    }

    public function test_2023_test_elections_are_seeded_without_fake_results(): void
    {
        $this->seed(ElectionSeeder::class);

        $this->assertSame(2, Election::count());
        $this->assertSame(0, ElectionResult::count());
        $this->assertSame(
            [
                '2023 Governorship Election — Bauchi',
                '2023 Presidential Election',
            ],
            Election::orderBy('name')->pluck('name')->all()
        );
    }

    public function test_reseeding_is_idempotent_and_does_not_overwrite_existing_users(): void
    {
        $passwordHash = Hash::make('existing-user-password');
        $existingOfficer = User::create([
            'name' => 'Keep Existing Account',
            'email' => 'officer@example.com',
            'password' => $passwordHash,
            'role' => User::ROLE_OBSERVER,
            'is_active' => true,
        ]);

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->assertSame(3, User::count());
        $this->assertSame(37, State::count());
        $this->assertSame(20, LocalGovernment::where('state_id', State::where('code', 'BA')->value('id'))->count());
        $this->assertSame(2, ElectionType::count());
        $this->assertSame(2, Election::count());
        $this->assertSame(0, ElectionResult::count());
        $this->assertSame('Keep Existing Account', $existingOfficer->fresh()->name);
        $this->assertSame(User::ROLE_OBSERVER, $existingOfficer->fresh()->role);
        $this->assertSame($passwordHash, $existingOfficer->fresh()->password);
    }

    public function test_election_reset_keeps_users_roles_and_authentication_tables(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $election = $this->createElection(ElectionType::PRESIDENTIAL, 2023);
        $pollingUnit = $this->pollingUnit('BA', 'BA/RESET/001', 'Alkaleri', 'Ward 1');
        $result = $this->createResult($election, $pollingUnit);
        $election->assignedOfficers()->attach($user->id);
        DatasetImport::create([
            'election_id' => $election->id,
            'user_id' => $user->id,
            'filename' => 'test.csv',
        ]);
        ValidationResult::create([
            'election_result_id' => $result->id,
            'status' => 'VALID',
            'validation_rule' => 'TEST',
            'validation_message' => 'Test validation record.',
        ]);

        Artisan::call('election:reset-data', ['--force' => true]);

        $this->assertSame(1, User::count());
        $this->assertSame(User::ROLE_ADMIN, $user->fresh()->role);
        $this->assertSame(0, Election::count());
        $this->assertSame(0, ElectionResult::count());
        $this->assertSame(0, DatasetImport::count());
        $this->assertSame(0, ValidationResult::count());
        $this->assertSame(2, ElectionType::count());
        $this->assertTrue(Schema::hasTable('sessions'));
        $this->assertTrue(Schema::hasTable('password_reset_tokens'));
    }

    private function typeId(string $code): int
    {
        return (int) ElectionType::where('code', $code)->value('id');
    }

    private function createElection(string $typeCode, int $year): Election
    {
        return Election::create([
            'election_type_id' => $this->typeId($typeCode),
            'year' => $year,
            'status' => Election::STATUS_ACTIVE,
        ]);
    }

    private function pollingUnit(
        string $stateCode,
        string $code,
        string $lgaName,
        string $wardName
    ): PollingUnit {
        $state = State::where('code', $stateCode)->firstOrFail();
        $localGovernment = LocalGovernment::firstOrCreate([
            'state_id' => $state->id,
            'name' => $lgaName,
        ]);
        $ward = Ward::firstOrCreate([
            'local_government_id' => $localGovernment->id,
            'name' => $wardName,
        ]);

        return PollingUnit::create([
            'ward_id' => $ward->id,
            'code' => $code,
            'name' => "Polling Unit {$code}",
        ]);
    }

    private function createResult(Election $election, PollingUnit $pollingUnit): ElectionResult
    {
        return ElectionResult::create([
            'election_id' => $election->id,
            'polling_unit_id' => $pollingUnit->id,
            'registered_voters' => 100,
            'accredited_voters' => 60,
            'rejected_votes' => 2,
            'total_votes' => 52,
            'validation_status' => 'VALID',
            'source_type' => 'REAL',
        ]);
    }
}
