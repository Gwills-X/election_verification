<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\ElectionResult;
use App\Services\AuditLogService;
use App\Services\ElectionGeographyOptions;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewController extends Controller
{
    /**
     * Return only results belonging to elections
     * assigned to the current officer.
     */
    private function scoped()
    {
        $ids = request()
            ->user()
            ->assignedElections()
            ->pluck('elections.id');

        return ElectionResult::whereIn(
            'election_id',
            $ids
        );
    }

    public function results(
        Request $request,
        ElectionGeographyOptions $geographyOptions
    )
    {
        $elections = request()->user()->assignedElections()
            ->with('electionType.scopeState')
            ->select('elections.id', 'elections.name', 'elections.election_type_id', 'elections.year')
            ->orderByDesc('year')
            ->get();
        $filters = $request->only(
            'q',
            'lga',
            'ward',
            'status',
            'review',
            'election_id'
        );
        $selectedElection = $elections->firstWhere(
            'id',
            (int) ($filters['election_id'] ?? 0)
        );
        $geography = $geographyOptions->forElections(
            $elections,
            $selectedElection,
            $filters
        );
        $filters = array_merge($filters, $geography['filters']);

        return Inertia::render('Officer/Results', [
            'results' => $this
                ->scoped()
                ->with([
                    'pollingUnit.ward.localGovernment',
                    'election:id,name,election_type_id,year',
                ])
                ->filter($filters)
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),

            'filters' => $filters,

            'elections' => $elections,
            'states' => $geography['states'],
            'lgas' => $geography['lgas'],
            'wards' => $geography['wards'],
        ]);
    }

    public function queue(
        Request $request,
        ElectionGeographyOptions $geographyOptions
    )
    {
        $elections = request()->user()->assignedElections()
            ->with('electionType.scopeState')
            ->select('elections.id', 'elections.name', 'elections.election_type_id', 'elections.year')
            ->orderByDesc('year')
            ->get();
        $filters = $request->only(
            'q',
            'lga',
            'ward',
            'election_id'
        );

        $filters['status'] = 'FLAGGED_FOR_REVIEW';
        $selectedElection = $elections->firstWhere(
            'id',
            (int) ($filters['election_id'] ?? 0)
        );
        $geography = $geographyOptions->forElections(
            $elections,
            $selectedElection,
            $filters
        );
        $filters = array_merge($filters, $geography['filters']);

        return Inertia::render('Officer/Queue', [
            'results' => $this
                ->scoped()
                ->with([
                    'pollingUnit.ward.localGovernment',
                    'election:id,name,election_type_id,year',
                ])
                ->filter($filters)
                ->whereNull('reviewed_at')
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),

            'filters' => $filters,

            'elections' => $elections,
            'states' => $geography['states'],
            'lgas' => $geography['lgas'],
            'wards' => $geography['wards'],
        ]);
    }

    public function show(Request $request, ElectionResult $result)
    {
        abort_unless(
            request()
                ->user()
                ->assignedElections()
                ->where(
                    'elections.id',
                    $result->election_id
                )
                ->exists(),
            403
        );

        return Inertia::render('Officer/Show', [
            'result' => $result->load(
                'pollingUnit.ward.localGovernment.state',
                'election.electionType.scopeState',
                'validationResults',
                'reviewer:id,name',
                'candidateVotes.party'
            ),
            'backUrl' => $request->query('from') === 'results'
                ? route('officer.results')
                : route('officer.queue'),
        ]);
    }

    public function review(
        Request $request,
        ElectionResult $result,
        AuditLogService $audit
    ) {
        abort_unless(
            $request
                ->user()
                ->assignedElections()
                ->where(
                    'elections.id',
                    $result->election_id
                )
                ->exists(),
            403
        );

        $data = $request->validate([
            'review_note' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'decision' => [
                'nullable',
                'string',
                'in:VALID,INVALID,FLAGGED_FOR_REVIEW',
            ],
        ]);

        $decision = $data['decision'] ?? $result->validation_status;

        $result->update([
            'validation_status' => $decision,
            'review_decision' => $decision,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
            'review_note' => $data['review_note'] ?? $result->review_note,
        ]);

        $audit->log(
            $request->user()->id,
            'REVIEW_NOTE_ADDED',
            "Reviewed result #{$result->id} ({$result->pollingUnit?->code}) with decision {$decision}."
        );

        return back()->with(
            'success',
            'Record review submitted.'
        );
    }
}
