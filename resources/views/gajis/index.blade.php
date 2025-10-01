@extends('layouts.app')

@section('title', 'Data Gaji')
@section('page-title', 'Data Gaji Pegawai')

@section('content')
@php
    $typeLabels = $typeLabels ?? ['pns' => 'PNS', 'pppk' => 'PPPK'];
    $monthOptions = $monthOptions ?? [];
    $selectedType = $selectedType ?? 'pns';
    $filtersReady = $filtersReady ?? false;
    $selectedYear = $selectedYear ?? null;
    $selectedMonth = $selectedMonth ?? null;
    $searchTerm = $searchTerm ?? null;
    $perPage = $perPage ?? 25;
    $perPageOptions = $perPageOptions ?? [25, 50, 100];
    $allowanceFields = $allowanceFields ?? [];
    $deductionFields = $deductionFields ?? [];
    $monetaryTotals = $monetaryTotals ?? [];
    $summaryTotals = $summaryTotals ?? ['allowance' => 0, 'deduction' => 0, 'transfer' => 0];
    $gajisPaginator = $gajis ?? null;
    $gajiTotal = $gajisPaginator ? $gajisPaginator->total() : 0;
    $gajiCurrentCount = $gajisPaginator ? $gajisPaginator->count() : 0;
    $columnBase = 2 + count($allowanceFields) + count($deductionFields) + 3;
    $currentUser = auth()->user();
    $canManageGaji = $currentUser->isSuperAdmin() || $currentUser->isAdminUnit();
    $columnCount = $columnBase + ($canManageGaji ? 2 : 0);
    $formatCurrency = fn (float $value) => number_format($value, 2, ',', '.');
@endphp

<div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <ul class="nav nav-pills mb-2 mb-md-0">
        @foreach ($typeLabels as $typeKey => $label)
            <li class="nav-item">
                <a class="nav-link {{ $typeKey === $selectedType ? 'active' : '' }}"
                   href="{{ route('gajis.index', array_filter([
                       'type' => $typeKey,
                       'tahun' => $selectedYear,
                       'bulan' => $selectedMonth,
                       'per_page' => $perPage === 25 ? null : $perPage,
                       'search' => $searchTerm,
                   ])) }}">{{ $label }}</a>
            </li>
        @endforeach
    </ul>
    <div class="d-flex flex-wrap gap-2 justify-content-end">
        @if ($filtersReady)
            <a href="{{ route('gajis.export', ['type' => $selectedType, 'tahun' => $selectedYear, 'bulan' => $selectedMonth]) }}" class="btn btn-success mb-2"><i class="fas fa-file-excel"></i> Ekspor Excel</a>
            @if ($canManageGaji)
                <button type="submit" class="btn btn-danger mb-2" id="gaji-bulk-delete-button"
                        form="gaji-bulk-delete-form" formaction="{{ route('gajis.bulk-destroy') }}"
                        formmethod="POST" formnovalidate name="delete_all" value="0" {{ $gajiCurrentCount === 0 ? 'disabled' : '' }}>
                    <i class="fas fa-trash"></i> Hapus Terpilih
                </button>
                <button type="submit" class="btn btn-danger mb-2" id="gaji-bulk-delete-all-button"
                        form="gaji-bulk-delete-form" formaction="{{ route('gajis.bulk-destroy') }}"
                        formmethod="POST" formnovalidate name="delete_all" value="1" {{ $gajiTotal === 0 ? 'disabled' : '' }}>
                    <i class="fas fa-trash-alt"></i> Hapus Semua
                </button>
                <a href="{{ route('gajis.template', ['type' => $selectedType, 'tahun' => $selectedYear, 'bulan' => $selectedMonth]) }}" class="btn btn-outline-secondary mb-2"><i class="fas fa-download"></i> Template</a>
                <form action="{{ route('gajis.import') }}" method="POST" enctype="multipart/form-data" class="form-inline mb-2">
                    @csrf
                    <input type="hidden" name="type" value="{{ $selectedType }}">
                    <input type="hidden" name="tahun" value="{{ $selectedYear }}">
                    <input type="hidden" name="bulan" value="{{ $selectedMonth }}">
                    <div class="input-group">
                        <div class="custom-file">
                            <input type="file" name="file" class="custom-file-input" id="gaji-import-file" accept=".xlsx" required>
                            <label class="custom-file-label" for="gaji-import-file">Pilih file...</label>
                        </div>
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-upload"></i> Import</button>
                        </div>
                    </div>
                </form>
                <a href="{{ route('gajis.create', ['type' => $selectedType, 'tahun' => $selectedYear, 'bulan' => $selectedMonth]) }}" class="btn btn-primary mb-2"><i class="fas fa-plus"></i> Tambah Data Gaji {{ $typeLabels[$selectedType] ?? strtoupper($selectedType) }}</a>
            @endif
        @else
            <div class="text-muted small mb-2">Pilih tahun dan bulan untuk mengakses ekspor, template, dan impor.</div>
        @endif
    </div>
</div>

@if ($errors->has('file'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        @foreach ($errors->get('file') as $message)
            <div>{{ $message }}</div>
        @endforeach
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if (session('status'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('status') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if ($filtersReady && $gajisPaginator)
    @if ($canManageGaji)
        <form id="gaji-bulk-delete-form" method="POST" action="{{ route('gajis.bulk-destroy') }}" class="d-none">
            @csrf
            @method('DELETE')
            <input type="hidden" name="type" value="{{ $selectedType }}">
            <input type="hidden" name="tahun" value="{{ $selectedYear }}">
            <input type="hidden" name="bulan" value="{{ $selectedMonth }}">
            <input type="hidden" name="per_page" value="{{ $perPage }}">
            <input type="hidden" name="search" value="{{ $searchTerm }}">
            @foreach (request()->except(['ids', 'page', '_token', '_method', 'delete_all', 'type', 'tahun', 'bulan', 'per_page', 'search']) as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
        </form>
    @endif
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('gajis.index') }}" class="form-inline flex-wrap gap-2">
            <input type="hidden" name="type" value="{{ $selectedType }}">
            <div class="form-group mr-2 mb-2">
                <label for="filter-tahun" class="mr-2">Tahun</label>
                <input type="number" name="tahun" id="filter-tahun" class="form-control @error('tahun') is-invalid @enderror" value="{{ $selectedYear ?? '' }}" min="2000" max="{{ (int) date('Y') + 5 }}" required>
                @error('tahun')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group mr-2 mb-2">
                <label for="filter-bulan" class="mr-2">Bulan</label>
                <select name="bulan" id="filter-bulan" class="form-control @error('bulan') is-invalid @enderror" required>
                    <option value="" disabled {{ $selectedMonth === null ? 'selected' : '' }}>Pilih bulan</option>
                    @foreach ($monthOptions as $value => $label)
                        <option value="{{ $value }}" {{ $selectedMonth !== null && (int) $selectedMonth === (int) $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('bulan')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group mr-2 mb-2">
                <label for="filter-search" class="mr-2">Cari</label>
                <input type="text" name="search" id="filter-search" class="form-control @error('search') is-invalid @enderror" value="{{ $searchTerm ?? '' }}" placeholder="Nama atau NIP">
                @error('search')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group mr-2 mb-2">
                <label for="filter-per-page" class="mr-2">Per halaman</label>
                <select name="per_page" id="filter-per-page" class="form-control">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}" {{ (int) $perPage === (int) $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-outline-secondary mb-2"><i class="fas fa-filter"></i> Terapkan</button>
        </form>
    </div>
</div>

@if ($filtersReady)
    @if ($gajisPaginator && $gajisPaginator->count() > 0)
        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="thead-light">
                        <tr>
                            @if ($canManageGaji)
                                <th class="text-center" style="width: 40px;">
                                    <input type="checkbox" id="select-all-gajis">
                                </th>
                            @endif
                            <th style="width: 60px;">No</th>
                            <th>Pegawai</th>
                            <th>Periode</th>
                            @foreach ($allowanceFields as $field => $label)
                                <th>{{ $label }}</th>
                            @endforeach
                            @foreach ($deductionFields as $field => $label)
                                <th>{{ $label }}</th>
                            @endforeach
                            <th>Total Tunjangan</th>
                            <th>Total Potongan</th>
                            <th>Total Transfer</th>
                            @if ($canManageGaji)
                                <th class="text-center">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $rowOffset = ($gajisPaginator->currentPage() - 1) * $gajisPaginator->perPage();
                        @endphp
                        @foreach ($gajisPaginator as $index => $gaji)
                            @php
                                $allowanceSum = 0.0;
                                foreach (array_keys($allowanceFields) as $fieldKey) {
                                    $allowanceSum += (float) ($gaji->$fieldKey ?? 0);
                                }
                                $deductionSum = 0.0;
                                foreach (array_keys($deductionFields) as $fieldKey) {
                                    $deductionSum += (float) ($gaji->$fieldKey ?? 0);
                                }
                                $transfer = $allowanceSum - $deductionSum;
                                $pegawai = $gaji->pegawai;
                            @endphp
                            <tr>
                                @if ($canManageGaji)
                                    <td class="align-middle text-center">
                                        <input type="checkbox" name="ids[]" value="{{ $gaji->id }}" class="gaji-select-checkbox" form="gaji-bulk-delete-form">
                                    </td>
                                @endif
                                <td class="align-middle">{{ $rowOffset + $loop->iteration }}</td>
                                <td class="align-middle">
                                    <div>{{ optional($pegawai)->nama_lengkap ?? '-' }}</div>
                                    <div class="text-muted small">{{ optional($pegawai)->nip ?? '-' }}</div>
                                </td>
                                <td class="align-middle">{{ $monthOptions[$gaji->bulan] ?? $gaji->bulan }}/{{ $gaji->tahun }}</td>
                                @foreach ($allowanceFields as $field => $label)
                                    <td class="align-middle">{{ $formatCurrency((float) ($gaji->$field ?? 0)) }}</td>
                                @endforeach
                                @foreach ($deductionFields as $field => $label)
                                    <td class="align-middle">{{ $formatCurrency((float) ($gaji->$field ?? 0)) }}</td>
                                @endforeach
                                <td class="align-middle">{{ $formatCurrency($allowanceSum) }}</td>
                                <td class="align-middle">{{ $formatCurrency($deductionSum) }}</td>
                                <td class="align-middle">{{ $formatCurrency($transfer) }}</td>
                                @if ($canManageGaji)
                                    <td class="align-middle text-center">
                                        <a href="{{ route('gajis.edit', ['gaji' => $gaji, 'type' => $selectedType]) }}" class="btn btn-sm btn-warning mb-1"><i class="fas fa-edit"></i></a>
                                        <form action="{{ route('gajis.destroy', ['gaji' => $gaji, 'type' => $selectedType]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data gaji ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="type" value="{{ $selectedType }}">
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="font-weight-bold">
                            @if ($canManageGaji)
                                <td></td>
                            @endif
                            <td colspan="2">Total ({{ number_format($gajiTotal, 0, ',', '.') }} data)</td>
                            <td></td>
                            @foreach ($allowanceFields as $field => $label)
                                <td>{{ $formatCurrency((float) ($monetaryTotals[$field] ?? 0)) }}</td>
                            @endforeach
                            @foreach ($deductionFields as $field => $label)
                                <td>{{ $formatCurrency((float) ($monetaryTotals[$field] ?? 0)) }}</td>
                            @endforeach
                            <td>{{ $formatCurrency((float) ($summaryTotals['allowance'] ?? 0)) }}</td>
                            <td>{{ $formatCurrency((float) ($summaryTotals['deduction'] ?? 0)) }}</td>
                            <td>{{ $formatCurrency((float) ($summaryTotals['transfer'] ?? 0)) }}</td>
                            @if ($canManageGaji)
                                <td></td>
                            @endif
                        </tr>
                    </tfoot>
                </table>
            </div>
            @if ($gajisPaginator->hasPages())
                <div class="card-footer bg-white">
                    {{ $gajisPaginator->appends([
                        'type' => $selectedType,
                        'tahun' => $selectedYear,
                        'bulan' => $selectedMonth,
                        'per_page' => $perPage === 25 ? null : $perPage,
                        'search' => $searchTerm,
                    ])->onEachSide(1)->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </div>
    @else
        <div class="alert alert-light text-center">Belum ada data gaji untuk kriteria ini.</div>
    @endif
@elseif (! $filtersReady)
    <div class="alert alert-info">Pilih tahun dan bulan untuk menampilkan data gaji.</div>
@endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const importInput = document.getElementById('gaji-import-file');
        if (importInput) {
            importInput.addEventListener('change', function () {
                const label = this.nextElementSibling;
                if (label && this.files.length > 0) {
                    label.textContent = this.files[0].name;
                }
            });
        }

        const bulkForm = document.getElementById('gaji-bulk-delete-form');
        const bulkSelectedButton = document.getElementById('gaji-bulk-delete-button');

        if (!bulkForm || !bulkSelectedButton) {
            return;
        }

        const bulkAllButton = document.getElementById('gaji-bulk-delete-all-button');
        const selectAll = document.getElementById('select-all-gajis');
        const itemCheckboxes = Array.from(document.querySelectorAll('.gaji-select-checkbox'));

        const updateButtonState = () => {
            const checkedCount = itemCheckboxes.filter((checkbox) => checkbox.checked).length;
            bulkSelectedButton.disabled = checkedCount === 0;

            if (selectAll) {
                selectAll.checked = checkedCount > 0 && checkedCount === itemCheckboxes.length;
                selectAll.indeterminate = checkedCount > 0 && checkedCount < itemCheckboxes.length;
            }
        };

        if (selectAll) {
            selectAll.addEventListener('change', (event) => {
                itemCheckboxes.forEach((checkbox) => {
                    checkbox.checked = event.target.checked;
                });
                updateButtonState();
            });
        }

        itemCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', updateButtonState);
        });

        bulkForm.addEventListener('submit', (event) => {
            const submitter = event.submitter;
            const isDeleteAll = submitter && submitter.id === 'gaji-bulk-delete-all-button';

            if (isDeleteAll) {
                if (!confirm('Hapus semua data gaji pada periode ini?')) {
                    event.preventDefault();
                }
                return;
            }

            const checkedCount = itemCheckboxes.filter((checkbox) => checkbox.checked).length;
            if (checkedCount === 0 || !confirm(`Hapus ${checkedCount} data gaji terpilih?`)) {
                event.preventDefault();
            }
        });

        updateButtonState();
    });
</script>
@endpush
