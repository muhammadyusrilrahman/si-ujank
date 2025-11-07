@extends('tpps.layout')

@section('title', 'Arsip E-Bupot TPP')

@section('card-tools')
    <a href="{{ route('tpps.ebupot.create', array_filter([
        'type' => $filters['type'] ?? null,
        'tahun' => $filters['tahun'] ?? null,
        'bulan' => $filters['bulan'] ?? null,
    ])) }}" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i>
        Buat E-Bupot
    </a>
@endsection

@section('card-body')
@php
    use Illuminate\Pagination\LengthAwarePaginator;

    $currentUser = auth()->user();
    $isSuperAdmin = $currentUser->isSuperAdmin();

    $typeOptions = collect($typeLabels ?? [])->map(function ($label, $key) {
        return [
            'value' => (string) $key,
            'display' => strtoupper($key) . ' - ' . $label,
        ];
    })->values()->all();

    $monthOptionsArray = collect($monthOptions ?? [])->map(function ($label, $value) {
        return [
            'value' => (string) $value,
            'label' => $label,
        ];
    })->values()->all();

    $reportsPaginator = $reports ?? null;
    $items = [];
    $pagination = null;

    if ($reportsPaginator instanceof LengthAwarePaginator && $reportsPaginator->count() > 0) {
        $items = $reportsPaginator->map(function ($report) use ($isSuperAdmin) {
            $periode = sprintf('%02d/%d', $report->bulan, $report->tahun);

            return [
                'id' => $report->id,
                'period' => $periode,
                'jenis_asn' => strtoupper($report->jenis_asn),
                'skpd' => optional($report->skpd)->name,
                'npwp' => $report->npwp_pemotong,
                'id_tku' => $report->id_tku,
                'kode_objek' => $report->kode_objek,
                'entry_count' => (int) $report->entry_count,
                'total_gross' => (float) $report->total_gross,
                'created_by' => optional($report->user)->name,
                'updated_at' => optional($report->updated_at)->format('d/m/Y H:i'),
                'links' => [
                    'xlsx' => route('tpps.ebupot.download', ['report' => $report->id, 'format' => 'xlsx']),
                    'xml' => route('tpps.ebupot.download', ['report' => $report->id, 'format' => 'xml']),
                ],
            ];
        })->values()->all();

        $paginatorArray = $reportsPaginator->toArray();
        $pagination = [
            'from' => $reportsPaginator->firstItem(),
            'links' => collect($paginatorArray['links'] ?? [])->map(function ($link) {
                return [
                    'url' => $link['url'],
                    'label' => $link['label'],
                    'active' => $link['active'],
                ];
            })->all(),
        ];
    }

    $props = [
        'typeOptions' => $typeOptions,
        'filters' => [
            'type' => $filters['type'] ?? '',
            'year' => $filters['tahun'] ?? '',
            'month' => $filters['bulan'] ?? '',
        ],
        'monthOptions' => $monthOptionsArray,
        'yearBounds' => [
            'min' => 2000,
            'max' => date('Y') + 5,
        ],
        'routes' => [
            'index' => route('tpps.ebupot.index'),
            'create' => route('tpps.ebupot.create', array_filter($filters ?? [])),
        ],
        'items' => $items,
        'pagination' => $pagination,
        'showSkpd' => $isSuperAdmin,
        'texts' => [
            'title' => 'Arsip E-Bupot TPP',
            'createButton' => 'Buat E-Bupot',
            'emptyMessage' => 'Belum ada arsip e-Bupot TPP untuk filter yang dipilih.',
        ],
    ];
@endphp

<div
    id="tpp-ebupot-index-root"
    data-props='@json($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'
></div>

<noscript>
    <div class="alert alert-warning mt-3">
        Halaman ini memerlukan JavaScript agar dapat digunakan sepenuhnya. Silakan aktifkan JavaScript pada peramban Anda.
    </div>
</noscript>
@endsection

