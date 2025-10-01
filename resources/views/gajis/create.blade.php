@extends('layouts.app')

@section('title', 'Tambah Gaji ' . ($typeLabels[$selectedType] ?? strtoupper($selectedType)))
@section('page-title', 'Tambah Data Gaji ' . ($typeLabels[$selectedType] ?? strtoupper($selectedType)))

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Form Gaji {{ $typeLabels[$selectedType] ?? strtoupper($selectedType) }}</h3>
    </div>
    <form action="{{ route('gajis.store') }}" method="POST">
        <div class="card-body">
            @include('gajis._form')
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('gajis.index', array_filter(['type' => $selectedType, 'tahun' => request('tahun'), 'bulan' => request('bulan')])) }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </form>
</div>
@endsection
