<?php
/**
 * Header Template Khusus Administrator
 * Template: assets/admin/admin.css & dashboard_admin.html
 * SMA Negeri 5 Maluku Barat Daya
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

require_role('admin');
$user = auth_user();
$page_title = $page_title ?? 'Dashboard Admin - SMAN 5 MBD';

// Query notifikasi jika belum ada
if (!isset($total_menunggu)) {
    $total_menunggu = (int) $pdo->query("SELECT COUNT(*) FROM pengajuan_konseling WHERE status = 'Menunggu'")->fetchColumn();
}
if (!isset($notif_list)) {
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
}

// Navigasi aktif
$current_script = basename($_SERVER['PHP_SELF']);
$current_role_param = $_GET['role'] ?? '';
$is_data_siswa     = ($current_script === 'pengguna.php' && $current_role_param === 'siswa');
$is_manajemen_user = ($current_script === 'pengguna.php' && $current_role_param !== 'siswa');
$is_dashboard      = ($current_script === 'index.php');
$is_kuesioner      = ($current_script === 'kuesioner.php');
$is_kelas          = ($current_script === 'kelas.php');
$is_profil         = ($current_script === 'profil.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?> | SMAN 5 MBD</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/admin/admin.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
  <div class="app">
    <!-- BACKDROP MOBILE -->
    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebarMobile()"></div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="adminSidebar">
      <div class="brand">
        <div class="brand-logo">✦</div>
        <div><strong>SMAN 5 MBD</strong><span>Sistem Konseling</span></div>
        <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" onclick="closeSidebarMobile()" aria-label="Tutup menu">&times;</button>
      </div>

      <nav class="navigation">
        <a class="nav-item <?= $is_dashboard ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/index.php"><span>⌂</span>Dashboard</a>
        <small>MENU UTAMA</small>
        <a class="nav-item <?= $is_data_siswa ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/pengguna.php?role=siswa"><span>♙</span>Data Siswa</a>
        <a class="nav-item <?= $is_kuesioner ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/kuesioner.php"><span>▤</span>Konseling &amp; EWS</a>
        <a class="nav-item <?= $is_kelas ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/kelas.php"><span>▥</span>Data Kelas &amp; Rombel</a>
        <a class="nav-item <?= $is_manajemen_user ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/pengguna.php"><span>⬟</span>Manajemen User</a>
        <a class="nav-item <?= $is_profil ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/profil.php"><span>⚙</span>Pengaturan Akun</a>
        <a class="nav-item" href="<?= BASE_URL ?>auth/logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?');" style="color:#ff8b99;">
          <span>🚪</span>Keluar (Logout)
        </a>
      </nav>

      <div class="sidebar-note">
        <div class="note-icon">❋</div>
        <p>Bersama kita wujudkan lingkungan sekolah yang sehat, aman dan suportif di SMAN 5 MBD.</p>
      </div>

      <div class="profile">
        <div class="avatar"><?= strtoupper(substr($user['nama_lengkap'] ?? 'A', 0, 1)) ?></div>
        <div>
          <strong><?= htmlspecialchars($user['nama_lengkap'] ?? 'Admin') ?></strong>
          <span>Administrator</span>
        </div>
        <a href="<?= BASE_URL ?>admin/profil.php" title="Lihat Profil" style="color:inherit; margin-left:auto;">
          <b>›</b>
        </a>
      </div>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <main class="main">
      <!-- TOPBAR -->
      <header class="topbar">
        <button class="menu-button" id="menuToggleBtn" aria-label="Buka menu" onclick="toggleSidebar()">☰</button>
        
        <form action="<?= BASE_URL ?>admin/pengguna.php" method="GET" class="search" style="margin:0;">
          <span>⌕</span>
          <input type="search" name="search" placeholder="Cari siswa, nama, atau NISN..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </form>

        <div class="top-actions">
          <!-- Notification Bell Dropdown -->
          <div class="dropdown-container">
            <button type="button" class="bell" id="bellBtn" onclick="toggleBellDropdown(event)" aria-label="Notifikasi Pengajuan" title="Notifikasi Pengajuan Siswa">
              ♧<i><?= $total_menunggu ?></i>
            </button>
            <div class="admin-dropdown notif-dropdown" id="bellDropdown">
              <div class="notif-dropdown-header">
                <strong>Pengajuan Konseling Masuk</strong>
                <span><?= $total_menunggu ?> Menunggu</span>
              </div>
              <div class="notif-list">
                <?php if (empty($notif_list)): ?>
                  <div class="notif-empty">
                    <span style="font-size: 20px; display: block; margin-bottom: 4px;">✓</span>
                    Tidak ada pengajuan konseling yang menunggu respon saat ini.
                  </div>
                <?php else: ?>
                  <?php foreach ($notif_list as $nf): ?>
                    <a href="<?= BASE_URL ?>admin/pengguna.php?search=<?= urlencode($nf['nama_lengkap']) ?>" class="notif-item">
                      <div class="notif-item-top">
                        <span class="notif-item-name"><?= htmlspecialchars($nf['nama_lengkap']) ?></span>
                        <span class="notif-item-badge <?= $nf['is_wajib'] ? 'notif-badge-wajib' : 'notif-badge-biasa' ?>">
                          <?= $nf['is_wajib'] ? 'Wajib Konseling' : 'Sukarela' ?>
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
                <a href="<?= BASE_URL ?>admin/pengguna.php?role=siswa">Lihat Seluruh Data Siswa &rarr;</a>
              </div>
            </div>
          </div>

          <!-- User Profile Dropdown -->
          <div class="dropdown-container">
            <button type="button" class="top-user" id="topUserBtn" onclick="toggleUserDropdown(event)" style="background:none; border:none; cursor:pointer; text-align:left;">
              <div class="avatar small"><?= strtoupper(substr($user['nama_lengkap'] ?? 'A', 0, 1)) ?></div>
              <strong><?= htmlspecialchars($user['nama_lengkap'] ?? 'Administrator') ?></strong>
              <span>⌄</span>
            </button>
            <div class="admin-dropdown" id="userDropdown">
              <div class="admin-dropdown-header">
                <strong><?= htmlspecialchars($user['nama_lengkap'] ?? 'Administrator') ?></strong>
                <span>Administrator Sistem</span>
              </div>
              <a href="<?= BASE_URL ?>admin/profil.php" class="admin-dropdown-item">
                <span>⚙</span> Pengaturan Profil
              </a>
              <a href="<?= BASE_URL ?>admin/pengguna.php" class="admin-dropdown-item">
                <span>⬟</span> Manajemen Pengguna
              </a>
              <a href="<?= BASE_URL ?>admin/kelas.php" class="admin-dropdown-item">
                <span>▥</span> Data Rombel Kelas
              </a>
              <a href="<?= BASE_URL ?>admin/kuesioner.php" class="admin-dropdown-item">
                <span>▤</span> Instrumen Konseling
              </a>
              <div class="admin-dropdown-divider"></div>
              <a href="<?= BASE_URL ?>auth/logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?');" class="admin-dropdown-item text-danger">
                <span>🚪</span> Keluar (Logout)
              </a>
            </div>
          </div>
        </div>
      </header>

      <!-- CONTENT BODY -->
      <section class="content">
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
