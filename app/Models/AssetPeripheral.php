<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetPeripheral extends Model
{
    use HasFactory;

    protected $fillable = [
        'peripheral_code',
        'asset_id',
        'type',
        'brand',
        'model_name',
        'serial_number',
        'condition',
        'location_note',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
