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

@php
    use Illuminate\Pagination\LengthAwarePaginator;

    $filterParams = array_filter([
        'type' => $selectedType ?? null,
        'tahun' => $selectedYear ?? null,
        'bulan' => $selectedMonth ?? null,
    ], fn ($value) => $value !== null && $value !== '');

    $extraLabels = [
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

    $extraKeys = array_values(array_keys($extraFieldMap ?? []));
    $extraLabelMap = collect($extraKeys)->mapWithKeys(function (string $key) use ($extraLabels) {
        $fallback = strtoupper(str_replace('_', ' ', $key));
        return [$key => $extraLabels[$key] ?? $fallback];
    })->all();

    $typeOptions = collect($typeLabels ?? [])->map(function ($label, $key) {
        return [
            'value' => (string) $key,
            'label' => strtoupper($key) . ' - ' . $label,
        ];
    })->values()->all();

    $monthOptionItems = collect($monthOptions ?? [])->map(function ($label, $value) {
        return [
            'value' => (string) $value,
            'label' => $label,
        ];
    })->values()->all();

    $rows = [];
    $pagination = null;

    if ($calculations instanceof LengthAwarePaginator && $calculations->count() > 0) {
        $rows = $calculations->map(function ($item) use ($extraKeys, $filterParams) {
            /** @var \App\Models\TppCalculation $calculation */
            $calculation = $item['model'];
            $row = $item['data'] ?? [];
            $extras = collect($extraKeys)->mapWithKeys(function ($key) use ($row) {
                return [$key => (float) ($row['extras'][$key] ?? 0)];
            })->all();

            return [
                'id' => $calculation->id,
                'pegawai' => [
                    'nama' => $row['pegawai']['nama'] ?? '-',
                    'nip' => $row['pegawai']['nip'] ?? '-',
                    'jabatan' => $row['pegawai']['jabatan'] ?? '-',
                ],
                'kelas_jabatan' => $row['kelas_jabatan'] ?? '-',
                'golongan' => $row['golongan'] ?? '-',
                'beban_kerja' => (float) ($row['beban_kerja'] ?? 0),
                'kondisi_kerja' => (float) ($row['kondisi_kerja'] ?? 0),
                'extras' => $extras,
                'jumlah_tpp' => (float) ($row['jumlah_tpp'] ?? 0),
                'presensi' => [
                    'ketidakhadiran' => (float) ($row['presensi']['ketidakhadiran'] ?? 0),
                    'persentase_ketidakhadiran' => (float) ($row['presensi']['persentase_ketidakhadiran'] ?? 0),
                    'persentase_kehadiran' => (float) ($row['presensi']['persentase_kehadiran'] ?? 0),
                    'nilai' => (float) ($row['presensi']['nilai'] ?? 0),
                ],
                'kinerja' => [
                    'persentase' => (float) ($row['kinerja']['persentase'] ?? 0),
                    'nilai' => (float) ($row['kinerja']['nilai'] ?? 0),
                ],
                'bruto' => (float) ($row['bruto'] ?? 0),
                'pfk' => [
                    'pph21' => (float) ($row['pfk']['pph21'] ?? 0),
                    'bpjs4' => (float) ($row['pfk']['bpjs4'] ?? 0),
                    'bpjs1' => (float) ($row['pfk']['bpjs1'] ?? 0),
                ],
                'netto' => (float) ($row['netto'] ?? 0),
                'tanda_terima' => $row['tanda_terima'] ?? '',
                'routes' => [
                    'update' => route('tpps.perhitungan.update', array_merge(['calculation' => $calculation->id], $filterParams)),
                    'edit' => route('tpps.perhitungan.edit', array_merge(['calculation' => $calculation->id], $filterParams)),
                    'destroy' => route('tpps.perhitungan.destroy', array_merge(['calculation' => $calculation->id], $filterParams)),
                ],
            ];
        })->values()->all();

        $paginatorArray = $calculations->toArray();
        $pagination = [
            'from' => $calculations->firstItem(),
            'to' => $calculations->lastItem(),
            'total' => $calculations->total(),
            'links' => collect($paginatorArray['links'] ?? [])->map(function ($link) {
                return [
                    'url' => $link['url'],
                    'label' => $link['label'],
                    'active' => $link['active'],
                ];
            })->all(),
        ];
    }

    $messages = [
        'status' => session('success') ?? session('status'),
        'error' => session('error'),
        'importErrors' => $errors->get('file'),
    ];

    $props = [
        'filtersReady' => (bool) ($filtersReady ?? false),
        'filters' => [
            'type' => (string) ($selectedType ?? ''),
            'year' => $selectedYear,
            'month' => $selectedMonth !== null ? (string) $selectedMonth : '',
            'perPage' => (int) ($perPage ?? ($perPageOptions[0] ?? 25)),
            'search' => (string) ($searchTerm ?? ''),
        ],
        'options' => [
            'types' => $typeOptions,
            'months' => $monthOptionItems,
            'perPage' => array_values($perPageOptions ?? [25, 50, 100]),
            'yearBounds' => [
                'min' => 2000,
                'max' => (int) date('Y') + 5,
            ],
        ],
        'extras' => [
            'order' => $extraKeys,
            'labels' => $extraLabelMap,
        ],
        'items' => $rows,
        'pagination' => $pagination,
        'permissions' => [
            'canManage' => auth()->user()?->isSuperAdmin() || auth()->user()?->isAdminUnit(),
        ],
        'routes' => [
            'index' => route('tpps.perhitungan'),
        ],
        'context' => [
            'hiddenFields' => array_filter([
                'jenis_asn' => $selectedType,
                'tahun' => $selectedYear,
                'bulan' => $selectedMonth,
            ], static fn ($value) => $value !== null && $value !== ''),
        ],
        'messages' => $messages,
        'csrfToken' => csrf_token(),
    ];
@endphp

@section('card-body')
    <div
        id="tpp-calculation-index-root"
        data-props='@json($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'
    ></div>

    <noscript>
        <div class="alert alert-warning mt-3">
            Halaman ini memerlukan JavaScript agar dapat digunakan sepenuhnya. Silakan aktifkan JavaScript pada peramban Anda.
        </div>
    </noscript>

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

