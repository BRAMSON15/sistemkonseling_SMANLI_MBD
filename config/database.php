<?php
/**
 * Konfigurasi Koneksi Database MySQL (PDO)
 * Sistem Konseling SMA Negeri 5 Maluku Barat Daya
 */

// Set Zona Waktu ke WIT (Waktu Indonesia Timur - Maluku)
date_default_timezone_set('Asia/Jayapura');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_konseling_mbd');
define('DB_PORT', '3306');

// Base URL sistem (otomatis mendeteksi folder laragon / domain)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_name = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$app_path = preg_replace('/\/+(admin|guru|siswa|auth|config|includes|assets).*$/i', '', $script_name);
define('BASE_URL', rtrim($protocol . $host . $app_path, '/') . '/');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Jika database belum dibuat / koneksi gagal
    die("<div style='font-family:sans-serif;padding:30px;background:#fef2f2;border:1px solid #ef4444;color:#991b1b;border-radius:8px;max-width:650px;margin:50px auto;'>
        <h3 style='margin-top:0;'>Gagal Terhubung ke Basis Data MySQL!</h3>
        <p>Pesan error: <strong>" . htmlspecialchars($e->getMessage()) . "</strong></p>
        <p>Pastikan MySQL Laragon/XAMPP sudah aktif dan database <code>" . DB_NAME . "</code> sudah diimpor.</p>
        <p><a href='" . ($app_path ? $app_path : '') . "/install.php' style='display:inline-block;padding:8px 16px;background:#2563eb;color:#fff;text-decoration:none;border-radius:4px;'>Jalankan Web Installer Otomatis</a></p>
    </div>");
}
