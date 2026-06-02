<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('external_id')->nullable()->index();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('tla', 3)->nullable();
            $table->string('crest_url')->nullable();
            $table->string('group')->nullable();
            $table->timestamps();

            $table->unique(['tournament_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
