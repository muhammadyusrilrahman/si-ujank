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
        Schema::create('skpds', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('alias')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->default('')->unique()->after('email');
            $table->foreignId('skpd_id')->nullable()->after('username')->constrained('skpds')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('skpd_id');
            $table->dropColumn('username');
        });

        Schema::dropIfExists('skpds');
    }
};

