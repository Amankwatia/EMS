<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'description',
    'academic_year',
    'start_at',
    'end_at',
    'status',
    'results_visible_to_public',
    'allow_internal_live_preview',
    'created_by',
    'closed_by',
    'closed_at',
    'locked_by',
    'locked_at',
    'lock_reason',
])]
class Election extends Model
{
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'closed_at' => 'datetime',
            'locked_at' => 'datetime',
            'results_visible_to_public' => 'boolean',
            'allow_internal_live_preview' => 'boolean',
        ];
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class)->orderBy('display_order')->orderBy('name');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function voters(): HasMany
    {
        return $this->hasMany(Voter::class);
    }

    public function anonymousVotes(): HasMany
    {
        return $this->hasMany(AnonymousVote::class);
    }

    public function acceptsVotes(): bool
    {
        $now = now();

        return $this->status === 'active'
            && ($this->start_at === null || $this->start_at->lte($now))
            && ($this->end_at === null || $this->end_at->gte($now));
    }

    public function resultsViewableByAdmins(): bool
    {
        return in_array($this->status, ['closed', 'published', 'locked'], true)
            || $this->allow_internal_live_preview;
    }
}
