<?php
// koneksi.php
// Konfigurasi pelaporan error PHP (untuk debugging)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost"; // Biasanya 'localhost' jika server web dan database di mesin yang sama
$username = "root";        // Ganti dengan username database Anda
$password = "";            // Ganti dengan password database Anda
$dbname = "portal"; // Pastikan ini adalah nama database Anda yang baru

// Buat koneksi ke database MySQLi
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    // Jika koneksi gagal, hentikan eksekusi script dan tampilkan error
    die("Koneksi gagal: " . $conn->connect_error);
}
// echo "Koneksi database berhasil!"; // Uncomment ini untuk debug koneksi
?>