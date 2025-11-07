<template>
  <div>
    <div v-if="errorMessages.length" class="alert alert-danger small mb-3" role="alert">
      <ul class="mb-0 pl-3">
        <li v-for="(message, index) in errorMessages" :key="index">
          {{ message }}
        </li>
      </ul>
    </div>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="nama_lengkap">Nama Pegawai</label>
        <input
          id="nama_lengkap"
          name="nama_lengkap"
          type="text"
          class="form-control"
          :class="fieldInvalidClass('nama_lengkap')"
          required
          v-model.trim="form.namaLengkap"
        >
        <div v-if="fieldError('nama_lengkap')" class="invalid-feedback">
          {{ fieldError('nama_lengkap') }}
        </div>
      </div>
      <div class="form-group col-md-6">
        <label for="nik">NIK Pegawai</label>
        <input
          id="nik"
          name="nik"
          type="text"
          class="form-control"
          :class="fieldInvalidClass('nik')"
          required
          v-model.trim="form.nik"
        >
        <div v-if="fieldError('nik')" class="invalid-feedback">
          {{ fieldError('nik') }}
        </div>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-md-4">
        <label for="nip">NIP Pegawai</label>
        <input
          id="nip"
          name="nip"
          type="text"
          class="form-control"
          :class="fieldInvalidClass('nip')"
          v-model.trim="form.nip"
        >
        <div v-if="fieldError('nip')" class="invalid-feedback">
          {{ fieldError('nip') }}
        </div>
      </div>
      <div class="form-group col-md-4">
        <label for="npwp">NPWP Pegawai</label>
        <input
          id="npwp"
          name="npwp"
          type="text"
          class="form-control"
          :class="fieldInvalidClass('npwp')"
          v-model.trim="form.npwp"
        >
        <div v-if="fieldError('npwp')" class="invalid-feedback">
          {{ fieldError('npwp') }}
        </div>
      </div>
      <div class="form-group col-md-4">
        <label for="email">Email</label>
        <input
          id="email"
          name="email"
          type="email"
          class="form-control"
          :class="fieldInvalidClass('email')"
          v-model.trim="form.email"
        >
        <div v-if="fieldError('email')" class="invalid-feedback">
          {{ fieldError('email') }}
        </div>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-md-4">
        <label for="tempat_lahir">Tempat Lahir</label>
        <input
          id="tempat_lahir"
          name="tempat_lahir"
          type="text"
          class="form-control"
          :class="fieldInvalidClass('tempat_lahir')"
          required
          v-model.trim="form.tempatLahir"
        >
        <div v-if="fieldError('tempat_lahir')" class="invalid-feedback">
          {{ fieldError('tempat_lahir') }}
        </div>
      </div>
      <div class="form-group col-md-4">
        <label for="tanggal_lahir">Tanggal Lahir</label>
        <input
          id="tanggal_lahir"
          name="tanggal_lahir"
          type="date"
          class="form-control"
          :class="fieldInvalidClass('tanggal_lahir')"
          required
          v-model="form.tanggalLahir"
        >
        <div v-if="fieldError('tanggal_lahir')" class="invalid-feedback">
          {{ fieldError('tanggal_lahir') }}
        </div>
      </div>
      <div class="form-group col-md-4">
        <label for="jenis_kelamin">Jenis Kelamin</label>
        <select
          id="jenis_kelamin"
          name="jenis_kelamin"
          class="form-control"
          :class="fieldInvalidClass('jenis_kelamin')"
          required
          v-model="form.jenisKelamin"
        >
          <option value="" disabled>
            Pilih jenis kelamin
          </option>
          <option
            v-for="option in genderOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
        <div v-if="fieldError('jenis_kelamin')" class="invalid-feedback">
          {{ fieldError('jenis_kelamin') }}
        </div>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="status_perkawinan">Status Perkawinan</label>
        <select
          id="status_perkawinan"
          name="status_perkawinan"
          class="form-control"
          :class="fieldInvalidClass('status_perkawinan')"
          required
          v-model="form.statusPerkawinan"
        >
          <option value="" disabled>
            Pilih status
          </option>
          <option
            v-for="option in statusPerkawinanOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
        <small class="form-text text-muted">
          Gunakan angka: 1 = Sudah Menikah, 2 = Belum Menikah atau Cerai Hidup/Mati.
        </small>
        <div v-if="fieldError('status_perkawinan')" class="invalid-feedback">
          {{ fieldError('status_perkawinan') }}
        </div>
      </div>
      <div class="form-group col-md-3">
        <label for="jumlah_istri_suami">Jumlah Istri/Suami</label>
        <input
          id="jumlah_istri_suami"
          name="jumlah_istri_suami"
          type="number"
          min="0"
          class="form-control"
          :class="fieldInvalidClass('jumlah_istri_suami')"
          v-model="form.jumlahIstriSuami"
        >
        <div v-if="fieldError('jumlah_istri_suami')" class="invalid-feedback">
          {{ fieldError('jumlah_istri_suami') }}
        </div>
      </div>
      <div class="form-group col-md-3">
        <label for="jumlah_anak">Jumlah Anak</label>
        <input
          id="jumlah_anak"
          name="jumlah_anak"
          type="number"
          min="0"
          class="form-control"
          :class="fieldInvalidClass('jumlah_anak')"
          v-model="form.jumlahAnak"
        >
        <div v-if="fieldError('jumlah_anak')" class="invalid-feedback">
          {{ fieldError('jumlah_anak') }}
        </div>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-md-4">
        <label for="jabatan">Nama Jabatan</label>
        <input
          id="jabatan"
          name="jabatan"
          type="text"
          class="form-control"
          :class="fieldInvalidClass('jabatan')"
          v-model.trim="form.jabatan"
        >
        <div v-if="fieldError('jabatan')" class="invalid-feedback">
          {{ fieldError('jabatan') }}
        </div>
      </div>
      <div class="form-group col-md-4">
        <label for="eselon">Eselon</label>
        <input
          id="eselon"
          name="eselon"
          type="text"
          class="form-control"
          :class="fieldInvalidClass('eselon')"
          v-model.trim="form.eselon"
        >
        <div v-if="fieldError('eselon')" class="invalid-feedback">
          {{ fieldError('eselon') }}
        </div>
      </div>
      <div class="form-group col-md-4">
        <label for="golongan">Golongan</label>
        <input
          id="golongan"
          name="golongan"
          type="text"
          class="form-control"
          :class="fieldInvalidClass('golongan')"
          v-model.trim="form.golongan"
        >
        <div v-if="fieldError('golongan')" class="invalid-feedback">
          {{ fieldError('golongan') }}
        </div>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="masa_kerja">Masa Kerja Golongan</label>
        <input
          id="masa_kerja"
          name="masa_kerja"
          type="text"
          class="form-control"
          :class="fieldInvalidClass('masa_kerja')"
          v-model.trim="form.masaKerja"
        >
        <div v-if="fieldError('masa_kerja')" class="invalid-feedback">
          {{ fieldError('masa_kerja') }}
        </div>
      </div>
      <div class="form-group col-md-6">
        <label for="jumlah_tanggungan">Jumlah Tanggungan</label>
        <input
          id="jumlah_tanggungan"
          name="jumlah_tanggungan"
          type="number"
          min="0"
          class="form-control"
          :class="fieldInvalidClass('jumlah_tanggungan')"
          v-model="form.jumlahTanggungan"
        >
        <div v-if="fieldError('jumlah_tanggungan')" class="invalid-feedback">
          {{ fieldError('jumlah_tanggungan') }}
        </div>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-md-12">
        <label for="alamat_rumah">Alamat</label>
        <textarea
          id="alamat_rumah"
          name="alamat_rumah"
          rows="3"
          class="form-control"
          :class="fieldInvalidClass('alamat_rumah')"
          v-model="form.alamatRumah"
        ></textarea>
        <div v-if="fieldError('alamat_rumah')" class="invalid-feedback">
          {{ fieldError('alamat_rumah') }}
        </div>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="tipe_jabatan">Tipe Jabatan</label>
        <select
          id="tipe_jabatan"
          name="tipe_jabatan"
          class="form-control"
          :class="fieldInvalidClass('tipe_jabatan')"
          required
          v-model="form.tipeJabatan"
        >
          <option value="" disabled>
            Pilih tipe jabatan
          </option>
          <option
            v-for="option in tipeJabatanOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
        <div v-if="fieldError('tipe_jabatan')" class="invalid-feedback">
          {{ fieldError('tipe_jabatan') }}
        </div>
      </div>
      <div class="form-group col-md-6">
        <label for="status_asn">Status ASN</label>
        <select
          id="status_asn"
          name="status_asn"
          class="form-control"
          :class="fieldInvalidClass('status_asn')"
          required
          v-model="form.statusAsn"
        >
          <option value="" disabled>
            Pilih status ASN
          </option>
          <option
            v-for="option in statusAsnOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
        <div v-if="fieldError('status_asn')" class="invalid-feedback">
          {{ fieldError('status_asn') }}
        </div>
      </div>
    </div>

    <div class="form-row align-items-center">
      <div class="form-group col-md-4">
        <label for="pasangan_pns">Pasangan PNS</label>
        <div class="form-check">
          <input
            class="form-check-input"
            id="pasangan_pns"
            type="checkbox"
            :checked="form.pasanganPns"
            @change="togglePasanganPns"
          >
          <label class="form-check-label" for="pasangan_pns">
            Ya
          </label>
        </div>
        <input type="hidden" name="pasangan_pns" :value="form.pasanganPns ? 1 : 0">
      </div>
      <div class="form-group col-md-4">
        <label for="nip_pasangan">NIP Pasangan</label>
        <input
          id="nip_pasangan"
          name="nip_pasangan"
          type="text"
          class="form-control"
          :class="fieldInvalidClass('nip_pasangan')"
          v-model.trim="form.nipPasangan"
        >
        <div v-if="fieldError('nip_pasangan')" class="invalid-feedback">
          {{ fieldError('nip_pasangan') }}
        </div>
      </div>
      <div class="form-group col-md-4">
        <label for="skpd_id">SKPD / Instansi</label>
        <select
          id="skpd_id"
          name="skpd_id"
          class="form-control"
          :class="fieldInvalidClass('skpd_id')"
          required
          v-model="form.skpdId"
          :disabled="skpdDisabled"
        >
          <option value="" disabled>
            Pilih SKPD
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
    </div>

    <div class="form-row">
      <div class="form-group col-md-4">
        <label for="kode_bank">Kode Bank</label>
        <input
          id="kode_bank"
          name="kode_bank"
          type="text"
          class="form-control"
          :class="fieldInvalidClass('kode_bank')"
          v-model.trim="form.kodeBank"
        >
        <div v-if="fieldError('kode_bank')" class="invalid-feedback">
          {{ fieldError('kode_bank') }}
        </div>
      </div>
      <div class="form-group col-md-4">
        <label for="nama_bank">Nama Bank</label>
        <input
          id="nama_bank"
          name="nama_bank"
          type="text"
          class="form-control"
          :class="fieldInvalidClass('nama_bank')"
          v-model.trim="form.namaBank"
        >
        <div v-if="fieldError('nama_bank')" class="invalid-feedback">
          {{ fieldError('nama_bank') }}
        </div>
      </div>
      <div class="form-group col-md-4">
        <label for="nomor_rekening_pegawai">Nomor Rekening Bank Pegawai</label>
        <input
          id="nomor_rekening_pegawai"
          name="nomor_rekening_pegawai"
          type="text"
          class="form-control"
          :class="fieldInvalidClass('nomor_rekening_pegawai')"
          v-model.trim="form.nomorRekeningPegawai"
        >
        <div v-if="fieldError('nomor_rekening_pegawai')" class="invalid-feedback">
          {{ fieldError('nomor_rekening_pegawai') }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';

const props = defineProps({
  mode: { type: String, default: 'create' },
  options: {
    type: Object,
    default: () => ({
      skpds: [],
      tipeJabatan: [],
      statusAsn: [],
      statusPerkawinan: [],
      genders: [],
    }),
  },
  old: { type: Object, default: () => ({}) },
  permissions: {
    type: Object,
    default: () => ({
      canSelectSkpd: false,
      forcedSkpdId: null,
    }),
  },
  errors: { type: Object, default: () => ({}) },
});

const toStringValue = (value) => {
  if (value === null || value === undefined) {
    return '';
  }

  return String(value);
};

const boolFrom = (value) => {
  if (value === true) {
    return true;
  }

  if (value === false || value === null || value === undefined) {
    return false;
  }

  const normalized = String(value).toLowerCase();
  return ['1', 'true', 'on', 'yes'].includes(normalized);
};

const form = reactive({
  namaLengkap: props.old?.nama_lengkap ?? '',
  nik: props.old?.nik ?? '',
  nip: props.old?.nip ?? '',
  npwp: props.old?.npwp ?? '',
  email: props.old?.email ?? '',
  tempatLahir: props.old?.tempat_lahir ?? '',
  tanggalLahir: props.old?.tanggal_lahir ?? '',
  jenisKelamin: props.old?.jenis_kelamin ?? '',
  statusPerkawinan: props.old?.status_perkawinan ?? '',
  jumlahIstriSuami: props.old?.jumlah_istri_suami ?? '',
  jumlahAnak: props.old?.jumlah_anak ?? '',
  jabatan: props.old?.jabatan ?? '',
  eselon: props.old?.eselon ?? '',
  golongan: props.old?.golongan ?? '',
  masaKerja: props.old?.masa_kerja ?? '',
  jumlahTanggungan: props.old?.jumlah_tanggungan ?? '',
  alamatRumah: props.old?.alamat_rumah ?? '',
  tipeJabatan: props.old?.tipe_jabatan ?? '',
  statusAsn: props.old?.status_asn ?? '',
  pasanganPns: boolFrom(props.old?.pasangan_pns ?? false),
  nipPasangan: props.old?.nip_pasangan ?? '',
  skpdId: toStringValue(props.old?.skpd_id ?? ''),
  kodeBank: props.old?.kode_bank ?? '',
  namaBank: props.old?.nama_bank ?? '',
  nomorRekeningPegawai: props.old?.nomor_rekening_pegawai ?? '',
});

const genderOptions = computed(() =>
  (props.options?.genders ?? [
    { value: 'Laki-laki', label: 'Laki-laki' },
    { value: 'Perempuan', label: 'Perempuan' },
  ]).map((option) => ({
    value: toStringValue(option.value ?? option),
    label: option.label ?? option.value ?? option,
  }))
);

const statusPerkawinanOptions = computed(() =>
  (props.options?.statusPerkawinan ?? []).map((option) => ({
    value: toStringValue(option.value ?? option.id ?? option),
    label: option.label ?? option.name ?? option,
  }))
);

const tipeJabatanOptions = computed(() =>
  (props.options?.tipeJabatan ?? []).map((option) => ({
    value: toStringValue(option.value ?? option.id ?? option),
    label: option.label ?? option.name ?? option,
  }))
);

const statusAsnOptions = computed(() =>
  (props.options?.statusAsn ?? []).map((option) => ({
    value: toStringValue(option.value ?? option.id ?? option),
    label: option.label ?? option.name ?? option,
  }))
);

const skpdOptions = computed(() =>
  (props.options?.skpds ?? []).map((option) => ({
    value: toStringValue(option.id ?? option.value ?? option),
    label: option.name ?? option.label ?? option.id ?? option,
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

watch(
  forcedSkpdId,
  (value) => {
    if (value !== '' && skpdDisabled.value) {
      form.skpdId = value;
    }
  },
  { immediate: true }
);

const togglePasanganPns = () => {
  form.pasanganPns = !form.pasanganPns;
};

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
