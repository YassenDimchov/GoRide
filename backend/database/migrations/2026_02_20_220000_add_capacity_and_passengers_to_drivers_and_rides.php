<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            if (! Schema::hasColumn('drivers', 'passenger_capacity')) {
                $table->unsignedTinyInteger('passenger_capacity')->default(4)->after('license_plate');
            }
        });

        Schema::table('rides', function (Blueprint $table) {
            if (! Schema::hasColumn('rides', 'passenger_count')) {
                $table->unsignedTinyInteger('passenger_count')->default(1)->after('end_address');
            }
        });

        DB::table('drivers')->whereNull('passenger_capacity')->update(['passenger_capacity' => 4]);
        DB::table('rides')->whereNull('passenger_count')->update(['passenger_count' => 1]);
    }

    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            if (Schema::hasColumn('rides', 'passenger_count')) {
                $table->dropColumn('passenger_count');
            }
        });

        Schema::table('drivers', function (Blueprint $table) {
            if (Schema::hasColumn('drivers', 'passenger_capacity')) {
                $table->dropColumn('passenger_capacity');
            }
        });
    }
};
