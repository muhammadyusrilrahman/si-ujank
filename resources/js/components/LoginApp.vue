<template>
  <div class="card card-outline card-primary login-card">
    <div class="logo-side">
      <div class="login-logo">
        <a :href="routes.home" class="logo-container">
          <img :src="assets.logo" :alt="appName">
        </a>
      </div>
    </div>

    <div class="form-side">
      <div class="login-form-wrapper">
        <div class="text-center mb-4">
          <h3 class="h4 mb-2">Silakan Masuk</h3>
          <p class="login-box-msg mb-0">
            Masukkan kredensial Anda untuk mengakses dashboard.
          </p>
        </div>

        <div v-if="errorMessages.length" class="alert alert-danger small mb-4" role="alert">
          <ul class="mb-0 pl-3">
            <li v-for="(message, index) in errorMessages" :key="index">
              {{ message }}
            </li>
          </ul>
        </div>

        <form :action="routes.login" method="POST" novalidate @submit="handleSubmit">
          <input type="hidden" name="_token" :value="csrfToken">

          <div class="form-group">
            <label for="username">Nama Pengguna</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
              </div>
              <input
                id="username"
                name="username"
                type="text"
                class="form-control"
                :class="fieldInvalidClass('username')"
                placeholder="Masukkan username"
                autocomplete="username"
                required
                v-model.trim="form.username"
              >
            </div>
            <div v-if="fieldError('username')" class="invalid-feedback d-block">
              {{ fieldError('username') }}
            </div>
          </div>

          <div class="form-group">
            <label for="password">Kata Sandi</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
              </div>
              <input
                id="password"
                name="password"
                type="password"
                class="form-control"
                :class="fieldInvalidClass('password')"
                placeholder="Masukkan kata sandi"
                autocomplete="current-password"
                required
                v-model="form.password"
              >
            </div>
            <div v-if="fieldError('password')" class="invalid-feedback d-block">
              {{ fieldError('password') }}
            </div>
          </div>

          <div class="form-group">
            <label for="skpd-display">SKPD / Instansi</label>
            <div class="dropdown" ref="skpdContainerRef">
              <div class="input-group">
                <input type="hidden" name="skpd_id" :value="form.skpdId ?? ''">
                <input
                  id="skpd-display"
                  type="text"
                  class="form-control"
                  :class="fieldInvalidClass('skpd_id')"
                  placeholder="Pilih SKPD / Instansi"
                  :value="selectedSkpdName"
                  readonly
                  role="combobox"
                  aria-haspopup="listbox"
                  :aria-expanded="skpdDropdownOpen ? 'true' : 'false'"
                  @click="toggleSkpdDropdown()"
                  @keydown.enter.prevent="toggleSkpdDropdown(true)"
                >
                <div class="input-group-append">
                  <span class="input-group-text"><i class="fas fa-building"></i></span>
                </div>
              </div>

              <div
                class="dropdown-menu w-100 p-0"
                :class="{ show: skpdDropdownOpen }"
                role="listbox"
              >
                <div class="p-2 border-bottom">
                  <input
                    ref="skpdSearchRef"
                    type="text"
                    class="form-control"
                    placeholder="Cari SKPD / Instansi"
                    v-model="skpdSearch"
                  >
                </div>
                <div
                  class="list-group list-group-flush"
                  style="max-height: 220px; overflow-y: auto;"
                >
                  <button
                    v-for="option in filteredSkpdOptions"
                    :key="option.id"
                    type="button"
                    class="list-group-item list-group-item-action"
                    @click="selectSkpd(option)"
                  >
                    {{ option.name }}
                  </button>
                  <div v-if="!filteredSkpdOptions.length" class="p-3 text-center text-muted small">
                    SKPD tidak ditemukan.
                  </div>
                </div>
              </div>
            </div>
            <div v-if="fieldError('skpd_id')" class="invalid-feedback d-block">
              {{ fieldError('skpd_id') }}
            </div>
          </div>

          <div class="form-group">
            <label class="d-block" for="captcha-input">Captcha</label>
            <div class="d-flex align-items-center mb-2">
              <img
                :src="captchaSrc"
                alt="Captcha"
                class="img-fluid border rounded"
                style="height: 56px; width: 180px; object-fit: cover;"
                @click="refreshCaptcha"
              >
              <button
                class="btn btn-outline-secondary ml-2"
                type="button"
                aria-label="Refresh captcha"
                @click="refreshCaptcha"
                :disabled="isSubmitting"
              >
                <i class="fas fa-sync"></i>
              </button>
            </div>
              <input
                id="captcha-input"
                name="captcha"
                type="text"
                class="form-control"
                :class="fieldInvalidClass('captcha')"
                placeholder="Masukkan kode keamanan"
                required
                autocomplete="off"
                v-model.trim="form.captcha"
              >
            <div v-if="fieldError('captcha')" class="invalid-feedback d-block">
              {{ fieldError('captcha') }}
            </div>
          </div>

          <div class="row">
            <div class="col-12">
              <button type="submit" class="btn btn-primary btn-block" :disabled="isSubmitting">
                <i class="fas fa-sign-in-alt mr-1"></i>
                {{ isSubmitting ? 'Memproses...' : 'Masuk Dashboard' }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue';

const props = defineProps({
  csrfToken: { type: String, required: true },
  routes: {
    type: Object,
    required: true,
    default: () => ({
      login: '#',
      captcha: '#',
      home: '/',
    }),
  },
  assets: {
    type: Object,
    default: () => ({
      logo: '',
    }),
  },
  appName: { type: String, default: 'SI-UJANK' },
  skpdOptions: { type: Array, default: () => [] },
  old: { type: Object, default: () => ({}) },
  errors: { type: Object, default: () => ({}) },
});

const csrfToken = computed(() => props.csrfToken);
const routes = computed(() => ({
  home: props.routes?.home ?? '/',
  login: props.routes?.login ?? '/login',
  captcha: props.routes?.captcha ?? '/captcha',
}));
const assets = computed(() => ({
  logo: props.assets?.logo ?? '',
}));
const appName = computed(() => props.appName ?? 'SI-UJANK');

const form = reactive({
  username: props.old?.username ?? '',
  password: '',
  captcha: '',
  skpdId: props.old?.skpd_id ?? '',
});

const skpdDropdownOpen = ref(false);
const skpdSearch = ref('');
const isSubmitting = ref(false);
const skpdContainerRef = ref(null);
const skpdSearchRef = ref(null);

const selectedSkpdName = computed(() => {
  if (!form.skpdId) {
    return props.old?.skpd_name ?? '';
  }

  const option = props.skpdOptions?.find((item) => String(item.id) === String(form.skpdId));
  return option ? option.name : props.old?.skpd_name ?? '';
});

const filteredSkpdOptions = computed(() => {
  const query = skpdSearch.value.trim().toLowerCase();
  if (!query) {
    return props.skpdOptions ?? [];
  }

  return (props.skpdOptions ?? []).filter((option) =>
    option.name.toLowerCase().includes(query)
  );
});

const errorMessages = computed(() => {
  const collection = props.errors ?? {};
  return Object.values(collection).flat().filter(Boolean);
});

const fieldError = (name) => {
  const fieldMessages = props.errors?.[name] ?? [];
  return fieldMessages.length ? fieldMessages[0] : null;
};

const fieldInvalidClass = (name) => (fieldError(name) ? 'is-invalid' : '');

const captchaSrc = ref(routes.value.captcha || '/captcha');

const refreshCaptcha = () => {
  const url = new URL(routes.value.captcha, window.location.origin);
  url.searchParams.set('t', Date.now().toString());
  captchaSrc.value = url.href;
};

const closeSkpdDropdown = () => {
  skpdDropdownOpen.value = false;
};

const openSkpdDropdown = () => {
  if (isSubmitting.value) {
    return;
  }
  skpdDropdownOpen.value = true;
  nextTick(() => {
    skpdSearchRef.value?.focus({ preventScroll: true });
    skpdSearchRef.value?.select();
  });
};

const toggleSkpdDropdown = (force) => {
  const nextState = typeof force === 'boolean' ? force : !skpdDropdownOpen.value;
  if (nextState) {
    openSkpdDropdown();
  } else {
    closeSkpdDropdown();
  }
};

const selectSkpd = (option) => {
  form.skpdId = option?.id ?? '';
  closeSkpdDropdown();
};

const handleClickOutside = (event) => {
  if (!skpdDropdownOpen.value) {
    return;
  }

  const root = skpdContainerRef.value;
  if (root && !root.contains(event.target)) {
    closeSkpdDropdown();
  }
};

const handleSubmit = () => {
  isSubmitting.value = true;
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  refreshCaptcha();
  document.documentElement.classList.add('vue-login-loaded');
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
  document.documentElement.classList.remove('vue-login-loaded');
});
</script>
