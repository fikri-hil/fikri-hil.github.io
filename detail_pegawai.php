<?php
$page_title = 'Detail Pegawai';
require_once 'config/database.php';

$id = intval($_GET['id'] ?? 0);
if(!$id) { header("Location: pegawai.php"); exit; }

$pegawai = $pdo->prepare("SELECT p.*, j.nama_jabatan FROM pegawai p LEFT JOIN jabatan j ON p.jabatan_id=j.id WHERE p.id=?");
$pegawai->execute([$id]);
$p = $pegawai->fetch();
if(!$p) { header("Location: pegawai.php"); exit; }

// Last payroll
$gaji = $pdo->prepare("SELECT dg.*, pg.nama_periode FROM data_gaji dg LEFT JOIN periode_gaji pg ON dg.periode_id=pg.id WHERE dg.pegawai_id=? ORDER BY dg.id DESC LIMIT 1");
$gaji->execute([$id]);
$last_gaji = $gaji->fetch();

require_once 'layout/header.php';
?>

<div class="breadcrumb-wrap">
    <nav><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item"><a href="pegawai.php">Pegawai</a></li>
        <li class="breadcrumb-item active">Detail</li>
    </ol></nav>
</div>

<div class="row g-4">
    <!-- Left: Profile Card -->
    <div class="col-xl-3 col-lg-4">
        <div class="card p-0 overflow-hidden">
            <div style="background:linear-gradient(135deg,#6366f1,#818cf8);height:80px;"></div>
            <div class="p-4 text-center" style="margin-top:-40px;">
                <div class="avatar-circle mx-auto mb-3" style="width:80px;height:80px;font-size:28px;border:4px solid #fff;box-shadow:0 4px 12px rgba(99,102,241,.3);">
                    <?php if($p['foto']): ?>
                    <img src="uploads/<?=$p['foto']?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                    <?php else: ?>
                    <?= strtoupper(substr($p['nama'],0,2)) ?>
                    <?php endif; ?>
                </div>
                <span class="badge-status badge-<?= strtolower($p['status']) ?> d-inline-block mb-2"><?= $p['status'] ?></span>
                <h5 style="font-weight:700;font-size:16px;margin:4px 0 2px;"><?= htmlspecialchars($p['nama']) ?></h5>
                <p style="color:#64748b;font-size:13px;margin:0;"><?= $p['nama_jabatan'] ?? '-' ?></p>
            </div>
            <div class="border-top p-4">
                <div class="d-flex flex-column gap-2">
                    <?php if($p['email']): ?>
                    <div class="d-flex align-items-center gap-2" style="font-size:13px;color:#475569;">
                        <i class="fas fa-envelope" style="width:16px;color:#94a3b8;"></i>
                        <?= htmlspecialchars($p['email']) ?>
                    </div>
                    <?php endif; ?>
                    <?php if($p['telepon']): ?>
                    <div class="d-flex align-items-center gap-2" style="font-size:13px;color:#475569;">
                        <i class="fas fa-phone" style="width:16px;color:#94a3b8;"></i>
                        <?= htmlspecialchars($p['telepon']) ?>
                    </div>
                    <?php endif; ?>
                    <?php if($p['alamat']): ?>
                    <div class="d-flex align-items-start gap-2" style="font-size:13px;color:#475569;">
                        <i class="fas fa-map-marker-alt mt-1" style="width:16px;color:#94a3b8;flex-shrink:0;"></i>
                        <?= htmlspecialchars($p['alamat']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="border-top p-4 d-flex gap-2">
                <a href="pegawai_edit.php?id=<?=$p['id']?>" class="btn-primary-custom flex-grow-1 justify-content-center">
                    <i class="fas fa-pencil"></i> Edit Data
                </a>
                <a href="pegawai.php" class="btn-outline-custom flex-grow-1 justify-content-center">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <!-- Right: Info Cards -->
    <div class="col-xl-9 col-lg-8">
        <div class="row g-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><span class="fw-700" style="font-size:15px;">Informasi Pegawai</span></div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <?php
                            $fields = [
                                ['NIP', $p['nip']],
                                ['Tempat, Tanggal Lahir', ($p['tempat_lahir']??'').', '.($p['tanggal_lahir'] ? date('d M Y', strtotime($p['tanggal_lahir'])) : '-')],
                                ['Jenis Kelamin', $p['jenis_kelamin'] ?? '-'],
                                ['Agama', $p['agama'] ?? '-'],
                                ['Status Pernikahan', $p['status_pernikahan'] ?? '-'],
                                ['Jumlah Anak', $p['jumlah_anak'] ?? 0],
                                ['Jabatan', $p['nama_jabatan'] ?? '-'],
                                ['Tanggal Bergabung', $p['tanggal_bergabung'] ? date('d F Y', strtotime($p['tanggal_bergabung'])) : '-'],
                                ['Gaji Pokok', 'Rp'.number_format($p['gaji_pokok'],0,',','.')],
                                ['Email', $p['email'] ?? '-'],
                            ];
                            foreach($fields as $f): ?>
                            <div class="col-md-6">
                                <div style="font-size:12px;color:#94a3b8;margin-bottom:2px;"><?=$f[0]?></div>
                                <div style="font-size:13px;font-weight:500;color:#1e293b;"><?= htmlspecialchars($f[1]) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><span class="fw-700" style="font-size:15px;">Riwayat Penggajian Terakhir</span></div>
                    <div class="card-body p-4">
                        <?php if($last_gaji): ?>
                        <div style="font-size:12px;color:#94a3b8;margin-bottom:12px;">Periode: <strong><?=$last_gaji['nama_periode']?></strong></div>
                        <?php
                        $gfields = [
                            ['Gaji Pokok', 'Rp'.number_format($last_gaji['gaji_pokok'],0,',','.'), '#1e293b'],
                            ['Tunjangan', 'Rp'.number_format($last_gaji['total_tunjangan'],0,',','.'), '#1e293b'],
                            ['Potongan', 'Rp'.number_format($last_gaji['total_potongan'],0,',','.'), '#ef4444'],
                        ];
                        foreach($gfields as $gf): ?>
                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span style="font-size:13px;color:#64748b;"><?=$gf[0]?></span>
                            <span style="font-size:13px;font-weight:600;color:<?=$gf[2]?>;"><?=$gf[1]?></span>
                        </div>
                        <?php endforeach; ?>
                        <div class="d-flex justify-content-between mt-3">
                            <span style="font-size:14px;font-weight:700;">Total Gaji</span>
                            <span style="font-size:16px;font-weight:800;color:#22c55e;">Rp<?=number_format($last_gaji['total_gaji'],0,',','.')?></span>
                        </div>
                        <a href="detail_gaji.php?id=<?=$last_gaji['id']?>" class="btn-outline-custom w-100 justify-content-center mt-3">
                            <i class="fas fa-file-invoice-dollar"></i> Lihat Detail Gaji
                        </a>
                        <?php else: ?>
                        <p class="text-muted text-center py-4" style="font-size:13px;">Belum ada data gaji</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><span class="fw-700" style="font-size:15px;">Dokumen</span></div>
                    <div class="card-body p-4">
                        <?php foreach(['KTP','NPWP','KK'] as $dok): ?>
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:32px;height:32px;background:#eff6ff;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-file-alt" style="color:#3b82f6;font-size:13px;"></i>
                                </div>
                                <span style="font-size:13px;font-weight:600;"><?=$dok?></span>
                            </div>
                            <button class="btn-outline-custom" style="padding:5px 12px;font-size:12px;">
                                <i class="fas fa-eye"></i> Lihat
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'layout/footer.php'; ?>
