<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('status')->default('success');
            $table->string('endpoint')->nullable();
            $table->unsignedInteger('items_processed')->default(0);
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->boolean('triggered_manually')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_sync_logs');
    }
};
