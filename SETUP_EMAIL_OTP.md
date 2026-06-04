# Panduan Setup Email OTP — Lupa Password

## Cara Cepat (Gmail + App Password)

### 1. Aktifkan 2-Step Verification di Gmail
- Buka https://myaccount.google.com/security
- Aktifkan "2-Step Verification"

### 2. Buat App Password
- Buka https://myaccount.google.com/apppasswords
- Pilih App: "Mail", Device: "Other (Custom)" → beri nama "HRD Payroll"
- Salin 16 karakter yang muncul (format: xxxx xxxx xxxx xxxx)

### 3. Edit forgot_password.php
Buka file `forgot_password.php`, cari baris define SMTP dan isi:

```php
define('SMTP_USER', 'emailanda@gmail.com');   // ← email Gmail Anda
define('SMTP_PASS', 'xxxx xxxx xxxx xxxx');   // ← App Password 16 karakter
define('SMTP_FROM', 'emailanda@gmail.com');   // ← sama dengan SMTP_USER
```

### 4. Install PHPMailer (opsional tapi disarankan)
Jika menggunakan Composer:
```bash
composer require phpmailer/phpmailer
```
Atau download manual dari https://github.com/PHPMailer/PHPMailer
dan letakkan folder `src/` di `hrd/phpmailer/src/`

Tanpa PHPMailer, sistem akan fallback ke fungsi `mail()` PHP bawaan
(perlu konfigurasi sendmail di php.ini).

### 5. Tambah kolom ke tb_user (otomatis)
Script akan otomatis menambahkan kolom `otp_code`, `otp_expires`, dan `email`
ke tabel `tb_user` jika belum ada.

Atau jalankan manual di phpMyAdmin:
```sql
USE login_hrd;
ALTER TABLE tb_user 
  ADD COLUMN otp_code VARCHAR(6) NULL,
  ADD COLUMN otp_expires DATETIME NULL,
  ADD COLUMN email VARCHAR(120) NULL;
```

## Mode Development (Tanpa Email)
Jika email gagal terkirim, OTP akan ditampilkan langsung di halaman 
(hanya untuk development). Di production, hapus/komentari baris tampilkan OTP.
