<?php

require_once "../config/database.php";
require_once "../config/session.php";
require_once __DIR__ . "/helpers.php";

requireRole("admin");

$id_admin_login = (int) $_SESSION["user_id"];

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

/* Pesan sekali tampil (Post-Redirect-Get) */

$flash = $_SESSION["flash_profil"] ?? null;
unset($_SESSION["flash_profil"]);

$error_profil = "";
$error_password = "";

function csrfValid()
{
    return isset($_POST["csrf_token"])
        && is_string($_POST["csrf_token"])
        && hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"]);
}

/* =========================================
   SIMPAN PROFIL (nama, email, foto)
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["simpan_profil"])) {

    if (!csrfValid()) {
        http_response_code(403);
        exit("Permintaan tidak valid.");
    }

    $nama_input  = trim($_POST["nama"] ?? "");
    $email_input = trim($_POST["email"] ?? "");
    $foto_input  = trim($_POST["foto"] ?? "");

    $foto_simpan = null;

    if ($nama_input === "" || mb_strlen($nama_input, "UTF-8") > 100) {

        $error_profil = "Nama wajib diisi (maksimal 100 karakter).";

    } elseif (!filter_var($email_input, FILTER_VALIDATE_EMAIL) || strlen($email_input) > 100) {

        $error_profil = "Format email tidak valid.";

    } elseif ($foto_input !== "" && strlen($foto_input) > 2048) {

        $error_profil = "Link foto terlalu panjang (maksimal 2048 karakter).";

    } elseif (
        $foto_input !== ""
        && (!filter_var($foto_input, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $foto_input))
    ) {

        $error_profil = "Link foto tidak valid. Gunakan alamat yang diawali http:// atau https://";

    } else {

        $foto_simpan = $foto_input === "" ? null : $foto_input;
    }

    if ($error_profil === "") {

        try {

            $stmt = $conn->prepare("
                UPDATE admin
                SET nama = ?, email = ?, foto = ?
                WHERE id_admin = ?
            ");

            $stmt->bind_param("sssi", $nama_input, $email_input, $foto_simpan, $id_admin_login);
            $stmt->execute();

            $_SESSION["nama"] = $nama_input;

            $_SESSION["flash_profil"] = ["tipe" => "success", "pesan" => "Profil berhasil diperbarui."];

            header("Location: edit_profil.php");
            exit;

        } catch (mysqli_sql_exception $e) {

            if ((int) $e->getCode() === 1062) {
                $error_profil = "Email sudah dipakai admin lain.";
            } elseif ((int) $e->getCode() === 1054) {
                $error_profil = "Kolom foto belum ada. Jalankan sql/migrasi_admin_profil.sql di phpMyAdmin dulu.";
            } else {
                $error_profil = "Gagal menyimpan profil. Coba lagi.";
            }
        }
    }
}

/* =========================================
   GANTI PASSWORD
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["ganti_password"])) {

    if (!csrfValid()) {
        http_response_code(403);
        exit("Permintaan tidak valid.");
    }

    $lama   = $_POST["password_lama"] ?? "";
    $baru   = $_POST["password_baru"] ?? "";
    $ulangi = $_POST["password_ulangi"] ?? "";

    $stmt = $conn->prepare("SELECT password FROM admin WHERE id_admin = ?");
    $stmt->bind_param("i", $id_admin_login);
    $stmt->execute();

    $hash = $stmt->get_result()->fetch_assoc()["password"] ?? "";

    if (!password_verify($lama, $hash)) {

        $error_password = "Password lama salah.";

    } elseif (strlen($baru) < 8) {

        $error_password = "Password baru minimal 8 karakter.";

    } elseif ($baru !== $ulangi) {

        $error_password = "Konfirmasi password baru tidak sama.";

    } else {

        $hash_baru = password_hash($baru, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE id_admin = ?");
        $stmt->bind_param("si", $hash_baru, $id_admin_login);
        $stmt->execute();

        $_SESSION["flash_profil"] = ["tipe" => "success", "pesan" => "Password berhasil diganti."];

        header("Location: edit_profil.php");
        exit;
    }
}

/* =========================================
   DATA ADMIN UNTUK FORM
========================================= */

$data = ["nama" => "", "username" => "", "email" => "", "foto" => ""];

try {
    $stmt = $conn->prepare("SELECT nama, username, email, foto FROM admin WHERE id_admin = ?");
} catch (Throwable $e) {
    $stmt = $conn->prepare("SELECT nama, username, email FROM admin WHERE id_admin = ?");
}

$stmt->bind_param("i", $id_admin_login);
$stmt->execute();

$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    header("Location: ../akun/logout.php");
    exit;
}

$data = array_merge($data, array_filter($row, function ($v) { return $v !== null; }));

/* jika validasi gagal, tampilkan kembali isian terakhir */

$nilai_nama  = $error_profil !== "" ? ($nama_input  ?? $data["nama"])  : $data["nama"];
$nilai_email = $error_profil !== "" ? ($email_input ?? $data["email"]) : $data["email"];
$nilai_foto  = $error_profil !== "" ? ($foto_input  ?? $data["foto"])  : $data["foto"];

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Profil - MUSA Complaint</title>

    <link rel="stylesheet" href="../asests/css/admin_profil.css">

</head>

<body>

<?php $halaman_aktif = "profil"; include __DIR__ . "/navbar.php"; ?>


<main class="profil-page">

    <a href="admin_dashboard.php" class="back-link">
        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        Kembali ke Dashboard
    </a>

    <h1>Edit Profil</h1>
    <p class="sub">Perbarui data diri dan foto profil admin.</p>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash["tipe"]); ?>" role="status">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <?= htmlspecialchars($flash["pesan"]); ?>
        </div>
    <?php endif; ?>


    <div class="profil-grid">

        <!-- ========== DATA PROFIL ========== -->

        <form method="POST" class="box">

            <input type="hidden" name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION["csrf_token"]); ?>">

            <h2><i class="fa-solid fa-id-card" aria-hidden="true"></i> Data Profil</h2>

            <?php if ($error_profil !== ""): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                    <?= htmlspecialchars($error_profil); ?>
                </div>
            <?php endif; ?>

            <div class="foto-preview">

                <span class="adm-av foto-besar" id="previewFoto">
                    <b><?= htmlspecialchars(inisialNama($nilai_nama)); ?></b>
                    <?php if (fotoValid($nilai_foto) !== ""): ?>
                        <img src="<?= htmlspecialchars(fotoValid($nilai_foto)); ?>" alt=""
                             referrerpolicy="no-referrer" onerror="this.remove()">
                    <?php endif; ?>
                </span>

                <p>Tempel link gambar (http/https) di kolom Foto Profil.<br>
                   Kosongkan untuk memakai inisial.</p>

            </div>

            <label for="foto">Foto Profil (URL)</label>
            <input type="url" id="foto" name="foto" maxlength="2048"
                   placeholder="https://contoh.com/fotoku.jpg"
                   value="<?= htmlspecialchars($nilai_foto); ?>">

            <label for="nama">Nama Lengkap</label>
            <input type="text" id="nama" name="nama" maxlength="100" required
                   value="<?= htmlspecialchars($nilai_nama); ?>">

            <label for="email">Email</label>
            <input type="email" id="email" name="email" maxlength="100" required
                   value="<?= htmlspecialchars($nilai_email); ?>">

            <label for="username">Username</label>
            <input type="text" id="username" value="<?= htmlspecialchars($data["username"]); ?>" disabled>
            <small class="hint">Username tidak dapat diubah.</small>

            <button type="submit" name="simpan_profil" class="btn-simpan">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                Simpan Perubahan
            </button>

        </form>


        <!-- ========== GANTI PASSWORD ========== -->

        <form method="POST" class="box">

            <input type="hidden" name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION["csrf_token"]); ?>">

            <h2><i class="fa-solid fa-lock" aria-hidden="true"></i> Ganti Password</h2>

            <?php if ($error_password !== ""): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                    <?= htmlspecialchars($error_password); ?>
                </div>
            <?php endif; ?>

            <label for="password_lama">Password Lama</label>
            <input type="password" id="password_lama" name="password_lama"
                   autocomplete="current-password" required>

            <label for="password_baru">Password Baru</label>
            <input type="password" id="password_baru" name="password_baru"
                   minlength="8" autocomplete="new-password" required>
            <small class="hint">Minimal 8 karakter.</small>

            <label for="password_ulangi">Ulangi Password Baru</label>
            <input type="password" id="password_ulangi" name="password_ulangi"
                   minlength="8" autocomplete="new-password" required>

            <button type="submit" name="ganti_password" class="btn-simpan btn-gelap">
                <i class="fa-solid fa-key" aria-hidden="true"></i>
                Ganti Password
            </button>

        </form>

    </div>

</main>

<script src="../asests/js/logout.js"></script>

<script>
    /* Pratinjau foto langsung saat link diketik/ditempel */
    (function () {

        var input = document.getElementById("foto");
        var kotak = document.getElementById("previewFoto");

        if (!input || !kotak) { return; }

        input.addEventListener("input", function () {

            var lama = kotak.querySelector("img");

            if (lama) { lama.remove(); }

            var url = input.value.trim();

            if (/^https?:\/\//i.test(url)) {

                var img = document.createElement("img");

                img.alt = "";
                img.referrerPolicy = "no-referrer";
                img.onerror = function () { img.remove(); };
                img.src = url;

                kotak.appendChild(img);
            }
        });
    })();
</script>

</body>

</html>