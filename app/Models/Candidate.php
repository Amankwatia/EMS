<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'election_id',
    'position_id',
    'candidate_name',
    'student_id',
    'class_name',
    'programme',
    'house',
    'gender',
    'photo_path',
    'manifesto',
    'ballot_number',
    'display_order',
    'status',
])]
class Candidate extends Model
{
    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}
