<?php

namespace Tests\Feature;

use App\Models\Chore;
use App\Models\Completion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChoreFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // A Wednesday.
        $this->travelTo(CarbonImmutable::create(2026, 10, 7, 12, 0, 0, 'Europe/London'));
    }

    public function test_creating_each_kind_of_chore_stores_its_schedule_and_first_due_date(): void
    {
        $dated = $this->create(['title' => 'Buy a birthday card', 'points' => 5, 'repeats' => 'once', 'due_on' => '2026-10-20']);
        $this->assertSchedule([Chore::ONCE, null, null, null, null, '2026-10-20'], $dated);
        $this->assertSame(5, $dated->points);

        $undated = $this->create(['title' => 'Fix the shed door', 'repeats' => 'once', 'due_on' => null]);
        $this->assertSchedule([Chore::ONCE, null, null, null, null, null], $undated);
        $this->assertSame(10, $undated->points);
        $today = collect($this->get(route('home'))->inertiaProps('chores'))->firstWhere('id', $undated->id);
        $this->assertSame('anytime', $today['group']);

        $daily = $this->create(['title' => 'Feed the cat', 'repeats' => 'daily']);
        $this->assertSchedule([Chore::ON, null, null, 'FREQ=DAILY', '2026-10-07', '2026-10-07'], $daily);

        $weekly = $this->create(['title' => 'Water the plants', 'repeats' => 'weekly', 'weekdays' => [1, 4], 'every_weeks' => 2]);
        // Thursday is the first Monday or Thursday on or after Wednesday 7 October.
        $this->assertSchedule([Chore::ON, null, null, 'FREQ=WEEKLY;INTERVAL=2;BYDAY=MO,TH', '2026-10-07', '2026-10-08'], $weekly);

        $monthly = $this->create(['title' => 'Pay the window cleaner', 'repeats' => 'monthly', 'month_days' => [-1]]);
        $this->assertSchedule([Chore::ON, null, null, 'FREQ=MONTHLY;BYMONTHDAY=-1', '2026-10-07', '2026-10-31'], $monthly);

        $after = $this->create(['title' => 'Clean the oven', 'repeats' => 'after', 'every' => 3, 'unit' => 'day']);
        $this->assertSchedule([Chore::AFTER, 3, 'day', null, null, '2026-10-07'], $after);
    }

    public function test_the_edit_page_opens_with_the_schedule_the_form_saved(): void
    {
        $forms = [
            ['repeats' => 'once', 'due_on' => '2026-10-20'],
            ['repeats' => 'once', 'due_on' => null],
            ['repeats' => 'daily'],
            ['repeats' => 'weekly', 'weekdays' => [1, 4], 'every_weeks' => 2],
            ['repeats' => 'weekly', 'weekdays' => [0, 6], 'every_weeks' => 1],
            ['repeats' => 'monthly', 'month_days' => [1, -1]],
            ['repeats' => 'after', 'every' => 3, 'unit' => 'month'],
        ];

        foreach ($forms as $form) {
            $details = ['title' => 'Water the plants', 'description' => 'The ones in the hall', 'points' => 5];
            $chore   = $this->create([...$details, ...$form]);

            $response = $this->get(route('chores.edit', $chore))->assertOk();

            $this->assertSame('Chores/Form', $response->inertiaPage()['component']);
            $this->assertSame(['id' => $chore->id, ...$details, ...$form], $response->inertiaProps('chore'));
        }
    }

    public function test_changing_a_weekly_chores_days_moves_its_next_due_date_straight_away_but_changing_only_its_details_does_not(): void
    {
        $chore = Chore::factory()->create([
            'title'       => 'Take out bins',
            'rule'        => 'FREQ=WEEKLY;BYDAY=MO,TH',
            'starts_on'   => '2026-09-01',
            'next_due_on' => '2026-10-05',
        ]);
        $form = ['title' => 'Take out the bins', 'description' => 'Both of them', 'points' => 20, 'repeats' => 'weekly', 'weekdays' => [1, 4], 'every_weeks' => 1];

        $this->put(route('chores.update', $chore), $form)->assertRedirect(route('chores.show', $chore));

        $chore->refresh();
        $this->assertSame(['Take out the bins', 'Both of them', 20], [$chore->title, $chore->description, $chore->points]);
        $this->assertSchedule([Chore::ON, null, null, 'FREQ=WEEKLY;BYDAY=MO,TH', '2026-09-01', '2026-10-05'], $chore);

        $this->put(route('chores.update', $chore), [...$form, 'weekdays' => [2, 5]])->assertRedirect(route('chores.show', $chore));

        // Friday is the first Tuesday or Friday on or after Wednesday 7 October.
        $this->assertSchedule([Chore::ON, null, null, 'FREQ=WEEKLY;BYDAY=TU,FR', '2026-10-07', '2026-10-09'], $chore->refresh());
    }

    public function test_changing_a_chore_to_after_its_done_makes_it_due_that_long_after_its_latest_completion(): void
    {
        $user  = User::factory()->create();
        $chore = Chore::factory()->create(['title' => 'Clean the oven', 'starts_on' => '2026-09-01', 'next_due_on' => '2026-10-06']);
        Completion::factory()->for($user)->for($chore)->create(['due_on' => '2026-10-01', 'completed_at' => '2026-10-01 09:00:00']);
        Completion::factory()->for($user)->for($chore)->create(['due_on' => '2026-10-05', 'completed_at' => '2026-10-05 21:00:00']);
        $never = Chore::factory()->create(['title' => 'Descale the kettle', 'starts_on' => '2026-09-01', 'next_due_on' => '2026-10-06']);
        $form  = ['repeats' => 'after', 'every' => 3, 'unit' => 'day'];

        $this->put(route('chores.update', $chore), ['title' => 'Clean the oven', ...$form])->assertRedirect();
        $this->put(route('chores.update', $never), ['title' => 'Descale the kettle', ...$form])->assertRedirect();

        // Last done two days ago, on 5 October, so due tomorrow.
        $this->assertSchedule([Chore::AFTER, 3, 'day', null, null, '2026-10-08'], $chore->refresh());
        $this->assertSchedule([Chore::AFTER, 3, 'day', null, null, '2026-10-07'], $never->refresh());
    }

    public function test_a_finished_one_off_changed_to_a_repeating_schedule_is_due_again_but_one_that_stays_a_one_off_stays_finished(): void
    {
        $user    = User::factory()->create();
        $toDaily = Chore::factory()->once()->create(['title' => 'Feed the cat', 'next_due_on' => '2026-10-07']);
        $toAfter = Chore::factory()->once()->create(['title' => 'Clean the oven', 'next_due_on' => '2026-10-04']);
        $stays   = Chore::factory()->once()->create(['title' => 'Buy a birthday card', 'next_due_on' => '2026-10-07']);

        $this->travelTo(CarbonImmutable::create(2026, 10, 4, 10, 0, 0, 'Europe/London'));
        $this->post(route('chores.complete', $toAfter), ['user_id' => $user->id, 'due_on' => '2026-10-04'])->assertRedirect();
        $this->travelTo(CarbonImmutable::create(2026, 10, 7, 12, 0, 0, 'Europe/London'));
        $this->post(route('chores.complete', $toDaily), ['user_id' => $user->id, 'due_on' => '2026-10-07'])->assertRedirect();
        $this->post(route('chores.complete', $stays), ['user_id' => $user->id, 'due_on' => '2026-10-07'])->assertRedirect();

        $this->put(route('chores.update', $toDaily), ['title' => 'Feed the cat', 'repeats' => 'daily'])->assertRedirect();
        $this->put(route('chores.update', $toAfter), ['title' => 'Clean the oven', 'repeats' => 'after', 'every' => 3, 'unit' => 'day'])
            ->assertRedirect();
        $this->put(route('chores.update', $stays), ['title' => 'Buy a card', 'repeats' => 'once', 'due_on' => '2026-10-07'])
            ->assertRedirect();

        $this->assertNull($toDaily->refresh()->finished_at);
        $this->assertSchedule([Chore::ON, null, null, 'FREQ=DAILY', '2026-10-07', '2026-10-07'], $toDaily);
        $this->assertNull($toAfter->refresh()->finished_at);
        // Done on 4 October, so due 3 days later.
        $this->assertSchedule([Chore::AFTER, 3, 'day', null, null, '2026-10-07'], $toAfter);
        $this->assertNotNull($stays->refresh()->finished_at);
        $this->assertSame('Buy a card', $stays->title);

        $chores = $this->get(route('home'))->assertOk()->inertiaProps('chores');
        $this->assertEqualsCanonicalizing([$toDaily->id, $toAfter->id], array_column($chores, 'id'));
    }

    public function test_weekly_with_no_days_and_monthly_with_no_dates_are_rejected(): void
    {
        $this->from(route('chores.create'))
            ->post(route('chores.store'), ['title' => 'Water the plants', 'repeats' => 'weekly', 'weekdays' => [], 'every_weeks' => 1])
            ->assertRedirect(route('chores.create'))
            ->assertSessionHasErrors('weekdays');

        $this->from(route('chores.create'))
            ->post(route('chores.store'), ['title' => 'Pay the window cleaner', 'repeats' => 'monthly', 'month_days' => []])
            ->assertRedirect(route('chores.create'))
            ->assertSessionHasErrors('month_days');

        $this->assertSame(0, Chore::query()->count());
    }

    private function create(array $form): Chore
    {
        $response = $this->post(route('chores.store'), $form);
        $chore    = Chore::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('chores.show', $chore));

        return $chore;
    }

    /**
     * @param array{0: string, 1: ?int, 2: ?string, 3: ?string, 4: ?string, 5: ?string} $expected
     *        schedule, every, unit, rule, starts_on and next_due_on
     */
    private function assertSchedule(array $expected, Chore $chore): void
    {
        $this->assertSame($expected, [
            $chore->schedule,
            $chore->every,
            $chore->unit,
            $chore->rule,
            $chore->starts_on?->toDateString(),
            $chore->next_due_on?->toDateString(),
        ]);
    }
}
