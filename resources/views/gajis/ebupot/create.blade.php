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
        $periodLabel = null;

        if (($selectedYear ?? false) && ($selectedMonth ?? false)) {
            $periodLabel = 'Periode: ' . ($monthOptions[$selectedMonth] ?? ($selectedMonth ?? '-')) . ' ' . ($selectedYear ?? '');
        }

        $componentProps = [
            'entries' => $entries->map(fn ($entry) => [
                'npwp_pemotong' => $entry['npwp_pemotong'] ?? '',
                'masa_pajak' => $entry['masa_pajak'] ?? '',
                'tahun_pajak' => $entry['tahun_pajak'] ?? '',
                'status_pegawai' => $entry['status_pegawai'] ?? '',
                'npwp_nik_tin' => $entry['npwp_nik_tin'] ?? '',
                'nomor_passport' => $entry['nomor_passport'] ?? '',
                'status' => $entry['status'] ?? '',
                'posisi' => $entry['posisi'] ?? '',
                'sertifikat_fasilitas' => $entry['sertifikat_fasilitas'] ?? '',
                'kode_objek_pajak' => $entry['kode_objek_pajak'] ?? '',
                'gross' => $entry['gross'] ?? 0,
                'tarif' => $entry['tarif'] ?? 0,
                'id_tku' => $entry['id_tku'] ?? '',
                'tgl_pemotongan' => $entry['tgl_pemotongan'] ?? '',
            ])->values(),
            'periodLabel' => $periodLabel,
            'emptyMessage' => 'Data gaji untuk periode yang dipilih belum tersedia. Silakan kembali dan pastikan filter tahun dan bulan berisi data.',
        ];
    @endphp

    <form action="{{ route('gajis.ebupot.store') }}" method="POST" class="mt-3" data-no-loader="true">
        @csrf
        <input type="hidden" name="default_npwp_pemotong" value="{{ $defaultNpwpPemotong }}">
        <input type="hidden" name="default_id_tku" value="{{ $defaultIdTku }}">
        <input type="hidden" name="default_kode_objek" value="{{ $defaultKodeObjek }}">
        <input type="hidden" name="type" value="{{ $selectedType ?? request('type') }}">
        <input type="hidden" name="tahun" value="{{ $selectedYear ?? request('tahun') }}">
        <input type="hidden" name="bulan" value="{{ $selectedMonth ?? request('bulan') }}">

        <div
            id="gaji-ebupot-root"
            data-props='@json($componentProps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'
        ></div>

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

