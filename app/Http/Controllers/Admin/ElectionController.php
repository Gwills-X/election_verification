<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreElectionRequest;
use App\Http\Requests\Admin\UpdateElectionRequest;
use App\Models\Election;
use App\Models\ElectionType;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ElectionController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Admin/Election/Index', [
            'elections' => Election::with('electionType.scopeState')
                ->withCount('results')
                ->withCount('assignedOfficers')
                ->when(
                    $request->status,
                    fn ($query, $value) =>
                        $query->where('status', $value)
                )
                ->orderByDesc('year')
                ->paginate(15)
                ->withQueryString(),

            'filters' => $request->only('status'),

            'statuses' => Election::STATUSES,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Election/Form', [
            'election' => null,
            'electionTypes' => ElectionType::with('scopeState')->orderBy('name')->get(),
        ]);
    }

    public function store(
        StoreElectionRequest $request,
        AuditLogService $audit
    ) {
        $election = Election::create(
            $request->validated()
        );

        $audit->log(
            $request->user()->id,
            'ELECTION_CREATED',
            "Created election '{$election->name}'."
        );

        return redirect()
            ->route(
                'admin.elections.show',
                $election
            )
            ->with(
                'success',
                'Election created.'
            );
    }

    public function show(Election $election)
    {
        return Inertia::render('Admin/Election/Show', [
            'election' => $election->load('electionType.scopeState'),

            'statusCounts' =>
                $election->statusCounts(),

            'candidateTotals' =>
                $election->candidateTotals(),

            'officers' =>
                $election
                    ->assignedOfficers()
                    ->select(
                        'users.id',
                        'users.name',
                        'users.email'
                    )
                    ->get(),

            'imports' =>
                $election
                    ->datasetImports()
                    ->with('user:id,name')
                    ->latest()
                    ->limit(5)
                    ->get(),

            'results' =>
                $election
                    ->results()
                    ->with('pollingUnit.ward.localGovernment')
                    ->latest('id')
                    ->paginate(10),
        ]);
    }

    public function edit(Election $election)
    {
        return Inertia::render('Admin/Election/Form', [
            'election' => $election->load('electionType.scopeState'),
            'electionTypes' => ElectionType::with('scopeState')->orderBy('name')->get(),
        ]);
    }

    public function update(
        UpdateElectionRequest $request,
        Election $election,
        AuditLogService $audit
    ) {
        $election->update(
            $request->validated()
        );

        $audit->log(
            $request->user()->id,
            'ELECTION_UPDATED',
            "Updated election '{$election->name}'."
        );

        return redirect()
            ->route(
                'admin.elections.show',
                $election
            )
            ->with(
                'success',
                'Election updated.'
            );
    }

    public function archive(
        Election $election,
        AuditLogService $audit
    ) {
        $election->update([
            'status' => Election::STATUS_ARCHIVED,
        ]);

        $audit->log(
            request()->user()->id,
            'ELECTION_ARCHIVED',
            "Archived election '{$election->name}'."
        );

        return back()->with(
            'success',
            'Election archived.'
        );
    }

    public function destroy(
        Election $election,
        AuditLogService $audit
    ) {
        if ($election->results()->exists()) {
            return back()->with(
                'error',
                'This election has result records and cannot be deleted. Archive it instead.'
            );
        }

        $audit->log(
            request()->user()->id,
            'ELECTION_DELETED',
            "Deleted election '{$election->name}'."
        );

        $election->delete();

        return redirect()
            ->route('admin.elections.index')
            ->with(
                'success',
                'Election deleted.'
            );
    }
}
