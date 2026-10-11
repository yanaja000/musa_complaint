<?php

require_once "../config/database.php";
require_once "../config/session.php";

requireRole("siswa");

$id_siswa = $_SESSION["user_id"];
$nama = $_SESSION["nama"];

/* =========================================
   STATISTIK PENGADUAN SISWA
   Satu query; status dihitung lewat NAMA status
   sehingga tidak tergantung nomor id_status.
========================================= */

$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(s.nama_status = 'Menunggu'), 0) AS menunggu,
        COALESCE(SUM(s.nama_status IN ('Diproses','Ditanggapi')), 0) AS diproses,
        COALESCE(SUM(s.nama_status = 'Selesai'),  0) AS selesai,
        COALESCE(SUM(s.nama_status = 'Ditolak'),  0) AS ditolak
    FROM pengaduan p
    JOIN status_pengaduan s
        ON p.id_status = s.id_status
    WHERE p.id_siswa = ?
");

$stmt->bind_param("i", $id_siswa);
$stmt->execute();

$stat = $stmt->get_result()->fetch_assoc();

$total    = (int) $stat["total"];
$menunggu = (int) $stat["menunggu"];
$diproses = (int) $stat["diproses"];
$selesai  = (int) $stat["selesai"];
$ditolak  = (int) $stat["ditolak"];


/* =========================================
   WARNA BADGE STATUS
========================================= */

function kelasBadgeStatus($nama_status)
{
    switch (strtolower(trim($nama_status))) {

        case "menunggu":
            return "bg-warning text-dark";     // kuning

        case "diproses":
        case "ditanggapi":
            return "bg-info text-white";       // cyan

        case "selesai":
            return "bg-success text-white";    // hijau

        case "ditolak":
            return "bg-danger text-white";     // merah

        default:
            return "bg-secondary text-white";  // status lain
    }
}


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
        s.nama_status,

        /* 1 = admin memperbarui/menanggapi dan siswa belum membukanya */
        (
            p.updated_at > COALESCE(p.dilihat_siswa_at, p.created_at)
            OR EXISTS (
                SELECT 1
                FROM tanggapan t
                WHERE t.id_pengaduan = p.id_pengaduan
                AND COALESCE(t.pengirim, 'admin') <> 'siswa'
                AND t.created_at > COALESCE(p.dilihat_siswa_at, p.created_at)
            )
        ) AS belum_dibaca
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
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet"
          href="../asests/css/siswa.css">

    <link rel="stylesheet"
          href="../asests/css/tanggapan.css">

</head>

<body>

<!-- =========================================
     MAIN
========================================= -->

<main class="dashboard-container">

    <!-- HEADER -->

    <section class="dashboard-welcome">

        <div>

            <p class="dashboard-label">
                Dashboard Siswa
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

        <div class="stat-card stat-total">

            <div class="stat-icon">
                <i class="fas fa-file-alt" aria-hidden="true"></i>
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


        <div class="stat-card stat-warning">

            <div class="stat-icon">
                <i class="fas fa-clock" aria-hidden="true"></i>
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


        <div class="stat-card stat-info">

            <div class="stat-icon">
                <i class="fas fa-cogs" aria-hidden="true"></i>
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


        <div class="stat-card stat-success">

            <div class="stat-icon">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
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


        <div class="stat-card stat-danger">

            <div class="stat-icon">
                <i class="fas fa-times-circle" aria-hidden="true"></i>
            </div>

            <div>

                <span>
                    Ditolak
                </span>

                <strong>
                    <?= $ditolak; ?>
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

            <a href="pengaduan_saya.php">
                Lihat Semua
            </a>

        </div>


        <div class="complaint-list">

            <?php if ($pengaduan->num_rows > 0): ?>

                <?php while ($row = $pengaduan->fetch_assoc()): ?>

                    <div class="complaint-card">

                        <div class="complaint-info">

                            <h3>
                                <a href="detail_pengaduan.php?id=<?= (int) $row["id_pengaduan"]; ?>"
                                   class="complaint-link">
                                    <?= htmlspecialchars($row["judul"]); ?>
                                </a>
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

                            <span class="<?= kelasBadgeStatus($row["nama_status"]); ?>">
                                <?= htmlspecialchars($row["nama_status"]); ?>

                                <?php if (!empty($row["belum_dibaca"])): ?>
                                    <i class="unread-dot" role="img"
                                       aria-label="Ada pembaruan baru"
                                       title="Ada pembaruan baru"></i>
                                <?php endif; ?>
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

<!-- Bottom Navigation Bar -->
<?php include "bottom-nav.php"; ?>

<script src="../asests/js/logout.js"></script>

</body>

</html>