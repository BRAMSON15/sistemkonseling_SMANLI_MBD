# Sistem Konseling Berbasis Website untuk Deteksi Dini Permasalahan Akademik dan Psikososial pada SMA Negeri 5 MBD

Aplikasi web **Bimbingan Konseling (BK)** berbasis **PHP Native (PDO)** dan **MySQL** yang dirancang sebagai **Early Warning System (EWS)** untuk mendeteksi secara proaktif permasalahan akademik (kesulitan belajar, penurunan motivasi, prokrastinasi) dan psikososial (kecemasan, stres, relasi teman sebaya, serta perundungan/bullying) pada **SMA Negeri 5 Maluku Barat Daya**.

> **Penelitian Skripsi:**  
> Penulis: **Agatha Natalia Laipeny** (NPM: 12155201220165)  
> Program Studi: **Informatika - Minat Penulisan Application Development**  
> Fakultas: **Fakultas Ilmu Komputer, Universitas Kristen Indonesia Maluku (UKIM) Ambon (2026)**  
> Dosen Pembimbing: **Reynaldi Siwalette, S.Kom., M.Kom** &bull; Kaprodi: **Joanna C. Patty, S.T., M.Kom**

---

## 🚀 Fitur Utama Sistem

### 1. Modul Siswa (Konseli)
- **Dashboard EWS**: Menampilkan status skrining terakhir, alarm/peringatan wajib konseling, jadwal pertemuan yang telah disetujui, dan total layanan.
- **Asesmen Deteksi Dini (25 Butir Soal Terstandar)**:
  - 10 Butir Dimensi Akademik (kesulitan materi, tugas, motivasi).
  - 5 Butir Dimensi Kecemasan & Stres Emosional.
  - 5 Butir Dimensi Interaksi & Hubungan Sosial.
  - 5 Butir Dimensi Perlindungan dari Perundungan (*Bullying* fisik, verbal, relasional, dan cyberbullying).
  - *Progress bar real-time* saat pengisian.
- **Hasil Deteksi Dini & Rekomendasi Otomatis**:
  - Skor per dimensi dan total skor (0 - 100).
  - Klasifikasi Tingkat Risiko: **Rendah (Aman)**, **Sedang (Perhatian)**, dan **Tinggi (Bahaya)**.
  - Evaluasi otomatis: Membuka opsi **Konseling Sukarela** untuk risiko rendah, dan memicu **Rekomendasi Wajib Konseling** untuk risiko sedang/tinggi.
- **Pengajuan Reservasi Jadwal Konseling**: Siswa dapat memilih Guru BK, tanggal pertemuan, jam istirahat/pulang sekolah, jenis bimbingan, dan keluhan awal.
- **Riwayat Layanan**: Rekam riwayat kuesioner dan status persetujuan jadwal serta catatan rekomendasi dari Guru BK.
- **Profil Siswa**: Pengaturan data pribadi, kontak darurat wali, dan ubah kata sandi.

### 2. Modul Guru Bimbingan Konseling (Konselor BK)
- **Dashboard Early Warning System**: Alarm merah untuk siswa berisiko tinggi yang membutuhkan intervensi segera, diagram donat (*Chart.js*) sebaran risiko siswa sekolah, dan agenda konseling hari ini & besok.
- **Monitoring Deteksi Dini**: Pemantauan hasil skrining seluruh siswa dengan filter rombel kelas, tingkat risiko, dan status tindak lanjut.
- **Manajemen Pengajuan Konseling**: Menyetujui jadwal (menentukan tanggal, jam, ruangan, dan pesan pengingat) atau menolak/meminta *reschedule*.
- **Agenda Jadwal Konseling**: Kalender kerja harian konselor.
- **Pelaksanaan Konseling (Rekam Medis Layanan BK)**:
  - Diagnosis & evaluasi Guru BK.
  - Tindakan intervensi (Konseling Individual, Restrukturisasi Kognitif, Relaksasi, Mediasi Rekan Sebaya).
  - Rekomendasi tindak lanjut yang dapat dibaca oleh siswa.
  - Penentuan status kasus: *Kasus Selesai*, *Perlu Sesi Lanjutan*, atau *Rujukan Khusus/Wali*.
- **Rekam Jejak Konseling**: Riwayat arsip intervensi seluruh siswa.
- **Laporan & Rekapitulasi Resmi**:
  - Rekapitulasi Deteksi Dini (filter kelas, tanggal, risiko).
  - Rekapitulasi Pelayanan Konseling (filter periode, jenis bimbingan).
  - Format cetak PDF resmi lengkap dengan **Kop Surat SMA Negeri 5 MBD** dan kolom tanda tangan Kepala Sekolah & Guru BK.

### 3. Modul Administrator
- **Dashboard Admin**: Ringkasan data master pengguna, kelas, butir soal, dan info server.
- **Manajemen Pengguna**: CRUD akun Admin, Guru BK, dan Siswa, serta fasilitas *Reset Password default 1-klik*.
- **Manajemen Kelas**: CRUD rombel tingkat Fase E (Kelas X) dan Fase F (Kelas XI, XII).
- **Manajemen Kuesioner & Butir Soal**: Pengaturan teks pertanyaan, pengelompokan kategori dimensi, dan nomor urut.

---

## 🔑 Akun Akses Demo Bawaan

| Peran Pengguna | Username / Identitas | Kata Sandi | Deskripsi |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` | Hak akses penuh konfigurasi sistem |
| **Guru BK (Konselor)** | `198504122010012003` | `guru123` | NIP Agatha Natalia Laipeny, S.Pd. |
| **Siswa 1 (Risiko Tinggi)** | `0071234561` | `siswa123` | Daniel Fransisko Renwarin (XI MIPA 1) |
| **Siswa 2 (Risiko Rendah)** | `0071234562` | `siswa123` | Maria Beatrix Lewerissa (XI MIPA 1) |
| **Siswa 3 (Risiko Sedang)** | `0071234563` | `siswa123` | Samuel Pieter Dahoklory (XI IPS 1) |

---

## 🛠️ Instalasi & Menjalankan di Laragon

1. Pastikan server Apache dan MySQL di Laragon sudah berjalan (*Start All*).
2. Proyek sudah berada di folder:
   ```
   c:\backup\laragon\www\sistemkonseling\
   ```
3. Buka browser dan akses alamat:
   ```
   http://localhost/sistemkonseling/
   ```
   *(Atau `http://sistemkonseling.test/` jika VirtualHost Laragon aktif)*
4. Jika basis data belum terinisialisasi, jalankan installer web otomatis:
   ```
   http://localhost/sistemkonseling/install.php
   ```
   Klik tombol **"Mulai Instalasi / Reset Basis Data"**.
5. Masuk melalui tombol **Login** menggunakan akun demo di atas.

---

## 📂 Struktur Direktori Proyek

```
sistemkonseling/
│── config/
│   ├── database.php            # Koneksi PDO MySQL & konfigurasi zona waktu (Asia/Jayapura - WIT)
│   └── helper.php              # Algoritma EWS, helper autentikasi, sanitasi, notifikasi, format tanggal
│── assets/
│   ├── css/custom.css          # Kustom styling antarmuka & format kop surat resmi
│── includes/
│   ├── header.php              # Master header layout
│   ├── sidebar.php             # Sidebar dinamis berdasarkan peran
│   ├── navbar.php              # Top navbar & dropdown notifikasi
│   └── footer.php              # Footer copyright & loader JS
│── auth/
│   ├── login.php               # Halaman login terpadu
│   └── logout.php              # Terminasi sesi login
│── siswa/
│   ├── index.php               # Dashboard Siswa
│   ├── deteksi_dini.php        # Form pengerjaan kuesioner skrining EWS
│   ├── hasil_deteksi.php       # Lembar hasil analisis risiko per dimensi
│   ├── ajukan_konseling.php    # Formulir pengajuan reservasi jadwal konseling
│   ├── riwayat_siswa.php       # Riwayat konseling & asesmen
│   └── profil.php              # Profil siswa & kontak darurat wali
│── guru/
│   ├── index.php               # Dashboard Guru BK (EWS)
│   ├── skrining.php            # Monitoring deteksi dini siswa
│   ├── pengajuan.php           # Konfirmasi jadwal siswa
│   ├── jadwal.php              # Kalender agenda konseling aktif
│   ├── konseling_proses.php    # Form pencatatan rekam medis BK & intervensi
│   ├── riwayat_konseling.php   # Arsip rekam jejak konseling
│   ├── laporan.php             # Cetak rekapitulasi EWS & konseling (Kop SMA Negeri 5 MBD)
│   └── profil.php              # Profil Guru BK
│── admin/
│   ├── index.php               # Dashboard Admin
│   ├── pengguna.php            # CRUD Pengguna & Reset Password
│   ├── kelas.php               # CRUD Data Kelas
│   ├── kuesioner.php           # CRUD Butir Soal Kuesioner
│   └── profil.php              # Profil Admin
│── database.sql                # Skema DDL & DML data awal lengkap
│── install.php                 # Installer web otomatis 1-klik
│── index.php                   # Halaman publik SMA Negeri 5 MBD
└── README.md                   # Dokumentasi lengkap sistem
```
