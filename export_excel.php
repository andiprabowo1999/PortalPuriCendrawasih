<?php
session_start();
require 'function.php'; // Pastikan ini mengarah ke file koneksi database Anda
include 'cek.php';    // Memastikan hanya yang login yang bisa akses

// Pastikan Anda telah menginstal PhpSpreadsheet melalui Composer
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Inisialisasi tanggal untuk filter (sama seperti di index.php)
$tanggal_awal = date('Y-m-d');
$tanggal_akhir = date('Y-m-d');

if (isset($_GET['tanggal_awal']) && !empty($_GET['tanggal_awal'])) {
    $tanggal_awal = $_GET['tanggal_awal'];
}
if (isset($_GET['tanggal_akhir']) && !empty($_GET['tanggal_akhir'])) {
    $tanggal_akhir = $_GET['tanggal_akhir'];
}

// Buat objek Spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set Judul Kolom
$sheet->setCellValue('A1', 'Waktu');
$sheet->setCellValue('B1', 'Nama');
$sheet->setCellValue('C1', 'RFID');
$sheet->setCellValue('D1', 'Arah Akses');
$sheet->setCellValue('E1', 'Status Akses');
$sheet->setCellValue('F1', 'Status Iuran');

// Ambil data dari database berdasarkan filter tanggal
$queryData = mysqli_query($conn, "SELECT
                                    la.waktu_akses,
                                    kr.nama_lengkap,
                                    la.rfid_uid,
                                    la.status_akses,
                                    la.arah_akses,
                                    la.status_iuran_terakhir AS status_iuran
                                  FROM
                                    log_akses la
                                  JOIN
                                    rfid kr ON la.rfid_uid = kr.rfid_uid -- Mengubah kartu_rfid menjadi rfid
                                  WHERE
                                    DATE(la.waktu_akses) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                                  ORDER BY
                                    la.waktu_akses ASC");

$row = 2; // Mulai menulis data dari baris ke-2 (setelah header)
while ($data = mysqli_fetch_assoc($queryData)) {
    $sheet->setCellValue('A' . $row, (new DateTime($data['waktu_akses']))->format('Y-m-d H:i:s'));
    $sheet->setCellValue('B' . $row, $data['nama_lengkap']);
    $sheet->setCellValue('C' . $row, $data['rfid_uid']);
    $sheet->setCellValue('D' . $row, $data['arah_akses']);
    $sheet->setCellValue('E' . $row, $data['status_akses']);
    $sheet->setCellValue('F' . $row, $data['status_iuran']);
    $row++;
}

// Set header HTTP untuk download file Excel
$filename = 'Laporan_Akses_Portal_' . $tanggal_awal . '_to_' . $tanggal_akhir . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;