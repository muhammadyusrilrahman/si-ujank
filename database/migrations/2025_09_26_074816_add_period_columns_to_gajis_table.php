<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('gajis', 'jenis_asn')) {
            return;
        }

        Schema::table('gajis', function (Blueprint $table) {
            $table->unsignedSmallInteger('tahun')->after('pegawai_id')->default((int) date('Y'));
            $table->unsignedTinyInteger('bulan')->after('tahun')->default((int) date('n'));
            $table->string('jenis_asn', 10)->after('bulan')->default('pns');
            $table->unique(['pegawai_id', 'tahun', 'bulan'], 'gajis_pegawai_tahun_bulan_unique');
            $table->index(['tahun', 'bulan', 'jenis_asn'], 'gajis_tahun_bulan_jenis_asn_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('gajis', 'jenis_asn')) {
            return;
        }

        Schema::table('gajis', function (Blueprint $table) {
            $table->dropUnique('gajis_pegawai_tahun_bulan_unique');
            $table->dropIndex('gajis_tahun_bulan_jenis_asn_index');
            $table->dropColumn(['jenis_asn', 'bulan', 'tahun']);
        });
    }
};
