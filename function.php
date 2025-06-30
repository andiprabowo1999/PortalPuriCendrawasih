<?php
// NAMA FILE: function.php

// Konfigurasi untuk menampilkan error saat pengembangan
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Detail koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "portal"; // Pastikan nama database sudah benar

// Membuat koneksi ke database
$conn = mysqli_connect($host, $user, $pass, $db);

// Memeriksa apakah koneksi berhasil atau gagal
if (!$conn) {
    // Mencatat error ke log server (lebih aman) dan menghentikan skrip
    error_log("Koneksi database gagal: " . mysqli_connect_error());
    // Mengirimkan pesan error yang bisa dibaca jika skrip diakses langsung
    die("Koneksi ke database gagal. Silakan periksa file function.php.");
}