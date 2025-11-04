<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "parfume"; // <- pastikan ini sesuai dengan nama database di phpMyAdmin

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Pastikan koneksi bisa diakses global
return $conn;
?>
