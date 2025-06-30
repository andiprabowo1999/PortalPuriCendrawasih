<?php
// NAMA FILE: export_excel.php

session_start();
require 'function.php'; 
include 'cek.php';    

$tanggal_awal = isset($_GET['tanggal_awal']) && !empty($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_GET['tanggal_akhir']) && !empty($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');

$filename = 'Laporan_Akses_Portal_' . $tanggal_awal . '_to_' . $tanggal_akhir . '.xls';
header("Content-Type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");

$query = "SELECT
            la.waktu_akses,
            r.nama_lengkap,
            la.rfid_uid,
            la.status_akses,
            la.arah_akses,
            la.status_iuran_terakhir AS status_ipl
          FROM
            log_akses la
          LEFT JOIN
            rfid r ON la.rfid_uid = r.rfid_uid
          WHERE
            DATE(la.waktu_akses) BETWEEN ? AND ?
          ORDER BY
            la.waktu_akses ASC";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing statement: " . htmlspecialchars($conn->error));
}
$stmt->bind_param("ss", $tanggal_awal, $tanggal_akhir);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Akses Portal</title>
</head>
<body>
    <h3>Laporan Akses Portal Otomatis</h3>
    <p>Periode: <?php echo htmlspecialchars($tanggal_awal); ?> s/d <?php echo htmlspecialchars($tanggal_akhir); ?></p>

    <table border="1">
        <thead>
            <tr>
                <th>No.</th>
                <th>Waktu Akses</th>
                <th>Nama Pemegang Kartu</th>
                <th>UID RFID</th>
                <th>Arah Akses</th>
                <th>Status Akses</th>
                <th>Status IPL Terakhir</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            if ($result->num_rows > 0) {
                while ($data = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $no++ . "</td>";
                    echo "<td>" . htmlspecialchars($data['waktu_akses']) . "</td>";
                    echo "<td>" . htmlspecialchars($data['nama_lengkap'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($data['rfid_uid']) . "</td>";
                    echo "<td>" . htmlspecialchars($data['arah_akses']) . "</td>";
                    echo "<td>" . htmlspecialchars($data['status_akses']) . "</td>";
                    echo "<td>" . htmlspecialchars($data['status_ipl']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='7' style='text-align:center;'>Tidak ada data untuk periode yang dipilih.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>
<?php
$stmt->close();
$conn->close();
exit();
?>