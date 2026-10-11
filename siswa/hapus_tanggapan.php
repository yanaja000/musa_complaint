<?php

/* =========================================================
   HAPUS PESAN SISWA  ->  tabel tanggapan (soft delete)
   Dipanggil dari popup konfirmasi di detail_pengaduan.php (POST)

   Proteksi:
   1. Hanya pesan milik siswa yang login (pengirim = 'siswa' AND id_siswa)
   2. Pengaduan harus milik siswa tersebut
   3. Ditolak bila status pengaduan Selesai / Ditolak
   4. Token CSRF wajib valid
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
$id_tanggapan = (int) ($_POST["id_tanggapan"] ?? 0);

if ($id_pengaduan <= 0 || $id_tanggapan <= 0) {
    header("Location: pengaduan_saya.php");
    exit;
}

$kembali = "detail_pengaduan.php?id=" . $id_pengaduan . "#konsultasi";

function flashHapus($tipe, $pesan)
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
    flashHapus("danger", "Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.");
    header("Location: " . $kembali);
    exit;
}

/* ---------- Cek kepemilikan pesan + status pengaduan ---------- */

$stmt = $conn->prepare("
    SELECT s.nama_status
    FROM tanggapan t
    JOIN pengaduan p
        ON t.id_pengaduan = p.id_pengaduan
    JOIN status_pengaduan s
        ON p.id_status = s.id_status
    WHERE t.id_tanggapan = ?
    AND t.id_pengaduan = ?
    AND t.pengirim = 'siswa'
    AND t.id_siswa = ?
    AND t.dihapus_siswa_at IS NULL
    AND p.id_siswa = ?
");

$stmt->bind_param("iiii", $id_tanggapan, $id_pengaduan, $id_siswa, $id_siswa);
$stmt->execute();

$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    flashHapus("danger", "Pesan tidak ditemukan atau bukan milik Anda.");
    header("Location: " . $kembali);
    exit;
}

$status = strtolower(trim($data["nama_status"]));

if ($status === "selesai" || $status === "ditolak") {
    flashHapus("warning", "Pengaduan sudah " . $status . ", pesan tidak dapat dihapus.");
    header("Location: " . $kembali);
    exit;
}

/* ---------- Hapus (sembunyikan dari siswa) ---------- */

/* SOFT DELETE: baris tetap ada di database agar admin masih bisa
   melihat riwayat percakapan; hanya disembunyikan dari sisi siswa. */

$stmt_hapus = $conn->prepare("
    UPDATE tanggapan
    SET dihapus_siswa_at = NOW()
    WHERE id_tanggapan = ?
    AND id_pengaduan = ?
    AND pengirim = 'siswa'
    AND id_siswa = ?
    AND dihapus_siswa_at IS NULL
");

$stmt_hapus->bind_param("iii", $id_tanggapan, $id_pengaduan, $id_siswa);

if ($stmt_hapus->execute() && $stmt_hapus->affected_rows > 0) {
    flashHapus("success", "Pesan berhasil dihapus.");
} else {
    flashHapus("danger", "Pesan gagal dihapus. Coba lagi.");
}

header("Location: " . $kembali);
exit;