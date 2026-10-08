<?php

require_once "../config/database.php";
require_once "../config/session.php";

requireRole("admin");

$id_siswa = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id_siswa <= 0) {
    header("Location: siswa.php");
    exit;
}

/* =========================
   DATA SISWA
========================= */

$stmt = $conn->prepare("
    SELECT
        id_siswa,
        nis,
        nama,
        kelas,
        email,
        username,
        created_at
    FROM siswa
    WHERE id_siswa = ?
");

$stmt->bind_param("i", $id_siswa);
$stmt->execute();

$siswa = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$siswa) {
    header("Location: siswa.php");
    exit;
}

/* =========================
   RINGKASAN PER STATUS
========================= */

$stmt = $conn->prepare("
    SELECT
        sp.nama_status,
        COUNT(p.id_pengaduan) AS total
    FROM status_pengaduan sp
    LEFT JOIN pengaduan p
        ON p.id_status = sp.id_status
        AND p.id_siswa = ?
    GROUP BY sp.id_status, sp.nama_status
    ORDER BY sp.id_status ASC
");

$stmt->bind_param("i", $id_siswa);
$stmt->execute();

$ringkasan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_pengaduan = array_sum(array_column($ringkasan, "total"));

/* =========================
   RIWAYAT PENGADUAN
========================= */

$stmt = $conn->prepare("
    SELECT
        p.id_pengaduan,
        p.judul,
        p.lokasi,
        p.created_at,
        k.nama_kategori,
        sp.nama_status
    FROM pengaduan p
    INNER JOIN kategori k
        ON p.id_kategori = k.id_kategori
    INNER JOIN status_pengaduan sp
        ON p.id_status = sp.id_status
    WHERE p.id_siswa = ?
    ORDER BY p.created_at DESC
");

$stmt->bind_param("i", $id_siswa);
$stmt->execute();

$pengaduan = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Detail Siswa - MUSA Complaint</title>

    <link
        rel="stylesheet"
        href="../asests/css/admin_detail_siswa.css"
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

    <a href="siswa.php" class="back-button">
        ← Kembali ke Data Siswa
    </a>


    <!-- PROFIL -->

    <section class="profile">

        <div class="avatar">
            <?= htmlspecialchars(
                mb_strtoupper(mb_substr($siswa["nama"], 0, 1))
            ); ?>
        </div>

        <div class="profile-info">

            <h1>
                <?= htmlspecialchars($siswa["nama"]); ?>
            </h1>

            <p>
                @<?= htmlspecialchars($siswa["username"]); ?>
            </p>

        </div>

        <div class="profile-total">

            <strong>
                <?= (int) $total_pengaduan; ?>
            </strong>

            <span>
                Total pengaduan
            </span>

        </div>

    </section>


    <div class="grid">

        <!-- DATA DIRI -->

        <section class="card">

            <div class="card-header">
                <h2>Data Siswa</h2>
            </div>

            <dl class="data-list">

                <div>
                    <dt>NIS</dt>
                    <dd><?= htmlspecialchars($siswa["nis"]); ?></dd>
                </div>

                <div>
                    <dt>Kelas</dt>
                    <dd><?= htmlspecialchars($siswa["kelas"]); ?></dd>
                </div>

                <div>
                    <dt>Email</dt>
                    <dd><?= htmlspecialchars($siswa["email"]); ?></dd>
                </div>

                <div>
                    <dt>Username</dt>
                    <dd><?= htmlspecialchars($siswa["username"]); ?></dd>
                </div>

                <div>
                    <dt>Terdaftar</dt>
                    <dd>
                        <?= date(
                            "d-m-Y H:i",
                            strtotime($siswa["created_at"])
                        ); ?>
                    </dd>
                </div>

            </dl>

        </section>


        <!-- RINGKASAN STATUS -->

        <section class="card">

            <div class="card-header">
                <h2>Ringkasan Status</h2>
            </div>

            <ul class="summary">

                <?php foreach ($ringkasan as $item): ?>

                    <li>

                        <span>
                            <?= htmlspecialchars($item["nama_status"]); ?>
                        </span>

                        <strong>
                            <?= (int) $item["total"]; ?>
                        </strong>

                    </li>

                <?php endforeach; ?>

            </ul>

        </section>

    </div>


    <!-- RIWAYAT PENGADUAN -->

    <section class="card">

        <div class="card-header">

            <h2>Riwayat Pengaduan</h2>

            <p>Semua pengaduan yang dikirim siswa ini.</p>

        </div>

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>
                        <th>No</th>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Lokasi</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>

                </thead>

                <tbody>

                <?php if ($pengaduan->num_rows > 0): ?>

                    <?php $no = 1; ?>

                    <?php while ($row = $pengaduan->fetch_assoc()): ?>

                        <tr>

                            <td><?= $no++; ?></td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($row["judul"]); ?>
                                </strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($row["nama_kategori"]); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row["lokasi"]); ?>
                            </td>

                            <td>
                                <?= date(
                                    "d-m-Y H:i",
                                    strtotime($row["created_at"])
                                ); ?>
                            </td>

                            <td>
                                <span class="status">
                                    <?= htmlspecialchars($row["nama_status"]); ?>
                                </span>
                            </td>

                            <td>
                                <a
                                    href="detail_pengaduan.php?id=<?= (int) $row["id_pengaduan"]; ?>"
                                    class="detail-button"
                                >
                                    Detail
                                </a>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="empty">
                            Siswa ini belum pernah mengirim pengaduan.
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