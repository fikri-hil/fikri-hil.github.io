<?php
/**
 * export_excel.php - Universal Excel Export Handler
 * Params: type = absensi | data_gaji | laporan_gaji | laporan_absensi
 */
require_once 'config/database.php';

$type = $_GET['type'] ?? '';

// ── helper: safe string ─────────────────────────────────────
function esc($v) { return htmlspecialchars_decode(strip_tags($v ?? '')); }
function rp($v)  { return number_format($v, 0, ',', '.'); }

switch ($type) {

    // ================================================================
    // ABSENSI
    // ================================================================
    case 'absensi':
        $date_from     = $_GET['from']    ?? date('Y-m-01');
        $date_to       = $_GET['to']      ?? date('Y-m-t');
        $pegawai_filter = $_GET['pegawai'] ?? '';
        $status_filter  = $_GET['status']  ?? '';

        $where  = "WHERE a.tanggal BETWEEN ? AND ?";
        $params = [$date_from, $date_to];
        if ($pegawai_filter) { $where .= " AND a.pegawai_id=?"; $params[] = $pegawai_filter; }
        if ($status_filter)  { $where .= " AND a.status=?";     $params[] = $status_filter; }

        $stmt = $pdo->prepare("SELECT a.*, p.nama, p.nip, j.nama_jabatan
            FROM absensi a
            LEFT JOIN pegawai p ON a.pegawai_id = p.id
            LEFT JOIN jabatan j ON p.jabatan_id = j.id
            $where ORDER BY a.tanggal DESC, a.id");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $filename = 'Laporan_Absensi_' . $date_from . '_sd_' . $date_to . '.xls';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office"
                    xmlns:x="urn:schemas-microsoft-com:office:excel"
                    xmlns="http://www.w3.org/TR/REC-html40">
        <head><meta charset="UTF-8">
        <style>
            th { background:#6366f1; color:#fff; font-weight:bold; text-align:center; border:1px solid #ccc; }
            td { border:1px solid #ccc; }
            .hadir     { color:#16a34a; }
            .terlambat { color:#f59e0b; }
            .sakit     { color:#a855f7; }
            .izin      { color:#3b82f6; }
            .alpha     { color:#ef4444; }
        </style></head><body>';
        echo '<h2 style="font-family:Arial;">Laporan Absensi Pegawai</h2>';
        echo '<p style="font-family:Arial;">Periode: ' . $date_from . ' s/d ' . $date_to . '</p>';
        echo '<table border="1" cellpadding="4" style="font-family:Arial;font-size:12px;border-collapse:collapse;">';
        echo '<tr>
            <th>No</th><th>Tanggal</th><th>NIP</th><th>Nama Pegawai</th>
            <th>Jabatan</th><th>Check In</th><th>Check Out</th>
            <th>Status</th><th>Keterangan</th>
        </tr>';
        foreach ($rows as $i => $r) {
            $cls = strtolower($r['status']);
            echo '<tr>
                <td align="center">' . ($i+1) . '</td>
                <td>' . date('d/m/Y', strtotime($r['tanggal'])) . '</td>
                <td>' . esc($r['nip']) . '</td>
                <td>' . esc($r['nama']) . '</td>
                <td>' . esc($r['nama_jabatan']) . '</td>
                <td align="center">' . ($r['check_in'] ?? '-') . '</td>
                <td align="center">' . ($r['check_out'] ?? '-') . '</td>
                <td align="center" class="' . $cls . '">' . esc($r['status']) . '</td>
                <td>' . esc($r['keterangan']) . '</td>
            </tr>';
        }
        echo '</table></body></html>';
        break;

    // ================================================================
    // DATA GAJI
    // ================================================================
    case 'data_gaji':
        $periode_id = intval($_GET['periode'] ?? 1);
        $stmt = $pdo->prepare("SELECT dg.*, p.nama, p.nip, j.nama_jabatan, pg.nama_periode
            FROM data_gaji dg
            LEFT JOIN pegawai p  ON dg.pegawai_id = p.id
            LEFT JOIN jabatan j  ON p.jabatan_id  = j.id
            LEFT JOIN periode_gaji pg ON dg.periode_id = pg.id
            WHERE dg.periode_id = ?
            ORDER BY j.nama_jabatan, p.nama");
        $stmt->execute([$periode_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $periode_nama = $rows[0]['nama_periode'] ?? 'Periode-' . $periode_id;

        $filename = 'Data_Gaji_' . str_replace(' ', '_', $periode_nama) . '.xls';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office"
                    xmlns:x="urn:schemas-microsoft-com:office:excel"
                    xmlns="http://www.w3.org/TR/REC-html40">
        <head><meta charset="UTF-8">
        <style>
            th { background:#6366f1; color:#fff; font-weight:bold; text-align:center; border:1px solid #ccc; }
            td { border:1px solid #ccc; }
            .dibayar { color:#16a34a; } .proses { color:#f59e0b; }
        </style></head><body>';
        echo '<h2 style="font-family:Arial;">Data Gaji Pegawai — ' . esc($periode_nama) . '</h2>';
        echo '<table border="1" cellpadding="4" style="font-family:Arial;font-size:12px;border-collapse:collapse;">';
        echo '<tr>
            <th>No</th><th>NIP</th><th>Nama Pegawai</th><th>Jabatan</th>
            <th>Gaji Pokok</th><th>Tunjangan</th><th>Potongan</th>
            <th>Total Gaji</th><th>Status</th>
        </tr>';
        $tot_gp=$tot_tj=$tot_pt=$tot_tg=0;
        foreach ($rows as $i => $r) {
            $tot_gp+=$r['gaji_pokok']; $tot_tj+=$r['total_tunjangan'];
            $tot_pt+=$r['total_potongan']; $tot_tg+=$r['total_gaji'];
            $cls = strtolower($r['status']);
            echo '<tr>
                <td align="center">' . ($i+1) . '</td>
                <td>' . esc($r['nip']) . '</td>
                <td>' . esc($r['nama']) . '</td>
                <td>' . esc($r['nama_jabatan']) . '</td>
                <td align="right">Rp' . rp($r['gaji_pokok']) . '</td>
                <td align="right">Rp' . rp($r['total_tunjangan']) . '</td>
                <td align="right" style="color:#ef4444;">Rp' . rp($r['total_potongan']) . '</td>
                <td align="right" style="font-weight:bold;color:#16a34a;">Rp' . rp($r['total_gaji']) . '</td>
                <td align="center" class="' . $cls . '">' . esc($r['status']) . '</td>
            </tr>';
        }
        echo '<tr style="background:#f1f5f9;font-weight:bold;">
            <td colspan="4" align="right">TOTAL</td>
            <td align="right">Rp' . rp($tot_gp) . '</td>
            <td align="right">Rp' . rp($tot_tj) . '</td>
            <td align="right" style="color:#ef4444;">Rp' . rp($tot_pt) . '</td>
            <td align="right" style="color:#16a34a;">Rp' . rp($tot_tg) . '</td>
            <td></td>
        </tr>';
        echo '</table></body></html>';
        break;

    // ================================================================
    // LAPORAN GAJI
    // ================================================================
    case 'laporan_gaji':
        $periode_filter = intval($_GET['periode'] ?? 1);
        $jabatan_filter = $_GET['jabatan'] ?? '';
        $status_filter  = $_GET['status']  ?? '';

        $where  = "WHERE 1=1";
        $params = [];
        if ($periode_filter) { $where .= " AND dg.periode_id=?"; $params[] = $periode_filter; }
        if ($jabatan_filter)  { $where .= " AND p.jabatan_id=?";  $params[] = $jabatan_filter; }
        if ($status_filter)   { $where .= " AND dg.status=?";     $params[] = $status_filter; }

        $stmt = $pdo->prepare("SELECT dg.*, p.nama, p.nip, j.nama_jabatan, p.status_pernikahan, p.jumlah_anak, pg.nama_periode
            FROM data_gaji dg
            LEFT JOIN pegawai p     ON dg.pegawai_id = p.id
            LEFT JOIN jabatan j     ON p.jabatan_id  = j.id
            LEFT JOIN periode_gaji pg ON dg.periode_id = pg.id
            $where ORDER BY j.nama_jabatan, p.nama");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $periode_nama = $rows[0]['nama_periode'] ?? 'Periode-' . $periode_filter;

        $filename = 'Laporan_Gaji_' . str_replace(' ', '_', $periode_nama) . '.xls';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office"
                    xmlns:x="urn:schemas-microsoft-com:office:excel"
                    xmlns="http://www.w3.org/TR/REC-html40">
        <head><meta charset="UTF-8">
        <style>
            th { background:#6366f1; color:#fff; font-weight:bold; text-align:center; border:1px solid #ccc; }
            td { border:1px solid #ccc; }
        </style></head><body>';
        echo '<h2 style="font-family:Arial;">Laporan Gaji — ' . esc($periode_nama) . '</h2>';
        echo '<table border="1" cellpadding="4" style="font-family:Arial;font-size:11px;border-collapse:collapse;">';
        echo '<tr>
            <th>No</th><th>NIP</th><th>Nama</th><th>Jabatan</th>
            <th>Gaji Pokok</th><th>Tj. Istri (8%)</th><th>Tj. Anak (5%)</th>
            <th>Tj. Kehadiran (2%)</th><th>Tj. Transport (1.5%)</th>
            <th>Total Potongan</th><th>Gaji Bersih</th><th>Status</th>
        </tr>';
        $totals = array_fill(0, 8, 0);
        foreach ($rows as $i => $r) {
            $gp  = $r['gaji_pokok'];
            $t1  = $gp * 0.08; $t2 = $gp * 0.05;
            $t3  = $gp * 0.02; $t4 = $gp * 0.015;
            $pt  = $r['total_potongan'];
            $tg  = $r['total_gaji'];
            $totals[0]+=$gp;$totals[1]+=$t1;$totals[2]+=$t2;
            $totals[3]+=$t3;$totals[4]+=$t4;$totals[5]+=$pt;$totals[6]+=$tg;
            echo '<tr>
                <td align="center">' . ($i+1) . '</td>
                <td>' . esc($r['nip']) . '</td>
                <td>' . esc($r['nama']) . '</td>
                <td>' . esc($r['nama_jabatan']) . '</td>
                <td align="right">Rp' . rp($gp) . '</td>
                <td align="right">Rp' . rp($t1) . '</td>
                <td align="right">Rp' . rp($t2) . '</td>
                <td align="right">Rp' . rp($t3) . '</td>
                <td align="right">Rp' . rp($t4) . '</td>
                <td align="right" style="color:#ef4444;">Rp' . rp($pt) . '</td>
                <td align="right" style="font-weight:bold;color:#16a34a;">Rp' . rp($tg) . '</td>
                <td align="center">' . esc($r['status']) . '</td>
            </tr>';
        }
        echo '<tr style="background:#f1f5f9;font-weight:bold;">
            <td colspan="4" align="right">TOTAL</td>
            <td align="right">Rp' . rp($totals[0]) . '</td>
            <td align="right">Rp' . rp($totals[1]) . '</td>
            <td align="right">Rp' . rp($totals[2]) . '</td>
            <td align="right">Rp' . rp($totals[3]) . '</td>
            <td align="right">Rp' . rp($totals[4]) . '</td>
            <td align="right" style="color:#ef4444;">Rp' . rp($totals[5]) . '</td>
            <td align="right" style="color:#16a34a;">Rp' . rp($totals[6]) . '</td>
            <td></td>
        </tr>';
        echo '</table></body></html>';
        break;

    // ================================================================
    // LAPORAN ABSENSI (halaman laporan, bukan absensi utama)
    // ================================================================
    case 'laporan_absensi':
        $date_from     = $_GET['from']    ?? date('Y-m-01');
        $date_to       = $_GET['to']      ?? date('Y-m-t');
        $pegawai_filter = $_GET['pegawai'] ?? '';
        $status_filter  = $_GET['status']  ?? '';

        $where  = "WHERE a.tanggal BETWEEN ? AND ?";
        $params = [$date_from, $date_to];
        if ($pegawai_filter) { $where .= " AND a.pegawai_id=?"; $params[] = $pegawai_filter; }
        if ($status_filter)  { $where .= " AND a.status=?";     $params[] = $status_filter; }

        $stmt = $pdo->prepare("SELECT a.*, p.nama, p.nip, j.nama_jabatan
            FROM absensi a
            LEFT JOIN pegawai p ON a.pegawai_id = p.id
            LEFT JOIN jabatan j ON p.jabatan_id = j.id
            $where ORDER BY a.tanggal DESC, p.nama");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $filename = 'Rekap_Laporan_Absensi_' . $date_from . '_sd_' . $date_to . '.xls';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office"
                    xmlns:x="urn:schemas-microsoft-com:office:excel"
                    xmlns="http://www.w3.org/TR/REC-html40">
        <head><meta charset="UTF-8">
        <style>
            th { background:#6366f1; color:#fff; font-weight:bold; text-align:center; border:1px solid #ccc; }
            td { border:1px solid #ccc; }
        </style></head><body>';
        echo '<h2 style="font-family:Arial;">Rekap Laporan Absensi</h2>';
        echo '<p style="font-family:Arial;">Periode: ' . $date_from . ' s/d ' . $date_to . '</p>';
        echo '<table border="1" cellpadding="4" style="font-family:Arial;font-size:12px;border-collapse:collapse;">';
        echo '<tr>
            <th>No</th><th>Tanggal</th><th>NIP</th><th>Nama Pegawai</th>
            <th>Jabatan</th><th>Check In</th><th>Check Out</th>
            <th>Status</th><th>Keterangan</th>
        </tr>';
        foreach ($rows as $i => $r) {
            echo '<tr>
                <td align="center">' . ($i+1) . '</td>
                <td>' . date('d/m/Y', strtotime($r['tanggal'])) . '</td>
                <td>' . esc($r['nip']) . '</td>
                <td>' . esc($r['nama']) . '</td>
                <td>' . esc($r['nama_jabatan']) . '</td>
                <td align="center">' . ($r['check_in'] ?? '-') . '</td>
                <td align="center">' . ($r['check_out'] ?? '-') . '</td>
                <td align="center">' . esc($r['status']) . '</td>
                <td>' . esc($r['keterangan']) . '</td>
            </tr>';
        }
        echo '</table></body></html>';
        break;

    default:
        http_response_code(400);
        echo 'Parameter type tidak valid.';
}
