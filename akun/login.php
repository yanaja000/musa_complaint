<?php

require_once "../config/database.php";
require_once "../config/session.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = "";
$notice = "";

// Pesan setelah berhasil reset password (dari lupa_password.php)
if (($_GET["reset"] ?? "") === "success") {
    $notice = "Password berhasil diperbarui. Silakan login dengan password baru Anda.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $login = trim($_POST["login"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($login) || empty($password)) {
        $error = "Username/email dan password wajib diisi.";
    } else {
        $loginSuccess = false;

        $stmt = $conn->prepare("
            SELECT id_siswa, nama, username, email, password
            FROM siswa
            WHERE username = ? OR email = ?
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param("ss", $login, $login);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (is_array($user) && password_verify($password, $user["password"])) {
                    $_SESSION["user_id"] = $user["id_siswa"];
                    $_SESSION["nama"] = $user["nama"];
                    $_SESSION["username"] = $user["username"];
                    $_SESSION["role"] = "siswa";
                    $loginSuccess = true;
                }
            }

            $stmt->close();
        }

        if (!$loginSuccess) {
            $stmt = $conn->prepare("
                SELECT id_admin, nama, username, email, password
                FROM admin
                WHERE username = ? OR email = ?
                LIMIT 1
            ");

            if ($stmt) {
                $stmt->bind_param("ss", $login, $login);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows === 1) {
                    $user = $result->fetch_assoc();

                    if (is_array($user) && password_verify($password, $user["password"])) {
                        $_SESSION["user_id"] = $user["id_admin"];
                        $_SESSION["nama"] = $user["nama"];
                        $_SESSION["username"] = $user["username"];
                        $_SESSION["role"] = "admin";
                        $loginSuccess = true;
                    }
                }

                $stmt->close();
            }
        }

        if ($loginSuccess) {
            if ($_SESSION["role"] === "admin") {
                header("Location: ../admin/admin_dashboard.php");
            } else {
                header("Location: ../siswa/siswa_dashboard.php");
            }
            exit;
        }

        $error = "Username/email atau password salah.";
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - MUSAcomplaints</title>

    <link rel="stylesheet" href="../asests/css/login.css">
</head>

<body class="auth-page-login">

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
                <img src="../asests/foto/" alt="Logo MUSAComplaint">
                <div>
                    <strong>MUSA<span>Complaint</span></strong>
                    <small>Sistem Pengaduan Sekolah</small>
                </div>
            </div>

                <nav class="auth-tabs" aria-label="Masuk atau daftar">
                <a class="auth-tab is-active" aria-current="page" href="login.php">Masuk</a>
                <a class="auth-tab" href="register.php">Daftar</a>
            </nav>

                <div class="auth-heading">
                    <h1>Selamat Datang Kembali</h1>
                    <p>Masuk ke akun MUSAComplaint untuk menyampaikan dan memantau pengaduan sekolah.</p>
                </div>

                <?php if ($notice): ?>
                    <div class="error-message is-success" role="status">
                        <span>✓</span>
                        <div><?= htmlspecialchars($notice); ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="error-message" role="alert">
                        <span>!</span>
                        <div><?= htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <div class="form-group">

                        <label for="login">Username atau Email</label>

                        <div class="input-wrapper">
                            <svg class="ico input-icon" aria-hidden="true"><use href="#i-user"/></svg>
                            <input
                                type="text"
                                id="login"
                                name="login"
                                placeholder="Masukkan username atau email"
                                value="<?= htmlspecialchars($_POST["login"] ?? ""); ?>"
                                autocomplete="username"
                                required
                            >
                        </div>

                    </div>

                    <div class="form-group">

                        <div class="label-row">
                            <label for="password">Password</label>
                            <a href="otp_lupa_password/lupa_password.php">Lupa Password?</a>
                        </div>

                        <div class="input-wrapper password-wrapper">
                            <svg class="ico input-icon" aria-hidden="true"><use href="#i-lock"/></svg>
                            <input
                                type="password"
                                name="password"
                                id="password"
                                placeholder="Masukkan password"
                                autocomplete="current-password"
                                required
                            >
                            <button type="button" class="toggle-password" id="togglePassword" data-target="password" aria-label="Tampilkan password">
                                <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                    <path class="eye-slash" d="M4 4l16 16"/>
                                </svg>
                            </button>
                        </div>

                    </div>

                    <button type="submit" class="login-button">
                        <span>Masuk</span>
                        <svg class="ico btn-arrow" aria-hidden="true"><use href="#i-arrow-right"/></svg>
                    </button>

                </form>

                <div class="auth-footer">
                    <p>
                        Belum memiliki akun?
                        <a href="register.php">Daftar sekarang</a>
                    </p>
                </div>

            </section>

            <p class="page-copy">© <?= date("Y"); ?> MUSAComplaint. Seluruh hak dilindungi.</p>

        </div>

    </main>

    <script src="../asests/js/login.js"></script>

</body>
</html>