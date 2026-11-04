<?php

/**
 * KONFIGURASI SMTP (RAHASIA)
 * --------------------------------------------------
 * Isi dengan data email pengirim Anda.
 * File ini TIDAK boleh di-commit ke GitHub
 * (tambahkan "config/mail.local.php" ke .gitignore).
 *
 * Contoh untuk Gmail:
 *   host       : smtp.gmail.com
 *   port       : 587  (encryption: tls)   atau 465 (encryption: ssl)
 *   username   : alamat Gmail pengirim
 *   password   : "App Password" 16 karakter dari Google (BUKAN password login Gmail)
 */

return [
    "host"       => "smtp.gmail.com",
    "port"       => 587,
    "encryption" => "tls",
    "username"   => "emailanda@gmail.com",
    "password"   => "xxxx xxxx xxxx xxxx",
    "from_email" => "emailanda@gmail.com",
    "from_name"  => "MUSAComplaint",

    // Ubah ke false HANYA untuk uji coba lokal jika muncul error
    // "certificate verify failed" di Laragon. Sebaiknya perbaiki openssl.cafile.
    "verify_ssl" => true,
];
