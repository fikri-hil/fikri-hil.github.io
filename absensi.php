<?php
$page_title = 'Data Absensi';
require_once 'config/database.php';

$date_from = $_GET['from'] ?? date('Y-m-01');
$date_to = $_GET['to'] ?? date('Y-m-t');
$pegawai_filter = $_GET['pegawai'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where = "WHERE a.tanggal BETWEEN ? AND ?";
$params = [$date_from, $date_to];
if($pegawai_filter) { $where .= " AND a.pegawai_id=?"; $params[] = $pegawai_filter; }
if($status_filter) { $where .= " AND a.status=?"; $params[] = $status_filter; }

$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM absensi a $where");
$total_stmt->execute($params);
$total_rows = $total_stmt->fetchColumn();
$total_pages = ceil($total_rows / $per_page);

$stmt = $pdo->prepare("SELECT a.*, p.nama, p.nip, j.nama_jabatan FROM absensi a LEFT JOIN pegawai p ON a.pegawai_id=p.id LEFT JOIN jabatan j ON p.jabatan_id=j.id $where ORDER BY a.tanggal DESC, a.id LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$absensi_list = $stmt->fetchAll();

// Summary
$sum_stmt = $pdo->prepare("SELECT 
    SUM(status IN ('Hadir')) as hadir,
    SUM(status='Terlambat') as terlambat,
    SUM(status='Izin') as izin,
    SUM(status='Sakit') as sakit,
    SUM(status='Alpha') as alpha,
    COUNT(*) as total
    FROM absensi a $where");
$sum_stmt->execute($params);
$summary = $sum_stmt->fetch();

$pegawai_list = $pdo->query("SELECT id, nama, nip FROM pegawai WHERE status='Aktif' ORDER BY nama")->fetchAll();

require_once 'layout/header.php';
?>

<div class="breadcrumb-wrap">
    <nav><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item active">Absensi</li>
    </ol></nav>
</div>

<!-- Filter -->
<div class="filter-bar mb-3">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
        <div>
            <label class="form-label fw-600 mb-1" style="font-size:11px;">Periode</label>
            <div class="d-flex gap-2">
                <input type="date" name="from" class="form-control" value="<?=$date_from?>" style="width:140px;">
                <input type="date" name="to" class="form-control" value="<?=$date_to?>" style="width:140px;">
            </div>
        </div>
        <div>
            <label class="form-label fw-600 mb-1" style="font-size:11px;">Pegawai</label>
            <select name="pegawai" class="form-select" style="width:180px;">
                <option value="">Semua Pegawai</option>
                <?php foreach($pegawai_list as $pg): ?>
                <option value="<?=$pg['id']?>" <?=$pegawai_filter==$pg['id']?'selected':''?>><?=$pg['nama']?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label fw-600 mb-1" style="font-size:11px;">Status</label>
            <select name="status" class="form-select" style="width:140px;">
                <option value="">Semua Status</option>
                <?php foreach(['Hadir','Terlambat','Izin','Sakit','Alpha'] as $s): ?>
                <option value="<?=$s?>" <?=$status_filter==$s?'selected':''?>><?=$s?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="d-flex gap-2 align-self-end">
            <button type="submit" class="btn-primary-custom"><i class="fas fa-filter"></i> Filter</button>
            <a href="absensi.php" class="btn-outline-custom"><i class="fas fa-redo"></i> Reset</a>
            <a href="export_excel.php?type=absensi&from=<?=$date_from?>&to=<?=$date_to?>&pegawai=<?=urlencode($pegawai_filter)?>&status=<?=urlencode($status_filter)?>" class="btn-success-custom"><i class="fas fa-file-excel"></i> Export Excel</a>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-3">
    <?php
    $cards = [
        ['Total Hadir', $summary['hadir']??0, number_format(($summary['hadir']??0)/max($summary['total'],1)*100,2).'%', 'blue', 'fa-calendar-check'],
        ['Terlambat', $summary['terlambat']??0, number_format(($summary['terlambat']??0)/max($summary['total'],1)*100,2).'%', 'orange', 'fa-clock'],
        ['Izin', $summary['izin']??0, number_format(($summary['izin']??0)/max($summary['total'],1)*100,2).'%', 'info', 'fa-door-open'],
        ['Sakit', $summary['sakit']??0, number_format(($summary['sakit']??0)/max($summary['total'],1)*100,2).'%', 'purple', 'fa-hospital'],
        ['Alpha', $summary['alpha']??0, number_format(($summary['alpha']??0)/max($summary['total'],1)*100,2).'%', 'red', 'fa-exclamation-triangle'],
    ];
    foreach($cards as $c): ?>
    <div class="col">
        <div class="stat-card">
            <div class="stat-icon <?=$c[3]?> mb-2" style="width:36px;height:36px;font-size:16px;">
                <i class="fas <?=$c[4]?>"></i>
            </div>
            <div class="stat-value" style="font-size:20px;"><?=$c[1]?></div>
            <div class="stat-label" style="font-size:10px;"><?=$c[0]?></div>
            <div class="stat-sub"><?=$c[2]?></div>
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
                        <th>Tanggal</th>
                        <th>NIP</th>
                        <th>Nama Pegawai</th>
                        <th>Jabatan</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($absensi_list)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">Tidak ada data</td></tr>
                    <?php else: ?>
                    <?php foreach($absensi_list as $i => $a): ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td>
                            <div style="font-weight:600;"><?= date('d M Y', strtotime($a['tanggal'])) ?></div>
                            <div style="font-size:11px;color:#94a3b8;"><?= date('l', strtotime($a['tanggal'])) ?></div>
                        </td>
                        <td><?= $a['nip'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle" style="width:28px;height:28px;font-size:10px;"><?= strtoupper(substr($a['nama']??'?',0,2)) ?></div>
                                <span style="font-weight:600;"><?= htmlspecialchars($a['nama']??'-') ?></span>
                            </div>
                        </td>
                        <td><?= $a['nama_jabatan'] ?? '-' ?></td>
                        <td>
                            <?php if($a['check_in']): ?>
                            <span style="color:<?= $a['status']=='Terlambat' ? '#f59e0b' : '#22c55e' ?>;font-weight:600;"><?= $a['check_in'] ?></span>
                            <?php else: ?><span style="color:#94a3b8;">-</span><?php endif; ?>
                        </td>
                        <td>
                            <?php if($a['check_out']): ?>
                            <span style="color:#22c55e;font-weight:600;"><?= $a['check_out'] ?></span>
                            <?php else: ?><span style="color:#94a3b8;">-</span><?php endif; ?>
                        </td>
                        <td><span class="badge-status badge-<?= strtolower($a['status']) ?>"><?= $a['status'] ?></span></td>
                        <td style="font-size:12px;color:#64748b;"><?= $a['keterangan'] ?? '-' ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <form method="POST" action="absensi_update.php" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                    <input type="hidden" name="action" value="hadir">
                                    <input type="hidden" name="redirect" value="absensi.php?from=<?= $date_from ?>&to=<?= $date_to ?>&page=<?= $page ?>">
                                    <button type="submit" class="btn-action btn-hadir" title="Tandai Hadir"
                                        style="background:<?= in_array($a['status'],['Hadir','Terlambat']) ? '#22c55e' : '#f0fdf4' ?>;
                                               color:<?= in_array($a['status'],['Hadir','Terlambat']) ? '#fff' : '#22c55e' ?>;
                                               border:1.5px solid #22c55e;">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <form method="POST" action="absensi_update.php" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                    <input type="hidden" name="action" value="tidak_hadir">
                                    <input type="hidden" name="redirect" value="absensi.php?from=<?= $date_from ?>&to=<?= $date_to ?>&page=<?= $page ?>">
                                    <button type="submit" class="btn-action btn-tidak-hadir" title="Tandai Tidak Hadir"
                                        style="background:<?= in_array($a['status'],['Alpha','Sakit','Izin']) ? '#ef4444' : '#fef2f2' ?>;
                                               color:<?= in_array($a['status'],['Alpha','Sakit','Izin']) ? '#fff' : '#ef4444' ?>;
                                               border:1.5px solid #ef4444;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
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
        <div style="font-size:13px;color:#64748b;">Menampilkan <?=$offset+1?> - <?=min($offset+$per_page,$total_rows)?> dari <?=$total_rows?> data</div>
        <?php if($total_pages > 1): ?>
        <nav>
            <ul class="pagination mb-0">
                <li class="page-item <?=$page<=1?'disabled':''?>">
                    <a class="page-link" href="?page=<?=$page-1?>&from=<?=$date_from?>&to=<?=$date_to?>"><i class="fas fa-chevron-left fa-xs"></i></a>
                </li>
                <?php for($p2=1;$p2<=$total_pages;$p2++): ?>
                <?php if($p2==$page||$p2==1||$p2==$total_pages||abs($p2-$page)<=1): ?>
                <li class="page-item <?=$p2==$page?'active':''?>">
                    <a class="page-link" href="?page=<?=$p2?>&from=<?=$date_from?>&to=<?=$date_to?>"><?=$p2?></a>
                </li>
                <?php elseif(abs($p2-$page)==2): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <?php endfor; ?>
                <li class="page-item <?=$page>=$total_pages?'disabled':''?>">
                    <a class="page-link" href="?page=<?=$page+1?>&from=<?=$date_from?>&to=<?=$date_to?>"><i class="fas fa-chevron-right fa-xs"></i></a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'layout/footer.php'; ?>
