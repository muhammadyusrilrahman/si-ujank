@extends('layouts.app')

@section('title', 'Data TPP')
@section('page-title', 'Data TPP Pegawai')

@section('content')
@php
    use Illuminate\Pagination\LengthAwarePaginator;

    $currentUser = auth()->user();
    $isSuperAdmin = $currentUser->isSuperAdmin();
    $canManageTpp = $currentUser->isSuperAdmin() || $currentUser->isAdminUnit();

    $typeLabels = $typeLabels ?? ['pns' => 'PNS', 'pppk' => 'PPPK'];
    $typeOptions = collect($typeLabels)->map(function ($label, $value) {
        return [
            'value' => (string) $value,
            'label' => $label,
        ];
    })->values();

    $monthOptions = $monthOptions ?? [];
    $monthOptionsArray = collect($monthOptions)->map(function ($label, $value) {
        return [
            'value' => (string) $value,
            'label' => $label,
        ];
    })->values();

    $selectedType = $selectedType ?? 'pns';
    $selectedTypeLabel = $typeLabels[$selectedType] ?? strtoupper($selectedType);
    $filtersReady = $filtersReady ?? false;
    $selectedYear = $selectedYear ?? null;
    $selectedMonth = $selectedMonth ?? null;
    $searchTerm = $searchTerm ?? null;
    $perPage = $perPage ?? 25;
    $perPageOptions = $perPageOptions ?? [25, 50, 100];
    $defaultPerPage = $perPageOptions[0] ?? 25;
    $selectedSkpdId = $isSuperAdmin ? ($selectedSkpdId ?? null) : null;

    $allowanceFields = $allowanceFields ?? [];
    $deductionFields = $deductionFields ?? [];

    $monetaryFieldList = collect($allowanceFields)->map(function ($label, $key) {
        return [
            'key' => $key,
            'label' => $label,
        ];
    })->values()->merge(
        collect($deductionFields)->map(function ($label, $key) {
            return [
                'key' => $key,
                'label' => $label,
            ];
        })->values()
    );

    $totalTppFields = $totalTppFields ?? [];
    $totalPotonganFields = $totalPotonganFields ?? [];

    $tppsPaginator = $tpps ?? null;
    $tppTotal = $tppsPaginator ? $tppsPaginator->total() : 0;

    $items = [];

    if ($filtersReady && $tppsPaginator instanceof LengthAwarePaginator) {
        $items = $tppsPaginator->map(function ($tpp) use ($allowanceFields, $deductionFields, $monthOptions, $totalTppFields, $totalPotonganFields, $selectedType, $canManageTpp) {
            $pegawai = $tpp->pegawai;

            $monetaryValues = [];
            foreach ($allowanceFields as $field => $label) {
                $monetaryValues[$field] = (float) ($tpp->{$field} ?? 0);
            }
            foreach ($deductionFields as $field => $label) {
                $monetaryValues[$field] = (float) ($tpp->{$field} ?? 0);
            }

            $allowanceSum = 0.0;
            foreach ($totalTppFields as $field) {
                $allowanceSum += (float) ($monetaryValues[$field] ?? 0);
            }

            $deductionSum = 0.0;
            foreach ($totalPotonganFields as $field) {
                $deductionSum += (float) ($monetaryValues[$field] ?? 0);
            }

            $periodLabel = ($monthOptions[$tpp->bulan] ?? $tpp->bulan) . ' ' . $tpp->tahun;

            return [
                'id' => $tpp->id,
                'pegawai' => [
                    'name' => optional($pegawai)->nama_lengkap ?? '-',
                    'nip' => optional($pegawai)->nip ?? '-',
                ],
                'period' => [
                    'year' => $tpp->tahun,
                    'month' => $tpp->bulan,
                    'label' => $periodLabel,
                ],
                'monetary' => $monetaryValues,
                'totals' => [
                    'allowance' => $allowanceSum,
                    'deduction' => $deductionSum,
                    'transfer' => $allowanceSum - $deductionSum,
                ],
                'links' => [
                    'edit' => $canManageTpp ? route('tpps.edit', ['tpp' => $tpp, 'type' => $selectedType]) : null,
                    'destroy' => $canManageTpp ? route('tpps.destroy', ['tpp' => $tpp, 'type' => $selectedType]) : null,
                ],
            ];
        })->values()->all();
    }

    $monetaryTotals = array_map('floatval', $monetaryTotals ?? []);
    $summaryTotals = [
        'allowance' => (float) (($summaryTotals['allowance'] ?? 0)),
        'deduction' => (float) (($summaryTotals['deduction'] ?? 0)),
        'transfer' => (float) (($summaryTotals['transfer'] ?? 0)),
    ];

    $pagination = null;
    if ($filtersReady && $tppsPaginator instanceof LengthAwarePaginator) {
        $paginatorArray = $tppsPaginator->toArray();
        $pagination = [
            'from' => $tppsPaginator->firstItem(),
            'links' => collect($paginatorArray['links'] ?? [])->map(function ($link) {
                return [
                    'url' => $link['url'],
                    'label' => $link['label'],
                    'active' => $link['active'],
                ];
            })->all(),
        ];
    }

    $skpdOptions = ($skpds ?? collect())->map(function ($skpd) {
        return [
            'id' => (string) $skpd->id,
            'name' => $skpd->name,
        ];
    })->values()->all();

    $props = [
        'typeOptions' => $typeOptions,
        'filters' => [
            'selectedType' => $selectedType,
            'selectedTypeLabel' => $selectedTypeLabel,
            'year' => $selectedYear,
            'month' => $selectedMonth,
            'search' => $searchTerm,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'filtersReady' => $filtersReady,
            'defaultPerPage' => $defaultPerPage,
            'isSuperAdmin' => $isSuperAdmin,
            'selectedSkpdId' => $selectedSkpdId,
        ],
        'monthOptions' => $monthOptionsArray,
        'skpds' => $skpdOptions,
        'canManage' => $canManageTpp,
        'items' => $items,
        'monetaryFields' => $monetaryFieldList,
        'monetaryTotals' => $monetaryTotals,
        'summaryTotals' => $summaryTotals,
        'counts' => [
            'total' => $tppTotal,
        ],
        'pagination' => $pagination,
        'routes' => [
            'index' => route('tpps.index'),
            'export' => route('tpps.export'),
            'ebupotIndex' => route('tpps.ebupot.index'),
            'ebupotCreate' => route('tpps.ebupot.create'),
            'template' => route('tpps.template'),
            'import' => route('tpps.import'),
            'bulkDestroy' => route('tpps.bulk-destroy'),
            'create' => route('tpps.create'),
        ],
        'csrfToken' => csrf_token(),
        'statusMessage' => session('status'),
        'importErrors' => $errors->get('file') ?? [],
        'texts' => [
            'createButtonPrefix' => 'Tambah Data TPP',
            'noDataMessage' => 'Belum ada data TPP untuk kriteria ini.',
            'filtersNotReadyMessage' => 'Pilih tahun dan bulan untuk menampilkan data TPP.',
            'confirmDeleteItem' => 'Hapus data TPP ini?',
            'confirmBulkDelete' => 'Hapus %count% data TPP terpilih?',
            'confirmBulkDeleteAll' => 'Hapus semua data TPP pada periode ini?',
            'totalAllowanceHeading' => 'Jumlah TPP',
            'totalDeductionHeading' => 'Jumlah Potongan',
            'totalTransferHeading' => 'Jumlah Ditransfer',
        ],
    ];
@endphp

<div
    id="tpp-index-root"
    data-props='@json($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'
></div>

<noscript>
    <div class="alert alert-warning mt-3">
        Halaman ini memerlukan JavaScript agar dapat digunakan sepenuhnya. Silakan aktifkan JavaScript pada peramban Anda.
    </div>
</noscript>
@endsection

