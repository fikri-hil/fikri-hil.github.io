<?php
$page_title = 'Generate Gaji';
require_once 'config/database.php';

$msg = ''; $msg_type = 'success';

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['generate'])) {
    $periode_id = intval($_POST['periode_id']);
    // Ambil pegawai aktif
    $pegawais = $pdo->query("SELECT * FROM pegawai WHERE status='Aktif'")->fetchAll();
    $tunjangans = $pdo->query("SELECT * FROM tunjangan WHERE status='Aktif'")->fetchAll();
    $potongans  = $pdo->query("SELECT * FROM potongan WHERE status='Aktif'")->fetchAll();

    $count = 0;
    foreach($pegawais as $p) {
        // Cek sudah ada data gaji untuk periode ini
        $exists = $pdo->prepare("SELECT COUNT(*) FROM data_gaji WHERE pegawai_id=? AND periode_id=?");
        $exists->execute([$p['id'], $periode_id]);
        if($exists->fetchColumn() > 0) continue;

        $gp = $p['gaji_pokok'];
        $total_tj = 0;
        foreach($tunjangans as $t) {
            $total_tj += $t['tipe']==='Persen' ? $gp * ($t['nominal']/100) : $t['nominal'];
        }
        $total_pt = 0;
        foreach($potongans as $pt) {
            $total_pt += $pt['tipe']==='Persen' ? $gp * ($pt['nominal']/100) : $pt['nominal'];
        }
        $total_gaji = $gp + $total_tj - $total_pt;

        $pdo->prepare("INSERT INTO data_gaji (pegawai_id,periode_id,gaji_pokok,total_tunjangan,total_potongan,total_gaji,status) VALUES (?,?,?,?,?,?,'Proses')")
            ->execute([$p['id'],$periode_id,$gp,$total_tj,$total_pt,$total_gaji]);
        $count++;
    }
    $msg = $count > 0 ? "Berhasil generate gaji untuk $count pegawai!" : "Semua pegawai sudah memiliki data gaji untuk periode ini.";
    $msg_type = $count > 0 ? 'success' : 'warning';
}

$periodes = $pdo->query("SELECT * FROM periode_gaji ORDER BY id DESC")->fetchAll();
$stats = $pdo->query("SELECT COUNT(DISTINCT pegawai_id) as sudah, (SELECT COUNT(*) FROM pegawai WHERE status='Aktif') as total FROM data_gaji WHERE periode_id=1")->fetch();

require_once 'layout/header.php';
?>
<div class="breadcrumb-wrap">
    <nav><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item active">Generate Gaji</li>
    </ol></nav>
</div>

<?php if($msg): ?>
<div class="alert alert-<?=$msg_type?> d-flex align-items-center gap-2 mb-3" style="border-radius:10px;font-size:13px;">
    <i class="fas fa-<?=$msg_type=='success'?'check-circle':'exclamation-circle'?>"></i> <?=$msg?>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><span class="fw-700" style="font-size:15px;">Generate Gaji Baru</span></div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:12px;">Pilih Periode</label>
                        <select name="periode_id" class="form-select" required>
                            <option value="">-- Pilih Periode --</option>
                            <?php foreach($periodes as $pr): ?>
                            <option value="<?=$pr['id']?>"><?=$pr['nama_periode']?> (<?=$pr['status']?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="note-info mb-4" style="margin-top:0;">
                        <i class="fas fa-info-circle me-1"></i>
                        Generate gaji akan menghitung gaji semua pegawai aktif berdasarkan tunjangan dan potongan yang aktif.
                    </div>
                    <button type="submit" name="generate" class="btn-primary-custom w-100 justify-content-center">
                        <i class="fas fa-play-circle"></i> Generate Gaji Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><span class="fw-700" style="font-size:15px;">Status Generate Gaji</span></div>
            <div class="card-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="stat-card text-center">
                            <div class="stat-value" style="font-size:28px;color:#6366f1;"><?=$stats['total']?></div>
                            <div class="stat-label">Total Pegawai Aktif</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card text-center">
                            <div class="stat-value" style="font-size:28px;color:#22c55e;"><?=$stats['sudah']?></div>
                            <div class="stat-label">Sudah Generate (Mei 2024)</div>
                        </div>
                    </div>
                </div>
                <div style="background:#f8fafc;border-radius:10px;padding:14px;">
                    <div style="font-size:12px;font-weight:700;color:#475569;margin-bottom:10px;">Periode Tersedia:</div>
                    <?php foreach($periodes as $pr): ?>
                    <?php $cnt = $pdo->prepare("SELECT COUNT(*) FROM data_gaji WHERE periode_id=?"); $cnt->execute([$pr['id']]); $c = $cnt->fetchColumn(); ?>
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <span style="font-size:13px;font-weight:600;"><?=$pr['nama_periode']?></span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge-status badge-<?=strtolower($pr['status'])?>"><?=$pr['status']?></span>
                            <span style="font-size:12px;color:#64748b;"><?=$c?> pegawai</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'layout/footer.php'; ?>
