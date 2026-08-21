# SIMLAB Development Roadmap

> Sistem Pengelolaan Laboratorium Komputer
> Tech Stack: Laravel 12 + MySQL + Tailwind CSS + Alpine.js + Chart.js

## Progress

| Fase | Nama | Status | Start | Done | Notes |
|------|------|--------|-------|------|-------|
| 1 | Foundation & Core | ✅ Done | 2026-08-20 | 2026-08-20 | Laravel 12, DB schema, Auth+RBAC, Dashboard |
| 2 | Manajemen Aset & Periferal | ✅ Done | 2026-08-20 | 2026-08-20 | CRUD aset, periferal, QR Code, Import/Export Excel |
| 3 | Penjadwalan & Reservasi Lab | ✅ Done | 2026-08-20 | 2026-08-20 | Kalender mingguan, booking recurring mingguan, collision detection auto-reject |
| 4 | Helpdesk & Tiket (Kanban) | ✅ Done | 2026-08-21 | 2026-08-21 | Kanban 3 kolom, auto-escalasi status PC, SLA |
| 5 | Seat Mapping & Presensi | ⬜ Not Started | - | - | Denah interaktif, check-in QR, polling AJAX |
| 6 | Laporan & Polish | ⬜ Not Started | - | - | Export Excel/PDF, halaman Riwayat Tiket (tabel+pagination), Dark/Light mode, responsive |

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
| 2026-08-21 | Fase 4: form tiket pakai dropdown prioritas (High/Medium/Low, default Medium) + tombol hapus tiket untuk super_admin & teknisi. Matriks role: lihat board + buat tiket = super_admin, teknisi, instruktur; proses/selesaikan/hapus = super_admin, teknisi; siswa 403. |
| 2026-08-21 | Generator kode tiket (`Ticket::nextCode()`) berbasis suffix kode terakhir (max `ticket_code`), bukan `max(id)+1` — id MySQL auto-increment bisa loncat (rollback/delete) sehingga kode jadi tidak berurutan. |

## Fase 1 — Detail Tasks

- [x] Setup Laravel 12 + konfigurasi .env (MySQL XAMPP)
- [x] Install & konfigurasi Tailwind CSS + Alpine.js
- [x] Migration: labs, assets, asset_peripherals, tickets, schedules, bookings, presences
- [x] Seed: 2 Labs (Lab 1: 20 PC, Lab 2: 15 PC) status Ready + 3 user default + 20 jadwal mingguan (Fase 3)
- [x] Auth: Laravel Breeze (login/logout)
- [x] RBAC: super_admin, teknisi, instruktur, siswa + middleware `role`
- [x] Dashboard: summary metrics + Doughnut Chart (Chart.js) + widget tiket
- [x] 29 test pass, Pint formatting clean, login flow verified di browser

## Fase 4 — Detail Tasks

- [x] Model `Ticket`: konstanta STATUSES/PRIORITIES/COMPONENTS, SLA_HOURS (High=2, Medium=24, Low=48 jam), mapping komponen→status aset, helper `slaDueAt()`/`isOverdue()`/`nextCode()`
- [x] TicketController: index (board + stats), store (auto-escalasi status PC), start, resolve, destroy
- [x] Routes `/tickets` dengan middleware role berlapis
- [x] TicketFactory + DatabaseSeeder (data tiket contoh dihapus — mulai dari data nyata user)
- [x] View `tickets/index`: Kanban 3 kolom (Open/In Progress/Resolved), badge prioritas & SLA, modal lapor + selesaikan + hapus
- [x] Sidebar menu Helpdesk aktif, topbar "Lapor Kendala" context-aware (modal langsung saat sudah di halaman tiket), dashboard widget link "Lihat Semua"
- [x] Logika status PC Model A: status = kondisi fisik per severity komponen; `syncAssetStatus()` recompute dari sisa tiket aktif saat resolve/hapus (guard Scrapped); In Progress tidak mengubah status; kolom Resolved dibatasi 5 terbaru
- [x] TicketTest: 25 test (RBAC, validasi, auto-escalasi, sync status multi-tiket, SLA guard, kode sekuensial, endpoint modal resolve, limit kolom resolved)
- [x] 99 test pass (265 assertions), Pint clean

## Fase 6 — Catatan Desain Awal

- Halaman **Riwayat Tiket**: tabel semua tiket (termasuk resolved lama yang tidak tampil di Kanban) + pagination + filter + export Excel/PDF. Kanban board hanya menampilkan 5 tiket resolved terbaru agar ringkas.

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
| 2026-08-21 | Bugfix: chart dashboard (doughnut & bar okupansi) tidak render — inline script di `@push('scripts')` dieksekusi sebelum module `app.js` mendefinisikan `window.Chart` (module script bersifat deferred) sehingga `new Chart(...)` throw ReferenceError. Fix: inisialisasi chart dibungkus listener `DOMContentLoaded`. Keputusan desain: fitur booking dipertahankan utuh (termasuk panel Daftar Booking Mingguan) — booking adalah kanal input untuk instruktur/guru, sedangkan jadwal praktikum hanya dikelola admin/teknisi. |
| 2026-08-21 | Bugfix: ikon panel "Kendala / Servis" di dashboard tidak tampil — `fa-shield-check` ternyata icon Pro-only FontAwesome 6 (tidak ada di package free). Diganti `fa-shield-halved`. Audit semua views: 45 ikon unik dipakai, hanya 1 yang invalid. |
| 2026-08-21 | Bugfix: panel "Jadwal Praktikum Hari Ini" menampilkan jadwal hari sebelumnya — app timezone masih UTC (default Laravel), sehingga `now()`/`today()` salah hari selama jam 00:00–06:59 WIB dan semua timestamp tampil geser -7 jam. Fix: `config/app.php` timezone diganti `Asia/Jakarta`. Live clock topbar tidak terdampak (sudah client-side JS WIB). |
| 2026-08-21 | Fase 4 selesai: Helpdesk & Tiket Kanban. Model Ticket + konstanta SLA, TicketController (store/start/resolve/destroy) dengan auto-escalasi status PC per komponen rusak (Mouse/Keyboard/LAN/Audio→Degraded, Monitor/Booting→Maintenance, Resolved→Ready dengan guard tidak menurunkan severity & tidak override Scrapped), kode tiket TKT-XXXX sekuensial, halaman Kanban 3 kolom + badge SLA overdue merah, sidebar F4 aktif, TicketSeeder 6 tiket, 92 test pass (241 assertions). |
| 2026-08-21 | Bugfix: dropdown filter lab di halaman Helpdesk tertutup panel Kanban — `.glass-panel` memakai `backdrop-filter` yang menciptakan stacking context baru sehingga `z-50` dropdown terkurung di dalam panelnya dan panel sibling sesudahnya menimpa. Fix: tambah `relative z-30` di header panel (pola yang sama sudah dipakai halaman assets & schedules). |
| 2026-08-21 | Revisi UX tombol "Lapor Kendala" di topbar: sebelumnya hanya anchor reload ke tickets.index sehingga saat diklik dari halaman Helpdesk itu sendiri tidak ada efek yang terlihat. Sekarang context-aware — jika sudah di halaman tiket, modal "Lapor Kerusakan" langsung dibuka via `$dispatch('open-modal')` tanpa reload; jika dari halaman lain, tetap navigasi ke halaman Helpdesk. `href` dipertahankan agar middle-click tetap berfungsi. |
| 2026-08-21 | Bugfix: submit modal "Selesaikan Tiket" error 405 — action form hardcoded `/tickets/{id}` (POST) padahal route resolve adalah `POST /tickets/{id}/resolve`. Fix: suffix `/resolve` ditambahkan di action Alpine + regression test baru (`resolve modal form targets resolve endpoint`) yang memastikan view memuat endpoint benar. 19 test TicketTest pass. |
| 2026-08-21 | Kolom Resolved di Kanban dibatasi 10 tiket terbaru (urut `resolved_at` desc) agar board tidak memanjang tanpa batas; badge tetap menampilkan total semua tiket selesai + catatan "Menampilkan 10 terbaru dari N". Keputusan: riwayat penuh disajikan lewat halaman Riwayat Tiket di Fase 6. |
| 2026-08-21 | Eksperimen UI ditolak & di-revert: percobaan mengganti kartu Kanban dengan 3 tabel ber-paginasi independen (checkpoint `acf9854`) tidak sesuai selera user — tampilan Kartu Kanban dipertahankan sebagai tampilan final halaman Helpdesk. Rollback via `git restore` 4 file trial, test tetap hijau. |
| 2026-08-21 | Perbaikan logika status PC (Opsi B): `syncAssetStatus()` menghitung ulang status PC dari sisa tiket aktif (`!= Resolved`, severity tertinggi menang, guard Scrapped) — dipakai di `resolve()` & `destroy()`. Fix 2 bug: (1) hapus tiket tidak pernah mengembalikan status PC, (2) resolve 1 dari 2 tiket aktif memaksa PC langsung Ready. Keputusan desain Model A: status PC = kondisi fisik dari severity komponen; In Progress tidak mengubah status PC; Open & In Progress sama-sama dihitung aktif. Pesan sukses resolve kini dinamis sesuai status akhir PC. +5 test baru. |
| 2026-08-21 | Revisi: limit kolom Resolved di Kanban diturunkan dari 10 menjadi 5 tiket terbaru (keputusan user; riwayat lengkap tetap diserahkan ke laporan Fase 6). Test limit direvisi mengikuti (`test_resolved_column_shows_only_five_newest`). |
| 2026-08-21 | Data tiket contoh dibuang: TicketSeeder dihapus dari DatabaseSeeder + file seeder dihapus, database di-fresh — tabel `tickets` kosong (fitur Lapor Kendala mulai dari data nyata user). Labs/assets/schedules/users tetap ter-seed. TicketFactory dipertahankan untuk kebutuhan test. |