<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * Memuat PHPMailer.
 * Mendukung 2 cara pemasangan:
 *   1. Composer  -> vendor/autoload.php  (di root project)
 *   2. Manual    -> libs/PHPMailer/src/   (di root project)
 */
function muatPhpMailer(): bool
{
    if (class_exists(PHPMailer::class)) {
        return true;
    }

    $root = dirname(__DIR__);

    if (is_file($root . "/vendor/autoload.php")) {
        require_once $root . "/vendor/autoload.php";
        return class_exists(PHPMailer::class);
    }

    $src = $root . "/libs/PHPMailer/src";

    if (is_file($src . "/PHPMailer.php")) {
        require_once $src . "/Exception.php";
        require_once $src . "/PHPMailer.php";
        require_once $src . "/SMTP.php";
        return class_exists(PHPMailer::class);
    }

    return false;
}

/**
 * Pesan error terakhir dari kirimEmailOtp(), aman ditampilkan ke pengguna
 * (tidak pernah memuat password / App Password / detail server).
 * Detail teknis hanya ditulis ke error_log server.
 */
function mailerLastError(?string $set = null): string
{
    static $pesan = "Gagal mengirim email OTP. Silakan coba lagi nanti.";

    if ($set !== null) {
        $pesan = $set;
    }

    return $pesan;
}

/**
 * Mengirim email OTP (dipakai untuk registrasi DAN reset password).
 *
 * @param string $toEmail Alamat penerima
 * @param string $otp     Kode OTP 6 digit (teks asli, hanya untuk email)
 * @param string $purpose "register" atau "reset_password"
 * @return bool true jika email berhasil dikirim
 */
function kirimEmailOtp(string $toEmail, string $otp, string $purpose): bool
{
    mailerLastError("Gagal mengirim email OTP. Silakan coba lagi nanti.");

    if (!muatPhpMailer()) {
        error_log("[MUSAComplaint] PHPMailer belum terpasang (vendor/ atau libs/PHPMailer/).");
        mailerLastError("Pustaka PHPMailer belum terpasang di server. Hubungi administrator.");
        return false;
    }

    $config = require __DIR__ . "/mail.php";

    // Gmail App Password ditampilkan Google dengan spasi (xxxx xxxx xxxx xxxx);
    // spasi harus dibuang agar autentikasi SMTP berhasil.
    $config["password"] = preg_replace('/\s+/', '', $config["password"]);

    if ($config["from_email"] === "") {
        $config["from_email"] = $config["username"];
    }

    $masihContoh = $config["username"] === "emailanda@gmail.com"
        || stripos($config["password"], "xxxx") !== false;

    if ($config["username"] === "" || $config["password"] === "" || $masihContoh) {
        error_log("[MUSAComplaint] Konfigurasi SMTP belum diisi (config/mail.local.php).");
        mailerLastError("Layanan email belum dikonfigurasi di server. Hubungi administrator.");
        return false;
    }

    if ($purpose === "reset_password") {
        $judul = "OTP Lupa Password";
        $intro = "Kami menerima permintaan untuk mereset password akun MUSAComplaint Anda. "
               . "Gunakan kode berikut untuk melanjutkan.";
    } else {
        $judul = "OTP Register";
        $intro = "Terima kasih telah mendaftar di MUSAComplaint. "
               . "Gunakan kode berikut untuk memverifikasi email Anda.";
    }

    $kode = htmlspecialchars($otp, ENT_QUOTES, "UTF-8");
    $introHtml = htmlspecialchars($intro, ENT_QUOTES, "UTF-8");

    $html = '
<div style="margin:0;padding:24px;background:#f5f9ff;font-family:Arial,Helvetica,sans-serif;color:#172033;">
  <div style="max-width:480px;margin:0 auto;background:#ffffff;border:1px solid #dbeafe;border-radius:14px;padding:32px;">
    <h1 style="margin:0 0 4px;font-size:22px;color:#2563eb;">MUSAComplaint</h1>
    <p style="margin:0 0 24px;font-size:12px;color:#64748b;">Sistem Pengaduan Sekolah</p>
    <h2 style="margin:0 0 12px;font-size:17px;">' . $judul . '</h2>
    <p style="margin:0 0 20px;font-size:14px;line-height:1.7;color:#334155;">' . $introHtml . '</p>
    <div style="text-align:center;margin:0 0 20px;">
      <span style="display:inline-block;padding:14px 26px;background:#eff6ff;border:1px solid #93c5fd;border-radius:10px;font-size:32px;font-weight:700;letter-spacing:8px;color:#1d4ed8;">' . $kode . '</span>
    </div>
    <p style="margin:0 0 8px;font-size:13px;color:#334155;">Kode ini berlaku selama <strong>5 menit</strong> dan hanya dapat digunakan satu kali.</p>
    <p style="margin:0 0 20px;font-size:13px;color:#b91c1c;"><strong>Kode ini bersifat rahasia.</strong> Jangan bagikan kepada siapapun, termasuk pihak yang mengaku sebagai admin atau pihak sekolah.</p>
    <hr style="border:none;border-top:1px solid #e2e8f0;margin:0 0 14px;">
    <p style="margin:0;font-size:11px;color:#94a3b8;">Jika Anda tidak merasa melakukan permintaan ini, abaikan email ini.</p>
  </div>
</div>';

    $teks = "MUSAComplaint - " . $judul . "\n\n"
          . $intro . "\n\n"
          . "Kode OTP Anda: " . $otp . "\n\n"
          . "Kode berlaku selama 5 menit dan hanya dapat digunakan satu kali.\n"
          . "Kode ini bersifat rahasia. Jangan bagikan kepada siapapun.\n\n"
          . "Jika Anda tidak merasa melakukan permintaan ini, abaikan email ini.";

    try {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = $config["host"];
        $mail->Port       = $config["port"];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config["username"];
        $mail->Password   = $config["password"];
        $mail->SMTPSecure = $config["encryption"] === "ssl"
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPAutoTLS = true;
        $mail->Timeout    = 15;
        $mail->SMTPDebug  = 0;
        $mail->CharSet    = "UTF-8";

        if (!$config["verify_ssl"]) {
            $mail->SMTPOptions = [
                "ssl" => [
                    "verify_peer"       => false,
                    "verify_peer_name"  => false,
                    "allow_self_signed" => true,
                ],
            ];
        }

        $mail->setFrom($config["from_email"], $config["from_name"]);
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = "[MUSAComplaint] " . $judul;
        $mail->Body    = $html;
        $mail->AltBody = $teks;

        $mail->send();

        return true;
    } catch (MailException $e) {
        error_log("[MUSAComplaint] Gagal kirim email OTP: " . $e->getMessage());
        mailerLastError("Gagal mengirim email OTP. Periksa konfigurasi SMTP (Gmail App Password) atau coba lagi nanti.");
        return false;
    } catch (Throwable $e) {
        error_log("[MUSAComplaint] Error email OTP: " . $e->getMessage());
        mailerLastError("Gagal mengirim email OTP. Silakan coba lagi nanti.");
        return false;
    }
}