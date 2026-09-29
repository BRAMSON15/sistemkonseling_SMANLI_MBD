<?php
/**
 * Rekam Jejak Layanan Konseling Siswa
 * Guru BK - SMA Negeri 5 Maluku Barat Daya
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

require_role('guru_bk');
$user = auth_user();
$page_title = 'Rekam Jejak Konseling Siswa';

$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$status = isset($_GET['status']) ? clean($_GET['status']) : '';
$kelas_id = isset($_GET['kelas_id']) && $_GET['kelas_id'] !== '' ? (int)$_GET['kelas_id'] : '';

$where = ["1=1"];
$params = [];

if ($search !== '') {
    $where[] = "(u.nama_lengkap LIKE ? OR sw.nisn LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status !== '') {
    $where[] = "c.status_perkembangan = ?";
    $params[] = $status;
}
if ($kelas_id !== '') {
    $where[] = "sw.kelas_id = ?";
    $params[] = $kelas_id;
}

$where_clause = implode(" AND ", $where);

$stmt = $pdo->prepare("
    SELECT c.*, j.tempat_ruangan, p.jenis_konseling,
           sw.nisn, sw.jenis_kelamin, u.nama_lengkap AS nama_siswa, k.nama_kelas,
           ug.nama_lengkap AS nama_guru
    FROM catatan_konseling c
    JOIN jadwal_konseling j ON c.jadwal_id = j.id
    JOIN pengajuan_konseling p ON j.pengajuan_id = p.id
    JOIN siswa sw ON c.siswa_id = sw.id
    JOIN users u ON sw.user_id = u.id
    LEFT JOIN kelas k ON sw.kelas_id = k.id
    JOIN guru_bk g ON c.guru_id = g.id
    JOIN users ug ON g.user_id = ug.id
    WHERE $where_clause
    ORDER BY c.tanggal_sesi DESC, c.id DESC
");
$stmt->execute($params);
$catatan_list = $stmt->fetchAll();

$kelas_list = $pdo->query("SELECT * FROM kelas ORDER BY tingkat ASC, nama_kelas ASC")->fetchAll();

include __DIR__ . '/../includes/guru_header.php';
?>

<!-- PAGE HEADER -->
<div class="page-header">
  <div class="page-header-title">
    <div class="page-header-icon">▧</div>
    <div>
      <h1>Rekam Jejak Konseling Siswa</h1>
      <p>Arsip dokumentasi rekam kasus siswa, catatan diagnosis konselor, riwayat intervensi, dan hasil evaluasi perkembangan.</p>
    </div>
  </div>
  <div class="page-header-actions">
    <a href="<?= BASE_URL ?>guru/laporan.php?jenis=konseling" class="btn btn-primary">
      <span>▣</span> Cetak Rekapitulasi Konseling
    </a>
  </div>
</div>

<!-- FILTER CARD -->
<div class="filter-card">
  <form method="GET" action="" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) auto; gap:12px; align-items:flex-end;">
    <div class="form-group" style="margin-bottom:0;">
      <label class="form-label">Cari Nama Siswa / NISN</label>
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
      <label class="form-label">Status Perkembangan Kasus</label>
      <select name="status" class="form-select form-select-sm">
        <option value="">-- Semua Status --</option>
        <option value="Kasus Selesai" <?= $status === 'Kasus Selesai' ? 'selected' : '' ?>>Kasus Selesai</option>
        <option value="Perlu Sesi Lanjutan" <?= $status === 'Perlu Sesi Lanjutan' ? 'selected' : '' ?>>Perlu Sesi Lanjutan</option>
        <option value="Rujukan Khusus/Wali" <?= $status === 'Rujukan Khusus/Wali' ? 'selected' : '' ?>>Rujukan Khusus / Wali</option>
      </select>
    </div>

    <div style="display:flex; gap:6px;">
      <button type="submit" class="btn btn-primary btn-sm">
        <span>⚲</span> Filter
      </button>
      <a href="<?= BASE_URL ?>guru/riwayat_konseling.php" class="btn btn-light btn-sm" title="Reset Filter">
        ↺ Reset
      </a>
    </div>
  </form>
</div>

<!-- DAFTAR HISTORIS REKAM KONSELING -->
<div style="display:flex; flex-direction:column; gap:14px;">
  <?php if (empty($catatan_list)): ?>
    <div class="card-base" style="padding:45px 20px; text-align:center; color:var(--muted);">
      <span style="font-size:36px; display:block; margin-bottom:10px; opacity:0.6;">📂</span>
      <strong style="color:var(--navy); font-size:14px; display:block; margin-bottom:4px;">Tidak Ada Rekam Jejak</strong>
      <p style="font-size:11px; margin:0;">Tidak ada arsip catatan konseling yang sesuai dengan parameter pencarian saat ini.</p>
    </div>
  <?php else: ?>
    <?php foreach ($catatan_list as $row): ?>
      <div class="card-base" style="padding:22px;">
        <!-- Header Rekam -->
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding-bottom:14px; border-bottom:1px solid #edf2f9; flex-wrap:wrap;">
          <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <div class="student-avatar" style="width:38px; height:38px; font-size:14px;">
              <?= strtoupper(substr($row['nama_siswa'], 0, 1)) ?>
            </div>
            <div>
              <div style="display:flex; align-items:center; gap:8px;">
                <strong style="font-size:14px; color:var(--navy);"><?= htmlspecialchars($row['nama_siswa']) ?></strong>
                <span class="badge badge-secondary"><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></span>
                <span class="badge badge-blue"><?= htmlspecialchars($row['jenis_konseling']) ?></span>
                
                <?php if ($row['status_perkembangan'] === 'Kasus Selesai'): ?>
                  <span class="badge-status badge-selesai">✓ Kasus Selesai</span>
                <?php elseif ($row['status_perkembangan'] === 'Perlu Sesi Lanjutan'): ?>
                  <span class="badge-status badge-menunggu">⏳ Perlu Sesi Lanjutan</span>
                <?php else: ?>
                  <span class="badge-status badge-ditolak">⚠ Rujukan Khusus / Wali</span>
                <?php endif; ?>
              </div>
              <small style="color:var(--muted); font-size:10px; margin-top:2px; display:block;">
                NISN: <?= htmlspecialchars($row['nisn']) ?> &bull; Konselor Pembimbing: <b><?= htmlspecialchars($row['nama_guru']) ?></b>
              </small>
            </div>
          </div>

          <div style="background:#f4f8fe; border:1px solid #dce8fa; border-radius:8px; padding:6px 12px; text-align:right;">
            <strong style="color:var(--blue); font-size:11px; display:block;">
              📅 Sesi: <?= tgl_indo($row['tanggal_sesi']) ?>
            </strong>
            <small style="color:var(--muted); font-size:9px;">Ruang: <?= htmlspecialchars($row['tempat_ruangan']) ?></small>
          </div>
        </div>

        <!-- 4 Grid Catatan Klinis -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:12px; margin-top:16px;">
          <!-- 1. Keluhan -->
          <div style="background:#f8fafc; border:1px solid #e2eaf4; padding:12px 14px; border-radius:8px;">
            <strong style="font-size:11px; color:#2c4b78; display:block; margin-bottom:4px;">1. Keluhan &amp; Fakta Permasalahan:</strong>
            <div style="font-size:11px; line-height:1.6; color:#334155;"><?= nl2br(htmlspecialchars($row['keluhan_utama'])) ?></div>
          </div>

          <!-- 2. Diagnosis -->
          <div style="background:#f8fafc; border:1px solid #e2eaf4; padding:12px 14px; border-radius:8px;">
            <strong style="font-size:11px; color:#2c4b78; display:block; margin-bottom:4px;">2. Diagnosis &amp; Analisis Guru BK:</strong>
            <div style="font-size:11px; line-height:1.6; color:#334155;"><?= nl2br(htmlspecialchars($row['hasil_evaluasi_bk'])) ?></div>
          </div>

          <!-- 3. Intervensi -->
          <div style="background:#f8fafc; border:1px solid #e2eaf4; padding:12px 14px; border-radius:8px;">
            <strong style="font-size:11px; color:#2c4b78; display:block; margin-bottom:4px;">3. Tindakan Intervensi Konseling:</strong>
            <div style="font-size:11px; line-height:1.6; color:#334155;"><?= nl2br(htmlspecialchars($row['tindakan_intervensi'])) ?></div>
          </div>

          <!-- 4. Rekomendasi -->
          <div style="background:#f8fafc; border:1px solid #e2eaf4; padding:12px 14px; border-radius:8px;">
            <strong style="font-size:11px; color:#2c4b78; display:block; margin-bottom:4px;">4. Rekomendasi Tindak Lanjut Siswa:</strong>
            <div style="font-size:11px; line-height:1.6; color:#334155;"><?= nl2br(htmlspecialchars($row['rekomendasi_tindak_lanjut'])) ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/guru_footer.php'; ?>
