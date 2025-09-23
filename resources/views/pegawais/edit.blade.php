@extends('layouts.app')

@section('title', 'Edit Pegawai')
@section('page-title', 'Edit Data Pegawai')

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Form Pegawai</h3>
    </div>
    <form action="{{ route('pegawais.update', $pegawai) }}" method="POST">
        @method('PUT')
        <div class="card-body">
            @include('pegawais._form', ['pegawai' => $pegawai, 'skpds' => $skpds])
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('pegawais.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Perbarui</button>
        </div>
    </form>
</div>
@endsection

