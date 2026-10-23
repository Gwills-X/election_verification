<?php

use App\Models\DatasetImport;
use App\Models\Election;
use App\Models\ElectionResult;
use App\Models\ValidationResult;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('election:reset-data {--force : Skip confirmation} {--election-id=} ', function () {
    $electionIds = $this->option('election-id')
        ? [ (int) $this->option('election-id') ]
        : Election::query()->pluck('id')->all();

    if (empty($electionIds)) {
        $this->info('No election data to reset. Users and authentication data remain intact.');
        return 0;
    }

    if (! $this->option('force') && ! $this->confirm('This will delete election results, import batches, assignments, and elections while keeping all user accounts and authentication data. Continue?')) {
        $this->warn('Election data reset cancelled.');
        return 0;
    }

    try {
        DB::transaction(function () use ($electionIds) {
            $resultIds = ElectionResult::query()->whereIn('election_id', $electionIds)->pluck('id')->all();

            if (! empty($resultIds)) {
                ValidationResult::query()->whereIn('election_result_id', $resultIds)->delete();
            }

            ElectionResult::query()->whereIn('election_id', $electionIds)->delete();
            DatasetImport::query()->whereIn('election_id', $electionIds)->delete();
            DB::table('election_user')->whereIn('election_id', $electionIds)->delete();
            Election::query()->whereIn('id', $electionIds)->delete();
        });
    } catch (QueryException $exception) {
        $this->error('Election data reset failed: ' . $exception->getMessage());
        return 1;
    }

    $this->info('Election-related data was reset successfully. User accounts and authentication data were not deleted.');
    return 0;
})->purpose('Delete election results and related data while leaving users and authentication intact.');
