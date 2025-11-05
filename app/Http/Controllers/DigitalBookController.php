<?php

namespace App\Http\Controllers;

use App\Models\DigitalBook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DigitalBookController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeSuperAdmin($request);

        $search = trim((string) $request->query('q', ''));

        $books = DigitalBook::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('digital-books.index', [
            'books' => $books,
            'search' => $search,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeSuperAdmin($request);

        return view('digital-books.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $validated = $this->validatePayload($request);

        DigitalBook::create($validated);

        return redirect()
            ->route('digital-books.index')
            ->with('status', 'Buku digital berhasil ditambahkan.');
    }

    public function edit(Request $request, DigitalBook $digitalBook): View
    {
        $this->authorizeSuperAdmin($request);

        return view('digital-books.edit', [
            'digitalBook' => $digitalBook,
        ]);
    }

    public function update(Request $request, DigitalBook $digitalBook): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $validated = $this->validatePayload($request);

        $digitalBook->update($validated);

        return redirect()
            ->route('digital-books.index')
            ->with('status', 'Buku digital berhasil diperbarui.');
    }

    public function destroy(Request $request, DigitalBook $digitalBook): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $digitalBook->delete();

        return redirect()
            ->route('digital-books.index')
            ->with('status', 'Buku digital berhasil dihapus.');
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'file_url' => ['required', 'url', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ], [], [
            'title' => 'Judul',
            'description' => 'Deskripsi',
            'file_url' => 'Tautan file',
        ]) + [
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
    }
}
