<template>
  <div>
    <div class="form-group">
      <label for="video-tutorial-title">Judul</label>
      <input
        id="video-tutorial-title"
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
      <label for="video-tutorial-url">Tautan Video</label>
      <input
        id="video-tutorial-url"
        name="video_url"
        type="url"
        class="form-control"
        :class="fieldError('video_url') ? 'is-invalid' : ''"
        v-model.trim="form.videoUrl"
        required
      >
      <small class="form-text text-muted">
        Masukkan URL video (YouTube, Vimeo, atau platform internal).
      </small>
      <div v-if="fieldError('video_url')" class="invalid-feedback">
        {{ fieldError('video_url') }}
      </div>
    </div>

    <div class="form-group">
      <label for="video-tutorial-description">Deskripsi</label>
      <textarea
        id="video-tutorial-description"
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
        id="video-tutorial-active"
        type="checkbox"
        class="form-check-input"
        :checked="form.isActive"
        @change="toggleActive($event)"
      >
      <label class="form-check-label" for="video-tutorial-active">
        Aktifkan video tutorial
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
      video_url: '',
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
  videoUrl: props.fields?.video_url ?? '',
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
