<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'election_id',
    'student_id',
    'full_name',
    'class_name',
    'programme',
    'house',
    'gender',
    'pin_hash',
    'is_eligible',
    'has_voted',
    'voted_at',
    'last_login_at',
])]
#[Hidden(['pin_hash'])]
class Voter extends Model
{
    protected function casts(): array
    {
        return [
            'is_eligible' => 'boolean',
            'has_voted' => 'boolean',
            'voted_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }
}
