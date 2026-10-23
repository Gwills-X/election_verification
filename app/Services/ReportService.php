<?php
namespace App\Services;

use App\Models\Election;
use App\Models\ElectionResult;
use App\Models\ValidationResult;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    public const REPORTS = [
        'election_summary' => 'Election Summary',
        'validation_summary' => 'Validation Summary',
        'invalid_records' => 'Invalid Records',
        'flagged_records' => 'Flagged Records',
        'reviewed_records' => 'Reviewed Records',
    ];

    public function streamCsv(string $report, ?Election $election): StreamedResponse
    {
        [$headers, $rows] = $this->build($report, $election);
        $name = $report . '-' . now()->format('Y-m-d-His') . '.csv';
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) fputcsv($out, $row);
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv']);
    }

    private function build(string $report, ?Election $election): array
    {
        return match ($report) {
            'election_summary' => $this->electionSummary($election),
            'validation_summary' => $this->validationSummary($election),
            'invalid_records' => $this->records($election, 'INVALID'),
            'flagged_records' => $this->records($election, 'FLAGGED_FOR_REVIEW'),
            'reviewed_records' => $this->records($election, null, true),
            default => [[], []],
        };
    }

    private function electionSummary(?Election $only): array
    {
        $headers = ['Election', 'Type', 'Year', 'Scope', 'Status', 'Result Records', 'Valid', 'Invalid', 'Flagged', 'Pending', 'Reviewed', 'Last Import'];
        $rows = [];
        $elections = $only
            ? collect([$only->load('electionType.scopeState')])
            : Election::with('electionType.scopeState')
                ->withCount('results')
                ->orderByDesc('year')
                ->get();
        foreach ($elections as $e) {
            $c = $e->statusCounts();
            $type = $e->electionType;
            $scope = $type->scope === 'NATIONAL'
                ? 'National'
                : $type->scopeState?->name;
            $rows[] = [$e->name, $type->name, $e->year, $scope, $e->status,
                $e->results_count ?? $e->results()->count(), $c['valid'], $c['invalid'], $c['flagged'], $c['pending'],
                $e->results()->whereNotNull('reviewed_at')->count(), optional($e->datasetImports()->latest()->first())->created_at];
        }
        return [$headers, $rows];
    }

    private function validationSummary(?Election $election): array
    {
        $headers = ['Section', 'Item', 'Records'];
        $scope = $election ? $election->name : 'All elections';
        $rows = [];
        $counts = $election ? $election->statusCounts() : ElectionResult::statusCounts();
        foreach (['valid' => 'Valid', 'invalid' => 'Invalid', 'flagged' => 'Flagged for Review', 'pending' => 'Pending'] as $k => $label) {
            $rows[] = [$scope, 'Status: ' . $label, $counts[$k]];
        }
        $ruleCounts = ValidationResult::when($election, fn ($q) => $q->whereHas('result', fn ($r) => $r->where('election_id', $election->id)))
            ->selectRaw('validation_rule, status, count(*) as n')->groupBy('validation_rule', 'status')->get();
        foreach ($ruleCounts as $r) {
            $rows[] = [$scope, "Rule: {$r->validation_rule} ({$r->status})", $r->n];
        }
        return [$headers, $rows];
    }

    private function records(?Election $election, ?string $status, bool $reviewed = false): array
    {
        $headers = ['Election', 'PU Code', 'PU Name', 'State', 'LGA', 'Ward', 'Registered', 'Accredited', 'Rejected', 'Total', 'Candidate / Party Votes', 'Validation Status', 'Source', 'Reviewed At', 'Reviewed By', 'Review Note'];
        $results = ElectionResult::with([
            'pollingUnit.ward.localGovernment.state',
            'election.electionType.scopeState',
            'reviewer',
            'candidateVotes.party',
        ])
            ->when($election, fn ($q) => $q->where('election_id', $election->id))
            ->when($status, fn ($q) => $q->where('validation_status', $status))
            ->when($reviewed, fn ($q) => $q->whereNotNull('reviewed_at'))
            ->latest('id')->get();
        $rows = [];
        foreach ($results as $r) {
            $pu = $r->pollingUnit;
            $lga = $pu?->ward?->localGovernment;
            $votes = $r->candidateVotes->mapWithKeys(fn ($vote) => [
                $vote->party?->code ?? $vote->party?->name => $vote->votes,
            ])->all();
            $rows[] = [$r->election?->name, $pu?->code, $pu?->name, $lga?->state?->name, $lga?->name, $pu?->ward?->name,
                $r->registered_voters, $r->accredited_voters, $r->rejected_votes, $r->total_votes,
                json_encode($votes), $r->validation_status, $r->source_type,
                optional($r->reviewed_at)->toDateTimeString(), $r->reviewer?->name, $r->review_note];
        }
        return [$headers, $rows];
    }
}
