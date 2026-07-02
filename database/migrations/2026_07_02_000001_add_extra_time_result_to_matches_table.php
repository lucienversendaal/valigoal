<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            // Net als bij strafschoppen bewaren we de eindstand na verlenging
            // apart (home_score/away_score blijven de stand na 90 minuten).
            // football-data levert de verlengingsgoals soms niet mee, dus mag
            // dit ook handmatig worden ingevuld.
            $table->unsignedTinyInteger('extra_time_home_score')->nullable()->after('penalty_away_score');
            $table->unsignedTinyInteger('extra_time_away_score')->nullable()->after('extra_time_home_score');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['extra_time_home_score', 'extra_time_away_score']);
        });
    }
};
