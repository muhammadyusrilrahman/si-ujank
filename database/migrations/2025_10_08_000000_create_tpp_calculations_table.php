<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tpp_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawais')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('skpd_id')->nullable()->constrained('skpds')->nullOnDelete();
            $table->string('jenis_asn', 10);
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('bulan');
            $table->string('kelas_jabatan', 100)->nullable();
            $table->string('golongan', 100)->nullable();
            $table->decimal('beban_kerja', 15, 2)->default(0);
            $table->decimal('kondisi_kerja', 15, 2)->default(0);
            $table->decimal('extra_plt20', 15, 2)->default(0);
            $table->decimal('extra_ppkd20', 15, 2)->default(0);
            $table->decimal('extra_bud20', 15, 2)->default(0);
            $table->decimal('extra_kbud20', 15, 2)->default(0);
            $table->decimal('extra_tim_tapd20', 15, 2)->default(0);
            $table->decimal('extra_tim_tpp20', 15, 2)->default(0);
            $table->decimal('extra_bendahara_penerimaan10', 15, 2)->default(0);
            $table->decimal('extra_bendahara_pengeluaran30', 15, 2)->default(0);
            $table->decimal('extra_pengurus_barang20', 15, 2)->default(0);
            $table->decimal('extra_pejabat_pengadaan10', 15, 2)->default(0);
            $table->decimal('extra_tim_tapd20_from_beban', 15, 2)->default(0);
            $table->decimal('extra_ppk5', 15, 2)->default(0);
            $table->decimal('extra_pptk5', 15, 2)->default(0);
            $table->unsignedInteger('presensi_ketidakhadiran')->default(0);
            $table->decimal('presensi_persen_ketidakhadiran', 6, 2)->default(0);
            $table->decimal('presensi_persen_kehadiran', 6, 2)->default(100);
            $table->decimal('presensi_nilai', 15, 2)->default(0);
            $table->decimal('kinerja_persen', 6, 2)->default(0);
            $table->decimal('kinerja_nilai', 15, 2)->default(0);
            $table->decimal('jumlah_tpp', 15, 2)->default(0);
            $table->decimal('bruto', 15, 2)->default(0);
            $table->decimal('pfk_pph21', 15, 2)->default(0);
            $table->decimal('pfk_bpjs4', 15, 2)->default(0);
            $table->decimal('pfk_bpjs1', 15, 2)->default(0);
            $table->decimal('netto', 15, 2)->default(0);
            $table->string('tanda_terima', 255)->nullable();
            $table->timestamps();

            $table->unique(['pegawai_id', 'jenis_asn', 'tahun', 'bulan'], 'tpp_calc_unique_pegawai_period');
            $table->index(['tahun', 'bulan']);
            $table->index(['jenis_asn']);
            $table->index(['skpd_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tpp_calculations');
    }
};

