<?php

require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/config/session.php";

/* =========================================
   STATUS LOGIN
========================================= */

$sudah_login = isset($_SESSION["user_id"], $_SESSION["role"]);

$link_dashboard = ($_SESSION["role"] ?? "") === "admin"
    ? "admin/admin_dashboard.php"
    : "siswa/siswa_dashboard.php";

$link_mulai = $sudah_login
    ? ($_SESSION["role"] === "admin" ? $link_dashboard : "siswa/buat_pengaduan.php")
    : "akun/register.php";

/* =========================================
   JUMLAH KATEGORI (dipakai di footer)
========================================= */

$jumlah_kategori = 11;

try {

    $q = $conn->query("SELECT COUNT(*) AS total FROM kategori");

    if ($q && ($baris = $q->fetch_assoc()) && (int) $baris["total"] > 0) {
        $jumlah_kategori = (int) $baris["total"];
    }

} catch (Throwable $e) {
    // pakai nilai bawaan jika query gagal
}

/* Logo aplikasi */

$logo_src = "asests/foto/baru.png";

/* Data tampilan (ikon dan isi sama seperti sebelumnya) */

$fitur = [
    ["fa-pen-to-square",     "Membuat Pengaduan",         "Sampaikan pengaduan, laporan, atau saran lewat formulir sederhana yang bisa diisi kapan saja dan di mana saja."],
    ["fa-camera",            "Upload Foto Bukti",         "Lampirkan foto sebagai bukti pendukung agar pengaduan lebih jelas dan mudah ditindaklanjuti."],
    ["fa-clock-rotate-left", "Riwayat Pengaduan",         "Semua pengaduan yang pernah dikirim tersimpan rapi dan bisa dibuka kembali kapan pun."],
    ["fa-chart-line",        "Memantau Status Pengaduan", "Lihat perkembangan pengaduan mulai dari menunggu, diproses, ditanggapi, hingga selesai."],
    ["fa-comments",          "Tanggapan Admin",           "Terima tanggapan resmi dari admin dan balas langsung di halaman detail pengaduan."],
    ["fa-user-shield",       "Privasi Pengaduan",         "Setiap siswa hanya bisa melihat pengaduannya sendiri, sehingga laporanmu tetap aman."],
];

$kategori = [
    ["fa-school",          "Fasilitas Sekolah",       "Sarana dan prasarana"],
    ["fa-broom",           "Kebersihan",              "Kelas, toilet, kantin"],
    ["fa-shield-halved",   "Keamanan",                "Rasa aman di sekolah"],
    ["fa-chalkboard-user", "Guru dan Staff",          "Sikap dan layanan"],
    ["fa-book-open",       "Pembelajaran",            "Proses belajar mengajar"],
    ["fa-hand",            "Perundungan",             "Bullying dan kekerasan"],
    ["fa-people-group",    "Organisasi dan Kegiatan", "OSIS, ekskul, acara"],
    ["fa-tree",            "Lingkungan Sekolah",      "Halaman dan sekitar"],
    ["fa-bell-concierge",  "Pelayanan Sekolah",       "Administrasi dan layanan"],
    ["fa-lightbulb",       "Saran dan Masukan",       "Ide untuk sekolah"],
    ["fa-ellipsis",        "Lainnya",                 "Di luar kategori lain"],
];

$keunggulan = [
    ["fa-shield-heart", "Ruang Aman untuk Siswa",  "Memberikan kemudahan bagi siswa untuk menyampaikan keluhan dan saran."],
    ["fa-layer-group",  "Pengelolaan Terstruktur", "Setiap pengaduan tersimpan dan dikelola dalam satu sistem digital."],
    ["fa-eye",          "Penanganan Transparan",   "Siswa dapat memantau perkembangan pengaduan yang disampaikan."],
];

$langkah = [
    ["fa-right-to-bracket", "Registrasi atau Login",       "Siswa membuat akun baru atau masuk dengan akun yang sudah ada."],
    ["fa-paper-plane",      "Kirim Pengaduan",             "Siswa membuat dan mengirim pengaduan lengkap dengan kategori dan foto bukti."],
    ["fa-user-gear",        "Admin Memproses",             "Admin memeriksa, memverifikasi, dan memproses pengaduan yang masuk."],
    ["fa-bell",             "Pantau dan Terima Tanggapan", "Siswa memantau status dan membaca tanggapan admin sampai selesai."],
];

/* Menu navbar: hanya lima menu */

$menu = [
    ["#beranda",    "Beranda"],
    ["#tentang",    "Tentang"],
    ["#fitur",      "Fitur"],
    ["#kategori",   "Kategori"],
    ["#cara-kerja", "Cara Kerja"],
];

$tahun = date("Y");

?>
<!DOCTYPE html>
<html lang="id" class="no-js">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MUSA Complaint, platform digital pengaduan sekolah yang praktis, aman, dan terstruktur.">
    <meta name="theme-color" content="#2563eb">

    <title>MUSA Complaint - Suara Kamu, Perubahan Untuk Sekolah Kita</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="asests/css/beranda.css">

</head>

<body>

    <!-- =========================================
         NAVBAR
    ========================================= -->

    <header class="navbar" id="navbar">

        <div class="container nav-inner">

            <a href="#beranda" class="nav-logo" aria-label="MUSA Complaint">



                <span class="nav-logo-icon">
                    <img src="<?= htmlspecialchars($logo_src); ?>" alt="Logo MUSA Complaint">
                </span>


                <span class="nav-logo-text">MUSA <span>Complaint</span></span>

            </a>

            <nav class="nav-links" id="navLinks" aria-label="Menu utama">

                <?php foreach ($menu as $m): ?>
                    <a href="<?= $m[0]; ?>"><?= $m[1]; ?></a>
                <?php endforeach; ?>

                <!-- Tombol akun versi mobile (di dalam menu) -->
                <div class="nav-auth-mobile">
                    <?php if ($sudah_login): ?>
                        <a href="<?= $link_dashboard; ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                        <a href="akun/logout.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                    <?php else: ?>
                        <a href="akun/login.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
                        <a href="akun/register.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-user-plus"></i> Registrasi</a>
                    <?php endif; ?>
                </div>

            </nav>

            <!-- Tombol akun versi desktop (sisi kanan) -->
            <div class="nav-auth">
                <?php if ($sudah_login): ?>
                    <a href="akun/logout.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                    <a href="<?= $link_dashboard; ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                <?php else: ?>
                    <a href="akun/login.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
                    <a href="akun/register.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-user-plus"></i> Registrasi</a>
                <?php endif; ?>
            </div>

            <button type="button"
                    class="nav-toggle"
                    id="navToggle"
                    aria-label="Buka menu"
                    aria-expanded="false"
                    aria-controls="navLinks">
                <span></span><span></span><span></span>
            </button>

        </div>

    </header>


    <main>

        <!-- =========================================
             HERO
        ========================================= -->

        <section class="hero" id="beranda">

            <div class="container hero-grid">

                <div class="hero-text">

                    <span class="hero-chip reveal"><img src="<?= htmlspecialchars($logo_src); ?>" alt="" aria-hidden="true"> Platform pengaduan sekolah</span>

                    <h1 class="reveal" style="--d:.04s">
                        Suara Kamu, <em>Perubahan</em> Untuk Sekolah Kita.
                    </h1>

                    <p class="hero-desc reveal" style="--d:.08s">
                        MUSA Complaint merupakan platform digital yang memudahkan siswa menyampaikan pengaduan, laporan, maupun saran mengenai lingkungan sekolah secara praktis, aman, dan terstruktur.
                    </p>

                    <div class="hero-cta reveal" style="--d:.16s">

                        <a href="<?= $link_mulai; ?>" class="btn btn-primary">
                            Mulai Sekarang <i class="fa-solid fa-arrow-right"></i>
                        </a>

                        <a href="#tentang" class="btn btn-outline">
                            Pelajari Selengkapnya <i class="fa-solid fa-arrow-down"></i>
                        </a>

                    </div>

                    <p class="hero-login reveal" style="--d:.2s">

                        <?php if ($sudah_login): ?>
                            Kamu sudah masuk. <a href="<?= $link_dashboard; ?>">Buka dashboard</a>


                        <?php endif; ?>

                    </p>

                    <div class="hero-trust reveal" style="--d:.24s">
                        <span><b class="ti"><i class="fa-solid fa-lock"></i></b> Aman dan Privat</span>
                        <span><b class="ti"><i class="fa-solid fa-list-check"></i></b> Terstruktur</span>
                        <span><b class="ti"><i class="fa-solid fa-bolt"></i></b> Mudah Digunakan</span>
                    </div>

                </div>


                <!-- Mockup dashboard -->

                <div class="hero-visual reveal" style="--d:.15s" aria-hidden="true">

                    <div class="mock">

                        <div class="mock-top"><i></i><i></i><i></i></div>

                        <div class="mock-body">

                            <div class="mock-side">
                                <span class="on"><i class="fa-solid fa-house"></i></span>
                                <span><i class="fa-solid fa-circle-plus"></i></span>
                                <span><i class="fa-solid fa-clipboard-list"></i></span>
                                <span><i class="fa-solid fa-user"></i></span>
                            </div>

                            <div class="mock-main">

                                <div class="mock-head">
                                    <h4>Dashboard Siswa</h4>
                                    <small>Ringkasan pengaduanmu</small>
                                </div>

                                <div class="mock-row">
                                    <span class="ri"><i class="fa-solid fa-snowflake"></i></span>
                                    <span class="tx"><b>AC kelas XI rusak</b><small>Fasilitas Sekolah</small></span>
                                    <span class="pill cyan">Diproses</span>
                                </div>

                                <div class="mock-row">
                                    <span class="ri"><i class="fa-solid fa-broom"></i></span>
                                    <span class="tx"><b>Toilet perlu dibersihkan</b><small>Kebersihan</small></span>
                                    <span class="pill kuning">Menunggu</span>
                                </div>

                                <div class="mock-row">
                                    <span class="ri"><i class="fa-solid fa-lightbulb"></i></span>
                                    <span class="tx"><b>Usul perpustakaan digital</b><small>Saran dan Masukan</small></span>
                                    <span class="pill hijau">Selesai</span>
                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="fcard">
                        <span class="fic"><i class="fa-solid fa-comment-dots"></i></span>
                        <div>
                            <b>Tanggapan Admin Baru</b>
                            <small>Pengaduan sedang ditindaklanjuti</small>
                        </div>
                    </div>

                </div>

            </div>

        </section>


        <!-- =========================================
             TENTANG
        ========================================= -->

        <section class="section" id="tentang">

            <div class="container about-grid">

                <!-- Kolom kiri: teks + ilustrasi -->

                <div class="about-left">

                    <div class="about-head reveal">
                        <span class="eyebrow">Tentang MUSA Complaint</span>
                        <h2>Apa itu MUSA Complaint?</h2>
                        <p>
                            MUSA Complaint merupakan platform pengaduan digital yang menghubungkan siswa dengan pihak sekolah. Melalui aplikasi ini, siswa dapat menyampaikan keluhan, laporan, maupun saran dengan lebih mudah dan terstruktur.
                        </p>
                    </div>

                    <!-- Ilustrasi laptop -->

                    <div class="about-visual reveal" style="--d:.12s" aria-hidden="true">

                        <svg viewBox="0 0 520 340" xmlns="http://www.w3.org/2000/svg" focusable="false">

                            <!-- bayangan -->
                            <ellipse cx="260" cy="318" rx="210" ry="12" fill="#bfdbfe" opacity=".55"/>

                            <!-- layar -->
                            <rect x="70" y="14" width="380" height="248" rx="16" fill="#1e3a8a"/>
                            <rect x="82" y="26" width="356" height="224" rx="9" fill="#ffffff"/>

                            <!-- bar atas aplikasi -->
                            <rect x="82" y="26" width="356" height="30" rx="9" fill="#2563eb"/>
                            <rect x="82" y="44" width="356" height="12" fill="#2563eb"/>
                            <circle cx="98" cy="41" r="4" fill="#ffffff" opacity=".6"/>
                            <circle cx="112" cy="41" r="4" fill="#ffffff" opacity=".6"/>
                            <circle cx="126" cy="41" r="4" fill="#ffffff" opacity=".6"/>
                            <rect x="330" y="36" width="92" height="10" rx="5" fill="#ffffff" opacity=".28"/>

                            <!-- sidebar -->
                            <rect x="82" y="56" width="52" height="194" fill="#eff6ff"/>
                            <rect x="93" y="70" width="30" height="30" rx="9" fill="#2563eb"/>
                            <rect x="102" y="80" width="12" height="10" rx="2" fill="#ffffff"/>
                            <rect x="93" y="110" width="30" height="30" rx="9" fill="#dbeafe"/>
                            <rect x="93" y="150" width="30" height="30" rx="9" fill="#dbeafe"/>
                            <rect x="93" y="190" width="30" height="30" rx="9" fill="#dbeafe"/>

                            <!-- judul -->
                            <rect x="150" y="70" width="110" height="10" rx="5" fill="#1e3a8a"/>
                            <rect x="150" y="87" width="150" height="7" rx="3.5" fill="#cbd5e1"/>

                            <!-- kartu ringkasan -->
                            <rect x="150" y="106" width="84" height="48" rx="10" fill="#eff6ff" stroke="#dbeafe"/>
                            <rect x="160" y="116" width="26" height="6" rx="3" fill="#93c5fd"/>
                            <rect x="160" y="130" width="40" height="14" rx="4" fill="#2563eb"/>

                            <rect x="244" y="106" width="84" height="48" rx="10" fill="#eff6ff" stroke="#dbeafe"/>
                            <rect x="254" y="116" width="26" height="6" rx="3" fill="#93c5fd"/>
                            <rect x="254" y="130" width="40" height="14" rx="4" fill="#0891b2"/>

                            <rect x="338" y="106" width="84" height="48" rx="10" fill="#eff6ff" stroke="#dbeafe"/>
                            <rect x="348" y="116" width="26" height="6" rx="3" fill="#93c5fd"/>
                            <rect x="348" y="130" width="40" height="14" rx="4" fill="#16a34a"/>

                            <!-- daftar pengaduan -->
                            <rect x="150" y="166" width="272" height="24" rx="8" fill="#ffffff" stroke="#dbeafe"/>
                            <rect x="158" y="171" width="14" height="14" rx="4" fill="#dbeafe"/>
                            <rect x="180" y="174" width="110" height="7" rx="3.5" fill="#94a3b8"/>
                            <rect x="368" y="171" width="46" height="14" rx="7" fill="#cffafe"/>

                            <rect x="150" y="196" width="272" height="24" rx="8" fill="#ffffff" stroke="#dbeafe"/>
                            <rect x="158" y="201" width="14" height="14" rx="4" fill="#dbeafe"/>
                            <rect x="180" y="204" width="90" height="7" rx="3.5" fill="#94a3b8"/>
                            <rect x="368" y="201" width="46" height="14" rx="7" fill="#fef3c7"/>

                            <rect x="150" y="226" width="272" height="18" rx="8" fill="#ffffff" stroke="#dbeafe"/>
                            <rect x="158" y="230" width="10" height="10" rx="3" fill="#dbeafe"/>
                            <rect x="176" y="232" width="76" height="6" rx="3" fill="#94a3b8"/>
                            <rect x="368" y="229" width="46" height="12" rx="6" fill="#dcfce7"/>

                            <!-- badan laptop -->
                            <path d="M40 262 H480 L462 296 Q459 302 452 302 H68 Q61 302 58 296 Z" fill="#93c5fd"/>
                            <path d="M40 262 H480 L476 270 H44 Z" fill="#bfdbfe"/>
                            <rect x="214" y="262" width="92" height="8" rx="4" fill="#60a5fa"/>

                            <!-- balon notifikasi -->
                            <g>
                                <rect x="398" y="-2" width="104" height="44" rx="14" fill="#ffffff" stroke="#dbeafe"/>
                                <rect x="408" y="8" width="24" height="24" rx="8" fill="#dbeafe"/>
                                <path d="M414 15 h12 a2 2 0 0 1 2 2 v6 a2 2 0 0 1 -2 2 h-6 l-4 4 v-4 h-2 a2 2 0 0 1 -2 -2 v-6 a2 2 0 0 1 2 -2z" fill="#2563eb"/>
                                <rect x="440" y="13" width="50" height="6" rx="3" fill="#1e3a8a"/>
                                <rect x="440" y="24" width="36" height="5" rx="2.5" fill="#cbd5e1"/>
                            </g>

                        </svg>

                    </div>

                </div>


                <!-- Kolom kanan: tiga keunggulan -->

                <div class="about-right">

                    <?php foreach ($keunggulan as $i => $k): ?>

                        <div class="about-point-wrap reveal" style="--d:<?= 0.08 + $i * 0.1; ?>s">
                            <article class="about-point">
                                <span class="ap-ic"><i class="fa-solid <?= $k[0]; ?>"></i></span>
                                <div>
                                    <h3><?= $k[1]; ?></h3>
                                    <p><?= $k[2]; ?></p>
                                </div>
                            </article>
                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </section>


        <!-- =========================================
             FITUR
        ========================================= -->

        <section class="section section-alt" id="fitur">

            <div class="container">

                <div class="section-head reveal">
                    <span class="eyebrow">Fitur Unggulan</span>
                    <h2>Semua yang kamu butuhkan untuk bersuara</h2>
                    <p>Fitur sederhana agar menyampaikan pengaduan terasa nyaman dan jelas hasilnya.</p>
                </div>

                <div class="feature-grid">

                    <?php foreach ($fitur as $i => $f): ?>

                        <article class="feature-item reveal" style="--d:<?= ($i % 3) * 0.08; ?>s">
                            <div class="f-ic"><i class="fa-solid <?= $f[0]; ?>"></i></div>
                            <h3><?= $f[1]; ?></h3>
                            <p><?= $f[2]; ?></p>
                        </article>

                    <?php endforeach; ?>

                </div>

            </div>

        </section>


        <!-- =========================================
             KATEGORI
        ========================================= -->

        <section class="section section-soft" id="kategori">

            <div class="container">

                <div class="section-head reveal">
                    <span class="eyebrow">Kategori Pengaduan</span>
                    <h2>Pilih kategori yang paling sesuai</h2>
                    <p>Pengaduan dikelompokkan agar mudah diarahkan ke pihak yang tepat.</p>
                </div>

                <div class="cat-grid">

                    <?php foreach ($kategori as $i => $k): ?>

                        <div class="cat-item reveal" style="--d:<?= ($i % 4) * 0.06; ?>s">
                            <span class="cat-ic"><i class="fa-solid <?= $k[0]; ?>"></i></span>
                            <div>
                                <h4><?= $k[1]; ?></h4>
                                <small><?= $k[2]; ?></small>
                            </div>
                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </section>


        <!-- =========================================
             CARA KERJA
        ========================================= -->

        <section class="section section-alt" id="cara-kerja">

            <div class="container">

                <div class="section-head reveal">
                    <span class="eyebrow">Cara Kerja</span>
                    <h2>Empat langkah menyampaikan pengaduan</h2>
                    <p>Dari pendaftaran sampai mendapat tanggapan, semuanya bisa dilakukan dari ponselmu.</p>
                </div>

                <div class="steps">

                    <?php foreach ($langkah as $i => $l): ?>

                        <div class="step reveal" style="--d:<?= $i * 0.1; ?>s">
                            <div class="step-num"><i class="fa-solid <?= $l[0]; ?>"></i></div>
                            <small>Langkah <?= $i + 1; ?></small>
                            <h4><?= $l[1]; ?></h4>
                            <p><?= $l[2]; ?></p>
                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </section>

    </main>


    <!-- =========================================
         FOOTER
    ========================================= -->

    <footer class="footer">

        <div class="container">

            <div class="footer-grid">

                <div class="footer-about">

                    <a href="#beranda" class="nav-logo" aria-label="MUSA Complaint">

                        <span class="nav-logo-icon">
                            <img src="<?= htmlspecialchars($logo_src); ?>" alt="Logo MUSA Complaint">
                        </span>

                        <span class="nav-logo-text">MUSA <span>Complaint</span></span>

                    </a>

                    <p>
                        Platform digital pengaduan sekolah yang memudahkan siswa menyampaikan pengaduan, laporan, dan saran secara praktis, aman, dan terstruktur.
                    </p>

                </div>

                <div>
                    <h5>Navigasi</h5>
                    <ul class="footer-links">
                        <?php foreach ($menu as $m): ?>
                            <li><a href="<?= $m[0]; ?>"><?= $m[1]; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div>
                    <h5>Akun</h5>
                    <ul class="footer-links">
                        <?php if ($sudah_login): ?>
                            <li><a href="<?= $link_dashboard; ?>">Dashboard</a></li>
                            <li><a href="akun/logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a href="akun/login.php">Login</a></li>
                            <li><a href="akun/register.php">Registrasi</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div>
                    <h5>Informasi Aplikasi</h5>
                    <ul class="footer-links">
                        <li>Aplikasi berbasis web</li>
                        <li>Untuk Siswa dan Admin</li>
                        <li><?= (int) $jumlah_kategori; ?> kategori pengaduan</li>
                        <li>Privasi terjaga</li>
                    </ul>
                </div>

            </div>

            <div class="footer-bottom">
                <span>&copy; <?= $tahun; ?> MUSA Complaint. Seluruh hak cipta dilindungi.</span>
                <span>Suara Kamu, Perubahan Untuk Sekolah Kita.</span>
            </div>

        </div>

    </footer>

    <button type="button" class="to-top" id="toTop" aria-label="Kembali ke atas">
        <i class="fa-solid fa-arrow-up"></i>
    </button>

    <script src="asests/js/beranda.js"></script>

</body>

</html>