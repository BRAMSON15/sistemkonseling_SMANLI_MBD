<?php
/**
 * Dashboard Siswa Modern, Interaktif, Empatik & Ramah Pengguna
 * Sistem Bimbingan Konseling SMA Negeri 5 Maluku Barat Daya
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

require_role('siswa');
$user = auth_user();
$page_title = 'Dashboard Siswa';
$siswa_id = $user['role_id'];

// Ambil skrining terakhir
$stmt_last = $pdo->prepare("
    SELECT * FROM skrining_asesmen 
    WHERE siswa_id = ? 
    ORDER BY tanggal_pengisian DESC LIMIT 1
");
$stmt_last->execute([$siswa_id]);
$last_screening = $stmt_last->fetch();

// Ambil jadwal konseling aktif atau menunggu
$stmt_jadwal = $pdo->prepare("
    SELECT p.*, j.tanggal_jadwal, j.waktu_mulai, j.waktu_selesai, j.tempat_ruangan, j.catatan_jadwal,
           u.nama_lengkap AS nama_guru, u.no_hp AS no_hp_guru, g.spesialisasi
    FROM pengajuan_konseling p
    LEFT JOIN jadwal_konseling j ON p.id = j.pengajuan_id
    LEFT JOIN guru_bk g ON j.guru_id = g.id OR p.guru_id = g.id
    LEFT JOIN users u ON g.user_id = u.id
    WHERE p.siswa_id = ? AND p.status IN ('Menunggu', 'Disetujui')
    ORDER BY p.tanggal_pengajuan DESC LIMIT 1
");
$stmt_jadwal->execute([$siswa_id]);
$active_counseling = $stmt_jadwal->fetch();

// Total konseling selesai
$stmt_total_k = $pdo->prepare("SELECT COUNT(*) FROM pengajuan_konseling WHERE siswa_id = ? AND status = 'Selesai'");
$stmt_total_k->execute([$siswa_id]);
$total_selesai = (int) $stmt_total_k->fetchColumn();

// Ambil data mood siswa hari ini dari basis data
$stmt_mood = $pdo->prepare("SELECT * FROM siswa_mood WHERE siswa_id = ? AND tanggal = CURDATE() LIMIT 1");
$stmt_mood->execute([$siswa_id]);
$saved_today_mood = $stmt_mood->fetch();
$active_mood_key = $saved_today_mood['mood'] ?? '';
$active_mood_emoji = $saved_today_mood['emoji'] ?? '';

// Sapaan Waktu (WIT)
$jam = (int) date('H');
$sapaan = match(true) {
    $jam >= 5 && $jam < 11 => 'Selamat Pagi ☀️',
    $jam >= 11 && $jam < 15 => 'Selamat Siang 🌤️',
    $jam >= 15 && $jam < 18 => 'Selamat Sore 🌇',
    default => 'Selamat Malam 🌙'
};

include __DIR__ . '/../includes/header.php';
?>

<!-- Banner Sapaan Personal Ramah & Empatik -->
<div class="dashboard-hero dashboard-hero-siswa p-4 p-md-5 mb-4 position-relative">
    <div class="row align-items-center position-relative" style="z-index: 2;">
        <div class="col-lg-8">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 mb-3 rounded-pill" style="background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.25);">
                <i class="bi bi-shield-heart-fill text-warning"></i>
                <span class="small fw-semibold text-white">Ruang Aman & Bimbingan Konseling Siswa</span>
            </div>
            <h2 class="fw-bold mb-2 text-white"><?= $sapaan ?>, <?= htmlspecialchars($user['nama_lengkap']) ?>! 👋</h2>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3 text-white-50 small">
                <span>NISN: <strong class="text-white"><?= htmlspecialchars($user['nisn']) ?></strong></span>
                <span>&bull;</span>
                <span>Kelas: <strong class="text-white"><?= htmlspecialchars($user['nama_kelas'] ?? 'Fase E/F') ?></strong></span>
                <span>&bull;</span>
                <span>SMA Negeri 5 Maluku Barat Daya</span>
            </div>
            <p class="text-white-90 mb-3 small" style="max-width: 650px; line-height: 1.6;">
                Bagaimana harimu sejauh ini? Ruang konseling ini hadir untuk mendengarkan ceritamu, membantumu mengatasi kendala belajar, serta menjaga ketenangan pikiranmu tanpa rasa takut atau malu.
            </p>
            <div class="d-flex flex-wrap align-items-center gap-3">
                <a href="<?= BASE_URL ?>siswa/deteksi_dini.php" class="btn btn-warning text-dark fw-bold px-4 py-2 shadow-sm rounded-pill d-inline-flex align-items-center gap-2">
                    <i class="bi bi-pencil-square"></i> Mulai Deteksi Dini EWS
                </a>
                <a href="<?= BASE_URL ?>siswa/ajukan_konseling.php" class="btn btn-outline-light px-4 py-2 rounded-pill d-inline-flex align-items-center gap-2">
                    <i class="bi bi-calendar-plus"></i> Ajukan Konseling Temu
                </a>
            </div>
        </div>
        <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
            <div class="p-3 rounded-4 text-start text-white d-inline-block w-100 w-lg-auto" style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-shield-check text-warning fs-5"></i>
                    <span class="fw-bold small">Jaminan Kerahasiaan 100%</span>
                </div>
                <p class="mb-0 small text-white-50" style="font-size: 0.775rem;">
                    Semua curhatan, hasil kuesioner, dan catatan konselingmu dijaga kerahasiaannya oleh Guru BK dan tidak dipublikasikan ke siapapun.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Widget Interaktif 1: Mood Tracker Harian Siswa -->
<div class="card card-base p-4 mb-4 bg-white shadow-sm border-primary-subtle">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
        <div>
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-emoji-smile-fill text-warning me-2"></i>Bagaimana Suasana Hatimu Hari Ini?
            </h6>
            <small class="text-muted">Pilih satu emoji yang paling menggambarkan perasaanmu saat ini untuk mencatat jurnal harianmu.</small>
        </div>
        <span class="badge <?= !empty($active_mood_key) ? 'badge-soft-success' : 'bg-light text-secondary border' ?> small py-2 px-3 rounded-pill" id="moodStatusBadge">
            <?= !empty($active_mood_key) ? $active_mood_emoji . ' Tercatat: ' . ucfirst($active_mood_key) : 'Pilih Perasaanmu' ?>
        </span>
    </div>

    <!-- Pilihan Mood -->
    <div class="mood-tracker mb-3">
        <div class="mood-btn <?= $active_mood_key === 'senang' ? 'selected' : '' ?>" data-mood="senang" onclick="setMood('senang', '😊', 'Luar biasa! Pertahankan energi positifmu dan bagikan senyumanmu ke teman-teman di sekolah hari ini! ✨')">
            <span class="mood-emoji">😊</span>
            <small class="fw-bold text-secondary d-block">Senang</small>
            <span class="text-muted" style="font-size: 0.7rem;">Penuh Semangat</span>
        </div>
        <div class="mood-btn <?= $active_mood_key === 'tenang' ? 'selected' : '' ?>" data-mood="tenang" onclick="setMood('tenang', '😌', 'Bagus sekali! Pikiran yang tenang membantumu lebih mudah menyerap pelajaran dan menjaga fokus. 🌿')">
            <span class="mood-emoji">😌</span>
            <small class="fw-bold text-secondary d-block">Tenang</small>
            <span class="text-muted" style="font-size: 0.7rem;">Damai & Rileks</span>
        </div>
        <div class="mood-btn <?= $active_mood_key === 'biasa' ? 'selected' : '' ?>" data-mood="biasa" onclick="setMood('biasa', '😐', 'Hari yang normal dan santai. Lakukan satu hal kecil yang membuatmu tersenyum hari ini! ☕')">
            <span class="mood-emoji">😐</span>
            <small class="fw-bold text-secondary d-block">Biasa Saja</small>
            <span class="text-muted" style="font-size: 0.7rem;">Mengalir Santai</span>
        </div>
        <div class="mood-btn <?= $active_mood_key === 'lelah' ? 'selected' : '' ?>" data-mood="lelah" onclick="setMood('lelah', '😓', 'Merasa lelah atau tugas menumpuk? Rehatlah sejenak, minum air putih, dan lakukan latihan pernapasan di bawah ya. 💧')">
            <span class="mood-emoji">😓</span>
            <small class="fw-bold text-secondary d-block">Lelah</small>
            <span class="text-muted" style="font-size: 0.7rem;">Butuh Rehat</span>
        </div>
        <div class="mood-btn <?= $active_mood_key === 'cemas' ? 'selected' : '' ?>" data-mood="cemas" onclick="setMood('cemas', '😔', 'Kamu tidak sendiri. Jika ada hal yang membebani hatimu, ceritakan ke Guru BK tanpa perlu sungkan. Kami siap mendengarkan. 💙')">
            <span class="mood-emoji">😔</span>
            <small class="fw-bold text-secondary d-block">Cemas / Sedih</small>
            <span class="text-muted" style="font-size: 0.7rem;">Butuh Teman</span>
        </div>
    </div>

    <!-- Kotak Respon & Dukungan Empatik -->
    <div id="moodFeedbackBox" class="p-3 bg-light rounded-3 small text-secondary <?= !empty($active_mood_key) ? '' : 'd-none' ?> border-start border-4 border-primary">
        <i class="bi bi-chat-heart-fill text-primary me-2"></i>
        <span id="moodFeedbackText">
            <?php
                echo match($active_mood_key) {
                    'senang' => 'Luar biasa! Pertahankan energi positifmu dan bagikan senyumanmu ke teman-teman di sekolah hari ini! ✨',
                    'tenang' => 'Bagus sekali! Pikiran yang tenang membantumu lebih mudah menyerap pelajaran dan menjaga fokus. 🌿',
                    'biasa'  => 'Hari yang normal dan santai. Lakukan satu hal kecil yang membuatmu tersenyum hari ini! ☕',
                    'lelah'  => 'Merasa lelah atau tugas menumpuk? Rehatlah sejenak, minum air putih, dan lakukan latihan pernapasan di bawah ya. 💧',
                    'cemas'  => 'Kamu tidak sendiri. Jika ada hal yang membebani hatimu, ceritakan ke Guru BK tanpa perlu sungkan. Kami siap mendengarkan. 💙',
                    default  => ''
                };
            ?>
        </span>
    </div>
</div>

<!-- Alert Peringatan Pendampingan Konseling Jika Risiko Sedang / Tinggi -->
<?php if ($last_screening && in_array($last_screening['tingkat_risiko'], ['Sedang', 'Tinggi']) && (!$active_counseling || $active_counseling['status'] !== 'Disetujui')): ?>
    <div class="card card-base mb-4 border-warning shadow-sm" style="background: #fffbeb;">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-start gap-3">
                    <div class="p-3 bg-warning text-dark rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-heart-pulse-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h6 class="fw-bold text-dark mb-0">Teman Cerita Menantimu di Ruang BK</h6>
                            <?= badge_risiko($last_screening['tingkat_risiko']) ?>
                        </div>
                        <p class="mb-0 small text-secondary" style="max-width: 650px;">
                            Berdasarkan hasil asesmen terakhirmu pada <strong><?= tgl_indo($last_screening['tanggal_pengisian']) ?></strong>, ada beberapa hal yang mungkin sedang membebani pikiran atau belajarmu. Jangan dipendam sendiri, yuk luangkan waktu sejenak untuk ngobrol santai bersama Guru BK.
                        </p>
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <a href="<?= BASE_URL ?>siswa/ajukan_konseling.php?skrining_id=<?= $last_screening['id'] ?>" class="btn btn-dark btn-sm fw-bold px-4 py-2 rounded-pill shadow-sm">
                        <i class="bi bi-calendar-plus-fill me-1"></i> Pilih Jadwal Temu Santai
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- 3 Kartu Progres Siswa (Metrik Utama) -->
<div class="row g-3 mb-4">
    <!-- 1. Card Skrining Terakhir -->
    <div class="col-lg-4">
        <div class="stat-card-modern">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="stat-label">Deteksi Dini Terakhir</span>
                <div class="stat-icon-wrapper bg-primary-subtle text-primary">
                    <i class="bi bi-clipboard2-pulse"></i>
                </div>
            </div>

            <?php if ($last_screening): ?>
                <div class="mb-2">
                    <?= badge_risiko($last_screening['tingkat_risiko']) ?>
                </div>
                <div class="d-flex align-items-baseline gap-1 mb-2">
                    <span class="display-6 fw-bold text-dark"><?= $last_screening['total_skor'] ?></span>
                    <span class="text-muted fw-semibold">/ 100</span>
                </div>
                
                <!-- Breakdown Dimensi Mini Bars -->
                <div class="small mb-3">
                    <div class="d-flex justify-content-between text-muted mb-1" style="font-size: 0.75rem;">
                        <span>Akademik: <strong><?= $last_screening['skor_akademik'] ?>/40</strong></span>
                        <span>Psikososial: <strong><?= $last_screening['skor_psikososial'] ?>/40</strong></span>
                    </div>
                    <div class="micro-bar mb-2">
                        <div class="micro-bar-fill bg-primary" style="width: <?= ($last_screening['total_skor']) ?>%;"></div>
                    </div>
                    <div class="text-muted" style="font-size: 0.725rem;">
                        <i class="bi bi-clock me-1"></i> <?= tgl_indo($last_screening['tanggal_pengisian']) ?>
                    </div>
                </div>

                <div class="mt-auto">
                    <a href="<?= BASE_URL ?>siswa/hasil_deteksi.php?id=<?= $last_screening['id'] ?>" class="btn btn-outline-primary btn-sm w-100 rounded-pill fw-semibold">
                        <i class="bi bi-eye me-1"></i> Buka Lembar Analisis
                    </a>
                </div>
            <?php else: ?>
                <div class="text-muted py-3 small">
                    <i class="bi bi-info-circle text-primary me-1"></i> Belum ada rekaman asesmen deteksi dini.
                </div>
                <div class="mt-auto">
                    <a href="<?= BASE_URL ?>siswa/deteksi_dini.php" class="btn btn-primary btn-sm w-100 rounded-pill fw-semibold">
                        <i class="bi bi-pencil-square me-1"></i> Mulai Pengisian Sekarang
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. Card Jadwal Aktif -->
    <div class="col-lg-4">
        <div class="stat-card-modern">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="stat-label">Jadwal Konseling Saya</span>
                <div class="stat-icon-wrapper bg-info-subtle text-info">
                    <i class="bi bi-calendar2-week"></i>
                </div>
            </div>

            <?php if ($active_counseling): ?>
                <div class="mb-2">
                    <?= badge_status($active_counseling['status']) ?>
                    <?php if ($active_counseling['is_wajib']): ?>
                        <span class="badge bg-danger">Wajib</span>
                    <?php endif; ?>
                </div>

                <?php if ($active_counseling['status'] === 'Disetujui'): ?>
                    <div class="fw-bold text-dark fs-5 mb-1">
                        <i class="bi bi-calendar3 me-1 text-primary"></i> <?= tgl_indo($active_counseling['tanggal_jadwal']) ?>
                    </div>
                    <div class="small text-secondary mb-1">
                        <i class="bi bi-clock me-1 text-primary"></i> <?= substr($active_counseling['waktu_mulai'], 0, 5) ?> - <?= substr($active_counseling['waktu_selesai'], 0, 5) ?> WIT
                    </div>
                    <div class="small text-secondary mb-2">
                        <i class="bi bi-geo-alt-fill me-1 text-danger"></i> <?= htmlspecialchars($active_counseling['tempat_ruangan'] ?? 'Ruang BK SMAN 5 MBD') ?>
                    </div>
                    <div class="small text-muted mb-3" style="font-size: 0.775rem;">
                        Konselor: <strong><?= htmlspecialchars($active_counseling['nama_guru'] ?? 'Guru BK') ?></strong>
                    </div>
                <?php else: ?>
                    <div class="fw-bold text-dark fs-6 mb-1">Menunggu Respon Konselor</div>
                    <p class="small text-muted mb-3" style="font-size: 0.775rem;">
                        Preferensi Waktu: <?= tgl_indo($active_counseling['preferensi_tanggal']) ?> (<?= htmlspecialchars($active_counseling['preferensi_waktu']) ?>)
                    </p>
                <?php endif; ?>

                <div class="mt-auto">
                    <a href="<?= BASE_URL ?>siswa/riwayat_siswa.php" class="btn btn-outline-secondary btn-sm w-100 rounded-pill fw-semibold">
                        <i class="bi bi-search me-1"></i> Cek Status Pengajuan
                    </a>
                </div>
            <?php else: ?>
                <div class="text-muted py-3 small">
                    <i class="bi bi-check2-circle text-success me-1"></i> Tidak ada antrean konseling saat ini.
                </div>
                <div class="mt-auto">
                    <a href="<?= BASE_URL ?>siswa/ajukan_konseling.php" class="btn btn-outline-primary btn-sm w-100 rounded-pill fw-semibold">
                        <i class="bi bi-calendar-plus me-1"></i> Buat Janji Konseling
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 3. Card Sesi Selesai & Prestasi Diri -->
    <div class="col-lg-4">
        <div class="stat-card-modern">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="stat-label">Sesi Konseling Tuntas</span>
                <div class="stat-icon-wrapper bg-success-subtle text-success">
                    <i class="bi bi-award-fill"></i>
                </div>
            </div>
            <div class="display-6 fw-bold text-dark"><?= $total_selesai ?></div>
            <div class="stat-sub text-success fw-semibold">
                <i class="bi bi-check-circle me-1"></i> Sesi Bimbingan Tuntas
            </div>
            <p class="text-muted small mt-2 mb-3" style="font-size: 0.775rem; line-height: 1.5;">
                Total sesi konsultasi yang telah kamu jalani bersama Guru BK untuk pengembangan diri dan prestasi.
            </p>
            <div class="mt-auto">
                <a href="<?= BASE_URL ?>siswa/riwayat_siswa.php" class="btn btn-light btn-sm w-100 border rounded-pill fw-semibold">
                    <i class="bi bi-clock-history me-1"></i> Buka Catatan & Solusi
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Fitur Relaksasi Pernapasan 4-7-8 & Tips Kesehatan Mental -->
<div class="row g-4 mb-4">
    <!-- Interactive Guided Breathing Exercise -->
    <div class="col-lg-5">
        <div class="card card-base p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-wind text-primary me-2"></i>Relaksasi Pernapasan (4-7-8)
                    </h6>
                    <small class="text-muted">Latihan cepat untuk meredakan cemas & tegang</small>
                </div>
                <span class="badge badge-soft-primary small">Self-Care</span>
            </div>

            <!-- Breathing Box Container -->
            <div class="breathing-box my-3" id="breathingBox">
                <div class="breathing-circle-outer" id="breathingOuter">
                    <div class="breathing-circle-inner" id="breathingInner">
                        <span id="breathingStageText">Mulai</span>
                        <span id="breathingSeconds" style="font-size: 1.25rem;">🌬️</span>
                    </div>
                </div>
                <div class="fw-bold text-dark small mt-2" id="breathingGuideText">
                    Klik tombol di bawah untuk memulai panduan pernapasan
                </div>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.725rem;">
                    Tarik napas 4 detik, tahan 7 detik, lalu hembuskan perlahan 8 detik.
                </p>
            </div>

            <div class="text-center mt-auto">
                <button class="btn btn-primary btn-sm rounded-pill px-4 fw-bold" id="btnToggleBreathe" onclick="toggleBreathing()">
                    <i class="bi bi-play-fill me-1"></i> Mulai Latihan Relaksasi
                </button>
            </div>
        </div>
    </div>

    <!-- Tips Belajar & Janji Kerahasiaan BK -->
    <div class="col-lg-7">
        <div class="card card-base p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-lightbulb-fill text-warning me-2"></i>Strategi Belajar & Tips Hari Ini
                </h6>
                <button class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1" onclick="nextTip()">
                    <i class="bi bi-arrow-repeat me-1"></i> Ganti Tips
                </button>
            </div>

            <!-- Card Tips Dinamis -->
            <div id="tipContent" class="p-3 bg-light rounded-3 border-start border-4 border-primary mb-3">
                <h6 class="fw-bold text-primary mb-1" id="tipTitle">Teknik Belajar Pomodoro (25 + 5 Menit)</h6>
                <p class="small text-secondary mb-0" id="tipDesc" style="line-height: 1.6;">
                    Belajarlah fokus selama 25 menit penuh tanpa gangguan HP, lalu istirahat santai 5 menit. Cara ini terbukti menjaga konsentrasi otak tetap segar dan mencegah prokrastinasi menunda tugas.
                </p>
            </div>

            <!-- 3 Prinsip Ruang Aman BK SMAN 5 MBD -->
            <div class="pt-2">
                <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-shield-check text-success me-1"></i>3 Komitmen Bimbingan Konseling SMAN 5 MBD:</h6>
                <div class="row g-2 small text-secondary">
                    <div class="col-12 d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success flex-shrink-0"></i>
                        <span><strong>100% Rahasia:</strong> Jawaban kuesioner dan sesi curhat tidak dibagikan ke siapapun.</span>
                    </div>
                    <div class="col-12 d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success flex-shrink-0"></i>
                        <span><strong>Bebas Sanksi:</strong> Konseling bukan hukuman dan tidak mempengaruhi nilai akademik raport.</span>
                    </div>
                    <div class="col-12 d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success flex-shrink-0"></i>
                        <span><strong>Ruang Suportif:</strong> Guru BK hadir sebagai sahabat bicara untuk mencari solusi bersama.</span>
                    </div>
                </div>
            </div>

            <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center small text-muted" style="font-size: 0.775rem;">
                <span><i class="bi bi-geo-alt me-1"></i> Gedung Utama Lt. 1 SMAN 5 MBD</span>
                <span>Tiakur, Moa</span>
            </div>
        </div>
    </div>
</div>

<script>
// Mood Tracker Interaktif Terhubung ke Backend Basis Data
function setMood(moodKey, emoji, feedback) {
    document.querySelectorAll('.mood-btn').forEach(b => b.classList.remove('selected'));
    const btn = document.querySelector(`.mood-btn[data-mood="${moodKey}"]`);
    if (btn) btn.classList.add('selected');

    const badge = document.getElementById('moodStatusBadge');
    badge.className = 'badge badge-soft-primary small py-2 px-3 rounded-pill';
    badge.innerHTML = `<span class="spinner-border spinner-border-sm me-1" style="width: 10px; height: 10px;"></span> Menyimpan...`;

    const box = document.getElementById('moodFeedbackBox');
    const text = document.getElementById('moodFeedbackText');
    box.classList.remove('d-none');
    text.textContent = feedback;

    // Kirim ke Backend API via POST
    fetch('<?= BASE_URL ?>siswa/api_mood.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            mood: moodKey,
            emoji: emoji
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            badge.className = 'badge badge-soft-success small py-2 px-3 rounded-pill';
            badge.textContent = `${emoji} Tersimpan di Server`;
            localStorage.setItem('student_daily_mood', moodKey);
        } else {
            badge.className = 'badge badge-soft-warning small py-2 px-3 rounded-pill';
            badge.textContent = `${emoji} Tersimpan Lokal`;
        }
    })
    .catch(err => {
        badge.className = 'badge badge-soft-secondary small py-2 px-3 rounded-pill';
        badge.textContent = `${emoji} Tercatat Offline`;
    });
}

// Rotasi Tips Edukasi & Kesehatan Mental
const studyTips = [
    {
        title: "Teknik Belajar Pomodoro (25 + 5 Menit)",
        desc: "Belajarlah fokus selama 25 menit penuh tanpa gangguan HP, lalu istirahat santai 5 menit. Cara ini terbukti menjaga konsentrasi otak tetap segar dan mencegah prokrastinasi tugas."
    },
    {
        title: "Latihan Pernapasan 4-7-8 untuk Meredakan Cemas",
        desc: "Tarik napas perlahan lewat hidung selama 4 detik, tahan napas selama 7 detik, lalu hembuskan lewat mulut selama 8 detik. Ulangi 4 kali saat kamu merasa tegang sebelum ujian."
    },
    {
        title: "Cukup Tidur 7-8 Jam Menjelang Ujian",
        desc: "Belajar semalam suntuk (SKS) justru menurunkan daya ingat hingga 40%. Tidur yang cukup membantu otak memindahkan memori materi pelajaran ke ingatan jangka panjang."
    },
    {
        title: "Jangan Ragu Berkata 'Tidak' pada Tekanan Negatif",
        desc: "Teman yang baik akan menghargai batasanmu. Jika ada ajakan yang melanggar aturan atau membuatmu tidak nyaman, tegaslah menolak demi masa depanmu sendiri."
    },
    {
        title: "Catat 3 Hal yang Kamu Syukuri Hari Ini",
        desc: "Sebelum tidur, tuliskan 3 hal sederhana yang membuatmu bersyukur hari ini. Kebiasaan ini melatih otakmu lebih tangguh dan bahagia menghadapi tantangan esok hari."
    }
];

let currentTipIndex = 0;
function nextTip() {
    currentTipIndex = (currentTipIndex + 1) % studyTips.length;
    document.getElementById('tipTitle').textContent = studyTips[currentTipIndex].title;
    document.getElementById('tipDesc').textContent = studyTips[currentTipIndex].desc;
}

// Interactive 4-7-8 Breathing Guided Animation
let isBreathing = false;
let breatheTimer = null;
let currentPhase = 'idle'; // inhale, hold, exhale
let timeLeft = 0;

function toggleBreathing() {
    const box = document.getElementById('breathingBox');
    const btn = document.getElementById('btnToggleBreathe');
    const stageText = document.getElementById('breathingStageText');
    const secondsText = document.getElementById('breathingSeconds');
    const guideText = document.getElementById('breathingGuideText');

    if (!isBreathing) {
        isBreathing = true;
        btn.className = 'btn btn-outline-danger btn-sm rounded-pill px-4 fw-bold';
        btn.innerHTML = '<i class="bi bi-stop-fill me-1"></i> Hentikan Relaksasi';
        box.classList.add('breathing-active');
        runBreathingCycle();
    } else {
        isBreathing = false;
        clearTimeout(breatheTimer);
        btn.className = 'btn btn-primary btn-sm rounded-pill px-4 fw-bold';
        btn.innerHTML = '<i class="bi bi-play-fill me-1"></i> Mulai Latihan Relaksasi';
        box.classList.remove('breathing-active');
        stageText.textContent = 'Mulai';
        secondsText.textContent = '🌬️';
        guideText.textContent = 'Latihan dihentikan. Kamu bisa memulainya lagi kapanpun dibutuhkan.';
    }
}

function runBreathingCycle() {
    if (!isBreathing) return;

    const stageText = document.getElementById('breathingStageText');
    const secondsText = document.getElementById('breathingSeconds');
    const guideText = document.getElementById('breathingGuideText');

    // 1. Inhale (4s)
    stageText.textContent = 'Tarik Napas';
    guideText.textContent = 'Tarik napas perlahan lewat hidung...';
    let s = 4;
    secondsText.textContent = s + 's';

    const countInhale = setInterval(() => {
        if (!isBreathing) { clearInterval(countInhale); return; }
        s--;
        if (s > 0) {
            secondsText.textContent = s + 's';
        } else {
            clearInterval(countInhale);
            // 2. Hold (7s)
            stageText.textContent = 'Tahan';
            guideText.textContent = 'Tahan napasmu dengan rileks...';
            let h = 7;
            secondsText.textContent = h + 's';

            const countHold = setInterval(() => {
                if (!isBreathing) { clearInterval(countHold); return; }
                h--;
                if (h > 0) {
                    secondsText.textContent = h + 's';
                } else {
                    clearInterval(countHold);
                    // 3. Exhale (8s)
                    stageText.textContent = 'Hembuskan';
                    guideText.textContent = 'Hembuskan perlahan lewat mulut sampai habis...';
                    let e = 8;
                    secondsText.textContent = e + 's';

                    const countExhale = setInterval(() => {
                        if (!isBreathing) { clearInterval(countExhale); return; }
                        e--;
                        if (e > 0) {
                            secondsText.textContent = e + 's';
                        } else {
                            clearInterval(countExhale);
                            // Loop cycle
                            if (isBreathing) {
                                runBreathingCycle();
                            }
                        }
                    }, 1000);
                }
            }, 1000);
        }
    }, 1000);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
