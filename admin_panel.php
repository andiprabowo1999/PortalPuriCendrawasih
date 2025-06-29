<?php
session_start(); // Pastikan session_start() sudah dipanggil
require 'function.php'; // Pastikan ini mengarah ke file koneksi database Anda
include 'cek.php';    // Memastikan hanya yang login yang bisa akses

// Verifikasi peran pengguna
// Jika bukan admin, arahkan ke halaman lain atau tampilkan pesan error
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header("Location: index.php"); // Arahkan kembali ke dashboard jika bukan admin
    exit;
}

// Inisialisasi pesan
$message = '';

// --- Logika Tambah Pengguna ---
if (isset($_POST['add_user'])) {
    $nama_lengkap = $_POST['nama_lengkap'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm']; // Tambahkan ini untuk konfirmasi

    // Validasi sederhana
    if (empty($nama_lengkap) || empty($username) || empty($password) || empty($password_confirm)) {
        $message = "<div class='alert alert-danger'>Semua kolom harus diisi!</div>";
    } elseif ($password !== $password_confirm) {
        $message = "<div class='alert alert-danger'>Kata Sandi dan Konfirmasi Kata Sandi tidak cocok!</div>";
    } else {
        // Cek apakah username sudah ada
        $stmt_check = mysqli_prepare($conn, "SELECT id FROM user WHERE username = ?");
        mysqli_stmt_bind_param($stmt_check, "s", $username);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $message = "<div class='alert alert-danger'>Username sudah ada, silakan pilih yang lain.</div>";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            // Gunakan prepared statement untuk keamanan yang lebih baik
            $stmt = mysqli_prepare($conn, "INSERT INTO user (nama_lengkap, username, password) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $nama_lengkap, $username, $hashedPassword);

            if (mysqli_stmt_execute($stmt)) {
                $message = "<div class='alert alert-success'>User berhasil ditambahkan!</div>"; // Diubah
            } else {
                $message = "<div class='alert alert-danger'>Gagal menambahkan user: " . mysqli_error($conn) . "</div>"; // Diubah
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_stmt_close($stmt_check);
    }
}

// --- Logika Hapus Pengguna ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = $_GET['id'];

    // Penting: Cegah pengguna menghapus akunnya sendiri
    if (isset($_SESSION['user_id']) && $id_to_delete == $_SESSION['user_id']) {
        $message = "<div class='alert alert-danger'>Anda tidak bisa menghapus akun Anda sendiri!</div>";
    } else {
        // Gunakan prepared statement untuk keamanan
        $stmt = mysqli_prepare($conn, "DELETE FROM user WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id_to_delete);

        if (mysqli_stmt_execute($stmt)) {
            $message = "<div class='alert alert-success'>User berhasil dihapus!</div>"; // Diubah
        } else {
            $message = "<div class='alert alert-danger'>Gagal menghapus user: " . mysqli_error($conn) . "</div>"; // Diubah
        }
        mysqli_stmt_close($stmt);
    }
}

// Ambil semua pengguna untuk ditampilkan
$users_query = mysqli_query($conn, "SELECT id, nama_lengkap, username FROM user ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Panel Admin - Manajemen User</title> <!-- Diubah -->
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="index.php">PURI CENDRAWASIH</a> <!-- Diubah -->
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
        <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
            </form>
        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user fa-fw"></i></a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><hr class="dropdown-divider" /></li>
                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                </ul>
            </li>
        </ul>
    </nav>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <div class="sb-sidenav-menu-heading">Core</div>
                        <a class="nav-link" href="index.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Dashboard
                        </a>
                        <div class="sb-sidenav-menu-heading">Admin Panel</div>
                        <a class="nav-link active" href="admin_panel.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-users-cog"></i></div>
                            Manajemen User <!-- Diubah -->
                        </a>
                        <a class="nav-link" href="registrasiRFID.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-id-card"></i></div> Registrasi RFID
                        </a>
                        <a class="nav-link" href="iuranBulananWarga.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-money-bill-wave"></i></div> Iuran Bulanan Warga
                        </a>
                        </div>
                </div>
                <div class="sb-sidenav-footer">
                    <div class="small">Logged in as:</div>
                    <?php echo htmlspecialchars($_SESSION['username']); ?>
                </div>
            </nav>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Manajemen User</h1> <!-- Diubah: Menghilangkan "RT" -->
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Manajemen User</li> <!-- Diubah -->
                    </ol>

                    <?php echo $message; // Menampilkan pesan sukses/gagal ?>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-user-plus me-1"></i>
                            Tambah User Baru <!-- Diubah -->
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3 mb-md-0">
                                            <input class="form-control" id="inputNama" name="nama_lengkap" type="text" placeholder="Nama Lengkap" required />
                                            <label for="inputNama">Nama Lengkap</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input class="form-control" id="inputUsername" name="username" type="text" placeholder="Username" required />
                                            <label for="inputUsername">Username</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3 mb-md-0">
                                            <input class="form-control" id="inputPassword" name="password" type="password" placeholder="Kata Sandi" required />
                                            <label for="inputPassword">Kata Sandi Awal</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3 mb-md-0">
                                            <input class="form-control" id="inputPasswordConfirm" name="password_confirm" type="password" placeholder="Konfirmasi Kata Sandi" required />
                                            <label for="inputPasswordConfirm">Konfirmasi Kata Sandi Awal</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 mb-0">
                                    <div class="d-grid">
                                        <button class="btn btn-primary" type="submit" name="add_user">Tambah User</button> <!-- Diubah -->
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            Daftar User <!-- Diubah -->
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nama Lengkap</th>
                                        <th>Username</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($users_query)) : ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                                            <td>
                                                <a href="edit_user.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-warning btn-sm">Edit</a>
                                                <a href="admin_panel.php?action=delete&id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?');">Hapus</a> <!-- Diubah -->
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; PURI CENDRAWASIH 2025</div> <!-- Diubah -->
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    </body>
</html>