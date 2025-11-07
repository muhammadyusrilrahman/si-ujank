<template>
  <div>
    <div v-if="statusMessage" class="alert alert-success alert-dismissible fade show" role="alert">
      <span>{{ statusMessage }}</span>
      <button type="button" class="close" data-dismiss="alert" aria-label="Close" @click="dismissStatus">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>

    <div v-if="errorMessages.length" class="alert alert-danger small mb-3" role="alert">
      <ul class="mb-0 pl-3">
        <li v-for="(message, index) in errorMessages" :key="index">
          {{ message }}
        </li>
      </ul>
    </div>

    <div class="form-group">
      <label for="skpd-name">Nama SKPD / Instansi</label>
      <input
        id="skpd-name"
        type="text"
        name="name"
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
      <label for="skpd-alias">
        Alias / Singkatan
        <span class="text-muted small">(Opsional)</span>
      </label>
      <input
        id="skpd-alias"
        type="text"
        name="alias"
        class="form-control"
        :class="fieldInvalidClass('alias')"
        v-model.trim="form.alias"
      >
      <div v-if="fieldError('alias')" class="invalid-feedback">
        {{ fieldError('alias') }}
      </div>
    </div>

    <div class="form-group">
      <label for="skpd-npwp">
        NPWP Instansi
        <span class="text-muted small">(Opsional)</span>
      </label>
      <input
        id="skpd-npwp"
        type="text"
        name="npwp"
        class="form-control"
        :class="fieldInvalidClass('npwp')"
        maxlength="25"
        placeholder="99.999.999.9-999.999"
        v-model.trim="form.npwp"
      >
      <div v-if="fieldError('npwp')" class="invalid-feedback">
        {{ fieldError('npwp') }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
  old: { type: Object, default: () => ({}) },
  status: { type: String, default: null },
  errors: { type: Object, default: () => ({}) },
});

const form = reactive({
  name: props.old?.name ?? '',
  alias: props.old?.alias ?? '',
  npwp: props.old?.npwp ?? '',
});

const internalStatus = ref(props.status ?? null);

watch(
  () => props.status,
  (value) => {
    internalStatus.value = value ?? null;
  }
);

const statusMessage = computed(() =>
  internalStatus.value && internalStatus.value.length ? internalStatus.value : null
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

const dismissStatus = () => {
  internalStatus.value = null;
};
</script>
