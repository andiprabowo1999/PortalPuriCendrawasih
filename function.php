<?php
// function.php
// Pastikan ini adalah baris pertama file, tanpa spasi/newline di atasnya.
// Konfigurasi pelaporan error PHP (untuk debugging) - bisa dihilangkan di produksi
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$pass = "";
$db = "portal"; // Pastikan ini nama database Anda

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    // Gunakan error_log untuk mencatat error di server, bukan ditampilkan langsung
    error_log("Koneksi gagal: " . mysqli_connect_error());
    die("Koneksi database gagal. Silakan coba lagi nanti.");
}