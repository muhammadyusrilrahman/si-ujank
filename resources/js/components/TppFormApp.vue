<template>
  <div>
    <div class="form-row">
      <div class="form-group col-md-4">
        <label>Jenis ASN</label>
        <input type="text" class="form-control" :value="typeLabel" readonly>
      </div>

      <div class="form-group col-md-4">
        <label for="tahun">Tahun</label>
        <input
          id="tahun"
          name="tahun"
          type="number"
          class="form-control"
          :class="fieldError('tahun') ? 'is-invalid' : ''"
          v-model="form.year"
          :min="yearBounds.min"
          :max="yearBounds.max"
          required
        >
        <div v-if="fieldError('tahun')" class="invalid-feedback">
          {{ fieldError('tahun') }}
        </div>
      </div>

      <div class="form-group col-md-4">
        <label for="bulan">Bulan</label>
        <select
          id="bulan"
          name="bulan"
          class="form-control"
          :class="fieldError('bulan') ? 'is-invalid' : ''"
          v-model="form.month"
          required
        >
          <option value="" disabled>Pilih bulan</option>
          <option
            v-for="option in monthOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
        <div v-if="fieldError('bulan')" class="invalid-feedback">
          {{ fieldError('bulan') }}
        </div>
      </div>
    </div>

    <div class="form-group">
      <label for="pegawai_id">Pegawai</label>
      <select
        id="pegawai_id"
        name="pegawai_id"
        class="form-control"
        :class="fieldError('pegawai_id') ? 'is-invalid' : ''"
        v-model="form.pegawaiId"
        required
      >
        <option value="" disabled>Pilih pegawai</option>
        <option
          v-for="pegawai in pegawaiOptions"
          :key="pegawai.id"
          :value="pegawai.id"
        >
          {{ pegawai.nama_lengkap }}<template v-if="pegawai.nip"> - {{ pegawai.nip }}</template>
        </option>
      </select>
      <div v-if="fieldError('pegawai_id')" class="invalid-feedback">
        {{ fieldError('pegawai_id') }}
      </div>
    </div>

    <hr>

    <div class="form-row">
      <div
        v-for="field in monetaryFieldList"
        :key="field.key"
        class="form-group col-md-4"
      >
        <label :for="`${field.key}-display`">
          {{ field.label }}
        </label>

        <input
          type="hidden"
          :name="field.key"
          :value="hiddenValue(field.key)"
        >

        <div class="input-group currency-input-group">
          <div class="input-group-prepend">
            <span class="input-group-text">Rp</span>
          </div>
          <input
            :id="`${field.key}-display`"
            type="text"
            class="form-control currency-input"
            :class="fieldError(field.key) ? 'is-invalid' : ''"
            inputmode="decimal"
            autocomplete="off"
            :placeholder="'0,00'"
            :value="displayValues[field.key]"
            @focus="onFocus(field.key, $event)"
            @input="onInput(field.key, $event)"
            @blur="onBlur(field.key, $event)"
          >
        </div>
        <div v-if="fieldError(field.key)" class="invalid-feedback d-block">
          {{ fieldError(field.key) }}
        </div>
      </div>
    </div>

    <div class="bg-light rounded border p-3 mb-3">
      <div class="row text-center text-md-left">
        <div class="col-md-4 mb-3 mb-md-0">
          <div class="font-weight-bold text-muted text-uppercase small">
            Total TPP
          </div>
          <div class="h5 mb-0">
            {{ formattedTotals.allowance }}
          </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
          <div class="font-weight-bold text-muted text-uppercase small">
            Total Potongan
          </div>
          <div class="h5 mb-0">
            {{ formattedTotals.deduction }}
          </div>
        </div>
        <div class="col-md-4">
          <div class="font-weight-bold text-muted text-uppercase small">
            Total Ditransfer
          </div>
          <div class="h5 mb-0">
            {{ formattedTotals.transfer }}
          </div>
        </div>
      </div>
    </div>

    <div v-if="formulaDetails.length" class="card shadow-sm mt-3">
      <div class="card-header bg-white border-bottom-0">
        <strong>Rincian Rumus Perhitungan</strong>
      </div>
      <div class="list-group list-group-flush">
        <div
          v-for="group in formulaDetails"
          :key="group.key"
          class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-md-center"
        >
          <div class="mb-2 mb-md-0">
            <div class="font-weight-bold">{{ group.label }}</div>
            <div class="text-muted small">
              {{ group.formula }}
            </div>
            <div v-if="group.note" class="text-muted small">
              {{ group.note }}
            </div>
          </div>
          <div class="h6 mb-0" :class="impactClass(group.impact)">
            {{ group.formattedValue }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive } from 'vue';

const props = defineProps({
  type: {
    type: Object,
    default: () => ({ key: '', label: '' }),
  },
  initial: {
    type: Object,
    default: () => ({ year: '', month: '', pegawaiId: '' }),
  },
  monthOptions: {
    type: Array,
    default: () => [],
  },
  pegawaiOptions: {
    type: Array,
    default: () => [],
  },
  monetaryFields: {
    type: Array,
    default: () => [],
  },
  totalAllowanceFields: {
    type: Array,
    default: () => [],
  },
  totalDeductionFields: {
    type: Array,
    default: () => [],
  },
  formulaGroups: {
    type: Array,
    default: () => [],
  },
  yearBounds: {
    type: Object,
    default: () => ({ min: 2000, max: new Date().getFullYear() }),
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
});

const formatter = new Intl.NumberFormat('id-ID', {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});

const typeLabel = computed(() => {
  const label = props.type?.label;
  if (label && label.length) {
    return label;
  }
  return props.type?.key ?? '';
});

const yearBounds = computed(() => ({
  min: Number(props.yearBounds?.min ?? 2000),
  max: Number(props.yearBounds?.max ?? new Date().getFullYear()),
}));

const form = reactive({
  year:
    props.initial?.year === null || props.initial?.year === undefined
      ? ''
      : String(props.initial.year),
  month:
    props.initial?.month === null || props.initial?.month === undefined
      ? ''
      : String(props.initial.month),
  pegawaiId:
    props.initial?.pegawaiId === null || props.initial?.pegawaiId === undefined
      ? ''
      : String(props.initial.pegawaiId),
});

const monetaryFieldList = props.monetaryFields.map((field) => ({
  key: field.key,
  label: field.label,
  category: field.category ?? 'allowance',
  value: field.value ?? '',
}));

const fieldLookup = monetaryFieldList.reduce((accumulator, field) => {
  accumulator[field.key] = field;
  return accumulator;
}, {});

const numericValues = reactive({});
const displayValues = reactive({});
const valuePresence = reactive({});

const normalizeKeyList = (keys) => (Array.isArray(keys) ? keys : []);

const sumByKeys = (keys) =>
  normalizeKeyList(keys).reduce((sum, key) => {
    const value = numericValues[key];
    return sum + (Number.isFinite(value) ? value : 0);
  }, 0);

const parseNumericString = (value) => {
  if (value === null || value === undefined || value === '') {
    return null;
  }
  const parsed = Number.parseFloat(String(value));
  return Number.isFinite(parsed) ? parsed : null;
};

const formatNumber = (value) => {
  if (!Number.isFinite(value)) {
    return '';
  }
  return formatter.format(value);
};

const formatCurrency = (value) => `Rp ${formatNumber(value)}`;

const normalizeImpact = (impact) => {
  if (impact === 'deduction' || impact === 'neutral') {
    return impact;
  }
  return 'allowance';
};

const formatImpactValue = (value, impact) => {
  const base = formatCurrency(value);
  if (impact === 'deduction' && value !== 0) {
    return `- ${base}`;
  }
  if (impact === 'allowance' && value !== 0) {
    return `+ ${base}`;
  }
  return base;
};

const impactClass = (impact) => {
  if (impact === 'deduction') {
    return 'text-danger';
  }
  if (impact === 'neutral') {
    return 'text-muted';
  }
  return 'text-success';
};

monetaryFieldList.forEach((field) => {
  const numeric = parseNumericString(field.value);
  numericValues[field.key] = numeric ?? 0;
  valuePresence[field.key] =
    field.value !== null && field.value !== undefined && field.value !== '';
  displayValues[field.key] = valuePresence[field.key]
    ? formatNumber(numericValues[field.key])
    : '';
});

const sanitizeCurrencyInput = (value) => {
  if (typeof value !== 'string') {
    return '';
  }

  const cleaned = value.replace(/[^\d,]/g, '');
  const [integerPart = '', decimalPart] = cleaned.split(',', 2);

  if (decimalPart !== undefined) {
    return `${integerPart},${decimalPart.slice(0, 2)}`;
  }

  return integerPart;
};

const parseCurrencyInput = (value) => {
  if (typeof value !== 'string' || value.trim() === '') {
    return null;
  }

  const normalized = value.replace(/\./g, '').replace(',', '.');
  const parsed = Number.parseFloat(normalized);

  return Number.isFinite(parsed) ? parsed : null;
};

const hiddenValue = (key) => {
  const value = numericValues[key];
  if (!valuePresence[key] || !Number.isFinite(value)) {
    return '';
  }

  return value.toFixed(2);
};

const onFocus = (key, event) => {
  const field = fieldLookup[key];
  if (!field) {
    return;
  }

  if (!valuePresence[key]) {
    displayValues[key] = '';
    event.target.value = '';
    return;
  }

  const numeric = numericValues[key];
  const plain = Number.isFinite(numeric)
    ? numeric.toFixed(2).replace('.', ',')
    : '';
  displayValues[key] = plain;
  event.target.value = plain;
  event.target.select();
};

const onInput = (key, event) => {
  const field = fieldLookup[key];
  if (!field) {
    event.target.value = displayValues[key] ?? '';
    return;
  }

  const sanitized = sanitizeCurrencyInput(event.target.value ?? '');
  if (sanitized !== event.target.value) {
    event.target.value = sanitized;
  }

  displayValues[key] = sanitized;

  const numeric = parseCurrencyInput(sanitized);
  if (numeric === null) {
    numericValues[key] = 0;
    valuePresence[key] = false;
  } else {
    numericValues[key] = numeric;
    valuePresence[key] = true;
  }
};

const onBlur = (key, event) => {
  const field = fieldLookup[key];
  if (!field) {
    return;
  }

  if (!valuePresence[key]) {
    displayValues[key] = '';
    event.target.value = '';
    return;
  }

  const formatted = formatNumber(numericValues[key]);
  displayValues[key] = formatted;
  event.target.value = formatted;
};

const totals = computed(() => {
  const allowance = sumByKeys(props.totalAllowanceFields);
  const deduction = sumByKeys(props.totalDeductionFields);

  return {
    allowance,
    deduction,
    transfer: allowance - deduction,
  };
});

const formattedTotals = computed(() => ({
  allowance: formatCurrency(totals.value.allowance),
  deduction: formatCurrency(totals.value.deduction),
  transfer: formatCurrency(totals.value.transfer),
}));

const formulaDetails = computed(() =>
  (props.formulaGroups || []).map((group, index) => {
    const impact = normalizeImpact(group.impact);
    const fields = normalizeKeyList(group.fields);
    const amount = sumByKeys(fields);
    const fallbackFormula = fields
      .map((key) => fieldLookup[key]?.label ?? key)
      .join(' + ');

    return {
      key: group.key ?? `formula_${index}`,
      label: group.label ?? `Komponen ${index + 1}`,
      impact,
      note: group.note ?? null,
      formula: group.formula ?? fallbackFormula,
      fields,
      value: amount,
      formattedValue: formatImpactValue(amount, impact),
    };
  })
);

const fieldError = (name) => {
  const messages = props.errors?.[name];
  if (!messages || !Array.isArray(messages) || messages.length === 0) {
    return null;
  }

  const first = messages[0];
  return first === null || first === undefined ? null : String(first);
};
</script>
