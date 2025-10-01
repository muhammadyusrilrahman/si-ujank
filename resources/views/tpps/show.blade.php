@extends('tpps.layout')

@section('title', 'Detail Data TPP')

@section('card-tools')
    <div class="btn-group">
        <a href="{{ route('tpps.edit', $tpp) }}" class="btn btn-warning btn-sm">
            <i class="fas fa-edit"></i> Edit
        </a>
        <form action="{{ route('tpps.destroy', $tpp) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data TPP ini?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">
                <i class="fas fa-trash"></i> Hapus
            </button>
        </form>
    </div>
@endsection

@section('card-body')
    @php\n        \ = \ ?? [];\n        \ = \ ?? [];\n        \ = \ ?? [];\n        \ = \->pegawai;
        $allowanceTotal = 0;
        $deductionTotal = 0;
        foreach ($allowanceFields as $field => $label) {
            $allowanceTotal += (float) $tpp->{$field};
        }
        foreach ($deductionFields as $field => $label) {
            $deductionTotal += (float) $tpp->{$field};
        }
        $transfer = $allowanceTotal - $deductionTotal;
    @endphp
    <div class="row">
        <div class="col-md-6">
            <table class="table">
                <tr>
                    <th style="width: 200px">Nama Pegawai</th>
                    <td>{{ optional($pegawai)->nama_lengkap }}</td>
                </tr>
                <tr>
                    <th>NIP</th>
                    <td>{{ optional($pegawai)->nip ?: '-' }}</td>
                </tr>
                <tr>
                    <th>SKPD</th>
                    <td>{{ optional(optional($pegawai)->skpd)->name ?: '-' }}</td>
                </tr>
                <tr>
                    <th>Periode</th>
                    <td>{{ $monthOptions[$tpp->bulan] ?? $tpp->bulan }} {{ $tpp->tahun }}</td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <table class="table">
                <tr>
                    <th style="width: 200px">Total Komponen TPP</th>
                    <td>Rp {{ number_format($allowanceTotal, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <th>Total Potongan</th>
                    <td>Rp {{ number_format($deductionTotal, 2, ',', '.') }}</td>
                </tr>
                <tr class="table-primary">
                    <th>Jumlah Ditransfer</th>
                    <td>Rp {{ number_format($transfer, 2, ',', '.') }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-end mt-3">
        <a href="{{ route('tpps.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
@endsection

