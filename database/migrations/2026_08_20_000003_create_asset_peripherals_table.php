<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_peripherals', function (Blueprint $table) {
            $table->id();
            $table->string('peripheral_code', 50)->unique();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('brand', 50);
            $table->string('model_name', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('condition', 50)->default('Baik / Normal');
            $table->string('location_note', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_peripherals');
    }
};
