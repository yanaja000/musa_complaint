<?php

require_once "../config/database.php";
require_once "../config/otp.php";

$error = "";
$success = "";

// Batas maksimal akun admin
const MAX_ADMIN = 2;

$jumlah_admin = (int) $conn->query(
    "SELECT COUNT(*) AS total FROM admin"
)->fetch_assoc()["total"];

$admin_penuh = $jumlah_admin >= MAX_ADMIN;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $role = $_POST["role"] ?? "";
    $nama = trim($_POST["nama"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $konfirmasi_password = $_POST["konfirmasi_password"] ?? "";

    $nis = trim($_POST["nis"] ?? "");
    $kelas = trim($_POST["kelas"] ?? "");

    // Status verifikasi email (OTP) - dicek di backend
    $proof = otpGetProof(OTP_PURPOSE_REGISTER);
    $emailTerverifikasi = $proof !== null
        && $proof["email"] === otpNormalizeEmail($email)
        && $proof["role"] === $role;

    // Validasi dasar
    if (empty($role) || empty($nama) || empty($email) ||
        empty($username) || empty($password) ||
        empty($konfirmasi_password)) {

        $error = "Semua field wajib diisi.";

    } elseif (!in_array($role, ["siswa", "admin"])) {

        $error = "Role tidak valid.";

    } elseif ($role === "admin" && $admin_penuh) {

        $error = "Registrasi admin ditutup karena kuota admin sudah penuh (maksimal " . MAX_ADMIN . ").";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Format email tidak valid.";

    } elseif ($password !== $konfirmasi_password) {

        $error = "Konfirmasi password tidak sama.";

    } elseif (strlen($password) < 6) {

        $error = "Password minimal 6 karakter.";

    } elseif (!$emailTerverifikasi) {

        $error = "Email belum diverifikasi. Silakan kirim dan masukkan kode OTP terlebih dahulu.";

    } else {

        // =====================================
        // REGISTRASI SISWA
        // =====================================

        if ($role === "siswa") {

            if (empty($nis) || empty($kelas)) {
                $error = "NIS dan kelas wajib diisi.";
            } else {

                // Cek username/email/NIS
                $stmt = $conn->prepare(
                    "SELECT id_siswa
                     FROM siswa
                     WHERE username = ? OR email = ? OR nis = ?
                     LIMIT 1"
                );

                $stmt->bind_param(
                    "sss",
                    $username,
                    $email,
                    $nis
                );

                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {

                    $error = "Username, email, atau NIS sudah digunakan.";

                } else {

                    $hashed_password = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    $stmt = $conn->prepare(
                        "INSERT INTO siswa
                        (nis, nama, kelas, email, username, password, role)
                        VALUES (?, ?, ?, ?, ?, ?, 'siswa')"
                    );

                    $stmt->bind_param(
                        "ssssss",
                        $nis,
                        $nama,
                        $kelas,
                        $email,
                        $username,
                        $hashed_password
                    );

                    $conn->begin_transaction();

                    try {

                        if (!otpConsume($conn, OTP_PURPOSE_REGISTER, otpNormalizeEmail($email), $role)) {

                            $conn->rollback();
                            $error = "Verifikasi email tidak valid atau sudah digunakan. Silakan kirim ulang kode OTP.";

                        } elseif ($stmt->execute()) {

                            $conn->commit();
                            otpClearProof(OTP_PURPOSE_REGISTER);
                            $success = "Registrasi siswa berhasil. Silakan login.";

                        } else {

                            $conn->rollback();
                            $error = "Registrasi gagal.";

                        }

                    } catch (Throwable $e) {

                        $conn->rollback();
                        error_log("[MUSAComplaint] Registrasi siswa gagal: " . $e->getMessage());
                        $error = "Registrasi gagal.";

                    }
                }
            }
        }

        // =====================================
        // REGISTRASI ADMIN
        // =====================================

        elseif ($role === "admin") {

            // Cek username/email
            $stmt = $conn->prepare(
                "SELECT id_admin
                 FROM admin
                 WHERE username = ? OR email = ?
                 LIMIT 1"
            );

            $stmt->bind_param(
                "ss",
                $username,
                $email
            );

            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {

                $error = "Username atau email sudah digunakan.";

            } else {

                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Insert hanya jika jumlah admin masih di bawah batas.
                // Pengecekan dilakukan di dalam query yang sama agar
                // tidak bisa ditembus oleh dua pendaftaran bersamaan.
                $max_admin = MAX_ADMIN;

                $stmt = $conn->prepare(
                    "INSERT INTO admin
                    (nama, email, username, password, role)
                    SELECT ?, ?, ?, ?, 'admin'
                    FROM DUAL
                    WHERE (SELECT COUNT(*) FROM admin) < ?"
                );

                $stmt->bind_param(
                    "ssssi",
                    $nama,
                    $email,
                    $username,
                    $hashed_password,
                    $max_admin
                );

                $conn->begin_transaction();

                try {

                    if (!otpConsume($conn, OTP_PURPOSE_REGISTER, otpNormalizeEmail($email), $role)) {

                        $conn->rollback();
                        $error = "Verifikasi email tidak valid atau sudah digunakan. Silakan kirim ulang kode OTP.";

                    } elseif (!$stmt->execute()) {

                        $conn->rollback();
                        $error = "Registrasi gagal.";

                    } elseif ($stmt->affected_rows === 0) {

                        $conn->rollback();
                        $admin_penuh = true;
                        $error = "Registrasi admin ditutup karena kuota admin sudah penuh (maksimal " . MAX_ADMIN . ").";

                    } else {

                        $conn->commit();
                        otpClearProof(OTP_PURPOSE_REGISTER);
                        $success = "Registrasi admin berhasil. Silakan login.";
                        $admin_penuh = ($jumlah_admin + 1) >= MAX_ADMIN;

                    }

                } catch (Throwable $e) {

                    $conn->rollback();
                    error_log("[MUSAComplaint] Registrasi admin gagal: " . $e->getMessage());
                    $error = "Registrasi gagal.";

                }
            }
        }
    }
}

// Status OTP untuk tampilan (bukti verifikasi divalidasi ke database)
$proofAktif = otpActiveProof($conn, OTP_PURPOSE_REGISTER);
$emailTerverifikasiAwal = $proofAktif["email"] ?? "";
$roleTerverifikasiAwal = $proofAktif["role"] ?? "";

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registrasi - MUSA Complaint</title>

    <link rel="stylesheet" href="../asests/css/login.css">
    <link rel="stylesheet" href="../asests/css/register.css">
</head>

<body class="auth-page-register">

<main class="auth-container">

    <?php
    $authAssets = "../asests";
    include __DIR__ . "/auth_side.php";
    ?>

    <div class="auth-main">

        <a href="../index.php" class="back-home">
            <svg class="ico" aria-hidden="true"><use href="#i-arrow-left"/></svg>
            <span>Kembali ke beranda</span>
        </a>

        <section class="auth-card">

            <div class="card-brand">
                <img src="../asests/foto/baru.png" alt="Logo MUSAComplaint">
                <div>
                    <strong>MUSA<span>Complaint</span></strong>
                    <small>Sistem Pengaduan Sekolah</small>
                </div>
            </div>

            <nav class="auth-tabs" aria-label="Masuk atau daftar">
                <a class="auth-tab" href="login.php">Masuk</a>
                <a class="auth-tab is-active" aria-current="page" href="register.php">Daftar</a>
            </nav>

            <div class="auth-heading">
                <h1>Buat Akun Baru</h1>
                <p>Daftar untuk mulai menyampaikan dan memantau pengaduan sekolah.</p>
            </div>

            <?php if (!empty($error)): ?>

                <div class="error-message" role="alert">
                    <span>!</span>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>

            <?php endif; ?>


            <?php if (!empty($success)): ?>

                <div class="error-message is-success" role="status">
                    <span>✓</span>
                    <div><?= htmlspecialchars($success) ?></div>
                </div>

            <?php endif; ?>


            <?php if ($admin_penuh && empty($success)): ?>

                <div class="admin-warning" role="alert">
                    <span>⚠️</span>
                    <div>
                        <strong>Kuota admin sudah penuh</strong>
                        Akun admin sudah mencapai batas maksimal
                        (<?= MAX_ADMIN ?> akun).
                    </div>
                </div>

            <?php endif; ?>

            <form method="POST" id="registerForm">

                <div class="form-group">

                    <label>Pilih Role</label>

                    <div class="role-container">

                        <label class="role-option" id="roleSiswa">

                            <input
                                type="radio"
                                name="role"
                                value="siswa"
                                onchange="ubahRole('siswa')"
                            >

                            <svg class="ico" aria-hidden="true"><use href="#i-user"/></svg>
                            <span class="role-title">Siswa</span>

                        </label>

                        <label
                            class="role-option<?= $admin_penuh ? ' disabled' : '' ?>"
                            id="roleAdmin"
                        >

                            <input
                                type="radio"
                                name="role"
                                value="admin"
                                onchange="ubahRole('admin')"
                            >

                            <svg class="ico" aria-hidden="true"><use href="#i-shield"/></svg>
                            <span class="role-title">Admin</span>

                        </label>

                    </div>

                </div>


                <div class="form-group">

                    <label for="nama">Nama Lengkap</label>

                    <div class="input-wrapper">
                            <svg class="ico input-icon" aria-hidden="true"><use href="#i-user"/></svg>
                            <input
                            type="text"
                            id="nama"
                            name="nama"
                            placeholder="Masukkan nama lengkap"
                            autocomplete="name"
                            required
                        >
                        </div>

                </div>


                <div id="dataSiswa" class="field-row">

                    <div class="form-group">

                        <label for="nis">NIS</label>

                        <div class="input-wrapper">
                            <svg class="ico input-icon" aria-hidden="true"><use href="#i-id"/></svg>
                            <input
                                type="text"
                                id="nis"
                                name="nis"
                                placeholder="Masukkan NIS"
                            >
                        </div>

                    </div>


                    <div class="form-group">

                        <label for="kelas">Kelas</label>

                        <div class="input-wrapper">
                            <svg class="ico input-icon" aria-hidden="true"><use href="#i-school"/></svg>
                            <input
                                type="text"
                                id="kelas"
                                name="kelas"
                                placeholder="Contoh: XI RPL 1"
                            >
                        </div>

                    </div>

                </div>


                <div class="form-group">

                    <label for="email">Email</label>

                    <div class="input-wrapper">
                            <svg class="ico input-icon" aria-hidden="true"><use href="#i-mail"/></svg>
                            <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Masukkan email"
                            value="<?= htmlspecialchars($emailTerverifikasiAwal) ?>"
                            autocomplete="email"
                            required
                        >
                        </div>

                </div>


                <div class="form-group otp-group">

                    <button type="button" id="otpSendBtn" class="otp-btn otp-send">
                        Kirim Kode OTP
                    </button>

                    <label for="otpInput">Kode OTP Register</label>

                    <div class="input-wrapper">
                            <svg class="ico input-icon" aria-hidden="true"><use href="#i-shield"/></svg>
                            <input
                            type="text"
                            id="otpInput"
                            class="otp-input"
                            inputmode="numeric"
                            maxlength="6"
                            autocomplete="one-time-code"
                            placeholder="Masukkan 6 digit kode OTP"
                        >
                        </div>

                    <div class="otp-status" id="otpStatus" role="status" aria-live="polite"></div>

                </div>


                <div class="form-group">

                    <label for="username">Username</label>

                    <div class="input-wrapper">
                            <svg class="ico input-icon" aria-hidden="true"><use href="#i-user"/></svg>
                            <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Masukkan username"
                            autocomplete="username"
                            required
                        >
                        </div>

                </div>


                <div class="form-group">

                    <label for="password">Password</label>

                    <div class="input-wrapper password-wrapper">
                            <svg class="ico input-icon" aria-hidden="true"><use href="#i-lock"/></svg>
                            <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Minimal 6 karakter"
                            autocomplete="new-password"
                            required
                        >
                        <button type="button" class="toggle-password" data-target="password" aria-label="Tampilkan password">
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
                            name="konfirmasi_password"
                            placeholder="Masukkan ulang password"
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


                <button type="submit" id="registerBtn" class="login-button" disabled>
                    <span>Daftar</span>
                    <svg class="ico btn-arrow" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                </button>

            </form>


            <div class="auth-footer">
                <p>
                    Sudah punya akun?
                    <a href="login.php">Masuk</a>
                </p>
            </div>

        </section>

        <p class="page-copy">© <?= date("Y"); ?> MUSAComplaint. Seluruh hak dilindungi.</p>

    </div>

</main>


<div class="limit-overlay" id="limitOverlay">
    <div class="limit-box" role="dialog" aria-modal="true">

        <div class="limit-icon">!</div>

        <h3>Kuota admin penuh</h3>

        <p>
            Akun admin sudah mencapai batas maksimal
            (<?= MAX_ADMIN ?> akun). Anda tidak dapat mendaftar
            sebagai admin. Silakan daftar sebagai siswa.
        </p>

        <button type="button" id="limitClose">Mengerti</button>

    </div>
</div>


<script>

const adminPenuh = <?= $admin_penuh ? 'true' : 'false' ?>;

if (adminPenuh) {

    const overlay = document.getElementById("limitOverlay");

    function tutupLimit() {
        overlay.classList.remove("show");
    }

    // Klik pilihan Admin -> munculkan popup, jangan pilih role
    document.getElementById("roleAdmin").addEventListener("click", function (e) {
        e.preventDefault();
        overlay.classList.add("show");
    });

    document.getElementById("limitClose").addEventListener("click", tutupLimit);

    overlay.addEventListener("click", function (e) {
        if (e.target === overlay) tutupLimit();
    });

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") tutupLimit();
    });
}

function ubahRole(role) {

    const siswa = document.getElementById("roleSiswa");
    const admin = document.getElementById("roleAdmin");
    const dataSiswa = document.getElementById("dataSiswa");

    siswa.classList.remove("active");
    admin.classList.remove("active");

    if (role === "siswa") {

        siswa.classList.add("active");

        dataSiswa.classList.remove("hidden");

    } else {

        admin.classList.add("active");

        dataSiswa.classList.add("hidden");

    }
}

</script>

<script>
window.MUSA_OTP = <?= json_encode([
    "csrf"      => otpCsrfToken(),
    "sendUrl"   => "otp_register/send_otp.php",
    "verifyUrl" => "otp_register/verify_otp.php",
    "verified"  => $emailTerverifikasiAwal !== "",
    "email"     => $emailTerverifikasiAwal,
    "role"      => $roleTerverifikasiAwal,
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="../asests/js/login.js"></script>
<script src="../asests/js/otp.js"></script>
<script src="../asests/js/register_otp.js"></script>

</body>

</html>