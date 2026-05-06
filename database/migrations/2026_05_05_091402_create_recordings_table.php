<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camera_id')->constrained('cameras')->onDelete('cascade');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->bigInteger('file_size')->nullable();
            $table->string('nvr_file_path')->nullable();
            $table->timestamps();
            
            $table->index(['camera_id', 'start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recordings');
    }
};
