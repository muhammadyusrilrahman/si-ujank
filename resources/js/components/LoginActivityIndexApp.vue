<template>
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title mb-0">
        <i class="fas fa-history mr-2"></i>
        {{ texts.title }}
      </h3>
      <span class="badge badge-light">
        {{ texts.totalLabel }}: {{ total }}
      </span>
    </div>

    <div class="card-body p-0">
      <div v-if="!items.length" class="p-4 text-center text-muted">
        {{ texts.emptyMessage }}
      </div>
      <div v-else class="table-responsive">
        <table class="table table-striped table-hover mb-0">
          <thead>
            <tr>
              <th v-if="isSuperAdmin" style="width: 25%;">
                {{ texts.userColumn }}
              </th>
              <th style="width: 25%;">
                {{ texts.ipColumn }}
              </th>
              <th class="d-none d-lg-table-cell">
                {{ texts.deviceColumn }}
              </th>
              <th style="width: 20%;">
                {{ texts.timeColumn }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="item.id">
              <td v-if="isSuperAdmin">
                {{ item.userName }}
              </td>
              <td>
                <span class="font-weight-semibold">
                  {{ item.ipAddress }}
                </span>
              </td>
              <td class="d-none d-lg-table-cell">
                {{ item.userAgent ?? texts.unknownDevice }}
              </td>
              <td>
                <span class="d-block">{{ item.timestamp }}</span>
                <small class="text-muted">{{ item.relativeTime }}</small>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="hasPagination" class="card-footer">
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
import { computed } from 'vue';

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  total: {
    type: Number,
    default: 0,
  },
  isSuperAdmin: {
    type: Boolean,
    default: false,
  },
  pagination: {
    type: Object,
    default: () => ({
      links: [],
    }),
  },
  routes: {
    type: Object,
    default: () => ({
      index: '',
    }),
  },
  texts: {
    type: Object,
    default: () => ({}),
  },
});

const defaultTexts = {
  title: 'Daftar Histori Login',
  totalLabel: 'Total',
  emptyMessage: 'Belum ada histori login yang tercatat.',
  userColumn: 'Pengguna',
  ipColumn: 'Alamat IP',
  deviceColumn: 'Perangkat',
  timeColumn: 'Waktu',
  unknownDevice: 'Perangkat tidak tercatat',
};

const texts = computed(() => ({
  ...defaultTexts,
  ...(props.texts || {}),
}));

const hasPagination = computed(() => {
  return Array.isArray(props.pagination?.links) && props.pagination.links.length > 0;
});

const navigate = (url) => {
  if (!url) {
    return;
  }

  window.location.href = url;
};
</script>
