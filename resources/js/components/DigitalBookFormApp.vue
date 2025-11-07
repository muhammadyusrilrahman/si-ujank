<template>
  <div>
    <div class="form-group">
      <label for="digital-book-title">Judul</label>
      <input
        id="digital-book-title"
        name="title"
        type="text"
        class="form-control"
        :class="fieldError('title') ? 'is-invalid' : ''"
        v-model.trim="form.title"
        required
      >
      <div v-if="fieldError('title')" class="invalid-feedback">
        {{ fieldError('title') }}
      </div>
    </div>

    <div class="form-group">
      <label for="digital-book-url">Tautan Buku Digital</label>
      <input
        id="digital-book-url"
        name="file_url"
        type="url"
        class="form-control"
        :class="fieldError('file_url') ? 'is-invalid' : ''"
        v-model.trim="form.fileUrl"
        required
      >
      <small class="form-text text-muted">
        Masukkan URL file (Google Drive, OneDrive, atau repositori lain).
      </small>
      <div v-if="fieldError('file_url')" class="invalid-feedback">
        {{ fieldError('file_url') }}
      </div>
    </div>

    <div class="form-group">
      <label for="digital-book-description">Deskripsi</label>
      <textarea
        id="digital-book-description"
        name="description"
        rows="4"
        class="form-control"
        :class="fieldError('description') ? 'is-invalid' : ''"
        v-model="form.description"
      ></textarea>
      <div v-if="fieldError('description')" class="invalid-feedback">
        {{ fieldError('description') }}
      </div>
    </div>

    <div class="form-group form-check">
      <input type="hidden" name="is_active" :value="form.isActive ? '1' : '0'">
      <input
        id="digital-book-active"
        type="checkbox"
        class="form-check-input"
        :checked="form.isActive"
        @change="toggleActive($event)"
      >
      <label class="form-check-label" for="digital-book-active">
        Aktifkan buku digital
      </label>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue';

const props = defineProps({
  fields: {
    type: Object,
    default: () => ({
      title: '',
      file_url: '',
      description: '',
      is_active: true,
    }),
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
});

const normalizeActive = (value) => {
  if (value === null || value === undefined || value === '') {
    return true;
  }

  if (typeof value === 'string') {
    return value === '1' || value.toLowerCase() === 'true';
  }

  if (typeof value === 'number') {
    return value === 1;
  }

  return Boolean(value);
};

const form = reactive({
  title: props.fields?.title ?? '',
  fileUrl: props.fields?.file_url ?? '',
  description: props.fields?.description ?? '',
  isActive: normalizeActive(props.fields?.is_active),
});

const fieldError = (name) => {
  const messages = props.errors?.[name];
  if (!messages || !Array.isArray(messages) || messages.length === 0) {
    return null;
  }

  const first = messages[0];
  return first === null || first === undefined ? null : String(first);
};

const toggleActive = (event) => {
  form.isActive = event.target.checked;
};
</script>
