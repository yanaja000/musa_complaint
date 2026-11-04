/* =========================================
   MUSA COMPLAINT - TAMPIL / SEMBUNYIKAN PASSWORD
   Dipakai oleh halaman Login dan Registrasi.
   Tombol .toggle-password mengontrol input pada atribut data-target
   (jika kosong, memakai input dengan id "password").
========================================= */

document.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll(".toggle-password").forEach(function (toggle) {

        const passwordInput = document.getElementById(toggle.dataset.target || "password");

        if (!passwordInput) {
            return;
        }

        toggle.addEventListener("click", function () {

            const tampil = passwordInput.type === "password";

            passwordInput.type = tampil ? "text" : "password";

            toggle.classList.toggle("show", tampil);

            toggle.setAttribute(
                "aria-label",
                tampil ? "Sembunyikan password" : "Tampilkan password"
            );

        });

    });

});