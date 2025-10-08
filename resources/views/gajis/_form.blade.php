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
    $initialTotals = ['allowance' => 0.0, 'deduction' => 0.0];
    foreach ($monetaryFields as $field => $label) {
        $rawValue = old($field, optional($gaji)->$field);
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
        <div class="form-group col-md-4">
            <label for="{{ $field }}">{{ $label }}</label>
            <input
                type="number"
                step="0.01"
                min="0"
                name="{{ $field }}"
                id="{{ $field }}"
                class="form-control @error($field) is-invalid @enderror gaji-monetary-input"
                value="{{ old($field, optional($gaji)->$field) }}"
                inputmode="decimal"
                data-category="{{ $monetaryCategories[$field] ?? 'allowance' }}">
            @error($field)
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    @endforeach
</div>
<div class="bg-light rounded border p-3 mb-3">
    <div class="row text-center text-md-left">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="font-weight-bold text-muted text-uppercase small">Total Gaji & Tunjangan</div>
            <div class="h5 mb-0" id="gaji-total-allowance">{{ number_format($initialTotals['allowance'], 2, ',', '.') }}</div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="font-weight-bold text-muted text-uppercase small">Total Potongan</div>
            <div class="h5 mb-0" id="gaji-total-deduction">{{ number_format($initialTotals['deduction'], 2, ',', '.') }}</div>
        </div>
        <div class="col-md-4">
            <div class="font-weight-bold text-muted text-uppercase small">Total Ditransfer</div>
            <div class="h5 mb-0" id="gaji-total-transfer">{{ number_format($initialTotals['transfer'], 2, ',', '.') }}</div>
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

        const allowanceEl = document.getElementById('gaji-total-allowance');
        const deductionEl = document.getElementById('gaji-total-deduction');
        const transferEl = document.getElementById('gaji-total-transfer');
        const formatter = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

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

            if (allowanceEl) allowanceEl.textContent = formatter.format(allowance);
            if (deductionEl) deductionEl.textContent = formatter.format(deduction);
            if (transferEl) transferEl.textContent = formatter.format(transfer);
        };

        inputs.forEach((input) => {
            input.addEventListener('input', updateTotals);
            input.addEventListener('change', updateTotals);
        });

        updateTotals();
    });
</script>
@endpush
