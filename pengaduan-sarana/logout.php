<?php
// Hapus session siswa
session_name('SESSION_SISWA');
session_start();
session_destroy();

// Hapus session admin
session_name('SESSION_ADMIN');
session_start();
session_destroy();

header('Location: login.php');
exit;