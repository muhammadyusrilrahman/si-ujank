<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tpps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawais')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('bulan');
            $table->string('jenis_asn', 10);
            $table->decimal('tpp_beban_kerja', 15, 2)->default(0);
            $table->decimal('tpp_tempat_bertugas', 15, 2)->default(0);
            $table->decimal('tpp_kondisi_kerja', 15, 2)->default(0);
            $table->decimal('tpp_kelangkaan_profesi', 15, 2)->default(0);
            $table->decimal('tpp_prestasi_kerja', 15, 2)->default(0);
            $table->decimal('tunjangan_pph', 15, 2)->default(0);
            $table->decimal('tunjangan_jaminan_hari_tua', 15, 2)->default(0);
            $table->decimal('iuran_jaminan_kesehatan', 15, 2)->default(0);
            $table->decimal('iuran_jaminan_kecelakaan_kerja', 15, 2)->default(0);
            $table->decimal('iuran_jaminan_kematian', 15, 2)->default(0);
            $table->decimal('iuran_simpanan_tapera', 15, 2)->default(0);
            $table->decimal('iuran_pensiun', 15, 2)->default(0);
            $table->decimal('potongan_iwp', 15, 2)->default(0);
            $table->decimal('potongan_pph_21', 15, 2)->default(0);
            $table->decimal('zakat', 15, 2)->default(0);
            $table->decimal('bulog', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['pegawai_id', 'tahun', 'bulan']);
            $table->index(['tahun', 'bulan', 'jenis_asn']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tpps');
    }
};
