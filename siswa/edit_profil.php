<?php

require_once "../config/database.php";
require_once "../config/session.php";

requireRole("siswa");

$id_siswa = $_SESSION["user_id"];

$error = "";
$success = "";

/* ==============================
   SIMPAN FOTO (URL)
============================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $foto_input = trim($_POST["foto"] ?? "");

    if ($foto_input === "") {

        $foto_simpan = null;   // kosong = hapus foto

    } elseif (strlen($foto_input) > 2048) {

        $error = "Link foto terlalu panjang (maksimal 2048 karakter).";

    } elseif (
        !filter_var($foto_input, FILTER_VALIDATE_URL) ||
        !preg_match('#^https?://#i', $foto_input)
    ) {

        $error = "Link foto tidak valid. Gunakan alamat yang diawali http:// atau https://";

    } else {

        $foto_simpan = $foto_input;
    }

    if ($error === "") {

        try {

            $stmt = $conn->prepare("UPDATE siswa SET foto = ? WHERE id_siswa = ?");
            $stmt->bind_param("si", $foto_simpan, $id_siswa);
            $stmt->execute();

            $success = "Foto profil berhasil disimpan.";

        } catch (Throwable $e) {

            $error = "Gagal menyimpan. Pastikan kolom foto sudah ada di tabel siswa (lihat SQL di panduan).";
        }
    }
}

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

$nama  = $siswa["nama"]  ?? "-";
$nisn  = $siswa["nis"]   ?? "-";
$kelas = $siswa["kelas"] ?? "-";

/* URL foto tersimpan di database (kalau gagal validasi, tampilkan input terakhir) */

$foto_db = $siswa["foto"] ?? "";

$foto_src = preg_match('#^https?://#i', $foto_db) ? $foto_db : "";

$nilai_input = ($error !== "" && isset($foto_input)) ? $foto_input : $foto_db;

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Profil - MUSA Complaint</title>

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

    <a href="profil.php" class="text-decoration-none small fw-medium">
        <i class="fa-solid fa-chevron-left me-1"></i> Kembali ke Profil
    </a>

    <h1 class="h5 fw-bold my-3">Edit Profil</h1>


    <?php if ($success !== ""): ?>
        <div class="alert alert-success py-2 small">
            <i class="fa-solid fa-circle-check me-1"></i>
            <?= htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="alert alert-danger py-2 small">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>
            <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>


    <form method="POST" class="card shadow-sm">

        <div class="card-body p-3 p-sm-4">

            <!-- ===== PREVIEW FOTO ===== -->

            <div class="profil-avatar" id="previewBox">

                <?php if ($foto_src !== ""): ?>

                    <img src="<?= htmlspecialchars($foto_src); ?>"
                         alt="Foto profil"
                         id="previewImg"
                         referrerpolicy="no-referrer">

                <?php else: ?>

                    <i class="fa-solid fa-user" id="previewIcon" aria-hidden="true"></i>

                <?php endif; ?>

            </div>

            <p class="text-center text-muted small mb-4">
                Preview foto profil
            </p>


            <!-- ===== INPUT URL FOTO ===== -->

            <div class="mb-3">

                <label for="foto" class="form-label fw-medium small">
                    Link Foto Profil (http/https)
                </label>

                <input type="url"
                       id="foto"
                       name="foto"
                       class="form-control"
                       placeholder="https://contoh.com/foto-saya.jpg"
                       maxlength="2048"
                       value="<?= htmlspecialchars($nilai_input); ?>">

                <div class="form-text">
                    Kosongkan untuk menghapus foto.
                </div>

            </div>


            <!-- ===== DATA READ-ONLY ===== -->

            <div class="mb-3">
                <label for="nama" class="form-label fw-medium small">Nama</label>
                <input type="text" id="nama" class="form-control"
                       value="<?= htmlspecialchars($nama); ?>" readonly>
            </div>

            <div class="row g-3 mb-4">

                <div class="col-7">
                    <label for="nisn" class="form-label fw-medium small">NISN</label>
                    <input type="text" id="nisn" class="form-control"
                           value="<?= htmlspecialchars($nisn); ?>" readonly>
                </div>

                <div class="col-5">
                    <label for="kelas" class="form-label fw-medium small">Kelas</label>
                    <input type="text" id="kelas" class="form-control"
                           value="<?= htmlspecialchars($kelas); ?>" readonly>
                </div>

            </div>


            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                <i class="fa-solid fa-floppy-disk me-2"></i>
                Simpan Perubahan
            </button>

        </div>

    </form>

</main>


<?php
$nav_aktif = "profil";   // halaman ini bagian dari menu Profil
include "bottom-nav.php";
?>

<script>
/* Preview langsung saat link diketik / ditempel (belum tersimpan sebelum klik Simpan) */

const inputFoto = document.getElementById("foto");
const kotak = document.getElementById("previewBox");

function tampilIkon() {
    kotak.innerHTML = '<i class="fa-solid fa-user" aria-hidden="true"></i>';
}

inputFoto.addEventListener("input", function () {

    const url = this.value.trim();

    if (!/^https?:\/\//i.test(url)) {
        tampilIkon();
        return;
    }

    const img = new Image();
    img.referrerPolicy = "no-referrer";
    img.alt = "Foto profil";
    img.onerror = tampilIkon;
    img.src = url;

    kotak.innerHTML = "";
    kotak.appendChild(img);
});

const gambarAwal = document.getElementById("previewImg");

if (gambarAwal) {
    gambarAwal.onerror = tampilIkon;
}
</script>

</body>

</html>