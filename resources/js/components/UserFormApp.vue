<template>
  <div>
    <div v-if="errorMessages.length" class="alert alert-danger small mb-3" role="alert">
      <ul class="mb-0 pl-3">
        <li v-for="(message, index) in errorMessages" :key="index">
          {{ message }}
        </li>
      </ul>
    </div>

    <div class="form-group">
      <label for="name">Nama Lengkap</label>
      <input
        id="name"
        name="name"
        type="text"
        class="form-control"
        :class="fieldInvalidClass('name')"
        required
        v-model.trim="form.name"
      >
      <div v-if="fieldError('name')" class="invalid-feedback">
        {{ fieldError('name') }}
      </div>
    </div>

    <div class="form-group">
      <label for="email">Email</label>
      <input
        id="email"
        name="email"
        type="email"
        class="form-control"
        :class="fieldInvalidClass('email')"
        required
        v-model.trim="form.email"
      >
      <div v-if="fieldError('email')" class="invalid-feedback">
        {{ fieldError('email') }}
      </div>
    </div>

    <div class="form-group">
      <label for="username">Username</label>
      <input
        id="username"
        name="username"
        type="text"
        class="form-control"
        :class="fieldInvalidClass('username')"
        required
        v-model.trim="form.username"
      >
      <div v-if="fieldError('username')" class="invalid-feedback">
        {{ fieldError('username') }}
      </div>
    </div>

    <div class="form-group">
      <label for="skpd_id">
        SKPD / Instansi
        <small v-if="skpdOptionalHint" class="text-muted">
          ({{ skpdOptionalHint }})
        </small>
      </label>
      <select
        id="skpd_id"
        name="skpd_id"
        class="form-control"
        :class="fieldInvalidClass('skpd_id')"
        v-model="form.skpdId"
        :disabled="skpdDisabled"
      >
        <option value="" :disabled="skpdDisabled">
          Tidak terikat pada SKPD
        </option>
        <option
          v-for="option in skpdOptions"
          :key="option.value"
          :value="option.value"
        >
          {{ option.label }}
        </option>
      </select>
      <input
        v-if="showForcedSkpdHidden"
        type="hidden"
        name="skpd_id"
        :value="forcedSkpdId"
      >
      <div v-if="fieldError('skpd_id')" class="invalid-feedback">
        {{ fieldError('skpd_id') }}
      </div>
    </div>

    <div class="form-group">
      <label for="role">Peran Pengguna</label>
      <select
        id="role"
        name="role"
        class="form-control"
        :class="fieldInvalidClass('role')"
        v-model="form.role"
        :disabled="roleDisabled"
        required
      >
        <option value="" disabled>
          Pilih peran
        </option>
        <option
          v-for="option in roleOptions"
          :key="option.value"
          :value="option.value"
        >
          {{ option.label }}
        </option>
      </select>
      <input
        v-if="showForcedRoleHidden"
        type="hidden"
        name="role"
        :value="forcedRole"
      >
      <div v-if="fieldError('role')" class="invalid-feedback">
        {{ fieldError('role') }}
      </div>
    </div>

    <div class="form-group">
      <label for="password">
        Password
        <span v-if="passwordLabelSuffix" class="text-muted">
          {{ passwordLabelSuffix }}
        </span>
      </label>
      <input
        id="password"
        name="password"
        type="password"
        class="form-control"
        :class="fieldInvalidClass('password')"
        :required="passwordRequired"
        v-model="form.password"
      >
      <div v-if="fieldError('password')" class="invalid-feedback">
        {{ fieldError('password') }}
      </div>
    </div>

    <div class="form-group">
      <label for="password_confirmation">
        Konfirmasi Password
        <span v-if="confirmLabelSuffix" class="text-muted">
          {{ confirmLabelSuffix }}
        </span>
      </label>
      <input
        id="password_confirmation"
        name="password_confirmation"
        type="password"
        class="form-control"
        :class="fieldInvalidClass('password_confirmation')"
        :required="passwordRequired"
        v-model="form.passwordConfirmation"
      >
      <div v-if="fieldError('password_confirmation')" class="invalid-feedback">
        {{ fieldError('password_confirmation') }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';

const props = defineProps({
  mode: { type: String, default: 'create' },
  user: { type: Object, default: null },
  options: {
    type: Object,
    default: () => ({
      skpds: [],
      roles: [],
    }),
  },
  old: { type: Object, default: () => ({}) },
  permissions: {
    type: Object,
    default: () => ({
      canSelectSkpd: false,
      forcedSkpdId: null,
      canSelectRole: true,
      forcedRole: null,
    }),
  },
  errors: { type: Object, default: () => ({}) },
  messages: {
    type: Object,
    default: () => ({
      passwordLabelSuffix: '',
      passwordConfirmLabelSuffix: '',
      skpdOptionalHint: null,
    }),
  },
});

const isEdit = computed(() => props.mode === 'edit');

const toStringValue = (value) => {
  if (value === null || value === undefined) {
    return '';
  }

  return String(value);
};

const form = reactive({
  name: props.old?.name ?? props.user?.name ?? '',
  email: props.old?.email ?? props.user?.email ?? '',
  username: props.old?.username ?? props.user?.username ?? '',
  skpdId: toStringValue(
    props.old?.skpd_id ?? props.user?.skpd_id ?? ''
  ),
  role: toStringValue(props.old?.role ?? props.user?.role ?? ''),
  password: '',
  passwordConfirmation: '',
});

const skpdOptions = computed(() =>
  (props.options?.skpds ?? []).map((option) => ({
    value: toStringValue(option.id ?? option.value ?? ''),
    label: option.name ?? option.label ?? option.id ?? '',
  }))
);

const roleOptions = computed(() =>
  (props.options?.roles ?? []).map((option) => ({
    value: toStringValue(option.value ?? option),
    label: option.label ?? option.value ?? option,
  }))
);

const canSelectSkpd = computed(() => Boolean(props.permissions?.canSelectSkpd));
const forcedSkpdId = computed(() => {
  const raw = props.permissions?.forcedSkpdId;
  return raw === undefined || raw === null || raw === '' ? '' : toStringValue(raw);
});
const skpdDisabled = computed(() => !canSelectSkpd.value);
const showForcedSkpdHidden = computed(
  () => skpdDisabled.value && forcedSkpdId.value !== ''
);

const canSelectRole = computed(() => Boolean(props.permissions?.canSelectRole));
const forcedRole = computed(() => {
  const raw = props.permissions?.forcedRole;
  return raw === undefined || raw === null || raw === '' ? '' : toStringValue(raw);
});
const roleDisabled = computed(() => !canSelectRole.value);
const showForcedRoleHidden = computed(
  () => roleDisabled.value && forcedRole.value !== ''
);

const passwordRequired = computed(() => !isEdit.value);

const passwordLabelSuffix = computed(
  () => props.messages?.passwordLabelSuffix ?? ''
);
const confirmLabelSuffix = computed(
  () => props.messages?.passwordConfirmLabelSuffix ?? ''
);
const skpdOptionalHint = computed(
  () => props.messages?.skpdOptionalHint ?? null
);

watch(
  forcedSkpdId,
  (value) => {
    if (value !== '' && skpdDisabled.value) {
      form.skpdId = value;
    }
  },
  { immediate: true }
);

watch(
  forcedRole,
  (value) => {
    if (value !== '' && roleDisabled.value) {
      form.role = value;
    }
  },
  { immediate: true }
);

const errorMessages = computed(() => {
  const collections = Object.values(props.errors ?? {});

  return collections
    .flat()
    .map((message) => String(message ?? '').trim())
    .filter((message) => message.length > 0);
});

const fieldError = (name) => {
  const messages = props.errors?.[name];
  if (!messages || !Array.isArray(messages) || messages.length === 0) {
    return null;
  }

  const first = messages[0];
  return first === null || first === undefined ? null : String(first);
};

const fieldInvalidClass = (name) => (fieldError(name) ? 'is-invalid' : '');
</script>
