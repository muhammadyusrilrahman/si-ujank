@extends('tpps.layout')

@section('title', 'Detail Data TPP')

@section('card-body')
@php
    $allowanceFields = $allowanceFields ?? [];
    $deductionFields = $deductionFields ?? [];

    $totalAllowance = 0.0;
    foreach ($allowanceFields as $field => $label) {
        $totalAllowance += (float) ($tpp->{$field} ?? 0);
    }

    $totalDeduction = 0.0;
    foreach ($deductionFields as $field => $label) {
        $totalDeduction += (float) ($tpp->{$field} ?? 0);
    }

    $monthOptions = $monthOptions ?? config('tpp.months', config('gaji.months', []));

    $showProps = ($showProps ?? []) + [
        'pegawai' => [
            'name' => optional($tpp->pegawai)->nama_lengkap ?? '-',
            'nip' => optional($tpp->pegawai)->nip ?? '-',
            'skpd' => optional(optional($tpp->pegawai)->skpd)->name ?? '-',
        ],
        'period' => [
            'label' => sprintf('%s %s', $monthOptions[$tpp->bulan] ?? $tpp->bulan, $tpp->tahun),
        ],
        'amounts' => [
            'total_allowance' => (float) ($showProps['amounts']['total_allowance'] ?? $totalAllowance),
            'total_deduction' => (float) ($showProps['amounts']['total_deduction'] ?? $totalDeduction),
            'total_transfer' => (float) ($showProps['amounts']['total_transfer'] ?? ($totalAllowance - $totalDeduction)),
        ],
        'routes' => [
            'index' => route('tpps.index', ['type' => $tpp->jenis_asn]),
            'edit' => route('tpps.edit', ['tpp' => $tpp, 'type' => $tpp->jenis_asn]),
            'destroy' => route('tpps.destroy', ['tpp' => $tpp, 'type' => $tpp->jenis_asn]),
        ],
        'texts' => array_merge([
            'totalAllowance' => 'Total Komponen TPP',
            'totalDeduction' => 'Total Potongan',
            'totalTransfer' => 'Jumlah Ditransfer',
        ], $showProps['texts'] ?? []),
        'confirmations' => [
            'delete' => $showProps['confirmations']['delete'] ?? 'Hapus data TPP ini?',
        ],
    ];

    $showProps['csrfToken'] = csrf_token();
@endphp

<div
    id="tpp-show-root"
    data-props='@json($showProps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'
></div>

<noscript>
    <div class="alert alert-warning mt-3">
        Halaman ini memerlukan JavaScript agar dapat digunakan sepenuhnya. Silakan aktifkan JavaScript pada peramban Anda.
    </div>
</noscript>
@endsection

