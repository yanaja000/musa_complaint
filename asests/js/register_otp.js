/* =========================================
   MUSA COMPLAINT - OTP PADA HALAMAN REGISTRASI
   Membutuhkan: otp.js dan window.MUSA_OTP (diisi oleh register.php)
========================================= */

document.addEventListener("DOMContentLoaded", function () {

    const cfg = window.MUSA_OTP;

    if (!cfg || !window.MusaOtp) {
        return;
    }

    const form = document.getElementById("registerForm");
    const emailInput = form.querySelector('input[name="email"]');
    const otpInput = document.getElementById("otpInput");
    const otpStatus = document.getElementById("otpStatus");
    const sendBtn = document.getElementById("otpSendBtn");
    const registerBtn = document.getElementById("registerBtn");

    let verified = false;
    let verifiedEmail = "";
    let verifiedRole = "";
    let sentEmail = "";
    let sentRole = "";
    let busy = false;

    const countdown = MusaOtp.createCountdown(sendBtn, {
        initial: "Kirim Kode OTP",
        waiting: "Kirim Ulang OTP ({s} dtk)",
        ready: "Kirim Ulang OTP"
    });

    function selectedRole() {
        const radio = form.querySelector('input[name="role"]:checked');
        return radio ? radio.value : "";
    }

    function currentEmail() {
        return emailInput.value.trim().toLowerCase();
    }

    function setStatus(text, type) {
        otpStatus.textContent = text || "";
        otpStatus.className = "otp-status" + (type ? " is-" + type : "");
    }

    function setVerified(email, role) {

        verified = true;
        verifiedEmail = email;
        verifiedRole = role;

        countdown.stop();

        otpInput.value = "";
        otpInput.disabled = true;

        sendBtn.disabled = true;
        sendBtn.textContent = "Email Terverifikasi";

        registerBtn.disabled = false;

        setStatus("✓ Email berhasil diverifikasi. Silakan lengkapi pendaftaran.", "ok");
    }

    // Kembali ke keadaan belum terverifikasi
    function resetVerification(message) {

        verified = false;
        verifiedEmail = "";
        verifiedRole = "";
        sentEmail = "";
        sentRole = "";

        otpInput.value = "";
        otpInput.disabled = false;

        registerBtn.disabled = true;

        countdown.reset();

        setStatus(message || "", message ? "error" : "");
    }

    // ---------- Email / role berubah ----------

    emailInput.addEventListener("input", function () {

        const now = currentEmail();

        if (verified && now !== verifiedEmail) {
            resetVerification("Email diubah. Silakan verifikasi ulang email Anda.");
            return;
        }

        if (!verified && sentEmail && now !== sentEmail) {
            resetVerification("");
        }
    });

    form.querySelectorAll('input[name="role"]').forEach(function (radio) {

        radio.addEventListener("change", function () {

            const role = selectedRole();

            if (verified && role !== verifiedRole) {
                resetVerification("Role diubah. Silakan verifikasi ulang email Anda.");
                return;
            }

            if (!verified && sentEmail && role !== sentRole) {
                resetVerification("");
            }
        });
    });

    // ---------- Kirim / kirim ulang OTP ----------

    sendBtn.addEventListener("click", async function () {

        if (busy || verified) {
            return;
        }

        const role = selectedRole();

        if (!role) {
            setStatus("Pilih role (Siswa atau Admin) terlebih dahulu.", "error");
            return;
        }

        if (!emailInput.value.trim() || !emailInput.checkValidity()) {
            setStatus("Masukkan alamat email yang valid terlebih dahulu.", "error");
            emailInput.focus();
            return;
        }

        busy = true;
        sendBtn.disabled = true;
        sendBtn.textContent = "Mengirim...";
        setStatus("Mengirim kode OTP...", "");

        const emailDikirim = emailInput.value.trim();

        const res = await MusaOtp.post(cfg.sendUrl, {
            csrf_token: cfg.csrf,
            purpose: "register",
            role: role,
            email: emailDikirim
        });

        busy = false;

        if (res.success) {

            sentEmail = emailDikirim.toLowerCase();
            sentRole = role;

            otpInput.disabled = false;
            otpInput.value = "";
            otpInput.focus();

            countdown.start(res.cooldown || 60);
            setStatus(res.message, "");

            return;
        }

        if (res.retry_after) {
            countdown.start(res.retry_after);
        } else {
            countdown.reset();
        }

        setStatus(res.message || "Gagal mengirim kode OTP.", "error");
    });

    // ---------- Verifikasi OTP (otomatis saat 6 digit terisi) ----------

    async function verifyOtp() {

        if (busy || verified || otpInput.value.length !== 6) {
            return;
        }

        const emailDicek = emailInput.value.trim();

        if (!emailDicek || !emailInput.checkValidity()) {
            setStatus("Isi alamat email yang valid terlebih dahulu, lalu masukkan kode OTP.", "error");
            return;
        }

        busy = true;
        otpInput.disabled = true;
        setStatus("Memverifikasi kode...", "");

        const res = await MusaOtp.post(cfg.verifyUrl, {
            csrf_token: cfg.csrf,
            purpose: "register",
            email: emailDicek,
            otp: otpInput.value
        });

        busy = false;

        if (res.success) {

            if (emailInput.value.trim().toLowerCase() !== emailDicek.toLowerCase()) {
                resetVerification("Email diubah. Silakan verifikasi ulang email Anda.");
                return;
            }

            setVerified(emailDicek.toLowerCase(), res.role || selectedRole());
            return;
        }

        otpInput.disabled = false;
        otpInput.value = "";
        otpInput.focus();

        setStatus(res.message || "Kode OTP tidak valid.", "error");
    }

    otpInput.addEventListener("input", function () {
        MusaOtp.onlyDigits(otpInput);
        verifyOtp();
    });

    // ---------- Tombol Daftar hanya jalan jika sudah terverifikasi ----------

    form.addEventListener("submit", function (event) {

        const cocok = verified
            && currentEmail() === verifiedEmail
            && selectedRole() === verifiedRole;

        if (!cocok) {
            event.preventDefault();
            setStatus("Verifikasi email dengan kode OTP terlebih dahulu sebelum mendaftar.", "error");
        }
    });

    // ---------- Kondisi awal (mis. setelah gagal submit tapi OTP masih valid) ----------

    if (cfg.verified && cfg.email) {

        emailInput.value = cfg.email;

        const radio = form.querySelector('input[name="role"][value="' + cfg.role + '"]');

        if (radio) {
            radio.checked = true;

            if (typeof ubahRole === "function") {
                ubahRole(cfg.role);
            }
        }

        setVerified(cfg.email.toLowerCase(), cfg.role);
    }

});