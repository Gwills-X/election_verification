<?php
namespace App\Http\Controllers;
use App\Models\Election;
use App\Models\ElectionResult;
use App\Models\LocalGovernment;
use App\Models\Ward;
use Illuminate\Http\Request;
use Inertia\Inertia;
class ResultController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->only('q', 'lga', 'ward', 'status');
        return Inertia::render('Results/Index', [
            'results' => ElectionResult::whereHas(
                'election',
                fn ($query) => $query->publiclyVisible()
            )->with('pollingUnit.ward.localGovernment')->filter($f)->latest('id')->paginate(20)->withQueryString(),
            'filters' => $f,
            'lgas' => LocalGovernment::whereHas('wards.pollingUnits.results.election', fn ($query) => $query->publiclyVisible())
                ->distinct()->orderBy('name')->pluck('name'),
            'wards' => Ward::whereHas('pollingUnits.results.election', fn ($query) => $query->publiclyVisible())
                ->distinct()->orderBy('name')->pluck('name'),
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

        return Inertia::render('Results/Show', ['result' => $result->load(
            'pollingUnit.ward.localGovernment.state',
            'election.electionType.scopeState',
            'validationResults',
            'candidateVotes.party'
        )]);
    }
}
