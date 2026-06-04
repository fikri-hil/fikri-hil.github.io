<?php
// ── Konfigurasi Database ───────────────────────────────────────────────────
$host   = 'localhost';
$dbuser = 'root';
$dbpass = '';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// $pdo  → database utama aplikasi (pegawai, gaji, absensi, dll)
try {
    $pdo = new PDO("mysql:host=$host;dbname=hrd_payroll;charset=utf8mb4", $dbuser, $dbpass, $options);
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;background:#fef2f2;color:#dc2626;
        padding:24px;border-radius:10px;margin:30px auto;max-width:500px;border:1px solid #fca5a5;">
        <strong>❌ Koneksi Database Gagal (hrd_payroll)</strong><br><br>
        <code>'.htmlspecialchars($e->getMessage()).'</code><br><br>
        <small>Pastikan XAMPP aktif dan database <b>hrd_payroll</b> sudah diimport.</small>
    </div>');
}

// $pdo_auth → database autentikasi user (tb_user di login_hrd)
try {
    $pdo_auth = new PDO("mysql:host=$host;dbname=login_hrd;charset=utf8mb4", $dbuser, $dbpass, $options);
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;background:#fef2f2;color:#dc2626;
        padding:24px;border-radius:10px;margin:30px auto;max-width:500px;border:1px solid #fca5a5;">
        <strong>❌ Koneksi Database Gagal (login_hrd)</strong><br><br>
        <code>'.htmlspecialchars($e->getMessage()).'</code><br><br>
        <small>Pastikan XAMPP aktif dan database <b>login_hrd</b> sudah diimport.</small>
    </div>');
}

// ── Helper cek login ───────────────────────────────────────────────────────
function cekLogin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}
