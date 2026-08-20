<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code', 50)->unique();
            $table->string('name', 100);
            $table->foreignId('lab_id')->constrained()->cascadeOnDelete();
            $table->string('seat_label', 30);
            $table->string('category')->default('PC Desktop');

            $table->string('cpu_spec', 100);
            $table->integer('ram_gb');
            $table->string('ram_type', 20)->default('DDR4');
            $table->string('storage_primary', 100);
            $table->string('storage_secondary', 100)->nullable();
            $table->string('gpu_spec', 100)->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('mac_address', 17)->nullable();
            $table->string('serial_number', 100)->nullable();

            $table->string('procurement_source', 100)->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->string('status')->default('Ready');
            $table->text('qr_code_url')->nullable();

            $table->timestamps();

            $table->index('lab_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
