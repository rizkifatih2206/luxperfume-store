<?php
include "koneksi.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $parfum_id = (int)$_POST["parfum_id"];
    $qty = (int)$_POST["qty"];

    // Ambil data parfum
    $result = $conn->query("SELECT * FROM parfum WHERE id = $parfum_id");
    $parfum = $result->fetch_assoc();

    if (!$parfum) {
        die("Parfum tidak ditemukan.");
    }

    // Cek stok
    if ($qty > $parfum['stok']) {
        echo "<script>alert('Stok tidak mencukupi!'); window.history.back();</script>";
        exit;
    }

    // Kurangi stok di database
    $conn->query("UPDATE parfum SET stok = stok - $qty WHERE id = $parfum_id");

    // Simpan ke session riwayat pembelian
    $_SESSION['last_purchase'] = [
        'nama' => $parfum['nama'],
        'brand' => $parfum['brand'],
        'qty' => $qty,
        'harga' => $parfum['harga_min'],
        'total' => $parfum['harga_min'] * $qty
    ];

    echo "<script>
        alert('Berhasil membeli {$parfum['nama']} sejumlah $qty!');
        window.location.href='stok.php';
    </script>";
} else {
    echo "<script>alert('Akses tidak sah.'); window.location.href='stok.php';</script>";
}
?>
