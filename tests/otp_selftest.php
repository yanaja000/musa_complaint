<?php

/**
 * UJI OTP (CLI) - MUSAComplaint
 *
 * Jalankan dari folder project:
 *     php tests/otp_selftest.php
 * Untuk sekaligus menguji pengiriman email nyata (SMTP Gmail):
 *     php tests/otp_selftest.php emailanda@gmail.com
 *
 * Memakai database & kode OTP asli project. Baris uji memakai email
 * palsu (selftest*@example.test) dan dibersihkan otomatis di akhir.
 */

if (PHP_SAPI !== "cli") {
    http_response_code(404);
    exit;
}

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/otp.php";
require_once __DIR__ . "/../config/mailer.php";

$lulus = 0;
$gagal = 0;

function cek(string $nama, bool $kondisi): void
{
    global $lulus, $gagal;

    $kondisi ? $lulus++ : $gagal++;
    echo ($kondisi ? "[LULUS] " : "[GAGAL] ") . $nama . PHP_EOL;
}

function emailUji(): string
{
    return "selftest" . bin2hex(random_bytes(4)) . "@example.test";
}

function salah(string $benar): string
{
    return $benar === "000000" ? "111111" : "000000";
}

$ip = "203.0.113.7"; // IP dokumentasi, khusus uji
$reg = OTP_PURPOSE_REGISTER;
$rst = OTP_PURPOSE_RESET;

try {

    otpEnsureTable($conn);
    cek("Tabel otp_codes tersedia", $conn->query("SHOW TABLES LIKE 'otp_codes'")->num_rows === 1);

    // ---- Pembuatan, hash, masa berlaku ----
    $email = emailUji();
    $o = otpCreate($conn, $email, $reg, "siswa", $ip);
    $row = $conn->query("SELECT otp_hash, expires_at, created_at FROM otp_codes WHERE id_otp = " . (int) $o["id"])->fetch_assoc();

    cek("Kode OTP 6 digit dibuat otomatis oleh backend", preg_match('/^\d{6}$/', $o["code"]) === 1);
    cek("OTP disimpan sebagai hash (bukan teks biasa)", $row["otp_hash"] !== $o["code"] && password_verify($o["code"], $row["otp_hash"]));
    cek("Masa berlaku OTP = 5 menit", strtotime($row["expires_at"]) - strtotime($row["created_at"]) === 300);

    // ---- Pembeda tujuan ----
    $r = otpVerify($conn, $email, $rst, $o["code"]);
    cek("OTP registrasi TIDAK bisa dipakai untuk lupa password", $r["ok"] === false);

    // ---- OTP salah ditolak ----
    $r = otpVerify($conn, $email, $reg, salah($o["code"]));
    cek("4. OTP salah ditolak", $r["ok"] === false && $r["code"] === "wrong");

    // ---- OTP benar + sekali pakai ----
    $r = otpVerify($conn, $email, $reg, $o["code"]);
    cek("3. OTP benar berhasil diverifikasi", $r["ok"] === true);

    $r = otpVerify($conn, $email, $reg, $o["code"]);
    cek("6. OTP yang sudah diverifikasi tidak bisa diverifikasi lagi", $r["ok"] === false);

    cek("6. Pemakaian OTP (consume) pertama berhasil", otpConsume($conn, $reg, $email, "siswa") === true);
    cek("6. Pemakaian OTP (consume) kedua DITOLAK (sekali pakai)", otpConsume($conn, $reg, $email, "siswa") === false);

    // ---- Kedaluwarsa ----
    $email = emailUji();
    $o = otpCreate($conn, $email, $reg, "siswa", $ip);
    $lampau = otpTanggal(time() - 5);
    $conn->query("UPDATE otp_codes SET expires_at = '$lampau' WHERE id_otp = " . (int) $o["id"]);
    $r = otpVerify($conn, $email, $reg, $o["code"]);
    cek("5. OTP kedaluwarsa ditolak walau kodenya benar", $r["ok"] === false && $r["code"] === "expired");

    // ---- Batas percobaan ----
    $email = emailUji();
    $o = otpCreate($conn, $email, $reg, "siswa", $ip);
    for ($i = 0; $i < OTP_MAX_ATTEMPTS; $i++) {
        otpVerify($conn, $email, $reg, salah($o["code"]));
    }
    $r = otpVerify($conn, $email, $reg, $o["code"]);
    cek("Setelah " . OTP_MAX_ATTEMPTS . " tebakan salah, kode benar pun ditolak (terkunci)", $r["ok"] === false);

    // ---- Jeda 60 detik ----
    $email = emailUji();
    $o = otpCreate($conn, $email, $rst, "siswa", $ip);
    $b = otpCheckRateLimit($conn, $email, $rst, $ip);
    cek("7. Kirim ulang langsung setelah kirim ditolak (jeda 60 dtk)", $b !== null && $b["type"] === "cooldown");

    $lama = otpTanggal(time() - 61);
    $conn->query("UPDATE otp_codes SET created_at = '$lama' WHERE id_otp = " . (int) $o["id"]);
    cek("7. Setelah 61 detik kirim ulang diizinkan", otpCheckRateLimit($conn, $email, $rst, $ip) === null);

    // ---- OTP lupa password: verifikasi benar ----
    $r = otpVerify($conn, $email, $rst, $o["code"]);
    cek("9. OTP lupa password berhasil diverifikasi", $r["ok"] === true);
    cek("9. Bukti verifikasi lupa password aktif untuk atur password baru", otpActiveProof($conn, $rst) !== null);

    // ---- Email nyata (opsional) ----
    if (!empty($argv[1])) {
        $ok = kirimEmailOtp($argv[1], "123456", $reg);
        cek("2. Email OTP terkirim ke " . $argv[1] . " (cek kotak masuk/spam)", $ok);

        if (!$ok) {
            echo "       Penyebab: " . mailerLastError() . " (detail teknis ada di error_log PHP)" . PHP_EOL;
        }
    } else {
        echo "[LEWAT] 2. Uji email nyata: jalankan  php tests/otp_selftest.php emailanda@gmail.com" . PHP_EOL;
    }

} catch (Throwable $e) {

    $gagal++;
    echo "[GAGAL] Error tak terduga: " . $e->getMessage() . PHP_EOL;

} finally {

    $conn->query("DELETE FROM otp_codes WHERE email LIKE 'selftest%@example.test'");
}

echo PHP_EOL . "Hasil: $lulus lulus, $gagal gagal" . PHP_EOL;
exit($gagal > 0 ? 1 : 0);