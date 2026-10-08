<?php

namespace App\Http\Controllers;

use App\Models\Chore;
use App\Models\Completion;
use App\Models\User;
use App\Support\Schedule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    public function create()
    {
        return Inertia::render('Chores/Form');
    }

    public function store(Request $request)
    {
        $chore = $this->save($request, new Chore());

        return redirect()->route('chores.show', $chore)->with('success', 'Chore created.');
    }

    public function edit(Chore $chore)
    {
        return Inertia::render('Chores/Form', [
            'chore' => [
                'id'          => $chore->id,
                'title'       => $chore->title,
                'description' => $chore->description,
                'points'      => $chore->points,
                ...Schedule::toForm($chore),
            ],
        ]);
    }

    public function update(Request $request, Chore $chore)
    {
        $this->save($request, $chore);

        return redirect()->route('chores.show', $chore)->with('success', 'Chore saved.');
    }

    public function destroy(Chore $chore)
    {
        $chore->delete();

        return redirect()->route('chores.index')->with('success', 'Chore deleted.');
    }

    /**
     * Save the chore form, and when the schedule changed, set the chore's next due date to match it from today.
     *
     * Editing only the title, description or points leaves the due date alone, and a finished one-off stays finished.
     */
    private function save(Request $request, Chore $chore): Chore
    {
        $input = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'points'       => 'nullable|integer|min:0',
            'repeats'      => 'required|in:once,daily,weekly,monthly,after',
            'due_on'       => 'exclude_unless:repeats,once|nullable|date',
            'weekdays'     => 'exclude_unless:repeats,weekly|required|array',
            'weekdays.*'   => 'integer|between:0,6',
            'every_weeks'  => 'exclude_unless:repeats,weekly|nullable|integer|between:1,52',
            'month_days'   => 'exclude_unless:repeats,monthly|required|array',
            'month_days.*' => ['integer', Rule::in([...range(1, 31), -1])],
            'every'        => 'exclude_unless:repeats,after|required|integer|between:1,365',
            'unit'         => 'exclude_unless:repeats,after|required|in:day,week,month',
        ], [
            'weekdays.required'   => 'Choose at least one day.',
            'month_days.required' => 'Choose at least one date.',
        ]);
        $today = today()->toImmutable();

        $chore->fill([
            'title'       => $input['title'],
            'description' => $input['description'] ?? null,
            'points'      => $input['points'] ?? 10,
            ...Schedule::fromForm($input),
        ]);

        if ($chore->schedule === Chore::ONCE) {
            $chore->next_due_on = $input['due_on'] ?? null;
        }

        // Compare the stored columns rather than the request, so re-saving an unchanged schedule doesn't move the date.
        if ($chore->isDirty(['schedule', 'every', 'unit', 'rule', 'next_due_on'])) {
            $chore->starts_on   = $chore->schedule === Chore::ON ? $today : null;
            $chore->finished_at = $chore->schedule === Chore::ONCE ? $chore->finished_at : null;
            $lastDoneAt         = $chore->schedule === Chore::AFTER
                ? $chore->completions()->latest('completed_at')->latest('id')->first()?->completed_at
                : null;

            $chore->next_due_on = match ($chore->schedule) {
                Chore::ONCE => $chore->next_due_on,
                Chore::ON => Schedule::of($chore)->firstDueOn($today),
                Chore::AFTER => $lastDoneAt
                    ? Schedule::of($chore)->nextDueOn(null, $lastDoneAt->toImmutable())
                    : Schedule::of($chore)->firstDueOn($today),
            };
        }

        $chore->save();

        return $chore;
    }
}
