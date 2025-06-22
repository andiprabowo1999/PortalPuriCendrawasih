<?php
require 'function.php'; // cek.php sudah memanggil session_start() jika belum
require 'cek.php';     // Memastikan pengguna sudah login

// Pastikan session_start() sudah dipanggil di cek.php atau di sini
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Default tanggal filter
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monitoring Akses Portal - PURI CENDRAWASIH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        .filter-form {
            margin-bottom: 20px;
            display: flex; /* Menggunakan flexbox untuk tata letak yang lebih baik */
            align-items: center; /* Pusatkan item secara vertikal */
            flex-wrap: wrap; /* Izinkan item untuk membungkus jika layar kecil */
        }
        .filter-form label {
            margin-right: 15px; /* Tambah sedikit ruang antar label/input */
            margin-bottom: 5px; /* Sedikit ruang di bawah label saat membungkus */
            font-weight: 500;
        }
        .filter-form input, .filter-form button {
            margin-right: 15px; /* Tambah sedikit ruang antar input/button */
            margin-bottom: 5px; /* Sedikit ruang di bawah input/button saat membungkus */
            padding: 8px 12px; /* Perbesar padding agar lebih mudah diklik */
            border-radius: 5px; /* Tambahkan border-radius */
        }
        .filter-form .btn-sm {
            padding: 8px 12px; /* Sesuaikan padding untuk tombol sm */
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php include 'header.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Monitoring Akses Portal</h1>

                    <form method="GET" class="filter-form">
                        <label>Dari Tanggal:
                            <input type="date" name="tanggal_awal" id="filter_tanggal_awal" value="<?= htmlspecialchars($tanggal_awal) ?>">
                        </label>
                        <label>Sampai Tanggal:
                            <input type="date" name="tanggal_akhir" id="filter_tanggal_akhir" value="<?= htmlspecialchars($tanggal_akhir) ?>">
                        </label>
                        <button type="submit" class="btn btn-primary btn-sm">Filter Data</button>
                        <a id="exportExcelLink" href="export_excel.php?tanggal_awal=<?= htmlspecialchars($tanggal_awal) ?>&tanggal_akhir=<?= htmlspecialchars($tanggal_akhir) ?>" target="_blank" class="btn btn-success btn-sm">
                            Export to Excel
                        </a>
                    </form>
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            Log Akses Portal
                        </div>
                        <div class="card-body">
                            <table id="logAksesTable" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Nama</th>
                                        <th>RFID</th>
                                        <th>Arah Akses</th>
                                        <th>Status Akses</th>
                                        <th>Status Iuran</th>
                                    </tr>
                                </thead>
                                <tbody id="logAksesTableBody">
                                    <tr><td colspan="6" class="text-center">Memuat data...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <?php include 'footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script src="js/datatables-simple-demo.js"></script>
    <script>
        // Fungsi utama untuk mengambil dan menampilkan data
        function fetchDataAndDisplay() {
            const tanggalAwal = document.getElementById('filter_tanggal_awal').value;
            const tanggalAkhir = document.getElementById('filter_tanggal_akhir').value;
            const tableBody = document.getElementById('logAksesTableBody');
            const exportLink = document.getElementById('exportExcelLink');

            // Log untuk debugging: Pastikan fungsi dipanggil
            console.log(`fetchDataAndDisplay dipanggil pada ${new Date().toLocaleTimeString()} untuk tanggal ${tanggalAwal} s/d ${tanggalAkhir}`);

            // Update link Export Excel sesuai filter saat ini
            exportLink.href = `export_excel.php?tanggal_awal=${tanggalAwal}&tanggal_akhir=${tanggalAkhir}`;

            // Tampilkan pesan loading saat data sedang diambil
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Memuat data...</td></tr>';

            // Menggunakan Fetch API untuk mengambil data dari get_latest_data.php
            fetch(`get_latest_data.php?tanggal_awal=${tanggalAwal}&tanggal_akhir=${tanggalAkhir}`)
                .then(response => {
                    // Cek jika respons HTTP tidak OK (misalnya 404, 500)
                    if (!response.ok) {
                        // Jika respons bukan JSON atau ada error server, coba baca sebagai teks
                        return response.text().then(text => {
                            throw new Error(`HTTP error! status: ${response.status}. Response: ${text}`);
                        });
                    }
                    // Jika respons OK, parse sebagai JSON
                    return response.json();
                })
                .then(data => {
                    // Log untuk debugging: Pastikan data diterima
                    console.log("Data diterima:", data);

                    tableBody.innerHTML = ''; // Kosongkan lagi sebelum mengisi data
                    if (data.status === 'success' && Object.keys(data.data).length > 0) {
                        let tableHtml = '';
                        for (const tanggal in data.data) {
                            // Header per tanggal
                            tableHtml += `<tr><td colspan="6" class="table-dark text-center"><strong>${new Date(tanggal).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'})}</strong></td></tr>`;

                            // Baris data untuk tanggal tersebut
                            data.data[tanggal].forEach(row => {
                                let badgeClass = '';
                                switch (row.status_iuran) {
                                    case 'LUNAS':
                                        badgeClass = 'bg-success';
                                        break;
                                    case 'BELUM LUNAS':
                                    case 'BELUM LUNAS (1 BULAN)':
                                    case 'BELUM LUNAS (2 BULAN)':
                                        badgeClass = 'bg-danger';
                                        break;
                                    case 'TIDAK TERDAFTAR':
                                    case 'KARTU TIDAK AKTIF':
                                    case 'TIDAK RELEVAN':
                                        badgeClass = 'bg-secondary';
                                        break;
                                    default:
                                        badgeClass = 'bg-info'; // Default jika status tidak cocok
                                        break;
                                }
                                tableHtml += `
                                    <tr>
                                        <td>${new Date(row.waktu_akses).toLocaleTimeString('en-GB', {hour: '2-digit', minute: '2-digit', second: '2-digit'})}</td>
                                        <td>${row.nama_lengkap}</td>
                                        <td>${row.rfid_uid}</td>
                                        <td>${row.arah_akses}</td>
                                        <td>${row.status_akses}</td>
                                        <td><span class="badge ${badgeClass}">${row.status_iuran}</span></td>
                                    </tr>
                                `;
                            });
                        }
                        tableBody.innerHTML = tableHtml;
                    } else {
                        // Jika status bukan success atau tidak ada data
                        tableBody.innerHTML = '<tr><td colspan="6" class="text-center"><div class="alert alert-info">Tidak ada data akses untuk rentang tanggal yang dipilih.</div></td></tr>';
                    }
                })
                .catch(error => {
                    // Tangani error jaringan atau parsing JSON
                    console.error('Error fetching data:', error);
                    tableBody.innerHTML = `<tr><td colspan="6" class="text-center"><div class="alert alert-danger">Gagal memuat data. Silakan periksa konsol browser (F12) untuk detail error.<br>Error: ${error.message}</div></td></tr>`;
                });
        }

        // Panggil fungsi saat halaman pertama kali dimuat
        document.addEventListener('DOMContentLoaded', fetchDataAndDisplay);

        // Set interval untuk auto-refresh data setiap 30 detik
        setInterval(fetchDataAndDisplay, 30000); // 30000 milidetik = 30 detik

        // Menangani submit form filter agar menggunakan AJAX, bukan reload halaman penuh
        document.querySelector('.filter-form').addEventListener('submit', function(event) {
            event.preventDefault(); // Mencegah form submit secara tradisional (reload halaman)
            fetchDataAndDisplay(); // Panggil fungsi untuk memuat data via AJAX
        });

    </script>
</body>
</html>