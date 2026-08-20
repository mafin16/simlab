<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Booking;
use App\Models\Lab;
use App\Models\Presence;
use App\Models\Schedule;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $labId = request('lab_id');
        $selectedLab = $labId ? Lab::find($labId) : null;

        $assetsQuery = Asset::where('category', 'PC Desktop');
        if ($labId) {
            $assetsQuery->where('lab_id', $labId);
        }

        $totalPc = (clone $assetsQuery)->count();

        $statusCounts = (clone $assetsQuery)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $ready = $statusCounts['Ready'] ?? 0;
        $degraded = $statusCounts['Degraded'] ?? 0;
        $maintenance = ($statusCounts['Maintenance'] ?? 0) + ($statusCounts['Scrapped'] ?? 0);

        $ticketsQuery = Ticket::with('asset');
        if ($labId) {
            $ticketsQuery->whereHas('asset', function ($q) use ($labId) {
                $q->where('lab_id', $labId);
            });
        }

        $activeTickets = (clone $ticketsQuery)->where('status', '!=', 'Resolved')->count();

        $todaySessions = Presence::whereDate('session_date', today())
            ->when($labId, fn ($q) => $q->whereHas('asset', fn ($q) => $q->where('lab_id', $labId)))
            ->distinct('asset_id')
            ->count('asset_id');

        $dayName = now()->format('l');
        $todayScheduleQuery = Schedule::where('day_name', $dayName)->with('lab');
        if ($labId) {
            $todayScheduleQuery->where('lab_id', $labId);
        }
        $todayScheduleCount = (clone $todayScheduleQuery)->count();
        $todaySchedules = (clone $todayScheduleQuery)->orderBy('start_time')->get();

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $dayLabels = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $occupancyValues = [];
        foreach ($days as $i => $d) {
            $count = Schedule::where('day_name', $d)
                ->when($labId, fn ($q) => $q->where('lab_id', $labId))
                ->count()
                + Booking::where('day_name', $d)
                    ->where('status', 'APPROVED')
                    ->when($labId, fn ($q) => $q->where('lab_id', $labId))
                    ->count();
            $occupancyValues[] = $count;
        }

        $occupancyData = [
            'labels' => $dayLabels,
            'values' => $occupancyValues,
        ];

        $doughnutData = [
            'labels' => ['Ready', 'Degraded', 'Maintenance'],
            'values' => [$ready, $degraded, $maintenance],
        ];

        $recentTickets = (clone $ticketsQuery)
            ->orderByDesc('reported_at')
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'totalPc',
            'ready',
            'degraded',
            'maintenance',
            'activeTickets',
            'todaySessions',
            'todayScheduleCount',
            'todaySchedules',
            'occupancyData',
            'doughnutData',
            'recentTickets',
            'selectedLab'
        ));
    }
}
