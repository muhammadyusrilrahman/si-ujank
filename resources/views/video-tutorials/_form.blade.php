@csrf
@php
    $videoTutorial = $videoTutorial ?? null;
    $isActiveValue = old('is_active', optional($videoTutorial)->is_active ?? true);
    if (is_string($isActiveValue)) {
        $isActiveValue = $isActiveValue === '1';
    }

    $props = [
        'fields' => [
            'title' => old('title', optional($videoTutorial)->title),
            'video_url' => old('video_url', optional($videoTutorial)->video_url),
            'description' => old('description', optional($videoTutorial)->description),
            'is_active' => $isActiveValue ? 1 : 0,
        ],
        'errors' => $errors->toArray(),
    ];
@endphp

<div
    id="video-tutorial-form-root"
    data-props='@json($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'
></div>

<noscript>
    <div class="alert alert-warning mt-3">
        Form ini memerlukan JavaScript agar dapat digunakan. Silakan aktifkan JavaScript pada peramban Anda.
    </div>
</noscript>

