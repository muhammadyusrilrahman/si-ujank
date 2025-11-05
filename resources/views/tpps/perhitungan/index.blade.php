@extends('tpps.layout')

@section('title', 'Perhitungan TPP')

@section('card-tools')
    @php
        $cardFilterParams = array_filter([
            'type' => request('type'),
            'tahun' => request('tahun'),
            'bulan' => request('bulan'),
            'skpd_id' => request('skpd_id'),
        ], fn ($value) => $value !== null && $value !== '');
    @endphp
    <a href="{{ route('tpps.index', $cardFilterParams) }}" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Kembali ke Data TPP
    </a>
    <a href="{{ route('tpps.perhitungan.template') }}" class="btn btn-outline-secondary btn-sm" data-no-loader="true">
        <i class="fas fa-file-download"></i> Unduh Template
    </a>
    @if ($filtersReady ?? false)
        <a href="{{ route('tpps.perhitungan.export', $cardFilterParams) }}" class="btn btn-outline-primary btn-sm" data-no-loader="true">
            <i class="fas fa-file-export"></i> Export Excel
        </a>
    @endif
    @if (auth()->user()?->isSuperAdmin() || auth()->user()?->isAdminUnit())
        @php
            $filtersReadyFlag = $filtersReady ?? false;
        @endphp
        <button type="button" class="btn btn-outline-info btn-sm {{ $filtersReadyFlag ? '' : 'disabled' }}" data-toggle="modal" data-target="#copyModal" {{ $filtersReadyFlag ? '' : 'disabled' }} title="{{ $filtersReadyFlag ? '' : 'Pilih jenis ASN, tahun, dan bulan terlebih dahulu' }}">
            <i class="fas fa-copy"></i> Salin Periode
        </button>
        <button type="button" class="btn btn-outline-success btn-sm {{ $filtersReadyFlag ? '' : 'disabled' }}" data-toggle="modal" data-target="#importModal" {{ $filtersReadyFlag ? '' : 'disabled' }} title="{{ $filtersReadyFlag ? '' : 'Pilih jenis ASN, tahun, dan bulan terlebih dahulu' }}">
            <i class="fas fa-file-upload"></i> Import Excel
        </button>
        <a href="{{ route('tpps.perhitungan.create', $cardFilterParams) }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Tambah Perhitungan
        </a>
    @endif
@endsection

@push('scripts')
@php
    $extraKeysForScript = array_values(array_keys($extraFieldMap ?? []));
@endphp
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const currencyFormatter = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const formatRupiah = (value) => `Rp ${currencyFormatter.format(value)}`;
        const percentFormatter = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const rows = Array.from(document.querySelectorAll('.calc-row'));
        const extraFields = @json($extraKeysForScript);
        const percentFields = ['presensi_persen_ketidakhadiran', 'presensi_persen_kehadiran', 'kinerja_persen'];
        const currencyOutputFields = new Set(['presensi_nilai', 'kinerja_nilai']);

        function toNumber(value) {
            const parsed = Number.parseFloat(value);
            return Number.isFinite(parsed) ? parsed : 0;
        }

        function setCurrencyDisplay(span, value) {
            span.textContent = formatRupiah(value);
        }

        function initRow(row) {
            row.querySelectorAll('.calc-input').forEach((input) => {
                input.addEventListener('input', () => {
                    recalcRow(row);
                    updateSummary();
                });
                input.addEventListener('change', () => {
                    recalcRow(row);
                    updateSummary();
                });
            });
            recalcRow(row);
        }

        function recalcRow(row) {
            const getInput = (field) => {
                const input = row.querySelector(`.calc-input[data-field="${field}"]`);
                return input ? toNumber(input.value) : 0;
            };
            const setOutput = (field, value) => {
                const input = row.querySelector(`.calc-output[data-field="${field}"]`);
                if (!input) {
                    return value;
                }

                if (currencyOutputFields.has(field)) {
                    const rounded = Math.round(value);
                    input.value = String(rounded);
                    return rounded;
                }

                input.value = value.toFixed(2);
                return value;
            };
            const setDisplay = (field, value) => {
                const span = row.querySelector(`.calc-display[data-field="${field}"]`);
                if (span) {
                    setCurrencyDisplay(span, value);
                }
            };

            const beban = getInput('beban_kerja');
            const kondisi = getInput('kondisi_kerja');
            let extrasTotal = 0;
            extraFields.forEach((field) => {
                extrasTotal += getInput(field);
            });

            const jumlahTpp = beban + kondisi + extrasTotal;

            let absentCount = getInput('presensi_ketidakhadiran');
            if (absentCount < 0) {
                absentCount = 0;
                const absentInput = row.querySelector('.calc-input[data-field="presensi_ketidakhadiran"]');
                if (absentInput) {
                    absentInput.value = absentCount.toFixed(2);
                }
            }
            const absentPercent = Math.min(40, absentCount * 3);
            const presencePercent = Math.max(0, 40 - absentPercent);
            const presenceValueRaw = jumlahTpp * (presencePercent / 100);

            let kinerjaPercent = getInput('kinerja_persen');
            if (kinerjaPercent < 0) {
                kinerjaPercent = 0;
            } else if (kinerjaPercent > 60) {
                kinerjaPercent = 60;
            }
            const kinerjaInput = row.querySelector('.calc-input[data-field="kinerja_persen"]');
            if (kinerjaInput) {
                kinerjaInput.value = kinerjaPercent.toFixed(2);
            }
            const kinerjaValueRaw = jumlahTpp * (kinerjaPercent / 100);

            const pfkPph21 = getInput('pfk_pph21');
            const pfkBpjs4 = getInput('pfk_bpjs4');
            const pfkBpjs1 = getInput('pfk_bpjs1');

            const presenceValue = setOutput('presensi_nilai', presenceValueRaw);
            const kinerjaValue = setOutput('kinerja_nilai', kinerjaValueRaw);

            const bruto = presenceValue + kinerjaValue + pfkPph21 + pfkBpjs4;
            const netto = bruto - (pfkPph21 + pfkBpjs4 + pfkBpjs1);

            setOutput('presensi_persen_ketidakhadiran', absentPercent);
            setOutput('presensi_persen_kehadiran', presencePercent);
            setDisplay('jumlah_tpp', jumlahTpp);
            setDisplay('bruto', bruto);
            setDisplay('netto', netto);

            row.dataset.bebanKerja = beban;
            row.dataset.kondisiKerja = kondisi;
            extraFields.forEach((field) => {
                row.dataset[field] = getInput(field);
            });
            row.dataset.jumlahTpp = jumlahTpp;
            row.dataset.presensiCount = absentCount;
            row.dataset.presensiPercentAbsent = absentPercent;
            row.dataset.presensiPercentPresence = presencePercent;
            row.dataset.presensiValue = presenceValue;
            row.dataset.kinerjaPercent = kinerjaPercent;
            row.dataset.kinerjaValue = kinerjaValue;
            row.dataset.pfkPph21 = pfkPph21;
            row.dataset.pfkBpjs4 = pfkBpjs4;
            row.dataset.pfkBpjs1 = pfkBpjs1;
            row.dataset.bruto = bruto;
            row.dataset.netto = netto;
        }

        function updateSummary() {
            const totals = {
                beban_kerja: 0,
                kondisi_kerja: 0,
                jumlah_tpp: 0,
                presensi_ketidakhadiran: 0,
                presensi_persen_ketidakhadiran: 0,
                presensi_persen_kehadiran: 0,
                presensi_nilai: 0,
                kinerja_persen: 0,
                kinerja_nilai: 0,
                bruto: 0,
                pfk_pph21: 0,
                pfk_bpjs4: 0,
                pfk_bpjs1: 0,
                netto: 0,
                rowCount: rows.length
            };
            extraFields.forEach((field) => {
                totals[field] = 0;
            });

            rows.forEach((row) => {
                totals.beban_kerja += toNumber(row.dataset.bebanKerja);
                totals.kondisi_kerja += toNumber(row.dataset.kondisiKerja);
                extraFields.forEach((field) => {
                    totals[field] += toNumber(row.dataset[field]);
                });
                totals.jumlah_tpp += toNumber(row.dataset.jumlahTpp);
                totals.presensi_ketidakhadiran += toNumber(row.dataset.presensiCount);
                totals.presensi_persen_ketidakhadiran += toNumber(row.dataset.presensiPercentAbsent);
                totals.presensi_persen_kehadiran += toNumber(row.dataset.presensiPercentPresence);
                totals.presensi_nilai += toNumber(row.dataset.presensiValue);
                totals.kinerja_persen += toNumber(row.dataset.kinerjaPercent);
                totals.kinerja_nilai += toNumber(row.dataset.kinerjaValue);
                totals.bruto += toNumber(row.dataset.bruto);
                totals.pfk_pph21 += toNumber(row.dataset.pfkPph21);
                totals.pfk_bpjs4 += toNumber(row.dataset.pfkBpjs4);
                totals.pfk_bpjs1 += toNumber(row.dataset.pfkBpjs1);
                totals.netto += toNumber(row.dataset.netto);
            });

            const avgAbsentPercent = totals.rowCount ? totals.presensi_persen_ketidakhadiran / totals.rowCount : 0;
            const avgPresencePercent = totals.rowCount ? totals.presensi_persen_kehadiran / totals.rowCount : 0;
            const avgKinerjaPercent = totals.rowCount ? totals.kinerja_persen / totals.rowCount : 0;

            document.querySelectorAll('.summary-cell').forEach((cell) => {
                const field = cell.dataset.summary;
                let value = totals[field] || 0;
                if (percentFields.includes(field)) {
                    let formatted = 0;
                    if (field === 'presensi_persen_ketidakhadiran') {
                        formatted = avgAbsentPercent;
                    } else if (field === 'presensi_persen_kehadiran') {
                        formatted = avgPresencePercent;
                    } else if (field === 'kinerja_persen') {
                        formatted = avgKinerjaPercent;
                    }
                    cell.textContent = percentFormatter.format(formatted);
                } else if (field === 'presensi_ketidakhadiran') {
                    cell.textContent = percentFormatter.format(value);
                } else {
                    cell.textContent = formatRupiah(value);
                }
            });

            const pfkTotal = totals.pfk_pph21 + totals.pfk_bpjs4 + totals.pfk_bpjs1;
            document.querySelectorAll('.summary-display').forEach((element) => {
                const field = element.dataset.summaryField;
                let value = 0;
                if (field === 'pfk_total') {
                    value = pfkTotal;
                } else if (field in totals) {
                    value = totals[field];
                }
                element.textContent = formatRupiah(value);
            });
        }

        rows.forEach(initRow);
        updateSummary();
    });
</script>
@endpush
@section('card-body')
    <style>
        .perhitungan-table th,
        .perhitungan-table td {
            white-space: nowrap;
        }

        .perhitungan-table .text-end {
            white-space: nowrap;
        }
    </style>

    @php
        $formatCurrency = fn (float $value): string => \App\Support\MoneyFormatter::rupiah($value);
        $formatPercent = fn (float $value): string => number_format($value, 2, ',', '.');
        $filterParams = array_filter([
            'type' => $selectedType ?? null,
            'tahun' => $selectedYear ?? null,
            'bulan' => $selectedMonth ?? null,
        ], fn ($value) => $value !== null && $value !== '');
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
        $extraKeys = array_keys($extraFieldMap ?? []);
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
        <div class="col-md-2">
            <label for="filter-year" class="form-label">Tahun</label>
            <input type="number" min="2000" max="{{ date('Y') + 5 }}" id="filter-year" name="tahun" value="{{ $selectedYear ?? '' }}" class="form-control" required>
        </div>
        <div class="col-md-2">
            <label for="filter-month" class="form-label">Bulan</label>
            <select id="filter-month" name="bulan" class="form-control" required>
                <option value="">Pilih Bulan</option>
                @foreach ($monthOptions as $value => $label)
                    <option value="{{ $value }}" {{ (string) $selectedMonth === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label for="filter-per-page" class="form-label">Data per Halaman</label>
            <select id="filter-per-page" name="per_page" class="form-control">
                @foreach ($perPageOptions as $option)
                    <option value="{{ $option }}" {{ (int) $perPage === (int) $option ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="filter-search" class="form-label">Cari Pegawai</label>
            <input type="text" id="filter-search" name="search" value="{{ $searchTerm ?? '' }}" class="form-control" placeholder="Nama atau NIP">
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Terapkan
            </button>
            <a href="{{ route('tpps.perhitungan') }}" class="btn btn-outline-secondary">Atur Ulang</a>
        </div>
    </form>

    @if (! $filtersReady)
        <div class="alert alert-info">Pilih jenis ASN, tahun, dan bulan, kemudian tekan Terapkan untuk menampilkan perhitungan TPP.</div>
    @elseif ($calculations->isEmpty())
        <div class="alert alert-warning">Belum ada data perhitungan TPP untuk filter yang dipilih.</div>
    @else
        @if ($summary)
            <div class="row row-cols-1 row-cols-md-5 g-3 mb-4">
                <div class="col">
                    <div class="card card-outline card-primary h-100">
                        <div class="card-body">
                            <div class="text-muted text-uppercase small">Jumlah TPP</div>
                            <div class="h5 mb-0 summary-display" data-summary-field="jumlah_tpp">{{ $formatCurrency($summary['jumlah_tpp'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card card-outline card-primary h-100">
                        <div class="card-body">
                            <div class="text-muted text-uppercase small">Total Presensi</div>
                            <div class="h5 mb-0 summary-display" data-summary-field="presensi_nilai">{{ $formatCurrency($summary['presensi_nilai'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card card-outline card-primary h-100">
                        <div class="card-body">
                            <div class="text-muted text-uppercase small">Total Kinerja</div>
                            <div class="h5 mb-0 summary-display" data-summary-field="kinerja_nilai">{{ $formatCurrency($summary['kinerja_nilai'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card card-outline card-primary h-100">
                        <div class="card-body">
                            <div class="text-muted text-uppercase small">Total PFK</div>
                            <div class="h5 mb-0 summary-display" data-summary-field="pfk_total">{{ $formatCurrency(($summary['pfk_pph21'] ?? 0) + ($summary['pfk_bpjs4'] ?? 0) + ($summary['pfk_bpjs1'] ?? 0)) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card card-outline card-primary h-100">
                        <div class="card-body">
                            <div class="text-muted text-uppercase small">Total Netto</div>
                            <div class="h5 mb-0 summary-display" data-summary-field="netto">{{ $formatCurrency($summary['netto'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle perhitungan-table">
                <thead class="table-primary">
                    <tr class="text-center align-middle">
                        <th rowspan="2">No</th>
                        <th rowspan="2">Nama / NIP / Jabatan</th>
                        <th rowspan="2">Kelas Jabatan</th>
                        <th rowspan="2">Gol / Ruang</th>
                        <th rowspan="2">Beban Kerja</th>
                        @foreach ($extraKeys as $key)
                            <th rowspan="2">{{ $extrasLabels[$key] ?? strtoupper(str_replace('_', ' ', $key)) }}</th>
                        @endforeach
                        <th rowspan="2">Kondisi Kerja</th>
                        <th rowspan="2">Jumlah TPP</th>
                        <th colspan="4">Persentase Indeks Presensi (maks 40%)</th>
                        <th colspan="2">Persentase Indeks Kinerja (maks 60%)</th>
                        <th rowspan="2">Bruto</th>
                        <th colspan="3">Setoran PFK</th>
                        <th rowspan="2">Netto</th>
                        <th rowspan="2">Tanda Terima</th>
                        <th rowspan="2">Aksi</th>
                    </tr>
                    <tr class="text-center">
                        <th>Jumlah Ketidakhadiran</th>
                        <th>% Ketidakhadiran</th>
                        <th>% Kehadiran</th>
                        <th>Nilai (Rp)</th>
                        <th>Persentase</th>
                        <th>Nilai (Rp)</th>
                        <th>PPH 21</th>
                        <th>BPJS 4%</th>
                        <th>BPJS 1%</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($calculations as $index => $item)
                    @php
                        $calculation = $item['model'];
                        $row = $item['data'];
                        $extras = $row['extras'] ?? [];
                        $rowNumber = ($calculations->currentPage() - 1) * $calculations->perPage() + $index + 1;
                        $formId = "calc-form-{$calculation->id}";
                        $formAction = route('tpps.perhitungan.update', array_merge(['calculation' => $calculation->id], $filterParams));
                    @endphp
                    <form id="{{ $formId }}" action="{{ $formAction }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="jenis_asn" value="{{ $calculation->jenis_asn }}">
                        <input type="hidden" name="tahun" value="{{ $calculation->tahun }}">
                        <input type="hidden" name="bulan" value="{{ $calculation->bulan }}">
                    </form>
                    <tr class="calc-row" data-row-id="{{ $calculation->id }}">
                        <td class="text-center">{{ $rowNumber }}</td>
                        <td>
                            <div>{{ $row['pegawai']['nama'] ?? '-' }}</div>
                            <div class="text-muted small">{{ $row['pegawai']['nip'] ?? '-' }}</div>
                            <div class="text-muted small">{{ $row['pegawai']['jabatan'] ?? '-' }}</div>
                        </td>
                        <td>{{ $row['kelas_jabatan'] }}</td>
                        <td>{{ $row['golongan'] }}</td>
                        <td>
                            <input type="number" min="0" step="0.01" class="form-control form-control-sm calc-input text-end" data-field="beban_kerja" name="beban_kerja" form="{{ $formId }}" value="{{ (float) ($row['beban_kerja'] ?? 0) }}">
                        </td>
                        @foreach ($extraKeys as $key)
                            <td>
                                <input type="number" min="0" step="0.01" class="form-control form-control-sm calc-input text-end" data-field="{{ $key }}" name="{{ $key }}" form="{{ $formId }}" value="{{ (float) ($extras[$key] ?? 0) }}">
                            </td>
                        @endforeach
                        <td>
                            <input type="number" min="0" step="0.01" class="form-control form-control-sm calc-input text-end" data-field="kondisi_kerja" name="kondisi_kerja" form="{{ $formId }}" value="{{ (float) ($row['kondisi_kerja'] ?? 0) }}">
                        </td>
                        <td class="text-end"><span class="calc-display currency" data-field="jumlah_tpp">{{ $formatCurrency((float) ($row['jumlah_tpp'] ?? 0)) }}</span></td>
                        <td>
                            <input type="number" min="0" step="0.01" class="form-control form-control-sm calc-input text-end" data-field="presensi_ketidakhadiran" name="presensi_ketidakhadiran" form="{{ $formId }}" value="{{ (float) ($row['presensi']['ketidakhadiran'] ?? 0) }}">
                        </td>
                        <td class="text-end">
                            <input type="number" min="0" max="40" step="0.01" class="form-control form-control-sm text-end calc-output" data-field="presensi_persen_ketidakhadiran" name="presensi_persen_ketidakhadiran" form="{{ $formId }}" value="{{ (float) ($row['presensi']['persentase_ketidakhadiran'] ?? 0) }}" readonly>
                        </td>
                        <td class="text-end">
                            <input type="number" min="0" max="40" step="0.01" class="form-control form-control-sm text-end calc-output" data-field="presensi_persen_kehadiran" name="presensi_persen_kehadiran" form="{{ $formId }}" value="{{ (float) ($row['presensi']['persentase_kehadiran'] ?? 0) }}" readonly>
                        </td>
                        <td class="text-end">
                            <input type="number" min="0" step="1" class="form-control form-control-sm text-end calc-output" data-field="presensi_nilai" name="presensi_nilai" form="{{ $formId }}" value="{{ (float) ($row['presensi']['nilai'] ?? 0) }}" readonly>
                        </td>
                        <td>
                            <input type="number" min="0" max="60" step="0.01" class="form-control form-control-sm calc-input text-end" data-field="kinerja_persen" name="kinerja_persen" form="{{ $formId }}" value="{{ (float) ($row['kinerja']['persentase'] ?? 60) }}">
                        </td>
                        <td class="text-end">
                            <input type="number" min="0" step="1" class="form-control form-control-sm text-end calc-output" data-field="kinerja_nilai" name="kinerja_nilai" form="{{ $formId }}" value="{{ (float) ($row['kinerja']['nilai'] ?? 0) }}" readonly>
                        </td>
                        <td class="text-end"><span class="calc-display currency" data-field="bruto">{{ $formatCurrency((float) ($row['bruto'] ?? 0)) }}</span></td>
                        <td>
                            <input type="number" min="0" step="0.01" class="form-control form-control-sm calc-input text-end" data-field="pfk_pph21" name="pfk_pph21" form="{{ $formId }}" value="{{ (float) ($row['pfk']['pph21'] ?? 0) }}">
                        </td>
                        <td>
                            <input type="number" min="0" step="0.01" class="form-control form-control-sm calc-input text-end" data-field="pfk_bpjs4" name="pfk_bpjs4" form="{{ $formId }}" value="{{ (float) ($row['pfk']['bpjs4'] ?? 0) }}">
                        </td>
                        <td>
                            <input type="number" min="0" step="0.01" class="form-control form-control-sm calc-input text-end" data-field="pfk_bpjs1" name="pfk_bpjs1" form="{{ $formId }}" value="{{ (float) ($row['pfk']['bpjs1'] ?? 0) }}">
                        </td>
                        <td class="text-end"><span class="calc-display currency" data-field="netto">{{ $formatCurrency((float) ($row['netto'] ?? 0)) }}</span></td>
                        <td>
                            <input type="text" class="form-control form-control-sm" name="tanda_terima" form="{{ $formId }}" value="{{ $row['tanda_terima'] ?? '' }}" readonly>
                        </td>
                        <td class="text-nowrap align-middle">
                            @if (auth()->user()?->isSuperAdmin() || auth()->user()?->isAdminUnit())
                                <button type="submit" class="btn btn-sm btn-primary mb-1" form="{{ $formId }}">
                                    <i class="fas fa-save"></i> Simpan
                                </button>
                                <a href="{{ route('tpps.perhitungan.edit', array_merge(['calculation' => $calculation->id], $filterParams)) }}" class="btn btn-sm btn-outline-secondary mb-1">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form action="{{ route('tpps.perhitungan.destroy', ['calculation' => $calculation->id] + $filterParams) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus perhitungan TPP ini?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @else
                                <span class="badge bg-secondary">Tidak ada aksi</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot class="table-secondary">
                    <tr>
                        <th colspan="4" class="text-end">Total</th>
                        <th class="text-end"><span class="summary-cell" data-summary="beban_kerja">0,00</span></th>
                        @foreach ($extraKeys as $key)
                            <th class="text-end"><span class="summary-cell" data-summary="{{ $key }}">0,00</span></th>
                        @endforeach
                        <th class="text-end"><span class="summary-cell" data-summary="kondisi_kerja">0,00</span></th>
                        <th class="text-end"><span class="summary-cell" data-summary="jumlah_tpp">0,00</span></th>
                        <th class="text-end"><span class="summary-cell" data-summary="presensi_ketidakhadiran">0,00</span></th>
                        <th class="text-end"><span class="summary-cell" data-summary="presensi_persen_ketidakhadiran">0,00</span></th>
                        <th class="text-end"><span class="summary-cell" data-summary="presensi_persen_kehadiran">0,00</span></th>
                        <th class="text-end"><span class="summary-cell" data-summary="presensi_nilai">0</span></th>
                        <th class="text-end"><span class="summary-cell" data-summary="kinerja_persen">0,00</span></th>
                        <th class="text-end"><span class="summary-cell" data-summary="kinerja_nilai">0</span></th>
                        <th class="text-end"><span class="summary-cell" data-summary="bruto">0,00</span></th>
                        <th class="text-end"><span class="summary-cell" data-summary="pfk_pph21">0,00</span></th>
                        <th class="text-end"><span class="summary-cell" data-summary="pfk_bpjs4">0,00</span></th>
                        <th class="text-end"><span class="summary-cell" data-summary="pfk_bpjs1">0,00</span></th>
                        <th class="text-end"><span class="summary-cell" data-summary="netto">0,00</span></th>
                        <th></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-3">
            {{ $calculations->withQueryString()->links() }}
        </div>
    @endif

    @if (auth()->user()?->isSuperAdmin() || auth()->user()?->isAdminUnit())
        <!-- Copy Modal -->
        <div class="modal fade" id="copyModal" tabindex="-1" aria-labelledby="copyModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('tpps.perhitungan.copy') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="copyModalLabel">Salin Perhitungan dari Periode Lain</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="type" value="{{ $selectedType ?? '' }}">
                            <input type="hidden" name="tahun" value="{{ $selectedYear ?? '' }}">
                            <input type="hidden" name="bulan" value="{{ $selectedMonth ?? '' }}">
                            <div class="mb-3">
                                <label for="copy-source-year" class="form-label">Tahun Sumber</label>
                                <input type="number" class="form-control" id="copy-source-year" name="source_tahun" min="2000" max="{{ date('Y') + 5 }}" value="{{ ($selectedYear ?? (int) date('Y')) - 1 }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="copy-source-month" class="form-label">Bulan Sumber</label>
                                <select class="form-control" id="copy-source-month" name="source_bulan" required>
                                    @foreach ($monthOptions as $value => $label)
                                        <option value="{{ $value }}" {{ (string) ($selectedMonth ?? '') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="copy-overwrite" name="overwrite">
                                <label class="form-check-label" for="copy-overwrite">
                                    Timpa data yang sudah ada di periode tujuan.
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Salin Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Import Modal -->
        <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('tpps.perhitungan.import') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="importModalLabel">Import Perhitungan TPP</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="type" value="{{ $selectedType }}">
                      <input type="hidden" name="tahun" value="{{ $selectedYear }}">
                      <input type="hidden" name="bulan" value="{{ $selectedMonth }}">
                      @if (auth()->user()->isSuperAdmin())
                          <div class="mb-3">
                              <label for="import-skpd" class="form-label">SKPD Tujuan</label>
                              <select name="skpd_id" id="import-skpd" class="form-control @error('skpd_id') is-invalid @enderror">
                                  <option value="" {{ ($selectedSkpdId ?? null) ? '' : 'selected' }}>Pilih SKPD (opsional)</option>
                                  @foreach ($skpds ?? collect() as $skpd)
                                      <option value="{{ $skpd->id }}" {{ (string) old('skpd_id', $selectedSkpdId ?? null) === (string) $skpd->id ? 'selected' : '' }}>
                                          {{ $skpd->name }}
                                      </option>
                                  @endforeach
                              </select>
                              @error('skpd_id')
                                  <div class="invalid-feedback">{{ $message }}</div>
                              @enderror
                          </div>
                      @endif
                      @if (! ($filtersReady ?? false))
                          <div class="alert alert-warning">
                              Pilih jenis ASN, tahun, dan bulan terlebih dahulu sebelum melakukan import.
                          </div>
                      @endif
                            <div class="mb-3">
                                <label for="import-file" class="form-label">File Excel (.xlsx)</label>
                                <input type="file" class="form-control" id="import-file" name="file" accept=".xlsx" required>
                                <small class="text-muted">Pastikan kolom mengikuti template perhitungan TPP.</small>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="import-overwrite" name="overwrite">
                                <label class="form-check-label" for="import-overwrite">
                                    Timpa data perhitungan yang sudah ada.
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success">Import</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
