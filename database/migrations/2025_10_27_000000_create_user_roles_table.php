<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('role', ['admin', 'gestor', 'professor', 'super_admin']);
            $table->timestamps();

            $table->unique(['user_id', 'role']);
        });

        // Backfill existing roles from users.role into user_roles
        // Ensure we only insert valid roles
        // $validRoles = ['admin', 'gestor', 'professor', 'super_admin'];

        // $users = DB::table('users')->select('id', 'role')->get();
        // $now = now();
        // $inserts = [];
        // foreach ($users as $u) {
        //     if ($u->role && in_array($u->role, $validRoles, true)) {
        //         $inserts[] = [
        //             'user_id' => $u->id,
        //             'role' => $u->role,
        //             'created_at' => $now,
        //             'updated_at' => $now,
        //         ];
        //     }
        // }
        // if (!empty($inserts)) {
        //     DB::table('user_roles')->insert($inserts);
        // }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};