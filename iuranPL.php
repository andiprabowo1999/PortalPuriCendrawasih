<?php
// NAMA FILE: iuranpl.php

require 'function.php';
require 'cek.php';

// --- Mengambil Pengaturan Sistem ---
$query_pengaturan = mysqli_query($conn, "SELECT nama_pengaturan, nilai_pengaturan FROM pengaturan");
$pengaturan = [];
while ($row = mysqli_fetch_assoc($query_pengaturan)) {
    $pengaturan[$row['nama_pengaturan']] = $row['nilai_pengaturan'];
}
$nominal_iuran_standar = isset($pengaturan['nominal_iuran']) ? (int)$pengaturan['nominal_iuran'] : 150000;

$tahun_tampil = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

// --- Proses Form Update Pembayaran IPL ---
if (isset($_POST['update_iuran'])) {
    $id_kk_update = $_POST['id_kk'];
    $bulan_update = (int)$_POST['bulan_update'];
    $jumlah_bayar_input = (int)preg_replace('/[^0-9]/', '', $_POST['jumlah_bayar']);

    // Tentukan status pembayaran baru
    if ($jumlah_bayar_input >= $nominal_iuran_standar) {
        $status_baru = 'LUNAS';
    } else if ($jumlah_bayar_input > 0) {
        $status_baru = 'BELUM LUNAS SEBAGIAN';
    } else {
        $status_baru = 'BELUM LUNAS';
    }
    
    // Gunakan INSERT ... ON DUPLICATE KEY UPDATE untuk efisiensi.
    // Ini akan membuat baris baru jika belum ada, atau memperbarui jika sudah ada.
    $stmt = $conn->prepare("
        INSERT INTO status_iuran (id_kk, bulan, tahun, status, jumlah_bayar, tanggal_pembayaran) 
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE status = VALUES(status), jumlah_bayar = VALUES(jumlah_bayar), tanggal_pembayaran = NOW()
    ");
    $stmt->bind_param("iissi", $id_kk_update, $bulan_update, $tahun_tampil, $status_baru, $jumlah_bayar_input);
    
    if ($stmt->execute()) {
        echo "<script>alert('Status IPL berhasil diperbarui!'); window.location='iuranpl.php?tahun={$tahun_tampil}';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui status: " . htmlspecialchars($stmt->error) . "');</script>";
    }
    $stmt->close();
    exit();
}

// --- Mengambil Data untuk Tampilan Tabel ---
$data_ipl = [];
$result_kk_list = mysqli_query($conn, "SELECT id_kk, nama_kepala_keluarga, nomor_rumah FROM kepala_keluarga ORDER BY nama_kepala_keluarga ASC");
while ($row_kk = mysqli_fetch_assoc($result_kk_list)) {
    $id_kk = $row_kk['id_kk'];
    $data_ipl[$id_kk] = [
        'nama_kepala_keluarga' => $row_kk['nama_kepala_keluarga'],
        'nomor_rumah' => $row_kk['nomor_rumah'],
        'iuran' => []
    ];
    // Ambil semua data iuran untuk KK ini dalam satu query
    $stmt_iuran = $conn->prepare("SELECT bulan, status, jumlah_bayar FROM status_iuran WHERE id_kk = ? AND tahun = ?");
    $stmt_iuran->bind_param("ii", $id_kk, $tahun_tampil);
    $stmt_iuran->execute();
    $result_iuran = $stmt_iuran->get_result();
    while ($row_iuran = $result_iuran->fetch_assoc()) {
        $data_ipl[$id_kk]['iuran'][$row_iuran['bulan']] = $row_iuran;
    }
    $stmt_iuran->close();
}

$months = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Iuran Pengelolaan Lingkungan (IPL) - PURI CENDRAWASIH</title>
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        .table-responsive-wrapper { overflow-x: auto; }
        .table th, .table td { white-space: nowrap; vertical-align: middle; padding: 0.5rem; text-align: center;}
        .table th:nth-child(2), .table td:nth-child(2) { text-align: left; }
        .badge { font-size: 0.8em; }
        .form-control-sm { min-width: 120px; }
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
                        <div class="col-md-8">
                            <div class="alert alert-info py-2 mb-0">
                                Nominal IPL Standar: <strong>Rp <?php echo number_format($nominal_iuran_standar, 0, ',', '.'); ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-table me-1"></i>Status Pembayaran Warga Tahun <?php echo $tahun_tampil; ?></div>
                        <div class="card-body">
                            <div class="table-responsive-wrapper">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No.</th>
                                            <th style="text-align: left;">Nama Kepala Keluarga</th>
                                            <th>No. Rumah</th>
                                            <?php foreach ($months as $month_name) { echo "<th>{$month_name}</th>"; } ?>
                                            <th style="min-width: 300px;">Update Pembayaran</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($data_ipl)): ?>
                                            <tr><td colspan="16" class="text-center">Belum ada data Kepala Keluarga. Silakan tambahkan di halaman Registrasi RFID.</td></tr>
                                        <?php else: ?>
                                            <?php $no = 1; foreach ($data_ipl as $id_kk => $data) : ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo htmlspecialchars($data['nama_kepala_keluarga']); ?></td>
                                                    <td><?php echo htmlspecialchars($data['nomor_rumah']); ?></td>
                                                    <?php for ($m = 1; $m <= 12; $m++) : ?>
                                                        <td>
                                                            <?php
                                                            $status_data = $data['iuran'][$m] ?? ['status' => 'BELUM LUNAS', 'jumlah_bayar' => 0];
                                                            $status = $status_data['status'];
                                                            $jumlah_bayar = $status_data['jumlah_bayar'];
                                                            $badge_class = 'badge ';
                                                            
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
                                                            echo "<span class='{$badge_class}'>$display_text</span>";
                                                            ?>
                                                        </td>
                                                    <?php endfor; ?>
                                                    <td>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="id_kk" value="<?php echo $id_kk; ?>">
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
                                        <?php endif; ?>
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