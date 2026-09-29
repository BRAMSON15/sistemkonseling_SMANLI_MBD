<?php
/**
 * Header Template Khusus Guru BK (Konselor)
 * Template: assets/guruBK/style-guru-bk.css & dashboard_guru_bk.html
 * SMA Negeri 5 Maluku Barat Daya
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

require_role('guru_bk');
$user = auth_user();
$guru_id = $user['role_id'] ?? null;
$page_title = $page_title ?? 'Dashboard Guru BK (EWS)';

// Notifikasi Pengajuan Menunggu
if (!isset($total_menunggu_bk)) {
    $total_menunggu_bk = (int) $pdo->query("SELECT COUNT(*) FROM pengajuan_konseling WHERE status = 'Menunggu'")->fetchColumn();
}
if (!isset($notif_bk_list)) {
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
    $notif_bk_list = $stmt_notif->fetchAll();
}

// Navigasi Aktif
$current_script = basename($_SERVER['PHP_SELF']);
$is_dashboard  = ($current_script === 'index.php');
$is_skrining   = ($current_script === 'skrining.php');
$is_pengajuan  = ($current_script === 'pengajuan.php');
$is_jadwal     = in_array($current_script, ['jadwal.php', 'konseling_proses.php']);
$is_riwayat    = ($current_script === 'riwayat_konseling.php');
$is_laporan    = ($current_script === 'laporan.php');
$is_profil     = ($current_script === 'profil.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?> | SMAN 5 MBD</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/guruBK/style-guru-bk.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<div class="app">
  <!-- BACKDROP MOBILE -->
  <div class="sidebar-backdrop" id="guruSidebarBackdrop" onclick="closeGuruSidebarMobile()"></div>

  <!-- SIDEBAR GURU BK -->
  <aside class="sidebar" id="guruSidebar">
    <div class="brand">
      <div class="brand-mark">✦</div>
      <div><strong>BK SMAN 5 MBD</strong><small>Deteksi Dini &amp; Konseling</small></div>
      <button type="button" class="sidebar-close-btn" onclick="closeGuruSidebarMobile()" aria-label="Tutup menu">&times;</button>
    </div>
    <nav>
      <small>LAYANAN KONSELING</small>
      <a class="nav-link <?= $is_dashboard ? 'active' : '' ?>" href="<?= BASE_URL ?>guru/index.php">
        <span>◉</span>Dashboard EWS
      </a>
      <a class="nav-link <?= $is_skrining ? 'active' : '' ?>" href="<?= BASE_URL ?>guru/skrining.php">
        <span>⌁</span>Monitoring Deteksi Dini
      </a>
      <a class="nav-link <?= $is_pengajuan ? 'active' : '' ?>" href="<?= BASE_URL ?>guru/pengajuan.php">
        <span>▱</span>Pengajuan Siswa
        <?php if ($total_menunggu_bk > 0): ?>
          <span class="badge badge-red" style="margin-left:auto;"><?= $total_menunggu_bk ?></span>
        <?php endif; ?>
      </a>
      <a class="nav-link <?= $is_jadwal ? 'active' : '' ?>" href="<?= BASE_URL ?>guru/jadwal.php">
        <span>▦</span>Jadwal Konseling
      </a>
      <a class="nav-link <?= $is_riwayat ? 'active' : '' ?>" href="<?= BASE_URL ?>guru/riwayat_konseling.php">
        <span>▧</span>Rekam Jejak BK
      </a>
      
      <small>PELAPORAN</small>
      <a class="nav-link <?= $is_laporan ? 'active' : '' ?>" href="<?= BASE_URL ?>guru/laporan.php">
        <span>▣</span>Cetak Laporan
      </a>
      
      <small>AKUN</small>
      <a class="nav-link <?= $is_profil ? 'active' : '' ?>" href="<?= BASE_URL ?>guru/profil.php">
        <span>♙</span>Profil Konselor
      </a>
      <a class="nav-link logout" href="<?= BASE_URL ?>auth/logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?');">
        <span>⇥</span>Keluar (Logout)
      </a>
    </nav>

    <div class="sidebar-bottom">
      <div class="shield">♡</div>
      <p><b>Ruang Aman Siswa</b><br>Setiap cerita berarti. Jaga kerahasiaan, hadirkan kepedulian.</p>
      <span>BK • SMAN 5 MBD</span>
    </div>
  </aside>

  <!-- MAIN WRAPPER -->
  <main class="main">
    <!-- TOPBAR GURU BK -->
    <header class="topbar">
      <button type="button" class="menu" onclick="toggleGuruSidebar()" aria-label="Buka menu">☰</button>
      <div class="page-title">
        <small>SMAN 5 Maluku Barat Daya</small>
        <strong><?= htmlspecialchars($page_title) ?></strong>
      </div>
      
      <div class="top-right">
        <!-- Notifikasi Lonceng -->
        <div class="dropdown-container">
          <button type="button" class="notification" onclick="toggleGuruBellDropdown(event)" aria-label="Notifikasi Pengajuan" title="Pengajuan Konseling Menunggu">
            ♧<?php if ($total_menunggu_bk > 0): ?><i><?= $total_menunggu_bk ?></i><?php endif; ?>
          </button>
          <div class="guru-dropdown notif-dropdown" id="guruBellDropdown">
            <div class="notif-dropdown-header">
              <strong>Pengajuan Konseling Masuk</strong>
              <span><?= $total_menunggu_bk ?> Menunggu</span>
            </div>
            <div class="notif-list">
              <?php if (empty($notif_bk_list)): ?>
                <div class="notif-empty">
                  <span style="font-size: 20px; display: block; margin-bottom: 4px;">✓</span>
                  Tidak ada pengajuan konseling yang menunggu respon saat ini.
                </div>
              <?php else: ?>
                <?php foreach ($notif_bk_list as $nf): ?>
                  <a href="<?= BASE_URL ?>guru/pengajuan.php" class="notif-item">
                    <div class="notif-item-top">
                      <span class="notif-item-name"><?= htmlspecialchars($nf['nama_lengkap']) ?></span>
                      <span class="notif-item-badge <?= $nf['is_wajib'] ? 'notif-badge-wajib' : 'notif-badge-biasa' ?>">
                        <?= $nf['is_wajib'] ? 'Wajib' : 'Sukarela' ?>
                      </span>
                    </div>
                    <div class="notif-item-desc">
                      Kelas: <?= htmlspecialchars($nf['nama_kelas'] ?? 'Umum') ?> &bull; Jenis: <?= htmlspecialchars($nf['jenis_konseling']) ?>
                    </div>
                    <div class="notif-item-time">
                      <?= date('d M Y, H:i', strtotime($nf['tanggal_pengajuan'])) ?> WIT
                    </div>
                  </a>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <div class="notif-dropdown-footer">
              <a href="<?= BASE_URL ?>guru/pengajuan.php">Lihat Seluruh Pengajuan Siswa &rarr;</a>
            </div>
          </div>
        </div>

        <!-- User Profile Dropdown -->
        <div class="dropdown-container">
          <div onclick="toggleGuruUserDropdown(event)" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
            <div class="profile-avatar"><?= strtoupper(substr($user['nama_lengkap'] ?? 'G', 0, 1)) ?></div>
            <div class="profile-name">
              <b><?= htmlspecialchars($user['nama_lengkap'] ?? 'Guru BK') ?></b>
              <small>Guru BK</small>
            </div>
            <span class="chevron">⌄</span>
          </div>

          <div class="guru-dropdown" id="guruUserDropdown">
            <div class="guru-dropdown-header">
              <strong><?= htmlspecialchars($user['nama_lengkap'] ?? 'Guru BK') ?></strong>
              <span>Konselor / Guru BK SMAN 5 MBD</span>
            </div>
            <a href="<?= BASE_URL ?>guru/profil.php" class="guru-dropdown-item">
              <span>⚙</span> Profil Konselor
            </a>
            <a href="<?= BASE_URL ?>guru/skrining.php" class="guru-dropdown-item">
              <span>⌁</span> Monitoring EWS
            </a>
            <a href="<?= BASE_URL ?>guru/jadwal.php" class="guru-dropdown-item">
              <span>▦</span> Jadwal Konseling
            </a>
            <div class="guru-dropdown-divider"></div>
            <a href="<?= BASE_URL ?>auth/logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?');" class="guru-dropdown-item text-danger">
              <span>🚪</span> Keluar (Logout)
            </a>
          </div>
        </div>
      </div>
    </header>

    <!-- CONTENT BODY -->
    <div class="content">
      <!-- Flash Message jika ada -->
      <?php if (isset($_SESSION['flash'])): $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <div class="flash-wrap <?= match($f['type']) {
          'success' => 'flash-success',
          'danger'  => 'flash-danger',
          'warning' => 'flash-warning',
          default   => 'flash-info'
        } ?>">
          <?= $f['message'] ?>
        </div>
      <?php endif; ?>
