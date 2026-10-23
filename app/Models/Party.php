<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Party extends Model
{
    protected $guarded = [];

    public function resultVotes(): HasMany
    {
        return $this->hasMany(ElectionResultCandidateVote::class);
    }
}
