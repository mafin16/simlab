# Contributing to SIMLAB

Terima kasih mau kontribusi bre! Ikuti panduan ini biar repo tetap aman untuk mode public.

## Setup Dev

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan test
```

## Workflow

* Branch `main` protected — buat branch fitur, PR, jangan `git push --force` ke `main` (force hanya untuk history rewrite admin).
* `php artisan pint` & `php artisan test` harus hijau sebelum PR.
* Jangan commit `.env`, `*.sqlite`, `storage/logs/*`, `bootstrap/cache/*`, `vendor/`, `node_modules/` — sudah di `.gitignore:9`.

## Kredensial Dummy

Seeder pakai `xxx@simlab.test / password` (dummy `.test`). **Jangan** pakai data real (NISN/nama siswa) di PR. Selalu ganti password setelah seed di demo publik.

## Commit Identity

Gunakan identity brand: `mafin <hello@mafin.dev>` — `git config user.name "mafin"` & `user.email "hello@mafin.dev"`. Mapping lama via `.mailmap`.
