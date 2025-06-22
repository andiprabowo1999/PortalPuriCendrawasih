<?php
require 'function.php';
include 'cek.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['log'])) {
    header("Location: login.php");
    exit;
}

// Proses form input RFID jika disubmit (Tambah RFID Baru)
if (isset($_POST['simpan'])) {
    $rfid_uid = $_POST['rfid_uid'];
    $nama_lengkap = $_POST['nama_lengkap'];

    // Cek apakah RFID UID sudah terdaftar di tabel `rfid` (sebelumnya kartu_rfid)
    $stmt_check = mysqli_prepare($conn, "SELECT rfid_uid FROM rfid WHERE rfid_uid = ?");
    mysqli_stmt_bind_param($stmt_check, "s", $rfid_uid);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);

    if (mysqli_stmt_num_rows($stmt_check) == 0) {
        // Jika belum terdaftar, masukkan data baru ke tabel `rfid` dengan status 'aktif'
        // Mengubah nama tabel kartu_rfid menjadi rfid dan status_kartu menjadi status_rfid
        $stmt_insert = mysqli_prepare($conn, "INSERT INTO rfid (rfid_uid, nama_lengkap, status_rfid) VALUES (?, ?, 'aktif')");
        mysqli_stmt_bind_param($stmt_insert, "ss", $rfid_uid, $nama_lengkap);

        if (mysqli_stmt_execute($stmt_insert)) {
            // --- Logika Baru: Isi status_iuran LUNAS sampai bulan sekarang ---
            $current_month = date('n'); // Bulan saat ini (1-12)
            $current_year = date('Y'); // Tahun saat ini

            for ($m = 1; $m <= $current_month; $m++) { // Loop dari Januari sampai bulan saat ini
                $stmt_insert_iuran = mysqli_prepare($conn, "INSERT INTO status_iuran (rfid_uid, bulan, tahun, status, tanggal_pembayaran) VALUES (?, ?, ?, 'LUNAS', NOW())");
                mysqli_stmt_bind_param($stmt_insert_iuran, "sii", $rfid_uid, $m, $current_year);
                mysqli_stmt_execute($stmt_insert_iuran);
                mysqli_stmt_close($stmt_insert_iuran);
            }
            // --- Akhir Logika Baru ---

            echo "<script>alert('RFID berhasil didaftarkan dan iuran diatur lunas sampai bulan ini!'); window.location='registrasiRFID.php';</script>";
        } else {
            echo "<script>alert('Gagal menyimpan data RFID: " . mysqli_error($conn) . "');</script>";
        }
        mysqli_stmt_close($stmt_insert);
    } else {
        echo "<script>alert('RFID ini sudah terdaftar!');</script>";
    }
    mysqli_stmt_close($stmt_check);
}

// Proses nonaktifkan RFID
if (isset($_GET['nonaktifkan']) && isset($_GET['rfid_uid'])) {
    $rfid_uid_to_change = $_GET['rfid_uid'];
    // Update status_rfid menjadi 'tidak_aktif' di tabel rfid
    $stmt_update = mysqli_prepare($conn, "UPDATE rfid SET status_rfid = 'tidak_aktif' WHERE rfid_uid = ?"); // Mengubah nama tabel dan kolom
    mysqli_stmt_bind_param($stmt_update, "s", $rfid_uid_to_change);

    if (mysqli_stmt_execute($stmt_update)) {
        echo "<script>alert('RFID berhasil dinonaktifkan!'); window.location='registrasiRFID.php';</script>";
    } else {
        echo "<script>alert('Gagal menonaktifkan RFID: " . mysqli_error($conn) . "');</script>";
    }
    mysqli_stmt_close($stmt_update);
}

// Proses aktifkan RFID
if (isset($_GET['aktifkan']) && isset($_GET['rfid_uid'])) {
    $rfid_uid_to_change = $_GET['rfid_uid'];
    // Update status_rfid menjadi 'aktif' di tabel rfid
    $stmt_update = mysqli_prepare($conn, "UPDATE rfid SET status_rfid = 'aktif' WHERE rfid_uid = ?"); // Mengubah nama tabel dan kolom
    mysqli_stmt_bind_param($stmt_update, "s", $rfid_uid_to_change);

    if (mysqli_stmt_execute($stmt_update)) {
        echo "<script>alert('RFID berhasil diaktifkan kembali!'); window.location='registrasiRFID.php';</script>";
    } else {
        echo "<script>alert('Gagal mengaktifkan RFID: " . mysqli_error($conn) . "');</script>";
    }
    mysqli_stmt_close($stmt_update);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Registrasi RFID - PURI CENDRAWASIH</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="sb-nav-fixed">
<?php include 'header.php'; // Memasukkan header ?>
<div id="layoutSidenav">
    <?php include 'sidebar.php'; // Memasukkan sidebar ?>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4">
                <h1 class="mt-4">Registrasi RFID</h1>
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-id-card me-1"></i>
                        Form Registrasi RFID
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="rfid_uid" class="form-label">ID RFID</label>
                                <input type="text" class="form-control" id="rfid_uid" name="rfid_uid" placeholder="Contoh: XX YY ZZ AA" required>
                            </div>
                            <div class="mb-3">
                                <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                            </div>
                            <button type="submit" name="simpan" class="btn btn-primary">Simpan</button>
                        </form>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-table me-1"></i>
                        Data RFID Terdaftar
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered" id="datatablesSimple">
                            <thead>
                                <tr>
                                    <th>RFID UID</th>
                                    <th>Nama Lengkap</th>
                                    <th>Status RFID</th> <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Ambil data dari tabel `rfid` (sebelumnya kartu_rfid)
                                // Mengurutkan berdasarkan waktu dibuat_pada (jika ada) atau rfid_uid
                                $data = mysqli_query($conn, "SELECT rfid_uid, nama_lengkap, status_rfid FROM rfid ORDER BY dibuat_pada DESC"); // Mengubah nama tabel dan kolom
                                if (!$data) {
                                    echo "<tr><td colspan='4'>Error: " . mysqli_error($conn) . "</td></tr>";
                                } else {
                                    while ($row = mysqli_fetch_assoc($data)) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['rfid_uid']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['nama_lengkap']) . "</td>";
                                        // Tentukan badge berdasarkan status_rfid (sebelumnya status_kartu)
                                        $badge_class = ($row['status_rfid'] === 'aktif') ? 'success' : 'secondary'; // Mengubah nama kolom
                                        echo "<td><span class='badge bg-" . $badge_class . "'>" . htmlspecialchars($row['status_rfid']) . "</span></td>"; // Mengubah nama kolom
                                        echo "<td>";
                                        // Tombol Aksi (Aktifkan/Nonaktifkan)
                                        if ($row['status_rfid'] === 'aktif') { // Mengubah nama kolom
                                            echo "<a href='registrasiRFID.php?nonaktifkan=true&rfid_uid=" . urlencode($row['rfid_uid']) . "' class='btn btn-warning btn-sm' onclick=\"return confirm('Apakah Anda yakin ingin menonaktifkan RFID ini?');\">Nonaktifkan</a>";
                                        } else {
                                            echo "<a href='registrasiRFID.php?aktifkan=true&rfid_uid=" . urlencode($row['rfid_uid']) . "' class='btn btn-success btn-sm' onclick=\"return confirm('Apakah Anda yakin ingin mengaktifkan RFID ini?');\">Aktifkan</a>";
                                        }
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
        <?php include 'footer.php'; // Memasukkan footer ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="js/scripts.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script src="js/datatables-simple-demo.js"></script>
</body>
</html>