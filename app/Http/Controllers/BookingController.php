<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\ScheduleCollisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function store(Request $request, ScheduleCollisionService $collision): RedirectResponse
    {
        $data = $request->validate([
            'lab_id' => 'required|exists:labs,id',
            'day_name' => 'required|in:'.implode(',', ScheduleController::DAYS),
            'applicant_name' => 'required|string|max:100',
            'event_name' => 'required|string|max:150',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $clash = $collision->check(
            $data['lab_id'],
            $data['day_name'],
            $data['start_time'],
            $data['end_time'],
        );

        if ($clash) {
            return back()
                ->withInput()
                ->with('error', "Booking ditolak: bentrok dengan {$clash['kind']} {$clash['label']} pada {$data['day_name']} jam {$clash['time']}.");
        }

        Booking::create($data + ['status' => 'APPROVED']);

        return back()
            ->with('success', "Booking '{$data['event_name']}' oleh {$data['applicant_name']} berhasil disetujui (berulang tiap {$data['day_name']}).");
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:APPROVED,REJECTED',
        ]);

        $booking->update($data);

        return back()
            ->with('success', "Status booking '{$booking->event_name}' diubah menjadi {$data['status']}.");
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        $label = $booking->event_name;
        $booking->delete();

        return back()
            ->with('success', "Booking '{$label}' berhasil dihapus.");
    }
}
