<?php
require_once 'config/database.php';
if($_SERVER['REQUEST_METHOD']==='POST') {
    $pdo->prepare("INSERT INTO periode_gaji (nama_periode,bulan,tahun,tanggal_mulai,tanggal_selesai,status) VALUES (?,?,?,?,?,?)")
        ->execute([$_POST['nama_periode'],$_POST['bulan'],$_POST['tahun'],$_POST['tanggal_mulai'],$_POST['tanggal_selesai'],$_POST['status']]);
}
header("Location: periode_gaji.php?msg=created");
exit;
