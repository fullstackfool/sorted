<?php

namespace App\Http\Controllers;

use App\Models\Label;
use App\Models\Task;
use App\Models\User;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        // Only show tasks for today and tomorrow (todo), plus recently completed/skipped from today
        $tasks = Task::query()
            ->primary()
            ->with([
                'template.users',
                'template.labels',
                'subtasks.template',
                'subtasks.user',
                'user',
            ])
            ->where(function ($query) {
                $query->where('status', 'todo')
                    ->where(function ($q) {
                        $q->whereDate('date', '<=', now()->addDay()->endOfDay())
                            ->orWhereNull('date');
                    });
            })
            ->orWhere(function ($query) {
                $query->whereNull('parent_id')
                    ->whereIn('status', ['done', 'skipped'])
                    ->whereDate('date', today());
            })
            ->orderBy('date')
            ->get()
            ->map(function (Task $task) {
                return [
                    'id'                     => $task->id,
                    'template_id'            => $task->template->id,
                    'title'                  => $task->template->title,
                    'description'            => $task->template->description,
                    'points'                 => $task->template->points,
                    'recurrence_type'        => $task->template->recurrence_type,
                    'recurrence_pattern'     => $task->template->recurrence_pattern,
                    'recurrence_description' => $task->template->recurrence_description,
                    'assigned_users'         => $task->template->users,
                    'labels'                 => $task->template->labels,
                    'status'                 => $task->status,
                    'completed_by'           => $task->user,
                    'completed_at'           => $task->completed_at,
                    'date'                   => $task->date,
                    'subtasks'               => $task->subtasks->map(function (Task $subtask) {
                        return [
                            'id'             => $subtask->id,
                            'template_id'    => $subtask->template->id,
                            'title'          => $subtask->template->title,
                            'description'    => $subtask->template->description,
                            'points'         => $subtask->template->points,
                            'assigned_users' => $subtask->template->users,
                            'labels'         => $subtask->template->labels,
                            'status'         => $subtask->status,
                            'completed_by'   => $subtask->user,
                            'completed_at'   => $subtask->completed_at,
                        ];
                    }),
                ];
            })
            ->sort(function ($a, $b) {
                // First sort by status (to-do first, then done/skipped)
                $aIsTodo = $a['status'] === 'todo';
                $bIsTodo = $b['status'] === 'todo';

                if ($aIsTodo !== $bIsTodo) {
                    return $aIsTodo ? -1 : 1;
                }

                // If both are completed, sort by completion date (most recent first)
                if (!$aIsTodo && !$bIsTodo) {
                    $aCompletedAt = $a['completed_at'] ? strtotime($a['completed_at']) : 0;
                    $bCompletedAt = $b['completed_at'] ? strtotime($b['completed_at']) : 0;
                    return $bCompletedAt - $aCompletedAt;
                }

                // For to-do tasks, sort by recurrence type
                $recurrencePriority = [
                    'daily'    => 0,
                    'weekly'   => 1,
                    'biweekly' => 2,
                    'monthly'  => 3,
                    'none'     => 4,
                    'custom'   => 5,
                ];

                $aPriority = $recurrencePriority[$a['recurrence_type']] ?? 6;
                $bPriority = $recurrencePriority[$b['recurrence_type']] ?? 6;

                return $aPriority - $bPriority;
            })
            ->values();

        // Get users with their stats for the scoreboard
        $users = User::all(['id', 'name', 'avatar_style', 'avatar_seed'])->map(function ($user) {
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

        $labels = Label::all(['id', 'name', 'color']);

        return Inertia::render('Today/Index', [
            'tasks'  => $tasks,
            'users'  => $users,
            'labels' => $labels,
        ]);
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
}
