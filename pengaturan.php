<?php
$page_title = 'Pengaturan';
require_once 'config/database.php';

// Ambil semua setting
$settings = [];
$rows = $pdo->query("SELECT setting_key, setting_value FROM pengaturan")->fetchAll();
foreach($rows as $r) {
    $settings[$r['setting_key']] = $r['setting_value'];
}

$msg = '';
if($_SERVER['REQUEST_METHOD']==='POST') {
    $tab = $_POST['active_tab'] ?? 'perusahaan';
    $fields = [];
    switch($tab) {
        case 'perusahaan':
            $fields = ['nama_perusahaan','alamat','telepon','email','npwp'];
            break;
        case 'penggajian':
            $fields = ['gaji_minimum','pembulatan_gaji','metode_pembayaran','tanggal_generate','auto_lembur','auto_potongan','kunci_data'];
            break;
    }
    foreach($fields as $f) {
        $val = $_POST[$f] ?? '0';
        // Checkbox -> 1 atau 0
        if(in_array($f, ['auto_lembur','auto_potongan','kunci_data'])) {
            $val = isset($_POST[$f]) ? '1' : '0';
        }
        $pdo->prepare("INSERT INTO pengaturan (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?")
            ->execute([$f, $val, $val]);
    }
    $msg = 'Pengaturan berhasil disimpan!';
    // Reload
    $rows = $pdo->query("SELECT setting_key, setting_value FROM pengaturan")->fetchAll();
    foreach($rows as $r) { $settings[$r['setting_key']] = $r['setting_value']; }
}

$active_tab = $_GET['tab'] ?? 'perusahaan';

require_once 'layout/header.php';
?>
<div class="breadcrumb-wrap">
    <nav><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
        <li class="breadcrumb-item active">Pengaturan</li>
    </ol></nav>
</div>

<?php if($msg): ?>
<div class="alert alert-success d-flex align-items-center gap-2 mb-3" style="border-radius:10px;font-size:13px;">
    <i class="fas fa-check-circle"></i> <?= $msg ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header p-0">
        <ul class="nav nav-tabs" style="padding:0 16px;border-bottom:none;gap:4px;">
            <?php
            $tabs = [
                'perusahaan' => ['Perusahaan','fa-building'],
                'umum'       => ['Umum','fa-sliders'],
                'penggajian' => ['Penggajian','fa-money-bill-wave'],
                'absensi'    => ['Absensi','fa-calendar-check'],
                'email'      => ['Email','fa-envelope'],
                'backup'     => ['Backup Database','fa-database'],
            ];
            foreach($tabs as $key => $t): ?>
            <li class="nav-item">
                <a class="nav-link <?= $active_tab==$key?'active':'' ?>"
                   href="?tab=<?=$key?>"
                   style="font-size:13px;font-weight:600;padding:12px 16px;">
                    <i class="fas <?=$t[1]?> me-1"></i> <?=$t[0]?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="card-body p-4">
        <form method="POST">
            <input type="hidden" name="active_tab" value="<?= $active_tab ?>">

            <?php if($active_tab==='perusahaan'): ?>
            <!-- TAB PERUSAHAAN -->
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:12px;">Nama Perusahaan</label>
                        <input type="text" name="nama_perusahaan" class="form-control" value="<?= htmlspecialchars($settings['nama_perusahaan']??'') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:12px;">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="3"><?= htmlspecialchars($settings['alamat']??'') ?></textarea>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-600" style="font-size:12px;">Telepon</label>
                            <input type="text" name="telepon" class="form-control" value="<?= htmlspecialchars($settings['telepon']??'') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-600" style="font-size:12px;">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($settings['email']??'') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:12px;">NPWP</label>
                        <input type="text" name="npwp" class="form-control" value="<?= htmlspecialchars($settings['npwp']??'') ?>">
                    </div>
                </div>
                <div class="col-lg-4">
                    <label class="form-label fw-600" style="font-size:12px;">Logo Perusahaan</label>
                    <div style="border:2px dashed #e2e8f0;border-radius:12px;padding:24px;text-align:center;">
                        <div style="width:56px;height:56px;background:var(--primary);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;font-weight:800;margin:0 auto 12px;">M</div>
                        <button type="button" class="btn-outline-custom" style="font-size:12px;">
                            <i class="fas fa-folder-open"></i> Pilih File
                        </button>
                        <div style="font-size:11px;color:#94a3b8;margin-top:8px;">Format: JPG, PNG, Max: 2MB</div>
                    </div>
                </div>
            </div>

            <?php elseif($active_tab==='penggajian'): ?>
            <!-- TAB PENGGAJIAN -->
            <div class="row g-4">
                <div class="col-lg-8">
                    <h6 class="fw-700 mb-3" style="font-size:14px;">Pengaturan Penggajian</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-600" style="font-size:12px;">Gaji Minimum</label>
                            <div class="input-group">
                                <span class="input-group-text" style="font-size:12px;background:#f8fafc;">Rp</span>
                                <input type="number" name="gaji_minimum" class="form-control" value="<?= $settings['gaji_minimum']??2500000 ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-600" style="font-size:12px;">Pembulatan Gaji</label>
                            <select name="pembulatan_gaji" class="form-select">
                                <option value="atas" <?= ($settings['pembulatan_gaji']??'')==='atas'?'selected':'' ?>>Pembulatan ke atas</option>
                                <option value="bawah" <?= ($settings['pembulatan_gaji']??'')==='bawah'?'selected':'' ?>>Pembulatan ke bawah</option>
                                <option value="normal" <?= ($settings['pembulatan_gaji']??'')==='normal'?'selected':'' ?>>Pembulatan normal</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-600" style="font-size:12px;">Metode Pembayaran Default</label>
                            <select name="metode_pembayaran" class="form-select">
                                <option value="Transfer Bank" <?= ($settings['metode_pembayaran']??'')==='Transfer Bank'?'selected':'' ?>>Transfer Bank</option>
                                <option value="Tunai" <?= ($settings['metode_pembayaran']??'')==='Tunai'?'selected':'' ?>>Tunai</option>
                                <option value="Cek" <?= ($settings['metode_pembayaran']??'')==='Cek'?'selected':'' ?>>Cek</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-600" style="font-size:12px;">Tanggal Generate Gaji</label>
                        <select name="tanggal_generate" class="form-select" style="width:220px;">
                            <?php for($d=1;$d<=28;$d++): ?>
                            <option value="<?=$d?>" <?= ($settings['tanggal_generate']??'1')==$d?'selected':'' ?>>Tanggal <?=$d?> setiap bulan</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="auto_lembur" id="chkLembur" value="1" <?= ($settings['auto_lembur']??'0')==='1'?'checked':'' ?>>
                            <label class="form-check-label" for="chkLembur" style="font-size:13px;">Otomatis hitung lembur</label>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="auto_potongan" id="chkPot" value="1" <?= ($settings['auto_potongan']??'0')==='1'?'checked':'' ?>>
                            <label class="form-check-label" for="chkPot" style="font-size:13px;">Otomatis hitung potongan</label>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="kunci_data" id="chkKunci" value="1" <?= ($settings['kunci_data']??'0')==='1'?'checked':'' ?>>
                            <label class="form-check-label" for="chkKunci" style="font-size:13px;">Kunci data setelah gaji dibayar</label>
                        </div>
                    </div>
                </div>
            </div>

            <?php elseif($active_tab==='umum'): ?>
            <div class="text-muted text-center py-4">
                <i class="fas fa-sliders fa-2x mb-3 d-block" style="color:#cbd5e1;"></i>
                Pengaturan umum akan ditambahkan di sini.
            </div>

            <?php elseif($active_tab==='absensi'): ?>
            <div class="text-muted text-center py-4">
                <i class="fas fa-calendar-check fa-2x mb-3 d-block" style="color:#cbd5e1;"></i>
                Pengaturan absensi akan ditambahkan di sini.
            </div>

            <?php elseif($active_tab==='email'): ?>
            <div class="text-muted text-center py-4">
                <i class="fas fa-envelope fa-2x mb-3 d-block" style="color:#cbd5e1;"></i>
                Pengaturan email akan ditambahkan di sini.
            </div>

            <?php elseif($active_tab==='backup'): ?>
            <div class="text-center py-4">
                <i class="fas fa-database fa-2x mb-3 d-block" style="color:#6366f1;"></i>
                <p style="font-size:13px;color:#64748b;">Backup database Anda untuk keamanan data.</p>
                <button type="button" class="btn-primary-custom" onclick="alert('Fitur backup akan segera tersedia.')">
                    <i class="fas fa-download"></i> Download Backup
                </button>
            </div>
            <?php endif; ?>

            <?php if(in_array($active_tab, ['perusahaan','penggajian'])): ?>
            <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                <button type="submit" class="btn-primary-custom">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php require_once 'layout/footer.php'; ?>
