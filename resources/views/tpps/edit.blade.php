@extends('layouts.app')

@section('title', 'Ubah TPP ' . ($typeLabels[$selectedType] ?? strtoupper($selectedType)))
@section('page-title', 'Ubah Data TPP ' . ($typeLabels[$selectedType] ?? strtoupper($selectedType)))

@section('content')<div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <div class="btn-group" role="group" aria-label="Pilih jenis ASN">
        @foreach ($typeLabels as $typeKey => $label)
            <a href="{{ route('tpps.edit', array_filter([
                'tpp' => $tpp->getKey(),
                'type' => $typeKey,
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
    <form action="{{ route('tpps.update', [$tpp]) }}" method="POST">
        @method('PUT')
        <div class="card-body">
            @include('tpps._form')
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('tpps.index', ['type' => $selectedType, 'tahun' => $tpp->tahun, 'bulan' => $tpp->bulan]) }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection

