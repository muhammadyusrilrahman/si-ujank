@extends('layouts.app')

@section('title', 'Tambah TPP ' . ($typeLabels[$selectedType] ?? strtoupper($selectedType)))
@section('page-title', 'Tambah Data TPP ' . ($typeLabels[$selectedType] ?? strtoupper($selectedType)))

@section('content')<div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <div class="btn-group" role="group" aria-label="Pilih jenis ASN">
        @foreach ($typeLabels as $typeKey => $label)
            <a href="{{ route('tpps.create', array_filter([
                'type' => $typeKey,
                'tahun' => request('tahun'),
                'bulan' => request('bulan'),
            ])) }}"
               class="btn btn-sm {{ $typeKey === $selectedType ? 'btn-primary' : 'btn-outline-primary' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div><div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Form TPP {{ $typeLabels[$selectedType] ?? strtoupper($selectedType) }}</h3>
    </div>
    <form action="{{ route('tpps.store') }}" method="POST">
        <div class="card-body">
            @include('tpps._form')
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('tpps.index', array_filter(['type' => $selectedType, 'tahun' => request('tahun'), 'bulan' => request('bulan')])) }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </form>
</div>
@endsection

