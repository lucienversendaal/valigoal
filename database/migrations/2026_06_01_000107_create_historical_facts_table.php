<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historical_facts', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->text('body')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historical_facts');
    }
};
