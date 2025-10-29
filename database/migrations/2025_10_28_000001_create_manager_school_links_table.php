<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('manager_school_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('school_code');
            $table->timestamps();

            $table->index('school_code');
            $table->unique(['tenant_id', 'user_id', 'school_code'], 'unique_manager_school');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_school_links');
    }
};