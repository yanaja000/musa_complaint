<?php

require_once "../config/database.php";
require_once "../config/session.php";

requireRole("admin");

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

/* =========================
   CEK ID PENGADUAN
========================= */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: pengaduan.php");
    exit;
}

$id_pengaduan = (int) $_GET["id"];

$id_admin = (int) $_SESSION["user_id"];
$hapus_error = "";


/* =========================
   PROSES HAPUS PENGADUAN
========================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["hapus_pengaduan"])
) {
    if (
        !isset($_POST["csrf_token"])
        || !is_string($_POST["csrf_token"])
        || !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        http_response_code(403);
        exit("Permintaan tidak valid.");
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $files = [];
    $transaction_started = false;

    try {
        $stmt_files = $conn->prepare("
            SELECT path_file
            FROM lampiran
            WHERE id_pengaduan = ?
        ");
        $stmt_files->bind_param("i", $id_pengaduan);
        $stmt_files->execute();
        $files = $stmt_files->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_files->close();

        $conn->begin_transaction();
        $transaction_started = true;

        foreach (["lampiran", "tanggapan", "riwayat_status"] as $table) {
            $stmt_delete = $conn->prepare(
                "DELETE FROM {$table} WHERE id_pengaduan = ?"
            );
            $stmt_delete->bind_param("i", $id_pengaduan);
            $stmt_delete->execute();
            $stmt_delete->close();
        }

        $stmt_delete_pengaduan = $conn->prepare("
            DELETE FROM pengaduan
            WHERE id_pengaduan = ?
        ");
        $stmt_delete_pengaduan->bind_param("i", $id_pengaduan);
        $stmt_delete_pengaduan->execute();

        if ($stmt_delete_pengaduan->affected_rows !== 1) {
            throw new RuntimeException("Pengaduan tidak ditemukan.");
        }

        $stmt_delete_pengaduan->close();
        $conn->commit();
        $transaction_started = false;
    } catch (Throwable $e) {
        if ($transaction_started) {
            $conn->rollback();
        }

        error_log("Hapus pengaduan admin gagal: " . $e->getMessage());
        $hapus_error = "Pengaduan gagal dihapus. Silakan coba lagi.";
    }

    if ($hapus_error === "") {
        $folder_upload = realpath(__DIR__ . "/../uploads/pengaduan");

        foreach ($files as $file) {
            if (!isset($file["path_file"]) || !is_string($file["path_file"])) {
                continue;
            }

            $file_path = realpath(__DIR__ . "/../" . $file["path_file"]);

            if (
                $folder_upload !== false
                && $file_path !== false
                && strpos($file_path, $folder_upload . DIRECTORY_SEPARATOR) === 0
                && is_file($file_path)
                && !unlink($file_path)
            ) {
                error_log("Gagal menghapus file lampiran pengaduan: " . $file_path);
            }
        }

        header("Location: pengaduan.php");
        exit;
    }
}


/* =========================
   PROSES UBAH STATUS
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["ubah_status"])) {

    $id_status_baru = (int) $_POST["id_status"];
    $catatan = trim($_POST["catatan"] ?? "");

    if ($id_status_baru > 0) {

        /* Ambil status sekarang */
        $stmt_status_lama = $conn->prepare("
            SELECT id_status
            FROM pengaduan
            WHERE id_pengaduan = ?
        ");

        $stmt_status_lama->bind_param(
            "i",
            $id_pengaduan
        );

        $stmt_status_lama->execute();

        $result_status_lama =
            $stmt_status_lama->get_result();

        $data_status_lama =
            $result_status_lama->fetch_assoc();


        if ($data_status_lama) {

            $id_status_lama =
                (int) $data_status_lama["id_status"];


            /* Update status pengaduan */

            $stmt_update = $conn->prepare("
                UPDATE pengaduan
                SET
                    id_status = ?,
                    updated_at = NOW()
                WHERE id_pengaduan = ?
            ");

            $stmt_update->bind_param(
                "ii",
                $id_status_baru,
                $id_pengaduan
            );

            $stmt_update->execute();


            /* Simpan riwayat hanya jika status berubah */

            if ($id_status_lama !== $id_status_baru) {

                $stmt_history = $conn->prepare("
                    INSERT INTO riwayat_status
                    (
                        id_pengaduan,
                        id_status,
                        id_admin,
                        catatan,
                        created_at
                    )
                    VALUES (?, ?, ?, ?, NOW())
                ");

                $stmt_history->bind_param(
                    "iiis",
                    $id_pengaduan,
                    $id_status_baru,
                    $id_admin,
                    $catatan
                );

                $stmt_history->execute();
            }
        }
    }

    header(
        "Location: detail_pengaduan.php?id=" .
        $id_pengaduan
    );

    exit;
}


/* =========================
   PROSES TANGGAPAN
========================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["kirim_tanggapan"])
) {

    $isi_tanggapan =
        trim($_POST["isi_tanggapan"] ?? "");

    if ($isi_tanggapan !== "") {

        $stmt_tanggapan = $conn->prepare("
            INSERT INTO tanggapan
            (
                id_pengaduan,
                id_admin,
                isi_tanggapan,
                created_at
            )
            VALUES (?, ?, ?, NOW())
        ");

        $stmt_tanggapan->bind_param(
            "iis",
            $id_pengaduan,
            $id_admin,
            $isi_tanggapan
        );

        $stmt_tanggapan->execute();
    }

    header(
        "Location: detail_pengaduan.php?id=" .
        $id_pengaduan
    );

    exit;
}


/* =========================
   DATA PENGADUAN
========================= */

$stmt = $conn->prepare("
    SELECT
        p.*,

        s.nama AS nama_siswa,
        s.nis,
        s.kelas,
        s.email AS email_siswa,

        k.nama_kategori,

        sp.nama_status

    FROM pengaduan p

    INNER JOIN siswa s
        ON p.id_siswa = s.id_siswa

    INNER JOIN kategori k
        ON p.id_kategori = k.id_kategori

    INNER JOIN status_pengaduan sp
        ON p.id_status = sp.id_status

    WHERE p.id_pengaduan = ?
");

$stmt->bind_param(
    "i",
    $id_pengaduan
);

$stmt->execute();

$result = $stmt->get_result();

$pengaduan = $result->fetch_assoc();


if (!$pengaduan) {
    header("Location: pengaduan.php");
    exit;
}


/* =========================
   DATA STATUS
========================= */

$status_query = $conn->query("
    SELECT
        id_status,
        nama_status
    FROM status_pengaduan
    ORDER BY id_status ASC
");


/* =========================
   FOTO BUKTI
========================= */

$stmt_lampiran = $conn->prepare("
    SELECT
        nama_file,
        path_file,
        tipe_file
    FROM lampiran
    WHERE id_pengaduan = ?
    ORDER BY created_at ASC
");

$stmt_lampiran->bind_param(
    "i",
    $id_pengaduan
);

$stmt_lampiran->execute();

$lampiran = $stmt_lampiran->get_result();


/* =========================
   RIWAYAT STATUS
========================= */

$stmt_riwayat = $conn->prepare("
    SELECT
        rs.catatan,
        rs.created_at,

        sp.nama_status,

        a.nama AS nama_admin

    FROM riwayat_status rs

    INNER JOIN status_pengaduan sp
        ON rs.id_status = sp.id_status

    LEFT JOIN admin a
        ON rs.id_admin = a.id_admin

    WHERE rs.id_pengaduan = ?

    ORDER BY rs.created_at DESC
");

$stmt_riwayat->bind_param(
    "i",
    $id_pengaduan
);

$stmt_riwayat->execute();

$riwayat = $stmt_riwayat->get_result();


/* =========================
   TANGGAPAN
========================= */

$stmt_tanggapan = $conn->prepare("
    SELECT
        t.isi_tanggapan,
        t.created_at,

        a.nama AS nama_admin

    FROM tanggapan t

    INNER JOIN admin a
        ON t.id_admin = a.id_admin

    WHERE t.id_pengaduan = ?

    ORDER BY t.created_at DESC
");

$stmt_tanggapan->bind_param(
    "i",
    $id_pengaduan
);

$stmt_tanggapan->execute();

$tanggapan = $stmt_tanggapan->get_result();

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Detail Pengaduan - MUSA Complaint
    </title>

    <link
        rel="stylesheet"
        href="../asests/css/admin_detail_pengaduan.css"
    >

</head>

<body>


<!-- =========================
     NAVBAR
========================= -->

<nav class="navbar">

    <a
        href="admin_dashboard.php"
        class="logo"
    >
        <span>MUSA</span> Complaint
    </a>

    <div class="nav-menu">

        <a href="admin_dashboard.php">
            Dashboard
        </a>

        <a
            href="pengaduan.php"
            class="active"
        >
            Pengaduan
        </a>

        <a href="#">
            Siswa
        </a>

        <a href="../akun/logout.php">
            Logout
        </a>

    </div>

</nav>


<!-- =========================
     MAIN
========================= -->

<main class="container">


    <!-- HEADER -->

<div class="page-header">

    <div class="page-top">

        <a
            href="pengaduan.php"
            class="back-button"
        >
            ← Kembali ke Pengaduan
        </a>

        <form method="post" id="formHapus">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION["csrf_token"],
                    ENT_QUOTES,
                    "UTF-8"
                ); ?>"
            >

            <input type="hidden" name="hapus_pengaduan" value="1">

            <button
                type="submit"
                class="btn-delete"
                onclick="return confirm('Yakin ingin menghapus pengaduan ini?')"
            >
                Hapus Pengaduan
            </button>

        </form>

    </div>

    <?php if ($hapus_error !== ""): ?>
        <p class="delete-error" role="alert">
            <?= htmlspecialchars($hapus_error, ENT_QUOTES, "UTF-8"); ?>
        </p>
    <?php endif; ?>

    <h1>
        Detail Pengaduan
    </h1>

    <p>
        Informasi lengkap pengaduan siswa.
    </p>

</div>


    <!-- =========================
         INFORMASI SISWA
    ========================= -->

    <section class="card">

        <div class="card-title">

            <h2>
                Informasi Siswa
            </h2>

        </div>

        <div class="student-grid">

            <div class="info-item">

                <span>
                    Nama Siswa
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $pengaduan["nama_siswa"]
                    ); ?>
                </strong>

            </div>


            <div class="info-item">

                <span>
                    NIS
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $pengaduan["nis"]
                    ); ?>
                </strong>

            </div>


            <div class="info-item">

                <span>
                    Kelas
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $pengaduan["kelas"]
                    ); ?>
                </strong>

            </div>


            <div class="info-item">

                <span>
                    Email
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $pengaduan["email_siswa"]
                    ); ?>
                </strong>

            </div>

        </div>

    </section>


    <!-- =========================
         DETAIL PENGADUAN
    ========================= -->

    <section class="card">

        <div class="card-title">

            <h2>
                Isi Pengaduan
            </h2>

        </div>


        <div class="detail-grid">

            <div class="detail-item">

                <span>
                    Judul
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $pengaduan["judul"]
                    ); ?>
                </strong>

            </div>


            <div class="detail-item">

                <span>
                    Kategori
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $pengaduan["nama_kategori"]
                    ); ?>
                </strong>

            </div>


            <div class="detail-item">

                <span>
                    Lokasi
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $pengaduan["lokasi"]
                    ); ?>
                </strong>

            </div>


            <div class="detail-item">

                <span>
                    Tanggal
                </span>

                <strong>
                    <?= date(
                        "d/m/Y H:i",
                        strtotime(
                            $pengaduan["created_at"]
                        )
                    ); ?>
                </strong>

            </div>

        </div>


        <div class="complaint-content">

            <span>
                Isi Pengaduan
            </span>

            <p>
                <?= nl2br(
                    htmlspecialchars(
                        $pengaduan["isi_pengaduan"]
                    )
                ); ?>
            </p>

        </div>

    </section>


    <!-- =========================
         FOTO BUKTI
    ========================= -->

    <section class="card">

        <div class="card-title">

            <h2>
                Foto Bukti
            </h2>

        </div>


        <?php if ($lampiran->num_rows > 0): ?>

            <div class="foto-bukti">

                <?php while ($file = $lampiran->fetch_assoc()): ?>

                    <?php if (
                        strpos(
                            $file["tipe_file"],
                            "image/"
                        ) === 0
                    ): ?>

                        <div class="foto-item">

                            <img
                                src="../<?= htmlspecialchars(
                                    $file["path_file"]
                                ); ?>"
                                alt="Foto bukti pengaduan"
                            >

                            <p>
                                <?= htmlspecialchars(
                                    $file["nama_file"]
                                ); ?>
                            </p>

                        </div>

                    <?php endif; ?>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <p class="empty">
                Tidak ada foto bukti.
            </p>

        <?php endif; ?>

    </section>


    <!-- =========================
         KELOLA STATUS
    ========================= -->

    <section class="card">

        <div class="card-title">

            <h2>
                Kelola Status
            </h2>

        </div>


        <form method="POST">

            <div class="form-group">

                <label>
                    Status Pengaduan
                </label>

                <select
                    name="id_status"
                    required
                >

                    <?php while (
                        $status = $status_query->fetch_assoc()
                    ): ?>

                        <option
                            value="<?= $status["id_status"]; ?>"
                            <?= (
                                $status["id_status"]
                                == $pengaduan["id_status"]
                            )
                                ? "selected"
                                : ""; ?>
                        >
                            <?= htmlspecialchars(
                                $status["nama_status"]
                            ); ?>
                        </option>

                    <?php endwhile; ?>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Catatan
                </label>

                <textarea
                    name="catatan"
                    rows="4"
                    placeholder="Tambahkan catatan perubahan status..."
                ></textarea>

            </div>


            <button
                type="submit"
                name="ubah_status"
                class="primary-button"
            >
                Simpan Status
            </button>

        </form>

    </section>


    <!-- =========================
         TANGGAPAN ADMIN
    ========================= -->

    <section class="card">

        <div class="card-title">

            <h2>
                Berikan Tanggapan
            </h2>

        </div>


        <form method="POST">

            <div class="form-group">

                <label>
                    Tanggapan
                </label>

                <textarea
                    name="isi_tanggapan"
                    rows="5"
                    placeholder="Tulis tanggapan untuk siswa..."
                    required
                ></textarea>

            </div>


            <button
                type="submit"
                name="kirim_tanggapan"
                class="primary-button"
            >
                Kirim Tanggapan
            </button>

        </form>

    </section>


    <!-- =========================
         RIWAYAT STATUS
    ========================= -->

    <section class="card">

        <div class="card-title">

            <h2>
                Riwayat Status
            </h2>

        </div>


        <?php if ($riwayat->num_rows > 0): ?>

            <div class="timeline">

                <?php while (
                    $item = $riwayat->fetch_assoc()
                ): ?>

                    <div class="timeline-item">

                        <div class="timeline-dot"></div>

                        <div class="timeline-content">

                            <strong>
                                <?= htmlspecialchars(
                                    $item["nama_status"]
                                ); ?>
                            </strong>

                            <span>
                                <?= date(
                                    "d/m/Y H:i",
                                    strtotime(
                                        $item["created_at"]
                                    )
                                ); ?>
                                -
                                <?= htmlspecialchars(
                                    $item["nama_admin"]
                                    ?? "Sistem"
                                ); ?>
                            </span>

                            <?php if (
                                !empty($item["catatan"])
                            ): ?>

                                <p>
                                    <?= nl2br(
                                        htmlspecialchars(
                                            $item["catatan"]
                                        )
                                    ); ?>
                                </p>

                            <?php endif; ?>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <p class="empty">
                Belum ada riwayat status.
            </p>

        <?php endif; ?>

    </section>


    <!-- =========================
         TANGGAPAN SEBELUMNYA
    ========================= -->

    <section class="card">

        <div class="card-title">

            <h2>
                Tanggapan Admin
            </h2>

        </div>


        <?php if ($tanggapan->num_rows > 0): ?>

            <div class="response-list">

                <?php while (
                    $item = $tanggapan->fetch_assoc()
                ): ?>

                    <div class="response-item">

                        <div class="response-header">

                            <strong>
                                <?= htmlspecialchars(
                                    $item["nama_admin"]
                                ); ?>
                            </strong>

                            <span>
                                <?= date(
                                    "d/m/Y H:i",
                                    strtotime(
                                        $item["created_at"]
                                    )
                                ); ?>
                            </span>

                        </div>

                        <p>
                            <?= nl2br(
                                htmlspecialchars(
                                    $item["isi_tanggapan"]
                                )
                            ); ?>
                        </p>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <p class="empty">
                Belum ada tanggapan dari admin.
            </p>

        <?php endif; ?>

    </section>

</main>

<script src="../asests/js/logout.js"></script>

</body>

</html>