<template>
  <div>
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
      <ul class="nav nav-pills mb-2 mb-md-0">
        <li v-for="option in typeOptions" :key="option.value" class="nav-item">
          <a
            class="nav-link"
            :class="{ active: option.value === filters.selectedType }"
            :href="buildTypeUrl(option.value)"
          >
            {{ option.label }}
          </a>
        </li>
      </ul>

      <div class="d-flex flex-wrap gap-2 justify-content-end">
        <template v-if="filters.filtersReady">
          <a
            :href="actionUrl('export')"
            class="btn btn-success mb-2"
            data-no-loader="true"
          >
            <i class="fas fa-file-excel"></i>
            <span class="ml-1">Ekspor Excel</span>
          </a>

          <template v-if="canManage">
            <a
              :href="actionUrl('ebupotIndex')"
              class="btn btn-outline-info mb-2"
            >
              <i class="fas fa-clipboard-list"></i>
              <span class="ml-1">Arsip E-Bupot</span>
            </a>
            <a
              :href="actionUrl('ebupotCreate')"
              class="btn btn-info mb-2"
            >
              <i class="fas fa-file-export"></i>
              <span class="ml-1">Buat E-Bupot</span>
            </a>
            <button
              type="button"
              class="btn btn-danger mb-2"
              :disabled="selectedIds.length === 0"
              @click="submitBulkDelete(false)"
            >
              <i class="fas fa-trash"></i>
              <span class="ml-1">Hapus Terpilih</span>
            </button>
            <button
              type="button"
              class="btn btn-danger mb-2"
              :disabled="counts.total === 0"
              @click="submitBulkDelete(true)"
            >
              <i class="fas fa-trash-alt"></i>
              <span class="ml-1">Hapus Semua</span>
            </button>
            <a
              :href="actionUrl('template')"
              class="btn btn-outline-secondary mb-2"
              data-no-loader="true"
            >
              <i class="fas fa-download"></i>
              <span class="ml-1">Template</span>
            </a>
            <form
              :action="routes.import"
              method="POST"
              enctype="multipart/form-data"
              class="form-inline mb-2"
            >
              <input type="hidden" name="_token" :value="csrfToken">
              <input type="hidden" name="type" :value="filters.selectedType">
              <input type="hidden" name="tahun" :value="filters.year ?? ''">
              <input type="hidden" name="bulan" :value="filters.month ?? ''">

              <template v-if="filters.isSuperAdmin">
                <div class="form-group mr-2 mb-2">
                  <select
                    class="custom-select custom-select-sm"
                    name="skpd_id"
                    :value="filters.selectedSkpdId ?? ''"
                  >
                    <option value="">
                      Pilih SKPD (opsional)
                    </option>
                    <option
                      v-for="skpd in skpds"
                      :key="skpd.id"
                      :value="skpd.id"
                    >
                      {{ skpd.name }}
                    </option>
                  </select>
                </div>
              </template>

              <div class="input-group">
                <div class="custom-file">
                  <input
                    ref="importFileInput"
                    type="file"
                    name="file"
                    class="custom-file-input"
                    id="gaji-import-file"
                    accept=".xlsx"
                    required
                    @change="handleImportFileChange"
                  >
                  <label class="custom-file-label" for="gaji-import-file">
                    {{ importFileLabel }}
                  </label>
                </div>
                <div class="input-group-append">
                  <button class="btn btn-primary" type="submit">
                    <i class="fas fa-upload"></i>
                    <span class="ml-1">Import</span>
                  </button>
                </div>
              </div>
            </form>
            <a
              :href="actionUrl('create')"
              class="btn btn-primary mb-2"
            >
              <i class="fas fa-plus"></i>
              <span class="ml-1">
                {{ textConfig.createButtonPrefix }} {{ filters.selectedTypeLabel }}
              </span>
            </a>
          </template>
        </template>
        <template v-else>
          <div class="text-muted small mb-2">
            {{ textConfig.filterActionHint }}
          </div>
        </template>
      </div>
    </div>

    <div v-if="statusMessage" class="alert alert-success alert-dismissible fade show" role="alert">
      {{ statusMessage }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>

    <div
      v-if="importErrors.length"
      class="alert alert-danger alert-dismissible fade show"
      role="alert"
    >
      <div v-for="(message, index) in importErrors" :key="index">
        {{ message }}
      </div>
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>

    <div class="card mb-3">
      <div class="card-body">
        <form :action="routes.index" method="GET" class="form-inline flex-wrap gap-2">
          <input type="hidden" name="type" :value="filters.selectedType">

          <div class="form-group mr-2 mb-2">
            <label class="mr-2" for="filter-tahun">Tahun</label>
            <input
              id="filter-tahun"
              type="number"
              name="tahun"
              class="form-control"
              :value="filters.year ?? ''"
              :min="2000"
              :max="yearMax"
              required
            >
          </div>

          <div class="form-group mr-2 mb-2">
            <label class="mr-2" for="filter-bulan">Bulan</label>
            <select
              id="filter-bulan"
              name="bulan"
              class="form-control"
              required
            >
              <option value="" disabled :selected="!filters.month">
                Pilih bulan
              </option>
              <option
                v-for="option in monthOptions"
                :key="option.value"
                :value="option.value"
                :selected="String(filters.month ?? '') === String(option.value)"
              >
                {{ option.label }}
              </option>
            </select>
          </div>

          <div class="form-group mr-2 mb-2">
            <label class="mr-2" for="filter-search">Cari</label>
            <input
              id="filter-search"
              type="text"
              name="search"
              class="form-control"
              :value="filters.search ?? ''"
              placeholder="Nama atau NIP"
            >
          </div>

          <div class="form-group mr-2 mb-2">
            <label class="mr-2" for="filter-per-page">Per halaman</label>
            <select id="filter-per-page" name="per_page" class="form-control">
              <option
                v-for="option in filters.perPageOptions"
                :key="option"
                :value="option"
                :selected="Number(filters.perPage) === Number(option)"
              >
                {{ option }}
              </option>
            </select>
          </div>

          <template v-if="filters.isSuperAdmin">
            <div class="form-group mr-2 mb-2">
              <label class="mr-2" for="filter-skpd">SKPD</label>
              <select
                id="filter-skpd"
                name="skpd_id"
                class="form-control"
              >
                <option value="" :selected="!filters.selectedSkpdId">
                  Semua SKPD
                </option>
                <option
                  v-for="skpd in skpds"
                  :key="skpd.id"
                  :value="skpd.id"
                  :selected="String(filters.selectedSkpdId ?? '') === String(skpd.id)"
                >
                  {{ skpd.name }}
                </option>
              </select>
            </div>
          </template>

          <button type="submit" class="btn btn-outline-secondary mb-2">
            <i class="fas fa-filter"></i>
            <span class="ml-1">Terapkan</span>
          </button>
        </form>
      </div>
    </div>

    <template v-if="filters.filtersReady">
      <template v-if="items.length">
        <div class="card">
          <div class="card-body table-responsive">
            <table class="table table-bordered table-striped table-hover">
              <thead class="thead-light">
                <tr>
                  <th
                    v-if="canManage"
                    class="text-center"
                    style="width: 40px;"
                  >
                    <input
                      ref="selectAllRef"
                      type="checkbox"
                      :checked="isAllSelected"
                      @change="toggleSelectAll($event.target.checked)"
                    >
                  </th>
                  <th style="width: 60px;">No</th>
                  <th>Pegawai</th>
                  <th>Periode</th>
                  <th v-for="field in monetaryFields" :key="field.key">
                    {{ field.label }}
                  </th>
                  <th>{{ textConfig.totalAllowanceHeading }}</th>
                  <th>{{ textConfig.totalDeductionHeading }}</th>
                  <th>{{ textConfig.totalTransferHeading }}</th>
                  <th v-if="canManage" class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in items" :key="item.id">
                  <td v-if="canManage" class="text-center align-middle">
                    <input
                      type="checkbox"
                      class="gaji-select-checkbox"
                      :value="String(item.id)"
                      v-model="selectedIds"
                    >
                  </td>
                  <td class="align-middle">
                    {{ rowNumber(index) }}
                  </td>
                  <td class="align-middle">
                    <div class="font-weight-semibold">
                      {{ item.pegawai.name }}
                    </div>
                    <div class="text-muted small">
                      {{ item.pegawai.nip ?? '-' }}
                    </div>
                  </td>
                  <td class="align-middle">
                    {{ item.period.label }}
                  </td>
                  <td
                    v-for="field in monetaryFields"
                    :key="`${item.id}-${field.key}`"
                    class="align-middle text-right"
                  >
                    {{ formatCurrency(item.monetary[field.key] ?? 0) }}
                  </td>
                  <td class="align-middle text-right">
                    {{ formatCurrency(item.totals.allowance) }}
                  </td>
                  <td class="align-middle text-right">
                    {{ formatCurrency(item.totals.deduction) }}
                  </td>
                  <td class="align-middle text-right">
                    {{ formatCurrency(item.totals.transfer) }}
                  </td>
                  <td v-if="canManage" class="align-middle text-center">
                    <a :href="item.links.edit" class="btn btn-sm btn-warning mb-1">
                      <i class="fas fa-edit"></i>
                    </a>
                    <button
                      type="button"
                      class="btn btn-sm btn-danger"
                      @click="deleteItem(item)"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="font-weight-bold">
                  <td v-if="canManage"></td>
                  <td colspan="2">
                    Total ({{ formatNumber(counts.total) }} data)
                  </td>
                  <td></td>
                  <td
                    v-for="field in monetaryFields"
                    :key="`total-${field.key}`"
                    class="text-right"
                  >
                    {{ formatCurrency(monetaryTotals[field.key] ?? 0) }}
                  </td>
                  <td class="text-right">
                    {{ formatCurrency(summaryTotals.allowance ?? 0) }}
                  </td>
                  <td class="text-right">
                    {{ formatCurrency(summaryTotals.deduction ?? 0) }}
                  </td>
                  <td class="text-right">
                    {{ formatCurrency(summaryTotals.transfer ?? 0) }}
                  </td>
                  <td v-if="canManage"></td>
                </tr>
              </tfoot>
            </table>
          </div>
          <div
            v-if="pagination && pagination.links && pagination.links.length"
            class="card-footer bg-white"
          >
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
      </template>
      <template v-else>
        <div class="alert alert-light text-center">
          {{ textConfig.noDataMessage }}
        </div>
      </template>
    </template>

    <template v-else>
      <div class="alert alert-info">
        {{ textConfig.filtersNotReadyMessage }}
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
  typeOptions: { type: Array, default: () => [] },
  filters: {
    type: Object,
    default: () => ({
      selectedType: 'pns',
      selectedTypeLabel: 'PNS',
      year: null,
      month: null,
      search: null,
      perPage: 25,
      perPageOptions: [],
      filtersReady: false,
      defaultPerPage: 25,
      isSuperAdmin: false,
      selectedSkpdId: null,
    }),
  },
  monthOptions: { type: Array, default: () => [] },
  skpds: { type: Array, default: () => [] },
  canManage: { type: Boolean, default: false },
  items: { type: Array, default: () => [] },
  monetaryFields: { type: Array, default: () => [] },
  monetaryTotals: { type: Object, default: () => ({}) },
  summaryTotals: { type: Object, default: () => ({}) },
  counts: {
    type: Object,
    default: () => ({ total: 0, current: 0 }),
  },
  pagination: {
    type: Object,
    default: null,
  },
  routes: {
    type: Object,
    default: () => ({
      index: '',
      export: '',
      ebupotIndex: '',
      ebupotCreate: '',
      template: '',
      import: '',
      bulkDestroy: '',
      create: '',
    }),
  },
  csrfToken: { type: String, default: '' },
  statusMessage: { type: String, default: null },
  importErrors: { type: Array, default: () => [] },
  texts: {
    type: Object,
    default: () => ({}),
  },
});

const selectedIds = ref([]);
const selectAllRef = ref(null);
const importFileInput = ref(null);
const importFileLabel = ref('Pilih file...');

const yearMax = new Date().getFullYear() + 5;

const monetaryFieldKeys = computed(() =>
  props.monetaryFields.map((field) => field.key)
);

const allSelectableIds = computed(() =>
  props.items.map((item) => String(item.id))
);

const isAllSelected = computed(() => {
  return (
    selectedIds.value.length > 0 &&
    selectedIds.value.length === allSelectableIds.value.length
  );
});

const isIndeterminate = computed(() => {
  return (
    selectedIds.value.length > 0 &&
    selectedIds.value.length < allSelectableIds.value.length
  );
});

const defaultPerPage = computed(() => props.filters.defaultPerPage ?? 25);

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

const formatNumber = (value) => {
  const num = Number.isFinite(value) ? value : parseFloat(value) || 0;
  return new Intl.NumberFormat('id-ID').format(num);
};

const defaultTexts = {
  filterActionHint:
    'Pilih tahun dan bulan untuk mengakses ekspor, template, dan impor.',
  filtersNotReadyMessage:
    'Pilih tahun dan bulan untuk menampilkan data gaji.',
  noDataMessage: 'Belum ada data gaji untuk kriteria ini.',
  createButtonPrefix: 'Tambah Data Gaji',
  totalAllowanceHeading: 'Jumlah Gaji dan Tunjangan',
  totalDeductionHeading: 'Jumlah Potongan',
  totalTransferHeading: 'Jumlah Ditransfer',
  confirmDeleteItem: 'Hapus data gaji ini?',
  confirmBulkDelete: 'Hapus %count% data gaji terpilih?',
  confirmBulkDeleteAll: 'Hapus semua data gaji pada periode ini?',
};

const textConfig = computed(() => ({
  ...defaultTexts,
  ...(props.texts || {}),
}));

const buildQueryParams = (overrides = {}) => {
  const params = {
    type: props.filters.selectedType,
    tahun: props.filters.year,
    bulan: props.filters.month,
    per_page:
      Number(props.filters.perPage) === Number(defaultPerPage.value)
        ? null
        : props.filters.perPage,
    search: props.filters.search,
    skpd_id: props.filters.isSuperAdmin ? props.filters.selectedSkpdId : null,
    ...overrides,
  };

  const searchParams = new URLSearchParams();

  Object.entries(params).forEach(([key, value]) => {
    if (value !== null && value !== undefined && value !== '') {
      searchParams.set(key, value);
    }
  });

  return searchParams.toString();
};

const appendQuery = (base, query) => {
  const url = new URL(base, window.location.origin);
  const existingParams = new URLSearchParams(url.search);

  if (query) {
    const newParams = new URLSearchParams(query);
    newParams.forEach((value, key) => {
      existingParams.set(key, value);
    });
  }

  url.search = existingParams.toString();
  return url.toString();
};

const buildTypeUrl = (type) => {
  const query = buildQueryParams({ type });
  return appendQuery(props.routes.index, query);
};

const actionUrl = (key) => {
  const base = props.routes?.[key];
  if (!base) {
    return '#';
  }

  const query = buildQueryParams();
  return appendQuery(base, query);
};

const toggleSelectAll = (checked) => {
  if (checked) {
    selectedIds.value = [...allSelectableIds.value];
  } else {
    selectedIds.value = [];
  }
};

const rowNumber = (index) => {
  const from = props.pagination?.from ?? 1;
  return from + index;
};

const submitBulkDelete = (deleteAll) => {
  if (!props.filters.filtersReady) {
    return;
  }

  if (deleteAll) {
    if (!confirm(textConfig.value.confirmBulkDeleteAll)) {
      return;
    }
  } else {
    if (!selectedIds.value.length) {
      return;
    }
    const message = textConfig.value.confirmBulkDelete.replace(
      '%count%',
      selectedIds.value.length.toString()
    );
    if (!confirm(message)) {
      return;
    }
  }

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = props.routes.bulkDestroy;

  const append = (name, value) => {
    if (value === null || value === undefined) {
      return;
    }
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    form.appendChild(input);
  };

  append('_token', props.csrfToken);
  append('_method', 'DELETE');
  append('type', props.filters.selectedType);
  append('tahun', props.filters.year);
  append('bulan', props.filters.month);
  if (
    props.filters.perPage &&
    Number(props.filters.perPage) !== Number(defaultPerPage.value)
  ) {
    append('per_page', props.filters.perPage);
  }
  if (props.filters.search) {
    append('search', props.filters.search);
  }
  if (props.filters.isSuperAdmin && props.filters.selectedSkpdId) {
    append('skpd_id', props.filters.selectedSkpdId);
  }

  if (deleteAll) {
    append('delete_all', '1');
  } else {
    append('delete_all', '0');
    selectedIds.value.forEach((id) => {
      append('ids[]', id);
    });
  }

  document.body.appendChild(form);
  form.submit();
};

const deleteItem = (item) => {
  if (!item?.links?.destroy) {
    return;
  }

  if (!confirm(textConfig.value.confirmDeleteItem)) {
    return;
  }

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = item.links.destroy;

  const append = (name, value) => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    form.appendChild(input);
  };

  append('_token', props.csrfToken);
  append('_method', 'DELETE');
  append('type', props.filters.selectedType);
  append('tahun', props.filters.year ?? '');
  append('bulan', props.filters.month ?? '');
  if (
    props.filters.perPage &&
    Number(props.filters.perPage) !== Number(defaultPerPage.value)
  ) {
    append('per_page', props.filters.perPage);
  }
  if (props.filters.search) {
    append('search', props.filters.search);
  }
  if (props.filters.isSuperAdmin && props.filters.selectedSkpdId) {
    append('skpd_id', props.filters.selectedSkpdId);
  }

  document.body.appendChild(form);
  form.submit();
};

const navigate = (url) => {
  if (!url) {
    return;
  }
  window.location.href = url;
};

const handleImportFileChange = (event) => {
  const files = event.target?.files;
  if (files && files.length > 0) {
    importFileLabel.value = files[0].name;
  } else {
    importFileLabel.value = 'Pilih file...';
  }
};

watch([selectedIds, () => props.items.length], () => {
  if (selectAllRef.value) {
    selectAllRef.value.indeterminate = isIndeterminate.value;
  }
});

onMounted(() => {
  if (importFileInput.value && importFileInput.value.files.length > 0) {
    importFileLabel.value = importFileInput.value.files[0].name;
  }
  if (selectAllRef.value) {
    selectAllRef.value.indeterminate = isIndeterminate.value;
  }
});
</script>
