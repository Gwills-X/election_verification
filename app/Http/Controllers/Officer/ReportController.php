<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionResult;
use App\Models\ValidationResult;
use App\Services\AuditLogService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ReportController extends Controller
{
    private function assignedElectionIds()
    {
        return request()
            ->user()
            ->assignedElections()
            ->pluck('elections.id');
    }

    private function electionFromRequest(Request $request): ?Election
    {
        if (!$request->filled('election_id')) {
            return request()
                ->user()
                ->assignedElections()
                ->latest('year')
                ->first();
        }

        return request()
            ->user()
            ->assignedElections()
            ->where(
                'elections.id',
                $request->election_id
            )
            ->firstOrFail();
    }

    public function index(Request $request)
    {
        $assignedElectionIds = $this->assignedElectionIds();
        $election = $this->electionFromRequest($request);
        $results = ElectionResult::whereIn(
            'election_id',
            $assignedElectionIds
        )->when(
            $election,
            fn ($query) => $query->where('election_id', $election->id)
        );

        $stats = [
            'election_summary' =>
                (clone $results)->count(),

            'validation_summary' =>
                ValidationResult::whereHas(
                    'result',
                    fn ($resultQuery) => $resultQuery
                        ->whereIn('election_id', $assignedElectionIds)
                        ->when(
                            $election,
                            fn ($query) => $query->where('election_id', $election->id)
                        )
                )->count(),

            'invalid_records' =>
                (clone $results)
                    ->where('validation_status', 'INVALID')
                    ->count(),

            'flagged_records' =>
                (clone $results)
                    ->where(
                        'validation_status',
                        'FLAGGED_FOR_REVIEW'
                    )
                    ->count(),

            'reviewed_records' =>
                (clone $results)
                    ->whereNotNull('reviewed_at')
                    ->count(),
        ];

        return Inertia::render('Admin/Report', [
            'reports' => ReportService::REPORTS,

            'elections' => request()
                ->user()
                ->assignedElections()
                ->select(
                    'elections.id',
                    'elections.name'
                )
                ->orderByDesc('year')
                ->get(),

            'election' => $election,

            'stats' => $stats,

            'exportRoute' => 'officer.reports.export',
        ]);
    }

    public function export(
        Request $request,
        ReportService $service,
        AuditLogService $audit
    ) {
        $data = $request->validate([
            'report' => [
                'required',
                Rule::in(
                    array_keys(
                        ReportService::REPORTS
                    )
                ),
            ],

            'election_id' => [
                'nullable',
                'exists:elections,id',
            ],
        ]);

        $election = $this->electionFromRequest(
            $request
        );
        abort_unless($election, 403);

        $audit->log(
            $request->user()->id,
            'REPORT_GENERATED',
            "Generated {$data['report']} report for '{$election?->name}'."
        );

        return $service->streamCsv(
            $data['report'],
            $election
        );
    }
}
