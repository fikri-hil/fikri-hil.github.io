<?php
// ── Cek session login ──────────────────────────────────────────────────────
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ── Handle period selector redirect BEFORE any output ─────────────────────
if (isset($_GET['set_bulan'])) {
    $sb = intval($_GET['set_bulan']);
    if ($sb >= 1 && $sb <= 5) $_SESSION['periode_bulan'] = $sb;
    header('Location: dashboard.php');
    exit;
}
// ──────────────────────────────────────────────────────────────────────────

$page_title = 'Dashboard';
require_once 'config/database.php';

// ── Helper: konversi nama bulan Indonesia → angka ──────────────────────────
function namaBulanKeAngka($nama) {
    $map = [
        'Januari'=>1,'Februari'=>2,'Maret'=>3,'April'=>4,
        'Mei'=>5,'Juni'=>6,'Juli'=>7,'Agustus'=>8,
        'September'=>9,'Oktober'=>10,'November'=>11,'Desember'=>12
    ];
    return $map[$nama] ?? 0;
}

// ── Ambil semua periode dari DB, urutkan kronologis ───────────────────────
$semua_periode = $pdo->query("SELECT * FROM periode_gaji ORDER BY tahun ASC, FIELD(bulan,'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember') ASC")->fetchAll();

// Buat mapping: nomor urut (1-based) → periode row
$periode_list = array_values($semua_periode); // 0-indexed
$total_periode = count($periode_list);

// ── Filter periode aktif dari session ─────────────────────────────────────
// session periode_bulan = nomor urut dari topbar (1 = paling lama, N = terbaru)
// Default ke periode terbaru
$sel_idx = intval($_SESSION['periode_bulan'] ?? $total_periode) - 1;
if ($sel_idx < 0) $sel_idx = 0;
if ($sel_idx >= $total_periode) $sel_idx = max(0, $total_periode - 1);

if ($total_periode > 0) {
    $sel_periode     = $periode_list[$sel_idx];
    $sel_periode_id  = $sel_periode['id'];
    $bulan_label     = $sel_periode['bulan'] . ' ' . $sel_periode['tahun'];
} else {
    $sel_periode_id  = 0;
    $bulan_label     = 'Belum Ada Periode';
}

// ── Stats ──────────────────────────────────────────────────────────────────
$total_pegawai = $pdo->query("SELECT COUNT(*) FROM pegawai")->fetchColumn();
$aktif_pegawai = $pdo->query("SELECT COUNT(*) FROM pegawai WHERE status='Aktif'")->fetchColumn();

// Total gaji berdasarkan periode_id (bukan created_at)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_gaji),0) FROM data_gaji WHERE periode_id=?");
$stmt->execute([$sel_periode_id]);
$total_gaji = $stmt->fetchColumn();

$today = date('Y-m-d');
$hadir     = $pdo->query("SELECT COUNT(*) FROM absensi WHERE tanggal='$today' AND status='Hadir'")->fetchColumn();
$terlambat = $pdo->query("SELECT COUNT(*) FROM absensi WHERE tanggal='$today' AND status='Terlambat'")->fetchColumn();

$total_hadir   = $hadir + $terlambat;
$pct_hadir     = $aktif_pegawai > 0 ? round($total_hadir/$aktif_pegawai*100, 2) : 0;
$pct_terlambat = $aktif_pegawai > 0 ? round($terlambat/$aktif_pegawai*100, 2) : 0;

// ── Pegawai terbaru ────────────────────────────────────────────────────────
$pegawai_baru = $pdo->query("SELECT p.*, j.nama_jabatan FROM pegawai p LEFT JOIN jabatan j ON p.jabatan_id=j.id ORDER BY p.tanggal_bergabung DESC LIMIT 4")->fetchAll();

// ── Absensi minggu ini ─────────────────────────────────────────────────────
$absensi_minggu = $pdo->query("SELECT tanggal,
    SUM(status IN ('Hadir','Terlambat')) as hadir,
    SUM(status NOT IN ('Hadir','Terlambat')) as tidak_hadir
    FROM absensi
    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY tanggal ORDER BY tanggal")->fetchAll();

// ── Komposisi Gaji berdasarkan periode_id ─────────────────────────────────
$ks = $pdo->prepare("SELECT
    COALESCE(SUM(gaji_pokok),0)      as gaji_pokok,
    COALESCE(SUM(total_tunjangan),0) as tunjangan,
    COALESCE(SUM(total_potongan),0)  as potongan,
    COALESCE(SUM(total_lembur),0)    as lembur
    FROM data_gaji WHERE periode_id=?");
$ks->execute([$sel_periode_id]);
$komposisi  = $ks->fetch();
$kGajiPokok = floatval($komposisi['gaji_pokok'] ?? 0);
$kTunjangan = floatval($komposisi['tunjangan']  ?? 0);
$kPotongan  = floatval($komposisi['potongan']   ?? 0);
$kLembur    = floatval($komposisi['lembur']     ?? 0);
$kTotal = $kGajiPokok + $kTunjangan + $kPotongan + $kLembur;
function pctGaji($v, $total) { return $total > 0 ? round($v/$total*100,1) : 0; }

// ── Grafik Gaji: ambil semua periode, join total gaji per periode_id ───────
// Ambil 12 periode terakhir (urut kronologis) lalu slice di JS
$grafik_raw = $pdo->query("
    SELECT
        pg.id,
        CONCAT(pg.bulan, ' ', pg.tahun) AS label,
        pg.bulan,
        pg.tahun,
        COALESCE(SUM(dg.total_gaji), 0) AS total
    FROM periode_gaji pg
    LEFT JOIN data_gaji dg ON dg.periode_id = pg.id
    GROUP BY pg.id, pg.bulan, pg.tahun
    ORDER BY pg.tahun ASC,
             FIELD(pg.bulan,'Januari','Februari','Maret','April','Mei','Juni',
                            'Juli','Agustus','September','Oktober','November','Desember') ASC
")->fetchAll();

$gajiLabels = json_encode(array_column($grafik_raw, 'label'));
$gajiData   = json_encode(array_map('floatval', array_column($grafik_raw, 'total')));

// ── Absensi chart labels ───────────────────────────────────────────────────
$absensiLabels = []; $absensiHadir = []; $absensiTidak = [];
$hariInd = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
foreach ($absensi_minggu as $row) {
    $absensiLabels[] = $hariInd[date('w',strtotime($row['tanggal']))].' '.date('d/m',strtotime($row['tanggal']));
    $absensiHadir[]  = intval($row['hadir']);
    $absensiTidak[]  = intval($row['tidak_hadir']);
}
if (empty($absensiLabels)) {
    for ($i = 6; $i >= 0; $i--) {
        $absensiLabels[] = $hariInd[date('w',strtotime("-$i days"))].' '.date('d/m',strtotime("-$i days"));
        $absensiHadir[]  = 0;
        $absensiTidak[]  = 0;
    }
}

require_once 'layout/header.php';
?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex align-items-start gap-3 mb-3">
                <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                <div>
                    <div class="stat-label">Total Pegawai</div>
                    <div class="stat-value"><?= $total_pegawai ?></div>
                    <div class="stat-sub">Aktif &nbsp;<span class="text-success fw-600"><?= $aktif_pegawai ?> Orang <i class="fas fa-arrow-up fa-xs"></i></span></div>
                </div>
            </div>
            <div class="mini-chart">
                <svg viewBox="0 0 120 40" preserveAspectRatio="none" style="width:100%;height:40px;">
                    <polyline points="0,30 20,25 40,28 60,20 80,22 100,15 120,12" fill="none" stroke="#3b82f6" stroke-width="2"/>
                    <polygon points="0,30 20,25 40,28 60,20 80,22 100,15 120,12 120,40 0,40" fill="rgba(59,130,246,.08)"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex align-items-start gap-3 mb-3">
                <div class="stat-icon green"><i class="fas fa-dollar-sign"></i></div>
                <div>
                    <div class="stat-label">Total Gaji <?= $bulan_label ?></div>
                    <div class="stat-value" style="font-size:17px;">Rp<?= number_format($total_gaji,0,',','.') ?></div>
                    <div class="stat-sub">Dari <?= $aktif_pegawai ?> Pegawai <i class="fas fa-arrow-up fa-xs text-success"></i></div>
                </div>
            </div>
            <div class="mini-chart">
                <svg viewBox="0 0 120 40" preserveAspectRatio="none" style="width:100%;height:40px;">
                    <polyline points="0,35 20,28 40,25 60,30 80,20 100,15 120,10" fill="none" stroke="#22c55e" stroke-width="2"/>
                    <polygon points="0,35 20,28 40,25 60,30 80,20 100,15 120,10 120,40 0,40" fill="rgba(34,197,94,.08)"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex align-items-start gap-3 mb-3">
                <div class="stat-icon orange"><i class="fas fa-calendar-check"></i></div>
                <div>
                    <div class="stat-label">Absensi Hari Ini</div>
                    <div class="stat-value"><?= $total_hadir ?></div>
                    <div class="stat-sub">Hadir &nbsp;<span class="text-success fw-600"><?= $pct_hadir ?>% <i class="fas fa-arrow-up fa-xs"></i></span></div>
                </div>
            </div>
            <div class="mini-chart">
                <svg viewBox="0 0 120 40" preserveAspectRatio="none" style="width:100%;height:40px;">
                    <polyline points="0,20 20,18 40,22 60,15 80,18 100,14 120,16" fill="none" stroke="#f59e0b" stroke-width="2"/>
                    <polygon points="0,20 20,18 40,22 60,15 80,18 100,14 120,16 120,40 0,40" fill="rgba(245,158,11,.08)"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex align-items-start gap-3 mb-3">
                <div class="stat-icon red"><i class="fas fa-exclamation-triangle"></i></div>
                <div>
                    <div class="stat-label">Terlambat Hari Ini</div>
                    <div class="stat-value"><?= $terlambat ?></div>
                    <div class="stat-sub">Telat &nbsp;<span class="text-danger fw-600"><?= $pct_terlambat ?>% <i class="fas fa-arrow-up fa-xs"></i></span></div>
                </div>
            </div>
            <div class="mini-chart">
                <svg viewBox="0 0 120 40" preserveAspectRatio="none" style="width:100%;height:40px;">
                    <polyline points="0,30 20,25 40,28 60,32 80,26 100,30 120,25" fill="none" stroke="#ef4444" stroke-width="2"/>
                    <polygon points="0,30 20,25 40,28 60,32 80,26 100,30 120,25 120,40 0,40" fill="rgba(239,68,68,.08)"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row: Grafik Gaji + Komposisi sejajar -->
<div class="row g-3 mb-4 align-items-stretch">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <span class="fw-700" style="font-size:15px;">Grafik Gaji</span>
                    <span class="text-muted ms-1" style="font-size:13px;" id="grafikLabel">(6 Bulan Terakhir)</span>
                </div>
                <select id="grafikRange" class="form-select form-select-sm" style="width:150px;">
                    <option value="6">6 Bulan Terakhir</option>
                    <option value="3">3 Bulan Terakhir</option>
                    <option value="12">1 Tahun Terakhir</option>
                </select>
            </div>
            <div class="card-body p-3 d-flex align-items-center">
                <canvas id="grafikGaji" style="width:100%;height:100%;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">
                <span class="fw-700" style="font-size:15px;">Komposisi Gaji — <?= $bulan_label ?></span>
            </div>
            <div class="card-body p-3 d-flex flex-column justify-content-center">
                <?php if ($kTotal <= 0): ?>
                <div class="text-center py-4" style="color:#94a3b8;">
                    <i class="fas fa-chart-pie fa-2x mb-2" style="opacity:.35;"></i>
                    <p style="font-size:13px;margin:0;">Data belum ditambahkan</p>
                </div>
                <?php else: ?>
                <!-- Donut chart -->
                <div style="width:200px;height:200px;margin:0 auto 16px;position:relative;">
                    <canvas id="komposisiGaji" width="200" height="200"></canvas>
                </div>
                <!-- Legend dinamis dari DB -->
                <?php
                $legend = [
                    ['Gaji Pokok','#3b82f6',$kGajiPokok],
                    ['Tunjangan', '#22c55e',$kTunjangan],
                    ['Potongan',  '#ef4444',$kPotongan],
                    ['Lembur',    '#f59e0b',$kLembur],
                ];
                foreach($legend as $item):
                    $lbl = $item[0]; $clr = $item[1]; $val = $item[2];
                ?>
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:9px;height:9px;border-radius:50%;background:<?=$clr?>;flex-shrink:0;"></div>
                        <span style="font-size:12px;"><?=$lbl?></span>
                    </div>
                    <div class="d-flex gap-2">
                        <span style="font-size:12px;font-weight:600;">Rp<?=number_format($val,0,',','.')?></span>
                        <span style="font-size:11px;color:#94a3b8;width:36px;text-align:right;"><?=pctGaji($val,$kTotal)?>%</span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Row -->
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-700" style="font-size:15px;">Absensi Minggu Ini</span>
                <div class="d-flex gap-3" style="font-size:12px;">
                    <span><i class="fas fa-circle text-success fa-xs"></i> Hadir</span>
                    <span><i class="fas fa-circle text-danger fa-xs"></i> Tidak Hadir</span>
                </div>
            </div>
            <div class="row g-0">
                <div class="col-9">
                    <div class="p-3"><canvas id="absensiChart" height="120"></canvas></div>
                </div>
                <div class="col-3 d-flex align-items-center justify-content-center p-3">
                    <div class="text-center">
                        <div style="background:#f0fdf4;border-radius:12px;padding:12px 16px;margin-bottom:10px;">
                            <div style="font-size:26px;font-weight:800;color:#16a34a;"><?=$total_hadir?></div>
                            <div style="font-size:11px;color:#16a34a;font-weight:600;">Total Hadir</div>
                            <div style="font-size:11px;color:#16a34a;"><?=$pct_hadir?>%</div>
                        </div>
                        <?php $tidak=$aktif_pegawai-$total_hadir; $pct_tidak=$aktif_pegawai>0?round($tidak/$aktif_pegawai*100,2):0; ?>
                        <div style="background:#fef2f2;border-radius:12px;padding:12px 16px;">
                            <div style="font-size:26px;font-weight:800;color:#dc2626;"><?=$tidak?></div>
                            <div style="font-size:11px;color:#dc2626;font-weight:600;">Tidak Hadir</div>
                            <div style="font-size:11px;color:#dc2626;"><?=$pct_tidak?>%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-700" style="font-size:15px;">Pegawai Terbaru</span>
                <a href="pegawai.php" class="btn-primary-custom" style="font-size:12px;padding:5px 12px;">Lihat Semua</a>
            </div>
            <div class="card-body">
                <?php if (empty($pegawai_baru)): ?>
                <div class="text-center py-4" style="color:#94a3b8;">
                    <i class="fas fa-users fa-2x mb-2" style="opacity:.35;"></i>
                    <p style="font-size:13px;margin:0;">Data belum ditambahkan</p>
                </div>
                <?php else: ?>
                <?php foreach($pegawai_baru as $p): ?>
                <div class="d-flex align-items-center gap-3 p-3 border-bottom">
                    <div class="avatar-circle"><?= strtoupper(substr($p['nama'],0,2)) ?></div>
                    <div class="flex-grow-1">
                        <div style="font-weight:600;font-size:13px;"><?= $p['nama'] ?></div>
                        <div style="font-size:11px;color:#64748b;"><?= $p['nama_jabatan'] ?></div>
                    </div>
                    <div class="text-end">
                        <span class="badge-status badge-aktif">Aktif</span>
                        <div style="font-size:11px;color:#94a3b8;margin-top:2px;"><?= date('d M Y',strtotime($p['tanggal_bergabung'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Data semua periode dari PHP (sudah disiapkan di atas sebagai $grafik_raw) ──
var gajiAllData = <?= json_encode(['labels' => array_column($grafik_raw,'label'), 'data' => array_map('floatval', array_column($grafik_raw,'total'))]) ?>;

// ── Grafik Gaji ────────────────────────────────────────────────────────────
var grafikGajiChart = new Chart(document.getElementById('grafikGaji'), {
    type: 'line',
    data: {
        labels: gajiAllData.labels.slice(-6),
        datasets: [{
            label: 'Total Gaji',
            data: gajiAllData.data.slice(-6),
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,.08)',
            fill: true, tension: 0.4,
            pointBackgroundColor: '#6366f1',
            pointRadius: 5, pointHoverRadius: 7,
            borderWidth: 2.5
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { ticks: { callback: function(v){ return 'Rp'+(v/1000000)+'jt'; }, font:{size:11} }, grid:{color:'#f1f5f9'} },
            x: { ticks: { font:{size:11} }, grid:{display:false} }
        }
    }
});

// Dropdown grafik gaji fungsional
document.getElementById('grafikRange').addEventListener('change', function() {
    var n = parseInt(this.value);
    var labels = { 3:'(3 Bulan Terakhir)', 6:'(6 Bulan Terakhir)', 12:'(1 Tahun Terakhir)' };
    document.getElementById('grafikLabel').textContent = labels[n] || '';
    grafikGajiChart.data.labels   = gajiAllData.labels.slice(-n);
    grafikGajiChart.data.datasets[0].data = gajiAllData.data.slice(-n);
    grafikGajiChart.update();
});

// ── Komposisi Gaji ─────────────────────────────────────────────────────────
if (document.getElementById('komposisiGaji')) {
new Chart(document.getElementById('komposisiGaji'), {
    type: 'doughnut',
    data: {
        labels: ['Gaji Pokok','Tunjangan','Potongan','Lembur'],
        datasets: [{
            data: [<?=$kGajiPokok?>,<?=$kTunjangan?>,<?=$kPotongan?>,<?=$kLembur?>],
            backgroundColor: ['#3b82f6','#22c55e','#ef4444','#f59e0b'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: false,
        maintainAspectRatio: true,
        cutout: '65%',
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: function(c){ return c.label+': Rp'+c.raw.toLocaleString('id-ID'); } } }
        }
    }
});
}

// ── Absensi Minggu Ini ─────────────────────────────────────────────────────
new Chart(document.getElementById('absensiChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($absensiLabels) ?>,
        datasets: [
            { label:'Hadir',       data:<?=json_encode($absensiHadir)?>, borderColor:'#22c55e', tension:.4, pointRadius:4, fill:false, borderWidth:2.5 },
            { label:'Tidak Hadir', data:<?=json_encode($absensiTidak)?>, borderColor:'#ef4444', tension:.4, pointRadius:4, fill:false, borderWidth:2.5 }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero:true, ticks:{font:{size:11}}, grid:{color:'#f1f5f9'} },
            x: { ticks:{font:{size:11}}, grid:{display:false} }
        }
    }
});
</script>

<?php require_once 'layout/footer.php'; ?>
