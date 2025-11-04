<?php
session_start();
include 'koneksi.php';

if ($_SESSION['role'] !== 'admin') {
    die("Akses ditolak");
}

$member_id = $_POST['member_id'];
$parfum_id = $_POST['parfum_id'];
$qty = $_POST['qty'] ?? 1;

// Cari nama member
$stmt = $conn->prepare("SELECT nama FROM users WHERE member_id = ?");
$stmt->bind_param("s", $member_id);
$stmt->execute();
$result = $stmt->get_result();
$member = $result->fetch_assoc();

if (!$member) {
    die("Member tidak ditemukan!");
}

// Ambil harga parfum
$stmt = $conn->prepare("SELECT harga_min, stok FROM parfum WHERE id = ?");
$stmt->bind_param("i", $parfum_id);
$stmt->execute();
$result = $stmt->get_result();
$parfum = $result->fetch_assoc();

if ($parfum['stok'] < $qty) {
    die("Stok tidak cukup");
}

$total = $parfum['harga_min'] * $qty;

// Masukkan ke orders
$stmt = $conn->prepare("INSERT INTO orders (user_id, member_id, parfum_id, qty, total, channel) VALUES (NULL, ?, ?, ?, ?, 'offline')");
$stmt->bind_param("siiii", $member_id, $parfum_id, $qty, $total);
$stmt->execute();

// Kurangi stok
$stmt = $conn->prepare("UPDATE parfum SET stok = stok - ? WHERE id = ?");
$stmt->bind_param("ii", $qty, $parfum_id);
$stmt->execute();

echo "Transaksi offline berhasil untuk member: " . htmlspecialchars($member['nama']);
?>
