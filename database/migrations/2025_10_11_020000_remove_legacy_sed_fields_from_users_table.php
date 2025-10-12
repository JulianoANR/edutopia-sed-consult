<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'sed_diretoria_id')) {
                $table->dropColumn('sed_diretoria_id');
            }
            if (Schema::hasColumn('users', 'sed_municipio_id')) {
                $table->dropColumn('sed_municipio_id');
            }
            if (Schema::hasColumn('users', 'sed_username')) {
                $table->dropColumn('sed_username');
            }
            if (Schema::hasColumn('users', 'sed_password')) {
                $table->dropColumn('sed_password');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'sed_diretoria_id')) {
                $table->string('sed_diretoria_id')->nullable();
            }
            if (!Schema::hasColumn('users', 'sed_municipio_id')) {
                $table->string('sed_municipio_id')->nullable();
            }
            if (!Schema::hasColumn('users', 'sed_username')) {
                $table->string('sed_username')->nullable();
            }
            if (!Schema::hasColumn('users', 'sed_password')) {
                $table->string('sed_password')->nullable();
            }
        });
    }
};