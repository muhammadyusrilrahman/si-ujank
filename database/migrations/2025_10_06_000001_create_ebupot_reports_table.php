<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ebupot_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skpd_id')->nullable()->constrained('skpds')->nullOnDelete();
            $table->string('jenis_asn', 10);
            $table->unsignedInteger('tahun');
            $table->unsignedTinyInteger('bulan');
            $table->string('npwp_pemotong', 32)->nullable();
            $table->string('id_tku', 50)->nullable();
            $table->string('kode_objek', 20)->nullable();
            $table->date('cut_off_date');
            $table->unsignedInteger('entry_count');
            $table->decimal('total_gross', 20, 2)->default(0);
            $table->json('payload');
            $table->timestamps();

            $table->unique(['skpd_id', 'jenis_asn', 'tahun', 'bulan'], 'ebupot_reports_unique_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ebupot_reports');
    }
};
