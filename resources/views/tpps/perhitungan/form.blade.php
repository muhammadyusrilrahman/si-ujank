@extends('tpps.layout')

@section('title', $isUpdate ? 'Ubah Perhitungan TPP' : 'Buat Perhitungan TPP')

@section('card-tools')
    <a href="{{ route('tpps.perhitungan', $filterParams) }}" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Kembali ke Perhitungan
    </a>
@endsection

@section('card-body')
    @php
        $payloadCollection = collect($payload ?? []);
        $extrasLabels = [
            'plt20' => 'TPP PLT 20%',
            'ppkd20' => 'TPP PPKD 20%',
            'bud20' => 'TPP BUD 20%',
            'kbud20' => 'TPP KBUD 20%',
            'tim_tapd20' => 'TPP Tim TAPD 20%',
            'tim_tpp20' => 'TPP Tim TPP 20%',
            'bendahara_penerimaan10' => 'TPP Bendahara Penerimaan 10%',
            'bendahara_pengeluaran30' => 'TPP Bendahara Pengeluaran 30%',
            'pengurus_barang20' => 'TPP Pengurus Barang 20%',
            'pejabat_pengadaan10' => 'TPP Pejabat Pengadaan 10%',
            'tim_tapd20_from_beban' => 'TPP Tim TAPD (20% dari Beban Kerja)',
            'ppk5' => 'TPP PPK 5%',
            'pptk5' => 'TPP PPTK 5%',
        ];

        $formatMoney = static function ($value) {
            if ($value === null || $value === '') {
                return '';
            }

            return number_format((float) $value, 2, '.', '');
        };

        $selectedJenisAsn = old('jenis_asn', $selectedType);
        $selectedYearValue = old('tahun', $defaultYear);
        $selectedMonthValue = old('bulan', $defaultMonth);
        $selectedPegawaiId = old('pegawai_id', optional($pegawai)->id);

        $typeOptionsData = collect($typeLabels)
            ->map(fn ($label, $key) => [
                'value' => (string) $key,
                'label' => $label,
            ])
            ->values()
            ->all();

        $monthOptionsData = collect($monthOptions)
            ->map(fn ($label, $key) => [
                'value' => (string) $key,
                'label' => $label,
            ])
            ->values()
            ->all();

        $pegawaiOptionsData = $pegawaiOptions
            ->map(fn ($option) => [
                'id' => (string) $option->id,
                'nama_lengkap' => $option->nama_lengkap,
                'nip' => $option->nip,
            ])
            ->values()
            ->all();

        $pegawaiDisplay = '';
        if ($isUpdate && $pegawai) {
            $pegawaiDisplay = trim(($pegawai->nama_lengkap ?? '-') . ($pegawai->nip ? ' - ' . $pegawai->nip : ''));
        }

        $extrasData = collect($extrasLabels)
            ->map(fn ($label, $key) => [
                'key' => $key,
                'label' => $label,
                'value' => $formatMoney(old($key, $payloadCollection->get($key, 0))),
            ])
            ->values()
            ->all();

        $props = [
            'isUpdate' => (bool) $isUpdate,
            'typeOptions' => $typeOptionsData,
            'selectedType' => (string) $selectedJenisAsn,
            'typeLocked' => (bool) $isUpdate,
            'monthOptions' => $monthOptionsData,
            'yearBounds' => [
                'min' => 2000,
                'max' => (int) date('Y') + 5,
            ],
            'pegawai' => [
                'options' => $pegawaiOptionsData,
                'selectedId' => $selectedPegawaiId ? (string) $selectedPegawaiId : '',
                'readonly' => (bool) $isUpdate,
                'display' => $pegawaiDisplay,
            ],
            'fields' => [
                'kelas_jabatan' => old('kelas_jabatan', $payloadCollection->get('kelas_jabatan')),
                'golongan' => old('golongan', $payloadCollection->get('golongan')),
                'tanda_terima' => old('tanda_terima', $payloadCollection->get('tanda_terima')),
                'beban_kerja' => $formatMoney(old('beban_kerja', $payloadCollection->get('beban_kerja', 0))),
                'kondisi_kerja' => $formatMoney(old('kondisi_kerja', $payloadCollection->get('kondisi_kerja', 0))),
                'pfk_pph21' => $formatMoney(old('pfk_pph21', $payloadCollection->get('pfk_pph21', 0))),
                'pfk_bpjs4' => $formatMoney(old('pfk_bpjs4', $payloadCollection->get('pfk_bpjs4', 0))),
                'pfk_bpjs1' => $formatMoney(old('pfk_bpjs1', $payloadCollection->get('pfk_bpjs1', 0))),
                'presensi_ketidakhadiran' => (float) old('presensi_ketidakhadiran', $payloadCollection->get('presensi_ketidakhadiran', 0)),
                'presensi_persen_ketidakhadiran' => (float) old('presensi_persen_ketidakhadiran', $payloadCollection->get('presensi_persen_ketidakhadiran', 0)),
                'presensi_persen_kehadiran' => (float) old('presensi_persen_kehadiran', $payloadCollection->get('presensi_persen_kehadiran', 40)),
                'presensi_nilai' => (float) old('presensi_nilai', $payloadCollection->get('presensi_nilai', 0)),
                'kinerja_persen' => (float) old('kinerja_persen', $payloadCollection->get('kinerja_persen', 60)),
                'kinerja_nilai' => (float) old('kinerja_nilai', $payloadCollection->get('kinerja_nilai', 0)),
            ],
            'extras' => $extrasData,
            'summary' => [
                'jumlah_tpp' => (float) $payloadCollection->get('jumlah_tpp', 0),
                'bruto' => (float) $payloadCollection->get('bruto', 0),
                'pfk_pph21' => (float) $payloadCollection->get('pfk_pph21', 0),
                'pfk_bpjs4' => (float) $payloadCollection->get('pfk_bpjs4', 0),
                'pfk_bpjs1' => (float) $payloadCollection->get('pfk_bpjs1', 0),
                'netto' => (float) $payloadCollection->get('netto', 0),
            ],
            'errors' => $errors->toArray(),
            'initial' => [
                'year' => $selectedYearValue !== null ? (string) $selectedYearValue : '',
                'month' => $selectedMonthValue !== null ? (string) $selectedMonthValue : '',
            ],
        ];
    @endphp

    <form method="POST" id="perhitungan-form" action="{{ $actionRoute }}">
        @csrf
        @if ($isUpdate)
            @method('PUT')
        @endif

        <div
            id="tpp-calculation-root"
            data-props='@json($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'
        ></div>

        <div class="d-flex flex-wrap gap-2 mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> {{ $isUpdate ? 'Simpan Perubahan' : 'Simpan Perhitungan' }}
            </button>
            <a href="{{ route('tpps.perhitungan', $filterParams) }}" class="btn btn-outline-secondary">
                Batal
            </a>
        </div>
    </form>
@endsection

