<?php
/**
 * Profil Administrator
 * SMA Negeri 5 Maluku Barat Daya
 * Template: assets/admin/admin.css & dashboard_admin.html
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

require_role('admin');
$user = auth_user();
$page_title = 'Pengaturan Akun & Profil Admin';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$data_admin = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $nama_lengkap = clean($_POST['nama_lengkap'] ?? '');
        $email        = clean($_POST['email'] ?? '');
        $no_hp        = clean($_POST['no_hp'] ?? '');

        if (empty($nama_lengkap)) {
            $errors[] = 'Nama lengkap tidak boleh kosong.';
        } else {
            try {
                $stmt_u = $pdo->prepare("UPDATE users SET nama_lengkap = ?, email = ?, no_hp = ? WHERE id = ?");
                $stmt_u->execute([$nama_lengkap, $email, $no_hp, $user['id']]);

                $_SESSION['nama_lengkap'] = $nama_lengkap;
                set_flash('success', 'Profil Administrator berhasil diperbarui.');
                header('Location: ' . BASE_URL . 'admin/profil.php');
                exit;
            } catch (Exception $e) {
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
                set_flash('success', 'Kata sandi Administrator berhasil diubah!');
                header('Location: ' . BASE_URL . 'admin/profil.php');
                exit;
            } else {
                $errors[] = 'Kata sandi lama yang dimasukkan salah.';
            }
        }
    }
}

include __DIR__ . '/../includes/admin_header.php';
?>

<!-- PAGE HEADER -->
<div class="page-header">
  <div class="page-header-title">
    <div class="page-header-icon">⚙</div>
    <div>
      <h1>Pengaturan Akun &amp; Profil Admin</h1>
      <p>Kelola identitas akun pengelola sistem bimbingan konseling dan keamanan kata sandi administrator.</p>
    </div>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash-wrap flash-danger" style="margin-bottom:18px;">
    <ul style="margin:0; padding-left:18px; font-size:12px;">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div style="display:grid; grid-template-columns: minmax(0, 1.35fr) minmax(0, 1.05fr); gap: 18px;">
  <!-- PANEL 1: DATA PROFIL ADMIN -->
  <div class="panel">
    <div class="panel-heading" style="margin-bottom:16px;">
      <div>
        <h2 style="font-size:15px;">Informasi Identitas Administrator</h2>
        <p>Perbarui nama akun dan informasi kontak resmi administrator</p>
      </div>
    </div>

    <form method="POST" action="">
      <input type="hidden" name="action" value="update_profile">

      <div class="admin-form-group">
        <label>Username Login (Permanen)</label>
        <input type="text" class="admin-form-control" value="<?= htmlspecialchars($data_admin['username']) ?>" style="background:#f4f7fc; color:#7288aa;" readonly>
      </div>

      <div class="admin-form-group">
        <label>Nama Lengkap Administrator *</label>
        <input type="text" name="nama_lengkap" class="admin-form-control" value="<?= htmlspecialchars($data_admin['nama_lengkap']) ?>" required>
      </div>

      <div class="admin-form-row">
        <div class="admin-form-group">
          <label>Alamat Email</label>
          <input type="email" name="email" class="admin-form-control" value="<?= htmlspecialchars($data_admin['email'] ?? '') ?>" placeholder="admin@sman5mbd.sch.id">
        </div>
        <div class="admin-form-group">
          <label>No. HP / WhatsApp</label>
          <input type="text" name="no_hp" class="admin-form-control" value="<?= htmlspecialchars($data_admin['no_hp'] ?? '') ?>" placeholder="08xxxxxxxxxx">
        </div>
      </div>

      <div style="margin-top:10px; display:flex; justify-content:flex-end;">
        <button type="submit" class="admin-btn admin-btn-primary">
          <span>✓</span> Simpan Perubahan Profil
        </button>
      </div>
    </form>
  </div>

  <!-- PANEL 2: KEAMANAN KATA SANDI -->
  <div class="panel">
    <div class="panel-heading" style="margin-bottom:16px;">
      <div>
        <h2 style="font-size:15px;">Keamanan Kata Sandi</h2>
        <p>Gunakan kombinasi sandi yang aman untuk melindungi hak akses sistem</p>
      </div>
    </div>

    <form method="POST" action="">
      <input type="hidden" name="action" value="change_password">

      <div class="admin-form-group">
        <label>Kata Sandi Saat Ini *</label>
        <input type="password" name="old_password" class="admin-form-control" placeholder="Masukkan kata sandi lama" required>
      </div>

      <div class="admin-form-group">
        <label>Kata Sandi Baru *</label>
        <input type="password" name="new_password" class="admin-form-control" placeholder="Minimal 6 karakter" required>
      </div>

      <div class="admin-form-group">
        <label>Konfirmasi Kata Sandi Baru *</label>
        <input type="password" name="confirm_password" class="admin-form-control" placeholder="Ketik ulang kata sandi baru" required>
      </div>

      <div style="margin-top:14px; display:flex; justify-content:flex-end;">
        <button type="submit" class="admin-btn admin-btn-primary" style="background:linear-gradient(120deg,#ff8c35,#ff741d); box-shadow:0 3px 10px rgba(255,116,29,.25);">
          <span>🔒</span> Perbarui Kata Sandi
        </button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
