
<?php

require_once "../config/database.php";
require_once "../config/session.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = "";

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

<body>

    <main class="auth-container">



        <aside class="auth-side">

            <div class="side-content">

                <div class="side-decoration">
                    <img src="../asests/foto/logo.png" alt="Logo MUSA Complaint">
                </div>

                <span class="side-label">SUARA UNTUK PERUBAHAN</span>

                <h2>
                    Sekolah Lebih Baik,
                    Dimulai dari
                    <span>Suaramu.</span>
                </h2>

                <p>
                    Sampaikan aspirasi dan pengaduanmu dengan mudah.
                    Bersama kita wujudkan lingkungan sekolah
                    yang lebih aman dan nyaman.
                </p>

                <div class="side-features">

                    <div class="feature-item">
                        <span class="feature-check">✓</span>
                        <span>Pengaduan mudah dan praktis</span>
                    </div>

                    <div class="feature-item">
                        <span class="feature-check">✓</span>
                        <span>Pantau perkembangan pengaduan</span>
                    </div>

                    <div class="feature-item">
                        <span class="feature-check">✓</span>
                        <span>Riwayat pengaduan terorganisir</span>
                    </div>

                </div>

            </div>

            <div class="side-footer">
                MUSAcomplaints · Suara untuk perubahan
            </div>

        </aside>

                <section class="auth-card">

            <div class="auth-brand">

                <img src="../asests/foto/logo.png" alt="Logo MUSA Complaint" class="brand-icon">

                <div>
                    <h1>MUSA<span>complaints</span></h1>
                    <p>Sistem Pengaduan Sekolah</p>
                </div>

            </div>

            <div class="auth-content">

                <div class="auth-heading">
                    <span class="welcome-label">SELAMAT DATANG</span>

                    <h2>Masuk ke Akun</h2>

                    <p>
                        Silakan masuk untuk menyampaikan dan
                        memantau pengaduan lingkungan sekolah.
                    </p>
                </div>

                <?php if ($error): ?>
                    <div class="error-message" role="alert">
                        <span>!</span>
                        <?= htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <div class="form-group">

                        <label for="login">Username atau Email</label>

                        <div class="input-wrapper">
                            <span class="input-icon">✉</span>

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

                        <label for="password">Password</label>

                        <div class="input-wrapper password-wrapper">

                            <span class="input-icon">◆</span>

                            <input
                                type="password"
                                name="password"
                                id="password"
                                placeholder="Masukkan password"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                class="toggle-password"
                                id="togglePassword"
                                aria-label="Tampilkan password"
                            >
                                <svg
                                    class="eye-icon"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                    <path class="eye-slash" d="M4 4l16 16"/>
                                </svg>
                            </button>

                        </div>

                    </div>

                    <button type="submit" class="login-button">
                        <span>Masuk Sekarang</span>
                        <span class="button-arrow">→</span>
                    </button>

                </form>

                <div class="auth-footer">
                    <p>
                        Belum memiliki akun?
                        <a href="register.php">Daftar sekarang</a>
                    </p>
                </div>

            </div>

            <div class="auth-bottom">
                <span>© <?= date("Y"); ?> MUSAcomplaints</span>
                <span>Sistem Pengaduan Sekolah</span>
            </div>

        </section>

    </main>

    <script src="../asests/js/login.js"></script>

</body>
</html>
