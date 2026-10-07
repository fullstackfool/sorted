<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Template;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Complete a task.
     */
    public function complete(Request $request, Task $task)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $now = now();

        // Complete the task
        $task->update([
            'status'       => 'done',
            'user_id'      => $request->user_id,
            'completed_at' => $now,
        ]);

        // If this is a parent task, complete all subtasks too
        foreach ($task->subtasks as $subtask) {
            $subtask->update([
                'status'       => 'done',
                'user_id'      => $request->user_id,
                'completed_at' => $now,
            ]);
        }

        // Create the next occurrence (only for parent tasks)
        if ($task->parent_id === null) {
            $task->createNextOccurrence();
        }

        return back();
    }

    /**
     * Skip a task.
     */
    public function skip(Request $request, Task $task)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $now = now();

        // Skip the task
        $task->update([
            'status'       => 'skipped',
            'user_id'      => $request->user_id,
            'completed_at' => $now,
            'notes'        => 'Skipped',
        ]);

        // If this is a parent task, skip all subtasks too
        foreach ($task->subtasks as $subtask) {
            $subtask->update([
                'status'       => 'skipped',
                'user_id'      => $request->user_id,
                'completed_at' => $now,
                'notes'        => 'Skipped with parent',
            ]);
        }

        // Create the next occurrence (only for parent tasks)
        if ($task->parent_id === null) {
            $task->createNextOccurrence();
        }

        return back();
    }

    /**
     * Reset a task back to todo status.
     */
    public function reset(Task $task)
    {
        // If this is a parent task, delete the next occurrence that was created on completion
        if ($task->parent_id === null) {
            Task::where('template_id', $task->template_id)
                ->where('status', 'todo')
                ->where('id', '!=', $task->id)
                ->whereNull('parent_id')
                ->delete();
        }

        // Reset the task
        $task->update([
            'status'       => 'todo',
            'user_id'      => null,
            'completed_at' => null,
            'notes'        => null,
        ]);

        // If this is a parent task, reset all subtasks too
        foreach ($task->subtasks as $subtask) {
            $subtask->update([
                'status'       => 'todo',
                'user_id'      => null,
                'completed_at' => null,
                'notes'        => null,
            ]);
        }

        return back();
    }
}
