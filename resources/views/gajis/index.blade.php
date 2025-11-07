@extends('layouts.app')

@section('title', 'Data Gaji')
@section('page-title', 'Data Gaji Pegawai')

@section('content')
@php
    use Illuminate\Pagination\LengthAwarePaginator;
    use App\Support\MoneyFormatter;

    $currentUser = auth()->user();
    $isSuperAdmin = $currentUser->isSuperAdmin();
    $canManageGaji = $currentUser->isSuperAdmin() || $currentUser->isAdminUnit();

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

    $monetaryFields = $monetaryFields ?? [];
    if (! $monetaryFields) {
        $monetaryFields = [];
        foreach (config('gaji.monetary_fields', []) as $field => $meta) {
            $monetaryFields[$field] = $meta['label'] ?? ucwords(str_replace('_', ' ', $field));
        }
    }

    $monetaryFieldList = collect($monetaryFields)->map(function ($label, $key) {
        return [
            'key' => $key,
            'label' => $label,
        ];
    })->values();

    $totalAllowanceFields = $totalAllowanceFields ?? config('gaji.total_allowance_fields', []);
    $totalDeductionFields = $totalDeductionFields ?? config('gaji.total_deduction_fields', []);

    $gajisPaginator = $gajis ?? null;
    $gajiTotal = $gajisPaginator ? $gajisPaginator->total() : 0;

    $items = [];

    if ($filtersReady && $gajisPaginator instanceof LengthAwarePaginator) {
        $items = $gajisPaginator->map(function ($gaji) use ($monetaryFields, $monthOptions, $totalAllowanceFields, $totalDeductionFields, $selectedType, $canManageGaji) {
            $pegawai = $gaji->pegawai;

            $monetaryValues = [];
            foreach ($monetaryFields as $field => $label) {
                $monetaryValues[$field] = (float) ($gaji->{$field} ?? 0);
            }

            $allowanceSum = 0.0;
            foreach ($totalAllowanceFields as $field) {
                $allowanceSum += (float) ($monetaryValues[$field] ?? 0);
            }

            $deductionSum = 0.0;
            foreach ($totalDeductionFields as $field) {
                $deductionSum += (float) ($monetaryValues[$field] ?? 0);
            }

            $periodLabel = ($monthOptions[$gaji->bulan] ?? $gaji->bulan) . ' ' . $gaji->tahun;

            return [
                'id' => $gaji->id,
                'pegawai' => [
                    'name' => optional($pegawai)->nama_lengkap ?? '-',
                    'nip' => optional($pegawai)->nip ?? '-',
                ],
                'period' => [
                    'year' => $gaji->tahun,
                    'month' => $gaji->bulan,
                    'label' => $periodLabel,
                ],
                'monetary' => $monetaryValues,
                'totals' => [
                    'allowance' => $allowanceSum,
                    'deduction' => $deductionSum,
                    'transfer' => $allowanceSum - $deductionSum,
                ],
                'links' => [
                    'edit' => $canManageGaji ? route('gajis.edit', ['gaji' => $gaji, 'type' => $selectedType]) : null,
                    'destroy' => $canManageGaji ? route('gajis.destroy', ['gaji' => $gaji, 'type' => $selectedType]) : null,
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
    if ($filtersReady && $gajisPaginator instanceof LengthAwarePaginator) {
        $paginatorArray = $gajisPaginator->toArray();
        $pagination = [
            'from' => $gajisPaginator->firstItem(),
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
        'canManage' => $canManageGaji,
        'items' => $items,
        'monetaryFields' => $monetaryFieldList,
        'monetaryTotals' => $monetaryTotals,
        'summaryTotals' => $summaryTotals,
        'counts' => [
            'total' => $gajiTotal,
        ],
        'pagination' => $pagination,
        'routes' => [
            'index' => route('gajis.index'),
            'export' => route('gajis.export'),
            'ebupotIndex' => route('gajis.ebupot.index'),
            'ebupotCreate' => route('gajis.ebupot.create'),
            'template' => route('gajis.template'),
            'import' => route('gajis.import'),
            'bulkDestroy' => route('gajis.bulk-destroy'),
            'create' => route('gajis.create'),
        ],
        'csrfToken' => csrf_token(),
        'statusMessage' => session('status'),
        'importErrors' => $errors->get('file') ?? [],
    ];
@endphp

<div
    id="gaji-index-root"
    data-props='@json($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'
></div>

<noscript>
    <div class="alert alert-warning mt-3">
        Halaman ini memerlukan JavaScript agar dapat digunakan sepenuhnya. Silakan aktifkan JavaScript pada peramban Anda.
    </div>
</noscript>
@endsection

