<?php
/**
 * Riwayat Layanan Konseling & Asesmen Siswa
 * SMA Negeri 5 Maluku Barat Daya
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

require_role('siswa');
$user = auth_user();
$siswa_id = $user['role_id'];
$page_title = 'Riwayat Layanan Saya';

// Ambil riwayat pengajuan & jadwal konseling
$stmt_p = $pdo->prepare("
    SELECT p.*, j.id AS jadwal_id, j.tanggal_jadwal, j.waktu_mulai, j.waktu_selesai, j.tempat_ruangan, j.catatan_jadwal,
           u.nama_lengkap AS nama_guru,
           c.keluhan_utama, c.hasil_evaluasi_bk, c.tindakan_intervensi, c.rekomendasi_tindak_lanjut, c.status_perkembangan
    FROM pengajuan_konseling p
    LEFT JOIN jadwal_konseling j ON p.id = j.pengajuan_id
    LEFT JOIN catatan_konseling c ON j.id = c.jadwal_id
    LEFT JOIN guru_bk g ON j.guru_id = g.id OR p.guru_id = g.id
    LEFT JOIN users u ON g.user_id = u.id
    WHERE p.siswa_id = ?
    ORDER BY p.tanggal_pengajuan DESC
");
$stmt_p->execute([$siswa_id]);
$riwayat_konseling = $stmt_p->fetchAll();

// Ambil riwayat skrining asesmen
$stmt_s = $pdo->prepare("
    SELECT * FROM skrining_asesmen 
    WHERE siswa_id = ? 
    ORDER BY tanggal_pengisian DESC
");
$stmt_s->execute([$siswa_id]);
$riwayat_skrining = $stmt_s->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-clock-history text-primary me-2"></i>Riwayat Layanan Konseling & Deteksi Dini</h4>
        <p class="text-secondary small mb-0">Arsip seluruh rekam jejak bimbingan konseling dan evaluasi perkembangan Anda di SMA Negeri 5 MBD.</p>
    </div>
    <a href="<?= BASE_URL ?>siswa/ajukan_konseling.php" class="btn btn-primary btn-sm">
        <i class="bi bi-calendar-plus me-1"></i> Ajukan Konseling Baru
    </a>
</div>

<!-- Nav Tabs -->
<ul class="nav nav-tabs mb-4 border-bottom" id="riwayatTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-semibold" id="konseling-tab" data-bs-toggle="tab" data-bs-target="#tab-konseling" type="button" role="tab">
            <i class="bi bi-calendar-event me-2"></i> Riwayat Sesi Konseling (<?= count($riwayat_konseling) ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="skrining-tab" data-bs-toggle="tab" data-bs-target="#tab-skrining" type="button" role="tab">
            <i class="bi bi-clipboard2-pulse me-2"></i> Riwayat Deteksi Dini (<?= count($riwayat_skrining) ?>)
        </button>
    </li>
</ul>

<div class="tab-content" id="riwayatTabContent">
    <!-- Tab 1: Riwayat Konseling -->
    <div class="tab-pane fade show active" id="tab-konseling" role="tabpanel">
        <?php if (empty($riwayat_konseling)): ?>
            <div class="card card-base p-5 text-center text-muted">
                <i class="bi bi-calendar-x display-4 d-block mb-3 text-secondary"></i>
                <h5>Belum Ada Riwayat Konseling</h5>
                <p class="small text-secondary mb-3">Anda belum pernah mengajukan jadwal konseling dengan Guru BK.</p>
                <div>
                    <a href="<?= BASE_URL ?>siswa/ajukan_konseling.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-calendar-plus me-1"></i> Ajukan Sekarang
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($riwayat_konseling as $rk): ?>
                    <div class="col-12">
                        <div class="card card-base p-3 p-md-4">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3 pb-2 border-bottom">
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($rk['jenis_konseling']) ?></span>
                                        <?= badge_status($rk['status']) ?>
                                        <?php if ($rk['is_wajib']): ?>
                                            <span class="badge bg-danger">Wajib</span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">Diajukan: <?= tgl_indo($rk['tanggal_pengajuan'], true) ?></small>
                                </div>
                                <div class="text-md-end">
                                    <span class="badge bg-light text-secondary border">
                                        <i class="bi bi-person-badge me-1"></i> Guru BK: <?= htmlspecialchars($rk['nama_guru'] ?? 'Ditentukan Sistem') ?>
                                    </span>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <span class="text-muted small d-block">Preferensi Waktu Diminta:</span>
                                    <div class="fw-semibold text-dark small">
                                        <i class="bi bi-calendar3 me-1 text-primary"></i> <?= tgl_indo($rk['preferensi_tanggal']) ?> (<?= htmlspecialchars($rk['preferensi_waktu']) ?>)
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <span class="text-muted small d-block">Jadwal Pasti Dikonfirmasi Guru BK:</span>
                                    <?php if ($rk['tanggal_jadwal']): ?>
                                        <div class="fw-bold text-success small">
                                            <i class="bi bi-check2-circle me-1"></i> <?= tgl_indo($rk['tanggal_jadwal']) ?>, Pukul <?= substr($rk['waktu_mulai'], 0, 5) ?> - <?= substr($rk['waktu_selesai'], 0, 5) ?> WIT
                                        </div>
                                        <div class="small text-muted mt-1">
                                            <i class="bi bi-geo-alt me-1 text-danger"></i> <?= htmlspecialchars($rk['tempat_ruangan']) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted small fst-italic">Menunggu konfirmasi atau pengaturan waktu dari Guru BK.</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="bg-light p-3 rounded mb-3">
                                <span class="fw-semibold text-secondary small d-block mb-1">Catatan / Hal yang Ingin Dikonsultasikan:</span>
                                <div class="small text-dark"><?= nl2br(htmlspecialchars($rk['alasan_pengajuan'])) ?></div>
                            </div>

                            <?php if ($rk['status'] === 'Ditolak' && !empty($rk['alasan_penolakan'])): ?>
                                <div class="alert alert-danger py-2 px-3 small mb-2">
                                    <strong>Alasan Ditolak / Reschedule:</strong> <?= htmlspecialchars($rk['alasan_penolakan']) ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($rk['catatan_jadwal'])): ?>
                                <div class="alert alert-info py-2 px-3 small mb-2">
                                    <strong>Pesan dari Guru BK:</strong> <?= htmlspecialchars($rk['catatan_jadwal']) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($rk['status'] === 'Selesai' && !empty($rk['rekomendasi_tindak_lanjut'])): ?>
                                <div class="p-3 bg-success-subtle border border-success-subtle rounded mt-2">
                                    <h6 class="fw-bold text-success mb-1"><i class="bi bi-check-circle-fill me-1"></i> Catatan & Rekomendasi Hasil Sesi Konseling:</h6>
                                    <div class="small text-dark mb-2"><strong>Rekomendasi Tindak Lanjut:</strong> <?= nl2br(htmlspecialchars($rk['rekomendasi_tindak_lanjut'])) ?></div>
                                    <div class="small text-secondary"><strong>Status Evaluasi:</strong> <span class="badge bg-secondary"><?= htmlspecialchars($rk['status_perkembangan']) ?></span></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tab 2: Riwayat Skrining -->
    <div class="tab-pane fade" id="tab-skrining" role="tabpanel">
        <?php if (empty($riwayat_skrining)): ?>
            <div class="card card-base p-5 text-center text-muted">
                <i class="bi bi-clipboard2-x display-4 d-block mb-3 text-secondary"></i>
                <h5>Belum Ada Riwayat Deteksi Dini</h5>
                <p class="small text-secondary mb-3">Anda belum pernah mengisi instrumen kuesioner deteksi dini.</p>
                <div>
                    <a href="<?= BASE_URL ?>siswa/deteksi_dini.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-pencil-square me-1"></i> Mulai Pengisian
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="card card-base overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>No</th>
                                <th>Tanggal Pengisian</th>
                                <th>Skor Akademik</th>
                                <th>Skor Psikososial</th>
                                <th>Skor Bullying</th>
                                <th>Total Skor</th>
                                <th>Tingkat Risiko</th>
                                <th>Rekomendasi</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php $no = 1; foreach ($riwayat_skrining as $rs): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= tgl_indo($rs['tanggal_pengisian']) ?></div>
                                        <small class="text-muted"><?= date('H:i', strtotime($rs['tanggal_pengisian'])) ?> WIT</small>
                                    </td>
                                    <td><?= $rs['skor_akademik'] ?> <span class="text-muted">/ 40</span></td>
                                    <td><?= $rs['skor_psikososial'] ?> <span class="text-muted">/ 40</span></td>
                                    <td><?= $rs['skor_bullying'] ?> <span class="text-muted">/ 20</span></td>
                                    <td>
                                        <span class="fw-bold fs-6"><?= $rs['total_skor'] ?></span>
                                        <small class="text-muted">(<?= $rs['persentase_skor'] ?>%)</small>
                                    </td>
                                    <td><?= badge_risiko($rs['tingkat_risiko']) ?></td>
                                    <td>
                                        <span class="badge <?= $rs['status_rekomendasi'] === 'Wajib Konseling' ? 'bg-danger' : 'bg-success' ?>">
                                            <?= htmlspecialchars($rs['status_rekomendasi']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>siswa/hasil_deteksi.php?id=<?= $rs['id'] ?>" class="btn btn-sm btn-outline-primary" title="Lihat Rincian">
                                            <i class="bi bi-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
