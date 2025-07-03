<?php
// NAMA FILE: insert_log.php

header('Content-Type: application/json');
require 'function.php';

// Menyiapkan respons default
$response = [
    'access_granted' => false,
    'message' => 'Kesalahan sistem tidak diketahui.'
];

// Mengambil parameter dari ESP32 dengan aman
$rfid_uid = isset($_GET['rfid_uid']) ? $_GET['rfid_uid'] : null;
$arah_akses = isset($_GET['arah_akses']) ? $_GET['arah_akses'] : null;

if ($rfid_uid && $arah_akses) {

    // 1. Cek apakah kartu RFID ada di database
    $stmt_rfid = $conn->prepare("SELECT id_kk, status_rfid FROM rfid WHERE rfid_uid = ?");
    $stmt_rfid->bind_param("s", $rfid_uid);
    $stmt_rfid->execute();
    $result_rfid = $stmt_rfid->get_result();

    if ($result_rfid->num_rows > 0) {
        // --- KARTU TERDAFTAR, LANJUTKAN PROSES VALIDASI ---
        $rfid_data = $result_rfid->fetch_assoc();
        $id_kk = $rfid_data['id_kk'];
        $status_akses_log = 'DITOLAK'; // Default
        $status_iuran_log = 'ERROR';   // Default

        if (is_null($id_kk)) {
            $response['message'] = 'Akses DITOLAK: Kartu belum terhubung ke KK.';
            $status_iuran_log = 'KARTU BELUM TERHUBUNG';
        }
        else if ($rfid_data['status_rfid'] !== 'aktif') {
            $response['message'] = 'Akses DITOLAK: Status kartu tidak aktif.';
            $status_iuran_log = 'RFID TIDAK AKTIF';
        } 
        else {
            if ($arah_akses == 'KELUAR') {
                $response['access_granted'] = true;
                $response['message'] = 'Akses DIZINKAN (Keluar).';
                $status_akses_log = 'DIZINKAN';
                $status_iuran_log = 'TIDAK RELEVAN';
            } 
            else if ($arah_akses == 'MASUK') {
                // Ambil Parameter Kebijakan dari Database
                $kebijakan_query = mysqli_query($conn, "SELECT nama_kebijakan, nilai_kebijakan FROM parameter_kebijakan");
                $kebijakan = [];
                while ($row = mysqli_fetch_assoc($kebijakan_query)) {
                    $kebijakan[$row['nama_kebijakan']] = $row['nilai_kebijakan'];
                }
                $batas_tunggakan_bulan = isset($kebijakan['batas_tunggakan_bulan']) ? (int)$kebijakan['batas_tunggakan_bulan'] : 2;

                $tunggakan_beruntun = 0;
                for ($i = 0; $i < $batas_tunggakan_bulan; $i++) {
                    $bulan_cek = date('n', strtotime("-$i month"));
                    $tahun_cek = date('Y', strtotime("-$i month"));

                    $stmt_iuran = $conn->prepare("SELECT status FROM status_iuran WHERE id_kk = ? AND bulan = ? AND tahun = ?");
                    $stmt_iuran->bind_param("iii", $id_kk, $bulan_cek, $tahun_cek);
                    $stmt_iuran->execute();
                    $result_iuran = $stmt_iuran->get_result();
                    
                    $status_bulan = ($result_iuran->num_rows > 0) ? $result_iuran->fetch_assoc()['status'] : 'BELUM LUNAS';
                    $stmt_iuran->close();

                    if ($status_bulan !== 'LUNAS') {
                        $tunggakan_beruntun++;
                    } else {
                        break; 
                    }
                }

                if ($tunggakan_beruntun >= $batas_tunggakan_bulan) {
                    $response['message'] = "Akses DITOLAK: IPL $batas_tunggakan_bulan bulan terakhir belum lunas.";
                    $status_akses_log = 'DITOLAK';
                    $status_iuran_log = "$tunggakan_beruntun BULAN BELUM LUNAS";
                } else {
                    $response['access_granted'] = true;
                    $response['message'] = 'Akses DIZINKAN.';
                    $status_akses_log = 'DIZINKAN';
                    $status_iuran_log = ($tunggakan_beruntun > 0) ? 'BELUM LUNAS' : 'LUNAS';
                }
            }
        }
        
        // Karena kartu terdaftar, kita bisa mencatat log-nya
        $stmt_log = $conn->prepare("INSERT INTO log_akses (rfid_uid, waktu_akses, status_akses, arah_akses, status_iuran_terakhir) VALUES (?, NOW(), ?, ?, ?)");
        if ($stmt_log) {
            $stmt_log->bind_param("ssss", $rfid_uid, $status_akses_log, $arah_akses, $status_iuran_log);
            $stmt_log->execute();
            $stmt_log->close();
        }

    } else {
        // --- KARTU TIDAK TERDAFTAR ---
        $response['message'] = 'Akses DITOLAK: RFID tidak terdaftar.';
        // JANGAN LAKUKAN PENCATATAN LOG DI SINI UNTUK MENGHINDARI ERROR
    }
    $stmt_rfid->close();

} else {
    $response['message'] = 'Parameter RFID atau Arah Akses tidak lengkap.';
}

// Kirimkan respons dalam format JSON ke ESP32
echo json_encode($response);
$conn->close();