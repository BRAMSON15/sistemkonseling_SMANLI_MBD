<?php
/**
 * Formulir Deteksi Dini Permasalahan Akademik & Psikososial
 * Siswa - SMA Negeri 5 Maluku Barat Daya
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

require_role('siswa');
$user = auth_user();
$siswa_id = $user['role_id'];
$page_title = 'Pengisian Asesmen Deteksi Dini';

// Ambil kuesioner aktif
$stmt_k = $pdo->prepare("SELECT * FROM kuesioner WHERE is_active = 1 LIMIT 1");
$stmt_k->execute();
$kuesioner = $stmt_k->fetch();

if (!$kuesioner) {
    set_flash('danger', 'Instrumen kuesioner saat ini belum diaktifkan oleh administrator.');
    header('Location: ' . BASE_URL . 'siswa/index.php');
    exit;
}

// Ambil butir pertanyaan aktif
$stmt_q = $pdo->prepare("
    SELECT * FROM pertanyaan 
    WHERE kuesioner_id = ? AND is_active = 1 
    ORDER BY nomor_urut ASC
");
$stmt_q->execute([$kuesioner['id']]);
$pertanyaan_list = $stmt_q->fetchAll();

// Ambil opsi jawaban
$stmt_opsi = $pdo->query("SELECT * FROM opsi_jawaban ORDER BY urutan ASC");
$opsi_list = $stmt_opsi->fetchAll();

$errors = [];

// Proses Submit Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jawaban_input = $_POST['jawaban'] ?? [];

    // Validasi kelengkapan jawaban
    if (count($jawaban_input) < count($pertanyaan_list)) {
        $errors[] = 'Harap jawab seluruh pertanyaan yang tersedia (' . count($pertanyaan_list) . ' butir).';
    } else {
        $skor_akademik = 0;
        $skor_psiko_dasar = 0;
        $skor_bullying = 0;
        $total_skor = 0;

        // Siapkan map pertanyaan untuk klasifikasi kategori
        $q_map = [];
        foreach ($pertanyaan_list as $p) {
            $q_map[$p['id']] = $p['kategori'];
        }

        // Map opsi untuk dapatkan bobot nilai
        $opsi_map = [];
        foreach ($opsi_list as $o) {
            $opsi_map[$o['id']] = (int) $o['bobot'];
        }

        // Hitung total nilai skor
        foreach ($jawaban_input as $p_id => $o_id) {
            $p_id = (int) $p_id;
            $o_id = (int) $o_id;
            $bobot = $opsi_map[$o_id] ?? 1;
            $total_skor += $bobot;

            $kat = $q_map[$p_id] ?? '';
            if ($kat === 'akademik') {
                $skor_akademik += $bobot;
            } elseif ($kat === 'perundungan_bullying') {
                $skor_bullying += $bobot;
            } else {
                // kecemasan_stres dan sosial_relasi
                $skor_psiko_dasar += $bobot;
            }
        }

        // Jalankan algoritma evaluasi risiko
        $evaluasi = hitung_evaluasi_risiko($skor_akademik, $skor_psiko_dasar, $skor_bullying, $total_skor);

        try {
            $pdo->beginTransaction();

            // Simpan ke tabel skrining_asesmen
            $stmt_ins = $pdo->prepare("
                INSERT INTO skrining_asesmen 
                (siswa_id, kuesioner_id, skor_akademik, skor_psikososial, skor_bullying, total_skor, persentase_skor, tingkat_risiko, status_rekomendasi, catatan_sistem)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt_ins->execute([
                $siswa_id,
                $kuesioner['id'],
                $skor_akademik,
                $skor_psiko_dasar,
                $skor_bullying,
                $evaluasi['total_skor'],
                $evaluasi['persentase'],
                $evaluasi['tingkat_risiko'],
                $evaluasi['status_rekomendasi'],
                $evaluasi['catatan_sistem']
            ]);

            $skrining_id = $pdo->lastInsertId();

            // Simpan rincian per butir jawaban
            $stmt_det = $pdo->prepare("
                INSERT INTO skrining_jawaban (skrining_id, pertanyaan_id, opsi_id, skor)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($jawaban_input as $p_id => $o_id) {
                $bobot = $opsi_map[(int)$o_id] ?? 1;
                $stmt_det->execute([$skrining_id, (int)$p_id, (int)$o_id, $bobot]);
            }

            // Jika terdeteksi risiko tinggi, kirimkan notifikasi alarm ke semua Guru BK
            if ($evaluasi['tingkat_risiko'] === 'Tinggi') {
                $stmt_g = $pdo->query("SELECT user_id FROM guru_bk");
                while ($g = $stmt_g->fetch()) {
                    kirim_notifikasi(
                        $pdo,
                        $g['user_id'],
                        'Peringatan Deteksi Dini: Risiko Tinggi',
                        'Siswa ' . $user['nama_lengkap'] . ' (' . ($user['nama_kelas'] ?? 'Siswa') . ') terdeteksi berada pada TINGKAT RISIKO TINGGI. Segera lakukan peninjauan.',
                        'guru/skrining.php'
                    );
                }
            }

            $pdo->commit();

            set_flash('success', 'Asesmen deteksi dini berhasil disimpan dan dianalisis oleh sistem.');
            header('Location: ' . BASE_URL . 'siswa/hasil_deteksi.php?id=' . $skrining_id);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Gagal menyimpan hasil kuesioner: ' . $e->getMessage();
        }
    }
}

// Kelompokkan pertanyaan berdasarkan dimensi
$dimensi_kelompok = [
    'akademik' => [
        'title' => 'Dimensi 1: Permasalahan Akademik & Kebiasaan Belajar',
        'desc'  => 'Pertanyaan mengenai proses pemahaman materi, motivasi belajar, dan ketepatan penyelesaian tugas sekolah.',
        'icon'  => 'bi-mortarboard-fill',
        'items' => []
    ],
    'kecemasan_stres' => [
        'title' => 'Dimensi 2: Kondisi Kecemasan & Stres Emosional',
        'desc'  => 'Pertanyaan mengenai ketegangan pikiran, beban emosional, dan pola tidur dalam kehidupan sehari-hari.',
        'icon'  => 'bi-heart-pulse-fill',
        'items' => []
    ],
    'sosial_relasi' => [
        'title' => 'Dimensi 3: Hubungan Sosial & Interaksi Teman Sebaya',
        'desc'  => 'Pertanyaan mengenai rasa percaya diri, adaptasi pergaulan, serta keterasingan di lingkungan sekolah.',
        'icon'  => 'bi-people-fill',
        'items' => []
    ],
    'perundungan_bullying' => [
        'title' => 'Dimensi 4: Deteksi & Perlindungan dari Perundungan (Bullying)',
        'desc'  => 'Pertanyaan mengenai pengalaman menghadapi ejekan verbal, intimidasi fisik, pengucilan, atau cyberbullying.',
        'icon'  => 'bi-shield-exclamation',
        'items' => []
    ],
];

foreach ($pertanyaan_list as $p) {
    if (isset($dimensi_kelompok[$p['kategori']])) {
        $dimensi_kelompok[$p['kategori']]['items'][] = $p;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="bi bi-clipboard2-pulse-fill text-primary me-2"></i><?= htmlspecialchars($kuesioner['judul']) ?></h4>
            <p class="text-secondary small mb-0">Isilah kuesioner ini secara jujur sesuai dengan pengalaman dan perasaanmu selama 1 bulan terakhir.</p>
        </div>
        <a href="<?= BASE_URL ?>siswa/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger shadow-sm mb-4">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Mohon lengkapi formulir:</div>
        <ul class="mb-0 small ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Sticky Progress Bar Card -->
<div class="card card-base p-3 mb-4 sticky-top bg-white shadow-sm" style="top: 70px; z-index: 10;">
    <div class="d-flex align-items-center justify-content-between small fw-semibold text-secondary mb-2">
        <span><i class="bi bi-check2-circle text-primary me-1"></i> Progres Pengisian:</span>
        <span id="progressText">0 dari <?= count($pertanyaan_list) ?> Terjawab (0%)</span>
    </div>
    <div class="progress" style="height: 10px;">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="progressBar" role="progressbar" style="width: 0%"></div>
    </div>
</div>

<form method="POST" action="" id="formSkrining">
    <?php foreach ($dimensi_kelompok as $kat_key => $dimensi): ?>
        <?php if (!empty($dimensi['items'])): ?>
            <div class="card card-base mb-4">
                <div class="card-header-clean bg-light py-3 px-4 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi <?= $dimensi['icon'] ?> text-primary fs-5"></i>
                        <h6 class="fw-bold text-dark mb-0"><?= $dimensi['title'] ?></h6>
                    </div>
                    <small class="text-muted d-block mt-1"><?= $dimensi['desc'] ?></small>
                </div>
                <div class="p-4">
                    <?php foreach ($dimensi['items'] as $item): ?>
                        <div class="question-item mb-3 p-3 border rounded">
                            <div class="d-flex gap-3 mb-2">
                                <span class="badge bg-primary-subtle text-primary rounded-pill d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem;">
                                    <?= $item['nomor_urut'] ?>
                                </span>
                                <div class="fw-medium text-dark flex-grow-1" style="font-size: 0.95rem;">
                                    <?= htmlspecialchars($item['teks_pertanyaan']) ?>
                                </div>
                            </div>

                            <div class="ms-md-5 pt-2">
                                <div class="row g-2">
                                    <?php foreach ($opsi_list as $opsi): ?>
                                        <div class="col-6 col-md-3">
                                            <input type="radio" 
                                                   class="btn-check q-radio" 
                                                   name="jawaban[<?= $item['id'] ?>]" 
                                                   id="q_<?= $item['id'] ?>_o_<?= $opsi['id'] ?>" 
                                                   value="<?= $opsi['id'] ?>" 
                                                   <?= (isset($_POST['jawaban'][$item['id']]) && $_POST['jawaban'][$item['id']] == $opsi['id']) ? 'checked' : '' ?>
                                                   required>
                                            <label class="btn btn-outline-secondary w-100 py-2 small text-truncate" for="q_<?= $item['id'] ?>_o_<?= $opsi['id'] ?>">
                                                <?= htmlspecialchars($opsi['label']) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="card card-base p-4 mb-5 text-center bg-light">
        <h5 class="fw-bold text-dark mb-2">Konfirmasi Pengiriman Jawaban</h5>
        <p class="text-muted small mx-auto mb-3" style="max-width: 600px;">
            Pastikan seluruh pertanyaan telah dijawab dengan jujur. Setelah dikirim, sistem akan memproses perhitungan skor secara langsung.
        </p>
        <div>
            <button type="submit" class="btn btn-primary btn-lg px-5 py-2 fw-bold shadow">
                <i class="bi bi-send-fill me-1"></i> Kirim & Dapatkan Hasil Analisis
            </button>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const totalQuestions = <?= count($pertanyaan_list) ?>;
    const radios = document.querySelectorAll('.q-radio');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');

    function updateProgress() {
        const answeredQuestions = new Set();
        radios.forEach(r => {
            if (r.checked) {
                answeredQuestions.add(r.name);
            }
        });
        const count = answeredQuestions.size;
        const percent = Math.round((count / totalQuestions) * 100);
        progressBar.style.width = percent + '%';
        progressText.textContent = `${count} dari ${totalQuestions} Terjawab (${percent}%)`;

        if (percent === 100) {
            progressBar.classList.remove('bg-primary');
            progressBar.classList.add('bg-success');
        } else {
            progressBar.classList.remove('bg-success');
            progressBar.classList.add('bg-primary');
        }
    }

    radios.forEach(r => {
        r.addEventListener('change', updateProgress);
    });

    // Jalankan awal jika form pernah terisi
    updateProgress();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
