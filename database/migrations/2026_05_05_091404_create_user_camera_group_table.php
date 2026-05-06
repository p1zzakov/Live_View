<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_camera_group', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('camera_group_id')->constrained('camera_groups')->onDelete('cascade');
            $table->enum('access_level', ['view', 'export', 'admin'])->default('view');
            $table->primary(['user_id', 'camera_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_camera_group');
    }
};
