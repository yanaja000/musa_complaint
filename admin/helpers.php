<?php

/* =========================================================
   helpers.php  -  fungsi bantu untuk semua halaman admin
========================================================= */

/* Kelas badge status (warna seragam dengan sisi siswa) */

if (!function_exists("kelasBadgeStatus")) {

    function kelasBadgeStatus($nama_status)
    {
        switch (strtolower(trim($nama_status))) {

            case "menunggu":
                return "adm-badge adm-badge-menunggu";

            case "diproses":
            case "ditanggapi":
                return "adm-badge adm-badge-diproses";

            case "selesai":
                return "adm-badge adm-badge-selesai";

            case "ditolak":
                return "adm-badge adm-badge-ditolak";

            default:
                return "adm-badge adm-badge-lain";
        }
    }
}

/* 13 Oktober 2026  (atau "Selasa, 13 Oktober 2026" jika $denganHari = true) */

if (!function_exists("tanggalIndonesia")) {

    function tanggalIndonesia($tanggal = null, $denganHari = false)
    {
        $waktu = $tanggal ? strtotime($tanggal) : time();

        if (!$waktu) {
            return "-";
        }

        $bulan = [
            1 => "Januari", "Februari", "Maret", "April", "Mei", "Juni",
            "Juli", "Agustus", "September", "Oktober", "November", "Desember",
        ];

        $hari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];

        $teks = date("j", $waktu) . " " . $bulan[(int) date("n", $waktu)] . " " . date("Y", $waktu);

        return $denganHari ? $hari[(int) date("w", $waktu)] . ", " . $teks : $teks;
    }
}

/* Hanya URL http(s) yang dianggap foto valid */

if (!function_exists("fotoValid")) {

    function fotoValid($url)
    {
        $url = trim((string) $url);

        return preg_match('#^https?://#i', $url) ? $url : "";
    }
}

/* Inisial untuk avatar tanpa foto: "Brian Pc" -> "BP" */

if (!function_exists("inisialNama")) {

    function inisialNama($nama)
    {
        $kata = preg_split('/\s+/', trim((string) $nama));
        $hasil = "";

        foreach (array_slice($kata, 0, 2) as $k) {
            $hasil .= mb_strtoupper(mb_substr($k, 0, 1, "UTF-8"), "UTF-8");
        }

        return $hasil !== "" ? $hasil : "?";
    }
}

/* Cetak avatar bulat (foto, atau inisial jika foto kosong) */

if (!function_exists("avatarHtml")) {

    function avatarHtml($nama, $foto = "", $kelas = "adm-av")
    {
        $foto = fotoValid($foto);

        /* inisial selalu dicetak; foto menutupinya, dan jika foto gagal
           dimuat (onerror) foto dibuang sehingga inisial tampil */

        $html = '<span class="' . $kelas . '"><b>' . htmlspecialchars(inisialNama($nama)) . '</b>';

        if ($foto !== "") {
            $html .= '<img src="' . htmlspecialchars($foto) . '" alt=""'
                . ' referrerpolicy="no-referrer" loading="lazy" onerror="this.remove()">';
        }

        return $html . '</span>';
    }
}