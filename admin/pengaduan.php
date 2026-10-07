<?php

require_once "../config/database.php";
require_once "../config/session.php";

requireRole("admin");

/* =========================
   AMBIL SEMUA PENGADUAN
========================= */

$query = $conn->query("
    SELECT
        p.id_pengaduan,
        p.judul,
        p.lokasi,
        p.created_at,

        s.nama AS nama_siswa,
        s.nis,
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

    <title>Pengaduan - MUSA Complaint</title>

    <link
        rel="stylesheet"
        href="../asests/css/admin_pengaduan.css"
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

        <a href="pengaduan.php" class="active">
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

<main class="container">

    <div class="page-header">

        <div>

            <h1>
                Pengaduan Siswa
            </h1>

            <p>
                Kelola dan lihat seluruh pengaduan yang dikirim siswa.
            </p>

        </div>

    </div>


    <!-- =========================
         TABLE
    ========================= -->

    <section class="card">

        <div class="card-header">

            <div>

                <h2>
                    Daftar Pengaduan
                </h2>

                <p>
                    Semua pengaduan siswa yang masuk ke sistem.
                </p>

            </div>

        </div>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>
                            No
                        </th>

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

                <?php if ($query && $query->num_rows > 0): ?>

                    <?php $no = 1; ?>

                    <?php while ($data = $query->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?= $no++; ?>
                            </td>


                            <!-- SISWA -->

                            <td>

                                <div class="student">

                                    <strong>
                                        <?= htmlspecialchars(
                                            $data["nama_siswa"]
                                        ); ?>
                                    </strong>

                                    <span>
                                        NIS:
                                        <?= htmlspecialchars(
                                            $data["nis"]
                                        ); ?>
                                    </span>

                                    <span>
                                        Kelas:
                                        <?= htmlspecialchars(
                                            $data["kelas"]
                                        ); ?>
                                    </span>

                                </div>

                            </td>


                            <!-- PENGADUAN -->

                            <td>

                                <div class="complaint">

                                    <strong>
                                        <?= htmlspecialchars(
                                            $data["judul"]
                                        ); ?>
                                    </strong>

                                    <span>
                                        <?= htmlspecialchars(
                                            $data["lokasi"]
                                        ); ?>
                                    </span>

                                </div>

                            </td>


                            <!-- KATEGORI -->

                            <td>

                                <?= htmlspecialchars(
                                    $data["nama_kategori"]
                                ); ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <span class="status">

                                    <?= htmlspecialchars(
                                        $data["nama_status"]
                                    ); ?>

                                </span>

                            </td>


                            <!-- TANGGAL -->

                            <td>

                                <?= date(
                                    "d/m/Y",
                                    strtotime($data["created_at"])
                                ); ?>

                            </td>


                            <!-- AKSI -->

                            <td>

                                <a
                                    href="detail_pengaduan.php?id=<?= $data["id_pengaduan"]; ?>"
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
                            colspan="7"
                            class="empty"
                        >
                            Belum ada pengaduan siswa.
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