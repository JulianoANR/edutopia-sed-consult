<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->foreignId('discipline_id')->nullable()->after('student_ra')->constrained('disciplines')->onDelete('set null');
            // Atualizar a unique para incluir discipline_id
            $table->dropUnique('attendance_unique_per_day');
            $table->unique(['class_code', 'date', 'student_ra', 'discipline_id'], 'attendance_unique_per_day');
            $table->index(['discipline_id']);
        });
    }

    public function down(): void
    {
        // Desabilitar verificações de FKs para dropar a coluna mesmo se a constraint tiver nome inesperado
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('attendance_records', function (Blueprint $table) {
            // Dropar coluna diretamente (remove automaticamente índices/uniques que a incluem)
            if (Schema::hasColumn('attendance_records', 'discipline_id')) {
                $table->dropColumn('discipline_id');
            }
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Remover duplicados que agora conflitam com a unique original (mesmo class_code/date/student_ra)
        DB::statement(
            'DELETE t1 FROM attendance_records t1 INNER JOIN attendance_records t2 ON t1.class_code = t2.class_code AND t1.date = t2.date AND t1.student_ra = t2.student_ra AND t1.id > t2.id'
        );

        Schema::table('attendance_records', function (Blueprint $table) {
            // Restaurar unique original sem discipline_id
            $table->unique(['class_code', 'date', 'student_ra'], 'attendance_unique_per_day');
        });
    }
};
?>