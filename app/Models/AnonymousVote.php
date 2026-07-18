<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'election_id',
    'position_id',
    'candidate_id',
    'anonymous_ballot_code',
    'is_abstain',
    'created_at',
])]
class AnonymousVote extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'is_abstain' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
