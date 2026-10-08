<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Support\Schedule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Chore extends Model
{
    /** @use HasFactory<\Database\Factories\ChoreFactory> */
    use HasFactory, SoftDeletes;

    public const ONCE = 'once';

    public const AFTER = 'after';

    public const ON = 'on';

    protected $fillable = [
        'title',
        'description',
        'points',
        'schedule',
        'every',
        'unit',
        'rule',
        'starts_on',
        'next_due_on',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'points'      => 'integer',
            'every'       => 'integer',
            'starts_on'   => DateOnly::class,
            'next_due_on' => DateOnly::class,
            'finished_at' => 'datetime',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(Completion::class);
    }

    /**
     * Record that $user did or skipped the chore, and move it on to its next due date.
     *
     * Records nothing when the chore is deleted, finished, no longer due on the date the page showed,
     * or already done or skipped today for that date (an "after" chore done early can be due on the same date again),
     * so a repeated request only counts once.
     *
     * SQLite has no row locks, so a request that collides with another's write is retried and then sees the moved date.
     * The next date is worked out before writing, to keep the write lock short.
     */
    public function record(User $user, string $status, ?string $shownDueOn): ?Completion
    {
        return DB::transaction(function () use ($user, $status, $shownDueOn) {
            $chore = static::query()->find($this->getKey());

            if (! $chore || $chore->finished_at !== null || $chore->next_due_on?->format('Y-m-d') !== $shownDueOn
                || $chore->completions()->where('due_on', $shownDueOn)->where('completed_at', '>=', today())->exists()) {
                return null;
            }

            $moveOn = $chore->schedule === self::ONCE
                ? ['finished_at' => now()]
                : ['next_due_on' => Schedule::of($chore)->nextDueOn($chore->next_due_on, today()->toImmutable())];

            $completion = $chore->completions()->create([
                'user_id'      => $user->id,
                'status'       => $status,
                'due_on'       => $chore->next_due_on,
                'points'       => $status === Completion::DONE ? $chore->points : 0,
                'completed_at' => now(),
            ]);
            $chore->update($moveOn);

            return $completion;
        }, attempts: 5);
    }
}
