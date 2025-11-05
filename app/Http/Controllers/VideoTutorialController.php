<?php

namespace App\Http\Controllers;

use App\Models\VideoTutorial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VideoTutorialController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeSuperAdmin($request);

        $search = trim((string) $request->query('q', ''));

        $videos = VideoTutorial::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('video-tutorials.index', [
            'videos' => $videos,
            'search' => $search,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeSuperAdmin($request);

        return view('video-tutorials.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $validated = $this->validatePayload($request);

        VideoTutorial::create($validated);

        return redirect()
            ->route('video-tutorials.index')
            ->with('status', 'Video tutorial berhasil ditambahkan.');
    }

    public function edit(Request $request, VideoTutorial $videoTutorial): View
    {
        $this->authorizeSuperAdmin($request);

        return view('video-tutorials.edit', [
            'videoTutorial' => $videoTutorial,
        ]);
    }

    public function update(Request $request, VideoTutorial $videoTutorial): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $validated = $this->validatePayload($request);

        $videoTutorial->update($validated);

        return redirect()
            ->route('video-tutorials.index')
            ->with('status', 'Video tutorial berhasil diperbarui.');
    }

    public function destroy(Request $request, VideoTutorial $videoTutorial): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $videoTutorial->delete();

        return redirect()
            ->route('video-tutorials.index')
            ->with('status', 'Video tutorial berhasil dihapus.');
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'video_url' => ['required', 'url', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ], [], [
            'title' => 'Judul',
            'description' => 'Deskripsi',
            'video_url' => 'Tautan video',
        ]) + [
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
    }
}
