<?php

require_once "../config/database.php";
require_once "../config/session.php";

requireRole("siswa");

$id_siswa = $_SESSION["user_id"];
$nama = $_SESSION["nama"];

/* =========================================
   STATISTIK PENGADUAN SISWA
========================================= */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM pengaduan
    WHERE id_siswa = ?
");

$stmt->bind_param("i", $id_siswa);
$stmt->execute();

$total = $stmt->get_result()->fetch_assoc()["total"];


/* Pengaduan menunggu */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS jumlah
    FROM pengaduan
    WHERE id_siswa = ?
    AND id_status = 1
");

$stmt->bind_param("i", $id_siswa);
$stmt->execute();

$menunggu = $stmt->get_result()->fetch_assoc()["jumlah"];


/* Pengaduan diproses */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS jumlah
    FROM pengaduan
    WHERE id_siswa = ?
    AND id_status = 2
");

$stmt->bind_param("i", $id_siswa);
$stmt->execute();

$diproses = $stmt->get_result()->fetch_assoc()["jumlah"];


/* Pengaduan selesai */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS jumlah
    FROM pengaduan
    WHERE id_siswa = ?
    AND id_status = 4
");

$stmt->bind_param("i", $id_siswa);
$stmt->execute();

$selesai = $stmt->get_result()->fetch_assoc()["jumlah"];


/* =========================================
   PENGADUAN TERBARU
========================================= */

$stmt = $conn->prepare("
    SELECT
        p.id_pengaduan,
        p.judul,
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
    LIMIT 5
");

$stmt->bind_param("i", $id_siswa);
$stmt->execute();

$pengaduan = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Dashboard Siswa - MUSA Complaint</title>

    <link rel="stylesheet"
          href="../asests/css/siswa.css">

</head>

<body>

<!-- =========================================
     NAVBAR
========================================= -->

<nav class="dashboard-navbar">

    <div class="dashboard-logo">
        MUSA <span>Complaint</span>
    </div>

    <div class="dashboard-menu">

        <a href="dashboard.php" class="active">
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


<!-- =========================================
     MAIN
========================================= -->

<main class="dashboard-container">

    <!-- HEADER -->

    <section class="dashboard-welcome">

        <div>

            <p class="dashboard-label">
                Dashboad Siswa
            </p>

            <h1>
                Halo, <?= htmlspecialchars($nama); ?> 👋
            </h1>

            <p>
                Sampaikan keluhan, laporan, atau saran
                untuk membantu membuat sekolah menjadi lebih baik.
            </p>

        </div>

        <a href="buat_pengaduan.php"
           class="primary-button">

            + Buat Pengaduan

        </a>

    </section>


    <!-- =========================================
         STATISTIK
    ========================================= -->

    <section class="dashboard-stats">

        <div class="stat-card">

            <div class="stat-icon">
                #
            </div>

            <div>

                <span>
                    Total Pengaduan
                </span>

                <strong>
                    <?= $total; ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                01
            </div>

            <div>

                <span>
                    Menunggu
                </span>

                <strong>
                    <?= $menunggu; ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                02
            </div>

            <div>

                <span>
                    Diproses
                </span>

                <strong>
                    <?= $diproses; ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ✓
            </div>

            <div>

                <span>
                    Selesai
                </span>

                <strong>
                    <?= $selesai; ?>
                </strong>

            </div>

        </div>

    </section>


    <!-- =========================================
         PENGADUAN TERBARU
    ========================================= -->

    <section class="dashboard-section">

        <div class="section-heading">

            <div>

                <h2>
                    Pengaduan Terbaru
                </h2>

                <p>
                    Pengaduan yang baru saja kamu kirim.
                </p>

            </div>

            <a href="pengaduan-saya.php">
                Lihat Semua
            </a>

        </div>


        <div class="complaint-list">

            <?php if ($pengaduan->num_rows > 0): ?>

                <?php while ($row = $pengaduan->fetch_assoc()): ?>

                    <div class="complaint-card">

                        <div class="complaint-info">

                            <h3>
                                <?= htmlspecialchars($row["judul"]); ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars($row["nama_kategori"]); ?>
                                ·
                                <?= htmlspecialchars($row["lokasi"]); ?>
                            </p>

                            <small>
                                <?= date(
                                    "d M Y, H:i",
                                    strtotime($row["created_at"])
                                ); ?>
                            </small>

                        </div>

                        <div class="complaint-status">

                            <span>
                                <?= htmlspecialchars($row["nama_status"]); ?>
                            </span>

                        </div>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="empty-state">

                    <h3>
                        Belum ada pengaduan
                    </h3>

                    <p>
                        Kamu belum mengirim pengaduan.
                    </p>

                    <a href="buat_pengaduan.php"
                       class="primary-button">

                        Buat Pengaduan

                    </a>

                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

</body>

</html>