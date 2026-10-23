<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Election extends Model
{
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_ARCHIVED = 'ARCHIVED';

    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_ACTIVE, self::STATUS_COMPLETED, self::STATUS_ARCHIVED];

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (Election $election): void {
            if (!$election->election_type_id || !$election->year) {
                return;
            }

            $type = $election->electionType()
                ->with('scopeState')
                ->first();

            if (!$type) {
                return;
            }

            $election->name = $election->generatedName($type);
        });
    }

    public function electionType(): BelongsTo
    {
        return $this->belongsTo(ElectionType::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ElectionResult::class);
    }

    public function datasetImports(): HasMany
    {
        return $this->hasMany(DatasetImport::class);
    }

    public function assignedOfficers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'election_user')->withTimestamps();
    }

    public function generatedName(?ElectionType $type = null): string
    {
        $type ??= $this->electionType()->with('scopeState')->firstOrFail();
        $name = "{$this->year} {$type->name} Election";

        if ($type->scope === 'STATE' && $type->scopeState) {
            $name .= " — {$type->scopeState->name}";
        }

        return $name;
    }

    public function allowsPollingUnit(PollingUnit $pollingUnit): bool
    {
        $type = $this->electionType()->with('scopeState')->first();
        $state = $pollingUnit->ward?->localGovernment?->state;

        return $type
            && $state?->country_code === 'NGA'
            && ($type->scope === 'NATIONAL' || $state->id === $type->scope_state_id);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_ACTIVE], true);
    }

    public function scopePubliclyVisible(Builder $q): Builder
    {
        return $q->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_COMPLETED]);
    }

    public function statusCounts(): array
    {
        $c = $this->results()->selectRaw('validation_status, count(*) as n')->groupBy('validation_status')->pluck('n', 'validation_status');
        return ['total' => (int) $c->sum(), 'valid' => (int) ($c['VALID'] ?? 0), 'invalid' => (int) ($c['INVALID'] ?? 0),
                'flagged' => (int) ($c['FLAGGED_FOR_REVIEW'] ?? 0), 'pending' => (int) ($c['PENDING'] ?? 0)];
    }

    public function candidateTotals(): array
    {
        return ElectionResultCandidateVote::query()
            ->join('election_results', 'election_results.id', '=', 'election_result_candidate_votes.election_result_id')
            ->join('parties', 'parties.id', '=', 'election_result_candidate_votes.party_id')
            ->where('election_results.election_id', $this->id)
            ->selectRaw('parties.code, SUM(election_result_candidate_votes.votes) AS votes')
            ->groupBy('parties.code')
            ->orderByDesc('votes')
            ->pluck('votes', 'code')
            ->map(fn ($votes) => (int) $votes)
            ->all();
    }
}
