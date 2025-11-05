<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pegawais', function (Blueprint $table) {
            $table->index('skpd_id', 'pegawais_skpd_id_index');
            $table->index('status_asn', 'pegawais_status_asn_index');
            $table->index(['skpd_id', 'status_asn'], 'pegawais_skpd_status_asn_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('skpd_id', 'users_skpd_id_index');
            $table->index('role', 'users_role_index');
            $table->index(['skpd_id', 'role'], 'users_skpd_role_index');
        });
    }

    public function down(): void
    {
        Schema::table('pegawais', function (Blueprint $table) {
            $table->dropIndex('pegawais_skpd_id_index');
            $table->dropIndex('pegawais_status_asn_index');
            $table->dropIndex('pegawais_skpd_status_asn_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_skpd_id_index');
            $table->dropIndex('users_role_index');
            $table->dropIndex('users_skpd_role_index');
        });
    }
};
