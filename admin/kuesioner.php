<?php
/**
 * Manajemen Instrumen & Butir Soal Deteksi Dini
 * Admin - SMA Negeri 5 Maluku Barat Daya
 * Template: assets/admin/admin.css & dashboard_admin.html
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

require_role('admin');
$user = auth_user();
$page_title = 'Instrumen & Soal Skrining EWS';

$kategori_filter = clean($_GET['kategori'] ?? '');

// Ambil Kuesioner Utama
$kuesioner = $pdo->query("SELECT * FROM kuesioner LIMIT 1")->fetch();

// Aksi POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah') {
        $kategori        = clean($_POST['kategori'] ?? 'akademik');
        $teks_pertanyaan = clean($_POST['teks_pertanyaan'] ?? '');
        $nomor_urut      = (int)($_POST['nomor_urut'] ?? 1);

        if (!empty($teks_pertanyaan)) {
            $stmt = $pdo->prepare("
                INSERT INTO pertanyaan (kuesioner_id, kategori, teks_pertanyaan, nomor_urut, is_active)
                VALUES (?, ?, ?, ?, 1)
            ");
            $stmt->execute([$kuesioner['id'], $kategori, $teks_pertanyaan, $nomor_urut]);
            set_flash('success', 'Butir instrumen baru berhasil ditambahkan.');
            header('Location: ' . BASE_URL . 'admin/kuesioner.php' . ($kategori_filter ? '?kategori=' . $kategori_filter : ''));
            exit;
        }
    } elseif ($action === 'edit') {
        $id              = (int)($_POST['pertanyaan_id'] ?? 0);
        $kategori        = clean($_POST['kategori'] ?? 'akademik');
        $teks_pertanyaan = clean($_POST['teks_pertanyaan'] ?? '');
        $nomor_urut      = (int)($_POST['nomor_urut'] ?? 1);

        if ($id > 0 && !empty($teks_pertanyaan)) {
            $stmt = $pdo->prepare("UPDATE pertanyaan SET kategori = ?, teks_pertanyaan = ?, nomor_urut = ? WHERE id = ?");
            $stmt->execute([$kategori, $teks_pertanyaan, $nomor_urut, $id]);
            set_flash('success', 'Butir instrumen berhasil diperbarui.');
            header('Location: ' . BASE_URL . 'admin/kuesioner.php' . ($kategori_filter ? '?kategori=' . $kategori_filter : ''));
            exit;
        }
    } elseif ($action === 'toggle_active') {
        $id = (int)($_POST['pertanyaan_id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE pertanyaan SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);
            set_flash('info', 'Status aktif butir instrumen berhasil diubah.');
            header('Location: ' . BASE_URL . 'admin/kuesioner.php' . ($kategori_filter ? '?kategori=' . $kategori_filter : ''));
            exit;
        }
    } elseif ($action === 'hapus') {
        $id = (int)($_POST['pertanyaan_id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM pertanyaan WHERE id = ?");
            $stmt->execute([$id]);
            set_flash('success', 'Butir instrumen berhasil dihapus.');
            header('Location: ' . BASE_URL . 'admin/kuesioner.php' . ($kategori_filter ? '?kategori=' . $kategori_filter : ''));
            exit;
        }
    }
}

// Build Query
$where = ["kuesioner_id = ?"];
$params = [$kuesioner['id']];

if (!empty($kategori_filter)) {
    $where[] = "kategori = ?";
    $params[] = $kategori_filter;
}

$where_clause = implode(" AND ", $where);
$stmt = $pdo->prepare("SELECT * FROM pertanyaan WHERE $where_clause ORDER BY nomor_urut ASC");
$stmt->execute($params);
$pertanyaan_list = $stmt->fetchAll();

// Opsi Jawaban Skala Likert
$opsi_list = $pdo->query("SELECT * FROM opsi_jawaban ORDER BY urutan ASC")->fetchAll();

include __DIR__ . '/../includes/admin_header.php';
?>

<!-- PAGE HEADER -->
<div class="page-header">
  <div class="page-header-title">
    <div class="page-header-icon">▤</div>
    <div>
      <h1>Instrumen &amp; Butir Soal Deteksi Dini EWS</h1>
      <p>Kelola butir indikator kuesioner deteksi dini akademik, psikososial, dan indikasi perundungan (bullying).</p>
    </div>
  </div>
  <div class="page-header-actions">
    <button type="button" class="admin-btn admin-btn-primary" onclick="openTambahSoalModal()">
      <span>▤＋</span> Tambah Butir Soal
    </button>
  </div>
</div>

<!-- KARTU INFO KUESIONER & LIKERT -->
<div style="display:grid; grid-template-columns: minmax(0, 1.8fr) minmax(0, 1.2fr); gap: 14px; margin-bottom: 18px;">
  <div class="panel" style="padding:16px 18px;">
    <h3 style="font-size:14px; font-weight:700; color:var(--navy); margin-bottom:4px;"><?= htmlspecialchars($kuesioner['judul']) ?></h3>
    <p style="font-size:11px; color:#5c769b; line-height:1.6;"><?= htmlspecialchars($kuesioner['deskripsi']) ?></p>
  </div>
  <div class="panel" style="padding:16px 18px;">
    <h4 style="font-size:12px; font-weight:700; color:var(--navy); margin-bottom:8px;">Skala Pembobotan Likert:</h4>
    <div style="display:flex; flex-wrap:wrap; gap:6px;">
      <?php foreach ($opsi_list as $o): ?>
        <span class="badge badge-secondary" style="font-size:10px;">
          <?= htmlspecialchars($o['label']) ?> (Bobot: <b><?= $o['bobot'] ?></b>)
        </span>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- FILTER TABS DIMENSI -->
<div style="display:flex; gap:8px; margin-bottom:16px; overflow-x:auto; padding-bottom:4px;">
  <a href="<?= BASE_URL ?>admin/kuesioner.php" class="admin-btn <?= empty($kategori_filter) ? 'admin-btn-primary' : 'admin-btn-secondary' ?>" style="height:34px; font-size:11px;">
    Semua Dimensi (<?= count($pertanyaan_list) ?>)
  </a>
  <a href="?kategori=akademik" class="admin-btn <?= $kategori_filter === 'akademik' ? 'admin-btn-primary' : 'admin-btn-secondary' ?>" style="height:34px; font-size:11px;">
    Dimensi Akademik
  </a>
  <a href="?kategori=kecemasan_stres" class="admin-btn <?= $kategori_filter === 'kecemasan_stres' ? 'admin-btn-primary' : 'admin-btn-secondary' ?>" style="height:34px; font-size:11px;">
    Kecemasan &amp; Stres
  </a>
  <a href="?kategori=sosial_relasi" class="admin-btn <?= $kategori_filter === 'sosial_relasi' ? 'admin-btn-primary' : 'admin-btn-secondary' ?>" style="height:34px; font-size:11px;">
    Sosial &amp; Relasi
  </a>
  <a href="?kategori=perundungan_bullying" class="admin-btn <?= $kategori_filter === 'perundungan_bullying' ? 'admin-btn-primary' : 'admin-btn-secondary' ?>" style="height:34px; font-size:11px;">
    Indikasi Bullying
  </a>
</div>

<!-- TABEL BUTIR PERTANYAAN -->
<div class="panel">
  <div class="panel-heading" style="margin-bottom:12px;">
    <div>
      <h2 style="font-size:14px;">Daftar Butir Pertanyaan</h2>
      <p>Total <?= count($pertanyaan_list) ?> butir pertanyaan aktif dalam instrumen</p>
    </div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width: 45px;">No</th>
          <th style="width: 150px;">Dimensi Kategori</th>
          <th>Pernyataan / Teks Butir Soal</th>
          <th style="width: 90px;">Status</th>
          <th style="text-align: right; width: 90px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pertanyaan_list)): ?>
          <tr>
            <td colspan="5" style="text-align: center; color: #7f96b7; padding: 28px;">
              Belum ada butir pertanyaan pada dimensi ini.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($pertanyaan_list as $row): ?>
            <tr>
              <td><b style="color:var(--navy); font-size:12px;">#<?= $row['nomor_urut'] ?></b></td>
              <td>
                <?php
                  $badge_class = match($row['kategori']) {
                    'akademik'             => 'badge-guru',
                    'kecemasan_stres'      => 'badge-warning',
                    'sosial_relasi'        => 'badge-siswa',
                    'perundungan_bullying' => 'badge-admin',
                    default                => 'badge-secondary'
                  };
                  $kat_label = match($row['kategori']) {
                    'akademik'             => 'Akademik',
                    'kecemasan_stres'      => 'Kecemasan / Stres',
                    'sosial_relasi'        => 'Sosial / Relasi',
                    'perundungan_bullying' => 'Bullying',
                    default                => $row['kategori']
                  };
                ?>
                <span class="badge <?= $badge_class ?>"><?= $kat_label ?></span>
              </td>
              <td>
                <span style="color:var(--text); font-size:12px; line-height:1.5; font-weight:500;">
                  <?= htmlspecialchars($row['teks_pertanyaan']) ?>
                </span>
              </td>
              <td>
                <form method="POST" action="" style="display:inline;">
                  <input type="hidden" name="action" value="toggle_active">
                  <input type="hidden" name="pertanyaan_id" value="<?= $row['id'] ?>">
                  <button type="submit" class="badge <?= $row['is_active'] ? 'badge-active' : 'badge-inactive' ?>" 
                          title="Klik untuk ubah status aktif butir" style="border:0; cursor:pointer;">
                    <?= $row['is_active'] ? '● Aktif' : '○ Nonaktif' ?>
                  </button>
                </form>
              </td>
              <td>
                <div class="action-btns">
                  <button type="button" class="btn-icon btn-icon-edit" title="Edit Butir Soal" 
                          onclick="openEditSoalModal(<?= htmlspecialchars(json_encode($row)) ?>)">
                    ✏
                  </button>
                  <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Hapus butir pertanyaan ini?');">
                    <input type="hidden" name="action" value="hapus">
                    <input type="hidden" name="pertanyaan_id" value="<?= $row['id'] ?>">
                    <button type="submit" class="btn-icon btn-icon-delete" title="Hapus Butir">
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

<!-- MODAL TAMBAH SOAL -->
<div class="admin-modal-backdrop" id="modalTambahSoal" onclick="closeModalOnBackdrop(event, 'modalTambahSoal')">
  <div class="admin-modal" role="dialog" aria-modal="true" aria-labelledby="modalTambahSoalTitle">
    <div class="admin-modal-header">
      <div style="display:flex; align-items:center; gap:8px;">
        <span style="font-size:18px;">▤＋</span>
        <h3 id="modalTambahSoalTitle" style="margin:0;">Tambah Butir Pertanyaan</h3>
      </div>
      <button type="button" class="admin-modal-close" onclick="closeModal('modalTambahSoal')">&times;</button>
    </div>
    <form method="POST" action="">
      <input type="hidden" name="action" value="tambah">
      <div class="admin-modal-body">
        <div class="admin-form-row">
          <div class="admin-form-group">
            <label for="kategoriSoal">Dimensi Kategori *</label>
            <select name="kategori" id="kategoriSoal" class="admin-form-control" required>
              <option value="akademik">Dimensi Akademik</option>
              <option value="kecemasan_stres">Kecemasan &amp; Stres Emosional</option>
              <option value="sosial_relasi">Hubungan Sosial &amp; Teman Sebaya</option>
              <option value="perundungan_bullying">Perlindungan Perundungan (Bullying)</option>
            </select>
          </div>
          <div class="admin-form-group">
            <label for="nomorUrut">Nomor Urut Butir *</label>
            <input type="number" name="nomor_urut" id="nomorUrut" class="admin-form-control" value="<?= count($pertanyaan_list) + 1 ?>" required>
          </div>
        </div>
        <div class="admin-form-group">
          <label for="teksSoal">Teks / Pernyataan Butir Instrumen *</label>
          <textarea name="teks_pertanyaan" id="teksSoal" class="admin-form-control" style="height:90px; padding:10px 12px; resize:vertical;" placeholder="Contoh: Saya merasa cemas dan kesulitan berkonsentrasi ketika menghadapi ujian..." required></textarea>
        </div>
      </div>
      <div class="admin-modal-footer">
        <button type="button" class="admin-btn admin-btn-secondary" onclick="closeModal('modalTambahSoal')">Batal</button>
        <button type="submit" class="admin-btn admin-btn-primary">✓ Tambah Soal</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL EDIT SOAL -->
<div class="admin-modal-backdrop" id="modalEditSoal" onclick="closeModalOnBackdrop(event, 'modalEditSoal')">
  <div class="admin-modal" role="dialog" aria-modal="true" aria-labelledby="modalEditSoalTitle">
    <div class="admin-modal-header">
      <div style="display:flex; align-items:center; gap:8px;">
        <span style="font-size:18px;">✏</span>
        <h3 id="modalEditSoalTitle" style="margin:0;">Edit Butir Pertanyaan</h3>
      </div>
      <button type="button" class="admin-modal-close" onclick="closeModal('modalEditSoal')">&times;</button>
    </div>
    <form method="POST" action="">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="pertanyaan_id" id="editPertanyaanId" value="">
      <div class="admin-modal-body">
        <div class="admin-form-row">
          <div class="admin-form-group">
            <label for="editKategoriSoal">Dimensi Kategori *</label>
            <select name="kategori" id="editKategoriSoal" class="admin-form-control" required>
              <option value="akademik">Dimensi Akademik</option>
              <option value="kecemasan_stres">Kecemasan &amp; Stres Emosional</option>
              <option value="sosial_relasi">Hubungan Sosial &amp; Teman Sebaya</option>
              <option value="perundungan_bullying">Perlindungan Perundungan (Bullying)</option>
            </select>
          </div>
          <div class="admin-form-group">
            <label for="editNomorUrut">Nomor Urut Butir *</label>
            <input type="number" name="nomor_urut" id="editNomorUrut" class="admin-form-control" required>
          </div>
        </div>
        <div class="admin-form-group">
          <label for="editTeksSoal">Teks / Pernyataan Butir Instrumen *</label>
          <textarea name="teks_pertanyaan" id="editTeksSoal" class="admin-form-control" style="height:90px; padding:10px 12px; resize:vertical;" required></textarea>
        </div>
      </div>
      <div class="admin-modal-footer">
        <button type="button" class="admin-btn admin-btn-secondary" onclick="closeModal('modalEditSoal')">Batal</button>
        <button type="submit" class="admin-btn admin-btn-primary">✓ Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openTambahSoalModal() {
    const modal = document.getElementById('modalTambahSoal');
    if (modal) {
      modal.classList.add('show');
      document.body.style.overflow = 'hidden';
    }
  }

  function openEditSoalModal(data) {
    document.getElementById('editPertanyaanId').value = data.id;
    document.getElementById('editKategoriSoal').value = data.kategori;
    document.getElementById('editNomorUrut').value = data.nomor_urut;
    document.getElementById('editTeksSoal').value = data.teks_pertanyaan;

    const modal = document.getElementById('modalEditSoal');
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
