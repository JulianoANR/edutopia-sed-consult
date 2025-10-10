<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teacher_class_discipline_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('discipline_id')->nullable()->constrained('disciplines')->onDelete('cascade');
            $table->string('class_code'); // Código SED da turma
            $table->boolean('full_access')->default(false); // Acesso a todas as disciplinas da turma
            $table->timestamps();

            $table->index('class_code');
            $table->unique(['user_id', 'class_code', 'discipline_id'], 'unique_teacher_class_discipline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_class_discipline_links');
    }
};