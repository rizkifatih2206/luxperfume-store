<?php
session_start();
include "koneksi.php";

header('Content-Type: application/json');

if(!isset($_SESSION['cart']) || empty($_SESSION['cart'])){
    echo json_encode(['status'=>'error','message'=>'Keranjang kosong!']);
    exit;
}

$nama = trim($_POST['nama'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');
$hp = trim($_POST['hp'] ?? '');
$bayar = (int)($_POST['bayar'] ?? 0);
$tanggal = date('Y-m-d H:i:s');

if(!$nama || !$alamat || !$hp || $bayar<=0){
    echo json_encode(['status'=>'error','message'=>'Form tidak lengkap!']);
    exit;
}

// Hitung total
$total = 0;
foreach($_SESSION['cart'] as $item){
    $total += $item['harga'] * $item['qty'];
}

// Simpan transaksi
$stmt = $conn->prepare("INSERT INTO transaksi (nama_pelanggan, alamat, no_hp, total, tanggal) VALUES (?,?,?,?,?)");
$stmt->bind_param("sssis", $nama, $alamat, $hp, $total, $tanggal);
$stmt->execute();
$transaksi_id = $stmt->insert_id;
$stmt->close();

// Detail transaksi
$stmt_detail = $conn->prepare("INSERT INTO transaksi_detail (transaksi_id, parfum_id, nama_parfum, harga, qty, subtotal) VALUES (?,?,?,?,?,?)");
$stmt_update = $conn->prepare("UPDATE parfum SET jumlah_stok = GREATEST(jumlah_stok - ?,0) WHERE id=?");
foreach($_SESSION['cart'] as $id => $item){
    $nama_parfum = $item['nama'];
    $harga = $item['harga'];
    $qty = $item['qty'];
    $subtotal = $harga*$qty;

    $stmt_detail->bind_param("iisiii", $transaksi_id,$id,$nama_parfum,$harga,$qty,$subtotal);
    $stmt_detail->execute();

    $stmt_update->bind_param("ii",$qty,$id);
    $stmt_update->execute();
}
$stmt_detail->close();
$stmt_update->close();

// Buat struk HTML
$struk = '<h3>Struk Pembelian</h3>';
$struk .= '<p>No. Struk: '.$transaksi_id.'</p>';
$struk .= '<p>Nama: '.htmlspecialchars($nama).'</p>';
$struk .= '<p>Alamat: '.htmlspecialchars($alamat).'</p>';
$struk .= '<p>HP: '.htmlspecialchars($hp).'</p>';
$struk .= '<p>Tanggal: '.$tanggal.'</p>';
$struk .= '<hr>';
$struk .= '<table style="width:100%; border-collapse:collapse;">';
foreach($_SESSION['cart'] as $item){
    $sub = $item['harga']*$item['qty'];
    $struk .= '<tr>
        <td>'.htmlspecialchars($item['nama']).'</td>
        <td style="text-align:center;">'.$item['qty'].'</td>
        <td style="text-align:right;">Rp '.number_format($sub,0,',','.').'</td>
    </tr>';
}
$struk .= '</table>';
$struk .= '<hr>';
$struk .= '<p>Total: Rp '.number_format($total,0,',','.').'</p>';
$struk .= '<p>Bayar: Rp '.number_format($bayar,0,',','.').'</p>';
$struk .= '<p>Kembali: Rp '.number_format($bayar-$total,0,',','.').'</p>';
$struk .= '<p>Terima kasih!</p>';

// Kosongkan keranjang
unset($_SESSION['cart']);

echo json_encode(['status'=>'success','struk_html'=>$struk]);
