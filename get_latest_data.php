<?php
// get_latest_data.php
header('Content-Type: application/json');
require 'function.php';
require 'cek.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['log']) || $_SESSION['log'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');

$response = [];

$query = "SELECT
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
            DATE(la.waktu_akses) BETWEEN ? AND ?
          ORDER BY
            la.waktu_akses DESC";

$stmt = mysqli_prepare($conn, $query);
if ($stmt === false) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, "ss", $tanggal_awal, $tanggal_akhir);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$data_per_tanggal = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $tanggal_log = date('Y-m-d', strtotime($row['waktu_akses']));
        if (!isset($data_per_tanggal[$tanggal_log])) {
            $data_per_tanggal[$tanggal_log] = [];
        }
        $data_per_tanggal[$tanggal_log][] = $row;
    }
    mysqli_free_result($result);
    $response['status'] = 'success';
    $response['data'] = $data_per_tanggal;
} else {
    $response['status'] = 'error';
    $response['message'] = 'Failed to fetch data: ' . mysqli_error($conn);
    $response['data'] = [];
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo json_encode($response);