<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Ticket extends Model
{
    use HasFactory;

    public const STATUSES = ['Open', 'In Progress', 'Resolved'];

    public const PRIORITIES = ['High', 'Medium', 'Low'];

    public const COMPONENTS = [
        'Mouse / Keyboard',
        'Monitor Blank / Flashing',
        'PC Tidak Bisa Booting / OS Error',
        'Koneksi Jaringan LAN Putus',
        'Audio / Headset Mati',
    ];

    public const SLA_HOURS = [
        'High' => 2,
        'Medium' => 24,
        'Low' => 48,
    ];

    public const COMPONENT_ASSET_STATUS = [
        'Mouse / Keyboard' => 'Degraded',
        'Koneksi Jaringan LAN Putus' => 'Degraded',
        'Audio / Headset Mati' => 'Degraded',
        'Monitor Blank / Flashing' => 'Maintenance',
        'PC Tidak Bisa Booting / OS Error' => 'Maintenance',
    ];

    protected $fillable = [
        'ticket_code',
        'asset_id',
        'component_issue',
        'description',
        'priority',
        'status',
        'reporter_name',
        'technician_name',
        'resolution_notes',
        'reported_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function slaDueAt(): Carbon
    {
        return $this->reported_at->copy()->addHours(self::SLA_HOURS[$this->priority] ?? 24);
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'Resolved' && now()->gt($this->slaDueAt());
    }

    public static function nextCode(): string
    {
        $last = (int) substr((string) static::query()->max('ticket_code'), 4);

        do {
            $code = 'TKT-'.str_pad((string) (++$last), 4, '0', STR_PAD_LEFT);
        } while (static::query()->where('ticket_code', $code)->exists());

        return $code;
    }
}
