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
        Schema::create('pegawais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skpd_id')->constrained('skpds')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('nama_lengkap');
            $table->string('nik')->unique();
            $table->string('nip')->nullable()->unique();
            $table->string('npwp')->nullable();
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->string('jenis_kelamin', 20);
            $table->string('status_perkawinan', 50)->nullable();
            $table->unsignedTinyInteger('jumlah_istri_suami')->default(0);
            $table->unsignedTinyInteger('jumlah_anak')->default(0);
            $table->string('jabatan')->nullable();
            $table->string('golongan')->nullable();
            $table->string('email')->nullable();
            $table->text('alamat_rumah')->nullable();
            $table->string('masa_kerja')->nullable();
            $table->unsignedTinyInteger('jumlah_tanggungan')->default(0);
            $table->boolean('pasangan_pns')->default(false);
            $table->string('nip_pasangan')->nullable();
            $table->string('kode_bank', 20)->nullable();
            $table->string('nama_bank')->nullable();
            $table->string('nomor_rekening_pegawai')->nullable();
            $table->string('tipe_jabatan', 50)->nullable();
            $table->string('status_asn', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawais');
    }
};

