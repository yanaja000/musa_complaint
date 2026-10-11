<?php

require_once "../config/database.php";
require_once "../config/session.php";

requireRole("siswa");

$id_siswa = (int) $_SESSION["user_id"];
$nama_siswa = $_SESSION["nama"] ?? "Kamu";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

/* Pesan sukses/gagal dari kirim_balasan.php (sekali tampil) */

$flash = $_SESSION["flash_balasan"] ?? null;
unset($_SESSION["flash_balasan"]);

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
   TANDAI SUDAH DIBACA
   (updated_at = updated_at supaya waktu update admin tidak ikut berubah)
========================================= */

$stmt_baca = $conn->prepare("
    UPDATE pengaduan
    SET dilihat_siswa_at = NOW(),
        updated_at = updated_at
    WHERE id_pengaduan = ?
    AND id_siswa = ?
");

$stmt_baca->bind_param("ii", $id_pengaduan, $id_siswa);
$stmt_baca->execute();

/* =========================================
   WARNA BADGE STATUS
========================================= */

if (!function_exists("kelasBadgeStatus")) {

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
}



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
   THREAD TANGGAPAN (Admin & Siswa)
   LEFT JOIN: balasan siswa tidak punya id_admin
========================================= */

$stmt_tanggapan = $conn->prepare("
    SELECT
        t.id_tanggapan,
        t.isi_tanggapan,
        t.created_at,
        t.pengirim,
        a.nama AS nama_admin
    FROM tanggapan t
    LEFT JOIN admin a
        ON t.id_admin = a.id_admin
    WHERE t.id_pengaduan = ?
    AND t.dihapus_siswa_at IS NULL
    ORDER BY t.created_at ASC, t.id_tanggapan ASC
");

$stmt_tanggapan->bind_param("i", $id_pengaduan);
$stmt_tanggapan->execute();

$thread = $stmt_tanggapan->get_result()->fetch_all(MYSQLI_ASSOC);

/* Tanggapan admin terbaru (untuk kotak "Tanggapan Admin") */

$tanggapan_admin_terakhir = null;

foreach ($thread as $item) {
    if (($item["pengirim"] ?? "admin") !== "siswa") {
        $tanggapan_admin_terakhir = $item;
    }
}

/* Form dikunci jika pengaduan sudah ditutup */

$status_kecil = strtolower(trim($pengaduan["nama_status"]));
$form_terkunci = in_array($status_kecil, ["selesai", "ditolak"], true);

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
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet"
          href="../asests/css/detail_pengaduan.css">

    <link rel="stylesheet"
          href="../asests/css/tanggapan.css">

</head>

<body>


<!-- ================= CONTENT ================= -->

<main class="container">

    <a href="pengaduan_saya.php" class="back-button">
        ← Kembali ke Pengaduan saya
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

        <span class="status <?= kelasBadgeStatus($pengaduan["nama_status"]); ?>">
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


    <!-- TANGGAPAN ADMIN (terbaru) -->

    <section class="card">

        <h2>Tanggapan Admin</h2>

        <?php if ($tanggapan_admin_terakhir): ?>

            <div class="response">

                <div class="response-header">

                    <strong>
                        <i class="fas fa-user-shield" aria-hidden="true"></i>
                        <?= htmlspecialchars($tanggapan_admin_terakhir["nama_admin"] ?? "Admin"); ?>
                    </strong>

                    <small>
                        <?= date("d-m-Y H:i", strtotime($tanggapan_admin_terakhir["created_at"])); ?>
                    </small>

                </div>

                <p>
                    <?= nl2br(htmlspecialchars($tanggapan_admin_terakhir["isi_tanggapan"])); ?>
                </p>

            </div>

        <?php else: ?>

            <p class="empty-text">
                Belum ada tanggapan dari admin.
            </p>

        <?php endif; ?>

    </section>


    <!-- RIWAYAT PERCAKAPAN + FORM BALASAN -->

    <section class="card" id="konsultasi">

        <h2>
            <i class="fas fa-comments" aria-hidden="true"></i>
            Riwayat Tanggapan
        </h2>

        <?php if ($flash): ?>

            <div class="thread-alert thread-alert-<?= htmlspecialchars($flash["tipe"]); ?>" role="alert">
                <?= htmlspecialchars($flash["pesan"]); ?>
            </div>

        <?php endif; ?>

        <?php if (count($thread) > 0): ?>

            <div class="thread">

                <?php foreach ($thread as $item): ?>

                    <?php
                    $dari_siswa = ($item["pengirim"] ?? "admin") === "siswa";
                    $nama_pengirim = $dari_siswa
                        ? $nama_siswa . " (Kamu)"
                        : ($item["nama_admin"] ?? "Admin");
                    ?>

                    <div class="thread-row <?= $dari_siswa ? "thread-siswa" : "thread-admin"; ?>">

                        <div class="thread-bubble">

                            <div class="thread-meta">

                                <strong>
                                    <i class="fas <?= $dari_siswa ? "fa-user" : "fa-user-shield"; ?>"
                                       aria-hidden="true"></i>
                                    <?= htmlspecialchars($nama_pengirim); ?>
                                </strong>

                                <span class="thread-role">
                                    <?= $dari_siswa ? "Siswa" : "Admin"; ?>
                                </span>

                                <?php if ($dari_siswa && !$form_terkunci): ?>

                                    <button type="button"
                                            class="thread-delete"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalHapusPesan"
                                            data-id="<?= (int) $item["id_tanggapan"]; ?>"
                                            aria-label="Hapus pesan ini"
                                            title="Hapus pesan">
                                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                    </button>

                                <?php endif; ?>

                            </div>

                            <p>
                                <?= nl2br(htmlspecialchars($item["isi_tanggapan"])); ?>
                            </p>

                            <small>
                                <?= date("d-m-Y H:i", strtotime($item["created_at"])); ?>
                            </small>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <p class="empty-text">
                Belum ada percakapan. Kamu bisa mengirim pesan setelah admin menanggapi
                atau jika ada tambahan informasi.
            </p>

        <?php endif; ?>

        <div id="thread-akhir"></div>


        <!-- FORM BALASAN -->

        <form action="kirim_balasan.php"
              method="POST"
              class="reply-form"
              id="formBalasan">

            <input type="hidden" name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION["csrf_token"]); ?>">

            <input type="hidden" name="id_pengaduan"
                   value="<?= $id_pengaduan; ?>">

            <label for="isi_balasan">Balas Tanggapan</label>

            <textarea id="isi_balasan"
                      name="isi_balasan"
                      rows="3"
                      maxlength="2000"
                      required
                      placeholder="<?= $form_terkunci
                          ? "Pengaduan sudah " . strtolower($pengaduan["nama_status"]) . ", balasan dikunci."
                          : "Tulis balasan untuk admin..."; ?>"
                      <?= $form_terkunci ? "disabled" : ""; ?>></textarea>

            <?php if ($form_terkunci): ?>

                <p class="reply-locked">
                    <i class="fas fa-lock" aria-hidden="true"></i>
                    Percakapan ditutup karena status pengaduan
                    <strong><?= htmlspecialchars($pengaduan["nama_status"]); ?></strong>.
                </p>

            <?php endif; ?>

            <button type="submit"
                    class="reply-button"
                    id="tombolBalas"
                    <?= $form_terkunci ? "disabled" : ""; ?>>

                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                Kirim Balasan

            </button>

        </form>

    </section>

</main>

<?php if (!$form_terkunci): ?>

<!-- POPUP KONFIRMASI HAPUS PESAN -->

<div class="modal fade"
     id="modalHapusPesan"
     tabindex="-1"
     aria-labelledby="judulHapusPesan"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content modal-hapus">

            <form action="hapus_tanggapan.php" method="POST" id="formHapusPesan">

                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($_SESSION["csrf_token"]); ?>">

                <input type="hidden" name="id_pengaduan"
                       value="<?= $id_pengaduan; ?>">

                <input type="hidden" name="id_tanggapan"
                       id="hapusIdTanggapan" value="">

                <div class="modal-hapus-body">

                    <div class="modal-hapus-icon">
                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                    </div>

                    <h5 id="judulHapusPesan">Hapus Pesan</h5>

                    <p>Apakah Anda yakin ingin menghapus pesan tanggapan ini?</p>

                </div>

                <div class="modal-hapus-footer">

                    <button type="button"
                            class="btn-batal"
                            data-bs-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit" class="btn-hapus" id="tombolYaHapus">
                        Ya, Hapus
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php endif; ?>

<?php include "bottom-nav.php"; ?>

<script src="../asests/js/logout.js"></script>

<script>
    /* Cegah klik ganda pada tombol kirim */
    /* Isi id_tanggapan ke form hapus saat ikon tempat sampah diklik */
    document.querySelectorAll(".thread-delete").forEach(function (tombol) {
        tombol.addEventListener("click", function () {
            document.getElementById("hapusIdTanggapan").value = tombol.dataset.id;
        });
    });

    var formHapus = document.getElementById("formHapusPesan");

    if (formHapus) {
        formHapus.addEventListener("submit", function () {
            document.getElementById("tombolYaHapus").disabled = true;
        });
    }

    var formBalasan = document.getElementById("formBalasan");

    if (formBalasan) {
        formBalasan.addEventListener("submit", function () {
            var tombol = document.getElementById("tombolBalas");
            if (tombol) { tombol.disabled = true; }
        });
    }
</script>

</body>
</html>