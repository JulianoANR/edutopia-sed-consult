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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->string('class_code'); // Código da turma (inNumClasse)
            $table->date('date'); // Data da presença
            $table->string('student_ra'); // RA completo (ex: 123456789-0)
            $table->enum('status', ['present', 'absent', 'justified']); // Presente/Ausente/Justificado
            $table->text('note')->nullable(); // Observações
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Usuário que registrou
            $table->timestamps();

            $table->unique(['class_code', 'date', 'student_ra'], 'attendance_unique_per_day');
            $table->index(['class_code', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};