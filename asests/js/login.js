document.addEventListener("DOMContentLoaded", function () {

    const passwordInput = document.getElementById("password");
    const togglePassword = document.getElementById("togglePassword");

    if (passwordInput && togglePassword) {

        togglePassword.addEventListener("click", function () {

            if (passwordInput.type === "password") {

                passwordInput.type = "text";

                togglePassword.classList.add("show");

                togglePassword.setAttribute(
                    "aria-label",
                    "Sembunyikan password"
                );

            } else {

                passwordInput.type = "password";

                togglePassword.classList.remove("show");

                togglePassword.setAttribute(
                    "aria-label",
                    "Tampilkan password"
                );

            }

        });

    }

});