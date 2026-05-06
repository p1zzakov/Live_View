<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camera_camera_group', function (Blueprint $table) {
            $table->foreignId('camera_id')->constrained('cameras')->onDelete('cascade');
            $table->foreignId('camera_group_id')->constrained('camera_groups')->onDelete('cascade');
            $table->primary(['camera_id', 'camera_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camera_camera_group');
    }
};
