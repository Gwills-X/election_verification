<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class ElectionType extends Model
{
    public const PRESIDENTIAL = 'PRESIDENTIAL';
    public const GOVERNORSHIP = 'GOVERNORSHIP';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (ElectionType $type): void {
            if ($type->code === self::PRESIDENTIAL) {
                $type->scope = 'NATIONAL';
                $type->scope_state_id = null;
                return;
            }

            $bauchiId = State::where('code', 'BA')->value('id');
            if (!$bauchiId) {
                throw new InvalidArgumentException(
                    'Bauchi must exist before the Governorship election type is saved.'
                );
            }

            if ($type->scope_state_id && (int) $type->scope_state_id !== (int) $bauchiId) {
                throw new InvalidArgumentException(
                    'Governorship elections are restricted to Bauchi State.'
                );
            }

            $type->scope = 'STATE';
            $type->scope_state_id = $bauchiId;
        });
    }

    public function scopeState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'scope_state_id');
    }

    public function elections(): HasMany
    {
        return $this->hasMany(Election::class);
    }

    public function getScopeLabelAttribute(): string
    {
        return $this->scope === 'NATIONAL'
            ? 'National'
            : ($this->scopeState?->name ?? 'State');
    }
}
