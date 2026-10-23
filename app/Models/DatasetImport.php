<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatasetImport extends Model
{
    public const TYPE_REAL = 'REAL';
    public const TYPE_SYNTHETIC = 'SYNTHETIC';
    public const TYPES = [self::TYPE_REAL, self::TYPE_SYNTHETIC];

    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_COMPLETED_WITH_CONFLICTS = 'COMPLETED_WITH_CONFLICTS';
    public const STATUS_ALREADY_IMPORTED = 'ALREADY_IMPORTED';
    public const STATUS_FAILED = 'FAILED';

    protected $guarded = [];
    protected $casts = [
        'candidate_columns' => 'array',
        'rules_skipped' => 'array',
        'completed_at' => 'datetime',
    ];

    public function election() { return $this->belongsTo(Election::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function results() { return $this->hasMany(ElectionResult::class); }
}
