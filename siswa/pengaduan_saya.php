<?php

require_once "../config/database.php";
require_once "../config/session.php";

requireRole("siswa");

$id_siswa = $_SESSION["user_id"];

// Token CSRF untuk form hapus
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$pesan = "";
$pesan_tipe = "";

/* =========================
   HAPUS PENGADUAN (POST)
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["hapus"])) {

    // Validasi CSRF
    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        http_response_code(403);
        die("Permintaan tidak valid.");
    }

    $id_pengaduan = (int) $_POST["hapus"];

    // Supaya error SQL benar-benar melempar exception
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {

        // Pastikan pengaduan milik siswa yang sedang login
        $stmt = $conn->prepare("
            SELECT id_status
            FROM pengaduan
            WHERE id_pengaduan = ?
            AND id_siswa = ?
        ");
        $stmt->bind_param("ii", $id_pengaduan, $id_siswa);
        $stmt->execute();
        $pengaduan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$pengaduan) {
            $pesan = "Pengaduan tidak ditemukan.";
            $pesan_tipe = "error";
        } elseif ((int) $pengaduan["id_status"] !== 1) {
            // Hanya status MENUNGGU yang boleh dihapus
            $pesan = "Pengaduan yang sudah diproses tidak dapat dihapus.";
            $pesan_tipe = "error";
        } else {

            // Ambil daftar file lampiran (belum dihapus dari disk)
            $stmt = $conn->prepare("
                SELECT path_file
                FROM lampiran
                WHERE id_pengaduan = ?
            ");
            $stmt->bind_param("i", $id_pengaduan);
            $stmt->execute();
            $files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $conn->begin_transaction();

            try {

                // Hapus lampiran
                $stmt = $conn->prepare("DELETE FROM lampiran WHERE id_pengaduan = ?");
                $stmt->bind_param("i", $id_pengaduan);
                $stmt->execute();
                $stmt->close();

                // Hapus tanggapan
                $stmt = $conn->prepare("DELETE FROM tanggapan WHERE id_pengaduan = ?");
                $stmt->bind_param("i", $id_pengaduan);
                $stmt->execute();
                $stmt->close();

                // Hapus riwayat status
                $stmt = $conn->prepare("DELETE FROM riwayat_status WHERE id_pengaduan = ?");
                $stmt->bind_param("i", $id_pengaduan);
                $stmt->execute();
                $stmt->close();

                // Terakhir hapus pengaduan (status dicek ulang agar aman
                // jika admin baru saja memproses pengaduan ini)
                $stmt = $conn->prepare("
                    DELETE FROM pengaduan
                    WHERE id_pengaduan = ?
                    AND id_siswa = ?
                    AND id_status = 1
                ");
                $stmt->bind_param("ii", $id_pengaduan, $id_siswa);
                $stmt->execute();

                if ($stmt->affected_rows !== 1) {
                    throw new Exception("Status pengaduan sudah berubah.");
                }

                $stmt->close();

                $conn->commit();

            } catch (Throwable $e) {

                $conn->rollback();
                throw $e;
            }

            // Hapus file foto dari folder SETELAH database berhasil dihapus
            $folder_upload = realpath(__DIR__ . "/../uploads/pengaduan");

            foreach ($files as $file) {

                $file_path = realpath(__DIR__ . "/../" . $file["path_file"]);

                if (
                    $folder_upload !== false &&
                    $file_path !== false &&
                    strpos($file_path, $folder_upload . DIRECTORY_SEPARATOR) === 0 &&
                    is_file($file_path)
                ) {
                    unlink($file_path);
                }
            }

            header("Location: pengaduan_saya.php?hapus=berhasil");
            exit;
        }

    } catch (Throwable $e) {

        error_log("Hapus pengaduan gagal: " . $e->getMessage());

        $pesan = "Pengaduan gagal dihapus. Silakan coba lagi.";
        $pesan_tipe = "error";
    }
}

if (isset($_GET["hapus"]) && $_GET["hapus"] === "berhasil") {
    $pesan = "Pengaduan berhasil dihapus.";
    $pesan_tipe = "success";
}

/* Ambil pengaduan milik siswa yang sedang login */
$stmt = $conn->prepare("
    SELECT
        p.id_pengaduan,
        p.id_status,
        p.judul,
        p.isi_pengaduan,
        p.lokasi,
        p.created_at,

        k.nama_kategori,
        s.nama_status
    FROM pengaduan p
    JOIN kategori k
        ON p.id_kategori = k.id_kategori
    JOIN status_pengaduan s
        ON p.id_status = s.id_status
    WHERE p.id_siswa = ?
    ORDER BY p.created_at DESC
");

$stmt->bind_param("i", $id_siswa);
$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pengaduan Saya - MUSA Complaint</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="../asests/css/pengaduan_saya.css"
    >

</head>

<body>

<!-- NAVBAR -->

<nav class="dashboard-navbar">

    <div class="dashboard-logo">
        MUSA <span>Complaint</span>
    </div>

    <div class="dashboard-menu">

        <a href="siswa_dashboard.php">
            Dashboard
        </a>

        <a href="buat_pengaduan.php">
            Buat Pengaduan
        </a>

        <a href="pengaduan_saya.php" class="active">
            Pengaduan Saya
        </a>

        <a href="../akun/logout.php">
            Logout
        </a>

    </div>

</nav>


<!-- CONTENT -->

<main class="container">

    <div class="header">

        <div>

            <p>MUSA COMPLAINT</p>

            <h1>Pengaduan Saya</h1>

            <span>
                Daftar pengaduan yang telah kamu kirim.
            </span>

        </div>

        <a
            href="buat_pengaduan.php"
            class="button"
        >
            + Buat Pengaduan
        </a>

    </div>


    <?php if ($pesan !== ""): ?>
        <div class="alert alert-<?= $pesan_tipe; ?>">
            <?= htmlspecialchars($pesan); ?>
        </div>
    <?php endif; ?>

    <!-- DAFTAR -->

    <div class="list">

        <?php if ($result->num_rows > 0): ?>

            <?php while ($row = $result->fetch_assoc()): ?>

                <div class="card">

                    <div class="card-header">

                        <div>

                            <span class="category">
                                <?= htmlspecialchars($row["nama_kategori"]); ?>
                            </span>

                            <h2>
                                <?= htmlspecialchars($row["judul"]); ?>
                            </h2>

                        </div>

                        <span class="status">
                            <?= htmlspecialchars($row["nama_status"]); ?>
                        </span>

                    </div>


                    <div class="info">

                        <div>
                            <small>Lokasi</small>
                            <p>
                                <?= htmlspecialchars($row["lokasi"]); ?>
                            </p>
                        </div>

                        <div>
                            <small>Tanggal</small>
                            <p>
                                <?= date(
                                    "d-m-Y H:i",
                                    strtotime($row["created_at"])
                                ); ?>
                            </p>
                        </div>

                    </div>


                    <p class="description">

                        <?= htmlspecialchars(
                            mb_strimwidth(
                                $row["isi_pengaduan"],
                                0,
                                180,
                                "..."
                            )
                        ); ?>

                    </p>


                    <div class="card-footer">

                        <a
                            href="detail_pengaduan.php?id=<?= $row["id_pengaduan"]; ?>"
                            class="detail"
                        >
                            Lihat Detail
                        </a>

                        <?php if ((int) $row["id_status"] === 1): ?>

<form
    method="post"
    action="pengaduan_saya.php"
    class="form-delete"
    data-judul="<?= htmlspecialchars($row["judul"]); ?>"
>
    <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars($_SESSION["csrf_token"]); ?>"
    >
    <input
        type="hidden"
        name="hapus"
        value="<?= (int) $row["id_pengaduan"]; ?>"
    >
    <button type="submit" class="btn-delete">
        Hapus
    </button>
</form>

                        <?php endif; ?>


                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty">

                <h2>
                    Belum Ada Pengaduan
                </h2>

                <p>
                    Kamu belum mengirim pengaduan apa pun.
                </p>

                <a
                    href="buat_pengaduan.php"
                    class="button"
                >
                    Buat Pengaduan
                </a>

            </div>

        <?php endif; ?>

    </div>

</main>

<div class="confirm-overlay" id="deleteOverlay">

    <div class="confirm-box" role="dialog" aria-modal="true">

        <div class="confirm-icon">!</div>

        <h3>Hapus pengaduan?</h3>

        <p>
            Pengaduan
            <strong id="deleteJudul"></strong>
            beserta foto buktinya akan dihapus permanen
            dan tidak dapat dikembalikan.
        </p>

        <div class="confirm-actions">

            <button type="button" class="confirm-cancel" id="deleteCancel">
                Batal
            </button>

            <button type="button" class="confirm-yes" id="deleteYes">
                Ya, Hapus
            </button>

        </div>

    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    var overlay = document.getElementById("deleteOverlay");
    var judul   = document.getElementById("deleteJudul");
    var yes     = document.getElementById("deleteYes");
    var cancel  = document.getElementById("deleteCancel");

    var formAktif = null;

    function tutup() {
        overlay.classList.remove("show");
        formAktif = null;
    }

    document.querySelectorAll(".form-delete").forEach(function (form) {

        form.addEventListener("submit", function (e) {

            e.preventDefault();

            formAktif = form;
            judul.textContent = "\u201C" + form.dataset.judul + "\u201D";

            overlay.classList.add("show");
            cancel.focus();
        });
    });

    yes.addEventListener("click", function () {

        if (formAktif) {
            yes.disabled = true;
            yes.textContent = "Menghapus...";
            formAktif.submit();
        }
    });

    cancel.addEventListener("click", tutup);

    overlay.addEventListener("click", function (e) {
        if (e.target === overlay) {
            tutup();
        }
    });

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            tutup();
        }
    });
});
</script>

<script src="../asests/js/logout.js"></script>

</body>

</html>