<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'lab_id',
        'day_name',
        'start_time',
        'end_time',
        'subject_name',
        'class_group',
        'instructor_name',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
        ];
    }

    public function setStartTimeAttribute(mixed $value): void
    {
        $this->attributes['start_time'] = $value instanceof \DateTimeInterface ? $value->format('H:i') : substr((string) $value, 0, 5);
    }

    public function setEndTimeAttribute(mixed $value): void
    {
        $this->attributes['end_time'] = $value instanceof \DateTimeInterface ? $value->format('H:i') : substr((string) $value, 0, 5);
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }
}
