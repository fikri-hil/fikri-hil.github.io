<?php
// layout/header.php - Shared navigation
// ob_start() memastikan tidak ada output terkirim sebelum header() dipanggil
if (!ob_get_level()) ob_start();

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ── Handle period redirect BEFORE any HTML output ──────────────────────────
if (isset($_GET['set_bulan'])) {
    $sb = intval($_GET['set_bulan']);
    // Tidak ada batas atas hardcode — batas nyata = jumlah periode di DB
    if ($sb >= 1) {
        $_SESSION['periode_bulan'] = $sb;
    }
    // Bersihkan buffer agar tidak ada output yang terkirim
    ob_end_clean();
    $redir = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: $redir");
    exit;
}
// ──────────────────────────────────────────────────────────────────────────

$_nama_login = $_SESSION['nama_lengkap'] ?? 'Admin HRD';
$_role_login = $_SESSION['role']         ?? 'Administrator';
$_inisial    = strtoupper(substr($_nama_login, 0, 1));
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'HRD Payroll' ?> - Sistem Penggajian</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #0f172a;
            --sidebar-width: 220px;
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --orange: #f97316;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --bg-light: #f8fafc;
            --card-shadow: 0 1px 3px rgba(0,0,0,.07), 0 1px 2px rgba(0,0,0,.04);
            --card-shadow-hover: 0 4px 12px rgba(0,0,0,.1);
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-light);
            color: #1e293b;
            font-size: 13.5px;
        }
        /* SIDEBAR */
        .sidebar {
            position: fixed; top: 0; left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            display: flex; flex-direction: column;
            z-index: 1000;
            overflow-y: auto;
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: #334155; border-radius: 2px; }
        .sidebar-brand {
            display: flex; align-items: center; gap: 12px;
            padding: 18px 16px 14px;
            border-bottom: 1px solid #1e293b;
        }
        .brand-icon {
            width: 38px; height: 38px;
            background: var(--primary);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; color: #fff; font-size: 15px;
            flex-shrink: 0;
        }
        .brand-text { color: #fff; }
        .brand-text .title { font-weight: 700; font-size: 13px; line-height: 1.2; }
        .brand-text .subtitle { font-size: 10px; color: var(--text-muted); letter-spacing: .5px; }
        .sidebar-section {
            padding: 14px 12px 4px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #475569;
            text-transform: uppercase;
        }
        .sidebar-nav { padding: 0 8px; }
        .nav-item { margin-bottom: 2px; }
        .nav-link-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 10px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            transition: all .15s ease;
        }
        .nav-link-item:hover { background: #1e293b; color: #e2e8f0; }
        .nav-link-item.active { background: var(--primary); color: #fff; }
        .nav-link-item i { width: 18px; text-align: center; font-size: 14px; }
        .sidebar-footer {
            margin-top: auto;
            padding: 12px;
            border-top: 1px solid #1e293b;
        }
        .user-info {
            display: flex; align-items: center; gap: 10px;
            padding: 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: background .15s;
        }
        .user-info:hover { background: #1e293b; }
        .user-avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: var(--primary);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 13px; font-weight: 700;
            flex-shrink: 0;
            overflow: hidden;
        }
        .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-name { font-size: 12px; font-weight: 600; color: #e2e8f0; }
        .user-role { font-size: 10px; color: #64748b; }
        /* TOPBAR */
        .topbar {
            position: fixed; top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: 60px;
            background: #fff;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            z-index: 999;
            box-shadow: 0 1px 3px rgba(0,0,0,.04);
        }
        .topbar-left { display: flex; align-items: center; gap: 14px; }
        .page-title { font-size: 18px; font-weight: 700; color: #0f172a; margin: 0; }
        .topbar-right { display: flex; align-items: center; gap: 14px; }
        .btn-icon {
            width: 36px; height: 36px;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: #fff;
            display: flex; align-items: center; justify-content: center;
            color: #64748b; cursor: pointer;
            position: relative;
            transition: all .15s;
        }
        .btn-icon:hover { background: var(--bg-light); color: #0f172a; }
        .badge-notif {
            position: absolute; top: -2px; right: -2px;
            background: var(--danger);
            color: #fff; font-size: 9px;
            width: 16px; height: 16px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700;
        }
        .period-selector {
            display: flex; align-items: center; gap: 6px;
            padding: 6px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 13px; font-weight: 500;
            color: #475569; background: #fff;
            cursor: pointer; position: relative; user-select: none;
        }
        .period-dropdown {
            position: absolute; top: 44px; left: 0;
            background: #fff; border: 1px solid #e2e8f0;
            border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.12);
            min-width: 190px; z-index: 9999; overflow: hidden; display: none;
        }
        .period-dropdown .pd-title {
            padding: 10px 14px 6px; font-size: 10px; font-weight: 700;
            color: #94a3b8; text-transform: uppercase; letter-spacing: .5px;
        }
        .period-dropdown a {
            display: block; padding: 9px 14px; font-size: 13px;
            color: #475569; text-decoration: none; font-weight: 500; transition: background .12s;
        }
        .period-dropdown a:hover, .period-dropdown a.active-period {
            background: #eff6ff; color: var(--primary); font-weight: 600;
        }
        /* Notif Panel */
        .notif-wrap { position: relative; }
        .notif-panel {
            position: absolute; top: 48px; right: 0;
            background: #fff; border: 1px solid #e2e8f0;
            border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,.13);
            width: 320px; z-index: 9999; display: none; overflow: hidden;
        }
        .notif-panel .notif-header {
            padding: 14px 16px 10px; font-weight: 700; font-size: 14px;
            color: #0f172a; border-bottom: 1px solid #f1f5f9;
            display: flex; justify-content: space-between; align-items: center;
        }
        .notif-panel .notif-item {
            display: flex; gap: 10px; align-items: flex-start;
            padding: 12px 16px; border-bottom: 1px solid #f8fafc;
            transition: background .12s;
        }
        .notif-panel .notif-item:hover { background: #f8fafc; }
        .notif-panel .notif-icon {
            width: 34px; height: 34px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; flex-shrink: 0;
        }
        .notif-panel .notif-text { font-size: 12px; color: #475569; line-height: 1.5; }
        .notif-panel .notif-text strong { color: #0f172a; }
        .notif-panel .notif-time { font-size: 10px; color: #94a3b8; margin-top: 2px; }
        .notif-panel .notif-footer {
            padding: 10px 16px; text-align: center; font-size: 12px;
            color: var(--primary); font-weight: 600; cursor: pointer; transition: background .12s;
        }
        .notif-panel .notif-footer:hover { background: #f8fafc; }
        /* Welcome Popup */
        #welcomeOverlay {
            position: fixed; inset: 0; background: rgba(15,23,42,.45);
            z-index: 99999; display: flex; align-items: center; justify-content: center;
            backdrop-filter: blur(3px); animation: wFadeIn .25s ease;
        }
        @keyframes wFadeIn { from{opacity:0} to{opacity:1} }
        #welcomeBox {
            background: #fff; border-radius: 20px; padding: 40px 48px;
            text-align: center; max-width: 420px; width: 90%;
            box-shadow: 0 24px 64px rgba(0,0,0,.18);
            animation: wSlideUp .3s cubic-bezier(.34,1.56,.64,1);
        }
        @keyframes wSlideUp { from{transform:translateY(30px);opacity:0} to{transform:translateY(0);opacity:1} }
        #welcomeBox .w-icon {
            width: 72px; height: 72px; background: linear-gradient(135deg,#6366f1,#818cf8);
            border-radius: 50%; margin: 0 auto 18px;
            display: flex; align-items: center; justify-content: center;
            font-size: 32px; color: #fff; box-shadow: 0 8px 24px rgba(99,102,241,.35);
        }
        #welcomeBox h2 { font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 6px; }
        #welcomeBox p  { font-size: 14px; color: #64748b; margin-bottom: 24px; line-height: 1.6; }
        #welcomeBox .w-role {
            display: inline-block; background: #eff6ff; color: var(--primary);
            font-weight: 700; font-size: 12px; padding: 4px 14px;
            border-radius: 20px; margin-bottom: 20px;
        }
        #welcomeBox .btn-welcome {
            background: linear-gradient(135deg,#6366f1,#818cf8); color: #fff;
            border: none; padding: 12px 32px; border-radius: 10px;
            font-size: 14px; font-weight: 700; cursor: pointer; width: 100%;
            box-shadow: 0 4px 14px rgba(99,102,241,.35); transition: opacity .15s;
        }
        #welcomeBox .btn-welcome:hover { opacity:.9; }
        .w-progress { height:3px; background:#e2e8f0; border-radius:2px; margin-top:18px; overflow:hidden; }
        .w-progress-bar {
            height:100%; background:linear-gradient(90deg,#6366f1,#818cf8);
            border-radius:2px; animation: wShrink 20s linear forwards;
        }
        @keyframes wShrink { from{width:100%} to{width:0%} }
        /* Override duration via inline style on .w-progress-bar */
        .admin-badge {
            display: flex; align-items: center; gap: 8px;
        }
        .admin-avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: var(--primary);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 12px; overflow: hidden;
        }
        .admin-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .admin-name { font-size: 13px; font-weight: 600; color: #0f172a; }
        .admin-role-text { font-size: 11px; color: #64748b; }
        /* MAIN CONTENT */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: 60px;
            padding: 24px;
            min-height: calc(100vh - 60px);
        }
        /* BREADCRUMB */
        .breadcrumb-wrap { margin-bottom: 18px; }
        .breadcrumb { font-size: 12px; color: #64748b; margin: 0; }
        .breadcrumb-item a { color: #64748b; text-decoration: none; }
        .breadcrumb-item a:hover { color: var(--primary); }
        /* CARDS */
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 18px;
            box-shadow: var(--card-shadow);
            border: 1px solid #f1f5f9;
            height: 100%;
        }
        .stat-icon {
            width: 44px; height: 44px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
        }
        .stat-icon.blue { background: #eff6ff; color: var(--info); }
        .stat-icon.green { background: #f0fdf4; color: var(--success); }
        .stat-icon.orange { background: #fff7ed; color: var(--orange); }
        .stat-icon.red { background: #fef2f2; color: var(--danger); }
        .stat-icon.purple { background: #f5f3ff; color: var(--primary); }
        .stat-value { font-size: 22px; font-weight: 700; color: #0f172a; }
        .stat-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .5px; }
        .stat-sub { font-size: 12px; color: #64748b; }
        /* TABLES */
        .card { border: 1px solid #f1f5f9; border-radius: 12px; box-shadow: var(--card-shadow); }
        .card-header { background: #fff; border-bottom: 1px solid #f1f5f9; padding: 16px 20px; border-radius: 12px 12px 0 0 !important; }
        .card-body { padding: 0; }
        .table { margin: 0; font-size: 13px; }
        .table thead th {
            background: #f8fafc;
            font-weight: 600;
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: #64748b;
            padding: 11px 16px;
            border: none;
            white-space: nowrap;
        }
        .table tbody td { padding: 12px 16px; border-color: #f1f5f9; vertical-align: middle; }
        .table tbody tr:hover { background: #fafbfc; }
        /* BADGES */
        .badge-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-aktif { background: #f0fdf4; color: #16a34a; }
        .badge-nonaktif { background: #f1f5f9; color: #64748b; }
        .badge-hadir { background: #f0fdf4; color: #16a34a; }
        .badge-terlambat { background: #fff7ed; color: #ea580c; }
        .badge-izin { background: #f0f9ff; color: #0369a1; }
        .badge-sakit { background: #faf5ff; color: #7c3aed; }
        .badge-alpha { background: #fef2f2; color: #dc2626; }
        .badge-dibayar { background: #f0fdf4; color: #16a34a; }
        .badge-proses { background: #fff7ed; color: #ea580c; }
        /* BUTTONS */
        .btn-primary-custom {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            display: inline-flex; align-items: center; gap: 6px;
            cursor: pointer; text-decoration: none;
            transition: all .15s;
        }
        .btn-primary-custom:hover { background: var(--primary-dark); color: #fff; }
        .btn-success-custom {
            background: var(--success);
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            display: inline-flex; align-items: center; gap: 6px;
            cursor: pointer; text-decoration: none;
            transition: all .15s;
        }
        .btn-success-custom:hover { background: #16a34a; color: #fff; }
        .btn-outline-custom {
            background: #fff;
            color: #475569;
            border: 1px solid var(--border);
            padding: 7px 14px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 13px;
            display: inline-flex; align-items: center; gap: 6px;
            cursor: pointer; text-decoration: none;
            transition: all .15s;
        }
        .btn-outline-custom:hover { background: var(--bg-light); color: #0f172a; }
        .btn-action {
            width: 30px; height: 30px;
            border-radius: 6px;
            border: none;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 13px; cursor: pointer;
            text-decoration: none;
            transition: all .15s;
        }
        .btn-view { background: #eff6ff; color: var(--info); }
        .btn-edit { background: #fff7ed; color: var(--orange); }
        .btn-delete { background: #fef2f2; color: var(--danger); }
        .btn-view:hover { background: var(--info); color: #fff; }
        .btn-edit:hover { background: var(--orange); color: #fff; }
        .btn-delete:hover { background: var(--danger); color: #fff; }
        /* FILTER BAR */
        .filter-bar {
            background: #fff;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
            box-shadow: var(--card-shadow);
        }
        .form-control, .form-select {
            font-size: 13px;
            border-color: var(--border);
            border-radius: 8px;
            padding: 8px 12px;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99,102,241,.1);
        }
        /* PAGINATION */
        .pagination { margin: 0; }
        .page-link {
            font-size: 12px;
            padding: 6px 11px;
            color: #475569;
            border-color: var(--border);
        }
        .page-item.active .page-link {
            background: var(--primary);
            border-color: var(--primary);
        }
        /* FOOTER */
        .page-footer {
            padding: 16px 24px;
            background: #fff;
            border-top: 1px solid var(--border);
            font-size: 12px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        /* MODAL */
        .modal-header { border-bottom: 1px solid #f1f5f9; }
        .modal-footer { border-top: 1px solid #f1f5f9; }
        .modal-content { border-radius: 14px; border: none; }
        /* AVATAR CIRCLE */
        .avatar-circle {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #818cf8);
            color: #fff;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700;
            flex-shrink: 0;
        }
        .avatar-circle img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        /* SEARCH */
        .search-wrap { position: relative; }
        .search-wrap input { padding-left: 34px; }
        .search-wrap i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; }
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
            .topbar { left: 0; }
        }
        .note-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 12px;
            color: #1d4ed8;
            margin-top: 16px;
        }
        .note-warning {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 12px;
            color: #92400e;
            margin-top: 16px;
        }
        .mini-chart { height: 40px; overflow: hidden; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">HR</div>
        <div class="brand-text">
            <div class="title">HRD PAYROLL</div>
            <div class="subtitle">SISTEM PENGGAJIAN</div>
        </div>
    </div>

    <div class="sidebar-section">Menu Utama</div>
    <div class="sidebar-nav">
        <div class="nav-item">
            <a href="dashboard.php" class="nav-link-item <?= $current_page=='dashboard'?'active':'' ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </div>
    </div>

    <div class="sidebar-section">Data Master</div>
    <div class="sidebar-nav">
        <div class="nav-item">
            <a href="pegawai.php" class="nav-link-item <?= $current_page=='pegawai'?'active':'' ?>">
                <i class="fas fa-user"></i> Pegawai
            </a>
        </div>
        <div class="nav-item">
            <a href="jabatan.php" class="nav-link-item <?= $current_page=='jabatan'?'active':'' ?>">
                <i class="fas fa-briefcase"></i> Jabatan
            </a>
        </div>
        <div class="nav-item">
            <a href="absensi.php" class="nav-link-item <?= $current_page=='absensi'?'active':'' ?>">
                <i class="fas fa-calendar-check"></i> Absensi
            </a>
        </div>
        <div class="nav-item">
            <a href="periode_gaji.php" class="nav-link-item <?= $current_page=='periode_gaji'?'active':'' ?>">
                <i class="fas fa-calendar-alt"></i> Periode Gaji
            </a>
        </div>
    </div>

    <div class="sidebar-section">Penggajian</div>
    <div class="sidebar-nav">
        <div class="nav-item">
            <a href="generate_gaji.php" class="nav-link-item <?= $current_page=='generate_gaji'?'active':'' ?>">
                <i class="fas fa-play-circle"></i> Generate Gaji
            </a>
        </div>
        <div class="nav-item">
            <a href="data_gaji.php" class="nav-link-item <?= $current_page=='data_gaji'?'active':'' ?>">
                <i class="fas fa-money-bill-wave"></i> Data Gaji
            </a>
        </div>
        <div class="nav-item">
            <a href="potongan.php" class="nav-link-item <?= $current_page=='potongan'?'active':'' ?>">
                <i class="fas fa-percent"></i> Potongan
            </a>
        </div>
        <div class="nav-item">
            <a href="tunjangan.php" class="nav-link-item <?= $current_page=='tunjangan'?'active':'' ?>">
                <i class="fas fa-gift"></i> Tunjangan
            </a>
        </div>
    </div>

    <div class="sidebar-section">Laporan</div>
    <div class="sidebar-nav">
        <div class="nav-item">
            <a href="laporan_gaji.php" class="nav-link-item <?= $current_page=='laporan_gaji'?'active':'' ?>">
                <i class="fas fa-file-invoice-dollar"></i> Laporan Gaji
            </a>
        </div>
        <div class="nav-item">
            <a href="laporan_absensi.php" class="nav-link-item <?= $current_page=='laporan_absensi'?'active':'' ?>">
                <i class="fas fa-file-alt"></i> Laporan Absensi
            </a>
        </div>
    </div>

    <div class="sidebar-section">Sistem</div>
    <div class="sidebar-nav">
        <div class="nav-item">
            <a href="user_management.php" class="nav-link-item <?= $current_page=='user_management'?'active':'' ?>">
                <i class="fas fa-users-cog"></i> User Management
            </a>
        </div>
        <div class="nav-item">
            <a href="pengaturan.php" class="nav-link-item <?= $current_page=='pengaturan'?'active':'' ?>">
                <i class="fas fa-cog"></i> Pengaturan
            </a>
        </div>
    </div>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar" style="font-weight:700;font-size:14px;"><?= htmlspecialchars($_inisial) ?></div>
            <div>
                <div class="user-name"><?= htmlspecialchars($_nama_login) ?></div>
                <div class="user-role"><?= htmlspecialchars($_role_login) ?></div>
            </div>
            <i class="fas fa-chevron-down ms-auto" style="color:#475569;font-size:10px;"></i>
        </div>
    </div>
</div>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-left">
        <button class="btn-icon" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <h1 class="page-title"><?= $page_title ?? 'Dashboard' ?></h1>
    </div>
    <div class="topbar-right">

        <!-- ── Period Dropdown ─────────────────────── -->
        <?php
        // Ambil semua periode dari DB, urut kronologis
        if (!isset($pdo)) require_once dirname(__DIR__).'/config/database.php';
        $_periode_db = $pdo->query("
            SELECT id, bulan, tahun,
                   CONCAT(bulan, ' ', tahun) AS label
            FROM periode_gaji
            ORDER BY tahun ASC,
                FIELD(bulan,'Januari','Februari','Maret','April','Mei','Juni',
                           'Juli','Agustus','September','Oktober','November','Desember') ASC
        ")->fetchAll();

        $_total_p    = count($_periode_db);
        $_sel_idx    = intval($_SESSION['periode_bulan'] ?? $_total_p) - 1;
        if ($_sel_idx < 0) $_sel_idx = 0;
        if ($_sel_idx >= $_total_p) $_sel_idx = max(0, $_total_p - 1);

        $label_bulan = $_total_p > 0 ? $_periode_db[$_sel_idx]['label'] : 'Belum Ada Periode';
        ?>
        <div class="period-selector" onclick="togglePeriod(event)" id="periodBtn">
            <i class="fas fa-calendar fa-sm"></i>
            <span id="periodLabel"><?= htmlspecialchars($label_bulan) ?></span>
            <i class="fas fa-chevron-down fa-sm" id="periodChevron"></i>
            <div class="period-dropdown" id="periodDropdown">
                <div class="pd-title">Pilih Periode</div>
                <?php foreach($_periode_db as $pi => $prow): ?>
                <a href="?set_bulan=<?= $pi+1 ?>" class="<?= $_sel_idx===$pi ? 'active-period' : '' ?>">
                    <i class="fas fa-calendar-day me-2" style="color:#6366f1;font-size:11px;"></i>
                    <?= htmlspecialchars($prow['label']) ?>
                </a>
                <?php endforeach; ?>
                <?php if ($_total_p === 0): ?>
                <div style="padding:10px 14px;font-size:12px;color:#94a3b8;">Belum ada periode</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Notification Bell ──────────────────── -->
        <div class="notif-wrap">
            <div class="btn-icon" onclick="toggleNotif(event)" id="notifBtn">
                <i class="fas fa-bell"></i>
                <span class="badge-notif" id="notifBadge">3</span>
            </div>
            <div class="notif-panel" id="notifPanel">
                <div class="notif-header">
                    <span>Notifikasi</span>
                    <span style="font-size:11px;color:#94a3b8;font-weight:400;">3 baru</span>
                </div>
                <div class="notif-item">
                    <div class="notif-icon" style="background:#fef2f2;color:#ef4444;">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div>
                        <div class="notif-text"><strong>5 pegawai terlambat</strong> hari ini masuk setelah pukul 08:00</div>
                        <div class="notif-time"><i class="far fa-clock me-1"></i>Hari ini, 08:30</div>
                    </div>
                </div>
                <div class="notif-item">
                    <div class="notif-icon" style="background:#fef9c3;color:#ca8a04;">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <div class="notif-text"><strong>Generate gaji <?= $label_bulan ?></strong> sudah siap untuk diproses</div>
                        <div class="notif-time"><i class="far fa-clock me-1"></i>Kemarin, 17:00</div>
                    </div>
                </div>
                <div class="notif-item">
                    <div class="notif-icon" style="background:#f0fdf4;color:#16a34a;">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <div class="notif-text"><strong>20 pegawai baru</strong> berhasil ditambahkan ke sistem</div>
                        <div class="notif-time"><i class="far fa-clock me-1"></i>2 hari lalu</div>
                    </div>
                </div>
                <div class="notif-footer" onclick="document.getElementById('notifBadge').textContent='0'; document.getElementById('notifPanel').style.display='none';">
                    Tandai semua sudah dibaca
                </div>
            </div>
        </div>

        <!-- ── Admin User Badge ──────────────────── -->
        <div class="admin-badge" style="position:relative;cursor:pointer;" onclick="toggleUserMenu()">
            <div class="admin-avatar" style="font-weight:700;font-size:14px;"><?= htmlspecialchars($_inisial) ?></div>
            <div>
                <div class="admin-name"><?= htmlspecialchars($_nama_login) ?></div>
                <div class="admin-role-text"><?= htmlspecialchars($_role_login) ?></div>
            </div>
            <i class="fas fa-chevron-down ms-1" style="color:#64748b;font-size:11px;"></i>
            <div id="userDropdown" style="display:none;position:absolute;top:48px;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.1);min-width:180px;z-index:9999;overflow:hidden;">
                <div style="padding:12px 16px;border-bottom:1px solid #f1f5f9;">
                    <div style="font-size:13px;font-weight:600;color:#0f172a;"><?= htmlspecialchars($_nama_login) ?></div>
                    <div style="font-size:11px;color:#64748b;"><?= htmlspecialchars($_role_login) ?></div>
                </div>
                <a href="logout.php" style="display:flex;align-items:center;gap:8px;padding:11px 16px;font-size:13px;color:#ef4444;text-decoration:none;font-weight:500;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-right-from-bracket"></i> Keluar
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ── Welcome Login Popup ─────────────────────────────────── -->
<?php if(!empty($_SESSION['show_welcome'])): ?>
<?php
    $wRole = strtolower($_SESSION['role'] ?? '');
    $wName = $_SESSION['nama_lengkap'] ?? 'Pengguna';
    $greetMap = [
        'administrator' => 'Admin',
        'admin'         => 'Admin',
        'hrd'           => 'HRD',
        'hr'            => 'HRD',
        'keuangan'      => 'Keuangan',
        'finance'       => 'Keuangan',
        'direktur'      => 'Direktur',
        'director'      => 'Direktur',
    ];
    $greetRole = $greetMap[$wRole] ?? ucfirst($wRole);
    $iconMap = [
        'Admin'=>'fa-user-shield','HRD'=>'fa-users-gear',
        'Keuangan'=>'fa-coins','Direktur'=>'fa-briefcase',
    ];
    $wIcon = $iconMap[$greetRole] ?? 'fa-user-check';
    unset($_SESSION['show_welcome']);
?>
<div id="welcomeOverlay" onclick="closeWelcome()">
    <div id="welcomeBox" onclick="event.stopPropagation()">
        <div class="w-icon"><i class="fas <?= $wIcon ?>"></i></div>
        <span class="w-role"><?= $greetRole ?></span>
        <h2>Selamat Datang, <?= $greetRole ?>! 👋</h2>
        <p>Halo <strong><?= htmlspecialchars($wName) ?></strong>,<br>
        Anda berhasil masuk ke Sistem HRD Payroll.<br>
        Semoga hari Anda produktif!</p>
        <button class="btn-welcome" onclick="closeWelcome()">Mulai Bekerja &nbsp;<span id="wCountdown" style="opacity:.7;font-size:12px;font-weight:600;">15s</span> →</button>
        <div class="w-progress"><div class="w-progress-bar" id="wBar" style="animation-duration:15s;"></div></div>
    </div>
</div>
<script>
(function(){
    var totalSec = 15;
    var remaining = totalSec;
    var t = setTimeout(closeWelcome, totalSec * 1000);
    var ticker = setInterval(function(){
        remaining--;
        var ct = document.getElementById('wCountdown');
        if(ct) ct.textContent = remaining + 's';
        if(remaining <= 0) clearInterval(ticker);
    }, 1000);
    function closeWelcome(){
        clearTimeout(t); clearInterval(ticker);
        var el = document.getElementById('welcomeOverlay');
        if(el){ el.style.opacity='0'; el.style.transition='opacity .3s'; setTimeout(function(){el.remove();},300); }
    }
    window.closeWelcome = closeWelcome;
})();
</script>
<?php endif; ?>

<script>
/* ── User menu ── */
function toggleUserMenu() {
    var d = document.getElementById('userDropdown');
    d.style.display = d.style.display === 'none' ? 'block' : 'none';
}
/* ── Period dropdown ── */
function togglePeriod(e) {
    e.stopPropagation();
    var dd = document.getElementById('periodDropdown');
    var ch = document.getElementById('periodChevron');
    var open = dd.style.display === 'block';
    dd.style.display = open ? 'none' : 'block';
    ch.style.transform = open ? '' : 'rotate(180deg)';
    // close notif if open
    document.getElementById('notifPanel').style.display='none';
}
/* ── Notification panel ── */
function toggleNotif(e) {
    e.stopPropagation();
    var p = document.getElementById('notifPanel');
    var open = p.style.display === 'block';
    p.style.display = open ? 'none' : 'block';
    // close period if open
    document.getElementById('periodDropdown').style.display='none';
    document.getElementById('periodChevron').style.transform='';
}
/* ── Close all on outside click ── */
document.addEventListener('click', function(e) {
    // user menu
    var badge = document.querySelector('.admin-badge');
    var ud = document.getElementById('userDropdown');
    if (badge && !badge.contains(e.target)) ud.style.display = 'none';
    // period
    var pb = document.getElementById('periodBtn');
    var pd = document.getElementById('periodDropdown');
    if (pb && !pb.contains(e.target)) {
        pd.style.display='none';
        document.getElementById('periodChevron').style.transform='';
    }
    // notif
    var nb = document.getElementById('notifBtn');
    var np = document.getElementById('notifPanel');
    if (nb && !nb.contains(e.target) && np && !np.contains(e.target)) np.style.display='none';
});
</script>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-content">
