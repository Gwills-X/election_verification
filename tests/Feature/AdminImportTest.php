<?php
namespace Tests\Feature;

use App\Models\DatasetImport;
use App\Models\Election;
use App\Models\ElectionResult;
use App\Models\ElectionType;
use App\Models\Party;
use App\Models\User;
use Database\Seeders\ElectionTypeSeeder;
use Database\Seeders\GeographySeeder;
use App\Services\ResultImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Events\QueryExecuted;
use Tests\TestCase;

class AdminImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([GeographySeeder::class, ElectionTypeSeeder::class]);
    }

    private function payload(Election $election, string $csv): array
    {
        return [
            'file' => UploadedFile::fake()->createWithContent('t.csv', $csv),
            'election_id' => $election->id,
            'dataset_type' => 'REAL',
        ];
    }

    private function election(): Election
    {
        return Election::create([
            'election_type_id' => ElectionType::where('code', ElectionType::PRESIDENTIAL)->value('id'),
            'year' => 2023,
            'status' => 'ACTIVE',
        ]);
    }

    private function csvRows(int $offset = 0): string
    {
        return "state,polling_unit_code,polling_unit_name,lga,ward,accredited_voters,apc,pdp,rejected_votes,total_votes\n"
            . "Bauchi,T" . (1 + $offset) . ",PU " . (1 + $offset) . ",Alkaleri,W,500,200,200,10,410\n"
            . "Bauchi,T" . (2 + $offset) . ",PU " . (2 + $offset) . ",Alkaleri,W,500,300,300,10,610\n";
    }

    public function test_guest_is_redirected_to_login(): void { $this->get('/admin/import')->assertRedirect('/login'); }

    public function test_non_admin_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'OBSERVER']))->get('/admin/import')->assertForbidden();
    }

    public function test_successful_import(): void
    {
        $csv = "state,polling_unit_code,polling_unit_name,lga,ward,accredited_voters,apc,pdp,rejected_votes,total_votes\nBauchi,T1,PU 1,Alkaleri,W,500,200,200,10,410\nBauchi,T2,PU 2,Alkaleri,W,500,300,300,10,610\n";
        $this->actingAs(User::factory()->create(['role' => 'ADMIN']))
            ->post('/admin/import', $this->payload($this->election(), $csv))->assertRedirect('/admin/import');
        $this->assertSame(2, ElectionResult::count());
        $this->assertSame(1, ElectionResult::where('validation_status', 'INVALID')->count());
        $this->assertSame(
            '2023 Presidential Election',
            ElectionResult::with('election')->firstOrFail()->election->name
        );
    }

    public function test_large_csv_import_caches_state_scope_lookups(): void
    {
        $columns = [
            'state',
            'polling_unit_code',
            'polling_unit_name',
            'lga',
            'ward',
            'registered_voters',
            'accredited_voters',
            'rejected_votes',
            'total_votes',
            'APC',
            'LP',
            'PDP',
            'NNPP',
        ];
        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, $columns);

        for ($index = 1; $index <= 5000; $index++) {
            fputcsv($stream, [
                'Bauchi',
                sprintf('05-01-01-%04d', $index),
                "Polling Unit {$index}",
                'Alkaleri',
                'Alkaleri',
                500,
                300,
                0,
                300,
                100,
                100,
                100,
                0,
            ]);
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        $path = tempnam(sys_get_temp_dir(), 'large-csv');
        file_put_contents($path, $csv);

        $election = $this->election();
        $user = User::factory()->create();
        $stateLookupCount = 0;
        DB::listen(function (QueryExecuted $query) use (&$stateLookupCount): void {
            if (str_contains(strtolower($query->sql), 'from `states`')) {
                $stateLookupCount++;
            }
        });

        $summary = app(ResultImportService::class)->import(
            $path,
            $election,
            DatasetImport::TYPE_REAL,
            $user->id
        );

        $this->assertSame(5000, $summary['imported']);
        $this->assertSame(5000, ElectionResult::count());
        $this->assertSame(20000, DB::table('election_result_candidate_votes')->count());
        $this->assertLessThanOrEqual(1, $stateLookupCount);
        $this->assertSame(
            DatasetImport::STATUS_COMPLETED,
            DatasetImport::findOrFail($summary['import_id'])->status
        );

        unlink($path);
    }

    public function test_exact_re_import_is_classified_as_existing_records(): void
    {
        $election = $this->election();
        $service = app(ResultImportService::class);
        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, $this->csvRows());

        $first = $service->import($path, $election, 'REAL', User::factory()->create()->id);
        $second = $service->import($path, $election, 'REAL', User::factory()->create()->id);

        $this->assertSame(2, ElectionResult::count());
        $this->assertSame(0, $second['new_records']);
        $this->assertSame(2, $second['existing_records']);
        $this->assertSame(0, $second['conflicts']);
        $this->assertSame(0, $second['invalid']);
        $this->assertSame(DatasetImport::STATUS_ALREADY_IMPORTED, $second['status']);
        $this->assertSame(2, DatasetImport::count());
        unlink($path);
    }

    public function test_overlapping_dataset_tracks_new_and_existing_records(): void
    {
        $election = $this->election();
        $service = app(ResultImportService::class);

        $pathOne = tempnam(sys_get_temp_dir(), 'csv1');
        $pathTwo = tempnam(sys_get_temp_dir(), 'csv2');
        file_put_contents($pathOne, "state,polling_unit_code,polling_unit_name,lga,ward,accredited_voters,apc,pdp,rejected_votes,total_votes\nBauchi,T1,PU 1,Alkaleri,W,500,200,200,10,410\nBauchi,T2,PU 2,Alkaleri,W,500,300,300,10,610\n");
        file_put_contents($pathTwo, "state,polling_unit_code,polling_unit_name,lga,ward,accredited_voters,apc,pdp,rejected_votes,total_votes\nBauchi,T1,PU 1,Alkaleri,W,500,200,200,10,410\nBauchi,T3,PU 3,Alkaleri,W,500,125,125,5,255\n");

        $service->import($pathOne, $election, 'REAL', User::factory()->create()->id);
        $summary = $service->import($pathTwo, $election, 'REAL', User::factory()->create()->id);

        $this->assertSame(3, ElectionResult::count());
        $this->assertSame(1, $summary['new_records']);
        $this->assertSame(1, $summary['existing_records']);
        $this->assertSame(0, $summary['conflicts']);

        unlink($pathOne);
        unlink($pathTwo);
    }

    public function test_conflicting_result_is_flagged_without_overwriting_existing_value(): void
    {
        $election = $this->election();
        $service = app(ResultImportService::class);

        $pathOne = tempnam(sys_get_temp_dir(), 'csv1');
        $pathTwo = tempnam(sys_get_temp_dir(), 'csv2');
        file_put_contents($pathOne, "state,polling_unit_code,polling_unit_name,lga,ward,accredited_voters,apc,pdp,rejected_votes,total_votes\nBauchi,T1,PU 1,Alkaleri,W,500,200,200,10,410\n");
        file_put_contents($pathTwo, "state,polling_unit_code,polling_unit_name,lga,ward,accredited_voters,apc,pdp,rejected_votes,total_votes\nBauchi,T1,PU 1,Alkaleri,W,500,250,200,10,460\n");

        $service->import($pathOne, $election, 'REAL', User::factory()->create()->id);
        $summary = $service->import($pathTwo, $election, 'REAL', User::factory()->create()->id);

        $existing = ElectionResult::first();
        $this->assertSame(1, ElectionResult::count());
        $this->assertSame(0, $summary['new_records']);
        $this->assertSame(0, $summary['existing_records']);
        $this->assertSame(1, $summary['conflicts']);
        $this->assertSame('FLAGGED_FOR_REVIEW', $existing->validation_status);
        $apc = Party::where('code', 'APC')->firstOrFail();
        $this->assertSame(
            200,
            (int) $existing->candidateVotes()->where('party_id', $apc->id)->value('votes')
        );

        unlink($pathOne);
        unlink($pathTwo);
    }

    public function test_invalid_csv_is_rejected(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'ADMIN']))
            ->post('/admin/import', $this->payload($this->election(), "foo,bar\n1,2\n"))->assertSessionHasErrors('file');
        $this->assertSame(0, ElectionResult::count());
    }

    public function test_governorship_import_rejects_rows_outside_bauchi(): void
    {
        $election = Election::create([
            'election_type_id' => ElectionType::where('code', ElectionType::GOVERNORSHIP)->value('id'),
            'year' => 2023,
            'status' => Election::STATUS_ACTIVE,
        ]);
        $csv = "state,polling_unit_code,polling_unit_name,lga,ward,accredited_voters,apc\n"
            . "Lagos,LAG/01,PU 1,Alimosho,Ward 1,300,120\n";

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->post('/admin/import', $this->payload($election, $csv))
            ->assertSessionHasErrors('file');

        $this->assertSame(0, ElectionResult::count());
        $this->assertSame(0, DatasetImport::count());
    }

    public function test_election_data_reset_keeps_users_and_removes_election_records(): void
    {
        $user = User::factory()->create(['role' => 'ADMIN']);
        $this->actingAs($user);

        $election = $this->election();
        $election->assignedOfficers()->attach($user->id);

        $csv = "state,polling_unit_code,polling_unit_name,lga,ward,accredited_voters,apc,pdp,rejected_votes,total_votes\nBauchi,T1,PU 1,Alkaleri,W,500,200,200,10,410\n";
        $this->post('/admin/import', $this->payload($election, $csv));

        Artisan::call('election:reset-data', ['--force' => true]);

        $this->assertSame(1, User::count());
        $this->assertSame(0, Election::count());
        $this->assertSame(0, ElectionResult::count());
        $this->assertSame(0, DatasetImport::count());
    }
}
