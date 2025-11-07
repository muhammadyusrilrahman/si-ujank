<template>
  <div>
    <form class="row g-3 align-items-end mb-4" @submit.prevent="applyFilters">
      <div class="col-md-3">
        <label for="filter-type" class="form-label">{{ texts.typeLabel }}</label>
        <select
          id="filter-type"
          class="form-control"
          v-model="filterState.type"
        >
          <option
            v-for="option in options.types"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
      </div>
      <div class="col-md-2">
        <label for="filter-year" class="form-label">{{ texts.yearLabel }}</label>
        <input
          id="filter-year"
          type="number"
          class="form-control"
          :min="options.yearBounds.min"
          :max="options.yearBounds.max"
          v-model.number="filterState.year"
          required
        >
      </div>
      <div class="col-md-2">
        <label for="filter-month" class="form-label">{{ texts.monthLabel }}</label>
        <select
          id="filter-month"
          class="form-control"
          v-model="filterState.month"
          required
        >
          <option value="">{{ texts.monthPlaceholder }}</option>
          <option
            v-for="option in options.months"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
      </div>
      <div class="col-md-2">
        <label for="filter-per-page" class="form-label">{{ texts.perPageLabel }}</label>
        <select
          id="filter-per-page"
          class="form-control"
          v-model.number="filterState.perPage"
        >
          <option
            v-for="option in options.perPage"
            :key="option"
            :value="Number(option)"
          >
            {{ option }}
          </option>
        </select>
      </div>
      <div class="col-md-3">
        <label for="filter-search" class="form-label">{{ texts.searchLabel }}</label>
        <input
          id="filter-search"
          type="text"
          class="form-control"
          :placeholder="texts.searchPlaceholder"
          v-model="filterState.search"
          @keyup.enter="applyFilters"
        >
      </div>
      <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-filter"></i>
          <span class="ml-1">{{ texts.applyButton }}</span>
        </button>
        <button type="button" class="btn btn-outline-secondary" @click="resetFilters">
          {{ texts.resetButton }}
        </button>
      </div>
    </form>

    <div v-if="messages.status" class="alert alert-success">
      {{ messages.status }}
    </div>

    <div v-if="messages.error" class="alert alert-danger">
      {{ messages.error }}
    </div>

    <div v-if="messages.importErrors && messages.importErrors.length" class="alert alert-danger">
      <div v-for="(message, index) in messages.importErrors" :key="index">
        {{ message }}
      </div>
    </div>

    <div v-if="!filtersReady" class="alert alert-info">
      {{ texts.filtersHint }}
    </div>
    <div v-else-if="!rows.length" class="alert alert-warning">
      {{ texts.emptyMessage }}
    </div>
    <template v-else>
      <div class="row row-cols-1 row-cols-md-5 g-3 mb-4">
        <div v-for="card in summaryCards" :key="card.key" class="col">
          <div class="card card-outline card-primary h-100">
            <div class="card-body">
              <div class="text-muted text-uppercase small">{{ card.label }}</div>
              <div class="h5 mb-0">
                {{ formatCurrency(card.value) }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover align-middle perhitungan-table">
          <thead class="table-primary">
            <tr class="text-center align-middle">
              <th rowspan="2">No</th>
              <th rowspan="2">{{ texts.columnPegawai }}</th>
              <th rowspan="2">{{ texts.columnKelasJabatan }}</th>
              <th rowspan="2">{{ texts.columnGolongan }}</th>
              <th rowspan="2">{{ texts.columnBebanKerja }}</th>
              <th
                v-for="key in extraKeys"
                :key="`extra-head-${key}`"
                rowspan="2"
              >
                {{ extraLabels[key] ?? key }}
              </th>
              <th rowspan="2">{{ texts.columnKondisiKerja }}</th>
              <th rowspan="2">{{ texts.columnJumlahTpp }}</th>
              <th colspan="4">{{ texts.columnPresensiTitle }}</th>
              <th colspan="2">{{ texts.columnKinerjaTitle }}</th>
              <th rowspan="2">{{ texts.columnBruto }}</th>
              <th colspan="3">{{ texts.columnPfkTitle }}</th>
              <th rowspan="2">{{ texts.columnNetto }}</th>
              <th rowspan="2">{{ texts.columnTandaTerima }}</th>
              <th rowspan="2">{{ texts.actionsColumn }}</th>
            </tr>
            <tr class="text-center">
              <th>{{ texts.columnPresensiCount }}</th>
              <th>{{ texts.columnPresensiAbsent }}</th>
              <th>{{ texts.columnPresensiPresent }}</th>
              <th>{{ texts.columnPresensiValue }}</th>
              <th>{{ texts.columnKinerjaPercent }}</th>
              <th>{{ texts.columnKinerjaValue }}</th>
              <th>{{ texts.columnPfkPph21 }}</th>
              <th>{{ texts.columnPfkBpjs4 }}</th>
              <th>{{ texts.columnPfkBpjs1 }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, index) in rows" :key="row.id" class="align-top">
              <td class="text-center">{{ rowNumber(index) }}</td>
              <td>
                <div>{{ row.pegawai.nama }}</div>
                <div class="text-muted small">{{ row.pegawai.nip }}</div>
                <div class="text-muted small">{{ row.pegawai.jabatan }}</div>
              </td>
              <td>{{ row.kelas_jabatan }}</td>
              <td>{{ row.golongan }}</td>
              <td>
                <input
                  type="number"
                  min="0"
                  step="0.01"
                  class="form-control form-control-sm text-end"
                  v-model.number="row.beban_kerja"
                  @input="recalcRow(row)"
                >
              </td>
              <td v-for="key in extraKeys" :key="`extra-${row.id}-${key}`">
                <input
                  type="number"
                  min="0"
                  step="0.01"
                  class="form-control form-control-sm text-end"
                  v-model.number="row.extras[key]"
                  @input="recalcRow(row)"
                >
              </td>
              <td>
                <input
                  type="number"
                  min="0"
                  step="0.01"
                  class="form-control form-control-sm text-end"
                  v-model.number="row.kondisi_kerja"
                  @input="recalcRow(row)"
                >
              </td>
              <td class="text-end">{{ formatCurrency(row.jumlah_tpp) }}</td>
              <td>
                <input
                  type="number"
                  min="0"
                  step="0.01"
                  class="form-control form-control-sm text-end"
                  v-model.number="row.presensi.ketidakhadiran"
                  @input="recalcRow(row)"
                >
              </td>
              <td>
                <input
                  type="number"
                  min="0"
                  max="40"
                  step="0.01"
                  class="form-control form-control-sm text-end"
                  :value="row.presensi.persentase_ketidakhadiran"
                  readonly
                >
              </td>
              <td>
                <input
                  type="number"
                  min="0"
                  max="40"
                  step="0.01"
                  class="form-control form-control-sm text-end"
                  :value="row.presensi.persentase_kehadiran"
                  readonly
                >
              </td>
              <td class="text-end">
                {{ formatCurrency(row.presensi.nilai) }}
              </td>
              <td>
                <input
                  type="number"
                  min="0"
                  max="60"
                  step="0.01"
                  class="form-control form-control-sm text-end"
                  v-model.number="row.kinerja.persentase"
                  @input="recalcRow(row)"
                >
              </td>
              <td class="text-end">
                {{ formatCurrency(row.kinerja.nilai) }}
              </td>
              <td class="text-end">{{ formatCurrency(row.bruto) }}</td>
              <td>
                <input
                  type="number"
                  min="0"
                  step="0.01"
                  class="form-control form-control-sm text-end"
                  v-model.number="row.pfk.pph21"
                  @input="recalcRow(row)"
                >
              </td>
              <td>
                <input
                  type="number"
                  min="0"
                  step="0.01"
                  class="form-control form-control-sm text-end"
                  v-model.number="row.pfk.bpjs4"
                  @input="recalcRow(row)"
                >
              </td>
              <td>
                <input
                  type="number"
                  min="0"
                  step="0.01"
                  class="form-control form-control-sm text-end"
                  v-model.number="row.pfk.bpjs1"
                  @input="recalcRow(row)"
                >
              </td>
              <td class="text-end">{{ formatCurrency(row.netto) }}</td>
              <td>
                <input
                  type="text"
                  class="form-control form-control-sm"
                  :value="row.tanda_terima"
                  readonly
                >
              </td>
              <td class="text-nowrap align-middle">
                <template v-if="permissions.canManage">
                  <button
                    type="button"
                    class="btn btn-sm btn-primary mb-1"
                    @click="submitRow(row)"
                  >
                    <i class="fas fa-save"></i>
                    <span class="ml-1">{{ texts.saveButton }}</span>
                  </button>
                  <a
                    v-if="row.routes.edit"
                    :href="row.routes.edit"
                    class="btn btn-sm btn-outline-secondary mb-1"
                  >
                    <i class="fas fa-pen"></i>
                  </a>
                  <button
                    v-if="row.routes.destroy"
                    type="button"
                    class="btn btn-sm btn-danger"
                    @click="destroyRow(row)"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </template>
                <template v-else>
                  <span class="badge badge-secondary">{{ texts.noActions }}</span>
                </template>
              </td>
            </tr>
          </tbody>
          <tfoot class="table-secondary" v-if="rows.length">
            <tr>
              <th colspan="4" class="text-end">{{ texts.totalLabel }}</th>
              <th class="text-end">{{ formatCurrency(summaryTotals.totals.beban_kerja) }}</th>
              <th
                v-for="key in extraKeys"
                :key="`extra-total-${key}`"
                class="text-end"
              >
                {{ formatCurrency(summaryTotals.extras[key] ?? 0) }}
              </th>
              <th class="text-end">{{ formatCurrency(summaryTotals.totals.kondisi_kerja) }}</th>
              <th class="text-end">{{ formatCurrency(summaryTotals.totals.jumlah_tpp) }}</th>
              <th class="text-end">{{ formatNumber(summaryTotals.totals.presensi_ketidakhadiran) }}</th>
              <th class="text-end">{{ formatNumber(summaryTotals.averages.absent) }}</th>
              <th class="text-end">{{ formatNumber(summaryTotals.averages.presence) }}</th>
              <th class="text-end">{{ formatCurrency(summaryTotals.totals.presensi_nilai) }}</th>
              <th class="text-end">{{ formatNumber(summaryTotals.averages.kinerja) }}</th>
              <th class="text-end">{{ formatCurrency(summaryTotals.totals.kinerja_nilai) }}</th>
              <th class="text-end">{{ formatCurrency(summaryTotals.totals.bruto) }}</th>
              <th class="text-end">{{ formatCurrency(summaryTotals.totals.pfk_pph21) }}</th>
              <th class="text-end">{{ formatCurrency(summaryTotals.totals.pfk_bpjs4) }}</th>
              <th class="text-end">{{ formatCurrency(summaryTotals.totals.pfk_bpjs1) }}</th>
              <th class="text-end">{{ formatCurrency(summaryTotals.totals.netto) }}</th>
              <th></th>
              <th></th>
            </tr>
          </tfoot>
        </table>
      </div>

      <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mt-3">
        <div class="text-muted small">
          {{ paginationText }}
        </div>
        <div class="d-flex flex-column flex-md-row align-items-md-center gap-2">
          <select
            v-if="options.perPage.length > 1"
            class="custom-select custom-select-sm w-auto"
            v-model.number="filterState.perPage"
            @change="applyFilters"
          >
            <option
              v-for="option in options.perPage"
              :key="`bottom-per-page-${option}`"
              :value="Number(option)"
            >
              {{ texts.perPageOptionLabel(option) }}
            </option>
          </select>
          <nav v-if="hasPagination" aria-label="Pagination Navigation">
            <ul class="pagination mb-0">
              <li
                v-for="(link, index) in pagination.links"
                :key="index"
                class="page-item"
                :class="{ active: link.active, disabled: !link.url }"
              >
                <template v-if="link.url">
                  <a
                    href="#"
                    class="page-link"
                    v-html="link.label"
                    @click.prevent="navigate(link.url)"
                  ></a>
                </template>
                <template v-else>
                  <span class="page-link" v-html="link.label"></span>
                </template>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({}),
  },
  options: {
    type: Object,
    default: () => ({
      types: [],
      months: [],
      perPage: [25, 50, 100],
      yearBounds: { min: 2000, max: new Date().getFullYear() + 5 },
    }),
  },
  extras: {
    type: Object,
    default: () => ({
      order: [],
      labels: {},
    }),
  },
  items: {
    type: Array,
    default: () => [],
  },
  pagination: {
    type: Object,
    default: () => null,
  },
  filtersReady: {
    type: Boolean,
    default: false,
  },
  permissions: {
    type: Object,
    default: () => ({
      canManage: false,
    }),
  },
  routes: {
    type: Object,
    default: () => ({
      index: '',
    }),
  },
  context: {
    type: Object,
    default: () => ({
      hiddenFields: {},
    }),
  },
  messages: {
    type: Object,
    default: () => ({}),
  },
  texts: {
    type: Object,
    default: () => ({}),
  },
  csrfToken: {
    type: String,
    default: '',
  },
});

const defaultTexts = {
  typeLabel: 'Jenis ASN',
  yearLabel: 'Tahun',
  monthLabel: 'Bulan',
  monthPlaceholder: 'Pilih Bulan',
  perPageLabel: 'Data per Halaman',
  searchLabel: 'Cari Pegawai',
  searchPlaceholder: 'Nama atau NIP',
  applyButton: 'Terapkan',
  resetButton: 'Atur Ulang',
  filtersHint: 'Pilih jenis ASN, tahun, dan bulan, kemudian tekan Terapkan untuk menampilkan perhitungan TPP.',
  emptyMessage: 'Belum ada data perhitungan TPP untuk filter yang dipilih.',
  columnPegawai: 'Nama / NIP / Jabatan',
  columnKelasJabatan: 'Kelas Jabatan',
  columnGolongan: 'Gol / Ruang',
  columnBebanKerja: 'Beban Kerja',
  columnKondisiKerja: 'Kondisi Kerja',
  columnJumlahTpp: 'Jumlah TPP',
  columnPresensiTitle: 'Persentase Indeks Presensi (maks 40%)',
  columnPresensiCount: 'Jumlah Ketidakhadiran',
  columnPresensiAbsent: '% Ketidakhadiran',
  columnPresensiPresent: '% Kehadiran',
  columnPresensiValue: 'Nilai (Rp)',
  columnKinerjaTitle: 'Persentase Indeks Kinerja (maks 60%)',
  columnKinerjaPercent: 'Persentase',
  columnKinerjaValue: 'Nilai (Rp)',
  columnBruto: 'Bruto',
  columnPfkTitle: 'Setoran PFK',
  columnPfkPph21: 'PPH 21',
  columnPfkBpjs4: 'BPJS 4%',
  columnPfkBpjs1: 'BPJS 1%',
  columnNetto: 'Netto',
  columnTandaTerima: 'Tanda Terima',
  actionsColumn: 'Aksi',
  saveButton: 'Simpan',
  noActions: 'Tidak ada aksi',
  deleteConfirm: 'Hapus perhitungan TPP ini?',
  totalLabel: 'Total',
  cardTotalTpp: 'Jumlah TPP',
  cardTotalPresence: 'Total Presensi',
  cardTotalPerformance: 'Total Kinerja',
  cardTotalPfk: 'Total PFK',
  cardTotalNetto: 'Total Netto',
  perPageOptionLabel: (value) => `${value} data`,
  paginationSummary: (from, to, total) => `Menampilkan ${from} - ${to} dari ${total} data perhitungan`,
};

const texts = computed(() => ({
  ...defaultTexts,
  ...(props.texts || {}),
}));

const filterState = reactive({
  type: props.filters?.type ?? '',
  year: props.filters?.year ?? '',
  month: props.filters?.month ?? '',
  perPage: props.filters?.perPage ?? (props.options?.perPage?.[0] ?? 25),
  search: props.filters?.search ?? '',
});

const options = computed(() => ({
  types: props.options?.types ?? [],
  months: props.options?.months ?? [],
  perPage: props.options?.perPage ?? [25, 50, 100],
  yearBounds: props.options?.yearBounds ?? {
    min: 2000,
    max: new Date().getFullYear() + 5,
  },
}));

const extraKeys = computed(() => props.extras?.order ?? []);
const extraLabels = computed(() => props.extras?.labels ?? {});
const hiddenFields = computed(() => props.context?.hiddenFields ?? {});
const messages = computed(() => props.messages ?? {});

const rows = reactive((props.items || []).map((item) => prepareRow(item)));

const pagination = computed(() => props.pagination || null);
const hasPagination = computed(() => Array.isArray(pagination.value?.links) && pagination.value.links.length > 0);

const paginationText = computed(() => {
  if (!pagination.value || pagination.value.total === 0) {
    return texts.value.paginationSummary(0, 0, 0);
  }

  const from = pagination.value.from ?? 0;
  const to = pagination.value.to ?? 0;
  const total = pagination.value.total ?? 0;
  return texts.value.paginationSummary(from, to, total);
});

const summaryTotals = computed(() => calculateSummary(rows, extraKeys.value));

const summaryCards = computed(() => [
  { key: 'jumlah_tpp', label: texts.value.cardTotalTpp, value: summaryTotals.value.totals.jumlah_tpp },
  { key: 'presensi_nilai', label: texts.value.cardTotalPresence, value: summaryTotals.value.totals.presensi_nilai },
  { key: 'kinerja_nilai', label: texts.value.cardTotalPerformance, value: summaryTotals.value.totals.kinerja_nilai },
  { key: 'pfk_total', label: texts.value.cardTotalPfk, value: summaryTotals.value.totals.pfk_total },
  { key: 'netto', label: texts.value.cardTotalNetto, value: summaryTotals.value.totals.netto },
]);

const routes = computed(() => ({
  index: props.routes?.index || window.location.pathname,
}));

const permissions = computed(() => ({
  canManage: Boolean(props.permissions?.canManage),
}));

function prepareRow(item) {
  const extras = {};
  extraKeys.value.forEach((key) => {
    extras[key] = toNumber(item?.extras?.[key] ?? 0);
  });

  return {
    id: item.id,
    pegawai: {
      nama: item?.pegawai?.nama ?? '-',
      nip: item?.pegawai?.nip ?? '-',
      jabatan: item?.pegawai?.jabatan ?? '-',
    },
    kelas_jabatan: item?.kelas_jabatan ?? '-',
    golongan: item?.golongan ?? '-',
    beban_kerja: toNumber(item?.beban_kerja ?? 0),
    extras,
    kondisi_kerja: toNumber(item?.kondisi_kerja ?? 0),
    jumlah_tpp: toNumber(item?.jumlah_tpp ?? 0),
    presensi: {
      ketidakhadiran: toNumber(item?.presensi?.ketidakhadiran ?? 0),
      persentase_ketidakhadiran: toNumber(item?.presensi?.persentase_ketidakhadiran ?? 0),
      persentase_kehadiran: toNumber(item?.presensi?.persentase_kehadiran ?? 0),
      nilai: toNumber(item?.presensi?.nilai ?? 0),
    },
    kinerja: {
      persentase: toNumber(item?.kinerja?.persentase ?? 0),
      nilai: toNumber(item?.kinerja?.nilai ?? 0),
    },
    bruto: toNumber(item?.bruto ?? 0),
    pfk: {
      pph21: toNumber(item?.pfk?.pph21 ?? 0),
      bpjs4: toNumber(item?.pfk?.bpjs4 ?? 0),
      bpjs1: toNumber(item?.pfk?.bpjs1 ?? 0),
    },
    netto: toNumber(item?.netto ?? 0),
    tanda_terima: item?.tanda_terima ?? '',
    routes: item?.routes ?? {},
  };
}

function toNumber(value) {
  const parsed = Number.parseFloat(value);
  return Number.isFinite(parsed) ? parsed : 0;
}

function clamp(value, min, max) {
  return Math.min(Math.max(value, min), max);
}

function roundAmount(value) {
  return Math.round((value + Number.EPSILON) * 100) / 100;
}

function roundCurrency(value) {
  return Math.round((value + Number.EPSILON) * 100) / 100;
}

function recalcRow(row) {
  row.beban_kerja = roundAmount(Math.max(0, toNumber(row.beban_kerja)));
  row.kondisi_kerja = roundAmount(Math.max(0, toNumber(row.kondisi_kerja)));

  let extrasTotal = 0;
  extraKeys.value.forEach((key) => {
    const value = Math.max(0, toNumber(row.extras[key] ?? 0));
    row.extras[key] = roundAmount(value);
    extrasTotal += row.extras[key];
  });

  const jumlahTpp = roundCurrency(row.beban_kerja + row.kondisi_kerja + extrasTotal);
  row.jumlah_tpp = jumlahTpp;

  const absentRaw = Math.max(0, toNumber(row.presensi.ketidakhadiran));
  const absentCount = roundAmount(absentRaw);
  const absentPercent = roundAmount(Math.min(40, absentCount * 3));
  const presencePercent = roundAmount(Math.max(0, 40 - absentPercent));
  const presenceValue = roundCurrency(jumlahTpp * (presencePercent / 100));

  let kinerjaPercent = toNumber(row.kinerja.persentase);
  kinerjaPercent = roundAmount(clamp(kinerjaPercent, 0, 60));
  const kinerjaValue = roundCurrency(jumlahTpp * (kinerjaPercent / 100));

  const pfkPph21 = roundAmount(Math.max(0, toNumber(row.pfk.pph21)));
  const pfkBpjs4 = roundAmount(Math.max(0, toNumber(row.pfk.bpjs4)));
  const pfkBpjs1 = roundAmount(Math.max(0, toNumber(row.pfk.bpjs1)));

  row.presensi.ketidakhadiran = absentCount;
  row.presensi.persentase_ketidakhadiran = absentPercent;
  row.presensi.persentase_kehadiran = presencePercent;
  row.presensi.nilai = roundCurrency(presenceValue);

  row.kinerja.persentase = kinerjaPercent;
  row.kinerja.nilai = roundCurrency(kinerjaValue);

  row.pfk.pph21 = pfkPph21;
  row.pfk.bpjs4 = pfkBpjs4;
  row.pfk.bpjs1 = pfkBpjs1;

  const bruto = roundCurrency(row.presensi.nilai + row.kinerja.nilai + pfkPph21 + pfkBpjs4);
  const netto = roundCurrency(bruto - (pfkPph21 + pfkBpjs4 + pfkBpjs1));

  row.bruto = bruto;
  row.netto = netto;
}

function calculateSummary(rowList, extrasOrder) {
  const totals = {
    beban_kerja: 0,
    kondisi_kerja: 0,
    jumlah_tpp: 0,
    presensi_ketidakhadiran: 0,
    presensi_persen_ketidakhadiran: 0,
    presensi_persen_kehadiran: 0,
    presensi_nilai: 0,
    kinerja_persen: 0,
    kinerja_nilai: 0,
    bruto: 0,
    pfk_pph21: 0,
    pfk_bpjs4: 0,
    pfk_bpjs1: 0,
    netto: 0,
  };

  const extrasTotals = {};
  extrasOrder.forEach((key) => {
    extrasTotals[key] = 0;
  });

  rowList.forEach((row) => {
    totals.beban_kerja += toNumber(row.beban_kerja);
    totals.kondisi_kerja += toNumber(row.kondisi_kerja);
    totals.jumlah_tpp += toNumber(row.jumlah_tpp);

    extrasOrder.forEach((key) => {
      extrasTotals[key] += toNumber(row.extras[key] ?? 0);
    });

    totals.presensi_ketidakhadiran += toNumber(row.presensi.ketidakhadiran);
    totals.presensi_persen_ketidakhadiran += toNumber(row.presensi.persentase_ketidakhadiran);
    totals.presensi_persen_kehadiran += toNumber(row.presensi.persentase_kehadiran);
    totals.presensi_nilai += toNumber(row.presensi.nilai);

    totals.kinerja_persen += toNumber(row.kinerja.persentase);
    totals.kinerja_nilai += toNumber(row.kinerja.nilai);

    totals.bruto += toNumber(row.bruto);
    totals.pfk_pph21 += toNumber(row.pfk.pph21);
    totals.pfk_bpjs4 += toNumber(row.pfk.bpjs4);
    totals.pfk_bpjs1 += toNumber(row.pfk.bpjs1);
    totals.netto += toNumber(row.netto);
  });

  const count = rowList.length || 0;

  const averages = {
    absent: count ? totals.presensi_persen_ketidakhadiran / count : 0,
    presence: count ? totals.presensi_persen_kehadiran / count : 0,
    kinerja: count ? totals.kinerja_persen / count : 0,
  };

  return {
    totals: {
      ...totals,
      pfk_total: totals.pfk_pph21 + totals.pfk_bpjs4 + totals.pfk_bpjs1,
    },
    extras: extrasTotals,
    averages,
    count,
  };
}

function formatCurrency(value) {
  const amount = toNumber(value);
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 2,
  })
    .format(amount)
    .replace('IDR', 'Rp')
    .trim();
}

function formatNumber(value) {
  return new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(toNumber(value));
}

function rowNumber(index) {
  const start = pagination.value?.from ?? 1;
  return start + index;
}

function applyFilters() {
  const params = new URLSearchParams();

  if (filterState.type) {
    params.set('type', filterState.type);
  }

  if (filterState.year) {
    params.set('tahun', String(filterState.year));
  }

  if (filterState.month) {
    params.set('bulan', String(filterState.month));
  }

  if (filterState.perPage) {
    params.set('per_page', String(filterState.perPage));
  }

  if (filterState.search && filterState.search.trim() !== '') {
    params.set('search', filterState.search.trim());
  }

  const targetUrl = params.toString() ? `${routes.value.index}?${params.toString()}` : routes.value.index;
  window.location.href = targetUrl;
}

function resetFilters() {
  window.location.href = routes.value.index;
}

function navigate(url) {
  if (!url) {
    return;
  }
  window.location.href = url;
}

function createHiddenInput(form, name, value) {
  const input = document.createElement('input');
  input.type = 'hidden';
  input.name = name;
  input.value = value !== undefined && value !== null ? String(value) : '';
  form.appendChild(input);
}

function submitRow(row) {
  if (!permissions.value.canManage || !row.routes?.update) {
    return;
  }

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = row.routes.update;

  createHiddenInput(form, '_token', props.csrfToken);
  createHiddenInput(form, '_method', 'PUT');

  Object.entries(hiddenFields.value || {}).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') {
      return;
    }
    createHiddenInput(form, key, value);
  });

  createHiddenInput(form, 'beban_kerja', row.beban_kerja);
  extraKeys.value.forEach((key) => {
    createHiddenInput(form, key, row.extras[key] ?? 0);
  });
  createHiddenInput(form, 'kondisi_kerja', row.kondisi_kerja);
  createHiddenInput(form, 'jumlah_tpp', row.jumlah_tpp);
  createHiddenInput(form, 'presensi_ketidakhadiran', row.presensi.ketidakhadiran);
  createHiddenInput(form, 'presensi_persen_ketidakhadiran', row.presensi.persentase_ketidakhadiran);
  createHiddenInput(form, 'presensi_persen_kehadiran', row.presensi.persentase_kehadiran);
  createHiddenInput(form, 'presensi_nilai', row.presensi.nilai);
  createHiddenInput(form, 'kinerja_persen', row.kinerja.persentase);
  createHiddenInput(form, 'kinerja_nilai', row.kinerja.nilai);
  createHiddenInput(form, 'bruto', row.bruto);
  createHiddenInput(form, 'pfk_pph21', row.pfk.pph21);
  createHiddenInput(form, 'pfk_bpjs4', row.pfk.bpjs4);
  createHiddenInput(form, 'pfk_bpjs1', row.pfk.bpjs1);
  createHiddenInput(form, 'netto', row.netto);
  createHiddenInput(form, 'tanda_terima', row.tanda_terima ?? '');

  document.body.appendChild(form);
  form.submit();
}

function destroyRow(row) {
  if (!permissions.value.canManage || !row.routes?.destroy) {
    return;
  }

  if (!window.confirm(texts.value.deleteConfirm)) {
    return;
  }

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = row.routes.destroy;

  createHiddenInput(form, '_token', props.csrfToken);
  createHiddenInput(form, '_method', 'DELETE');

  document.body.appendChild(form);
  form.submit();
}

onMounted(() => {
  rows.forEach((row) => recalcRow(row));
});
</script>

<style scoped>
.perhitungan-table th,
.perhitungan-table td {
  white-space: nowrap;
}

.perhitungan-table .text-end {
  white-space: nowrap;
}
</style>
