<?php
// NAMA FILE: index.php

require 'function.php';
require 'cek.php';

// Menentukan rentang tanggal, defaultnya adalah hari ini
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Monitoring Akses Portal - PURI CENDRAWASIH</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="sb-nav-fixed">
    <?php include 'header.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Monitoring Akses Portal</h1>
                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-filter me-1"></i>Filter Data Log</div>
                        <div class="card-body">
                            <form method="GET" class="row gx-3 gy-2 align-items-center">
                                <div class="col-sm-4">
                                    <label class="form-label" for="tanggal_awal">Dari Tanggal:</label>
                                    <input class="form-control" type="date" name="tanggal_awal" id="tanggal_awal" value="<?= htmlspecialchars($tanggal_awal) ?>">
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label" for="tanggal_akhir">Sampai Tanggal:</label>
                                    <input class="form-control" type="date" name="tanggal_akhir" id="tanggal_akhir" value="<?= htmlspecialchars($tanggal_akhir) ?>">
                                </div>
                                <div class="col-auto align-self-end">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-search me-2"></i>Tampilkan</button>
                                </div>
                                <div class="col-auto align-self-end">
                                    <a href="export_excel.php?tanggal_awal=<?= htmlspecialchars($tanggal_awal) ?>&tanggal_akhir=<?= htmlspecialchars($tanggal_akhir) ?>" target="_blank" class="btn btn-success">
                                        <i class="fas fa-file-excel me-2"></i>Export ke Excel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-table me-1"></i>Log Aktivitas Akses Portal</div>
                        <div class="card-body">
                            <table id="datatablesSimple">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Nama Pemegang Kartu</th>
                                        <th>UID RFID</th>
                                        <th>Arah</th>
                                        <th>Status Akses</th>
                                        <th>Status IPL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $conn->prepare("SELECT la.waktu_akses, r.nama_lengkap, la.rfid_uid, la.status_akses, la.arah_akses, la.status_iuran_terakhir FROM log_akses la LEFT JOIN rfid r ON la.rfid_uid = r.rfid_uid WHERE DATE(la.waktu_akses) BETWEEN ? AND ? ORDER BY la.waktu_akses DESC");
                                    $stmt->bind_param("ss", $tanggal_awal, $tanggal_akhir);
                                    $stmt->execute();
                                    $result_log = $stmt->get_result();
                                    while ($data = mysqli_fetch_array($result_log)) {
                                        echo "<tr>
                                            <td>" . htmlspecialchars($data['waktu_akses']) . "</td>
                                            <td>" . htmlspecialchars($data['nama_lengkap'] ?? 'Kartu Dihapus/Tidak Dikenal') . "</td>
                                            <td><code>" . htmlspecialchars($data['rfid_uid']) . "</code></td>
                                            <td>" . htmlspecialchars($data['arah_akses']) . "</td>
                                            <td>" . htmlspecialchars($data['status_akses']) . "</td>
                                            <td>" . htmlspecialchars($data['status_iuran_terakhir']) . "</td>
                                          </tr>";
                                    }
                                    $stmt->close();
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <?php include 'footer.php'; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script src="js/datatables-simple-demo.js"></script>
</body>
</html>