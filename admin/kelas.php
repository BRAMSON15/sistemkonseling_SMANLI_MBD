<?php
/**
 * Manajemen Data Kelas & Rombel
 * Admin - SMA Negeri 5 Maluku Barat Daya
 * Template: assets/admin/admin.css & dashboard_admin.html
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

require_role('admin');
$user = auth_user();
$page_title = 'Data Kelas & Rombel';

// Aksi POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah') {
        $nama_kelas   = clean($_POST['nama_kelas'] ?? '');
        $tingkat      = clean($_POST['tingkat'] ?? '10');
        $jurusan      = clean($_POST['jurusan'] ?? 'Umum');
        $tahun_ajaran = clean($_POST['tahun_ajaran'] ?? '2025/2026');

        if (empty($nama_kelas)) {
            set_flash('danger', 'Nama kelas tidak boleh kosong.');
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO kelas (nama_kelas, tingkat, jurusan, tahun_ajaran) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nama_kelas, $tingkat, $jurusan, $tahun_ajaran]);
                set_flash('success', "Rombel kelas <b>" . htmlspecialchars($nama_kelas) . "</b> berhasil ditambahkan.");
                header('Location: ' . BASE_URL . 'admin/kelas.php');
                exit;
            } catch (Exception $e) {
                set_flash('danger', 'Gagal menambah kelas: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'edit') {
        $id           = (int)($_POST['kelas_id'] ?? 0);
        $nama_kelas   = clean($_POST['nama_kelas'] ?? '');
        $tingkat      = clean($_POST['tingkat'] ?? '10');
        $jurusan      = clean($_POST['jurusan'] ?? 'Umum');
        $tahun_ajaran = clean($_POST['tahun_ajaran'] ?? '2025/2026');

        if ($id > 0 && !empty($nama_kelas)) {
            $stmt = $pdo->prepare("UPDATE kelas SET nama_kelas = ?, tingkat = ?, jurusan = ?, tahun_ajaran = ? WHERE id = ?");
            $stmt->execute([$nama_kelas, $tingkat, $jurusan, $tahun_ajaran, $id]);
            set_flash('success', "Data kelas <b>" . htmlspecialchars($nama_kelas) . "</b> berhasil diperbarui.");
            header('Location: ' . BASE_URL . 'admin/kelas.php');
            exit;
        }
    } elseif ($action === 'hapus') {
        $id = (int)($_POST['kelas_id'] ?? 0);
        if ($id > 0) {
            $stmt_c = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE kelas_id = ?");
            $stmt_c->execute([$id]);
            if ($stmt_c->fetchColumn() > 0) {
                set_flash('warning', "Kelas tidak dapat dihapus karena masih menampung data siswa aktif.");
            } else {
                $stmt = $pdo->prepare("DELETE FROM kelas WHERE id = ?");
                $stmt->execute([$id]);
                set_flash('success', "Kelas berhasil dihapus.");
            }
            header('Location: ' . BASE_URL . 'admin/kelas.php');
            exit;
        }
    }
}

// Ambil daftar kelas beserta jumlah siswa
$stmt = $pdo->query("
    SELECT k.*, COUNT(s.id) AS jumlah_siswa
    FROM kelas k
    LEFT JOIN siswa s ON k.id = s.kelas_id
    GROUP BY k.id
    ORDER BY k.tingkat ASC, k.nama_kelas ASC
");
$kelas_list = $stmt->fetchAll();

include __DIR__ . '/../includes/admin_header.php';
?>

<!-- PAGE HEADER -->
<div class="page-header">
  <div class="page-header-title">
    <div class="page-header-icon">▥</div>
    <div>
      <h1>Data Kelas &amp; Rombongan Belajar</h1>
      <p>Kelola rombongan belajar tingkat Fase E (Kelas 10) dan Fase F (Kelas 11 &amp; 12) SMA Negeri 5 MBD.</p>
    </div>
  </div>
  <div class="page-header-actions">
    <button type="button" class="admin-btn admin-btn-primary" onclick="openTambahKelasModal()">
      <span>▥＋</span> Tambah Kelas Baru
    </button>
  </div>
</div>

<!-- TABEL KELAS -->
<div class="panel">
  <div class="panel-heading" style="margin-bottom:12px;">
    <div>
      <h2 style="font-size:14px;">Daftar Rombel Kelas Aktif</h2>
      <p>Total <?= count($kelas_list) ?> rombongan belajar terdaftar</p>
    </div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width: 40px;">No</th>
          <th>Nama Rombel Kelas</th>
          <th>Tingkat Fase</th>
          <th>Jurusan / Peminatan</th>
          <th>Tahun Ajaran</th>
          <th>Total Siswa</th>
          <th style="text-align: right; width: 100px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($kelas_list)): ?>
          <tr>
            <td colspan="7" style="text-align: center; color: #7f96b7; padding: 28px;">
              Belum ada data rombongan belajar kelas yang ditambahkan.
            </td>
          </tr>
        <?php else: ?>
          <?php $no = 1; foreach ($kelas_list as $row): ?>
            <tr>
              <td><?= $no++ ?></td>
              <td>
                <strong style="color:var(--navy); font-size:13px;"><?= htmlspecialchars($row['nama_kelas']) ?></strong>
              </td>
              <td>
                <span class="badge badge-guru">Fase <?= $row['tingkat'] ?> (Kelas <?= $row['tingkat'] ?>)</span>
              </td>
              <td><?= htmlspecialchars($row['jurusan']) ?></td>
              <td>
                <code style="background:#f0f5fc; color:#3b5987; padding:2px 6px; border-radius:4px; font-size:11px;">
                  <?= htmlspecialchars($row['tahun_ajaran']) ?>
                </code>
              </td>
              <td>
                <span class="badge badge-siswa" style="font-size:11px;">
                  ♟ <?= $row['jumlah_siswa'] ?> Siswa
                </span>
              </td>
              <td>
                <div class="action-btns">
                  <button type="button" class="btn-icon btn-icon-edit" title="Edit Data Kelas" 
                          onclick="openEditKelasModal(<?= htmlspecialchars(json_encode($row)) ?>)">
                    ✏
                  </button>
                  <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Hapus rombel kelas ini?');">
                    <input type="hidden" name="action" value="hapus">
                    <input type="hidden" name="kelas_id" value="<?= $row['id'] ?>">
                    <button type="submit" class="btn-icon btn-icon-delete" title="Hapus Kelas">
                      🗑
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL TAMBAH KELAS -->
<div class="admin-modal-backdrop" id="modalTambahKelas" onclick="closeModalOnBackdrop(event, 'modalTambahKelas')">
  <div class="admin-modal" role="dialog" aria-modal="true" aria-labelledby="modalTambahKelasTitle">
    <div class="admin-modal-header">
      <div style="display:flex; align-items:center; gap:8px;">
        <span style="font-size:18px;">▥＋</span>
        <h3 id="modalTambahKelasTitle" style="margin:0;">Tambah Rombel Kelas</h3>
      </div>
      <button type="button" class="admin-modal-close" onclick="closeModal('modalTambahKelas')">&times;</button>
    </div>
    <form method="POST" action="">
      <input type="hidden" name="action" value="tambah">
      <div class="admin-modal-body">
        <div class="admin-form-group">
          <label for="namaKelas">Nama Kelas / Rombel *</label>
          <input type="text" name="nama_kelas" id="namaKelas" class="admin-form-control" placeholder="Contoh: X-1, XI MIPA 2, XII IPS 1" required>
        </div>
        <div class="admin-form-row">
          <div class="admin-form-group">
            <label for="tingkatKelas">Tingkat Fase *</label>
            <select name="tingkat" id="tingkatKelas" class="admin-form-control">
              <option value="10">Kelas 10 (Fase E)</option>
              <option value="11">Kelas 11 (Fase F)</option>
              <option value="12">Kelas 12 (Fase F)</option>
            </select>
          </div>
          <div class="admin-form-group">
            <label for="jurusanKelas">Peminatan / Jurusan *</label>
            <input type="text" name="jurusan" id="jurusanKelas" class="admin-form-control" value="Umum" required>
          </div>
        </div>
        <div class="admin-form-group">
          <label for="tahunAjaran">Tahun Ajaran *</label>
          <input type="text" name="tahun_ajaran" id="tahunAjaran" class="admin-form-control" value="2025/2026" required>
        </div>
      </div>
      <div class="admin-modal-footer">
        <button type="button" class="admin-btn admin-btn-secondary" onclick="closeModal('modalTambahKelas')">Batal</button>
        <button type="submit" class="admin-btn admin-btn-primary">✓ Simpan Kelas</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL EDIT KELAS -->
<div class="admin-modal-backdrop" id="modalEditKelas" onclick="closeModalOnBackdrop(event, 'modalEditKelas')">
  <div class="admin-modal" role="dialog" aria-modal="true" aria-labelledby="modalEditKelasTitle">
    <div class="admin-modal-header">
      <div style="display:flex; align-items:center; gap:8px;">
        <span style="font-size:18px;">✏</span>
        <h3 id="modalEditKelasTitle" style="margin:0;">Edit Rombel Kelas</h3>
      </div>
      <button type="button" class="admin-modal-close" onclick="closeModal('modalEditKelas')">&times;</button>
    </div>
    <form method="POST" action="">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="kelas_id" id="editKelasId" value="">
      <div class="admin-modal-body">
        <div class="admin-form-group">
          <label for="editNamaKelas">Nama Kelas / Rombel *</label>
          <input type="text" name="nama_kelas" id="editNamaKelas" class="admin-form-control" required>
        </div>
        <div class="admin-form-row">
          <div class="admin-form-group">
            <label for="editTingkatKelas">Tingkat Fase *</label>
            <select name="tingkat" id="editTingkatKelas" class="admin-form-control">
              <option value="10">Kelas 10 (Fase E)</option>
              <option value="11">Kelas 11 (Fase F)</option>
              <option value="12">Kelas 12 (Fase F)</option>
            </select>
          </div>
          <div class="admin-form-group">
            <label for="editJurusanKelas">Peminatan / Jurusan *</label>
            <input type="text" name="jurusan" id="editJurusanKelas" class="admin-form-control" required>
          </div>
        </div>
        <div class="admin-form-group">
          <label for="editTahunAjaran">Tahun Ajaran *</label>
          <input type="text" name="tahun_ajaran" id="editTahunAjaran" class="admin-form-control" required>
        </div>
      </div>
      <div class="admin-modal-footer">
        <button type="button" class="admin-btn admin-btn-secondary" onclick="closeModal('modalEditKelas')">Batal</button>
        <button type="submit" class="admin-btn admin-btn-primary">✓ Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openTambahKelasModal() {
    const modal = document.getElementById('modalTambahKelas');
    if (modal) {
      modal.classList.add('show');
      document.body.style.overflow = 'hidden';
    }
  }

  function openEditKelasModal(data) {
    document.getElementById('editKelasId').value = data.id;
    document.getElementById('editNamaKelas').value = data.nama_kelas;
    document.getElementById('editTingkatKelas').value = data.tingkat;
    document.getElementById('editJurusanKelas').value = data.jurusan;
    document.getElementById('editTahunAjaran').value = data.tahun_ajaran;

    const modal = document.getElementById('modalEditKelas');
    if (modal) {
      modal.classList.add('show');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.remove('show');
      document.body.style.overflow = '';
    }
  }

  function closeModalOnBackdrop(e, modalId) {
    if (e.target.id === modalId) {
      closeModal(modalId);
    }
  }
</script>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
