<?php
/**
 * absensi_update.php
 * Handler untuk tombol ✓ (Hadir) dan ✗ (Tidak Hadir) pada halaman absensi.
 */
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: absensi.php");
    exit;
}

$id     = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
$redirect = $_POST['redirect'] ?? 'absensi.php';

// Sanitasi redirect — hanya izinkan URL relatif di project ini
if (!preg_match('/^absensi\.php/', $redirect)) {
    $redirect = 'absensi.php';
}

if (!$id || !in_array($action, ['hadir', 'tidak_hadir'])) {
    header("Location: $redirect");
    exit;
}

// Ambil data absensi yang ada
$stmt = $pdo->prepare("SELECT * FROM absensi WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    header("Location: $redirect&error=not_found");
    exit;
}

if ($action === 'hadir') {
    // Jika sudah Hadir / Terlambat → toggle kembali ke Alpha (batal)
    if (in_array($row['status'], ['Hadir', 'Terlambat'])) {
        $pdo->prepare("UPDATE absensi SET status='Alpha', check_in=NULL, check_out=NULL, keterangan='Ditandai tidak hadir via tombol' WHERE id=?")
            ->execute([$id]);
    } else {
        // Tandai hadir: set check_in = 08:00, check_out = 17:00, status Hadir
        $pdo->prepare("UPDATE absensi SET status='Hadir', check_in='08:00:00', check_out='17:00:00', keterangan='Ditandai hadir via tombol' WHERE id=?")
            ->execute([$id]);
    }
} elseif ($action === 'tidak_hadir') {
    // Jika sudah Alpha/Sakit/Izin → toggle kembali ke Hadir (batal)
    if (in_array($row['status'], ['Alpha', 'Sakit', 'Izin'])) {
        $pdo->prepare("UPDATE absensi SET status='Hadir', check_in='08:00:00', check_out='17:00:00', keterangan='Ditandai hadir via tombol' WHERE id=?")
            ->execute([$id]);
    } else {
        // Tandai tidak hadir: hapus jam, status Alpha
        $pdo->prepare("UPDATE absensi SET status='Alpha', check_in=NULL, check_out=NULL, keterangan='Ditandai tidak hadir via tombol' WHERE id=?")
            ->execute([$id]);
    }
}

header("Location: $redirect");
exit;
