@extends('layouts.app')

@section('title', 'Buku Digital')
@section('page-title', 'Kelola Buku Digital')

@section('content')
@php
    $books = $books ?? collect();
    $search = $search ?? '';
@endphp
<div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <form action="{{ route('digital-books.index') }}" method="GET" class="form-inline">
            <div class="input-group">
                <input type="text" name="q" class="form-control" placeholder="Cari judul atau deskripsi" value="{{ $search }}">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i> Cari</button>
                </div>
            </div>
        </form>
        <a href="{{ route('digital-books.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Buku
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
                        <th>Tautan</th>
                        <th>Status</th>
                        <th style="width: 160px" class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($books as $index => $book)
                        <tr>
                            <td>{{ $books->firstItem() + $index }}</td>
                            <td>{{ $book->title }}</td>
                            <td class="text-muted small">{{ \Illuminate\Support\Str::limit($book->description, 120) }}</td>
                            <td>
                                <a href="{{ $book->file_url }}" target="_blank" rel="noopener" class="text-primary">
                                    Lihat tautan <i class="fas fa-external-link-alt ml-1"></i>
                                </a>
                            </td>
                            <td>
                                @if ($book->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('digital-books.edit', $book) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('digital-books.destroy', $book) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus buku digital ini?');">
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
                            <td colspan="6" class="text-center text-muted py-4">Belum ada data buku digital.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($books instanceof \Illuminate\Pagination\AbstractPaginator && $books->hasPages())
        <div class="card-footer">
            {{ $books->onEachSide(1)->links('pagination::bootstrap-4') }}
        </div>
    @endif
</div>
@endsection
