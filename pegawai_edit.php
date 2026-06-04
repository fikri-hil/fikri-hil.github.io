<?php
require_once 'config/database.php';
$id = intval($_GET['id'] ?? 0);
$is_edit = $id > 0;
$page_title = $is_edit ? 'Edit Pegawai' : 'Tambah Pegawai';
$p = [];
if($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM pegawai WHERE id=?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if(!$p) { header("Location: pegawai.php"); exit; }
}
$jabatans = $pdo->query("SELECT * FROM jabatan ORDER BY nama_jabatan")->fetchAll();

$errors = [];
if($_SERVER['REQUEST_METHOD']==='POST') {
    $data = $_POST;
    if(empty($data['nama'])) $errors[] = 'Nama wajib diisi';
    if(empty($data['nip'])) $errors[] = 'NIP wajib diisi';
    // Cek NIP duplikat
    if (!empty($data['nip'])) {
        $cekNip = $pdo->prepare("SELECT id FROM pegawai WHERE nip = ? AND id != ?");
        $cekNip->execute([$data['nip'], $is_edit ? $id : 0]);
        if ($cekNip->fetch()) {
            $errors[] = 'NIP <strong>' . htmlspecialchars($data['nip']) . '</strong> sudah terdaftar untuk pegawai lain. Gunakan NIP yang berbeda.';
        }
    }
    if(empty($errors)) {
        $fields = ['nip','nama','jabatan_id','tempat_lahir','tanggal_lahir','jenis_kelamin','agama','status_pernikahan','jumlah_anak','email','telepon','alamat','gaji_pokok','tanggal_bergabung','status'];
        $vals = array_map(function($f) use ($data) { return isset($data[$f]) ? $data[$f] : null; }, $fields);
        try {
            if($is_edit) {
                $set = implode('=?,', $fields).'=?';
                $vals[] = $id;
                $pdo->prepare("UPDATE pegawai SET $set WHERE id=?")->execute($vals);
            } else {
                $cols = implode(',', $fields);
                $phs = implode(',', array_fill(0, count($fields), '?'));
                $pdo->prepare("INSERT INTO pegawai ($cols) VALUES ($phs)")->execute($vals);
            }
            header("Location: pegawai.php?msg=".($is_edit?'updated':'created'));
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = 'NIP sudah terdaftar. Gunakan NIP yang berbeda.';
            } else {
                $errors[] = 'Terjadi kesalahan database. Silakan coba lagi.';
            }
        }
    }
}
require_once 'layout/header.php';
?>
<div class="breadcrumb-wrap">
    <nav><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item"><a href="pegawai.php">Pegawai</a></li>
        <li class="breadcrumb-item active"><?=$page_title?></li>
    </ol></nav>
</div>

<?php if($errors): ?>
<div class="alert alert-danger" style="border-radius:10px;font-size:13px;">
    <?= implode('<br>', $errors) ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><span class="fw-700" style="font-size:15px;"><?=$page_title?></span></div>
    <div class="card-body p-4">
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-600" style="font-size:12px;">NIP <span class="text-danger">*</span></label>
                    <input type="text" name="nip" class="form-control" value="<?=htmlspecialchars($p['nip']??'')?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-600" style="font-size:12px;">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" value="<?=htmlspecialchars($p['nama']??'')?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-600" style="font-size:12px;">Jabatan</label>
                    <select name="jabatan_id" class="form-select">
                        <option value="">Pilih Jabatan</option>
                        <?php foreach($jabatans as $j): ?>
                        <option value="<?=$j['id']?>" <?=($p['jabatan_id']??'')==$j['id']?'selected':''?>><?=$j['nama_jabatan']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-600" style="font-size:12px;">Gaji Pokok</label>
                    <input type="number" name="gaji_pokok" class="form-control" value="<?=$p['gaji_pokok']??''?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-600" style="font-size:12px;">Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" class="form-control" value="<?=htmlspecialchars($p['tempat_lahir']??'')?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-600" style="font-size:12px;">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" class="form-control" value="<?=$p['tanggal_lahir']??''?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-600" style="font-size:12px;">Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="form-select">
                        <option value="">Pilih</option>
                        <option value="Laki-laki" <?=($p['jenis_kelamin']??'')=='Laki-laki'?'selected':''?>>Laki-laki</option>
                        <option value="Perempuan" <?=($p['jenis_kelamin']??'')=='Perempuan'?'selected':''?>>Perempuan</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-600" style="font-size:12px;">Agama</label>
                    <select name="agama" class="form-select">
                        <?php foreach(['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu'] as $ag): ?>
                        <option value="<?=$ag?>" <?=($p['agama']??'')==$ag?'selected':''?>><?=$ag?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-600" style="font-size:12px;">Status Pernikahan</label>
                    <select name="status_pernikahan" class="form-select">
                        <?php foreach(['Belum Menikah','Menikah','Cerai'] as $sp): ?>
                        <option value="<?=$sp?>" <?=($p['status_pernikahan']??'')==$sp?'selected':''?>><?=$sp?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-600" style="font-size:12px;">Jumlah Anak</label>
                    <input type="number" name="jumlah_anak" class="form-control" min="0" value="<?=$p['jumlah_anak']??0?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-600" style="font-size:12px;">Email</label>
                    <input type="email" name="email" class="form-control" value="<?=htmlspecialchars($p['email']??'')?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-600" style="font-size:12px;">Telepon</label>
                    <input type="text" name="telepon" class="form-control" value="<?=htmlspecialchars($p['telepon']??'')?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-600" style="font-size:12px;">Alamat</label>
                    <textarea name="alamat" class="form-control" rows="2"><?=htmlspecialchars($p['alamat']??'')?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-600" style="font-size:12px;">Tanggal Bergabung</label>
                    <input type="date" name="tanggal_bergabung" class="form-control" value="<?=$p['tanggal_bergabung']??''?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-600" style="font-size:12px;">Status</label>
                    <select name="status" class="form-select">
                        <option value="Aktif" <?=($p['status']??'Aktif')=='Aktif'?'selected':''?>>Aktif</option>
                        <option value="Nonaktif" <?=($p['status']??'')=='Nonaktif'?'selected':''?>>Nonaktif</option>
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn-primary-custom"><i class="fas fa-save"></i> <?=$is_edit?'Simpan Perubahan':'Tambah Pegawai'?></button>
                <a href="pegawai.php" class="btn-outline-custom"><i class="fas fa-times"></i> Batal</a>
            </div>
        </form>
    </div>
</div>
<?php require_once 'layout/footer.php'; ?>
