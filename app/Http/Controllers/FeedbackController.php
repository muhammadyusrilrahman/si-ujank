<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isAdminUnit()) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        Feedback::create([
            'user_id' => $user->id,
            'message' => $validated['message'],
        ]);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Terima kasih, feedback Anda berhasil dikirim.');
    }

    public function reply(Request $request, Feedback $feedback): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'reply' => ['required', 'string', 'max:4000'],
        ]);

        $feedback->update([
            'reply' => $validated['reply'],
            'replied_by' => $user->id,
            'replied_at' => now(),
        ]);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Balasan feedback berhasil disimpan.');
    }

    public function toggle(Request $request, Feedback $feedback): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin()) {
            abort(403);
        }

        $newStatus = ! $feedback->is_active;
        $feedback->update([
            'is_active' => $newStatus,
        ]);

        $message = $newStatus
            ? 'Feedback berhasil diaktifkan.'
            : 'Feedback berhasil dinonaktifkan.';

        return redirect()
            ->route('dashboard')
            ->with('status', $message);
    }
}
