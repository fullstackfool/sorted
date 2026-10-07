<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read int              $id
 * @property int                   $template_id
 * @property int|null              $parent_id
 * @property string                $status
 * @property int|null              $user_id
 * @property string|null           $notes
 * @property Carbon                $date
 * @property Carbon|null           $completed_at
 * @property-read Carbon           $created_at
 * @property-read Carbon           $updated_at
 * @property-read Template         $template
 * @property-read User|null        $user
 * @property-read Task|null        $parent
 * @property-read Collection<Task> $subtasks
 */
class Task extends Model
{
    protected $fillable = [
        'template_id',
        'parent_id',
        'date',
        'status',
        'user_id',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date'         => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function scopePrimary(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Create the next occurrence of this task after completion/skip.
     */
    public function createNextOccurrence(): ?Task
    {
        $template = $this->template;

        // Calculate the next occurrence date
        $nextDate = $template->calculateNextOccurrence($this->date);

        if (!$nextDate) {
            return null;
        }

        // Create the next task
        $nextTask = Task::create([
            'template_id' => $template->id,
            'date'        => $nextDate,
            'status'      => 'todo',
        ]);

        // Create subtasks for any subtemplates
        foreach ($template->subtemplates as $subtemplate) {
            Task::create([
                'template_id' => $subtemplate->id,
                'parent_id'   => $nextTask->id,
                'date'        => $nextDate,
                'status'      => 'todo',
            ]);
        }

        return $nextTask;
    }
}
