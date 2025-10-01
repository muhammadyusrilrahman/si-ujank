<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gajis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawais')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('bulan');
            $table->string('jenis_asn', 10); // snapshot status ASN (mis. PNS atau PPPK)
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->decimal('perhitungan_suami_istri', 15, 2)->default(0);
            $table->decimal('perhitungan_anak', 15, 2)->default(0);
            $table->decimal('tunjangan_keluarga', 15, 2)->default(0);
            $table->decimal('tunjangan_jabatan', 15, 2)->default(0);
            $table->decimal('tunjangan_fungsional', 15, 2)->default(0);
            $table->decimal('tunjangan_fungsional_umum', 15, 2)->default(0);
            $table->decimal('tunjangan_beras', 15, 2)->default(0);
            $table->decimal('tunjangan_pph', 15, 2)->default(0);
            $table->decimal('pembulatan_gaji', 15, 2)->default(0);
            $table->decimal('iuran_jaminan_kesehatan', 15, 2)->default(0);
            $table->decimal('iuran_jaminan_kecelakaan_kerja', 15, 2)->default(0);
            $table->decimal('iuran_jaminan_kematian', 15, 2)->default(0);
            $table->decimal('iuran_simpanan_tapera', 15, 2)->default(0);
            $table->decimal('iuran_pensiun', 15, 2)->default(0);
            $table->decimal('tunjangan_khusus_papua', 15, 2)->default(0);
            $table->decimal('tunjangan_jaminan_hari_tua', 15, 2)->default(0);
            $table->decimal('potongan_iwp', 15, 2)->default(0);
            $table->decimal('potongan_pph_21', 15, 2)->default(0);
            $table->decimal('zakat', 15, 2)->default(0);
            $table->decimal('bulog', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['pegawai_id', 'tahun', 'bulan']);
            $table->index(['tahun', 'bulan', 'jenis_asn']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gajis');
    }
};
