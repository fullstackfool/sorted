<?php

namespace Tests\Feature;

use App\Models\Chore;
use App\Models\Completion;
use App\Models\User;
use App\Support\Scoreboard;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class ScoreboardTest extends TestCase
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

    public function test_the_todays_page_scoreboard_week_runs_from_monday_to_sunday(): void
    {
        $other = User::factory()->create();
        $this->done($this->user, '2026-10-05 00:00:00', 10);
        $this->done($this->user, '2026-10-04 23:59:59', 5);
        $this->done($other, '2026-10-07 08:00:00', 7);

        $users = $this->get(route('home'))->assertOk()->inertiaProps('users');

        $this->assertSame([
            [
                'id'             => $this->user->id,
                'name'           => $this->user->name,
                'avatar_url'     => null,
                'weekly_points'  => 10,
                'today_points'   => 0,
                'current_streak' => 0,
            ],
            [
                'id'             => $other->id,
                'name'           => $other->name,
                'avatar_url'     => null,
                'weekly_points'  => 7,
                'today_points'   => 7,
                'current_streak' => 1,
            ],
        ], $users);
    }

    public function test_points_are_the_ones_saved_when_the_chore_was_done(): void
    {
        $chore = Chore::factory()->create(['points' => 10, 'next_due_on' => '2026-10-07']);
        $this->post(route('chores.complete', $chore), ['user_id' => $this->user->id, 'due_on' => '2026-10-07'])
            ->assertRedirect();

        $chore->update(['points' => 50]);

        $this->assertScore(weekly: 10, today: 10, streak: 1);
    }

    public function test_completions_of_a_deleted_chore_still_count(): void
    {
        $completion = $this->done($this->user, '2026-10-07 09:00:00', 15);

        $completion->chore->delete();

        $this->assertScore(weekly: 15, today: 15, streak: 1);
    }

    public function test_skips_add_no_points_and_do_not_extend_a_streak(): void
    {
        $this->done($this->user, '2026-10-05 18:00:00', 10);
        $chore = Chore::factory()->create(['points' => 20, 'starts_on' => '2026-10-01', 'next_due_on' => '2026-10-06']);

        $this->travelTo(CarbonImmutable::create(2026, 10, 6, 12, 0, 0, 'Europe/London'));
        $this->post(route('chores.skip', $chore), ['user_id' => $this->user->id, 'due_on' => '2026-10-06']);
        $this->travelTo(CarbonImmutable::create(2026, 10, 7, 12, 0, 0, 'Europe/London'));
        $this->post(route('chores.skip', $chore), ['user_id' => $this->user->id, 'due_on' => '2026-10-07']);

        $this->assertSame(2, $chore->completions()->where('status', Completion::SKIPPED)->count());
        $this->assertScore(weekly: 10, today: 0, streak: 0);
    }

    public function test_a_streak_counts_back_from_yesterday_until_today_has_something_done(): void
    {
        $lapsed = User::factory()->create();
        $this->done($lapsed, '2026-10-05 18:00:00', 10);
        $this->done($this->user, '2026-10-05 18:00:00', 10);
        $this->done($this->user, '2026-10-06 18:00:00', 10);

        $this->assertScore(weekly: 20, today: 0, streak: 2);

        $this->done($this->user, '2026-10-07 09:00:00', 10);

        $this->assertScore(weekly: 30, today: 10, streak: 3);
        $this->assertSame(0, Scoreboard::forEveryone()->firstWhere('id', $lapsed->id)['current_streak']);
    }

    private function done(User $user, string $completedAt, int $points): Completion
    {
        return Completion::factory()->for($user)->create(['completed_at' => $completedAt, 'points' => $points]);
    }

    private function assertScore(int $weekly, int $today, int $streak): void
    {
        $score = Scoreboard::forEveryone()->firstWhere('id', $this->user->id);

        $this->assertSame(
            ['weekly_points' => $weekly, 'today_points' => $today, 'current_streak' => $streak],
            Arr::only($score, ['weekly_points', 'today_points', 'current_streak']),
        );
    }
}
