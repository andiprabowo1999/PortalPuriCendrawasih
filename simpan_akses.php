<?php
header("Content-Type: application/json"); // Opsional: Beri tahu klien bahwa respons adalah JSON

// Konfigurasi Database MySQL Anda
$servername = "localhost"; // Karena XAMPP berjalan di PC yang sama
$username = "root";        // Username default XAMPP MySQL
$password = "";            // Password default XAMPP MySQL (kosong)
$dbname = "portal";        // Nama database Anda
$table_name = "dataaksesportal"; // Nama tabel Anda

// Membuat koneksi ke database
$conn = new mysqli($servername, $username, $password, $dbname);

// Memeriksa koneksi
if ($conn->connect_error) {
    // Log error ini ke file atau tampilkan jika debug
    error_log("Koneksi database gagal: " . $conn->connect_error);
    die(json_encode(["status" => "error", "message" => "Koneksi database gagal."]));
}

// Mengambil data dari permintaan POST
// Pastikan nama variabel POST sesuai dengan yang dikirim dari ESP32
$uid = isset($_POST['uid']) ? $conn->real_escape_string($_POST['uid']) : 'N/A';
$arah = isset($_POST['arah']) ? $conn->real_escape_string($_POST['arah']) : 'N/A';
$status = isset($_POST['status']) ? $conn->real_escape_string($_POST['status']) : 'N/A';

$timestamp = date("Y-m-d H:i:s"); // Mendapatkan waktu saat ini

// Query SQL untuk menyimpan data
// Asumsi kolom di tabel dataaksesportal adalah:
// id (AUTO_INCREMENT PRIMARY KEY)
// uid_kartu (VARCHAR)
// arah_akses (VARCHAR)
// status_akses (VARCHAR)
// waktu_akses (DATETIME)
$sql = "INSERT INTO $table_name (uid_kartu, arah_akses, status_akses, waktu_akses)
        VALUES ('$uid', '$arah', '$status', '$timestamp')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["status" => "success", "message" => "Data akses berhasil disimpan"]);
} else {
    // Log error SQL ini
    error_log("Error SQL: " . $sql . " - " . $conn->error);
    echo json_encode(["status" => "error", "message" => "Gagal menyimpan data akses."]);
}

$conn->close();
?>