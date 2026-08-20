<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Lab;
use App\Models\Schedule;
use App\Services\ScheduleCollisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    public function index(Request $request): View
    {
        $labs = Lab::orderBy('id')->get();
        $selectedLab = $labs->firstWhere('id', $request->integer('lab_id')) ?? $labs->first();

        if (! $selectedLab) {
            return view('schedules.index', [
                'labs' => $labs,
                'selectedLab' => null,
                'ranges' => collect(),
                'scheduleMap' => collect(),
                'bookingMap' => collect(),
                'bookings' => collect(),
                'canManage' => in_array($request->user()->role, ['super_admin', 'teknisi']),
                'canBook' => in_array($request->user()->role, ['super_admin', 'teknisi', 'instruktur']),
            ]);
        }

        $schedules = Schedule::where('lab_id', $selectedLab->id)->get();
        $bookings = Booking::where('lab_id', $selectedLab->id)
            ->orderByRaw("FIELD(day_name, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday')")
            ->orderBy('start_time')
            ->get();

        $ranges = $schedules
            ->map(fn ($s) => ['start' => $s->start_time->format('H:i'), 'end' => $s->end_time->format('H:i')])
            ->concat($bookings->where('status', 'APPROVED')->map(fn ($b) => ['start' => $b->start_time->format('H:i'), 'end' => $b->end_time->format('H:i')]))
            ->unique(fn ($r) => $r['start'].'-'.$r['end'])
            ->sortBy('start')
            ->values();

        $scheduleMap = $schedules->keyBy(fn ($s) => $s->day_name.'|'.$s->start_time->format('H:i'));
        $bookingMap = $bookings
            ->where('status', 'APPROVED')
            ->keyBy(fn ($b) => $b->day_name.'|'.$b->start_time->format('H:i'));

        return view('schedules.index', [
            'labs' => $labs,
            'selectedLab' => $selectedLab,
            'ranges' => $ranges,
            'scheduleMap' => $scheduleMap,
            'bookingMap' => $bookingMap,
            'bookings' => $bookings,
            'canManage' => in_array($request->user()->role, ['super_admin', 'teknisi']),
            'canBook' => in_array($request->user()->role, ['super_admin', 'teknisi', 'instruktur']),
        ]);
    }

    public function store(Request $request, ScheduleCollisionService $collision): RedirectResponse
    {
        $data = $request->validate([
            'lab_id' => 'required|exists:labs,id',
            'day_name' => 'required|in:'.implode(',', self::DAYS),
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'subject_name' => 'required|string|max:100',
            'class_group' => 'required|string|max:50',
            'instructor_name' => 'required|string|max:100',
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
                ->with('error', "Jadwal bentrok dengan {$clash['kind']} {$clash['label']} pada {$data['day_name']} jam {$clash['time']}.");
        }

        Schedule::create($data);

        return back()
            ->with('success', "Jadwal {$data['subject_name']} ({$data['class_group']}) berhasil ditambahkan.");
    }

    public function update(Request $request, Schedule $schedule, ScheduleCollisionService $collision): RedirectResponse
    {
        $data = $request->validate([
            'lab_id' => 'required|exists:labs,id',
            'day_name' => 'required|in:'.implode(',', self::DAYS),
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'subject_name' => 'required|string|max:100',
            'class_group' => 'required|string|max:50',
            'instructor_name' => 'required|string|max:100',
        ]);

        $clash = $collision->check(
            $data['lab_id'],
            $data['day_name'],
            $data['start_time'],
            $data['end_time'],
            excludeScheduleId: $schedule->id,
        );

        if ($clash) {
            return back()
                ->withInput()
                ->with('error', "Jadwal bentrok dengan {$clash['kind']} {$clash['label']} pada {$data['day_name']} jam {$clash['time']}.");
        }

        $schedule->update($data);

        return back()
            ->with('success', "Jadwal {$data['subject_name']} berhasil diperbarui.");
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        $label = $schedule->subject_name;
        $schedule->delete();

        return back()
            ->with('success', "Jadwal {$label} berhasil dihapus.");
    }
}
