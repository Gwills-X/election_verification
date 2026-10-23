<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    protected $guarded = [];

    public function localGovernments(): HasMany
    {
        return $this->hasMany(LocalGovernment::class);
    }
}
