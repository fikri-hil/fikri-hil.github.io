<?php
/**
 * export_pdf.php - Download Slip Gaji as PDF (via browser print-to-PDF)
 * Uses a clean print-optimized HTML page with window.onload auto-print
 * Param: id = data_gaji.id
 */
require_once 'config/database.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: data_gaji.php'); exit; }

$stmt = $pdo->prepare("SELECT dg.*, p.nama, p.nip, p.email, p.telepon, p.alamat,
    j.nama_jabatan, pg.nama_periode, pg.bulan, pg.tahun
    FROM data_gaji dg
    LEFT JOIN pegawai p       ON dg.pegawai_id = p.id
    LEFT JOIN jabatan j       ON p.jabatan_id  = j.id
    LEFT JOIN periode_gaji pg ON dg.periode_id = pg.id
    WHERE dg.id = ?");
$stmt->execute([$id]);
$r = $stmt->fetch();
if (!$r) { header('Location: data_gaji.php'); exit; }

$no_slip    = '#SG-' . str_pad($r['id'], 5, '0', STR_PAD_LEFT);
$gp         = $r['gaji_pokok'];
$tj         = $r['total_tunjangan'];
$lembur     = $r['total_lembur'] ?? 0;
$total_pend = $gp + $tj + $lembur;
$pt_bpjsk   = $gp * 0.01;
$pt_bpjstk  = $gp * 0.02;
$pt_pph     = $gp * 0.05;
$total_pot  = $r['total_potongan'];
$gaji_bersih = $r['total_gaji'];

function rp2($v) { return 'Rp' . number_format($v, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Slip Gaji - <?= htmlspecialchars($r['nama']) ?> - <?= htmlspecialchars($r['nama_periode']) ?></title>
<style>
  @page { size: A4; margin: 20mm 18mm; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Arial', sans-serif; font-size: 13px; color: #1e293b; background: #fff; }

  .slip-wrapper { max-width: 720px; margin: 0 auto; padding: 32px; border: 2px solid #e2e8f0; border-radius: 12px; }

  /* Header */
  .slip-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 2px solid #6366f1; }
  .company-name { font-size: 20px; font-weight: 800; color: #0f172a; }
  .company-sub  { font-size: 11px; color: #64748b; margin-top: 3px; }
  .slip-no      { text-align: right; }
  .slip-no .label { font-size: 11px; color: #94a3b8; }
  .slip-no .value { font-size: 14px; font-weight: 700; color: #6366f1; }

  /* Badge status */
  .badge { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; }
  .badge-dibayar { background: #dcfce7; color: #16a34a; }
  .badge-proses  { background: #fef9c3; color: #ca8a04; }

  /* Employee Info */
  .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 24px; margin: 20px 0; padding: 16px; background: #f8fafc; border-radius: 8px; }
  .info-item .lbl { font-size: 10px; color: #94a3b8; margin-bottom: 2px; text-transform: uppercase; letter-spacing: .5px; }
  .info-item .val { font-weight: 700; font-size: 14px; }

  /* Salary table */
  .salary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin: 24px 0; }
  .section-title { font-weight: 700; font-size: 13px; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
  .income .section-title { color: #16a34a; }
  .deduct .section-title { color: #ef4444; }
  .line-item { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 12px; }
  .line-item .label { color: #475569; }
  .line-item .amount { font-weight: 600; }
  .deduct .line-item .amount { color: #ef4444; }
  .line-total { display: flex; justify-content: space-between; padding: 10px 0 0; font-weight: 700; font-size: 13px; }
  .income .line-total .amount { color: #16a34a; }
  .deduct .line-total .amount { color: #ef4444; }

  /* Net pay */
  .net-pay { background: linear-gradient(135deg, #f0fdf4, #dcfce7); border-radius: 10px; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; margin-top: 20px; }
  .net-pay .lbl { font-size: 12px; color: #16a34a; font-weight: 700; }
  .net-pay .sub { font-size: 11px; color: #64748b; margin-top: 2px; }
  .net-pay .amount { font-size: 26px; font-weight: 800; color: #16a34a; }

  /* Signature */
  .sign-area { display: flex; justify-content: space-between; margin-top: 36px; padding-top: 20px; border-top: 1px solid #e2e8f0; }
  .sign-box { text-align: center; width: 180px; }
  .sign-box .sign-title { font-size: 11px; color: #64748b; margin-bottom: 56px; }
  .sign-box .sign-line { border-top: 1px solid #0f172a; padding-top: 4px; font-size: 11px; font-weight: 600; }

  /* Footer note */
  .slip-footer { margin-top: 20px; font-size: 10px; color: #94a3b8; text-align: center; border-top: 1px dashed #e2e8f0; padding-top: 10px; }

  /* Print controls */
  .no-print { position: fixed; bottom: 24px; right: 24px; display: flex; gap: 10px; z-index: 999; }
  .btn-pdf  { background: #22c55e; color: #fff; border: none; padding: 12px 20px; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(34,197,94,.3); }
  .btn-back { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; padding: 12px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; }

  @media print {
    .no-print { display: none !important; }
    body { margin: 0; }
    .slip-wrapper { border: none; padding: 0; max-width: 100%; }
  }
</style>
</head>
<body>

<!-- Floating action buttons (hidden on print) -->
<div class="no-print">
  <a href="detail_gaji.php?id=<?= $id ?>" class="btn-back">← Kembali</a>
  <button class="btn-pdf" onclick="window.print()">🖨️ Simpan / Cetak PDF</button>
</div>

<div class="slip-wrapper">

  <!-- Header -->
  <div class="slip-header">
    <div>
      <div class="company-name">PT. Maju Sejahtera</div>
      <div class="company-sub">Slip Gaji Periode: <strong><?= htmlspecialchars($r['nama_periode']) ?></strong></div>
      <div style="margin-top:8px;">
        <span class="badge badge-<?= strtolower($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span>
      </div>
    </div>
    <div class="slip-no">
      <div class="label">No. Slip</div>
      <div class="value"><?= $no_slip ?></div>
    </div>
  </div>

  <!-- Employee Info -->
  <div class="info-grid">
    <div class="info-item">
      <div class="lbl">Nama Pegawai</div>
      <div class="val"><?= htmlspecialchars($r['nama']) ?></div>
    </div>
    <div class="info-item">
      <div class="lbl">NIP</div>
      <div class="val"><?= htmlspecialchars($r['nip']) ?></div>
    </div>
    <div class="info-item">
      <div class="lbl">Jabatan</div>
      <div class="val"><?= htmlspecialchars($r['nama_jabatan']) ?></div>
    </div>
    <div class="info-item">
      <div class="lbl">Periode</div>
      <div class="val"><?= htmlspecialchars($r['bulan']) ?> <?= htmlspecialchars($r['tahun']) ?></div>
    </div>
  </div>

  <!-- Salary Breakdown -->
  <div class="salary-grid">
    <!-- Pendapatan -->
    <div class="income">
      <div class="section-title">
        <span style="display:inline-block;width:14px;height:14px;background:#dcfce7;border-radius:50%;text-align:center;line-height:14px;font-size:10px;color:#16a34a;">+</span>
        Pendapatan
      </div>
      <div class="line-item"><span class="label">Gaji Pokok</span><span class="amount"><?= rp2($gp) ?></span></div>
      <div class="line-item"><span class="label">Tunjangan</span><span class="amount"><?= rp2($tj) ?></span></div>
      <div class="line-item"><span class="label">Lembur</span><span class="amount"><?= rp2($lembur) ?></span></div>
      <div class="line-total"><span>Total Pendapatan</span><span class="amount"><?= rp2($total_pend) ?></span></div>
    </div>
    <!-- Potongan -->
    <div class="deduct">
      <div class="section-title">
        <span style="display:inline-block;width:14px;height:14px;background:#fee2e2;border-radius:50%;text-align:center;line-height:14px;font-size:10px;color:#ef4444;">−</span>
        Potongan
      </div>
      <div class="line-item"><span class="label">BPJS Kesehatan (1%)</span><span class="amount"><?= rp2($pt_bpjsk) ?></span></div>
      <div class="line-item"><span class="label">BPJS Ketenagakerjaan (2%)</span><span class="amount"><?= rp2($pt_bpjstk) ?></span></div>
      <div class="line-item"><span class="label">PPh 21 (5%)</span><span class="amount"><?= rp2($pt_pph) ?></span></div>
      <div class="line-total"><span>Total Potongan</span><span class="amount"><?= rp2($total_pot) ?></span></div>
    </div>
  </div>

  <!-- Net Pay -->
  <div class="net-pay">
    <div>
      <div class="lbl">GAJI BERSIH DITERIMA</div>
      <div class="sub"><?= htmlspecialchars($r['bulan']) ?> <?= htmlspecialchars($r['tahun']) ?></div>
    </div>
    <div class="amount"><?= rp2($gaji_bersih) ?></div>
  </div>

  <!-- Signature Area -->
  <div class="sign-area">
    <div class="sign-box">
      <div class="sign-title">Pegawai</div>
      <div class="sign-line">(<?= htmlspecialchars($r['nama']) ?>)</div>
    </div>
    <div class="sign-box">
      <div class="sign-title">HRD / Penggajian</div>
      <div class="sign-line">(__________________)</div>
    </div>
    <div class="sign-box">
      <div class="sign-title">Direktur</div>
      <div class="sign-line">(__________________)</div>
    </div>
  </div>

  <div class="slip-footer">
    Dokumen ini dicetak secara sistem — PT. Maju Sejahtera &bull; <?= htmlspecialchars($r['nama_periode']) ?> &bull; <?= $no_slip ?>
  </div>

</div>

<script>
// Auto-trigger print dialog saat dibuka via tombol Download PDF
if (window.location.search.includes('autoprint=1')) {
    window.onload = function() { setTimeout(function(){ window.print(); }, 400); };
}
</script>
</body>
</html>
