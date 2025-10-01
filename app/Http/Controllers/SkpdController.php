<?php

namespace App\Http\Controllers;

use App\Models\Skpd;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SkpdController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('q');

        $skpds = Skpd::query()->withCount('users')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('alias', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('skpds.index', [
            'skpds' => $skpds,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('skpds.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:skpds,name'],
            'alias' => ['nullable', 'string', 'max:255'],
        ]);

        Skpd::create($validated);

        return redirect()->route('skpds.index')->with('status', 'SKPD berhasil ditambahkan.');
    }

    public function edit(Skpd $skpd): View
    {
        return view('skpds.edit', compact('skpd'));
    }

    public function update(Request $request, Skpd $skpd): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', "unique:skpds,name,{$skpd->id}"],
            'alias' => ['nullable', 'string', 'max:255'],
        ]);

        $skpd->update($validated);

        return redirect()->route('skpds.index')->with('status', 'SKPD berhasil diperbarui.');
    }

    public function destroy(Skpd $skpd): RedirectResponse
    {
        if ($skpd->users()->exists()) {
            return redirect()->route('skpds.index')->withErrors([
                'skpd' => 'SKPD tidak dapat dihapus karena masih memiliki pengguna terkait.',
            ]);
        }

        $skpd->delete();

        return redirect()->route('skpds.index')->with('status', 'SKPD berhasil dihapus.');
    }
}


