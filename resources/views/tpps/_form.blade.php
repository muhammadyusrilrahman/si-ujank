@csrf
<input type="hidden" name="type" value="{{ $selectedType }}">
@php
    $tpp = $tpp ?? null;
    $pegawaiOptions = $pegawaiOptions ?? collect();
    $monthOptions = $monthOptions ?? [];
    $typeLabels = $typeLabels ?? [];
    $monetaryFields = $monetaryFields ?? [];
    $totalAllowanceFields = $totalAllowanceFields ?? config('tpp.total_allowance_fields', []);
    $totalDeductionFields = $totalDeductionFields ?? config('tpp.total_deduction_fields', []);

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

    $selectedPegawai = old('pegawai_id', optional($tpp)->pegawai_id);
    $selectedYear = old('tahun', optional($tpp)->tahun ?? $defaultYear ?? null);
    $selectedMonth = old('bulan', optional($tpp)->bulan ?? $defaultMonth ?? null);

    $formatNumeric = static function ($value) {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_numeric($value)) {
            return number_format((float) $value, 2, '.', '');
        }

        return (string) $value;
    };

    $monetaryMeta = config('tpp.monetary_fields', []);

    $monetaryFieldProps = collect($monetaryFields)->map(function ($label, $field) use ($monetaryMeta, $formatNumeric, $tpp) {
        $raw = old($field, optional($tpp)->$field);

        return [
            'key' => $field,
            'label' => $label,
            'category' => $monetaryMeta[$field]['category'] ?? 'allowance',
            'value' => $formatNumeric($raw),
        ];
    })->values();

    $formulaGroupsConfig = config('tpp.formula_groups', []);
    $formulaGroups = collect($formulaGroupsConfig)->map(function ($group, $index) use ($monetaryFields) {
        $fields = collect($group['fields'] ?? [])
            ->filter(fn ($field) => $field !== null && $field !== '')
            ->map(fn ($field) => (string) $field)
            ->values();

        if ($fields->isEmpty()) {
            return null;
        }

        $fieldLabels = $fields->map(function ($field) use ($monetaryFields) {
            return $monetaryFields[$field]
                ?? (string) \Illuminate\Support\Str::of($field)->replace('_', ' ')->title();
        });

        $impact = $group['impact'] ?? 'allowance';
        if (! in_array($impact, ['allowance', 'deduction', 'neutral'], true)) {
            $impact = 'allowance';
        }

        return [
            'key' => (string) ($group['key'] ?? 'group_' . $index),
            'label' => (string) ($group['label'] ?? 'Rumus #' . ($index + 1)),
            'impact' => $impact,
            'fields' => $fields->all(),
            'formula' => $group['formula'] ?? $fieldLabels->implode(' + '),
            'note' => $group['note'] ?? null,
        ];
    })->filter()->values()->all();

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
        'totalAllowanceFields' => array_values($totalAllowanceFields),
        'totalDeductionFields' => array_values($totalDeductionFields),
        'formulaGroups' => $formulaGroups,
        'yearBounds' => $yearBounds,
        'errors' => $errors->toArray(),
    ];
@endphp

<div
    id="tpp-form-root"
    data-props='@json($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'
></div>

<noscript>
    <div class="alert alert-warning mt-3">
        Form TPP membutuhkan JavaScript agar dapat digunakan. Silakan aktifkan JavaScript pada peramban Anda.
    </div>
</noscript>

