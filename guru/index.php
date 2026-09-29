<?php
/**
 * Dashboard Guru BK - Early Warning System (EWS) Command Center
 * Template: assets/guruBK/dashboard_guru_bk.html & assets/guruBK/style-guru-bk.css
 * Sistem Konseling SMA Negeri 5 Maluku Barat Daya
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

require_role('guru_bk');
$user = auth_user();
$guru_id = $user['role_id'] ?? null;
$page_title = 'Dashboard Guru BK (EWS)';

// 1. Statistik Utama Triage EWS
$total_skrining = (int) $pdo->query("SELECT COUNT(*) FROM skrining_asesmen")->fetchColumn();
$total_tinggi   = (int) $pdo->query("SELECT COUNT(*) FROM skrining_asesmen WHERE tingkat_risiko = 'Tinggi'")->fetchColumn();
$total_sedang   = (int) $pdo->query("SELECT COUNT(*) FROM skrining_asesmen WHERE tingkat_risiko = 'Sedang'")->fetchColumn();
$total_rendah   = (int) $pdo->query("SELECT COUNT(*) FROM skrining_asesmen WHERE tingkat_risiko = 'Rendah'")->fetchColumn();
$total_menunggu = (int) $pdo->query("SELECT COUNT(*) FROM pengajuan_konseling WHERE status = 'Menunggu'")->fetchColumn();
$total_selesai  = (int) $pdo->query("SELECT COUNT(*) FROM jadwal_konseling WHERE status = 'Selesai'")->fetchColumn();

// 2. Daftar Siswa Risiko Tinggi Terkini
$stmt_urgent = $pdo->query("
    SELECT s.id AS skrining_id, s.tanggal_pengisian, s.total_skor, s.skor_akademik, s.skor_psikososial, s.skor_bullying, s.tingkat_risiko, s.status_tindak_lanjut,
           sw.id AS siswa_id, sw.nisn, sw.nama_wali, sw.kontak_wali, u.nama_lengkap, u.no_hp AS no_hp_siswa, k.nama_kelas,
           (SELECT COUNT(*) FROM pengajuan_konseling p WHERE p.siswa_id = sw.id AND p.status IN ('Menunggu', 'Disetujui')) AS has_active_request
    FROM skrining_asesmen s
    JOIN siswa sw ON s.siswa_id = sw.id
    JOIN users u ON sw.user_id = u.id
    LEFT JOIN kelas k ON sw.kelas_id = k.id
    WHERE s.tingkat_risiko = 'Tinggi'
    ORDER BY s.tanggal_pengisian DESC LIMIT 5
");
$siswa_urgent = $stmt_urgent->fetchAll();

// 3. Jadwal Konseling Mendatang
$stmt_jadwal = $pdo->prepare("
    SELECT j.*, p.jenis_konseling, p.alasan_pengajuan, u.nama_lengkap AS nama_siswa, sw.nisn, k.nama_kelas
    FROM jadwal_konseling j
    JOIN pengajuan_konseling p ON j.pengajuan_id = p.id
    JOIN siswa sw ON p.siswa_id = sw.id
    JOIN users u ON sw.user_id = u.id
    LEFT JOIN kelas k ON sw.kelas_id = k.id
    WHERE j.tanggal_jadwal >= CURDATE() AND j.status = 'Dijadwalkan'
    ORDER BY j.tanggal_jadwal ASC, j.waktu_mulai ASC LIMIT 4
");
$stmt_jadwal->execute();
$jadwal_mendatang = $stmt_jadwal->fetchAll();

// 4. Radar Emosi Siswa Hari Ini (Check-in Harian Mood)
$stmt_mood_summary = $pdo->query("
    SELECT mood, emoji, COUNT(*) AS jumlah 
    FROM siswa_mood 
    WHERE tanggal = CURDATE() 
    GROUP BY mood, emoji
");
$mood_summary_today = $stmt_mood_summary->fetchAll();
$total_mood_today = 0;
foreach ($mood_summary_today as $ms) {
    $total_mood_today += (int) $ms['jumlah'];
}

// 5. Aktivitas Konseling & Asesmen Terkini
$activities = [];

$stmt_act_req = $pdo->query("
    SELECT p.tanggal_pengajuan, p.jenis_konseling, p.status, u.nama_lengkap
    FROM pengajuan_konseling p
    JOIN siswa s ON p.siswa_id = s.id
    JOIN users u ON s.user_id = u.id
    ORDER BY p.tanggal_pengajuan DESC LIMIT 2
");
while ($r = $stmt_act_req->fetch()) {
    $activities[] = [
        'title' => 'Pengajuan konseling: ' . htmlspecialchars($r['nama_lengkap']),
        'desc'  => 'Jenis: ' . $r['jenis_konseling'] . ' · Waktu: ' . date('H:i', strtotime($r['tanggal_pengajuan'])) . ' WIT',
        'status'=> $r['status'] === 'Menunggu' ? 'Menunggu' : $r['status'],
        'status_class' => $r['status'] === 'Menunggu' ? 'waiting' : 'done',
        'dot'   => 'blue',
        'time'  => strtotime($r['tanggal_pengajuan'])
    ];
}

$stmt_act_skr = $pdo->query("
    SELECT s.tanggal_pengisian, s.tingkat_risiko, u.nama_lengkap
    FROM skrining_asesmen s
    JOIN siswa sw ON s.siswa_id = sw.id
    JOIN users u ON sw.user_id = u.id
    ORDER BY s.tanggal_pengisian DESC LIMIT 2
");
while ($r = $stmt_act_skr->fetch()) {
    $activities[] = [
        'title' => 'Asesmen EWS: ' . htmlspecialchars($r['nama_lengkap']),
        'desc'  => 'Hasil: Risiko ' . $r['tingkat_risiko'] . ' · ' . date('H:i', strtotime($r['tanggal_pengisian'])) . ' WIT',
        'status'=> 'Ditinjau',
        'status_class' => 'review',
        'dot'   => $r['tingkat_risiko'] === 'Tinggi' ? 'orange' : 'green',
        'time'  => strtotime($r['tanggal_pengisian'])
    ];
}

usort($activities, fn($a, $b) => $b['time'] <=> $a['time']);
$activities = array_slice($activities, 0, 4);

// Format Tanggal Indonesia
$hari_indo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$nama_hari = $hari_indo[(int)date('w')];
$tgl_sekarang_indo = $nama_hari . ', ' . tgl_indo(date('Y-m-d'));

include __DIR__ . '/../includes/guru_header.php';
?>

<!-- HERO SECTION COMMAND CENTER -->
<section class="hero">
  <div class="hero-copy">
    <div class="eyebrow">🛡 &nbsp; Early Warning System (EWS) Command Center</div>
    <h1>Selamat Bertugas, <?= htmlspecialchars($user['nama_lengkap']) ?>!</h1>
    <p>
      Pusat pengawasan proaktif bimbingan konseling SMAN 5 MBD. Pantau deteksi dini permasalahan akademik, 
      kecemasan psikososial, dan indikasi perundungan untuk intervensi tepat sasaran.
    </p>
    <div class="hero-meta">
      <span><i></i>Pemantauan Real-Time Aktif</span>
      <span>▦ &nbsp; <?= $tgl_sekarang_indo ?> · Zona WIT</span>
    </div>
  </div>
  
  <div class="hero-actions">
    <a href="<?= BASE_URL ?>guru/pengajuan.php" class="hero-button primary">
      <span>▱</span>Pengajuan Siswa 
      <?php if ($total_menunggu > 0): ?>
        <b style="background:#dc354c; color:#fff; padding:2px 7px; border-radius:10px; font-size:10px; margin-left:6px;"><?= $total_menunggu ?></b>
      <?php endif; ?>
    </a>
    <a href="<?= BASE_URL ?>guru/skrining.php" class="hero-button">
      <span>▣</span>Monitoring Deteksi Dini
    </a>
    <a href="<?= BASE_URL ?>guru/laporan.php" class="hero-button">
      <span>▤</span>Cetak Rekapitulasi BK
    </a>
  </div>
  
  <div class="hero-decoration">
    <div class="orbit orbit-one"></div>
    <div class="orbit orbit-two"></div>
    <div class="pulse">♡</div>
  </div>
</section>

<!-- 4 STATS CARDS TRIAGE EWS -->
<section class="stats">
  <!-- Total Asesmen -->
  <article class="stat-card stat-blue">
    <div class="stat-top">
      <span>TOTAL ASESMEN MASUK</span>
      <i>♟</i>
    </div>
    <strong><?= $total_skrining ?></strong>
    <p><span class="tag blue-tag">EWS</span> Siswa telah mengisi instrumen</p>
    <a href="<?= BASE_URL ?>guru/skrining.php">Buka Semua Skrining →</a>
  </article>

  <!-- Risiko Tinggi -->
  <article class="stat-card stat-red">
    <div class="stat-top">
      <span>RISIKO TINGGI (ALARM)</span>
      <i>⬢</i>
    </div>
    <strong><?= $total_tinggi ?></strong>
    <p>Memerlukan intervensi &amp; pendampingan segera</p>
    <a href="<?= BASE_URL ?>guru/skrining.php?risiko=Tinggi">Prioritas Penanganan →</a>
  </article>

  <!-- Risiko Sedang -->
  <article class="stat-card stat-orange">
    <div class="stat-top">
      <span>RISIKO SEDANG</span>
      <i>▲</i>
    </div>
    <strong><?= $total_sedang ?></strong>
    <p>Perlu pemantauan &amp; bimbingan belajar</p>
    <a href="<?= BASE_URL ?>guru/skrining.php?risiko=Sedang">Daftar Siswa Sedang →</a>
  </article>

  <!-- Risiko Rendah -->
  <article class="stat-card stat-green">
    <div class="stat-top">
      <span>RISIKO RENDAH (AMAN)</span>
      <i>♧</i>
    </div>
    <strong><?= $total_rendah ?></strong>
    <p>Kondisi adaptif, normal &amp; stabil</p>
    <a href="<?= BASE_URL ?>guru/skrining.php?risiko=Rendah">Daftar Siswa Stabil →</a>
  </article>
</section>

<!-- RADAR EMOSIONAL SISWA HARI INI -->
<section class="panel mood-panel">
  <div class="mood-icon">♥</div>
  <div class="mood-content">
    <div class="panel-title-row">
      <div>
        <h2>Radar Emosional Siswa Hari Ini <span>(Check-in Portal Siswa)</span></h2>
        <p>Data suasana hati yang dibagikan siswa secara real-time melalui fitur Mood Tracker.</p>
      </div>
      <span class="count-badge">Total Check-in: <?= $total_mood_today ?> Siswa</span>
    </div>

    <?php if (empty($mood_summary_today)): ?>
      <div class="empty-state">
        ⓘ &nbsp; Belum ada siswa yang melakukan check-in suasana hati hari ini.
      </div>
    <?php else: ?>
      <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:14px;">
        <?php foreach ($mood_summary_today as $ms): ?>
          <div style="display:flex; align-items:center; gap:8px; padding:6px 14px; background:#f4f8ff; border:1px solid #d7e6fc; border-radius:20px; font-size:12px;">
            <span style="font-size:18px;"><?= $ms['emoji'] ?></span>
            <strong style="color:var(--navy); text-transform:capitalize;"><?= htmlspecialchars($ms['mood']) ?></strong>
            <span style="background:#2878ed; color:#fff; border-radius:10px; padding:1px 7px; font-size:10px; font-weight:700;">
              <?= $ms['jumlah'] ?>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- PERINGATAN DINI RISIKO TINGGI (RISK SECTION) -->
<section class="risk-section">
  <div class="risk-heading">
    <div>
      <span class="alert-symbol">♟</span>
      <b>PERINGATAN DINI: Siswa Terindikasi Risiko Tinggi Terkini</b>
    </div>
    <a href="<?= BASE_URL ?>guru/skrining.php?risiko=Tinggi">Lihat Semua (<?= $total_tinggi ?>) →</a>
  </div>
  
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Tanggal Asesmen</th>
          <th>Identitas Siswa</th>
          <th>Kelas</th>
          <th>Skor Akademik</th>
          <th>Skor Psikososial</th>
          <th>Skor Bullying</th>
          <th>Total Skor</th>
          <th>Kontak Orang Tua / Wali</th>
          <th>Tindakan Cepat</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($siswa_urgent)): ?>
          <tr>
            <td colspan="9" style="text-align: center; color: #168d5a; padding: 24px; font-weight: 500;">
              ✓ Kondisi Terpantau Aman: Tidak ada siswa terindikasi risiko tinggi yang belum tertangani saat ini.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($siswa_urgent as $su): ?>
            <tr>
              <td><?= date('d M Y', strtotime($su['tanggal_pengisian'])) ?></td>
              <td>
                <div class="student-cell">
                  <span class="student-avatar"><?= strtoupper(substr($su['nama_lengkap'], 0, 1)) ?></span>
                  <div>
                    <b><?= htmlspecialchars($su['nama_lengkap']) ?></b>
                    <small>NISN: <?= htmlspecialchars($su['nisn']) ?></small>
                  </div>
                </div>
              </td>
              <td><?= htmlspecialchars($su['nama_kelas'] ?? 'Umum') ?></td>
              <td><span class="score"><?= $su['skor_akademik'] ?></span></td>
              <td><span class="score"><?= $su['skor_psikososial'] ?></span></td>
              <td><span class="score <?= $su['skor_bullying'] >= 10 ? 'danger' : '' ?>"><?= $su['skor_bullying'] ?></span></td>
              <td><span class="risk-pill"><?= $su['total_skor'] ?> · Tinggi</span></td>
              <td>
                <span style="font-size:10px;">
                  <?= htmlspecialchars($su['nama_wali'] ?: 'Wali') ?>: 
                  <code><?= htmlspecialchars($su['kontak_wali'] ?: '-') ?></code>
                </span>
              </td>
              <td>
                <a class="action-btn" href="<?= BASE_URL ?>guru/skrining.php?search=<?= urlencode($su['nama_lengkap']) ?>">
                  Tinjau Kasus ↗
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  
  <div class="table-note">
    Data di atas merupakan peringatan proaktif sistem Early Warning System (EWS). Prioritaskan tindak lanjut pendampingan dan penanganan sesuai kode etik bimbingan konseling.
  </div>
</section>

<!-- LOWER GRID: AKTIVITAS & AGENDA -->
<section class="lower-grid">
  <!-- Panel 1: Aktivitas Konseling Terbaru -->
  <article class="panel activity-panel">
    <div class="panel-heading">
      <h2>▤ &nbsp;Aktivitas Konseling Terbaru</h2>
      <a href="<?= BASE_URL ?>guru/riwayat_konseling.php">Lihat Riwayat →</a>
    </div>
    
    <?php if (empty($activities)): ?>
      <div style="padding: 24px 0; text-align: center; color: var(--muted); font-size: 11px;">
        Belum ada aktivitas konseling yang tercatat.
      </div>
    <?php else: ?>
      <?php foreach ($activities as $act): ?>
        <div class="activity-row">
          <span class="activity-dot <?= $act['dot'] ?>"></span>
          <div>
            <b><?= $act['title'] ?></b>
            <p><?= $act['desc'] ?></p>
            <small><?= tgl_indo(date('Y-m-d', $act['time'])) ?></small>
          </div>
          <span class="status <?= $act['status_class'] ?>"><?= $act['status'] ?></span>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </article>

  <!-- Panel 2: Agenda Konseling -->
  <article class="panel agenda-panel">
    <div class="panel-heading">
      <h2>▦ &nbsp;Agenda Konseling Mendatang</h2>
      <a href="<?= BASE_URL ?>guru/jadwal.php">Lihat Jadwal →</a>
    </div>

    <?php if (empty($jadwal_mendatang)): ?>
      <div style="padding: 24px 0; text-align: center; color: var(--muted); font-size: 11px;">
        Belum ada agenda konseling terjadwal dalam waktu dekat.
      </div>
    <?php else: ?>
      <?php foreach ($jadwal_mendatang as $jm): ?>
        <?php 
          $tgl_ts = strtotime($jm['tanggal_jadwal']);
          $tgl_day = date('d', $tgl_ts);
          $tgl_mon = strtoupper(date('M', $tgl_ts));
        ?>
        <div class="agenda-item">
          <div class="agenda-date">
            <b><?= $tgl_day ?></b>
            <small><?= $tgl_mon ?></small>
          </div>
          <div style="flex:1;">
            <b>Konseling <?= htmlspecialchars($jm['jenis_konseling']) ?>: <?= htmlspecialchars($jm['nama_siswa']) ?></b>
            <p><?= htmlspecialchars($jm['tempat_ruangan'] ?? 'Ruang BK') ?> &bull; <?= substr($jm['waktu_mulai'], 0, 5) ?> - <?= substr($jm['waktu_selesai'], 0, 5) ?> WIT</p>
            <span class="agenda-tag">Terjadwal</span>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </article>
</section>

<?php include __DIR__ . '/../includes/guru_footer.php'; ?>
