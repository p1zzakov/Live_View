<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cameras', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['dahua', 'hikvision', 'polyvision', 'analog']);
            $table->foreignId('nvr_id')->constrained('nvrs')->onDelete('cascade');
            $table->string('rtsp_live_url');
            $table->string('rtsp_playback_template')->nullable();
            $table->string('onvif_url')->nullable();
            $table->json('credentials')->nullable();
            $table->integer('channel_number');
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_recording')->default(false);
            $table->timestamp('last_health_check')->nullable();
            $table->timestamps();
            
            $table->index(['nvr_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cameras');
    }
};
