<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Schedule;

class ScheduleCollisionService
{
    /**
     * Cek apakah rentang waktu baru bertabrakan dengan schedule/booking yang sudah ada
     * di lab & hari yang sama. Return null jika aman, atau array deskripsi bentrok.
     *
     * @param  string  $startTime  format "HH:MM"
     * @param  string  $endTime  format "HH:MM"
     * @param  int|null  $excludeScheduleId  skip saat update schedule
     * @param  int|null  $excludeBookingId  skip saat update booking
     * @return array{kind: string, label: string, time: string}|null
     */
    public function check(
        int $labId,
        string $dayName,
        string $startTime,
        string $endTime,
        ?int $excludeScheduleId = null,
        ?int $excludeBookingId = null,
    ): ?array {
        $schedule = Schedule::where('lab_id', $labId)
            ->where('day_name', $dayName)
            ->when($excludeScheduleId, fn ($q) => $q->where('id', '!=', $excludeScheduleId))
            ->get()
            ->first(fn ($s) => $this->overlaps(
                $startTime, $endTime,
                $s->start_time?->format('H:i') ?: '00:00',
                $s->end_time?->format('H:i') ?: '23:59',
            ));

        if ($schedule) {
            return [
                'kind' => 'jadwal',
                'label' => $schedule->subject_name,
                'time' => $schedule->start_time?->format('H:i').'–'.$schedule->end_time?->format('H:i'),
            ];
        }

        $booking = Booking::where('lab_id', $labId)
            ->where('day_name', $dayName)
            ->where('status', 'APPROVED')
            ->when($excludeBookingId, fn ($q) => $q->where('id', '!=', $excludeBookingId))
            ->get()
            ->first(fn ($b) => $this->overlaps(
                $startTime, $endTime,
                $b->start_time?->format('H:i') ?: '00:00',
                $b->end_time?->format('H:i') ?: '23:59',
            ));

        if ($booking) {
            return [
                'kind' => 'booking',
                'label' => $booking->event_name,
                'time' => $booking->start_time?->format('H:i').'–'.$booking->end_time?->format('H:i'),
            ];
        }

        return null;
    }

    /**
     * Deteksi tumpang-tindih dua rentang waktu (exclusive pada batas sama).
     */
    private function overlaps(string $aStart, string $aEnd, string $bStart, string $bEnd): bool
    {
        return $aStart < $bEnd && $aEnd > $bStart;
    }
}
