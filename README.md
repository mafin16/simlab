# SIMLAB — Sistem Pengelolaan Laboratorium Komputer

> Aplikasi manajemen lab komputer multi-lab: aset & periferal, jadwal & booking, helpdesk tiket Kanban, seat mapping live, presensi QR, dan laporan PDF/Excel.
> Tech stack: **Laravel 12 + MySQL + Tailwind CSS + Alpine.js + Chart.js**

![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)
![PHP ^8.2](https://img.shields.io/badge/PHP-%5E8.2-777BB4)
![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20)

## Fitur Utama
* **Dashboard** — ringkasan aset, tiket, jadwal hari ini (Chart.js doughnut + bar).
* **Manajemen Aset** — CRUD PC, periferal, QR per aset (`/assets`), import/export Excel (PhpSpreadsheet), template.
* **Penjadwalan** — kalender mingguan, booking recurring `day_name`, deteksi bentrok (`ScheduleCollisionService`).
* **Helpdesk** — Kanban 3 kolom (Open/In Progress/Resolved), SLA (High 2j / Medium 24j / Low 48j), sinkron status PC.
* **Seat Mapping & Presensi** — denah grid per lab, polling 10 detik, check-in publik tanpa login via QR (`/checkin/{asset_code}`), auto clear session.
* **Laporan & Audit** — hub `/reports` (PDF DomPDF portrait A4 + Excel), riwayat tiket filter & pagination (`/tickets/history`).

## Quick Start

```bash
git clone https://github.com/mafin16/simlab.git
cd simlab
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
# atau via Apache VirtualHost -> DocumentRoot ke /public
```

`.env.example` default `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY=` kosong — **wajib** `php artisan key:generate` sebelum run. Jangan copy `APP_KEY` dari dev ke produksi.

## Akun Default (Seeder — Dev Only)

> ⚠️ **Hanya untuk development/testing. Ganti password segera setelah `db:seed` di server publik/demo. Jangan pakai di produksi!**

| Role | Email (dummy `.test`) | Password | Catatan |
|------|----------------------|----------|---------|
| Super Admin | admin@simlab.test | `password` | WAJIB GANTI |
| Teknisi | teknisi@simlab.test | `password` | WAJIB GANTI |
| Instruktur | instruktur@simlab.test | `password` | WAJIB GANTI |
| Siswa | siswa@simlab.test | `password` | view-only seatmap |

Seeder: `database/seeders/DatabaseSeeder.php` (2 Lab: 20+15 PC, 20 jadwal). Factory pakai `faker` untuk data dummy. Domain `.test` reserved (RFC2606), bukan email asli.

## Konfigurasi Penting

* `APP_URL` sesuaikan (default `http://localhost`). Untuk akses LAN: `php artisan serve --host=0.0.0.0 --port=8080`.
* `DB_CONNECTION=sqlite` di `.env.example` untuk dev cepat; ganti ke `mysql` untuk produksi (`DB_HOST`, `DB_DATABASE=simlab`, `DB_PASSWORD` kuat — jangan kosong seperti XAMPP default).
* `LOG_LEVEL=debug` untuk dev, `warning` untuk prod. `SESSION_ENCRYPT=false` default, set `true` jika butuh.
* Upload/QR eksternal: `resources/views/assets/show.blade.php` pakai `https://api.qrserver.com` — butuh internet, atau ganti ke `bacon/bacon-qr-code` offline.

## Struktur Lab (Contoh)

* `LAB-1` / `LAB-2` (multi-lab single deploy).
* `asset_code` `LAB1-PC-01..`, IP dummy `192.168.x.x` / `10.x` private, QR encode URL `http://<host>/checkin/{code}`.

## Testing

```bash
php artisan test
# 138 tests (420 assertions) — coverage RBAC, tiketing, seatmap, laporan PDF/Excel
php artisan pint
npm run build
```

## Keamanan untuk Mode Public

* `.env`, `/vendor`, `/node_modules`, `/storage/logs/*`, `/database/*.sqlite`, `/bootstrap/cache/*`, `*.key` sudah di `.gitignore:9` & `.gitattributes:10` `export-ignore`.
* Rate-limit check-in publik (`throttle:10,1` di `routes/web.php:98`).
* `Cache-Control: no-store` di download PDF/Excel (`ReportController`).
* Lihat `SECURITY.md` untuk pelaporan vuln, `CONTRIBUTING.md` untuk workflow, `Roadmap.md` untuk changelog (sudah diredaksi generic).

## Roadmap

Lihat `Roadmap.md` — Fase 1-6 done (Foundation s.d. Laporan & Polish). Detail teknis di `Architecture.md`, `Database.md`, `Rules.md`.

## License

MIT — Copyright (c) 2026 **mafin** — lihat `LICENSE`.

## Kontak

Author: **mafin** <hello@mafin.dev> — branding personal. Issues & PR welcome.
