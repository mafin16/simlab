# SIMLAB Development Roadmap

> Sistem Pengelolaan Laboratorium Komputer
> Tech Stack: Laravel 12 + MySQL + Tailwind CSS + Alpine.js + Chart.js

## Progress

| Fase | Nama | Status | Start | Done | Notes |
|------|------|--------|-------|------|-------|
| 1 | Foundation & Core | ✅ Done | 2026-08-20 | 2026-08-20 | Laravel 12, DB schema, Auth+RBAC, Dashboard |
| 2 | Manajemen Aset & Periferal | ✅ Done | 2026-08-20 | 2026-08-20 | CRUD aset, periferal, QR Code, Import/Export Excel |
| 3 | Penjadwalan & Reservasi Lab | ✅ Done | 2026-08-20 | 2026-08-20 | Kalender mingguan, booking recurring mingguan, collision detection auto-reject |
| 4 | Helpdesk & Tiket (Kanban) | ⬜ Not Started | - | - | Kanban 3 kolom, auto-escalasi status PC, SLA |
| 5 | Seat Mapping & Presensi | ⬜ Not Started | - | - | Denah interaktif, check-in QR, polling AJAX |
| 6 | Laporan & Polish | ⬜ Not Started | - | - | Export Excel/PDF, Dark/Light mode, responsive |

## Keputusan Teknis (Technical Decisions)

| Tanggal | Keputusan |
|---------|-----------|
| 2026-08-20 | Tech stack final: Laravel 12 + MySQL 8 (XAMPP). Backend bukan Node.js/Go seperti Architecture.md, disesuaikan dengan environment. |
| 2026-08-20 | Laravel 11 punya security advisories yang diblokir Composer, jadi upgrade ke Laravel 12 (stable). |
| 2026-08-20 | Real-time pakai AJAX polling dulu (bukan WebSocket/Socket.io), upgradeable ke WebSocket nanti. |
| 2026-08-20 | Auth fase awal: Admin & Guru/Instruktur pakai email+password. Siswa ditunda (nanti, kemungkinan hanya untuk pelaporan kerusakan via QR). |
| 2026-08-20 | Multi-lab: 2 lab dikelola dalam 1 sistem (multi-lab), bukan deploy terpisah. |
| 2026-08-20 | Frontend: Blade + Tailwind CSS + Alpine.js (sesuai spec Architecture.md opsi lightweight). |
| 2026-08-20 | DB test terpisah (`simlab_test`) di phpunit.xml supaya test tidak menghapus data produksi (`simlab`). |
| 2026-08-20 | Excel pakai `phpoffice/phpspreadsheet ^5.9` (berhasil diinstall setelah network terbuka). Import/Export/Template aset full PhpSpreadsheet, siap juga untuk Laporan (Fase 6). |
| 2026-08-20 | Penjadwalan (Fase 3) pakai pola recurring mingguan berbasis `day_name` (Senin–Sabtu), bukan tanggal spesifik — sesuai prototype. `bookings.booking_date` diganti `day_name`. `schedules.time_slot` varchar diganti kolom `start_time`/`end_time` (time) agar mudah collision detection. |
| 2026-08-20 | Collision detection dipusatkan di `App\Services\ScheduleCollisionService` — dipakai ScheduleController & BookingController, langsung reject tanpa konfirmasi (kesepakatan). |

## Fase 1 — Detail Tasks

- [x] Setup Laravel 12 + konfigurasi .env (MySQL XAMPP)
- [x] Install & konfigurasi Tailwind CSS + Alpine.js
- [x] Migration: labs, assets, asset_peripherals, tickets, schedules, bookings, presences
- [x] Seed: 2 Labs (Lab 1: 20 PC, Lab 2: 15 PC) status Ready + 3 user default + 20 jadwal mingguan (Fase 3)
- [x] Auth: Laravel Breeze (login/logout)
- [x] RBAC: super_admin, teknisi, instruktur, siswa + middleware `role`
- [x] Dashboard: summary metrics + Doughnut Chart (Chart.js) + widget tiket
- [x] 29 test pass, Pint formatting clean, login flow verified di browser

## Akun Default (untuk testing)

| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@simlab.test | password |
| Teknisi | teknisi@simlab.test | password |
| Instruktur | instruktur@simlab.test | password |

## Changelog

| Tanggal | Perubahan |
|---------|-----------|
| 2026-08-20 | Roadmap.md dibuat, mulai eksekusi Fase 1 |
| 2026-08-20 | Fase 1 selesai: Laravel 12 setup, semua migration, seed 2 lab + 35 PC, Breeze auth, RBAC middleware, dashboard dengan Chart.js doughnut, 29 test pass |
| 2026-08-20 | Revisi UI: layout top-nav diganti jadi sidebar layout (fixed sidebar kiri + top bar + content area). User dropdown pindah ke top bar dengan Edit Profil, Ubah Password, Logout. Fix dark mode via `darkMode: 'class'` + `<html class="dark">`. Komponen Breeze (dropdown, modal, input, button) diupdate ke tema slate/blue konsisten. Login page + guest layout di-restyle dengan branding SIMLAB. |
| 2026-08-20 | Route register dimatikan (404) — pembuatan akun public ditutup, akun dibuat admin via user management (nanti di Fase 2). Test register diupdate. |
| 2026-08-20 | Revisi UI: tampilan disamakan dengan prototype.html. Sidebar w-64 + brand header gradient + dropdown "Pilih Ruang Lab" + 6 menu dengan ikon FontAwesome + user card di bawah. Topbar: toggle sidebar, theme toggle, live clock, tombol Lapor Kendala & Tambah Aset. Dashboard: 4 kartu statistik + doughnut & bar chart + widget tiket & jadwal. FontAwesome diinstall via npm. Light-mode overrides & glass-panel ditambahkan di app.css. Test dashboard diupdate sesuai teks UI baru. |
| 2026-08-20 | Dropdown "Pilih Ruang Lab" di sidebar jadi fungsional (filter `?lab_id=`): Semua Lab = 35 PC, Lab 1 = 20 PC, Lab 2 = 15 PC. DashboardController di-scope by lab_id untuk semua query (assets, tickets, presence). Bar chart okupansi & widget jadwal dikosongkan (empty state) karena modul penjadwalan belum dibangun. Test lab filtering ditambahkan. |
| 2026-08-20 | Fase 2 selesai: CRUD aset + periferal lengkap, QR code via api.qrserver.com, Import/Export & template Excel. `composer require phpoffice/phpspreadsheet` gagal (DNS packagist/GitHub diblokir), fallback: helper pure-PHP `App\Support\Xlsx` (ZipArchive + SimpleXML) — output & parsing .xlsx asli tanpa dependency. View aset (index, create, edit, show), sidebar & topbar link diaktifkan, filter (search/lab/status/category), role teknisi full akses. Test aset 16 test + 46 total pass, Pint clean, npm build OK. |
| 2026-08-20 | Network kembali terbuka (VPN). `phpoffice/phpspreadsheet ^5.9` berhasil diinstall. Export/Import/Template di-migrasi dari helper `App\Support\Xlsx` ke PhpSpreadsheet: styling header (bold + fill biru), auto column width, freeze baris pertama, title sheet, dukungan .xls. Helper pure-PHP dihapus. 46 test tetap pass. |
| 2026-08-20 | Fase 3 selesai: jadwal & booking lab. Migration (bookings day_name, schedules start/end_time), `ScheduleCollisionService`, ScheduleController + BookingController, halaman `schedules/index` dengan weekly timetable grid 7 kolom + modal booking/tambah jadwal/edit + daftar booking mingguan, sidebar F3 aktif, dashboard diisi data jadwal hari ini + bar chart okupansi mingguan, ScheduleSeeder (20 jadwal, 2 lab), 74 test pass (198 assertions). |