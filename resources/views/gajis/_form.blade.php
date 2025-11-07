@csrf
<input type="hidden" name="type" value="{{ $selectedType }}">
@php
    $gaji = $gaji ?? null;
    $pegawaiOptions = $pegawaiOptions ?? collect();
    $monthOptions = $monthOptions ?? [];
    $typeLabels = $typeLabels ?? [];
    $monetaryFields = $monetaryFields ?? [];
    $totalAllowanceFields = $totalAllowanceFields ?? config('gaji.total_allowance_fields', []);
    $totalDeductionFields = $totalDeductionFields ?? config('gaji.total_deduction_fields', []);

    $typeLabel = $typeLabels[$selectedType] ?? strtoupper($selectedType);

    $monthOptionsData = collect($monthOptions)->map(function ($label, $value) {
        return [
            'value' => (string) $value,
            'label' => $label,
        ];
    })->values();

    $pegawaiOptionsData = $pegawaiOptions->map(function ($pegawai) {
        return [
            'id' => (string) $pegawai->id,
            'nama_lengkap' => $pegawai->nama_lengkap,
            'nip' => $pegawai->nip ? (string) $pegawai->nip : null,
        ];
    })->values();

    $selectedPegawai = old('pegawai_id', optional($gaji)->pegawai_id);
    $selectedYear = old('tahun', optional($gaji)->tahun ?? $defaultYear ?? null);
    $selectedMonth = old('bulan', optional($gaji)->bulan ?? $defaultMonth ?? null);

    $formatNumeric = static function ($value) {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_numeric($value)) {
            return number_format((float) $value, 2, '.', '');
        }

        return (string) $value;
    };

    $monetaryMeta = config('gaji.monetary_fields', []);
    $computedFieldSources = [
        'tunjangan_keluarga' => ['perhitungan_suami_istri', 'perhitungan_anak'],
    ];

    $monetaryFieldProps = collect($monetaryFields)->map(function ($label, $field) use ($monetaryMeta, $computedFieldSources, $formatNumeric, $gaji) {
        $raw = old($field, optional($gaji)->$field);

        return [
            'key' => $field,
            'label' => $label,
            'category' => $monetaryMeta[$field]['category'] ?? 'allowance',
            'value' => $formatNumeric($raw),
            'isComputed' => array_key_exists($field, $computedFieldSources),
            'computedSources' => $computedFieldSources[$field] ?? [],
        ];
    })->values();

    $yearBounds = [
        'min' => 2000,
        'max' => (int) date('Y') + 5,
    ];

    $props = [
        'type' => [
            'key' => $selectedType,
            'label' => $typeLabel,
        ],
        'initial' => [
            'year' => $selectedYear !== null ? (string) $selectedYear : '',
            'month' => $selectedMonth !== null ? (string) $selectedMonth : '',
            'pegawaiId' => $selectedPegawai !== null ? (string) $selectedPegawai : '',
        ],
        'monthOptions' => $monthOptionsData,
        'pegawaiOptions' => $pegawaiOptionsData,
        'monetaryFields' => $monetaryFieldProps,
        'computedMap' => $computedFieldSources,
        'totalAllowanceFields' => array_values($totalAllowanceFields),
        'totalDeductionFields' => array_values($totalDeductionFields),
        'yearBounds' => $yearBounds,
        'errors' => $errors->toArray(),
    ];
@endphp

<div
    id="gaji-form-root"
    data-props='@json($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'
></div>

<noscript>
    <div class="alert alert-warning mt-3">
        Form gaji membutuhkan JavaScript agar dapat digunakan. Silakan aktifkan JavaScript pada peramban Anda.
    </div>
</noscript>

