<?php
session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php'); exit;
}

require_once 'config/database.php';

// ── Load PHPMailer jika tersedia ─────────────────────────────────────────
$use_phpmailer = false;
foreach ([
    __DIR__ . '/vendor/phpmailer/phpmailer/src/',
    __DIR__ . '/phpmailer/src/',
    __DIR__ . '/PHPMailer/src/',
] as $pm_path) {
    if (file_exists($pm_path . 'PHPMailer.php')) {
        require_once $pm_path . 'PHPMailer.php';
        require_once $pm_path . 'SMTP.php';
        require_once $pm_path . 'Exception.php';
        $use_phpmailer = true; break;
    }
}

// ── SMTP Config — EDIT BAGIAN INI ─────────────────────────────────────────
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_SECURE',   'tls');
define('SMTP_USER',     'fikrihilali3009@gmail.com');    // ← ganti email Gmail Anda
define('SMTP_PASS',     'xxxx xxxx xxxx xxxx');    // ← App Password 16 karakter
define('SMTP_FROM',     'emailanda@gmail.com');    // ← sama dengan SMTP_USER
define('SMTP_FROMNAME', 'HRD Payroll System');

// ── Siapkan kolom tambahan di tb_user (ALTER IF NOT EXISTS) ──────────────
try {
    // MySQL 8+ support IF NOT EXISTS; untuk versi lama pakai try-catch per kolom
    foreach (['otp_code VARCHAR(6) NULL', 'otp_expires DATETIME NULL', 'email VARCHAR(150) NULL'] as $col) {
        $colName = explode(' ', $col)[0];
        try {
            $pdo_auth->query("ALTER TABLE tb_user ADD COLUMN $col");
        } catch (Exception $e) { /* kolom sudah ada */ }
    }
} catch (Exception $e) {}

// ── Helper: kirim OTP via email ───────────────────────────────────────────
function kirimOTP($to_email, $to_name, $otp) {
    global $use_phpmailer;
    $subject = '[HRD Payroll] Kode OTP Reset Password Anda';
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <style>
      body{font-family:Arial,sans-serif;background:#f1f5f9;margin:0;padding:24px;}
      .wrap{max-width:480px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);}
      .hdr{background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:32px 36px;text-align:center;}
      .hdr h1{color:#fff;font-size:20px;margin:0;font-weight:800;}
      .hdr p{color:rgba(255,255,255,.8);font-size:13px;margin:6px 0 0;}
      .body{padding:32px 36px;}
      .otp-box{background:#eef2ff;border:2px dashed #6366f1;border-radius:12px;padding:24px;text-align:center;margin:20px 0;}
      .otp-code{font-size:42px;font-weight:800;color:#6366f1;letter-spacing:12px;font-family:monospace;}
      .otp-note{font-size:12px;color:#64748b;margin-top:8px;}
      .warn{background:#fef9c3;border:1px solid #fde68a;border-radius:8px;padding:12px;font-size:12px;color:#92400e;margin-top:16px;}
      .footer{padding:18px 36px;background:#f8fafc;font-size:11px;color:#94a3b8;text-align:center;border-top:1px solid #f1f5f9;}
    </style></head><body>
    <div class="wrap">
      <div class="hdr"><h1>🔐 Reset Password</h1><p>HRD Payroll — Sistem Penggajian</p></div>
      <div class="body">
        <p style="color:#475569;font-size:14px;">Halo <strong>' . htmlspecialchars($to_name) . '</strong>,</p>
        <p style="color:#64748b;font-size:13px;line-height:1.6;">Kami menerima permintaan reset password. Gunakan kode OTP di bawah ini:</p>
        <div class="otp-box">
          <div class="otp-code">' . $otp . '</div>
          <div class="otp-note">Kode berlaku selama <strong>10 menit</strong></div>
        </div>
        <div class="warn">⚠️ Jangan bagikan kode ini kepada siapapun, termasuk tim HRD.</div>
        <p style="color:#94a3b8;font-size:12px;margin-top:18px;">Jika Anda tidak meminta ini, abaikan email ini.</p>
      </div>
      <div class="footer">&copy; ' . date('Y') . ' HRD Payroll System &bull; Pesan otomatis, jangan dibalas</div>
    </div></body></html>';

    if ($use_phpmailer) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE === 'ssl'
                ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom(SMTP_FROM, SMTP_FROMNAME);
            $mail->addAddress($to_email, $to_name);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = "Kode OTP Anda: $otp (berlaku 10 menit). Jangan bagikan ke siapapun.";
            $mail->send();
            return ['ok' => true];
        } catch (Exception $e) {
            return ['ok' => false, 'err' => $mail->ErrorInfo];
        }
    } else {
        $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . SMTP_FROMNAME . " <" . SMTP_FROM . ">\r\n";
        $ok = @mail($to_email, $subject, $html, $headers);
        return ['ok' => $ok, 'err' => 'Gunakan PHPMailer untuk hasil terbaik'];
    }
}

// ── Cari email user: tb_user.email ATAU cross-ref ke pegawai.email ─────────
function cariEmailUser($pdo_auth, $pdo, $user_id, $nama_lengkap) {
    // 1. Cek apakah tb_user sudah punya email
    try {
        $st = $pdo_auth->prepare("SELECT email FROM tb_user WHERE id=? AND email IS NOT NULL AND email != ''");
        $st->execute([$user_id]);
        $row = $st->fetch();
        if ($row && $row['email']) return $row['email'];
    } catch (Exception $e) {}

    // 2. Cross-reference ke tabel pegawai berdasarkan nama lengkap
    try {
        $st = $pdo->prepare("SELECT email FROM pegawai WHERE nama = ? AND email IS NOT NULL AND email != '' LIMIT 1");
        $st->execute([$nama_lengkap]);
        $row = $st->fetch();
        if ($row && $row['email']) {
            // Simpan ke tb_user supaya berikutnya langsung ketemu
            try {
                $pdo_auth->prepare("UPDATE tb_user SET email=? WHERE id=?")
                         ->execute([$row['email'], $user_id]);
            } catch (Exception $e) {}
            return $row['email'];
        }
    } catch (Exception $e) {}

    return null;
}

// ═══════════════════════════════════════════════════════════════════════════
// STATE & HANDLERS
// ═══════════════════════════════════════════════════════════════════════════
$step    = $_SESSION['fp_step'] ?? 1;
$message = '';
$error   = '';

if (isset($_GET['reset'])) {
    unset($_SESSION['fp_step'], $_SESSION['fp_user_id'], $_SESSION['fp_email'],
          $_SESSION['fp_otp_verified'], $_SESSION['fp_nama']);
    header('Location: forgot_password.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // ── STEP 1: Cari User by Username → temukan email ────────────────────
    if ($_POST['action'] === 'verify_email') {
        $input = trim($_POST['username_or_email'] ?? '');

        if ($input === '') {
            $error = 'Username atau email wajib diisi.';
        } else {
            // Cari di tb_user by username ATAU email
            $stmt = $pdo_auth->prepare(
                "SELECT * FROM tb_user WHERE username=? OR email=? LIMIT 1"
            );
            $stmt->execute([$input, $input]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'Username atau email tidak ditemukan dalam sistem.';
            } else {
                // Cari email
                $email = cariEmailUser($pdo_auth, $pdo, $user['id'], $user['nama_lengkap']);

                if (!$email) {
                    // Jika tidak ada email, minta input email manual
                    $_SESSION['fp_step']    = 'input_email';
                    $_SESSION['fp_user_id'] = $user['id'];
                    $_SESSION['fp_nama']    = $user['nama_lengkap'];
                    $step = 'input_email';
                } else {
                    // Punya email → langsung kirim OTP
                    $otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $expires = date('Y-m-d H:i:s', time() + 600);
                    try {
                        $pdo_auth->prepare("UPDATE tb_user SET otp_code=?, otp_expires=?, email=? WHERE id=?")
                                 ->execute([$otp, $expires, $email, $user['id']]);
                    } catch (Exception $e) {}

                    $result = kirimOTP($email, $user['nama_lengkap'], $otp);
                    $_SESSION['fp_step']    = 2;
                    $_SESSION['fp_user_id'] = $user['id'];
                    $_SESSION['fp_email']   = $email;
                    $_SESSION['fp_nama']    = $user['nama_lengkap'];
                    $step = 2;

                    if ($result['ok']) {
                        $message = 'Kode OTP telah dikirim ke <strong>' . htmlspecialchars(maskEmail($email)) . '</strong>';
                    } else {
                        // Dev fallback: tampilkan OTP
                        $message = '⚠️ Email gagal terkirim. SMTP belum dikonfigurasi.<br>
                            <strong>Kode OTP untuk testing: ' . $otp . '</strong><br>
                            <small>Edit SMTP_USER & SMTP_PASS di forgot_password.php</small>';
                    }
                }
            }
        }
    }

    // ── STEP 1b: Input email manual (jika user tidak punya email) ─────────
    elseif ($_POST['action'] === 'set_email') {
        if (empty($_SESSION['fp_user_id'])) { header('Location: forgot_password.php'); exit; }
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
            $step  = 'input_email';
        } else {
            $otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires = date('Y-m-d H:i:s', time() + 600);
            try {
                $pdo_auth->prepare("UPDATE tb_user SET otp_code=?, otp_expires=?, email=? WHERE id=?")
                         ->execute([$otp, $expires, $email, $_SESSION['fp_user_id']]);
            } catch (Exception $e) {}

            $nama   = $_SESSION['fp_nama'] ?? 'Pengguna';
            $result = kirimOTP($email, $nama, $otp);
            $_SESSION['fp_step']  = 2;
            $_SESSION['fp_email'] = $email;
            $step = 2;

            if ($result['ok']) {
                $message = 'Kode OTP telah dikirim ke <strong>' . htmlspecialchars(maskEmail($email)) . '</strong>';
            } else {
                $message = '⚠️ SMTP belum dikonfigurasi. <strong>OTP testing: ' . $otp . '</strong>';
            }
        }
    }

    // ── STEP 2: Verifikasi OTP ───────────────────────────────────────────
    elseif ($_POST['action'] === 'verify_otp') {
        if (empty($_SESSION['fp_user_id'])) { header('Location: forgot_password.php'); exit; }
        $otp_input = trim(implode('', $_POST['otp_digit'] ?? []));
        $stmt = $pdo_auth->prepare("SELECT otp_code, otp_expires FROM tb_user WHERE id=?");
        $stmt->execute([$_SESSION['fp_user_id']]);
        $row = $stmt->fetch();

        if (!$row || !$row['otp_code']) {
            $error = 'Sesi tidak valid. Mulai ulang.'; $step = 2;
        } elseif (new DateTime() > new DateTime($row['otp_expires'])) {
            $error = 'Kode OTP sudah kadaluarsa. Minta ulang.'; $step = 2;
        } elseif ($otp_input !== $row['otp_code']) {
            $error = 'Kode OTP salah. Coba lagi.'; $step = 2;
        } else {
            $_SESSION['fp_step'] = 3; $_SESSION['fp_otp_verified'] = true; $step = 3;
        }
    }

    // ── STEP 2: Resend OTP ───────────────────────────────────────────────
    elseif ($_POST['action'] === 'resend_otp') {
        if (empty($_SESSION['fp_user_id']) || empty($_SESSION['fp_email'])) {
            header('Location: forgot_password.php'); exit;
        }
        $otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', time() + 600);
        try {
            $pdo_auth->prepare("UPDATE tb_user SET otp_code=?, otp_expires=? WHERE id=?")
                     ->execute([$otp, $expires, $_SESSION['fp_user_id']]);
        } catch (Exception $e) {}
        $nama   = $_SESSION['fp_nama'] ?? 'Pengguna';
        $result = kirimOTP($_SESSION['fp_email'], $nama, $otp);
        $step   = 2;
        $message = $result['ok']
            ? 'Kode OTP baru telah dikirim.'
            : '⚠️ Gagal kirim. OTP testing: <strong>' . $otp . '</strong>';
    }

    // ── STEP 3: Simpan Password Baru ─────────────────────────────────────
    elseif ($_POST['action'] === 'reset_password') {
        if (empty($_SESSION['fp_user_id']) || empty($_SESSION['fp_otp_verified'])) {
            header('Location: forgot_password.php'); exit;
        }
        $np = $_POST['new_password'] ?? '';
        $cp = $_POST['confirm_password'] ?? '';
        if (strlen($np) < 6) {
            $error = 'Password minimal 6 karakter.'; $step = 3;
        } elseif ($np !== $cp) {
            $error = 'Konfirmasi password tidak cocok.'; $step = 3;
        } else {
            $hash = password_hash($np, PASSWORD_BCRYPT);
            $pdo_auth->prepare("UPDATE tb_user SET password=?, otp_code=NULL, otp_expires=NULL WHERE id=?")
                     ->execute([$hash, $_SESSION['fp_user_id']]);
            unset($_SESSION['fp_step'], $_SESSION['fp_user_id'], $_SESSION['fp_email'],
                  $_SESSION['fp_otp_verified'], $_SESSION['fp_nama']);
            $_SESSION['fp_success'] = 'Password berhasil diubah. Silakan login.';
            header('Location: login.php'); exit;
        }
    }
}

if (empty($_SESSION['fp_step'])) $step = 1;

// ── Helper masking email ───────────────────────────────────────────────────
function maskEmail($email) {
    $parts = explode('@', $email);
    $name  = $parts[0];
    $domain = $parts[1] ?? '';
    $visible = substr($name, 0, 2);
    return $visible . str_repeat('*', max(2, strlen($name)-2)) . '@' . $domain;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password – HRD Payroll</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { min-height:100vh; font-family:'Plus Jakarta Sans',sans-serif; background:#0f172a; display:flex; align-items:center; justify-content:center; overflow:hidden; position:relative; }
        .blob { position:fixed; border-radius:50%; filter:blur(90px); opacity:.30; pointer-events:none; }
        .blob-1 { width:520px;height:520px;background:#6366f1;top:-160px;left:-160px; }
        .blob-2 { width:420px;height:420px;background:#8b5cf6;bottom:-120px;right:-120px; }
        .blob-3 { width:280px;height:280px;background:#3b82f6;top:50%;left:50%;transform:translate(-50%,-50%); }
        .card { position:relative;z-index:10;background:rgba(30,41,59,.88);backdrop-filter:blur(24px);border:1px solid rgba(148,163,184,.12);border-radius:24px;padding:44px 40px;width:100%;max-width:420px;box-shadow:0 28px 64px rgba(0,0,0,.5);animation:cardIn .35s ease; }
        @keyframes cardIn { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
        .logo { display:flex;align-items:center;gap:12px;margin-bottom:28px; }
        .logo-box { width:48px;height:48px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:17px;font-weight:800;color:#fff; }
        .logo-name { font-size:15px;font-weight:800;color:#f1f5f9;letter-spacing:.4px; }
        .logo-sub  { font-size:10px;font-weight:600;color:#94a3b8;letter-spacing:2px;text-transform:uppercase; }
        /* Steps */
        .steps-grid { display:grid;grid-template-columns:1fr auto 1fr auto 1fr;align-items:center;width:100%;margin-bottom:28px; }
        .step-col { display:flex;flex-direction:column;align-items:center;gap:4px; }
        .step-dot { width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;transition:all .3s; }
        .step-dot.done   { background:#6366f1;color:#fff; }
        .step-dot.active { background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;box-shadow:0 0 0 4px rgba(99,102,241,.2); }
        .step-dot.idle   { background:#1e293b;color:#475569; }
        .step-line { flex:1;height:2px;background:#1e293b;transition:background .3s; }
        .step-line.done { background:#6366f1; }
        .step-label { font-size:9px;color:#64748b;text-align:center;margin-top:2px;white-space:nowrap; }
        .step-col.active .step-label { color:#818cf8;font-weight:600; }
        .step-icon { width:56px;height:56px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:24px;color:#fff;margin-bottom:18px;box-shadow:0 6px 20px rgba(99,102,241,.35); }
        h1 { font-size:22px;font-weight:700;color:#f1f5f9;margin-bottom:4px; }
        .sub { font-size:13px;color:#94a3b8;margin-bottom:22px;line-height:1.6; }
        /* Alerts */
        .alert-s { background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);border-radius:10px;padding:11px 14px;color:#86efac;font-size:13px;margin-bottom:16px;display:flex;align-items:flex-start;gap:8px;line-height:1.6; }
        .alert-e { background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);border-radius:10px;padding:11px 14px;color:#fca5a5;font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:8px; }
        /* Form */
        label { display:block;font-size:13px;font-weight:600;color:#cbd5e1;margin-bottom:6px; }
        .field { position:relative;margin-bottom:18px; }
        .field input { width:100%;background:rgba(15,23,42,.65);border:1.5px solid rgba(148,163,184,.18);border-radius:12px;padding:12px 16px;font-size:14px;font-family:inherit;color:#f1f5f9;outline:none;transition:border-color .2s,box-shadow .2s; }
        .field input::placeholder { color:#475569; }
        .field input:focus { border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.2); }
        .field input.err { border-color:#ef4444; }
        .field-icon { position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#475569;font-size:14px;pointer-events:none; }
        .field input.has-icon { padding-left:42px; }
        .eye { position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#64748b;font-size:15px;padding:0; }
        .eye:hover { color:#94a3b8; }
        /* OTP */
        .otp-row { display:flex;gap:10px;justify-content:center;margin:20px 0; }
        .otp-row input { width:48px;height:56px;background:rgba(15,23,42,.7);border:1.5px solid rgba(148,163,184,.2);border-radius:12px;font-size:22px;font-weight:800;color:#f1f5f9;text-align:center;outline:none;transition:border-color .2s,box-shadow .2s;font-family:inherit; }
        .otp-row input:focus { border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.25); }
        .otp-row input.filled { border-color:#6366f1;background:rgba(99,102,241,.12); }
        .email-badge { background:rgba(99,102,241,.1);border:1px solid rgba(99,102,241,.2);border-radius:10px;padding:10px 14px;margin-bottom:16px;display:flex;align-items:center;gap:8px;font-size:13px;color:#a5b4fc; }
        /* Buttons */
        .btn-sub  { width:100%;padding:13px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;font-family:inherit;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:opacity .2s,transform .1s;margin-bottom:12px; }
        .btn-sub:hover { opacity:.9; }
        .btn-back { width:100%;padding:11px;background:transparent;color:#94a3b8;border:1.5px solid rgba(148,163,184,.2);border-radius:12px;font-size:14px;font-weight:600;font-family:inherit;cursor:pointer;text-align:center;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s; }
        .btn-back:hover { border-color:#6366f1;color:#818cf8; }
        .resend-wrap { text-align:center;margin-top:12px; }
        .resend-btn  { background:none;border:none;color:#6366f1;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit; }
        .resend-btn:hover { text-decoration:underline; }
        .resend-timer { font-size:13px;color:#64748b; }
        /* Strength */
        .pw-str { margin-top:4px;font-size:11px; }
        .str-bar { height:4px;border-radius:2px;background:#1e293b;margin-top:4px;overflow:hidden; }
        .str-fill { height:100%;border-radius:2px;transition:width .3s,background .3s;width:0%; }
    </style>
</head>
<body>
<div class="blob blob-1"></div><div class="blob blob-2"></div><div class="blob blob-3"></div>

<div class="card">
    <div class="logo">
        <div class="logo-box">HR</div>
        <div><div class="logo-name">HRD PAYROLL</div><div class="logo-sub">Sistem Penggajian</div></div>
    </div>

    <!-- Step indicator -->
    <?php $sn = is_numeric($step) ? intval($step) : 1; ?>
    <div class="steps-grid">
        <div class="step-col <?= $sn>=1?'active':'' ?>">
            <div class="step-dot <?= $sn>1?'done':($sn==1?'active':'idle') ?>"><?= $sn>1?'<i class="fa-solid fa-check" style="font-size:10px;"></i>':'1' ?></div>
            <div class="step-label">Username</div>
        </div>
        <div class="step-line <?= $sn>=2?'done':'' ?>"></div>
        <div class="step-col <?= $sn>=2?'active':'' ?>">
            <div class="step-dot <?= $sn>2?'done':($sn==2?'active':'idle') ?>"><?= $sn>2?'<i class="fa-solid fa-check" style="font-size:10px;"></i>':'2' ?></div>
            <div class="step-label">Kode OTP</div>
        </div>
        <div class="step-line <?= $sn>=3?'done':'' ?>"></div>
        <div class="step-col <?= $sn>=3?'active':'' ?>">
            <div class="step-dot <?= $sn==3?'active':'idle' ?>">3</div>
            <div class="step-label">Password Baru</div>
        </div>
    </div>

    <?php if($error): ?><div class="alert-e"><i class="fa-solid fa-circle-exclamation"></i><?= $error ?></div><?php endif; ?>
    <?php if($message): ?><div class="alert-s"><i class="fa-solid fa-circle-check" style="flex-shrink:0;margin-top:2px;"></i><span><?= $message ?></span></div><?php endif; ?>

    <!-- ═══ STEP 1: Input Username ═══════════════════════════════════════ -->
    <?php if($step===1): ?>
    <div class="step-icon"><i class="fa-solid fa-user"></i></div>
    <h1>Lupa Password</h1>
    <p class="sub">Masukkan <strong style="color:#a5b4fc;">username</strong> akun Anda. Kode OTP akan dikirim ke email yang terdaftar.</p>
    <form method="POST">
        <input type="hidden" name="action" value="verify_email">
        <label>Username</label>
        <div class="field">
            <i class="field-icon fa-solid fa-user"></i>
            <input type="text" name="username_or_email" class="has-icon <?= $error?'err':'' ?>"
                   placeholder="Masukkan username Anda"
                   value="<?= htmlspecialchars($_POST['username_or_email']??'') ?>"
                   autofocus required>
        </div>
        <button type="submit" class="btn-sub"><i class="fa-solid fa-paper-plane"></i> Kirim Kode OTP</button>
    </form>
    <a href="login.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali ke Login</a>

    <!-- ═══ STEP 1b: Input Email Manual ══════════════════════════════════ -->
    <?php elseif($step==='input_email'): ?>
    <div class="step-icon"><i class="fa-solid fa-envelope"></i></div>
    <h1>Tambahkan Email</h1>
    <p class="sub">Akun <strong style="color:#a5b4fc;"><?= htmlspecialchars($_SESSION['fp_nama']??'') ?></strong> belum memiliki email terdaftar. Masukkan email untuk menerima OTP.</p>
    <form method="POST">
        <input type="hidden" name="action" value="set_email">
        <label>Alamat Email</label>
        <div class="field">
            <i class="field-icon fa-solid fa-envelope"></i>
            <input type="email" name="email" class="has-icon <?= $error?'err':'' ?>"
                   placeholder="contoh@email.com" autofocus required>
        </div>
        <button type="submit" class="btn-sub"><i class="fa-solid fa-paper-plane"></i> Kirim Kode OTP</button>
    </form>
    <a href="forgot_password.php?reset=1" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali</a>

    <!-- ═══ STEP 2: Verifikasi OTP ════════════════════════════════════════ -->
    <?php elseif($step===2): ?>
    <div class="step-icon"><i class="fa-solid fa-shield-halved"></i></div>
    <h1>Verifikasi OTP</h1>
    <p class="sub">Masukkan 6 digit kode yang dikirim ke:</p>
    <div class="email-badge"><i class="fa-solid fa-envelope"></i><span><?= htmlspecialchars(maskEmail($_SESSION['fp_email']??'')) ?></span></div>
    <form method="POST" id="otpForm">
        <input type="hidden" name="action" value="verify_otp">
        <div class="otp-row">
            <?php for($i=0;$i<6;$i++): ?>
            <input type="text" name="otp_digit[]" maxlength="1" inputmode="numeric" pattern="[0-9]"
                   id="otp<?=$i?>" autocomplete="off"
                   oninput="otpIn(this,<?=$i?>)" onkeydown="otpKd(event,<?=$i?>)" onpaste="otpPaste(event)">
            <?php endfor; ?>
        </div>
        <button type="submit" class="btn-sub" id="btnV" disabled><i class="fa-solid fa-check-circle"></i> Verifikasi Kode</button>
    </form>
    <div class="resend-wrap">
        <span class="resend-timer" id="rTimer">Kirim ulang dalam <strong id="cd">60</strong>s</span>
        <form method="POST" style="display:inline;" id="resendF">
            <input type="hidden" name="action" value="resend_otp">
            <button type="submit" class="resend-btn" id="rBtn" style="display:none;"><i class="fa-solid fa-rotate-right"></i> Kirim Ulang</button>
        </form>
    </div>
    <div style="margin-top:14px;"><a href="forgot_password.php?reset=1" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Ganti Akun</a></div>

    <!-- ═══ STEP 3: Password Baru ═════════════════════════════════════════ -->
    <?php elseif($step===3): ?>
    <div class="step-icon"><i class="fa-solid fa-lock-open"></i></div>
    <h1>Password Baru</h1>
    <p class="sub">Buat password baru yang kuat untuk akun Anda.</p>
    <div class="email-badge"><i class="fa-solid fa-user-circle"></i><span><?= htmlspecialchars($_SESSION['fp_nama']??'') ?></span></div>
    <form method="POST">
        <input type="hidden" name="action" value="reset_password">
        <label>Password Baru</label>
        <div class="field">
            <input type="password" id="np" name="new_password" class="<?=$error?'err':''?>"
                   placeholder="Minimal 6 karakter" oninput="chkStr(this.value)"
                   autocomplete="new-password" required>
            <button type="button" class="eye" onclick="togPw('np','eyN')"><i class="fa-solid fa-eye" id="eyN"></i></button>
        </div>
        <div class="pw-str" id="strT" style="color:#64748b;"></div>
        <div class="str-bar"><div class="str-fill" id="strB"></div></div>
        <div style="margin-bottom:18px;"></div>
        <label>Konfirmasi Password</label>
        <div class="field">
            <input type="password" id="cp" name="confirm_password" class="<?=$error?'err':''?>"
                   placeholder="Ulangi password baru" autocomplete="new-password" required>
            <button type="button" class="eye" onclick="togPw('cp','eyC')"><i class="fa-solid fa-eye" id="eyC"></i></button>
        </div>
        <button type="submit" class="btn-sub"><i class="fa-solid fa-floppy-disk"></i> Simpan Password Baru</button>
    </form>
    <a href="forgot_password.php?reset=1" class="btn-back"><i class="fa-solid fa-rotate-left"></i> Mulai Ulang</a>
    <?php endif; ?>
</div>

<script>
function otpIn(el,idx){ el.value=el.value.replace(/\D/,''); el.classList.toggle('filled',el.value!==''); if(el.value&&idx<5)document.getElementById('otp'+(idx+1)).focus(); chkAll(); }
function otpKd(e,idx){ if(e.key==='Backspace'&&!e.target.value&&idx>0){var p=document.getElementById('otp'+(idx-1));p.value='';p.classList.remove('filled');p.focus();} }
function otpPaste(e){ e.preventDefault(); var t=(e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6); for(var i=0;i<t.length;i++){var b=document.getElementById('otp'+i);if(b){b.value=t[i];b.classList.add('filled');}} if(t.length>0){var l=document.getElementById('otp'+(t.length-1));if(l)l.focus();} chkAll(); }
function chkAll(){ var ok=true; for(var i=0;i<6;i++){if(!document.getElementById('otp'+i).value){ok=false;break;}} var b=document.getElementById('btnV'); if(b)b.disabled=!ok; }
<?php if($step===2): ?>
(function(){ var s=60,t=setInterval(function(){ s--; var e=document.getElementById('cd'); if(e)e.textContent=s; if(s<=0){clearInterval(t);document.getElementById('rTimer').style.display='none';document.getElementById('rBtn').style.display='inline';}},1000); })();
<?php endif; ?>
function chkStr(v){ var b=document.getElementById('strB'),t=document.getElementById('strT');if(!b||!t)return; var s=0;if(v.length>=6)s++;if(v.length>=10)s++;if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++; var L=[{p:'0%',c:'',l:''},{p:'20%',c:'#ef4444',l:'Sangat lemah'},{p:'40%',c:'#f59e0b',l:'Lemah'},{p:'65%',c:'#eab308',l:'Cukup'},{p:'82%',c:'#22c55e',l:'Kuat'},{p:'100%',c:'#16a34a',l:'Sangat kuat'}]; var lv=L[Math.min(s,5)]; b.style.width=lv.p;b.style.background=lv.c;t.textContent=lv.l;t.style.color=lv.c; }
function togPw(f,i){ var el=document.getElementById(f),ic=document.getElementById(i); if(el.type==='password'){el.type='text';ic.classList.replace('fa-eye','fa-eye-slash');}else{el.type='password';ic.classList.replace('fa-eye-slash','fa-eye');} }
</script>
</body>
</html>
