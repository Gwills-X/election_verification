<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionResult;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Results belonging only to elections assigned
     * to the currently authenticated officer.
     */
    private function base()
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

    public function index()
    {
        $user = request()->user();

        $base = $this->base();

        return Inertia::render('Officer/Dashboard', [
            'assignedElections' => $user
                ->assignedElections()
                ->with('electionType.scopeState')
                ->withCount('results')
                ->get(),

            'awaitingReview' => (clone $base)
                ->where(
                    'validation_status',
                    'FLAGGED_FOR_REVIEW'
                )
                ->whereNull('reviewed_at')
                ->count(),

            'flagged' => (clone $base)
                ->where(
                    'validation_status',
                    'FLAGGED_FOR_REVIEW'
                )
                ->count(),

            'reviewedByMe' => (clone $base)
                ->where(
                    'reviewed_by',
                    $user->id
                )
                ->count(),

            'reviewedTotal' => (clone $base)
                ->whereNotNull('reviewed_at')
                ->count(),
        ]);
    }

    public function elections()
    {
        return Inertia::render('Officer/Election', [
            'elections' => request()
                ->user()
                ->assignedElections()
                ->with('electionType.scopeState')
                ->withCount('results')
                ->get(),
        ]);
    }

    public function electionShow(Election $election)
    {
        abort_unless(
            request()
                ->user()
                ->assignedElections()
                ->where('elections.id', $election->id)
                ->exists(),
            403
        );

        return Inertia::render('Officer/ElectionShow', [
            'election' => $election->load('electionType.scopeState'),
            'statusCounts' => $election->statusCounts(),
            'candidateTotals' => $election->candidateTotals(),
        ]);
    }
}
