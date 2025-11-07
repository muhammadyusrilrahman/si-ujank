<template>
  <div :class="['wrapper', { 'sidebar-collapse': ui.sidebarCollapsed }]">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
      <ul class="navbar-nav">
        <li class="nav-item">
          <button
            type="button"
            class="nav-link btn btn-link p-0"
            @click="toggleSidebar"
          >
            <i class="fas fa-bars"></i>
          </button>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
          <a :href="routes.dashboard" class="nav-link">Dashboard</a>
        </li>
      </ul>
      <ul class="navbar-nav ml-auto align-items-center">
        <li v-if="user.name" class="nav-item mr-3 text-sm text-muted">
          <i class="fas fa-user mr-1"></i>{{ user.name }}
        </li>
        <li v-if="user.skpd" class="nav-item mr-3 text-sm text-muted">
          <i class="fas fa-building mr-1"></i>{{ user.skpd }}
        </li>
        <li class="nav-item">
          <button
            type="button"
            class="nav-link btn btn-link p-0"
            @click="toggleFullscreen"
          >
            <i class="fas fa-expand-arrows-alt"></i>
          </button>
        </li>
        <li v-if="routes.logout" class="nav-item">
          <form ref="logoutForm" :action="routes.logout" method="POST" class="d-inline">
            <input type="hidden" name="_token" :value="csrfToken">
            <button type="submit" class="btn btn-link nav-link" style="padding: 0 10px;">
              <i class="fas fa-sign-out-alt"></i> Keluar
            </button>
          </form>
        </li>
      </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
      <a :href="routes.dashboard" class="brand-link d-flex align-items-center">
        <img
          :src="app.logo"
          :alt="app.name"
          style="height: 56px; width: auto; margin-right: 0.75rem;"
        >
        <span class="brand-text font-weight-light">{{ app.name }}</span>
      </a>
      <div class="sidebar">
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
            <li v-for="item in navItems" :key="item.key" class="nav-item" :class="itemClasses(item)">
              <template v-if="item.children && item.children.length">
                <a href="#" class="nav-link" :class="{ active: item.active }" @click.prevent="toggleTree(item.key)">
                  <i class="nav-icon" :class="item.icon"></i>
                  <p>
                    {{ item.label }}
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview" v-show="item.expanded">
                  <li
                    v-for="child in item.children"
                    :key="child.key"
                    class="nav-item"
                  >
                    <a :href="child.url" class="nav-link" :class="{ active: child.active }">
                      <i class="far fa-circle nav-icon"></i>
                      <p>{{ child.label }}</p>
                    </a>
                  </li>
                </ul>
              </template>
              <template v-else>
                <a :href="item.url" class="nav-link" :class="{ active: item.active }">
                  <i class="nav-icon" :class="item.icon"></i>
                  <p>{{ item.label }}</p>
                </a>
              </template>
            </li>
          </ul>
        </nav>
      </div>
    </aside>

    <div class="content-wrapper">
      <div v-if="page.title || page.breadcrumbHtml" class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1 class="m-0">{{ page.title || 'Dashboard' }}</h1>
            </div>
            <div class="col-sm-6" v-if="page.breadcrumbHtml" v-html="page.breadcrumbHtml"></div>
          </div>
        </div>
      </div>
      <section class="content">
        <div class="container-fluid">
          <div v-if="flashes.length" class="mb-3">
            <transition-group name="flash" tag="div">
              <div
                v-for="flash in flashes"
                :key="flash.id"
                class="alert alert-dismissible fade show"
                :class="flashClass(flash)"
                role="alert"
              >
                <div v-html="flash.message"></div>
                <button type="button" class="btn-close" @click="dismissFlash(flash.id)" aria-label="Close"></button>
              </div>
            </transition-group>
          </div>
          <div v-html="page.contentHtml"></div>
        </div>
      </section>
    </div>

    <footer class="main-footer">
      <div class="float-right d-none d-sm-inline">{{ app.version }}</div>
      <strong>&copy; {{ currentYear }} {{ app.name }}.</strong> All rights reserved.
    </footer>

    <div
      class="loading-overlay"
      :class="{ active: overlay.active }"
      role="status"
      aria-live="polite"
      aria-label="Memuat halaman"
    >
      <div class="text-center">
        <div class="spinner"></div>
        <div class="loading-text">Memuat...</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { useGlobalStore } from '../stores/globalStore';

const props = defineProps({
  app: {
    type: Object,
    default: () => ({
      name: 'SI-UJANK',
      logo: '/SI-UJANK.png',
      version: 'Version 1.0',
    }),
  },
  user: {
    type: Object,
    default: () => ({
      name: '',
      skpd: '',
      isSuperAdmin: false,
      isAdminUnit: false,
    }),
  },
  navigation: {
    type: Array,
    default: () => [],
  },
  page: {
    type: Object,
    default: () => ({
      title: '',
      breadcrumbHtml: '',
      contentHtml: '',
    }),
  },
  flashes: {
    type: Array,
    default: () => [],
  },
  routes: {
    type: Object,
    default: () => ({
      dashboard: '/',
      logout: '',
    }),
  },
  csrfToken: {
    type: String,
    default: '',
  },
});

const store = useGlobalStore();
store.initializeFlashes(props.flashes ?? []);

const flashes = computed(() => store.state.flashes);

const overlay = reactive({
  active: false,
});

const ui = reactive({
  sidebarCollapsed: false,
});

const expandedKeys = ref(
  new Set(
    (props.navigation ?? [])
      .filter((item) => item.expanded)
      .map((item) => item.key)
  )
);

const navItems = computed(() =>
  (props.navigation ?? []).map((item) => ({
    ...item,
    expanded: expandedKeys.value.has(item.key),
  }))
);

const logoutForm = ref(null);
const currentYear = new Date().getFullYear();

const itemClasses = (item) => {
  if (item.children && item.children.length) {
    return {
      'has-treeview': true,
      'menu-open': item.expanded,
    };
  }
  return {};
};

const toggleTree = (key) => {
  if (!key) {
    return;
  }
  const next = new Set(expandedKeys.value);
  if (next.has(key)) {
    next.delete(key);
  } else {
    next.add(key);
  }
  expandedKeys.value = next;
};

const toggleSidebar = () => {
  ui.sidebarCollapsed = !ui.sidebarCollapsed;
  document.body.classList.toggle('sidebar-collapse', ui.sidebarCollapsed);
};

const toggleFullscreen = () => {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen()?.catch(() => {});
  } else {
    document.exitFullscreen()?.catch(() => {});
  }
};

const flashClass = (flash) => {
  const mapping = {
    success: 'alert-success',
    error: 'alert-danger',
    danger: 'alert-danger',
    warning: 'alert-warning',
    info: 'alert-info',
  };
  return mapping[flash.type] ?? 'alert-info';
};

const dismissFlash = (id) => {
  store.removeFlash(id);
};

const showOverlay = () => {
  overlay.active = true;
};

const hideOverlay = () => {
  overlay.active = false;
};

const shouldSkipLoader = (element) => {
  if (!element) {
    return false;
  }

  if (element.dataset?.noLoader === 'true') {
    return true;
  }

  if (typeof element.hasAttribute === 'function' && element.hasAttribute('download')) {
    return true;
  }

  const matchKeywords = (value) => {
    if (!value || typeof value !== 'string') {
      return false;
    }
    const normalized = value.toLowerCase();
    return ['download', 'export', 'import'].some((keyword) => normalized.includes(keyword));
  };

  if (typeof element.getAttribute === 'function') {
    const href = element.getAttribute('href');
    if (matchKeywords(href)) {
      return true;
    }

    const action = element.getAttribute('action');
    if (matchKeywords(action)) {
      return true;
    }
  }

  return false;
};

const handleClick = (event) => {
  const link = event.target.closest('a');
  if (!link) {
    return;
  }
  if (event.defaultPrevented) {
    return;
  }
  if (link.target && link.target !== '_self') {
    return;
  }
  if (link.hasAttribute('download') || link.getAttribute('href') === null) {
    return;
  }
  const href = link.getAttribute('href');
  if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
    return;
  }
  if (shouldSkipLoader(link)) {
    return;
  }
  showOverlay();
};

const handleSubmit = (event) => {
  const form = event.target;
  if (shouldSkipLoader(form)) {
    return;
  }
  showOverlay();
};

const listeners = [];

const registerListener = (target, event, handler, options) => {
  target.addEventListener(event, handler, options);
  listeners.push(() => target.removeEventListener(event, handler, options));
};

onMounted(() => {
  hideOverlay();
  document.documentElement.classList.add('vue-app-loaded');
  document.body.classList.toggle('sidebar-collapse', ui.sidebarCollapsed);
  registerListener(window, 'pageshow', hideOverlay);
  registerListener(document, 'DOMContentLoaded', hideOverlay, { once: true });
  registerListener(window, 'beforeunload', () => showOverlay());
  registerListener(document, 'click', handleClick);
  registerListener(document, 'submit', handleSubmit);
});

onBeforeUnmount(() => {
  document.documentElement.classList.remove('vue-app-loaded');
  listeners.forEach((unsubscribe) => unsubscribe());
  listeners.length = 0;
});
</script>

<style scoped>
.loading-overlay {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(2px);
  z-index: 2000;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.2s ease-in-out;
  visibility: hidden;
}

.loading-overlay.active {
  opacity: 1;
  pointer-events: all;
  visibility: visible;
}

.loading-overlay .spinner {
  width: 3.5rem;
  height: 3.5rem;
  border: 0.4rem solid rgba(255, 255, 255, 0.28);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: loader-spin 0.8s linear infinite;
}

.loading-overlay .loading-text {
  margin-top: 1.25rem;
  font-weight: 600;
  color: #ffffff;
  letter-spacing: 0.08em;
}

@keyframes loader-spin {
  to {
    transform: rotate(360deg);
  }
}

.flash-enter-active,
.flash-leave-active {
  transition: all 0.2s ease;
}

.flash-enter-from,
.flash-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
