<?php

/**
 * HANDLER ENDPOINT OTP (dipakai bersama oleh folder otp_register
 * dan otp_lupa_password agar tidak ada kode ganda).
 * Tujuan OTP (purpose) ditentukan oleh file pemanggil di server,
 * BUKAN oleh data kiriman browser.
 */

require_once __DIR__ . "/otp.php";
require_once __DIR__ . "/mailer.php";

/** Proses kirim OTP. POST: csrf_token, role, email */
function otpEndpointKirim(mysqli $conn, string $purpose): void
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        otpJson(["success" => false, "message" => "Metode tidak diizinkan."], 405);
    }

    if (!otpCsrfValid($_POST["csrf_token"] ?? null)) {
        otpJson([
            "success" => false,
            "message" => "Sesi tidak valid. Muat ulang halaman lalu coba lagi.",
        ], 403);
    }

    $role = $_POST["role"] ?? "";
    $email = otpNormalizeEmail((string) ($_POST["email"] ?? ""));

    // Lupa password: jenis akun tidak lagi dipilih pengguna.
    // Jika role tidak dikirim, tentukan dari email (siswa dulu, lalu admin).
    if ($purpose === OTP_PURPOSE_RESET && !otpValidRole($role)) {
        $role = otpEmailExists($conn, "siswa", $email)
            ? "siswa"
            : (otpEmailExists($conn, "admin", $email) ? "admin" : "siswa");
    }

    if (!otpValidRole($role)) {
        otpJson(["success" => false, "message" => "Pilih role terlebih dahulu."], 422);
    }

    if ($email === "" || strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        otpJson(["success" => false, "message" => "Format email tidak valid."], 422);
    }

    $ip = otpClientIp();
    $status = 200;
    $respon = [];

    // Kunci per email+purpose agar permintaan paralel tidak menembus pembatasan
    $namaKunci = "otp_" . md5($purpose . "|" . $email);
    $kunciDiambil = false;

    try {
        $stmt = $conn->prepare("SELECT GET_LOCK(?, 5)");
        $stmt->bind_param("s", $namaKunci);
        $stmt->execute();
        $stmt->close();
        $kunciDiambil = true;

        if (random_int(1, 20) === 1) {
            otpCleanup($conn);
        }

        $batas = otpCheckRateLimit($conn, $email, $purpose, $ip);

        if ($batas !== null) {

            $status = 429;
            $respon = [
                "success"     => false,
                "code"        => $batas["type"],
                "message"     => $batas["message"],
                "retry_after" => $batas["retry_after"],
            ];

        } elseif ($purpose === OTP_PURPOSE_REGISTER) {

            // ---------- OTP REGISTRASI ----------
            if (otpEmailExists($conn, $role, $email)) {

                $status = 409;
                $respon = [
                    "success" => false,
                    "message" => "Email sudah terdaftar. Silakan login atau gunakan email lain.",
                ];

            } else {

                $otp = otpCreate($conn, $email, $purpose, $role, $ip);

                if (kirimEmailOtp($email, $otp["code"], $purpose)) {
                    $respon = [
                        "success"    => true,
                        "message"    => "Kode OTP telah dikirim ke $email. Berlaku selama 5 menit.",
                        "cooldown"   => OTP_COOLDOWN,
                        "expires_in" => OTP_TTL,
                    ];
                } else {
                    otpDelete($conn, $otp["id"]);
                    $status = 500;
                    $respon = [
                        "success" => false,
                        "code"    => "mail_failed",
                        "message" => mailerLastError(),
                    ];
                }
            }

        } else {

            // ---------- OTP RESET PASSWORD ----------
            // Respons selalu sama, baik email terdaftar maupun tidak.
            $terdaftar = otpEmailExists($conn, $role, $email);
            $otp = otpCreate($conn, $email, $purpose, $role, $ip, !$terdaftar);
            $emailGagal = false;

            if ($terdaftar) {
                if (!kirimEmailOtp($email, $otp["code"], $purpose)) {
                    // Baris dihapus agar pengguna boleh mencoba lagi tanpa menunggu 60 detik
                    otpDelete($conn, $otp["id"]);
                    $emailGagal = true;
                }
            } else {
                // Samakan waktu respons dengan email yang terdaftar
                usleep(random_int(600000, 1400000));
            }

            if ($emailGagal) {
                $status = 500;
                $respon = [
                    "success" => false,
                    "code"    => "mail_failed",
                    "message" => mailerLastError(),
                ];
            } else {
                $respon = [
                    "success"    => true,
                    "message"    => "Jika email terdaftar, kode OTP telah dikirim. Berlaku selama 5 menit.",
                    "cooldown"   => OTP_COOLDOWN,
                    "expires_in" => OTP_TTL,
                ];
            }
        }

    } catch (Throwable $e) {

        error_log("[MUSAComplaint] send_otp error: " . $e->getMessage());
        $status = 500;
        $respon = [
            "success" => false,
            "message" => "Terjadi kesalahan pada server. Silakan coba lagi.",
        ];

    } finally {

        if ($kunciDiambil) {
            try {
                $stmt = $conn->prepare("SELECT RELEASE_LOCK(?)");
                $stmt->bind_param("s", $namaKunci);
                $stmt->execute();
                $stmt->close();
            } catch (Throwable $e) {
                // kunci otomatis lepas saat koneksi ditutup
            }
        }
    }

    otpJson($respon, $status);

}

/** Proses verifikasi OTP. POST: csrf_token, email, otp */
function otpEndpointVerifikasi(mysqli $conn, string $purpose): void
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        otpJson(["success" => false, "message" => "Metode tidak diizinkan."], 405);
    }

    if (!otpCsrfValid($_POST["csrf_token"] ?? null)) {
        otpJson([
            "success" => false,
            "message" => "Sesi tidak valid. Muat ulang halaman lalu coba lagi.",
        ], 403);
    }

    $email = otpNormalizeEmail((string) ($_POST["email"] ?? ""));
    $kode = trim((string) ($_POST["otp"] ?? ""));

    if ($email === "" || strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        otpJson(["success" => false, "message" => "Format email tidak valid."], 422);
    }

    if (!preg_match('/^\d{6}$/', $kode)) {
        otpJson(["success" => false, "message" => "Kode OTP harus terdiri dari 6 digit angka."], 422);
    }

    try {

        $hasil = otpVerify($conn, $email, $purpose, $kode);

        if ($hasil["ok"]) {
            otpJson([
                "success" => true,
                "message" => $purpose === OTP_PURPOSE_REGISTER
                    ? "Email berhasil diverifikasi."
                    : "Kode OTP benar. Silakan buat password baru.",
                "role"    => $hasil["role"],
            ]);
        }

        otpJson([
            "success" => false,
            "code"    => $hasil["code"],
            "message" => $hasil["message"],
        ], 422);

    } catch (Throwable $e) {

        error_log("[MUSAComplaint] verify_otp error: " . $e->getMessage());
        otpJson([
            "success" => false,
            "message" => "Terjadi kesalahan pada server. Silakan coba lagi.",
        ], 500);
    }

}