<?php
$page_title = 'Detail Gaji';
require_once 'config/database.php';

$id = intval($_GET['id'] ?? 0);
if(!$id) { header("Location: data_gaji.php"); exit; }

$stmt = $pdo->prepare("SELECT dg.*, p.nama, p.nip, p.alamat, p.email, p.telepon, j.nama_jabatan, pg.nama_periode, pg.bulan, pg.tahun FROM data_gaji dg LEFT JOIN pegawai p ON dg.pegawai_id=p.id LEFT JOIN jabatan j ON p.jabatan_id=j.id LEFT JOIN periode_gaji pg ON dg.periode_id=pg.id WHERE dg.id=?");
$stmt->execute([$id]);
$r = $stmt->fetch();
if(!$r) { header("Location: data_gaji.php"); exit; }

require_once 'layout/header.php';
?>

<div class="breadcrumb-wrap">
    <nav><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item"><a href="data_gaji.php">Data Gaji</a></li>
        <li class="breadcrumb-item active">Detail Gaji</li>
    </ol></nav>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-700" style="font-size:15px;">Slip Gaji</span>
                <div class="d-flex gap-2">
                    <span class="badge-status badge-<?=strtolower($r['status'])?>"><?=$r['status']?></span>
                    <button class="btn-outline-custom" style="padding:5px 12px;font-size:12px;" onclick="window.print()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
            <div class="card-body p-4">
                <!-- Header Slip -->
                <div class="d-flex justify-content-between align-items-start mb-4 pb-4 border-bottom">
                    <div>
                        <div style="font-size:18px;font-weight:800;color:#0f172a;">PT. Maju Sejahtera</div>
                        <div style="font-size:12px;color:#64748b;">Slip Gaji Periode: <strong><?=$r['nama_periode']?></strong></div>
                    </div>
                    <div class="text-end">
                        <div style="font-size:12px;color:#64748b;">No. Slip</div>
                        <div style="font-size:13px;font-weight:700;">#SG-<?=str_pad($r['id'],5,'0',STR_PAD_LEFT)?></div>
                    </div>
                </div>

                <!-- Employee Info -->
                <div class="row g-3 mb-4 pb-4 border-bottom">
                    <div class="col-6">
                        <div style="font-size:11px;color:#94a3b8;margin-bottom:3px;">Nama Pegawai</div>
                        <div style="font-weight:700;font-size:15px;"><?=htmlspecialchars($r['nama'])?></div>
                    </div>
                    <div class="col-6">
                        <div style="font-size:11px;color:#94a3b8;margin-bottom:3px;">NIP</div>
                        <div style="font-weight:600;"><?=$r['nip']?></div>
                    </div>
                    <div class="col-6">
                        <div style="font-size:11px;color:#94a3b8;margin-bottom:3px;">Jabatan</div>
                        <div style="font-weight:600;"><?=$r['nama_jabatan']?></div>
                    </div>
                    <div class="col-6">
                        <div style="font-size:11px;color:#94a3b8;margin-bottom:3px;">Periode</div>
                        <div style="font-weight:600;"><?=$r['bulan']?> <?=$r['tahun']?></div>
                    </div>
                </div>

                <!-- Salary Breakdown -->
                <div class="row g-4">
                    <div class="col-md-6">
                        <div style="font-weight:700;font-size:13px;margin-bottom:12px;color:#16a34a;"><i class="fas fa-plus-circle me-1"></i> Pendapatan</div>
                        <?php
                        $pendapatan = [
                            ['Gaji Pokok', $r['gaji_pokok']],
                            ['Tunjangan', $r['total_tunjangan']],
                            ['Lembur', $r['total_lembur']],
                        ];
                        $total_pendapatan = 0;
                        foreach($pendapatan as $it): $total_pendapatan += $it[1]; ?>
                        <div class="d-flex justify-content-between mb-2 pb-1 border-bottom" style="border-color:#f1f5f9!important;">
                            <span style="font-size:13px;color:#475569;"><?=$it[0]?></span>
                            <span style="font-size:13px;">Rp<?=number_format($it[1],0,',','.')?></span>
                        </div>
                        <?php endforeach; ?>
                        <div class="d-flex justify-content-between mt-2">
                            <span style="font-size:13px;font-weight:700;">Total Pendapatan</span>
                            <span style="font-size:13px;font-weight:700;color:#16a34a;">Rp<?=number_format($total_pendapatan,0,',','.')?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div style="font-weight:700;font-size:13px;margin-bottom:12px;color:#ef4444;"><i class="fas fa-minus-circle me-1"></i> Potongan</div>
                        <?php
                        $potongan_items = [
                            ['BPJS Kesehatan (1%)', $r['gaji_pokok'] * 0.01],
                            ['BPJS Ketenagakerjaan (2%)', $r['gaji_pokok'] * 0.02],
                            ['PPh 21 (5%)', $r['gaji_pokok'] * 0.05],
                        ];
                        foreach($potongan_items as $it): ?>
                        <div class="d-flex justify-content-between mb-2 pb-1 border-bottom" style="border-color:#f1f5f9!important;">
                            <span style="font-size:13px;color:#475569;"><?=$it[0]?></span>
                            <span style="font-size:13px;color:#ef4444;">Rp<?=number_format($it[1],0,',','.')?></span>
                        </div>
                        <?php endforeach; ?>
                        <div class="d-flex justify-content-between mt-2">
                            <span style="font-size:13px;font-weight:700;">Total Potongan</span>
                            <span style="font-size:13px;font-weight:700;color:#ef4444;">Rp<?=number_format($r['total_potongan'],0,',','.')?></span>
                        </div>
                    </div>
                </div>

                <!-- Total -->
                <div class="mt-4 pt-4 border-top">
                    <div style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-radius:12px;padding:20px 24px;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div style="font-size:12px;color:#16a34a;font-weight:600;">GAJI BERSIH DITERIMA</div>
                                <div style="font-size:12px;color:#64748b;margin-top:2px;"><?=$r['bulan']?> <?=$r['tahun']?></div>
                            </div>
                            <div style="font-size:24px;font-weight:800;color:#16a34a;">
                                Rp<?= number_format($r['total_gaji'],0,',','.') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><span class="fw-700" style="font-size:14px;">Info Pegawai</span></div>
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="avatar-circle" style="width:52px;height:52px;font-size:18px;"><?=strtoupper(substr($r['nama'],0,2))?></div>
                    <div>
                        <div style="font-weight:700;font-size:14px;"><?=htmlspecialchars($r['nama'])?></div>
                        <div style="font-size:12px;color:#64748b;"><?=$r['nama_jabatan']?></div>
                    </div>
                </div>
                <?php if($r['email']): ?>
                <div class="d-flex gap-2 mb-2" style="font-size:13px;color:#475569;">
                    <i class="fas fa-envelope mt-1" style="color:#94a3b8;width:14px;"></i>
                    <?=htmlspecialchars($r['email'])?>
                </div>
                <?php endif; ?>
                <?php if($r['telepon']): ?>
                <div class="d-flex gap-2 mb-2" style="font-size:13px;color:#475569;">
                    <i class="fas fa-phone mt-1" style="color:#94a3b8;width:14px;"></i>
                    <?=htmlspecialchars($r['telepon'])?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><span class="fw-700" style="font-size:14px;">Aksi</span></div>
            <div class="card-body p-4 d-flex flex-column gap-2">
                <a href="data_gaji.php" class="btn-outline-custom justify-content-center"><i class="fas fa-arrow-left"></i> Kembali</a>
                <button class="btn-primary-custom justify-content-center" onclick="window.print()"><i class="fas fa-print"></i> Cetak Slip</button>
                <a href="export_pdf.php?id=<?=$r['id']?>&autoprint=1" target="_blank" class="btn-success-custom justify-content-center" style="text-decoration:none;"><i class="fas fa-file-pdf"></i> Download PDF</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'layout/footer.php'; ?>
