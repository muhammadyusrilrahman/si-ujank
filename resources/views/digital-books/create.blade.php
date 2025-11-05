@extends('layouts.app')

@section('title', 'Tambah Buku Digital')
@section('page-title', 'Tambah Buku Digital')

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Form Buku Digital</h3>
    </div>
    <form action="{{ route('digital-books.store') }}" method="POST">
        <div class="card-body">
            @include('digital-books._form')
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('digital-books.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan
            </button>
        </div>
    </form>
</div>
@endsection
