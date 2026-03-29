<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL enum alter. Keep values consistent with app logic.
        DB::statement("ALTER TABLE `student_export_requests` MODIFY `status` ENUM('pending','processing','done','failed','cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `student_export_requests` MODIFY `status` ENUM('pending','processing','done','failed') NOT NULL DEFAULT 'pending'");
    }
};

