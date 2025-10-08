@extends('gajis.layout')

@section('title', 'Buat E-Bupot')

@section('card-tools')
    <a href="{{ route('gajis.index', array_filter(['type' => $selectedType ?? request('type'), 'tahun' => $selectedYear ?? request('tahun'), 'bulan' => $selectedMonth ?? request('bulan')])) }}" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
@endsection

@section('card-body')
    @php
        $entries = collect($prefilledEntries ?? []);
    @endphp

    @if ($entries->isEmpty())
        <div class="alert alert-warning">
            Data gaji untuk periode yang dipilih belum tersedia. Silakan kembali dan pastikan filter tahun dan bulan berisi data.
        </div>
    @endif

    <form action="{{ route('gajis.ebupot.store') }}" method="POST" class="mt-3">
        @csrf
        <input type="hidden" name="default_npwp_pemotong" value="{{ $defaultNpwpPemotong }}">
        <input type="hidden" name="default_id_tku" value="{{ $defaultIdTku }}">
        <input type="hidden" name="default_kode_objek" value="{{ $defaultKodeObjek }}">
        <input type="hidden" name="type" value="{{ $selectedType ?? request('type') }}">
        <input type="hidden" name="tahun" value="{{ $selectedYear ?? request('tahun') }}">
        <input type="hidden" name="bulan" value="{{ $selectedMonth ?? request('bulan') }}">

        @if (($selectedYear ?? false) && ($selectedMonth ?? false))
            <div class="mb-3">
                <span class="badge bg-primary">
                    Periode: {{ $monthOptions[$selectedMonth] ?? ($selectedMonth ?? '-') }} {{ $selectedYear ?? '' }}
                </span>
            </div>
        @endif

        @if ($entries->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle text-nowrap">
                    <thead class="table-primary">
                        <tr>
                            <th>NPWP Pemotong</th>
                            <th>Masa Pajak</th>
                            <th>Tahun Pajak</th>
                            <th>Status Pegawai</th>
                            <th>NPWP/NIK/TIN</th>
                            <th>Nomor Passport</th>
                            <th>Status</th>
                            <th>Posisi</th>
                            <th>Sertifikat/Fasilitas</th>
                            <th>Kode Objek Pajak</th>
                            <th>Penghasilan Kotor</th>
                            <th>Tarif</th>
                            <th>ID TKU</th>
                            <th>Tgl Pemotongan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            <tr>
                                <td>{{ $entry['npwp_pemotong'] !== '' ? $entry['npwp_pemotong'] : '—' }}</td>
                                <td>{{ $entry['masa_pajak'] }}</td>
                                <td>{{ $entry['tahun_pajak'] }}</td>
                                <td>{{ $entry['status_pegawai'] }}</td>
                                <td>{{ $entry['npwp_nik_tin'] !== '' ? $entry['npwp_nik_tin'] : '—' }}</td>
                                <td>{{ $entry['nomor_passport'] !== '' ? $entry['nomor_passport'] : '—' }}</td>
                                <td>{{ $entry['status'] }}</td>
                                <td>{{ $entry['posisi'] }}</td>
                                <td>{{ $entry['sertifikat_fasilitas'] }}</td>
                                <td>{{ $entry['kode_objek_pajak'] }}</td>
                                <td>{{ number_format($entry['gross'], 2, ',', '.') }}</td>
                                <td>{{ rtrim(rtrim(number_format($entry['tarif'], 4, ',', '.'), '0'), ',') ?: '0' }}</td>
                                <td>{{ $entry['id_tku'] !== '' ? $entry['id_tku'] : '—' }}</td>
                                <td>{{ $entry['tgl_pemotongan'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="d-flex justify-content-end gap-2 mt-3">
            <button type="submit" name="export" value="xml" class="btn btn-outline-primary">
                <i class="fas fa-file-code"></i> Unduh XML
            </button>
            <button type="submit" name="export" value="xlsx" class="btn btn-primary">
                <i class="fas fa-file-download"></i> Unduh XLSX
            </button>
        </div>
    </form>
@endsection
