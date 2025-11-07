<template>
  <div class="card">
    <div
      class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2"
    >
      <form class="form-inline w-100 w-md-auto" @submit.prevent="handleSearch">
        <div class="input-group">
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
      <a :href="routes.create" class="btn btn-primary">
        <i class="fas fa-plus"></i>
        <span class="ml-1">{{ texts.createButton }}</span>
      </a>
    </div>
    <div class="card-body p-0">
      <div v-if="statusMessage" class="alert alert-success m-3 mb-0">
        {{ statusMessage }}
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th style="width: 60px">#</th>
              <th>{{ texts.titleColumn }}</th>
              <th>{{ texts.descriptionColumn }}</th>
              <th>{{ texts.linkColumn }}</th>
              <th>{{ texts.statusColumn }}</th>
              <th style="width: 160px" class="text-right">
                {{ texts.actionsColumn }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!items.length">
              <td colspan="6" class="text-center text-muted py-4">
                {{ texts.emptyMessage }}
              </td>
            </tr>
            <tr v-for="(item, index) in items" :key="item.id ?? index">
              <td>{{ rowNumber(index) }}</td>
              <td>{{ item.title }}</td>
              <td class="text-muted small">
                {{ truncateDescription(item.description) }}
              </td>
              <td>
                <a
                  :href="item.link_url"
                  target="_blank"
                  rel="noopener"
                  class="text-primary"
                >
                  {{ texts.linkText }}
                  <i class="fas fa-external-link-alt ml-1"></i>
                </a>
              </td>
              <td>
                <span
                  class="badge"
                  :class="item.is_active ? 'badge-success' : 'badge-secondary'"
                >
                  {{ item.is_active ? texts.statusActive : texts.statusInactive }}
                </span>
              </td>
              <td class="text-right">
                <a :href="item.edit_url" class="btn btn-sm btn-outline-primary">
                  <i class="fas fa-edit"></i>
                </a>
                <button
                  type="button"
                  class="btn btn-sm btn-outline-danger ml-1"
                  @click="submitDelete(item)"
                >
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer" v-if="hasPagination">
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

<script setup>
import { computed, reactive, ref } from 'vue';

const props = defineProps({
  searchQuery: { type: String, default: '' },
  routes: {
    type: Object,
    default: () => ({
      index: '',
      create: '',
    }),
  },
  statusMessage: { type: String, default: null },
  csrfToken: { type: String, default: '' },
  items: { type: Array, default: () => [] },
  pagination: {
    type: Object,
    default: null,
  },
  texts: {
    type: Object,
    default: () => ({}),
  },
  truncateLength: {
    type: Number,
    default: 120,
  },
});

const defaultTexts = {
  searchPlaceholder: 'Cari data',
  searchButton: 'Cari',
  createButton: 'Tambah Data',
  titleColumn: 'Judul',
  descriptionColumn: 'Deskripsi',
  linkColumn: 'Tautan',
  linkText: 'Lihat',
  statusColumn: 'Status',
  statusActive: 'Aktif',
  statusInactive: 'Nonaktif',
  actionsColumn: 'Aksi',
  emptyMessage: 'Belum ada data.',
  deleteConfirm: 'Hapus data ini?',
};

const texts = computed(() => ({
  ...defaultTexts,
  ...(props.texts || {}),
}));

const search = ref(props.searchQuery ?? '');

const hasPagination = computed(() => {
  return (
    props.pagination &&
    Array.isArray(props.pagination.links) &&
    props.pagination.links.length > 0
  );
});

const rowNumber = (index) => {
  const base = props.pagination?.from ?? 1;
  return base + index;
};

const truncateDescription = (value) => {
  if (!value) {
    return '';
  }

  if (value.length <= props.truncateLength) {
    return value;
  }

  return `${value.slice(0, props.truncateLength)}…`;
};

const handleSearch = () => {
  const params = new URLSearchParams(window.location.search);
  const query = search.value.trim();

  if (query) {
    params.set('q', query);
  } else {
    params.delete('q');
  }

  const baseUrl = props.routes.index || window.location.pathname;
  const targetUrl = params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl;
  window.location.href = targetUrl;
};

const submitDelete = (item) => {
  if (!item?.delete_url) {
    return;
  }

  if (!confirm(texts.value.deleteConfirm)) {
    return;
  }

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = item.delete_url;

  const methodInput = document.createElement('input');
  methodInput.type = 'hidden';
  methodInput.name = '_method';
  methodInput.value = 'DELETE';

  const tokenInput = document.createElement('input');
  tokenInput.type = 'hidden';
  tokenInput.name = '_token';
  tokenInput.value = props.csrfToken;

  form.appendChild(methodInput);
  form.appendChild(tokenInput);
  document.body.appendChild(form);
  form.submit();
};

const navigate = (url) => {
  if (!url) {
    return;
  }

  window.location.href = url;
};
</script>
