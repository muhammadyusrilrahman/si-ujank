@extends('layouts.app')

@section('title', 'Tambah Pegawai')
@section('page-title', 'Tambah Data Pegawai')

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Form Pegawai</h3>
    </div>
    <form action="{{ route('pegawais.store') }}" method="POST">
        <div class="card-body">
            @include('pegawais._form', ['pegawai' => null, 'skpds' => $skpds])
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('pegawais.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </form>
</div>
@endsection

