<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('operator')->after('email'); // admin, operator
            $table->foreignId('layout_id')->nullable()->after('role')->constrained('layouts')->nullOnDelete();
            $table->boolean('can_access_archive')->default(true)->after('layout_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'layout_id', 'can_access_archive']);
        });
    }
};
