<?php
// NAMA FILE: insert_log.php

header('Content-Type: application/json');
require 'function.php';

$response = [
    'access_granted' => false,
    'message' => 'Kesalahan sistem tidak diketahui.'
];
$status_akses_log = 'DITOLAK';
$status_iuran_log = 'ERROR SISTEM';
$rfid_uid = isset($_GET['rfid_uid']) ? $_GET['rfid_uid'] : 'N/A';
$arah_akses = isset($_GET['arah_akses']) ? $_GET['arah_akses'] : 'N/A';

if ($rfid_uid !== 'N/A' && $arah_akses !== 'N/A') {

    // 1. Ambil Pengaturan Sistem dari Database
    $pengaturan_query = mysqli_query($conn, "SELECT nama_pengaturan, nilai_pengaturan FROM pengaturan");
    $pengaturan = [];
    while ($row = mysqli_fetch_assoc($pengaturan_query)) {
        $pengaturan[$row['nama_pengaturan']] = $row['nilai_pengaturan'];
    }
    $batas_tunggakan_bulan = isset($pengaturan['batas_tunggakan_bulan']) ? (int)$pengaturan['batas_tunggakan_bulan'] : 2;

    // 2. Cek status kartu RFID dan dapatkan ID KK-nya
    $stmt_rfid = $conn->prepare("SELECT id_kk, status_rfid FROM rfid WHERE rfid_uid = ?");
    $stmt_rfid->bind_param("s", $rfid_uid);
    $stmt_rfid->execute();
    $result_rfid = $stmt_rfid->get_result();

    if ($result_rfid->num_rows > 0) {
        $rfid_data = $result_rfid->fetch_assoc();
        $id_kk = $rfid_data['id_kk'];

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
    } else {
        $response['message'] = 'Akses DITOLAK: RFID tidak terdaftar.';
        $status_iuran_log = 'TIDAK TERDAFTAR';
    }
    $stmt_rfid->close();

} else {
    $response['message'] = 'Parameter RFID atau Arah Akses tidak lengkap.';
    $status_iuran_log = 'REQUEST TIDAK LENGKAP';
}

// 3. Catat semua aktivitas akses ke dalam log
$stmt_log = $conn->prepare("INSERT INTO log_akses (rfid_uid, waktu_akses, status_akses, arah_akses, status_iuran_terakhir) VALUES (?, NOW(), ?, ?, ?)");
if ($stmt_log) {
    $stmt_log->bind_param("ssss", $rfid_uid, $status_akses_log, $arah_akses, $status_iuran_log);
    $stmt_log->execute();
    $stmt_log->close();
}

echo json_encode($response);
$conn->close();