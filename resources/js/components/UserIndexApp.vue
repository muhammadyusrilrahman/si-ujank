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

      <template v-if="permissions.canCreate && routes.create">
        <a :href="routes.create" class="btn btn-primary">
          <i class="fas fa-plus"></i>
          <span class="ml-1">{{ texts.createButton }}</span>
        </a>
      </template>
    </div>

    <div class="card-body p-0">
      <div v-if="statusMessage" class="alert alert-success m-3 mb-0">
        {{ statusMessage }}
      </div>
      <div v-if="errorMessage" class="alert alert-danger m-3 mb-0">
        {{ errorMessage }}
      </div>

      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th style="width: 60px">#</th>
              <th>{{ texts.nameColumn }}</th>
              <th>{{ texts.usernameColumn }}</th>
              <th>{{ texts.emailColumn }}</th>
              <th>{{ texts.skpdColumn }}</th>
              <th>{{ texts.roleColumn }}</th>
              <th style="width: 170px" class="text-right">
                {{ texts.actionsColumn }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!items.length">
              <td colspan="7" class="text-center text-muted py-4">
                {{ texts.emptyMessage }}
              </td>
            </tr>
            <tr v-for="(item, index) in items" :key="item.id ?? index">
              <td>{{ rowNumber(index) }}</td>
              <td>{{ item.name }}</td>
              <td>{{ item.username }}</td>
              <td>{{ item.email }}</td>
              <td>{{ item.skpd ?? '-' }}</td>
              <td>
                <span class="badge" :class="roleBadgeClass(item)">
                  {{ item.role?.label ?? texts.roleUnknown }}
                </span>
              </td>
              <td class="text-right text-nowrap">
                <template v-if="item.can_edit && item.edit_url">
                  <a :href="item.edit_url" class="btn btn-sm btn-warning mr-1">
                    <i class="fas fa-edit"></i>
                  </a>
                </template>
                <template v-if="item.can_delete && item.delete_url">
                  <button
                    type="button"
                    class="btn btn-sm btn-danger"
                    @click="confirmDelete(item)"
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

    <div class="card-footer" v-if="hasPagination">
      <nav aria-label="Pagination">
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
import { computed, ref } from 'vue';

const props = defineProps({
  searchQuery: {
    type: String,
    default: '',
  },
  statusMessage: {
    type: String,
    default: null,
  },
  errorMessage: {
    type: String,
    default: null,
  },
  csrfToken: {
    type: String,
    default: '',
  },
  routes: {
    type: Object,
    default: () => ({
      index: '',
      create: '',
    }),
  },
  permissions: {
    type: Object,
    default: () => ({
      canCreate: false,
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
  texts: {
    type: Object,
    default: () => ({}),
  },
});

const defaultTexts = {
  searchPlaceholder: 'Cari nama, username, atau email',
  searchButton: 'Cari',
  createButton: 'Tambah Pengguna',
  nameColumn: 'Nama',
  usernameColumn: 'Username',
  emailColumn: 'Email',
  skpdColumn: 'SKPD',
  roleColumn: 'Peran',
  roleUnknown: 'Tidak diketahui',
  actionsColumn: 'Aksi',
  emptyMessage: 'Belum ada data pengguna.',
  deleteConfirm: 'Hapus pengguna ini?',
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

const navigate = (url) => {
  if (!url) {
    return;
  }

  window.location.href = url;
};

const confirmDelete = (item) => {
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

const roleBadgeClass = (item) => {
  const variant = item?.role?.variant;
  return variant ? `badge-${variant}` : 'badge-secondary';
};
</script>
