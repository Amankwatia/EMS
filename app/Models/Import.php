<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'election_id',
    'import_type',
    'filename',
    'total_rows',
    'successful_rows',
    'failed_rows',
    'failed_rows_path',
    'generated_pins_path',
    'generated_pins_expires_at',
    'imported_by',
    'created_at',
])]
class Import extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'generated_pins_expires_at' => 'datetime',
        ];
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
