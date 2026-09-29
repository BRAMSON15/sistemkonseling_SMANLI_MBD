<?php
/**
 * Web Installer & Database Seeder Otomatis
 * Sistem Konseling SMA Negeri 5 Maluku Barat Daya
 */

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'db_konseling_mbd';

$message = '';
$status = 'info';

$is_cli = (php_sapi_name() === 'cli');
$is_post = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';

if ($is_post || isset($_GET['auto']) || $is_cli) {
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `$dbname`;");

        // Baca file database.sql
        $sql_file = __DIR__ . '/database.sql';
        if (!file_exists($sql_file)) {
            throw new Exception("File database.sql tidak ditemukan di direktori proyek!");
        }

        $sql_content = file_get_contents($sql_file);
        // Pecah per query (pisahkan dengan semicolon di akhir baris)
        $queries = explode(";\n", $sql_content);
        foreach ($queries as $q) {
            $q = trim($q);
            if (!empty($q)) {
                $pdo->exec($q);
            }
        }

        // Generate password hash valid menggunakan PHP BCRYPT
        $hash_admin = password_hash('admin123', PASSWORD_BCRYPT);
        $hash_guru  = password_hash('guru123', PASSWORD_BCRYPT);
        $hash_siswa = password_hash('siswa123', PASSWORD_BCRYPT);

        // Update password pengguna default
        $stmt_update = $pdo->prepare("UPDATE users SET password = ? WHERE role = ?");
        $stmt_update->execute([$hash_admin, 'admin']);
        $stmt_update->execute([$hash_guru, 'guru_bk']);
        $stmt_update->execute([$hash_siswa, 'siswa']);

        $status = 'success';
        $message = "Basis data `$dbname` dan seluruh tabel berhasil diinstalasi serta diinisialisasi dengan data default!";
    } catch (Exception $e) {
        $status = 'danger';
        $message = "Terjadi kesalahan saat instalasi: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalasi Basis Data - Sistem Konseling SMAN 5 MBD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f1f5f9; font-family: system-ui, -apple-system, sans-serif; }
        .card-custom { border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card card-custom p-4">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-database-check fs-2"></i>
                        </div>
                        <h3 class="fw-bold text-dark">Instalasi Basis Data Sistem Konseling</h3>
                        <p class="text-muted">SMA Negeri 5 Maluku Barat Daya (EWS Akademik & Psikososial)</p>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-<?= $status ?> d-flex align-items-center mb-4">
                            <i class="bi bi-<?= $status === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> fs-4 me-2"></i>
                            <div><?= htmlspecialchars($message) ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="bg-light p-3 rounded mb-4">
                        <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-1"></i> Informasi Akun Demo Bawaan:</h6>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="p-2 border rounded bg-white">
                                    <span class="badge bg-danger mb-1">Administrator</span>
                                    <div><strong>User:</strong> <code>admin</code></div>
                                    <div><strong>Pass:</strong> <code>admin123</code></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 border rounded bg-white">
                                    <span class="badge bg-primary mb-1">Guru BK</span>
                                    <div><strong>User/NIP:</strong> <code>198504122010012003</code></div>
                                    <div><strong>Pass:</strong> <code>guru123</code></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 border rounded bg-white">
                                    <span class="badge bg-success mb-1">Siswa (3 Akun)</span>
                                    <div><strong>NISN:</strong> <code>0071234561</code> / 62 / 63</div>
                                    <div><strong>Pass:</strong> <code>siswa123</code></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST">
                        <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                            <button type="submit" class="btn btn-primary px-4 py-2">
                                <i class="bi bi-play-circle-fill me-1"></i> Mulai Instalasi / Reset Basis Data
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary px-4 py-2">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Buka Halaman Utama / Login
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
