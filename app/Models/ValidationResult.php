<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ValidationResult extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['checked_at' => 'datetime'];
    public function result() { return $this->belongsTo(ElectionResult::class, 'election_result_id'); }
}
