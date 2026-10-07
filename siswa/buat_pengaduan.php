<?php

require_once "../config/database.php";
require_once "../config/session.php";

requireRole("siswa");

$id_siswa = $_SESSION["user_id"];

$success = "";
$error = "";

/* =========================================
   PROSES KIRIM PENGADUAN
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id_kategori = $_POST["id_kategori"] ?? "";
    $judul = trim($_POST["judul"] ?? "");
    $isi_pengaduan = trim($_POST["isi_pengaduan"] ?? "");
    $lokasi = trim($_POST["lokasi"] ?? "");

    if (
        empty($id_kategori) ||
        empty($judul) ||
        empty($isi_pengaduan) ||
        empty($lokasi)
    ) {

        $error = "Semua field wajib diisi.";

    } else {

        /*
         * Status 1 = MENUNGGU
         */

        $id_status = 1;

        $stmt = $conn->prepare("
            INSERT INTO pengaduan
            (
                id_siswa,
                id_kategori,
                id_status,
                judul,
                isi_pengaduan,
                lokasi
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "iiisss",
            $id_siswa,
            $id_kategori,
            $id_status,
            $judul,
            $isi_pengaduan,
            $lokasi
        );

        if ($stmt->execute()) {

            $id_pengaduan = $conn->insert_id;

            /* =========================
                UPLOAD FOTO BUKTI
            ========================= */

if (isset($_FILES["gambar"]) && $_FILES["gambar"]["error"] === UPLOAD_ERR_OK) {

    // Maksimal 5 MB
    if ($_FILES["gambar"]["size"] > 5 * 1024 * 1024) {
        die("Ukuran foto maksimal 5 MB.");
    }

    // Cek tipe file
    $allowed_types = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];

    $file_type = mime_content_type(
        $_FILES["gambar"]["tmp_name"]
    );

    if (!in_array($file_type, $allowed_types)) {
        die("Foto harus berformat JPG, PNG, atau WEBP.");
    }

    // Folder penyimpanan
    $folder = "../uploads/pengaduan/";

    // Ambil ekstensi file
    $extension = strtolower(
        pathinfo(
            $_FILES["gambar"]["name"],
            PATHINFO_EXTENSION
        )
    );

    // Buat nama file baru
    $nama_file = uniqid("pengaduan_", true) . "." . $extension;

    // Lokasi file
    $path_file = $folder . $nama_file;

    // Pindahkan file ke folder uploads
    if (!move_uploaded_file(
        $_FILES["gambar"]["tmp_name"],
        $path_file
    )) {
        die("Foto gagal disimpan.");
    }

    // Simpan informasi foto ke database
    $stmt_lampiran = $conn->prepare("
        INSERT INTO lampiran
        (
            id_pengaduan,
            nama_file,
            path_file,
            tipe_file,
            created_at
        )
        VALUES (?, ?, ?, ?, NOW())
    ");

    $path_database = "uploads/pengaduan/" . $nama_file;

    $stmt_lampiran->bind_param(
        "isss",
        $id_pengaduan,
        $_FILES["gambar"]["name"],
        $path_database,
        $file_type
    );

    $stmt_lampiran->execute();
}

            /*
             * Simpan riwayat status pertama
             */

            $riwayat = $conn->prepare("
                INSERT INTO riwayat_status
                (
                    id_pengaduan,
                    id_status,
                    id_admin,
                    catatan
                )
                VALUES (?, ?, NULL, ?)
            ");

            $catatan = "Pengaduan dibuat oleh siswa.";

            $riwayat->bind_param(
                "iis",
                $id_pengaduan,
                $id_status,
                $catatan
            );

            $riwayat->execute();

            $success = "Pengaduan berhasil dikirim.";

        } else {

            $error = "Pengaduan gagal dikirim.";
        }
    }
}


/* =========================================
   AMBIL KATEGORI
========================================= */

$kategori = $conn->query("
    SELECT
        id_kategori,
        nama_kategori,
        deskripsi
    FROM kategori
    ORDER BY nama_kategori ASC
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

    <title>
        Buat Pengaduan - MUSA Complaint
    </title>

    <link rel="stylesheet" href="../asests/css/pengaduan.css">

</head>

<body>

<!-- =========================================
     NAVBAR
========================================= -->

<nav class="dashboard-navbar">

    <div class="dashboard-logo">
        Musa <span>Complaint</span>
    </div>

    <div class="dashboard-menu">

        <a href="siswa_dashboard.php">
            Dashboard
        </a>

        <a href="buat_pengaduan.php" class="active">
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
     FORM
========================================= -->

<main class="complaint-container">

    <div class="complaint-header">

        <p>
            MUSA Complaint
        </p>

        <h1>
            Buat Pengaduan
        </h1>

        <span>
            Sampaikan keluhan, laporan, atau saran
            dengan jelas agar dapat ditindaklanjuti.
        </span>

    </div>


    <?php if ($success): ?>

        <div class="success-message">

            <?= htmlspecialchars($success); ?>

        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="error-message">

            <?= htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>


    <div class="complaint-form-card">

        <form method="POST" enctype="multipart/form-data">

            <!-- KATEGORI -->

            <div class="form-group">

                <label>
                    Kategori Pengaduan
                </label>

                <select
                    name="id_kategori"
                    required
                >

                    <option value="">
                        Pilih kategori
                    </option>

                    <?php while ($row = $kategori->fetch_assoc()): ?>

                        <option
                            value="<?= $row["id_kategori"]; ?>"
                        >

                            <?= htmlspecialchars(
                                $row["nama_kategori"]
                            ); ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>


            <!-- JUDUL -->

            <div class="form-group">

                <label>
                    Judul Pengaduan
                </label>

                <input
                    type="text"
                    name="judul"
                    placeholder="Contoh: Kursi kelas rusak"
                    maxlength="150"
                    required
                >

            </div>


            <!-- LOKASI -->

            <div class="form-group">

                <label>
                    Lokasi Kejadian
                </label>

                <input
                    type="text"
                    name="lokasi"
                    placeholder="Contoh: Ruang Kelas XI RPL"
                    maxlength="150"
                    required
                >

            </div>


            <!-- ISI -->

            <div class="form-group">

                <label>
                    Isi Pengaduan
                </label>

                <textarea
                    name="isi_pengaduan"
                    rows="7"
                    placeholder="Jelaskan pengaduan secara detail..."
                    required
                ></textarea>

            </div>

            <div class="form-group">
                <label for="gambar">Foto Bukti</label>

                <input
                    type="file"
                    name="gambar"
                    id="gambar"
                    accept="image/jpeg,image/png,image/webp">

                <small>
                    Upload foto sebagai bukti pengaduan. Maksimal 5 MB.
                </small>
            </div>

            <!-- BUTTON -->

            <div class="complaint-actions">

                <a
                    href="siswa_dashboard.php"
                    class="secondary-button"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="primary-button"
                >
                    Kirim Pengaduan
                </button>

            </div>

        </form>

    </div>

</main>

</body>

</html>