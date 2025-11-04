<?php
include "koneksi.php";
session_start();

// Pastikan hanya admin yang bisa akses (opsional)
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Cek apakah ada parameter id
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Cek dulu apakah data parfumnya ada
    $check = $conn->query("SELECT * FROM parfum WHERE id = $id");
    if ($check->num_rows > 0) {
        // Hapus parfum dari database
        $delete = $conn->query("DELETE FROM parfum WHERE id = $id");
        if ($delete) {
            echo "<script>alert('Parfum berhasil dihapus!'); window.location='dashboard_admin.php';</script>";
        } else {
            echo "<script>alert('Gagal menghapus parfum!'); window.location='dashboard_admin.php';</script>";
        }
    } else {
        echo "<script>alert('Parfum tidak ditemukan!'); window.location='dashboard_admin.php';</script>";
    }
} else {
    header("Location: dashboard_admin.php");
    exit;
}
?>
