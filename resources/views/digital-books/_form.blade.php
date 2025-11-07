@csrf
@php
    $digitalBook = $digitalBook ?? null;
    $isActiveValue = old('is_active', optional($digitalBook)->is_active ?? true);
    if (is_string($isActiveValue)) {
        $isActiveValue = $isActiveValue === '1';
    }

    $props = [
        'fields' => [
            'title' => old('title', optional($digitalBook)->title),
            'file_url' => old('file_url', optional($digitalBook)->file_url),
            'description' => old('description', optional($digitalBook)->description),
            'is_active' => $isActiveValue ? 1 : 0,
        ],
        'errors' => $errors->toArray(),
    ];
@endphp

<div
    id="digital-book-form-root"
    data-props='@json($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'
></div>

<noscript>
    <div class="alert alert-warning mt-3">
        Form ini memerlukan JavaScript agar dapat digunakan. Silakan aktifkan JavaScript pada peramban Anda.
    </div>
</noscript>

