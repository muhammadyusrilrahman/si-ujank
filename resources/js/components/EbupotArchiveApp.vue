<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">{{ texts.title }}</h5>
      <a :href="routes.create" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i>
        <span class="ml-1">{{ texts.createButton }}</span>
      </a>
    </div>

    <div class="card mb-3">
      <div class="card-body">
        <form class="row align-items-end" @submit.prevent="applyFilters">
          <div class="col-md-3 mb-3">
            <label class="form-label" for="ebupot-type">
              {{ texts.typeLabel }}
            </label>
            <select
              id="ebupot-type"
              class="form-control"
              v-model="filterState.type"
            >
              <option value="">{{ texts.typeAll }}</option>
              <option
                v-for="option in typeOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.display }}
              </option>
            </select>
          </div>

          <div class="col-md-3 mb-3">
            <label class="form-label" for="ebupot-year">
              {{ texts.yearLabel }}
            </label>
            <input
              id="ebupot-year"
              type="number"
              class="form-control"
              v-model.number="filterState.year"
              :min="yearBounds.min"
              :max="yearBounds.max"
              :placeholder="texts.yearPlaceholder"
            >
          </div>

          <div class="col-md-3 mb-3">
            <label class="form-label" for="ebupot-month">
              {{ texts.monthLabel }}
            </label>
            <select
              id="ebupot-month"
              class="form-control"
              v-model="filterState.month"
            >
              <option value="">{{ texts.monthAll }}</option>
              <option
                v-for="option in monthOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </div>

          <div class="col-md-3 mb-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary mr-2 flex-fill">
              <i class="fas fa-filter"></i>
              <span class="ml-1">{{ texts.applyButton }}</span>
            </button>
            <button
              type="button"
              class="btn btn-outline-secondary flex-fill"
              @click="resetFilters"
            >
              {{ texts.resetButton }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="!items.length" class="alert alert-info mb-0">
      {{ texts.emptyMessage }}
    </div>

    <div v-else class="card">
      <div class="card-body table-responsive">
        <table class="table table-striped table-bordered align-middle">
          <thead class="table-primary">
            <tr class="text-center">
              <th>#</th>
              <th>{{ texts.periodColumn }}</th>
              <th>{{ texts.typeColumn }}</th>
              <th v-if="showSkpd">{{ texts.skpdColumn }}</th>
              <th>{{ texts.npwpColumn }}</th>
              <th>{{ texts.tkuColumn }}</th>
              <th>{{ texts.kodeObjekColumn }}</th>
              <th>{{ texts.entryCountColumn }}</th>
              <th>{{ texts.totalGrossColumn }}</th>
              <th>{{ texts.createdByColumn }}</th>
              <th>{{ texts.updatedAtColumn }}</th>
              <th>{{ texts.actionsColumn }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in items" :key="item.id">
              <td class="text-center">{{ rowNumber(index) }}</td>
              <td class="text-center">{{ item.period }}</td>
              <td class="text-center">{{ item.jenis_asn }}</td>
              <td v-if="showSkpd">{{ item.skpd ?? '-' }}</td>
              <td>{{ item.npwp ?? '-' }}</td>
              <td>{{ item.id_tku ?? '-' }}</td>
              <td>{{ item.kode_objek ?? '-' }}</td>
              <td class="text-center">{{ formatNumber(item.entry_count) }}</td>
              <td class="text-right">{{ formatCurrency(item.total_gross) }}</td>
              <td>{{ item.created_by ?? '-' }}</td>
              <td class="text-center">{{ item.updated_at ?? '-' }}</td>
              <td class="text-center">
                <div class="btn-group btn-group-sm" role="group">
                  <a :href="item.links.xlsx" class="btn btn-outline-primary">
                    <i class="fas fa-file-excel"></i>
                  </a>
                  <a :href="item.links.xml" class="btn btn-outline-secondary">
                    <i class="fas fa-file-code"></i>
                  </a>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="pagination && pagination.links && pagination.links.length" class="card-footer bg-white">
        <nav aria-label="Pagination Navigation">
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
  </div>
</template>

<script setup>
import { computed, reactive } from 'vue';

const props = defineProps({
  typeOptions: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({
      type: '',
      year: '',
      month: '',
    }),
  },
  monthOptions: {
    type: Array,
    default: () => [],
  },
  yearBounds: {
    type: Object,
    default: () => ({
      min: 2000,
      max: new Date().getFullYear() + 5,
    }),
  },
  routes: {
    type: Object,
    default: () => ({
      index: '',
      create: '',
    }),
  },
  items: {
    type: Array,
    default: () => [],
  },
  pagination: {
    type: Object,
    default: null,
  },
  showSkpd: {
    type: Boolean,
    default: false,
  },
  texts: {
    type: Object,
    default: () => ({}),
  },
});

const defaultTexts = {
  title: 'Arsip E-Bupot',
  createButton: 'Buat E-Bupot',
  typeLabel: 'Jenis ASN',
  typeAll: 'Semua',
  yearLabel: 'Tahun',
  yearPlaceholder: '',
  monthLabel: 'Bulan',
  monthAll: 'Semua',
  applyButton: 'Terapkan',
  resetButton: 'Atur Ulang',
  emptyMessage: 'Belum ada arsip untuk filter yang dipilih.',
  periodColumn: 'Periode',
  typeColumn: 'Jenis ASN',
  skpdColumn: 'SKPD',
  npwpColumn: 'NPWP Pemotong',
  tkuColumn: 'ID TKU',
  kodeObjekColumn: 'Kode Objek',
  entryCountColumn: 'Jumlah Data',
  totalGrossColumn: 'Total Penghasilan',
  createdByColumn: 'Dibuat Oleh',
  updatedAtColumn: 'Diperbarui',
  actionsColumn: 'Aksi',
};

const texts = computed(() => ({
  ...defaultTexts,
  ...(props.texts || {}),
}));

const filterState = reactive({
  type: props.filters.type ?? '',
  year: props.filters.year ?? '',
  month: props.filters.month ?? '',
});

const formatNumber = (value) => {
  const number = Number.isFinite(value) ? value : parseFloat(value) || 0;
  return new Intl.NumberFormat('id-ID').format(number);
};

const formatCurrency = (value) => {
  const amount = Number.isFinite(value) ? value : parseFloat(value) || 0;
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 2,
  })
    .format(amount)
    .replace('IDR', 'Rp')
    .trim();
};

const buildQuery = () => {
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

  return params.toString();
};

const applyFilters = () => {
  const query = buildQuery();
  const targetUrl = query ? `${props.routes.index}?${query}` : props.routes.index;
  window.location.href = targetUrl;
};

const resetFilters = () => {
  window.location.href = props.routes.index;
};

const navigate = (url) => {
  if (!url) {
    return;
  }
  window.location.href = url;
};

const rowNumber = (index) => {
  const start = props.pagination?.from ?? 1;
  return start + index;
};
</script>
