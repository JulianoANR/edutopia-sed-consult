<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teacher_class_discipline_links', function (Blueprint $table) {
            $table->string('class_name')->nullable()->after('class_code');
            $table->string('school_year', 4)->nullable()->after('class_name');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_class_discipline_links', function (Blueprint $table) {
            $table->dropColumn(['class_name', 'school_year']);
        });
    }
};