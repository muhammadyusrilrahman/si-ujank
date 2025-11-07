@csrf
@php
    $skpd = $skpd ?? null;
    $statusMessage = $statusMessage ?? session('status');

    $props = [
        'old' => [
            'name' => old('name', optional($skpd)->name),
            'alias' => old('alias', optional($skpd)->alias),
            'npwp' => old('npwp', optional($skpd)->npwp),
        ],
        'errors' => $errors->toArray(),
        'status' => $statusMessage,
    ];
@endphp

<div
    id="skpd-form-root"
    data-props='@json($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'
></div>

<noscript>
    <div class="alert alert-warning mt-3">
        Form membutuhkan JavaScript agar dapat digunakan. Silakan aktifkan JavaScript pada peramban Anda.
    </div>
</noscript>

