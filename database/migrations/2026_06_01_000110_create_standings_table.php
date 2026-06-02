<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('group')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->unsignedSmallInteger('played')->default(0);
            $table->unsignedSmallInteger('won')->default(0);
            $table->unsignedSmallInteger('draw')->default(0);
            $table->unsignedSmallInteger('lost')->default(0);
            $table->smallInteger('goals_for')->default(0);
            $table->smallInteger('goals_against')->default(0);
            $table->smallInteger('goal_difference')->default(0);
            $table->unsignedSmallInteger('points')->default(0);
            $table->timestamps();

            $table->unique(['tournament_id', 'group', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standings');
    }
};
