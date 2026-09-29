<?php
/**
 * API Simpan Mood Harian Siswa
 * Sistem Konseling SMA Negeri 5 Maluku Barat Daya
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

if (!is_logged_in() || $_SESSION['role'] !== 'siswa') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

$user = auth_user();
$siswa_id = $user['role_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $mood  = clean($input['mood'] ?? '');
    $emoji = clean($input['emoji'] ?? '😊');
    $note  = clean($input['catatan'] ?? '');
    $valid_moods = ['senang', 'tenang', 'biasa', 'lelah', 'cemas'];

    if (!in_array($mood, $valid_moods)) {
        echo json_encode(['success' => false, 'message' => 'Mood tidak valid.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO siswa_mood (siswa_id, mood, emoji, catatan, tanggal)
            VALUES (?, ?, ?, ?, CURDATE())
            ON DUPLICATE KEY UPDATE 
                mood = VALUES(mood),
                emoji = VALUES(emoji),
                catatan = VALUES(catatan),
                created_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$siswa_id, $mood, $emoji, $note]);

        echo json_encode([
            'success' => true,
            'message' => 'Mood harian berhasil disimpan ke basis data sistem konseling.',
            'mood'    => $mood,
            'emoji'   => $emoji
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Kesalahan database: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Metode request tidak didukung.']);
