<template>
  <div>
    <div v-if="!hasEntries" class="alert alert-warning">
      {{ emptyMessage }}
    </div>

    <template v-else>
      <div v-if="periodLabel" class="mb-3">
        <span class="badge bg-primary">{{ periodLabel }}</span>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle text-nowrap">
          <thead class="table-primary">
            <tr>
              <th v-for="column in columns" :key="column.key">
                {{ column.label }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(entry, index) in entries" :key="index">
              <td v-for="column in columns" :key="column.key">
                {{ renderCell(entry, column) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  entries: {
    type: Array,
    default: () => [],
  },
  periodLabel: {
    type: String,
    default: '',
  },
  emptyMessage: {
    type: String,
    default: 'Data belum tersedia.',
  },
  columns: {
    type: Array,
    default: () => [
      { key: 'npwp_pemotong', label: 'NPWP Pemotong', type: 'nullable' },
      { key: 'masa_pajak', label: 'Masa Pajak' },
      { key: 'tahun_pajak', label: 'Tahun Pajak' },
      { key: 'status_pegawai', label: 'Status Pegawai' },
      { key: 'npwp_nik_tin', label: 'NPWP/NIK/TIN', type: 'nullable' },
      { key: 'nomor_passport', label: 'Nomor Passport', type: 'nullable' },
      { key: 'status', label: 'Status' },
      { key: 'posisi', label: 'Posisi' },
      { key: 'sertifikat_fasilitas', label: 'Sertifikat/Fasilitas' },
      { key: 'kode_objek_pajak', label: 'Kode Objek Pajak' },
      { key: 'gross', label: 'Penghasilan Kotor', type: 'currency' },
      { key: 'tarif', label: 'Tarif', type: 'tariff' },
      { key: 'id_tku', label: 'ID TKU', type: 'nullable' },
      { key: 'tgl_pemotongan', label: 'Tgl Pemotongan' },
    ],
  },
});

const hasEntries = computed(() => props.entries && props.entries.length > 0);

const toNumber = (value) => {
  const parsed = Number.parseFloat(value);
  return Number.isFinite(parsed) ? parsed : 0;
};

const formatCurrency = (value) => {
  const amount = toNumber(value);
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 2,
  })
    .format(amount)
    .replace('IDR', 'Rp')
    .trim();
};

const formatTariff = (value) => {
  if (value === null || value === undefined || value === '') {
    return '0';
  }

  const number = Number.parseFloat(value);
  if (!Number.isFinite(number)) {
    return String(value);
  }

  const formatted = number.toFixed(4).replace('.', ',');
  return formatted.replace(/,?0+$/, '').replace(/,$/, '') || '0';
};

const renderCell = (entry, column) => {
  const raw = entry?.[column.key];

  switch (column.type) {
    case 'currency':
      return formatCurrency(raw);
    case 'tariff':
      return formatTariff(raw);
    case 'nullable':
      return raw && raw !== '' ? raw : '-';
    default:
      return raw ?? '-';
  }
};
</script>
