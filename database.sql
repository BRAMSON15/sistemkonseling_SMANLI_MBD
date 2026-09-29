-- Database: db_konseling_mbd
-- Rancang Bangun Sistem Konseling Berbasis Website Untuk Deteksi Dini Permasalahan Akademik dan Psikososial
-- SMA Negeri 5 Maluku Barat Daya (MBD)

CREATE DATABASE IF NOT EXISTS `db_konseling_mbd` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_konseling_mbd`;

-- 1. Tabel Users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NULL,
  `no_hp` VARCHAR(20) NULL,
  `role` ENUM('admin', 'guru_bk', 'siswa') NOT NULL,
  `foto` VARCHAR(255) DEFAULT 'default.png',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Tabel Kelas
CREATE TABLE IF NOT EXISTS `kelas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_kelas` VARCHAR(50) NOT NULL,
  `tingkat` ENUM('10', '11', '12') NOT NULL,
  `jurusan` VARCHAR(50) NOT NULL,
  `tahun_ajaran` VARCHAR(20) NOT NULL DEFAULT '2025/2026',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Tabel Siswa
CREATE TABLE IF NOT EXISTS `siswa` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `nisn` VARCHAR(20) NOT NULL UNIQUE,
  `kelas_id` INT NULL,
  `jenis_kelamin` ENUM('L', 'P') NOT NULL,
  `tempat_lahir` VARCHAR(100) NULL,
  `tanggal_lahir` DATE NULL,
  `alamat` TEXT NULL,
  `nama_wali` VARCHAR(100) NULL,
  `kontak_wali` VARCHAR(20) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`kelas_id`) REFERENCES `kelas`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. Tabel Guru BK
CREATE TABLE IF NOT EXISTS `guru_bk` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `nip` VARCHAR(30) NOT NULL UNIQUE,
  `spesialisasi` VARCHAR(100) DEFAULT 'Bimbingan Konseling & Psikososial Remaja',
  `no_ruangan` VARCHAR(50) DEFAULT 'Ruang BK Lantai 1',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Tabel Kuesioner (Master Asesmen EWS)
CREATE TABLE IF NOT EXISTS `kuesioner` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `judul` VARCHAR(150) NOT NULL,
  `deskripsi` TEXT NULL,
  `tipe` ENUM('akademik', 'psikososial', 'komprehensif') DEFAULT 'komprehensif',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 6. Tabel Pertanyaan Kuesioner
CREATE TABLE IF NOT EXISTS `pertanyaan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kuesioner_id` INT NOT NULL,
  `kategori` ENUM('akademik', 'kecemasan_stres', 'sosial_relasi', 'perundungan_bullying') NOT NULL,
  `teks_pertanyaan` TEXT NOT NULL,
  `nomor_urut` INT NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`kuesioner_id`) REFERENCES `kuesioner`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Tabel Opsi Jawaban
CREATE TABLE IF NOT EXISTS `opsi_jawaban` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `label` VARCHAR(50) NOT NULL,
  `bobot` INT NOT NULL,
  `urutan` INT NOT NULL
) ENGINE=InnoDB;

-- 8. Tabel Skrining Asesmen (Hasil Deteksi Dini EWS)
CREATE TABLE IF NOT EXISTS `skrining_asesmen` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `siswa_id` INT NOT NULL,
  `kuesioner_id` INT NOT NULL,
  `tanggal_pengisian` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `skor_akademik` INT NOT NULL DEFAULT 0,
  `skor_psikososial` INT NOT NULL DEFAULT 0,
  `skor_bullying` INT NOT NULL DEFAULT 0,
  `total_skor` INT NOT NULL DEFAULT 0,
  `persentase_skor` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `tingkat_risiko` ENUM('Rendah', 'Sedang', 'Tinggi') NOT NULL,
  `status_rekomendasi` ENUM('Sukarela', 'Wajib Konseling') NOT NULL,
  `catatan_sistem` TEXT NULL,
  `status_tindak_lanjut` ENUM('Belum Ditindaklanjuti', 'Sedang Konseling', 'Selesai') DEFAULT 'Belum Ditindaklanjuti',
  FOREIGN KEY (`siswa_id`) REFERENCES `siswa`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`kuesioner_id`) REFERENCES `kuesioner`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. Tabel Detail Jawaban Siswa
CREATE TABLE IF NOT EXISTS `skrining_jawaban` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `skrining_id` INT NOT NULL,
  `pertanyaan_id` INT NOT NULL,
  `opsi_id` INT NOT NULL,
  `skor` INT NOT NULL,
  FOREIGN KEY (`skrining_id`) REFERENCES `skrining_asesmen`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`pertanyaan_id`) REFERENCES `pertanyaan`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`opsi_id`) REFERENCES `opsi_jawaban`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 10. Tabel Pengajuan Konseling
CREATE TABLE IF NOT EXISTS `pengajuan_konseling` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `siswa_id` INT NOT NULL,
  `skrining_id` INT NULL,
  `guru_id` INT NULL,
  `tanggal_pengajuan` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `preferensi_tanggal` DATE NOT NULL,
  `preferensi_waktu` VARCHAR(50) NOT NULL,
  `jenis_konseling` ENUM('Individu', 'Kelompok', 'Akademik', 'Psikososial/Pribadi', 'Karir') NOT NULL DEFAULT 'Individu',
  `alasan_pengajuan` TEXT NOT NULL,
  `status` ENUM('Menunggu', 'Disetujui', 'Ditolak', 'Selesai') DEFAULT 'Menunggu',
  `alasan_penolakan` TEXT NULL,
  `is_wajib` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`siswa_id`) REFERENCES `siswa`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`skrining_id`) REFERENCES `skrining_asesmen`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`guru_id`) REFERENCES `guru_bk`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 11. Tabel Jadwal Konseling
CREATE TABLE IF NOT EXISTS `jadwal_konseling` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pengajuan_id` INT NOT NULL,
  `guru_id` INT NOT NULL,
  `tanggal_jadwal` DATE NOT NULL,
  `waktu_mulai` TIME NOT NULL,
  `waktu_selesai` TIME NOT NULL,
  `tempat_ruangan` VARCHAR(100) DEFAULT 'Ruang BK SMA Negeri 5 MBD',
  `catatan_jadwal` TEXT NULL,
  `status` ENUM('Dijadwalkan', 'Berlangsung', 'Selesai', 'Batal') DEFAULT 'Dijadwalkan',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`pengajuan_id`) REFERENCES `pengajuan_konseling`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`guru_id`) REFERENCES `guru_bk`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 12. Tabel Catatan Konseling (Rekam Medis Layanan BK)
CREATE TABLE IF NOT EXISTS `catatan_konseling` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `jadwal_id` INT NOT NULL,
  `siswa_id` INT NOT NULL,
  `guru_id` INT NOT NULL,
  `tanggal_sesi` DATE NOT NULL,
  `keluhan_utama` TEXT NOT NULL,
  `hasil_evaluasi_bk` TEXT NOT NULL,
  `tindakan_intervensi` TEXT NOT NULL,
  `rekomendasi_tindak_lanjut` TEXT NOT NULL,
  `status_perkembangan` ENUM('Perlu Sesi Lanjutan', 'Kasus Selesai', 'Rujukan Khusus/Wali') DEFAULT 'Kasus Selesai',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal_konseling`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`siswa_id`) REFERENCES `siswa`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`guru_id`) REFERENCES `guru_bk`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 13. Tabel Notifikasi
CREATE TABLE IF NOT EXISTS `notifikasi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `judul` VARCHAR(150) NOT NULL,
  `pesan` TEXT NOT NULL,
  `link` VARCHAR(255) NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 14. Tabel Mood Siswa Harian (Emotional Tracking)
CREATE TABLE IF NOT EXISTS `siswa_mood` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `siswa_id` INT NOT NULL,
  `mood` ENUM('senang', 'tenang', 'biasa', 'lelah', 'cemas') NOT NULL,
  `emoji` VARCHAR(10) NOT NULL,
  `catatan` TEXT NULL,
  `tanggal` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_siswa_tanggal` (`siswa_id`, `tanggal`),
  FOREIGN KEY (`siswa_id`) REFERENCES `siswa`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========================================================
-- SEED DATA AWAL (MASTER, USER, DAN INSTRUMEN KUESIONER)
-- ========================================================

-- Master Opsi Jawaban (Skala Likert 1-4)
INSERT INTO `opsi_jawaban` (`id`, `label`, `bobot`, `urutan`) VALUES
(1, 'Tidak Pernah', 1, 1),
(2, 'Jarang', 2, 2),
(3, 'Sering', 3, 3),
(4, 'Selalu', 4, 4)
ON DUPLICATE KEY UPDATE `label`=VALUES(`label`);

-- Master Kelas
INSERT INTO `kelas` (`id`, `nama_kelas`, `tingkat`, `jurusan`, `tahun_ajaran`) VALUES
(1, 'X-1 (Fase E)', '10', 'Umum', '2025/2026'),
(2, 'X-2 (Fase E)', '10', 'Umum', '2025/2026'),
(3, 'XI MIPA 1', '11', 'MIPA', '2025/2026'),
(4, 'XI IPS 1', '11', 'IPS', '2025/2026'),
(5, 'XII MIPA 1', '12', 'MIPA', '2025/2026'),
(6, 'XII IPS 1', '12', 'IPS', '2025/2026')
ON DUPLICATE KEY UPDATE `nama_kelas`=VALUES(`nama_kelas`);

-- Master Kuesioner
INSERT INTO `kuesioner` (`id`, `judul`, `deskripsi`, `tipe`, `is_active`) VALUES
(1, 'Instrumen Deteksi Dini Permasalahan Akademik dan Psikososial Siswa', 'Instrumen terstandar skrining dini untuk mengidentifikasi tingkat risiko kesulitan belajar akademik, tekanan psikososial, dan indikasi perundungan (bullying) pada siswa SMA Negeri 5 Maluku Barat Daya.', 'komprehensif', 1)
ON DUPLICATE KEY UPDATE `judul`=VALUES(`judul`);

-- Master Pertanyaan (25 Butir: 10 Akademik, 5 Kecemasan/Stres, 5 Relasi Sosial, 5 Bullying)
INSERT INTO `pertanyaan` (`id`, `kuesioner_id`, `kategori`, `teks_pertanyaan`, `nomor_urut`, `is_active`) VALUES
-- 10 Pertanyaan Dimensi Akademik
(1, 1, 'akademik', 'Saya merasa kesulitan memahami materi pelajaran yang disampaikan oleh guru di kelas.', 1, 1),
(2, 1, 'akademik', 'Saya menunda-nunda pengerjaan tugas sekolah sehingga tugas menumpuk dan sering terlambat dikumpulkan.', 2, 1),
(3, 1, 'akademik', 'Saya merasa tidak termotivasi dan malas untuk berangkat ke sekolah atau mengikuti pelajaran.', 3, 1),
(4, 1, 'akademik', 'Nilai ulangan atau ujian saya mengalami penurunan drastis pada beberapa mata pelajaran terakhir.', 4, 1),
(5, 1, 'akademik', 'Saya merasa kesulitan membagi waktu antara belajar, istirahat, dan kegiatan lainnya di luar sekolah.', 5, 1),
(6, 1, 'akademik', 'Saya merasa mengantuk, kehilangan konsentrasi, atau pikiran melayang saat proses belajar mengajar berlangsung.', 6, 1),
(7, 1, 'akademik', 'Saya merasa takut atau cemas berlebihan saat menghadapi ujian atau presentasi di depan kelas.', 7, 1),
(8, 1, 'akademik', 'Saya merasa ragu dan bingung mengenai rencana studi lanjut atau pilihan karir masa depan setelah lulus SMA.', 8, 1),
(9, 1, 'akademik', 'Saya merasa sarana atau buku penunjang belajar yang saya miliki sangat terbatas untuk mengikuti pelajaran.', 9, 1),
(10, 1, 'akademik', 'Saya merasa enggan bertanya kepada guru ketika ada materi pelajaran yang belum saya mengerti.', 10, 1),

-- 5 Pertanyaan Dimensi Psikososial (Kecemasan & Stres)
(11, 1, 'kecemasan_stres', 'Saya merasa gelisah, tegang, atau mudah panik tanpa sebab yang jelas dalam kehidupan sehari-hari.', 11, 1),
(12, 1, 'kecemasan_stres', 'Saya mengalami kesulitan tidur nyenyak atau sering terbangun karena memikirkan banyak beban pikiran.', 12, 1),
(13, 1, 'kecemasan_stres', 'Saya merasa mudah lelah, murung, dan kehilangan minat terhadap aktivitas yang sebelumnya saya senangi.', 13, 1),
(14, 1, 'kecemasan_stres', 'Saya merasa tertekan oleh ekspektasi orang tua, guru, atau lingkungan di sekitar saya.', 14, 1),
(15, 1, 'kecemasan_stres', 'Saya merasa mudah tersinggung, emosi meluap-luap, atau sulit menenangkan diri saat ada masalah kecil.', 15, 1),

-- 5 Pertanyaan Dimensi Psikososial (Sosial & Relasi Teman Sebaya)
(16, 1, 'sosial_relasi', 'Saya merasa terasing, kesepian, atau tidak memiliki teman dekat untuk berbagi cerita di sekolah.', 16, 1),
(17, 1, 'sosial_relasi', 'Saya merasa canggung, minder, atau tidak percaya diri ketika harus bergaul dalam kelompok teman sebaya.', 17, 1),
(18, 1, 'sosial_relasi', 'Saya sering merasa diabaikan atau sengaja dijauhi oleh teman-teman saat kegiatan kelompok.', 18, 1),
(19, 1, 'sosial_relasi', 'Saya mengalami konflik, pertengkaran, atau perselisihan yang belum terselesaikan dengan teman sekolah.', 19, 1),
(20, 1, 'sosial_relasi', 'Saya memilih mengisolasi diri di sudut kelas atau menghindari keramaian saat jam istirahat berlangsung.', 20, 1),

-- 5 Pertanyaan Dimensi Psikososial (Perundungan / Bullying)
(21, 1, 'perundungan_bullying', 'Saya pernah menerima ejekan, hinaan, olokan nama orang tua, atau panggilan yang menyakitkan di sekolah.', 21, 1),
(22, 1, 'perundungan_bullying', 'Saya pernah diancam, dipaksa memberikan uang/barang, atau diperlakukan kasar secara fisik oleh siswa lain.', 22, 1),
(23, 1, 'perundungan_bullying', 'Saya mendapati rumor bohong atau fitnah tentang diri saya disebarkan oleh teman di sekolah maupun media sosial.', 23, 1),
(24, 1, 'perundungan_bullying', 'Saya merasa takut atau tidak nyaman melewati area tertentu di sekolah karena khawatir diganggu oleh siswa tertentu.', 24, 1),
(25, 1, 'perundungan_bullying', 'Saya pernah melihat tindakan perundungan terhadap siswa lain namun merasa takut untuk melaporkannya ke guru.', 25, 1)
ON DUPLICATE KEY UPDATE `teks_pertanyaan`=VALUES(`teks_pertanyaan`);

-- ========================================================
-- DATA PENGGUNA DEMO / DEFAULT
-- Passwords:
-- admin   : admin123  (BCRYPT: $2y$10$wKz0b0qQcWj17MfZp2Qp2eB7sTfZ1dJbA2L5w1vXp9s5P2k2r3iTe)
-- gurubk  : guru123   (BCRYPT: $2y$10$tZ2E7H4/zG0mN8p1kC6Z2uF6s0XhZz6pG2kH5J4L3M2N1O0P9Q8Re)
-- 1234567890 (siswa): siswa123 (BCRYPT: $2y$10$u8hI9P7k5V3jR1wE4tY6Ou7K8L9M0N1P2Q3R4S5T6U7V8W9X0Y1Ze)
-- ========================================================

-- Akun User Admin
INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `email`, `no_hp`, `role`, `is_active`) VALUES
(1, 'admin', '$2y$10$wKz0b0qQcWj17MfZp2Qp2eB7sTfZ1dJbA2L5w1vXp9s5P2k2r3iTe', 'Administrator Sistem BK', 'admin@sman5mbd.sch.id', '081234567890', 'admin', 1),
(2, '198504122010012003', '$2y$10$tZ2E7H4/zG0mN8p1kC6Z2uF6s0XhZz6pG2kH5J4L3M2N1O0P9Q8Re', 'Agatha Natalia Laipeny, S.Pd.', 'agatha.bk@sman5mbd.sch.id', '081344556677', 'guru_bk', 1),
(3, '0071234561', '$2y$10$u8hI9P7k5V3jR1wE4tY6Ou7K8L9M0N1P2Q3R4S5T6U7V8W9X0Y1Ze', 'Daniel Fransisko Renwarin', 'daniel.renwarin@siswa.sman5mbd.sch.id', '082199887711', 'siswa', 1),
(4, '0071234562', '$2y$10$u8hI9P7k5V3jR1wE4tY6Ou7K8L9M0N1P2Q3R4S5T6U7V8W9X0Y1Ze', 'Maria Beatrix Lewerissa', 'maria.lewerissa@siswa.sman5mbd.sch.id', '082199887722', 'siswa', 1),
(5, '0071234563', '$2y$10$u8hI9P7k5V3jR1wE4tY6Ou7K8L9M0N1P2Q3R4S5T6U7V8W9X0Y1Ze', 'Samuel Pieter Dahoklory', 'samuel.dahoklory@siswa.sman5mbd.sch.id', '082199887733', 'siswa', 1)
ON DUPLICATE KEY UPDATE `nama_lengkap`=VALUES(`nama_lengkap`);

-- Detail Guru BK
INSERT INTO `guru_bk` (`id`, `user_id`, `nip`, `spesialisasi`, `no_ruangan`) VALUES
(1, 2, '198504122010012003', 'Konseling Akademik & Perkembangan Remaja', 'Ruang BK Lantai 1 SMA N 5 MBD')
ON DUPLICATE KEY UPDATE `spesialisasi`=VALUES(`spesialisasi`);

-- Detail Siswa
INSERT INTO `siswa` (`id`, `user_id`, `nisn`, `kelas_id`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`, `alamat`, `nama_wali`, `kontak_wali`) VALUES
(1, 3, '0071234561', 3, 'L', 'Tiakur', '2008-04-14', 'Jl. Trans Pulau Moa, Tiakur, Maluku Barat Daya', 'Frans Renwarin', '081240112233'),
(2, 4, '0071234562', 3, 'P', 'Moa', '2008-07-22', 'Desa Klis, Kec. Moa, Maluku Barat Daya', 'Yohanis Lewerissa', '081240445566'),
(3, 5, '0071234563', 4, 'L', 'Wonreli', '2007-11-03', 'Desa Wakarleli, Tiakur, Maluku Barat Daya', 'Petrus Dahoklory', '081240778899')
ON DUPLICATE KEY UPDATE `nisn`=VALUES(`nisn`);

-- ========================================================
-- DATA SAMPEL RIWAYAT SKRINING & KONSELING AWAL
-- ========================================================

-- Skrining Sampel 1: Siswa Daniel (Risiko Tinggi - Terindikasi Kesulitan Akademik & Stres)
INSERT INTO `skrining_asesmen` (`id`, `siswa_id`, `kuesioner_id`, `tanggal_pengisian`, `skor_akademik`, `skor_psikososial`, `skor_bullying`, `total_skor`, `persentase_skor`, `tingkat_risiko`, `status_rekomendasi`, `catatan_sistem`, `status_tindak_lanjut`) VALUES
(1, 1, 1, DATE_SUB(NOW(), INTERVAL 5 DAY), 34, 32, 14, 80, 80.00, 'Tinggi', 'Wajib Konseling', 'Peringatan EWS: Terdeteksi risiko tinggi gabungan antara prokrastinasi belajar dan tekanan psikologis.', 'Sedang Konseling'),
(2, 2, 1, DATE_SUB(NOW(), INTERVAL 3 DAY), 18, 17, 6, 41, 41.00, 'Rendah', 'Sukarela', 'Kondisi siswa terpantau stabil dan adaptif.', 'Belum Ditindaklanjuti'),
(3, 3, 1, DATE_SUB(NOW(), INTERVAL 2 DAY), 26, 24, 12, 62, 62.00, 'Sedang', 'Wajib Konseling', 'Peringatan EWS: Terdeteksi penurunan motivasi belajar dan indikasi konflik sosial.', 'Belum Ditindaklanjuti')
ON DUPLICATE KEY UPDATE `total_skor`=VALUES(`total_skor`);

-- Pengajuan Konseling Sampel untuk Siswa Daniel (Wajib Konseling karena Skor Tinggi)
INSERT INTO `pengajuan_konseling` (`id`, `siswa_id`, `skrining_id`, `guru_id`, `tanggal_pengajuan`, `preferensi_tanggal`, `preferensi_waktu`, `jenis_konseling`, `alasan_pengajuan`, `status`, `is_wajib`) VALUES
(1, 1, 1, 1, DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00 - 10:00 WIT', 'Individu', 'Saya merasa sangat tertekan dengan tugas sekolah dan sulit tidur akhir-akhir ini.', 'Disetujui', 1)
ON DUPLICATE KEY UPDATE `status`=VALUES(`status`);

-- Jadwal Konseling Aktif
INSERT INTO `jadwal_konseling` (`id`, `pengajuan_id`, `guru_id`, `tanggal_jadwal`, `waktu_mulai`, `waktu_selesai`, `tempat_ruangan`, `catatan_jadwal`, `status`) VALUES
(1, 1, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', '10:00:00', 'Ruang BK SMA Negeri 5 MBD', 'Mohon hadir tepat waktu dan membawa buku agenda belajar.', 'Dijadwalkan')
ON DUPLICATE KEY UPDATE `tempat_ruangan`=VALUES(`tempat_ruangan`);

-- Notifikasi Sampel
INSERT INTO `notifikasi` (`id`, `user_id`, `judul`, `pesan`, `link`, `is_read`, `created_at`) VALUES
(1, 3, 'Jadwal Konseling Disetujui', 'Pengajuan konseling Anda telah disetujui untuk dilaksanakan pada jadwal yang ditentukan.', 'siswa/riwayat_siswa.php', 0, NOW()),
(2, 2, 'Peringatan Deteksi Dini Kritis', 'Siswa Daniel Fransisko Renwarin terdeteksi memiliki tingkat risiko TINGGI pada asesmen deteksi dini.', 'guru/skrining.php', 0, NOW())
ON DUPLICATE KEY UPDATE `judul`=VALUES(`judul`);

-- Sampel Mood Harian Siswa (Emotional Tracking Radar)
INSERT INTO `siswa_mood` (`id`, `siswa_id`, `mood`, `emoji`, `catatan`, `tanggal`, `created_at`) VALUES
(1, 1, 'cemas', '😔', 'Banyak tugas sekolah dan sulit tidur', CURDATE(), NOW()),
(2, 2, 'tenang', '😌', 'Hari ini pelajaran cukup menyenangkan', CURDATE(), NOW()),
(3, 3, 'lelah', '😓', 'Kelelahan setelah latihan olahraga', CURDATE(), NOW())
ON DUPLICATE KEY UPDATE `mood`=VALUES(`mood`), `catatan`=VALUES(`catatan`);

