<?php
// insert_log.php
// Pastikan ini adalah baris pertama file, tanpa spasi/newline di atasnya.
header('Content-Type: application/json'); // Penting: Pastikan ini dikirim pertama
include 'koneksi.php'; // Koneksi database

$response = [
    'access_granted' => false,
    'message' => 'Terjadi kesalahan tidak dikenal.'
];

if (isset($_GET['rfid_uid']) && isset($_GET['arah_akses'])) {
    $rfid_uid = $conn->real_escape_string($_GET['rfid_uid']);
    $arah_akses = $conn->real_escape_string($_GET['arah_akses']);

    // Catatan: Kami menggunakan NOW() di SQL untuk waktu_akses agar lebih akurat dengan waktu server database.

    // 1. Cek apakah RFID terdaftar dan aktif di tabel `rfid` (sebelumnya kartu_rfid)
    // Mengubah nama tabel kartu_rfid menjadi rfid dan kolom status_kartu menjadi status_rfid
    $stmt_rfid = $conn->prepare("SELECT rfid_uid, nama_lengkap, status_rfid FROM rfid WHERE rfid_uid = ?");
    $stmt_rfid->bind_param("s", $rfid_uid);
    $stmt_rfid->execute();
    $result_rfid = $stmt_rfid->get_result();

    $nama_lengkap = "Tidak Dikenal";
    $status_rfid_db = "TIDAK TERDAFTAR"; // Mengubah nama variabel status_kartu_rfid menjadi status_rfid_db

    if ($result_rfid->num_rows > 0) {
        $row_rfid = $result_rfid->fetch_assoc();
        $nama_lengkap = $row_rfid['nama_lengkap'];
        $status_rfid_db = $row_rfid['status_rfid']; // Mengubah nama kolom

        if ($status_rfid_db == 'aktif') { // Mengubah nama kolom
            // Logika akses berdasarkan iuran hanya untuk arah 'MASUK'
            if ($arah_akses == 'MASUK') {
                // Tentukan bulan dan tahun saat ini dan bulan sebelumnya
                $bulan_ini = date('m');
                $tahun_ini = date('Y');
                $bulan_lalu = date('m', strtotime('-1 month'));
                $tahun_lalu = date('Y', strtotime('-1 month'));

                // Ambil status iuran untuk bulan ini
                $stmt_iuran_ini = $conn->prepare("SELECT status FROM status_iuran WHERE rfid_uid = ? AND bulan = ? AND tahun = ?");
                $stmt_iuran_ini->bind_param("sii", $rfid_uid, $bulan_ini, $tahun_ini);
                $stmt_iuran_ini->execute();
                $result_iuran_ini = $stmt_iuran_ini->get_result();
                $status_iuran_ini_db = 'BELUM LUNAS'; // Default jika tidak ada record

                if ($result_iuran_ini->num_rows > 0) {
                    $status_iuran_ini_db = $result_iuran_ini->fetch_assoc()['status'];
                }
                $stmt_iuran_ini->close();

                // Ambil status iuran untuk bulan lalu
                $stmt_iuran_lalu = $conn->prepare("SELECT status FROM status_iuran WHERE rfid_uid = ? AND bulan = ? AND tahun = ?");
                $stmt_iuran_lalu->bind_param("sii", $rfid_uid, $bulan_lalu, $tahun_lalu);
                $stmt_iuran_lalu->execute();
                $result_iuran_lalu = $stmt_iuran_lalu->get_result();
                $status_iuran_lalu_db = 'BELUM LUNAS'; // Default jika tidak ada record

                if ($result_iuran_lalu->num_rows > 0) {
                    $status_iuran_lalu_db = $result_iuran_lalu->fetch_assoc()['status'];
                }
                $stmt_iuran_lalu->close();

                // Logika penentuan akses berdasarkan 2 bulan terakhir
                // Akses DITOLAK HANYA JIKA kedua bulan (bulan ini DAN bulan lalu) adalah BELUM LUNAS
                if ($status_iuran_ini_db == 'BELUM LUNAS' && $status_iuran_lalu_db == 'BELUM LUNAS') {
                    $response['access_granted'] = false;
                    $response['message'] = 'Akses DITOLAK: Iuran 2 bulan terakhir belum lunas.';
                    $status_akses_log = 'DITOLAK';
                    $status_iuran_log = '2 BULAN BELUM LUNAS'; // Untuk dicatat di log
                } else {
                    $response['access_granted'] = true;
                    $response['message'] = 'Akses DIZINKAN: Iuran lunas atau hanya 1 bulan menunggak.';
                    $status_akses_log = 'DIZINKAN';
                    // Tentukan status iuran untuk log berdasarkan status bulan ini (atau yang relevan)
                    $status_iuran_log = $status_iuran_ini_db;
                }

            } else { // Arah akses 'KELUAR'
                $response['access_granted'] = true;
                $response['message'] = 'Akses DIZINKAN (Keluar).';
                $status_akses_log = 'DIZINKAN';
                $status_iuran_log = 'TIDAK RELEVAN'; // Iuran tidak relevan untuk keluar
            }
        } else {
            // RFID terdaftar tapi statusnya 'tidak_aktif'
            $response['access_granted'] = false;
            $response['message'] = 'Akses DITOLAK: RFID tidak aktif.';
            $status_akses_log = 'DITOLAK';
            $status_iuran_log = 'RFID TIDAK AKTIF'; // Mengubah KARTU TIDAK AKTIF menjadi RFID TIDAK AKTIF
        }
    } else {
        // RFID tidak terdaftar sama sekali
        $response['access_granted'] = false;
        $response['message'] = 'Akses DITOLAK: RFID tidak terdaftar.';
        $status_akses_log = 'DITOLAK';
        $status_iuran_log = 'TIDAK TERDAFTAR';
    }

    // Catat Log Akses ke Database `log_akses`
    // GUNAKAN NOW() LANGSUNG DI SINI UNTUK WAKTU AKSES
    $stmt_log = $conn->prepare("INSERT INTO log_akses (rfid_uid, waktu_akses, status_akses, arah_akses, status_iuran_terakhir) VALUES (?, NOW(), ?, ?, ?)");
    $stmt_log->bind_param("ssss", $rfid_uid, $status_akses_log, $arah_akses, $status_iuran_log);
    if (!$stmt_log->execute()) {
        // Log error jika gagal menyimpan log akses
        error_log("Error inserting log_akses: " . $stmt_log->error);
        $response['message'] .= ' (Gagal merekam log akses.)'; // Tambahkan pesan ke hardware
    }
    $stmt_log->close();

    $stmt_rfid->close();

} else {
    // Jika rfid_uid atau arah_akses tidak diterima dari sensor
    $response['access_granted'] = false;
    $response['message'] = 'Parameter RFID UID atau Arah Akses tidak lengkap.';
}

// Kirim Respons ke Hardware (JSON)
echo json_encode($response);

// Tutup koneksi database
mysqli_close($conn);