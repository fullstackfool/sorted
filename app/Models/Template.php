<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Artisan;

/**
 * @property-read int                  $id
 * @property int|null                  $parent_id
 * @property string                    $title
 * @property string|null               $description
 * @property int|null                  $points
 * @property string|null               $deadline
 * @property string                    $recurrence_type
 * @property array|null                $recurrence_pattern
 * @property string|null               $next_due_date
 * @property-read string               $recurrence_description
 * @property-read Carbon               $created_at
 * @property-read Carbon               $updated_at
 * @property-read Collection<User>     $users
 * @property-read Collection<Label>    $labels
 * @property-read Collection<Template> $subtemplates
 * @property-read Collection<Task>     $tasks
 * @property-read Template|null        $parent
 */
class Template extends Model
{
    protected $fillable = [
        'title',
        'description',
        'points',
        'deadline',
        'recurrence_type',
        'recurrence_pattern',
        'next_due_date',
        'parent_id',
    ];

    protected static function boot(): void
    {
        parent::boot();

        // When a template is created, ensure it has a pending task
        static::created(function (Template $template) {
            $template->ensurePendingTaskExists();
        });

        // When a template is updated, regenerate pending task if schedule changed
        static::updated(function (Template $template) {
            if ($template->wasChanged(['recurrence_type', 'recurrence_pattern', 'deadline'])) {
                $template->regeneratePendingTask();
            }
        });
    }

    /**
     * Regenerate the pending task for this template.
     * Deletes existing pending task and creates a new one with the updated schedule.
     */
    public function regeneratePendingTask(): void
    {
        // Skip subtemplates - they're handled by their parent
        if ($this->parent_id !== null) {
            return;
        }

        // Delete existing pending tasks (and their subtasks via cascade)
        $this->tasks()->where('status', 'todo')->delete();

        // Update next_due_date
        $this->next_due_date = $this->calculateNextDueDate();
        $this->saveQuietly(); // Use saveQuietly to avoid triggering updated event again

        // Create new pending task
        $this->ensurePendingTaskExists();
    }

    /**
     * Ensure this template has a pending task for its next occurrence.
     * Also creates subtasks if this template has subtemplates.
     */
    public function ensurePendingTaskExists(): void
    {
        // Skip subtemplates - they're handled by their parent
        if ($this->parent_id !== null) {
            return;
        }

        // Check if a pending task already exists
        $pendingTask = $this->tasks()->where('status', 'todo')->first();

        if ($pendingTask) {
            return;
        }

        // Calculate the next occurrence date
        $nextDate = $this->calculateNextOccurrence(now()->subDay());

        if (!$nextDate) {
            // For one-time tasks with a deadline, use the deadline
            if ($this->recurrence_type === 'none' && $this->deadline) {
                $nextDate = $this->deadline;
            } else {
                return;
            }
        }

        // Create the task
        $task = Task::create([
            'template_id' => $this->id,
            'date'        => $nextDate,
            'status'      => 'todo',
        ]);

        // Create subtasks for any subtemplates
        foreach ($this->subtemplates as $subtemplate) {
            Task::create([
                'template_id' => $subtemplate->id,
                'parent_id'   => $task->id,
                'date'        => $nextDate,
                'status'      => 'todo',
            ]);
        }
    }

    protected function casts(): array
    {
        return [
            'deadline'           => 'datetime',
            'next_due_date'      => 'datetime',
            'recurrence_pattern' => 'json',
        ];
    }

    protected $appends = ['recurrence_description'];

    /**
     * Short day names for display
     */
    protected static array $shortDayNames = [
        0 => 'Sun',
        1 => 'Mon',
        2 => 'Tue',
        3 => 'Wed',
        4 => 'Thu',
        5 => 'Fri',
        6 => 'Sat',
    ];

    // Relationships
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function subtemplates(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function scopePrimary(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeWithSubtemplates(Builder $query): Builder
    {
        return $query->with('subtemplates');
    }

    /**
     * Get a human-readable description of the recurrence pattern
     */
    public function getRecurrenceDescriptionAttribute(): string
    {
        $pattern    = $this->recurrence_pattern ?? [];
        $isFlexible = $pattern['flexible'] ?? false;

        return match ($this->recurrence_type) {
            'none' => $this->deadline ? 'One-time task' : 'No schedule',
            'daily' => 'Every day',
            'weekly' => $this->getWeeklyDescription($pattern, $isFlexible),
            'biweekly' => $this->getBiweeklyDescription($pattern),
            'monthly' => $this->getMonthlyDescription($pattern, $isFlexible),
            'custom' => 'Custom schedule',
            default => 'Unknown',
        };
    }

    /**
     * Get description for weekly recurrence
     */
    protected function getWeeklyDescription(array $pattern, bool $isFlexible): string
    {
        if ($isFlexible) {
            return 'Weekly (flexible)';
        }

        $daysOfWeek = $pattern['days_of_week'] ?? [];
        if (empty($daysOfWeek)) {
            return 'Weekly';
        }

        // Sort days before formatting
        sort($daysOfWeek);
        $dayNames = array_map(fn ($day) => self::$shortDayNames[$day] ?? '', $daysOfWeek);
        return 'Every ' . $this->formatDayList($dayNames);
    }

    /**
     * Get description for biweekly recurrence
     */
    protected function getBiweeklyDescription(array $pattern): string
    {
        $daysOfWeek = $pattern['days_of_week'] ?? [];
        if (empty($daysOfWeek)) {
            return 'Every 2 weeks';
        }

        // Sort days before formatting
        sort($daysOfWeek);
        $dayNames = array_map(fn ($day) => self::$shortDayNames[$day] ?? '', $daysOfWeek);
        return 'Every 2 weeks on ' . $this->formatDayList($dayNames);
    }

    /**
     * Get description for monthly recurrence
     */
    protected function getMonthlyDescription(array $pattern, bool $isFlexible): string
    {
        if ($isFlexible) {
            return 'Monthly (flexible)';
        }

        $daysOfMonth = $pattern['days_of_month'] ?? [];
        if (empty($daysOfMonth)) {
            return 'Monthly';
        }

        // Sort days before formatting
        sort($daysOfMonth);
        $ordinals = array_map(fn ($day) => $this->ordinal($day), $daysOfMonth);
        return $this->formatDayList($ordinals) . ' of each month';
    }

    /**
     * Format a list of days with proper grammar
     */
    protected function formatDayList(array $items): string
    {
        if (count($items) === 1) {
            return $items[0];
        }

        if (count($items) === 2) {
            return $items[0] . ' & ' . $items[1];
        }

        $last = array_pop($items);
        return implode(', ', $items) . ' & ' . $last;
    }

    /**
     * Get ordinal suffix for a number (1st, 2nd, 3rd, etc.)
     */
    protected function ordinal(int $number): string
    {
        $suffix = match ($number % 100) {
            11, 12, 13 => 'th',
            default => match ($number % 10) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
            },
        };

        return $number . $suffix;
    }

    /**
     * Calculate the next due date based on recurrence pattern
     */
    public function calculateNextDueDate(): ?string
    {
        return $this->calculateNextOccurrence(now());
    }

    /**
     * Calculate the next occurrence after a given date
     */
    public function calculateNextOccurrence($afterDate): ?string
    {
        // Only calculate for non-flexible recurring tasks
        if ($this->recurrence_type === 'none') {
            return null;
        }

        $pattern    = $this->recurrence_pattern ?? [];
        $isFlexible = $pattern['flexible'] ?? false;

        // Flexible tasks don't have a specific due date
        if ($isFlexible) {
            return null;
        }

        $date = is_string($afterDate) ? \Carbon\Carbon::parse($afterDate) : $afterDate;

        // Daily tasks are due the next day
        if ($this->recurrence_type === 'daily') {
            return $date->copy()->addDay()->startOfDay()->toDateTimeString();
        }

        // Calculate based on pattern
        return match ($this->recurrence_type) {
            'weekly' => $this->calculateNextWeeklyDate($pattern, $date),
            'biweekly' => $this->calculateNextBiweeklyDate($pattern, $date),
            'monthly' => $this->calculateNextMonthlyDate($pattern, $date),
            default => null,
        };
    }

    /**
     * Calculate next weekly due date
     */
    protected function calculateNextWeeklyDate(array $pattern, $fromDate = null): ?string
    {
        $daysOfWeek = $pattern['days_of_week'] ?? [];
        if (empty($daysOfWeek)) {
            return null;
        }

        $date             = $fromDate ?? now();
        $currentDayOfWeek = $date->dayOfWeek;

        // Sort days to check them in order
        sort($daysOfWeek);

        // Find the next occurrence after the given date
        foreach ($daysOfWeek as $targetDay) {
            if ($targetDay > $currentDayOfWeek) {
                // Found a day later this week
                return $date->copy()->next($targetDay)->startOfDay()->toDateTimeString();
            }
        }

        // No day found this week, use the first day next week
        $firstDay = min($daysOfWeek);
        return $date->copy()->next($firstDay)->startOfDay()->toDateTimeString();
    }

    /**
     * Calculate next biweekly due date
     */
    protected function calculateNextBiweeklyDate(array $pattern, $fromDate = null): ?string
    {
        $daysOfWeek = $pattern['days_of_week'] ?? [];
        if (empty($daysOfWeek)) {
            return null;
        }

        // For biweekly, add 2 weeks to the from date
        $date             = $fromDate ?? now();
        $date             = $date->copy()->addWeeks(2);
        $currentDayOfWeek = $date->dayOfWeek;

        // Sort days to check them in order
        sort($daysOfWeek);

        // Find the closest day in that week
        foreach ($daysOfWeek as $targetDay) {
            if ($targetDay >= $currentDayOfWeek) {
                $daysToAdd = $targetDay - $currentDayOfWeek;
                return $date->copy()->addDays($daysToAdd)->startOfDay()->toDateTimeString();
            }
        }

        // If no day found, use the first day of the following week
        $firstDay  = min($daysOfWeek);
        $daysToAdd = (7 - $currentDayOfWeek) + $firstDay;
        return $date->copy()->addDays($daysToAdd)->startOfDay()->toDateTimeString();
    }

    /**
     * Calculate next monthly due date
     */
    protected function calculateNextMonthlyDate(array $pattern, $fromDate = null): ?string
    {
        $daysOfMonth = $pattern['days_of_month'] ?? [];
        if (empty($daysOfMonth)) {
            return null;
        }

        $date       = $fromDate ?? now();
        $currentDay = $date->day;

        // Find next occurrence this month
        foreach ($daysOfMonth as $targetDay) {
            if ($targetDay > $currentDay) {
                try {
                    return $date->copy()->day($targetDay)->startOfDay()->toDateTimeString();
                } catch (\Exception $e) {
                    // Invalid day for this month, skip
                    continue;
                }
            }
        }

        // No day found this month, use first day next month
        $firstDay = min($daysOfMonth);
        try {
            return $date->copy()->addMonth()->day($firstDay)->startOfDay()->toDateTimeString();
        } catch (\Exception $e) {
            // Invalid day for next month, return null
            return null;
        }
    }
}
