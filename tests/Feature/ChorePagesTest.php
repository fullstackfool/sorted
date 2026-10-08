<?php

namespace Tests\Feature;

use App\Models\Chore;
use App\Models\Completion;
use App\Models\Label;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChorePagesTest extends TestCase
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

    public function test_the_chores_list_shows_every_chore_not_deleted_with_its_schedule_next_due_date_and_whether_its_finished(): void
    {
        $label = Label::create(['name' => 'Kitchen', 'color' => '#ff0000']);
        $bins  = Chore::factory()->create([
            'title'       => 'Take out bins',
            'points'      => 15,
            'rule'        => 'FREQ=WEEKLY;BYDAY=TU,WE,TH',
            'starts_on'   => '2026-09-01',
            'next_due_on' => '2026-10-08',
        ]);
        $bins->users()->attach($this->user);
        $bins->labels()->attach($label);
        $shed = Chore::factory()->once()->create(['title' => 'Fix the shed door', 'next_due_on' => null]);
        $card = Chore::factory()->once()->create(['title' => 'Buy a birthday card', 'next_due_on' => '2026-10-07']);
        $this->post(route('chores.complete', $card), ['user_id' => $this->user->id, 'due_on' => '2026-10-07'])
            ->assertRedirect();
        $oven = Chore::factory()->after()->create(['title' => 'Clean the oven', 'next_due_on' => '2026-10-14']);
        Chore::factory()->create(['title' => 'Wash the car'])->delete();

        $response = $this->get(route('chores.index'))->assertOk();

        $this->assertSame('Chores/Index', $response->inertiaPage()['component']);
        $this->assertSame([
            [
                'id'                   => $card->id,
                'title'                => 'Buy a birthday card',
                'points'               => 10,
                'schedule_description' => 'One-off',
                'next_due_on'          => '2026-10-07',
                'finished'             => true,
                'assigned_users'       => [],
                'labels'               => [],
            ],
            [
                'id'                   => $oven->id,
                'title'                => 'Clean the oven',
                'points'               => 10,
                'schedule_description' => "1 week after it's done",
                'next_due_on'          => '2026-10-14',
                'finished'             => false,
                'assigned_users'       => [],
                'labels'               => [],
            ],
            [
                'id'                   => $shed->id,
                'title'                => 'Fix the shed door',
                'points'               => 10,
                'schedule_description' => 'One-off',
                'next_due_on'          => null,
                'finished'             => false,
                'assigned_users'       => [],
                'labels'               => [],
            ],
            [
                'id'                   => $bins->id,
                'title'                => 'Take out bins',
                'points'               => 15,
                'schedule_description' => 'Every Tue, Wed & Thu',
                'next_due_on'          => '2026-10-08',
                'finished'             => false,
                'assigned_users'       => [['id' => $this->user->id, 'name' => $this->user->name]],
                'labels'               => [['id' => $label->id, 'name' => 'Kitchen', 'color' => '#ff0000']],
            ],
        ], $response->inertiaProps('chores'));
    }

    public function test_deleting_a_chore_hides_it_but_keeps_its_completions_and_the_points_earned_from_it(): void
    {
        $chore = Chore::factory()->create(['title' => 'Do the dishes', 'points' => 15, 'next_due_on' => '2026-10-07']);
        $kept  = Chore::factory()->create(['title' => 'Water plants']);
        $this->post(route('chores.complete', $chore), ['user_id' => $this->user->id, 'due_on' => '2026-10-07'])
            ->assertRedirect();

        $this->delete(route('chores.destroy', $chore))
            ->assertRedirect(route('chores.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($chore);
        $this->assertSame([$kept->id], array_column($this->get(route('chores.index'))->inertiaProps('chores'), 'id'));
        $this->assertSame(1, Completion::query()->where('chore_id', $chore->id)->count());
        $score = collect($this->get(route('home'))->inertiaProps('users'))->firstWhere('id', $this->user->id);
        $this->assertSame(15, $score['weekly_points']);
    }

    public function test_a_chores_page_counts_done_and_skipped_completions_totals_their_saved_points_and_lists_recent_history(): void
    {
        $other = User::factory()->create();
        $label = Label::create(['name' => 'Kitchen', 'color' => '#ff0000']);
        $chore = Chore::factory()->create([
            'title'       => 'Do the dishes',
            'description' => 'Rinse first',
            'points'      => 15,
            'starts_on'   => '2026-09-01',
            'next_due_on' => '2026-10-08',
        ]);
        $chore->users()->attach($this->user);
        $chore->labels()->attach($label);

        $old = Completion::factory()->for($this->user)->for($chore)->create([
            'points'       => 10,
            'due_on'       => '2026-08-20',
            'completed_at' => '2026-08-20 09:00:00',
        ]);
        $byOther = Completion::factory()->for($other)->for($chore)->create([
            'points'       => 15,
            'due_on'       => '2026-10-05',
            'completed_at' => '2026-10-05 18:00:00',
        ]);
        $skip = Completion::factory()->for($this->user)->for($chore)->create([
            'status'       => Completion::SKIPPED,
            'points'       => 0,
            'due_on'       => '2026-10-06',
            'completed_at' => '2026-10-06 08:00:00',
        ]);
        $latest = Completion::factory()->for($this->user)->for($chore)->create([
            'points'       => 15,
            'due_on'       => '2026-10-07',
            'completed_at' => '2026-10-07 09:00:00',
        ]);
        Completion::factory()->for($other)->create(['points' => 99]);
        $chore->update(['points' => 50]);

        $response = $this->get(route('chores.show', $chore))->assertOk();
        $props    = $response->inertiaProps();

        $this->assertSame('Chores/Show', $response->inertiaPage()['component']);
        $this->assertSame([
            'id'                   => $chore->id,
            'title'                => 'Do the dishes',
            'description'          => 'Rinse first',
            'points'               => 50,
            'schedule_description' => 'Every day',
            'next_due_on'          => '2026-10-08',
            'finished_at'          => null,
            'users'                => [['id' => $this->user->id, 'name' => $this->user->name]],
            'labels'               => [['id' => $label->id, 'name' => 'Kitchen', 'color' => '#ff0000']],
        ], $props['chore']);
        $this->assertSame(['total_completions' => 3, 'total_skips' => 1, 'total_points_earned' => 10 + 15 + 15], $props['analytics']);

        $me = ['id' => $this->user->id, 'name' => $this->user->name];
        $this->assertSame([
            $this->historyEntry($latest, Completion::DONE, 15, '2026-10-07 09:00:00', '2026-10-07', $me),
            $this->historyEntry($skip, Completion::SKIPPED, 0, '2026-10-06 08:00:00', '2026-10-06', $me),
            $this->historyEntry($byOther, Completion::DONE, 15, '2026-10-05 18:00:00', '2026-10-05', ['id' => $other->id, 'name' => $other->name]),
        ], $props['completionHistory']);
        $this->assertNotContains($old->id, array_column($props['completionHistory'], 'id'));

        $this->assertSame([
            ['user' => $me, 'completions' => 2],
            ['user' => ['id' => $other->id, 'name' => $other->name], 'completions' => 1],
        ], $props['userStats']);
    }

    public function test_a_deleted_chores_page_is_not_found(): void
    {
        $chore = Chore::factory()->create();

        $this->delete(route('chores.destroy', $chore))->assertRedirect(route('chores.index'));

        $this->get(route('chores.show', $chore))->assertNotFound();
    }

    private function historyEntry(Completion $completion, string $status, int $points, string $completedAt, string $dueOn, array $user): array
    {
        return [
            'id'           => $completion->id,
            'status'       => $status,
            'points'       => $points,
            'completed_at' => CarbonImmutable::parse($completedAt)->toJSON(),
            'due_on'       => $dueOn,
            'user'         => $user,
        ];
    }
}
