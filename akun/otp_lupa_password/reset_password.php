<?php

/**
 * PROSES RESET PASSWORD
 * Endpoint JSON. Hanya berhasil jika session memiliki bukti OTP reset_password
 * yang sudah diverifikasi, belum dipakai, dan belum lewat batas waktu.
 *
 * POST: csrf_token, password, konfirmasi_password
 */

require_once "../../config/database.php";
require_once "../../config/otp.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    otpJson(["success" => false, "message" => "Metode tidak diizinkan."], 405);
}

if (!otpCsrfValid($_POST["csrf_token"] ?? null)) {
    otpJson([
        "success" => false,
        "message" => "Sesi tidak valid. Muat ulang halaman lalu coba lagi.",
    ], 403);
}

$password = (string) ($_POST["password"] ?? "");
$konfirmasi = (string) ($_POST["konfirmasi_password"] ?? "");

try {

    // 1. Wajib sudah lolos verifikasi OTP (dicek ke session DAN database)
    $proof = otpActiveProof($conn, OTP_PURPOSE_RESET);

    if (!$proof) {
        otpJson([
            "success"  => false,
            "code"     => "no_proof",
            "message"  => "Verifikasi OTP tidak ditemukan atau sudah kedaluwarsa. Silakan ulangi proses lupa password.",
        ], 403);
    }

    // 2. Validasi password (aturan sama dengan registrasi: minimal 6 karakter)
    if ($password === "" || $konfirmasi === "") {
        otpJson(["success" => false, "message" => "Password baru dan konfirmasi wajib diisi."], 422);
    }

    if ($password !== $konfirmasi) {
        otpJson(["success" => false, "message" => "Konfirmasi password tidak sama."], 422);
    }

    if (strlen($password) < 6) {
        otpJson(["success" => false, "message" => "Password minimal 6 karakter."], 422);
    }

    if (strlen($password) > 72) {
        otpJson(["success" => false, "message" => "Password maksimal 72 karakter."], 422);
    }

    $email = $proof["email"];
    $role = $proof["role"];
    $tabel = ["siswa" => "siswa", "admin" => "admin"][$role] ?? null;

    if ($tabel === null) {
        otpJson(["success" => false, "message" => "Permintaan tidak valid."], 400);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT); // bcrypt

    // 3. Pakai OTP (sekali pakai) + ganti password dalam satu transaksi
    $conn->begin_transaction();

    if (!otpConsume($conn, OTP_PURPOSE_RESET, $email, $role)) {
        $conn->rollback();
        otpClearProof(OTP_PURPOSE_RESET);
        otpJson([
            "success" => false,
            "code"    => "no_proof",
            "message" => "Kode OTP sudah digunakan atau tidak valid. Silakan ulangi proses lupa password.",
        ], 403);
    }

    $stmt = $conn->prepare("UPDATE `$tabel` SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hash, $email);
    $stmt->execute();
    $berubah = $stmt->affected_rows;
    $stmt->close();

    if ($berubah < 1) {
        $conn->rollback();
        otpClearProof(OTP_PURPOSE_RESET);
        otpJson([
            "success" => false,
            "code"    => "no_proof",
            "message" => "Password tidak dapat diperbarui. Silakan ulangi proses lupa password.",
        ], 422);
    }

    $conn->commit();

    otpClearProof(OTP_PURPOSE_RESET);
    session_regenerate_id(true);

    otpJson([
        "success"  => true,
        "message"  => "Password berhasil diperbarui. Silakan login dengan password baru Anda.",
        "redirect" => "../login.php?reset=success",
    ]);

} catch (Throwable $e) {

    try {
        $conn->rollback();
    } catch (Throwable $ignore) {
    }

    error_log("[MUSAComplaint] reset_password error: " . $e->getMessage());
    otpJson([
        "success" => false,
        "message" => "Terjadi kesalahan pada server. Silakan coba lagi.",
    ], 500);
}
