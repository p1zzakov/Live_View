<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layouts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Название раскладки
            $table->text('description')->nullable(); // Описание
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Владелец (null = общая)
            $table->enum('grid_type', ['1x1', '2x2', '3x3', '4x4'])->default('2x2'); // Тип сетки
            $table->boolean('is_default')->default(false); // Раскладка по умолчанию
            $table->boolean('is_public')->default(false); // Доступна всем
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('layouts');
    }
};
