<?php
/**
 * Profil Guru BK
 * SMA Negeri 5 Maluku Barat Daya
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

require_role('guru_bk');
$user = auth_user();
$guru_id = $user['role_id'];
$page_title = 'Profil Guru BK';

$stmt = $pdo->prepare("
    SELECT u.nama_lengkap, u.email, u.no_hp, g.nip, g.spesialisasi, g.no_ruangan
    FROM users u
    JOIN guru_bk g ON u.id = g.user_id
    WHERE g.id = ?
");
$stmt->execute([$guru_id]);
$data_guru = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $nama_lengkap = clean($_POST['nama_lengkap'] ?? '');
        $email        = clean($_POST['email'] ?? '');
        $no_hp        = clean($_POST['no_hp'] ?? '');
        $spesialisasi = clean($_POST['spesialisasi'] ?? '');
        $no_ruangan   = clean($_POST['no_ruangan'] ?? '');

        if (empty($nama_lengkap)) {
            $errors[] = 'Nama lengkap tidak boleh kosong.';
        } else {
            try {
                $pdo->beginTransaction();
                $stmt_u = $pdo->prepare("UPDATE users SET nama_lengkap = ?, email = ?, no_hp = ? WHERE id = ?");
                $stmt_u->execute([$nama_lengkap, $email, $no_hp, $user['id']]);

                $stmt_g = $pdo->prepare("UPDATE guru_bk SET spesialisasi = ?, no_ruangan = ? WHERE id = ?");
                $stmt_g->execute([$spesialisasi, $no_ruangan, $guru_id]);

                $_SESSION['nama_lengkap'] = $nama_lengkap;
                $pdo->commit();

                set_flash('success', 'Profil Guru BK berhasil diperbarui.');
                header('Location: ' . BASE_URL . 'guru/profil.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Gagal menyimpan profil: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'change_password') {
        $old_pass = $_POST['old_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (empty($old_pass) || empty($new_pass) || empty($confirm_pass)) {
            $errors[] = 'Semua kolom kata sandi wajib diisi.';
        } elseif ($new_pass !== $confirm_pass) {
            $errors[] = 'Konfirmasi kata sandi baru tidak sama.';
        } elseif (strlen($new_pass) < 6) {
            $errors[] = 'Kata sandi minimal 6 karakter.';
        } else {
            $stmt_chk = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt_chk->execute([$user['id']]);
            $current_hash = $stmt_chk->fetchColumn();

            if (password_verify($old_pass, $current_hash)) {
                $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
                $stmt_up = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt_up->execute([$new_hash, $user['id']]);
                set_flash('success', 'Kata sandi berhasil diubah!');
                header('Location: ' . BASE_URL . 'guru/profil.php');
                exit;
            } else {
                $errors[] = 'Kata sandi lama salah.';
            }
        }
    }
}

include __DIR__ . '/../includes/guru_header.php';
?>

<!-- PAGE HEADER -->
<div class="page-header">
  <div class="page-header-title">
    <div class="page-header-icon">♙</div>
    <div>
      <h1>Profil Guru BK (Konselor)</h1>
      <p>Kelola data profil tenaga pendidik konselor bimbingan konseling dan pengaturan kata sandi akun.</p>
    </div>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash-wrap flash-danger" style="margin-bottom:18px;">
    <div>
      <strong>Perhatian:</strong>
      <ul style="margin:4px 0 0 16px; font-size:11px;">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
<?php endif; ?>

<div style="display:grid; grid-template-columns: minmax(0, 1.4fr) minmax(280px, 1fr); gap:20px; align-items:flex-start;">
  <!-- FORM IDENTITAS KONSELOR -->
  <div class="card-base" style="padding:24px;">
    <div style="padding-bottom:14px; border-bottom:1px solid #edf2f9; margin-bottom:18px;">
      <h2 style="font-size:15px; font-weight:700; color:var(--navy); margin-bottom:4px;">Identitas &amp; Preferensi Ruang Konselor</h2>
      <p style="font-size:11px; color:var(--muted); margin:0;">Informasi ini akan muncul pada notifikasi dan lembar konseling siswa.</p>
    </div>

    <form method="POST" action="">
      <input type="hidden" name="action" value="update_profile">

      <div class="form-group">
        <label class="form-label">NIP Konselor (Tetap)</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($data_guru['nip'] ?? '-') ?>" readonly style="background:#f8fafc; color:#64748b; font-weight:600;">
      </div>

      <div class="form-group">
        <label class="form-label">Nama Lengkap &amp; Gelar <span style="color:#ef4444;">*</span></label>
        <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($data_guru['nama_lengkap']) ?>" required>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
        <div>
          <label class="form-label">Alamat Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($data_guru['email'] ?? '') ?>">
        </div>
        <div>
          <label class="form-label">Nomor WhatsApp / HP</label>
          <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($data_guru['no_hp'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Bidang Keahlian / Spesialisasi</label>
        <input type="text" name="spesialisasi" class="form-control" value="<?= htmlspecialchars($data_guru['spesialisasi'] ?? '') ?>" placeholder="Misal: Konseling Psikososial &amp; Karir Siswa">
      </div>

      <div class="form-group" style="margin-bottom:20px;">
        <label class="form-label">Lokasi / Ruang Konseling Tetap</label>
        <input type="text" name="no_ruangan" class="form-control" value="<?= htmlspecialchars($data_guru['no_ruangan'] ?? '') ?>" placeholder="Misal: Ruang BK Gedung Utama SMAN 5 MBD">
      </div>

      <div style="display:flex; justify-content:flex-end;">
        <button type="submit" class="btn btn-primary" style="padding:9px 24px;">
          <span>💾</span> Simpan Perubahan Profil
        </button>
      </div>
    </form>
  </div>

  <!-- FORM GANTI KATA SANDI -->
  <div class="card-base" style="padding:24px;">
    <div style="padding-bottom:14px; border-bottom:1px solid #edf2f9; margin-bottom:18px;">
      <h2 style="font-size:15px; font-weight:700; color:var(--navy); margin-bottom:4px;">Keamanan Akun</h2>
      <p style="font-size:11px; color:var(--muted); margin:0;">Perbarui kata sandi untuk menjaga kerahasiaan data siswa.</p>
    </div>

    <form method="POST" action="">
      <input type="hidden" name="action" value="change_password">

      <div class="form-group">
        <label class="form-label">Kata Sandi Saat Ini</label>
        <input type="password" name="old_password" class="form-control" required placeholder="Masukkan kata sandi lama">
      </div>

      <div class="form-group">
        <label class="form-label">Kata Sandi Baru</label>
        <input type="password" name="new_password" class="form-control" required placeholder="Minimal 6 karakter">
      </div>

      <div class="form-group" style="margin-bottom:20px;">
        <label class="form-label">Konfirmasi Kata Sandi Baru</label>
        <input type="password" name="confirm_password" class="form-control" required placeholder="Ulangi kata sandi baru">
      </div>

      <button type="submit" class="btn btn-secondary" style="width:100%; padding:9px 16px;">
        <span>🔒</span> Perbarui Kata Sandi
      </button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/guru_footer.php'; ?>
