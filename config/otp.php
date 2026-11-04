<?php

/**
 * LAYANAN OTP BERSAMA - MUSAComplaint
 * Dipakai oleh registrasi (purpose = register)
 * dan lupa password (purpose = reset_password).
 *
 * Alur status satu baris OTP di tabel otp_codes:
 *   dibuat  -> (verified_at NULL, is_used 0)  : menunggu diverifikasi
 *   benar   -> (verified_at terisi, is_used 0): sudah terverifikasi, belum dipakai
 *   dipakai -> (is_used 1)                    : registrasi/reset selesai ATAU dinonaktifkan
 */

require_once __DIR__ . "/session.php";

date_default_timezone_set("Asia/Jakarta");

const OTP_LENGTH = 6;
const OTP_TTL = 300;                // masa berlaku OTP: 5 menit
const OTP_COOLDOWN = 60;            // jeda minta OTP baru: 60 detik
const OTP_MAX_ATTEMPTS = 5;         // batas percobaan verifikasi salah per OTP
const OTP_MAX_PER_EMAIL_HOUR = 5;   // maks. OTP per email per jam
const OTP_MAX_PER_IP_HOUR = 30;     // maks. OTP per IP per jam
const OTP_PROOF_TTL = 600;          // setelah verifikasi, user punya 10 menit menyelesaikan proses

const OTP_PURPOSE_REGISTER = "register";
const OTP_PURPOSE_RESET = "reset_password";

/* =========================================
   TABEL DATABASE
========================================= */

/**
 * Memastikan tabel otp_codes ada (CREATE TABLE IF NOT EXISTS).
 * Struktur identik dengan database/otp_codes.sql. Dipanggil sebelum
 * query OTP apa pun, dan TIDAK boleh dipanggil di dalam transaksi
 * (DDL di MySQL menyebabkan commit implisit).
 */
function otpEnsureTable(mysqli $conn): void
{
    static $sudah = false;

    if ($sudah) {
        return;
    }

    $conn->query(
        "CREATE TABLE IF NOT EXISTS `otp_codes` (
            `id_otp`      int UNSIGNED NOT NULL AUTO_INCREMENT,
            `email`       varchar(100) NOT NULL,
            `purpose`     enum('register','reset_password') NOT NULL,
            `role`        enum('siswa','admin') NOT NULL,
            `otp_hash`    varchar(255) NOT NULL,
            `attempts`    tinyint UNSIGNED NOT NULL DEFAULT 0,
            `expires_at`  datetime NOT NULL,
            `is_used`     tinyint(1) NOT NULL DEFAULT 0,
            `verified_at` datetime DEFAULT NULL,
            `used_at`     datetime DEFAULT NULL,
            `ip_address`  varchar(45) NOT NULL DEFAULT '',
            `created_at`  datetime NOT NULL,
            PRIMARY KEY (`id_otp`),
            KEY `idx_email_purpose` (`email`, `purpose`, `id_otp`),
            KEY `idx_ip_created` (`ip_address`, `created_at`),
            KEY `idx_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $sudah = true;
}

/* =========================================
   UTILITAS
========================================= */

function otpTanggal(int $timestamp): string
{
    return date("Y-m-d H:i:s", $timestamp);
}

function otpJson(array $data, int $status = 200): void
{
    http_response_code($status);
    header("Content-Type: application/json; charset=UTF-8");
    header("Cache-Control: no-store");
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function otpCsrfToken(): string
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

function otpCsrfValid($token): bool
{
    return is_string($token)
        && !empty($_SESSION["csrf_token"])
        && hash_equals($_SESSION["csrf_token"], $token);
}

function otpClientIp(): string
{
    return substr($_SERVER["REMOTE_ADDR"] ?? "0.0.0.0", 0, 45);
}

function otpNormalizeEmail(string $email): string
{
    return strtolower(trim($email));
}

function otpValidPurpose($purpose): bool
{
    return in_array($purpose, [OTP_PURPOSE_REGISTER, OTP_PURPOSE_RESET], true);
}

function otpValidRole($role): bool
{
    return in_array($role, ["siswa", "admin"], true);
}

/** Cek apakah email sudah terdaftar pada tabel akun sesuai role. */
function otpEmailExists(mysqli $conn, string $role, string $email): bool
{
    $tabel = ["siswa" => "siswa", "admin" => "admin"][$role] ?? null;

    if ($tabel === null) {
        return false;
    }

    $stmt = $conn->prepare("SELECT 1 FROM `$tabel` WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $ada = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $ada;
}

/* =========================================
   PEMBATASAN PENGIRIMAN (ANTI-SPAM)
========================================= */

/**
 * @return array|null null jika boleh kirim, atau ["message", "retry_after"]
 */
function otpCheckRateLimit(mysqli $conn, string $email, string $purpose, string $ip): ?array
{
    otpEnsureTable($conn);

    $now = time();

    // 1. Jeda 60 detik sejak permintaan terakhir
    $stmt = $conn->prepare(
        "SELECT created_at FROM otp_codes
         WHERE email = ? AND purpose = ?
         ORDER BY id_otp DESC LIMIT 1"
    );
    $stmt->bind_param("ss", $email, $purpose);
    $stmt->execute();
    $last = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($last) {
        $selisih = $now - strtotime($last["created_at"]);

        if ($selisih < OTP_COOLDOWN) {
            $sisa = OTP_COOLDOWN - $selisih;

            return [
                "type"        => "cooldown",
                "message"     => "Mohon tunggu $sisa detik sebelum meminta kode baru.",
                "retry_after" => $sisa,
            ];
        }
    }

    $sejak = otpTanggal($now - 3600);

    // 2. Batas per email per jam
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total, MIN(created_at) AS tertua
         FROM otp_codes
         WHERE email = ? AND purpose = ? AND created_at >= ?"
    );
    $stmt->bind_param("sss", $email, $purpose, $sejak);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ((int) $row["total"] >= OTP_MAX_PER_EMAIL_HOUR) {
        $sisa = max(60, 3600 - ($now - strtotime($row["tertua"])));

        return [
            "type"        => "limit",
            "message"     => "Permintaan kode OTP terlalu sering. Silakan coba lagi nanti.",
            "retry_after" => $sisa,
        ];
    }

    // 3. Batas per alamat IP per jam
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total, MIN(created_at) AS tertua
         FROM otp_codes
         WHERE ip_address = ? AND created_at >= ?"
    );
    $stmt->bind_param("ss", $ip, $sejak);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ((int) $row["total"] >= OTP_MAX_PER_IP_HOUR) {
        $sisa = max(60, 3600 - ($now - strtotime($row["tertua"])));

        return [
            "type"        => "limit",
            "message"     => "Permintaan kode OTP terlalu sering. Silakan coba lagi nanti.",
            "retry_after" => $sisa,
        ];
    }

    return null;
}

/* =========================================
   PEMBUATAN OTP
========================================= */

/**
 * Membuat OTP baru & menonaktifkan semua OTP lama (email + purpose yang sama).
 * Jika $dummy = true, baris disimpan sebagai sudah-terpakai (tidak bisa diverifikasi);
 * dipakai agar perilaku & pembatasan sama untuk email yang tidak terdaftar.
 *
 * @return array ["id" => int, "code" => string]
 */
function otpCreate(
    mysqli $conn,
    string $email,
    string $purpose,
    string $role,
    string $ip,
    bool $dummy = false
): array {
    otpEnsureTable($conn);

    $code = str_pad((string) random_int(0, 999999), OTP_LENGTH, "0", STR_PAD_LEFT);
    $hash = password_hash($code, PASSWORD_DEFAULT);

    $now = time();
    $dibuat = otpTanggal($now);
    $kedaluwarsa = otpTanggal($now + OTP_TTL);
    $terpakai = $dummy ? 1 : 0;

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare(
            "UPDATE otp_codes SET is_used = 1
             WHERE email = ? AND purpose = ? AND is_used = 0"
        );
        $stmt->bind_param("ss", $email, $purpose);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare(
            "INSERT INTO otp_codes
             (email, purpose, role, otp_hash, expires_at, is_used, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "sssssiss",
            $email,
            $purpose,
            $role,
            $hash,
            $kedaluwarsa,
            $terpakai,
            $ip,
            $dibuat
        );
        $stmt->execute();
        $id = (int) $conn->insert_id;
        $stmt->close();

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }

    return ["id" => $id, "code" => $code];
}

/** Menonaktifkan satu OTP (mis. gagal kirim email, kedaluwarsa, percobaan habis). */
function otpInvalidate(mysqli $conn, int $id): void
{
    $stmt = $conn->prepare("UPDATE otp_codes SET is_used = 1 WHERE id_otp = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

/**
 * Menghapus satu OTP. Dipakai saat email GAGAL terkirim, supaya pengguna
 * tidak terkena jeda 60 detik / kuota per jam untuk kode yang tidak pernah sampai.
 */
function otpDelete(mysqli $conn, int $id): void
{
    $stmt = $conn->prepare("DELETE FROM otp_codes WHERE id_otp = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

/** Hapus OTP lebih dari 1 hari (dipanggil sesekali agar tabel tidak membengkak). */
function otpCleanup(mysqli $conn): void
{
    $batas = otpTanggal(time() - 86400);
    $stmt = $conn->prepare("DELETE FROM otp_codes WHERE created_at < ?");
    $stmt->bind_param("s", $batas);
    $stmt->execute();
    $stmt->close();
}

/* =========================================
   VERIFIKASI OTP
========================================= */

/**
 * @return array ["ok" => bool, "code" => string, "message" => string, "role" => string]
 *
 * Untuk purpose reset_password, semua kegagalan memakai satu pesan umum
 * agar tidak membocorkan apakah sebuah email terdaftar.
 */
function otpVerify(mysqli $conn, string $email, string $purpose, string $kode): array
{
    otpEnsureTable($conn);

    $spesifik = ($purpose === OTP_PURPOSE_REGISTER);

    $umum = [
        "ok"      => false,
        "code"    => "invalid",
        "message" => "Kode OTP salah atau sudah kedaluwarsa. Periksa kembali kode Anda atau minta kode baru.",
    ];

    $stmt = $conn->prepare(
        "SELECT id_otp, role, otp_hash, attempts, expires_at
         FROM otp_codes
         WHERE email = ? AND purpose = ? AND is_used = 0 AND verified_at IS NULL
         ORDER BY id_otp DESC LIMIT 1"
    );
    $stmt->bind_param("ss", $email, $purpose);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return $spesifik
            ? [
                "ok"      => false,
                "code"    => "not_found",
                "message" => "Tidak ada kode OTP yang aktif (belum dikirim, kedaluwarsa, atau sudah digunakan). Silakan kirim kode OTP.",
            ]
            : $umum;
    }

    $id = (int) $row["id_otp"];

    // Kedaluwarsa
    if (strtotime($row["expires_at"]) < time()) {
        otpInvalidate($conn, $id);

        return $spesifik
            ? [
                "ok"      => false,
                "code"    => "expired",
                "message" => "Kode OTP sudah kedaluwarsa. Silakan kirim ulang kode OTP.",
            ]
            : $umum;
    }

    // Percobaan sudah habis
    if ((int) $row["attempts"] >= OTP_MAX_ATTEMPTS) {
        otpInvalidate($conn, $id);

        return $spesifik
            ? [
                "ok"      => false,
                "code"    => "locked",
                "message" => "Terlalu banyak percobaan salah. Silakan kirim ulang kode OTP.",
            ]
            : $umum;
    }

    // Catat percobaan LEBIH DULU (aman dari request paralel)
    $maks = OTP_MAX_ATTEMPTS;
    $stmt = $conn->prepare(
        "UPDATE otp_codes SET attempts = attempts + 1
         WHERE id_otp = ? AND is_used = 0 AND attempts < ?"
    );
    $stmt->bind_param("ii", $id, $maks);
    $stmt->execute();
    $tercatat = $stmt->affected_rows === 1;
    $stmt->close();

    if (!$tercatat) {
        return $umum;
    }

    $percobaan = (int) $row["attempts"] + 1;

    // Kode salah
    if (!password_verify($kode, $row["otp_hash"])) {
        $sisa = max(0, OTP_MAX_ATTEMPTS - $percobaan);

        if ($sisa === 0) {
            otpInvalidate($conn, $id);
        }

        if (!$spesifik) {
            return $umum;
        }

        return [
            "ok"      => false,
            "code"    => $sisa > 0 ? "wrong" : "locked",
            "message" => $sisa > 0
                ? "Kode OTP salah. Sisa percobaan: $sisa."
                : "Kode OTP salah dan percobaan sudah habis. Silakan kirim ulang kode OTP.",
        ];
    }

    // Kode benar -> tandai terverifikasi (atomik, hanya sekali)
    $sekarang = otpTanggal(time());
    $stmt = $conn->prepare(
        "UPDATE otp_codes SET verified_at = ?
         WHERE id_otp = ? AND is_used = 0 AND verified_at IS NULL AND expires_at >= ?"
    );
    $stmt->bind_param("sis", $sekarang, $id, $sekarang);
    $stmt->execute();
    $berhasil = $stmt->affected_rows === 1;
    $stmt->close();

    if (!$berhasil) {
        return $umum;
    }

    $_SESSION["otp_proof"][$purpose] = [
        "id"    => $id,
        "email" => $email,
        "role"  => $row["role"],
        "at"    => time(),
    ];

    return ["ok" => true, "code" => "verified", "message" => "Kode OTP benar.", "role" => $row["role"]];
}

/* =========================================
   BUKTI VERIFIKASI (SESSION + DATABASE)
========================================= */

/** Bukti verifikasi dari session saja (belum dicek ke database). */
function otpGetProof(string $purpose): ?array
{
    $proof = $_SESSION["otp_proof"][$purpose] ?? null;

    if (!is_array($proof) || !isset($proof["id"], $proof["email"], $proof["role"], $proof["at"])) {
        return null;
    }

    if (time() - (int) $proof["at"] > OTP_PROOF_TTL) {
        unset($_SESSION["otp_proof"][$purpose]);
        return null;
    }

    return $proof;
}

function otpClearProof(string $purpose): void
{
    unset($_SESSION["otp_proof"][$purpose]);
}

/** Bukti verifikasi yang juga dicek ke database (terverifikasi, belum dipakai, belum lewat batas). */
function otpActiveProof(mysqli $conn, string $purpose): ?array
{
    $proof = otpGetProof($purpose);

    if (!$proof) {
        return null;
    }

    otpEnsureTable($conn);

    $id = (int) $proof["id"];
    $batas = otpTanggal(time() - OTP_PROOF_TTL);

    $stmt = $conn->prepare(
        "SELECT 1 FROM otp_codes
         WHERE id_otp = ? AND purpose = ? AND email = ? AND role = ?
           AND is_used = 0 AND verified_at IS NOT NULL AND verified_at >= ?
         LIMIT 1"
    );
    $stmt->bind_param("issss", $id, $purpose, $proof["email"], $proof["role"], $batas);
    $stmt->execute();
    $valid = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if (!$valid) {
        otpClearProof($purpose);
        return null;
    }

    return $proof;
}

/**
 * Menandai OTP terverifikasi sebagai TERPAKAI (sekali pakai, atomik).
 * Panggil di dalam transaksi bersama proses simpan akun / ganti password.
 * Setelah commit berhasil, panggil otpClearProof().
 */
function otpConsume(mysqli $conn, string $purpose, string $email, string $role): bool
{
    $proof = otpGetProof($purpose);

    if (!$proof || $proof["email"] !== $email || $proof["role"] !== $role) {
        return false;
    }

    $id = (int) $proof["id"];
    $sekarang = otpTanggal(time());
    $batas = otpTanggal(time() - OTP_PROOF_TTL);

    $stmt = $conn->prepare(
        "UPDATE otp_codes SET is_used = 1, used_at = ?
         WHERE id_otp = ? AND email = ? AND purpose = ? AND role = ?
           AND is_used = 0 AND verified_at IS NOT NULL AND verified_at >= ?"
    );
    $stmt->bind_param("sissss", $sekarang, $id, $email, $purpose, $role, $batas);
    $stmt->execute();
    $berhasil = $stmt->affected_rows === 1;
    $stmt->close();

    return $berhasil;
}