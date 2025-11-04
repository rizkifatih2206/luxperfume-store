<?php
include "koneksi.php";
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_promo = mysqli_real_escape_string($conn, $_POST['nama_promo']);
    $potongan = (int)$_POST['potongan'];

    $query = "INSERT INTO promo (nama_promo, potongan, status) VALUES ('$nama_promo', '$potongan', 'aktif')";
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Promo berhasil ditambahkan!'); window.location='dashboard_admin.php';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan promo!'); window.location='dashboard_admin.php';</script>";
    }
}
?>
