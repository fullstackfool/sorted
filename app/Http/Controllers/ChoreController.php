<?php

namespace App\Http\Controllers;

use App\Models\Chore;
use App\Models\Completion;
use App\Models\User;
use App\Support\Schedule;
use Inertia\Inertia;

class ChoreController extends Controller
{
    public function index()
    {
        $chores = Chore::query()
            ->with(['users', 'labels'])
            ->orderBy('title')
            ->get()
            ->map(fn (Chore $chore) => [
                'id'                   => $chore->id,
                'title'                => $chore->title,
                'points'               => $chore->points,
                'schedule_description' => Schedule::of($chore)->description(),
                'next_due_on'          => $chore->next_due_on?->toDateString(),
                'finished'             => $chore->finished_at !== null,
                'assigned_users'       => $chore->users->map->only(['id', 'name']),
                'labels'               => $chore->labels->map->only(['id', 'name', 'color']),
            ]);

        return Inertia::render('Chores/Index', [
            'chores' => $chores,
        ]);
    }

    public function show(Chore $chore)
    {
        $chore->load(['users', 'labels']);

        $analytics = [
            'total_completions'   => $chore->completions()->where('status', Completion::DONE)->count(),
            'total_skips'         => $chore->completions()->where('status', Completion::SKIPPED)->count(),
            'total_points_earned' => (int) $chore->completions()->sum('points'),
        ];

        $completionHistory = $chore->completions()
            ->with('user:id,name')
            ->where('completed_at', '>=', today()->subDays(30))
            ->latest('completed_at')
            ->latest('id')
            ->get()
            ->map(fn (Completion $completion) => [
                ...$completion->only(['id', 'status', 'points', 'completed_at']),
                'due_on' => $completion->due_on?->toDateString(),
                'user'   => $completion->user?->only(['id', 'name']),
            ]);

        $done      = fn ($query) => $query->where('chore_id', $chore->id)->where('status', Completion::DONE);
        $userStats = User::query()
            ->whereHas('completions', $done)
            ->withCount(['completions' => $done])
            ->get()
            ->map(fn (User $user) => [
                'user'        => $user->only(['id', 'name']),
                'completions' => $user->completions_count,
            ]);

        return Inertia::render('Chores/Show', [
            'chore' => [
                'id'                   => $chore->id,
                'title'                => $chore->title,
                'description'          => $chore->description,
                'points'               => $chore->points,
                'schedule_description' => Schedule::of($chore)->description(),
                'next_due_on'          => $chore->next_due_on?->toDateString(),
                'finished_at'          => $chore->finished_at,
                'users'                => $chore->users->map->only(['id', 'name']),
                'labels'               => $chore->labels->map->only(['id', 'name', 'color']),
            ],
            'analytics'         => $analytics,
            'completionHistory' => $completionHistory,
            'userStats'         => $userStats,
        ]);
    }

    public function destroy(Chore $chore)
    {
        $chore->delete();

        return redirect()->route('chores.index')->with('success', 'Chore deleted.');
    }
}
