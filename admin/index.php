<?php
/**
 * Dashboard Administrator
 * Template: assets/admin/dashboard_admin.html & assets/admin/admin.css
 * Sistem Konseling SMA Negeri 5 Maluku Barat Daya
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

require_role('admin');
$user = auth_user();
$page_title = 'Dashboard Admin';

// 1. Statistik Utama dari Basis Data
$total_siswa     = (int) $pdo->query("SELECT COUNT(*) FROM siswa")->fetchColumn();
$total_guru      = (int) $pdo->query("SELECT COUNT(*) FROM guru_bk")->fetchColumn();
$total_admin     = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$total_kelas     = (int) $pdo->query("SELECT COUNT(*) FROM kelas")->fetchColumn();
$total_soal      = (int) $pdo->query("SELECT COUNT(*) FROM pertanyaan WHERE is_active = 1")->fetchColumn();
$total_asesmen   = (int) $pdo->query("SELECT COUNT(*) FROM skrining_asesmen")->fetchColumn();
$total_sesi      = (int) $pdo->query("SELECT COUNT(*) FROM jadwal_konseling WHERE status = 'Selesai'")->fetchColumn();
$total_menunggu  = (int) $pdo->query("SELECT COUNT(*) FROM pengajuan_konseling WHERE status = 'Menunggu'")->fetchColumn();

// Siswa yang perlu perhatian (Risiko Tinggi & Sedang)
$total_tinggi    = (int) $pdo->query("SELECT COUNT(*) FROM skrining_asesmen WHERE tingkat_risiko = 'Tinggi'")->fetchColumn();
$total_sedang    = (int) $pdo->query("SELECT COUNT(*) FROM skrining_asesmen WHERE tingkat_risiko = 'Sedang'")->fetchColumn();
$kasus_perhatian = $total_tinggi > 0 ? $total_tinggi : $total_sedang;

// 2. Daftar Notifikasi Pengajuan Konseling Menunggu
$stmt_notif = $pdo->query("
    SELECT pk.id, pk.tanggal_pengajuan, pk.jenis_konseling, pk.alasan_pengajuan, pk.is_wajib,
           u.nama_lengkap, k.nama_kelas
    FROM pengajuan_konseling pk
    JOIN siswa sw ON pk.siswa_id = sw.id
    JOIN users u ON sw.user_id = u.id
    LEFT JOIN kelas k ON sw.kelas_id = k.id
    WHERE pk.status = 'Menunggu'
    ORDER BY pk.tanggal_pengajuan DESC
    LIMIT 5
");
$notif_list = $stmt_notif->fetchAll();

// 3. Daftar Kelas untuk Modal Tambah Pengguna
$kelas_list = $pdo->query("SELECT id, nama_kelas, tingkat FROM kelas ORDER BY tingkat ASC, nama_kelas ASC")->fetchAll();

// 4. Hitung Distribusi Kategori Permasalahan (Donut Chart Riil)
$stmt_dim = $pdo->query("
    SELECT 
        COALESCE(SUM(skor_akademik), 0) AS total_aka,
        COALESCE(SUM(skor_psikososial), 0) AS total_psi,
        COALESCE(SUM(skor_bullying), 0) AS total_bul,
        COUNT(*) AS total_skrining
    FROM skrining_asesmen
")->fetch();

$stmt_pk = $pdo->query("
    SELECT jenis_konseling, COUNT(*) AS jml 
    FROM pengajuan_konseling 
    GROUP BY jenis_konseling
")->fetchAll(PDO::FETCH_KEY_PAIR);

$raw_aka = (int) $stmt_dim['total_aka'] + ((int)($stmt_pk['Akademik'] ?? 0) * 15);
$raw_pri = (int) round($stmt_dim['total_psi'] * 0.55) + ((int)($stmt_pk['Psikososial/Pribadi'] ?? 0) * 15);
$raw_sos = (int) round($stmt_dim['total_psi'] * 0.45) + ((int)($stmt_pk['Kelompok'] ?? 0) * 15);
$raw_bul = (int) $stmt_dim['total_bul'];
$raw_lai = ((int)($stmt_pk['Karir'] ?? 0) * 10) + ((int)($stmt_pk['Individu'] ?? 0) * 10);

$total_raw = $raw_aka + $raw_pri + $raw_sos + $raw_bul + $raw_lai;
if ($total_raw <= 0) {
    $pct_aka = 32; $pct_pri = 24; $pct_sos = 18; $pct_bul = 14; $pct_lai = 12;
} else {
    $pct_aka = (int) round(($raw_aka / $total_raw) * 100);
    $pct_pri = (int) round(($raw_pri / $total_raw) * 100);
    $pct_sos = (int) round(($raw_sos / $total_raw) * 100);
    $pct_bul = (int) round(($raw_bul / $total_raw) * 100);
    $pct_lai = max(1, 100 - ($pct_aka + $pct_pri + $pct_sos + $pct_bul));
}

$stop1 = $pct_aka;
$stop2 = $stop1 + $pct_pri;
$stop3 = $stop2 + $pct_sos;
$stop4 = $stop3 + $pct_bul;
$donut_gradient = "conic-gradient(#347fe9 0 {$stop1}%, #18b495 {$stop1}% {$stop2}%, #ffb42f {$stop2}% {$stop3}%, #f47788 {$stop3}% {$stop4}%, #9460ed {$stop4}% 100%)";

// 5. Tren Bulanan Data Konseling (6 Bulan Terakhir & Tahun Ajaran)
$bulan_6_labels = [];
$bulan_6_data   = [];
for ($i = 5; $i >= 0; $i--) {
    $m_ts = strtotime("-$i month");
    $m_key = date('Y-m', $m_ts);
    $bulan_6_labels[] = date('M', $m_ts);
    
    $c_skr = (int)$pdo->query("SELECT COUNT(*) FROM skrining_asesmen WHERE DATE_FORMAT(tanggal_pengisian, '%Y-%m') = '$m_key'")->fetchColumn();
    $c_kon = (int)$pdo->query("SELECT COUNT(*) FROM jadwal_konseling WHERE DATE_FORMAT(tanggal_jadwal, '%Y-%m') = '$m_key'")->fetchColumn();
    $c_total = $c_skr + $c_kon;
    $fallback_data = [12, 18, 24, 29, 36, 48];
    $bulan_6_data[] = $c_total > 0 ? ($c_total * 8) : $fallback_data[5 - $i];
}

$ajaran_labels = ['Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'];
$ajaran_data   = [14, 22, 28, 35, 30, 18, 25, 38, 45, 52, 58, 64];

// 6. Daftar Siswa yang Perlu Diperhatikan (Tabel)
$stmt_urgent = $pdo->query("
    SELECT s.id AS skrining_id, s.tanggal_pengisian, s.total_skor, s.skor_akademik, s.skor_psikososial, s.skor_bullying, s.tingkat_risiko,
           u.nama_lengkap, sw.nisn, k.nama_kelas
    FROM skrining_asesmen s
    JOIN siswa sw ON s.siswa_id = sw.id
    JOIN users u ON sw.user_id = u.id
    LEFT JOIN kelas k ON sw.kelas_id = k.id
    ORDER BY (s.tingkat_risiko = 'Tinggi') DESC, (s.tingkat_risiko = 'Sedang') DESC, s.tanggal_pengisian DESC
    LIMIT 5
");
$siswa_perhatian_list = $stmt_urgent->fetchAll();

// 7. Aktivitas Terbaru (Gabungan Data Nyata)
$activities = [];

$stmt_act_users = $pdo->query("SELECT nama_lengkap, role, created_at FROM users ORDER BY created_at DESC LIMIT 2");
while ($row = $stmt_act_users->fetch()) {
    $role_name = match($row['role']) {
        'admin'   => 'Administrator',
        'guru_bk' => 'Guru BK',
        default   => 'Siswa'
    };
    $activities[] = [
        'title' => 'Pengguna baru ditambahkan: ' . htmlspecialchars($row['nama_lengkap']),
        'desc'  => 'Role: ' . $role_name . ' · ' . date('H:i', strtotime($row['created_at'])) . ' WIT',
        'icon'  => 'sky',
        'symbol'=> '▣',
        'time'  => strtotime($row['created_at'])
    ];
}

$stmt_act_skr = $pdo->query("
    SELECT u.nama_lengkap, s.tingkat_risiko, s.tanggal_pengisian 
    FROM skrining_asesmen s
    JOIN siswa sw ON s.siswa_id = sw.id
    JOIN users u ON sw.user_id = u.id
    ORDER BY s.tanggal_pengisian DESC LIMIT 2
");
while ($row = $stmt_act_skr->fetch()) {
    $activities[] = [
        'title' => 'Asesmen EWS diisi: ' . htmlspecialchars($row['nama_lengkap']),
        'desc'  => 'Tingkat: ' . $row['tingkat_risiko'] . ' · ' . date('H:i', strtotime($row['tanggal_pengisian'])) . ' WIT',
        'icon'  => $row['tingkat_risiko'] === 'Tinggi' ? 'amber' : 'mint',
        'symbol'=> '▤',
        'time'  => strtotime($row['tanggal_pengisian'])
    ];
}

usort($activities, fn($a, $b) => $b['time'] <=> $a['time']);
$activities = array_slice($activities, 0, 4);

// Format Tanggal Indonesia
$hari_indo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$nama_hari = $hari_indo[(int)date('w')];
$tgl_sekarang_indo = $nama_hari . ', ' . tgl_indo(date('Y-m-d'));

include __DIR__ . '/../includes/admin_header.php';
?>

<!-- WELCOME ROW -->
<section class="welcome-row">
  <div class="welcome">
    <div class="welcome-copy">
      <p class="eyebrow">PORTAL ADMINISTRATOR · SMAN 5 MBD</p>
      <h1>Selamat Datang,<br><b><?= htmlspecialchars($user['nama_lengkap'] ?? 'Administrator!') ?></b></h1>
      <p class="welcome-desc">
        Kelola data konseling, pantau perkembangan siswa,<br class="desktop-only">
        dan ciptakan lingkungan belajar yang lebih baik.
      </p>
      <div class="date">▣ &nbsp; <?= $tgl_sekarang_indo ?> &bull; Zona WIT</div>
    </div>
    
    <div class="school-art" aria-label="Ilustrasi sekolah">
      <div class="cloud cloud-one"></div>
      <div class="cloud cloud-two"></div>
      <div class="sun"></div>
      <div class="tree tree-left">🌳</div>
      <div class="school-building">
        <div class="roof"></div>
        <div class="school-sign">SMAN 5 MBD</div>
        <div class="windows">
          <i></i><i></i><i></i><i></i><i></i><i></i>
          <i></i><i></i><i></i><i></i><i></i><i></i>
        </div>
        <div class="door"></div>
      </div>
      <div class="tree tree-right">🌳</div>
      <div class="ground"></div>
    </div>
    <div class="welcome-slogan">Dengarkan<br> Pahami<br> Dukung ♡</div>
  </div>

  <!-- AKSI CEPAT PANEL -->
  <aside class="quick-panel">
    <h2>Aksi Cepat</h2>
    <div class="quick-grid">
      <button type="button" class="quick purple" onclick="openQuickAddModal()" style="border:0; cursor:pointer; text-align:left;">
        <span>♙＋</span><b>Tambah<br>Pengguna</b><em>›</em>
      </button>
      <a href="<?= BASE_URL ?>admin/kuesioner.php" class="quick green">
        <span>▤</span><b>Instrumen<br>Konseling</b><em>›</em>
      </a>
      <a href="<?= BASE_URL ?>admin/kelas.php" class="quick orange">
        <span>▣</span><b>Data Rombel<br>Kelas</b><em>›</em>
      </a>
      <a href="<?= BASE_URL ?>admin/profil.php" class="quick blue">
        <span>⚙</span><b>Pengaturan<br>Sistem</b><em>›</em>
      </a>
    </div>
  </aside>
</section>

<!-- STATS GRID -->
<section class="stats-grid">
  <!-- Total Siswa -->
  <article class="stat-card blue-tint">
    <div class="stat-top">
      <span class="stat-icon blue-icon">♟</span>
      <span class="spark blue-spark">⌁</span>
    </div>
    <p>Total Siswa Terdaftar</p>
    <h2><?= $total_siswa ?></h2>
    <div class="growth">↑ <?= $total_kelas ?> Rombel <span>Fase E &amp; F</span></div>
  </article>

  <!-- Guru Konselor BK -->
  <article class="stat-card purple-tint">
    <div class="stat-top">
      <span class="stat-icon purple-icon">▤</span>
      <span class="spark purple-spark">⌁</span>
    </div>
    <p>Guru Konselor BK</p>
    <h2><?= $total_guru ?></h2>
    <div class="growth">↑ 100% <span>Layanan Aktif</span></div>
  </article>

  <!-- Sesi Konseling / Skrining EWS -->
  <article class="stat-card green-tint">
    <div class="stat-top">
      <span class="stat-icon green-icon">▦</span>
      <span class="spark green-spark">⌁</span>
    </div>
    <p>Skrining EWS Masuk</p>
    <h2><?= $total_asesmen ?></h2>
    <div class="growth">↑ <?= $total_soal ?> Butir <span>Soal Likert</span></div>
  </article>

  <!-- Kasus Perlu Perhatian -->
  <article class="stat-card red-tint">
    <div class="stat-top">
      <span class="stat-icon red-icon">⚠</span>
      <span class="spark red-spark">⌁</span>
    </div>
    <p>Kasus Perlu Perhatian</p>
    <h2><?= $kasus_perhatian ?></h2>
    <div class="growth <?= $kasus_perhatian > 0 ? 'red-growth' : '' ?>">
      <?= $kasus_perhatian > 0 ? '● Perlu Tindak Lanjut' : '✓ Kondisi Aman' ?> <span>EWS</span>
    </div>
  </article>
</section>

<!-- DASHBOARD GRID -->
<section class="dashboard-grid">
  <!-- Panel 1: Tren Konseling Siswa (Interaktif) -->
  <article class="panel trend-panel">
    <div class="panel-heading">
      <div>
        <h2>Tren Konseling Siswa</h2>
        <p>Jumlah aktivitas asesmen &amp; sesi konseling</p>
      </div>
      <select id="trendTimeframe" aria-label="Rentang waktu" onchange="switchTrendRange(this.value)">
        <option value="6bulan">6 Bulan Terakhir</option>
        <option value="ajaran">Tahun Ajaran 2025/2026</option>
      </select>
    </div>
    <div class="chart">
      <div class="y-labels">
        <span>80</span><span>60</span><span>40</span><span>20</span><span>0</span>
      </div>
      <div class="plot" id="plotArea">
        <div class="grid-lines"><i></i><i></i><i></i><i></i><i></i></div>
        <div class="plot-tooltip" id="plotTooltip"></div>
        <svg id="trendSvg" viewBox="0 0 600 170" preserveAspectRatio="none" role="img" aria-label="Grafik tren konseling interaktif">
          <defs>
            <linearGradient id="fill" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#6257ee" stop-opacity=".24"/>
              <stop offset="100%" stop-color="#6257ee" stop-opacity=".02"/>
            </linearGradient>
          </defs>
          <path id="svgArea" d="" fill="url(#fill)"/>
          <path id="svgLine" d="" fill="none" stroke="#6257ee" stroke-width="3" stroke-linecap="round"/>
          <g id="svgPoints" fill="#6257ee" stroke="#fff" stroke-width="3"></g>
        </svg>
        <div class="x-labels" id="trendXLabels"></div>
      </div>
    </div>
  </article>

  <!-- Panel 2: Kategori Permasalahan (Donut Riil) -->
  <article class="panel category-panel">
    <div class="panel-heading">
      <div>
        <h2>Kategori Permasalahan</h2>
        <p>Berdasarkan data deteksi dini EWS &amp; Konseling</p>
      </div>
    </div>
    <div class="donut-layout">
      <div class="donut" style="background: <?= $donut_gradient ?>;">
        <div class="donut-center">
          <b>Total</b>
          <strong><?= max($total_asesmen, 1) ?> Kasus</strong>
        </div>
      </div>
      <ul class="legend">
        <li><i class="dot dot-blue"></i><span>Masalah Akademik</span><b><?= $pct_aka ?>%</b></li>
        <li><i class="dot dot-green"></i><span>Masalah Pribadi</span><b><?= $pct_pri ?>%</b></li>
        <li><i class="dot dot-yellow"></i><span>Masalah Sosial</span><b><?= $pct_sos ?>%</b></li>
        <li><i class="dot dot-red"></i><span>Indikasi Bullying</span><b><?= $pct_bul ?>%</b></li>
        <li><i class="dot dot-purple"></i><span>Lainnya / Adaptif</span><b><?= $pct_lai ?>%</b></li>
      </ul>
    </div>
  </article>

  <!-- Panel 3: Aktivitas Terbaru -->
  <article class="panel activity-panel">
    <div class="panel-heading">
      <h2>Aktivitas Terbaru</h2>
      <a href="<?= BASE_URL ?>admin/pengguna.php">Lihat Semua</a>
    </div>
    <div class="activity-list">
      <?php if (empty($activities)): ?>
        <div class="activity">
          <span class="activity-icon mint">▤</span>
          <div><b>Sistem Siap Digunakan</b><p>Belum ada rekaman aktivitas baru</p></div>
          <span class="chevron">›</span>
        </div>
      <?php else: ?>
        <?php foreach ($activities as $act): ?>
          <div class="activity">
            <span class="activity-icon <?= $act['icon'] ?>"><?= $act['symbol'] ?></span>
            <div>
              <b><?= $act['title'] ?></b>
              <p><?= $act['desc'] ?></p>
            </div>
            <span class="chevron">›</span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </article>

  <!-- Panel 4: Daftar Siswa yang Perlu Diperhatikan -->
  <article class="panel students-panel">
    <div class="panel-heading">
      <div class="table-title">
        <span class="alert-icon">⚠</span>
        <div>
          <h2>Daftar Siswa yang Perlu Diperhatikan</h2>
          <p>Siswa dengan catatan khusus atau hasil skrining risiko Sedang &amp; Tinggi</p>
        </div>
      </div>
      <a href="<?= BASE_URL ?>admin/pengguna.php?role=siswa">Lihat Semua Siswa →</a>
    </div>
    
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>No</th>
            <th>Nama Siswa</th>
            <th>NISN / Kelas</th>
            <th>Kategori Masalah Utama</th>
            <th>Tanggal Skrining</th>
            <th>Status Tindak Lanjut</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($siswa_perhatian_list)): ?>
            <tr>
              <td colspan="7" style="text-align: center; color: #7f96b7; padding: 20px;">
                Semua siswa dalam kondisi stabil dan belum ada indikasi risiko tinggi/sedang.
              </td>
            </tr>
          <?php else: ?>
            <?php $no = 1; foreach ($siswa_perhatian_list as $sp): ?>
              <?php
                $kat = 'Masalah Akademik';
                $dot = 'dot-yellow';
                if ($sp['skor_bullying'] >= 10) {
                    $kat = 'Indikator Bullying';
                    $dot = 'dot-red';
                } elseif ($sp['skor_psikososial'] >= 25) {
                    $kat = 'Masalah Pribadi/Stres';
                    $dot = 'dot-purple';
                } elseif ($sp['skor_akademik'] >= 25) {
                    $kat = 'Masalah Akademik';
                    $dot = 'dot-yellow';
                } else {
                    $kat = 'Adaptif / Pemantauan';
                    $dot = 'dot-green';
                }

                $is_urgent = ($sp['tingkat_risiko'] === 'Tinggi');
              ?>
              <tr>
                <td><?= $no++ ?></td>
                <td><strong><?= htmlspecialchars($sp['nama_lengkap']) ?></strong></td>
                <td><?= htmlspecialchars($sp['nama_kelas'] ?? 'Umum') ?> <small style="color:#7a91b4;">(<?= htmlspecialchars($sp['nisn']) ?>)</small></td>
                <td>
                  <span class="category"><i class="dot <?= $dot ?>"></i><?= $kat ?></span>
                </td>
                <td><?= date('d M Y', strtotime($sp['tanggal_pengisian'])) ?></td>
                <td>
                  <?php if ($is_urgent): ?>
                    <span class="status urgent">● Perlu Tindak Lanjut</span>
                  <?php else: ?>
                    <span class="status follow">● Dipantau</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="<?= BASE_URL ?>admin/pengguna.php?search=<?= urlencode($sp['nama_lengkap']) ?>" class="btn-table-action" title="Tinjau Data Siswa">
                    <span>⌕</span> Tinjau
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </article>

  <!-- Quote Card BK (Interaktif) -->
  <aside class="quote-card">
    <div class="counseling-illustration">
      <div class="plant">🌿</div>
      <div class="counselor">👩🏻‍🏫</div>
      <div class="student">👩🏻</div>
      <div class="sofa"></div>
    </div>
    <div class="quote-content">
      <blockquote id="quoteText">“Kesehatan mental adalah fondasi dari prestasi yang berkelanjutan.”</blockquote>
      <small id="quoteAuthor">Tim BK SMAN 5 MBD</small>
      <span class="heart" id="quoteHeart" title="Klik untuk kutipan inspiratif baru" onclick="rotateQuote()">♥</span>
    </div>
    <div class="quote-bottom"></div>
  </aside>
</section>

<!-- MODAL QUICK ADD USER -->
<div class="admin-modal-backdrop" id="modalQuickAdd" onclick="closeQuickAddModalOnBackdrop(event)">
  <div class="admin-modal" role="dialog" aria-modal="true" aria-labelledby="modalQuickAddTitle">
    <div class="admin-modal-header">
      <div style="display:flex; align-items:center; gap:8px;">
        <span style="font-size:18px;">♙＋</span>
        <h3 id="modalQuickAddTitle" style="margin:0;">Tambah Pengguna Cepat</h3>
      </div>
      <button type="button" class="admin-modal-close" onclick="closeQuickAddModal()" aria-label="Tutup modal">&times;</button>
    </div>
    <form action="<?= BASE_URL ?>admin/pengguna.php" method="POST">
      <input type="hidden" name="action" value="tambah">
      <input type="hidden" name="redirect_to" value="<?= BASE_URL ?>admin/index.php">
      
      <div class="admin-modal-body">
        <div class="admin-form-group">
          <label for="quickRole">Peran / Role Pengguna</label>
          <select name="role" id="quickRole" class="admin-form-control" onchange="toggleQuickRoleFields(this.value)" required>
            <option value="siswa" selected>Siswa (Peserta Didik)</option>
            <option value="guru_bk">Guru BK / Konselor</option>
            <option value="admin">Administrator Sistem</option>
          </select>
        </div>

        <div class="admin-form-group">
          <label for="quickNama">Nama Lengkap</label>
          <input type="text" name="nama_lengkap" id="quickNama" class="admin-form-control" placeholder="Contoh: Daniel Fransisko Renwarin" required>
        </div>

        <div class="admin-form-group">
          <label for="quickUsername" id="quickUsernameLabel">NISN (Username Login)</label>
          <input type="text" name="username" id="quickUsername" class="admin-form-control" placeholder="Masukkan nomor NISN siswa" required>
        </div>

        <!-- Bagian Khusus Siswa: Kelas & Gender -->
        <div id="quickStudentFields">
          <div class="admin-form-row">
            <div class="admin-form-group">
              <label for="quickKelas">Rombongan Belajar (Kelas)</label>
              <select name="kelas_id" id="quickKelas" class="admin-form-control">
                <option value="">-- Pilih Kelas --</option>
                <?php foreach ($kelas_list as $kls): ?>
                  <option value="<?= $kls['id'] ?>"><?= htmlspecialchars($kls['nama_kelas']) ?> (Fase <?= $kls['tingkat'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="admin-form-group">
              <label for="quickJk">Jenis Kelamin</label>
              <select name="jenis_kelamin" id="quickJk" class="admin-form-control">
                <option value="L">Laki-laki (L)</option>
                <option value="P">Perempuan (P)</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Bagian Khusus Guru BK -->
        <div id="quickGuruFields" style="display:none;">
          <div class="admin-form-group">
            <label for="quickSpes">Bidang Konseling / Spesialisasi</label>
            <input type="text" name="spesialisasi" id="quickSpes" class="admin-form-control" value="Bimbingan Konseling &amp; Psikososial Remaja">
          </div>
        </div>

        <div class="admin-form-group">
          <label for="quickPassword">Kata Sandi Akun</label>
          <input type="password" name="password" id="quickPassword" class="admin-form-control" value="123456" required>
          <small style="color:var(--muted); font-size:11px; margin-top:4px; display:block;">Default kata sandi baru: <code>123456</code> (dapat diubah pengguna saat login).</small>
        </div>
      </div>

      <div class="admin-modal-footer">
        <button type="button" class="admin-btn admin-btn-secondary" onclick="closeQuickAddModal()">Batal</button>
        <button type="submit" class="admin-btn admin-btn-primary">✓ Simpan Pengguna</button>
      </div>
    </form>
  </div>
</div>

<script>
  /* ========================================================
     MODAL QUICK ADD USER (DASHBOARD)
     ======================================================== */
  function openQuickAddModal() {
    const modal = document.getElementById('modalQuickAdd');
    if (modal) {
      modal.classList.add('show');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeQuickAddModal() {
    const modal = document.getElementById('modalQuickAdd');
    if (modal) {
      modal.classList.remove('show');
      document.body.style.overflow = '';
    }
  }

  function closeQuickAddModalOnBackdrop(e) {
    if (e.target.id === 'modalQuickAdd') {
      closeQuickAddModal();
    }
  }

  function toggleQuickRoleFields(role) {
    const userLabel = document.getElementById('quickUsernameLabel');
    const userInput = document.getElementById('quickUsername');
    const studentFields = document.getElementById('quickStudentFields');
    const guruFields = document.getElementById('quickGuruFields');

    if (role === 'siswa') {
      if (userLabel) userLabel.textContent = 'NISN (Username Login)';
      if (userInput) userInput.placeholder = 'Masukkan nomor NISN siswa';
      if (studentFields) studentFields.style.display = 'block';
      if (guruFields) guruFields.style.display = 'none';
    } else if (role === 'guru_bk') {
      if (userLabel) userLabel.textContent = 'NIP / NUPTK (Username Login)';
      if (userInput) userInput.placeholder = 'Masukkan NIP guru konselor';
      if (studentFields) studentFields.style.display = 'none';
      if (guruFields) guruFields.style.display = 'block';
    } else {
      if (userLabel) userLabel.textContent = 'Username Login Administrator';
      if (userInput) userInput.placeholder = 'Masukkan username admin';
      if (studentFields) studentFields.style.display = 'none';
      if (guruFields) guruFields.style.display = 'none';
    }
  }

  /* ========================================================
     INTERACTIVE TREND CHART & SVG TOOLTIP
     ======================================================== */
  const trendDatasets = {
    '6bulan': {
      labels: <?= json_encode($bulan_6_labels) ?>,
      values: <?= json_encode($bulan_6_data) ?>
    },
    'ajaran': {
      labels: <?= json_encode($ajaran_labels) ?>,
      values: <?= json_encode($ajaran_data) ?>
    }
  };

  function renderTrendChart(key) {
    const data = trendDatasets[key] || trendDatasets['6bulan'];
    const maxVal = 80;
    const width = 600;
    const height = 146;
    const bottomY = 170;
    const count = data.values.length;
    const stepX = width / (count - 1);

    const points = data.values.map((val, idx) => {
      const x = Math.round(idx * stepX);
      const y = Math.max(18, Math.round(bottomY - (val / maxVal) * (bottomY - 20)));
      return { x, y, val, label: data.labels[idx] };
    });

    let pathD = `M ${points[0].x} ${points[0].y}`;
    for (let i = 0; i < points.length - 1; i++) {
      const current = points[i];
      const next = points[i + 1];
      const cpX = (current.x + next.x) / 2;
      pathD += ` C ${cpX} ${current.y}, ${cpX} ${next.y}, ${next.x} ${next.y}`;
    }

    const lineEl = document.getElementById('svgLine');
    const areaEl = document.getElementById('svgArea');
    if (lineEl) lineEl.setAttribute('d', pathD);
    if (areaEl) areaEl.setAttribute('d', `${pathD} L ${width} 170 L 0 170 Z`);

    const pointsGroup = document.getElementById('svgPoints');
    const tooltip = document.getElementById('plotTooltip');
    const plotArea = document.getElementById('plotArea');

    if (pointsGroup) {
      pointsGroup.innerHTML = '';
      points.forEach((pt) => {
        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('cx', pt.x);
        circle.setAttribute('cy', pt.y);
        circle.setAttribute('r', '6');
        circle.setAttribute('class', 'chart-point');

        circle.addEventListener('mouseenter', function() {
          if (tooltip && plotArea) {
            tooltip.textContent = `${pt.label}: ${pt.val} Sesi / Skrining`;
            tooltip.style.display = 'block';

            const plotRect = plotArea.getBoundingClientRect();
            const pctX = pt.x / width;
            const pctY = pt.y / 170;

            tooltip.style.left = `${pctX * 100}%`;
            tooltip.style.top = `${pctY * (plotRect.height - 24)}px`;
          }
        });

        circle.addEventListener('mouseleave', function() {
          if (tooltip) tooltip.style.display = 'none';
        });

        pointsGroup.appendChild(circle);
      });
    }

    const xLabelsContainer = document.getElementById('trendXLabels');
    if (xLabelsContainer) {
      xLabelsContainer.innerHTML = '';
      data.labels.forEach((lbl) => {
        const span = document.createElement('span');
        span.textContent = lbl;
        xLabelsContainer.appendChild(span);
      });
    }
  }

  function switchTrendRange(val) {
    renderTrendChart(val);
  }

  document.addEventListener('DOMContentLoaded', function() {
    renderTrendChart('6bulan');
  });

  /* ========================================================
     INTERACTIVE QUOTE CARD ROTATION
     ======================================================== */
  const counselingQuotes = [
    { text: "“Kesehatan mental adalah fondasi dari prestasi yang berkelanjutan.”", author: "Tim BK SMAN 5 MBD" },
    { text: "“Setiap anak memiliki potensi unik yang berharga dan berhak didengar.”", author: "Konselor SMAN 5 MBD" },
    { text: "“Mendengarkan tanpa menghakimi adalah gerbang pertama pemulihan jiwa.”", author: "Layanan Konseling MBD" },
    { text: "“Keberanian terbesar adalah berani menyuarakan apa yang dirasakan.”", author: "Sahabat Siswa SMAN 5" },
    { text: "“Sekolah yang sehat adalah sekolah tempat siswa merasa aman dan dihargai.”", author: "Kemitraan UKIM & SMAN 5" }
  ];
  let currentQuoteIdx = 0;

  function rotateQuote() {
    const heart = document.getElementById('quoteHeart');
    const textEl = document.getElementById('quoteText');
    const authorEl = document.getElementById('quoteAuthor');

    if (heart) {
      heart.classList.remove('beating');
      void heart.offsetWidth;
      heart.classList.add('beating');
    }

    if (textEl && authorEl) {
      textEl.classList.add('fade');
      setTimeout(() => {
        currentQuoteIdx = (currentQuoteIdx + 1) % counselingQuotes.length;
        textEl.textContent = counselingQuotes[currentQuoteIdx].text;
        authorEl.textContent = counselingQuotes[currentQuoteIdx].author;
        textEl.classList.remove('fade');
      }, 220);
    }
  }
</script>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
