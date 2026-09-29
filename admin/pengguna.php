<?php
/**
 * Manajemen Pengguna (Admin, Guru BK, Siswa)
 * Admin - SMA Negeri 5 Maluku Barat Daya
 * Template: assets/admin/admin.css & dashboard_admin.html
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

require_role('admin');
$user = auth_user();

$role_filter = clean($_GET['role'] ?? '');
$search      = clean($_GET['search'] ?? '');

$page_title = ($role_filter === 'siswa') ? 'Data Siswa' : 'Manajemen Data Pengguna';

// Proses Tambah / Edit / Aksi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah') {
        $role         = clean($_POST['role'] ?? 'siswa');
        $username     = clean($_POST['username'] ?? '');
        $password_raw = clean($_POST['password'] ?? '123456');
        $nama_lengkap = clean($_POST['nama_lengkap'] ?? '');
        $email        = clean($_POST['email'] ?? '');
        $no_hp        = clean($_POST['no_hp'] ?? '');

        if (empty($username) || empty($nama_lengkap) || empty($password_raw)) {
            set_flash('danger', 'Harap isi Username/NISN/NIP, Nama Lengkap, dan Kata Sandi.');
        } else {
            try {
                // Cek username duplikat
                $stmt_c = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                $stmt_c->execute([$username]);
                if ($stmt_c->fetchColumn() > 0) {
                    throw new Exception("Username '$username' sudah terdaftar dalam sistem.");
                }

                $pdo->beginTransaction();
                $hash = password_hash($password_raw, PASSWORD_BCRYPT);
                $stmt_u = $pdo->prepare("
                    INSERT INTO users (username, password, nama_lengkap, email, no_hp, role)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt_u->execute([$username, $hash, $nama_lengkap, $email, $no_hp, $role]);
                $new_uid = $pdo->lastInsertId();

                if ($role === 'siswa') {
                    $nisn     = $username; // NISN dijadikan username siswa
                    $kelas_id = !empty($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : null;
                    $jk       = clean($_POST['jenis_kelamin'] ?? 'L');
                    $nama_w   = clean($_POST['nama_wali'] ?? '');
                    $kontak_w = clean($_POST['kontak_wali'] ?? '');

                    $stmt_s = $pdo->prepare("
                        INSERT INTO siswa (user_id, nisn, kelas_id, jenis_kelamin, nama_wali, kontak_wali)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt_s->execute([$new_uid, $nisn, $kelas_id, $jk, $nama_w, $kontak_w]);

                } elseif ($role === 'guru_bk') {
                    $nip      = $username;
                    $spes     = clean($_POST['spesialisasi'] ?? 'Bimbingan Konseling Siswa');
                    $ruang    = clean($_POST['no_ruangan'] ?? 'Ruang BK Lantai 1');

                    $stmt_g = $pdo->prepare("
                        INSERT INTO guru_bk (user_id, nip, spesialisasi, no_ruangan)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt_g->execute([$new_uid, $nip, $spes, $ruang]);
                }

                $pdo->commit();
                set_flash('success', "Pengguna baru <b>" . htmlspecialchars($nama_lengkap) . "</b> berhasil ditambahkan.");
                $redirect = !empty($_POST['redirect_to']) ? clean($_POST['redirect_to']) : BASE_URL . 'admin/pengguna.php' . ($role_filter ? '?role=' . $role_filter : '');
                header('Location: ' . $redirect);
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash('danger', 'Gagal menambah pengguna: ' . $e->getMessage());
                $redirect = !empty($_POST['redirect_to']) ? clean($_POST['redirect_to']) : BASE_URL . 'admin/pengguna.php' . ($role_filter ? '?role=' . $role_filter : '');
                header('Location: ' . $redirect);
                exit;
            }
        }

    } elseif ($action === 'reset_password') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $role_target = clean($_POST['role_target'] ?? 'siswa');
        $default_pass = match($role_target) {
            'admin'   => 'admin123',
            'guru_bk' => 'guru123',
            default   => 'siswa123'
        };

        if ($uid > 0) {
            $hash = password_hash($default_pass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $uid]);
            set_flash('success', "Kata sandi pengguna berhasil di-reset menjadi: <code>$default_pass</code>");
            header('Location: ' . BASE_URL . 'admin/pengguna.php' . ($role_filter ? '?role=' . $role_filter : ''));
            exit;
        }

    } elseif ($action === 'toggle_active') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid > 0 && $uid !== $user['id']) {
            $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$uid]);
            set_flash('info', "Status keaktifan pengguna berhasil diperbarui.");
            header('Location: ' . BASE_URL . 'admin/pengguna.php' . ($role_filter ? '?role=' . $role_filter : ''));
            exit;
        }

    } elseif ($action === 'hapus') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid > 0 && $uid !== $user['id']) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$uid]);
            set_flash('success', "Pengguna berhasil dihapus dari sistem.");
            header('Location: ' . BASE_URL . 'admin/pengguna.php' . ($role_filter ? '?role=' . $role_filter : ''));
            exit;
        }
    }
}

// Build Query Users
$where = ["1=1"];
$params = [];

if (!empty($role_filter)) {
    $where[] = "u.role = ?";
    $params[] = $role_filter;
}
if (!empty($search)) {
    $where[] = "(u.nama_lengkap LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = implode(" AND ", $where);

$stmt_users = $pdo->prepare("
    SELECT u.*, 
           s.nisn, s.jenis_kelamin, k.nama_kelas,
           g.nip, g.spesialisasi
    FROM users u
    LEFT JOIN siswa s ON u.id = s.user_id
    LEFT JOIN kelas k ON s.kelas_id = k.id
    LEFT JOIN guru_bk g ON u.id = g.user_id
    WHERE $where_clause
    ORDER BY u.created_at DESC
");
$stmt_users->execute($params);
$users_list = $stmt_users->fetchAll();

$kelas_list = $pdo->query("SELECT * FROM kelas ORDER BY tingkat ASC, nama_kelas ASC")->fetchAll();

include __DIR__ . '/../includes/admin_header.php';
?>

<!-- PAGE HEADER -->
<div class="page-header">
  <div class="page-header-title">
    <div class="page-header-icon"><?= $role_filter === 'siswa' ? '♙' : '⬟' ?></div>
    <div>
      <h1><?= $role_filter === 'siswa' ? 'Data Siswa SMAN 5 MBD' : 'Manajemen Data Pengguna' ?></h1>
      <p>
        <?= $role_filter === 'siswa' 
          ? 'Daftar seluruh siswa terdaftar pada rombel Fase E & Fase F SMA Negeri 5 Maluku Barat Daya.' 
          : 'Kelola akun Siswa, Guru BK/Konselor, dan Administrator sistem konseling.' ?>
      </p>
    </div>
  </div>
  <div class="page-header-actions">
    <button type="button" class="admin-btn admin-btn-primary" onclick="openTambahUserModal()">
      <span>♙＋</span> Tambah Pengguna Baru
    </button>
  </div>
</div>

<!-- FILTER CARD -->
<div class="filter-card">
  <form method="GET" action="" class="filter-form">
    <div class="admin-form-group" style="margin:0;">
      <label style="font-size:11px; margin-bottom:4px; font-weight:600; color:var(--text);">Cari Nama / Username / NISN</label>
      <input type="text" name="search" class="admin-form-control" placeholder="Ketik nama, NISN, atau NIP..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="admin-form-group" style="margin:0;">
      <label style="font-size:11px; margin-bottom:4px; font-weight:600; color:var(--text);">Filter Peran (Role)</label>
      <select name="role" class="admin-form-control">
        <option value="">-- Semua Peran Pengguna --</option>
        <option value="siswa" <?= $role_filter === 'siswa' ? 'selected' : '' ?>>Siswa (Peserta Didik)</option>
        <option value="guru_bk" <?= $role_filter === 'guru_bk' ? 'selected' : '' ?>>Guru BK / Konselor</option>
        <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Administrator</option>
      </select>
    </div>
    <div style="display:flex; gap:8px;">
      <button type="submit" class="admin-btn admin-btn-primary" style="height:38px;">
        <span>⌕</span> Terapkan
      </button>
      <a href="<?= BASE_URL ?>admin/pengguna.php<?= $role_filter === 'siswa' ? '?role=siswa' : '' ?>" class="admin-btn admin-btn-secondary" style="height:38px;" title="Reset Filter">
        <span>↺</span> Reset
      </a>
    </div>
  </form>
</div>

<!-- TABEL DATA PENGGUNA -->
<div class="panel">
  <div class="panel-heading" style="margin-bottom:12px;">
    <div>
      <h2 style="font-size:14px;"><?= $role_filter === 'siswa' ? 'Daftar Siswa Terdaftar' : 'Daftar Seluruh Akun Pengguna' ?></h2>
      <p>Ditemukan total <?= count($users_list) ?> data pengguna</p>
    </div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width: 40px;">No</th>
          <th>Nama Lengkap &amp; Email</th>
          <th>Username / ID</th>
          <th>Peran</th>
          <th>Keterangan / Rombel</th>
          <th>Kontak HP</th>
          <th>Status Akun</th>
          <th style="text-align: right; width: 100px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users_list)): ?>
          <tr>
            <td colspan="8" style="text-align: center; color: #7f96b7; padding: 28px;">
              Tidak ada data pengguna yang cocok dengan kriteria pencarian atau filter peran.
            </td>
          </tr>
        <?php else: ?>
          <?php $no = 1; foreach ($users_list as $u_item): ?>
            <tr>
              <td><?= $no++ ?></td>
              <td>
                <strong style="color:var(--navy); font-size:12px;"><?= htmlspecialchars($u_item['nama_lengkap']) ?></strong>
                <small style="display:block; color:#7e95b5; font-size:10px; margin-top:2px;">
                  <?= htmlspecialchars($u_item['email'] ?: 'Email belum diisi') ?>
                </small>
              </td>
              <td>
                <code style="background:#eef4fd; color:#185cb8; padding:3px 7px; border-radius:5px; font-size:11px;">
                  <?= htmlspecialchars($u_item['username']) ?>
                </code>
              </td>
              <td>
                <?php if ($u_item['role'] === 'admin'): ?>
                  <span class="badge badge-admin">ADMINISTRATOR</span>
                <?php elseif ($u_item['role'] === 'guru_bk'): ?>
                  <span class="badge badge-guru">GURU BK</span>
                <?php else: ?>
                  <span class="badge badge-siswa">SISWA</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($u_item['role'] === 'siswa'): ?>
                  <span class="badge badge-secondary"><?= htmlspecialchars($u_item['nama_kelas'] ?? 'Belum ada kelas') ?></span>
                  <small style="display:block; color:#7e95b5; font-size:10px; margin-top:2px;">
                    <?= $u_item['jenis_kelamin'] === 'L' ? 'Laki-Laki (L)' : 'Perempuan (P)' ?>
                  </small>
                <?php elseif ($u_item['role'] === 'guru_bk'): ?>
                  <span style="font-size:11px; color:#2878ed;"><?= htmlspecialchars($u_item['spesialisasi'] ?? 'Guru BK') ?></span>
                <?php else: ?>
                  <span style="color:#7e95b5; font-size:11px;">Pengelola Sistem</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($u_item['no_hp'] ?: '-') ?></td>
              <td>
                <form method="POST" action="" style="display:inline;">
                  <input type="hidden" name="action" value="toggle_active">
                  <input type="hidden" name="user_id" value="<?= $u_item['id'] ?>">
                  <button type="submit" class="badge <?= $u_item['is_active'] ? 'badge-active' : 'badge-inactive' ?>" 
                          <?= $u_item['id'] === $user['id'] ? 'disabled title="Akun Anda sendiri"' : 'title="Klik untuk ubah status keaktifan"' ?>
                          style="border:0; cursor:<?= $u_item['id'] === $user['id'] ? 'not-allowed' : 'pointer' ?>;">
                    <?= $u_item['is_active'] ? '● Aktif' : '○ Nonaktif' ?>
                  </button>
                </form>
              </td>
              <td>
                <div class="action-btns">
                  <!-- Reset Sandi -->
                  <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Reset kata sandi akun ini ke kata sandi bawaan default?');">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" value="<?= $u_item['id'] ?>">
                    <input type="hidden" name="role_target" value="<?= $u_item['role'] ?>">
                    <button type="submit" class="btn-icon btn-icon-key" title="Reset Kata Sandi Bawaan">
                      🔑
                    </button>
                  </form>

                  <!-- Hapus Akun -->
                  <?php if ($u_item['id'] !== $user['id']): ?>
                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun ini beserta seluruh datanya?');">
                      <input type="hidden" name="action" value="hapus">
                      <input type="hidden" name="user_id" value="<?= $u_item['id'] ?>">
                      <button type="submit" class="btn-icon btn-icon-delete" title="Hapus Pengguna">
                        🗑
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL TAMBAH PENGGUNA -->
<div class="admin-modal-backdrop" id="modalTambahUser" onclick="closeTambahUserModalOnBackdrop(event)">
  <div class="admin-modal" role="dialog" aria-modal="true" aria-labelledby="modalUserTitle">
    <div class="admin-modal-header">
      <div style="display:flex; align-items:center; gap:8px;">
        <span style="font-size:18px;">♙＋</span>
        <h3 id="modalUserTitle" style="margin:0;">Tambah Pengguna Baru</h3>
      </div>
      <button type="button" class="admin-modal-close" onclick="closeTambahUserModal()" aria-label="Tutup modal">&times;</button>
    </div>
    <form method="POST" action="">
      <input type="hidden" name="action" value="tambah">
      <input type="hidden" name="redirect_to" value="<?= BASE_URL ?>admin/pengguna.php<?= $role_filter ? '?role=' . $role_filter : '' ?>">

      <div class="admin-modal-body">
        <div class="admin-form-group">
          <label for="roleSelector">Peran Pengguna (Role) *</label>
          <select name="role" id="roleSelector" class="admin-form-control" required onchange="toggleRoleFields(this.value)">
            <option value="siswa" <?= $role_filter === 'siswa' ? 'selected' : '' ?>>Siswa (Peserta Didik)</option>
            <option value="guru_bk" <?= $role_filter === 'guru_bk' ? 'selected' : '' ?>>Guru BK / Konselor</option>
            <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Administrator Sistem</option>
          </select>
        </div>

        <div class="admin-form-group">
          <label for="inputNama">Nama Lengkap *</label>
          <input type="text" name="nama_lengkap" id="inputNama" class="admin-form-control" placeholder="Contoh: Daniel Fransisko Renwarin" required>
        </div>

        <div class="admin-form-row">
          <div class="admin-form-group">
            <label for="inputUsername" id="labelUsername">Username / NISN *</label>
            <input type="text" name="username" id="inputUsername" class="admin-form-control" placeholder="Nomor NISN atau NIP" required>
          </div>
          <div class="admin-form-group">
            <label for="inputPassword">Kata Sandi Awal *</label>
            <input type="password" name="password" id="inputPassword" class="admin-form-control" value="123456" required>
          </div>
        </div>

        <div class="admin-form-row">
          <div class="admin-form-group">
            <label for="inputEmail">Email (Opsional)</label>
            <input type="email" name="email" id="inputEmail" class="admin-form-control" placeholder="email@contoh.com">
          </div>
          <div class="admin-form-group">
            <label for="inputHp">No. WhatsApp / HP</label>
            <input type="text" name="no_hp" id="inputHp" class="admin-form-control" placeholder="08xxxxxxxxxx">
          </div>
        </div>

        <!-- Bidang Khusus Siswa -->
        <div id="siswaFields" style="<?= $role_filter === 'guru_bk' || $role_filter === 'admin' ? 'display:none;' : '' ?>">
          <div class="admin-form-row">
            <div class="admin-form-group">
              <label for="selectKelas">Rombel Kelas</label>
              <select name="kelas_id" id="selectKelas" class="admin-form-control">
                <option value="">-- Pilih Kelas --</option>
                <?php foreach ($kelas_list as $k): ?>
                  <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?> (Fase <?= $k['tingkat'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="admin-form-group">
              <label for="selectJk">Jenis Kelamin</label>
              <select name="jenis_kelamin" id="selectJk" class="admin-form-control">
                <option value="L">Laki-Laki (L)</option>
                <option value="P">Perempuan (P)</option>
              </select>
            </div>
          </div>

          <div class="admin-form-row">
            <div class="admin-form-group">
              <label for="inputWali">Nama Orang Tua / Wali</label>
              <input type="text" name="nama_wali" id="inputWali" class="admin-form-control" placeholder="Nama wali murid">
            </div>
            <div class="admin-form-group">
              <label for="inputKontakWali">Kontak No. HP Wali</label>
              <input type="text" name="kontak_wali" id="inputKontakWali" class="admin-form-control" placeholder="08xxxxxxxxxx">
            </div>
          </div>
        </div>

        <!-- Bidang Khusus Guru BK -->
        <div id="guruFields" style="<?= $role_filter === 'guru_bk' ? '' : 'display:none;' ?>">
          <div class="admin-form-group">
            <label for="inputSpes">Spesialisasi Bimbingan Konseling</label>
            <input type="text" name="spesialisasi" id="inputSpes" class="admin-form-control" value="Konseling Remaja &amp; Deteksi Dini">
          </div>
          <div class="admin-form-group">
            <label for="inputRuangan">Ruangan Konseling</label>
            <input type="text" name="no_ruangan" id="inputRuangan" class="admin-form-control" value="Ruang BK SMA Negeri 5 MBD">
          </div>
        </div>
      </div>

      <div class="admin-modal-footer">
        <button type="button" class="admin-btn admin-btn-secondary" onclick="closeTambahUserModal()">Batal</button>
        <button type="submit" class="admin-btn admin-btn-primary">✓ Simpan Pengguna</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openTambahUserModal() {
    const modal = document.getElementById('modalTambahUser');
    if (modal) {
      modal.classList.add('show');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeTambahUserModal() {
    const modal = document.getElementById('modalTambahUser');
    if (modal) {
      modal.classList.remove('show');
      document.body.style.overflow = '';
    }
  }

  function closeTambahUserModalOnBackdrop(e) {
    if (e.target.id === 'modalTambahUser') {
      closeTambahUserModal();
    }
  }

  function toggleRoleFields(role) {
    const sf = document.getElementById('siswaFields');
    const gf = document.getElementById('guruFields');
    const labelU = document.getElementById('labelUsername');
    const inputU = document.getElementById('inputUsername');
    if (role === 'siswa') {
      if (sf) sf.style.display = 'block';
      if (gf) gf.style.display = 'none';
      if (labelU) labelU.textContent = 'Nomor NISN (Sebagai Username) *';
      if (inputU) inputU.placeholder = 'Masukkan NISN siswa';
    } else if (role === 'guru_bk') {
      if (sf) sf.style.display = 'none';
      if (gf) gf.style.display = 'block';
      if (labelU) labelU.textContent = 'NIP Guru BK (Sebagai Username) *';
      if (inputU) inputU.placeholder = 'Masukkan NIP guru konselor';
    } else {
      if (sf) sf.style.display = 'none';
      if (gf) gf.style.display = 'none';
      if (labelU) labelU.textContent = 'Username Administrator *';
      if (inputU) inputU.placeholder = 'Masukkan username admin';
    }
  }

  // Trigger role setup once on load
  document.addEventListener('DOMContentLoaded', function() {
    const roleSel = document.getElementById('roleSelector');
    if (roleSel) toggleRoleFields(roleSel.value);
  });
</script>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
