<?php
$page_title = 'Laporan Gaji';
require_once 'config/database.php';

$periode_filter = $_GET['periode'] ?? '1';
$jabatan_filter = $_GET['jabatan'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10; $offset = ($page - 1) * $per_page;

$where = "WHERE 1=1";
$params = [];
if($periode_filter) { $where .= " AND dg.periode_id=?"; $params[] = $periode_filter; }
if($jabatan_filter) { $where .= " AND p.jabatan_id=?"; $params[] = $jabatan_filter; }
if($status_filter) { $where .= " AND dg.status=?"; $params[] = $status_filter; }

$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM data_gaji dg LEFT JOIN pegawai p ON dg.pegawai_id=p.id $where");
$total_stmt->execute($params); $total_rows = $total_stmt->fetchColumn();
$total_pages = ceil($total_rows / $per_page);

$stmt = $pdo->prepare("SELECT dg.*, p.nama, p.nip, j.nama_jabatan FROM data_gaji dg LEFT JOIN pegawai p ON dg.pegawai_id=p.id LEFT JOIN jabatan j ON p.jabatan_id=j.id $where ORDER BY j.nama_jabatan, p.nama LIMIT $per_page OFFSET $offset");
$stmt->execute($params); $list = $stmt->fetchAll();

// Totals (all pages)
$tot_stmt = $pdo->prepare("SELECT COALESCE(SUM(dg.gaji_pokok),0) as gp, COALESCE(SUM(dg.total_tunjangan),0) as tj, COALESCE(SUM(dg.total_potongan),0) as pt, COALESCE(SUM(dg.total_gaji),0) as tg, COUNT(DISTINCT dg.pegawai_id) as cnt FROM data_gaji dg LEFT JOIN pegawai p ON dg.pegawai_id=p.id $where");
$tot_stmt->execute($params); $totals = $tot_stmt->fetch();

// Summary rows total
$sum_gp = $sum_tj_istri = $sum_tj_anak = $sum_tj_alpha = $sum_tj_terlambat = $sum_pt = $sum_tg = 0;
foreach($list as $r) {
    $sum_gp += $r['gaji_pokok'];
    $sum_tj_istri += $r['gaji_pokok'] * 0.08;
    $sum_tj_anak  += $r['gaji_pokok'] * 0.05;
    $sum_tj_alpha += $r['gaji_pokok'] * 0.02;
    $sum_tj_terlambat += $r['gaji_pokok'] * 0.015;
    $sum_pt += $r['total_potongan'];
    $sum_tg += $r['total_gaji'];
}

$periodes = $pdo->query("SELECT * FROM periode_gaji ORDER BY id DESC")->fetchAll();
$jabatans = $pdo->query("SELECT * FROM jabatan ORDER BY nama_jabatan")->fetchAll();

require_once 'layout/header.php';
?>
<div class="breadcrumb-wrap">
    <nav><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item active">Laporan Gaji</li>
    </ol></nav>
</div>

<!-- Filter -->
<div class="filter-bar mb-3">
    <h6 class="fw-700 mb-3" style="font-size:14px;">Filter Laporan</h6>
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-auto">
            <label class="form-label fw-600 mb-1" style="font-size:11px;">Periode</label>
            <select name="periode" class="form-select" style="width:160px;">
                <option value="">Semua Periode</option>
                <?php foreach($periodes as $pr): ?>
                <option value="<?=$pr['id']?>" <?=$periode_filter==$pr['id']?'selected':''?>><?=$pr['nama_periode']?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label fw-600 mb-1" style="font-size:11px;">Jabatan</label>
            <select name="jabatan" class="form-select" style="width:180px;">
                <option value="">Semua Jabatan</option>
                <?php foreach($jabatans as $j): ?>
                <option value="<?=$j['id']?>" <?=$jabatan_filter==$j['id']?'selected':''?>><?=$j['nama_jabatan']?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label fw-600 mb-1" style="font-size:11px;">Status Pegawai</label>
            <select name="status" class="form-select" style="width:150px;">
                <option value="">Semua Status</option>
                <option value="Proses" <?=$status_filter=='Proses'?'selected':''?>>Proses</option>
                <option value="Dibayar" <?=$status_filter=='Dibayar'?'selected':''?>>Dibayar</option>
            </select>
        </div>
        <div class="col-auto d-flex gap-2">
            <button type="submit" class="btn-primary-custom"><i class="fas fa-filter"></i> Filter</button>
            <a href="laporan_gaji.php" class="btn-outline-custom"><i class="fas fa-redo"></i> Reset</a>
            <a href="export_excel.php?type=laporan_gaji&periode=<?=$periode_filter?>&jabatan=<?=urlencode($jabatan_filter)?>&status=<?=urlencode($status_filter)?>" class="btn-success-custom"><i class="fas fa-file-excel"></i> Export Excel</a>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-3">
    <?php
    $scards = [
        ['TOTAL PEGAWAI', $totals['cnt'].' Orang', 'blue', 'fa-users'],
        ['TOTAL GAJI POKOK', 'Rp'.number_format($totals['gp'],0,',','.'), 'green', 'fa-dollar-sign'],
        ['TOTAL TUNJANGAN', 'Rp'.number_format($totals['tj'],0,',','.'), 'purple', 'fa-gift'],
        ['TOTAL POTONGAN', 'Rp'.number_format($totals['pt'],0,',','.'), 'red', 'fa-percent'],
        ['TOTAL DIBAYAR', 'Rp'.number_format($totals['tg'],0,',','.'), 'orange', 'fa-wallet'],
    ];
    foreach($scards as $sc): ?>
    <div class="col">
        <div class="stat-card">
            <div class="stat-icon <?=$sc[2]?> mb-2" style="width:38px;height:38px;font-size:16px;"><i class="fas <?=$sc[3]?>"></i></div>
            <div style="font-size:14px;font-weight:700;"><?=$sc[1]?></div>
            <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-top:2px;"><?=$sc[0]?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <span class="fw-700" style="font-size:15px;">Rincian Laporan Gaji</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table" style="font-size:12px;">
                <thead>
                    <tr>
                        <th rowspan="2" style="vertical-align:middle;">No</th>
                        <th rowspan="2" style="vertical-align:middle;">NIP</th>
                        <th rowspan="2" style="vertical-align:middle;">Nama Pegawai</th>
                        <th rowspan="2" style="vertical-align:middle;">Jabatan</th>
                        <th rowspan="2" style="vertical-align:middle;">Gaji Pokok</th>
                        <th colspan="4" style="text-align:center;background:#f0fdf4;color:#16a34a;">Tunjangan</th>
                        <th colspan="3" style="text-align:center;background:#fef2f2;color:#dc2626;">Potongan</th>
                        <th rowspan="2" style="vertical-align:middle;color:#dc2626;">Total Potongan</th>
                        <th rowspan="2" style="vertical-align:middle;color:#16a34a;">Total Gaji (Diterima)</th>
                        <th rowspan="2" style="vertical-align:middle;">Aksi</th>
                    </tr>
                    <tr>
                        <th style="background:#f0fdf4;color:#16a34a;">Istri/Suami</th>
                        <th style="background:#f0fdf4;color:#16a34a;">Anak</th>
                        <th style="background:#f0fdf4;color:#16a34a;">Alpha</th>
                        <th style="background:#f0fdf4;color:#16a34a;">Terlambat</th>
                        <th style="background:#fef2f2;color:#dc2626;">Alpha</th>
                        <th style="background:#fef2f2;color:#dc2626;">Terlambat</th>
                        <th style="background:#fef2f2;color:#dc2626;">Lainnya</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($list)): ?>
                    <tr><td colspan="15" class="text-center text-muted py-4">Tidak ada data</td></tr>
                    <?php else: ?>
                    <?php foreach($list as $i => $r): ?>
                    <?php
                        $tj_istri = round($r['gaji_pokok'] * 0.08);
                        $tj_anak  = round($r['gaji_pokok'] * 0.05);
                        $tj_alpha = round($r['gaji_pokok'] * 0.02);
                        $tj_terlambat = round($r['gaji_pokok'] * 0.015);
                        $pt_alpha = round($r['total_potongan'] * 0.3);
                        $pt_terlambat = round($r['total_potongan'] * 0.4);
                        $pt_lain = $r['total_potongan'] - $pt_alpha - $pt_terlambat;
                    ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td><?= $r['nip'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle" style="width:26px;height:26px;font-size:9px;"><?=strtoupper(substr($r['nama']??'?',0,2))?></div>
                                <span style="font-weight:600;"><?=htmlspecialchars($r['nama']??'-')?></span>
                            </div>
                        </td>
                        <td><?= $r['nama_jabatan'] ?? '-' ?></td>
                        <td>Rp<?= number_format($r['gaji_pokok'],0,',','.') ?></td>
                        <td>Rp<?= number_format($tj_istri,0,',','.') ?></td>
                        <td>Rp<?= number_format($tj_anak,0,',','.') ?></td>
                        <td>Rp<?= number_format($tj_alpha,0,',','.') ?></td>
                        <td>Rp<?= number_format($tj_terlambat,0,',','.') ?></td>
                        <td style="color:#ef4444;">Rp<?= number_format($pt_alpha,0,',','.') ?></td>
                        <td style="color:#ef4444;">Rp<?= number_format($pt_terlambat,0,',','.') ?></td>
                        <td style="color:#ef4444;">Rp0</td>
                        <td style="color:#ef4444;font-weight:700;">Rp<?= number_format($r['total_potongan'],0,',','.') ?></td>
                        <td style="color:#16a34a;font-weight:700;">Rp<?= number_format($r['total_gaji'],0,',','.') ?></td>
                        <td>
                            <a href="detail_gaji.php?id=<?=$r['id']?>" class="btn-action btn-view" title="Detail"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <!-- TOTAL ROW -->
                    <tr style="background:#f8fafc;font-weight:700;border-top:2px solid #e2e8f0;">
                        <td colspan="4" style="text-align:right;font-size:12px;">TOTAL</td>
                        <td>Rp<?= number_format($sum_gp,0,',','.') ?></td>
                        <td>Rp<?= number_format($sum_tj_istri,0,',','.') ?></td>
                        <td>Rp<?= number_format($sum_tj_anak,0,',','.') ?></td>
                        <td>Rp<?= number_format($sum_tj_alpha,0,',','.') ?></td>
                        <td>Rp<?= number_format($sum_tj_terlambat,0,',','.') ?></td>
                        <td style="color:#ef4444;">-</td>
                        <td style="color:#ef4444;">-</td>
                        <td style="color:#ef4444;">Rp0</td>
                        <td style="color:#ef4444;">Rp<?= number_format($sum_pt,0,',','.') ?></td>
                        <td style="color:#16a34a;">Rp<?= number_format($sum_tg,0,',','.') ?></td>
                        <td></td>
                    </tr>
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
<div class="note-info mt-3"><i class="fas fa-info-circle me-2"></i><strong>Catatan:</strong> Laporan gaji menampilkan ringkasan pembayaran gaji sesuai periode yang dipilih.</div>

<?php require_once 'layout/footer.php'; ?>
