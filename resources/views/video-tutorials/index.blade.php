@extends('layouts.app')

@section('title', 'Video Tutorial')
@section('page-title', 'Kelola Video Tutorial')

@section('content')
@php
    use Illuminate\Pagination\LengthAwarePaginator;

    $videosCollection = $videos ?? collect();
    $searchQuery = $search ?? '';
    $isPaginated = $videos instanceof LengthAwarePaginator;

    $items = $videosCollection->map(function ($video) {
        return [
            'id' => $video->id,
            'title' => $video->title,
            'description' => $video->description,
            'link_url' => $video->video_url,
            'is_active' => (bool) $video->is_active,
            'edit_url' => route('video-tutorials.edit', $video),
            'delete_url' => route('video-tutorials.destroy', $video),
        ];
    })->values()->all();

    $pagination = null;

    if ($isPaginated) {
        $paginatorArray = $videos->toArray();
        $pagination = [
            'from' => $videos->firstItem(),
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
            'index' => route('video-tutorials.index'),
            'create' => route('video-tutorials.create'),
        ],
        'statusMessage' => session('status'),
        'csrfToken' => csrf_token(),
        'items' => $items,
        'pagination' => $pagination,
        'texts' => [
            'searchPlaceholder' => 'Cari judul atau deskripsi',
            'createButton' => 'Tambah Video',
            'linkColumn' => 'Tautan Video',
            'linkText' => 'Tonton video',
            'emptyMessage' => 'Belum ada data video tutorial.',
            'deleteConfirm' => 'Hapus video tutorial ini?',
            'statusActive' => 'Aktif',
            'statusInactive' => 'Nonaktif',
        ],
    ];
@endphp

<div
    id="video-tutorials-index-root"
    data-props='@json($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'
></div>

<noscript>
    <div class="alert alert-warning mt-3">
        Halaman ini memerlukan JavaScript agar dapat digunakan sepenuhnya. Silakan aktifkan JavaScript pada peramban Anda.
    </div>
</noscript>
@endsection

