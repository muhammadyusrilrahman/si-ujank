<template>
  <div class="card">
    <div class="card-header">
      <div class="row align-items-center">
        <div class="col-lg-6 mb-3 mb-lg-0">
          <form class="form-inline" @submit.prevent="handleSearch">
            <input type="hidden" name="per_page" :value="perPageValue">
            <div class="input-group w-100">
              <input
                type="text"
                class="form-control"
                :placeholder="texts.searchPlaceholder"
                v-model="search"
              >
              <div class="input-group-append">
                <button type="submit" class="btn btn-outline-secondary">
                  <i class="fas fa-search"></i>
                  <span class="d-none d-md-inline ml-1">{{ texts.searchButton }}</span>
                </button>
              </div>
            </div>
          </form>
        </div>
        <div class="col-lg-6 d-flex flex-wrap gap-2 justify-content-lg-end align-items-center">
          <a
            :href="routes.export"
            class="btn btn-success mr-2 mb-2"
            data-no-loader="true"
          >
            <i class="fas fa-file-excel"></i>
            <span class="ml-1">{{ texts.exportButton }}</span>
          </a>

          <template v-if="permissions.canManage">
            <button
              type="button"
              class="btn btn-danger mr-2 mb-2"
              :disabled="isBulkDisabled"
              @click="submitBulkDelete"
            >
              <i class="fas fa-trash"></i>
              <span class="ml-1">{{ texts.bulkDeleteButton }}</span>
            </button>

            <a
              :href="routes.template"
              class="btn btn-outline-secondary mr-2 mb-2"
              data-no-loader="true"
            >
              <i class="fas fa-download"></i>
              <span class="ml-1">{{ texts.templateButton }}</span>
            </a>

            <form
              v-if="permissions.canImport"
              class="form-inline mr-2 mb-2"
              :action="routes.import"
              method="POST"
              enctype="multipart/form-data"
            >
              <input type="hidden" name="_token" :value="csrfToken">

              <div
                v-if="importConfig.showSkpdSelect"
                class="form-group mr-2 mb-2"
              >
                <select
                  name="skpd_id"
                  class="custom-select custom-select-sm"
                  v-model="importSkpdId"
                >
                  <option value="">
                    {{ texts.importSkpdPlaceholder }}
                  </option>
                  <option
                    v-for="option in importConfig.skpdOptions"
                    :key="option.id"
                    :value="option.id"
                  >
                    {{ option.name }}
                  </option>
                </select>
                <div v-if="skpdError" class="invalid-feedback d-block">
                  {{ skpdError }}
                </div>
              </div>

              <div class="input-group">
                <div class="custom-file">
                  <input
                    type="file"
                    name="file"
                    class="custom-file-input"
                    accept=".xlsx,.xls"
                    required
                    @change="handleFileChange"
                  >
                  <label class="custom-file-label">
                    {{ importFileLabel }}
                  </label>
                </div>
                <div class="input-group-append">
                  <button class="btn btn-primary" type="submit">
                    <i class="fas fa-upload"></i>
                    <span class="ml-1">{{ texts.importButton }}</span>
                  </button>
                </div>
              </div>
            </form>

            <a
              v-if="permissions.canCreate && routes.create"
              :href="routes.create"
              class="btn btn-primary mb-2"
            >
              <i class="fas fa-plus"></i>
              <span class="ml-1">{{ texts.createButton }}</span>
            </a>
          </template>
        </div>
      </div>
    </div>

    <div class="card-body p-0">
      <div v-if="statusMessage" class="alert alert-success m-3 mb-0">
        {{ statusMessage }}
      </div>

      <div v-if="importErrors.length" class="alert alert-danger m-3 mb-0">
        <div v-for="(message, idx) in importErrors" :key="idx">
          {{ message }}
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th v-if="permissions.canManage" style="width: 50px" class="text-center">
                <input
                  ref="selectAllCheckbox"
                  type="checkbox"
                  :checked="allSelected"
                  @change="toggleSelectAll"
                  aria-label="Pilih semua pegawai"
                >
              </th>
              <th v-for="column in columns" :key="column.key">
                {{ column.label }}
              </th>
              <th
                v-if="permissions.canManage"
                style="width: 160px"
              >
                {{ texts.actionsColumn }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!items.length">
              <td
                :colspan="permissions.canManage ? columns.length + 2 : columns.length + 1"
                class="text-center text-muted py-4"
              >
                {{ texts.emptyMessage }}
              </td>
            </tr>
            <tr v-for="item in items" :key="item.id">
              <td v-if="permissions.canManage" class="text-center">
                <input
                  type="checkbox"
                  class="pegawai-select-checkbox"
                  :value="item.id"
                  :checked="isSelected(item.id)"
                  @change="toggleItem(item.id, $event.target.checked)"
                >
              </td>
              <td v-for="column in columns" :key="column.key">
                {{ item.fields[column.key] ?? '-' }}
              </td>
              <td v-if="permissions.canManage">
                <template v-if="item.links.edit">
                  <a :href="item.links.edit" class="btn btn-sm btn-warning mr-1">
                    <i class="fas fa-edit"></i>
                  </a>
                </template>
                <template v-if="item.links.destroy">
                  <button
                    type="button"
                    class="btn btn-sm btn-danger"
                    @click="confirmDelete(item.links.destroy)"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </template>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card-footer bg-white">
      <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div class="text-muted small mb-2 mb-md-0">
          {{ showingText }}
        </div>
        <div class="d-flex flex-column flex-md-row align-items-md-center gap-2 w-100 w-md-auto">
          <form class="form-inline mb-2 mb-md-0" @submit.prevent="changePerPage">
            <label for="per-page-select" class="mr-2 mb-0">
              {{ texts.perPageLabel }}
            </label>
            <select
              id="per-page-select"
              class="custom-select custom-select-sm"
              v-model.number="perPageValue"
              @change="changePerPage"
            >
              <option
                v-for="option in perPageOptions"
                :key="option"
                :value="Number(option)"
              >
                {{ option }}
              </option>
            </select>
            <span class="ml-2">{{ texts.perPageSuffix }}</span>
          </form>
          <nav v-if="hasPagination" aria-label="Pagination">
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
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  searchQuery: {
    type: String,
    default: '',
  },
  perPage: {
    type: Number,
    default: 25,
  },
  perPageOptions: {
    type: Array,
    default: () => [25, 50, 100],
  },
  statusMessage: {
    type: String,
    default: null,
  },
  importErrors: {
    type: Array,
    default: () => [],
  },
  skpdError: {
    type: String,
    default: null,
  },
  routes: {
    type: Object,
    default: () => ({
      index: '',
      create: '',
      export: '',
      template: '',
      import: '',
      bulkDelete: '',
    }),
  },
  permissions: {
    type: Object,
    default: () => ({
      canManage: false,
      canCreate: false,
      canImport: false,
    }),
  },
  items: {
    type: Array,
    default: () => [],
  },
  pagination: {
    type: Object,
    default: () => ({
      from: 0,
      to: 0,
      total: 0,
      links: [],
    }),
  },
  queryParams: {
    type: Object,
    default: () => ({}),
  },
  importConfig: {
    type: Object,
    default: () => ({
      showSkpdSelect: false,
      skpdOptions: [],
      selectedSkpd: '',
    }),
  },
  csrfToken: {
    type: String,
    default: '',
  },
  columns: {
    type: Array,
    default: null,
  },
  texts: {
    type: Object,
    default: () => ({}),
  },
});

const defaultColumns = [
  { key: 'nip', label: 'NIP Pegawai' },
  { key: 'nama', label: 'Nama Pegawai' },
  { key: 'nik', label: 'NIK Pegawai' },
  { key: 'npwp', label: 'NPWP Pegawai' },
  { key: 'tanggal_lahir', label: 'Tanggal Lahir Pegawai' },
  { key: 'tipe_jabatan', label: 'Tipe Jabatan' },
  { key: 'jabatan', label: 'Nama Jabatan' },
  { key: 'eselon', label: 'Eselon' },
  { key: 'status_asn', label: 'Status ASN' },
  { key: 'golongan', label: 'Golongan' },
  { key: 'masa_kerja', label: 'Masa Kerja Golongan' },
  { key: 'alamat', label: 'Alamat' },
  { key: 'status_perkawinan', label: 'Status Pernikahan' },
  { key: 'jumlah_pasangan', label: 'Jumlah Suami/Istri' },
  { key: 'jumlah_anak', label: 'Jumlah Anak' },
  { key: 'jumlah_tanggungan', label: 'Jumlah Tanggungan' },
  { key: 'pasangan_pns', label: 'Pasangan PNS' },
  { key: 'nip_pasangan', label: 'NIP Pasangan' },
  { key: 'kode_bank', label: 'Kode Bank' },
  { key: 'nama_bank', label: 'Nama Bank' },
  { key: 'rekening', label: 'Nomor Rekening Bank Pegawai' },
];

const defaultTexts = {
  searchPlaceholder: 'Cari nama, NIK, NIP, atau jabatan',
  searchButton: 'Cari',
  exportButton: 'Ekspor Excel',
  bulkDeleteButton: 'Hapus Terpilih',
  templateButton: 'Template',
  importButton: 'Import',
  importFilePlaceholder: 'Pilih file...',
  importSkpdPlaceholder: 'Pilih SKPD (opsional)',
  createButton: 'Tambah Pegawai',
  actionsColumn: 'Aksi',
  emptyMessage: 'Belum ada data pegawai.',
  deleteConfirm: 'Hapus data pegawai ini?',
  bulkDeleteConfirm: 'Hapus semua data pegawai terpilih?',
  perPageLabel: 'Tampilkan',
  perPageSuffix: 'data',
  showingTemplate: (from, to, total) => `Menampilkan ${from} - ${to} dari ${total} data pegawai`,
};

const textMap = computed(() => ({
  ...defaultTexts,
  ...(props.texts || {}),
}));

const columns = computed(() => (props.columns && props.columns.length ? props.columns : defaultColumns));
const search = ref(props.searchQuery ?? '');
const perPageValue = ref(Number(props.perPage) || 25);
const selectedIds = ref([]);
const importSkpdId = ref(props.importConfig?.selectedSkpd ?? '');
const selectedFileName = ref('');

const importFileLabel = computed(() => selectedFileName.value || textMap.value.importFilePlaceholder);

const hasPagination = computed(() => {
  return Array.isArray(props.pagination?.links) && props.pagination.links.length > 0;
});

const showingText = computed(() => {
  const from = props.pagination?.from ?? 0;
  const to = props.pagination?.to ?? 0;
  const total = props.pagination?.total ?? 0;
  return textMap.value.showingTemplate(from || 0, to || 0, total || 0);
});

const statusMessage = computed(() => props.statusMessage);
const importErrors = computed(() => props.importErrors || []);
const skpdError = computed(() => props.skpdError);

const allSelectableIds = computed(() => props.items.map((item) => item.id));

const isBulkDisabled = computed(() => selectedIds.value.length === 0);

const allSelected = computed(() => {
  const selectedSet = new Set(selectedIds.value);
  return allSelectableIds.value.length > 0 && allSelectableIds.value.every((id) => selectedSet.has(id));
});

const isIndeterminate = computed(() => {
  const selectedSize = selectedIds.value.length;
  const total = allSelectableIds.value.length;
  return selectedSize > 0 && selectedSize < total;
});

const isSelected = (id) => selectedIds.value.includes(id);

const toggleItem = (id, checked) => {
  const next = new Set(selectedIds.value);
  if (checked) {
    next.add(id);
  } else {
    next.delete(id);
  }
  selectedIds.value = Array.from(next);
};

const toggleSelectAll = (event) => {
  if (event.target.checked) {
    selectedIds.value = Array.from(new Set([...allSelectableIds.value]));
  } else {
    selectedIds.value = [];
  }
};

const buildSearchParams = () => {
  const params = new URLSearchParams();

  Object.entries(props.queryParams || {}).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') {
      return;
    }
    params.set(key, value);
  });

  if (search.value && search.value.trim() !== '') {
    params.set('q', search.value.trim());
  } else {
    params.delete('q');
  }

  if (perPageValue.value) {
    params.set('per_page', String(perPageValue.value));
  }

  params.delete('page');

  return params;
};

const handleSearch = () => {
  const params = buildSearchParams();
  const query = params.toString();
  const targetUrl = query ? `${props.routes.index}?${query}` : props.routes.index;
  window.location.href = targetUrl;
};

const changePerPage = () => {
  const params = buildSearchParams();
  params.set('per_page', String(perPageValue.value));
  params.delete('page');
  const query = params.toString();
  const targetUrl = query ? `${props.routes.index}?${query}` : props.routes.index;
  window.location.href = targetUrl;
};

const navigate = (url) => {
  if (!url) {
    return;
  }
  window.location.href = url;
};

const confirmDelete = (destroyUrl) => {
  if (!destroyUrl) {
    return;
  }
  if (!window.confirm(textMap.value.deleteConfirm)) {
    return;
  }

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = destroyUrl;

  const tokenInput = document.createElement('input');
  tokenInput.type = 'hidden';
  tokenInput.name = '_token';
  tokenInput.value = props.csrfToken;
  form.appendChild(tokenInput);

  const methodInput = document.createElement('input');
  methodInput.type = 'hidden';
  methodInput.name = '_method';
  methodInput.value = 'DELETE';
  form.appendChild(methodInput);

  document.body.appendChild(form);
  form.submit();
};

const submitBulkDelete = () => {
  if (!props.routes.bulkDelete) {
    return;
  }

  if (!selectedIds.value.length) {
    return;
  }

  if (!window.confirm(textMap.value.bulkDeleteConfirm)) {
    return;
  }

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = props.routes.bulkDelete;

  const tokenInput = document.createElement('input');
  tokenInput.type = 'hidden';
  tokenInput.name = '_token';
  tokenInput.value = props.csrfToken;
  form.appendChild(tokenInput);

  const methodInput = document.createElement('input');
  methodInput.type = 'hidden';
  methodInput.name = '_method';
  methodInput.value = 'DELETE';
  form.appendChild(methodInput);

  selectedIds.value.forEach((id) => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'ids[]';
    input.value = id;
    form.appendChild(input);
  });

  Object.entries(props.queryParams || {}).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') {
      return;
    }
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = key;
    input.value = value;
    form.appendChild(input);
  });

  document.body.appendChild(form);
  form.submit();
};

const handleFileChange = (event) => {
  const file = event.target?.files?.[0];
  selectedFileName.value = file ? file.name : '';
};

watch(
  () => props.items,
  () => {
    selectedIds.value = [];
  },
  { deep: true }
);

const selectAllCheckbox = ref(null);

watch(
  () => props.importConfig?.selectedSkpd,
  (value) => {
    importSkpdId.value = value ?? '';
  },
  { immediate: true }
);

watch(
  [allSelected, isIndeterminate],
  () => {
    if (selectAllCheckbox.value) {
      selectAllCheckbox.value.indeterminate = isIndeterminate.value;
    }
  },
  { immediate: true }
);
</script>
