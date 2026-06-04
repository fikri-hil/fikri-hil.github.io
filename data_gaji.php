<?php
$page_title = 'Data Gaji';
require_once 'config/database.php';

$periode_filter = $_GET['periode'] ?? '1';
$jabatan_filter = $_GET['jabatan'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10; $offset = ($page - 1) * $per_page;

$where = "WHERE 1=1";
$params = [];
if($periode_filter) { $where .= " AND dg.periode_id=?"; $params[] = $periode_filter; }
if($jabatan_filter) { $where .= " AND p.jabatan_id=?"; $params[] = $jabatan_filter; }
if($status_filter) { $where .= " AND dg.status=?"; $params[] = $status_filter; }
if($search) { $where .= " AND (p.nama LIKE ? OR p.nip LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM data_gaji dg LEFT JOIN pegawai p ON dg.pegawai_id=p.id $where");
$total_stmt->execute($params);
$total_rows = $total_stmt->fetchColumn();
$total_pages = ceil($total_rows / $per_page);

$stmt = $pdo->prepare("SELECT dg.*, p.nama, p.nip, j.nama_jabatan FROM data_gaji dg LEFT JOIN pegawai p ON dg.pegawai_id=p.id LEFT JOIN jabatan j ON p.jabatan_id=j.id $where LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$list = $stmt->fetchAll();

// Summary — ikuti SEMUA filter aktif (periode, jabatan, status, search)
$sum_where  = "WHERE 1=1"; $sum_params = [];
if($periode_filter) { $sum_where .= " AND dg.periode_id=?";           $sum_params[] = $periode_filter; }
if($jabatan_filter) { $sum_where .= " AND p.jabatan_id=?";             $sum_params[] = $jabatan_filter; }
if($status_filter)  { $sum_where .= " AND dg.status=?";                $sum_params[] = $status_filter; }
if($search)         { $sum_where .= " AND (p.nama LIKE ? OR p.nip LIKE ?)"; $sum_params[] = "%$search%"; $sum_params[] = "%$search%"; }
$sum_stmt = $pdo->prepare("SELECT COUNT(DISTINCT dg.pegawai_id) as total_pegawai, COALESCE(SUM(dg.gaji_pokok),0) as total_gp, COALESCE(SUM(dg.total_tunjangan),0) as total_tj, COALESCE(SUM(dg.total_potongan),0) as total_pt, COALESCE(SUM(dg.total_gaji),0) as total_dibayar FROM data_gaji dg LEFT JOIN pegawai p ON dg.pegawai_id=p.id $sum_where");
$sum_stmt->execute($sum_params); $sum = $sum_stmt->fetch();

$periodes = $pdo->query("SELECT * FROM periode_gaji ORDER BY id DESC")->fetchAll();
$jabatans = $pdo->query("SELECT * FROM jabatan ORDER BY nama_jabatan")->fetchAll();

require_once 'layout/header.php';
?>

<div class="breadcrumb-wrap">
    <nav><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item active">Data Gaji</li>
    </ol></nav>
</div>

<!-- Filter -->
<div class="filter-bar mb-3">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
        <div>
            <label class="form-label fw-600 mb-1" style="font-size:11px;">Periode</label>
            <select name="periode" class="form-select" style="width:150px;">
                <option value="">Semua Periode</option>
                <?php foreach($periodes as $pr): ?>
                <option value="<?=$pr['id']?>" <?=$periode_filter==$pr['id']?'selected':''?>><?=$pr['nama_periode']?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label fw-600 mb-1" style="font-size:11px;">Jabatan</label>
            <select name="jabatan" class="form-select" style="width:170px;">
                <option value="">Semua Jabatan</option>
                <?php foreach($jabatans as $j): ?>
                <option value="<?=$j['id']?>" <?=$jabatan_filter==$j['id']?'selected':''?>><?=$j['nama_jabatan']?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label fw-600 mb-1" style="font-size:11px;">Status</label>
            <select name="status" class="form-select" style="width:150px;">
                <option value="">Semua Status</option>
                <option value="Proses" <?=$status_filter=='Proses'?'selected':''?>>Proses</option>
                <option value="Dibayar" <?=$status_filter=='Dibayar'?'selected':''?>>Dibayar</option>
            </select>
        </div>
        <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" name="search" class="form-control" placeholder="Cari pegawai..." value="<?=htmlspecialchars($search)?>" style="width:200px;">
        </div>
        <div class="d-flex gap-2 align-self-end">
            <button type="submit" class="btn-primary-custom"><i class="fas fa-filter"></i> Filter</button>
            <a href="export_excel.php?type=data_gaji&periode=<?=$periode_filter?>" class="btn-success-custom"><i class="fas fa-file-excel"></i> Export Excel</a>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-3">
    <?php
    $scards = [
        ['Total Pegawai', $sum['total_pegawai'].' Orang', 'blue', 'fa-users'],
        ['Total Gaji Pokok', 'Rp'.number_format($sum['total_gp'],0,',','.'), 'green', 'fa-dollar-sign'],
        ['Total Tunjangan', 'Rp'.number_format($sum['total_tj'],0,',','.'), 'purple', 'fa-gift'],
        ['Total Potongan', 'Rp'.number_format($sum['total_pt'],0,',','.'), 'red', 'fa-percent'],
        ['Total Dibayar', 'Rp'.number_format($sum['total_dibayar'],0,',','.'), 'orange', 'fa-wallet'],
    ];
    foreach($scards as $sc): ?>
    <div class="col">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="stat-icon <?=$sc[2]?>" style="width:34px;height:34px;font-size:14px;"><i class="fas <?=$sc[3]?>"></i></div>
                <div class="stat-label" style="font-size:10px;"><?=$sc[0]?></div>
            </div>
            <div style="font-size:15px;font-weight:700;color:#0f172a;"><?=$sc[1]?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>NIP</th>
                        <th>Nama Pegawai</th>
                        <th>Jabatan</th>
                        <th>Gaji Pokok</th>
                        <th>Total Tunjangan</th>
                        <th>Total Potongan</th>
                        <th>Total Gaji</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($list)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">Tidak ada data</td></tr>
                    <?php else: ?>
                    <?php foreach($list as $i => $r): ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td><?= $r['nip'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle" style="width:28px;height:28px;font-size:10px;"><?=strtoupper(substr($r['nama']??'?',0,2))?></div>
                                <span style="font-weight:600;"><?=htmlspecialchars($r['nama']??'-')?></span>
                            </div>
                        </td>
                        <td><?= $r['nama_jabatan'] ?? '-' ?></td>
                        <td>Rp<?= number_format($r['gaji_pokok'],0,',','.') ?></td>
                        <td>Rp<?= number_format($r['total_tunjangan'],0,',','.') ?></td>
                        <td style="color:#ef4444;">Rp<?= number_format($r['total_potongan'],0,',','.') ?></td>
                        <td style="font-weight:700;color:#22c55e;">Rp<?= number_format($r['total_gaji'],0,',','.') ?></td>
                        <td><span class="badge-status badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                        <td>
                            <a href="detail_gaji.php?id=<?=$r['id']?>" class="btn-action btn-view" title="Detail"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="d-flex align-items-center justify-content-between px-4 py-3 border-top" style="background:#fafbfc;">
        <div style="font-size:13px;color:#64748b;">Menampilkan <?=$offset+1?> - <?=min($offset+$per_page,$total_rows)?> dari <?=$total_rows?> data</div>
        <?php if($total_pages > 1): ?>
        <nav><ul class="pagination mb-0">
            <li class="page-item <?=$page<=1?'disabled':''?>"><a class="page-link" href="?page=<?=$page-1?>&periode=<?=$periode_filter?>"><i class="fas fa-chevron-left fa-xs"></i></a></li>
            <?php for($p2=1;$p2<=$total_pages;$p2++): ?>
            <?php if($p2==$page||$p2==1||$p2==$total_pages||abs($p2-$page)<=1): ?>
            <li class="page-item <?=$p2==$page?'active':''?>"><a class="page-link" href="?page=<?=$p2?>&periode=<?=$periode_filter?>"><?=$p2?></a></li>
            <?php elseif(abs($p2-$page)==2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
            <?php endfor; ?>
            <li class="page-item <?=$page>=$total_pages?'disabled':''?>"><a class="page-link" href="?page=<?=$page+1?>&periode=<?=$periode_filter?>"><i class="fas fa-chevron-right fa-xs"></i></a></li>
        </ul></nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'layout/footer.php'; ?>
