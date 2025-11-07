<template>
  <div>
    <div class="row">
      <div class="col-md-3 mb-3">
        <label class="form-label" for="jenis_asn">Jenis ASN</label>
        <template v-if="typeLocked">
          <select class="form-control" id="jenis_asn" disabled>
            <option :value="state.jenisAsn">
              {{ typeLabel }}
            </option>
          </select>
          <input type="hidden" name="jenis_asn" :value="state.jenisAsn">
        </template>
        <template v-else>
          <select
            id="jenis_asn"
            name="jenis_asn"
            class="form-control"
            :class="fieldError('jenis_asn') ? 'is-invalid' : ''"
            v-model="state.jenisAsn"
            required
          >
            <option value="" disabled>Pilih Jenis ASN</option>
            <option
              v-for="option in typeOptions"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </select>
          <div v-if="fieldError('jenis_asn')" class="invalid-feedback">
            {{ fieldError('jenis_asn') }}
          </div>
        </template>
      </div>

      <div class="col-md-3 mb-3">
        <label class="form-label" for="tahun">Tahun</label>
        <input
          id="tahun"
          name="tahun"
          type="number"
          class="form-control"
          :class="fieldError('tahun') ? 'is-invalid' : ''"
          v-model="state.year"
          :min="yearBounds.min"
          :max="yearBounds.max"
          required
        >
        <div v-if="fieldError('tahun')" class="invalid-feedback">
          {{ fieldError('tahun') }}
        </div>
      </div>

      <div class="col-md-3 mb-3">
        <label class="form-label" for="bulan">Bulan</label>
        <select
          id="bulan"
          name="bulan"
          class="form-control"
          :class="fieldError('bulan') ? 'is-invalid' : ''"
          v-model="state.month"
          required
        >
          <option value="" disabled>Pilih Bulan</option>
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

      <div class="col-md-3 mb-3">
        <label class="form-label" for="pegawai_id">Pegawai</label>
        <template v-if="pegawaiReadonly">
          <input
            id="pegawai_id"
            type="text"
            class="form-control"
            :value="pegawaiDisplay"
            readonly
          >
          <input type="hidden" name="pegawai_id" :value="state.pegawaiId">
        </template>
        <template v-else>
          <select
            id="pegawai_id"
            name="pegawai_id"
            class="form-control"
            :class="fieldError('pegawai_id') ? 'is-invalid' : ''"
            v-model="state.pegawaiId"
            required
          >
            <option value="" disabled>Pilih Pegawai</option>
            <option
              v-for="option in pegawaiOptions"
              :key="option.id"
              :value="option.id"
            >
              {{ pegawaiLabel(option) }}
            </option>
          </select>
        </template>
        <div v-if="fieldError('pegawai_id')" class="invalid-feedback">
          {{ fieldError('pegawai_id') }}
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label" for="kelas_jabatan">Kelas Jabatan</label>
        <input
          id="kelas_jabatan"
          name="kelas_jabatan"
          type="text"
          class="form-control"
          :class="fieldError('kelas_jabatan') ? 'is-invalid' : ''"
          v-model="state.kelasJabatan"
        >
        <div v-if="fieldError('kelas_jabatan')" class="invalid-feedback">
          {{ fieldError('kelas_jabatan') }}
        </div>
      </div>

      <div class="col-md-4 mb-3">
        <label class="form-label" for="golongan">Golongan / Ruang</label>
        <input
          id="golongan"
          name="golongan"
          type="text"
          class="form-control"
          :class="fieldError('golongan') ? 'is-invalid' : ''"
          v-model="state.golongan"
        >
        <div v-if="fieldError('golongan')" class="invalid-feedback">
          {{ fieldError('golongan') }}
        </div>
      </div>

      <div class="col-md-4 mb-3">
        <label class="form-label" for="tanda_terima">Nomor Rekening (Tanda Terima)</label>
        <input
          id="tanda_terima"
          name="tanda_terima"
          type="text"
          class="form-control"
          :class="fieldError('tanda_terima') ? 'is-invalid' : ''"
          v-model="state.tandaTerima"
          placeholder="Otomatis diisi dari data pegawai"
        >
        <div v-if="fieldError('tanda_terima')" class="invalid-feedback">
          {{ fieldError('tanda_terima') }}
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label" for="beban_kerja_display">Beban Kerja (Rp)</label>
        <input type="hidden" name="beban_kerja" :value="hiddenValue('beban_kerja')">
        <div class="input-group currency-input-group">
          <div class="input-group-prepend">
            <span class="input-group-text">Rp</span>
          </div>
          <input
            id="beban_kerja_display"
            type="text"
            class="form-control currency-input"
            :class="fieldError('beban_kerja') ? 'is-invalid' : ''"
            :value="currency.beban_kerja.display"
            placeholder="0,00"
            inputmode="decimal"
            autocomplete="off"
            @focus="onFocus('beban_kerja', $event)"
            @input="onInput('beban_kerja', $event)"
            @blur="onBlur('beban_kerja', $event)"
            required
          >
        </div>
        <div v-if="fieldError('beban_kerja')" class="invalid-feedback d-block">
          {{ fieldError('beban_kerja') }}
        </div>
      </div>

      <div class="col-md-6 mb-3">
        <label class="form-label" for="kondisi_kerja_display">Kondisi Kerja (Rp)</label>
        <input type="hidden" name="kondisi_kerja" :value="hiddenValue('kondisi_kerja')">
        <div class="input-group currency-input-group">
          <div class="input-group-prepend">
            <span class="input-group-text">Rp</span>
          </div>
          <input
            id="kondisi_kerja_display"
            type="text"
            class="form-control currency-input"
            :class="fieldError('kondisi_kerja') ? 'is-invalid' : ''"
            :value="currency.kondisi_kerja.display"
            placeholder="0,00"
            inputmode="decimal"
            autocomplete="off"
            @focus="onFocus('kondisi_kerja', $event)"
            @input="onInput('kondisi_kerja', $event)"
            @blur="onBlur('kondisi_kerja', $event)"
            required
          >
        </div>
        <div v-if="fieldError('kondisi_kerja')" class="invalid-feedback d-block">
          {{ fieldError('kondisi_kerja') }}
        </div>
      </div>
    </div>

    <div class="mb-4">
      <h5 class="mb-3">Tambahan TPP</h5>
      <div class="row">
        <div
          v-for="extra in extras"
          :key="extra.key"
          class="col-md-4 mb-3"
        >
          <label class="form-label" :for="`${extra.key}-display`">
            {{ extra.label }}
          </label>
          <input type="hidden" :name="extra.key" :value="hiddenValue(extra.key)">
          <div class="input-group currency-input-group">
            <div class="input-group-prepend">
              <span class="input-group-text">Rp</span>
            </div>
            <input
              :id="`${extra.key}-display`"
              type="text"
              class="form-control currency-input"
              :class="fieldError(extra.key) ? 'is-invalid' : ''"
              :value="extra.field.display"
              placeholder="0,00"
              inputmode="decimal"
              autocomplete="off"
              @focus="onFocus(extra.key, $event)"
              @input="onInput(extra.key, $event)"
              @blur="onBlur(extra.key, $event)"
            >
          </div>
          <div v-if="fieldError(extra.key)" class="invalid-feedback d-block">
            {{ fieldError(extra.key) }}
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6 mb-4">
        <h5 class="mb-3">Indeks Presensi</h5>
        <div class="mb-3">
          <label class="form-label" for="presensi-ketidakhadiran">Jumlah Ketidakhadiran</label>
          <input
            id="presensi-ketidakhadiran"
            name="presensi_ketidakhadiran"
            type="number"
            min="0"
            step="0.01"
            class="form-control"
            :class="fieldError('presensi_ketidakhadiran') ? 'is-invalid' : ''"
            v-model.number="presensi.ketidakhadiran"
            @blur="normalizePresensiCount"
          >
          <div v-if="fieldError('presensi_ketidakhadiran')" class="invalid-feedback">
            {{ fieldError('presensi_ketidakhadiran') }}
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="presensi-persentase-ketidakhadiran">% Ketidakhadiran</label>
          <input
            id="presensi-persentase-ketidakhadiran"
            name="presensi_persen_ketidakhadiran"
            type="number"
            min="0"
            max="40"
            step="0.01"
            class="form-control"
            readonly
            :value="presensiPercentAbsentString"
          >
          <div v-if="fieldError('presensi_persen_ketidakhadiran')" class="invalid-feedback">
            {{ fieldError('presensi_persen_ketidakhadiran') }}
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="presensi-persentase-kehadiran">% Kehadiran (otomatis)</label>
          <input
            id="presensi-persentase-kehadiran"
            name="presensi_persen_kehadiran"
            type="number"
            min="0"
            max="40"
            step="0.01"
            class="form-control"
            readonly
            :value="presensiPercentPresenceString"
          >
          <div v-if="fieldError('presensi_persen_kehadiran')" class="invalid-feedback">
            {{ fieldError('presensi_persen_kehadiran') }}
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="presensi-nilai">Nilai Presensi (Rp)</label>
          <input
            id="presensi-nilai"
            name="presensi_nilai"
            type="number"
            min="0"
            step="1"
            class="form-control"
            readonly
            :value="presensi.nilai"
          >
          <div v-if="fieldError('presensi_nilai')" class="invalid-feedback">
            {{ fieldError('presensi_nilai') }}
          </div>
        </div>
      </div>

      <div class="col-md-6 mb-4">
        <h5 class="mb-3">Indeks Kinerja</h5>
        <div class="mb-3">
          <label class="form-label" for="kinerja-persentase">Persentase Kinerja (%)</label>
          <input
            id="kinerja-persentase"
            name="kinerja_persen"
            type="number"
            min="0"
            max="60"
            step="0.01"
            class="form-control"
            :class="fieldError('kinerja_persen') ? 'is-invalid' : ''"
            v-model.number="kinerja.persen"
            @blur="normalizeKinerjaPercent"
          >
          <div v-if="fieldError('kinerja_persen')" class="invalid-feedback">
            {{ fieldError('kinerja_persen') }}
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="kinerja-nilai">Nilai Kinerja (Rp)</label>
          <input
            id="kinerja-nilai"
            name="kinerja_nilai"
            type="number"
            min="0"
            step="1"
            class="form-control"
            readonly
            :value="kinerja.nilai"
          >
          <div v-if="fieldError('kinerja_nilai')" class="invalid-feedback">
            {{ fieldError('kinerja_nilai') }}
          </div>
        </div>
      </div>
    </div>

    <div class="row mb-4">
      <div class="col-md-4">
        <label class="form-label" for="pfk-pph21-display">PFK PPh Pasal 21 (Rp)</label>
        <input type="hidden" name="pfk_pph21" :value="hiddenValue('pfk_pph21')">
        <div class="input-group currency-input-group">
          <div class="input-group-prepend">
            <span class="input-group-text">Rp</span>
          </div>
          <input
            id="pfk-pph21-display"
            type="text"
            class="form-control currency-input"
            :class="fieldError('pfk_pph21') ? 'is-invalid' : ''"
            :value="currency.pfk_pph21.display"
            placeholder="0,00"
            inputmode="decimal"
            autocomplete="off"
            @focus="onFocus('pfk_pph21', $event)"
            @input="onInput('pfk_pph21', $event)"
            @blur="onBlur('pfk_pph21', $event)"
          >
        </div>
        <div v-if="fieldError('pfk_pph21')" class="invalid-feedback d-block">
          {{ fieldError('pfk_pph21') }}
        </div>
      </div>

      <div class="col-md-4">
        <label class="form-label" for="pfk-bpjs4-display">PFK BPJS 4% (Rp)</label>
        <input type="hidden" name="pfk_bpjs4" :value="hiddenValue('pfk_bpjs4')">
        <div class="input-group currency-input-group">
          <div class="input-group-prepend">
            <span class="input-group-text">Rp</span>
          </div>
          <input
            id="pfk-bpjs4-display"
            type="text"
            class="form-control currency-input"
            :class="fieldError('pfk_bpjs4') ? 'is-invalid' : ''"
            :value="currency.pfk_bpjs4.display"
            placeholder="0,00"
            inputmode="decimal"
            autocomplete="off"
            @focus="onFocus('pfk_bpjs4', $event)"
            @input="onInput('pfk_bpjs4', $event)"
            @blur="onBlur('pfk_bpjs4', $event)"
          >
        </div>
        <div v-if="fieldError('pfk_bpjs4')" class="invalid-feedback d-block">
          {{ fieldError('pfk_bpjs4') }}
        </div>
      </div>

      <div class="col-md-4">
        <label class="form-label" for="pfk-bpjs1-display">PFK BPJS 1% (Rp)</label>
        <input type="hidden" name="pfk_bpjs1" :value="hiddenValue('pfk_bpjs1')">
        <div class="input-group currency-input-group">
          <div class="input-group-prepend">
            <span class="input-group-text">Rp</span>
          </div>
          <input
            id="pfk-bpjs1-display"
            type="text"
            class="form-control currency-input"
            :class="fieldError('pfk_bpjs1') ? 'is-invalid' : ''"
            :value="currency.pfk_bpjs1.display"
            placeholder="0,00"
            inputmode="decimal"
            autocomplete="off"
            @focus="onFocus('pfk_bpjs1', $event)"
            @input="onInput('pfk_bpjs1', $event)"
            @blur="onBlur('pfk_bpjs1', $event)"
          >
        </div>
        <div v-if="fieldError('pfk_bpjs1')" class="invalid-feedback d-block">
          {{ fieldError('pfk_bpjs1') }}
        </div>
      </div>
    </div>

    <div class="card border shadow-sm mb-4">
      <div class="card-body">
        <div class="row text-center text-md-left">
          <div class="col-md-2 mb-3 mb-md-0">
            <div class="text-muted text-uppercase small">Jumlah TPP</div>
            <div class="h5 mb-0">{{ summaryDisplay.jumlah }}</div>
          </div>
          <div class="col-md-2 mb-3 mb-md-0">
            <div class="text-muted text-uppercase small">Bruto</div>
            <div class="h5 mb-0">{{ summaryDisplay.bruto }}</div>
          </div>
          <div class="col-md-2 mb-3 mb-md-0">
            <div class="text-muted text-uppercase small">PFK BPJS 4%</div>
            <div class="h5 mb-0">{{ summaryDisplay.pfkBpjs4 }}</div>
          </div>
          <div class="col-md-2 mb-3 mb-md-0">
            <div class="text-muted text-uppercase small">PFK BPJS 1%</div>
            <div class="h5 mb-0">{{ summaryDisplay.pfkBpjs1 }}</div>
          </div>
          <div class="col-md-2 mb-3 mb-md-0">
            <div class="text-muted text-uppercase small">PFK PPh 21</div>
            <div class="h5 mb-0">{{ summaryDisplay.pfkPph }}</div>
          </div>
          <div class="col-md-2">
            <div class="text-muted text-uppercase small">Netto</div>
            <div class="h5 mb-0">{{ summaryDisplay.netto }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';

const props = defineProps({
  isUpdate: { type: Boolean, default: false },
  typeOptions: { type: Array, default: () => [] },
  selectedType: { type: String, default: '' },
  typeLocked: { type: Boolean, default: false },
  monthOptions: { type: Array, default: () => [] },
  yearBounds: {
    type: Object,
    default: () => ({ min: 2000, max: new Date().getFullYear() }),
  },
  pegawai: {
    type: Object,
    default: () => ({
      options: [],
      selectedId: '',
      readonly: false,
      display: '',
    }),
  },
  fields: {
    type: Object,
    default: () => ({}),
  },
  extras: { type: Array, default: () => [] },
  summary: {
    type: Object,
    default: () => ({
      jumlah_tpp: 0,
      bruto: 0,
      pfk_pph21: 0,
      pfk_bpjs4: 0,
      pfk_bpjs1: 0,
      netto: 0,
    }),
  },
  errors: { type: Object, default: () => ({}) },
  initial: {
    type: Object,
    default: () => ({ year: '', month: '' }),
  },
});

const parseNumericString = (value) => {
  if (value === null || value === undefined || value === '') {
    return null;
  }

  const parsed = Number.parseFloat(String(value));
  return Number.isFinite(parsed) ? parsed : null;
};

const formatter = new Intl.NumberFormat('id-ID', {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});

const formatNumber = (value) => {
  if (!Number.isFinite(value)) {
    return '0,00';
  }
  return formatter.format(value);
};

const formatCurrency = (value) => `Rp ${formatNumber(value)}`;

const state = reactive({
  jenisAsn: props.selectedType ?? '',
  year: props.initial?.year ?? '',
  month: props.initial?.month ?? '',
  pegawaiId: props.pegawai?.selectedId ?? '',
  kelasJabatan: props.fields?.kelas_jabatan ?? '',
  golongan: props.fields?.golongan ?? '',
  tandaTerima: props.fields?.tanda_terima ?? '',
});

const pegawaiOptions = computed(() =>
  (props.pegawai?.options ?? []).map((option) => ({
    id: option.id ?? '',
    nama_lengkap: option.nama_lengkap ?? option.nama ?? '',
    nip: option.nip ?? null,
  }))
);

const pegawaiReadonly = computed(() => Boolean(props.pegawai?.readonly));
const pegawaiDisplay = computed(() => props.pegawai?.display ?? '');

const typeLabel = computed(() => {
  const current = props.typeOptions?.find((option) => option.value === state.jenisAsn);
  return current ? current.label : state.jenisAsn;
});

const yearBounds = computed(() => ({
  min: Number(props.yearBounds?.min ?? 2000),
  max: Number(props.yearBounds?.max ?? new Date().getFullYear()),
}));

const createCurrencyField = (rawValue) => {
  const numeric = parseNumericString(rawValue);
  return reactive({
    numeric: numeric ?? 0,
    hasValue: rawValue !== null && rawValue !== undefined && rawValue !== '',
    display:
      rawValue !== null && rawValue !== undefined && rawValue !== ''
        ? formatNumber(numeric ?? 0)
        : '',
  });
};

const currency = reactive({
  beban_kerja: createCurrencyField(props.fields?.beban_kerja ?? ''),
  kondisi_kerja: createCurrencyField(props.fields?.kondisi_kerja ?? ''),
  pfk_pph21: createCurrencyField(props.fields?.pfk_pph21 ?? ''),
  pfk_bpjs4: createCurrencyField(props.fields?.pfk_bpjs4 ?? ''),
  pfk_bpjs1: createCurrencyField(props.fields?.pfk_bpjs1 ?? ''),
});

const extras = reactive(
  (props.extras ?? []).map((extra) => ({
    key: extra.key,
    label: extra.label,
    field: createCurrencyField(extra.value ?? ''),
  }))
);

const currencyFields = new Map();
currencyFields.set('beban_kerja', currency.beban_kerja);
currencyFields.set('kondisi_kerja', currency.kondisi_kerja);
currencyFields.set('pfk_pph21', currency.pfk_pph21);
currencyFields.set('pfk_bpjs4', currency.pfk_bpjs4);
currencyFields.set('pfk_bpjs1', currency.pfk_bpjs1);
extras.forEach((extra) => {
  currencyFields.set(extra.key, extra.field);
});

const getCurrencyField = (key) => currencyFields.get(key);

const hiddenValue = (key) => {
  const field = getCurrencyField(key);
  if (!field) {
    return '';
  }

  if (!field.hasValue) {
    return '';
  }

  return Number.isFinite(field.numeric) ? field.numeric.toFixed(2) : '';
};

const extrasSum = computed(() =>
  extras.reduce((sum, extra) => {
    const field = extra.field;
    if (!field) {
      return sum;
    }
    return sum + (field.hasValue ? field.numeric : 0);
  }, 0)
);

const getNumeric = (key) => {
  const field = getCurrencyField(key);
  if (!field) {
    return 0;
  }
  return field.hasValue && Number.isFinite(field.numeric) ? field.numeric : 0;
};

const presensi = reactive({
  ketidakhadiran:
    parseNumericString(props.fields?.presensi_ketidakhadiran ?? 0) ?? 0,
  persenKetidakhadiran:
    parseNumericString(props.fields?.presensi_persen_ketidakhadiran ?? 0) ?? 0,
  persenKehadiran:
    parseNumericString(props.fields?.presensi_persen_kehadiran ?? 40) ?? 40,
  nilai: Math.round(parseNumericString(props.fields?.presensi_nilai ?? 0) ?? 0),
});

const kinerja = reactive({
  persen: parseNumericString(props.fields?.kinerja_persen ?? 60) ?? 60,
  nilai: Math.round(parseNumericString(props.fields?.kinerja_nilai ?? 0) ?? 0),
});

const clampNumber = (value, min, max) => {
  if (!Number.isFinite(value)) {
    return min;
  }
  return Math.min(max, Math.max(min, value));
};

const jumlahTpp = computed(
  () => getNumeric('beban_kerja') + getNumeric('kondisi_kerja') + extrasSum.value
);

const pfkPphValue = computed(() => getNumeric('pfk_pph21'));
const pfkBpjs4Value = computed(() => getNumeric('pfk_bpjs4'));
const pfkBpjs1Value = computed(() => getNumeric('pfk_bpjs1'));

const bruto = computed(
  () =>
    presensi.nilai +
    kinerja.nilai +
    pfkPphValue.value +
    pfkBpjs4Value.value
);

const netto = computed(
  () => bruto.value - (pfkPphValue.value + pfkBpjs4Value.value + pfkBpjs1Value.value)
);

const summaryDisplay = computed(() => ({
  jumlah: formatCurrency(jumlahTpp.value),
  bruto: formatCurrency(bruto.value),
  pfkBpjs4: formatCurrency(pfkBpjs4Value.value),
  pfkBpjs1: formatCurrency(pfkBpjs1Value.value),
  pfkPph: formatCurrency(pfkPphValue.value),
  netto: formatCurrency(netto.value),
}));

const presensiPercentAbsentString = computed(() =>
  formatNumber(presensi.persenKetidakhadiran)
);
const presensiPercentPresenceString = computed(() =>
  formatNumber(presensi.persenKehadiran)
);

const updatePresensiDerived = () => {
  const absence = clampNumber(presensi.ketidakhadiran ?? 0, 0, Number.MAX_VALUE);
  const percentAbsent = Math.min(40, absence * 3);
  const percentPresence = Math.max(0, 40 - percentAbsent);
  const presenceValue = Math.round(
    jumlahTpp.value * (percentPresence / 100)
  );

  presensi.persenKetidakhadiran = Number(percentAbsent.toFixed(2));
  presensi.persenKehadiran = Number(percentPresence.toFixed(2));
  presensi.nilai = presenceValue;
};

const updateKinerjaDerived = () => {
  const percent = clampNumber(kinerja.persen ?? 0, 0, 60);
  kinerja.nilai = Math.round(jumlahTpp.value * (percent / 100));
};

watch(
  () => [presensi.ketidakhadiran, jumlahTpp.value],
  () => {
    updatePresensiDerived();
  },
  { immediate: true }
);

watch(
  () => [kinerja.persen, jumlahTpp.value],
  () => {
    updateKinerjaDerived();
  },
  { immediate: true }
);

const normalizePresensiCount = () => {
  if (!Number.isFinite(presensi.ketidakhadiran) || presensi.ketidakhadiran < 0) {
    presensi.ketidakhadiran = 0;
  }
  presensi.ketidakhadiran = Number(presensi.ketidakhadiran.toFixed(2));
  updatePresensiDerived();
};

const normalizeKinerjaPercent = () => {
  if (!Number.isFinite(kinerja.persen)) {
    kinerja.persen = 0;
  }
  kinerja.persen = Number(clampNumber(kinerja.persen, 0, 60).toFixed(2));
  updateKinerjaDerived();
};

const fieldError = (name) => {
  const messages = props.errors?.[name];
  if (!messages || !Array.isArray(messages) || messages.length === 0) {
    return null;
  }

  const first = messages[0];
  return first === null || first === undefined ? null : String(first);
};

const sanitizeCurrencyInput = (value) => {
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

const pegawaiLabel = (option) => {
  const name = option.nama_lengkap ?? '';
  const nip = option.nip ? ` - ${option.nip}` : '';
  return `${name}${nip}`;
};

const onFocus = (key, event) => {
  const field = getCurrencyField(key);
  if (!field) {
    return;
  }

  if (!field.hasValue) {
    field.display = '';
    event.target.value = '';
    return;
  }

  const plain = Number.isFinite(field.numeric)
    ? field.numeric.toFixed(2).replace('.', ',')
    : '';
  field.display = plain;
  event.target.value = plain;
  event.target.select();
};

const onInput = (key, event) => {
  const field = getCurrencyField(key);
  if (!field) {
    return;
  }

  const sanitized = sanitizeCurrencyInput(event.target.value ?? '');
  if (sanitized !== event.target.value) {
    event.target.value = sanitized;
  }

  field.display = sanitized;
  const numeric = parseCurrencyInput(sanitized);
  if (numeric === null) {
    field.numeric = 0;
    field.hasValue = false;
  } else {
    field.numeric = numeric;
    field.hasValue = true;
  }

  updatePresensiDerived();
  updateKinerjaDerived();
};

const onBlur = (key, event) => {
  const field = getCurrencyField(key);
  if (!field) {
    return;
  }

  if (!field.hasValue) {
    field.display = '';
    event.target.value = '';
    return;
  }

  const formatted = formatNumber(field.numeric);
  field.display = formatted;
  event.target.value = formatted;

  updatePresensiDerived();
  updateKinerjaDerived();
};
</script>
