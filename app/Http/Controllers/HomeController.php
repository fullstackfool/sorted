<?php

namespace App\Http\Controllers;

use App\Models\Chore;
use App\Models\Completion;
use App\Models\Label;
use App\Support\Schedule;
use App\Support\Scoreboard;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $today = today()->toImmutable();

        // Chores due by tomorrow, plus undated one-offs. Ordering by date with no date last keeps the groups in order.
        $chores = Chore::query()
            ->with(['users', 'labels'])
            ->whereNull('finished_at')
            ->where(fn ($query) => $query
                ->where('next_due_on', '<=', $today->addDay()->toDateString())
                ->orWhere(fn ($query) => $query->whereNull('next_due_on')->where('schedule', Chore::ONCE)))
            ->orderByRaw('next_due_on is null')
            ->orderBy('next_due_on')
            ->orderBy('title')
            ->get()
            ->map(fn (Chore $chore) => [
                'id'                   => $chore->id,
                'title'                => $chore->title,
                'description'          => $chore->description,
                'points'               => $chore->points,
                'schedule_description' => Schedule::of($chore)->description(),
                'assigned_users'       => $chore->users->map->only(['id', 'name']),
                'labels'               => $chore->labels->map->only(['id', 'name', 'color']),
                'due_on'               => $chore->next_due_on?->toDateString(),
                'group'                => match (true) {
                    $chore->next_due_on === null => 'anytime',
                    $chore->next_due_on->lt($today) => 'overdue',
                    $chore->next_due_on->eq($today) => 'today',
                    default => 'tomorrow',
                },
            ]);

        // Latest first, the same order Completion::undo() uses, so each chore's first entry is its latest completion.
        $completions = Completion::query()
            ->with(['chore', 'user'])
            ->whereHas('chore', fn ($query) => $query->withoutTrashed())
            ->whereBetween('completed_at', [$today, $today->endOfDay()])
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->get();
        $latestIds = $completions->unique('chore_id')->pluck('id');

        $done = $completions->map(fn (Completion $completion) => [
            'id'           => $completion->id,
            'chore_id'     => $completion->chore_id,
            'title'        => $completion->chore->title,
            'points'       => $completion->points,
            'status'       => $completion->status,
            'completed_by' => $completion->user?->only(['id', 'name']),
            'completed_at' => $completion->completed_at,
            'can_undo'     => $latestIds->contains($completion->id),
        ]);

        $labels = Label::all(['id', 'name', 'color']);

        return Inertia::render('Today/Index', [
            'chores' => $chores,
            'done'   => $done,
            'users'  => Scoreboard::forEveryone(),
            'labels' => $labels,
        ]);
    }
}
