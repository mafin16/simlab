<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_code',
        'name',
        'lab_id',
        'seat_label',
        'category',
        'cpu_spec',
        'ram_gb',
        'ram_type',
        'storage_primary',
        'storage_secondary',
        'gpu_spec',
        'ip_address',
        'mac_address',
        'serial_number',
        'procurement_source',
        'purchase_date',
        'warranty_expiry',
        'status',
        'qr_code_url',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'warranty_expiry' => 'date',
        ];
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }

    public function peripherals(): HasMany
    {
        return $this->hasMany(AssetPeripheral::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function presences(): HasMany
    {
        return $this->hasMany(Presence::class);
    }
}
