<?php

namespace Tests\Feature;

use App\Models\Chore;
use App\Models\Completion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CompletingChoresTest extends TestCase
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

    public function test_completing_a_daily_chore_records_who_when_and_its_points_and_moves_it_to_tomorrow(): void
    {
        $chore = Chore::factory()->create(['points' => 10, 'next_due_on' => '2026-10-07']);
        $chore->update(['points' => 25]);

        $this->post(route('chores.complete', $chore), ['user_id' => $this->user->id, 'due_on' => '2026-10-07'])
            ->assertRedirect();

        $completion = $chore->completions()->sole();
        $this->assertSame($this->user->id, $completion->user_id);
        $this->assertSame(Completion::DONE, $completion->status);
        $this->assertSame('2026-10-07', $completion->due_on->format('Y-m-d'));
        $this->assertSame(25, $completion->points);
        $this->assertSame('2026-10-07 12:00:00', $completion->completed_at->format('Y-m-d H:i:s'));
        $this->assertDueOn('2026-10-08', $chore);
    }

    public function test_skipping_records_no_points_and_moves_the_chore_on_the_same_way(): void
    {
        $chore = Chore::factory()->create(['points' => 25, 'next_due_on' => '2026-10-07']);

        $this->post(route('chores.skip', $chore), ['user_id' => $this->user->id, 'due_on' => '2026-10-07'])
            ->assertRedirect();

        $completion = $chore->completions()->sole();
        $this->assertSame($this->user->id, $completion->user_id);
        $this->assertSame(Completion::SKIPPED, $completion->status);
        $this->assertSame('2026-10-07', $completion->due_on->format('Y-m-d'));
        $this->assertSame(0, $completion->points);
        $this->assertSame('2026-10-07 12:00:00', $completion->completed_at->format('Y-m-d H:i:s'));
        $this->assertDueOn('2026-10-08', $chore);
    }

    public function test_the_same_request_twice_is_recorded_once_and_a_stale_due_date_records_nothing(): void
    {
        $chore = Chore::factory()->create(['next_due_on' => '2026-10-07']);

        $this->record('chores.complete', $chore, '2026-10-07');
        $this->record('chores.complete', $chore, '2026-10-07');

        $this->assertSame(1, $chore->completions()->count());
        $this->assertDueOn('2026-10-08', $chore);

        $this->record('chores.skip', $chore, '2026-10-05');
        $this->record('chores.complete', $chore, null);

        $this->assertSame(1, $chore->completions()->count());
        $this->assertDueOn('2026-10-08', $chore);
    }

    public function test_a_dated_one_off_is_finished_and_a_second_request_records_nothing(): void
    {
        $chore = Chore::factory()->once()->create(['next_due_on' => '2026-10-09']);

        $this->record('chores.complete', $chore, '2026-10-09');

        $chore->refresh();
        $this->assertSame('2026-10-07 12:00:00', $chore->finished_at->format('Y-m-d H:i:s'));
        $this->assertDueOn('2026-10-09', $chore);
        $this->assertSame('2026-10-09', $chore->completions()->sole()->due_on->format('Y-m-d'));

        $this->record('chores.complete', $chore, '2026-10-09');
        $this->record('chores.skip', $chore, '2026-10-09');

        $this->assertSame(1, $chore->completions()->count());
    }

    public function test_an_undated_one_off_is_recorded_once_and_finished(): void
    {
        $chore = Chore::factory()->once()->create(['next_due_on' => null]);

        $this->record('chores.complete', $chore, null);
        $this->record('chores.complete', $chore, null);

        $chore->refresh();
        $this->assertNotNull($chore->finished_at);
        $this->assertNull($chore->next_due_on);
        $this->assertNull($chore->completions()->sole()->due_on);
    }

    public function test_an_every_three_days_after_chore_done_late_is_next_due_three_days_from_today(): void
    {
        $chore = Chore::factory()->after()->create(['every' => 3, 'unit' => 'day', 'next_due_on' => '2026-10-05']);

        $this->record('chores.complete', $chore, '2026-10-05');

        $this->assertDueOn('2026-10-10', $chore);
    }

    public function test_a_daily_chore_five_days_late_is_next_due_tomorrow(): void
    {
        $chore = Chore::factory()->create(['starts_on' => '2026-09-01', 'next_due_on' => '2026-10-02']);

        $this->record('chores.complete', $chore, '2026-10-02');

        $this->assertDueOn('2026-10-08', $chore);
    }

    public function test_a_wednesday_chore_done_on_the_tuesday_before_is_next_due_the_following_wednesday(): void
    {
        $this->travelTo(CarbonImmutable::create(2026, 10, 6, 12, 0, 0, 'Europe/London'));
        $chore = Chore::factory()->create([
            'rule'        => 'FREQ=WEEKLY;BYDAY=WE',
            'starts_on'   => '2026-09-02',
            'next_due_on' => '2026-10-07',
        ]);

        $this->record('chores.complete', $chore, '2026-10-07');

        $this->assertDueOn('2026-10-14', $chore);
    }

    public function test_undoing_the_latest_completion_deletes_it_and_puts_the_chore_back_on_its_previous_due_date(): void
    {
        $chore = Chore::factory()->create(['starts_on' => '2026-09-01', 'next_due_on' => '2026-10-02']);
        $this->record('chores.complete', $chore, '2026-10-02');
        $completion = $chore->completions()->sole();

        $this->post(route('completions.undo', $completion))->assertRedirect();

        $this->assertModelMissing($completion);
        $this->assertDueOn('2026-10-02', $chore);
    }

    public function test_undoing_a_finished_one_off_makes_it_unfinished_again(): void
    {
        $chore = Chore::factory()->once()->create(['next_due_on' => '2026-10-09']);
        $this->record('chores.complete', $chore, '2026-10-09');

        $this->post(route('completions.undo', $chore->completions()->sole()))->assertRedirect();

        $chore->refresh();
        $this->assertNull($chore->finished_at);
        $this->assertDueOn('2026-10-09', $chore);
        $this->assertSame(0, $chore->completions()->count());
    }

    public function test_undoing_an_older_completion_changes_nothing(): void
    {
        // Both are recorded in the same second, so only the id tells which is more recent.
        $chore = Chore::factory()->create(['next_due_on' => '2026-10-07']);
        $this->record('chores.complete', $chore, '2026-10-07');
        $this->record('chores.skip', $chore, '2026-10-08');
        [$older, $latest] = $chore->completions()->orderBy('id')->get()->all();
        $this->assertDueOn('2026-10-09', $chore);

        $this->post(route('completions.undo', $older))->assertRedirect();

        $this->assertModelExists($older);
        $this->assertModelExists($latest);
        $this->assertDueOn('2026-10-09', $chore);

        $this->post(route('completions.undo', $latest));
        $this->assertModelMissing($latest);
        $this->assertDueOn('2026-10-08', $chore);

        $this->post(route('completions.undo', $older));
        $this->assertModelMissing($older);
        $this->assertDueOn('2026-10-07', $chore);
    }

    public function test_complete_and_skip_reject_a_missing_or_unknown_person(): void
    {
        $chore = Chore::factory()->create(['next_due_on' => '2026-10-07']);

        foreach (['chores.complete', 'chores.skip'] as $route) {
            $this->post(route($route, $chore), ['due_on' => '2026-10-07'])
                ->assertSessionHasErrors('user_id');
            $this->post(route($route, $chore), ['user_id' => $this->user->id + 1, 'due_on' => '2026-10-07'])
                ->assertSessionHasErrors('user_id');
        }

        $this->assertSame(0, Completion::count());
        $this->assertDueOn('2026-10-07', $chore);
    }

    private function record(string $route, Chore $chore, ?string $dueOn): TestResponse
    {
        return $this->post(route($route, $chore), ['user_id' => $this->user->id, 'due_on' => $dueOn])
            ->assertRedirect();
    }

    private function assertDueOn(?string $expected, Chore $chore): void
    {
        $this->assertSame($expected, $chore->fresh()->next_due_on?->format('Y-m-d'));
    }
}
