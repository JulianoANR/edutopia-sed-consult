<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropIndex(['discipline_id']);
            $table->dropUnique('attendance_unique_per_day');
            $table->dropConstrainedForeignId('discipline_id');
            $table->unique(['class_code', 'date', 'student_ra'], 'attendance_unique_per_day');
        });
    }
};