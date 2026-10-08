<?php

namespace Tests\Feature;

use App\Models\Chore;
use App\Models\Completion;
use App\Models\Label;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeoplePagesTest extends TestCase
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

    public function test_the_people_list_shows_each_persons_chores_done_count_and_total_points_from_completions(): void
    {
        $other   = User::factory()->create();
        $kept    = Chore::factory()->create(['points' => 99]);
        $deleted = Chore::factory()->create();
        $this->user->chores()->attach([$kept->id, $deleted->id]);
        $deleted->delete();
        Completion::factory()->for($this->user)->for($kept)->create(['points' => 10, 'completed_at' => '2026-09-20 10:00:00']);
        Completion::factory()->for($this->user)->for($deleted)->create(['points' => 15, 'completed_at' => '2026-10-06 10:00:00']);
        Completion::factory()->for($this->user)->for($kept)->create(['status' => Completion::SKIPPED, 'points' => 0]);

        $props = $this->get(route('users.index'))->assertOk()->inertiaProps();

        $this->assertSame([
            [
                'id'              => $this->user->id,
                'name'            => $this->user->name,
                'email'           => $this->user->email,
                'avatar_url'      => null,
                'tasks_assigned'  => 1,
                'tasks_completed' => 2,
                'total_points'    => 25,
            ],
            [
                'id'              => $other->id,
                'name'            => $other->name,
                'email'           => $other->email,
                'avatar_url'      => null,
                'tasks_assigned'  => 0,
                'tasks_completed' => 0,
                'total_points'    => 0,
            ],
        ], $props['userDetails']);
        $this->assertSame(15, $props['users'][0]['weekly_points']);
    }

    public function test_a_persons_page_shows_their_chores_stats_and_ten_latest_completions_with_chore_titles_and_points(): void
    {
        $laundry = Chore::factory()->create(['title' => 'Do laundry', 'points' => 20]);
        $pets    = Chore::factory()->create(['title' => 'Feed the pets']);
        $pets->labels()->attach(Label::create(['name' => 'Pets', 'color' => '#22c55e']));
        $this->user->chores()->attach([$laundry->id, $pets->id]);

        Completion::factory()->for($this->user)->for($laundry)->create(['points' => 20, 'completed_at' => '2026-10-06 18:00:00']);
        $skip = Completion::factory()->for($this->user)->for($pets)->create([
            'status'       => Completion::SKIPPED,
            'points'       => 0,
            'completed_at' => '2026-10-07 09:00:00',
        ]);
        Completion::factory()->count(9)->for($this->user)->for($pets)->sequence(
            fn ($sequence) => ['points' => 5, 'completed_at' => CarbonImmutable::parse('2026-10-01 08:00:00')->subDays($sequence->index)],
        )->create();
        $oldest = Completion::factory()->for($this->user)->for($pets)->create(['points' => 5, 'completed_at' => '2026-09-01 08:00:00']);
        Completion::factory()->create(['completed_at' => '2026-10-07 11:00:00']);
        $laundry->update(['points' => 50]);
        $laundry->delete();

        $props = $this->get(route('users.show', $this->user))->assertOk()->inertiaProps();

        $this->assertSame(['Feed the pets'], array_column($props['user']['chores'], 'title'));
        $this->assertSame(['Pets'], array_column($props['user']['chores'][0]['labels'], 'name'));
        $this->assertSame(['tasks_assigned' => 1, 'tasks_completed' => 11, 'total_points' => 20 + 10 * 5], $props['stats']);

        $recent = $props['recentCompletions'];
        $this->assertCount(10, $recent);
        $this->assertSame([
            'id'           => $skip->id,
            'status'       => Completion::SKIPPED,
            'points'       => 0,
            'completed_at' => CarbonImmutable::parse('2026-10-07 09:00:00')->toJSON(),
            'chore'        => ['id' => $pets->id, 'title' => 'Feed the pets'],
        ], $recent[0]);
        $this->assertSame(Completion::DONE, $recent[1]['status']);
        $this->assertSame(20, $recent[1]['points']);
        $this->assertSame(['id' => $laundry->id, 'title' => 'Do laundry'], $recent[1]['chore']);
        $this->assertNotContains($oldest->id, array_column($recent, 'id'));
    }
}
