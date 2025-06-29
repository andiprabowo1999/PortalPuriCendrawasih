<?php
// Pastikan output adalah format JSON
header('Content-Type: application/json');
// Sertakan file koneksi database
require 'function.php'; // function.php seharusnya berisi koneksi $conn

// Siapkan respons default jika terjadi error
$response = [
    'access_granted' => false,
    'message' => 'Terjadi kesalahan tidak dikenal.'
];

// Pastikan parameter rfid_uid dan arah_akses diterima dari ESP32
if (isset($_GET['rfid_uid']) && isset($_GET['arah_akses'])) {
    $rfid_uid = $_GET['rfid_uid'];
    $arah_akses = $_GET['arah_akses'];

    // 1. Ambil Pengaturan Sistem dari Database
    $query_pengaturan = mysqli_query($conn, "SELECT nama_pengaturan, nilai_pengaturan FROM pengaturan");
    $pengaturan = [];
    while ($row = mysqli_fetch_assoc($query_pengaturan)) {
        $pengaturan[$row['nama_pengaturan']] = $row['nilai_pengaturan'];
    }
    $batas_tunggakan_bulan = isset($pengaturan['batas_tunggakan_bulan']) ? (int)$pengaturan['batas_tunggakan_bulan'] : 2; // Default 2 jika tidak ada di DB
    $nominal_iuran = isset($pengaturan['nominal_iuran']) ? (int)$pengaturan['nominal_iuran'] : 150000; // Default 150000

    // 2. Cek apakah RFID terdaftar dan aktif
    $stmt_rfid = $conn->prepare("SELECT status_rfid FROM rfid WHERE rfid_uid = ?");
    $stmt_rfid->bind_param("s", $rfid_uid);
    $stmt_rfid->execute();
    $result_rfid = $stmt_rfid->get_result();

    if ($result_rfid->num_rows > 0) {
        $rfid_data = $result_rfid->fetch_assoc();

        if ($rfid_data['status_rfid'] == 'aktif') {
            // Jika RFID aktif, lanjutkan ke pengecekan selanjutnya

            // Jika arahnya KELUAR, selalu izinkan
            if ($arah_akses == 'KELUAR') {
                $response['access_granted'] = true;
                $response['message'] = 'Akses DIZINKAN (Keluar).';
                $status_akses_log = 'DIZINKAN';
                $status_iuran_log = 'TIDAK RELEVAN';
            } 
            // Jika arahnya MASUK, cek status IPL
            else if ($arah_akses == 'MASUK') {
                $tunggakan_beruntun = 0;
                // Loop mundur sebanyak batas bulan tunggakan
                for ($i = 0; $i < $batas_tunggakan_bulan; $i++) {
                    $bulan_cek = date('n', strtotime("-$i month"));
                    $tahun_cek = date('Y', strtotime("-$i month"));

                    // Cek status pembayaran di bulan tersebut
                    $stmt_iuran = $conn->prepare("SELECT status FROM status_iuran WHERE rfid_uid = ? AND bulan = ? AND tahun = ?");
                    $stmt_iuran->bind_param("sii", $rfid_uid, $bulan_cek, $tahun_cek);
                    $stmt_iuran->execute();
                    $result_iuran = $stmt_iuran->get_result();
                    
                    $status_bulan_ini = 'BELUM LUNAS'; // Default jika tidak ada data
                    if ($result_iuran->num_rows > 0) {
                        $iuran_data = $result_iuran->fetch_assoc();
                        $status_bulan_ini = $iuran_data['status'];
                    }
                    $stmt_iuran->close();

                    // Jika statusnya bukan LUNAS, hitung sebagai tunggakan
                    if ($status_bulan_ini != 'LUNAS') {
                        $tunggakan_beruntun++;
                    } else {
                        // Jika ada satu bulan lunas, hentikan pengecekan karena tidak lagi beruntun
                        break;
                    }
                }

                // Tentukan akses berdasarkan jumlah tunggakan beruntun
                if ($tunggakan_beruntun >= $batas_tunggakan_bulan) {
                    $response['access_granted'] = false;
                    $response['message'] = "Akses DITOLAK: IPL $batas_tunggakan_bulan bulan terakhir belum lunas.";
                    $status_akses_log = 'DITOLAK';
                    $status_iuran_log = "$batas_tunggakan_bulan BULAN BELUM LUNAS";
                } else {
                    $response['access_granted'] = true;
                    $response['message'] = 'Akses DIZINKAN.';
                    $status_akses_log = 'DIZINKAN';
                    $status_iuran_log = ($tunggakan_beruntun > 0) ? 'BELUM LUNAS' : 'LUNAS'; // Status log disederhanakan
                }
            }

        } else {
            // RFID terdaftar tapi statusnya 'tidak_aktif'
            $response['access_granted'] = false;
            $response['message'] = 'Akses DITOLAK: RFID tidak aktif.';
            $status_akses_log = 'DITOLAK';
            $status_iuran_log = 'RFID TIDAK AKTIF';
        }

    } else {
        // RFID tidak terdaftar sama sekali
        $response['access_granted'] = false;
        $response['message'] = 'Akses DITOLAK: RFID tidak terdaftar.';
        $status_akses_log = 'DITOLAK';
        $status_iuran_log = 'TIDAK TERDAFTAR';
    }
    $stmt_rfid->close();

    // 3. Catat semua aktivitas akses ke dalam log, apapun hasilnya
    $stmt_log = $conn->prepare("INSERT INTO log_akses (rfid_uid, waktu_akses, status_akses, arah_akses, status_iuran_terakhir) VALUES (?, NOW(), ?, ?, ?)");
    if ($stmt_log === false) {
        error_log("Gagal menyiapkan statement log: " . $conn->error);
    } else {
        $stmt_log->bind_param("ssss", $rfid_uid, $status_akses_log, $arah_akses, $status_iuran_log);
        if (!$stmt_log->execute()) {
            // Jika gagal menyimpan log, catat error tapi jangan hentikan respons ke ESP32
            error_log("Gagal menyimpan log_akses: " . $stmt_log->error);
        }
        $stmt_log->close();
    }

} else {
    // Jika parameter dari ESP32 tidak lengkap
    $response['message'] = 'Parameter RFID UID atau Arah Akses tidak lengkap.';
}

// Kirimkan respons dalam format JSON ke ESP32
echo json_encode($response);

// Tutup koneksi database
$conn->close();