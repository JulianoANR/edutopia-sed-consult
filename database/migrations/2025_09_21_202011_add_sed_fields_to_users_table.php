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
        Schema::table('users', function (Blueprint $table) {
            $table->string('sed_diretoria_id')->nullable();
            $table->string('sed_municipio_id')->nullable();
            $table->string('sed_username')->nullable();
            $table->string('sed_password')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sed_diretoria_id', 'sed_municipio_id', 'sed_username', 'sed_password']);
        });
    }
};
