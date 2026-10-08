<?php

namespace App\Http\Controllers;

use App\Models\Completion;
use App\Models\User;
use App\Support\Scoreboard;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->withCount(['chores', 'completions as done_count' => fn ($query) => $query->where('status', Completion::DONE)])
            ->withSum('completions', 'points')
            ->get()
            ->map(function ($user) {
                return [
                    'id'              => $user->id,
                    'name'            => $user->name,
                    'email'           => $user->email,
                    'avatar_url'      => $user->avatar_url,
                    'tasks_assigned'  => $user->chores_count,
                    'tasks_completed' => $user->done_count,
                    'total_points'    => (int) $user->completions_sum_points,
                ];
            });

        return Inertia::render('Users/Index', [
            'users'       => Scoreboard::forEveryone(),
            'userDetails' => $users,
        ]);
    }

    public function create()
    {
        return Inertia::render('Users/Create', [
            'users' => Scoreboard::forEveryone(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'avatar_style' => 'nullable|string|max:50',
            'avatar_seed'  => 'nullable|string|max:255',
        ]);

        User::create($validated);

        return redirect()->route('users.index')->with('success', 'User created!');
    }

    public function show(User $user)
    {
        $user->load(['chores.labels']);

        $stats = [
            'tasks_assigned'  => $user->chores->count(),
            'tasks_completed' => $user->completions()->where('status', Completion::DONE)->count(),
            'total_points'    => (int) $user->completions()->sum('points'),
        ];

        $recentCompletions = $user->completions()
            ->with('chore:id,title')
            ->latest('completed_at')
            ->latest('id')
            ->take(10)
            ->get()
            ->map(fn (Completion $completion) => [
                ...$completion->only(['id', 'status', 'points', 'completed_at']),
                'chore' => $completion->chore->only(['id', 'title']),
            ]);

        return Inertia::render('Users/Show', [
            'user'              => $user,
            'stats'             => $stats,
            'recentCompletions' => $recentCompletions,
            'users'             => Scoreboard::forEveryone(),
        ]);
    }

    public function edit(User $user)
    {
        return Inertia::render('Users/Edit', [
            'user'  => $user,
            'users' => Scoreboard::forEveryone(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'avatar_style' => 'nullable|string|max:50',
            'avatar_seed'  => 'nullable|string|max:255',
        ]);

        $user->update($validated);

        return redirect()->route('users.show', $user)->with('success', 'Profile updated!');
    }

    public function destroy(User $user)
    {
        $user->templates()->detach();
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted.');
    }
}
