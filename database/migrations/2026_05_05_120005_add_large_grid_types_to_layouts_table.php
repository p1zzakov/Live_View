<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Добавляем новые типы в enum
        DB::statement("ALTER TABLE layouts DROP CONSTRAINT IF EXISTS layouts_grid_type_check");
        DB::statement("ALTER TABLE layouts ALTER COLUMN grid_type TYPE VARCHAR(10)");
        
        // Можно добавить новый constraint если нужно
        // DB::statement("ALTER TABLE layouts ADD CONSTRAINT layouts_grid_type_check CHECK (grid_type IN ('1x1', '2x2', '3x3', '4x4', '5x5', '6x6', '6x8', '8x6'))");
    }

    public function down(): void
    {
        // Откат не делаем, т.к. могут быть данные
    }
};
