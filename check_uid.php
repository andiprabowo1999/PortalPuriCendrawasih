<?php
header('Content-Type: application/json');
include 'koneksi.php';

$response = ["status" => "error", "message" => "Terjadi kesalahan.", "status_akses" => "DITOLAK", "status_iuran" => "TIDAK RELEVAN"];

if (isset($_GET['rfid_uid']) && isset($_GET['arah_akses'])) {
    $rfid_uid = $conn->real_escape_string($_GET['rfid_uid']);
    $arah_akses = $conn->real_escape_string($_GET['arah_akses']);

    // 1. Cek UID di tabel `rfid` (sebelumnya kartu_rfid)
    // Menggunakan rfid_uid sebagai kunci, sesuai skema baru
    $sql_check_uid = "SELECT rfid_uid, nama_lengkap, status_rfid FROM rfid WHERE rfid_uid = ? AND status_rfid = 'aktif'"; //
    $stmt_check_uid = $conn->prepare($sql_check_uid);
    if ($stmt_check_uid === false) {
        $response["message"] = 'Failed to prepare UID statement: ' . $conn->error;
        echo json_encode($response);
        exit;
    }
    $stmt_check_uid->bind_param("s", $rfid_uid);
    $stmt_check_uid->execute();
    $result_uid = $stmt_check_uid->get_result();


    if ($result_uid && $result_uid->num_rows > 0) {
        $row_uid = $result_uid->fetch_assoc();
        // $nama_lengkap = $row_uid['nama_lengkap']; // tidak dipakai langsung

        // Default status akses
        $status_akses_final = "DIZINKAN"; // Asumsi awal diizinkan jika RFID aktif
        $status_iuran_final = "TIDAK RELEVAN"; // Default, akan diupdate oleh pengecekan iuran

        // --- Pengecekan status iuran (DIpindahkan di luar kondisi arah) ---
        $bulan_sekarang_num = date('m'); // Angka bulan
        $tahun_sekarang_num = date('Y'); // Angka tahun

        // Asumsi kolom di status_iuran adalah rfid_uid, bulan, tahun, status
        $sql_check_iuran = "SELECT status FROM status_iuran WHERE rfid_uid = ? AND bulan = ? AND tahun = ?";
        $stmt_check_iuran = $conn->prepare($sql_check_iuran);
        if ($stmt_check_iuran === false) {
            $response["message"] = 'Failed to prepare iuran statement: ' . $conn->error;
            echo json_encode($response);
            exit;
        }
        $stmt_check_iuran->bind_param("sii", $rfid_uid, $bulan_sekarang_num, $tahun_sekarang_num);
        $stmt_check_iuran->execute();
        $result_iuran = $stmt_check_iuran->get_result();

        if ($result_iuran && $result_iuran->num_rows > 0) {
            $row_iuran = $result_iuran->fetch_assoc();
            $status_iuran_final = $row_iuran['status']; // Ambil status dari database
        } else {
            // Jika tidak ada data iuran untuk bulan ini, anggap belum lunas
            $status_iuran_final = "BELUM LUNAS";
        }
        $stmt_check_iuran->close();
        // --- Akhir pengecekan status iuran ---

        // 2. Tentukan status akses berdasarkan arah dan status iuran
        if ($arah_akses == 'MASUK') {
            if ($status_iuran_final == 'BELUM LUNAS' || $status_iuran_final == '2 BULAN BELUM LUNAS') {
                $status_akses_final = "DITOLAK"; // Tolak jika belum lunas untuk masuk
                $response["message"] = "Akses ditolak: Iuran bulan ini belum lunas.";
            } else { // Jika status_iuran_final adalah 'LUNAS'
                $status_akses_final = "DIZINKAN"; // Diizinkan jika lunas untuk masuk
                $response["message"] = "Akses diizinkan: Iuran lunas.";
            }
        } else { // Jika arah_akses adalah 'KELUAR'
            $status_akses_final = "DIZINKAN"; // Biasanya keluar selalu diizinkan
            $response["message"] = "Akses diizinkan (arah keluar).";
            // status_iuran_final sudah diatur di atas, jadi akan tercatat "LUNAS" atau "BELUM LUNAS"
        }

        // Finalisasi respon
        if ($status_akses_final == "DIZINKAN") {
            $response["status"] = "success";
        } else {
            $response["status"] = "error";
        }
        $response["status_akses"] = $status_akses_final;
        $response["status_iuran"] = $status_iuran_final;

    } else {
        $response["message"] = "RFID tidak terdaftar atau tidak aktif.";
        $response["status_akses"] = "DITOLAK";
        $response["status_iuran"] = "TIDAK TERDAFTAR"; // Tambahkan status iuran ini untuk log jika perlu
    }
    $stmt_check_uid->close();
} else {
    $response["message"] = "Parameter RFID UID atau Arah Akses tidak lengkap.";
}

$conn->close();
echo json_encode($response);