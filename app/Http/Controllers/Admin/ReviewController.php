<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionResult;
use App\Services\AuditLogService;
use App\Services\ElectionGeographyOptions;
use Illuminate\Http\Request;
use Inertia\Inertia;
class ReviewController extends Controller
{
    public function results(Request $request, ElectionGeographyOptions $geographyOptions)
    {
        return $this->list($request, 'All results', null, $geographyOptions);
    }

    public function flagged(Request $request, ElectionGeographyOptions $geographyOptions)
    {
        return $this->list(
            $request,
            'Flagged records',
            'FLAGGED_FOR_REVIEW',
            $geographyOptions
        );
    }

    private function list(
        Request $request,
        string $title,
        ?string $forced,
        ElectionGeographyOptions $geographyOptions
    )
    {
        $f = $request->only(
            'q',
            'election_id',
            'state',
            'lga',
            'ward',
            'status',
            'review'
        );
        if ($forced) $f['status'] = $forced;
        $elections = Election::with('electionType.scopeState')
            ->select('id', 'name', 'election_type_id', 'year')
            ->orderByDesc('year')
            ->get();
        $selectedElection = $elections->firstWhere(
            'id',
            (int) ($f['election_id'] ?? 0)
        );
        $geography = $geographyOptions->forElections(
            $elections,
            $selectedElection,
            $f
        );
        $f = array_merge($f, $geography['filters']);

        return Inertia::render('Admin/Results', [
            'title' => $title,
            'routeName' => $forced ? 'admin.flagged' : 'admin.results',
            'results' => ElectionResult::with([
                'pollingUnit.ward.localGovernment',
                'election.electionType.scopeState',
            ])->filter($f)->latest('id')->paginate(20)->withQueryString(),
            'filters' => $f,
            'elections' => $elections,
            'states' => $geography['states'],
            'lgas' => $geography['lgas'],
            'wards' => $geography['wards'],
        ]);
    }

    public function show(Request $request, ElectionResult $result)
    {
        return Inertia::render('Admin/Show', [
            'result' => $result->load(
                'pollingUnit.ward.localGovernment.state',
                'election.electionType.scopeState',
                'validationResults',
                'reviewer:id,name',
                'candidateVotes.party'
            ),
            'backUrl' => $request->query('from') === 'flagged'
                ? route('admin.flagged')
                : route('admin.results'),
            'flash' => session('success'),
        ]);
    }

    public function review(Request $request, ElectionResult $result, AuditLogService $audit)
    {
        $data = $request->validate([
            'review_note' => ['nullable', 'string', 'max:2000'],
            'decision' => ['nullable', 'string', 'in:VALID,INVALID,FLAGGED_FOR_REVIEW'],
        ]);

        $decision = $data['decision'] ?? $result->validation_status;

        $result->update([
            'validation_status' => $decision,
            'review_decision' => $decision,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
            'review_note' => $data['review_note'] ?? $result->review_note,
        ]);

        $audit->log($request->user()->id, 'RECORD_REVIEWED', "Marked result #{$result->id} as reviewed with decision {$decision}.");
        return back()->with('success', 'Record review submitted.');
    }
}
