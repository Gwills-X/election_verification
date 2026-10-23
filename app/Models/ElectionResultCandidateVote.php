<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectionResultCandidateVote extends Model
{
    protected $guarded = [];

    public function electionResult(): BelongsTo
    {
        return $this->belongsTo(ElectionResult::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
