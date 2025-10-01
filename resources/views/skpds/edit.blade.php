@extends('layouts.app')

@section('title', 'Edit SKPD')
@section('page-title', 'Edit SKPD / Instansi')

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Form SKPD</h3>
    </div>
    <form action="{{ route('skpds.update', $skpd) }}" method="POST">
        @method('PUT')
        <div class="card-body">
            @include('skpds._form')
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('skpds.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Perbarui</button>
        </div>
    </form>
</div>
@endsection
