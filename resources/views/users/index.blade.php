@extends('layouts.app')

@section('title', 'Data Pengguna')
@section('page-title', 'Daftar Pengguna')

@section('content')
@php
    $currentUser = auth()->user();
@endphp
<div class="row mb-3">
    <div class="col-md-8">
        <form method="GET" action="{{ route('users.index') }}" class="form-inline">
            <div class="input-group">
                <input type="text" name="q" class="form-control" placeholder="Cari nama, username, atau email" value="{{ $search }}">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i> Cari</button>
                </div>
            </div>
        </form>
    </div>
    <div class="col-md-4 text-md-right mt-3 mt-md-0">
        @if ($currentUser->isSuperAdmin() || $currentUser->isAdminUnit())
            <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Pengguna</a>
        @endif
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

@if ($errors->has('user'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ $errors->first('user') }}
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
                        <th>Username</th>
                        <th>Email</th>
                        <th>SKPD</th>
                        <th>Peran</th>
                        <th style="width: 170px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $index => $user)
                        @php
                            $canManage = $currentUser->isSuperAdmin() || ($currentUser->isAdminUnit() && $user->skpd_id === $currentUser->skpd_id);
                            $canEdit = $canManage;
                            $canDelete = $canManage && ! $currentUser->is($user);
                        @endphp
                        <tr>
                            <td>{{ $users->firstItem() + $index }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->username }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->skpd->name ?? '-' }}</td>
                            <td>
                                @if ($user->role === 'super_admin')
                                    <span class="badge badge-danger">Super Admin</span>
                                @elseif ($user->role === 'admin_unit')
                                    <span class="badge badge-primary">Admin Unit</span>
                                @else
                                    <span class="badge badge-secondary">User Reguler</span>
                                @endif
                            </td>
                            <td>
                                @if ($canEdit)
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                @endif
                                @if ($canDelete)
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Hapus pengguna ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Belum ada data pengguna.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($users->hasPages())
        <div class="card-footer">
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection






