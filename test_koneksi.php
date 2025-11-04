<?php
$mysqli = new mysqli("localhost", "root", "", "parfum"); // ganti perfume -> parfum
if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
} else {
    echo "Koneksi berhasil!";
}
?>
