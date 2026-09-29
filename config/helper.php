<?php
/**
 * Helper & Utility Functions
 * Sistem Konseling SMA Negeri 5 Maluku Barat Daya
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah pengguna sudah login
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Dapatkan data user yang sedang login
 */
function auth_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'           => $_SESSION['user_id'],
        'username'     => $_SESSION['username'] ?? '',
        'nama_lengkap' => $_SESSION['nama_lengkap'] ?? '',
        'role'         => $_SESSION['role'] ?? '',
        'role_id'      => $_SESSION['role_id'] ?? null, // id di tabel siswa atau guru_bk
        'kelas_id'     => $_SESSION['kelas_id'] ?? null,
        'nama_kelas'   => $_SESSION['nama_kelas'] ?? '',
        'nisn'         => $_SESSION['nisn'] ?? '',
        'nip'          => $_SESSION['nip'] ?? ''
    ];
}

/**
 * Filter akses berdasarkan peran (Role-based Access Control)
 * @param array|string $roles
 */
function require_role($roles, string $redirect = 'auth/login.php'): void {
    if (!is_logged_in()) {
        set_flash('danger', 'Sesi Anda telah berakhir, silakan login kembali.');
        header('Location: ' . BASE_URL . $redirect);
        exit;
    }

    $roles = (array) $roles;
    if (!in_array($_SESSION['role'], $roles)) {
        set_flash('danger', 'Anda tidak memiliki hak akses untuk membuka halaman tersebut.');
        if ($_SESSION['role'] === 'admin') {
            header('Location: ' . BASE_URL . 'admin/index.php');
        } elseif ($_SESSION['role'] === 'guru_bk') {
            header('Location: ' . BASE_URL . 'guru/index.php');
        } else {
            header('Location: ' . BASE_URL . 'siswa/index.php');
        }
        exit;
    }
}

/**
 * Sanitasi string input form
 */
function clean(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Set flash message ke session
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type, // success, danger, warning, info
        'message' => $message
    ];
}

/**
 * Render flash alert jika ada
 */
function render_flash(): string {
    if (!isset($_SESSION['flash'])) {
        return '';
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $icon = match ($f['type']) {
        'success' => 'bi-check-circle-fill',
        'danger'  => 'bi-exclamation-triangle-fill',
        'warning' => 'bi-exclamation-circle-fill',
        default   => 'bi-info-circle-fill'
    };

    return '<div class="alert alert-' . $f['type'] . ' alert-dismissible fade show shadow-sm d-flex align-items-center mb-4" role="alert">
        <i class="bi ' . $icon . ' me-2 fs-5"></i>
        <div>' . $f['message'] . '</div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}

/**
 * Format tanggal Indonesia
 * Contoh: 2026-09-26 -> 26 September 2026
 */
function tgl_indo(?string $datetime, bool $with_time = false): string {
    if (empty($datetime)) return '-';
    $time = strtotime($datetime);
    if (!$time) return '-';

    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    $nama_hari = $hari[date('w', $time)];
    $tgl = date('j', $time);
    $bln = $bulan[(int) date('n', $time)];
    $thn = date('Y', $time);

    $hasil = "$tgl $bln $thn";
    if ($with_time) {
        $jam = date('H:i', $time);
        $hasil .= " pukul $jam WIT";
    }
    return $hasil;
}

/**
 * Hitung Kategori Risiko & Rekomendasi Deteksi Dini (Early Warning System)
 * Rumus & Skala Penilaian Proposal:
 * - Akademik: 10 pertanyaan (skor 10 - 40)
 * - Psikososial Kecemasan/Stres: 5 pertanyaan (skor 5 - 20)
 * - Psikososial Relasi Sosial: 5 pertanyaan (skor 5 - 20)
 * - Psikososial Bullying: 5 pertanyaan (skor 5 - 20)
 * Total Skor Minimum = 25, Maksimum = 100
 */
function hitung_evaluasi_risiko(int $skor_akademik, int $skor_psikososial, int $skor_bullying, int $total_skor): array {
    $persentase = round(($total_skor / 100) * 100, 2);

    $catatan = [];
    $tingkat = 'Rendah';
    $rekomendasi = 'Sukarela';

    // Evaluasi Indikator Bullying Spesifik (Kritis jika skor bullying >= 14 dari 20)
    if ($skor_bullying >= 14) {
        $catatan[] = 'Indikasi KRITIS: Terdapat tanda-tanda keterpaparan atau risiko perundungan (bullying) yang memerlukan penanganan darurat guru BK.';
    } elseif ($skor_bullying >= 10) {
        $catatan[] = 'Indikasi Perhatian: Terdeteksi potensi konflik sosial atau intimidasi di lingkungan sekolah.';
    }

    // Evaluasi Akademik
    if ($skor_akademik >= 30) {
        $catatan[] = 'Permasalahan Akademik Signifikan: Kesulitan belajar dan penundaan tugas memerlukan bimbingan strategi belajar.';
    } elseif ($skor_akademik >= 22) {
        $catatan[] = 'Perhatian Akademik: Terdapat penurunan motivasi dan kebiasaan belajar yang perlu didampingi.';
    }

    // Evaluasi Psikososial
    if ($skor_psikososial >= 30) {
        $catatan[] = 'Beban Psikologis Berat: Tingkat kecemasan/stres atau isolasi sosial siswa cukup tinggi.';
    } elseif ($skor_psikososial >= 22) {
        $catatan[] = 'Tekanan Psikososial Sedang: Siswa mengalami kegelisahan atau hambatan komunikasi teman sebaya.';
    }

    // Penentuan Tingkat Risiko Keseluruhan
    if ($total_skor >= 70 || $skor_bullying >= 15) {
        $tingkat = 'Tinggi';
        $rekomendasi = 'Wajib Konseling';
        $summary = 'Siswa terdeteksi berada pada TINGKAT RISIKO TINGGI. Sistem merekomendasikan intervensi konseling segera untuk mencegah perburukan akademik dan kesehatan mental.';
    } elseif ($total_skor >= 45 || $skor_bullying >= 10 || $skor_akademik >= 25 || $skor_psikososial >= 25) {
        $tingkat = 'Sedang';
        $rekomendasi = 'Wajib Konseling';
        $summary = 'Siswa terdeteksi berada pada TINGKAT RISIKO SEDANG. Diperlukan konseling tatap muka untuk mendampingi kendala yang dihadapi siswa.';
    } else {
        $tingkat = 'Rendah';
        $rekomendasi = 'Sukarela';
        $summary = 'Kondisi siswa secara umum stabil dan berada dalam batas risiko rendah. Siswa dapat memanfaatkan konseling secara sukarela.';
    }

    $catatan_str = implode("\n", $catatan);
    if (!empty($catatan_str)) {
        $summary .= "\n\nRincian Analisis:\n" . $catatan_str;
    }

    return [
        'total_skor'         => $total_skor,
        'persentase'         => $persentase,
        'tingkat_risiko'     => $tingkat,
        'status_rekomendasi' => $rekomendasi,
        'catatan_sistem'     => $summary
    ];
}

/**
 * Render Badge Tingkat Risiko
 */
function badge_risiko(string $tingkat): string {
    return match (strtolower($tingkat)) {
        'tinggi' => '<span class="badge bg-danger text-white"><i class="bi bi-exclamation-octagon-fill me-1"></i>Tinggi (Bahaya)</span>',
        'sedang' => '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle-fill me-1"></i>Sedang (Perhatian)</span>',
        default  => '<span class="badge bg-success text-white"><i class="bi bi-shield-check me-1"></i>Rendah (Aman)</span>'
    };
}

/**
 * Render Badge Status Pengajuan / Jadwal
 */
function badge_status(string $status): string {
    return match (strtolower($status)) {
        'menunggu'      => '<span class="badge bg-secondary"><i class="bi bi-hourglass-split me-1"></i>Menunggu</span>',
        'disetujui', 'dijadwalkan' => '<span class="badge bg-primary"><i class="bi bi-calendar-check me-1"></i>Disetujui</span>',
        'berlangsung'   => '<span class="badge bg-info text-dark"><i class="bi bi-play-circle me-1"></i>Berlangsung</span>',
        'selesai'       => '<span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>Selesai</span>',
        'ditolak', 'batal' => '<span class="badge bg-danger"><i class="bi bi-x-circle-fill me-1"></i>Ditolak</span>',
        default         => '<span class="badge bg-light text-dark">' . htmlspecialchars($status) . '</span>'
    };
}

/**
 * Kirim notifikasi sistem ke pengguna
 */
function kirim_notifikasi(PDO $pdo, int $user_id, string $judul, string $pesan, ?string $link = null): bool {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifikasi (user_id, judul, pesan, link) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$user_id, $judul, $pesan, $link]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Dapatkan jumlah notifikasi belum dibaca
 */
function unread_notifications_count(PDO $pdo, int $user_id): int {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return (int) $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}
