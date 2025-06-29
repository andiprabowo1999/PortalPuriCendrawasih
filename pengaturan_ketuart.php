// pengaturan_ketuart.php
<?php
require 'function.php';
require 'cek.php';

// Pastikan hanya 'ketuart' yang bisa akses
if ($_SESSION['username'] !== 'ketuart') {
    header("Location: index.php");
    exit;
}

// Logika untuk update pengaturan
if (isset($_POST['simpan_pengaturan'])) {
    $batas_tunggakan = $_POST['batas_tunggakan'];
    $nominal_iuran = $_POST['nominal_iuran'];

    // Update batas tunggakan
    $stmt1 = $conn->prepare("UPDATE pengaturan SET nilai_pengaturan = ? WHERE nama_pengaturan = 'batas_tunggakan_bulan'");
    $stmt1->bind_param("s", $batas_tunggakan);
    $stmt1->execute();

    // Update nominal iuran
    $stmt2 = $conn->prepare("UPDATE pengaturan SET nilai_pengaturan = ? WHERE nama_pengaturan = 'nominal_iuran'");
    $stmt2->bind_param("s", $nominal_iuran);
    $stmt2->execute();

    echo "<script>alert('Pengaturan berhasil disimpan!');</script>";
}

// Ambil data pengaturan saat ini
$query_pengaturan = mysqli_query($conn, "SELECT * FROM pengaturan");
$pengaturan = [];
while ($row = mysqli_fetch_assoc($query_pengaturan)) {
    $pengaturan[$row['nama_pengaturan']] = $row['nilai_pengaturan'];
}

// Ambil data statistik untuk dashboard
$total_kk = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM rfid"));
$bulan_ini = date('n');
$tahun_ini = date('Y');
$sudah_bayar = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM status_iuran WHERE bulan = $bulan_ini AND tahun = $tahun_ini AND status = 'LUNAS'"));
$belum_bayar = $total_kk - $sudah_bayar;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Pengaturan & Dashboard Ketua RT</title>
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
                    <h1 class="mt-4">Dashboard & Pengaturan Ketua RT</h1>
                    
                    <div class="row">
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-primary text-white mb-4">
                                <div class="card-body">Jumlah KK: <?php echo $total_kk; ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-success text-white mb-4">
                                <div class="card-body">Sudah Membayar (Bulan Ini): <?php echo $sudah_bayar; ?></div>
                            </div>
                        </div>
                         <div class="col-xl-3 col-md-6">
                            <div class="card bg-danger text-white mb-4">
                                <div class="card-body">Belum Membayar (Bulan Ini): <?php echo $belum_bayar; ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">Ubah Pengaturan Sistem</div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="batas_tunggakan" class="form-label">Batas Tunggakan (Bulan)</label>
                                    <select name="batas_tunggakan" id="batas_tunggakan" class="form-select">
                                        <option value="1" <?php echo ($pengaturan['batas_tunggakan_bulan'] == '1') ? 'selected' : ''; ?>>1 Bulan</option>
                                        <option value="2" <?php echo ($pengaturan['batas_tunggakan_bulan'] == '2') ? 'selected' : ''; ?>>2 Bulan</option>
                                        <option value="3" <?php echo ($pengaturan['batas_tunggakan_bulan'] == '3') ? 'selected' : ''; ?>>3 Bulan</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="nominal_iuran" class="form-label">Nominal Iuran (Rp)</label>
                                    <input type="number" class="form-control" id="nominal_iuran" name="nominal_iuran" value="<?php echo $pengaturan['nominal_iuran']; ?>">
                                </div>
                                <button type="submit" name="simpan_pengaturan" class="btn btn-primary">Simpan</button>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
            <?php include 'footer.php'; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
</body>
</html>