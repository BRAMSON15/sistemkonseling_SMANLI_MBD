<?php
/**
 * Halaman Login Terpadu
 * Sistem Konseling SMA Negeri 5 Maluku Barat Daya
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

// Jika sudah login, redirect langsung ke dashboard masing-masing
if (is_logged_in()) {
    $role = $_SESSION['role'];
    if ($role === 'admin') header('Location: ' . BASE_URL . 'admin/index.php');
    elseif ($role === 'guru_bk') header('Location: ' . BASE_URL . 'guru/index.php');
    else header('Location: ' . BASE_URL . 'siswa/index.php');
    exit;
}

$error = '';
$username_input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_input = trim($_POST['username'] ?? '');
    $password_input = trim($_POST['password'] ?? '');

    if (empty($username_input) || empty($password_input)) {
        $error = 'Silakan masukkan Username / NISN dan Kata Sandi Anda.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$username_input]);
            $user = $stmt->fetch();

            if ($user && password_verify($password_input, $user['password'])) {
                // Inisialisasi sesi login
                $_SESSION['user_id']      = $user['id'];
                $_SESSION['username']     = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role']         = $user['role'];

                // Dapatkan data spesifik berdasarkan role
                if ($user['role'] === 'siswa') {
                    $stmt_s = $pdo->prepare("
                        SELECT s.id AS siswa_id, s.nisn, s.kelas_id, k.nama_kelas 
                        FROM siswa s 
                        LEFT JOIN kelas k ON s.kelas_id = k.id 
                        WHERE s.user_id = ? LIMIT 1
                    ");
                    $stmt_s->execute([$user['id']]);
                    $data_s = $stmt_s->fetch();
                    if ($data_s) {
                        $_SESSION['role_id']    = $data_s['siswa_id'];
                        $_SESSION['nisn']       = $data_s['nisn'];
                        $_SESSION['kelas_id']   = $data_s['kelas_id'];
                        $_SESSION['nama_kelas'] = $data_s['nama_kelas'];
                    }
                    header('Location: ' . BASE_URL . 'siswa/index.php');
                    exit;
                } elseif ($user['role'] === 'guru_bk') {
                    $stmt_g = $pdo->prepare("SELECT id AS guru_id, nip FROM guru_bk WHERE user_id = ? LIMIT 1");
                    $stmt_g->execute([$user['id']]);
                    $data_g = $stmt_g->fetch();
                    if ($data_g) {
                        $_SESSION['role_id'] = $data_g['guru_id'];
                        $_SESSION['nip']     = $data_g['nip'];
                    }
                    header('Location: ' . BASE_URL . 'guru/index.php');
                    exit;
                } else {
                    // Admin
                    header('Location: ' . BASE_URL . 'admin/index.php');
                    exit;
                }
            } else {
                $error = 'Username/NISN atau kata sandi tidak valid. Silakan coba kembali.';
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Konseling SMA Negeri 5 MBD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/custom.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f766e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            padding: 1.5rem;
        }
        .login-card {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: #ffffff;
            width: 100%;
            max-width: 440px;
        }
    </style>
</head>
<body>

<div class="login-card p-4 p-md-5">
    <div class="text-center mb-4">
        <a href="<?= BASE_URL ?>" class="text-decoration-none">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle shadow-sm mb-3" style="width: 60px; height: 60px;">
                <i class="bi bi-shield-heart-fill fs-2"></i>
            </div>
            <h4 class="fw-bold text-dark mb-0">E-Konseling SMAN 5 MBD</h4>
            <p class="text-muted small mt-1">Sistem Deteksi Dini Akademik & Psikososial</p>
        </a>
    </div>

    <?= render_flash() ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger d-flex align-items-center mb-3 py-2 px-3 small" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
            <div><?= htmlspecialchars($error) ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="username" class="form-label small fw-semibold text-secondary">Username / NISN / NIP</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                <input type="text" class="form-control bg-light border-start-0 ps-0" id="username" name="username" value="<?= htmlspecialchars($username_input) ?>" placeholder="Masukkan NISN atau Username" required autofocus>
            </div>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label small fw-semibold text-secondary">Kata Sandi</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control bg-light border-start-0 ps-0" id="password" name="password" placeholder="Masukkan kata sandi" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm mb-3">
            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Sistem
        </button>

        <div class="text-center">
            <a href="<?= BASE_URL ?>" class="text-decoration-none small text-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda
            </a>
        </div>
    </form>

    <!-- Quick Info Akun Demo -->
    <div class="mt-4 pt-3 border-top">
        <p class="text-muted small mb-2 fw-semibold text-center"><i class="bi bi-key-fill text-warning me-1"></i>Akun Akses Uji Coba:</p>
        <div class="accordion accordion-flush" id="accDemo">
            <div class="accordion-item bg-transparent">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed py-2 px-0 bg-transparent text-secondary small" type="button" data-bs-toggle="collapse" data-bs-target="#demoDetails">
                        Lihat Kredensial Demo (Admin, Guru BK, Siswa)
                    </button>
                </h2>
                <div id="demoDetails" class="accordion-collapse collapse" data-bs-parent="#accDemo">
                    <div class="accordion-body px-0 pt-2 pb-0 small text-muted">
                        <div class="border rounded p-2 bg-light mb-1">
                            <strong>Siswa:</strong> NISN <code>0071234561</code> | Pass: <code>siswa123</code>
                        </div>
                        <div class="border rounded p-2 bg-light mb-1">
                            <strong>Guru BK:</strong> NIP <code>198504122010012003</code> | Pass: <code>guru123</code>
                        </div>
                        <div class="border rounded p-2 bg-light">
                            <strong>Admin:</strong> User <code>admin</code> | Pass: <code>admin123</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
