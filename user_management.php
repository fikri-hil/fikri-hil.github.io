<?php
$page_title = 'User Management';
require_once 'config/database.php';

if(isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$_GET['delete']]);
    header("Location: user_management.php?msg=deleted"); exit;
}

if($_SERVER['REQUEST_METHOD']==='POST') {
    if($_POST['action']==='add') {
        $hashed = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO users (username,password,nama_lengkap,role,status) VALUES (?,?,?,?,?)")
            ->execute([$_POST['username'],$hashed,$_POST['nama_lengkap'],$_POST['role'],$_POST['status']]);
    } elseif($_POST['action']==='edit') {
        if(!empty($_POST['password'])) {
            $hashed = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET username=?,password=?,nama_lengkap=?,role=?,status=? WHERE id=?")
                ->execute([$_POST['username'],$hashed,$_POST['nama_lengkap'],$_POST['role'],$_POST['status'],$_POST['id']]);
        } else {
            $pdo->prepare("UPDATE users SET username=?,nama_lengkap=?,role=?,status=? WHERE id=?")
                ->execute([$_POST['username'],$_POST['nama_lengkap'],$_POST['role'],$_POST['status'],$_POST['id']]);
        }
    }
    header("Location: user_management.php"); exit;
}

$list = $pdo->query("SELECT * FROM users ORDER BY id")->fetchAll();

require_once 'layout/header.php';
?>
<div class="breadcrumb-wrap">
    <nav><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item active">User Management</li>
    </ol></nav>
</div>

<div class="mb-3">
    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalUser">
        <i class="fas fa-plus"></i> Tambah User
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th><th>Username</th><th>Nama Lengkap</th>
                        <th>Role</th><th>Status</th><th>Terakhir Login</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($list as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($r['username']) ?></td>
                        <td><?= htmlspecialchars($r['nama_lengkap']??'-') ?></td>
                        <td>
                            <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;
                                background:<?= $r['role']=='Administrator'?'#f5f3ff':'#f0f9ff' ?>;
                                color:<?= $r['role']=='Administrator'?'#6d28d9':'#0369a1' ?>;">
                                <?= $r['role'] ?>
                            </span>
                        </td>
                        <td><span class="badge-status badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                        <td style="font-size:12px;color:#64748b;"><?= $r['last_login'] ? date('d M Y H:i', strtotime($r['last_login'])) : '-' ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn-action btn-edit" onclick='editUser(<?=json_encode($r)?>) '><i class="fas fa-pencil"></i></button>
                                <?php if($r['username']!='admin'): ?>
                                <button class="btn-action btn-delete" onclick="confirmDelete('user_management.php?delete=<?=$r['id']?>','<?=htmlspecialchars($r['username'])?>')"><i class="fas fa-trash"></i></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="px-4 py-3 border-top" style="background:#fafbfc;">
        <div style="font-size:13px;color:#64748b;">Menampilkan 1 - <?=count($list)?> dari <?=count($list)?> data</div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalUser" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-700" id="modalUserTitle">Tambah User</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-600" style="font-size:12px;">Username</label>
                            <input type="text" name="username" id="fUser" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-600" style="font-size:12px;">Password <span id="passHint" style="color:#94a3b8;font-weight:400;"></span></label>
                            <input type="password" name="password" id="fPass" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-600" style="font-size:12px;">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" id="fNama" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-600" style="font-size:12px;">Role</label>
                            <select name="role" id="fRole" class="form-select">
                                <option>Administrator</option>
                                <option>Keuangan</option>
                                <option>HRD</option>
                                <option>Direktur</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-600" style="font-size:12px;">Status</label>
                            <select name="status" id="fStatus" class="form-select">
                                <option>Aktif</option><option>Nonaktif</option>
                            </select>
                        </div>
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
function editUser(data) {
    document.getElementById('modalUserTitle').textContent = 'Edit User';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formId').value = data.id;
    document.getElementById('fUser').value = data.username;
    document.getElementById('fPass').value = '';
    document.getElementById('passHint').textContent = '(kosongkan jika tidak diubah)';
    document.getElementById('fNama').value = data.nama_lengkap || '';
    document.getElementById('fRole').value = data.role;
    document.getElementById('fStatus').value = data.status;
    new bootstrap.Modal(document.getElementById('modalUser')).show();
}
</script>
<?php require_once 'layout/footer.php'; ?>
