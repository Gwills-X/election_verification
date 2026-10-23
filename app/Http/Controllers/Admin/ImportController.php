<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportResultRequest;
use App\Models\Election;
use App\Services\AuditLogService;
use App\Services\ResultImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use InvalidArgumentException;
use Throwable;

class ImportController extends Controller
{
    public function index(Request $request)
    {
        $elections = Election::with('electionType.scopeState')
            ->orderByDesc('year')
            ->get([
                'id',
                'name',
                'year',
                'election_type_id',
                'status',
            ]);
        $selectedElectionId = $elections->firstWhere(
            'id',
            $request->integer('election_id')
        )?->id ?? $elections->first()?->id;

        return Inertia::render('Admin/Import', [
            'elections' => $elections,
            'selectedElectionId' => $selectedElectionId,

            'summary' => session('summary'),
        ]);
    }

    public function store(
        ImportResultRequest $request,
        ResultImportService $importer,
        AuditLogService $audit
    ) {
        $election = Election::findOrFail(
            $request->election_id
        );

        abort_unless(
            $election->isEditable(),
            422,
            'This election is not open for data upload.'
        );

        $file = $request->file('file');

        $audit->log(
            $request->user()->id,
            'DATASET_UPLOADED',
            "Uploaded {$file->getClientOriginalName()} to '{$election->name}'."
        );

        try {
            $summary = $importer->import(
    $file->getRealPath(),
    $election,
    $request->dataset_type,
    $request->user()->id,
    $request->election_result_type
);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors([
                'file' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            Log::error(
                'Election dataset import failed.',
                [
                    'election_id' => $election->id,
                    'user_id' => $request->user()->id,
                    'exception' => $e,
                ]
            );

            return back()->withErrors([
                'file' =>
                    'The import failed unexpectedly. No records were saved.',
            ]);
        }

        $audit->log(
            $request->user()->id,
            'DATASET_IMPORTED',
            "Imported {$summary['imported']}/{$summary['found']} records for '{$election->name}' "
            . "({$summary['valid']} valid, {$summary['invalid']} invalid, "
            . "{$summary['flagged']} flagged)."
        );

        return redirect()
            ->route('admin.import')
            ->with('summary', $summary);
    }
}
