<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW view_pegawai_gaji AS
SELECT
    pegawais.nip AS `NIP Pegawai`,
    pegawais.nama_lengkap AS `Nama Pegawai`,
    pegawais.nik AS `NIK Pegawai`,
    pegawais.npwp AS `NPWP Pegawai`,
    pegawais.tanggal_lahir AS `Tanggal Lahir Pegawai`,
    pegawais.tipe_jabatan AS `Tipe Jabatan`,
    pegawais.jabatan AS `Nama Jabatan`,
    pegawais.eselon AS `Eselon`,
    pegawais.status_asn AS `Status ASN`,
    pegawais.golongan AS `golongan`,
    pegawais.masa_kerja AS `Masa Kerja Golongan`,
    pegawais.alamat_rumah AS `Alamat`,
    pegawais.status_perkawinan AS `Status Pernikahan`,
    pegawais.jumlah_istri_suami AS `Jumlah Istri_Suami`,
    pegawais.jumlah_anak AS `Jumlah Anak`,
    pegawais.jumlah_tanggungan AS `Jumlah Tanggungan`,
    pegawais.pasangan_pns AS `Pasangan PNS`,
    pegawais.nip_pasangan AS `NIP Pasangan`,
    pegawais.kode_bank AS `Kode Bank`,
    pegawais.nama_bank AS `Nama Bank`,
    pegawais.nomor_rekening_pegawai AS `Nomor Rekening Bank Pegawai`,
    gajis.tahun AS `Tahun`,
    gajis.bulan AS `Bulan`,
    gajis.jenis_asn AS `Jenis ASN`,
    gajis.gaji_pokok AS `Gaji Pokok`,
    gajis.perhitungan_suami_istri AS `Perhitungan Suami_Istri`,
    gajis.perhitungan_anak AS `Perhitungan Anak`,
    gajis.tunjangan_keluarga AS `Tunjangan Keluarga`,
    gajis.tunjangan_jabatan AS `Tunjangan Jabatan`,
    gajis.tunjangan_fungsional AS `Tunjangan Fungsional`,
    gajis.tunjangan_fungsional_umum AS `Tunjangan Fungsional Umum`,
    gajis.tunjangan_beras AS `Tunjangan Beras`,
    gajis.tunjangan_pph AS `Tunjangan PPh`,
    gajis.pembulatan_gaji AS `Pembulatan Gaji`,
    gajis.iuran_jaminan_kesehatan AS `Iuran Jaminan Kesehatan`,
    gajis.iuran_jaminan_kecelakaan_kerja AS `Iuran Jaminan Kecelakaan Kerja`,
    gajis.iuran_jaminan_kematian AS `Iuran Jaminan Kematian`,
    gajis.iuran_simpanan_tapera AS `Iuran Simpanan Tapera`,
    gajis.iuran_pensiun AS `Iuran Pensiun`,
    gajis.tunjangan_khusus_papua AS `Tunjangan Khusus Papua`,
    gajis.tunjangan_jaminan_hari_tua AS `Tunjangan Jaminan Hari Tua`,
    gajis.potongan_iwp AS `Potongan IWP`,
    gajis.potongan_pph_21 AS `Potongan PPh 21`,
    gajis.zakat AS `Zakat`,
    gajis.bulog AS `Bulog`,
    (
        COALESCE(gajis.gaji_pokok, 0) +
        COALESCE(gajis.tunjangan_keluarga, 0) +
        COALESCE(gajis.tunjangan_jabatan, 0) +
        COALESCE(gajis.tunjangan_fungsional, 0) +
        COALESCE(gajis.tunjangan_fungsional_umum, 0) +
        COALESCE(gajis.tunjangan_beras, 0) +
        COALESCE(gajis.tunjangan_pph, 0) +
        COALESCE(gajis.pembulatan_gaji, 0) +
        COALESCE(gajis.iuran_jaminan_kesehatan, 0) +
        COALESCE(gajis.iuran_jaminan_kecelakaan_kerja, 0) +
        COALESCE(gajis.iuran_jaminan_kematian, 0) +
        COALESCE(gajis.iuran_simpanan_tapera, 0) +
        COALESCE(gajis.tunjangan_khusus_papua, 0)
    ) AS `Jumlah Gaji dan Tunjangan`,
    (
        COALESCE(gajis.iuran_jaminan_kesehatan, 0) +
        COALESCE(gajis.iuran_jaminan_kecelakaan_kerja, 0) +
        COALESCE(gajis.iuran_jaminan_kematian, 0) +
        COALESCE(gajis.iuran_simpanan_tapera, 0) +
        COALESCE(gajis.iuran_pensiun, 0) +
        COALESCE(gajis.tunjangan_jaminan_hari_tua, 0) +
        COALESCE(gajis.potongan_iwp, 0) +
        COALESCE(gajis.potongan_pph_21, 0) +
        COALESCE(gajis.zakat, 0) +
        COALESCE(gajis.bulog, 0)
    ) AS `Jumlah Potongan`,
    (
        (
            COALESCE(gajis.gaji_pokok, 0) +
            COALESCE(gajis.tunjangan_keluarga, 0) +
            COALESCE(gajis.tunjangan_jabatan, 0) +
            COALESCE(gajis.tunjangan_fungsional, 0) +
            COALESCE(gajis.tunjangan_fungsional_umum, 0) +
            COALESCE(gajis.tunjangan_beras, 0) +
            COALESCE(gajis.tunjangan_pph, 0) +
            COALESCE(gajis.pembulatan_gaji, 0) +
            COALESCE(gajis.iuran_jaminan_kesehatan, 0) +
            COALESCE(gajis.iuran_jaminan_kecelakaan_kerja, 0) +
            COALESCE(gajis.iuran_jaminan_kematian, 0) +
            COALESCE(gajis.iuran_simpanan_tapera, 0) +
            COALESCE(gajis.tunjangan_khusus_papua, 0)
        ) -
        (
            COALESCE(gajis.iuran_jaminan_kesehatan, 0) +
            COALESCE(gajis.iuran_jaminan_kecelakaan_kerja, 0) +
            COALESCE(gajis.iuran_jaminan_kematian, 0) +
            COALESCE(gajis.iuran_simpanan_tapera, 0) +
            COALESCE(gajis.iuran_pensiun, 0) +
            COALESCE(gajis.tunjangan_jaminan_hari_tua, 0) +
            COALESCE(gajis.potongan_iwp, 0) +
            COALESCE(gajis.potongan_pph_21, 0) +
            COALESCE(gajis.zakat, 0) +
            COALESCE(gajis.bulog, 0)
        )
    ) AS `Jumlah Ditransfer`
FROM gajis
INNER JOIN pegawais ON pegawais.id = gajis.pegawai_id;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS view_pegawai_gaji');
    }
};
