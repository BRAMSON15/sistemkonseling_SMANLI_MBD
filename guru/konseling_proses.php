<?php
/**
 * Pelaksanaan Konseling & Pencatatan Rekam Medis BK
 * Guru BK - SMA Negeri 5 Maluku Barat Daya
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

require_role('guru_bk');
$user = auth_user();
$guru_id = $user['role_id'];
$page_title = 'Pelaksanaan & Pencatatan Konseling';

$jadwal_id = isset($_GET['jadwal_id']) ? (int) $_GET['jadwal_id'] : 0;

if ($jadwal_id <= 0) {
    set_flash('danger', 'Jadwal konseling tidak valid.');
    header('Location: ' . BASE_URL . 'guru/jadwal.php');
    exit;
}

// Ambil data jadwal & siswa
$stmt = $pdo->prepare("
    SELECT j.*, p.jenis_konseling, p.alasan_pengajuan, p.is_wajib, p.skrining_id,
           sw.id AS siswa_id, sw.nisn, sw.jenis_kelamin, sw.nama_wali, sw.kontak_wali,
           u.id AS user_id_siswa, u.nama_lengkap AS nama_siswa, u.no_hp AS kontak_siswa,
           k.nama_kelas, k.jurusan
    FROM jadwal_konseling j
    JOIN pengajuan_konseling p ON j.pengajuan_id = p.id
    JOIN siswa sw ON p.siswa_id = sw.id
    JOIN users u ON sw.user_id = u.id
    LEFT JOIN kelas k ON sw.kelas_id = k.id
    WHERE j.id = ?
");
$stmt->execute([$jadwal_id]);
$jadwal = $stmt->fetch();

if (!$jadwal) {
    set_flash('danger', 'Data jadwal konseling tidak ditemukan.');
    header('Location: ' . BASE_URL . 'guru/jadwal.php');
    exit;
}

// Ambil data deteksi dini terkait (jika ada)
$skrining_terkait = null;
if (!empty($jadwal['skrining_id'])) {
    $stmt_sk = $pdo->prepare("SELECT * FROM skrining_asesmen WHERE id = ?");
    $stmt_sk->execute([$jadwal['skrining_id']]);
    $skrining_terkait = $stmt_sk->fetch();
}

$errors = [];

// Proses Simpan Rekam Konseling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal_sesi   = clean($_POST['tanggal_sesi'] ?? date('Y-m-d'));
    $keluhan_utama  = clean($_POST['keluhan_utama'] ?? '');
    $evaluasi_bk    = clean($_POST['hasil_evaluasi_bk'] ?? '');
    $intervensi     = clean($_POST['tindakan_intervensi'] ?? '');
    $rekomendasi    = clean($_POST['rekomendasi_tindak_lanjut'] ?? '');
    $status_kasus   = clean($_POST['status_perkembangan'] ?? 'Kasus Selesai');

    if (empty($keluhan_utama) || empty($evaluasi_bk) || empty($intervensi) || empty($rekomendasi)) {
        $errors[] = 'Seluruh kolom rekam evaluasi dan intervensi wajib diisi.';
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Simpan Catatan Konseling
            $stmt_ins = $pdo->prepare("
                INSERT INTO catatan_konseling 
                (jadwal_id, siswa_id, guru_id, tanggal_sesi, keluhan_utama, hasil_evaluasi_bk, tindakan_intervensi, rekomendasi_tindak_lanjut, status_perkembangan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt_ins->execute([
                $jadwal_id,
                $jadwal['siswa_id'],
                $guru_id,
                $tanggal_sesi,
                $keluhan_utama,
                $evaluasi_bk,
                $intervensi,
                $rekomendasi,
                $status_kasus
            ]);

            // 2. Update status jadwal ke 'Selesai'
            $stmt_uj = $pdo->prepare("UPDATE jadwal_konseling SET status = 'Selesai' WHERE id = ?");
            $stmt_uj->execute([$jadwal_id]);

            // 3. Update status pengajuan ke 'Selesai'
            $stmt_up = $pdo->prepare("UPDATE pengajuan_konseling SET status = 'Selesai' WHERE id = ?");
            $stmt_up->execute([$jadwal['pengajuan_id']]);

            // 4. Update status tindak lanjut skrining ke 'Selesai' jika ada
            if (!empty($jadwal['skrining_id'])) {
                $stmt_us = $pdo->prepare("UPDATE skrining_asesmen SET status_tindak_lanjut = 'Selesai' WHERE id = ?");
                $stmt_us->execute([$jadwal['skrining_id']]);
            }

            // 5. Kirim notifikasi ke siswa
            kirim_notifikasi(
                $pdo,
                $jadwal['user_id_siswa'],
                'Hasil Rekam Konseling Selesai',
                'Guru BK telah menyelesaikan pencatatan sesi konseling Anda. Silakan lihat rekomendasi tindak lanjut di menu riwayat Anda.',
                'siswa/riwayat_siswa.php'
            );

            $pdo->commit();
            set_flash('success', 'Rekam medis konseling berhasil disimpan dan kasus telah ditindaklanjuti.');
            header('Location: ' . BASE_URL . 'guru/riwayat_konseling.php');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Gagal menyimpan rekam konseling: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/guru_header.php';
?>

<!-- PAGE HEADER -->
<div class="page-header">
  <div class="page-header-title">
    <div class="page-header-icon">✍</div>
    <div>
      <h1>Pelaksanaan &amp; Rekam Medis Konseling</h1>
      <p>Catat lembar evaluasi klinis bimbingan konseling, fakta keluhan siswa, intervensi psikologis, dan arahan tindak lanjut.</p>
    </div>
  </div>
  <div class="page-header-actions">
    <a href="<?= BASE_URL ?>guru/jadwal.php" class="btn btn-outline-secondary">
      ← Kembali ke Jadwal
    </a>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash-wrap flash-danger" style="margin-bottom:18px;">
    <div>
      <strong>Perhatian:</strong>
      <ul style="margin:4px 0 0 16px; font-size:11px;">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
<?php endif; ?>

<div style="display:grid; grid-template-columns: minmax(280px, 1fr) minmax(0, 2fr); gap:20px; align-items:flex-start;">
  <!-- KOLOM KIRI: IDENTITAS SISWA & INDIKATOR EWS -->
  <div>
    <!-- Kartu Profil Siswa -->
    <div class="card-base" style="padding:22px; margin-bottom:18px;">
      <div style="text-align:center; padding-bottom:16px; border-bottom:1px solid #edf2f9; margin-bottom:14px;">
        <div class="student-avatar" style="width:64px; height:64px; font-size:24px; margin:0 auto 10px; background:#e1edff; color:#1874ef;">
          <?= strtoupper(substr($jadwal['nama_siswa'], 0, 1)) ?>
        </div>
        <strong style="font-size:15px; color:var(--navy); display:block;"><?= htmlspecialchars($jadwal['nama_siswa']) ?></strong>
        <span style="font-size:11px; color:var(--muted);">NISN: <?= htmlspecialchars($jadwal['nisn']) ?></span>
      </div>

      <div style="display:flex; flex-direction:column; gap:8px; font-size:11px;">
        <div style="display:flex; justify-content:space-between;">
          <span style="color:var(--muted);">Kelas / Jurusan:</span>
          <b><?= htmlspecialchars($jadwal['nama_kelas'] ?? '-') ?></b>
        </div>
        <div style="display:flex; justify-content:space-between;">
          <span style="color:var(--muted);">Jenis Kelamin:</span>
          <b><?= $jadwal['jenis_kelamin'] === 'L' ? 'Laki-Laki' : 'Perempuan' ?></b>
        </div>
        <div style="display:flex; justify-content:space-between;">
          <span style="color:var(--muted);">No. Kontak Siswa:</span>
          <b><?= htmlspecialchars($jadwal['kontak_siswa'] ?? '-') ?></b>
        </div>
        <div style="display:flex; justify-content:space-between;">
          <span style="color:var(--muted);">Wali Siswa:</span>
          <b><?= htmlspecialchars($jadwal['nama_wali'] ?? 'Orang Tua') ?></b>
        </div>
        <div style="display:flex; justify-content:space-between;">
          <span style="color:var(--muted);">No. Kontak Wali:</span>
          <b><?= htmlspecialchars($jadwal['kontak_wali'] ?? '-') ?></b>
        </div>
      </div>
    </div>

    <!-- Kartu Indikator Deteksi Dini EWS -->
    <?php if ($skrining_terkait): ?>
      <div class="card-base" style="padding:20px; border-left:4px solid #ef4444;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
          <strong style="font-size:12px; color:var(--navy);">⌁ Indikator EWS Terkait</strong>
          <?php if ($skrining_terkait['tingkat_risiko'] === 'Tinggi'): ?>
            <span class="badge-risiko-tinggi">Risiko Tinggi</span>
          <?php elseif ($skrining_terkait['tingkat_risiko'] === 'Sedang'): ?>
            <span class="badge-risiko-sedang">Risiko Sedang</span>
          <?php else: ?>
            <span class="badge-risiko-rendah">Risiko Rendah</span>
          <?php endif; ?>
        </div>
        
        <div style="display:flex; flex-direction:column; gap:6px; font-size:11px; margin-bottom:12px;">
          <div style="display:flex; justify-content:space-between;">
            <span>Akademik:</span>
            <b><?= $skrining_terkait['skor_akademik'] ?> / 40</b>
          </div>
          <div style="display:flex; justify-content:space-between;">
            <span>Psikososial:</span>
            <b><?= $skrining_terkait['skor_psikososial'] ?> / 40</b>
          </div>
          <div style="display:flex; justify-content:space-between;">
            <span>Bullying:</span>
            <b style="color:<?= $skrining_terkait['skor_bullying'] >= 10 ? '#dc2626' : 'inherit' ?>;"><?= $skrining_terkait['skor_bullying'] ?> / 20</b>
          </div>
          <div style="display:flex; justify-content:space-between; font-weight:700; color:var(--navy); padding-top:4px; border-top:1px dashed #e2eaf4;">
            <span>Total Skor:</span>
            <span><?= $skrining_terkait['total_skor'] ?> / 100</span>
          </div>
        </div>

        <div style="background:#f8fafc; padding:10px; border-radius:6px; font-size:10px; line-height:1.5; color:#576982; font-style:italic;">
          <?= htmlspecialchars(substr($skrining_terkait['catatan_sistem'], 0, 150)) ?>...
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- KOLOM KANAN: FORM LEMBAR REKAM LAYANAN KONSELING -->
  <div class="card-base" style="padding:26px;">
    <div style="padding-bottom:14px; border-bottom:1px solid #edf2f9; margin-bottom:18px;">
      <h2 style="font-size:16px; font-weight:700; color:var(--navy); margin-bottom:4px;">Lembar Rekam Medis Bimbingan Konseling</h2>
      <p style="font-size:11px; color:var(--muted); margin:0;">Isi dokumentasi hasil pembicaraan mendalam, hasil observasi konselor, intervensi, serta komitmen siswa.</p>
    </div>

    <form method="POST" action="">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label">Tanggal Pelaksanaan Sesi <span style="color:#ef4444;">*</span></label>
          <input type="date" name="tanggal_sesi" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label">Jenis Layanan Konseling</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($jadwal['jenis_konseling']) ?>" readonly style="background:#f8fafc; font-weight:600; color:var(--blue);">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Topik Awal Permasalahan yang Diajukan Siswa</label>
        <div style="background:#f8fafc; border:1px solid #e2eaf4; padding:10px 14px; border-radius:8px; font-size:11px; line-height:1.5; color:#475569;">
          <?= nl2br(htmlspecialchars($jadwal['alasan_pengajuan'])) ?>
        </div>
      </div>

      <!-- Keluhan Utama & Fakta Pembicaraan -->
      <div class="form-group">
        <label for="keluhan_utama" class="form-label">1. Keluhan Utama &amp; Fakta Permasalahan Terungkap <span style="color:#ef4444;">*</span></label>
        <textarea name="keluhan_utama" id="keluhan_utama" rows="3" class="form-control" placeholder="Uraikan apa yang dirasakan siswa, kendala belajar, dinamika keluarga, atau relasi teman sebaya yang terungkap saat konseling..." required></textarea>
      </div>

      <!-- Diagnosis / Evaluasi Guru BK -->
      <div class="form-group">
        <label for="hasil_evaluasi_bk" class="form-label">2. Diagnosis &amp; Analisis Guru BK <span style="color:#ef4444;">*</span></label>
        <textarea name="hasil_evaluasi_bk" id="hasil_evaluasi_bk" rows="3" class="form-control" placeholder="Contoh: Siswa mengalami stres akademik akibat prokrastinasi tugas yang diperburuk oleh rasa terasing dari kelompok belajar di kelas..." required></textarea>
      </div>

      <!-- Tindakan Intervensi yang Diberikan -->
      <div class="form-group">
        <label for="tindakan_intervensi" class="form-label">3. Tindakan Intervensi Konseling yang Dilakukan <span style="color:#ef4444;">*</span></label>
        <textarea name="tindakan_intervensi" id="tindakan_intervensi" rows="3" class="form-control" placeholder="Contoh: Pemberian konseling individual dengan teknik restrukturisasi kognitif, penyusunan jadwal belajar harian mandiri, dan latihan relaksasi pernapasan..." required></textarea>
      </div>

      <!-- Rekomendasi Tindak Lanjut untuk Siswa -->
      <div class="form-group">
        <label for="rekomendasi_tindak_lanjut" class="form-label">4. Rekomendasi Tindak Lanjut (Ditampilkan ke Akun Siswa) <span style="color:#ef4444;">*</span></label>
        <textarea name="rekomendasi_tindak_lanjut" id="rekomendasi_tindak_lanjut" rows="3" class="form-control" placeholder="Tuliskan arahan tugas praktis atau kebiasaan positif yang harus dijalankan siswa setelah sesi ini..." required></textarea>
      </div>

      <!-- Status Kasus -->
      <div class="form-group" style="margin-bottom:20px;">
        <label for="status_perkembangan" class="form-label">5. Status Evaluasi Kasus Siswa <span style="color:#ef4444;">*</span></label>
        <select name="status_perkembangan" id="status_perkembangan" class="form-select" required>
          <option value="Kasus Selesai" selected>Kasus Selesai (Siswa adaptif, komitmen terbentuk &amp; target konseling tercapai)</option>
          <option value="Perlu Sesi Lanjutan">Perlu Sesi Lanjutan (Memerlukan pemantauan berkala konseling berikutnya)</option>
          <option value="Rujukan Khusus/Wali">Rujukan Khusus / Panggilan Orang Tua / Wali Kelas</option>
        </select>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:10px; padding-top:16px; border-top:1px solid #edf2f9;">
        <a href="<?= BASE_URL ?>guru/jadwal.php" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary" style="padding:9px 24px;">
          <span>💾</span> Simpan Lembar Rekam Medis
        </button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/guru_footer.php'; ?>
