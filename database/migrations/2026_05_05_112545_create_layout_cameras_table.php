<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layout_cameras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layout_id')->constrained()->onDelete('cascade');
            $table->foreignId('camera_id')->constrained()->onDelete('cascade');
            $table->integer('position'); // Позиция в сетке (0-15 для 4x4)
            $table->timestamps();
            
            // Уникальность: одна камера не может быть на двух позициях в одной раскладке
            $table->unique(['layout_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('layout_cameras');
    }
};
