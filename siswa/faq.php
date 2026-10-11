<?php
require_once "../config/session.php";

// Hanya siswa yang sudah login (pakai session yang sama dengan halaman lain)
requireRole("siswa");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Bantuan - MUSAComplaint</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 80px; /* Space untuk bottom navigation jika ada */
        }
        .header-title {
            color: #0d6efd;
            font-weight: 700;
        }
        .accordion-button:not(.collapsed) {
            background-color: #e7f1ff;
            color: #0d6efd;
            font-weight: 600;
        }
        .accordion-button:focus {
            box-shadow: none;
            border-color: rgba(13, 110, 253, 0.25);
        }
        .faq-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .contact-box {
            background: #ffffff;
            border-left: 4px solid #0d6efd;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <div class="bg-white border-bottom sticky-top py-3 mb-4">
        <div class="container d-flex align-items-center">
            <a href="profil.php" class="btn btn-light btn-sm rounded-circle me-3">
                <i class="fas fa-arrow-left text-primary"></i>
            </a>
            <h5 class="mb-0 header-title">
                <i class="fas fa-question-circle me-2"></i>Pusat Bantuan & FAQ
            </h5>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="text-center mb-4">
            <h6 class="fw-bold text-dark">Pertanyaan Sering Diajukan</h6>
            <p class="text-muted small">Temukan jawaban cepat atas kendala dan pertanyaan seputar pengaduan sekolah MUSAComplaint.</p>
        </div>

        <!-- FAQ Accordion -->
        <div class="accordion faq-card overflow-hidden mb-4" id="faqAccordion">

            <!-- FAQ Item 1 -->
            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                        <i class="fas fa-clock text-primary me-3"></i>
                        Berapa lama laporan saya akan diproses oleh pihak sekolah?
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary small lh-base">
                        Laporan akan ditinjau oleh tim admin/kesiswaan dalam kurun waktu 1–2 hari kerja setelah dikirim. Kamu bisa memantau perubahan statusnya secara langsung di menu <strong>Pengaduan Saya</strong>.
                    </div>
                </div>
            </div>

            <!-- FAQ Item 2 -->
            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        <i class="fas fa-user-shield text-primary me-3"></i>
                        Apakah identitas saya aman saat mengirimkan pengaduan?
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary small lh-base">
                        Ya, data diri kamu tersimpan dengan aman di sistem. Laporan hanya dapat diakses oleh admin dan petugas sekolah yang berwenang untuk memproses keluhanmu.
                    </div>
                </div>
            </div>

            <!-- FAQ Item 3 -->
            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header" id="headingThree">
                    <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        <i class="fas fa-bell text-primary me-3"></i>
                        Bagaimana saya tahu jika laporan saya sudah ditanggapi atau selesai?
                    </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary small lh-base">
                        Kamu akan melihat indikator <strong>titik merah</strong> pada daftar pengaduan serta perubahan angka pada <strong>ikon lonceng notifikasi</strong> di bagian atas layar setiap kali ada pembaruan status dari admin.
                    </div>
                </div>
            </div>

            <!-- FAQ Item 4 -->
            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header" id="headingFour">
                    <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                        <i class="fas fa-edit text-primary me-3"></i>
                        Apakah saya bisa mengubah atau menghapus laporan yang sudah dikirim?
                    </button>
                </h2>
                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary small lh-base">
                        Laporan yang sudah dikirim tidak dapat diubah atau dihapus agar riwayat penanganan tetap akurat. Jika ada informasi tambahan, kamu bisa menyampaikannya saat petugas menghubungi atau menanggapi laporanmu.
                    </div>
                </div>
            </div>

            <!-- FAQ Item 5 -->
            <div class="accordion-item border-0">
                <h2 class="accordion-header" id="headingFive">
                    <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                        <i class="fas fa-times-circle text-primary me-3"></i>
                        Apa yang harus saya lakukan jika laporan saya ditolak?
                    </button>
                </h2>
                <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary small lh-base">
                        Jika status pengaduan menjadi <strong>Ditolak</strong>, periksa alasan penolakan pada halaman <em>Detail Pengaduan</em>. Biasanya hal ini terjadi jika isi laporan kurang jelas, tidak relevan, atau tidak menyertakan bukti pendukung yang cukup.
                    </div>
                </div>
            </div>

        </div>

        <!-- Box Kontak Bantuan Tambahan -->
        <div class="contact-box p-3 d-flex align-items-center justify-content-between">
            <div>
                <h6 class="mb-1 fw-bold text-dark small">Masih butuh bantuan?</h6>
                <p class="mb-0 text-muted extra-small" style="font-size: 0.8rem;">Hubungi Ruang Bimbingan Konseling (BK) / Kesiswaan.</p>
            </div>
            <a href="https://wa.me/6281234567890" target="_blank" class="btn btn-sm btn-success rounded-pill px-3">
                <i class="fab fa-whatsapp me-1"></i> Kontak
            </a>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle (Termasuk Popper untuk Accordion) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>