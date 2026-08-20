<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('day_name');
            $table->time('end_time')->nullable()->after('start_time');
        });

        // Populate dari time_slot lama ("07:00-09:15")
        DB::table('schedules')->get()->each(function ($row) {
            $parts = preg_split('/[-\u2013]/', (string) $row->time_slot);
            if (count($parts) === 2) {
                DB::table('schedules')->where('id', $row->id)->update([
                    'start_time' => trim($parts[0]),
                    'end_time' => trim($parts[1]),
                ]);
            }
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('time_slot');
            $table->index(['lab_id', 'day_name', 'start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex(['lab_id', 'day_name', 'start_time', 'end_time']);
            $table->string('time_slot', 30)->after('day_name');
        });

        DB::table('schedules')->get()->each(function ($row) {
            if ($row->start_time && $row->end_time) {
                DB::table('schedules')->where('id', $row->id)->update([
                    'time_slot' => substr($row->start_time, 0, 5).'-'.substr($row->end_time, 0, 5),
                ]);
            }
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']);
        });
    }
};
