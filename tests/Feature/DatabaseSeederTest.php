<?php

namespace Tests\Feature;

use App\Models\Chore;
use App\Models\Completion;
use App\Models\Label;
use App\Models\User;
use App\Support\Schedule;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    private const TITLES = [
        'Do the dishes',
        'Tidy living room',
        'Feed the pets',
        'Make all beds',
        'Do laundry',
        'Deep clean refrigerator',
        'Vacuum all floors',
        'Take out trash and recycling',
        'Mop kitchen and bathroom',
        'Clean bathrooms',
        'Grocery shopping',
        'Meal prep for the week',
        'Wash all windows',
        'Change all bed linens',
        'Garden maintenance',
        'Organize garage',
        'Sort and organize toy closet',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // A Wednesday.
        $this->travelTo(CarbonImmutable::create(2026, 10, 7, 12, 0, 0, 'Europe/London'));
        $this->seed();
    }

    public function test_it_seeds_the_four_people_and_six_labels(): void
    {
        $this->assertEqualsCanonicalizing(['Karl', 'Lisa', 'Leo', 'Kai'], User::pluck('name')->all());
        $this->assertEqualsCanonicalizing(
            ['Cleaning', 'Kitchen', 'Outdoor', 'Laundry', 'Pets', 'Bathroom'],
            Label::pluck('name')->all(),
        );
    }

    public function test_it_seeds_the_seventeen_chores_and_leaves_the_old_tables_empty(): void
    {
        $titles = Chore::pluck('title')->all();

        $this->assertEqualsCanonicalizing(self::TITLES, $titles);
        foreach (['Mow the lawn', 'Trim hedges', 'Weed flower beds'] as $subtask) {
            $this->assertNotContains($subtask, $titles);
        }

        foreach (['templates', 'tasks', 'label_template', 'template_user'] as $table) {
            if (Schema::hasTable($table)) {
                $this->assertSame(0, DB::table($table)->count(), "The {$table} table should be empty.");
            }
        }
    }

    public function test_each_chore_has_its_schedule_and_due_date(): void
    {
        // [schedule, every, unit, rule, starts_on, next_due_on]
        $expected = [
            'Do the dishes'                => [Chore::ON, null, null, 'FREQ=DAILY', '2026-10-04', '2026-10-05'],
            'Tidy living room'             => [Chore::ON, null, null, 'FREQ=DAILY', '2026-10-06', '2026-10-06'],
            'Feed the pets'                => [Chore::ON, null, null, 'FREQ=DAILY', '2026-10-06', '2026-10-07'],
            'Make all beds'                => [Chore::ON, null, null, 'FREQ=DAILY', '2026-10-06', '2026-10-07'],
            'Do laundry'                   => [Chore::AFTER, 1, 'week', null, null, '2026-10-11'],
            'Deep clean refrigerator'      => [Chore::AFTER, 1, 'month', null, null, '2026-10-27'],
            'Vacuum all floors'            => [Chore::ON, null, null, 'FREQ=WEEKLY;BYDAY=SA', '2026-10-07', '2026-10-10'],
            'Take out trash and recycling' => [Chore::ON, null, null, 'FREQ=WEEKLY;BYDAY=WE', '2026-10-07', '2026-10-07'],
            'Mop kitchen and bathroom'     => [Chore::ON, null, null, 'FREQ=WEEKLY;BYDAY=FR', '2026-10-07', '2026-10-09'],
            'Clean bathrooms'              => [Chore::ON, null, null, 'FREQ=WEEKLY;BYDAY=SU', '2026-10-07', '2026-10-11'],
            'Grocery shopping'             => [Chore::ON, null, null, 'FREQ=WEEKLY;BYDAY=SA', '2026-10-07', '2026-10-10'],
            'Meal prep for the week'       => [Chore::ON, null, null, 'FREQ=WEEKLY;BYDAY=SU', '2026-10-07', '2026-10-11'],
            'Wash all windows'             => [Chore::ON, null, null, 'FREQ=MONTHLY;BYMONTHDAY=1', '2026-10-07', '2026-11-01'],
            'Change all bed linens'        => [Chore::ON, null, null, 'FREQ=MONTHLY;BYMONTHDAY=15', '2026-10-07', '2026-10-15'],
            'Garden maintenance'           => [Chore::ONCE, null, null, null, null, '2026-10-17'],
            'Organize garage'              => [Chore::ONCE, null, null, null, null, '2026-10-21'],
            'Sort and organize toy closet' => [Chore::ONCE, null, null, null, null, '2026-10-14'],
        ];

        $actual = Chore::all()->mapWithKeys(fn (Chore $chore) => [$chore->title => [
            $chore->schedule,
            $chore->every,
            $chore->unit,
            $chore->rule,
            $chore->starts_on?->format('Y-m-d'),
            $chore->next_due_on?->format('Y-m-d'),
        ]])->all();

        $this->assertEquals($expected, $actual);
    }

    public function test_each_chore_keeps_its_people_and_labels(): void
    {
        // People and labels in alphabetical order.
        $expected = [
            'Do the dishes'                => [['Kai', 'Leo'], ['Kitchen']],
            'Tidy living room'             => [['Kai', 'Leo'], ['Cleaning']],
            'Feed the pets'                => [['Kai', 'Leo'], ['Pets']],
            'Make all beds'                => [['Kai', 'Leo'], ['Cleaning']],
            'Do laundry'                   => [['Karl', 'Lisa'], ['Laundry']],
            'Vacuum all floors'            => [['Kai', 'Leo'], ['Cleaning']],
            'Take out trash and recycling' => [['Leo'], ['Outdoor']],
            'Mop kitchen and bathroom'     => [['Kai'], ['Bathroom', 'Cleaning', 'Kitchen']],
            'Clean bathrooms'              => [['Karl', 'Lisa'], ['Bathroom', 'Cleaning']],
            'Grocery shopping'             => [['Karl', 'Lisa'], ['Kitchen']],
            'Meal prep for the week'       => [['Karl', 'Lisa'], ['Kitchen']],
            'Deep clean refrigerator'      => [['Karl', 'Lisa'], ['Cleaning', 'Kitchen']],
            'Wash all windows'             => [['Karl'], ['Cleaning', 'Outdoor']],
            'Change all bed linens'        => [['Lisa'], ['Laundry']],
            'Garden maintenance'           => [['Karl', 'Leo'], ['Outdoor']],
            'Organize garage'              => [['Karl'], ['Cleaning', 'Outdoor']],
            'Sort and organize toy closet' => [['Kai', 'Leo'], ['Cleaning']],
        ];

        $actual = Chore::with('users', 'labels')->get()->mapWithKeys(fn (Chore $chore) => [$chore->title => [
            $chore->users->pluck('name')->sort()->values()->all(),
            $chore->labels->pluck('name')->sort()->values()->all(),
        ]])->all();

        $this->assertEquals($expected, $actual);
    }

    public function test_garden_maintenance_lists_its_steps_in_the_description(): void
    {
        $description = Chore::where('title', 'Garden maintenance')->value('description');

        $this->assertStringEndsWith("Steps:\n- Mow the lawn\n- Trim hedges\n- Weed flower beds", $description);
    }

    public function test_it_seeds_five_past_completions(): void
    {
        $expected = [
            ['Do the dishes', 'Kai', Completion::SKIPPED, '2026-10-04', 0, '2026-10-04 18:00:00'],
            ['Feed the pets', 'Leo', Completion::DONE, '2026-10-06', 5, '2026-10-06 08:00:00'],
            ['Make all beds', 'Kai', Completion::DONE, '2026-10-06', 5, '2026-10-06 09:00:00'],
            ['Do laundry', 'Lisa', Completion::DONE, '2026-10-04', 15, '2026-10-04 17:00:00'],
            ['Deep clean refrigerator', 'Karl', Completion::DONE, '2026-09-27', 30, '2026-09-27 11:00:00'],
        ];

        $actual = Completion::with('chore', 'user')->get()->map(fn (Completion $completion) => [
            $completion->chore->title,
            $completion->user->name,
            $completion->status,
            $completion->due_on->format('Y-m-d'),
            $completion->points,
            $completion->completed_at->format('Y-m-d H:i:s'),
        ])->all();

        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    public function test_due_dates_of_completed_chores_follow_from_their_last_completion(): void
    {
        $chores = Chore::has('completions')->with('completions')->get();

        $this->assertCount(5, $chores);
        foreach ($chores as $chore) {
            $last = $chore->completions->sortByDesc('completed_at')->first();

            $this->assertEquals(
                Schedule::of($chore)->nextDueOn($last->due_on, $last->completed_at->toImmutable()->startOfDay()),
                $chore->next_due_on,
                "{$chore->title} is due on the wrong day.",
            );
        }
    }
}
