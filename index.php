<?php
// NAMA FILE: index.php

require 'function.php';
require 'cek.php';

// Menentukan rentang tanggal, defaultnya adalah hari ini
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Monitoring Akses Portal - PURI CENDRAWASIH</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="sb-nav-fixed">
    <?php include 'header.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Monitoring Akses Portal</h1>
                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-filter me-1"></i>Filter Data Log</div>
                        <div class="card-body">
                            <form method="GET" class="row gx-3 gy-2 align-items-center">
                                <div class="col-sm-4">
                                    <label class="form-label" for="tanggal_awal">Dari Tanggal:</label>
                                    <input class="form-control" type="date" name="tanggal_awal" id="tanggal_awal" value="<?= htmlspecialchars($tanggal_awal) ?>">
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label" for="tanggal_akhir">Sampai Tanggal:</label>
                                    <input class="form-control" type="date" name="tanggal_akhir" id="tanggal_akhir" value="<?= htmlspecialchars($tanggal_akhir) ?>">
                                </div>
                                <div class="col-auto align-self-end">
                                    <button type="button" id="filterButton" class="btn btn-primary"><i class="fas fa-search me-2"></i>Filter</button>
                                </div>
                                <div class="col-auto align-self-end">
                                    <a id="exportLink" href="export_excel.php?tanggal_awal=<?= htmlspecialchars($tanggal_awal) ?>&tanggal_akhir=<?= htmlspecialchars($tanggal_akhir) ?>" target="_blank" class="btn btn-success">
                                        <i class="fas fa-file-excel me-2"></i>Export ke Excel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-table me-1"></i>Log Aktivitas Akses Portal</div>
                        <div class="card-body">
                            <table id="datatablesSimple">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Nama Pemegang Kartu</th>
                                        <th>UID RFID</th>
                                        <th>Arah</th>
                                        <th>Status Akses</th>
                                        <th>Status IPL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <?php include 'footer.php'; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script>
        let dataTable;

        // Fungsi untuk mengambil data dan memperbarui tabel
        function fetchDataAndDisplay() {
            const tanggalAwal = document.getElementById('tanggal_awal').value;
            const tanggalAkhir = document.getElementById('tanggal_akhir').value;
            
            // Update link export setiap kali data di-refresh
            document.getElementById('exportLink').href = `export_excel.php?tanggal_awal=${tanggalAwal}&tanggal_akhir=${tanggalAkhir}`;
            
            console.log(`Memuat data untuk ${tanggalAwal} sampai ${tanggalAkhir}...`);

            fetch(`get_latest_data.php?tanggal_awal=${tanggalAwal}&tanggal_akhir=${tanggalAkhir}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(jsonResponse => {
                    if (jsonResponse.status === 'success') {
                        const newData = {
                            headings: ['Waktu', 'Nama Pemegang Kartu', 'UID RFID', 'Arah', 'Status Akses', 'Status IPL'],
                            data: jsonResponse.data.map(row => [
                                row.waktu_akses,
                                row.nama_lengkap || 'Kartu Dihapus/Tidak Dikenal',
                                `<code>${row.rfid_uid}</code>`,
                                row.arah_akses,
                                row.status_akses,
                                row.status_iuran_terakhir
                            ])
                        };
                        
                        if (dataTable) {
                            dataTable.destroy();
                        }

                        dataTable = new simpleDatatables.DataTable("#datatablesSimple", {
                            data: newData,
                            searchable: false,
                            paging: false,
                            info: false,
                        });

                        console.log('Tabel berhasil diperbarui.');
                    } else {
                        console.error('API Error:', jsonResponse.message);
                    }
                })
                .catch(error => console.error('Gagal mengambil atau mem-parsing data:', error));
        }

        // Event listener saat halaman selesai dimuat
        document.addEventListener('DOMContentLoaded', () => {
            // Muat data untuk pertama kali
            fetchDataAndDisplay(); 
            
            // Atur auto-refresh setiap 5 detik (5000 milidetik)
            setInterval(fetchDataAndDisplay, 5000);

            // Tambahkan event listener untuk tombol filter
            document.getElementById('filterButton').addEventListener('click', (e) => {
                e.preventDefault(); // Mencegah form submit
                fetchDataAndDisplay();
            });
        });
    </script>
</body>
</html>