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

    <title>Login - MUSA Complaint</title>

    <link rel="stylesheet" href="../asests/css/login.css">

</head>

<body>

    <div class="auth-container">

        <div class="auth-card">

            <div class="auth-header">

                <h1>MUSA Complaint</h1>

                <p>
                    Masuk untuk melanjutkan
                </p>

            </div>

            <?php if ($error): ?>

                <div class="error-message">
                    <?= htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <div class="form-group">

                    <label>
                        Username / Email
                    </label>

                    <input
                        type="text"
                        name="login"
                        placeholder="Masukkan username atau email"
                        required
                    >

                </div>

                <div class="form-group password-group">

                    <label for="password">Password</label>

                    <div class="password-wrapper">

                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="Masukkan password"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            id="togglePassword"
                            aria-label="Tampilkan password"
                        >
                            <span class="eye-icon"></span>
                        </button>

                    </div>

                </div>

            

                <button type="submit">
                    Login
                </button>

            </form>

            <div class="auth-footer">

                <p>
                    Belum punya akun?
                    <a href="register.php">
                        Daftar sekarang
                    </a>
                </p>

            </div>

        </div>

    </div>

    <script src="../asests/js/login.js"></script>

</body>

</html>