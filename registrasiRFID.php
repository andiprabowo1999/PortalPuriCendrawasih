<?php
require 'function.php';
require 'cek.php';

// Proses Tambah Kepala Keluarga Baru
if (isset($_POST['tambah_kk'])) {
    $nama_kk = $_POST['nama_kepala_keluarga'];
    $no_rumah = $_POST['nomor_rumah'];

    $stmt = $conn->prepare("INSERT INTO kepala_keluarga (nama_kepala_keluarga, nomor_rumah) VALUES (?, ?)");
    $stmt->bind_param("ss", $nama_kk, $no_rumah);
    
    if ($stmt->execute()) {
        // ---- LOGIKA BARU DIMULAI DI SINI ----
        
        // 1. Dapatkan ID dari KK yang baru saja ditambahkan
        $id_kk_baru = mysqli_insert_id($conn);
        
        // 2. Dapatkan bulan dan tahun saat ini
        $bulan_sekarang = date('n');
        $tahun_sekarang = date('Y');
        
        // 3. Ambil nominal iuran standar untuk dicatat
        $query_nominal = mysqli_query($conn, "SELECT nilai_pengaturan FROM pengaturan WHERE nama_pengaturan = 'nominal_iuran'");
        $nominal_iuran_standar = mysqli_fetch_assoc($query_nominal)['nilai_pengaturan'] ?? 150000;

        // 4. Loop dari Januari sampai bulan ini, dan set status 'LUNAS'
        for ($m = 1; $m <= $bulan_sekarang; $m++) {
            $stmt_iuran = $conn->prepare(
                "INSERT INTO status_iuran (id_kk, bulan, tahun, status, jumlah_bayar, tanggal_pembayaran) 
                 VALUES (?, ?, ?, 'LUNAS', ?, NOW())"
            );
            $stmt_iuran->bind_param("iisi", $id_kk_baru, $m, $tahun_sekarang, $nominal_iuran_standar);
            $stmt_iuran->execute();
        }
        $stmt_iuran->close();
        
        // ---- LOGIKA BARU SELESAI ----

        echo "<script>alert('Kepala Keluarga berhasil ditambahkan dan IPL telah diatur lunas hingga bulan ini!'); window.location='registrasiRFID.php';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan KK: " . htmlspecialchars($stmt->error) . "');</script>";
    }
    $stmt->close();
}

// Proses Tambah RFID baru ke KK yang sudah ada
if (isset($_POST['tambah_rfid'])) {
    $id_kk = $_POST['id_kk'];
    $rfid_uid = $_POST['rfid_uid'];
    $nama_pemegang = $_POST['nama_pemegang'];

    $stmt_check = $conn->prepare("SELECT id_kk FROM rfid WHERE rfid_uid = ?");
    $stmt_check->bind_param("s", $rfid_uid);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        echo "<script>alert('RFID UID ini sudah terdaftar di KK lain!');</script>";
    } else {
        $stmt_insert = $conn->prepare("INSERT INTO rfid (rfid_uid, id_kk, nama_lengkap, status_rfid) VALUES (?, ?, ?, 'aktif')");
        $stmt_insert->bind_param("sis", $rfid_uid, $id_kk, $nama_pemegang);
        
        if ($stmt_insert->execute()) {
            echo "<script>alert('Kartu RFID berhasil ditambahkan ke KK!'); window.location='registrasiRFID.php';</script>";
        } else {
            echo "<script>alert('Gagal menambahkan RFID: " . htmlspecialchars($stmt_insert->error) . "');</script>";
        }
        $stmt_insert->close();
    }
    $stmt_check->close();
}

// Proses ubah status rfid (aktif/tidak aktif)
if(isset($_GET['action']) && isset($_GET['rfid_uid'])){
    $rfid_uid_to_change = $_GET['rfid_uid'];
    $new_status = $_GET['action'] === 'nonaktifkan' ? 'tidak_aktif' : 'aktif';
    $stmt = $conn->prepare("UPDATE rfid SET status_rfid = ? WHERE rfid_uid = ?");
    $stmt->bind_param("ss", $new_status, $rfid_uid_to_change);
    if($stmt->execute()){
         echo "<script>alert('Status RFID berhasil diubah!'); window.location='registrasiRFID.php';</script>";
    } else {
         echo "<script>alert('Gagal mengubah status RFID.');</script>";
    }
    $stmt->close();
}

// Ambil semua data KK dan RFID yang terkait untuk ditampilkan
$data_kk = [];
$result_kk = mysqli_query($conn, "SELECT * FROM kepala_keluarga ORDER BY nama_kepala_keluarga ASC");
while ($row_kk = mysqli_fetch_assoc($result_kk)) {
    $id_kk = $row_kk['id_kk'];
    $data_kk[$id_kk] = $row_kk;
    $data_kk[$id_kk]['rfid_list'] = [];

    $stmt_rfid = $conn->prepare("SELECT rfid_uid, nama_lengkap, status_rfid FROM rfid WHERE id_kk = ?");
    $stmt_rfid->bind_param("i", $id_kk);
    $stmt_rfid->execute();
    $result_rfid = $stmt_rfid->get_result();
    while ($row_rfid = $result_rfid->fetch_assoc()) {
        $data_kk[$id_kk]['rfid_list'][] = $row_rfid;
    }
    $stmt_rfid->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Manajemen KK & Registrasi RFID - PURI CENDRAWASIH</title>
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="sb-nav-fixed">
    <?php include 'header.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Registrasi RFID</h1>
                    
                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-user-plus me-1"></i> Tambah Kepala Keluarga Baru</div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row g-2">
                                    <div class="col-md">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="namaKK" name="nama_kepala_keluarga" placeholder="Nama Kepala Keluarga" required>
                                            <label for="namaKK">Nama Kepala Keluarga</label>
                                        </div>
                                    </div>
                                    <div class="col-md">
                                         <div class="form-floating">
                                            <input type="text" class="form-control" id="noRumah" name="nomor_rumah" placeholder="Nomor Rumah" required>
                                            <label for="noRumah">Nomor Rumah (Contoh: A1/05)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-auto">
                                        <button type="submit" name="tambah_kk" class="btn btn-primary h-100">Tambah KK</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-users me-1"></i> Daftar Kepala Keluarga dan Kartu RFID Terdaftar</div>
                        <div class="card-body">
                            <div class="accordion" id="accordionKK">
                                <?php if (empty($data_kk)): ?>
                                    <p class="text-center text-muted">Belum ada data Kepala Keluarga. Silakan tambahkan dari form di atas.</p>
                                <?php else: ?>
                                    <?php foreach ($data_kk as $id_kk => $kk): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading<?php echo $id_kk; ?>">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $id_kk; ?>">
                                                <strong><?php echo htmlspecialchars($kk['nama_kepala_keluarga']); ?></strong>&nbsp;(<?php echo htmlspecialchars($kk['nomor_rumah']); ?>)
                                            </button>
                                        </h2>
                                        <div id="collapse<?php echo $id_kk; ?>" class="accordion-collapse collapse" data-bs-parent="#accordionKK">
                                            <div class="accordion-body">
                                                <h6>Kartu RFID Terdaftar</h6>
                                                <?php if(empty($kk['rfid_list'])): ?>
                                                    <p class="text-muted fst-italic">Belum ada kartu RFID yang terdaftar untuk KK ini.</p>
                                                <?php else: ?>
                                                <table class="table table-sm table-bordered table-striped">
                                                    <thead class="table-light"><tr><th>UID RFID</th><th>Nama Pemegang</th><th>Status</th><th>Aksi</th></tr></thead>
                                                    <tbody>
                                                        <?php foreach ($kk['rfid_list'] as $rfid): ?>
                                                            <tr>
                                                                <td><code><?php echo htmlspecialchars($rfid['rfid_uid']); ?></code></td>
                                                                <td><?php echo htmlspecialchars($rfid['nama_lengkap']); ?></td>
                                                                <td>
                                                                    <?php $badge_class = ($rfid['status_rfid'] === 'aktif') ? 'success' : 'secondary'; ?>
                                                                    <span class="badge bg-<?php echo $badge_class; ?>"><?php echo htmlspecialchars($rfid['status_rfid']); ?></span>
                                                                </td>
                                                                <td>
                                                                    <?php if ($rfid['status_rfid'] === 'aktif'): ?>
                                                                        <a href="registrasiRFID.php?action=nonaktifkan&rfid_uid=<?php echo urlencode($rfid['rfid_uid']); ?>" class="btn btn-warning btn-sm" onclick="return confirm('Anda yakin ingin menonaktifkan kartu ini?');">Nonaktifkan</a>
                                                                    <?php else: ?>
                                                                        <a href="registrasiRFID.php?action=aktifkan&rfid_uid=<?php echo urlencode($rfid['rfid_uid']); ?>" class="btn btn-success btn-sm" onclick="return confirm('Anda yakin ingin mengaktifkan kembali kartu ini?');">Aktifkan</a>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                                <?php endif; ?>
                                                <hr>
                                                <h6>Tambah RFID Baru untuk KK Ini</h6>
                                                <form method="POST">
                                                    <input type="hidden" name="id_kk" value="<?php echo $id_kk; ?>">
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" name="rfid_uid" placeholder="Scan atau ketik UID RFID baru" required>
                                                        <input type="text" class="form-control" name="nama_pemegang" placeholder="Nama Pemegang Kartu" required>
                                                        <button type="submit" name="tambah_rfid" class="btn btn-info">Tambahkan Kartu</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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