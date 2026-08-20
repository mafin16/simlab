UI/UX Design System & Specifications
SIMLAB - Computer Laboratory Management System
1. Prinsip Desain (Design Philosophy)
High Contrast & Readability: Memastikan perbedaan kontras warna yang tegas pada Mode Terang maupun Mode Gelap agar informasi statistik PC dapat dibaca dalam sekali lihat.
Density & Information Richness: Menampilkan data teknis PC, spesifikasi hardware, dan status secara padat namun rapi (data-dense dashboard).
Feedback Visual Seketika: Setiap aksi pengguna (simpan, hapus, update tiket) harus memberikan balasan dalam bentuk animasi, perubahan warna status, atau toast notification.
2. Palet Warna & Token Tema (Color Tokens)
2.1. Dark Mode Tokens (Default)
Background Utama (Base): Slate 950 (#020617)
Panel / Card Surface: Slate 900 (#0f172a) dengan opacity 70% + Glassmorphism Blur (12px).
Border Card/Panel: White 8% (rgba(255, 255, 255, 0.08)).
Teks Utama: Slate 100 (#f1f5f9)
Teks Sekunder: Slate 400 (#94a3b8)
2.2. Light Mode Tokens
Background Utama (Base): Slate 50 (#f8fafc)
Panel / Card Surface: Pure White (#ffffff)
Border Card/Panel: Slate 200 (#e2e8f0)
Shadow: 0 1px 3px 0 rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.05)
Teks Utama: Slate 900 (#0f172a)
Teks Sekunder: Slate 600 (#475569)
2.3. Accent & Status Color Palette
Brand / Primary Accent: Blue 600 (#2563eb)
Ready / Normal Status: Emerald 500 (#10b981) | Badge BG: Emerald 500/10
Degraded / Warning Status: Amber 500 (#f59e0b) | Badge BG: Amber 500/10
Maintenance / Error Status: Red 500 (#ef4444) | Badge BG: Red 500/10
Info / Schedule Accent: Indigo 500 (#6366f1) | Badge BG: Indigo 500/10
3. Tipografi
Primary Font Family: Inter ('Inter', sans-serif)
Monospace Font (untuk IP, MAC, Kode Aset): JetBrains Mono / Fira Code / System Monospace.
Level
Size
Weight
Line Height
Penggunaan
Heading 1
18px (1.125rem)
Bold (700)
1.75rem
Judul Halaman Utama / Header Brand
Heading 2
14px (0.875rem)
Bold (700)
1.25rem
Judul Sub-Panel & Modal Header
Body Text
12px (0.75rem)
Medium (500)
1rem
Teks Tabel, Card, Deskripsi
Caption / Badge
10px - 11px
SemiBold (600)
0.875rem
Label Status, Timestamp, Tag

4. Komponen UI Standar
4.1. Status Badges
Pill-shaped badges dengan ikon FontAwesome pendamping:
<!-- Badge Ready -->
<span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 inline-flex items-center gap-1">
  <i class="fa-solid fa-check"></i> Ready
</span>


4.2. Interactive Seat Card (Denah Meja)
Card dengan ukuran grid proporsional.
Memiliki respon visual hover scale (transform: scale(1.05)).
Indikator pulsa animasi (animate-pulse) untuk PC yang sedang memiliki pengguna aktif.
4.3. Floating Toast Notification
Diletakkan di sudut kanan bawah (bottom: 1.25rem; right: 1.25rem;).
Otomatis menghilang dalam 3.5 detik dengan efek fade-out dan slide-down.
