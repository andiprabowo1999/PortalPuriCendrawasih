<?php
// cek.php
// Memulai sesi jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

//jika belum login, arahkan ke halaman login
if(!isset($_SESSION['log']) || $_SESSION['log'] !== true){
    header('location:login.php');
    exit; // Pastikan untuk keluar setelah redirect
}