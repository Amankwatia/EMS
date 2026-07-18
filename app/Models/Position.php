<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'election_id',
    'name',
    'description',
    'max_choices',
    'display_order',
    'is_required',
    'allow_abstain',
    'is_active',
])]
class Position extends Model
{
    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'allow_abstain' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class)->orderBy('display_order')->orderBy('candidate_name');
    }
}
