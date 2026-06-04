<?php
$page_title = 'Edit Periode Gaji';
require_once 'config/database.php';

$id = intval($_GET['id'] ?? 0);
if(!$id) { header("Location: periode_gaji.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM periode_gaji WHERE id=?");
$stmt->execute([$id]);
$r = $stmt->fetch();
if(!$r) { header("Location: periode_gaji.php"); exit; }

if($_SERVER['REQUEST_METHOD']==='POST') {
    $pdo->prepare("UPDATE periode_gaji SET nama_periode=?,bulan=?,tahun=?,tanggal_mulai=?,tanggal_selesai=?,status=? WHERE id=?")
        ->execute([$_POST['nama_periode'],$_POST['bulan'],$_POST['tahun'],$_POST['tanggal_mulai'],$_POST['tanggal_selesai'],$_POST['status'],$id]);
    header("Location: periode_gaji.php?msg=updated"); exit;
}

require_once 'layout/header.php';
?>

<div class="breadcrumb-wrap">
    <nav><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item"><a href="periode_gaji.php">Periode Gaji</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol></nav>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <a href="periode_gaji.php" class="btn-outline-custom" style="padding:5px 10px;"><i class="fas fa-arrow-left"></i> Kembali</a>
                <span class="fw-700" style="font-size:15px;">Edit Periode Gaji</span>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:12px;">Nama Periode</label>
                        <input type="text" name="nama_periode" class="form-control" value="<?=htmlspecialchars($r['nama_periode'])?>" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-600" style="font-size:12px;">Bulan</label>
                            <select name="bulan" class="form-select">
                                <?php foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $b): ?>
                                <option <?=$r['bulan']==$b?'selected':''?>><?=$b?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-600" style="font-size:12px;">Tahun</label>
                            <input type="number" name="tahun" class="form-control" value="<?=$r['tahun']?>">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-600" style="font-size:12px;">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" class="form-control" value="<?=$r['tanggal_mulai']?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-600" style="font-size:12px;">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" class="form-control" value="<?=$r['tanggal_selesai']?>">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-600" style="font-size:12px;">Status</label>
                        <select name="status" class="form-select">
                            <option <?=$r['status']=='Aktif'?'selected':''?>>Aktif</option>
                            <option <?=$r['status']=='Nonaktif'?'selected':''?>>Nonaktif</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="periode_gaji.php" class="btn-outline-custom">Batal</a>
                        <button type="submit" class="btn-primary-custom"><i class="fas fa-save"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'layout/footer.php'; ?>
