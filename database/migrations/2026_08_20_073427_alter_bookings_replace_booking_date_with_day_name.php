<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('booking_date');
            $table->string('day_name', 15)->after('lab_id');
            $table->index(['lab_id', 'day_name', 'start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['lab_id', 'day_name', 'start_time', 'end_time']);
            $table->dropColumn('day_name');
            $table->date('booking_date');
        });
    }
};
