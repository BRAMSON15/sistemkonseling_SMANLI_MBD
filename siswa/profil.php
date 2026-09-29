<?php
/**
 * Profil Siswa & Ubah Password
 * Sistem Konseling SMA Negeri 5 Maluku Barat Daya
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

require_role('siswa');
$user = auth_user();
$siswa_id = $user['role_id'];
$page_title = 'Profil Siswa';

// Ambil data detail siswa
$stmt = $pdo->prepare("
    SELECT u.username, u.nama_lengkap, u.email, u.no_hp,
           s.nisn, s.jenis_kelamin, s.tempat_lahir, s.tanggal_lahir, s.alamat, s.nama_wali, s.kontak_wali,
           k.nama_kelas, k.jurusan
    FROM users u
    JOIN siswa s ON u.id = s.user_id
    LEFT JOIN kelas k ON s.kelas_id = k.id
    WHERE s.id = ?
");
$stmt->execute([$siswa_id]);
$data_siswa = $stmt->fetch();

$errors = [];
$success = '';

// Proses Update Profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $email        = clean($_POST['email'] ?? '');
        $no_hp        = clean($_POST['no_hp'] ?? '');
        $tempat_lahir = clean($_POST['tempat_lahir'] ?? '');
        $tgl_lahir    = clean($_POST['tanggal_lahir'] ?? '');
        $alamat       = clean($_POST['alamat'] ?? '');
        $nama_wali    = clean($_POST['nama_wali'] ?? '');
        $kontak_wali  = clean($_POST['kontak_wali'] ?? '');

        try {
            $pdo->beginTransaction();

            $stmt_u = $pdo->prepare("UPDATE users SET email = ?, no_hp = ? WHERE id = ?");
            $stmt_u->execute([$email, $no_hp, $user['id']]);

            $stmt_s = $pdo->prepare("
                UPDATE siswa SET tempat_lahir = ?, tanggal_lahir = ?, alamat = ?, nama_wali = ?, kontak_wali = ?
                WHERE id = ?
            ");
            $stmt_s->execute([$tempat_lahir, !empty($tgl_lahir) ? $tgl_lahir : null, $alamat, $nama_wali, $kontak_wali, $siswa_id]);

            $pdo->commit();
            set_flash('success', 'Data profil dan kontak darurat berhasil diperbarui.');
            header('Location: ' . BASE_URL . 'siswa/profil.php');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Gagal menyimpan profil: ' . $e->getMessage();
        }

    } elseif ($action === 'change_password') {
        $old_pass = $_POST['old_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (empty($old_pass) || empty($new_pass) || empty($confirm_pass)) {
            $errors[] = 'Seluruh kolom kata sandi wajib diisi.';
        } elseif ($new_pass !== $confirm_pass) {
            $errors[] = 'Konfirmasi kata sandi baru tidak cocok.';
        } elseif (strlen($new_pass) < 6) {
            $errors[] = 'Kata sandi baru minimal harus 6 karakter.';
        } else {
            $stmt_chk = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt_chk->execute([$user['id']]);
            $current_hash = $stmt_chk->fetchColumn();

            if (password_verify($old_pass, $current_hash)) {
                $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
                $stmt_up = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt_up->execute([$new_hash, $user['id']]);
                set_flash('success', 'Kata sandi berhasil diubah! Gunakan kata sandi baru untuk login selanjutnya.');
                header('Location: ' . BASE_URL . 'siswa/profil.php');
                exit;
            } else {
                $errors[] = 'Kata sandi lama yang Anda masukkan tidak sesuai.';
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h4 class="fw-bold text-dark mb-1"><i class="bi bi-person-circle text-primary me-2"></i>Profil & Data Siswa</h4>
    <p class="text-secondary small mb-0">Kelola informasi pribadi, kontak darurat wali, serta keamanan akun Anda.</p>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger shadow-sm mb-4">
        <ul class="mb-0 small ps-3">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card card-base p-4">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person-lines-fill text-primary me-2"></i>Data Pribadi & Kontak</h5>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">NISN (Tetap)</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($data_siswa['nisn']) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Nama Lengkap (Tetap)</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($data_siswa['nama_lengkap']) ?>" readonly>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Kelas & Jurusan</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($data_siswa['nama_kelas'] ?? '-') ?> (<?= htmlspecialchars($data_siswa['jurusan'] ?? '-') ?>)" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Jenis Kelamin</label>
                        <input type="text" class="form-control bg-light" value="<?= $data_siswa['jenis_kelamin'] === 'L' ? 'Laki-Laki' : 'Perempuan' ?>" readonly>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" class="form-control" value="<?= htmlspecialchars($data_siswa['tempat_lahir'] ?? '') ?>" placeholder="Kota/Desa Kelahiran">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" class="form-control" value="<?= htmlspecialchars($data_siswa['tanggal_lahir'] ?? '') ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Email Siswa</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($data_siswa['email'] ?? '') ?>" placeholder="email@contoh.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Nomor WhatsApp / HP</label>
                        <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($data_siswa['no_hp'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Alamat Rumah di Moa / MBD</label>
                    <textarea name="alamat" class="form-control" rows="2" placeholder="Nama Jalan, Desa, Kecamatan"><?= htmlspecialchars($data_siswa['alamat'] ?? '') ?></textarea>
                </div>

                <hr class="my-4">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-people me-2 text-info"></i>Kontak Orang Tua / Wali Siswa</h6>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Nama Orang Tua / Wali</label>
                        <input type="text" name="nama_wali" class="form-control" value="<?= htmlspecialchars($data_siswa['nama_wali'] ?? '') ?>" placeholder="Nama Ayah/Ibu/Wali">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">No. HP/WA Wali</label>
                        <input type="text" name="kontak_wali" class="form-control" value="<?= htmlspecialchars($data_siswa['kontak_wali'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                    <i class="bi bi-save me-1"></i> Simpan Perubahan Profil
                </button>
            </form>
        </div>
    </div>

    <!-- Kolom Kanan: Ubah Password -->
    <div class="col-lg-5">
        <div class="card card-base p-4">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-shield-lock-fill text-warning me-2"></i>Ubah Kata Sandi</h5>
            <form method="POST" action="">
                <input type="hidden" name="action" value="change_password">
                
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Kata Sandi Saat Ini</label>
                    <input type="password" name="old_password" class="form-control" required placeholder="Masukkan kata sandi lama">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Kata Sandi Baru</label>
                    <input type="password" name="new_password" class="form-control" required placeholder="Minimal 6 karakter">
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold text-secondary">Ulangi Kata Sandi Baru</label>
                    <input type="password" name="confirm_password" class="form-control" required placeholder="Ketik ulang kata sandi baru">
                </div>

                <button type="submit" class="btn btn-outline-dark w-100 fw-semibold">
                    <i class="bi bi-key me-1"></i> Perbarui Kata Sandi
                </button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
