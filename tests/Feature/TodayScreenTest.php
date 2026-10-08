<?php

namespace Tests\Feature;

use App\Models\Chore;
use App\Models\Completion;
use App\Models\Label;
use App\Models\User;
use App\Support\Schedule;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class TodayScreenTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // A Wednesday.
        $this->travelTo(CarbonImmutable::create(2026, 10, 7, 12, 0, 0, 'Europe/London'));
        $this->user = User::factory()->create();
    }

    public function test_chores_are_grouped_overdue_today_and_tomorrow_and_later_ones_are_not_listed(): void
    {
        $label   = Label::create(['name' => 'Kitchen', 'color' => '#ff0000']);
        $overdue = Chore::factory()->create([
            'title'       => 'Do the dishes',
            'description' => 'Rinse first',
            'points'      => 15,
            'starts_on'   => '2026-09-01',
            'next_due_on' => '2026-10-04',
        ]);
        $overdue->users()->attach($this->user);
        $overdue->labels()->attach($label);
        Chore::factory()->create(['title' => 'Water plants', 'next_due_on' => '2026-10-07']);
        Chore::factory()->create(['title' => 'Feed the cat', 'next_due_on' => '2026-10-07']);
        Chore::factory()->create(['title' => 'Take out bins', 'next_due_on' => '2026-10-08']);
        Chore::factory()->create(['title' => 'Mow the lawn', 'next_due_on' => '2026-10-09']);

        $chores = $this->get(route('home'))->assertOk()->inertiaProps('chores');

        $this->assertSame([
            ['Do the dishes', 'overdue'],
            ['Feed the cat', 'today'],
            ['Water plants', 'today'],
            ['Take out bins', 'tomorrow'],
        ], array_map(fn (array $chore) => [$chore['title'], $chore['group']], $chores));
        $this->assertSame([
            'id'                   => $overdue->id,
            'title'                => 'Do the dishes',
            'description'          => 'Rinse first',
            'points'               => 15,
            'schedule_description' => Schedule::of($overdue)->description(),
            'assigned_users'       => [['id' => $this->user->id, 'name' => $this->user->name]],
            'labels'               => [['id' => $label->id, 'name' => 'Kitchen', 'color' => '#ff0000']],
            'due_on'               => '2026-10-04',
            'group'                => 'overdue',
        ], $chores[0]);
    }

    public function test_finished_one_offs_and_deleted_chores_are_not_listed(): void
    {
        $kept    = Chore::factory()->create(['next_due_on' => '2026-10-07']);
        $oneOff  = Chore::factory()->once()->create(['next_due_on' => '2026-10-07']);
        $deleted = Chore::factory()->create(['next_due_on' => '2026-10-07']);
        $this->post(route('chores.complete', $oneOff), ['user_id' => $this->user->id, 'due_on' => '2026-10-07'])
            ->assertRedirect();
        $deleted->delete();

        $chores = $this->get(route('home'))->assertOk()->inertiaProps('chores');

        $this->assertSame([$kept->id], array_column($chores, 'id'));
    }

    public function test_a_one_off_with_no_date_is_listed_any_time_after_tomorrow(): void
    {
        $undated = Chore::factory()->once()->create(['title' => 'Fix the shed door', 'next_due_on' => null]);
        Chore::factory()->create(['title' => 'Take out bins', 'next_due_on' => '2026-10-08']);

        $chores = $this->get(route('home'))->assertOk()->inertiaProps('chores');

        $this->assertSame([
            ['Take out bins', 'tomorrow', '2026-10-08'],
            ['Fix the shed door', 'anytime', null],
        ], array_map(fn (array $chore) => [$chore['title'], $chore['group'], $chore['due_on']], $chores));
        $this->assertSame($undated->id, $chores[1]['id']);
    }

    public function test_chores_done_or_skipped_today_are_listed_latest_first_and_only_each_chores_latest_can_be_undone(): void
    {
        $other = User::factory()->create();
        $daily = Chore::factory()->create(['title' => 'Feed the cat', 'points' => 10, 'next_due_on' => '2026-10-07']);
        $late  = Chore::factory()->after()->create(['title' => 'Clean the oven', 'points' => 20, 'next_due_on' => '2026-09-30']);
        Completion::factory()->for($this->user)->create(['completed_at' => '2026-10-06 23:59:59']);

        $this->travelTo(CarbonImmutable::create(2026, 10, 7, 0, 0, 0, 'Europe/London'));
        $this->post(route('chores.complete', $daily), ['user_id' => $other->id, 'due_on' => '2026-10-07']);
        $this->travelTo(CarbonImmutable::create(2026, 10, 7, 10, 0, 0, 'Europe/London'));
        $this->post(route('chores.skip', $daily), ['user_id' => $this->user->id, 'due_on' => '2026-10-08']);
        $this->travelTo(CarbonImmutable::create(2026, 10, 7, 11, 0, 0, 'Europe/London'));
        $this->post(route('chores.complete', $late), ['user_id' => $this->user->id, 'due_on' => '2026-09-30']);
        $this->travelTo(CarbonImmutable::create(2026, 10, 7, 12, 0, 0, 'Europe/London'));
        $late->update(['points' => 50]);

        $done = $this->get(route('home'))->assertOk()->inertiaProps('done');

        [$dailyDone, $dailySkipped] = $daily->completions()->orderBy('completed_at')->get();
        $this->assertSame([
            [
                'id'           => $late->completions()->sole()->id,
                'chore_id'     => $late->id,
                'title'        => 'Clean the oven',
                'points'       => 20,
                'status'       => Completion::DONE,
                'completed_by' => ['id' => $this->user->id, 'name' => $this->user->name],
                'can_undo'     => true,
            ],
            [
                'id'           => $dailySkipped->id,
                'chore_id'     => $daily->id,
                'title'        => 'Feed the cat',
                'points'       => 0,
                'status'       => Completion::SKIPPED,
                'completed_by' => ['id' => $this->user->id, 'name' => $this->user->name],
                'can_undo'     => true,
            ],
            [
                'id'           => $dailyDone->id,
                'chore_id'     => $daily->id,
                'title'        => 'Feed the cat',
                'points'       => 10,
                'status'       => Completion::DONE,
                'completed_by' => ['id' => $other->id, 'name' => $other->name],
                'can_undo'     => false,
            ],
        ], array_map(fn (array $item) => Arr::except($item, 'completed_at'), $done));
        $this->assertTrue(CarbonImmutable::parse($done[0]['completed_at'])->eq(CarbonImmutable::create(2026, 10, 7, 11, 0, 0, 'Europe/London')));
    }

    public function test_a_chore_done_today_then_deleted_leaves_done_today_and_its_completion_cannot_be_undone(): void
    {
        $deleted = Chore::factory()->create(['title' => 'Water the plants', 'points' => 5, 'next_due_on' => '2026-10-07']);
        $kept    = Chore::factory()->create(['title' => 'Feed the cat', 'points' => 10, 'next_due_on' => '2026-10-07']);
        foreach ([$deleted, $kept] as $chore) {
            $this->post(route('chores.complete', $chore), ['user_id' => $this->user->id, 'due_on' => '2026-10-07'])->assertRedirect();
        }
        $completion = $deleted->completions()->sole();
        $this->delete(route('chores.destroy', $deleted))->assertRedirect();

        $this->assertSame([$kept->id], array_column($this->get(route('home'))->assertOk()->inertiaProps('done'), 'chore_id'));

        $this->post(route('completions.undo', $completion))->assertRedirect();

        $this->assertModelExists($completion);
        $score = collect($this->get(route('home'))->inertiaProps('users'))->firstWhere('id', $this->user->id);
        $this->assertSame(5 + 10, $score['today_points']);
    }
}
