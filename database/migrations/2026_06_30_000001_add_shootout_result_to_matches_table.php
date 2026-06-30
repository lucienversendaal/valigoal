<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            // home_score/away_score blijven de stand na 90 minuten (regulier),
            // want voorspellingen worden daartegen afgerekend. Bij knockout-
            // duels die in de verlenging of via strafschoppen beslist zijn
            // bewaren we het beslissingstype + de strafschoppenreeks apart.
            $table->string('decided_by')->nullable()->after('away_score');
            $table->unsignedTinyInteger('penalty_home_score')->nullable()->after('decided_by');
            $table->unsignedTinyInteger('penalty_away_score')->nullable()->after('penalty_home_score');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['decided_by', 'penalty_home_score', 'penalty_away_score']);
        });
    }
};
