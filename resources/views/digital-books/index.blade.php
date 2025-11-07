@extends('layouts.app')

@section('title', 'Buku Digital')
@section('page-title', 'Kelola Buku Digital')

@section('content')
@php
    use Illuminate\Pagination\LengthAwarePaginator;

    $booksCollection = $books ?? collect();
    $searchQuery = $search ?? '';
    $isPaginated = $books instanceof LengthAwarePaginator;

    $items = $booksCollection->map(function ($book) {
        return [
            'id' => $book->id,
            'title' => $book->title,
            'description' => $book->description,
            'link_url' => $book->file_url,
            'is_active' => (bool) $book->is_active,
            'edit_url' => route('digital-books.edit', $book),
            'delete_url' => route('digital-books.destroy', $book),
        ];
    })->values()->all();

    $pagination = null;

    if ($isPaginated) {
        $paginatorArray = $books->toArray();
        $pagination = [
            'from' => $books->firstItem(),
            'links' => collect($paginatorArray['links'] ?? [])->map(function ($link) {
                return [
                    'url' => $link['url'],
                    'label' => $link['label'],
                    'active' => $link['active'],
                ];
            })->all(),
        ];
    }

    $props = [
        'searchQuery' => $searchQuery,
        'routes' => [
            'index' => route('digital-books.index'),
            'create' => route('digital-books.create'),
        ],
        'statusMessage' => session('status'),
        'csrfToken' => csrf_token(),
        'items' => $items,
        'pagination' => $pagination,
        'texts' => [
            'searchPlaceholder' => 'Cari judul atau deskripsi',
            'createButton' => 'Tambah Buku',
            'linkColumn' => 'Tautan',
            'linkText' => 'Lihat tautan',
            'emptyMessage' => 'Belum ada data buku digital.',
            'deleteConfirm' => 'Hapus buku digital ini?',
            'statusActive' => 'Aktif',
            'statusInactive' => 'Nonaktif',
        ],
    ];
@endphp

<div
    id="digital-books-index-root"
    data-props='@json($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'
></div>

<noscript>
    <div class="alert alert-warning mt-3">
        Halaman ini memerlukan JavaScript agar dapat digunakan sepenuhnya. Silakan aktifkan JavaScript pada peramban Anda.
    </div>
</noscript>
@endsection

