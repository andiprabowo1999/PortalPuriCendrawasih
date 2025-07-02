<?php
$loggedInUsername = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest';
?>
<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">
                <div class="sb-sidenav-menu-heading">Core</div>
                <a class="nav-link" href="index.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                    Monitoring Akses Portal
                </a>

                <?php if (isset($_SESSION['username']) && $_SESSION['username'] === 'admin'): ?>
                    <div class="sb-sidenav-menu-heading">Admin Panel</div>
                    <a class="nav-link" href="admin_panel.php">
                        <div class="sb-nav-link-icon"><i class="fas fa-users-cog"></i></div>
                        Manajemen Pengguna
                    </a>
                <?php endif; ?>

                <?php if (isset($_SESSION['username']) && $_SESSION['username'] === 'ketuart'): ?>
                    <div class="sb-sidenav-menu-heading">Ketua RT</div>
                    <a class="nav-link" href="parameter_kebijakan.php">
                        <div class="sb-nav-link-icon"><i class="fas fa-cogs"></i></div>
                        Parameter Kebijakan
                    </a>
                <?php endif; ?>
                
                <div class="sb-sidenav-menu-heading">Manajemen Warga</div>
                <a class="nav-link" href="registrasiRFID.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-id-card"></i></div>
                    Registrasi RFID
                </a>
                
                <a class="nav-link" href="iuranpl.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-money-bill-wave"></i></div>
                    Iuran Pengelolaan Lingkungan
                </a>
                
                <div class="sb-sidenav-menu-heading">Akun</div>
                <a class="nav-link" href="logout.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-sign-out-alt"></i></div>
                    Logout
                </a>
            </div>
        </div>
        <div class="sb-sidenav-footer">
            <div class="small">Logged in as:</div>
            <?php echo $loggedInUsername; ?>
        </div>
    </nav>
</div>
