<?php
$page_title = 'Data Pegawai';
require_once 'config/database.php';

// Handle delete
if(isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM pegawai WHERE id=?")->execute([$_GET['delete']]);
    header("Location: pegawai.php?msg=deleted");
    exit;
}

// Filters
$jabatan_filter = $_GET['jabatan'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where = "WHERE 1=1";
$params = [];
if($jabatan_filter) { $where .= " AND p.jabatan_id=?"; $params[] = $jabatan_filter; }
if($status_filter) { $where .= " AND p.status=?"; $params[] = $status_filter; }
if($search) { $where .= " AND (p.nama LIKE ? OR p.nip LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$total = $pdo->prepare("SELECT COUNT(*) FROM pegawai p $where");
$total->execute($params);
$total_rows = $total->fetchColumn();
$total_pages = ceil($total_rows / $per_page);

$stmt = $pdo->prepare("SELECT p.*, j.nama_jabatan FROM pegawai p LEFT JOIN jabatan j ON p.jabatan_id=j.id $where LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$pegawai_list = $stmt->fetchAll();

$jabatans = $pdo->query("SELECT * FROM jabatan ORDER BY nama_jabatan")->fetchAll();

require_once 'layout/header.php';
?>

<div class="breadcrumb-wrap">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
            <li class="breadcrumb-item active">Pegawai</li>
        </ol>
    </nav>
</div>

<!-- Filter Bar -->
<div class="filter-bar d-flex flex-wrap align-items-center gap-2 justify-content-between mb-3">
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <a href="pegawai_tambah.php" class="btn-primary-custom">
            <i class="fas fa-plus"></i> Tambah Pegawai
        </a>
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <select name="jabatan" class="form-select" style="width:160px;" onchange="this.form.submit()">
                <option value="">Semua Jabatan</option>
                <?php foreach($jabatans as $j): ?>
                <option value="<?=$j['id']?>" <?=$jabatan_filter==$j['id']?'selected':''?>><?=$j['nama_jabatan']?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="form-select" style="width:140px;" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="Aktif" <?=$status_filter=='Aktif'?'selected':''?>>Aktif</option>
                <option value="Nonaktif" <?=$status_filter=='Nonaktif'?'selected':''?>>Nonaktif</option>
            </select>
        </form>
    </div>
    <div class="search-wrap">
        <form method="GET">
            <i class="fas fa-search"></i>
            <input type="text" name="search" class="form-control" placeholder="Cari nama / NIP..." value="<?=htmlspecialchars($search)?>" style="width:220px;">
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Foto</th>
                        <th>NIP</th>
                        <th>Nama Pegawai</th>
                        <th>Jabatan</th>
                        <th>Status</th>
                        <th>Tanggal Bergabung</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($pegawai_list)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data ditemukan</td></tr>
                    <?php else: ?>
                    <?php foreach($pegawai_list as $i => $p): ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td>
                            <div class="avatar-circle" style="width:36px;height:36px;font-size:13px;">
                                <?php if($p['foto']): ?>
                                <img src="uploads/<?=$p['foto']?>" alt="">
                                <?php else: ?>
                                <?= strtoupper(substr($p['nama'],0,2)) ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= $p['nip'] ?></td>
                        <td><span style="font-weight:600;"><?= htmlspecialchars($p['nama']) ?></span></td>
                        <td><?= $p['nama_jabatan'] ?? '-' ?></td>
                        <td><span class="badge-status badge-<?= strtolower($p['status']) ?>"><?= $p['status'] ?></span></td>
                        <td><?= $p['tanggal_bergabung'] ? date('d F Y', strtotime($p['tanggal_bergabung'])) : '-' ?></td>
                        <td>
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="detail_pegawai.php?id=<?=$p['id']?>" class="btn-action btn-view" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="pegawai_edit.php?id=<?=$p['id']?>" class="btn-action btn-edit" title="Edit">
                                    <i class="fas fa-pencil"></i>
                                </a>
                                <button class="btn-action btn-delete" title="Hapus" onclick="confirmDelete('pegawai.php?delete=<?=$p['id']?>','<?=htmlspecialchars($p['nama'])?>') ">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="d-flex align-items-center justify-content-between px-4 py-3 border-top" style="background:#fafbfc;">
        <div style="font-size:13px;color:#64748b;">
            Menampilkan <?= $offset+1 ?> - <?= min($offset+$per_page, $total_rows) ?> dari <?= $total_rows ?> data
        </div>
        <?php if($total_pages > 1): ?>
        <nav>
            <ul class="pagination mb-0">
                <li class="page-item <?= $page<=1?'disabled':'' ?>">
                    <a class="page-link" href="?page=<?=$page-1?>&jabatan=<?=$jabatan_filter?>&status=<?=$status_filter?>&search=<?=$search?>"><i class="fas fa-chevron-left fa-xs"></i></a>
                </li>
                <?php for($p2=1;$p2<=$total_pages;$p2++): ?>
                <?php if($p2==$page||$p2==1||$p2==$total_pages||abs($p2-$page)<=1): ?>
                <li class="page-item <?=$p2==$page?'active':''?>">
                    <a class="page-link" href="?page=<?=$p2?>&jabatan=<?=$jabatan_filter?>&status=<?=$status_filter?>&search=<?=$search?>"><?=$p2?></a>
                </li>
                <?php elseif(abs($p2-$page)==2): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <?php endfor; ?>
                <li class="page-item <?= $page>=$total_pages?'disabled':'' ?>">
                    <a class="page-link" href="?page=<?=$page+1?>&jabatan=<?=$jabatan_filter?>&status=<?=$status_filter?>&search=<?=$search?>"><i class="fas fa-chevron-right fa-xs"></i></a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'layout/footer.php'; ?>
