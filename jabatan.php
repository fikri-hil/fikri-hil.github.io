<?php
$page_title = 'Jabatan';
require_once 'config/database.php';

if(isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM jabatan WHERE id=?")->execute([$_GET['delete']]);
    header("Location: jabatan.php"); exit;
}
if($_SERVER['REQUEST_METHOD']==='POST') {
    if($_POST['action']==='add') {
        $pdo->prepare("INSERT INTO jabatan (nama_jabatan,gaji_pokok) VALUES (?,?)")
            ->execute([$_POST['nama_jabatan'],$_POST['gaji_pokok']]);
    } elseif($_POST['action']==='edit') {
        $pdo->prepare("UPDATE jabatan SET nama_jabatan=?,gaji_pokok=? WHERE id=?")
            ->execute([$_POST['nama_jabatan'],$_POST['gaji_pokok'],$_POST['id']]);
    }
    header("Location: jabatan.php"); exit;
}
$list = $pdo->query("SELECT j.*, COUNT(p.id) as jumlah_pegawai FROM jabatan j LEFT JOIN pegawai p ON j.id=p.jabatan_id GROUP BY j.id ORDER BY j.gaji_pokok DESC")->fetchAll();

require_once 'layout/header.php';
?>
<div class="breadcrumb-wrap">
    <nav><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item active">Jabatan</li>
    </ol></nav>
</div>
<div class="mb-3">
    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalJabatan"><i class="fas fa-plus"></i> Tambah Jabatan</button>
</div>
<div class="card">
    <div class="card-body">
        <table class="table table-hover">
            <thead><tr><th>No</th><th>Nama Jabatan</th><th>Gaji Pokok</th><th>Jumlah Pegawai</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php foreach($list as $i => $r): ?>
                <tr>
                    <td><?=$i+1?></td>
                    <td style="font-weight:600;"><?=htmlspecialchars($r['nama_jabatan'])?></td>
                    <td>Rp<?=number_format($r['gaji_pokok'],0,',','.')?></td>
                    <td><span style="background:#eff6ff;color:#3b82f6;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;"><?=$r['jumlah_pegawai']?> orang</span></td>
                    <td>
                        <div class="d-flex gap-1">
                            <button class="btn-action btn-edit" onclick='editJabatan(<?=json_encode($r)?> )'><i class="fas fa-pencil"></i></button>
                            <button class="btn-action btn-delete" onclick="confirmDelete('jabatan.php?delete=<?=$r['id']?>','<?=htmlspecialchars($r['nama_jabatan'])?>')"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-top" style="background:#fafbfc;font-size:13px;color:#64748b;">Total: <?=count($list)?> jabatan</div>
</div>
<div class="modal fade" id="modalJabatan" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-700" id="mJTitle">Tambah Jabatan</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" id="mJAction" value="add">
                <input type="hidden" name="id" id="mJId">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:12px;">Nama Jabatan</label>
                        <input type="text" name="nama_jabatan" id="mJNama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:12px;">Gaji Pokok</label>
                        <input type="number" name="gaji_pokok" id="mJGaji" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-outline-custom" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-primary-custom"><i class="fas fa-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function editJabatan(d) {
    document.getElementById('mJTitle').textContent = 'Edit Jabatan';
    document.getElementById('mJAction').value = 'edit';
    document.getElementById('mJId').value = d.id;
    document.getElementById('mJNama').value = d.nama_jabatan;
    document.getElementById('mJGaji').value = d.gaji_pokok;
    new bootstrap.Modal(document.getElementById('modalJabatan')).show();
}
</script>
<?php require_once 'layout/footer.php'; ?>
