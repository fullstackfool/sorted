<?php

namespace Tests\Feature;

use App\Models\Chore;
use App\Models\Completion;
use App\Models\Label;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChoreModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_date_set_from_a_datetime_is_stored_as_a_plain_date(): void
    {
        // Half past midnight in British Summer Time, so a timezone slip would land on the previous day.
        $chore = Chore::factory()->create([
            'next_due_on' => Carbon::create(2026, 7, 14, 0, 30, 15, 'Europe/London'),
        ]);

        $this->assertSame('2026-07-14', DB::table('chores')->where('id', $chore->id)->value('next_due_on'));

        $this->assertInstanceOf(CarbonImmutable::class, $chore->next_due_on);
        $this->assertSame('2026-07-14 00:00:00 Europe/London', $chore->next_due_on->format('Y-m-d H:i:s e'));

        $dueOn = $chore->fresh()->next_due_on;
        $this->assertInstanceOf(CarbonImmutable::class, $dueOn);
        $this->assertSame('2026-07-14 00:00:00 Europe/London', $dueOn->format('Y-m-d H:i:s e'));

        $this->assertSame('2026-07-14', $chore->fresh()->toArray()['next_due_on']);
        $this->assertSame('2026-07-14', json_decode($chore->fresh()->toJson(), true)['next_due_on']);
    }

    public function test_null_due_date_stays_null(): void
    {
        $chore = Chore::factory()->once()->create(['next_due_on' => null]);

        $this->assertNull(DB::table('chores')->where('id', $chore->id)->value('next_due_on'));
        $this->assertNull($chore->fresh()->next_due_on);
        $this->assertNull($chore->fresh()->toArray()['next_due_on']);
    }

    public function test_people_labels_and_completions_relate_both_ways(): void
    {
        $chore = Chore::factory()->create();
        $user = User::factory()->create();
        $label = Label::create(['name' => 'Kitchen', 'color' => '#ff0000']);

        $chore->users()->attach($user);
        $chore->labels()->attach($label);
        $completion = Completion::factory()->for($chore)->for($user)->create();

        $chore = $chore->fresh();
        $user = $user->fresh();
        $label = $label->fresh();
        $completion = $completion->fresh();

        $this->assertSame([$user->id], $chore->users->modelKeys());
        $this->assertSame([$chore->id], $user->chores->modelKeys());
        $this->assertSame([$label->id], $chore->labels->modelKeys());
        $this->assertSame([$chore->id], $label->chores->modelKeys());
        $this->assertSame([$completion->id], $chore->completions->modelKeys());
        $this->assertSame([$completion->id], $user->completions->modelKeys());
        $this->assertTrue($completion->chore->is($chore));
        $this->assertTrue($completion->user->is($user));
    }

    public function test_deleting_a_chore_soft_deletes_it_and_keeps_its_completions(): void
    {
        $chore = Chore::factory()->create();
        $completion = Completion::factory()->for($chore)->create(['points' => 15]);

        $chore->delete();

        $this->assertFalse(Chore::query()->whereKey($chore->id)->exists());
        $this->assertSoftDeleted($chore);
        $this->assertModelExists($completion);

        $completion = $completion->fresh();
        $this->assertSame(15, $completion->points);
        $this->assertTrue($completion->chore->is($chore));
        $this->assertTrue($completion->chore->trashed());
    }
}
