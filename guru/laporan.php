<?php
/**
 * Laporan & Rekapitulasi Resmi BK
 * SMA Negeri 5 Maluku Barat Daya
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

require_role('guru_bk');
$user = auth_user();
$page_title = 'Laporan & Rekapitulasi Layanan BK';

$jenis_laporan = isset($_GET['jenis']) && $_GET['jenis'] === 'konseling' ? 'konseling' : 'skrining';
$kelas_id      = isset($_GET['kelas_id']) && $_GET['kelas_id'] !== '' ? (int)$_GET['kelas_id'] : '';
$tgl_mulai     = clean($_GET['tgl_mulai'] ?? date('Y-m-01'));
$tgl_selesai   = clean($_GET['tgl_selesai'] ?? date('Y-m-d'));
$risiko        = clean($_GET['risiko'] ?? '');

$kelas_list = $pdo->query("SELECT * FROM kelas ORDER BY tingkat ASC, nama_kelas ASC")->fetchAll();

// Data Laporan Skrining
$data_skrining = [];
if ($jenis_laporan === 'skrining') {
    $where = ["DATE(s.tanggal_pengisian) BETWEEN ? AND ?"];
    $params = [$tgl_mulai, $tgl_selesai];

    if ($kelas_id !== '') {
        $where[] = "sw.kelas_id = ?";
        $params[] = $kelas_id;
    }
    if ($risiko !== '') {
        $where[] = "s.tingkat_risiko = ?";
        $params[] = $risiko;
    }

    $where_clause = implode(" AND ", $where);
    $stmt = $pdo->prepare("
        SELECT s.*, sw.nisn, sw.jenis_kelamin, u.nama_lengkap, k.nama_kelas, k.jurusan
        FROM skrining_asesmen s
        JOIN siswa sw ON s.siswa_id = sw.id
        JOIN users u ON sw.user_id = u.id
        LEFT JOIN kelas k ON sw.kelas_id = k.id
        WHERE $where_clause
        ORDER BY s.tanggal_pengisian DESC
    ");
    $stmt->execute($params);
    $data_skrining = $stmt->fetchAll();
}

// Data Laporan Konseling
$data_konseling = [];
if ($jenis_laporan === 'konseling') {
    $where = ["c.tanggal_sesi BETWEEN ? AND ?"];
    $params = [$tgl_mulai, $tgl_selesai];

    if ($kelas_id !== '') {
        $where[] = "sw.kelas_id = ?";
        $params[] = $kelas_id;
    }

    $where_clause = implode(" AND ", $where);
    $stmt = $pdo->prepare("
        SELECT c.*, p.jenis_konseling,
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
        ORDER BY c.tanggal_sesi DESC
    ");
    $stmt->execute($params);
    $data_konseling = $stmt->fetchAll();
}

include __DIR__ . '/../includes/guru_header.php';
?>

<!-- Kop Surat Resmi SMA Negeri 5 MBD (Muncul saat Print) -->
<div class="kop-surat">
  <div class="kop-text">
    <h4>PEMERINTAH PROVINSI MALUKU</h4>
    <h4>DINAS PENDIDIKAN DAN KEBUDAYAAN</h4>
    <h3>SMA NEGERI 5 MALUKU BARAT DAYA</h3>
    <p>Jl. Trans Pulau Moa, Tiakur, Kabupaten Maluku Barat Daya, Maluku 97442</p>
    <p>Email: sman5mbd@gmail.com &bull; Laman: https://sman5mbd.sch.id</p>
  </div>
</div>

<!-- PAGE HEADER (NO PRINT) -->
<div class="no-print page-header">
  <div class="page-header-title">
    <div class="page-header-icon">▣</div>
    <div>
      <h1>Laporan &amp; Rekapitulasi Layanan BK</h1>
      <p>Cetak dan ekspor dokumen laporan rekapitulasi deteksi dini EWS dan pelayanan konseling berformat resmi kedinasan.</p>
    </div>
  </div>
  <div class="page-header-actions">
    <button onclick="window.print()" class="btn btn-primary">
      <span>🖨</span> Cetak / Simpan PDF
    </button>
  </div>
</div>

<!-- SWITCHER TAB LAPORAN (NO PRINT) -->
<div class="no-print" style="margin-bottom:16px;">
  <div class="tab-pills">
    <a href="?jenis=skrining" class="tab-pill <?= $jenis_laporan === 'skrining' ? 'active' : '' ?>">
      ⌁ Rekapitulasi Deteksi Dini (EWS)
    </a>
    <a href="?jenis=konseling" class="tab-pill <?= $jenis_laporan === 'konseling' ? 'active' : '' ?>">
      📋 Rekapitulasi Layanan Konseling
    </a>
  </div>
</div>

<!-- FILTER CARD (NO PRINT) -->
<div class="no-print filter-card">
  <form method="GET" action="" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) auto; gap:12px; align-items:flex-end;">
    <input type="hidden" name="jenis" value="<?= htmlspecialchars($jenis_laporan) ?>">
    
    <div class="form-group" style="margin-bottom:0;">
      <label class="form-label">Periode Mulai</label>
      <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= $tgl_mulai ?>">
    </div>

    <div class="form-group" style="margin-bottom:0;">
      <label class="form-label">Sampai Tanggal</label>
      <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?= $tgl_selesai ?>">
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

    <?php if ($jenis_laporan === 'skrining'): ?>
      <div class="form-group" style="margin-bottom:0;">
        <label class="form-label">Tingkat Risiko</label>
        <select name="risiko" class="form-select form-select-sm">
          <option value="">-- Semua Risiko --</option>
          <option value="Tinggi" <?= $risiko === 'Tinggi' ? 'selected' : '' ?>>Tinggi</option>
          <option value="Sedang" <?= $risiko === 'Sedang' ? 'selected' : '' ?>>Sedang</option>
          <option value="Rendah" <?= $risiko === 'Rendah' ? 'selected' : '' ?>>Rendah</option>
        </select>
      </div>
    <?php endif; ?>

    <div>
      <button type="submit" class="btn btn-primary btn-sm">
        <span>⚲</span> Filter Laporan
      </button>
    </div>
  </form>
</div>

<!-- ==========================================
     TAMPILAN LAPORAN SKRINING DETEKSI DINI
     ========================================== -->
<?php if ($jenis_laporan === 'skrining'): ?>
  <div style="text-align:center; margin-bottom:16px;">
    <h2 style="font-size:14px; font-weight:800; color:#0e1831; text-transform:uppercase; margin-bottom:4px; letter-spacing:0.5px;">
      REKAPITULASI HASIL DETEKSI DINI PERMASALAHAN AKADEMIK DAN PSIKOSOSIAL
    </h2>
    <span style="font-size:11px; color:#475569;">
      Periode Data: <b><?= tgl_indo($tgl_mulai) ?></b> s/d <b><?= tgl_indo($tgl_selesai) ?></b>
    </span>
  </div>

  <div class="panel" style="overflow:hidden; margin-bottom:24px;">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:36px; text-align:center;">No</th>
            <th>Tanggal</th>
            <th>NISN</th>
            <th>Nama Siswa</th>
            <th style="text-align:center;">L/P</th>
            <th>Kelas</th>
            <th style="text-align:center;">Akademik (40)</th>
            <th style="text-align:center;">Psikososial (40)</th>
            <th style="text-align:center;">Bullying (20)</th>
            <th style="text-align:center;">Total Skor (100)</th>
            <th style="text-align:center;">Tingkat Risiko</th>
            <th>Rekomendasi Intervensi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($data_skrining)): ?>
            <tr>
              <td colspan="12" style="text-align:center; padding:35px 20px; color:var(--muted);">
                Tidak ada data skrining asesmen yang tercatat pada rentang periode ini.
              </td>
            </tr>
          <?php else: ?>
            <?php $no = 1; foreach ($data_skrining as $ds): ?>
              <tr>
                <td style="text-align:center;"><?= $no++ ?></td>
                <td><?= date('d/m/Y', strtotime($ds['tanggal_pengisian'])) ?></td>
                <td><?= htmlspecialchars($ds['nisn']) ?></td>
                <td><b><?= htmlspecialchars($ds['nama_lengkap']) ?></b></td>
                <td style="text-align:center;"><?= $ds['jenis_kelamin'] ?></td>
                <td><span class="badge badge-secondary"><?= htmlspecialchars($ds['nama_kelas'] ?? '-') ?></span></td>
                <td style="text-align:center;"><?= $ds['skor_akademik'] ?></td>
                <td style="text-align:center;"><?= $ds['skor_psikososial'] ?></td>
                <td style="text-align:center; font-weight:<?= $ds['skor_bullying'] >= 10 ? 'bold' : 'normal' ?>; color:<?= $ds['skor_bullying'] >= 10 ? '#dc2626' : 'inherit' ?>;">
                  <?= $ds['skor_bullying'] ?>
                </td>
                <td style="text-align:center;">
                  <b><?= $ds['total_skor'] ?></b> (<?= $ds['persentase_skor'] ?>%)
                </td>
                <td style="text-align:center;">
                  <?php if ($ds['tingkat_risiko'] === 'Tinggi'): ?>
                    <span class="badge-risiko-tinggi">Tinggi</span>
                  <?php elseif ($ds['tingkat_risiko'] === 'Sedang'): ?>
                    <span class="badge-risiko-sedang">Sedang</span>
                  <?php else: ?>
                    <span class="badge-risiko-rendah">Rendah</span>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($ds['status_rekomendasi']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<!-- ==========================================
     TAMPILAN LAPORAN PELAYANAN KONSELING
     ========================================== -->
<?php else: ?>
  <div style="text-align:center; margin-bottom:16px;">
    <h2 style="font-size:14px; font-weight:800; color:#0e1831; text-transform:uppercase; margin-bottom:4px; letter-spacing:0.5px;">
      REKAPITULASI PELAYANAN BIMBINGAN DAN KONSELING SISWA
    </h2>
    <span style="font-size:11px; color:#475569;">
      Periode Data: <b><?= tgl_indo($tgl_mulai) ?></b> s/d <b><?= tgl_indo($tgl_selesai) ?></b>
    </span>
  </div>

  <div class="panel" style="overflow:hidden; margin-bottom:24px;">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:36px; text-align:center;">No</th>
            <th>Tanggal Sesi</th>
            <th>NISN &amp; Nama Siswa</th>
            <th>Kelas</th>
            <th>Layanan</th>
            <th>Konselor (Guru BK)</th>
            <th>Keluhan &amp; Masalah Inti</th>
            <th>Tindakan Intervensi</th>
            <th style="text-align:center;">Status Kasus</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($data_konseling)): ?>
            <tr>
              <td colspan="9" style="text-align:center; padding:35px 20px; color:var(--muted);">
                Tidak ada data pelayanan konseling yang tercatat pada rentang periode ini.
              </td>
            </tr>
          <?php else: ?>
            <?php $no = 1; foreach ($data_konseling as $dk): ?>
              <tr>
                <td style="text-align:center;"><?= $no++ ?></td>
                <td><?= date('d/m/Y', strtotime($dk['tanggal_sesi'])) ?></td>
                <td>
                  <b><?= htmlspecialchars($dk['nama_siswa']) ?></b>
                  <small style="color:var(--muted); display:block; font-size:9px;">NISN: <?= htmlspecialchars($dk['nisn']) ?></small>
                </td>
                <td><span class="badge badge-secondary"><?= htmlspecialchars($dk['nama_kelas'] ?? '-') ?></span></td>
                <td><span class="badge badge-blue"><?= htmlspecialchars($dk['jenis_konseling']) ?></span></td>
                <td><?= htmlspecialchars($dk['nama_guru']) ?></td>
                <td style="max-width:240px; white-space:normal; line-height:1.5;">
                  <?= nl2br(htmlspecialchars($dk['keluhan_utama'])) ?>
                </td>
                <td style="max-width:240px; white-space:normal; line-height:1.5;">
                  <?= nl2br(htmlspecialchars($dk['tindakan_intervensi'])) ?>
                </td>
                <td style="text-align:center;">
                  <?php if ($dk['status_perkembangan'] === 'Kasus Selesai'): ?>
                    <span class="badge-status badge-selesai">Kasus Selesai</span>
                  <?php else: ?>
                    <span class="badge-status badge-menunggu"><?= htmlspecialchars($dk['status_perkembangan']) ?></span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<!-- Tanda Tangan Cetak Resmi -->
<div style="display:grid; grid-template-columns:1fr 1fr; text-align:center; margin-top:36px; padding-top:20px; page-break-inside:avoid;">
  <div>
    <p style="font-size:11px; margin-bottom:50px;">
      Mengetahui,<br>
      <strong>Kepala SMA Negeri 5 Maluku Barat Daya</strong>
    </p>
    <p style="font-weight:700; text-decoration:underline; margin:0; font-size:12px;">Drs. J. Dahoklory, M.Pd.</p>
    <small style="color:var(--muted); font-size:10px;">NIP. 196803151994031008</small>
  </div>
  <div>
    <p style="font-size:11px; margin-bottom:50px;">
      Tiakur, <?= tgl_indo(date('Y-m-d')) ?><br>
      <strong>Guru Bimbingan Konseling (BK)</strong>
    </p>
    <p style="font-weight:700; text-decoration:underline; margin:0; font-size:12px;">Agatha Natalia Laipeny, S.Pd.</p>
    <small style="color:var(--muted); font-size:10px;">NIP. 198504122010012003</small>
  </div>
</div>

<?php include __DIR__ . '/../includes/guru_footer.php'; ?>
