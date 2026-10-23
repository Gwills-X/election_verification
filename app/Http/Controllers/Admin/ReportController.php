<?php

namespace App\Http\Controllers\Admin;

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
    public function index(Request $request)
    {
        $election = $request->election_id
            ? Election::find($request->election_id)
            : Election::whereIn(
                'status',
                [
                    Election::STATUS_ACTIVE,
                    Election::STATUS_COMPLETED,
                ]
            )
                ->latest()
                ->first();

        $stats = [
            'election_summary' =>
                $election?->results()->count() ?? 0,

            'validation_summary' =>
                ValidationResult::when(
                    $election,
                    fn ($query) =>
                        $query->whereHas(
                            'result',
                            fn ($resultQuery) =>
                                $resultQuery->where(
                                    'election_id',
                                    $election->id
                                )
                        )
                )->count(),

            'invalid_records' =>
                ElectionResult::when(
                    $election,
                    fn ($query) =>
                        $query->where(
                            'election_id',
                            $election->id
                        )
                )
                    ->where(
                        'validation_status',
                        'INVALID'
                    )
                    ->count(),

            'flagged_records' =>
                ElectionResult::when(
                    $election,
                    fn ($query) =>
                        $query->where(
                            'election_id',
                            $election->id
                        )
                )
                    ->where(
                        'validation_status',
                        'FLAGGED_FOR_REVIEW'
                    )
                    ->count(),

            'reviewed_records' =>
                ElectionResult::when(
                    $election,
                    fn ($query) =>
                        $query->where(
                            'election_id',
                            $election->id
                        )
                )
                    ->whereNotNull('reviewed_at')
                    ->count(),
        ];

        return Inertia::render('Admin/Report', [
            'reports' => ReportService::REPORTS,

            'elections' => Election::select('id', 'name', 'election_type_id', 'year')
                ->orderByDesc('year')
                ->get(),

            'election' => $election?->load('electionType.scopeState'),

            'stats' => $stats,
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

        $election = !empty($data['election_id'])
            ? Election::find($data['election_id'])
            : null;

        $audit->log(
            $request->user()->id,
            'REPORT_GENERATED',
            'Generated ' .
                ReportService::REPORTS[$data['report']] .
                ' report' .
                ($election
                    ? " for '{$election->name}'."
                    : '.')
        );

        return $service->streamCsv(
            $data['report'],
            $election
        );
    }
}
