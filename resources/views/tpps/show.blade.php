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
    @php
        $totalTppFields = [
            'tpp_beban_kerja',
            'tpp_tempat_bertugas',
            'tpp_kondisi_kerja',
            'tpp_kelangkaan_profesi',
            'tpp_prestasi_kerja',
            'tunjangan_pph',
            'iuran_jaminan_kesehatan',
            'iuran_jaminan_kecelakaan_kerja',
            'iuran_jaminan_kematian',
            'iuran_simpanan_tapera',
            'iuran_pensiun',
            'tunjangan_jaminan_hari_tua',
        ];

        $totalPotonganFields = [
            'iuran_jaminan_kesehatan',
            'iuran_jaminan_kecelakaan_kerja',
            'iuran_jaminan_kematian',
            'iuran_simpanan_tapera',
            'iuran_pensiun',
            'tunjangan_jaminan_hari_tua',
            'potongan_iwp',
            'potongan_pph_21',
            'zakat',
            'bulog',
        ];

        $pegawai = $tpp->pegawai;
        $totalTppAmount = 0.0;
        foreach ($totalTppFields as $field) {
            $totalTppAmount += (float) ($tpp->{$field} ?? 0.0);
        }

        $totalPotonganAmount = 0.0;
        foreach ($totalPotonganFields as $field) {
            $totalPotonganAmount += (float) ($tpp->{$field} ?? 0.0);
        }

        $transfer = $totalTppAmount - $totalPotonganAmount;
        $monthOptions = $monthOptions ?? config('tpp.months', config('gaji.months', []));
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
                    <td>{{ \App\Support\MoneyFormatter::rupiah($totalTppAmount) }}</td>
                </tr>
                <tr>
                    <th>Total Potongan</th>
                    <td>{{ \App\Support\MoneyFormatter::rupiah($totalPotonganAmount) }}</td>
                </tr>
                <tr class="table-primary">
                    <th>Jumlah Ditransfer</th>
                    <td>{{ \App\Support\MoneyFormatter::rupiah($transfer) }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-end mt-3">
        <a href="{{ route('tpps.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
@endsection

