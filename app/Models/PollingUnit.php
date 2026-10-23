<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PollingUnit extends Model
{
    protected $guarded = [];

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ElectionResult::class);
    }
}
