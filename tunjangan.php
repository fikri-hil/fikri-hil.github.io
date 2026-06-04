<?php
$page_title = 'Tunjangan';
require_once 'config/database.php';

if(isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM tunjangan WHERE id=?")->execute([$_GET['delete']]);
    header("Location: tunjangan.php?msg=deleted"); exit;
}

if($_SERVER['REQUEST_METHOD']==='POST') {
    if($_POST['action']==='add') {
        $pdo->prepare("INSERT INTO tunjangan (nama_tunjangan,tipe,nominal,keterangan,status) VALUES (?,?,?,?,?)")
            ->execute([$_POST['nama_tunjangan'],$_POST['tipe'],$_POST['nominal'],$_POST['keterangan'],$_POST['status']]);
    } elseif($_POST['action']==='edit') {
        $pdo->prepare("UPDATE tunjangan SET nama_tunjangan=?,tipe=?,nominal=?,keterangan=?,status=? WHERE id=?")
            ->execute([$_POST['nama_tunjangan'],$_POST['tipe'],$_POST['nominal'],$_POST['keterangan'],$_POST['status'],$_POST['id']]);
    }
    header("Location: tunjangan.php"); exit;
}

$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10; $offset = ($page - 1) * $per_page;
$total_rows = $pdo->query("SELECT COUNT(*) FROM tunjangan")->fetchColumn();
$list = $pdo->prepare("SELECT * FROM tunjangan ORDER BY id LIMIT $per_page OFFSET $offset");
$list->execute(); $list = $list->fetchAll();

require_once 'layout/header.php';
?>
<div class="breadcrumb-wrap">
    <nav><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item active">Tunjangan</li>
    </ol></nav>
</div>

<div class="mb-3">
    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalTunjangan">
        <i class="fas fa-plus"></i> Tambah Tunjangan
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th><th>Nama Tunjangan</th><th>Tipe</th>
                        <th>Nominal / Persen</th><th>Keterangan</th><th>Status</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($list as $i => $r): ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($r['nama_tunjangan']) ?></td>
                        <td><?= $r['tipe'] ?></td>
                        <td><?= $r['tipe']=='Persen' ? $r['nominal'].'%' : 'Rp'.number_format($r['nominal'],0,',','.') ?></td>
                        <td style="font-size:12px;color:#64748b;"><?= htmlspecialchars($r['keterangan']??'-') ?></td>
                        <td><span class="badge-status badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn-action btn-edit" onclick='editTunjangan(<?=json_encode($r)?>)'><i class="fas fa-pencil"></i></button>
                                <button class="btn-action btn-delete" onclick="confirmDelete('tunjangan.php?delete=<?=$r['id']?>','<?=htmlspecialchars($r['nama_tunjangan'])?>')"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="px-4 py-3 border-top" style="background:#fafbfc;">
        <div style="font-size:13px;color:#64748b;">Menampilkan 1 - <?=count($list)?> dari <?=$total_rows?> data</div>
    </div>
</div>
<div class="note-warning"><i class="fas fa-exclamation-circle me-2"></i><strong>Catatan:</strong> Tunjangan dengan status aktif akan otomatis dihitung saat generate gaji.</div>

<!-- Modal -->
<div class="modal fade" id="modalTunjangan" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-700" id="modalTitle">Tambah Tunjangan</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:12px;">Nama Tunjangan</label>
                        <input type="text" name="nama_tunjangan" id="fNama" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-600" style="font-size:12px;">Tipe</label>
                            <select name="tipe" id="fTipe" class="form-select">
                                <option>Nominal</option><option>Persen</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-600" style="font-size:12px;">Nominal / Persen</label>
                            <input type="number" name="nominal" id="fNominal" class="form-control" step="0.01">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:12px;">Keterangan</label>
                        <input type="text" name="keterangan" id="fKet" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:12px;">Status</label>
                        <select name="status" id="fStatus" class="form-select">
                            <option>Aktif</option><option>Nonaktif</option>
                        </select>
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
function editTunjangan(data) {
    document.getElementById('modalTitle').textContent = 'Edit Tunjangan';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formId').value = data.id;
    document.getElementById('fNama').value = data.nama_tunjangan;
    document.getElementById('fTipe').value = data.tipe;
    document.getElementById('fNominal').value = data.nominal;
    document.getElementById('fKet').value = data.keterangan || '';
    document.getElementById('fStatus').value = data.status;
    new bootstrap.Modal(document.getElementById('modalTunjangan')).show();
}
</script>
<?php require_once 'layout/footer.php'; ?>
