<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->unsignedTinyInteger('home_score');
            $table->unsignedTinyInteger('away_score');
            $table->unsignedSmallInteger('points')->nullable();
            $table->boolean('is_exact')->default(false);
            $table->boolean('is_correct_outcome')->default(false);
            $table->boolean('is_correct_goal_difference')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'match_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
