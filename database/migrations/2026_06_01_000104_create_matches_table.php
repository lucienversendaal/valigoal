<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('external_id')->nullable()->unique();
            $table->foreignId('home_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('away_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('stage')->nullable();
            $table->string('group')->nullable();
            $table->unsignedSmallInteger('matchday')->nullable();
            $table->timestamp('kickoff_at')->nullable()->index();
            $table->string('status')->default('SCHEDULED')->index();
            $table->unsignedTinyInteger('home_score')->nullable();
            $table->unsignedTinyInteger('away_score')->nullable();
            $table->string('winner')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->boolean('points_awarded')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
