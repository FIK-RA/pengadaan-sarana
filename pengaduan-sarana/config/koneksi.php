<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'db_pengaduan';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Koneksi database gagal: ' . $e->getMessage()]);
    exit;
}