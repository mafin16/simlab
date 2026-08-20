<?php

namespace Database\Seeders;

use App\Models\Lab;
use App\Models\Schedule;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $labs = Lab::all();

        if ($labs->isEmpty()) {
            return;
        }

        $template = [
            ['day' => 'Monday', 'start' => '07:00', 'end' => '09:15', 'subject' => 'Pemrograman Web', 'class' => 'XII RPL 1', 'instructor' => 'Pak Hendra, S.Kom'],
            ['day' => 'Monday', 'start' => '09:30', 'end' => '11:45', 'subject' => 'Pemrograman Mobile', 'class' => 'XII RPL 2', 'instructor' => 'Bu Rina'],
            ['day' => 'Tuesday', 'start' => '07:00', 'end' => '09:15', 'subject' => 'Basis Data', 'class' => 'XI RPL 2', 'instructor' => 'Pak Budi'],
            ['day' => 'Tuesday', 'start' => '13:00', 'end' => '15:15', 'subject' => 'Pemrograman Web', 'class' => 'XII RPL 1', 'instructor' => 'Pak Hendra, S.Kom'],
            ['day' => 'Wednesday', 'start' => '07:00', 'end' => '09:15', 'subject' => 'Pemrograman Berorientasi Objek', 'class' => 'XII RPL 1', 'instructor' => 'Pak Dedi'],
            ['day' => 'Wednesday', 'start' => '13:00', 'end' => '15:15', 'subject' => 'Proyek Kreatif', 'class' => 'XI RPL 1', 'instructor' => 'Bu Sari'],
            ['day' => 'Thursday', 'start' => '07:00', 'end' => '09:15', 'subject' => 'Prak. Jaringan Dasar', 'class' => 'XI RPL 1', 'instructor' => 'Pak Agus'],
            ['day' => 'Thursday', 'start' => '09:30', 'end' => '11:45', 'subject' => 'IoT & Embedded', 'class' => 'XI RPL 2', 'instructor' => 'Pak Agus'],
            ['day' => 'Friday', 'start' => '07:00', 'end' => '09:15', 'subject' => 'Pemutakhiran System OS', 'class' => 'XII RPL 2', 'instructor' => 'Teknisi Lab'],
            ['day' => 'Saturday', 'start' => '07:00', 'end' => '09:15', 'subject' => 'Ekstrakurikuler Coding', 'class' => 'Komunitas', 'instructor' => 'Kakak Mentoring'],
        ];

        foreach ($labs as $lab) {
            foreach ($template as $item) {
                Schedule::create([
                    'lab_id' => $lab->id,
                    'day_name' => $item['day'],
                    'start_time' => $item['start'],
                    'end_time' => $item['end'],
                    'subject_name' => $item['subject'],
                    'class_group' => $item['class'],
                    'instructor_name' => $item['instructor'],
                ]);
            }
        }
    }
}
