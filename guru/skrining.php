<?php
/**
 * Monitoring Deteksi Dini Siswa (EWS)
 * Guru BK - SMA Negeri 5 Maluku Barat Daya
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

require_role('guru_bk');
$user = auth_user();
$page_title = 'Monitoring Deteksi Dini Siswa';

// Filter Parameter
$kelas_id = isset($_GET['kelas_id']) && $_GET['kelas_id'] !== '' ? (int)$_GET['kelas_id'] : '';
$risiko   = isset($_GET['risiko']) && in_array($_GET['risiko'], ['Rendah', 'Sedang', 'Tinggi']) ? $_GET['risiko'] : '';
$status   = isset($_GET['status']) && $_GET['status'] !== '' ? clean($_GET['status']) : '';
$search   = isset($_GET['search']) ? clean($_GET['search']) : '';

// Action update status tindak lanjut jika ada POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $skrining_id = (int)($_POST['skrining_id'] ?? 0);
    $new_status  = clean($_POST['status_tindak_lanjut'] ?? 'Belum Ditindaklanjuti');
    if ($skrining_id > 0) {
        $stmt_up = $pdo->prepare("UPDATE skrining_asesmen SET status_tindak_lanjut = ? WHERE id = ?");
        $stmt_up->execute([$new_status, $skrining_id]);
        set_flash('success', 'Status tindak lanjut asesmen berhasil diperbarui.');
        header('Location: ' . BASE_URL . 'guru/skrining.php?' . http_build_query($_GET));
        exit;
    }
}

// Build Query
$where = ["1=1"];
$params = [];

if ($kelas_id !== '') {
    $where[] = "sw.kelas_id = ?";
    $params[] = $kelas_id;
}
if ($risiko !== '') {
    $where[] = "s.tingkat_risiko = ?";
    $params[] = $risiko;
}
if ($status !== '') {
    $where[] = "s.status_tindak_lanjut = ?";
    $params[] = $status;
}
if ($search !== '') {
    $where[] = "(u.nama_lengkap LIKE ? OR sw.nisn LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = implode(" AND ", $where);

$stmt_sk = $pdo->prepare("
    SELECT s.*, sw.id AS siswa_id, sw.nisn, sw.jenis_kelamin, sw.nama_wali, sw.kontak_wali, u.nama_lengkap, u.no_hp, k.nama_kelas, k.jurusan,
           (SELECT COUNT(*) FROM pengajuan_konseling p WHERE p.skrining_id = s.id OR (p.siswa_id = sw.id AND p.status IN ('Menunggu', 'Disetujui'))) AS has_booking
    FROM skrining_asesmen s
    JOIN siswa sw ON s.siswa_id = sw.id
    JOIN users u ON sw.user_id = u.id
    LEFT JOIN kelas k ON sw.kelas_id = k.id
    WHERE $where_clause
    ORDER BY s.tanggal_pengisian DESC
");
$stmt_sk->execute($params);
$skrining_list = $stmt_sk->fetchAll();

// Daftar Master Kelas untuk Filter
$kelas_list = $pdo->query("SELECT * FROM kelas ORDER BY tingkat ASC, nama_kelas ASC")->fetchAll();

include __DIR__ . '/../includes/guru_header.php';
?>

<!-- PAGE HEADER -->
<div class="page-header">
  <div class="page-header-title">
    <div class="page-header-icon">⌁</div>
    <div>
      <h1>Monitoring Deteksi Dini Siswa (EWS)</h1>
      <p>Early Warning System untuk melacak indikator kesulitan belajar, stres psikososial, dan risiko perundungan.</p>
    </div>
  </div>
  <div class="page-header-actions">
    <a href="<?= BASE_URL ?>guru/laporan.php?jenis=skrining" class="btn btn-outline-secondary">
      <span>▣</span> Cetak Rekapitulasi EWS
    </a>
  </div>
</div>

<!-- FILTER CARD -->
<div class="filter-card">
  <form method="GET" action="" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) auto; gap:12px; align-items:flex-end;">
    <div class="form-group" style="margin-bottom:0;">
      <label class="form-label">Cari Siswa / NISN</label>
      <input type="text" name="search" class="form-control form-control-sm" placeholder="Nama atau NISN..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="form-group" style="margin-bottom:0;">
      <label class="form-label">Kelas</label>
      <select name="kelas_id" class="form-select form-select-sm">
        <option value="">-- Semua Kelas --</option>
        <?php foreach ($kelas_list as $k): ?>
          <option value="<?= $k['id'] ?>" <?= $kelas_id === $k['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($k['nama_kelas']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="margin-bottom:0;">
      <label class="form-label">Tingkat Risiko</label>
      <select name="risiko" class="form-select form-select-sm">
        <option value="">-- Semua Risiko --</option>
        <option value="Tinggi" <?= $risiko === 'Tinggi' ? 'selected' : '' ?>>Tinggi (Bahaya)</option>
        <option value="Sedang" <?= $risiko === 'Sedang' ? 'selected' : '' ?>>Sedang (Perhatian)</option>
        <option value="Rendah" <?= $risiko === 'Rendah' ? 'selected' : '' ?>>Rendah (Aman)</option>
      </select>
    </div>
    <div class="form-group" style="margin-bottom:0;">
      <label class="form-label">Status Tindak Lanjut</label>
      <select name="status" class="form-select form-select-sm">
        <option value="">-- Semua Status --</option>
        <option value="Belum Ditindaklanjuti" <?= $status === 'Belum Ditindaklanjuti' ? 'selected' : '' ?>>Belum Ditindaklanjuti</option>
        <option value="Sedang Konseling" <?= $status === 'Sedang Konseling' ? 'selected' : '' ?>>Sedang Konseling</option>
        <option value="Selesai" <?= $status === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
      </select>
    </div>
    <div style="display:flex; gap:6px;">
      <button type="submit" class="btn btn-primary btn-sm" title="Terapkan Filter">
        <span>⚲</span> Filter
      </button>
      <a href="<?= BASE_URL ?>guru/skrining.php" class="btn btn-light btn-sm" title="Reset Filter">
        ↺ Reset
      </a>
    </div>
  </form>
</div>

<!-- TABEL HASIL DETEKSI DINI -->
<div class="panel" style="overflow:hidden;">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:40px;">No</th>
          <th>Tanggal Pengisian</th>
          <th>Nama Siswa &amp; NISN</th>
          <th>Kelas</th>
          <th>Akademik</th>
          <th>Psikososial</th>
          <th>Bullying</th>
          <th>Total Skor</th>
          <th>Tingkat Risiko</th>
          <th>Status Tindak Lanjut</th>
          <th style="text-align:right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($skrining_list)): ?>
          <tr>
            <td colspan="11" style="text-align:center; padding:35px 20px; color:var(--muted);">
              <span style="font-size:32px; display:block; margin-bottom:8px; opacity:0.6;">📭</span>
              Tidak ada data deteksi dini yang sesuai dengan kriteria filter saat ini.
            </td>
          </tr>
        <?php else: ?>
          <?php $no = 1; foreach ($skrining_list as $row): ?>
            <tr style="<?= $row['tingkat_risiko'] === 'Tinggi' ? 'background:#fff8f8;' : '' ?>">
              <td><?= $no++ ?></td>
              <td>
                <div style="font-weight:600; color:var(--text);"><?= tgl_indo($row['tanggal_pengisian']) ?></div>
                <small style="color:var(--muted); font-size:9px;"><?= date('H:i', strtotime($row['tanggal_pengisian'])) ?> WIT</small>
              </td>
              <td>
                <div class="student-cell">
                  <div class="student-avatar" style="<?= $row['tingkat_risiko'] === 'Tinggi' ? 'background:#ffeaed; color:#d8334b;' : '' ?>">
                    <?= strtoupper(substr($row['nama_lengkap'], 0, 1)) ?>
                  </div>
                  <div>
                    <b><?= htmlspecialchars($row['nama_lengkap']) ?></b>
                    <small>NISN: <?= htmlspecialchars($row['nisn']) ?> &bull; <?= $row['jenis_kelamin'] === 'L' ? 'L' : 'P' ?></small>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge badge-secondary"><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></span>
              </td>
              <td>
                <span class="score <?= $row['skor_akademik'] >= 25 ? 'danger' : '' ?>">
                  <?= $row['skor_akademik'] ?> <small style="opacity:0.7;">/40</small>
                </span>
              </td>
              <td>
                <span class="score <?= $row['skor_psikososial'] >= 25 ? 'danger' : '' ?>">
                  <?= $row['skor_psikososial'] ?> <small style="opacity:0.7;">/40</small>
                </span>
              </td>
              <td>
                <?php if ($row['skor_bullying'] >= 14): ?>
                  <span class="badge badge-red" style="font-weight:700;">⚠ <?= $row['skor_bullying'] ?>/20</span>
                <?php elseif ($row['skor_bullying'] >= 10): ?>
                  <span class="badge badge-orange"><?= $row['skor_bullying'] ?>/20</span>
                <?php else: ?>
                  <span style="color:#10b981; font-weight:600;"><?= $row['skor_bullying'] ?>/20</span>
                <?php endif; ?>
              </td>
              <td>
                <b style="font-size:12px; color:var(--text);"><?= $row['total_skor'] ?></b>
                <small style="color:var(--muted); font-size:9px;">(<?= $row['persentase_skor'] ?>%)</small>
              </td>
              <td>
                <?php if ($row['tingkat_risiko'] === 'Tinggi'): ?>
                  <span class="badge-risiko-tinggi">🔴 Tinggi</span>
                <?php elseif ($row['tingkat_risiko'] === 'Sedang'): ?>
                  <span class="badge-risiko-sedang">🟡 Sedang</span>
                <?php else: ?>
                  <span class="badge-risiko-rendah">🟢 Rendah</span>
                <?php endif; ?>
              </td>
              <td>
                <form method="POST" action="" style="display:inline-block; margin:0;">
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="skrining_id" value="<?= $row['id'] ?>">
                  <select name="status_tindak_lanjut" class="form-select form-select-sm" onchange="this.form.submit()" style="font-size:10px; padding:3px 6px; border-radius:6px; min-width:130px; font-weight:500;">
                    <option value="Belum Ditindaklanjuti" <?= $row['status_tindak_lanjut'] === 'Belum Ditindaklanjuti' ? 'selected' : '' ?>>Belum Ditindaklanjuti</option>
                    <option value="Sedang Konseling" <?= $row['status_tindak_lanjut'] === 'Sedang Konseling' ? 'selected' : '' ?>>Sedang Konseling</option>
                    <option value="Selesai" <?= $row['status_tindak_lanjut'] === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                  </select>
                </form>
              </td>
              <td style="text-align:right;">
                <button type="button" class="btn-icon btn-icon-view" onclick="openGuruModal('modalDetail<?= $row['id'] ?>')" title="Lihat Detail Evaluasi EWS">
                  👁
                </button>
              </td>
            </tr>

            <!-- MODAL DETAIL EVALUASI EWS -->
            <div class="guru-modal-backdrop" id="modalDetail<?= $row['id'] ?>">
              <div class="guru-modal" style="width:min(620px, 100%);">
                <div class="guru-modal-header">
                  <h3>⌁ Hasil Evaluasi EWS: <?= htmlspecialchars($row['nama_lengkap']) ?></h3>
                  <button type="button" class="guru-modal-close" onclick="closeGuruModal('modalDetail<?= $row['id'] ?>')">&times;</button>
                </div>
                <div class="guru-modal-body">
                  <!-- Header Ringkasan Siswa -->
                  <div style="display:flex; align-items:center; justify-content:space-between; padding:14px; background:#f4f8fe; border-radius:10px; margin-bottom:18px;">
                    <div>
                      <strong style="font-size:14px; color:var(--navy); display:block;"><?= htmlspecialchars($row['nama_lengkap']) ?></strong>
                      <span style="font-size:11px; color:var(--muted);">Kelas: <?= htmlspecialchars($row['nama_kelas'] ?? '-') ?> &bull; NISN: <?= htmlspecialchars($row['nisn']) ?> &bull; <?= $row['jenis_kelamin'] === 'L' ? 'Laki-Laki' : 'Perempuan' ?></span>
                    </div>
                    <div>
                      <?php if ($row['tingkat_risiko'] === 'Tinggi'): ?>
                        <span class="badge-risiko-tinggi">Risiko Tinggi</span>
                      <?php elseif ($row['tingkat_risiko'] === 'Sedang'): ?>
                        <span class="badge-risiko-sedang">Risiko Sedang</span>
                      <?php else: ?>
                        <span class="badge-risiko-rendah">Risiko Rendah</span>
                      <?php endif; ?>
                    </div>
                  </div>

                  <!-- Skor Per Dimensi -->
                  <div style="margin-bottom:18px;">
                    <div style="font-size:12px; font-weight:700; color:var(--navy); margin-bottom:10px;">Rincian Skor Per Dimensi:</div>
                    
                    <!-- Dimensi Akademik -->
                    <div style="margin-bottom:10px;">
                      <div style="display:flex; justify-content:space-between; font-size:11px; margin-bottom:4px;">
                        <span>1. Dimensi Akademik (Kesulitan Belajar &amp; Tugas)</span>
                        <b><?= $row['skor_akademik'] ?> / 40</b>
                      </div>
                      <div class="progress-container">
                        <div class="progress-fill <?= $row['skor_akademik'] >= 25 ? 'red' : 'blue' ?>" style="width: <?= ($row['skor_akademik']/40)*100 ?>%;"></div>
                      </div>
                    </div>

                    <!-- Dimensi Psikososial -->
                    <div style="margin-bottom:10px;">
                      <div style="display:flex; justify-content:space-between; font-size:11px; margin-bottom:4px;">
                        <span>2. Dimensi Psikososial (Stres, Kecemasan &amp; Teman)</span>
                        <b><?= $row['skor_psikososial'] ?> / 40</b>
                      </div>
                      <div class="progress-container">
                        <div class="progress-fill <?= $row['skor_psikososial'] >= 25 ? 'red' : 'orange' ?>" style="width: <?= ($row['skor_psikososial']/40)*100 ?>%;"></div>
                      </div>
                    </div>

                    <!-- Dimensi Perundungan -->
                    <div>
                      <div style="display:flex; justify-content:space-between; font-size:11px; margin-bottom:4px;">
                        <span style="color:<?= $row['skor_bullying'] >= 10 ? '#dc2626' : 'inherit' ?>;">3. Indikasi Perundungan / Bullying</span>
                        <b style="color:<?= $row['skor_bullying'] >= 10 ? '#dc2626' : 'inherit' ?>;"><?= $row['skor_bullying'] ?> / 20</b>
                      </div>
                      <div class="progress-container">
                        <div class="progress-fill red" style="width: <?= ($row['skor_bullying']/20)*100 ?>%;"></div>
                      </div>
                    </div>
                  </div>

                  <!-- Analisis Otomatis EWS -->
                  <div style="margin-bottom:16px;">
                    <div style="font-size:11px; font-weight:700; color:var(--navy); margin-bottom:5px;">Analisis Sistem Early Warning:</div>
                    <div style="background:#f8fafc; border:1px solid #e2eaf4; padding:12px; border-radius:8px; font-size:11px; line-height:1.6; color:#475569; white-space:pre-line;">
                      <?= htmlspecialchars($row['catatan_sistem'] ?? 'Tidak ada catatan sistem khusus.') ?>
                    </div>
                  </div>

                  <!-- Data Orang Tua / Kontak -->
                  <div style="background:#fdfdfd; border:1px dashed #d0dbe9; padding:10px 14px; border-radius:8px; font-size:11px; color:#5c718a;">
                    👨‍👩‍👧 <b>Wali Siswa:</b> <?= htmlspecialchars($row['nama_wali'] ?? 'Orang Tua') ?> &bull; <b>No. HP/WA:</b> <?= htmlspecialchars($row['kontak_wali'] ?? '-') ?>
                  </div>
                </div>
                <div class="guru-modal-footer">
                  <button type="button" class="btn btn-secondary btn-sm" onclick="closeGuruModal('modalDetail<?= $row['id'] ?>')">Tutup</button>
                  <a href="<?= BASE_URL ?>guru/pengajuan.php?siswa_id=<?= $row['siswa_id'] ?>" class="btn btn-primary btn-sm">
                    <span>▦</span> Buat Jadwal Konseling
                  </a>
                </div>
              </div>
            </div>

          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="table-note">
    <span>💡 Status tindak lanjut dapat langsung diganti melalui dropdown. Guru BK disarankan menjadwalkan konseling tatap muka untuk siswa berisiko Sedang dan Tinggi.</span>
  </div>
</div>

<?php include __DIR__ . '/../includes/guru_footer.php'; ?>
