<?php
session_name('SESSION_ADMIN');
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once '../config/koneksi.php';

// --- API AJAX Handler ---
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    // 1. Aksi Update Status & Feedback
    if ($_GET['action'] === 'update_status') {
        $id_pelaporan = $_POST['id_pelaporan'] ?? '';
        $status       = $_POST['status'] ?? '';
        $feedback     = trim($_POST['feedback'] ?? '');

        if (empty($id_pelaporan) || empty($status)) {
            echo json_encode(['status' => 'error', 'message' => 'ID Pelaporan dan Status wajib diisi!']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE aspirasi SET status = ?, feedback = ? WHERE id_pelaporan = ?");
            $stmt->execute([$status, $feedback, $id_pelaporan]);

            echo json_encode(['status' => 'success', 'message' => 'Status dan umpan balik berhasil diperbarui!']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data: ' . $e->getMessage()]);
        }
        exit;
    }

    // 2. Aksi Ambil Semua Data Aspirasi (Refresh Data)
    if ($_GET['action'] === 'get_all') {
        $stmt = $pdo->query("
            SELECT ia.*, COALESCE(s.nama, 'Siswa Tidak Ditemukan') AS nama_siswa, COALESCE(s.kelas, '-') AS kelas, k.ket_kategori, a.status, a.feedback 
            FROM input_aspirasi ia
            LEFT JOIN siswa s ON ia.nis = s.nis
            JOIN kategori k ON ia.id_kategori = k.id_kategori
            JOIN aspirasi a ON ia.id_pelaporan = a.id_pelaporan
            ORDER BY ia.id_pelaporan DESC
        ");
        $data = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $data]);
        exit;
    }
}

// Ambil Ringkasan Statistik
$totalAspirasi = $pdo->query("SELECT COUNT(*) FROM aspirasi")->fetchColumn();
$totalMenunggu = $pdo->query("SELECT COUNT(*) FROM aspirasi WHERE status = 'Menunggu'")->fetchColumn();
$totalProses   = $pdo->query("SELECT COUNT(*) FROM aspirasi WHERE status = 'Proses'")->fetchColumn();
$totalSelesai  = $pdo->query("SELECT COUNT(*) FROM aspirasi WHERE status = 'Selesai'")->fetchColumn();

// Ambil Data Awal Aspirasi
$stmtAll = $pdo->query("
    SELECT ia.*, COALESCE(s.nama, 'Siswa Tidak Ditemukan') AS nama_siswa, COALESCE(s.kelas, '-') AS kelas, k.ket_kategori, a.status, a.feedback 
    FROM input_aspirasi ia
    LEFT JOIN siswa s ON ia.nis = s.nis
    JOIN kategori k ON ia.id_kategori = k.id_kategori
    JOIN aspirasi a ON ia.id_pelaporan = a.id_pelaporan
    ORDER BY ia.id_pelaporan DESC
");
$aspirasiList = $stmtAll->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Pengaduan Sarana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container py-5">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1 text-primary">Panel Kelola Aspirasi</h2>
            <p class="text-muted mb-0">Selamat datang, Administrator (<strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong>)</p>
        </div>
        <a href="../logout.php" class="btn btn-outline-danger btn-sm px-3 rounded-pill fw-semibold">Keluar</a>
    </div>

    <!-- Ringkasan Statistik -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="glass-card p-3 text-center">
                <span class="text-muted small fw-semibold d-block mb-1">Total Aspirasi</span>
                <h3 class="fw-bold text-dark mb-0"><?= $totalAspirasi ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card p-3 text-center">
                <span class="text-warning small fw-semibold d-block mb-1">Menunggu</span>
                <h3 class="fw-bold text-warning mb-0" id="stat-menunggu"><?= $totalMenunggu ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card p-3 text-center">
                <span class="text-primary small fw-semibold d-block mb-1">Diproses</span>
                <h3 class="fw-bold text-primary mb-0" id="stat-proses"><?= $totalProses ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card p-3 text-center">
                <span class="text-success small fw-semibold d-block mb-1">Selesai</span>
                <h3 class="fw-bold text-success mb-0" id="stat-selesai"><?= $totalSelesai ?></h3>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Aspirasi -->
    <div class="glass-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0 text-primary">Daftar Pengaduan Siswa</h4>
            <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="loadAdminData()">Refresh Data</button>
        </div>

        <div class="table-responsive">
            <table class="table table-custom align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Siswa / Kelas</th>
                        <th>Kategori & Lokasi</th>
                        <th>Rincian Pengaduan</th>
                        <th>Status</th>
                        <th>Umpan Balik</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="adminTableBody">
                    <?php if (empty($aspirasiList)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada aspirasi yang masuk.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($aspirasiList as $item): ?>
                            <?php 
                                $badgeClass = 'badge-menunggu';
                                if ($item['status'] === 'Proses') $badgeClass = 'badge-proses';
                                if ($item['status'] === 'Selesai') $badgeClass = 'badge-selesai';
                            ?>
                            <tr id="row-<?= $item['id_pelaporan'] ?>">
                                <td class="small"><?= date('d/m/Y', strtotime($item['tanggal'])) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($item['nama_siswa']) ?></strong><br>
                                    <small class="text-muted">NIS: <?= htmlspecialchars($item['nis']) ?> (<?= htmlspecialchars($item['kelas']) ?>)</small>
                                </td>
                                <td>
                                    <span class="fw-semibold"><?= htmlspecialchars($item['ket_kategori']) ?></span><br>
                                    <small class="text-muted"><?= htmlspecialchars($item['lokasi']) ?></small>
                                </td>
                                <td class="small"><?= nl2br(htmlspecialchars($item['ket'])) ?></td>
                                <td><span class="badge badge-status <?= $badgeClass ?>"><?= $item['status'] ?></span></td>
                                <td class="small text-muted"><?= $item['feedback'] ? htmlspecialchars($item['feedback']) : '-' ?></td>
                                <td class="text-center">
                                    <button 
                                        class="btn btn-sm btn-primary-custom px-3"
                                        onclick="openTanggapanModal('<?= $item['id_pelaporan'] ?>', '<?= $item['status'] ?>', '<?= htmlspecialchars(addslashes($item['feedback'] ?? '')) ?>')">
                                        Tanggapi
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tanggapan Admin -->
<div class="modal fade" id="modalTanggapan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card border-0" style="background: #ffffff;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary">Beri Umpan Balik</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formTanggapan">
                    <input type="hidden" id="modal_id_pelaporan" name="id_pelaporan">
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold">Status Pengaduan</label>
                        <select id="modal_status" name="status" class="form-select" required>
                            <option value="Menunggu">Menunggu</option>
                            <option value="Proses">Proses</option>
                            <option value="Selesai">Selesai</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold">Umpan Balik / Catatan Admin</label>
                        <textarea id="modal_feedback" name="feedback" class="form-control" rows="4" placeholder="Tuliskan tindak lanjut atau tanggapan untuk siswa..."></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary-custom px-4">Simpan Tanggapan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let tanggapanModal;

    document.addEventListener('DOMContentLoaded', () => {
        tanggapanModal = new bootstrap.Modal(document.getElementById('modalTanggapan'));
        
        // Auto Polling Admin tiap 3 detik
        setInterval(loadAdminData, 3000);
    });

    function openTanggapanModal(id, currentStatus, currentFeedback) {
        document.getElementById('modal_id_pelaporan').value = id;
        document.getElementById('modal_status').value = currentStatus;
        document.getElementById('modal_feedback').value = currentFeedback;
        tanggapanModal.show();
    }

    // Submit Tanggapan Admin via AJAX
    document.getElementById('formTanggapan').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);

        fetch('index.php?action=update_status', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                tanggapanModal.hide();
                loadAdminData(); // Langsung perbarui tabel & statistik
            } else {
                alert(res.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Terjadi kesalahan koneksi!');
        });
    });

    function loadAdminData() {
        fetch('index.php?action=get_all')
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                renderTableAndStats(res.data);
            }
        })
        .catch(err => console.error(err));
    }

    function renderTableAndStats(data) {
        const tbody = document.getElementById('adminTableBody');
        
        let countMenunggu = 0;
        let countProses = 0;
        let countSelesai = 0;

        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Belum ada aspirasi yang masuk.</td></tr>';
        } else {
            let html = '';
            data.forEach(item => {
                if (item.status === 'Menunggu') countMenunggu++;
                if (item.status === 'Proses') countProses++;
                if (item.status === 'Selesai') countSelesai++;

                let badgeClass = 'badge-menunggu';
                if (item.status === 'Proses') badgeClass = 'badge-proses';
                if (item.status === 'Selesai') badgeClass = 'badge-selesai';

                const dateFormatted = new Date(item.tanggal).toLocaleDateString('id-ID', {
                    day: '2-digit', month: '2-digit', year: 'numeric'
                });

                const safeFeedback = item.feedback ? item.feedback.replace(/'/g, "\\'").replace(/"/g, '&quot;') : '';

                html += `
                    <tr id="row-${item.id_pelaporan}">
                        <td class="small">${dateFormatted}</td>
                        <td>
                            <strong>${escapeHtml(item.nama_siswa)}</strong><br>
                            <small class="text-muted">NIS: ${escapeHtml(item.nis)} (${escapeHtml(item.kelas)})</small>
                        </td>
                        <td>
                            <span class="fw-semibold">${escapeHtml(item.ket_kategori)}</span><br>
                            <small class="text-muted">${escapeHtml(item.lokasi)}</small>
                        </td>
                        <td class="small">${escapeHtml(item.ket).replace(/\n/g, '<br>')}</td>
                        <td><span class="badge badge-status ${badgeClass}">${item.status}</span></td>
                        <td class="small text-muted">${item.feedback ? escapeHtml(item.feedback) : '-'}</td>
                        <td class="text-center">
                            <button 
                                class="btn btn-sm btn-primary-custom px-3"
                                onclick="openTanggapanModal('${item.id_pelaporan}', '${item.status}', '${safeFeedback}')">
                                Tanggapi
                            </button>
                        </td>
                    </tr>
                `;
            });

            if (tbody.innerHTML !== html) {
                tbody.innerHTML = html;
            }
        }

        document.getElementById('stat-menunggu').innerText = countMenunggu;
        document.getElementById('stat-proses').innerText = countProses;
        document.getElementById('stat-selesai').innerText = countSelesai;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
</script>
</body>
</html>