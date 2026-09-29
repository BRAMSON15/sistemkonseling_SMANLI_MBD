<?php
/**
 * Navbar Template
 * Sistem Konseling SMA Negeri 5 Maluku Barat Daya
 */
$user = auth_user();
$unread_count = 0;
$latest_notifs = [];

if (isset($pdo) && $user) {
    $unread_count = unread_notifications_count($pdo, $user['id']);
    try {
        $stmt_n = $pdo->prepare("SELECT * FROM notifikasi WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt_n->execute([$user['id']]);
        $latest_notifs = $stmt_n->fetchAll();
    } catch (Exception $e) {}
}

$role_badge = match($user['role'] ?? '') {
    'admin'   => '<span class="badge bg-danger">Administrator</span>',
    'guru_bk' => '<span class="badge bg-primary">Guru BK</span>',
    'siswa'   => '<span class="badge bg-success">Siswa (' . htmlspecialchars($user['nama_kelas'] ?? 'Kelas') . ')</span>',
    default   => ''
};
?>
<header class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-3 py-2 sticky-top shadow-sm">
    <div class="container-fluid p-0">
        <!-- Sidebar Toggle Mobile -->
        <button class="btn btn-outline-secondary d-lg-none me-2" type="button" id="sidebarToggleBtn">
            <i class="bi bi-list fs-5"></i>
        </button>

        <div class="d-none d-md-flex flex-column">
            <span class="text-secondary small">SMA Negeri 5 Maluku Barat Daya</span>
            <span class="fw-semibold text-dark"><?= htmlspecialchars($page_title ?? 'Dashboard') ?></span>
        </div>

        <div class="ms-auto d-flex align-items-center gap-3">
            <!-- Notifikasi Dropdown -->
            <div class="dropdown">
                <button class="btn btn-light position-relative rounded-circle p-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifikasi">
                    <i class="bi bi-bell fs-5 text-secondary"></i>
                    <?php if ($unread_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                            <?= $unread_count ?>
                        </span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow border-0 p-2" style="width: 320px; max-height: 400px; overflow-y: auto;">
                    <div class="d-flex align-items-center justify-content-between px-2 py-1 border-bottom mb-2">
                        <span class="fw-bold small text-dark">Pemberitahuan</span>
                        <span class="badge bg-secondary-subtle text-secondary small"><?= $unread_count ?> baru</span>
                    </div>
                    <?php if (empty($latest_notifs)): ?>
                        <div class="text-center py-3 text-muted small">
                            <i class="bi bi-bell-slash fs-4 d-block mb-1"></i>
                            Tidak ada notifikasi baru
                        </div>
                    <?php else: ?>
                        <?php foreach ($latest_notifs as $n): ?>
                            <a href="<?= !empty($n['link']) ? BASE_URL . $n['link'] : '#' ?>" class="dropdown-item p-2 rounded mb-1 text-wrap <?= $n['is_read'] ? 'text-muted' : 'bg-light fw-medium' ?>" style="font-size: 0.825rem;">
                                <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($n['judul']) ?></div>
                                <div class="text-secondary mb-1"><?= htmlspecialchars($n['pesan']) ?></div>
                                <small class="text-muted" style="font-size: 0.7rem;"><?= tgl_indo($n['created_at'], true) ?></small>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Profile Dropdown -->
            <div class="dropdown">
                <button class="btn btn-light d-flex align-items-center gap-2 border-0 bg-transparent p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px;">
                        <?= strtoupper(substr($user['nama_lengkap'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="text-start d-none d-sm-block">
                        <div class="fw-semibold text-dark small" style="line-height: 1.2;"><?= htmlspecialchars($user['nama_lengkap'] ?? 'User') ?></div>
                        <div style="font-size: 0.725rem;"><?= $role_badge ?></div>
                    </div>
                    <i class="bi bi-chevron-down text-secondary small ms-1"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold text-dark"><?= htmlspecialchars($user['nama_lengkap'] ?? '') ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($user['username'] ?? '') ?></div>
                    </li>
                    <li>
                        <?php
                            $profile_url = match($user['role'] ?? '') {
                                'admin'   => BASE_URL . 'admin/profil.php',
                                'guru_bk' => BASE_URL . 'guru/profil.php',
                                default   => BASE_URL . 'siswa/profil.php'
                            };
                        ?>
                        <a class="dropdown-item py-2" href="<?= $profile_url ?>">
                            <i class="bi bi-person me-2 text-secondary"></i> Profil Saya
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>auth/logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?');">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
