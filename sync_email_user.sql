-- ============================================================
-- sync_email_user.sql
-- Sinkronkan email dari tabel pegawai (hrd_payroll) ke tb_user (login_hrd)
-- Jalankan di phpMyAdmin pada database login_hrd
-- ============================================================

USE login_hrd;

-- Step 1: Tambah kolom jika belum ada
ALTER TABLE tb_user 
    ADD COLUMN IF NOT EXISTS otp_code    VARCHAR(6)   NULL,
    ADD COLUMN IF NOT EXISTS otp_expires DATETIME     NULL,
    ADD COLUMN IF NOT EXISTS email       VARCHAR(150) NULL;

-- Step 2: Isi email dari tabel pegawai berdasarkan nama_lengkap
-- (Cocokkan tb_user.nama_lengkap dengan pegawai.nama)
UPDATE login_hrd.tb_user u
INNER JOIN hrd_payroll.pegawai p ON p.nama = u.nama_lengkap
SET u.email = p.email
WHERE (u.email IS NULL OR u.email = '')
  AND p.email IS NOT NULL AND p.email != '';

-- Step 3: Cek hasilnya
SELECT id, username, nama_lengkap, role, email FROM tb_user;

-- ============================================================
-- Jika nama_lengkap tidak cocok, isi manual:
-- UPDATE tb_user SET email='emailanda@gmail.com' WHERE username='admin';
-- UPDATE tb_user SET email='hrd@gmail.com'       WHERE username='hrd';
-- ============================================================
