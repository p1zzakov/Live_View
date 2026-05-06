<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nvrs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('vendor', ['polyvision', 'dahua', 'hikvision', 'other']);
            $table->string('ip_address');
            $table->integer('http_port')->default(80);
            $table->integer('rtsp_port')->default(554);
            $table->json('credentials');
            $table->string('api_endpoint')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_health_check')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nvrs');
    }
};
