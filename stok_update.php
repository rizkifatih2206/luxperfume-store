<?php
session_start();
include "koneksi.php"; // pastikan file ini ada di folder yang sama

// cek role admin
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
    header("Location:index.php");
    exit;
}

// proses form submit
if(isset($_POST['submit'])){
    // amankan input
    $nama      = mysqli_real_escape_string($conn, $_POST['nama']);
    $brand     = mysqli_real_escape_string($conn, $_POST['brand']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $harga_min = (int)$_POST['harga_min'];
    $harga_max = (int)$_POST['harga_max'];
    $stok      = (int)$_POST['stok'];

    // proses upload gambar
    $gambar = '';
    if(isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0){
        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $gambar = uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['gambar']['tmp_name'], 'images/' . $gambar);
    }

    // simpan ke database
    $stmt = $conn->prepare("INSERT INTO parfum (brand,nama,deskripsi,harga_min,harga_max,stok,gambar) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param("sssiiis", $brand, $nama, $deskripsi, $harga_min, $harga_max, $stok, $gambar);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Parfum berhasil ditambahkan'); window.location='catalog.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Upload Parfum</title>
<style>
body{font-family:Arial, sans-serif;background:#f6f6f7;margin:0}
.box{max-width:500px;margin:40px auto;background:#fff;border-radius:16px;box-shadow:0 8px 24px rgba(0,0,0,.08);padding:20px}
input, textarea{padding:10px;border:1px solid #ddd;border-radius:10px;width:100%;margin-bottom:12px}
.btn{background:#ff4ec4;color:#fff;border:none;padding:12px 16px;border-radius:10px;font-weight:700;cursor:pointer}
</style>
</head>
<body>
<div class="box">
<h2>Tambah Parfum Baru</h2>
<form method="post" enctype="multipart/form-data">
    <label>Brand</label>
    <input type="text" name="brand" required>

    <label>Nama Parfum</label>
    <input type="text" name="nama" required>

    <label>Deskripsi</label>
    <textarea name="deskripsi" rows="3" required></textarea>

    <label>Harga Minimum</label>
    <input type="number" name="harga_min" required>

    <label>Harga Maksimum</label>
    <input type="number" name="harga_max" required>

    <label>Stok</label>
    <input type="number" name="stok" min="0" required>

    <label>Gambar</label>
    <input type="file" name="gambar" accept="image/*" required>

    <button class="btn" type="submit" name="submit">Upload Parfum</button>
</form>
<p><a href="catalog.php">← Kembali ke Katalog</a></p>
</div>
</body>
</html>
