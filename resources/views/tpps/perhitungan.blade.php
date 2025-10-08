@extends('tpps.layout')

@section('title', 'Perhitungan TPP')

@push('styles')
<style>
    .perhitungan-table th,
    .perhitungan-table td {
        white-space: normal;
        word-break: normal;
        overflow-wrap: anywhere;
        min-width: 15ch;
        max-width: 35ch;
    }

    .perhitungan-table .wide-column {
        min-width: 25ch;
        max-width: 40ch;
    }

    .perhitungan-table .number-column {
        min-width: 10ch;
        max-width: 10ch;
    }
</style>
@endpush

@section('card-tools')
    <a href="{{ route('tpps.index', array_filter(['type' => request('type'), 'tahun' => request('tahun'), 'bulan' => request('bulan')])) }}" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Kembali ke Data TPP
    </a>
@endsection

@section('card-body')
    @php
        $formatCurrency = fn (float $value): string => number_format($value, 2, ',', '.');
        $formatPercent = fn (float $value): string => number_format($value, 2, ',', '.');
        $rows = $rows ?? collect();

        $sampleRows = collect([
            [
                'pegawai' => [
                    'nama' => 'Siti Rahmawati',
                    'nip' => '19790312 200501 2 001',
                    'jabatan' => 'Kepala Sub Bagian Umum',
                ],
                'kelas_jabatan' => '10',
                'golongan' => 'III/d',
                'beban_kerja' => 7_500_000.00,
                'extras' => [
                    'plt20' => 1_500_000.00,
                    'ppkd20' => 0.00,
                    'bud20' => 0.00,
                    'kbud20' => 0.00,
                    'tim_tapd20' => 1_500_000.00,
                    'tim_tpp20' => 0.00,
                    'bendahara_penerimaan10' => 0.00,
                    'bendahara_pengeluaran30' => 0.00,
                    'pengurus_barang20' => 0.00,
                    'pejabat_pengadaan10' => 750_000.00,
                    'tim_tapd20_from_beban' => 1_500_000.00,
                    'ppk5' => 375_000.00,
                    'pptk5' => 375_000.00,
                ],
                'kondisi_kerja' => 1_250_000.00,
                'jumlah_tpp' => 13_250_000.00,
                'presensi' => [
                    'ketidakhadiran' => 0,
                    'persentase_ketidakhadiran' => 0,
                    'persentase_kehadiran' => 100,
                    'nilai' => 5_300_000.00,
                ],
                'kinerja' => [
                    'persentase' => 60.00,
                    'nilai' => 7_950_000.00,
                ],
                'bruto' => 13_250_000.00,
                'pfk' => [
                    'pph21' => 1_200_000.00,
                    'bpjs4' => 530_000.00,
                    'bpjs1' => 132_500.00,
                ],
                'netto' => 11_387_500.00,
            ],
            [
                'pegawai' => [
                    'nama' => 'Andi Prasetyo',
                    'nip' => '19840824 201001 1 002',
                    'jabatan' => 'Analis Keuangan',
                ],
                'kelas_jabatan' => '9',
                'golongan' => 'III/c',
                'beban_kerja' => 5_250_000.00,
                'extras' => [
                    'plt20' => 0.00,
                    'ppkd20' => 1_050_000.00,
                    'bud20' => 0.00,
                    'kbud20' => 0.00,
                    'tim_tapd20' => 0.00,
                    'tim_tpp20' => 1_050_000.00,
                    'bendahara_penerimaan10' => 525_000.00,
                    'bendahara_pengeluaran30' => 0.00,
                    'pengurus_barang20' => 0.00,
                    'pejabat_pengadaan10' => 0.00,
                    'tim_tapd20_from_beban' => 0.00,
                    'ppk5' => 262_500.00,
                    'pptk5' => 0.00,
                ],
                'kondisi_kerja' => 950_000.00,
                'jumlah_tpp' => 9_087_500.00,
                'presensi' => [
                    'ketidakhadiran' => 1,
                    'persentase_ketidakhadiran' => 5.00,
                    'persentase_kehadiran' => 95.00,
                    'nilai' => 3_635_000.00,
                ],
                'kinerja' => [
                    'persentase' => 55.00,
                    'nilai' => 4_998_125.00,
                ],
                'bruto' => 9_087_500.00,
                'pfk' => [
                    'pph21' => 820_000.00,
                    'bpjs4' => 363_500.00,
                    'bpjs1' => 90_875.00,
                ],
                'netto' => 7_813_125.00,
            ],
        ]);

        $displayRows = $rows->isNotEmpty() ? $rows : $sampleRows;
        $showingSample = $rows->isEmpty();
    @endphp

    <form method="GET" action="{{ route('tpps.perhitungan') }}" class="row g-3 align-items-end mb-4">
        <div class="col-md-3">
            <label for="filter-type" class="form-label">Jenis ASN</label>
            <select id="filter-type" name="type" class="form-control">
                @foreach ($typeLabels as $key => $label)
                    <option value="{{ $key }}" {{ $key === $selectedType ? 'selected' : '' }}>{{ strtoupper($key) }} - {{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="filter-year" class="form-label">Tahun</label>
            <input type="number" min="2000" max="{{ date('Y') + 5 }}" id="filter-year" name="tahun" value="{{ $selectedYear ?? '' }}" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label for="filter-month" class="form-label">Bulan</label>
            <select id="filter-month" name="bulan" class="form-control" required>
                <option value="">Pilih Bulan</option>
                @foreach ($monthOptions as $value => $label)
                    <option value="{{ $value }}" {{ (string) $selectedMonth === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill"><i class="fas fa-filter"></i> Terapkan</button>
            <a href="{{ route('tpps.perhitungan') }}" class="btn btn-outline-secondary flex-fill">Atur Ulang</a>
        </div>
    </form>

    @if (! $filtersReady)
        <div class="alert alert-info">Pilih jenis ASN, tahun, dan bulan untuk menampilkan perhitungan TPP. Ditampilkan contoh visual tabel.</div>
    @elseif ($showingSample)
        <div class="alert alert-warning">Belum ada data TPP untuk kriteria yang dipilih. Menampilkan contoh visual tabel.</div>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover align-middle perhitungan-table">
            <thead class="table-primary">
                <tr class="text-center">
                    <th rowspan="2" class="number-column text-center">No</th>
                    <th rowspan="2" class="wide-column">Nama / NIP / Jabatan</th>
                    <th rowspan="2">Kelas Jabatan</th>
                    <th rowspan="2">Gol / Ruang</th>
                    <th rowspan="2">Tambahan Penghasilan Beban Kerja Per Bulan</th>
                    <th rowspan="2">Tambahan TPP PLT 20%</th>
                    <th rowspan="2">Tambahan TPP PPKD 20%</th>
                    <th rowspan="2">Tambahan TPP BUD 20%</th>
                    <th rowspan="2">Tambahan TPP KBUD 20%</th>
                    <th rowspan="2">Tambahan TPP TIM TAPD 20%</th>
                    <th rowspan="2">Tambahan TPP TIM TPP 20%</th>
                    <th rowspan="2">Tambahan TPP Bendahara Penerimaan 10%</th>
                    <th rowspan="2">Tambahan TPP Bendahara Pengeluaran 30%</th>
                    <th rowspan="2">Tambahan TPP Pengurus Barang 20%</th>
                    <th rowspan="2">Tambahan TPP Pejabat Pengadaan 10%</th>
                    <th rowspan="2">Tambahan TPP TIM TAPD (20% dari Beban Kerja)</th>
                    <th rowspan="2">Tambahan TPP PPK 5%</th>
                    <th rowspan="2">Tambahan TPP PPTK 5%</th>
                    <th rowspan="2">Tambahan Penghasilan Kondisi Kerja Per Bulan</th>
                    <th rowspan="2">Jumlah TPP</th>
                    <th colspan="4">Persentase Indeks Presensi Maximal 40%</th>
                    <th colspan="2">Persentase Indeks Kinerja Maximal 60%</th>
                    <th rowspan="2">Jumlah TPP (BRUTO)</th>
                    <th colspan="3">Setoran PFK</th>
                    <th rowspan="2">Jumlah TPP Diterima (NETTO)</th>
                    <th rowspan="2">Tanda Terima</th>
                </tr>
                <tr class="text-center">
                    <th>Jumlah Ketidakhadiran</th>
                    <th>% Ketidakhadiran</th>
                    <th>% Kehadiran</th>
                    <th>Nilai (Rp)</th>
                    <th>Persentase</th>
                    <th>Nilai (Rp)</th>
                    <th>PPH Pasal 21</th>
                    <th>BPJS 4%</th>
                    <th>BPJS 1%</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($displayRows as $index => $row)
                    @php
                        $jumlahTpp = (float) ($row['jumlah_tpp'] ?? 0);
                        $presensiData = $row['presensi'] ?? [];
                        $presensiNilai = (float) ($presensiData['nilai'] ?? 0);
                        $persentaseKetidakhadiran = (float) ($presensiData['persentase_ketidakhadiran'] ?? 0);
                        $persentaseKehadiran = (float) ($presensiData['persentase_kehadiran'] ?? 0);

                        $kinerjaData = $row['kinerja'] ?? [];
                        if (is_array($kinerjaData)) {
                            $kinerjaPersentase = (float) ($kinerjaData['persentase'] ?? 0);
                            $kinerjaNilai = (float) ($kinerjaData['nilai'] ?? 0);
                        } else {
                            $kinerjaPersentase = (float) $kinerjaData;
                            $kinerjaNilai = $jumlahTpp * (min($kinerjaPersentase, 60) / 100);
                        }
                    @endphp
                    <tr>
                        <td class="text-center number-column">{{ $index + 1 }}</td>
                        <td class="wide-column">
                            <div>{{ $row['pegawai']['nama'] }}</div>
                            <div class="text-muted small">{{ $row['pegawai']['nip'] }}</div>
                            <div class="text-muted small">{{ $row['pegawai']['jabatan'] }}</div>
                        </td>
                        <td>{{  $row['kelas_jabatan']  }}</td>
                        <td>{{ $row['golongan'] }}</td>
                        <td class="text-end">{{ $formatCurrency((float) ($row['beban_kerja'] ?? 0)) }}</td>
                        <td class="text-end">{{ $formatCurrency((float) ($row['extras']['plt20'] ?? 0)) }}</td>
                        <td class="text-end">{{ $formatCurrency((float) ($row['extras']['ppkd20'] ?? 0)) }}</td>
                        <td class="text-end">{{ $formatCurrency((float) ($row['extras']['bud20'] ?? 0)) }}</td>
                        <td class="text-end">{{ $formatCurrency((float) ($row['extras']['kbud20'] ?? 0)) }}</td>
                        <td class="text-end">{{ $formatCurrency((float) ($row['extras']['tim_tapd20'] ?? 0)) }}</td>
                        <td class="text-end">{{ $formatCurrency((float) ($row['extras']['tim_tpp20'] ?? 0)) }}</td>
                        <td class="text-end">{{ $formatCurrency((float) ($row['extras']['bendahara_penerimaan10'] ?? 0)) }}</td>
                        <td class="text-end">{{ $formatCurrency((float) ($row['extras']['bendahara_pengeluaran30'] ?? 0)) }}</td>
                        <td class="text-end">{{ $formatCurrency((float) ($row['extras']['pengurus_barang20'] ?? 0)) }}</td>
                        <td class="text-end">{{ $formatCurrency((float) ($row['extras']['pejabat_pengadaan10'] ?? 0)) }}</td>
                        <td class="text-end">{{ $formatCurrency((float) ($row['extras']['tim_tapd20_from_beban'] ?? 0)) }}</td>
                        <td class="text-end">{{ $formatCurrency((float) ($row['extras']['ppk5'] ?? 0)) }}</td>
                        <td class="text-end">{{ $formatCurrency((float) ($row['extras']['pptk5'] ?? 0)) }}</td>
                        <td class="text-end">{{ $formatCurrency((float) ($row['kondisi_kerja'] ?? 0)) }}</td>
                        <td class="text-end">{{ $formatCurrency($jumlahTpp) }}</td>
                        <td class="text-end">{{ $formatCurrency((float) ($presensiData['ketidakhadiran'] ?? 0)) }}</td>
                        <td class="text-end">{{ $formatPercent($persentaseKetidakhadiran) }}%</td>
                        <td class="text-end">{{ $formatPercent($persentaseKehadiran) }}%</td>
                        <td class="text-end">{{ $formatCurrency($presensiNilai) }}</td>
                        <td class="text-end">{{ $formatPercent($kinerjaPersentase) }}%</td>
                        <td class="text-end">{{ $formatCurrency($kinerjaNilai) }}</td>
                        <td class="text-end">{{ $formatCurrency((float) ($row['bruto'] ?? $jumlahTpp)) }}</td>
                        <td class="text-end">{{ $formatCurrency((float) ($row['pfk']['pph21'] ?? 0)) }}</td>
                        <td class="text-end">{{ $formatCurrency((float) ($row['pfk']['bpjs4'] ?? 0)) }}</td>
                        <td class="text-end">{{ $formatCurrency((float) ($row['pfk']['bpjs1'] ?? 0)) }}</td>
                        <td class="text-end">{{ $formatCurrency((float) ($row['netto'] ?? ($jumlahTpp - ((float) ($row['pfk']['pph21'] ?? 0) + (float) ($row['pfk']['bpjs4'] ?? 0) + (float) ($row['pfk']['bpjs1'] ?? 0))))) }}</td>
                        <td></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection













