<?php
// NAMA FILE: function.php

// PENTING: Mengatur zona waktu default untuk semua fungsi tanggal/waktu di PHP
// Ini akan memastikan semua tanggal yang dihasilkan sesuai dengan waktu Indonesia Barat (WIB).
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi untuk menampilkan error saat pengembangan
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Detail koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "portal"; 

// Membuat koneksi ke database
$conn = mysqli_connect($host, $user, $pass, $db);

// Memeriksa apakah koneksi berhasil atau gagal
if (!$conn) {
    error_log("Koneksi database gagal: " . mysqli_connect_error());
    die("Koneksi ke database gagal. Silakan periksa file function.php.");
}
?>
