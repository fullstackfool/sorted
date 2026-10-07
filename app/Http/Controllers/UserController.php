<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Get users with their stats for the scoreboard
     */
    private function getUsersWithStats()
    {
        return User::all(['id', 'name', 'avatar_style', 'avatar_seed'])->map(function ($user) {
            // Get weekly points (current week starting from Sunday)
            $weekStart = now()->startOfWeek(0); // 0 = Sunday
            $weekEnd   = now()->endOfWeek(6);   // 6 = Saturday

            $weeklyPoints = Task::where('tasks.user_id', $user->id)
                ->where('tasks.status', 'done')
                ->whereBetween('tasks.completed_at', [$weekStart, $weekEnd])
                ->join('templates', 'tasks.template_id', '=', 'templates.id')
                ->sum('templates.points');

            // Get today's points
            $todayPoints = Task::where('tasks.user_id', $user->id)
                ->where('tasks.status', 'done')
                ->whereDate('tasks.completed_at', today())
                ->join('templates', 'tasks.template_id', '=', 'templates.id')
                ->sum('templates.points');

            // Calculate current streak
            $streak = $this->calculateStreak($user->id);

            return [
                'id'             => $user->id,
                'name'           => $user->name,
                'avatar_url'     => $user->avatar_url,
                'weekly_points'  => $weeklyPoints,
                'today_points'   => $todayPoints,
                'current_streak' => $streak,
            ];
        });
    }

    /**
     * Calculate the current streak for a user
     */
    private function calculateStreak($userId)
    {
        $streak      = 0;
        $currentDate = now()->startOfDay();

        // Go backwards from today to find consecutive days with completed tasks
        while (true) {
            $hasCompletedTask = Task::where('user_id', $userId)
                ->where('status', 'done')
                ->whereDate('completed_at', $currentDate)
                ->exists();

            if (!$hasCompletedTask) {
                // If today has no completed tasks yet and streak is 0, check yesterday
                if ($streak === 0 && $currentDate->isToday()) {
                    $currentDate->subDay();
                    continue;
                }
                break;
            }

            $streak++;
            $currentDate->subDay();
        }

        return $streak;
    }

    public function index()
    {
        $users = User::withCount('templates')
            ->get()
            ->map(function ($user) {
                // Calculate completions from tasks
                $completionsCount = Task::where('user_id', $user->id)
                    ->where('status', 'done')
                    ->count();

                // Calculate total points from tasks
                $totalPoints = Task::where('tasks.user_id', $user->id)
                    ->where('tasks.status', 'done')
                    ->join('templates', 'tasks.template_id', '=', 'templates.id')
                    ->sum('templates.points');

                return [
                    'id'              => $user->id,
                    'name'            => $user->name,
                    'email'           => $user->email,
                    'avatar_url'      => $user->avatar_url,
                    'tasks_assigned'  => $user->templates_count,
                    'tasks_completed' => $completionsCount,
                    'total_points'    => $totalPoints,
                ];
            });

        // Get users with stats for scoreboard
        $usersWithStats = $this->getUsersWithStats();

        return Inertia::render('Users/Index', [
            'users'       => $usersWithStats,
            'userDetails' => $users,
        ]);
    }

    public function create()
    {
        $users = $this->getUsersWithStats();

        return Inertia::render('Users/Create', [
            'users' => $users,
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
        $user->load(['templates.labels']);

        // Get task instances for this user
        $tasks = Task::where('user_id', $user->id)
            ->with('template')
            ->get();

        $completionsCount = $tasks->where('status', 'done')->count();

        $stats = [
            'tasks_assigned'  => $user->templates()->count(),
            'tasks_completed' => $completionsCount,
            'total_points'    => $tasks->where('status', 'done')->sum(function ($task) {
                return $task->template->points ?? 0;
            }),
        ];

        // Load recent completions
        $user->completions = $tasks->sortByDesc('completed_at')->take(10);

        // Get users with stats for scoreboard
        $users = $this->getUsersWithStats();

        return Inertia::render('Users/Show', [
            'user'  => $user,
            'stats' => $stats,
            'users' => $users,
        ]);
    }

    public function edit(User $user)
    {
        $users = $this->getUsersWithStats();

        return Inertia::render('Users/Edit', [
            'user'  => $user,
            'users' => $users,
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
