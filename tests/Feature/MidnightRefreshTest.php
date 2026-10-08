<?php

namespace Tests\Feature;

use App\Support\SyncStamp;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;
use Tests\TestCase;

class MidnightRefreshTest extends TestCase
{
    public function test_the_scheduler_tells_open_pages_to_reload_at_midnight(): void
    {
        $refresh = $this->scheduledEvents()->firstWhere('description', 'refresh-open-pages');

        $this->assertNotNull($refresh);
        $this->assertSame('0 0 * * *', $refresh->expression);

        $before = SyncStamp::current();
        $refresh->run($this->app);

        $this->assertNotSame($before, SyncStamp::current());
    }

    public function test_the_old_task_generator_is_no_longer_scheduled(): void
    {
        $this->assertFalse(
            $this->scheduledEvents()->contains(fn (Event $event) => str_contains((string) $event->command, 'tasks:generate')),
        );
    }

    /**
     * @return Collection<int, Event>
     */
    private function scheduledEvents(): Collection
    {
        return collect($this->app->make(Schedule::class)->events());
    }
}
