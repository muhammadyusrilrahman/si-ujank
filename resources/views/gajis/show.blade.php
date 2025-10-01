@extends('gajis.layout')

@section('title', 'Detail Data Gaji')

@section('card-tools')
    <div class="btn-group">
        <a href="{{ route('gajis.edit', $gaji) }}" class="btn btn-warning btn-sm">
            <i class="fas fa-edit"></i> Edit
        </a>
        <form action="{{ route('gajis.destroy', $gaji) }}" method="POST" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                <i class="fas fa-trash"></i> Hapus
            </button>
        </form>
    </div>
@endsection

@section('card-body')
    <div class="row">
        <div class="col-md-6">
            <table class="table">
                <tr>
                    <th style="width: 200px">Nama Pegawai</th>
                    <td>{{ $gaji->pegawai->nama }}</td>
                </tr>
                <tr>
                    <th>NIP</th>
                    <td>{{ $gaji->pegawai->nip }}</td>
                </tr>
                <tr>
                    <th>SKPD</th>
                    <td>{{ $gaji->pegawai->skpd->nama }}</td>
                </tr>
                <tr>
                    <th>Periode</th>
                    <td>{{ date('F', mktime(0, 0, 0, $gaji->bulan, 1)) }} {{ $gaji->tahun }}</td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <table class="table">
                <tr>
                    <th style="width: 200px">Gaji Pokok</th>
                    <td>Rp {{ number_format($gaji->gaji_pokok, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <th>Tunjangan</th>
                    <td>Rp {{ number_format($gaji->tunjangan, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <th>Potongan</th>
                    <td>Rp {{ number_format($gaji->potongan, 0, ',', '.') }}</td>
                </tr>
                <tr class="table-primary">
                    <th>Total Gaji</th>
                    <td>Rp {{ number_format($gaji->gaji_pokok + $gaji->tunjangan - $gaji->potongan, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-end mt-3">
        <a href="{{ route('gajis.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
@endsection