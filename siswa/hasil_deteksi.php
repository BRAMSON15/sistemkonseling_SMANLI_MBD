<?php
/**
 * Hasil Analisis Deteksi Dini Siswa
 * Sistem Konseling SMA Negeri 5 Maluku Barat Daya
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

require_role('siswa');
$user = auth_user();
$siswa_id = $user['role_id'];
$page_title = 'Hasil Deteksi Dini';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $stmt = $pdo->prepare("
        SELECT s.*, k.judul AS judul_kuesioner 
        FROM skrining_asesmen s
        JOIN kuesioner k ON s.kuesioner_id = k.id
        WHERE s.id = ? AND s.siswa_id = ?
    ");
    $stmt->execute([$id, $siswa_id]);
    $hasil = $stmt->fetch();
} else {
    // Ambil data terbaru jika tidak ada ID
    $stmt = $pdo->prepare("
        SELECT s.*, k.judul AS judul_kuesioner 
        FROM skrining_asesmen s
        JOIN kuesioner k ON s.kuesioner_id = k.id
        WHERE s.siswa_id = ? 
        ORDER BY s.tanggal_pengisian DESC LIMIT 1
    ");
    $stmt->execute([$siswa_id]);
    $hasil = $stmt->fetch();
}

if (!$hasil) {
    set_flash('info', 'Anda belum memiliki riwayat pengisian kuesioner deteksi dini.');
    header('Location: ' . BASE_URL . 'siswa/deteksi_dini.php');
    exit;
}

// Persentase dimensi
$pct_akademik = round(($hasil['skor_akademik'] / 40) * 100);
$pct_psikososial = round(($hasil['skor_psikososial'] / 40) * 100);
$pct_bullying = round(($hasil['skor_bullying'] / 20) * 100);

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Hasil Analisis Deteksi Dini</h4>
        <p class="text-secondary small mb-0">Dilaksanakan pada: <strong><?= tgl_indo($hasil['tanggal_pengisian'], true) ?></strong></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>siswa/deteksi_dini.php" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-repeat me-1"></i> Tes Ulang
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print">
            <i class="bi bi-printer me-1"></i> Cetak Hasil
        </button>
    </div>
</div>

<!-- Header Kop Surat untuk Mode Cetak -->
<div class="kop-surat">
    <div class="kop-text text-center">
        <h4>PEMERINTAH PROVINSI MALUKU - DINAS PENDIDIKAN DAN KEBUDAYAAN</h4>
        <h3>SMA NEGERI 5 MALUKU BARAT DAYA</h3>
        <p>Jl. Trans Pulau Moa, Tiakur, Kabupaten Maluku Barat Daya, Maluku &bull; Kode Pos 97442</p>
        <p>Layanan Bimbingan & Konseling &bull; Lembar Hasil Asesmen Deteksi Dini Siswa</p>
    </div>
</div>

<div class="row g-4">
    <!-- Kolom Kiri: Ringkasan Skor & Status Risiko -->
    <div class="col-lg-5">
        <div class="card card-base p-4 text-center h-100">
            <span class="text-muted small text-uppercase fw-semibold tracking-wide">Tingkat Risiko Terindikasi</span>
            
            <div class="my-3">
                <?php if ($hasil['tingkat_risiko'] === 'Tinggi'): ?>
                    <div class="d-inline-flex p-3 rounded-circle bg-danger-subtle text-danger mb-2">
                        <i class="bi bi-exclamation-octagon-fill display-4"></i>
                    </div>
                    <h2 class="fw-bold text-danger mb-0">RISIKO TINGGI</h2>
                    <p class="text-muted small mt-1">Perlu Intervensi Segera</p>
                <?php elseif ($hasil['tingkat_risiko'] === 'Sedang'): ?>
                    <div class="d-inline-flex p-3 rounded-circle bg-warning-subtle text-warning mb-2">
                        <i class="bi bi-exclamation-triangle-fill display-4"></i>
                    </div>
                    <h2 class="fw-bold text-warning mb-0">RISIKO SEDANG</h2>
                    <p class="text-muted small mt-1">Perlu Bimbingan & Pendampingan</p>
                <?php else: ?>
                    <div class="d-inline-flex p-3 rounded-circle bg-success-subtle text-success mb-2">
                        <i class="bi bi-shield-check display-4"></i>
                    </div>
                    <h2 class="fw-bold text-success mb-0">RISIKO RENDAH</h2>
                    <p class="text-muted small mt-1">Kondisi Adaptif & Normal</p>
                <?php endif; ?>
            </div>

            <div class="p-3 bg-light rounded-3 mb-3">
                <div class="row text-center">
                    <div class="col-6 border-end">
                        <span class="text-secondary small">Total Skor</span>
                        <div class="fs-4 fw-bold text-dark"><?= $hasil['total_skor'] ?> <small class="text-muted fs-6">/ 100</small></div>
                    </div>
                    <div class="col-6">
                        <span class="text-secondary small">Persentase Risiko</span>
                        <div class="fs-4 fw-bold text-primary"><?= $hasil['persentase_skor'] ?>%</div>
                    </div>
                </div>
            </div>

            <!-- Rekomendasi Tindak Lanjut -->
            <div class="text-start mt-2">
                <label class="small fw-semibold text-secondary mb-1">Rekomendasi Layanan:</label>
                <?php if ($hasil['status_rekomendasi'] === 'Wajib Konseling'): ?>
                    <div class="alert alert-danger py-2 px-3 small mb-3">
                        <i class="bi bi-exclamation-circle-fill me-1"></i>
                        <strong>Wajib Konseling:</strong> Sistem menyarankan pertemuan terjadwal bersama Guru BK.
                    </div>
                    <a href="<?= BASE_URL ?>siswa/ajukan_konseling.php?skrining_id=<?= $hasil['id'] ?>" class="btn btn-primary w-100 py-2 fw-bold">
                        <i class="bi bi-calendar-plus-fill me-1"></i> Ajukan Jadwal Konseling
                    </a>
                <?php else: ?>
                    <div class="alert alert-success py-2 px-3 small mb-3">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        <strong>Konseling Sukarela:</strong> Anda tidak diwajibkan, namun dipersilakan jika ingin konsultasi karir/pribadi.
                    </div>
                    <a href="<?= BASE_URL ?>siswa/ajukan_konseling.php?skrining_id=<?= $hasil['id'] ?>" class="btn btn-outline-primary w-100 py-2">
                        <i class="bi bi-calendar-event me-1"></i> Ajukan Konseling Sukarela
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Rincian Dimensi Skor & Catatan Analisis -->
    <div class="col-lg-7">
        <div class="card card-base p-4 mb-4">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-bar-chart-steps text-primary me-2"></i>Distribusi Skor per Dimensi</h5>

            <!-- Dimensi Akademik -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-semibold small"><i class="bi bi-mortarboard me-1 text-primary"></i> 1. Dimensi Akademik</span>
                    <span class="small fw-bold text-dark"><?= $hasil['skor_akademik'] ?> / 40 (<?= $pct_akademik ?>%)</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar <?= $pct_akademik >= 75 ? 'bg-danger' : ($pct_akademik >= 50 ? 'bg-warning' : 'bg-success') ?>" 
                         role="progressbar" style="width: <?= $pct_akademik ?>%"></div>
                </div>
                <small class="text-muted">Mengukur kesulitan materi, konsentrasi belajar, dan ketepatan tugas.</small>
            </div>

            <!-- Dimensi Stres & Relasi Sosial -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-semibold small"><i class="bi bi-heart-pulse me-1 text-warning"></i> 2. Dimensi Psikososial (Kecemasan & Sosial)</span>
                    <span class="small fw-bold text-dark"><?= $hasil['skor_psikososial'] ?> / 40 (<?= $pct_psikososial ?>%)</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar <?= $pct_psikososial >= 75 ? 'bg-danger' : ($pct_psikososial >= 50 ? 'bg-warning' : 'bg-success') ?>" 
                         role="progressbar" style="width: <?= $pct_psikososial ?>%"></div>
                </div>
                <small class="text-muted">Mengukur ketegangan pikiran, beban emosi, pola tidur, dan rasa minder.</small>
            </div>

            <!-- Dimensi Perundungan (Bullying) -->
            <div class="mb-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-semibold small"><i class="bi bi-shield-exclamation me-1 text-danger"></i> 3. Dimensi Perlindungan dari Bullying</span>
                    <span class="small fw-bold text-dark"><?= $hasil['skor_bullying'] ?> / 20 (<?= $pct_bullying ?>%)</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar <?= $pct_bullying >= 60 ? 'bg-danger' : ($pct_bullying >= 40 ? 'bg-warning' : 'bg-success') ?>" 
                         role="progressbar" style="width: <?= $pct_bullying ?>%"></div>
                </div>
                <small class="text-muted">Mengukur paparan ejekan verbal, intimidasi fisik, rumor, atau pengucilan.</small>
            </div>
        </div>

        <!-- Catatan Hasil Analisis Sistem -->
        <div class="card card-base p-4">
            <h5 class="fw-bold text-dark mb-2"><i class="bi bi-chat-left-quote text-secondary me-2"></i>Catatan Analisis Sistem (EWS)</h5>
            <div class="bg-light p-3 rounded-3 text-secondary small" style="white-space: pre-line; line-height: 1.6;">
                <?= htmlspecialchars($hasil['catatan_sistem']) ?>
            </div>
            <div class="mt-3 small text-muted">
                <i class="bi bi-info-circle me-1"></i> Asesmen ini bertujuan untuk memetakan kebutuhan bantuan sedini mungkin. Jika kamu membutuhkan konseling segera di luar jadwal resmi, silakan langsung kunjungi Ruang BK SMA Negeri 5 MBD.
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
