<?php
require 'function.php'; // Memuat koneksi dan fungsi dasar
require 'cek.php';    // Memeriksa sesi login pengguna

// Pastikan sesi sudah dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Jika belum login, alihkan ke halaman login
if (!isset($_SESSION['log'])) {
    header("Location: login.php");
    exit;
}

// --- Mengambil Pengaturan Sistem dari Database ---
$query_pengaturan = mysqli_query($conn, "SELECT nama_pengaturan, nilai_pengaturan FROM pengaturan");
$pengaturan = [];
while ($row = mysqli_fetch_assoc($query_pengaturan)) {
    $pengaturan[$row['nama_pengaturan']] = $row['nilai_pengaturan'];
}
// Ambil nominal standar IPL dari pengaturan, set default jika tidak ada
$nominal_iuran_standar = isset($pengaturan['nominal_iuran']) ? (int)$pengaturan['nominal_iuran'] : 150000;

// Tentukan tahun yang akan ditampilkan, defaultnya adalah tahun ini
$tahun_tampil = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

// --- Logika untuk Memastikan Setiap Warga Memiliki Entri untuk Setiap Bulan ---
// Ini penting agar semua warga muncul di tabel meskipun belum pernah membayar
$stmt_warga_all = $conn->prepare("SELECT rfid_uid FROM rfid");
$stmt_warga_all->execute();
$result_warga_all = $stmt_warga_all->get_result();
$semua_warga_rfid = [];
while ($row = $result_warga_all->fetch_assoc()) {
    $semua_warga_rfid[] = $row['rfid_uid'];
}
$stmt_warga_all->close();

// Untuk setiap bulan di tahun yang ditampilkan, pastikan ada baris data untuk setiap warga
for ($m = 1; $m <= 12; $m++) {
    foreach ($semua_warga_rfid as $rfid_uid) {
        // 'INSERT IGNORE' akan mengabaikan perintah jika data sudah ada (berdasarkan UNIQUE KEY rfid_uid, bulan, tahun)
        $stmt_insert_if_not_exists = $conn->prepare("INSERT IGNORE INTO status_iuran (rfid_uid, bulan, tahun, status, jumlah_bayar) VALUES (?, ?, ?, 'BELUM LUNAS', 0)");
        $stmt_insert_if_not_exists->bind_param("sii", $rfid_uid, $m, $tahun_tampil);
        $stmt_insert_if_not_exists->execute();
        $stmt_insert_if_not_exists->close();
    }
}

// --- Proses Form Update Pembayaran IPL ---
if (isset($_POST['update_iuran'])) {
    $rfid_uid_update = $_POST['rfid_uid'];
    $bulan_update = (int)$_POST['bulan_update'];
    $tahun_update = (int)$_POST['tahun_update'];
    // Ambil nominal yang diinput dan bersihkan dari karakter non-numerik
    $jumlah_bayar_input = (int)preg_replace('/[^0-9]/', '', $_POST['jumlah_bayar']);

    // Tentukan status pembayaran baru berdasarkan nominal yang diinput
    if ($jumlah_bayar_input >= $nominal_iuran_standar) {
        $status_baru = 'LUNAS';
    } else if ($jumlah_bayar_input > 0 && $jumlah_bayar_input < $nominal_iuran_standar) {
        $status_baru = 'BELUM LUNAS SEBAGIAN';
    } else {
        $status_baru = 'BELUM LUNAS';
    }

    // Perbarui data di database
    $stmt = $conn->prepare("UPDATE status_iuran SET status = ?, jumlah_bayar = ?, tanggal_pembayaran = NOW() WHERE rfid_uid = ? AND bulan = ? AND tahun = ?");
    $stmt->bind_param("sisii", $status_baru, $jumlah_bayar_input, $rfid_uid_update, $bulan_update, $tahun_update);

    if ($stmt->execute()) {
        echo "<script>alert('Status IPL berhasil diperbarui!'); window.location='iuranpl.php?tahun={$tahun_tampil}';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui status IPL: " . htmlspecialchars($stmt->error) . "'); window.location='iuranpl.php?tahun={$tahun_tampil}';</script>";
    }
    $stmt->close();
    exit();
}

// --- Mengambil Semua Data Iuran untuk Ditampilkan di Tabel ---
$data_iuran_per_warga = [];
$stmt_warga = $conn->prepare("SELECT rfid_uid, nama_lengkap FROM rfid ORDER BY nama_lengkap ASC");
$stmt_warga->execute();
$result_warga = $stmt_warga->get_result();

while ($row_warga = $result_warga->fetch_assoc()) {
    $rfid_uid = $row_warga['rfid_uid'];
    $data_iuran_per_warga[$rfid_uid] = [
        'nama_lengkap' => $row_warga['nama_lengkap'],
        'iuran' => []
    ];
    // Ambil data iuran untuk warga ini pada tahun yang dipilih
    $stmt_iuran = $conn->prepare("SELECT bulan, status, jumlah_bayar FROM status_iuran WHERE rfid_uid = ? AND tahun = ?");
    $stmt_iuran->bind_param("si", $rfid_uid, $tahun_tampil);
    $stmt_iuran->execute();
    $result_iuran = $stmt_iuran->get_result();
    $iuran_warga_per_bulan = [];
    while($row_iuran = $result_iuran->fetch_assoc()){
        // Simpan data iuran per bulan
        $iuran_warga_per_bulan[$row_iuran['bulan']] = [
            'status' => $row_iuran['status'],
            'jumlah_bayar' => $row_iuran['jumlah_bayar']
        ];
    }
    $data_iuran_per_warga[$rfid_uid]['iuran'] = $iuran_warga_per_bulan;
    $stmt_iuran->close();
}
$stmt_warga->close();

// Definisikan nama bulan untuk header tabel
$months = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
    7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Iuran Pengelolaan Lingkungan (IPL) - PURI CENDRAWASIH</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        .table-responsive-wrapper { overflow-x: auto; }
        .table th, .table td { white-space: nowrap; vertical-align: middle; padding: 0.5rem; }
        .badge { font-size: 0.8em; }
        .input-group-sm .form-control { width: 100px; } /* Atur lebar input nominal */
    </style>
</head>
<body class="sb-nav-fixed">
    <?php include 'header.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Iuran Pengelolaan Lingkungan (IPL)</h1>

                    <div class="row mb-3 align-items-center">
                        <!-- Form Filter Tahun -->
                        <div class="col-md-4">
                            <form action="" method="GET">
                                <div class="input-group">
                                    <label for="tahun" class="input-group-text">Tahun:</label>
                                    <select name="tahun" id="tahun" class="form-select" onchange="this.form.submit()">
                                        <?php
                                        $current_year_option = date('Y');
                                        for ($y = $current_year_option + 1; $y >= $current_year_option - 5; $y--) {
                                            echo "<option value='{$y}' " . ($y == $tahun_tampil ? 'selected' : '') . ">{$y}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <!-- Info Nominal IPL -->
                        <div class="col-md-8">
                            <div class="alert alert-info py-2 mb-0">
                                Nominal IPL Saat Ini: <strong>Rp <?php echo number_format($nominal_iuran_standar, 0, ',', '.'); ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            Status Pembayaran Warga Tahun <?php echo $tahun_tampil; ?>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive-wrapper">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No.</th>
                                            <th>Nama Lengkap</th>
                                            <?php foreach ($months as $month_name) { echo "<th>{$month_name}</th>"; } ?>
                                            <th style="min-width: 280px;">Update Pembayaran</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; ?>
                                        <?php foreach ($data_iuran_per_warga as $rfid_uid => $data) : ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo htmlspecialchars($data['nama_lengkap']); ?></td>
                                                <?php for ($m = 1; $m <= 12; $m++) : ?>
                                                    <td>
                                                        <?php
                                                        // Default data jika bulan tersebut belum ada di array
                                                        $status_data = $data['iuran'][$m] ?? ['status' => 'BELUM LUNAS', 'jumlah_bayar' => 0];
                                                        $status = $status_data['status'];
                                                        $jumlah_bayar = $status_data['jumlah_bayar'];
                                                        $badge_class = 'badge ';
                                                        $display_text = '';

                                                        if ($status == 'LUNAS') {
                                                            $badge_class .= 'bg-success';
                                                            $display_text = 'LUNAS';
                                                        } else if ($status == 'BELUM LUNAS SEBAGIAN') {
                                                            $badge_class .= 'bg-warning text-dark';
                                                            $sisa = $nominal_iuran_standar - $jumlah_bayar;
                                                            $display_text = 'Kurang: ' . number_format($sisa, 0, ',', '.');
                                                        } else {
                                                            $badge_class .= 'bg-danger';
                                                            $display_text = 'BELUM BAYAR';
                                                        }
                                                        echo "<span class='{$badge_class}'>" . $display_text . "</span>";
                                                        ?>
                                                    </td>
                                                <?php endfor; ?>
                                                <td>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="rfid_uid" value="<?php echo htmlspecialchars($rfid_uid); ?>">
                                                        <input type="hidden" name="tahun_update" value="<?php echo $tahun_tampil; ?>">
                                                        <div class="input-group input-group-sm">
                                                            <select name="bulan_update" class="form-select">
                                                                <?php foreach ($months as $num => $name) { echo "<option value='{$num}'>{$name}</option>"; } ?>
                                                            </select>
                                                            <input type="text" name="jumlah_bayar" class="form-control" placeholder="Nominal Bayar" required>
                                                            <button type="submit" name="update_iuran" class="btn btn-primary">Simpan</button>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/scripts.js"></script>
</body>
</html>
