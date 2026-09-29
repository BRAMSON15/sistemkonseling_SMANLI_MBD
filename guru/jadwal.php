<?php
/**
 * Jadwal Pelaksanaan Konseling Guru BK
 * SMA Negeri 5 Maluku Barat Daya
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

require_role('guru_bk');
$user = auth_user();
$guru_id = $user['role_id'];
$page_title = 'Jadwal Konseling Siswa';

$filter = isset($_GET['filter']) ? clean($_GET['filter']) : 'aktif';

$where = ["1=1"];
$params = [];

if ($filter === 'hari_ini') {
    $where[] = "j.tanggal_jadwal = CURDATE()";
} elseif ($filter === 'mendatang') {
    $where[] = "j.tanggal_jadwal >= CURDATE() AND j.status = 'Dijadwalkan'";
} elseif ($filter === 'selesai') {
    $where[] = "j.status = 'Selesai'";
} else {
    // aktif (dijadwalkan)
    $where[] = "j.status = 'Dijadwalkan'";
}

$where_str = implode(" AND ", $where);

$stmt_j = $pdo->prepare("
    SELECT j.*, p.jenis_konseling, p.alasan_pengajuan, p.is_wajib,
           sw.id AS siswa_id, sw.nisn, sw.jenis_kelamin, u.nama_lengkap AS nama_siswa, u.no_hp AS no_hp_siswa,
           k.nama_kelas,
           c.id AS catatan_id, c.status_perkembangan, c.keluhan_utama, c.hasil_evaluasi_bk, c.tindakan_intervensi, c.rekomendasi_tindak_lanjut
    FROM jadwal_konseling j
    JOIN pengajuan_konseling p ON j.pengajuan_id = p.id
    JOIN siswa sw ON p.siswa_id = sw.id
    JOIN users u ON sw.user_id = u.id
    LEFT JOIN kelas k ON sw.kelas_id = k.id
    LEFT JOIN catatan_konseling c ON j.id = c.jadwal_id
    WHERE $where_str
    ORDER BY j.tanggal_jadwal ASC, j.waktu_mulai ASC
");
$stmt_j->execute($params);
$jadwal_list = $stmt_j->fetchAll();

include __DIR__ . '/../includes/guru_header.php';
?>

<!-- PAGE HEADER -->
<div class="page-header">
  <div class="page-header-title">
    <div class="page-header-icon">▦</div>
    <div>
      <h1>Agenda Jadwal Konseling Siswa</h1>
      <p>Kelola jadwal tatap muka, laksanakan sesi bimbingan konseling klinis, dan pantau penyelesaian kasus siswa.</p>
    </div>
  </div>
  <div class="page-header-actions">
    <a href="<?= BASE_URL ?>guru/pengajuan.php" class="btn btn-outline-primary">
      <span>▱</span> Tinjau Pengajuan Masuk
    </a>
  </div>
</div>

<!-- FILTER TABS -->
<div style="margin-bottom:20px;">
  <div class="tab-pills">
    <a href="?filter=aktif" class="tab-pill <?= $filter === 'aktif' ? 'active' : '' ?>">
      ▦ Jadwal Aktif (Dijadwalkan)
    </a>
    <a href="?filter=hari_ini" class="tab-pill <?= $filter === 'hari_ini' ? 'active' : '' ?>">
      ★ Sesi Hari Ini
    </a>
    <a href="?filter=selesai" class="tab-pill <?= $filter === 'selesai' ? 'active' : '' ?>">
      ✓ Selesai Dilaksanakan
    </a>
  </div>
</div>

<!-- DAFTAR JADWAL -->
<div style="display:flex; flex-direction:column; gap:14px;">
  <?php if (empty($jadwal_list)): ?>
    <div class="card-base" style="padding:45px 20px; text-align:center; color:var(--muted);">
      <span style="font-size:36px; display:block; margin-bottom:10px; opacity:0.6;">📅</span>
      <strong style="color:var(--navy); font-size:14px; display:block; margin-bottom:4px;">Tidak Ada Jadwal Konseling</strong>
      <p style="font-size:11px; margin:0;">Tidak ada sesi bimbingan konseling yang ditemukan pada kategori filter ini.</p>
    </div>
  <?php else: ?>
    <?php foreach ($jadwal_list as $row): ?>
      <div class="card-base" style="padding:20px 22px;">
        <!-- Top Bar Item -->
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
                
                <?php if ($row['status'] === 'Dijadwalkan'): ?>
                  <span class="badge-status badge-disetujui">Dijadwalkan</span>
                <?php else: ?>
                  <span class="badge-status badge-selesai">Selesai Dilaksanakan</span>
                <?php endif; ?>

                <?php if ($row['is_wajib']): ?>
                  <span class="badge badge-red" style="font-weight:700;">Wajib</span>
                <?php endif; ?>
              </div>
              <small style="color:var(--muted); font-size:10px; margin-top:2px; display:block;">
                NISN: <?= htmlspecialchars($row['nisn']) ?> &bull; No. HP/WA: <?= htmlspecialchars($row['no_hp_siswa'] ?? '-') ?>
              </small>
            </div>
          </div>
          
          <!-- Tanggal & Jam Badge -->
          <div style="background:#f4f8fe; border:1px solid #dce8fa; border-radius:10px; padding:8px 14px; text-align:right;">
            <strong style="color:var(--blue); font-size:12px; display:block;">
              📅 <?= tgl_indo($row['tanggal_jadwal']) ?>
            </strong>
            <small style="color:var(--text); font-size:10px; font-weight:600;">
              ⏰ Pukul <?= substr($row['waktu_mulai'], 0, 5) ?> - <?= substr($row['waktu_selesai'], 0, 5) ?> WIT
            </small>
          </div>
        </div>

        <!-- Ruangan & Catatan -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px; margin:14px 0;">
          <div style="background:#f8fafc; padding:10px 14px; border-radius:8px; border:1px solid #edf2f9;">
            <span style="display:block; font-size:10px; color:var(--muted); margin-bottom:2px;">Lokasi / Tempat Sesi:</span>
            <strong style="color:var(--navy); font-size:11px;">📍 <?= htmlspecialchars($row['tempat_ruangan']) ?></strong>
          </div>
          <div style="background:#f8fafc; padding:10px 14px; border-radius:8px; border:1px solid #edf2f9;">
            <span style="display:block; font-size:10px; color:var(--muted); margin-bottom:2px;">Pesan Tambahan Guru BK:</span>
            <span style="font-size:11px; color:#475569;"><?= htmlspecialchars($row['catatan_jadwal'] ?? 'Tidak ada pesan khusus.') ?></span>
          </div>
        </div>

        <!-- Topik Awal Siswa -->
        <div style="background:#f9fafb; border-left:3px solid #64748b; padding:10px 14px; border-radius:0 8px 8px 0; margin-bottom:14px;">
          <span style="font-size:10px; font-weight:700; color:#475569; display:block; margin-bottom:2px;">Topik Awal Permasalahan Siswa:</span>
          <div style="font-size:11px; line-height:1.5; color:#334155;"><?= nl2br(htmlspecialchars($row['alasan_pengajuan'])) ?></div>
        </div>

        <!-- Action Row -->
        <div style="display:flex; justify-content:flex-end; gap:8px; padding-top:12px; border-top:1px solid #edf2f9;">
          <?php if ($row['status'] === 'Dijadwalkan'): ?>
            <a href="<?= BASE_URL ?>guru/konseling_proses.php?jadwal_id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">
              <span>✍</span> Laksanakan Konseling &amp; Catat Rekam Medis
            </a>
          <?php else: ?>
            <button type="button" class="btn btn-outline-success btn-sm" onclick="openGuruModal('modalCatatan<?= $row['id'] ?>')">
              <span>📋</span> Lihat Rekam Catatan Konseling
            </button>
          <?php endif; ?>
        </div>

        <!-- MODAL LIHAT CATATAN KONSELING -->
        <?php if ($row['status'] === 'Selesai'): ?>
          <div class="guru-modal-backdrop" id="modalCatatan<?= $row['id'] ?>">
            <div class="guru-modal" style="width:min(600px, 100%);">
              <div class="guru-modal-header" style="background:linear-gradient(135deg, #0e1831, #15803d);">
                <h3>📋 Rekam Layanan Konseling: <?= htmlspecialchars($row['nama_siswa']) ?></h3>
                <button type="button" class="guru-modal-close" onclick="closeGuruModal('modalCatatan<?= $row['id'] ?>')">&times;</button>
              </div>
              <div class="guru-modal-body">
                <div style="margin-bottom:14px;">
                  <span style="font-size:11px; font-weight:700; color:var(--navy); display:block; margin-bottom:4px;">1. Keluhan &amp; Fakta Permasalahan:</span>
                  <div style="background:#f8fafc; border:1px solid #e2eaf4; padding:10px 12px; border-radius:8px; font-size:11px; line-height:1.6; color:#334155;">
                    <?= nl2br(htmlspecialchars($row['keluhan_utama'] ?? '-')) ?>
                  </div>
                </div>

                <div style="margin-bottom:14px;">
                  <span style="font-size:11px; font-weight:700; color:var(--navy); display:block; margin-bottom:4px;">2. Hasil Evaluasi &amp; Diagnosis Guru BK:</span>
                  <div style="background:#f8fafc; border:1px solid #e2eaf4; padding:10px 12px; border-radius:8px; font-size:11px; line-height:1.6; color:#334155;">
                    <?= nl2br(htmlspecialchars($row['hasil_evaluasi_bk'] ?? '-')) ?>
                  </div>
                </div>

                <div style="margin-bottom:14px;">
                  <span style="font-size:11px; font-weight:700; color:var(--navy); display:block; margin-bottom:4px;">3. Tindakan Intervensi yang Diberikan:</span>
                  <div style="background:#f8fafc; border:1px solid #e2eaf4; padding:10px 12px; border-radius:8px; font-size:11px; line-height:1.6; color:#334155;">
                    <?= nl2br(htmlspecialchars($row['tindakan_intervensi'] ?? '-')) ?>
                  </div>
                </div>

                <div style="margin-bottom:14px;">
                  <span style="font-size:11px; font-weight:700; color:var(--navy); display:block; margin-bottom:4px;">4. Rekomendasi Tindak Lanjut untuk Siswa:</span>
                  <div style="background:#f8fafc; border:1px solid #e2eaf4; padding:10px 12px; border-radius:8px; font-size:11px; line-height:1.6; color:#334155;">
                    <?= nl2br(htmlspecialchars($row['rekomendasi_tindak_lanjut'] ?? '-')) ?>
                  </div>
                </div>

                <div style="display:flex; align-items:center; gap:8px; background:#f4f8fe; padding:10px 14px; border-radius:8px;">
                  <span style="font-size:11px; font-weight:600; color:var(--navy);">Status Evaluasi Kasus:</span>
                  <span class="badge badge-green"><?= htmlspecialchars($row['status_perkembangan'] ?? '-') ?></span>
                </div>
              </div>
              <div class="guru-modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeGuruModal('modalCatatan<?= $row['id'] ?>')">Tutup</button>
              </div>
            </div>
          </div>
        <?php endif; ?>

      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/guru_footer.php'; ?>
