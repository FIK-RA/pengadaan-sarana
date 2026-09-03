<?php
session_name('SESSION_SISWA');
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    header('Location: ../login.php');
    exit;
}

require_once '../config/koneksi.php';

// --- API AJAX Handler ---
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    // 1. Aksi Simpan Aspirasi
    if ($_GET['action'] === 'simpan') {
        $nis         = trim($_POST['nis'] ?? '');
        $id_kategori = $_POST['id_kategori'] ?? '';
        $lokasi      = trim($_POST['lokasi'] ?? '');
        $ket         = trim($_POST['ket'] ?? '');

        $stmtCek = $pdo->prepare("SELECT * FROM siswa WHERE nis = ?");
        $stmtCek->execute([$nis]);

        if ($stmtCek->rowCount() === 0) {
            echo json_encode(['status' => 'error', 'message' => 'NIS tidak ditemukan! Pastikan NIS sudah terdaftar.']);
            exit;
        }

        if (empty($lokasi) || empty($ket)) {
            echo json_encode(['status' => 'error', 'message' => 'Lokasi dan keterangan tidak boleh kosong!']);
            exit;
        }

        try {
            $tanggal = date('Y-m-d');
            $stmtInput = $pdo->prepare("INSERT INTO input_aspirasi (nis, id_kategori, lokasi, ket, tanggal) VALUES (?, ?, ?, ?, ?)");
            $stmtInput->execute([$nis, $id_kategori, $lokasi, $ket, $tanggal]);
            $id_pelaporan = $pdo->lastInsertId();

            $stmtAspirasi = $pdo->prepare("INSERT INTO aspirasi (id_pelaporan, status, id_kategori, feedback) VALUES (?, 'Menunggu', ?, NULL)");
            $stmtAspirasi->execute([$id_pelaporan, $id_kategori]);

            echo json_encode(['status' => 'success', 'message' => 'Aspirasi berhasil dikirim!']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $e->getMessage()]);
        }
        exit;
    }

    // 2. Aksi Ambil Histori Aspirasi per NIS
    if ($_GET['action'] === 'get_histori') {
        $nis = trim($_GET['nis'] ?? '');

        if (empty($nis)) {
            echo json_encode(['status' => 'success', 'data' => []]);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT ia.*, k.ket_kategori, a.status, a.feedback 
            FROM input_aspirasi ia
            JOIN kategori k ON ia.id_kategori = k.id_kategori
            JOIN aspirasi a ON ia.id_pelaporan = a.id_pelaporan
            WHERE ia.nis = ?
            ORDER BY ia.id_pelaporan DESC
        ");
        $stmt->execute([$nis]);
        $data = $stmt->fetchAll();

        echo json_encode(['status' => 'success', 'data' => $data]);
        exit;
    }
}

$kategoriList = $pdo->query("SELECT * FROM kategori")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aspirasi Sarana Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container py-5">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold mb-1 text-primary">Pengaduan Sarana Sekolah</h2>
            <p class="text-muted mb-0">Selamat datang, <strong><?= htmlspecialchars($_SESSION['nama'] ?? 'Siswa') ?></strong> (<?= htmlspecialchars($_SESSION['kelas'] ?? '') ?>)</p>
        </div>
        <a href="../logout.php" class="btn btn-outline-danger btn-sm px-3 rounded-pill fw-semibold">Keluar</a>
    </div>

    <div class="row g-4">
        <!-- Form Input Aspirasi -->
        <div class="col-lg-5">
            <div class="glass-card p-4">
                <h4 class="fw-bold mb-3 text-primary">Form Aspirasi</h4>
                
                <div id="alertContainer"></div>

                <form id="formAspirasi">
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold">NIS Siswa</label>
                        <input type="number" id="nis" name="nis" class="form-control" value="<?= htmlspecialchars($_SESSION['nis'] ?? '') ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold">Kategori Sarana</label>
                        <select id="id_kategori" name="id_kategori" class="form-select" required>
                            <?php foreach ($kategoriList as $kat): ?>
                                <option value="<?= $kat['id_kategori'] ?>"><?= htmlspecialchars($kat['ket_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold">Lokasi Sarana</label>
                        <input type="text" id="lokasi" name="lokasi" class="form-control" placeholder="Contoh: Lab Komputer 2" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold">Keterangan Pengaduan</label>
                        <textarea id="ket" name="ket" class="form-control" rows="3" placeholder="Jelaskan detail kerusakan..." required></textarea>
                    </div>
                    <button type="submit" id="btnSubmit" class="btn btn-primary-custom w-100 py-2">
                        Kirim Aspirasi
                    </button>
                </form>
            </div>
        </div>

        <!-- Histori Aspirasi -->
        <div class="col-lg-7">
            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0 text-primary">Histori & Progres</h4>
                </div>

                <div class="input-group mb-4">
                    <input type="number" id="searchNis" class="form-control" value="<?= htmlspecialchars($_SESSION['nis'] ?? '') ?>" placeholder="Masukkan NIS...">
                    <button class="btn btn-primary-custom px-4" type="button" id="btnCariHistori">Cari Histori</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kategori & Lokasi</th>
                                <th>Pengaduan</th>
                                <th>Status</th>
                                <th>Umpan Balik</th>
                            </tr>
                        </thead>
                        <tbody id="historiTableBody">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Memuat data histori...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/script.js"></script>
</body>
</html>