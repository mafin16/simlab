Architecture Specification Document
SIMLAB - Computer Laboratory Management System
1. Gambaran Umum Arsitektur (High-Level Architecture)
SIMLAB menggunakan arsitektur Single Page Application (SPA) yang dipadukan dengan RESTful API / GraphQL dan WebSocket Engine untuk komunikasi data secara real-time.
 +-----------------------------------------------------------------------+
 |                         CLIENT LAYER (BROWSER)                        |
 |                                                                       |
 |   +---------------------------------------------------------------+   |
 |   |          Tailwind CSS + Alpine.js / React (SPA UI)             |   |
 |   +---------------------------------------------------------------+   |
 |          |                                        ^                   |
 |     HTTPS / JSON                              WebSockets              |
 |          v                                        |                   |
 +----------|----------------------------------------|-------------------+
            |                                        |
 +----------|----------------------------------------|-------------------+
 |          v               SERVER LAYER             |                   |
 |   +---------------+                       +---------------+           |
 |   |  API Gateway  |                       | WebSocket Svc |           |
 |   |  (Nginx/Caddy)|                       |  (Socket.io)  |           |
 |   +---------------+                       +---------------+           |
 |           |                                       |                   |
 |           +-------------------+-------------------+                   |
 |                               |                                       |
 |                       +---------------+                               |
 |                       | Node.js / Go  |                               |
 |                       | App Backend   |                               |
 |                       +---------------+                               |
 |                               |                                       |
 +-------------------------------|---------------------------------------+
                                 |
 +-------------------------------|---------------------------------------+
 |                               v             DATA LAYER                |
 |   +---------------------------------------------------------------+   |
 |   |               PostgreSQL / MySQL Relational DB                |   |
 |   +---------------------------------------------------------------+   |
 |   |               Redis Cache (Session & Pub/Sub)                 |   |
 |   +---------------------------------------------------------------+   |
 +-----------------------------------------------------------------------+


2. Tech Stack Recommendations
2.1. Frontend Stack
Framework UI: React.js / Vue.js (atau Alpine.js + Tailwind CSS untuk lightweight monolithic deployment).
Styling Engine: Tailwind CSS v3 dengan variabel CSS kustom untuk Dark/Light mode switching.
Visualization: Chart.js / Recharts (Doughnut & Bar Charts).
Iconography: FontAwesome 6 / Lucide Icons.
2.2. Backend Stack
Runtime Environment: Node.js (Express / NestJS) atau Go (Fiber).
Authentication: OAuth2 / JWT (JSON Web Tokens) + HTTP-Only Cookie.
ORM / Query Builder: Prisma ORM / TypeORM / Drizzle.
Real-time Engine: Socket.io / WebSockets untuk pembaruan seatmap dan tiket instan.
2.3. Database & Storage Stack
Database Utama: PostgreSQL 15+ / MySQL 8.0+.
In-Memory Cache: Redis (untuk pemetaan sesi aktif, rate-limiting, dan WebSocket Pub/Sub broker).
Storage File: Local File System / AWS S3 Compatible (MinIO) untuk menyimpan foto bukti kerusakan & berkas ekspor.
3. Alur Komunikasi Data & Real-time Pipeline
3.1. Alur Check-in Seat Mapping
Siswa melakukan scan QR Code pada meja PC LAB1-PC-05.
Client mengirim request POST /api/v1/presences/check-in dengan payload { asset_code: "LAB1-PC-05", student_id: "20260012" }.
Backend memvalidasi sesi praktikum yang sedang berjalan di Lab 1.
Jika valid, record disimpan di DB, lalu backend memicu event WebSocket seat_status_changed.
Seluruh antarmuka admin/laboran yang sedang membuka halaman Seat Map menerima event tersebut dan mengubah indikator PC LAB1-PC-05 menjadi warna biru (Active User).
3.2. Alur Pembuatan Tiket Kerusakan
[User submit tiket] 
        │
        ▼
[API: POST /tickets] ──► [Save Ticket to DB]
        │
        ├──► [Update Asset Status = 'Degraded' / 'Maintenance']
        │
        └──► [Emit WS Event 'ticket_created'] ──► [UI Kanban Update & Badge Counter +1]


4. Keamanan & Arsitektur Jaringan (Security)
Transport Layer Security: Seluruh komunikasi wajib menggunakan HTTPS (TLS 1.3).
RBAC Middleware: Setiap endpoint diawasi oleh middleware pemeriksa role (requireRole(['ADMIN', 'TEKNISI'])).
Sanitasi Data: Sanitasi parameter query dan payload body untuk mencegah SQL Injection & Cross-Site Scripting (XSS).
Rate Limiting: Pembatasan maksimal 100 request/menit per IP address untuk mencegah Denial of Service (DoS).
