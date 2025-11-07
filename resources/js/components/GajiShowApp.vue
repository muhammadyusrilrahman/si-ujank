<template>
  <div>
    <div class="row">
      <div class="col-md-6">
        <table class="table table-borderless mb-4">
          <tbody>
            <tr>
              <th style="width: 200px">Nama Pegawai</th>
              <td>{{ pegawai.name }}</td>
            </tr>
            <tr>
              <th>NIP</th>
              <td>{{ pegawai.nip }}</td>
            </tr>
            <tr>
              <th>SKPD</th>
              <td>{{ pegawai.skpd }}</td>
            </tr>
            <tr>
              <th>Periode</th>
              <td>{{ period.label }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="col-md-6">
        <table class="table mb-4">
          <tbody>
            <tr>
              <th style="width: 200px">Gaji Pokok</th>
              <td>{{ formatCurrency(amounts.gaji_pokok) }}</td>
            </tr>
            <tr>
              <th>Tunjangan</th>
              <td>{{ formatCurrency(amounts.tunjangan) }}</td>
            </tr>
            <tr>
              <th>Potongan</th>
              <td>{{ formatCurrency(amounts.potongan) }}</td>
            </tr>
            <tr class="table-primary font-weight-semibold">
              <th>Total Gaji</th>
              <td>{{ formatCurrency(amounts.total) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
      <a :href="routes.index" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i>
        <span class="ml-1">Kembali</span>
      </a>
      <template v-if="routes.edit">
        <a :href="routes.edit" class="btn btn-warning btn-sm">
          <i class="fas fa-edit"></i>
          <span class="ml-1">Edit</span>
        </a>
      </template>
      <template v-if="routes.destroy">
        <button type="button" class="btn btn-danger btn-sm" @click="confirmDelete">
          <i class="fas fa-trash"></i>
          <span class="ml-1">Hapus</span>
        </button>
      </template>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  pegawai: {
    type: Object,
    default: () => ({
      name: '-',
      nip: '-',
      skpd: '-',
    }),
  },
  period: {
    type: Object,
    default: () => ({
      label: '-',
    }),
  },
  amounts: {
    type: Object,
    default: () => ({
      gaji_pokok: 0,
      tunjangan: 0,
      potongan: 0,
      total: 0,
    }),
  },
  routes: {
    type: Object,
    default: () => ({
      index: '#',
      edit: null,
      destroy: null,
    }),
  },
  csrfToken: {
    type: String,
    default: '',
  },
  confirmations: {
    type: Object,
    default: () => ({
      delete: 'Apakah Anda yakin ingin menghapus data ini?',
    }),
  },
});

const formatCurrency = (value) => {
  const amount = Number.isFinite(value) ? value : parseFloat(value) || 0;
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 2,
  })
    .format(amount)
    .replace('IDR', 'Rp')
    .trim();
};

const confirmDelete = () => {
  if (!props.routes.destroy) {
    return;
  }

  if (!confirm(props.confirmations.delete)) {
    return;
  }

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = props.routes.destroy;

  const tokenInput = document.createElement('input');
  tokenInput.type = 'hidden';
  tokenInput.name = '_token';
  tokenInput.value = props.csrfToken;

  const methodInput = document.createElement('input');
  methodInput.type = 'hidden';
  methodInput.name = '_method';
  methodInput.value = 'DELETE';

  form.appendChild(tokenInput);
  form.appendChild(methodInput);

  document.body.appendChild(form);
  form.submit();
};
</script>
