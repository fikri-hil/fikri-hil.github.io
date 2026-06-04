<?php
/**
 * Setup password - jalankan SEKALI lalu HAPUS file ini
 * URL: http://localhost/hrd_payroll/setup.php
 */
require_once 'config/database.php';

$password = 'password'; // password default
$hash = password_hash($password, PASSWORD_BCRYPT);

$pdo_auth->prepare("UPDATE tb_user SET password = ?")->execute([$hash]);
$count = $pdo_auth->query("SELECT COUNT(*) FROM tb_user")->fetchColumn();
$users = $pdo_auth->query("SELECT username, nama_lengkap, role FROM tb_user")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Setup - HRD Payroll</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family:'Plus Jakarta Sans',sans-serif; background:#f8fafc; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
        .card { background:#fff; border-radius:16px; padding:36px 40px; max-width:480px; width:100%; box-shadow:0 4px 24px rgba(0,0,0,.08); border:1px solid #f1f5f9; }
        h2 { color:#16a34a; font-size:20px; margin-bottom:8px; }
        p  { color:#475569; font-size:14px; }
        code { background:#f1f5f9; padding:2px 8px; border-radius:6px; font-size:15px; font-weight:700; color:#6366f1; }
        table { width:100%; border-collapse:collapse; margin:16px 0; font-size:13px; }
        th { background:#f8fafc; padding:8px 12px; text-align:left; color:#64748b; font-weight:600; border-bottom:1px solid #f1f5f9; }
        td { padding:10px 12px; border-bottom:1px solid #f8fafc; }
        .warning { background:#fef2f2; border:1px solid #fca5a5; border-radius:10px; padding:12px 16px; color:#dc2626; font-size:13px; font-weight:600; margin:16px 0; }
        .btn { display:inline-flex; align-items:center; gap:8px; background:#6366f1; color:#fff; padding:11px 22px; border-radius:10px; text-decoration:none; font-weight:700; font-size:14px; margin-top:8px; }
        .btn:hover { background:#4f46e5; color:#fff; }
    </style>
</head>
<body>
<div class="card">
    <h2>✅ Setup Password Berhasil!</h2>
    <p><strong><?= $count ?></strong> user telah diset dengan password: <code>password</code></p>

    <table>
        <thead><tr><th>Username</th><th>Nama</th><th>Role</th><th>Password</th></tr></thead>
        <tbody>
        <?php foreach($users as $u): ?>
        <tr>
            <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
            <td><?= htmlspecialchars($u['nama_lengkap']) ?></td>
            <td><?= htmlspecialchars($u['role']) ?></td>
            <td><code style="font-size:12px;">password</code></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="warning">⚠️ Hapus file <strong>setup.php</strong> ini sekarang demi keamanan!</div>

    <a href="login.php" class="btn">→ Ke Halaman Login</a>
</div>
</body>
</html>
