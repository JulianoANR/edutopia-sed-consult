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
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->string('class_name')->after('class_code')->nullable();
            $table->string('school_code')->after('class_code')->nullable();
            $table->string('school_name')->after('school_code')->nullable();
            $table->string('type_ensino')->after('school_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn(['class_name', 'school_code', 'school_name', 'type_ensino']);
        });
    }
};
