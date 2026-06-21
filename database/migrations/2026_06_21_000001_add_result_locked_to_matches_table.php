<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            // When true the result was corrected by hand and the API sync must
            // not overwrite the score/winner anymore.
            $table->boolean('result_locked')->default(false)->after('points_awarded');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn('result_locked');
        });
    }
};
