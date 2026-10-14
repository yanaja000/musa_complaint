<?php

require_once "../config/database.php";
require_once "../config/session.php";
require_once __DIR__ . "/helpers.php";

requireRole("admin");

/* =========================================
   STATISTIK (satu query; dihitung lewat NAMA status
   sehingga tidak tergantung nomor id_status)
   DITANGGAPI digabung ke kartu "Diproses"
========================================= */

$stat = [
    "total" => 0, "menunggu" => 0, "diproses" => 0, "selesai" => 0, "ditolak" => 0,
];

$query_stat = $conn->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(sp.nama_status = 'MENUNGGU'), 0) AS menunggu,
        COALESCE(SUM(sp.nama_status IN ('DIPROSES', 'DITANGGAPI')), 0) AS diproses,
        COALESCE(SUM(sp.nama_status = 'SELESAI'), 0) AS selesai,
        COALESCE(SUM(sp.nama_status = 'DITOLAK'), 0) AS ditolak
    FROM pengaduan p
    JOIN status_pengaduan sp
        ON p.id_status = sp.id_status
");

if ($query_stat) {
    $stat = array_map("intval", $query_stat->fetch_assoc());
}

function persen($bagian, $total)
{
    return $total > 0 ? round($bagian / $total * 100) : 0;
}

/* =========================================
   PENGADUAN TERBARU
========================================= */

$query_pengaduan = $conn->query("
    SELECT
        p.id_pengaduan,
        p.judul,
        p.lokasi,
        p.created_at,

        s.nama AS nama_siswa,
        s.kelas,
        s.foto,

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

/* =========================================
   BALASAN SISWA TERBARU
   (pesan siswa yang belum dihapus oleh siswa)
========================================= */

$balasan_siswa = [];

try {

    $query_balasan = $conn->query("
        SELECT
            t.id_pengaduan,
            t.isi_tanggapan,
            t.created_at,

            s.nama,
            s.foto,

            p.judul

        FROM tanggapan t

        INNER JOIN pengaduan p
            ON t.id_pengaduan = p.id_pengaduan

        INNER JOIN siswa s
            ON p.id_siswa = s.id_siswa

        WHERE t.pengirim = 'siswa'
        AND t.dihapus_siswa_at IS NULL

        ORDER BY t.created_at DESC

        LIMIT 5
    ");

    if ($query_balasan) {
        $balasan_siswa = $query_balasan->fetch_all(MYSQLI_ASSOC);
    }

} catch (Throwable $e) {
    $balasan_siswa = [];
}

$nama_depan = explode(" ", trim($_SESSION["nama"] ?? "Admin"))[0];

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Admin - MUSA Complaint</title>

    <!-- CSS: dimuat otomatis oleh navbar.php (asests/css/admin_dashboard.css) -->

</head>

<body>

<?php $halaman_aktif = "dashboard"; include __DIR__ . "/navbar.php"; ?>


<main class="dash">

    <!-- =========================================
         BANNER SAMBUTAN
    ========================================= -->

    <section class="welcome">

        <div class="welcome-text">

            <span class="welcome-date">
                <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                <?= tanggalIndonesia(null, true); ?>
            </span>

            <h1>Halo, <?= htmlspecialchars($nama_depan); ?>! 👋</h1>

            <p>
                <?php if ($stat["menunggu"] > 0): ?>
                    Ada <strong><?= $stat["menunggu"]; ?> pengaduan</strong> yang menunggu tindakanmu.
                <?php else: ?>
                    Semua pengaduan sudah ditangani. Kerja bagus!
                <?php endif; ?>
            </p>

        </div>

        <a href="pengaduan.php" class="welcome-button">
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            <?= $stat["menunggu"] > 0 ? "Tinjau Pengaduan" : "Lihat Semua Pengaduan"; ?>
        </a>

    </section>


    <!-- =========================================
         KARTU STATISTIK
    ========================================= -->

    <section class="stat-grid">

        <?php
        $kartu = [
            ["Total Pengaduan", $stat["total"],    "c-primary", "fa-file-alt",     "Semua"],
            ["Menunggu",        $stat["menunggu"], "c-warning", "fa-clock",        persen($stat["menunggu"], $stat["total"]) . "%"],
            ["Diproses",        $stat["diproses"], "c-info",    "fa-cogs",         persen($stat["diproses"], $stat["total"]) . "%"],
            ["Selesai",         $stat["selesai"],  "c-success", "fa-check-circle", persen($stat["selesai"], $stat["total"]) . "%"],
            ["Ditolak",         $stat["ditolak"],  "c-danger",  "fa-times-circle", persen($stat["ditolak"], $stat["total"]) . "%"],
        ];
        ?>

        <?php foreach ($kartu as [$label, $jumlah, $kelas, $ikon, $persen_teks]): ?>

            <div class="stat-card <?= $kelas; ?>">

                <div class="stat-top">
                    <div class="stat-icon"><i class="fas <?= $ikon; ?>" aria-hidden="true"></i></div>
                    <span class="stat-persen"><?= $persen_teks; ?></span>
                </div>

                <strong><?= $jumlah; ?></strong>
                <span class="stat-label"><?= $label; ?></span>

            </div>

        <?php endforeach; ?>

    </section>


    <!-- =========================================
         ISI: TABEL + PANEL SAMPING
    ========================================= -->

    <div class="dash-grid">

        <!-- PENGADUAN TERBARU -->

        <section class="panel">

            <div class="panel-head">

                <div>
                    <h2>Pengaduan Terbaru</h2>
                    <p>8 pengaduan terakhir dari siswa.</p>
                </div>

                <a href="pengaduan.php" class="view-all">
                    Lihat Semua
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </a>

            </div>

            <div class="c-list">

                <?php if ($query_pengaduan && $query_pengaduan->num_rows > 0): ?>

                    <?php while ($row = $query_pengaduan->fetch_assoc()): ?>

                        <a href="detail_pengaduan.php?id=<?= (int) $row["id_pengaduan"]; ?>"
                           class="c-item">

                            <?= avatarHtml($row["nama_siswa"], $row["foto"]); ?>

                            <div class="c-main">

                                <strong><?= htmlspecialchars($row["judul"]); ?></strong>

                                <span class="c-sub">
                                    <?= htmlspecialchars($row["nama_siswa"]); ?>
                                    &middot; <?= htmlspecialchars($row["kelas"]); ?>
                                    &middot; <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                                    <?= htmlspecialchars($row["lokasi"] ?? "-"); ?>
                                </span>

                                <div class="c-tags">
                                    <span class="c-kat"><?= htmlspecialchars($row["nama_kategori"]); ?></span>
                                    <span class="c-date">
                                        <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                        <?= date("d/m/Y", strtotime($row["created_at"])); ?>
                                    </span>
                                </div>

                            </div>

                            <div class="c-end">
                                <span class="<?= kelasBadgeStatus($row["nama_status"]); ?>">
                                    <?= htmlspecialchars($row["nama_status"]); ?>
                                </span>
                                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </div>

                        </a>

                    <?php endwhile; ?>

                <?php else: ?>

                    <p class="empty-data">Belum ada pengaduan.</p>

                <?php endif; ?>

            </div>

        </section>


        <!-- PANEL SAMPING -->

        <aside class="dash-side">

            <!-- Ringkasan status -->

            <section class="panel">

                <div class="panel-head">
                    <div>
                        <h2>Ringkasan Status</h2>
                        <p>Sebaran seluruh pengaduan.</p>
                    </div>
                </div>

                <?php
                $bar = [
                    ["Menunggu", $stat["menunggu"], "bg-warning"],
                    ["Diproses", $stat["diproses"], "bg-info"],
                    ["Selesai",  $stat["selesai"],  "bg-success"],
                    ["Ditolak",  $stat["ditolak"],  "bg-danger"],
                ];
                ?>

                <?php foreach ($bar as [$label, $jumlah, $warna]): ?>

                    <div class="bar-row">

                        <div class="bar-label">
                            <span><?= $label; ?></span>
                            <strong><?= $jumlah; ?></strong>
                        </div>

                        <div class="bar-track">
                            <div class="bar-fill <?= $warna; ?>"
                                 style="width: <?= persen($jumlah, $stat["total"]); ?>%"></div>
                        </div>

                    </div>

                <?php endforeach; ?>

            </section>


            <!-- Balasan siswa terbaru -->

            <section class="panel">

                <div class="panel-head">
                    <div>
                        <h2>Balasan Siswa</h2>
                        <p>Pesan terbaru dari siswa.</p>
                    </div>
                </div>

                <?php if (count($balasan_siswa) > 0): ?>

                    <ul class="reply-list">

                        <?php foreach ($balasan_siswa as $b): ?>

                            <li>

                                <a href="detail_pengaduan.php?id=<?= (int) $b["id_pengaduan"]; ?>#chat-akhir">

                                    <?= avatarHtml($b["nama"], $b["foto"]); ?>

                                    <div class="reply-text">

                                        <div class="reply-top">
                                            <strong><?= htmlspecialchars($b["nama"]); ?></strong>
                                            <small><?= date("d/m H:i", strtotime($b["created_at"])); ?></small>
                                        </div>

                                        <p><?= htmlspecialchars(mb_strimwidth($b["isi_tanggapan"], 0, 70, "…", "UTF-8")); ?></p>

                                        <span class="reply-judul">
                                            <i class="fa-regular fa-comment-dots" aria-hidden="true"></i>
                                            <?= htmlspecialchars(mb_strimwidth($b["judul"], 0, 30, "…", "UTF-8")); ?>
                                        </span>

                                    </div>

                                </a>

                            </li>

                        <?php endforeach; ?>

                    </ul>

                <?php else: ?>

                    <p class="empty-data small">Belum ada balasan dari siswa.</p>

                <?php endif; ?>

            </section>

        </aside>

    </div>

</main>

<script src="../asests/js/logout.js"></script>

</body>

</html>