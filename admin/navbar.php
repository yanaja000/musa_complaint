<?php

/* =========================================================
   navbar.php  -  navbar admin bersama (semua halaman admin)

   Cara pakai di halaman admin (di dalam <body>):

       <?php $halaman_aktif = "dashboard"; include __DIR__ . "/navbar.php"; ?>

   Nilai $halaman_aktif: "dashboard" | "pengaduan" | "siswa" | "profil" | ""
   Menampilkan: header (logo + foto profil bulat), Bottom Navigation Bar,
   dan popup kartu profil. CSS: asests/css/admin_dashboard.css (satu file)
========================================================= */

require_once __DIR__ . "/helpers.php";

$halaman_aktif = $halaman_aktif ?? "";

$adm_id = (int) ($_SESSION["user_id"] ?? 0);

$adm = [
    "nama"       => $_SESSION["nama"] ?? "Admin",
    "username"   => "",
    "email"      => "",
    "foto"       => "",
    "created_at" => null,
];

$adm_ditangani = 0;

/* ---------- data admin (foto boleh belum ada kolomnya) ---------- */

$adm_stmt = false;

try {
    $adm_stmt = $conn->prepare("
        SELECT nama, username, email, foto, created_at
        FROM admin
        WHERE id_admin = ?
    ");
} catch (Throwable $e) {
    $adm_stmt = false;
}

if (!$adm_stmt) {
    try {
        $adm_stmt = $conn->prepare("
            SELECT nama, username, email, created_at
            FROM admin
            WHERE id_admin = ?
        ");
    } catch (Throwable $e) {
        $adm_stmt = false;
    }
}

if ($adm_stmt) {

    $adm_stmt->bind_param("i", $adm_id);
    $adm_stmt->execute();

    $adm_row = $adm_stmt->get_result()->fetch_assoc();

    if ($adm_row) {
        $adm = array_merge(
            $adm,
            array_filter($adm_row, function ($nilai) { return $nilai !== null; })
        );
    }
}

$adm_foto = fotoValid($adm["foto"]);

/* ---------- jumlah pengaduan yang pernah diubah statusnya oleh admin ini ---------- */

try {

    $adm_stmt2 = $conn->prepare("
        SELECT COUNT(DISTINCT id_pengaduan) AS jumlah
        FROM riwayat_status
        WHERE id_admin = ?
    ");

    $adm_stmt2->bind_param("i", $adm_id);
    $adm_stmt2->execute();

    $adm_ditangani = (int) $adm_stmt2->get_result()->fetch_assoc()["jumlah"];

} catch (Throwable $e) {
    $adm_ditangani = 0;
}

?>

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<link rel="stylesheet"
      href="../asests/css/admin_dashboard.css">


<!-- =========================================
     NAVBAR ADMIN
========================================= -->

<header class="adm-navbar">

    <a href="admin_dashboard.php" class="adm-logo">

        <span class="adm-logo-icon">
            <i class="fa-solid fa-bullhorn" aria-hidden="true"></i>
        </span>

        <span class="adm-logo-text"><b>MUSA</b>Complaint</span>

    </a>

    <!-- Foto profil bulat: klik untuk membuka kartu profil -->

    <button type="button"
            class="adm-profile-btn"
            data-bs-toggle="modal"
            data-bs-target="#modalProfilAdmin"
            aria-label="Buka kartu profil saya">

        <span class="adm-profile-name">
            <strong><?= htmlspecialchars($adm["nama"]); ?></strong>
            <small>Admin</small>
        </span>

        <?= avatarHtml($adm["nama"], $adm_foto, "adm-av adm-av-nav"); ?>

    </button>

</header>


<!-- =========================================
     BOTTOM NAVIGATION (sama seperti siswa)
========================================= -->

<nav class="adm-bottom-nav" aria-label="Navigasi admin">

    <div class="adm-bn-inner">

        <a href="admin_dashboard.php"
           class="adm-bn-item <?= $halaman_aktif === "dashboard" ? "active" : ""; ?>">
            <i class="fa-solid fa-house" aria-hidden="true"></i>
            <span>Dashboard</span>
        </a>

        <a href="pengaduan.php"
           class="adm-bn-item <?= $halaman_aktif === "pengaduan" ? "active" : ""; ?>">
            <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
            <span>Pengaduan</span>
        </a>

        <a href="siswa.php"
           class="adm-bn-item <?= $halaman_aktif === "siswa" ? "active" : ""; ?>">
            <i class="fa-solid fa-user-graduate" aria-hidden="true"></i>
            <span>Siswa</span>
        </a>

        <!-- Profil: membuka kartu profil -->
        <button type="button"
                class="adm-bn-item <?= $halaman_aktif === "profil" ? "active" : ""; ?>"
                data-bs-toggle="modal"
                data-bs-target="#modalProfilAdmin">
            <i class="fa-solid fa-user" aria-hidden="true"></i>
            <span>Profil</span>
        </button>

    </div>

</nav>


<!-- =========================================
     POPUP KARTU PROFIL ADMIN (Bootstrap 5 Modal)
========================================= -->

<div class="modal fade"
     id="modalProfilAdmin"
     tabindex="-1"
     aria-labelledby="judulKartuProfilAdmin"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content kp-card">

            <div class="kp-header">

                <a href="../akun/logout.php" class="kp-logout">
                    <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                    Logout
                </a>

                <button type="button"
                        class="kp-close"
                        data-bs-dismiss="modal"
                        aria-label="Tutup">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>

            </div>

            <div class="kp-body">

                <?= avatarHtml($adm["nama"], $adm_foto, "adm-av kp-foto"); ?>

                <h2 class="kp-nama" id="judulKartuProfilAdmin">
                    <?= htmlspecialchars(mb_strtoupper($adm["nama"], "UTF-8")); ?>
                </h2>

                <p class="kp-user">@<?= htmlspecialchars($adm["username"]); ?></p>

                <div class="kp-grid">

                    <div class="kp-info">
                        <span class="kp-info-label">
                            <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
                            Status
                        </span>
                        <strong>Admin</strong>
                    </div>

                    <div class="kp-info">
                        <span class="kp-info-label">
                            <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
                            Ditangani
                        </span>
                        <strong><?= $adm_ditangani; ?> pengaduan</strong>
                    </div>

                </div>

                <div class="kp-info kp-info-wide">
                    <span class="kp-info-label">
                        <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                        Email
                    </span>
                    <strong><?= htmlspecialchars($adm["email"]); ?></strong>
                </div>

                <a href="edit_profil.php" class="kp-edit">
                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                    Edit Profil
                </a>

            </div>

            <div class="kp-footer">
                <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
                Bergabung sejak: <?= tanggalIndonesia($adm["created_at"]); ?>
            </div>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>