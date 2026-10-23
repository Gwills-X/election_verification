<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\ElectionResult;
use App\Models\LocalGovernment;
use App\Models\State;
use App\Models\Ward;
use App\Services\ElectionGeographyOptions;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicVerificationController extends Controller
{
    public function home()
    {
        $visibleResults = ElectionResult::whereHas(
            'election',
            fn ($query) => $query->publiclyVisible()
        );

        return Inertia::render('Public/Home', [
            'elections' => Election::publiclyVisible()
                ->with('electionType.scopeState')
                ->withCount('results')
                ->latest()
                ->get(),

            'stats' => ElectionResult::statusCounts($visibleResults),
        ]);
    }

    public function index(
        Request $request,
        ElectionGeographyOptions $geographyOptions
    )
    {
        $elections = Election::publiclyVisible()
            ->with('electionType.scopeState')
            ->get([
                'id',
                'name',
                'year',
                'election_type_id',
            ]);

        $filters = $request->only(
            'election_id',
            'state',
            'lga',
            'ward',
            'q'
        );

        $election = !empty($filters['election_id'])
            ? $elections->firstWhere(
                'id',
                (int) $filters['election_id']
            )
            : null;

        $election ??= $elections->first();

        $filters['election_id'] = $election?->id;
        $geography = $geographyOptions->forElections(
            $elections,
            $election,
            $filters
        );
        $filters = array_merge($filters, $geography['filters']);

        return Inertia::render('Public/Verify', [
            'elections' => $elections,

            'election' => $election,

            'states' => $geography['states'],

            'lgas' => $geography['lgas'],

            'wards' => $geography['wards'],

            'results' => ElectionResult::whereHas(
                'election',
                fn ($query) => $query->publiclyVisible()
            )
                ->with('pollingUnit.ward.localGovernment')
                ->filter($filters)
                ->paginate(15)
                ->withQueryString(),

            'filters' => $filters,
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

        return Inertia::render('Public/Show', [
            'result' => $result->load(
                'pollingUnit.ward.localGovernment.state',
                'election.electionType.scopeState',
                'validationResults',
                'reviewer:id,name',
                'candidateVotes.party'
            ),
        ]);
    }

    /**
     * Backwards-compatible legacy results page.
     */
    public function legacyIndex(Request $request)
    {
        $filters = $request->only(
            'q',
            'lga',
            'ward',
            'status'
        );

        return Inertia::render('Results/Index', [
            'results' => ElectionResult::whereHas(
                'election',
                fn ($query) => $query->publiclyVisible()
            )
                ->with('pollingUnit.ward.localGovernment')
                ->filter($filters)
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),

            'filters' => $filters,

            'lgas' => LocalGovernment::whereHas(
                'wards.pollingUnits.results',
                fn ($query) => $query->whereHas(
                    'election',
                    fn ($electionQuery) => $electionQuery->publiclyVisible()
                )
            )->distinct()->orderBy('name')->pluck('name'),

            'wards' => Ward::whereHas(
                'pollingUnits.results',
                fn ($query) => $query->whereHas(
                    'election',
                    fn ($electionQuery) => $electionQuery->publiclyVisible()
                )
            )->distinct()->orderBy('name')->pluck('name'),
        ]);
    }
}
