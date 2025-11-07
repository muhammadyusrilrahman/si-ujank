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
            :readonly="field.isComputed"
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
            Jumlah Gaji dan Tunjangan
          </div>
          <div class="h5 mb-0">
            {{ formattedTotals.allowance }}
          </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
          <div class="font-weight-bold text-muted text-uppercase small">
            Jumlah Potongan
          </div>
          <div class="h5 mb-0">
            {{ formattedTotals.deduction }}
          </div>
        </div>
        <div class="col-md-4">
          <div class="font-weight-bold text-muted text-uppercase small">
            Jumlah Ditransfer
          </div>
          <div class="h5 mb-0">
            {{ formattedTotals.transfer }}
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
  computedMap: {
    type: Object,
    default: () => ({}),
  },
  totalAllowanceFields: {
    type: Array,
    default: () => [],
  },
  totalDeductionFields: {
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
  isComputed: Boolean(field.isComputed),
  computedSources: Array.isArray(field.computedSources)
    ? field.computedSources
    : [],
}));

const fieldLookup = monetaryFieldList.reduce((accumulator, field) => {
  accumulator[field.key] = field;
  return accumulator;
}, {});

const numericValues = reactive({});
const displayValues = reactive({});
const valuePresence = reactive({});

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

monetaryFieldList.forEach((field) => {
  const numeric = parseNumericString(field.value);
  numericValues[field.key] = numeric ?? 0;
  valuePresence[field.key] = field.isComputed
    ? true
    : field.value !== null && field.value !== undefined && field.value !== '';
});

const recomputeComputedFields = () => {
  Object.entries(props.computedMap || {}).forEach(([target, sources]) => {
    const total = (sources || []).reduce((sum, sourceKey) => {
      const value = numericValues[sourceKey];
      return sum + (Number.isFinite(value) ? value : 0);
    }, 0);

    numericValues[target] = Number.isFinite(total) ? total : 0;
    valuePresence[target] = true;
    displayValues[target] = formatNumber(numericValues[target]);
  });
};

recomputeComputedFields();

monetaryFieldList.forEach((field) => {
  if (field.isComputed) {
    displayValues[field.key] = formatNumber(numericValues[field.key]);
  } else {
    displayValues[field.key] = valuePresence[field.key]
      ? formatNumber(numericValues[field.key])
      : '';
  }
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
  const field = fieldLookup[key];
  if (!field) {
    return '';
  }

  const numeric = numericValues[key];
  if (field.isComputed) {
    return Number.isFinite(numeric) ? numeric.toFixed(2) : '0.00';
  }

  return valuePresence[key] && Number.isFinite(numeric) ? numeric.toFixed(2) : '';
};

const onFocus = (key, event) => {
  const field = fieldLookup[key];
  if (!field || field.isComputed) {
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
  if (!field || field.isComputed) {
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

  recomputeComputedFields();
};

const onBlur = (key, event) => {
  const field = fieldLookup[key];
  if (!field) {
    return;
  }

  if (field.isComputed) {
    const formatted = formatNumber(numericValues[key]);
    displayValues[key] = formatted;
    event.target.value = formatted;
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
  const allowance = (props.totalAllowanceFields || []).reduce((sum, key) => {
    const value = numericValues[key];
    return sum + (Number.isFinite(value) ? value : 0);
  }, 0);

  const deduction = (props.totalDeductionFields || []).reduce((sum, key) => {
    const value = numericValues[key];
    return sum + (Number.isFinite(value) ? value : 0);
  }, 0);

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

const fieldError = (name) => {
  const messages = props.errors?.[name];
  if (!messages || !Array.isArray(messages) || messages.length === 0) {
    return null;
  }

  const first = messages[0];
  return first === null || first === undefined ? null : String(first);
};
</script>
