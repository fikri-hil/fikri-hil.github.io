<?php
$page_title = 'Periode Gaji';
require_once 'config/database.php';

if(isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM periode_gaji WHERE id=?")->execute([$_GET['delete']]);
    header("Location: periode_gaji.php?msg=deleted"); exit;
}

$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10; $offset = ($page - 1) * $per_page;
$total_rows = $pdo->query("SELECT COUNT(*) FROM periode_gaji")->fetchColumn();
$total_pages = ceil($total_rows / $per_page);

$stmt = $pdo->prepare("SELECT * FROM periode_gaji ORDER BY tahun DESC, FIELD(bulan,'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember') DESC LIMIT $per_page OFFSET $offset");
$stmt->execute();
$list = $stmt->fetchAll();

require_once 'layout/header.php';
?>

<div class="breadcrumb-wrap">
    <nav><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item active">Periode Gaji</li>
    </ol></nav>
</div>

<div class="mb-3">
    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalPeriode">
        <i class="fas fa-plus"></i> Tambah Periode
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Periode</th>
                        <th>Bulan</th>
                        <th>Tahun</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($list as $i => $r): ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($r['nama_periode']) ?></td>
                        <td><?= $r['bulan'] ?></td>
                        <td><?= $r['tahun'] ?></td>
                        <td><?= date('d M Y', strtotime($r['tanggal_mulai'])) ?></td>
                        <td><?= date('d M Y', strtotime($r['tanggal_selesai'])) ?></td>
                        <td><span class="badge-status badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="edit_periode_gaji.php?id=<?=$r['id']?>" class="btn-action btn-edit" title="Edit"><i class="fas fa-pencil"></i></a>
                                <button class="btn-action btn-delete" onclick="confirmDelete('periode_gaji.php?delete=<?=$r['id']?>','<?=htmlspecialchars($r['nama_periode'])?>')"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="d-flex align-items-center justify-content-between px-4 py-3 border-top" style="background:#fafbfc;">
        <div style="font-size:13px;color:#64748b;">Menampilkan <?=$offset+1?> - <?=min($offset+$per_page,$total_rows)?> dari <?=$total_rows?> data</div>
        <?php if($total_pages > 1): ?>
        <nav><ul class="pagination mb-0">
            <li class="page-item <?=$page<=1?'disabled':''?>"><a class="page-link" href="?page=<?=$page-1?>"><i class="fas fa-chevron-left fa-xs"></i></a></li>
            <?php for($p2=1;$p2<=$total_pages;$p2++): ?>
            <?php if($p2==$page||$p2==1||$p2==$total_pages||abs($p2-$page)<=1): ?>
            <li class="page-item <?=$p2==$page?'active':''?>"><a class="page-link" href="?page=<?=$p2?>"><?=$p2?></a></li>
            <?php elseif(abs($p2-$page)==2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
            <?php endfor; ?>
            <li class="page-item <?=$page>=$total_pages?'disabled':''?>"><a class="page-link" href="?page=<?=$page+1?>"><i class="fas fa-chevron-right fa-xs"></i></a></li>
        </ul></nav>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Add Periode -->
<div class="modal fade" id="modalPeriode" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-700">Tambah Periode Gaji</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="periode_gaji_save.php">
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-600" style="font-size:12px;">Nama Periode</label>
                    <input type="text" name="nama_periode" class="form-control" placeholder="cth: Mei 2024" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-600" style="font-size:12px;">Bulan</label>
                        <select name="bulan" class="form-select">
                            <?php foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $b): ?>
                            <option><?=$b?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-600" style="font-size:12px;">Tahun</label>
                        <input type="number" name="tahun" class="form-control" value="<?=date('Y')?>" min="2020" max="2030">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-600" style="font-size:12px;">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-600" style="font-size:12px;">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-600" style="font-size:12px;">Status</label>
                    <select name="status" class="form-select">
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

<?php require_once 'layout/footer.php'; ?>
