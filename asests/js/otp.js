/* =========================================
   MUSA COMPLAINT - HELPER OTP (dipakai register & lupa password)
   Catatan: JavaScript hanya memanggil backend (send_otp.php / verify_otp.php).
   OTP dibuat, dikirim, dan diverifikasi sepenuhnya di server.
========================================= */

window.MusaOtp = (function () {

    // Kirim data ke backend, selalu mengembalikan objek JSON
    async function post(url, data) {

        try {

            const response = await fetch(url, {
                method: "POST",
                body: new URLSearchParams(data),
                credentials: "same-origin",
                headers: { "Accept": "application/json" }
            });

            const json = await response.json();

            if (json && typeof json === "object") {
                return json;
            }

        } catch (error) {
            // jatuh ke pesan umum di bawah
        }

        return {
            success: false,
            message: "Tidak dapat terhubung ke server. Periksa koneksi lalu coba lagi."
        };
    }

    // Countdown pada sebuah tombol (kirim / kirim ulang OTP)
    function createCountdown(button, labels) {

        let timer = null;

        function stop() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        function start(seconds) {

            stop();

            const end = Date.now() + seconds * 1000;

            button.disabled = true;

            function tick() {

                const left = Math.ceil((end - Date.now()) / 1000);

                if (left <= 0) {
                    stop();
                    button.disabled = false;
                    button.textContent = labels.ready;
                    return;
                }

                button.textContent = labels.waiting.replace("{s}", left);
            }

            tick();
            timer = setInterval(tick, 250);
        }

        function reset() {
            stop();
            button.disabled = false;
            button.textContent = labels.initial;
        }

        return { start: start, stop: stop, reset: reset };
    }

    // Hanya izinkan angka pada input OTP
    function onlyDigits(input) {
        input.value = input.value.replace(/\D/g, "").slice(0, 6);
    }

    return {
        post: post,
        createCountdown: createCountdown,
        onlyDigits: onlyDigits
    };

})();
