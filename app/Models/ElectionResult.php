<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class ElectionResult extends Model
{
    protected $guarded = [];
    protected $casts = ['reviewed_at' => 'datetime'];

    protected static function booted(): void
    {
        static::saving(function (ElectionResult $result): void {
            if (!$result->election_id || !$result->polling_unit_id) {
                return;
            }

            $election = Election::with('electionType')
                ->findOrFail($result->election_id);
            $pollingUnit = PollingUnit::with('ward.localGovernment')
                ->findOrFail($result->polling_unit_id);

            if (!$election->allowsPollingUnit($pollingUnit)) {
                throw new InvalidArgumentException(
                    'The polling unit is outside this election type geographic scope.'
                );
            }
        });
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function pollingUnit(): BelongsTo
    {
        return $this->belongsTo(PollingUnit::class);
    }

    public function validationResults(): HasMany
    {
        return $this->hasMany(ValidationResult::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function datasetImport(): BelongsTo
    {
        return $this->belongsTo(DatasetImport::class);
    }

    public function candidateVotes(): HasMany
    {
        return $this->hasMany(ElectionResultCandidateVote::class);
    }

    public function scopeFilter(Builder $q, array $f): Builder
    {
        return $q
            ->when($f['q'] ?? null, fn ($q, $v) => $q->whereHas('pollingUnit', fn ($p) => $p->where('code', 'like', "%{$v}%")->orWhere('name', 'like', "%{$v}%")))
            ->when($f['election_id'] ?? null, fn ($q, $v) => $q->where('election_id', $v))
            ->when($f['state'] ?? null, fn ($q, $v) => $q->whereHas('pollingUnit.ward.localGovernment.state', fn ($state) => $state->where('name', $v)))
            ->when($f['lga'] ?? null, fn ($q, $v) => $q->whereHas('pollingUnit.ward.localGovernment', fn ($lga) => $lga->where('name', $v)))
            ->when($f['ward'] ?? null, fn ($q, $v) => $q->whereHas('pollingUnit.ward', fn ($ward) => $ward->where('name', $v)))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('validation_status', $v))
            ->when(($f['review'] ?? null) === 'reviewed', fn ($q) => $q->whereNotNull('reviewed_at'))
            ->when(($f['review'] ?? null) === 'unreviewed', fn ($q) => $q->whereNull('reviewed_at'));
    }

    public static function statusCounts(?Builder $query = null): array
    {
        $c = ($query ?? static::query())->selectRaw('validation_status, count(*) as n')->groupBy('validation_status')->pluck('n', 'validation_status');
        return ['total' => (int) $c->sum(), 'valid' => (int) ($c['VALID'] ?? 0), 'invalid' => (int) ($c['INVALID'] ?? 0),
                'flagged' => (int) ($c['FLAGGED_FOR_REVIEW'] ?? 0), 'pending' => (int) ($c['PENDING'] ?? 0)];
    }

    public static function candidateTotals(?int $electionId = null): array
    {
        return ElectionResultCandidateVote::query()
            ->join('election_results', 'election_results.id', '=', 'election_result_candidate_votes.election_result_id')
            ->join('parties', 'parties.id', '=', 'election_result_candidate_votes.party_id')
            ->when($electionId, fn ($query, $id) => $query->where('election_results.election_id', $id))
            ->selectRaw('parties.code, SUM(election_result_candidate_votes.votes) AS votes')
            ->groupBy('parties.code')
            ->orderByDesc('votes')
            ->pluck('votes', 'code')
            ->map(fn ($votes) => (int) $votes)
            ->all();
    }
}
