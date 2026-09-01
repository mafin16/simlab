# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| main (latest) | ✅ |

## Reporting a Vulnerability

Jangan buka issue publik untuk vuln sensitif. Hubungi **mafin <hello@mafin.dev>** dengan subjek `[SIMLAB SECURITY]`.

Sertakan: deskripsi, langkah reproduksi, dampak, dan versi commit (`git rev-parse HEAD`). Kami targetkan respon awal 48 jam.

Harap jangan melakukan DoS, akses data pihak lain, atau spam check-in publik (`/checkin/{code}` rate-limited 10/min). Untuk test check-in gunakan lab dummy lokal.

## Hardening untuk Deploy Publik

* `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning`, generate `APP_KEY` baru (jangan reuse dev key).
* `DB_PASSWORD` kuat, `SESSION_ENCRYPT=true` jika sensitif.
* `.env`, `*.sqlite`, `storage/logs/*`, `bootstrap/cache/*`, `*.key` tidak ikut commit (sudah di `.gitignore` & `.gitattributes`).
