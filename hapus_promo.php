<?php
include "koneksi.php";
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];
mysqli_query($conn, "DELETE FROM promo WHERE id=$id");

echo "<script>alert('Promo berhasil dihapus!'); window.location='dashboard_admin.php';</script>";
?>
