<?php

namespace App\Http\Controllers\Observer;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionResult;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $visibleResults = ElectionResult::whereHas(
            'election',
            fn ($query) => $query->publiclyVisible()
        );

        return Inertia::render('Observer/Dashboard', [
            'elections' => Election::publiclyVisible()
                ->with('electionType.scopeState')
                ->withCount('results')
                ->latest()
                ->get(),

            'stats' => ElectionResult::statusCounts($visibleResults),
        ]);
    }

    public function elections()
    {
        return Inertia::render('Observer/Elections', [
            'elections' => Election::publiclyVisible()
                ->with('electionType.scopeState')
                ->withCount('results')
                ->latest()
                ->get(),
        ]);
    }

    public function electionShow(Election $election)
    {
        abort_unless(
            in_array($election->status, [
                Election::STATUS_ACTIVE,
                Election::STATUS_COMPLETED,
            ], true),
            404
        );

        return Inertia::render('Observer/ElectionShow', [
            'election' => $election->load('electionType.scopeState'),
            'statusCounts' => $election->statusCounts(),
            'candidateTotals' => $election->candidateTotals(),
        ]);
    }

    public function results(Request $request)
    {
        $filters = $request->only(
            'q',
            'lga',
            'ward',
            'status',
            'election_id'
        );

        return Inertia::render('Observer/Results', [
            'results' => ElectionResult::whereHas(
                'election',
                fn ($query) => $query->publiclyVisible()
            )->with([
                'pollingUnit.ward.localGovernment',
                'election:id,name,election_type_id,year',
            ])
                ->filter($filters)
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),

            'filters' => $filters,

            'elections' => Election::publiclyVisible()
                ->with('electionType.scopeState')
                ->select('id', 'name', 'election_type_id', 'year')
                ->get(),
        ]);
    }

    public function show(ElectionResult $result)
    {
        abort_unless(
            in_array($result->election?->status, [
                Election::STATUS_ACTIVE,
                Election::STATUS_COMPLETED,
            ], true),
            404
        );

        return Inertia::render('Observer/Show', [
            'result' => $result->load(
                'pollingUnit.ward.localGovernment.state',
                'election.electionType.scopeState',
                'validationResults',
                'reviewer:id,name',
                'candidateVotes.party'
            ),
        ]);
    }
}
