<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

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

    /**
     * Remove this completion and put its chore back on the date it was due before.
     *
     * Only the chore's most recent completion can be undone, and not once the chore is deleted;
     * for any other this changes nothing and returns false.
     */
    public function undo(): bool
    {
        return DB::transaction(function () {
            $latest = static::query()
                ->where('chore_id', $this->chore_id)
                ->orderByDesc('completed_at')
                ->orderByDesc('id')
                ->first();

            if (! $this->is($latest) || $this->chore->trashed()) {
                return false;
            }

            $this->chore->update(['next_due_on' => $this->due_on, 'finished_at' => null]);
            $this->delete();

            return true;
        });
    }
}
