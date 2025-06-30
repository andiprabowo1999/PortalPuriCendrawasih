window.addEventListener('DOMContentLoaded', event => {
    // Simple-DataTables
    // https://github.com/fiduswriter/Simple-DataTables/wiki

    const datatablesSimple = document.getElementById('datatablesSimple');
    if (datatablesSimple) {
        // Inisialisasi DataTable dengan opsi untuk menghilangkan fitur tertentu
        new simpleDatatables.DataTable(datatablesSimple, {
            // "searchable: false" akan menghilangkan kotak pencarian (Search)
            searchable: false,
            // "paging: false" akan menghilangkan pilihan entri per halaman dan navigasi halaman
            paging: false,
            // "info: false" akan menghilangkan teks "Showing 1 to 10 of 10 entries"
            info: false
        });
    }
});
