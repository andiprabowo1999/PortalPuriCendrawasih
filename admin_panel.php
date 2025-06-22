<?php
session_start(); // Pastikan session_start() sudah dipanggil
require 'function.php'; // Pastikan ini mengarah ke file koneksi database Anda
include 'cek.php';    // Memastikan hanya yang login yang bisa akses

// Verifikasi peran pengguna
// Jika bukan admin, arahkan ke halaman lain atau tampilkan pesan error
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') { //
    header("Location: index.php"); // Arahkan kembali ke dashboard jika bukan admin
    exit;
}

// Inisialisasi pesan
$message = '';

// --- Logika Tambah Pengguna ---
if (isset($_POST['add_user'])) { //
    $nama_lengkap = $_POST['nama_lengkap']; //
    $username = $_POST['username']; //
    $password = $_POST['password']; //
    $password_confirm = $_POST['password_confirm']; // Tambahkan ini untuk konfirmasi

    // Validasi sederhana
    if (empty($nama_lengkap) || empty($username) || empty($password) || empty($password_confirm)) { //
        $message = "<div class='alert alert-danger'>Semua kolom harus diisi!</div>"; //
    } elseif ($password !== $password_confirm) { //
        $message = "<div class='alert alert-danger'>Kata Sandi dan Konfirmasi Kata Sandi tidak cocok!</div>"; //
    } else {
        // Cek apakah username sudah ada
        $stmt_check = mysqli_prepare($conn, "SELECT id FROM user WHERE username = ?"); //
        mysqli_stmt_bind_param($stmt_check, "s", $username); //
        mysqli_stmt_execute($stmt_check); //
        mysqli_stmt_store_result($stmt_check); //
        if (mysqli_stmt_num_rows($stmt_check) > 0) { //
            $message = "<div class='alert alert-danger'>Username sudah ada, silakan pilih yang lain.</div>"; //
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT); //
            // Gunakan prepared statement untuk keamanan yang lebih baik
            $stmt = mysqli_prepare($conn, "INSERT INTO user (nama_lengkap, username, password) VALUES (?, ?, ?)"); //
            mysqli_stmt_bind_param($stmt, "sss", $nama_lengkap, $username, $hashedPassword); //

            if (mysqli_stmt_execute($stmt)) { //
                $message = "<div class='alert alert-success'>Pengguna berhasil ditambahkan!</div>"; //
            } else {
                $message = "<div class='alert alert-danger'>Gagal menambahkan pengguna: " . mysqli_error($conn) . "</div>"; //
            }
            mysqli_stmt_close($stmt); //
        }
        mysqli_stmt_close($stmt_check); //
    }
}

// --- Logika Hapus Pengguna ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) { //
    $id_to_delete = $_GET['id']; //

    // Penting: Cegah pengguna menghapus akunnya sendiri
    // $_SESSION['user_id'] kini diasumsikan ada setelah login.
    if (isset($_SESSION['user_id']) && $id_to_delete == $_SESSION['user_id']) { //
        $message = "<div class='alert alert-danger'>Anda tidak bisa menghapus akun Anda sendiri!</div>"; //
    } else {
        // Gunakan prepared statement untuk keamanan
        $stmt = mysqli_prepare($conn, "DELETE FROM user WHERE id = ?"); //
        mysqli_stmt_bind_param($stmt, "i", $id_to_delete); //

        if (mysqli_stmt_execute($stmt)) { //
            $message = "<div class='alert alert-success'>Pengguna berhasil dihapus!</div>"; //
        } else {
            $message = "<div class='alert alert-danger'>Gagal menghapus pengguna: " . mysqli_error($conn) . "</div>"; //
        }
        mysqli_stmt_close($stmt); //
    }
}

// Ambil semua pengguna untuk ditampilkan
$users_query = mysqli_query($conn, "SELECT id, nama_lengkap, username FROM user ORDER BY id ASC"); //
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Panel Admin - Manajemen Pengguna RT</title>
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="sb-nav-fixed">
    <?php include 'header.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Manajemen Pengguna RT</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Manajemen Pengguna</li>
                    </ol>

                    <?php echo $message; // Menampilkan pesan sukses/gagal ?>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-user-plus me-1"></i>
                            Tambah Pengguna Baru
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
                                        <button class="btn btn-primary" type="submit" name="add_user">Tambah Pengguna</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            Daftar Pengguna
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
                                                <a href="admin_panel.php?action=delete&id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?');">Hapus</a>
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
                        <div class="text-muted">Copyright &copy; Portal RT Anda 2025</div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    </body>
</html>