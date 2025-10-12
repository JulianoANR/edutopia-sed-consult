<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('disciplines', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            // Ajustar unicidades para serem por tenant
            $table->dropUnique('disciplines_name_unique');
            $table->dropUnique('disciplines_code_unique');
            $table->unique(['tenant_id', 'name'], 'disciplines_tenant_name_unique');
            $table->unique(['tenant_id', 'code'], 'disciplines_tenant_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('disciplines', function (Blueprint $table) {
            // Remover FK de tenant antes de dropar índices
            $table->dropForeign('disciplines_tenant_id_foreign');

            // Dropar uniques que incluem tenant_id
            $table->dropUnique('disciplines_tenant_name_unique');
            $table->dropUnique('disciplines_tenant_code_unique');

            // Dropar coluna tenant_id
            $table->dropColumn('tenant_id');

            // Restaurar unicidades originais
            $table->unique(['name']);
            $table->unique(['code']);
        });
    }
};