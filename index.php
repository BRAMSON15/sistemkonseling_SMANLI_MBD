<?php
/**
 * Landing Page Utama Modern & Interaktif
 * Sistem Konseling SMA Negeri 5 Maluku Barat Daya
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helper.php';

$user = auth_user();

// Query Dinamis dari Basis Data
$total_siswa_db     = (int) $pdo->query("SELECT COUNT(*) FROM siswa")->fetchColumn();
$total_soal_db      = (int) $pdo->query("SELECT COUNT(*) FROM pertanyaan WHERE is_active = 1")->fetchColumn();
$total_asesmen_db   = (int) $pdo->query("SELECT COUNT(*) FROM skrining_asesmen")->fetchColumn();
$total_konseling_db = (int) $pdo->query("SELECT COUNT(*) FROM jadwal_konseling WHERE status = 'Selesai'")->fetchColumn();

// Jumlah soal dinamis per kategori dimensi
$count_akademik = (int) $pdo->query("SELECT COUNT(*) FROM pertanyaan WHERE kategori = 'akademik' AND is_active = 1")->fetchColumn();
$count_stres    = (int) $pdo->query("SELECT COUNT(*) FROM pertanyaan WHERE kategori = 'kecemasan_stres' AND is_active = 1")->fetchColumn();
$count_sosial   = (int) $pdo->query("SELECT COUNT(*) FROM pertanyaan WHERE kategori = 'sosial_relasi' AND is_active = 1")->fetchColumn();
$count_bullying = (int) $pdo->query("SELECT COUNT(*) FROM pertanyaan WHERE kategori = 'perundungan_bullying' AND is_active = 1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Konseling Deteksi Dini - SMA Negeri 5 MBD</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/custom.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #ffffff; }
        
        .hero-section {
            background: linear-gradient(135deg, #091322 0%, #1e3a8a 55%, #0f766e 100%);
            color: #ffffff;
            padding: 100px 0 90px;
            position: relative;
            overflow: hidden;
        }
        
        .hero-pattern {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.12) 1px, transparent 1px);
            background-size: 28px 28px;
            opacity: 0.6;
        }

        .glow-circle {
            position: absolute;
            width: 450px;
            height: 450px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.15) 0%, rgba(0, 0, 0, 0) 70%);
            top: -100px;
            right: -50px;
            pointer-events: none;
        }

        .hero-badge {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #f8fafc;
            padding: 8px 18px;
            border-radius: 50px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }

        .stat-counter-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .stat-counter-card:hover {
            transform: translateY(-5px);
            border-color: #3b82f6;
            box-shadow: 0 20px 30px -10px rgba(59, 130, 246, 0.15);
        }

        .interactive-sim-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #e0f2fe 100%);
            border: 2px dashed #38bdf8;
            border-radius: 20px;
            padding: 2rem;
        }

        .dimension-nav .nav-link {
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 600;
            color: #475569;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
        }

        .dimension-nav .nav-link.active {
            background: #ffffff;
            color: #1e3a8a;
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .faq-accordion .accordion-button:not(.collapsed) {
            background-color: #f1f5f9;
            color: #1e3a8a;
            font-weight: 700;
        }
        
        .faq-accordion .accordion-button {
            font-weight: 600;
            border-radius: 12px !important;
        }
    </style>
</head>
<body>

<!-- Navbar Publik -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3 sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= BASE_URL ?>">
            <div class="bg-primary text-white rounded-3 p-2 d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                <i class="bi bi-shield-heart-fill fs-5"></i>
            </div>
            <div>
                <span class="d-block" style="line-height: 1.2;">BK SMAN 5 MBD</span>
                <small class="text-white-50 fw-normal" style="font-size: 0.68rem;">Deteksi Dini & Konseling Siswa</small>
            </div>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item"><a class="nav-link" href="#tentang">Tentang EWS</a></li>
                <li class="nav-item"><a class="nav-link" href="#dimensi">4 Dimensi Skrining</a></li>
                <li class="nav-item"><a class="nav-link" href="#simulasi">Simulasi Cepat</a></li>
                <li class="nav-item"><a class="nav-link" href="#faq">Tanya Jawab (FAQ)</a></li>
                <?php if (is_logged_in()): ?>
                    <?php
                        $dash_url = match($user['role']) {
                            'admin'   => BASE_URL . 'admin/index.php',
                            'guru_bk' => BASE_URL . 'guru/index.php',
                            default   => BASE_URL . 'siswa/index.php'
                        };
                    ?>
                    <li class="nav-item ms-lg-2">
                        <a href="<?= $dash_url ?>" class="btn btn-warning text-dark btn-sm px-3 py-2 fw-bold shadow-sm">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard Saya (<?= htmlspecialchars($user['nama_lengkap']) ?>)
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-lg-2">
                        <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-outline-light btn-sm px-3 py-2 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk / Login
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<header class="hero-section text-center position-relative">
    <div class="hero-pattern"></div>
    <div class="glow-circle"></div>
    <div class="container position-relative" style="z-index: 2;">
        <div class="hero-badge mb-3">
            <i class="bi bi-stars text-warning"></i>
            <span>Early Warning System &bull; SMA Negeri 5 Maluku Barat Daya</span>
        </div>
        <h1 class="display-4 fw-bold mb-3 text-white tracking-tight">
            Sistem Deteksi Dini & Konseling Digital
        </h1>
        <p class="lead mb-4 mx-auto text-light opacity-90" style="max-width: 800px; font-weight: 400;">
            Wadah bimbingan konseling yang ramah dan aman bagi siswa SMA Negeri 5 MBD untuk mendeteksi sedini mungkin kendala belajar akademik, meredakan tekanan psikososial, dan mencegah perundungan (*bullying*) secara terstruktur dan terjaga kerahasiaannya.
        </p>

        <div class="d-flex flex-wrap justify-content-center gap-3 mb-5">
            <?php if (is_logged_in()): ?>
                <a href="<?= $dash_url ?>" class="btn btn-warning text-dark btn-lg px-4 py-3 fw-bold shadow-lg rounded-pill">
                    <i class="bi bi-grid-fill me-2"></i> Buka Dashboard Saya
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-warning text-dark btn-lg px-4 py-3 fw-bold shadow-lg rounded-pill">
                    <i class="bi bi-pencil-square me-2"></i> Mulai Skrining Deteksi Dini
                </a>
                <a href="#simulasi" class="btn btn-outline-light btn-lg px-4 py-3 fw-semibold rounded-pill">
                    <i class="bi bi-play-circle me-2"></i> Coba Simulasi Cepat
                </a>
            <?php endif; ?>
        </div>

        <!-- Quick Access Shortcut Chips -->
        <div class="pt-3 border-top border-white border-opacity-10 d-flex flex-wrap justify-content-center align-items-center gap-3 small text-white-50">
            <span><i class="bi bi-lock-fill text-success me-1"></i> 100% Rahasia</span>
            <span>&bull;</span>
            <span><i class="bi bi-clock-history text-info me-1"></i> Hasil Real-Time</span>
            <span>&bull;</span>
            <span><i class="bi bi-chat-heart-fill text-warning me-1"></i> Didampingi Guru BK Berpengalaman</span>
        </div>
    </div>
</header>

<!-- Counter Statistics Bar -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="row g-4">
            <div class="col-6 col-lg-3">
                <div class="stat-counter-card">
                    <div class="text-primary mb-2 fs-2"><i class="bi bi-people-fill"></i></div>
                    <div class="display-6 fw-bold text-dark"><?= $total_siswa_db ?></div>
                    <div class="text-muted small fw-semibold">Siswa Terpantau Proaktif</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-counter-card">
                    <div class="text-success mb-2 fs-2"><i class="bi bi-clipboard2-check-fill"></i></div>
                    <div class="display-6 fw-bold text-dark"><?= $total_soal_db ?></div>
                    <div class="text-muted small fw-semibold">Indikator Soal Terstandar</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-counter-card">
                    <div class="text-warning mb-2 fs-2"><i class="bi bi-shield-check"></i></div>
                    <div class="display-6 fw-bold text-dark"><?= $total_asesmen_db ?></div>
                    <div class="text-muted small fw-semibold">Asesmen Terlaksana</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-counter-card">
                    <div class="text-info mb-2 fs-2"><i class="bi bi-calendar2-check-fill"></i></div>
                    <div class="display-6 fw-bold text-dark"><?= $total_konseling_db ?></div>
                    <div class="text-muted small fw-semibold">Sesi Konseling Selesai</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Tentang & Paradigma Section -->
<section id="tentang" class="py-5 bg-light">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-bold mb-2">
                    <i class="bi bi-lightbulb-fill me-1"></i> Paradigma Preventif-Proaktif
                </span>
                <h2 class="fw-bold mt-2 mb-3">Mengapa Deteksi Dini Sangat Krusial di SMA Negeri 5 MBD?</h2>
                <p class="text-secondary leading-relaxed">
                    Banyak siswa merasa canggung, takut dihakimi, atau ragu untuk bercerita secara langsung kepada Guru BK ketika mengalami kendala belajar maupun tekanan sosial. Akibatnya, masalah baru terungkap saat prestasi sudah merosot tajam atau saat krisis emosional terjadi.
                </p>
                <p class="text-secondary leading-relaxed">
                    Sistem ini hadir menjembatani komunikasi tersebut: melalui asesmen digital terstruktur, sistem secara otomatis menghitung skor indikator dan memberikan <strong>rekomendasi pendampingan konseling</strong> sejak tahap awal, sehingga penanganan dapat diberikan dengan cepat, nyaman, dan tepat sasaran.
                </p>

                <div class="card border-0 bg-white p-3 rounded-4 shadow-sm mt-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-success-subtle text-success p-3 rounded-circle fs-4">
                            <i class="bi bi-quote"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-dark fw-medium small fst-italic">
                                "Setiap anak berhak mendapatkan lingkungan belajar yang aman, bebas dari perundungan, dan didukung penuh dalam meraih potensi masa depannya."
                            </p>
                            <small class="text-muted fw-bold">&mdash; Tim Bimbingan Konseling SMA Negeri 5 Maluku Barat Daya</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Interaktif 4 Dimensi -->
            <div class="col-lg-6" id="dimensi">
                <div class="card card-base p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-layers-fill text-primary me-2"></i>Jelajahi 4 Dimensi Indikator Skrining</h5>
                    
                    <ul class="nav nav-pills dimension-nav flex-column gap-2 mb-3" id="dimensiTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active w-100 text-start" data-bs-toggle="pill" data-bs-target="#tab-akademik" type="button">
                                <i class="bi bi-mortarboard-fill text-primary fs-5"></i>
                                <div>
                                    <div class="fw-bold">1. Dimensi Permasalahan Akademik</div>
                                    <small class="text-muted"><?= $count_akademik ?> Butir &bull; Kesulitan materi, motivasi belajar & tugas</small>
                                </div>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link w-100 text-start" data-bs-toggle="pill" data-bs-target="#tab-stres" type="button">
                                <i class="bi bi-heart-pulse-fill text-warning fs-5"></i>
                                <div>
                                    <div class="fw-bold">2. Dimensi Kecemasan & Stres Emosional</div>
                                    <small class="text-muted"><?= $count_stres ?> Butir &bull; Kepanikan, ketegangan emosi & pola tidur</small>
                                </div>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link w-100 text-start" data-bs-toggle="pill" data-bs-target="#tab-sosial" type="button">
                                <i class="bi bi-people-fill text-info fs-5"></i>
                                <div>
                                    <div class="fw-bold">3. Dimensi Hubungan Sosial Teman Sebaya</div>
                                    <small class="text-muted"><?= $count_sosial ?> Butir &bull; Keterasingan, minder, dan adaptasi sosial</small>
                                </div>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link w-100 text-start" data-bs-toggle="pill" data-bs-target="#tab-bullying" type="button">
                                <i class="bi bi-shield-exclamation text-danger fs-5"></i>
                                <div>
                                    <div class="fw-bold">4. Perlindungan dari Perundungan (Bullying)</div>
                                    <small class="text-muted"><?= $count_bullying ?> Butir &bull; Ejekan verbal, intimidasi fisik & siber</small>
                                </div>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content bg-light p-3 rounded-3" id="dimensiTabContent">
                        <div class="tab-pane fade show active small text-secondary" id="tab-akademik">
                            <strong>Fokus Asesmen:</strong> Mengidentifikasi kendala pemahaman materi kurikulum, kebiasaan menunda tugas (prokrastinasi), keputusasaan terhadap nilai ulangan, dan ketidakpastian rencana karir studi lanjutan.
                        </div>
                        <div class="tab-pane fade small text-secondary" id="tab-stres">
                            <strong>Fokus Asesmen:</strong> Mengetahui gejala somatis kecemasan seperti jantung berdebar tanpa sebab, gangguan pola tidur, kelelahan mental, serta beban ekspektasi lingkungan sekitar.
                        </div>
                        <div class="tab-pane fade small text-secondary" id="tab-sosial">
                            <strong>Fokus Asesmen:</strong> Mendeteksi siswa yang merasa diabaikan saat kerja kelompok, menarik diri di pojok kelas, serta memfasilitasi rekonsiliasi perselisihan antar teman.
                        </div>
                        <div class="tab-pane fade small text-secondary" id="tab-bullying">
                            <strong>Fokus Asesmen:</strong> Menyediakan kanal perlindungan aman untuk korban maupun saksi perundungan nama orang tua, ancaman fisik, maupun penyebaran gosip di media sosial tanpa takut diintimidasi balik.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Interactive Self-Check Simulation Section -->
<section id="simulasi" class="py-5 bg-white">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold mb-2">
                <i class="bi bi-play-circle-fill me-1"></i> Uji Coba Cepat
            </span>
            <h2 class="fw-bold text-dark">Simulasi Singkat Deteksi Dini</h2>
            <p class="text-muted small mx-auto" style="max-width: 600px;">
                Coba jawab 3 pertanyaan simulasi berikut untuk merasakan bagaimana logika Early Warning System (EWS) mengkalkulasi tingkat risiko secara interaktif!
            </p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="interactive-sim-card shadow-sm">
                    <form id="simForm">
                        <div class="mb-3">
                            <label class="fw-bold small text-dark d-block mb-1">
                                1. Apakah kamu sering merasa kewalahan dan menunda pengerjaan tugas sekolah?
                            </label>
                            <div class="d-flex gap-2 flex-wrap">
                                <input type="radio" class="btn-check sim-q" name="sq1" id="sq1_1" value="1" checked>
                                <label class="btn btn-sm btn-outline-secondary" for="sq1_1">Tidak Pernah (1)</label>
                                <input type="radio" class="btn-check sim-q" name="sq1" id="sq1_2" value="2">
                                <label class="btn btn-sm btn-outline-secondary" for="sq1_2">Jarang (2)</label>
                                <input type="radio" class="btn-check sim-q" name="sq1" id="sq1_3" value="3">
                                <label class="btn btn-sm btn-outline-secondary" for="sq1_3">Sering (3)</label>
                                <input type="radio" class="btn-check sim-q" name="sq1" id="sq1_4" value="4">
                                <label class="btn btn-sm btn-outline-secondary" for="sq1_4">Selalu (4)</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold small text-dark d-block mb-1">
                                2. Apakah kamu merasa cemas atau sulit tidur karena memikirkan beban pikiran?
                            </label>
                            <div class="d-flex gap-2 flex-wrap">
                                <input type="radio" class="btn-check sim-q" name="sq2" id="sq2_1" value="1" checked>
                                <label class="btn btn-sm btn-outline-secondary" for="sq2_1">Tidak Pernah (1)</label>
                                <input type="radio" class="btn-check sim-q" name="sq2" id="sq2_2" value="2">
                                <label class="btn btn-sm btn-outline-secondary" for="sq2_2">Jarang (2)</label>
                                <input type="radio" class="btn-check sim-q" name="sq2" id="sq2_3" value="3">
                                <label class="btn btn-sm btn-outline-secondary" for="sq2_3">Sering (3)</label>
                                <input type="radio" class="btn-check sim-q" name="sq2" id="sq2_4" value="4">
                                <label class="btn btn-sm btn-outline-secondary" for="sq2_4">Selalu (4)</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold small text-dark d-block mb-1">
                                3. Apakah kamu pernah mengalami ejekan atau perlakuan kasar dari teman di sekolah?
                            </label>
                            <div class="d-flex gap-2 flex-wrap">
                                <input type="radio" class="btn-check sim-q" name="sq3" id="sq3_1" value="1" checked>
                                <label class="btn btn-sm btn-outline-secondary" for="sq3_1">Tidak Pernah (1)</label>
                                <input type="radio" class="btn-check sim-q" name="sq3" id="sq3_2" value="2">
                                <label class="btn btn-sm btn-outline-secondary" for="sq3_2">Jarang (2)</label>
                                <input type="radio" class="btn-check sim-q" name="sq3" id="sq3_3" value="3">
                                <label class="btn btn-sm btn-outline-secondary" for="sq3_3">Sering (3)</label>
                                <input type="radio" class="btn-check sim-q" name="sq3" id="sq3_4" value="4">
                                <label class="btn btn-sm btn-outline-secondary" for="sq3_4">Selalu (4)</label>
                            </div>
                        </div>

                        <div class="p-3 bg-white rounded-3 border mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-dark small"><i class="bi bi-cpu-fill text-primary me-1"></i>Hasil Simulasi EWS Instan:</span>
                                <span id="simRiskBadge" class="badge bg-success">Risiko Rendah (Normal)</span>
                            </div>
                            <p id="simRiskFeedback" class="small text-muted mb-0">
                                Skor Anda berada pada batas wajar. Anda dapat berkonsultasi secara sukarela kapan saja jika membutuhkan teman bicara.
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section id="faq" class="py-5 bg-light">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-bold mb-2">
                <i class="bi bi-question-circle-fill me-1"></i> Bantuan & Keamanan
            </span>
            <h2 class="fw-bold text-dark">Pertanyaan yang Sering Diajukan (FAQ)</h2>
            <p class="text-muted small mx-auto" style="max-width: 600px;">
                Semua hal yang perlu kamu ketahui tentang privasi data dan jalannya bimbingan konseling di SMAN 5 MBD.
            </p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion accordion-flush faq-accordion" id="faqAccordion">
                    <div class="accordion-item mb-3 border rounded-3 bg-white shadow-sm overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Apakah teman atau guru mata pelajaran lain bisa melihat jawaban kuesioner saya?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body small text-secondary pt-0">
                                <strong>Tidak bisa.</strong> Sistem konseling dirancang dengan hak akses terisolasi. Jawaban dan rekam catatan konseling hanya dapat diakses oleh Guru BK yang bersumpah memegang asas kerahasiaan bimbingan konseling.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item mb-3 border rounded-3 bg-white shadow-sm overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Apakah pengisian deteksi dini mempengaruhi nilai rapor saya?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body small text-secondary pt-0">
                                <strong>Sama sekali tidak.</strong> Asesmen ini bertujuan untuk memetakan kebutuhan bantuan dan pendampingan psikososial, bukan sebagai ujian akademis yang bernilai angka rapor. Jawablah dengan jujur dan santai.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item mb-3 border rounded-3 bg-white shadow-sm overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Bagaimana jika hasil deteksi dini saya terindikasi Risiko Tinggi?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body small text-secondary pt-0">
                                Kamu tidak perlu takut atau cemas! Sistem akan secara otomatis mengarahkanmu untuk memilih jadwal temu dengan Guru BK. Guru BK akan mendengarkan keluhanmu secara ramah, mencari solusi bersama, dan membantu menyelesaikan hambatan yang kamu rasakan.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item mb-3 border rounded-3 bg-white shadow-sm overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Apakah saya boleh mengajukan konseling jika hanya ingin konsultasi karir/kuliah?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body small text-secondary pt-0">
                                <strong>Tentu saja!</strong> Layanan konseling terbuka bagi seluruh siswa, termasuk bimbingan pemilihan jurusan kuliah, minat bakat, pengembangan potensi diri, maupun beasiswa setelah lulus SMA.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white py-5">
    <div class="container text-center">
        <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle mb-3" style="width: 48px; height: 48px;">
            <i class="bi bi-shield-heart-fill fs-4"></i>
        </div>
        <h5 class="fw-bold mb-1">SMA NEGERI 5 MALUKU BARAT DAYA</h5>
        <p class="text-white-50 small mb-4">
            Jl. Trans Pulau Moa, Tiakur, Kabupaten Maluku Barat Daya, Provinsi Maluku &bull; Kode Pos 97442
        </p>
        <hr class="border-secondary my-4">
        <p class="text-white-50 small mb-0">
            Penelitian Skripsi: <strong>Agatha Natalia Laipeny (NPM: 12155201220165)</strong> &bull; Program Studi Informatika UKIM Ambon (2026)
        </p>
    </div>
</footer>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Interaktif Self-Check Simulasi
document.addEventListener('DOMContentLoaded', function() {
    const simRadios = document.querySelectorAll('.sim-q');
    const simRiskBadge = document.getElementById('simRiskBadge');
    const simRiskFeedback = document.getElementById('simRiskFeedback');

    function calculateSim() {
        let score = 0;
        let bullyingVal = 1;

        const q1 = document.querySelector('input[name="sq1"]:checked');
        const q2 = document.querySelector('input[name="sq2"]:checked');
        const q3 = document.querySelector('input[name="sq3"]:checked');

        if (q1) score += parseInt(q1.value);
        if (q2) score += parseInt(q2.value);
        if (q3) {
            score += parseInt(q3.value);
            bullyingVal = parseInt(q3.value);
        }

        if (score >= 9 || bullyingVal >= 3) {
            simRiskBadge.className = 'badge bg-danger';
            simRiskBadge.textContent = 'Risiko Tinggi (Perlu Konseling Segera)';
            simRiskFeedback.innerHTML = '<span class="text-danger fw-bold"><i class="bi bi-exclamation-octagon me-1"></i>Peringatan EWS:</span> Terdeteksi indikasi beban pikiran atau tekanan perundungan yang cukup berat. Sangat disarankan berkonsultasi tatap muka dengan Guru BK.';
        } else if (score >= 6) {
            simRiskBadge.className = 'badge bg-warning text-dark';
            simRiskBadge.textContent = 'Risiko Sedang (Perhatian)';
            simRiskFeedback.innerHTML = '<span class="text-warning-emphasis fw-bold"><i class="bi bi-exclamation-triangle me-1"></i>Perhatian:</span> Ada beberapa hambatan belajar atau emosi yang mulai mengganggu rutinitasmu. Bimbingan konseling akan sangat membantumu.';
        } else {
            simRiskBadge.className = 'badge bg-success';
            simRiskBadge.textContent = 'Risiko Rendah (Normal/Aman)';
            simRiskFeedback.innerHTML = '<span class="text-success fw-bold"><i class="bi bi-check-circle me-1"></i>Kondisi Adaptif:</span> Skor Anda berada pada batas wajar. Anda tetap dapat memanfaatkan layanan konseling secara sukarela.';
        }
    }

    simRadios.forEach(r => r.addEventListener('change', calculateSim));
});
</script>
</body>
</html>
