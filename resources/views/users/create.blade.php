@extends('layouts.app')

@section('title', 'Tambah Pengguna')
@section('page-title', 'Tambah Pengguna')

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Form Pengguna</h3>
    </div>
    <form action="{{ route('users.store') }}" method="POST">
        <div class="card-body">
            @include('users._form', ['user' => null, 'roleOptions' => $roleOptions])
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('users.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </form>
</div>
@endsection
