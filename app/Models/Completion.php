<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Completion extends Model
{
    /** @use HasFactory<\Database\Factories\CompletionFactory> */
    use HasFactory;

    public const DONE = 'done';

    public const SKIPPED = 'skipped';

    protected $fillable = [
        'chore_id',
        'user_id',
        'status',
        'due_on',
        'points',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_on'       => DateOnly::class,
            'completed_at' => 'datetime',
            'points'       => 'integer',
        ];
    }

    public function chore(): BelongsTo
    {
        return $this->belongsTo(Chore::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
