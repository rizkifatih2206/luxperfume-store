<?php
// beli_proses.php
session_start();
require 'koneksi.php'; // ini akan bikin $conn siap dipakai

// Pastikan koneksi valid
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Koneksi database tidak tersedia. Periksa file koneksi.php");
}

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id   = (int) $_SESSION['user_id'];
$member_id = isset($_SESSION['member_id']) ? trim($_SESSION['member_id']) : null;

// Data dari form
$parfum_id = isset($_POST['parfum_id']) ? (int) $_POST['parfum_id'] : 0;
$qty       = isset($_POST['qty']) ? max(1, (int) $_POST['qty']) : 1;

if ($parfum_id <= 0) {
    echo "<script>alert('Parfum tidak valid');location.href='stok.php';</script>";
    exit;
}

// Ambil data parfum
$stmt = $conn->prepare("SELECT id, nama, harga_min, stok FROM parfum WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $parfum_id);
$stmt->execute();
$res = $stmt->get_result();
$parfum = $res->fetch_assoc();
$stmt->close();

if (!$parfum) {
    echo "<script>alert('Parfum tidak ditemukan');location.href='stok.php';</script>";
    exit;
}

if ($qty > (int)$parfum['stok']) {
    echo "<script>alert('Stok tidak mencukupi');location.href='stok.php';</script>";
    exit;
}

$harga = (int) $parfum['harga_min'];
$total = $harga * $qty;

$conn->begin_transaction();

try {
    // Insert ke tabel orders
    if ($member_id !== null && $member_id !== '') {
        $ins = $conn->prepare("
            INSERT INTO orders (user_id, member_id, parfum_id, qty, total, channel)
            VALUES (?, ?, ?, ?, ?, 'online')
        ");
        $ins->bind_param("isiii", $user_id, $member_id, $parfum_id, $qty, $total);
    } else {
        $ins = $conn->prepare("
            INSERT INTO orders (user_id, parfum_id, qty, total, channel)
            VALUES (?, ?, ?, ?, 'online')
        ");
        $ins->bind_param("iiii", $user_id, $parfum_id, $qty, $total);
    }

    $ins->execute();
    $ins->close();

    // Update stok
    $upd = $conn->prepare("UPDATE parfum SET stok = stok - ? WHERE id = ?");
    $upd->bind_param("ii", $qty, $parfum_id);
    $upd->execute();
    $upd->close();

    $conn->commit();

    echo "<script>alert('Pembelian berhasil!');location.href='stok.php';</script>";
    exit;

} catch (Exception $e) {
    $conn->rollback();
    echo "<script>alert('Transaksi gagal: " . addslashes($e->getMessage()) . "');history.back();</script>";
    exit;
}
