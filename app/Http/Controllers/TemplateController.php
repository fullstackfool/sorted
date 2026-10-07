<?php

namespace App\Http\Controllers;

use App\Models\Label;
use App\Models\Task;
use App\Models\Template;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    /**
     * Display a listing of templates.
     */
    public function index(): Response
    {
        $templates = Template::query()
            ->primary()
            ->with([
                'users',
                'labels',
                'subtemplates',
            ])
            ->orderBy('title')
            ->get()
            ->map(function (Template $template) {
                return [
                    'id'                     => $template->id,
                    'title'                  => $template->title,
                    'description'            => $template->description,
                    'points'                 => $template->points,
                    'recurrence_type'        => $template->recurrence_type,
                    'recurrence_pattern'     => $template->recurrence_pattern,
                    'recurrence_description' => $template->recurrence_description,
                    'deadline'               => $template->deadline,
                    'next_due_date'          => $template->next_due_date,
                    'assigned_users'         => $template->users,
                    'labels'                 => $template->labels,
                    'subtemplates'           => $template->subtemplates,
                ];
            });

        $users = User::all(['id', 'name']);
        $labels = Label::all(['id', 'name', 'color']);

        return Inertia::render('Templates/Index', [
            'templates' => $templates,
            'users'     => $users,
            'labels'    => $labels,
        ]);
    }

    /**
     * Show the form for creating a new template.
     */
    public function create(): Response
    {
        $users = User::all(['id', 'name']);
        $labels = Label::all(['id', 'name', 'color']);

        return Inertia::render('Templates/Create', [
            'users'  => $users,
            'labels' => $labels,
        ]);
    }

    /**
     * Store a newly created template.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string',
            'points'             => 'nullable|integer|min:0',
            'recurrence_type'    => 'required|string|in:none,daily,weekly,biweekly,monthly',
            'recurrence_pattern' => 'nullable|array',
            'deadline'           => 'nullable|date',
        ]);

        $template = Template::create([
            'title'              => $validated['title'],
            'description'        => $validated['description'] ?? null,
            'points'             => $validated['points'] ?? 10,
            'recurrence_type'    => $validated['recurrence_type'],
            'recurrence_pattern' => $validated['recurrence_pattern'],
            'deadline'           => $validated['deadline'],
        ]);

        // Calculate next_due_date if applicable
        $template->next_due_date = $template->calculateNextDueDate();
        $template->save();

        // Note: The boot() method on Template will automatically create the first task

        return redirect()->route('templates.index')->with('success', 'Template created successfully!');
    }

    /**
     * Display the specified template.
     */
    public function show(Template $template): Response
    {
        $template->load(['users', 'labels', 'subtemplates', 'tasks.user']);

        // Get users for display
        $users = User::all(['id', 'name']);

        // Calculate analytics
        $totalCompletions = $template->tasks()->where('status', 'done')->count();
        $totalSkips = $template->tasks()->where('status', 'skipped')->count();
        $totalTasks = $template->tasks()->count();

        // Get completion history (last 30 days)
        $completionHistory = $template->tasks()
            ->with('user')
            ->where('date', '>=', now()->subDays(30))
            ->orderBy('completed_at', 'desc')
            ->get();

        // Calculate points earned
        $totalPointsEarned = $template->tasks()->where('status', 'done')->count() * $template->points;

        // Get user stats
        $userStats = $template->tasks()
            ->where('status', 'done')
            ->whereNotNull('user_id')
            ->with('user')
            ->get()
            ->groupBy('user_id')
            ->map(function ($tasks) {
                return [
                    'user'        => $tasks->first()->user,
                    'completions' => $tasks->count(),
                ];
            })
            ->values();

        // Prepare template data with recurrence description
        $templateData = $template->toArray();
        $templateData['recurrence_description'] = $template->recurrence_description;

        return Inertia::render('Templates/Show', [
            'template'          => $templateData,
            'users'             => $users,
            'analytics'         => [
                'total_completions'   => $totalCompletions,
                'total_skips'         => $totalSkips,
                'total_tasks'         => $totalTasks,
                'total_points_earned' => $totalPointsEarned,
                'completion_rate'     => $totalTasks > 0 ? round(($totalCompletions / $totalTasks) * 100) : 0,
            ],
            'completionHistory' => $completionHistory,
            'userStats'         => $userStats,
        ]);
    }

    /**
     * Update the specified template.
     */
    public function update(Request $request, Template $template)
    {
        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string',
            'points'             => 'required|integer|min:0',
            'recurrence_type'    => 'required|in:none,daily,weekly,biweekly,monthly,custom',
            'recurrence_pattern' => 'nullable|array',
            'deadline'           => 'nullable|date',
        ]);

        $template->update($validated);

        // Automatically calculate and set next_due_date based on recurrence pattern
        $nextDueDate = $template->calculateNextDueDate();
        $template->update(['next_due_date' => $nextDueDate]);

        return back();
    }

    /**
     * Remove the specified template.
     */
    public function destroy(Template $template)
    {
        // Delete all tasks for this template (cascade should handle this, but being explicit)
        Task::where('template_id', $template->id)->delete();

        // Delete the template itself
        $template->delete();

        return redirect()->route('templates.index')->with('success', 'Template deleted successfully');
    }
}

