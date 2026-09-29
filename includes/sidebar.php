<?php
/**
 * Sidebar Template
 * Sistem Konseling SMA Negeri 5 Maluku Barat Daya
 */
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
?>
<aside class="sidebar" id="appSidebar">
    <div class="sidebar-header">
        <a href="<?= BASE_URL ?>" class="sidebar-brand">
            <div class="bg-primary text-white rounded p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                <i class="bi bi-shield-heart-fill"></i>
            </div>
            <div>
                <div class="fw-bold" style="line-height: 1.2;">BK SMAN 5 MBD</div>
                <small class="text-secondary" style="font-size: 0.7rem; font-weight: normal;">Deteksi Dini & Konseling</small>
            </div>
        </a>
    </div>

    <ul class="sidebar-menu">
        <?php if ($role === 'siswa'): ?>
            <li class="sidebar-menu-header">Menu Siswa</li>
            <li>
                <a href="<?= BASE_URL ?>siswa/index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">
                    <i class="bi bi-grid-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>siswa/deteksi_dini.php" class="<?= $current_page == 'deteksi_dini.php' ? 'active' : '' ?>">
                    <i class="bi bi-clipboard2-pulse-fill"></i>
                    <span>Deteksi Dini EWS</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>siswa/hasil_deteksi.php" class="<?= $current_page == 'hasil_deteksi.php' ? 'active' : '' ?>">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span>Hasil Deteksi</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>siswa/ajukan_konseling.php" class="<?= $current_page == 'ajukan_konseling.php' ? 'active' : '' ?>">
                    <i class="bi bi-calendar-plus-fill"></i>
                    <span>Ajukan Konseling</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>siswa/riwayat_siswa.php" class="<?= $current_page == 'riwayat_siswa.php' ? 'active' : '' ?>">
                    <i class="bi bi-clock-history"></i>
                    <span>Riwayat Layanan</span>
                </a>
            </li>
            <li class="sidebar-menu-header">Akun</li>
            <li>
                <a href="<?= BASE_URL ?>siswa/profil.php" class="<?= $current_page == 'profil.php' ? 'active' : '' ?>">
                    <i class="bi bi-person-circle"></i>
                    <span>Profil Saya</span>
                </a>
            </li>

        <?php elseif ($role === 'guru_bk'): ?>
            <li class="sidebar-menu-header">Layanan Konseling</li>
            <li>
                <a href="<?= BASE_URL ?>guru/index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard EWS</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>guru/skrining.php" class="<?= $current_page == 'skrining.php' ? 'active' : '' ?>">
                    <i class="bi bi-activity"></i>
                    <span>Monitoring Deteksi Dini</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>guru/pengajuan.php" class="<?= $current_page == 'pengajuan.php' ? 'active' : '' ?>">
                    <i class="bi bi-inbox-fill"></i>
                    <span>Pengajuan Siswa</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>guru/jadwal.php" class="<?= in_array($current_page, ['jadwal.php', 'konseling_proses.php']) ? 'active' : '' ?>">
                    <i class="bi bi-calendar-week-fill"></i>
                    <span>Jadwal Konseling</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>guru/riwayat_konseling.php" class="<?= $current_page == 'riwayat_konseling.php' ? 'active' : '' ?>">
                    <i class="bi bi-folder-check"></i>
                    <span>Rekam Jejak BK</span>
                </a>
            </li>
            <li class="sidebar-menu-header">Pelaporan</li>
            <li>
                <a href="<?= BASE_URL ?>guru/laporan.php" class="<?= $current_page == 'laporan.php' ? 'active' : '' ?>">
                    <i class="bi bi-printer-fill"></i>
                    <span>Cetak Laporan</span>
                </a>
            </li>
            <li class="sidebar-menu-header">Akun</li>
            <li>
                <a href="<?= BASE_URL ?>guru/profil.php" class="<?= $current_page == 'profil.php' ? 'active' : '' ?>">
                    <i class="bi bi-person-badge-fill"></i>
                    <span>Profil Konselor</span>
                </a>
            </li>

        <?php elseif ($role === 'admin'): ?>
            <li class="sidebar-menu-header">Menu Administrator</li>
            <li>
                <a href="<?= BASE_URL ?>admin/index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">
                    <i class="bi bi-speedometer"></i>
                    <span>Dashboard Admin</span>
                </a>
            </li>
            <li class="sidebar-menu-header">Data Master</li>
            <li>
                <a href="<?= BASE_URL ?>admin/pengguna.php" class="<?= $current_page == 'pengguna.php' ? 'active' : '' ?>">
                    <i class="bi bi-people-fill"></i>
                    <span>Data Pengguna</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>admin/kelas.php" class="<?= $current_page == 'kelas.php' ? 'active' : '' ?>">
                    <i class="bi bi-building"></i>
                    <span>Data Kelas</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>admin/kuesioner.php" class="<?= $current_page == 'kuesioner.php' ? 'active' : '' ?>">
                    <i class="bi bi-card-checklist"></i>
                    <span>Instrumen & Soal</span>
                </a>
            </li>
            <li class="sidebar-menu-header">Akun</li>
            <li>
                <a href="<?= BASE_URL ?>admin/profil.php" class="<?= $current_page == 'profil.php' ? 'active' : '' ?>">
                    <i class="bi bi-person-gear"></i>
                    <span>Profil Admin</span>
                </a>
            </li>
        <?php endif; ?>

        <li class="mt-4">
            <a href="<?= BASE_URL ?>auth/logout.php" class="text-danger" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?');">
                <i class="bi bi-box-arrow-right"></i>
                <span>Keluar (Logout)</span>
            </a>
        </li>
    </ul>
</aside>
