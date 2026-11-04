<?php

require_once "../../config/database.php";
require_once "../../config/otp.php";

// Jika OTP sudah terverifikasi dan belum dipakai (mis. halaman dimuat ulang),
// langsung tampilkan langkah buat password baru.
$proofAktif = otpActiveProof($conn, OTP_PURPOSE_RESET);
$langkahAwal = $proofAktif ? 3 : 1;

$judul = [
    1 => [
        "Lupa Password?",
        "Masukkan email akun Anda untuk mendapatkan kode verifikasi.",
    ],
    2 => [
        "Verifikasi OTP Lupa Password",
        "Masukkan 6 digit kode OTP yang dikirim ke email Anda. Kode berlaku selama 5 menit.",
    ],
    3 => [
        "Buat Password Baru",
        "Buat password baru untuk akun Anda. Gunakan minimal 6 karakter.",
    ],
];

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Lupa Password - MUSAcomplaints</title>

    <link rel="stylesheet" href="../../asests/css/login.css">
</head>

<body>

    <main class="auth-container">

        <?php
        $authAssets = "../../asests";
        include __DIR__ . "/../auth_side.php";
        ?>

        <div class="auth-main">

            <section class="auth-card">

                <div class="card-brand">
                <img src="../../asests/foto/logo.png" alt="Logo MUSAComplaint">
                <div>
                    <strong>MUSA<span>Complaint</span></strong>
                    <small>Sistem Pengaduan Sekolah</small>
                </div>
            </div>

                <div class="auth-heading">
                    <h1 id="judulLangkah"><?= htmlspecialchars($judul[$langkahAwal][0]); ?></h1>

                    <p id="deskripsiLangkah">
                        <?= htmlspecialchars($judul[$langkahAwal][1]); ?>
                    </p>
                </div>

                <div class="error-message" id="alertBox" role="alert" hidden>
                    <span id="alertIcon">!</span>
                    <div class="alert-text" id="alertText"></div>
                </div>


                <!-- LANGKAH 1: EMAIL -->
                <form id="stepEmail" novalidate <?= $langkahAwal === 1 ? "" : "hidden"; ?>>

                    <div class="form-group">

                        <label for="email">Email</label>

                        <div class="input-wrapper">
                            <svg class="ico input-icon" aria-hidden="true"><use href="#i-mail"/></svg>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="Masukkan email akun Anda"
                                autocomplete="email"
                                required
                            >
                        </div>

                    </div>

                    <button type="submit" class="login-button" id="btnKirimEmail">
                        <span class="btn-label">Kirim Kode OTP</span>
                        <svg class="ico btn-arrow" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                    </button>

                </form>


                <!-- LANGKAH 2: VERIFIKASI OTP -->
                <form id="stepOtp" novalidate hidden>

                    <p class="otp-hint">
                        Kode dikirim ke <strong id="emailTampil"></strong>
                    </p>

                    <div class="form-group">

                        <label for="otp">Kode OTP Lupa Password</label>

                        <div class="input-wrapper">
                            <svg class="ico input-icon" aria-hidden="true"><use href="#i-shield"/></svg>
                            <input
                                type="text"
                                id="otp"
                                class="otp-code"
                                inputmode="numeric"
                                maxlength="6"
                                autocomplete="one-time-code"
                                placeholder="Masukkan 6 digit kode OTP"
                                required
                            >
                        </div>

                    </div>

                    <button type="submit" class="login-button" id="btnVerifikasi">
                        <span class="btn-label">Verifikasi Kode</span>
                        <svg class="ico btn-arrow" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                    </button>

                    <div class="otp-resend">
                        <button type="button" class="otp-link-btn" id="btnKirimUlang" disabled>
                            Kirim Ulang OTP
                        </button>

                        <button type="button" class="otp-link-btn is-muted" id="btnGantiEmail">
                            Ganti email
                        </button>
                    </div>

                </form>


                <!-- LANGKAH 3: PASSWORD BARU -->
                <form id="stepPassword" novalidate <?= $langkahAwal === 3 ? "" : "hidden"; ?>>

                    <div class="form-group">

                        <label for="passwordBaru">Password Baru</label>

                        <div class="input-wrapper password-wrapper">
                            <svg class="ico input-icon" aria-hidden="true"><use href="#i-lock"/></svg>
                            <input
                                type="password"
                                id="passwordBaru"
                                placeholder="Minimal 6 karakter"
                                autocomplete="new-password"
                                required
                            >
                            <button type="button" class="toggle-password" data-target="passwordBaru" aria-label="Tampilkan password">
                                <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                    <path class="eye-slash" d="M4 4l16 16"/>
                                </svg>
                            </button>
                        </div>

                    </div>

                    <div class="form-group">

                        <label for="konfirmasiPassword">Konfirmasi Password</label>

                        <div class="input-wrapper password-wrapper">
                            <svg class="ico input-icon" aria-hidden="true"><use href="#i-lock"/></svg>
                            <input
                                type="password"
                                id="konfirmasiPassword"
                                placeholder="Masukkan ulang password baru"
                                autocomplete="new-password"
                                required
                            >
                            <button type="button" class="toggle-password" data-target="konfirmasiPassword" aria-label="Tampilkan password">
                                <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                    <path class="eye-slash" d="M4 4l16 16"/>
                                </svg>
                            </button>
                        </div>

                    </div>

                    <button type="submit" class="login-button" id="btnSimpan">
                        <span class="btn-label">Reset Password</span>
                        <svg class="ico btn-arrow" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                    </button>

                </form>


                <div class="auth-footer">
                    <p>
                        Ingat password Anda?
                        <a href="../login.php">Kembali ke login</a>
                    </p>
                </div>

            </section>

            <p class="page-copy">© <?= date("Y"); ?> MUSAComplaint. Seluruh hak dilindungi.</p>

        </div>

    </main>

    <script>
    window.MUSA_LUPA = <?= json_encode([
        "csrf"      => otpCsrfToken(),
        "sendUrl"   => "send_otp.php",
        "verifyUrl" => "verify_otp.php",
        "resetUrl"  => "reset_password.php",
        "step"      => $langkahAwal,
        "headings"  => $judul,
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="../../asests/js/otp.js"></script>
    <script src="../../asests/js/lupa_password.js"></script>

</body>
</html>