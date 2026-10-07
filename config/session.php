<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define("BASE_URL", "/musa-complaint");

function requireLogin()
{
    if (!isset($_SESSION["user_id"], $_SESSION["role"])) {
        header("Location: " . BASE_URL . "/akun/login.php");
        exit;
    }
}

function requireRole($role)
{
    requireLogin();

    if ($_SESSION["role"] !== $role) {
        header("Location: " . BASE_URL . "/akun/login.php");
        exit;
    }
}