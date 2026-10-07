<?php

namespace Tests\Unit;

use App\Models\Chore;
use App\Support\Schedule;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    public function test_after_rules_count_from_the_day_it_was_done_and_start_today(): void
    {
        $everyThreeDays = new Schedule(Chore::AFTER, every: 3, unit: 'day');
        $this->assertDay('2026-10-10', $everyThreeDays->nextDueOn($this->day('2026-10-05'), $this->day('2026-10-07')));
        $this->assertDay('2026-10-07', $everyThreeDays->firstDueOn($this->day('2026-10-07')));

        $everyMonth = new Schedule(Chore::AFTER, every: 1, unit: 'month');
        $this->assertDay('2027-02-28', $everyMonth->nextDueOn($this->day('2027-01-31'), $this->day('2027-01-31')));

        $everyTwoWeeks = Schedule::of(new Chore(['schedule' => Chore::AFTER, 'every' => 2, 'unit' => 'week']));
        $this->assertDay('2026-10-21', $everyTwoWeeks->nextDueOn(null, $this->day('2026-10-07')));
    }

    public function test_a_long_missed_daily_chore_catches_up_in_one_go(): void
    {
        $daily = $this->on(Schedule::dailyRule(), '2026-09-01');

        $this->assertDay('2026-10-08', $daily->nextDueOn($this->day('2026-10-02'), $this->day('2026-10-07')));
    }

    public function test_every_two_weeks_on_monday_and_thursday_gives_both_days_of_every_other_week(): void
    {
        $fortnightly = Schedule::of(new Chore([
            'schedule'  => Chore::ON,
            'rule'      => Schedule::weekdaysRule([1, 4], 2),
            'starts_on' => '2026-10-05',
        ]));

        $dueOn = $this->day('2026-10-05');
        $dates = [];
        foreach (range(1, 4) as $ignored) {
            $dueOn = $fortnightly->nextDueOn($dueOn, $dueOn);
            $dates[] = $dueOn->format('Y-m-d');
        }

        $this->assertSame(['2026-10-08', '2026-10-19', '2026-10-22', '2026-11-02'], $dates);

        // Weeks count from starts_on, so from a day in the off week the next date is in the following on week.
        $this->assertDay('2026-10-19', $fortnightly->nextDueOn($this->day('2026-10-08'), $this->day('2026-10-13')));
        $this->assertDay('2026-10-19', $fortnightly->firstDueOn($this->day('2026-10-13')));
    }

    public function test_monthly_dates_a_month_does_not_have_are_skipped(): void
    {
        $firstAndThirtyFirst = $this->on(Schedule::monthDaysRule([1, 31]), '2027-01-01');

        $dueOn = $this->day('2027-01-31');
        $dueOn = $firstAndThirtyFirst->nextDueOn($dueOn, $dueOn);
        $this->assertDay('2027-02-01', $dueOn);
        $dueOn = $firstAndThirtyFirst->nextDueOn($dueOn, $dueOn);
        $this->assertDay('2027-03-01', $dueOn);
        $dueOn = $firstAndThirtyFirst->nextDueOn($dueOn, $dueOn);
        $this->assertDay('2027-03-31', $dueOn);
    }

    public function test_last_day_of_each_month_follows_the_length_of_the_month(): void
    {
        $lastDay = $this->on(Schedule::monthDaysRule([-1]), '2027-01-01');

        $this->assertDay('2027-02-28', $lastDay->firstDueOn($this->day('2027-02-15')));
        $this->assertDay('2027-03-31', $lastDay->nextDueOn($this->day('2027-02-28'), $this->day('2027-02-28')));
        $this->assertDay('2028-02-29', $lastDay->nextDueOn($this->day('2028-01-31'), $this->day('2028-01-31')));
    }

    public function test_bins_done_early_or_late_are_next_due_the_following_scheduled_day(): void
    {
        $bins = $this->on(Schedule::weekdaysRule([3]), '2026-09-02');
        $dueOn = $this->day('2026-10-07');

        $this->assertDay('2026-10-14', $bins->nextDueOn($dueOn, $this->day('2026-10-06')));
        $this->assertDay('2026-10-14', $bins->nextDueOn($dueOn, $this->day('2026-10-08')));
    }

    public function test_on_rules_are_first_due_on_the_first_scheduled_day_from_today_or_their_start(): void
    {
        $wednesdays = $this->on(Schedule::weekdaysRule([3]), '2026-09-02');
        $this->assertDay('2026-10-14', $wednesdays->firstDueOn($this->day('2026-10-08')));
        $this->assertDay('2026-10-07', $wednesdays->firstDueOn($this->day('2026-10-07')));

        $firstOfMonth = $this->on(Schedule::monthDaysRule([1]), '2026-09-01');
        $this->assertDay('2026-11-01', $firstOfMonth->firstDueOn($this->day('2026-10-02')));

        $startsLater = $this->on(Schedule::weekdaysRule([3]), '2026-11-02');
        $this->assertDay('2026-11-04', $startsLater->firstDueOn($this->day('2026-10-08')));
    }

    public function test_one_offs_have_no_first_or_next_due_date(): void
    {
        $once = new Schedule(Chore::ONCE);

        $this->assertNull($once->firstDueOn($this->day('2026-10-07')));
        $this->assertNull($once->nextDueOn($this->day('2026-10-07'), $this->day('2026-10-07')));
        $this->assertNull($once->nextDueOn(null, $this->day('2026-10-07')));
    }

    public function test_descriptions(): void
    {
        $this->assertSame('One-off', (new Schedule(Chore::ONCE))->description());

        $this->assertSame("1 day after it's done", (new Schedule(Chore::AFTER, every: 1, unit: 'day'))->description());
        $this->assertSame("3 days after it's done", (new Schedule(Chore::AFTER, every: 3, unit: 'day'))->description());
        $this->assertSame("1 week after it's done", (new Schedule(Chore::AFTER, every: 1, unit: 'week'))->description());
        $this->assertSame("2 months after it's done", (new Schedule(Chore::AFTER, every: 2, unit: 'month'))->description());

        $this->assertSame('Every day', $this->on('FREQ=DAILY')->description());
        $this->assertSame('Every Mon & Thu', $this->on('FREQ=WEEKLY;BYDAY=MO,TH')->description());
        $this->assertSame('Every 2 weeks on Mon & Thu', $this->on('FREQ=WEEKLY;INTERVAL=2;BYDAY=MO,TH')->description());
        $this->assertSame('Every Mon, Wed & Fri', $this->on('FREQ=WEEKLY;BYDAY=FR,MO,WE')->description());
        $this->assertSame('Every Sat & Sun', $this->on('FREQ=WEEKLY;BYDAY=SU,SA')->description());

        $this->assertSame('1st & 15th of each month', $this->on('FREQ=MONTHLY;BYMONTHDAY=1,15')->description());
        $this->assertSame('Last day of each month', $this->on('FREQ=MONTHLY;BYMONTHDAY=-1')->description());
        $this->assertSame('1st & last day of each month', $this->on('FREQ=MONTHLY;BYMONTHDAY=1,-1')->description());
        $this->assertSame('2nd, 3rd & 11th of each month', $this->on('FREQ=MONTHLY;BYMONTHDAY=11,2,3')->description());
    }

    public function test_rule_builders(): void
    {
        $this->assertSame('FREQ=DAILY', Schedule::dailyRule());

        $this->assertSame('FREQ=WEEKLY;INTERVAL=2;BYDAY=MO,TH', Schedule::weekdaysRule([4, 1], 2));
        $this->assertSame('FREQ=WEEKLY;BYDAY=MO,TH', Schedule::weekdaysRule([4, 1, 4]));
        $this->assertSame('FREQ=WEEKLY;BYDAY=MO,SU', Schedule::weekdaysRule([0, 1]));

        $this->assertSame('FREQ=MONTHLY;BYMONTHDAY=1,15', Schedule::monthDaysRule([15, 1]));
        $this->assertSame('FREQ=MONTHLY;BYMONTHDAY=1,-1', Schedule::monthDaysRule([-1, 1]));
    }

    private function on(string $rule, string $startsOn = '2026-01-01'): Schedule
    {
        return new Schedule(Chore::ON, rule: $rule, startsOn: $this->day($startsOn));
    }

    private function day(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, 'Europe/London');
    }

    private function assertDay(string $expected, ?CarbonImmutable $actual): void
    {
        $this->assertSame("{$expected} 00:00:00 Europe/London", $actual?->format('Y-m-d H:i:s e'));
    }
}
