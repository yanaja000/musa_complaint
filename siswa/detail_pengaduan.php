<?php

require_once "../config/database.php";
require_once "../config/session.php";

requireRole("siswa");

$id_siswa = $_SESSION["user_id"];

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: pengaduan_saya.php");
    exit;
}

$id_pengaduan = (int) $_GET["id"];

/* =========================================
   AMBIL DATA PENGADUAN
========================================= */

$stmt = $conn->prepare("
    SELECT
        p.id_pengaduan,
        p.judul,
        p.isi_pengaduan,
        p.lokasi,
        p.created_at,
        p.updated_at,
        k.nama_kategori,
        s.nama_status
    FROM pengaduan p
    JOIN kategori k
        ON p.id_kategori = k.id_kategori
    JOIN status_pengaduan s
        ON p.id_status = s.id_status
    WHERE p.id_pengaduan = ?
    AND p.id_siswa = ?
");

$stmt->bind_param("ii", $id_pengaduan, $id_siswa);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: pengaduan_saya.php");
    exit;
}

$pengaduan = $result->fetch_assoc();


/* =========================================
   RIWAYAT STATUS
========================================= */

$stmt_status = $conn->prepare("
    SELECT
        r.catatan,
        r.created_at,
        s.nama_status,
        a.nama AS nama_admin
    FROM riwayat_status r
    JOIN status_pengaduan s
        ON r.id_status = s.id_status
    LEFT JOIN admin a
        ON r.id_admin = a.id_admin
    WHERE r.id_pengaduan = ?
    ORDER BY r.created_at ASC
");

$stmt_status->bind_param("i", $id_pengaduan);
$stmt_status->execute();

$riwayat = $stmt_status->get_result();


/* =========================================
   TANGGAPAN ADMIN
========================================= */

$stmt_tanggapan = $conn->prepare("
    SELECT
        t.isi_tanggapan,
        t.created_at,
        a.nama AS nama_admin
    FROM tanggapan t
    JOIN admin a
        ON t.id_admin = a.id_admin
    WHERE t.id_pengaduan = ?
    ORDER BY t.created_at ASC
");

$stmt_tanggapan->bind_param("i", $id_pengaduan);
$stmt_tanggapan->execute();

$tanggapan = $stmt_tanggapan->get_result();

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Detail Pengaduan - MUSA Complaint</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
          rel="stylesheet">

    <link rel="stylesheet"
          href="../asests/css/detail_pengaduan.css">

</head>

<body>


<!-- ================= NAVBAR ================= -->

<nav class="navbar">

    <div class="logo">
        MUSA <span>Complaint</span>
    </div>

    <div class="menu">

        <a href="siswa_dashboard.php">
            Dashboard
        </a>

        <a href="buat_pengaduan.php">
            Buat Pengaduan
        </a>

        <a href="pengaduan_saya.php">
            Pengaduan Saya
        </a>

        <a href="../akun/logout.php">
            Logout
        </a>

    </div>

</nav>


<!-- ================= CONTENT ================= -->

<main class="container">

    <a href="pengaduan_saya.php" class="back">
        ← Kembali ke Pengaduan Saya
    </a>


    <!-- HEADER -->

    <div class="detail-header">

        <div>

            <span class="category">
                <?= htmlspecialchars($pengaduan["nama_kategori"]); ?>
            </span>

            <h1>
                <?= htmlspecialchars($pengaduan["judul"]); ?>
            </h1>

        </div>

        <span class="status">
            <?= htmlspecialchars($pengaduan["nama_status"]); ?>
        </span>

    </div>


    <!-- INFO -->

    <div class="info-grid">

        <div class="info-box">

            <small>Lokasi</small>

            <p>
                <?= htmlspecialchars($pengaduan["lokasi"]); ?>
            </p>

        </div>


        <div class="info-box">

            <small>Tanggal Pengaduan</small>

            <p>
                <?= date(
                    "d-m-Y H:i",
                    strtotime($pengaduan["created_at"])
                ); ?>
            </p>

        </div>

    </div>


    <!-- ISI PENGADUAN -->

    <section class="card">

        <h2>Isi Pengaduan</h2>

        <p class="content-text">
            <?= nl2br(
                htmlspecialchars($pengaduan["isi_pengaduan"])
            ); ?>
        </p>

    </section>

     <!-- FOTO BUKTI -->

 <section class="card">

    <h2>Foto Bukti Pengaduan</h2>

    <?php

    $stmt_lampiran = $conn->prepare("
        SELECT
            nama_file,
            path_file,
            tipe_file
        FROM lampiran
        WHERE id_pengaduan = ?
        ORDER BY created_at ASC
    ");

    $stmt_lampiran->bind_param("i", $id_pengaduan);
    $stmt_lampiran->execute();

    $lampiran = $stmt_lampiran->get_result();

    ?>

    <?php if ($lampiran->num_rows > 0): ?>

        <div class="foto-bukti">

            <?php while ($file = $lampiran->fetch_assoc()): ?>

                <?php if (strpos($file["tipe_file"], "image/") === 0): ?>

                    <div class="foto-item">

                        <img
                            src="../<?= htmlspecialchars($file["path_file"]); ?>"
                            alt="Foto bukti pengaduan"
                        >

                        <p>
                            <?= htmlspecialchars($file["nama_file"]); ?>
                        </p>

                    </div>

                <?php endif; ?>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <p class="empty-text">
            Tidak ada foto bukti yang dilampirkan.
        </p>

    <?php endif; ?>

</section>


    <!-- RIWAYAT STATUS -->

    <section class="card">

        <h2>Riwayat Status</h2>

        <?php if ($riwayat->num_rows > 0): ?>

            <div class="timeline">

                <?php while ($row = $riwayat->fetch_assoc()): ?>

                    <div class="timeline-item">

                        <div class="timeline-dot"></div>

                        <div class="timeline-content">

                            <h3>
                                <?= htmlspecialchars(
                                    $row["nama_status"]
                                ); ?>
                            </h3>

                            <?php if (!empty($row["catatan"])): ?>

                                <p>
                                    <?= nl2br(
                                        htmlspecialchars(
                                            $row["catatan"]
                                        )
                                    ); ?>
                                </p>

                            <?php endif; ?>

                            <small>

                                <?= date(
                                    "d-m-Y H:i",
                                    strtotime(
                                        $row["created_at"]
                                    )
                                ); ?>

                                <?php if (!empty($row["nama_admin"])): ?>

                                    · Admin:
                                    <?= htmlspecialchars(
                                        $row["nama_admin"]
                                    ); ?>

                                <?php endif; ?>

                            </small>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <p class="empty-text">
                Belum ada riwayat status.
            </p>

        <?php endif; ?>

    </section>


    <!-- TANGGAPAN ADMIN -->

    <section class="card">

        <h2>Tanggapan Admin</h2>

        <?php if ($tanggapan->num_rows > 0): ?>

            <?php while ($row = $tanggapan->fetch_assoc()): ?>

                <div class="response">

                    <div class="response-header">

                        <strong>
                            <?= htmlspecialchars(
                                $row["nama_admin"]
                            ); ?>
                        </strong>

                        <small>
                            <?= date(
                                "d-m-Y H:i",
                                strtotime(
                                    $row["created_at"]
                                )
                            ); ?>
                        </small>

                    </div>

                    <p>
                        <?= nl2br(
                            htmlspecialchars(
                                $row["isi_tanggapan"]
                            )
                        ); ?>
                    </p>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <p class="empty-text">
                Belum ada tanggapan dari admin.
            </p>

        <?php endif; ?>

    </section>

</main>

</body>
</html>