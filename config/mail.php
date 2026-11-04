<?php

/**
 * Loader konfigurasi SMTP.
 *
 * Urutan prioritas:
 *   1. Environment variable (MAIL_HOST, MAIL_PORT, MAIL_ENCRYPTION,
 *      MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_EMAIL, MAIL_FROM_NAME, MAIL_VERIFY_SSL)
 *   2. File config/mail.local.php (JANGAN di-commit ke Git)
 *
 * File ini tidak berisi rahasia apapun, aman di-commit.
 */

$local = is_file(__DIR__ . "/mail.local.php")
    ? (require __DIR__ . "/mail.local.php")
    : [];

$ambil = function (string $key, $default) use ($local) {
    $env = getenv("MAIL_" . strtoupper($key));

    if ($env !== false && $env !== "") {
        return $env;
    }

    return $local[$key] ?? $default;
};

$verify = $ambil("verify_ssl", true);

return [
    "host"       => (string) $ambil("host", "smtp.gmail.com"),
    "port"       => (int) $ambil("port", 587),
    "encryption" => strtolower((string) $ambil("encryption", "tls")), // tls | ssl
    "username"   => (string) $ambil("username", ""),
    "password"   => (string) $ambil("password", ""),
    "from_email" => (string) $ambil("from_email", ""),
    "from_name"  => (string) $ambil("from_name", "MUSAComplaint"),
    "verify_ssl" => !in_array($verify, [false, 0, "0", "false"], true),
];
