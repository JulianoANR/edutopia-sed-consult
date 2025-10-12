<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            // Atualizar a unique para incluir tenant
            $table->dropUnique('attendance_unique_per_day');
            $table->unique(['tenant_id', 'class_code', 'date', 'student_ra', 'discipline_id'], 'attendance_unique_per_day');

        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            // Remover a FK primeiro (sem dropar a coluna) para liberar o índice único
            $table->dropForeign(['tenant_id']);

            // Dropar a unique que inclui tenant_id
            $table->dropUnique('attendance_unique_per_day');

            // Dropar a coluna após remover índices/constraints
            $table->dropColumn('tenant_id');

            // Restaurar a unique sem tenant_id
            $table->unique(['class_code', 'date', 'student_ra', 'discipline_id'], 'attendance_unique_per_day');
        });
    }
};