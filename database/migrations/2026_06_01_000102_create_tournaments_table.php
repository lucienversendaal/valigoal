<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_id')->nullable()->unique();
            $table->string('code')->default('WC');
            $table->string('name');
            $table->string('season')->nullable();
            $table->string('emblem_url')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('winner_team_id')->nullable();
            $table->foreignId('runner_up_team_id')->nullable();
            $table->string('top_scorer_name')->nullable();
            $table->unsignedSmallInteger('top_scorer_goals')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
