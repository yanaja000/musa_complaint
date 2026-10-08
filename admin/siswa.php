<?php

require_once "../config/database.php";
require_once "../config/session.php";

requireRole("admin");

$query = $conn->query("
    SELECT
        s.id_siswa,
        s.nis,
        s.nama,
        s.kelas,
        s.email,
        s.username,
        s.created_at,

        COUNT(p.id_pengaduan) AS jumlah_pengaduan

    FROM siswa s

    LEFT JOIN pengaduan p
        ON s.id_siswa = p.id_siswa

    GROUP BY
        s.id_siswa,
        s.nis,
        s.nama,
        s.kelas,
        s.email,
        s.username,
        s.created_at

    ORDER BY s.nama ASC
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

    <title>Data Siswa - MUSA Complaint</title>

    <link
        rel="stylesheet"
        href="../asests/css/admin_siswa.css"
    >

</head>

<body>

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

        <a href="siswa.php" class="active">
            Siswa
        </a>

        <a href="../akun/logout.php">
            Logout
        </a>

    </div>

</nav>


<main class="container">

    <div class="page-header">

        <div>

            <h1>
                Data Siswa
            </h1>

            <p>
                Lihat data siswa yang terdaftar di MUSA Complaint.
            </p>

        </div>

    </div>


    <section class="card">

        <div class="card-header">

            <h2>
                Daftar Siswa
            </h2>

            <p>
                Seluruh siswa yang memiliki akun.
            </p>

        </div>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>No</th>
                        <th>Siswa</th>
                        <th>NIS</th>
                        <th>Kelas</th>
                        <th>Email</th>
                        <th>Pengaduan</th>
                        <th>Aksi</th>

                    </tr>

                </thead>

                <tbody>

                <?php if ($query && $query->num_rows > 0): ?>

                    <?php $no = 1; ?>

                    <?php while ($siswa = $query->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?= $no++; ?>
                            </td>

                            <td>

                                <div class="student">

                                    <strong>
                                        <?= htmlspecialchars(
                                            $siswa["nama"]
                                        ); ?>
                                    </strong>

                                    <span>
                                        @<?= htmlspecialchars(
                                            $siswa["username"]
                                        ); ?>
                                    </span>

                                </div>

                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $siswa["nis"]
                                ); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $siswa["kelas"]
                                ); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $siswa["email"]
                                ); ?>
                            </td>

                            <td>

                                <span class="jumlah">

                                    <?= (int) $siswa["jumlah_pengaduan"]; ?>

                                </span>

                            </td>

                            <td>

                                <a
                                    href="detail_siswa.php?id=<?= $siswa["id_siswa"]; ?>"
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
                            Belum ada siswa yang terdaftar.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<script src="../asests/js/logout.js"></script>

</body>

</html>