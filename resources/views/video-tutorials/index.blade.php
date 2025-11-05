@extends('layouts.app')

@section('title', 'Video Tutorial')
@section('page-title', 'Kelola Video Tutorial')

@section('content')
@php
    $videos = $videos ?? collect();
    $search = $search ?? '';
@endphp
<div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <form action="{{ route('video-tutorials.index') }}" method="GET" class="form-inline">
            <div class="input-group">
                <input type="text" name="q" class="form-control" placeholder="Cari judul atau deskripsi" value="{{ $search }}">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i> Cari</button>
                </div>
            </div>
        </form>
        <a href="{{ route('video-tutorials.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Video
        </a>
    </div>
    <div class="card-body p-0">
        @if (session('status'))
            <div class="alert alert-success m-3">
                {{ session('status') }}
            </div>
        @endif
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 60px">#</th>
                        <th>Judul</th>
                        <th>Deskripsi</th>
                        <th>Tautan Video</th>
                        <th>Status</th>
                        <th style="width: 160px" class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($videos as $index => $video)
                        <tr>
                            <td>{{ $videos->firstItem() + $index }}</td>
                            <td>{{ $video->title }}</td>
                            <td class="text-muted small">{{ \Illuminate\Support\Str::limit($video->description, 120) }}</td>
                            <td>
                                <a href="{{ $video->video_url }}" target="_blank" rel="noopener" class="text-primary">
                                    Tonton video <i class="fas fa-external-link-alt ml-1"></i>
                                </a>
                            </td>
                            <td>
                                @if ($video->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('video-tutorials.edit', $video) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('video-tutorials.destroy', $video) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus video tutorial ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada data video tutorial.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($videos instanceof \Illuminate\Pagination\AbstractPaginator && $videos->hasPages())
        <div class="card-footer">
            {{ $videos->onEachSide(1)->links('pagination::bootstrap-4') }}
        </div>
    @endif
</div>
@endsection
