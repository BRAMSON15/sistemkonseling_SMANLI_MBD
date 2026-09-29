<?php
/**
 * Kelola Pengajuan Jadwal Konseling Siswa
 * Guru BK - SMA Negeri 5 Maluku Barat Daya
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

require_role('guru_bk');
$user = auth_user();
$guru_id = $user['role_id'];
$page_title = 'Kelola Pengajuan Konseling';

$status_filter = isset($_GET['status']) ? clean($_GET['status']) : 'Menunggu';

// Proses Aksi Guru BK
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pengajuan_id = (int)($_POST['pengajuan_id'] ?? 0);

    if ($action === 'setujui' && $pengajuan_id > 0) {
        $tanggal_jadwal = clean($_POST['tanggal_jadwal'] ?? '');
        $waktu_mulai    = clean($_POST['waktu_mulai'] ?? '');
        $waktu_selesai  = clean($_POST['waktu_selesai'] ?? '');
        $tempat_ruangan = clean($_POST['tempat_ruangan'] ?? 'Ruang BK SMA Negeri 5 MBD');
        $catatan_jadwal = clean($_POST['catatan_jadwal'] ?? '');

        if (empty($tanggal_jadwal) || empty($waktu_mulai) || empty($waktu_selesai)) {
            set_flash('danger', 'Harap lengkapi tanggal dan jam pelaksanaan konseling.');
        } else {
            try {
                $pdo->beginTransaction();

                // Ambil data pengajuan & user_id siswa
                $stmt_p = $pdo->prepare("
                    SELECT p.*, sw.user_id 
                    FROM pengajuan_konseling p
                    JOIN siswa sw ON p.siswa_id = sw.id
                    WHERE p.id = ?
                ");
                $stmt_p->execute([$pengajuan_id]);
                $pengajuan = $stmt_p->fetch();

                if ($pengajuan) {
                    // Masukkan ke jadwal_konseling
                    $stmt_ins = $pdo->prepare("
                        INSERT INTO jadwal_konseling 
                        (pengajuan_id, guru_id, tanggal_jadwal, waktu_mulai, waktu_selesai, tempat_ruangan, catatan_jadwal, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'Dijadwalkan')
                    ");
                    $stmt_ins->execute([
                        $pengajuan_id,
                        $guru_id,
                        $tanggal_jadwal,
                        $waktu_mulai,
                        $waktu_selesai,
                        $tempat_ruangan,
                        $catatan_jadwal
                    ]);

                    // Update status pengajuan
                    $stmt_up = $pdo->prepare("UPDATE pengajuan_konseling SET status = 'Disetujui', guru_id = ? WHERE id = ?");
                    $stmt_up->execute([$guru_id, $pengajuan_id]);

                    // Update status tindak lanjut skrining jika terkait
                    if (!empty($pengajuan['skrining_id'])) {
                        $stmt_sk = $pdo->prepare("UPDATE skrining_asesmen SET status_tindak_lanjut = 'Sedang Konseling' WHERE id = ?");
                        $stmt_sk->execute([$pengajuan['skrining_id']]);
                    }

                    // Kirim notifikasi ke siswa
                    kirim_notifikasi(
                        $pdo,
                        $pengajuan['user_id'],
                        'Jadwal Konseling Disetujui',
                        'Pengajuan konseling Anda telah disetujui pada ' . tgl_indo($tanggal_jadwal) . ' pukul ' . substr($waktu_mulai, 0, 5) . ' WIT di ' . $tempat_ruangan . '.',
                        'siswa/riwayat_siswa.php'
                    );

                    $pdo->commit();
                    set_flash('success', 'Pengajuan berhasil disetujui dan jadwal konseling telah diterbitkan untuk siswa.');
                    header('Location: ' . BASE_URL . 'guru/jadwal.php');
                    exit;
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash('danger', 'Gagal memproses persetujuan: ' . $e->getMessage());
            }
        }

    } elseif ($action === 'tolak' && $pengajuan_id > 0) {
        $alasan_penolakan = clean($_POST['alasan_penolakan'] ?? '');
        if (empty($alasan_penolakan)) {
            set_flash('danger', 'Harap isi alasan penolakan atau instruksi reschedule jadwal.');
        } else {
            try {
                $stmt_p = $pdo->prepare("
                    SELECT p.*, sw.user_id 
                    FROM pengajuan_konseling p
                    JOIN siswa sw ON p.siswa_id = sw.id
                    WHERE p.id = ?
                ");
                $stmt_p->execute([$pengajuan_id]);
                $pengajuan = $stmt_p->fetch();

                if ($pengajuan) {
                    $stmt_up = $pdo->prepare("UPDATE pengajuan_konseling SET status = 'Ditolak', alasan_penolakan = ? WHERE id = ?");
                    $stmt_up->execute([$alasan_penolakan, $pengajuan_id]);

                    kirim_notifikasi(
                        $pdo,
                        $pengajuan['user_id'],
                        'Pembaruan Pengajuan Konseling',
                        'Pengajuan konseling Anda belum dapat disetujui: ' . $alasan_penolakan,
                        'siswa/riwayat_siswa.php'
                    );

                    set_flash('info', 'Pengajuan konseling telah ditolak / diminta reschedule.');
                    header('Location: ' . BASE_URL . 'guru/pengajuan.php');
                    exit;
                }
            } catch (Exception $e) {
                set_flash('danger', 'Gagal memproses: ' . $e->getMessage());
            }
        }
    }
}

// Build query pengajuan
$where = [];
$params = [];

if (!empty($status_filter) && $status_filter !== 'Semua') {
    $where[] = "p.status = ?";
    $params[] = $status_filter;
}

$where_str = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$stmt_list = $pdo->prepare("
    SELECT p.*, sw.id AS siswa_id, sw.nisn, u.nama_lengkap, u.no_hp, k.nama_kelas,
           s.tingkat_risiko, s.total_skor, s.skor_bullying
    FROM pengajuan_konseling p
    JOIN siswa sw ON p.siswa_id = sw.id
    JOIN users u ON sw.user_id = u.id
    LEFT JOIN kelas k ON sw.kelas_id = k.id
    LEFT JOIN skrining_asesmen s ON p.skrining_id = s.id
    $where_str
    ORDER BY p.tanggal_pengajuan DESC
");
$stmt_list->execute($params);
$pengajuan_list = $stmt_list->fetchAll();

include __DIR__ . '/../includes/guru_header.php';
?>

<!-- PAGE HEADER -->
<div class="page-header">
  <div class="page-header-title">
    <div class="page-header-icon">▱</div>
    <div>
      <h1>Kelola Pengajuan Konseling Siswa</h1>
      <p>Tinjau permohonan konseling masuk dari siswa, tentukan jam temu tatap muka, dan tentukan ruang sesi konseling.</p>
    </div>
  </div>
  <div class="page-header-actions">
    <a href="<?= BASE_URL ?>guru/jadwal.php" class="btn btn-outline-primary">
      <span>▦</span> Lihat Agenda Jadwal Aktif
    </a>
  </div>
</div>

<!-- FILTER TABS -->
<div style="margin-bottom:20px;">
  <div class="tab-pills">
    <a href="?status=Menunggu" class="tab-pill <?= $status_filter === 'Menunggu' ? 'active' : '' ?>">
      ⏳ Menunggu Respon
    </a>
    <a href="?status=Disetujui" class="tab-pill <?= $status_filter === 'Disetujui' ? 'active' : '' ?>">
      ✓ Disetujui
    </a>
    <a href="?status=Ditolak" class="tab-pill <?= $status_filter === 'Ditolak' ? 'active' : '' ?>">
      ✕ Ditolak / Reschedule
    </a>
    <a href="?status=Semua" class="tab-pill <?= $status_filter === 'Semua' ? 'active' : '' ?>">
      Semua Riwayat
    </a>
  </div>
</div>

<!-- DAFTAR PENGAJUAN -->
<div style="display:flex; flex-direction:column; gap:14px;">
  <?php if (empty($pengajuan_list)): ?>
    <div class="card-base" style="padding:45px 20px; text-align:center; color:var(--muted);">
      <span style="font-size:36px; display:block; margin-bottom:10px; opacity:0.6;">📥</span>
      <strong style="color:var(--navy); font-size:14px; display:block; margin-bottom:4px;">Tidak Ada Pengajuan</strong>
      <p style="font-size:11px; margin:0;">Tidak ada permohonan konseling dengan status <b><?= htmlspecialchars($status_filter) ?></b> saat ini.</p>
    </div>
  <?php else: ?>
    <?php foreach ($pengajuan_list as $row): ?>
      <div class="card-base" style="padding:20px 22px;">
        <!-- Top Bar Item -->
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding-bottom:14px; border-bottom:1px solid #edf2f9; flex-wrap:wrap;">
          <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <div class="student-avatar" style="width:36px; height:36px; font-size:14px;">
              <?= strtoupper(substr($row['nama_lengkap'], 0, 1)) ?>
            </div>
            <div>
              <div style="display:flex; align-items:center; gap:8px;">
                <strong style="font-size:14px; color:var(--navy);"><?= htmlspecialchars($row['nama_lengkap']) ?></strong>
                <span class="badge badge-secondary"><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></span>
                
                <?php if ($row['status'] === 'Menunggu'): ?>
                  <span class="badge-status badge-menunggu">Menunggu Konfirmasi</span>
                <?php elseif ($row['status'] === 'Disetujui'): ?>
                  <span class="badge-status badge-disetujui">Disetujui</span>
                <?php elseif ($row['status'] === 'Ditolak'): ?>
                  <span class="badge-status badge-ditolak">Ditolak / Reschedule</span>
                <?php else: ?>
                  <span class="badge-status badge-selesai">Selesai</span>
                <?php endif; ?>

                <?php if ($row['is_wajib']): ?>
                  <span class="badge badge-red" style="font-weight:700;">⚠ Wajib Konseling</span>
                <?php endif; ?>
              </div>
              <small style="color:var(--muted); font-size:10px; margin-top:2px; display:block;">
                NISN: <?= htmlspecialchars($row['nisn']) ?> &bull; Diajukan: <?= tgl_indo($row['tanggal_pengajuan'], true) ?>
              </small>
            </div>
          </div>
          <div>
            <?php if ($row['tingkat_risiko']): ?>
              <span style="font-size:10px; color:var(--muted); margin-right:4px;">Indikasi EWS:</span>
              <?php if ($row['tingkat_risiko'] === 'Tinggi'): ?>
                <span class="badge-risiko-tinggi">Risiko Tinggi</span>
              <?php elseif ($row['tingkat_risiko'] === 'Sedang'): ?>
                <span class="badge-risiko-sedang">Risiko Sedang</span>
              <?php else: ?>
                <span class="badge-risiko-rendah">Risiko Rendah</span>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Detail Permintaan -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; margin:16px 0;">
          <div style="background:#f8fafc; padding:10px 14px; border-radius:8px; border:1px solid #edf2f9;">
            <span style="display:block; font-size:10px; color:var(--muted); margin-bottom:3px;">Jenis Layanan:</span>
            <strong style="color:var(--blue); font-size:12px;"><?= htmlspecialchars($row['jenis_konseling']) ?></strong>
          </div>
          <div style="background:#f8fafc; padding:10px 14px; border-radius:8px; border:1px solid #edf2f9;">
            <span style="display:block; font-size:10px; color:var(--muted); margin-bottom:3px;">Preferensi Tanggal Diminta:</span>
            <strong style="color:var(--navy); font-size:12px;">📅 <?= tgl_indo($row['preferensi_tanggal']) ?></strong>
          </div>
          <div style="background:#f8fafc; padding:10px 14px; border-radius:8px; border:1px solid #edf2f9;">
            <span style="display:block; font-size:10px; color:var(--muted); margin-bottom:3px;">Preferensi Jam:</span>
            <strong style="color:var(--navy); font-size:12px;">⏰ <?= htmlspecialchars($row['preferensi_waktu']) ?> WIT</strong>
          </div>
        </div>

        <!-- Pesan Alasan Pengajuan -->
        <div style="background:#f4f7fb; border-left:3px solid var(--blue); padding:12px 14px; border-radius:0 8px 8px 0; margin-bottom:14px;">
          <span style="font-size:10px; font-weight:700; color:#334f77; display:block; margin-bottom:3px;">Kendala / Alasan yang Disampaikan Siswa:</span>
          <div style="font-size:11px; line-height:1.6; color:#2c3e50;"><?= nl2br(htmlspecialchars($row['alasan_pengajuan'])) ?></div>
        </div>

        <?php if ($row['status'] === 'Ditolak'): ?>
          <div style="background:#ffeef1; border:1px solid #ffd2d8; color:#d62e47; padding:10px 14px; border-radius:8px; font-size:11px; margin-bottom:12px;">
            <strong>Alasan Ditolak / Direschedule:</strong> <?= htmlspecialchars($row['alasan_penolakan']) ?>
          </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <?php if ($row['status'] === 'Menunggu'): ?>
          <div style="display:flex; justify-content:flex-end; gap:8px; padding-top:12px; border-top:1px solid #edf2f9;">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="openGuruModal('modalTolak<?= $row['id'] ?>')">
              <span>✕</span> Tolak / Reschedule
            </button>
            <button type="button" class="btn btn-success btn-sm" onclick="openGuruModal('modalSetujui<?= $row['id'] ?>')">
              <span>✓</span> Konfirmasi &amp; Atur Jadwal
            </button>
          </div>

          <!-- MODAL SETUJUI & ATUR JADWAL -->
          <div class="guru-modal-backdrop" id="modalSetujui<?= $row['id'] ?>">
            <div class="guru-modal" style="width:min(520px, 100%);">
              <form method="POST" action="">
                <input type="hidden" name="action" value="setujui">
                <input type="hidden" name="pengajuan_id" value="<?= $row['id'] ?>">
                
                <div class="guru-modal-header" style="background:linear-gradient(135deg, #0e1831, #0f766e);">
                  <h3>✓ Konfirmasi Jadwal Konseling</h3>
                  <button type="button" class="guru-modal-close" onclick="closeGuruModal('modalSetujui<?= $row['id'] ?>')">&times;</button>
                </div>
                
                <div class="guru-modal-body">
                  <p style="font-size:11px; color:var(--muted); margin-bottom:16px;">
                    Tetapkan alokasi waktu dan ruangan untuk sesi konseling siswa <b><?= htmlspecialchars($row['nama_lengkap']) ?></b>.
                  </p>

                  <div class="form-group">
                    <label class="form-label">Tanggal Pelaksanaan <span style="color:#ef4444;">*</span></label>
                    <input type="date" name="tanggal_jadwal" class="form-control" value="<?= $row['preferensi_tanggal'] ?>" required>
                  </div>

                  <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
                    <div>
                      <label class="form-label">Waktu Mulai <span style="color:#ef4444;">*</span></label>
                      <input type="time" name="waktu_mulai" class="form-control" value="09:45" required>
                    </div>
                    <div>
                      <label class="form-label">Waktu Selesai <span style="color:#ef4444;">*</span></label>
                      <input type="time" name="waktu_selesai" class="form-control" value="10:30" required>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="form-label">Tempat / Ruangan Konseling</label>
                    <input type="text" name="tempat_ruangan" class="form-control" value="Ruang BK SMA Negeri 5 MBD" required>
                  </div>

                  <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Catatan Tambahan untuk Siswa (Opsional)</label>
                    <textarea name="catatan_jadwal" class="form-control" rows="2" placeholder="Contoh: Harap hadir tepat waktu dan membawa buku agenda belajar..."></textarea>
                  </div>
                </div>

                <div class="guru-modal-footer">
                  <button type="button" class="btn btn-secondary btn-sm" onclick="closeGuruModal('modalSetujui<?= $row['id'] ?>')">Batal</button>
                  <button type="submit" class="btn btn-success btn-sm">Setujui &amp; Terbitkan Jadwal</button>
                </div>
              </form>
            </div>
          </div>

          <!-- MODAL TOLAK / RESCHEDULE -->
          <div class="guru-modal-backdrop" id="modalTolak<?= $row['id'] ?>">
            <div class="guru-modal" style="width:min(480px, 100%);">
              <form method="POST" action="">
                <input type="hidden" name="action" value="tolak">
                <input type="hidden" name="pengajuan_id" value="<?= $row['id'] ?>">

                <div class="guru-modal-header" style="background:linear-gradient(135deg, #0e1831, #991b1b);">
                  <h3>✕ Tolak / Minta Reschedule</h3>
                  <button type="button" class="guru-modal-close" onclick="closeGuruModal('modalTolak<?= $row['id'] ?>')">&times;</button>
                </div>

                <div class="guru-modal-body">
                  <p style="font-size:11px; color:var(--muted); margin-bottom:14px;">
                    Berikan alasan penolakan atau saran tanggal/jam alternatif agar siswa dapat mengajukan ulang jadwal temu.
                  </p>
                  
                  <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Alasan / Catatan Instruksi untuk Siswa <span style="color:#ef4444;">*</span></label>
                    <textarea name="alasan_penolakan" class="form-control" rows="3" placeholder="Contoh: Pada tanggal tersebut Guru BK sedang ada tugas dinas di Tiakur. Mohon ajukan kembali untuk hari Kamis." required></textarea>
                  </div>
                </div>

                <div class="guru-modal-footer">
                  <button type="button" class="btn btn-secondary btn-sm" onclick="closeGuruModal('modalTolak<?= $row['id'] ?>')">Kembali</button>
                  <button type="submit" class="btn btn-danger btn-sm">Kirim Penolakan</button>
                </div>
              </form>
            </div>
          </div>

        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/guru_footer.php'; ?>
