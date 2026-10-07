<?php

namespace App\Support;

use App\Models\Chore;
use Carbon\CarbonImmutable;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Support\Str;
use RRule\RRule;

/**
 * A chore's repeat rules: when it's first due, when it's next due, and how to describe them.
 *
 * "On set days" rules are RRULE bodies without a DTSTART, anchored on the chore's `starts_on`.
 */
class Schedule
{
    /**
     * RRULE weekday codes in Mon…Sun order, with their short names.
     */
    private const DAYS = [
        'MO' => 'Mon',
        'TU' => 'Tue',
        'WE' => 'Wed',
        'TH' => 'Thu',
        'FR' => 'Fri',
        'SA' => 'Sat',
        'SU' => 'Sun',
    ];

    public function __construct(
        private string $kind,
        private ?int $every = null,
        private ?string $unit = null,
        private ?string $rule = null,
        private ?CarbonImmutable $startsOn = null,
    ) {}

    public static function of(Chore $chore): self
    {
        return new self($chore->schedule, $chore->every, $chore->unit, $chore->rule, $chore->starts_on);
    }

    /**
     * When a new or changed chore is first due. A one-off's date is whatever was entered, so it has none.
     */
    public function firstDueOn(CarbonImmutable $today): ?CarbonImmutable
    {
        return match ($this->kind) {
            Chore::ONCE => null,
            Chore::AFTER => self::day($today),
            Chore::ON => $this->occurrenceAfter($today->max($this->startsOn), inclusive: true),
        };
    }

    /**
     * When the chore is next due after being done or skipped on $doneOn.
     */
    public function nextDueOn(?CarbonImmutable $dueOn, CarbonImmutable $doneOn): ?CarbonImmutable
    {
        return match ($this->kind) {
            Chore::ONCE => null,
            Chore::AFTER => match ($this->unit) {
                'day' => self::day($doneOn)->addDays($this->every),
                'week' => self::day($doneOn)->addWeeks($this->every),
                'month' => self::day($doneOn)->addMonthsNoOverflow($this->every),
            },
            Chore::ON => $this->occurrenceAfter($doneOn->max($dueOn ?? $doneOn), inclusive: false),
        };
    }

    public function description(): string
    {
        return match ($this->kind) {
            Chore::ONCE => 'One-off',
            Chore::AFTER => $this->every . ' ' . Str::plural($this->unit, $this->every) . " after it's done",
            Chore::ON => $this->ruleDescription(),
        };
    }

    public static function dailyRule(): string
    {
        return 'FREQ=DAILY';
    }

    /**
     * @param array<int> $weekdays 0 = Sun … 6 = Sat, as the UI numbers them
     */
    public static function weekdaysRule(array $weekdays, int $every = 1): string
    {
        $picked = array_map(fn (int $day) => ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'][$day], $weekdays);
        $byDay  = implode(',', array_intersect(array_keys(self::DAYS), $picked));

        return 'FREQ=WEEKLY' . ($every > 1 ? ";INTERVAL={$every}" : '') . ";BYDAY={$byDay}";
    }

    /**
     * @param array<int> $days days of the month, with -1 for the last day
     */
    public static function monthDaysRule(array $days): string
    {
        $days = array_unique($days);
        usort($days, fn (int $a, int $b) => ($a === -1 ? 32 : $a) <=> ($b === -1 ? 32 : $b));

        return 'FREQ=MONTHLY;BYMONTHDAY=' . implode(',', $days);
    }

    private function ruleDescription(): string
    {
        $rule = (new RRule($this->rule))->getRule();

        if ($rule['FREQ'] === 'DAILY') {
            return 'Every day';
        }

        if ($rule['FREQ'] === 'WEEKLY') {
            $days = $this->formatDayList(
                array_values(array_intersect_key(self::DAYS, array_flip(explode(',', $rule['BYDAY']))))
            );

            return $rule['INTERVAL'] > 1 ? "Every {$rule['INTERVAL']} weeks on {$days}" : "Every {$days}";
        }

        $monthDays = array_map('intval', explode(',', $rule['BYMONTHDAY']));
        $dates     = array_filter($monthDays, fn (int $day) => $day > 0);
        sort($dates);
        $items = array_map(fn (int $day) => $this->ordinal($day), $dates);
        if (in_array(-1, $monthDays, true)) {
            $items[] = 'last day';
        }

        return ucfirst($this->formatDayList($items)) . ' of each month';
    }

    /**
     * The first scheduled day after $from, or on it when inclusive.
     *
     * The rule is anchored on `starts_on` and runs on UTC midnights so clock changes can't shift a date.
     */
    private function occurrenceAfter(CarbonImmutable $from, bool $inclusive): CarbonImmutable
    {
        $rrule = new RRule($this->rule, self::utcMidnight($this->startsOn));
        $next  = $rrule->getOccurrencesAfter(self::utcMidnight($from), $inclusive, 1)[0];

        return self::day($next);
    }

    private static function utcMidnight(DateTimeInterface $date): DateTime
    {
        return new DateTime($date->format('Y-m-d'), new DateTimeZone('UTC'));
    }

    /**
     * The same calendar date at midnight in the app timezone.
     */
    private static function day(DateTimeInterface $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date->format('Y-m-d'), config('app.timezone'));
    }

    /**
     * Format a list of days with proper grammar
     */
    private function formatDayList(array $items): string
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
    private function ordinal(int $number): string
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
}
