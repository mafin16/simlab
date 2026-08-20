Business Rules & Operational Workflows
SIMLAB - Computer Laboratory Management System
1. Aturan Siklus Hidup Aset (Asset Lifecycle Rules)
       +------------+     Tiket Baru Masuk    +--------------+
       |   READY    | ----------------------> |   DEGRADED   |
       +------------+                         +--------------+
             ^                                       |
             |                                       | Butuh Servis
             |                                       | Berat
             |         Tiket Selesai         v
             +------------------------------- +--------------+
               (Status = 'Resolved')          | MAINTENANCE  |
                                              +--------------+
                                                     |
                                                     | Rusak Total
                                                     v
                                              +--------------+
                                              |  SCRAPPED    |
                                              +--------------+


Aturan Perubahan Status Otomatis:
Ketika tiket baru dibuat untuk unit PC tertentu dengan kategori Mouse/Keyboard, status PC otomatis berubah menjadi Degraded (tetapi masih bisa dipakai jika mendesak).
Ketika tiket dibuat dengan kategori Monitor Blank atau Gagal Booting/OS, status PC otomatis berubah menjadi Maintenance (tidak bisa digunakan untuk presensi/praktikum).
Saat tiket ditandai sebagai Resolved oleh teknisi, status PC otomatis kembali menjadi Ready.
Aturan Penamaan Kode Aset:
Format wajib: [KODE_LAB]-[TIPE_ASSET]-[NOMOR_URUT]
Contoh: LAB1-PC-01, LAB2-PC-15, LAB2-SVR-01.
2. Aturan Penjadwalan & Pengajuan Booking Lab
Prioritas Sesi:
Jadwal Rutin Mingguan (Mata Pelajaran/Kuliah): Memiliki prioritas tertinggi.
Booking Insidental: Hanya dapat diajukan pada slot jam/hari yang bernilai KOSONG / PRAKTIKUM BEBAS.
Aturan Pencegahan Bentrok (Collision Detection Logic):
Waktu pengajuan booking dinyatakan BENTROK jika:

pada Ruang Lab dan Hari yang sama.
Jika terjadi bentrok, sistem wajib menolak pengajuan dan menampilkan pesan eror lokasi slot tumpang tindih.
3. Aturan Pemeliharaan & Tiket (Helpdesk SLA)
Tingkat Prioritas Tiket:
High: PC Server Mati, Mati Listrik Lab, Proyektor Utama Blank. (SLA Penanganan: Max 2 Jam).
Medium: PC Client Mati Total, Network Disconnected. (SLA Penanganan: Max 24 Jam).
Low: Mouse/Keyboard Macet, Kabel Kendor, Audio Mati. (SLA Penanganan: Max 48 Jam).
Alur Papan Kanban:
Tiket dibuat  masuk kolom Open.
Teknisi menekan tombol "Proses Tiket"  status tiket berubah menjadi In Progress, nama teknisi tercatat sebagai penanggung jawab.
Perbaikan selesai  teknisi menekan "Selesaikan Tiket", wajib mengisi catatan solusi  status tiket berubah menjadi Resolved.
4. Aturan Presensi & Pemetaan Meja (Seat Mapping)
Satu User Satu Kursi: Satu identitas siswa (NISN/NIM) hanya boleh aktif di 1 unit PC pada jam/sesi praktikum yang sama.
Auto Log-out / Clear Session: Sesi presensi pada seat mapping otomatis di-clear ketika jam sesi praktikum berakhir sesuai tabel jadwal.
