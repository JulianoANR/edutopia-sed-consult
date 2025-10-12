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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('diretoria_id');
            $table->unsignedBigInteger('municipio_id');
            $table->unsignedBigInteger('rede_ensino_cod');
            $table->string('sed_username');
            $table->text('sed_password_encrypted');
            $table->string('status')->default('active');
            $table->timestamp('last_validated_at')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
        });
        Schema::dropIfExists('tenants');
    }
};