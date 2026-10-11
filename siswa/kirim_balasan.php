<?php

/* =========================================================
   KIRIM BALASAN SISWA -> tabel tanggapan
   Dipanggil dari form di detail_pengaduan.php (method POST)
========================================================= */

require_once "../config/database.php";
require_once "../config/session.php";

requireRole("siswa");

$id_siswa = (int) $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: pengaduan_saya.php");
    exit;
}

$id_pengaduan = (int) ($_POST["id_pengaduan"] ?? 0);

if ($id_pengaduan <= 0) {
    header("Location: pengaduan_saya.php");
    exit;
}

$kembali = "detail_pengaduan.php?id=" . $id_pengaduan;

function flashBalasan($tipe, $pesan)
{
    $_SESSION["flash_balasan"] = ["tipe" => $tipe, "pesan" => $pesan];
}

/* ---------- CSRF ---------- */

if (
    empty($_SESSION["csrf_token"])
    || !isset($_POST["csrf_token"])
    || !is_string($_POST["csrf_token"])
    || !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {
    flashBalasan("danger", "Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.");
    header("Location: " . $kembali);
    exit;
}

/* ---------- Validasi isi ---------- */

$isi = trim($_POST["isi_balasan"] ?? "");

if ($isi === "") {
    flashBalasan("danger", "Balasan tidak boleh kosong.");
    header("Location: " . $kembali);
    exit;
}

if (mb_strlen($isi, "UTF-8") > 2000) {
    flashBalasan("danger", "Balasan terlalu panjang (maksimal 2000 karakter).");
    header("Location: " . $kembali);
    exit;
}

/* ---------- Pengaduan harus milik siswa ini & statusnya belum ditutup ---------- */

$stmt = $conn->prepare("
    SELECT s.nama_status
    FROM pengaduan p
    JOIN status_pengaduan s
        ON p.id_status = s.id_status
    WHERE p.id_pengaduan = ?
    AND p.id_siswa = ?
");

$stmt->bind_param("ii", $id_pengaduan, $id_siswa);
$stmt->execute();

$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    header("Location: pengaduan_saya.php");
    exit;
}

$status = strtolower(trim($data["nama_status"]));

if ($status === "selesai" || $status === "ditolak") {
    flashBalasan("warning", "Pengaduan sudah " . $status . ", balasan tidak dapat dikirim lagi.");
    header("Location: " . $kembali);
    exit;
}

/* ---------- Simpan balasan ---------- */

$stmt_simpan = $conn->prepare("
    INSERT INTO tanggapan
    (
        id_pengaduan,
        id_admin,
        id_siswa,
        isi_tanggapan,
        pengirim,
        created_at
    )
    VALUES (?, NULL, ?, ?, 'siswa', NOW())
");

$stmt_simpan->bind_param("iis", $id_pengaduan, $id_siswa, $isi);

if ($stmt_simpan->execute()) {

    /* Balasan siswa sendiri tidak boleh memunculkan notifikasi untuk siswa */

    $stmt_baca = $conn->prepare("
        UPDATE pengaduan
        SET dilihat_siswa_at = NOW(),
            updated_at = updated_at
        WHERE id_pengaduan = ?
        AND id_siswa = ?
    ");

    $stmt_baca->bind_param("ii", $id_pengaduan, $id_siswa);
    $stmt_baca->execute();

    flashBalasan("success", "Balasan berhasil dikirim.");

} else {

    flashBalasan("danger", "Balasan gagal disimpan. Coba lagi.");
}

/* Post-Redirect-Get: refresh halaman tidak mengirim ulang balasan */

header("Location: " . $kembali . "#thread-akhir");
exit;