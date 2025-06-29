<?php
require 'function.php';
require 'cek.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['log'])) {
    header("Location: login.php");
    exit;
}

$tahun_tampil = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

$bulan_sekarang = date('n');
$tahun_sekarang = date('Y');
$bulan_sebelumnya = date('n', strtotime('-1 month'));
$tahun_sebelumnya = date('Y', strtotime('-1 month'));

// --- Logika Otomatis: Memastikan Entri Bulan Ini Ada & Mempertahankan LUNAS/BELUM LUNAS ---
$stmt_aktif = $conn->prepare("SELECT rfid_uid, nama_lengkap FROM rfid WHERE status_rfid = 'aktif'"); // Mengubah kartu_rfid menjadi rfid, status_kartu menjadi status_rfid
$stmt_aktif->execute();
$result_aktif = $stmt_aktif->get_result();

while ($row_aktif = $result_aktif->fetch_assoc()) {
    $rfid_uid_aktif = $row_aktif['rfid_uid'];

    $stmt_check_current = $conn->prepare("INSERT INTO status_iuran (rfid_uid, bulan, tahun, status) VALUES (?, ?, ?, 'BELUM LUNAS') ON DUPLICATE KEY UPDATE rfid_uid=rfid_uid");
    $stmt_check_current->bind_param("sii", $rfid_uid_aktif, $bulan_sekarang, $tahun_sekarang);
    $stmt_check_current->execute();
    $stmt_check_current->close();

    $status_current = 'BELUM LUNAS';
    $stmt_get_current = $conn->prepare("SELECT status FROM status_iuran WHERE rfid_uid = ? AND bulan = ? AND tahun = ?");
    $stmt_get_current->bind_param("sii", $rfid_uid_aktif, $bulan_sekarang, $tahun_sekarang);
    $stmt_get_current->execute();
    $res_get_current = $stmt_get_current->get_result();
    if ($res_get_current->num_rows > 0) {
        $status_current = $res_get_current->fetch_assoc()['status'];
    }
    $stmt_get_current->close();

    $status_previous = 'BELUM LUNAS';
    $stmt_get_previous = $conn->prepare("SELECT status FROM status_iuran WHERE rfid_uid = ? AND bulan = ? AND tahun = ?");
    $stmt_get_previous->bind_param("sii", $rfid_uid_aktif, $bulan_sebelumnya, $tahun_sebelumnya);
    $stmt_get_previous->execute();
    $res_get_previous = $stmt_get_previous->get_result();
    if ($res_get_previous->num_rows > 0) {
        $status_previous = $res_get_previous->fetch_assoc()['status'];
    }
    $stmt_get_previous->close();

    $target_status_for_current_month = $status_current;

    // Logika untuk mengubah status BULAN INI menjadi '2 BULAN BELUM LUNAS' (ini hanya berlaku di insert_log, tidak disimpan di DB)
    // Di sini kita hanya memastikan status di DB tidak '2 BULAN BELUM LUNAS'
    if ($status_current == '2 BULAN BELUM LUNAS') {
        $target_status_for_current_month = 'BELUM LUNAS';
    }

    if ($target_status_for_current_month !== $status_current) {
        $stmt_update_auto = $conn->prepare("UPDATE status_iuran SET status = ? WHERE rfid_uid = ? AND bulan = ? AND tahun = ?");
        $stmt_update_auto->bind_param("ssii", $target_status_for_current_month, $rfid_uid_aktif, $bulan_sekarang, $tahun_sekarang);
        $stmt_update_auto->execute();
        $stmt_update_auto->close();
    }
}
$stmt_aktif->close();


// --- Proses update status pembayaran dari Form (manual admin) ---
if (isset($_POST['update_iuran'])) {
    $rfid_uid_update = $_POST['rfid_uid'];
    $bulan_update = (int)$_POST['bulan_update'];
    $tahun_update = (int)$_POST['tahun_update'];
    $status_baru = $_POST['status_baru'];

    $stmt = $conn->prepare("INSERT INTO status_iuran (rfid_uid, bulan, tahun, status, tanggal_pembayaran) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE status = VALUES(status), tanggal_pembayaran = NOW()");
    $stmt->bind_param("siis", $rfid_uid_update, $bulan_update, $tahun_update, $status_baru);

    if ($stmt->execute()) {
        echo "<script>alert('Status iuran berhasil diperbarui!'); window.location='iuranBulananWarga.php?tahun={$tahun_tampil}';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui status iuran: " . $stmt->error . "'); window.location='iuranBulananWarga.php?tahun={$tahun_tampil}';</script>";
    }
    $stmt->close();
}

// --- Ambil Data untuk Tampilan Per Tahun ---
$data_iuran_per_warga = [];

$stmt_warga = $conn->prepare("SELECT rfid_uid, nama_lengkap FROM rfid ORDER BY nama_lengkap ASC"); // Mengubah kartu_rfid menjadi rfid
$stmt_warga->execute();
$result_warga = $stmt_warga->get_result();

while ($row_warga = $result_warga->fetch_assoc()) {
    $rfid_uid = $row_warga['rfid_uid'];
    $data_iuran_per_warga[$rfid_uid] = [
        'rfid_uid' => $row_warga['rfid_uid'],
        'nama_lengkap' => $row_warga['nama_lengkap'],
        'status_bulan' => []
    ];

    for ($m = 1; $m <= 12; $m++) {
        $stmt_status = $conn->prepare("SELECT status FROM status_iuran WHERE rfid_uid = ? AND bulan = ? AND tahun = ?");
        $stmt_status->bind_param("sii", $rfid_uid, $m, $tahun_tampil);
        $stmt_status->execute();
        $result_status = $stmt_status->get_result();

        $status_bulan = 'BELUM LUNAS';
        if ($result_status->num_rows > 0) {
            $status_bulan = $result_status->fetch_assoc()['status'];
        }
        $data_iuran_per_warga[$rfid_uid]['status_bulan'][$m] = $status_bulan;
        $stmt_status->close();
    }
}
$stmt_warga->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Iuran Bulanan Warga - PURI CENDRAWASIH</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        /* Gaya untuk badge status */
        .badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
            color: white;
        }
        .badge-success { background-color: #28a745; }
        .badge-warning { background-color: #ffc107; color: #212529; }
        .badge-danger { background-color: #dc3545; }

        /* Container untuk tabel agar bisa di-scroll horizontal */
        .table-responsive-wrapper {
            overflow-x: auto;
            position: relative;
            margin-bottom: 1.5rem;
        }

        /* Gaya untuk tabel itu sendiri */
        #datatablesSimple {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        #datatablesSimple thead th,
        #datatablesSimple tbody td {
            white-space: nowrap;
            vertical-align: middle;
            padding: 0.75rem;
            border: 1px solid #dee2e6;
        }

        /* Kolom yang tetap (sticky) di sebelah kiri */
        #datatablesSimple thead th:nth-child(1),
        #datatablesSimple tbody td:nth-child(1),
        #datatablesSimple thead th:nth-child(2),
        #datatablesSimple tbody td:nth-child(2),
        #datatablesSimple thead th:nth-child(3),
        #datatablesSimple tbody td:nth-child(3) {
            position: sticky;
            left: 0;
            z-index: 2;
            background-color: #f8f9fa;
            border-right: 2px solid #dee2e6;
        }

        #datatablesSimple thead th:nth-child(1),
        #datatablesSimple tbody td:nth-child(1) {
            left: 0;
        }
        #datatablesSimple thead th:nth-child(2),
        #datatablesSimple tbody td:nth-child(2) {
            left: 50px;
        }
        #datatablesSimple thead th:nth-child(3),
        #datatablesSimple tbody td:nth-child(3) {
            left: 200px;
        }


        /* Kolom Aksi yang tetap (sticky) di sebelah kanan */
        #datatablesSimple thead th:last-child,
        #datatablesSimple tbody td:last-child {
            position: sticky;
            right: 0;
            z-index: 3;
            background-color: #f8f9fa;
            border-left: 2px solid #dee2e6;
            min-width: 160px;
            box-shadow: -2px 0 5px rgba(0,0,0,0.1);
        }

        /* Pastikan elemen di dalam sticky column tidak ikut ter-scroll */
        #datatablesSimple tbody td:last-child .input-group-sm {
            flex-wrap: nowrap;
        }
        #datatablesSimple tbody td:last-child select {
            width: auto !important;
            min-width: 60px;
        }
        #datatablesSimple tbody td:last-child button {
            flex-shrink: 0;
        }

        /* Menyesuaikan lebar header untuk sticky columns agar sinkron */
        #datatablesSimple thead th {
            top: 0;
            z-index: 4;
            background-color: #e9ecef;
        }

        /* Styling untuk menyembunyikan form aksi (display: none) */
        .action-hidden {
            display: none !important;
        }
    </style>
</head>
<body class="sb-nav-fixed">
<?php include 'header.php'; ?>
<div id="layoutSidenav">
    <?php include 'sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4">
                <h1 class="mt-4">Iuran Bulanan Warga</h1>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <form action="" method="GET">
                            <div class="input-group">
                                <label for="tahun" class="input-group-text">Tahun:</label>
                                <select name="tahun" id="tahun" class="form-select" onchange="this.form.submit()">
                                    <?php
                                    $current_year_option = date('Y');
                                    for ($y = $current_year_option; $y >= $current_year_option - 5; $y--) {
                                        echo "<option value='{$y}' " . ($y == $tahun_tampil ? 'selected' : '') . ">{$y}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-table me-1"></i>
                        Status Pembayaran Warga Tahun <?php echo $tahun_tampil; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive-wrapper">
                            <table class="table table-bordered" id="datatablesSimple" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Nama Lengkap</th>
                                        <th>UID RFID</th>
                                        <?php
                                        $months = [
                                            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
                                            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
                                        ];
                                        foreach ($months as $num => $month_name) {
                                            $current_month_style = ($num == $bulan_sekarang && $tahun_tampil == $tahun_sekarang) ? ' style="background-color: #e0f7fa;"' : '';
                                            echo "<th{$current_month_style}>{$month_name}</th>";
                                        }
                                        ?>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; ?>
                                    <?php foreach ($data_iuran_per_warga as $rfid_uid => $data) : ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo htmlspecialchars($data['nama_lengkap']); ?></td>
                                            <td><?php echo htmlspecialchars($data['rfid_uid']); ?></td>
                                            <?php for ($m = 1; $m <= 12; $m++) : ?>
                                                <td>
                                                    <?php
                                                    $status = $data['status_bulan'][$m];
                                                    $badge_class = 'badge ';
                                                    if ($status == 'LUNAS') {
                                                        $badge_class .= 'badge-success';
                                                    } else if ($status == 'BELUM LUNAS' || $status == '2 BULAN BELUM LUNAS') {
                                                        $badge_class .= 'badge-warning';
                                                        $status = 'BELUM LUNAS'; // Pastikan tampilan hanya BELUM LUNAS
                                                    }
                                                    echo "<span class='{$badge_class}'>" . htmlspecialchars($status) . "</span>";
                                                    ?>
                                                </td>
                                            <?php endfor; ?>
                                            <td>
                                                <?php
                                                $hide_action_form_overall = false;
                                                if ($tahun_tampil > $tahun_sekarang) {
                                                    $hide_action_form_overall = true;
                                                }
                                                ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="rfid_uid" value="<?php echo htmlspecialchars($data['rfid_uid']); ?>">
                                                    <input type="hidden" name="tahun_update" value="<?php echo $tahun_tampil; ?>">
                                                    <div class="input-group input-group-sm">
                                                        <select name="bulan_update" class="form-select form-select-sm">
                                                            <?php foreach ($months as $num => $name) : ?>
                                                                <?php
                                                                $disabled = '';
                                                                if ($tahun_tampil == $tahun_sekarang && $num > $bulan_sekarang) {
                                                                    $disabled = 'disabled';
                                                                }
                                                                $selected = '';
                                                                if ($tahun_tampil == $tahun_sekarang && $num == $bulan_sekarang) {
                                                                    $selected = 'selected';
                                                                } else if ($tahun_tampil < $tahun_sekarang && $num == 12) {
                                                                    $selected = 'selected';
                                                                }
                                                                ?>
                                                                <option value="<?php echo $num; ?>" <?php echo $selected; ?> <?php echo $disabled; ?>><?php echo $name; ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <select name="status_baru" class="form-select form-select-sm">
                                                            <option value="LUNAS">LUNAS</option>
                                                            <option value="BELUM LUNAS">BELUM LUNAS</option>
                                                        </select>
                                                        <button type="submit" name="update_iuran" class="btn btn-primary btn-sm" <?php echo $hide_action_form_overall ? 'disabled' : ''; ?>>Simpan</button>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <?php include 'footer.php'; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="js/scripts.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script src="js/datatables-simple-demo.js"></script>
</body>
</html>