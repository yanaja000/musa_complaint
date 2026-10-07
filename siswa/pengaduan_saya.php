<?php

require_once "../config/database.php";
require_once "../config/session.php";

requireRole("siswa");

$id_siswa = $_SESSION["user_id"];

/* Ambil pengaduan milik siswa yang sedang login */
$stmt = $conn->prepare("
    SELECT
        p.id_pengaduan,
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

</body>

</html>