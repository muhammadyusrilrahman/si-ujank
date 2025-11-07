<template>
  <div>
    <div
      v-if="statusMessage"
      class="alert alert-success alert-dismissible fade show"
      role="alert"
    >
      <span>{{ statusMessage }}</span>
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>

    <div class="row">
      <div
        v-for="box in statsBoxes"
        :key="box.id"
        class="col-xl-4 col-md-6 col-12"
      >
        <div class="small-box" :class="box.background">
          <div class="inner">
            <h3>{{ box.value }}</h3>
            <p>{{ box.label }}</p>
          </div>
          <div class="icon">
            <i :class="box.icon"></i>
          </div>
          <a :href="box.link" class="small-box-footer">
            {{ box.linkLabel }} <i class="fas fa-arrow-circle-right"></i>
          </a>
        </div>
      </div>
    </div>

    <div class="row">
      <section class="col-lg-7 connectedSortable">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              <i class="fas fa-info-circle mr-1"></i> Tentang Aplikasi
            </h3>
          </div>
          <div class="card-body">
            <p class="text-muted mb-0">
              <strong>SI-UJANK</strong> adalah aplikasi penunjang kebutuhan penatausahaan keuangan yang mana aplikasi ini dibangun dengan konsep data preparation.
              Pengguna akan diberikan kemudahan untuk menyiapkan berkas dan data gaji, TPP, e-bupot dan sebagainya.
            </p>
          </div>
        </div>
      </section>

      <section class="col-lg-5 connectedSortable">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">
              <i class="fas fa-history mr-1"></i> Histori Masuk Aplikasi
            </h3>
            <a :href="links.loginActivitiesIndex || '#'" class="btn btn-sm btn-outline-primary">
              Lihat semua
            </a>
          </div>
          <div class="card-body">
            <template v-if="hasLoginActivities">
              <div class="mb-3 text-muted small">
                Menampilkan 3 histori login terbaru.
              </div>
              <ul class="list-group list-group-flush">
                <li
                  v-for="activity in loginActivities"
                  :key="activity.id ?? activity.timestamp"
                  class="list-group-item"
                >
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <div class="font-weight-semibold">
                        <i class="fas fa-sign-in-alt text-primary mr-2"></i>
                        {{ activity.userName }}
                      </div>
                      <small class="text-muted d-block">
                        {{ activity.ipAddress }}
                        <template v-if="activity.userAgent">
                          - {{ activity.userAgent }}
                        </template>
                      </small>
                    </div>
                    <small class="text-muted">
                      {{ activity.timestamp }}
                    </small>
                  </div>
                </li>
              </ul>
            </template>
            <div v-else class="text-center text-muted">
              Belum ada histori login yang tercatat.
            </div>
          </div>
        </div>
      </section>
    </div>

    <div class="row">
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">
              <i class="fas fa-book mr-2"></i> Buku Panduan Terbaru
            </h3>
            <a
              v-if="isSuperAdmin && links.digitalBooksIndex"
              :href="links.digitalBooksIndex"
              class="btn btn-sm btn-outline-primary"
            >
              Kelola Buku
            </a>
          </div>
          <div class="card-body p-0">
            <template v-if="hasDigitalBooks">
              <div class="list-group list-group-flush">
                <a
                  v-for="book in digitalBooks"
                  :key="book.id ?? book.title"
                  :href="book.fileUrl"
                  target="_blank"
                  rel="noopener"
                  class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
                >
                  <div>
                    <div class="font-weight-semibold">
                      {{ book.title }}
                    </div>
                    <small v-if="book.description" class="text-muted d-block">
                      {{ book.description }}
                    </small>
                  </div>
                  <span class="badge badge-primary badge-pill">
                    <i class="fas fa-external-link-alt"></i>
                  </span>
                </a>
              </div>
            </template>
            <div v-else class="p-4 text-center text-muted">
              Belum ada buku panduan aktif.
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6 mt-3 mt-lg-0">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">
              <i class="fas fa-video mr-2"></i> Video Tutorial Terbaru
            </h3>
            <a
              v-if="isSuperAdmin && links.videoTutorialsIndex"
              :href="links.videoTutorialsIndex"
              class="btn btn-sm btn-outline-primary"
            >
              Kelola Video
            </a>
          </div>
          <div class="card-body p-0">
            <template v-if="hasVideoTutorials">
              <div class="list-group list-group-flush">
                <a
                  v-for="video in videoTutorials"
                  :key="video.id ?? video.title"
                  :href="video.videoUrl"
                  target="_blank"
                  rel="noopener"
                  class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
                >
                  <div>
                    <div class="font-weight-semibold">
                      {{ video.title }}
                    </div>
                    <small v-if="video.description" class="text-muted d-block">
                      {{ video.description }}
                    </small>
                  </div>
                  <span class="badge badge-danger badge-pill">
                    <i class="fas fa-play"></i>
                  </span>
                </a>
              </div>
            </template>
            <div v-else class="p-4 text-center text-muted">
              Belum ada video tutorial aktif.
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="feedbackMode !== 'hidden'" class="row mt-3">
      <div class="col-12">
        <div class="card card-outline card-primary">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">
              <i class="fas fa-comments mr-2"></i> Feedback Aplikasi
            </h3>
            <span class="badge badge-info">
              {{ feedbackMode === 'super-admin' ? 'Super Admin' : 'Admin Unit' }}
            </span>
          </div>
          <div class="card-body">
            <template v-if="feedbackMode === 'admin-unit'">
              <p class="text-muted small mb-3">
                Sampaikan kritik dan saran Anda, feedback akan tampil dengan nama
                <strong>{{ authUserName }}</strong>.
              </p>
              <form
                v-if="adminFeedbackConfig.storeUrl"
                :action="adminFeedbackConfig.storeUrl"
                method="POST"
                class="mb-4"
              >
                <input type="hidden" name="_token" :value="feedbackCsrfToken">
                <div class="form-group mb-3">
                  <label for="feedback-message" class="font-weight-semibold">Kritik &amp; Saran</label>
                  <textarea
                    id="feedback-message"
                    name="message"
                    rows="4"
                    :class="['form-control', adminFeedbackConfig.error ? 'is-invalid' : '']"
                    maxlength="2000"
                    required
                    v-model="adminMessage"
                  ></textarea>
                  <div v-if="adminFeedbackConfig.error" class="invalid-feedback">
                    {{ adminFeedbackConfig.error }}
                  </div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="fas fa-paper-plane mr-1"></i> Kirim Feedback
                </button>
              </form>

              <h6 class="font-weight-semibold mb-3">Feedback Anda</h6>
              <template v-if="!adminFeedbackItems.length">
                <div class="text-muted">
                  {{ adminFeedbackConfig.emptyMessage }}
                </div>
              </template>
              <template v-else>
                <div v-if="adminFeedbackConfig.summary" class="mb-3 text-muted small">
                  {{ adminFeedbackConfig.summary }}
                </div>
                <div
                  v-for="item in adminFeedbackItems"
                  :key="item.id"
                  class="border rounded p-3 mb-3"
                >
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <div class="font-weight-semibold">
                        {{ authUserName }}
                        <span class="text-muted">(Anda)</span>
                      </div>
                      <small class="text-muted d-block">
                        Dikirim {{ item.createdDiff }}
                      </small>
                    </div>
                    <span class="badge" :class="`badge-${item.statusVariant}`">
                      {{ item.status }}
                    </span>
                  </div>
                  <div class="mt-3 text-dark" v-html="item.messageHtml"></div>
                  <div v-if="item.reply" class="mt-3 p-3 bg-light border rounded">
                    <div class="d-flex justify-content-between align-items-start">
                      <div class="font-weight-semibold text-success">
                        <i class="fas fa-reply mr-1"></i> Balasan Super Admin
                      </div>
                      <small class="text-muted">
                        {{ item.reply.diff }}
                      </small>
                    </div>
                    <div class="mt-2 text-dark" v-html="item.reply.bodyHtml"></div>
                  </div>
                </div>
              </template>
            </template>

            <template v-else-if="feedbackMode === 'super-admin'">
              <div class="mb-3 text-muted small">
                {{ superAdminFeedbackConfig.summary }}
              </div>
              <template v-if="!superAdminFeedbackItems.length">
                <div class="text-muted">
                  {{ superAdminFeedbackConfig.emptyMessage }}
                </div>
              </template>
              <template v-else>
                <div
                  v-for="item in superAdminFeedbackItems"
                  :key="item.id"
                  class="border rounded p-3 mb-3"
                  :class="item.isActive ? '' : 'bg-light'"
                >
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <div class="font-weight-semibold">
                        {{ item.authorName }}
                        <span v-if="item.authorSkpd" class="text-muted">({{ item.authorSkpd }})</span>
                      </div>
                      <small class="text-muted d-block">
                        Masuk {{ item.createdDiff }}
                      </small>
                    </div>
                    <span class="badge" :class="item.isActive ? 'badge-success' : 'badge-secondary'">
                      {{ item.isActive ? 'Aktif' : 'Nonaktif' }}
                    </span>
                  </div>
                  <div class="mt-3 text-dark" v-html="item.messageHtml"></div>
                  <div v-if="item.reply" class="mt-3 p-3 bg-white border rounded">
                    <div class="d-flex justify-content-between align-items-start">
                      <div class="font-weight-semibold text-success">
                        <i class="fas fa-reply mr-1"></i>
                        Balasan Terkirim oleh {{ item.reply.replierName }}
                      </div>
                      <small class="text-muted">
                        {{ item.reply.diff }}
                      </small>
                    </div>
                    <div class="mt-2 text-dark" v-html="item.reply.bodyHtml"></div>
                  </div>

                  <div class="mt-3">
                    <form
                      :action="item.routes.reply"
                      method="POST"
                      class="mb-2"
                    >
                      <input type="hidden" name="_token" :value="feedbackCsrfToken">
                      <input type="hidden" name="feedback_id" :value="item.id">
                      <label :for="`reply-${item.id}`" class="small font-weight-semibold">
                        {{ item.reply ? 'Perbarui Balasan' : 'Balas Feedback' }}
                      </label>
                      <textarea
                        :id="`reply-${item.id}`"
                        name="reply"
                        rows="3"
                        class="form-control form-control-sm"
                        :class="replyErrorId === item.id && superAdminFeedbackConfig.error ? 'is-invalid' : ''"
                        :value="replyValueFor(item)"
                      ></textarea>
                      <div
                        v-if="replyErrorId === item.id && superAdminFeedbackConfig.error"
                        class="invalid-feedback d-block"
                      >
                        {{ superAdminFeedbackConfig.error }}
                      </div>
                      <button type="submit" class="btn btn-primary btn-sm mt-2">
                        <i class="fas fa-reply mr-1"></i> Simpan Balasan
                      </button>
                    </form>
                    <form
                      :action="item.routes.toggle"
                      method="POST"
                      class="d-inline"
                    >
                      <input type="hidden" name="_token" :value="feedbackCsrfToken">
                      <input type="hidden" name="_method" value="PATCH">
                      <button type="submit" class="btn btn-outline-secondary btn-sm">
                        {{ item.isActive ? 'Nonaktifkan' : 'Aktifkan' }}
                      </button>
                    </form>
                  </div>
                </div>
              </template>
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  statusMessage: { type: String, default: null },
  stats: {
    type: Object,
    default: () => ({
      users: '0',
      pegawai_pns_cpns: '0',
      pegawai_pppk: '0',
    }),
  },
  skpdLabel: { type: String, default: 'SKPD Anda' },
  links: { type: Object, default: () => ({}) },
  digitalBooks: { type: Array, default: () => [] },
  videoTutorials: { type: Array, default: () => [] },
  loginActivities: { type: Array, default: () => [] },
  feedback: { type: Object, default: () => ({ mode: 'hidden' }) },
  authUserName: { type: String, default: '' },
  isSuperAdmin: { type: Boolean, default: false },
  isAdminUnit: { type: Boolean, default: false },
});

const statusMessage = computed(() => props.statusMessage ?? null);
const links = computed(() => props.links ?? {});
const loginActivities = computed(() => props.loginActivities ?? []);
const digitalBooks = computed(() => props.digitalBooks ?? []);
const videoTutorials = computed(() => props.videoTutorials ?? []);
const authUserName = computed(() => props.authUserName ?? '');
const isSuperAdmin = computed(() => Boolean(props.isSuperAdmin));

const statsBoxes = computed(() => [
  {
    id: 'users',
    value: props.stats?.users ?? '0',
    label: `Pengguna ${props.skpdLabel}`,
    icon: 'fas fa-users-cog',
    background: 'bg-primary',
    link: props.links?.usersIndex || '#',
    linkLabel: 'Lihat pengguna',
  },
  {
    id: 'pegawai-pns-cpns',
    value: props.stats?.pegawai_pns_cpns ?? '0',
    label: `Pegawai PNS & CPNS ${props.skpdLabel}`,
    icon: 'fas fa-id-badge',
    background: 'bg-success',
    link: props.links?.pegawaisIndex || '#',
    linkLabel: 'Kelola pegawai',
  },
  {
    id: 'pegawai-pppk',
    value: props.stats?.pegawai_pppk ?? '0',
    label: `Pegawai PPPK ${props.skpdLabel}`,
    icon: 'fas fa-user-tie',
    background: 'bg-warning',
    link: props.links?.pegawaisIndex || '#',
    linkLabel: 'Kelola pegawai',
  },
]);

const hasLoginActivities = computed(() => loginActivities.value.length > 0);
const hasDigitalBooks = computed(() => digitalBooks.value.length > 0);
const hasVideoTutorials = computed(() => videoTutorials.value.length > 0);

const feedbackConfig = computed(() => props.feedback ?? {});
const feedbackMode = computed(() => feedbackConfig.value.mode ?? 'hidden');
const feedbackCsrfToken = computed(() => feedbackConfig.value.csrfToken ?? '');
const adminFeedbackConfig = computed(() => feedbackConfig.value.adminUnit ?? {});
const superAdminFeedbackConfig = computed(() => feedbackConfig.value.superAdmin ?? {});
const adminFeedbackItems = computed(() => adminFeedbackConfig.value.items ?? []);
const superAdminFeedbackItems = computed(() => superAdminFeedbackConfig.value.items ?? []);

const replyErrorId = computed(() => {
  const rawId = superAdminFeedbackConfig.value.oldFeedbackId;

  if (rawId === undefined || rawId === null || rawId === '') {
    return null;
  }

  const numericId = Number(rawId);
  return Number.isNaN(numericId) ? null : numericId;
});

const replyValueFor = (item) => {
  if (!item) {
    return '';
  }

  if (replyErrorId.value !== null && replyErrorId.value === item.id) {
    return superAdminFeedbackConfig.value.oldReply ?? '';
  }

  return item.reply?.body ?? '';
};

const adminMessage = ref(adminFeedbackConfig.value.oldMessage ?? '');

watch(
  () => adminFeedbackConfig.value.oldMessage,
  (value) => {
    adminMessage.value = value ?? '';
  }
);
</script>
