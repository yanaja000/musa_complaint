<?php

require_once "../config/database.php";
require_once "../config/session.php";

requireRole("siswa");

$id_siswa = $_SESSION["user_id"];

/* ==============================
   DATA SISWA
============================== */

$stmt = $conn->prepare("SELECT * FROM siswa WHERE id_siswa = ?");
$stmt->bind_param("i", $id_siswa);
$stmt->execute();

$siswa = $stmt->get_result()->fetch_assoc();

if (!$siswa) {
    header("Location: ../akun/logout.php");
    exit;
}

/* Kolom yang belum ada di tabel otomatis tampil "-" (tidak error) */

$nama   = $siswa["nama"]  ?? "-";
$kelas  = $siswa["kelas"] ?? "-";
$nisn   = $siswa["nis"]   ?? "-";
$email  = $siswa["email"] ?? "-";

/* Tanggal terdaftar (format Indonesia) */

$bulan = [1 => "Jan", "Feb", "Mar", "Apr", "Mei", "Jun",
               "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];

$tanggal_daftar = "-";

if (!empty($siswa["created_at"])) {
    $t = strtotime($siswa["created_at"]);
    $tanggal_daftar = date("d", $t) . " " . $bulan[(int) date("n", $t)] . " " . date("Y", $t);
}

/* Foto profil: kolom "foto" berisi URL/link gambar (http/https) */

$foto_src = "";

if (!empty($siswa["foto"]) && preg_match('#^https?://#i', $siswa["foto"])) {
    $foto_src = $siswa["foto"];
}

/* Menu pengaturan: ubah nama file tujuan di sini.
   Semua nama file huruf kecil (server Linux membedakan huruf besar/kecil).
   Jika file tujuan belum ada di folder siswa/, menu tampil nonaktif
   (bukan link yang berujung 404 Not Found). */

$menu_pengaturan = [
    ["Edit Profil",         "fa-user-pen",        "edit_profil.php"],
    ["Ganti Password",      "fa-lock",            "ganti_password.php"],
    ["Pusat Bantuan / FAQ", "fa-circle-question", "faq.php"],
];

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Profil - MUSA Complaint</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
          rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- CSS halaman profil -->
    <link rel="stylesheet"
          href="../asests/css/profil.css">

</head>

<body>

<main class="container profil-wrap">

    <!-- ===== HEADER PROFIL ===== -->

    <section class="text-center mb-4">

        <div class="profil-avatar">

            <?php if ($foto_src !== ""): ?>
                <img src="<?= htmlspecialchars($foto_src); ?>"
                     alt="Foto profil"
                     referrerpolicy="no-referrer"
                     onerror="this.replaceWith(Object.assign(document.createElement('i'),{className:'fa-solid fa-user'}))">
            <?php else: ?>
                <i class="fa-solid fa-user" aria-hidden="true"></i>
            <?php endif; ?>

        </div>

        <h1 class="profil-nama">
            <?= htmlspecialchars($nama); ?>
        </h1>

        <p class="profil-sub">
            Kelas <?= htmlspecialchars($kelas); ?>
            &middot; NISN <?= htmlspecialchars($nisn); ?>
        </p>

        <span class="badge rounded-pill text-bg-success px-3 py-2">
            <i class="fa-solid fa-circle-check me-1"></i>
            Siswa Aktif
        </span>

    </section>


    <!-- ===== CARD INFORMASI AKUN ===== -->

    <div class="card shadow-sm mb-3">

        <div class="card-body px-3 py-3">

            <h2 class="card-title-small mb-1">Informasi Akun</h2>

            <div class="info-item">
                <span class="info-icon"><i class="fa-solid fa-envelope"></i></span>
                <div>
                    <div class="info-label">Email</div>
                    <div class="info-value"><?= htmlspecialchars($email); ?></div>
                </div>
            </div>

            <div class="info-item">
                <span class="info-icon"><i class="fa-solid fa-calendar-days"></i></span>
                <div>
                    <div class="info-label">Tanggal Terdaftar</div>
                    <div class="info-value"><?= htmlspecialchars($tanggal_daftar); ?></div>
                </div>
            </div>

        </div>

    </div>


    <!-- ===== CARD MENU PENGATURAN ===== -->

    <div class="card shadow-sm mb-4 overflow-hidden">

        <div class="list-group list-group-flush">

            <?php foreach ($menu_pengaturan as [$label, $ikon, $tujuan]): ?>

                <?php $tersedia = is_file(__DIR__ . "/" . $tujuan); ?>

                <?php if ($tersedia): ?>

                    <a href="<?= htmlspecialchars($tujuan); ?>"
                       class="list-group-item list-group-item-action">

                        <i class="fa-solid <?= $ikon; ?> menu-icon" aria-hidden="true"></i>

                        <span><?= $label; ?></span>

                        <i class="fa-solid fa-chevron-right menu-arrow" aria-hidden="true"></i>

                    </a>

                <?php else: ?>

                    <div class="list-group-item disabled text-muted"
                         aria-disabled="true">

                        <i class="fa-solid <?= $ikon; ?> menu-icon" aria-hidden="true"></i>

                        <span><?= $label; ?></span>

                        <span class="badge text-bg-secondary ms-auto">Segera</span>

                    </div>

                <?php endif; ?>

            <?php endforeach; ?>

        </div>

    </div>




    <a href="../akun/logout.php"
       class="btn btn-danger w-100 py-2 fw-semibold">

        <i class="fa-solid fa-right-from-bracket me-2"></i>
        Logout

    </a>

</main>


<!-- Header brand + Bottom Navigation Bar (menu Profil otomatis active) -->
<?php include "bottom-nav.php"; ?>

<script src="../asests/js/logout.js"></script>

</body>

</html>