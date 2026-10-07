<?php

require_once "../config/database.php";
require_once "../config/session.php";

requireRole("admin");

/* =========================
   DATA STATISTIK
========================= */

$total_pengaduan = 0;
$total_menunggu = 0;
$total_diproses = 0;
$total_selesai = 0;

/* Total semua pengaduan */
$query_total = $conn->query("
    SELECT COUNT(*) AS total
    FROM pengaduan
");

if ($query_total) {
    $total_pengaduan = $query_total->fetch_assoc()["total"];
}

/* Menunggu */
$query_menunggu = $conn->query("
    SELECT COUNT(*) AS total
    FROM pengaduan
    WHERE id_status = 1
");

if ($query_menunggu) {
    $total_menunggu = $query_menunggu->fetch_assoc()["total"];
}

/* Diproses */
$query_diproses = $conn->query("
    SELECT COUNT(*) AS total
    FROM pengaduan
    WHERE id_status = 2
");

if ($query_diproses) {
    $total_diproses = $query_diproses->fetch_assoc()["total"];
}

/* Selesai */
$query_selesai = $conn->query("
    SELECT COUNT(*) AS total
    FROM pengaduan
    WHERE id_status = 4
");

if ($query_selesai) {
    $total_selesai = $query_selesai->fetch_assoc()["total"];
}


/* =========================
   PENGADUAN TERBARU
========================= */

$query_pengaduan = $conn->query("
    SELECT
        p.id_pengaduan,
        p.judul,
        p.lokasi,
        p.created_at,

        s.nama AS nama_siswa,
        s.kelas,

        k.nama_kategori,

        sp.nama_status

    FROM pengaduan p

    INNER JOIN siswa s
        ON p.id_siswa = s.id_siswa

    INNER JOIN kategori k
        ON p.id_kategori = k.id_kategori

    INNER JOIN status_pengaduan sp
        ON p.id_status = sp.id_status

    ORDER BY p.created_at DESC

    LIMIT 8
");

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard Admin - MUSA Complaint</title>

    <link
        rel="stylesheet"
        href="../asests/css/admin_dashboard.css"
    >

</head>

<body>

<!-- =========================
     NAVBAR
========================= -->

<nav class="navbar">

    <a href="admin_dashboard.php" class="logo">
        <span>MUSA</span> Complaint
    </a>

    <div class="nav-menu">

        <a href="admin_dashboard.php">
            Dashboard
        </a>

        <a href="pengaduan.php">
            Pengaduan
        </a>

        <a href="siswa.php">
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

<main class="dashboard-container">

    <!-- HEADER -->

    <section class="dashboard-header">

        <div>

            <h1>
                Dashboard Admin
            </h1>

            <p>
                Kelola dan pantau seluruh pengaduan siswa.
            </p>

        </div>

    </section>


    <!-- =========================
         STATISTIK
    ========================= -->

    <section class="stat-grid">

        <div class="stat-card">

            <div class="stat-info">

                <span>
                    Total Pengaduan
                </span>

                <strong>
                    <?= $total_pengaduan; ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-info">

                <span>
                    Menunggu
                </span>

                <strong>
                    <?= $total_menunggu; ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-info">

                <span>
                    Diproses
                </span>

                <strong>
                    <?= $total_diproses; ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-info">

                <span>
                    Selesai
                </span>

                <strong>
                    <?= $total_selesai; ?>
                </strong>

            </div>

        </div>

    </section>


    <!-- =========================
         PENGADUAN TERBARU
    ========================= -->

    <section class="content-card">

        <div class="section-header">

            <div>

                <h2>
                    Pengaduan Terbaru
                </h2>

                <p>
                    Daftar pengaduan terbaru dari siswa.
                </p>

            </div>

            <a href="pengaduan.php" class="view-all">
                Lihat Semua
            </a>

        </div>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>
                            Siswa
                        </th>

                        <th>
                            Pengaduan
                        </th>

                        <th>
                            Kategori
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Tanggal
                        </th>

                        <th>
                            Aksi
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php if ($query_pengaduan && $query_pengaduan->num_rows > 0): ?>

                    <?php while ($pengaduan = $query_pengaduan->fetch_assoc()): ?>

                        <tr>

                            <td>

                                <div class="student-info">

                                    <strong>
                                        <?= htmlspecialchars(
                                            $pengaduan["nama_siswa"]
                                        ); ?>
                                    </strong>

                                    <span>
                                        <?= htmlspecialchars(
                                            $pengaduan["kelas"]
                                        ); ?>
                                    </span>

                                </div>

                            </td>


                            <td>

                                <div class="complaint-info">

                                    <strong>
                                        <?= htmlspecialchars(
                                            $pengaduan["judul"]
                                        ); ?>
                                    </strong>

                                    <span>
                                        <?= htmlspecialchars(
                                            $pengaduan["lokasi"]
                                        ); ?>
                                    </span>

                                </div>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $pengaduan["nama_kategori"]
                                ); ?>

                            </td>


                            <td>

                                <span class="status">

                                    <?= htmlspecialchars(
                                        $pengaduan["nama_status"]
                                    ); ?>

                                </span>

                            </td>


                            <td>

                                <?= date(
                                    "d/m/Y",
                                    strtotime($pengaduan["created_at"])
                                ); ?>

                            </td>


                            <td>

                                <a
                                    href="#"
                                    class="detail-button"
                                >
                                    Detail
                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="6"
                            class="empty-data"
                        >
                            Belum ada pengaduan.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

</body>

</html>