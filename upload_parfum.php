<?php
include "koneksi.php";
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $harga_min = (int)$_POST['harga_min'];
    $harga_max = (int)$_POST['harga_max'];
    $stok = (int)$_POST['stok'];

    // Proses upload gambar
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $gambar_name = basename($_FILES["gambar"]["name"]);
    $target_file = $target_dir . time() . "_" . $gambar_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    $check = getimagesize($_FILES["gambar"]["tmp_name"]);
    if ($check === false) {
        echo "<script>alert('File bukan gambar!'); window.location='dashboard_admin.php';</script>";
        exit;
    }

    if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
        // Simpan ke tabel parfum
        $query = "INSERT INTO parfum (nama, harga_min, harga_max, stok, gambar)
                  VALUES ('$nama', '$harga_min', '$harga_max', '$stok', '$target_file')";
        
        if (mysqli_query($conn, $query)) {
            echo "<script>
                    alert('✅ Parfum baru berhasil ditambahkan ke stok!');
                    window.location='dashboard_admin.php';
                  </script>";
        } else {
            echo "<script>
                    alert('❌ Gagal menyimpan ke database!');
                    window.location='dashboard_admin.php';
                  </script>";
        }
    } else {
        echo "<script>
                alert('❌ Gagal mengunggah gambar!');
                window.location='dashboard_admin.php';
              </script>";
    }
}
?>
