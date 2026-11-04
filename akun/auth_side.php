<?php
/**
 * Panel kiri bersama untuk halaman Login, Registrasi, dan Lupa Password.
 * Hanya tampilan (tidak ada logika backend).
 *
 * Variabel yang dibaca dari halaman pemanggil:
 *   $authAssets - path menuju folder asests (default "../asests")
 */
$authAssets = $authAssets ?? "../asests";
?>
<svg class="svg-sprite" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <symbol id="i-mail" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></symbol>
    <symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></symbol>
    <symbol id="i-lock" viewBox="0 0 24 24"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></symbol>
    <symbol id="i-id" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M6 17c.5-1.8 1.7-3 3-3s2.5 1.2 3 3M15 9h3M15 13h3"/></symbol>
    <symbol id="i-school" viewBox="0 0 24 24"><path d="M3 21h18M5 21V9l7-5 7 5v12"/><path d="M9 21v-6h6v6"/></symbol>
    <symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 3 4 6v6c0 5 3.4 8 8 9 4.6-1 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></symbol>
    <symbol id="i-hash" viewBox="0 0 24 24"><path d="M5 9h14M5 15h14M10 4 8 20M16 4l-2 16"/></symbol>
    <symbol id="i-arrow-left" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></symbol>
    <symbol id="i-arrow-right" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></symbol>
    <symbol id="i-file" viewBox="0 0 24 24"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/><path d="M14 3v5h5M9 13h6M9 17h4"/></symbol>
    <symbol id="i-chart" viewBox="0 0 24 24"><path d="M4 20V11M10 20V4M16 20v-6M2 20h20"/></symbol>
    <symbol id="i-send" viewBox="0 0 24 24"><path d="m22 2-7 20-4-9-9-4 20-7Z"/><path d="M22 2 11 13"/></symbol>
</svg>

<aside class="auth-side">

    <span class="side-dot" aria-hidden="true"></span>

    <div class="side-brand">
        <img src="<?= htmlspecialchars($authAssets); ?>/foto/logo.png" alt="Logo MUSAComplaint">
        <span class="side-brand-name">MUSA<span>Complaint</span></span>
    </div>

    <div class="side-body">

        <span class="side-badge">Sistem Pengaduan Sekolah</span>

        <h2>
            Sampaikan Pengaduan
            <span>Lebih Mudah &amp; Terarah</span>
        </h2>

        <p>
            Satu layanan untuk menyampaikan aspirasi, memantau laporan,
            dan menerima tanggapan dari pihak sekolah.
        </p>

        <div class="side-cards">

            <div class="side-card">
                <span class="side-card-icon"><svg class="ico"><use href="#i-file"/></svg></span>
                <div>
                    <h3>Pengaduan Mudah</h3>
                    <p>Tulis dan kirim pengaduan sekolah dengan formulir yang sederhana.</p>
                </div>
            </div>

            <div class="side-card">
                <span class="side-card-icon"><svg class="ico"><use href="#i-chart"/></svg></span>
                <div>
                    <h3>Pantau Laporan</h3>
                    <p>Lihat status dan riwayat pengaduanmu kapan saja di satu tempat.</p>
                </div>
            </div>

            <div class="side-card">
                <span class="side-card-icon"><svg class="ico"><use href="#i-send"/></svg></span>
                <div>
                    <h3>Respon Terarah</h3>
                    <p>Setiap pengaduan ditinjau dan ditanggapi langsung oleh admin sekolah.</p>
                </div>
            </div>

        </div>

    </div>

    <div class="side-copy">
        © <?= date("Y"); ?> MUSAComplaint. Seluruh hak dilindungi.
    </div>

</aside>