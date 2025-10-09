@csrf
<input type="hidden" name="type" value="{{ $selectedType }}">
@php
    $tpp = $tpp ?? null;
    $pegawaiOptions = $pegawaiOptions ?? collect();
    $monthOptions = $monthOptions ?? [];
    $typeLabels = $typeLabels ?? [];
    $monetaryFields = $monetaryFields ?? [];
    $selectedPegawai = old('pegawai_id', optional($tpp)->pegawai_id);
    $defaultYear = $defaultYear ?? null;
    $defaultMonth = $defaultMonth ?? null;
    $selectedYear = old('tahun', optional($tpp)->tahun ?? $defaultYear);
    $selectedMonth = old('bulan', optional($tpp)->bulan ?? $defaultMonth);
    $monetaryMeta = config('tpp.monetary_fields', []);
    $monetaryCategories = [];
    foreach ($monetaryMeta as $field => $meta) {
        $monetaryCategories[$field] = $meta['category'] ?? 'allowance';
    }
    $initialTotals = ['allowance' => 0.0, 'deduction' => 0.0];
    foreach ($monetaryFields as $field => $label) {
        $rawValue = old($field, optional($tpp)->$field);
        $numericValue = is_numeric($rawValue) ? (float) $rawValue : 0.0;
        if (($monetaryCategories[$field] ?? 'allowance') === 'deduction') {
            $initialTotals['deduction'] += $numericValue;
        } else {
            $initialTotals['allowance'] += $numericValue;
        }
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
            $rawValue = old($field, optional($tpp)->$field);
            $displayValue = ($rawValue === null || $rawValue === '') ? '' : \App\Support\MoneyFormatter::rupiah($rawValue, 2, false);
        @endphp
        <div class="form-group col-md-4">
            <label for="{{ $field }}">{{ $label }}</label>
            <input
                type="hidden"
                name="{{ $field }}"
                id="{{ $field }}"
                class="tpp-monetary-input"
                value="{{ $rawValue === null || $rawValue === '' ? '' : $rawValue }}"
                data-category="{{ $monetaryCategories[$field] ?? 'allowance' }}">
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
                    placeholder="0,00">
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
            <div class="font-weight-bold text-muted text-uppercase small">Total TPP</div>
            <div class="h5 mb-0" id="tpp-total-allowance">{{ \App\Support\MoneyFormatter::rupiah($initialTotals['allowance']) }}</div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="font-weight-bold text-muted text-uppercase small">Total Potongan</div>
            <div class="h5 mb-0" id="tpp-total-deduction">{{ \App\Support\MoneyFormatter::rupiah($initialTotals['deduction']) }}</div>
        </div>
        <div class="col-md-4">
            <div class="font-weight-bold text-muted text-uppercase small">Total Ditransfer</div>
            <div class="h5 mb-0" id="tpp-total-transfer">{{ \App\Support\MoneyFormatter::rupiah($initialTotals['transfer']) }}</div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const inputs = Array.from(document.querySelectorAll('.tpp-monetary-input'));
        if (!inputs.length) {
            return;
        }

        const allowanceEl = document.getElementById('tpp-total-allowance');
        const deductionEl = document.getElementById('tpp-total-deduction');
        const transferEl = document.getElementById('tpp-total-transfer');
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

        const updateTotals = () => {
            let allowance = 0;
            let deduction = 0;

            inputs.forEach((input) => {
                const category = input.dataset.category || 'allowance';
                const value = Number.parseFloat(input.value);
                const amount = Number.isFinite(value) ? value : 0;

                if (category === 'deduction') {
                    deduction += amount;
                } else {
                    allowance += amount;
                }
            });

            const transfer = allowance - deduction;

            if (allowanceEl) allowanceEl.textContent = formatRupiah(allowance);
            if (deductionEl) deductionEl.textContent = formatRupiah(deduction);
            if (transferEl) transferEl.textContent = formatRupiah(transfer);
        };

        const currencyInputs = Array.from(document.querySelectorAll('.currency-input'));
        currencyInputs.forEach((displayInput) => {
            const target = displayInput.dataset.target;
            const hiddenInput = target ? document.getElementById(target) : null;
            if (!hiddenInput) {
                return;
            }

            const syncHidden = (numericValue) => {
                if (numericValue === null) {
                    hiddenInput.value = '';
                } else {
                    hiddenInput.value = numericValue.toFixed(2);
                }
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

            // Initialize display with formatted value and ensure hidden value matches.
            const initialNumeric = parseCurrency(displayInput.value);
            displayInput.value = initialNumeric === null ? '' : formatNumber(initialNumeric);
            syncHidden(initialNumeric);
        });

        updateTotals();
    });
</script>
@endpush

