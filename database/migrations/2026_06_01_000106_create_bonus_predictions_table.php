<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('player_name')->nullable();
            $table->unsignedSmallInteger('points')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'tournament_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_predictions');
    }
};
