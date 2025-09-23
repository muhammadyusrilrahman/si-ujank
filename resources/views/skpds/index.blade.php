@extends('layouts.app')

@section('title', 'Data SKPD')
@section('page-title', 'Daftar SKPD / Instansi')

@section('content')
<div class="row mb-3">
    <div class="col-md-8">
        <form method="GET" action="{{ route('skpds.index') }}" class="form-inline">
            <div class="input-group">
                <input type="text" name="q" class="form-control" placeholder="Cari nama atau alias" value="{{ $search }}">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i> Cari</button>
                </div>
            </div>
        </form>
    </div>
    <div class="col-md-4 text-md-right mt-3 mt-md-0">
        <a href="{{ route('skpds.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah SKPD</a>
    </div>
</div>

@if (session('status'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('status') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if ($errors->has('skpd'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ $errors->first('skpd') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 60px">#</th>
                        <th>Nama</th>
                        <th>Alias</th>
                        <th>Jumlah Pengguna</th>
                        <th style="width: 160px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($skpds as $index => $skpd)
                        <tr>
                            <td>{{ $skpds->firstItem() + $index }}</td>
                            <td>{{ $skpd->name }}</td>
                            <td>{{ $skpd->alias ?? '-' }}</td>
                            <td>{{ $skpd->users()->count() }}</td>
                            <td>
                                <a href="{{ route('skpds.edit', $skpd) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('skpds.destroy', $skpd) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Hapus SKPD ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Belum ada data SKPD.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($skpds->hasPages())
        <div class="card-footer">
            {{ $skpds->links() }}
        </div>
    @endif
</div>
@endsection

