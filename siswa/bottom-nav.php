<?php
/* =========================================================
   BOTTOM NAVIGATION BAR + HEADER BRAND - SISWA (MUSA Complaint)
   Satu file untuk semuanya: logo + tulisan "MUSA Complaint" di atas
   dan 4 menu di bawah. Cukup di-include di halaman siswa mana pun.

   Logo: simpan gambar di  asests/img/logo.png
   (atau logo.jpg / logo.jpeg / logo.webp / logo.svg).
   Kalau belum ada, otomatis tampil ikon biru sebagai pengganti.

   Cara pakai (taruh sebelum </body>):
       <?php include "bottom-nav.php"; ?>

   Menu aktif terdeteksi otomatis dari nama file halaman.
   Kalau mau dipaksa manual, set sebelum include:
       $nav_aktif = "riwayat";   // beranda | buat | riwayat | profil
========================================================= */

if (!isset($nav_aktif)) {

    $file_sekarang = basename($_SERVER["PHP_SELF"]);

    $peta_halaman = [
        "siswa_dashboard.php"   => "beranda",
        "buat_pengaduan.php"    => "buat",
        "pengaduan_saya.php"    => "riwayat",
        "detail_pengaduan.php"  => "riwayat",   // detail masih bagian Riwayat
        "profil.php"            => "profil",
    ];

    $nav_aktif = $peta_halaman[$file_sekarang] ?? "";
}

/* Cari file logo */

$logo_src = "";

foreach (["png", "jpg", "jpeg", "webp", "svg"] as $ext) {

    if (file_exists(__DIR__ . "/../asests/img/logo." . $ext)) {
        $logo_src = "../asests/img/logo." . $ext;
        break;
    }
}

/* Foto profil siswa (URL dari kolom foto). Kosong/gagal -> ikon placeholder */

$nav_foto = "";

if (isset($conn, $_SESSION["user_id"])) {

    try {

        $nav_stmt = $conn->prepare("SELECT foto FROM siswa WHERE id_siswa = ?");
        $nav_stmt->bind_param("i", $_SESSION["user_id"]);
        $nav_stmt->execute();

        $nav_row = $nav_stmt->get_result()->fetch_assoc();

        if (!empty($nav_row["foto"]) && preg_match('#^https?://#i', $nav_row["foto"])) {
            $nav_foto = $nav_row["foto"];
        }

    } catch (Throwable $e) {
        $nav_foto = "";   // kolom foto belum ada -> pakai placeholder
    }
}

/* Notifikasi: jumlah pengaduan yang diperbarui admin tapi belum dibuka siswa */

$nav_notif = 0;
$nav_notif_link = "pengaduan_saya.php";

if (isset($conn, $_SESSION["user_id"])) {

    try {

        $nav_stmt = $conn->prepare("
            SELECT
                COUNT(*) AS jumlah,
                MIN(p.id_pengaduan) AS id_pertama
            FROM pengaduan p
            WHERE p.id_siswa = ?
            AND (
                p.updated_at > COALESCE(p.dilihat_siswa_at, p.created_at)
                OR EXISTS (
                    SELECT 1
                    FROM tanggapan t
                    WHERE t.id_pengaduan = p.id_pengaduan
                    AND t.created_at > COALESCE(p.dilihat_siswa_at, p.created_at)
                )
            )
        ");
        $nav_stmt->bind_param("i", $_SESSION["user_id"]);
        $nav_stmt->execute();

        $nav_row = $nav_stmt->get_result()->fetch_assoc();

        $nav_notif = (int) $nav_row["jumlah"];

        /* kalau hanya 1 yang belum dibaca, langsung buka detailnya */
        if ($nav_notif === 1) {
            $nav_notif_link = "detail_pengaduan.php?id=" . (int) $nav_row["id_pertama"];
        }

    } catch (Throwable $e) {
        $nav_notif = 0;   // kolom dilihat_siswa_at belum dibuat
    }
}

$menu_nav = [
    "beranda" => ["Beranda", "fa-house",          "siswa_dashboard.php"],
    "buat"    => ["Buat",    "fa-circle-plus",    "buat_pengaduan.php"],
    "riwayat" => ["Riwayat", "fa-clipboard-list", "pengaduan_saya.php"],
    "profil"  => ["Profil",  "fa-user",           "profil.php"],
];
?>

<!-- FontAwesome + CSS bottom nav -->
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<link rel="stylesheet"
      href="../asests/css/bottom-nav.css">

<!-- =========================================
     HEADER BRAND (logo + nama aplikasi)
========================================= -->

<header class="top-brand">

    <a href="siswa_dashboard.php" class="top-brand-link">

        <?php if ($logo_src !== ""): ?>

            <img src="<?= $logo_src; ?>"
                 alt="Logo MUSA Complaint"
                 class="top-brand-logo">

        <?php else: ?>

            <span class="top-brand-logo top-brand-fallback">
                <i class="fa-solid fa-bullhorn" aria-hidden="true"></i>
            </span>

        <?php endif; ?>

        <span class="top-brand-text">
            MUSA<span>Complaint</span>
        </span>

    </a>

    <div class="top-actions">

        <!-- Lonceng notifikasi (klik -> riwayat pengaduan) -->
        <a href="<?= $nav_notif_link; ?>"
           class="top-bell"
           aria-label="Notifikasi<?= $nav_notif > 0 ? ' (' . $nav_notif . ' baru)' : ''; ?>">

            <i class="fas fa-bell" aria-hidden="true"></i>

            <?php if ($nav_notif > 0): ?>
                <span class="top-bell-badge">
                    <?= $nav_notif > 99 ? "99+" : $nav_notif; ?>
                </span>
            <?php endif; ?>

        </a>

        <!-- Avatar siswa (klik -> halaman profil) -->
        <a href="profil.php" class="top-avatar" aria-label="Profil saya">

            <?php if ($nav_foto !== ""): ?>

                <img src="<?= htmlspecialchars($nav_foto); ?>"
                     alt="Foto profil"
                     referrerpolicy="no-referrer"
                     onerror="this.replaceWith(Object.assign(document.createElement('i'),{className:'fa-solid fa-user'}))">

            <?php else: ?>

                <i class="fa-solid fa-user" aria-hidden="true"></i>

            <?php endif; ?>

        </a>

    </div>

</header>


<!-- =========================================
     BOTTOM NAVIGATION BAR
========================================= -->

<nav class="bottom-nav" aria-label="Navigasi utama">

    <div class="bottom-nav-inner">

        <?php foreach ($menu_nav as $kunci => [$label, $ikon, $tujuan]): ?>

            <a href="<?= $tujuan; ?>"
               class="bottom-nav-item<?= $nav_aktif === $kunci ? ' active' : ''; ?>"
               <?= $nav_aktif === $kunci ? 'aria-current="page"' : ''; ?>>

                <i class="fa-solid <?= $ikon; ?>" aria-hidden="true"></i>

                <span><?= $label; ?></span>

            </a>

        <?php endforeach; ?>

    </div>

</nav>