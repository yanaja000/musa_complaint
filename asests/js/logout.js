document.addEventListener("DOMContentLoaded", function () {

    var links = document.querySelectorAll('a[href$="logout.php"]');

    if (links.length === 0) {
        return;
    }

    /* ===== CSS popup ===== */

    var style = document.createElement("style");

    style.textContent = `
        .logout-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;

            display: none;
            align-items: center;
            justify-content: center;

            padding: 20px;
            background: rgba(23, 32, 51, 0.45);
        }

        .logout-overlay.show {
            display: flex;
        }

        .logout-box {
            width: 100%;
            max-width: 400px;

            padding: 30px;

            background: #ffffff;
            border: 1px solid #e4ebf5;
            border-radius: 16px;

            font-family: 'Poppins', sans-serif;
            text-align: center;
        }

        .logout-icon {
            width: 54px;
            height: 54px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin: 0 auto 16px;

            border-radius: 50%;
            background: #eaf2ff;
            color: #2563eb;

            font-size: 24px;
            font-weight: 600;
        }

        .logout-box h3 {
            margin-bottom: 8px;

            font-size: 20px;
            font-weight: 600;
            color: #172033;
        }

        .logout-box p {
            margin-bottom: 24px;

            font-size: 14px;
            line-height: 1.6;
            color: #64748b;
        }

        .logout-actions {
            display: flex;
            gap: 12px;
        }

        .logout-actions button,
        .logout-actions a {
            flex: 1;

            padding: 12px;

            border-radius: 10px;

            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
            text-decoration: none;

            cursor: pointer;
            transition: 0.2s ease;
        }

        .logout-cancel {
            background: #ffffff;
            color: #2563eb;
            border: 1px solid #cddfff;
        }

        .logout-cancel:hover {
            background: #eaf2ff;
            border-color: #2563eb;
        }

        .logout-confirm {
            background: #2563eb;
            color: #ffffff;
            border: 1px solid #2563eb;
        }

        .logout-confirm:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
        }
    `;

    document.head.appendChild(style);

    /* ===== HTML popup ===== */

    var overlay = document.createElement("div");

    overlay.className = "logout-overlay";

    overlay.innerHTML = `
        <div class="logout-box" role="dialog" aria-modal="true">

            <div class="logout-icon">!</div>

            <h3>Keluar dari akun?</h3>

            <p>
                Anda harus login kembali untuk
                mengakses MUSA Complaint.
            </p>

            <div class="logout-actions">

                <button type="button" class="logout-cancel">
                    Batal
                </button>

                <a href="#" class="logout-confirm">
                    Ya, Keluar
                </a>

            </div>

        </div>
    `;

    document.body.appendChild(overlay);

    var confirmButton = overlay.querySelector(".logout-confirm");
    var cancelButton = overlay.querySelector(".logout-cancel");

    function closePopup() {
        overlay.classList.remove("show");
    }

    /* ===== Event ===== */

    links.forEach(function (link) {

        link.addEventListener("click", function (e) {

            e.preventDefault();

            confirmButton.href = link.getAttribute("href");

            overlay.classList.add("show");

            confirmButton.focus();
        });
    });

    cancelButton.addEventListener("click", closePopup);

    overlay.addEventListener("click", function (e) {
        if (e.target === overlay) {
            closePopup();
        }
    });

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            closePopup();
        }
    });
});