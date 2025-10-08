<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skpds', function (Blueprint $table) {
            $table->string('npwp', 25)->nullable()->after('alias');
        });
    }

    public function down(): void
    {
        Schema::table('skpds', function (Blueprint $table) {
            $table->dropColumn('npwp');
        });
    }
};
