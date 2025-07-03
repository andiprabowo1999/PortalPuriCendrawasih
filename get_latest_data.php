<?php
// NAMA FILE: get_latest_data.php

// Mengatur header agar output selalu berformat JSON
header('Content-Type: application/json');
require 'function.php'; // Memuat koneksi database
// Tidak perlu cek login karena ini adalah endpoint data, keamanan ditangani oleh sesi di halaman utama

// Mengambil parameter rentang tanggal dari permintaan JavaScript
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');

// Menyiapkan array respons default
$response = [
    'status' => 'error',
    'message' => 'Gagal mengambil data.',
    'data' => []
];

// Query untuk mengambil data log akses
$query = "SELECT 
            la.waktu_akses,
            r.nama_lengkap,
            la.rfid_uid,
            la.status_akses,
            la.arah_akses,
            la.status_iuran_terakhir
          FROM 
            log_akses la
          LEFT JOIN 
            rfid r ON la.rfid_uid = r.rfid_uid
          WHERE 
            DATE(la.waktu_akses) BETWEEN ? AND ?
          ORDER BY 
            la.waktu_akses DESC";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("ss", $tanggal_awal, $tanggal_akhir);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data_log = [];
    while ($row = $result->fetch_assoc()) {
        // Memastikan semua data adalah string dan aman untuk ditampilkan
        foreach ($row as $key => $value) {
            $row[$key] = htmlspecialchars($value ?? '');
        }
        $data_log[] = $row;
    }
    
    $response['status'] = 'success';
    $response['message'] = 'Data berhasil diambil.';
    $response['data'] = $data_log;
    
    $stmt->close();
} else {
    $response['message'] = 'Error: Gagal menyiapkan query database.';
}

$conn->close();
// Mengirimkan data dalam format JSON
echo json_encode($response);
?>
