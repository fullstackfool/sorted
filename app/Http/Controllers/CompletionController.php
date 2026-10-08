<?php

namespace App\Http\Controllers;

use App\Models\Chore;
use App\Models\Completion;
use App\Models\User;
use Illuminate\Http\Request;

class CompletionController extends Controller
{
    public function complete(Request $request, Chore $chore)
    {
        return $this->record($request, $chore, Completion::DONE);
    }

    public function skip(Request $request, Chore $chore)
    {
        return $this->record($request, $chore, Completion::SKIPPED);
    }

    public function undo(Completion $completion)
    {
        $completion->undo();

        return back();
    }

    private function record(Request $request, Chore $chore, string $status)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'due_on'  => 'nullable|date_format:Y-m-d',
        ]);

        $chore->record(User::findOrFail($validated['user_id']), $status, $validated['due_on'] ?? null);

        return back();
    }
}
