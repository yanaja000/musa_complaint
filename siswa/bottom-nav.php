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

/* Data untuk popup Kartu Profil (nama, NIS, kelas, tanggal daftar) */

$nav_profil = [
    "nama"       => $_SESSION["nama"] ?? "Siswa",
    "nis"        => "",
    "kelas"      => "-",
    "created_at" => null,
];

if (!function_exists("tanggalIndonesia")) {

    function tanggalIndonesia($tanggal)
    {
        if (empty($tanggal)) {
            return "-";
        }

        $bulan = [
            1 => "Januari", "Februari", "Maret", "April", "Mei", "Juni",
            "Juli", "Agustus", "September", "Oktober", "November", "Desember",
        ];

        $waktu = strtotime($tanggal);

        return date("j", $waktu) . " " . $bulan[(int) date("n", $waktu)] . " " . date("Y", $waktu);
    }
}

if (isset($conn, $_SESSION["user_id"])) {

    try {

        $nav_stmt = $conn->prepare("
            SELECT foto, nama, nis, kelas, created_at
            FROM siswa
            WHERE id_siswa = ?
        ");
        $nav_stmt->bind_param("i", $_SESSION["user_id"]);
        $nav_stmt->execute();

        $nav_row = $nav_stmt->get_result()->fetch_assoc();

        if ($nav_row) {
            $nav_profil = array_merge(
                $nav_profil,
                array_filter($nav_row, function ($nilai) { return $nilai !== null; })
            );
        }

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
                    AND COALESCE(t.pengirim, 'admin') <> 'siswa'
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

<link rel="stylesheet"
      href="../asests/css/modal-profil.css">

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

        <!-- Avatar siswa (klik -> popup Kartu Profil) -->
        <button type="button"
                class="top-avatar"
                data-bs-toggle="modal"
                data-bs-target="#modalProfilSiswa"
                aria-label="Buka kartu profil saya">

            <?php if ($nav_foto !== ""): ?>

                <img src="<?= htmlspecialchars($nav_foto); ?>"
                     alt="Foto profil"
                     referrerpolicy="no-referrer"
                     onerror="this.replaceWith(Object.assign(document.createElement('i'),{className:'fa-solid fa-user'}))">

            <?php else: ?>

                <i class="fa-solid fa-user" aria-hidden="true"></i>

            <?php endif; ?>

        </button>

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


<!-- =========================================
     POPUP KARTU PROFIL SISWA (Bootstrap 5 Modal)
========================================= -->

<div class="modal fade"
     id="modalProfilSiswa"
     tabindex="-1"
     aria-labelledby="judulKartuProfil"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content kp-card">

            <!-- Header gradasi -->
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

                <!-- Foto bulat di batas gradasi -->
                <div class="kp-foto">

                    <?php if ($nav_foto !== ""): ?>

                        <img src="<?= htmlspecialchars($nav_foto); ?>"
                             alt="Foto <?= htmlspecialchars($nav_profil["nama"]); ?>"
                             referrerpolicy="no-referrer"
                             onerror="this.replaceWith(Object.assign(document.createElement('i'),{className:'fa-solid fa-user'}))">

                    <?php else: ?>

                        <i class="fa-solid fa-user" aria-hidden="true"></i>

                    <?php endif; ?>

                </div>

                <h2 class="kp-nama" id="judulKartuProfil">
                    <?= htmlspecialchars(mb_strtoupper($nav_profil["nama"], "UTF-8")); ?>
                </h2>

                <p class="kp-nis">
                    @<?= htmlspecialchars($nav_profil["nis"]); ?>
                </p>

                <!-- Grid informasi -->
                <div class="kp-grid">

                    <div class="kp-info">
                        <span class="kp-info-label">
                            <i class="fa-solid fa-user-graduate" aria-hidden="true"></i>
                            Status
                        </span>
                        <strong>Siswa</strong>
                    </div>

                    <div class="kp-info">
                        <span class="kp-info-label">
                            <i class="fa-solid fa-school" aria-hidden="true"></i>
                            Kelas
                        </span>
                        <strong><?= htmlspecialchars($nav_profil["kelas"]); ?></strong>
                    </div>

                </div>

            </div>

            <!-- Footer -->
            <div class="kp-footer">
                <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
                Bergabung sejak: <?= tanggalIndonesia($nav_profil["created_at"]); ?>
            </div>

        </div>

    </div>

</div>

<!-- Bootstrap 5 JS (untuk semua modal di halaman siswa) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>