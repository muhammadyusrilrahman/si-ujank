@csrf
<input type="hidden" name="type" value="{{ $selectedType }}">
@php
    $gaji = $gaji ?? null;
    $pegawaiOptions = $pegawaiOptions ?? collect();
    $monthOptions = $monthOptions ?? [];
    $typeLabels = $typeLabels ?? [];
    $monetaryFields = $monetaryFields ?? [];
    $selectedPegawai = old('pegawai_id', optional($gaji)->pegawai_id);
    $defaultYear = $defaultYear ?? null;
    $defaultMonth = $defaultMonth ?? null;
    $selectedYear = old('tahun', optional($gaji)->tahun ?? $defaultYear);
    $selectedMonth = old('bulan', optional($gaji)->bulan ?? $defaultMonth);
    $monetaryMeta = config('gaji.monetary_fields', []);
    $monetaryCategories = [];
    foreach ($monetaryMeta as $field => $meta) {
        $monetaryCategories[$field] = $meta['category'] ?? 'allowance';
    }
    $computedFieldSources = [
        'tunjangan_keluarga' => ['perhitungan_suami_istri', 'perhitungan_anak'],
    ];

    $totalGajiFields = [
        'gaji_pokok',
        'perhitungan_suami_istri',
        'perhitungan_anak',
        'tunjangan_keluarga',
        'tunjangan_jabatan',
        'tunjangan_fungsional',
        'tunjangan_fungsional_umum',
        'tunjangan_beras',
        'tunjangan_pph',
        'pembulatan_gaji',
        'iuran_jaminan_kesehatan',
        'iuran_jaminan_kecelakaan_kerja',
        'iuran_jaminan_kematian',
        'iuran_simpanan_tapera',
        'iuran_pensiun',
        'tunjangan_khusus_papua',
        'tunjangan_jaminan_hari_tua',
    ];

    $totalPotonganFields = [
        'iuran_jaminan_kesehatan',
        'iuran_jaminan_kecelakaan_kerja',
        'iuran_jaminan_kematian',
        'iuran_simpanan_tapera',
        'iuran_pensiun',
        'potongan_iwp',
        'potongan_pph_21',
        'zakat',
        'bulog',
    ];

    $rawMonetaryInputs = [];
    $monetaryValues = [];
    foreach ($monetaryFields as $field => $label) {
        $rawValue = old($field, optional($gaji)->$field);
        $rawMonetaryInputs[$field] = $rawValue;
        $monetaryValues[$field] = is_numeric($rawValue) ? (float) $rawValue : 0.0;
    }

    foreach ($computedFieldSources as $targetField => $sourceFields) {
        $computedTotal = 0.0;
        foreach ($sourceFields as $sourceField) {
            $computedTotal += $monetaryValues[$sourceField] ?? 0.0;
        }
        $monetaryValues[$targetField] = $computedTotal;
        $rawMonetaryInputs[$targetField] = $computedTotal;
    }

    $initialTotals = ['allowance' => 0.0, 'deduction' => 0.0];
    foreach ($totalGajiFields as $field) {
        $initialTotals['allowance'] += $monetaryValues[$field] ?? 0.0;
    }
    foreach ($totalPotonganFields as $field) {
        $initialTotals['deduction'] += $monetaryValues[$field] ?? 0.0;
    }
    $initialTotals['transfer'] = $initialTotals['allowance'] - $initialTotals['deduction'];
@endphp
<div class="form-row">
    <div class="form-group col-md-4">
        <label>Jenis ASN</label>
        <input type="text" class="form-control" value="{{ $typeLabels[$selectedType] ?? strtoupper($selectedType) }}" readonly>
    </div>
    <div class="form-group col-md-4">
        <label for="tahun">Tahun</label>
        <input type="number" name="tahun" id="tahun" class="form-control @error('tahun') is-invalid @enderror" value="{{ $selectedYear ?? '' }}" min="2000" max="{{ (int) date('Y') + 5 }}" required>
        @error('tahun')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-4">
        <label for="bulan">Bulan</label>
        <select name="bulan" id="bulan" class="form-control @error('bulan') is-invalid @enderror" required>
            <option value="" disabled {{ $selectedMonth === null ? 'selected' : '' }}>Pilih bulan</option>
            @foreach ($monthOptions as $value => $label)
                <option value="{{ $value }}" {{ $selectedMonth !== null && (int) $selectedMonth === (int) $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('bulan')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
<div class="form-group">
    <label for="pegawai_id">Pegawai</label>
    <select name="pegawai_id" id="pegawai_id" class="form-control @error('pegawai_id') is-invalid @enderror" required>
        <option value="" disabled {{ $selectedPegawai ? '' : 'selected' }}>Pilih pegawai</option>
        @foreach ($pegawaiOptions as $pegawai)
            <option value="{{ $pegawai->id }}" {{ (int) $selectedPegawai === (int) $pegawai->id ? 'selected' : '' }}>
                {{ $pegawai->nama_lengkap }} @if ($pegawai->nip) - {{ $pegawai->nip }} @endif
            </option>
        @endforeach
    </select>
    @error('pegawai_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<hr>
<div class="form-row">
    @foreach ($monetaryFields as $field => $label)
        @php
            $rawValue = $rawMonetaryInputs[$field] ?? null;
            if (array_key_exists($field, $computedFieldSources)) {
                $rawValue = $monetaryValues[$field];
            }
            $numericRaw = is_numeric($rawValue) ? (float) $rawValue : null;
            $displayValue = $numericRaw === null ? '' : \App\Support\MoneyFormatter::rupiah($numericRaw, 2, false);
        @endphp
        <div class="form-group col-md-4">
            <label for="{{ $field }}">{{ $label }}</label>
            <input
                type="hidden"
                name="{{ $field }}"
                id="{{ $field }}"
                class="gaji-monetary-input"
                value="{{ $numericRaw === null ? '' : number_format($numericRaw, 2, '.', '') }}"
                data-category="{{ $monetaryCategories[$field] ?? 'allowance' }}"
                @if (array_key_exists($field, $computedFieldSources)) data-computed="true" @endif>
            <div class="input-group currency-input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Rp</span>
                </div>
                <input
                    type="text"
                    inputmode="decimal"
                    autocomplete="off"
                    class="form-control currency-input {{ $errors->has($field) ? 'is-invalid' : '' }}"
                    data-target="{{ $field }}"
                    value="{{ $displayValue }}"
                    placeholder="0,00"
                    @if (array_key_exists($field, $computedFieldSources)) readonly data-computed="true" @endif>
            </div>
            @error($field)
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    @endforeach
</div>
<div class="bg-light rounded border p-3 mb-3">
    <div class="row text-center text-md-left">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="font-weight-bold text-muted text-uppercase small">Total Gaji & Tunjangan</div>
            <div class="h5 mb-0" id="gaji-total-allowance">{{ \App\Support\MoneyFormatter::rupiah($initialTotals['allowance']) }}</div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="font-weight-bold text-muted text-uppercase small">Total Potongan</div>
            <div class="h5 mb-0" id="gaji-total-deduction">{{ \App\Support\MoneyFormatter::rupiah($initialTotals['deduction']) }}</div>
        </div>
        <div class="col-md-4">
            <div class="font-weight-bold text-muted text-uppercase small">Total Ditransfer</div>
            <div class="h5 mb-0" id="gaji-total-transfer">{{ \App\Support\MoneyFormatter::rupiah($initialTotals['transfer']) }}</div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const inputs = Array.from(document.querySelectorAll('.gaji-monetary-input'));
        if (!inputs.length) {
            return;
        }

        const computedFieldSources = @json($computedFieldSources);
        const totalGajiFields = @json($totalGajiFields);
        const totalPotonganFields = @json($totalPotonganFields);

        const totalGajiEl = document.getElementById('gaji-total-allowance');
        const totalPotonganEl = document.getElementById('gaji-total-deduction');
        const transferEl = document.getElementById('gaji-total-transfer');
        const formatter = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const formatRupiah = (value) => `Rp ${formatter.format(value)}`;
        const formatNumber = (value) => formatter.format(value);

        const parseCurrency = (value) => {
            if (typeof value !== 'string') {
                return null;
            }
            const normalized = value.replace(/\./g, '').replace(',', '.');
            const parsed = Number.parseFloat(normalized);
            return Number.isFinite(parsed) ? parsed : null;
        };

        const sanitizeCurrencyInput = (value) => {
            const cleaned = value.replace(/[^\d,]/g, '');
            const [integerPart, decimalPart] = cleaned.split(',', 2);
            if (decimalPart !== undefined) {
                return `${integerPart},${decimalPart.slice(0, 2)}`;
            }
            return integerPart;
        };

        const hiddenInputs = new Map();
        inputs.forEach((input) => {
            hiddenInputs.set(input.id, input);
        });

        const displayInputs = new Map();

        const getHiddenNumeric = (field) => {
            const hidden = hiddenInputs.get(field);
            if (!hidden) {
                return 0;
            }
            const value = Number.parseFloat(hidden.value);
            return Number.isFinite(value) ? value : 0;
        };

        const setHiddenNumeric = (field, numericValue) => {
            const hidden = hiddenInputs.get(field);
            if (!hidden) {
                return;
            }
            if (numericValue === null || !Number.isFinite(numericValue)) {
                hidden.value = '';
            } else {
                hidden.value = numericValue.toFixed(2);
            }
        };

        const updateComputedFields = () => {
            Object.keys(computedFieldSources).forEach((targetField) => {
                const sources = computedFieldSources[targetField] || [];
                const total = sources.reduce((accumulator, sourceField) => accumulator + getHiddenNumeric(sourceField), 0);
                setHiddenNumeric(targetField, total);
                const display = displayInputs.get(targetField);
                if (display) {
                    display.value = formatNumber(total);
                }
            });
        };

        const sumFields = (fields) => fields.reduce((total, field) => total + getHiddenNumeric(field), 0);

        const updateTotals = () => {
            updateComputedFields();
            const totalGaji = sumFields(totalGajiFields);
            const totalPotongan = sumFields(totalPotonganFields);
            const transfer = totalGaji - totalPotongan;

            if (totalGajiEl) totalGajiEl.textContent = formatRupiah(totalGaji);
            if (totalPotonganEl) totalPotonganEl.textContent = formatRupiah(totalPotongan);
            if (transferEl) transferEl.textContent = formatRupiah(transfer);
        };

        const currencyInputs = Array.from(document.querySelectorAll('.currency-input'));
        currencyInputs.forEach((displayInput) => {
            const target = displayInput.dataset.target;
            if (!target) {
                return;
            }

            displayInputs.set(target, displayInput);

            const hiddenInput = hiddenInputs.get(target);
            if (!hiddenInput) {
                return;
            }

            if (Object.prototype.hasOwnProperty.call(computedFieldSources, target)) {
                return;
            }

            const syncHidden = (numericValue) => {
                setHiddenNumeric(target, numericValue === null ? null : numericValue);
                updateTotals();
            };

            displayInput.addEventListener('focus', () => {
                const numericValue = parseCurrency(displayInput.value);
                displayInput.value = numericValue === null ? '' : numericValue.toString().replace('.', ',');
                displayInput.select();
            });

            displayInput.addEventListener('input', () => {
                const sanitized = sanitizeCurrencyInput(displayInput.value);
                if (sanitized !== displayInput.value) {
                    displayInput.value = sanitized;
                }
                const numericValue = parseCurrency(displayInput.value);
                syncHidden(numericValue);
            });

            displayInput.addEventListener('blur', () => {
                const numericValue = parseCurrency(displayInput.value);
                displayInput.value = numericValue === null ? '' : formatNumber(numericValue);
                syncHidden(numericValue);
            });

            const initialNumeric = parseCurrency(displayInput.value);
            displayInput.value = initialNumeric === null ? '' : formatNumber(initialNumeric);
            syncHidden(initialNumeric);
        });

        updateTotals();
    });
</script>
@endpush
