/* =========================================
   MUSA COMPLAINT - LUPA PASSWORD
   Alur: email -> verifikasi OTP -> password baru -> login
   Membutuhkan: otp.js dan window.MUSA_LUPA (diisi oleh lupa_password.php)
   Semua proses (kirim/verifikasi OTP, ganti password) dilakukan di backend.
========================================= */

document.addEventListener("DOMContentLoaded", function () {

    const cfg = window.MUSA_LUPA;

    if (!cfg || !window.MusaOtp) {
        return;
    }

    const stepEmail = document.getElementById("stepEmail");
    const stepOtp = document.getElementById("stepOtp");
    const stepPassword = document.getElementById("stepPassword");

    const judul = document.getElementById("judulLangkah");
    const deskripsi = document.getElementById("deskripsiLangkah");

    const alertBox = document.getElementById("alertBox");
    const alertIcon = document.getElementById("alertIcon");
    const alertText = document.getElementById("alertText");

    const emailInput = document.getElementById("email");
    const otpInput = document.getElementById("otp");
    const emailTampil = document.getElementById("emailTampil");

    const btnKirimEmail = document.getElementById("btnKirimEmail");
    const btnVerifikasi = document.getElementById("btnVerifikasi");
    const btnSimpan = document.getElementById("btnSimpan");
    const btnKirimUlang = document.getElementById("btnKirimUlang");
    const btnGantiEmail = document.getElementById("btnGantiEmail");

    const passwordBaru = document.getElementById("passwordBaru");
    const konfirmasi = document.getElementById("konfirmasiPassword");

    let email = "";
    // Jenis akun tidak lagi dipilih pengguna. Dikirim kosong agar server
    // menentukannya sendiri dari email (lihat config/otp_endpoint.php).
    let role = "";
    let busy = false;

    const countdown = MusaOtp.createCountdown(btnKirimUlang, {
        initial: "Kirim Ulang OTP",
        waiting: "Kirim Ulang OTP ({s} dtk)",
        ready: "Kirim Ulang OTP"
    });

    // ---------- Helper tampilan ----------

    function showAlert(text, type) {

        alertText.textContent = text;
        alertBox.classList.toggle("is-success", type === "success");
        alertIcon.textContent = type === "success" ? "✓" : "!";
        alertBox.hidden = false;
        alertBox.style.display = "";
    }

    // Atribut hidden saja tidak cukup: .error-message { display: flex }
    // pada login.css mengalahkannya, sehingga kotak kosong tetap terlihat.
    function hideAlert() {
        alertBox.hidden = true;
        alertBox.style.display = "none";
    }

    function showStep(number) {

        stepEmail.hidden = number !== 1;
        stepOtp.hidden = number !== 2;
        stepPassword.hidden = number !== 3;

        const teks = cfg.headings[number];

        if (teks) {
            judul.textContent = teks[0];
            deskripsi.textContent = teks[1];
        }
    }

    function setBusy(button, on, text) {

        const label = button.querySelector(".btn-label");

        busy = on;
        button.disabled = on;

        if (!label) {
            return;
        }

        if (on) {
            button.dataset.label = label.textContent;
            label.textContent = text;
        } else if (button.dataset.label) {
            label.textContent = button.dataset.label;
        }
    }

    // ---------- Langkah 1: kirim OTP ----------

    async function kirimOtp(dariTombolUlang) {

        const res = await MusaOtp.post(cfg.sendUrl, {
            csrf_token: cfg.csrf,
            purpose: "reset_password",
            role: role,
            email: email
        });

        if (res.success) {

            otpInput.value = "";
            showStep(2);
            countdown.start(res.cooldown || 60);
            showAlert(res.message, "success");
            otpInput.focus();

            return;
        }

        // Masih dalam masa jeda 60 detik: kode sebelumnya masih bisa dipakai
        if (res.code === "cooldown" && res.retry_after) {

            showStep(2);
            countdown.start(res.retry_after);
            showAlert(res.message, "error");
            otpInput.focus();

            return;
        }

        if (dariTombolUlang) {

            // Batas per jam -> tombol menunggu; gagal kirim email -> boleh coba lagi
            if (res.retry_after) {
                countdown.start(res.retry_after);
            } else {
                countdown.reset();
            }
        }

        showAlert(res.message || "Gagal mengirim kode OTP.", "error");
    }

    stepEmail.addEventListener("submit", async function (event) {

        event.preventDefault();

        if (busy) {
            return;
        }

        hideAlert();

        if (!emailInput.value.trim() || !emailInput.checkValidity()) {
            showAlert("Masukkan alamat email yang valid.", "error");
            emailInput.focus();
            return;
        }

        email = emailInput.value.trim();
        emailTampil.textContent = email;

        setBusy(btnKirimEmail, true, "Mengirim...");
        await kirimOtp(false);
        setBusy(btnKirimEmail, false);
    });

    // ---------- Langkah 2: verifikasi OTP ----------

    async function verifikasiOtp() {

        if (busy) {
            return;
        }

        hideAlert();

        if (otpInput.value.length !== 6) {
            showAlert("Kode OTP harus terdiri dari 6 digit angka.", "error");
            otpInput.focus();
            return;
        }

        setBusy(btnVerifikasi, true, "Memverifikasi...");

        const res = await MusaOtp.post(cfg.verifyUrl, {
            csrf_token: cfg.csrf,
            purpose: "reset_password",
            email: email,
            otp: otpInput.value
        });

        setBusy(btnVerifikasi, false);

        if (res.success) {

            countdown.stop();
            showStep(3);
            showAlert(res.message, "success");
            passwordBaru.focus();

            return;
        }

        otpInput.value = "";
        otpInput.focus();
        showAlert(res.message || "Kode OTP tidak valid.", "error");
    }

    stepOtp.addEventListener("submit", function (event) {
        event.preventDefault();
        verifikasiOtp();
    });

    otpInput.addEventListener("input", function () {

        MusaOtp.onlyDigits(otpInput);

        if (otpInput.value.length === 6) {
            verifikasiOtp();
        }
    });

    btnKirimUlang.addEventListener("click", async function () {

        if (busy) {
            return;
        }

        hideAlert();

        busy = true;
        btnKirimUlang.disabled = true;
        btnKirimUlang.textContent = "Mengirim...";

        await kirimOtp(true);

        busy = false;
    });

    btnGantiEmail.addEventListener("click", function () {

        countdown.stop();
        hideAlert();
        otpInput.value = "";
        showStep(1);
        emailInput.focus();
    });

    // ---------- Langkah 3: password baru ----------

    stepPassword.addEventListener("submit", async function (event) {

        event.preventDefault();

        if (busy) {
            return;
        }

        hideAlert();

        if (!passwordBaru.value || !konfirmasi.value) {
            showAlert("Password baru dan konfirmasi wajib diisi.", "error");
            return;
        }

        if (passwordBaru.value.length < 6) {
            showAlert("Password minimal 6 karakter.", "error");
            passwordBaru.focus();
            return;
        }

        if (passwordBaru.value !== konfirmasi.value) {
            showAlert("Konfirmasi password tidak sama.", "error");
            konfirmasi.focus();
            return;
        }

        setBusy(btnSimpan, true, "Menyimpan...");

        const res = await MusaOtp.post(cfg.resetUrl, {
            csrf_token: cfg.csrf,
            password: passwordBaru.value,
            konfirmasi_password: konfirmasi.value
        });

        if (res.success) {

            passwordBaru.disabled = true;
            konfirmasi.disabled = true;

            showAlert(res.message + " Mengarahkan ke halaman login...", "success");

            setTimeout(function () {
                window.location.href = res.redirect || "../login.php?reset=success";
            }, 2000);

            return;
        }

        setBusy(btnSimpan, false);

        // Bukti OTP hilang / kedaluwarsa -> ulangi dari awal
        if (res.code === "no_proof") {

            passwordBaru.value = "";
            konfirmasi.value = "";
            otpInput.value = "";

            showStep(1);
            showAlert(res.message, "error");

            return;
        }

        showAlert(res.message || "Gagal memperbarui password.", "error");
    });

    // ---------- Tampil/sembunyikan password (gaya sama dengan login) ----------

    document.querySelectorAll(".toggle-password[data-target]").forEach(function (toggle) {

        const target = document.getElementById(toggle.dataset.target);

        if (!target) {
            return;
        }

        toggle.addEventListener("click", function () {

            const tampil = target.type === "password";

            target.type = tampil ? "text" : "password";
            toggle.classList.toggle("show", tampil);
            toggle.setAttribute(
                "aria-label",
                tampil ? "Sembunyikan password" : "Tampilkan password"
            );
        });
    });

    // ---------- Langkah awal ----------

    hideAlert();
    showStep(cfg.step || 1);

});