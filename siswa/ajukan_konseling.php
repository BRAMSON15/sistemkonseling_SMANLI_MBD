<?php
/**
 * Formulir Pengajuan Jadwal Konseling
 * Siswa - SMA Negeri 5 Maluku Barat Daya
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

require_role('siswa');
$user = auth_user();
$siswa_id = $user['role_id'];
$page_title = 'Pengajuan Jadwal Konseling';

$skrining_id = isset($_GET['skrining_id']) ? (int) $_GET['skrining_id'] : null;
$skrining_data = null;
$is_wajib = 0;

if ($skrining_id) {
    $stmt_sk = $pdo->prepare("SELECT * FROM skrining_asesmen WHERE id = ? AND siswa_id = ?");
    $stmt_sk->execute([$skrining_id, $siswa_id]);
    $skrining_data = $stmt_sk->fetch();
    if ($skrining_data && in_array($skrining_data['tingkat_risiko'], ['Sedang', 'Tinggi'])) {
        $is_wajib = 1;
    }
}

// Ambil daftar Guru BK aktif
$stmt_g = $pdo->query("
    SELECT g.id AS guru_id, u.nama_lengkap, g.nip, g.spesialisasi, g.no_ruangan 
    FROM guru_bk g 
    JOIN users u ON g.user_id = u.id 
    WHERE u.is_active = 1
");
$guru_list = $stmt_g->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guru_id           = (int) ($_POST['guru_id'] ?? 0);
    $jenis_konseling   = clean($_POST['jenis_konseling'] ?? 'Individu');
    $preferensi_tgl    = clean($_POST['preferensi_tanggal'] ?? '');
    $preferensi_waktu  = clean($_POST['preferensi_waktu'] ?? '');
    $alasan_pengajuan  = clean($_POST['alasan_pengajuan'] ?? '');

    if (empty($preferensi_tgl) || empty($preferensi_waktu) || empty($alasan_pengajuan)) {
        $errors[] = 'Silakan lengkapi semua kolom yang bertanda bintang (*).';
    } elseif (strtotime($preferensi_tgl) < strtotime(date('Y-m-d'))) {
        $errors[] = 'Tanggal preferensi tidak boleh tanggal yang sudah lewat.';
    } else {
        try {
            $stmt_ins = $pdo->prepare("
                INSERT INTO pengajuan_konseling 
                (siswa_id, skrining_id, guru_id, tanggal_pengajuan, preferensi_tanggal, preferensi_waktu, jenis_konseling, alasan_pengajuan, status, is_wajib)
                VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, 'Menunggu', ?)
            ");
            $stmt_ins->execute([
                $siswa_id,
                $skrining_id,
                $guru_id > 0 ? $guru_id : null,
                $preferensi_tgl,
                $preferensi_waktu,
                $jenis_konseling,
                $alasan_pengajuan,
                $is_wajib
            ]);

            $pengajuan_id = $pdo->lastInsertId();

            // Kirim notifikasi ke Guru BK terpilih atau semua Guru BK
            if ($guru_id > 0) {
                $stmt_u = $pdo->prepare("SELECT user_id FROM guru_bk WHERE id = ?");
                $stmt_u->execute([$guru_id]);
                $u_id = $stmt_u->fetchColumn();
                if ($u_id) {
                    kirim_notifikasi(
                        $pdo,
                        $u_id,
                        'Pengajuan Konseling Baru',
                        'Siswa ' . $user['nama_lengkap'] . ' (' . ($user['nama_kelas'] ?? '') . ') mengajukan jadwal konseling untuk tanggal ' . tgl_indo($preferensi_tgl),
                        'guru/pengajuan.php'
                    );
                }
            } else {
                foreach ($guru_list as $g) {
                    $stmt_u = $pdo->prepare("SELECT user_id FROM guru_bk WHERE id = ?");
                    $stmt_u->execute([$g['guru_id']]);
                    $u_id = $stmt_u->fetchColumn();
                    if ($u_id) {
                        kirim_notifikasi(
                            $pdo,
                            $u_id,
                            'Pengajuan Konseling Baru',
                            'Siswa ' . $user['nama_lengkap'] . ' (' . ($user['nama_kelas'] ?? '') . ') mengajukan jadwal konseling untuk tanggal ' . tgl_indo($preferensi_tgl),
                            'guru/pengajuan.php'
                        );
                    }
                }
            }

            set_flash('success', 'Pengajuan jadwal konseling berhasil dikirim! Mohon menunggu konfirmasi waktu dan tempat dari Guru BK.');
            header('Location: ' . BASE_URL . 'siswa/riwayat_siswa.php');
            exit;

        } catch (Exception $e) {
            $errors[] = 'Gagal menyimpan pengajuan: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-calendar-plus-fill text-primary me-2"></i>Pengajuan Jadwal Konseling</h4>
        <p class="text-secondary small mb-0">Ajukan permohonan sesi bimbingan bersama Guru BK secara terstruktur dan terjaga kerahasiaannya.</p>
    </div>
    <a href="<?= BASE_URL ?>siswa/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Batal
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger shadow-sm mb-4">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Periksa kembali isian formulir:</div>
        <ul class="mb-0 small ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($is_wajib): ?>
    <div class="alert alert-warning border-warning d-flex align-items-center mb-4 p-3 shadow-sm">
        <i class="bi bi-shield-exclamation fs-3 text-warning me-3"></i>
        <div>
            <div class="fw-bold text-dark">Rekomendasi Wajib Konseling (Deteksi Dini)</div>
            <div class="small text-secondary">
                Pengajuan ini terhubung dengan hasil asesmen deteksi dini Anda yang menunjukkan tingkat risiko <strong><?= strtoupper($skrining_data['tingkat_risiko']) ?></strong>.
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-base p-4">
            <form method="POST" action="">
                <!-- Pilihan Guru BK -->
                <div class="mb-3">
                    <label for="guru_id" class="form-label small fw-semibold text-secondary">Pilih Guru BK / Konselor</label>
                    <select name="guru_id" id="guru_id" class="form-select">
                        <option value="">-- Guru BK yang Sedang Bertugas (Otomatis) --</option>
                        <?php foreach ($guru_list as $g): ?>
                            <option value="<?= $g['guru_id'] ?>">
                                <?= htmlspecialchars($g['nama_lengkap']) ?> - <?= htmlspecialchars($g['spesialisasi']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text small">Anda dapat memilih guru BK tertentu atau membiarkannya kosong untuk dialokasikan secara acak/merata.</div>
                </div>

                <!-- Jenis Konseling -->
                <div class="mb-3">
                    <label for="jenis_konseling" class="form-label small fw-semibold text-secondary">Jenis Layanan Konseling <span class="text-danger">*</span></label>
                    <select name="jenis_konseling" id="jenis_konseling" class="form-select" required>
                        <option value="Individu" selected>Konseling Individu (Privat 1-on-1)</option>
                        <option value="Akademik">Bimbingan Belajar & Kendala Akademik</option>
                        <option value="Psikososial/Pribadi">Konseling Psikososial & Masalah Pribadi</option>
                        <option value="Karir">Bimbingan Karir & Pilihan Minat Studi Lanjut</option>
                        <option value="Kelompok">Konseling Kelompok Teman Sebaya</option>
                    </select>
                </div>

                <!-- Preferensi Waktu & Tanggal -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="preferensi_tanggal" class="form-label small fw-semibold text-secondary">Preferensi Tanggal Pertemuan <span class="text-danger">*</span></label>
                        <input type="date" name="preferensi_tanggal" id="preferensi_tanggal" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                        <div class="form-text small">Pilih tanggal yang memungkinkan Anda hadir ke Ruang BK.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="preferensi_waktu" class="form-label small fw-semibold text-secondary">Preferensi Jam Pertemuan <span class="text-danger">*</span></label>
                        <select name="preferensi_waktu" id="preferensi_waktu" class="form-select" required>
                            <option value="Istirahat 1 (09:45 - 10:15 WIT)">Jam Istirahat 1 (09:45 - 10:15 WIT)</option>
                            <option value="Istirahat 2 (12:00 - 12:45 WIT)">Jam Istirahat 2 (12:00 - 12:45 WIT)</option>
                            <option value="Setelah Pulang Sekolah (14:00 - 15:00 WIT)">Setelah Pulang Sekolah (14:00 - 15:00 WIT)</option>
                            <option value="Jam Kosong / Fleksibel">Jam Pelajaran Kosong / Fleksibel</option>
                        </select>
                    </div>
                </div>

                <!-- Alasan / Catatan Pengajuan -->
                <div class="mb-4">
                    <label for="alasan_pengajuan" class="form-label small fw-semibold text-secondary">Uraian Singkat Kendala / Hal yang Ingin Dikonsultasikan <span class="text-danger">*</span></label>
                    <textarea name="alasan_pengajuan" id="alasan_pengajuan" rows="4" class="form-control" placeholder="Tuliskan secara singkat kendala belajar, rasa cemas, konflik dengan teman, atau hal apa saja yang ingin kamu ceritakan kepada Guru BK..." required></textarea>
                    <div class="form-text small text-muted">
                        <i class="bi bi-lock-fill text-success me-1"></i> Cerita Anda sepenuhnya rahasia dan aman. Guru BK siap mendengar tanpa menghakimi.
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= BASE_URL ?>siswa/index.php" class="btn btn-outline-secondary px-4">Batal</a>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                        <i class="bi bi-send-check me-1"></i> Kirim Pengajuan Konseling
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
