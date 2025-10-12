<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teacher_class_discipline_links', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_class_discipline_links', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            }

            // Remover FKs que podem depender do índice único atual
            $table->dropForeign(['user_id']);
            $table->dropForeign(['discipline_id']);

            // Ajustar unique para incluir tenant
            $table->dropUnique('unique_teacher_class_discipline');
            $table->unique(['tenant_id', 'user_id', 'class_code', 'discipline_id'], 'unique_teacher_class_discipline');

            // Recriar FKs
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('discipline_id')->references('id')->on('disciplines')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Desabilitar verificação de FKs para garantir remoção da coluna mesmo se o nome da FK variar
        Schema::disableForeignKeyConstraints();

        Schema::table('teacher_class_discipline_links', function (Blueprint $table) {
            // Dropar coluna tenant_id (quaisquer índices/uniques envolvendo a coluna serão removidos)
            $table->dropColumn('tenant_id');

            // Restaurar unique original
            $table->unique(['user_id', 'class_code', 'discipline_id'], 'unique_teacher_class_discipline');
        });

        Schema::enableForeignKeyConstraints();
    }
};
?>