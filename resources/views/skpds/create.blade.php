@extends('layouts.app')

@section('title', 'Tambah SKPD')
@section('page-title', 'Tambah SKPD / Instansi')

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Form SKPD</h3>
    </div>
    <form action="{{ route('skpds.store') }}" method="POST">
        <div class="card-body">
            @include('skpds._form', ['skpd' => null])
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('skpds.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </form>
</div>
@endsection

