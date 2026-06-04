<?php
session_start();

// Sudah login? langsung ke dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $pdo_auth->prepare("SELECT * FROM tb_user WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            $stored = $user['password'];

            // Cek apakah password tersimpan sebagai bcrypt hash atau plain text
            $isHash  = (strlen($stored) === 60 && substr($stored, 0, 4) === '$2y$');
            $correct = $isHash
                ? password_verify($password, $stored)
                : ($password === $stored);

            $aktif = (isset($user['status']) ? strtolower($user['status']) : 'aktif') === 'aktif';

            if ($correct && $aktif) {
                // Jika masih plain text, upgrade ke hash sekarang
                if (!$isHash) {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $pdo_auth->prepare("UPDATE tb_user SET password = ? WHERE id = ?")
                             ->execute([$hash, $user['id']]);
                }

                $_SESSION['user_id']      = $user['id'];
                $_SESSION['username']     = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role']         = $user['role'];

                $pdo_auth->prepare("UPDATE tb_user SET last_login = NOW() WHERE id = ?")
                         ->execute([$user['id']]);

                if (!empty($_POST['remember'])) {
                    setcookie('remember_user', $user['username'], time() + (86400 * 30), '/');
                }

                // Trigger welcome popup on next page
                $_SESSION['show_welcome'] = true;

                header('Location: dashboard.php');
                exit;

            } elseif ($correct && !$aktif) {
                $error = 'Akun Anda tidak aktif. Hubungi administrator.';
            } else {
                $error = 'Username atau password salah.';
            }
        } else {
            $error = 'Username atau password salah.';
        }
    }
}

$remembered = $_COOKIE['remember_user'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – HRD Payroll</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            opacity: 0.30;
            pointer-events: none;
        }
        .blob-1 { width: 520px; height: 520px; background: #6366f1; top: -160px; left: -160px; }
        .blob-2 { width: 420px; height: 420px; background: #8b5cf6; bottom: -120px; right: -120px; }
        .blob-3 { width: 280px; height: 280px; background: #3b82f6; top: 50%; left: 50%; transform: translate(-50%,-50%); }

        .card {
            position: relative;
            z-index: 10;
            background: rgba(30, 41, 59, 0.88);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(148,163,184,0.12);
            border-radius: 24px;
            padding: 44px 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 28px 64px rgba(0,0,0,0.5);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
        }
        .logo-box {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 17px; font-weight: 800; color: #fff;
        }
        .logo-name { font-size: 15px; font-weight: 800; color: #f1f5f9; letter-spacing: .4px; }
        .logo-sub  { font-size: 10px; font-weight: 600; color: #94a3b8; letter-spacing: 2px; text-transform: uppercase; }

        h1 { font-size: 24px; font-weight: 700; color: #f1f5f9; margin-bottom: 4px; }
        .sub { font-size: 13px; color: #94a3b8; margin-bottom: 28px; }

        .alert {
            background: rgba(239,68,68,.12);
            border: 1px solid rgba(239,68,68,.3);
            border-radius: 10px;
            padding: 11px 14px;
            color: #fca5a5;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
        }

        label { display: block; font-size: 13px; font-weight: 600; color: #cbd5e1; margin-bottom: 6px; }

        .field { position: relative; margin-bottom: 18px; }
        .field input {
            width: 100%;
            background: rgba(15,23,42,.65);
            border: 1.5px solid rgba(148,163,184,.18);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 14px; font-family: inherit;
            color: #f1f5f9;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .field input::placeholder { color: #475569; }
        .field input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,.2);
        }
        .field input.err { border-color: #ef4444; }
        .eye {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: #64748b; font-size: 15px; padding: 0; line-height: 1;
        }
        .eye:hover { color: #94a3b8; }

        .row-extra {
            display: flex; align-items: center; justify-content: space-between;
            font-size: 13px; margin-bottom: 24px;
        }
        .chk { display: flex; align-items: center; gap: 7px; color: #94a3b8; cursor: pointer; user-select: none; }
        .chk input { width: 15px; height: 15px; accent-color: #6366f1; cursor: pointer; }
        .forgot { color: #818cf8; text-decoration: none; font-weight: 500; }
        .forgot:hover { color: #a5b4fc; }

        .btn-masuk {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff; border: none; border-radius: 12px;
            font-size: 15px; font-weight: 700; font-family: inherit;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: opacity .2s, transform .1s;
        }
        .btn-masuk:hover { opacity: .9; }
        .btn-masuk:active { transform: scale(.98); }

        .hint { text-align: center; font-size: 12px; color: #475569; margin-top: 20px; }
    </style>
</head>
<body>
<div class="blob blob-1"></div>
<div class="blob blob-2"></div>
<div class="blob blob-3"></div>

<div class="card">
    <div class="logo">
        <div class="logo-box">HR</div>
        <div>
            <div class="logo-name">HRD PAYROLL</div>
            <div class="logo-sub">Sistem Penggajian</div>
        </div>
    </div>

    <h1>Selamat Datang</h1>
    <p class="sub">Masuk ke sistem penggajian HR</p>

    <?php if ($error): ?>
    <div class="alert">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <label for="username">Username</label>
        <div class="field">
            <input type="text" id="username" name="username"
                placeholder="Masukkan username"
                value="<?= htmlspecialchars($remembered ?: ($_POST['username'] ?? '')) ?>"
                class="<?= $error ? 'err' : '' ?>"
                autofocus required>
        </div>

        <label for="password">Password</label>
        <div class="field">
            <input type="password" id="password" name="password"
                placeholder="Masukkan password"
                class="<?= $error ? 'err' : '' ?>"
                required>
            <button type="button" class="eye" onclick="togglePw()">
                <i class="fa-solid fa-eye" id="eyeIco"></i>
            </button>
        </div>

        <div class="row-extra">
            <label class="chk">
                <input type="checkbox" name="remember" value="1" <?= $remembered ? 'checked' : '' ?>>
                Ingat saya
            </label>
            <a href="forgot_password.php" class="forgot">Lupa password?</a>
        </div>

        <button type="submit" class="btn-masuk">
            <i class="fa-solid fa-right-to-bracket"></i> Masuk
        </button>
    </form>

</div>

<script>
function togglePw() {
    var i = document.getElementById('password');
    var e = document.getElementById('eyeIco');
    if (i.type === 'password') {
        i.type = 'text';
        e.classList.replace('fa-eye','fa-eye-slash');
    } else {
        i.type = 'password';
        e.classList.replace('fa-eye-slash','fa-eye');
    }
}
</script>
</body>
</html>
