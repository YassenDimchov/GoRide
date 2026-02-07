<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->unsignedInteger('trip_distance_m')->nullable()->after('end_lng');
            $table->unsignedInteger('trip_duration_s')->nullable()->after('trip_distance_m');
            $table->decimal('estimated_fare', 8, 2)->nullable()->after('trip_duration_s');
        });
    }

    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropColumn([
                'trip_distance_m',
                'trip_duration_s',
                'estimated_fare',
            ]);
        });
    }
};

