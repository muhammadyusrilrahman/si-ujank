@extends('gajis.layout')

@section('title', 'Detail Data Gaji')

@section('card-body')
@php
    $props = $showProps ?? [
        'pegawai' => [
            'name' => optional($gaji->pegawai)->nama_lengkap ?? optional($gaji->pegawai)->nama ?? '-',
            'nip' => optional($gaji->pegawai)->nip ?? '-',
            'skpd' => optional(optional($gaji->pegawai)->skpd)->name ?? '-',
        ],
        'period' => [
            'label' => sprintf('%s %s', \App\Support\MonthName::id($gaji->bulan), $gaji->tahun),
        ],
        'amounts' => [
            'gaji_pokok' => (float) ($gaji->gaji_pokok ?? 0),
            'tunjangan' => (float) ($gaji->tunjangan ?? 0),
            'potongan' => (float) ($gaji->potongan ?? 0),
            'total' => (float) (($gaji->gaji_pokok ?? 0) + ($gaji->tunjangan ?? 0) - ($gaji->potongan ?? 0)),
        ],
        'routes' => [
            'index' => route('gajis.index', ['type' => $gaji->jenis_asn]),
            'edit' => route('gajis.edit', ['gaji' => $gaji, 'type' => $gaji->jenis_asn]),
            'destroy' => route('gajis.destroy', ['gaji' => $gaji, 'type' => $gaji->jenis_asn]),
        ],
        'confirmations' => [
            'delete' => 'Apakah Anda yakin ingin menghapus data ini?',
        ],
    ];

    $props['csrfToken'] = csrf_token();
@endphp

<div
    id="gaji-show-root"
    data-props='@json($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'
></div>

<noscript>
    <div class="alert alert-warning mt-3">
        Halaman ini memerlukan JavaScript agar dapat digunakan sepenuhnya. Silakan aktifkan JavaScript pada peramban Anda.
    </div>
</noscript>
@endsection

