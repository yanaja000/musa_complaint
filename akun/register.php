<?php

require_once "../config/database.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $role = $_POST["role"] ?? "";
    $nama = trim($_POST["nama"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $konfirmasi_password = $_POST["konfirmasi_password"] ?? "";

    $nis = trim($_POST["nis"] ?? "");
    $kelas = trim($_POST["kelas"] ?? "");

    // Validasi dasar
    if (empty($role) || empty($nama) || empty($email) ||
        empty($username) || empty($password) ||
        empty($konfirmasi_password)) {

        $error = "Semua field wajib diisi.";

    } elseif (!in_array($role, ["siswa", "admin"])) {

        $error = "Role tidak valid.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Format email tidak valid.";

    } elseif ($password !== $konfirmasi_password) {

        $error = "Konfirmasi password tidak sama.";

    } elseif (strlen($password) < 6) {

        $error = "Password minimal 6 karakter.";

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

                    if ($stmt->execute()) {
                        $success = "Registrasi siswa berhasil. Silakan login.";
                    } else {
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

                $stmt = $conn->prepare(
                    "INSERT INTO admin
                    (nama, email, username, password, role)
                    VALUES (?, ?, ?, ?, 'admin')"
                );

                $stmt->bind_param(
                    "ssss",
                    $nama,
                    $email,
                    $username,
                    $hashed_password
                );

                if ($stmt->execute()) {
                    $success = "Registrasi admin berhasil. Silakan login.";
                } else {
                    $error = "Registrasi gagal.";
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registrasi - MUSA Complaint</title>
    <link rel="stylesheet" href="../asests/css/register.css">

    

</head>

<body>

<div class="container">

    <div class="card">

        <div class="logo">
            <h1>MUSA Complaint</h1>
            <p>Sistem Pengaduan Sekolah</p>
        </div>

        <?php if (!empty($error)): ?>

            <div class="message">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <?php if (!empty($success)): ?>

            <div class="message">
                <?= htmlspecialchars($success) ?>
            </div>

        <?php endif; ?>


        <form method="POST">

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

                        <div class="role-title">
                            Siswa
                        </div>


                    </label>


                    <label class="role-option" id="roleAdmin">

                        <input
                            type="radio"
                            name="role"
                            value="admin"
                            onchange="ubahRole('admin')"
                        >

                        <div class="role-title">
                            Admin
                        </div>

                    </label>

                </div>

            </div>


            <div class="form-group">

                <label>Nama Lengkap</label>

                <input
                    type="text"
                    name="nama"
                    placeholder="Masukkan nama lengkap"
                    required
                >

            </div>


            <div id="dataSiswa">

                <div class="form-group">

                    <label>NIS</label>

                    <input
                        type="text"
                        name="nis"
                        placeholder="Masukkan NIS"
                    >

                </div>


                <div class="form-group">

                    <label>Kelas</label>

                    <input
                        type="text"
                        name="kelas"
                        placeholder="Contoh: XI RPL 1"
                    >

                </div>

            </div>


            <div class="form-group">

                <label>Email</label>

                <input
                    type="email"
                    name="email"
                    placeholder="Masukkan email"
                    required
                >

            </div>


            <div class="form-group">

                <label>Username</label>

                <input
                    type="text"
                    name="username"
                    placeholder="Masukkan username"
                    required
                >

            </div>


            <div class="form-group">

                <label>Password</label>

                <input
                    type="password"
                    name="password"
                    placeholder="Minimal 6 karakter"
                    required
                    
                >

                

            </div>


            <div class="form-group">

                <label>Konfirmasi Password</label>

                <input
                    type="password"
                    name="konfirmasi_password"
                    placeholder="Masukkan ulang password"
                    required
                >

            </div>


            <button type="submit">
                Daftar
            </button>

        </form>


        <div class="login-link">

            Sudah punya akun?

            <a href="login.php">
                Login
            </a>

        </div>

    </div>

</div>


<script>

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

</body>

</html>