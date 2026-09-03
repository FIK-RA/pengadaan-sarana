<?php

// Bedakan nama session berdasarkan aksi form login
if (isset($_POST['action']) && $_POST['action'] === 'login_admin') {
    session_name('SESSION_ADMIN');
} else {
    session_name('SESSION_SISWA');
}

session_start();
require_once 'config/koneksi.php';

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/index.php');
        exit;
    } elseif ($_SESSION['role'] === 'siswa') {
        header('Location: siswa/index.php');
        exit;
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Logika Login Siswa (Validasi NIS + Nama)
    if ($action === 'login_siswa') {
        $nis  = trim($_POST['nis'] ?? '');
        $nama = trim($_POST['nama'] ?? '');

        $stmt = $pdo->prepare("SELECT * FROM siswa WHERE nis = ? AND LOWER(nama) = LOWER(?)");
        $stmt->execute([$nis, $nama]);
        $siswa = $stmt->fetch();

        if ($siswa) {
            $_SESSION['role']  = 'siswa';
            $_SESSION['nis']   = $siswa['nis'];
            $_SESSION['nama']  = $siswa['nama'];
            $_SESSION['kelas'] = $siswa['kelas'];
            header('Location: siswa/index.php');
            exit;
        } else {
            $error = 'Kombinasi NIS dan Nama tidak cocok atau belum terdaftar!';
        }
    }

    // 2. Logika Login Admin
    elseif ($action === 'login_admin') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && $password === $admin['password']) {
            $_SESSION['role']     = 'admin';
            $_SESSION['id_admin'] = $admin['id_admin'];
            $_SESSION['username'] = $admin['username'];
            header('Location: admin/index.php');
            exit;
        } else {
            $error = 'Username atau password Admin salah!';
        }
    }

    // 3. Logika Pendaftaran Siswa
    elseif ($action === 'register_siswa') {
        $nis   = trim($_POST['nis'] ?? '');
        $nama  = trim($_POST['nama'] ?? '');
        $kelas = trim($_POST['kelas'] ?? '');

        if (empty($nis) || empty($nama) || empty($kelas)) {
            $error = 'Semua field pendaftaran wajib diisi!';
        } else {
            $stmtCek = $pdo->prepare("SELECT * FROM siswa WHERE nis = ?");
            $stmtCek->execute([$nis]);

            if ($stmtCek->rowCount() > 0) {
                $error = 'NIS sudah terdaftar! Silakan langsung login.';
            } else {
                $stmtIns = $pdo->prepare("INSERT INTO siswa (nis, nama, kelas) VALUES (?, ?, ?)");
                $stmtIns->execute([$nis, $nama, $kelas]);
                $success = 'Pendaftaran berhasil! Silakan login menggunakan NIS dan Nama Anda.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Autentikasi - Pengaduan Sarana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .form-box { display: none; }
        .form-box.active { display: block; }
    </style>
</head>
<body>

<div class="container auth-container py-5">
    <div class="col-md-6 col-lg-4">
        <div class="glass-card p-4">
            
            <?php if ($error): ?>
                <div class="alert alert-danger py-2 rounded-3" role="alert"><small><?= htmlspecialchars($error) ?></small></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success py-2 rounded-3" role="alert"><small><?= htmlspecialchars($success) ?></small></div>
            <?php endif; ?>

            <!-- FORM 1: LOGIN SISWA -->
            <div id="box-login-siswa" class="form-box active">
                <h4 class="fw-bold text-center mb-1 text-primary">Masuk Siswa</h4>
                <p class="text-muted text-center small mb-4">Masukkan NIS & Nama Anda</p>
                
                <form action="login.php" method="POST">
                    <input type="hidden" name="action" value="login_siswa">
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold">Nomor Induk Siswa (NIS)</label>
                        <input type="number" name="nis" class="form-control" placeholder="Contoh: 12345" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" placeholder="Contoh: Budi Santoso" required>
                    </div>
                    <button type="submit" class="btn btn-primary-custom w-100 py-2 mb-4">Masuk</button>
                </form>

                <div class="border-top pt-3 text-center d-flex justify-content-between">
                    <a href="#" onclick="switchForm('box-daftar-siswa')" class="auth-link">Daftar sebagai Siswa</a>
                    <a href="#" onclick="switchForm('box-login-admin')" class="auth-link">Login sebagai Admin</a>
                </div>
            </div>

            <!-- FORM 2: LOGIN ADMIN -->
            <div id="box-login-admin" class="form-box">
                <h4 class="fw-bold text-center mb-1 text-primary">Masuk Admin</h4>
                <p class="text-muted text-center small mb-4">Akses khusus petugas administrator</p>
                
                <form action="login.php" method="POST">
                    <input type="hidden" name="action" value="login_admin">
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                    </div>
                    <button type="submit" class="btn btn-primary-custom w-100 py-2 mb-4">Masuk Admin</button>
                </form>

                <div class="border-top pt-3 text-center d-flex justify-content-between">
                    <a href="#" onclick="switchForm('box-login-siswa')" class="auth-link">Sudah punya akun siswa?</a>
                    <a href="#" onclick="switchForm('box-daftar-siswa')" class="auth-link">Daftar sebagai Siswa</a>
                </div>
            </div>

            <!-- FORM 3: DAFTAR SISWA -->
            <div id="box-daftar-siswa" class="form-box">
                <h4 class="fw-bold text-center mb-1 text-primary">Pendaftaran Siswa</h4>
                <p class="text-muted text-center small mb-4">Lengkapi data untuk membuat akun</p>
                
                <form action="login.php" method="POST">
                    <input type="hidden" name="action" value="register_siswa">
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold">Nomor Induk Siswa (NIS)</label>
                        <input type="number" name="nis" class="form-control" placeholder="Contoh: 12347" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" placeholder="Contoh: Budi Santoso" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold">Kelas</label>
                        <input type="text" name="kelas" class="form-control" placeholder="Contoh: XII RPL 1" required>
                    </div>
                    <button type="submit" class="btn btn-primary-custom w-100 py-2 mb-4">Daftar Sekarang</button>
                </form>

                <div class="border-top pt-3 text-center d-flex justify-content-between">
                    <a href="#" onclick="switchForm('box-login-siswa')" class="auth-link">Sudah punya akun siswa?</a>
                    <a href="#" onclick="switchForm('box-login-admin')" class="auth-link">Login sebagai Admin</a>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    function switchForm(targetId) {
        document.querySelectorAll('.form-box').forEach(box => {
            box.classList.remove('active');
        });
        document.getElementById(targetId).classList.add('active');
    }
</script>
</body>
</html>