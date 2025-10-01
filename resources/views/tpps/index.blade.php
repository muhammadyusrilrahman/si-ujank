@extends('layouts.app')

@section('title', 'Data TPP')
@section('page-title', 'Data TPP Pegawai')

@section('content')
@php
    $typeLabels = $typeLabels ?? ['pns' => 'PNS', 'pppk' => 'PPPK'];
    $monthOptions = $monthOptions ?? [];
    $selectedType = $selectedType ?? 'pns';
    $filtersReady = $filtersReady ?? false;
    $selectedYear = $selectedYear ?? null;
    $selectedMonth = $selectedMonth ?? null;
    $perPage = $perPage ?? 25;
    $perPageOptions = $perPageOptions ?? [25, 50, 100];
    $allowanceFields = $allowanceFields ?? [];
    $deductionFields = $deductionFields ?? [];
    $currentUser = auth()->user();
    $canManageTpp = $currentUser->isSuperAdmin() || $currentUser->isAdminUnit();
    $formatCurrency = fn (float $value) => number_format($value, 2, ',', '.');
    $tppsPaginator = $tpps ?? null;
    $tppTotal = $tppsPaginator ? $tppsPaginator->total() : 0;
    $tppCurrentCount = $tppsPaginator ? $tppsPaginator->count() : 0;
    $monetaryTotals = $monetaryTotals ?? [];
    $summaryTotals = $summaryTotals ?? ['allowance' => 0, 'deduction' => 0, 'transfer' => 0];
    $baseColumnCount = 2 + count($allowanceFields) + count($deductionFields) + 3;
    $columnCount = $baseColumnCount + ($canManageTpp ? 2 : 0);
@endphp
<div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <ul class="nav nav-pills mb-2 mb-md-0">
        @foreach ($typeLabels as $typeKey => $label)
            <li class="nav-item">
                <a class="nav-link {{ $typeKey === $selectedType ? 'active' : '' }}" href="{{ route('tpps.index', array_filter([
                    'type' => $typeKey,
                    'tahun' => $selectedYear,
                    'bulan' => $selectedMonth,
                    'per_page' => $perPage === 25 ? null : $perPage,
                ])) }}">{{ $label }}</a>
            </li>
        @endforeach
    </ul>
    <div class="d-flex flex-wrap gap-2 justify-content-end">
        @if ($filtersReady)
            <a href="{{ route('tpps.export', ['type' => $selectedType, 'tahun' => $selectedYear, 'bulan' => $selectedMonth]) }}" class="btn btn-success mb-2"><i class="fas fa-file-excel"></i> Ekspor Excel</a>
            @if ($canManageTpp)
                <button type="submit" class="btn btn-danger mb-2" id="tpp-bulk-delete-button" form="tpp-bulk-delete-form" formaction="{{ route('tpps.bulk-destroy') }}" formmethod="POST" formnovalidate name="delete_all" value="0" {{ $tppCurrentCount === 0 ? 'disabled' : '' }}>
                    <i class="fas fa-trash"></i> Hapus Terpilih
                </button>
                <button type="submit" class="btn btn-danger mb-2" id="tpp-bulk-delete-all-button" form="tpp-bulk-delete-form" formaction="{{ route('tpps.bulk-destroy') }}" formmethod="POST" formnovalidate name="delete_all" value="1" {{ $tppTotal === 0 ? 'disabled' : '' }}>
                    <i class="fas fa-trash-alt"></i> Hapus Semua
                </button>
                <a href="{{ route('tpps.template', ['type' => $selectedType, 'tahun' => $selectedYear, 'bulan' => $selectedMonth]) }}" class="btn btn-outline-secondary mb-2"><i class="fas fa-download"></i> Template</a>
                <form action="{{ route('tpps.import') }}" method="POST" enctype="multipart/form-data" class="form-inline mb-2">
                    @csrf
                    <input type="hidden" name="type" value="{{ $selectedType }}">
                    <input type="hidden" name="tahun" value="{{ $selectedYear }}">
                    <input type="hidden" name="bulan" value="{{ $selectedMonth }}">
                    <div class="input-group">
                        <div class="custom-file">
                            <input type="file" name="file" class="custom-file-input" id="tpp-import-file" accept=".xlsx" required>
                            <label class="custom-file-label" for="tpp-import-file">Pilih file...</label>
                        </div>
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-upload"></i> Import</button>
                        </div>
                    </div>
                </form>
                <a href="{{ route('tpps.create', ['type' => $selectedType, 'tahun' => $selectedYear, 'bulan' => $selectedMonth]) }}" class="btn btn-primary mb-2"><i class="fas fa-plus"></i> Tambah Data TPP {{ $typeLabels[$selectedType] ?? strtoupper($selectedType) }}</a>
            @endif
        @else
            <div class="text-muted small mb-2">Pilih tahun dan bulan untuk mengakses ekspor, template, dan impor.</div>
        @endif
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
@if ($filtersReady && $canManageTpp && $tppsPaginator)
    <form id="tpp-bulk-delete-form" method="POST" action="{{ route('tpps.bulk-destroy') }}" class="d-none">
        @csrf
        @method('DELETE')
        <input type="hidden" name="type" value="{{ $selectedType }}">
        <input type="hidden" name="tahun" value="{{ $selectedYear }}">
        <input type="hidden" name="bulan" value="{{ $selectedMonth }}">
        <input type="hidden" name="per_page" value="{{ $perPage }}">
        @foreach (request()->except(['ids', 'page', '_token', '_method', 'delete_all', 'type', 'tahun', 'bulan', 'per_page']) as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
    </form>
@endif
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('tpps.index') }}" class="form-inline flex-wrap gap-2">
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

@if ($filtersReady && $tppsPaginator)
<div class="card">

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        @if ($canManageTpp)
                            <th class="text-center" style="width: 60px;">
                                <input type="checkbox" id="select-all-tpps" aria-label="Pilih semua Data TPP">
                            </th>
                        @endif
                        <th>Pegawai</th>
                        <th>Periode</th>
                        @foreach ($allowanceFields as $field => $label)
                            <th>{{ $label }}</th>
                        @endforeach
                        @foreach ($deductionFields as $field => $label)
                            <th>{{ $label }}</th>
                        @endforeach
                        <th>Jumlah TPP</th>
                        <th>Jumlah Potongan</th>
                        <th>Jumlah Ditransfer</th>
                        @if ($canManageTpp)
                            <th class="text-center" style="width: 100px;">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @if ($tppTotal > 0)
                        <tr class="font-weight-bold bg-light">
                            @if ($canManageTpp)
                                <td class="text-center align-middle">-</td>
                            @endif
                            <td>Total ({{ number_format($tppTotal, 0, ',', '.') }} data)</td>
                            <td>{{ $selectedMonth !== null && $selectedYear !== null ? (($monthOptions[$selectedMonth] ?? $selectedMonth) . '/' . $selectedYear) : '-' }}</td>
                            @foreach ($allowanceFields as $field => $label)
                                <td>{{ $formatCurrency((float) ($monetaryTotals[$field] ?? 0)) }}</td>
                            @endforeach
                            @foreach ($deductionFields as $field => $label)
                                <td>{{ $formatCurrency((float) ($monetaryTotals[$field] ?? 0)) }}</td>
                            @endforeach
                            <td>{{ $formatCurrency((float) ($summaryTotals['allowance'] ?? 0)) }}</td>
                            <td>{{ $formatCurrency((float) ($summaryTotals['deduction'] ?? 0)) }}</td>
                            <td>{{ $formatCurrency((float) ($summaryTotals['transfer'] ?? 0)) }}</td>
                            @if ($canManageTpp)
                                <td class="text-center align-middle">-</td>
                            @endif
                        </tr>
                    @endif
                    @forelse ($tpps as $tpp)
                        <tr>
                            @if ($canManageTpp)
                                <td class="text-center align-middle">
                                    <input type="checkbox" name="ids[]" value="{{ $tpp->id }}" class="tpp-select-checkbox" form="tpp-bulk-delete-form">
                                </td>
                            @endif
                            @php
                                $totalAllowance = 0;
                                foreach ($allowanceFields as $field => $label) {
                                    $totalAllowance += (float) $tpp->$field;
                                }
                                $totalDeduction = 0;
                                foreach ($deductionFields as $field => $label) {
                                    $totalDeduction += (float) $tpp->$field;
                                }
                                $transfer = $totalAllowance - $totalDeduction;
                            @endphp
                            <td>
                                <div class="font-weight-bold">{{ optional($tpp->pegawai)->nama_lengkap }}</div>
                                <div class="text-muted small">{{ optional($tpp->pegawai)->nip ?: '-' }}</div>
                            </td>
                            <td>{{ $monthOptions[$tpp->bulan] ?? $tpp->bulan }}/{{ $tpp->tahun }}</td>
                            @foreach ($allowanceFields as $field => $label)
                                <td>{{ $formatCurrency((float) $tpp->$field) }}</td>
                            @endforeach
                            @foreach ($deductionFields as $field => $label)
                                <td>{{ $formatCurrency((float) $tpp->$field) }}</td>
                            @endforeach
                            <td>{{ $formatCurrency($totalAllowance) }}</td>
                            <td>{{ $formatCurrency($totalDeduction) }}</td>
                            <td>{{ $formatCurrency($transfer) }}</td>
                            @if ($canManageTpp)
                                <td class="text-center">
                                    <a href="{{ route('tpps.edit', ['tpp' => $tpp, 'type' => $selectedType]) }}" class="btn btn-sm btn-warning mb-1"><i class="fas fa-edit"></i></a>
                                    <form action="{{ route('tpps.destroy', ['tpp' => $tpp, 'type' => $selectedType]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data TPP ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="type" value="{{ $selectedType }}">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $columnCount }}" class="text-center text-muted py-4">Belum ada Data TPP untuk kriteria ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($tpps->hasPages())
        <div class="card-footer bg-white">
            {{ $tpps->onEachSide(1)->links('pagination::bootstrap-4') }}
        </div>
    @endif
</div>
@elseif (! $filtersReady)
    <div class="alert alert-info">Pilih tahun dan bulan untuk menampilkan Data TPP.</div>
@endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const importInput = document.getElementById('tpp-import-file');
        if (importInput) {
            importInput.addEventListener('change', function () {
                const label = this.nextElementSibling;
                if (label && this.files.length > 0) {
                    label.textContent = this.files[0].name;
                }
            });
        }

        const bulkForm = document.getElementById('tpp-bulk-delete-form');
        const bulkSelectedButton = document.getElementById('tpp-bulk-delete-button');
        const bulkAllButton = document.getElementById('tpp-bulk-delete-all-button');

        if (!bulkForm || !bulkSelectedButton) {
            return;
        }

        const selectAll = document.getElementById('select-all-tpps');
        const itemCheckboxes = Array.from(document.querySelectorAll('.tpp-select-checkbox'));

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
            const isDeleteAll = submitter && submitter.id === 'tpp-bulk-delete-all-button';

            if (isDeleteAll) {
                if (!confirm('Hapus semua data TPP pada periode ini?')) {
                    event.preventDefault();
                }

                return;
            }

            const checkedCount = itemCheckboxes.filter((checkbox) => checkbox.checked).length;
            if (checkedCount === 0 || !confirm(`Hapus ${checkedCount} data TPP terpilih?`)) {
                event.preventDefault();
            }
        });

        updateButtonState();
    });
</script>
@endpush
















