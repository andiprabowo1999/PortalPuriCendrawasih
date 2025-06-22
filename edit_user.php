<?php
session_start(); // Pastikan session_start() sudah dipanggil
require 'function.php';
include 'cek.php';

// Verifikasi peran pengguna
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header("Location: index.php"); // Arahkan kembali ke dashboard jika bukan admin
    exit;
}

$message = '';
$user_data = null;

// Ambil ID pengguna dari URL
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];
    // Gunakan prepared statement untuk keamanan
    $stmt = mysqli_prepare($conn, "SELECT id, nama_lengkap, username FROM user WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$user_data) {
        $message = "<div class='alert alert-danger'>Pengguna tidak ditemukan!</div>";
    }
} else {
    header('Location: admin_panel.php'); // Redirect jika tidak ada ID
    exit;
}

// --- Logika Edit Pengguna ---
if (isset($_POST['update_user'])) {
    $id_to_update = $_POST['user_id'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $username = $_POST['username'];
    $new_password = $_POST['password']; // Password baru (opsional)
    $password_confirm = $_POST['password_confirm']; // Konfirmasi password baru

    // Validasi input
    if (empty($nama_lengkap) || empty($username)) {
        $message = "<div class='alert alert-danger'>Nama Lengkap dan Username tidak boleh kosong!</div>";
    } elseif (!empty($new_password) && $new_password !== $password_confirm) {
        $message = "<div class='alert alert-danger'>Kata Sandi baru dan Konfirmasi Kata Sandi tidak cocok!</div>";
    } else {
        // Cek apakah username baru sudah ada (selain user yang sedang diedit)
        $stmt_check = mysqli_prepare($conn, "SELECT id FROM user WHERE username = ? AND id != ?");
        mysqli_stmt_bind_param($stmt_check, "si", $username, $id_to_update);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $message = "<div class='alert alert-danger'>Username sudah ada, silakan pilih yang lain.</div>";
        } else {
            $sql = "UPDATE user SET nama_lengkap = ?, username = ?";
            $params = [$nama_lengkap, $username];
            $types = "ss";

            // Jika ada password baru, hash dan tambahkan ke query
            if (!empty($new_password)) {
                $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                $sql .= ", password = ?";
                $params[] = $hashedPassword;
                $types .= "s";
            }

            $sql .= " WHERE id = ?";
            $params[] = $id_to_update;
            $types .= "i";

            $stmt_update = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt_update, $types, ...$params);

            if (mysqli_stmt_execute($stmt_update)) {
                $message = "<div class='alert alert-success'>Pengguna berhasil diperbarui!</div>";
                // Perbarui data yang ditampilkan setelah update
                $query = mysqli_query($conn, "SELECT id, nama_lengkap, username FROM user WHERE id = '$id_to_update'");
                $user_data = mysqli_fetch_assoc($query);
            } else {
                $message = "<div class='alert alert-danger'>Gagal memperbarui pengguna: " . mysqli_error($conn) . "</div>";
            }
            mysqli_stmt_close($stmt_update);
        }
        mysqli_stmt_close($stmt_check);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Edit Pengguna - Panel Admin</title>
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="index.php">Portal RT</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user fa-fw"></i></a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
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
                            Manajemen Pengguna
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
                    <h1 class="mt-4">Edit Pengguna</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="admin_panel.php">Manajemen Pengguna</a></li>
                        <li class="breadcrumb-item active">Edit Pengguna</li>
                    </ol>

                    <?php echo $message; ?>

                    <?php if ($user_data) : ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-user-edit me-1"></i>
                                Edit Data Pengguna: <?php echo htmlspecialchars($user_data['nama_lengkap']); ?>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_data['id']); ?>">
                                    <div class="form-floating mb-3">
                                        <input class="form-control" id="inputNama" name="nama_lengkap" type="text" value="<?php echo htmlspecialchars($user_data['nama_lengkap']); ?>" required />
                                        <label for="inputNama">Nama Lengkap</label>
                                    </div>
                                    <div class="form-floating mb-3">
                                        <input class="form-control" id="inputUsername" name="username" type="text" value="<?php echo htmlspecialchars($user_data['username']); ?>" required />
                                        <label for="inputUsername">Username</label>
                                    </div>
                                    <div class="form-floating mb-3">
                                        <input class="form-control" id="inputNewPassword" name="password" type="password" placeholder="Biarkan kosong jika tidak ingin mengubah password" />
                                        <label for="inputNewPassword">Kata Sandi Baru (kosongkan jika tidak ingin mengubah)</label>
                                    </div>
                                    <div class="form-floating mb-3">
                                        <input class="form-control" id="inputPasswordConfirm" name="password_confirm" type="password" placeholder="Konfirmasi Kata Sandi Baru" />
                                        <label for="inputPasswordConfirm">Konfirmasi Kata Sandi Baru</label>
                                    </div>
                                    <div class="mt-4 mb-0">
                                        <div class="d-grid">
                                            <button class="btn btn-primary" type="submit" name="update_user">Perbarui Pengguna</button>
                                            <a href="admin_panel.php" class="btn btn-secondary mt-2">Kembali ke Daftar Pengguna</a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
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