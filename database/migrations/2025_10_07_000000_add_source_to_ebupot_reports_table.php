<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ebupot_reports', function (Blueprint $table) {
            if (Schema::hasColumn('ebupot_reports', 'source')) {
                return;
            }

            $table->dropForeign(['skpd_id']);
            $table->dropUnique('ebupot_reports_unique_period');

            $table->string('source', 20)->default('gaji')->after('skpd_id');
            $table->unique(
                ['source', 'skpd_id', 'jenis_asn', 'tahun', 'bulan'],
                'ebupot_reports_unique_period_source'
            );

            $table->foreign('skpd_id')
                ->references('id')
                ->on('skpds')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ebupot_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('ebupot_reports', 'source')) {
                return;
            }

            $table->dropForeign(['skpd_id']);
            $table->dropUnique('ebupot_reports_unique_period_source');
            $table->dropColumn('source');
            $table->unique(['skpd_id', 'jenis_asn', 'tahun', 'bulan'], 'ebupot_reports_unique_period');
            $table->foreign('skpd_id')
                ->references('id')
                ->on('skpds')
                ->nullOnDelete();
        });
    }
};
