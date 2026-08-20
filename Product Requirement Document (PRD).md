Product Requirement Document (PRD)
SIMLAB - Sistem Pengelolaan Laboratorium Komputer v2.0
1. Ringkasan Eksekutif & Latar Belakang
SIMLAB adalah sistem informasi pengelolaan laboratorium komputer berbasis web terintegrasi yang dirancang untuk merapikan operasional harian, pemeliharaan aset IT, efisiensi penggunaan ruang, serta otomatisasi helpdesk penanganan kerusakan perangkat di lingkungan sekolah dan perguruan tinggi.
Sistem ini menyelesaikan permasalahan klasik seperti:
Pencatatan inventaris yang masih manual dan tidak real-time.
Sulitnya mengidentifikasi PC rusak atau pengguna yang bertanggung jawab saat terjadi kendala fisik.
Bentroknya jadwal penggunaan lab insidental di luar jam praktikum reguler.
Lambatnya koordinasi antara guru/instruktur dengan teknisi/laboran.
2. Persona Pengguna & Hak Akses (RBAC)
Peran (Role)
Pengguna Utama
Wewenang & Kebutuhan Utama
Super Admin / Kepala Lab
Kepala Laboratorium, Wakasek Sarpras
Mengelola seluruh sistem, melihat analitik utilisasi, menyetujui anggaran, ekspor laporan resmi.
Teknisi / Laboran
Staff IT, Laboran
Mengelola aset & periferal, memproses tiket kerusakan (Kanban), memperbarui status PC, mencetak QR Code.
Instruktur / Guru
Dosen, Guru Pengampu
Melihat jadwal lab, mengajukan booking insidental, presensi siswa per PC, submit tiket kendala saat mengajar.
Siswa / User
Siswa, Mahasiswa
Memindai QR meja untuk check-in/presensi, melihat jadwal kosong, melaporkan kendala PC secara mandiri.

3. Batasan Lingkup & Spesifikasi Awal (Initial Baseline)
Total Ruangan: 2 Ruang Lab
Lab 1: 20 Unit PC (LAB1-PC-01 s/d LAB1-PC-20)
Lab 2: 15 Unit PC (LAB2-PC-01 s/d LAB2-PC-15)
Total Unit Aset Utama: 35 Unit PC Desktop.
Kondisi Awal: 100% Ready / Normal pada deployment awal.
4. Rincian Modul Fitur Utama
4.1. Dashboard Analytics & Summary Metrics
Metrik Utama: Total Unit PC (35), Jumlah Ready, Jumlah Degraded/Maintenance, Total Sesi Praktikum Hari Ini.
Visualisasi Grafis:
Doughnut Chart: Distirbusi kelayakan unit (Ready vs Degraded vs Servis).
Bar Chart: Kepadatan jam penggunaan lab per hari (Senin - Sabtu).
Quick Widgets:
Daftar tiket kerusakan aktif yang membutuhkan respons cepat.
Ringkasan jadwal praktikum yang sedang berjalan dan sesi berikutnya.
4.2. Manajemen Inventaris Aset & Periferal
Detail Spesifikasi PC: Kode Aset, Nama Unit, Ruang Lab, Baris/Meja, CPU, RAM (GB & Tipe), Storage, GPU, IP Address, MAC Address, S/N.
Hierarki Aset (Parent-Child): Menghubungkan periferal (Monitor, Keyboard, Mouse, UPS) ke PC Induk.
Pencetakan Label QR Code: Generasi QR Code unik per unit PC untuk pelaporan kendala cepat via HP.
Bulk Import / Export Excel: Fitur unggah masal data aset berbasis template .xlsx terstruktur dan ekspor data rekap.
4.3. Penjadwalan & Reservasi Lab (Scheduling)
Kalender Mingguan: Tampilan matriks jam (07:00 - 15:15 WIB) vs Hari (Senin - Sabtu).
Pengajuan Booking Insidental: Form reservasi ruang untuk workshop, ujian sertifikasi, atau kegiatan ekstrakurikuler.
Deteksi Bentrok Otomatis: Sistem menolak pengajuan jika waktu dan ruangan bersinggungan dengan jadwal reguler atau booking terkonfirmasi lainnya.
4.4. Helpdesk & Tiket Kerusakan (Kanban Workflow)
Pelaporan Kendala: Pelaporan berorientasi unit PC dan komponen spesifik (Mouse, Monitor, Booting/OS, LAN, Audio).
Papan Kanban 3 Kolom:
Open (Belum ditangani / baru masuk)
In Progress (Sedang dalam proses perbaikan/sparepart)
Resolved (Selesai diperbaiki & PC kembali Ready)
Eskalasi Status PC: Otomatis mengubah status PC menjadi Degraded atau Maintenance saat tiket dibuat.
4.5. Interactive Seat Mapping & Real-time User Presence
Layout Denah Interaktif: Visualisasi denah fisik meja lab (Grid 5 Baris x 6 Kolom).
Indikator Warna Status:
Hijau: Ready & Kosong
Biru (Pulse): Terisi / Siswa sedang Presensi
Kuning: Kendala Minor (Degraded)
Merah: Rusak / Maintenance
Modal Pop-up Detail: Klik unit PC pada denah untuk melihat informasi spesifikasi, user aktif, atau aksi quick-status change.
4.6. Laporan & Audit System
Ekspor rekapitulasi aset PC lengkap dalam format Excel (.xlsx).
Cetak riwayat pemeliharaan & penggantian komponen dalam format PDF (.pdf).
Cetak log utilisasi dan presensi jam praktikum per semester.
5. Kriteria Penerimaan (Acceptance Criteria)
Fitur Impor Data: Sistem harus menolak data file Excel jika kolom mandatory (Kode Aset, CPU, RAM, Lab) kosong atau format tanggal salah.
Pencegahan Bentrok Booking: Jika Lab 1 di-booking pada Senin jam 08:00 - 10:00, sistem wajib memberikan respon eror jika ada pengajuan lain pada rentang waktu yang tumpang tindih.
Integritas Status Aset: Menutup tiket perbaikan (Resolved) harus secara otomatis mengembalikan status PC induk menjadi Ready.
Kecepatan Respons Seatmap: Perubahan status PC pada seat mapping harus terefleksi di layar tanpa perlu refresh halaman penuh (client-side reactive update).
6. Non-Functional Requirements (NFR)
Performa: Waktu muat halaman utama < 1.5 detik pada koneksi jaringan lokal (LAN).
Keamanan: Encrypted JWT authentication, Hashed Password (BCrypt/Argon2), dan sanitasi input terhadap XSS/SQL Injection.
Kemudahan Penggunaan (Usability): Antarmuka intuitif, mendukung mode gelap (Dark Mode) dan terang (Light Mode), serta fully responsive di perangkat seluler/tablet.
