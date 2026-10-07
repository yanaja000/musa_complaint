<?php

require_once "../config/session.php";
requireRole("admin");

?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
</head>
<body>
    <h1>Dashboard Admin</h1>
    <p>Selamat datang, <?= htmlspecialchars($_SESSION["nama"] ?? "Admin"); ?>.</p>
    <p><a href="../akun/logout.php">Logout</a></p>
</body>
</html>
