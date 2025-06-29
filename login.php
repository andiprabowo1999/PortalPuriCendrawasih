<?php
session_start();
require 'function.php'; // Pastikan function.php sudah mengarah ke database yang benar

// Jika sudah login, lempar ke index
if (isset($_SESSION['log']) && $_SESSION['log'] === true) {
    header("Location: index.php");
    exit;
}

// Proses login jika ada POST
if (isset($_POST['login'])) {
    $username = $_POST['username']; // Menggunakan username
    $password = $_POST['password'];

    // Query untuk user berdasarkan username, ambil juga ID-nya
    $query = mysqli_query($conn, "SELECT id, nama_lengkap, username, password FROM user WHERE username='$username'");
    $user = mysqli_fetch_assoc($query);

    // Gunakan password_verify untuk mencocokkan password
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['log'] = true;
        $_SESSION['username'] = $user['username']; // Simpan username di session
        $_SESSION['user_id'] = $user['id']; // <<< TAMBAHAN INI <<< Simpan ID pengguna di session
        $_SESSION['nama_lengkap'] = $user['nama_lengkap']; // Opsional: simpan nama lengkap
        header("Location: index.php");
        exit;
    } else {
        echo "<script>alert('Username atau password salah!'); window.location='login.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Login - Puri Cendrawasih</title>
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    </head>
    <body class="bg-primary">
        <div id="layoutAuthentication">
            <div id="layoutAuthentication_content">
                <main>
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-lg-5">
                                <div class="card shadow-lg border-0 rounded-lg mt-5">
                                    <div class="card-header"><h3 class="text-center font-weight-light my-4">Login Puri Cendrawasih</h3></div>
                                    <div class="card-body">
                                        <form method="post">
                                            <div class="form-floating mb-3">
                                                <input class="form-control" name="username" id="inputUsername" type="text" placeholder="Username Anda" required />
                                                <label for="inputUsername">Username</label>
                                            </div>
                                            <div class="form-floating mb-3">
                                                <input class="form-control" name="password" id="inputPassword" type="password" placeholder="Password" required />
                                                <label for="inputPassword">Kata Sandi</label>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                                                <button class="btn btn-primary" name="login">Login</button>
                                            </div>
                                        </form>
                                    </div>
                                    </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
    </body>
</html>