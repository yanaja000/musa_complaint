document.addEventListener("DOMContentLoaded", function () {

    var form = document.getElementById("formHapus");

    if (!form) {
        return;
    }

    /* ===== CSS popup ===== */

    var style = document.createElement("style");

    style.textContent = `
        .hapus-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;

            display: none;
            align-items: center;
            justify-content: center;

            padding: 20px;
            background: rgba(23, 32, 51, 0.45);
        }

        .hapus-overlay.show {
            display: flex;
        }

        .hapus-box {
            width: 100%;
            max-width: 400px;

            padding: 30px;

            background: #ffffff;
            border: 1px solid #e4ebf5;
            border-radius: 16px;

            font-family: 'Poppins', sans-serif;
            text-align: center;
        }

        .hapus-icon {
            width: 54px;
            height: 54px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin: 0 auto 16px;

            border-radius: 50%;
            background: #fee2e2;
            color: #dc2626;

            font-size: 24px;
            font-weight: 600;
        }

        .hapus-box h3 {
            margin-bottom: 8px;

            font-size: 20px;
            font-weight: 600;
            color: #172033;
        }

        .hapus-box p {
            margin-bottom: 24px;

            font-size: 14px;
            line-height: 1.6;
            color: #64748b;
        }

        .hapus-actions {
            display: flex;
            gap: 12px;
        }

        .hapus-actions button {
            flex: 1;

            padding: 12px;

            border-radius: 10px;

            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 500;
            text-align: center;

            cursor: pointer;
            transition: 0.2s ease;
        }

        .hapus-cancel {
            background: #ffffff;
            color: #2563eb;
            border: 1px solid #cddfff;
        }

        .hapus-cancel:hover {
            background: #eaf2ff;
            border-color: #2563eb;
        }

        .hapus-confirm {
            background: #dc2626;
            color: #ffffff;
            border: 1px solid #dc2626;
        }

        .hapus-confirm:hover {
            background: #b91c1c;
            border-color: #b91c1c;
        }

        .hapus-confirm:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
    `;

    document.head.appendChild(style);

    /* ===== HTML popup ===== */

    var overlay = document.createElement("div");

    overlay.className = "hapus-overlay";

    overlay.innerHTML = `
        <div class="hapus-box" role="dialog" aria-modal="true">

            <div class="hapus-icon">!</div>

            <h3>Hapus pengaduan?</h3>

            <p>
                Pengaduan beserta foto bukti, tanggapan, dan
                riwayat statusnya akan dihapus permanen dan
                tidak dapat dikembalikan.
            </p>

            <div class="hapus-actions">

                <button type="button" class="hapus-cancel">
                    Batal
                </button>

                <button type="button" class="hapus-confirm">
                    Ya, Hapus
                </button>

            </div>

        </div>
    `;

    document.body.appendChild(overlay);

    var confirmButton = overlay.querySelector(".hapus-confirm");
    var cancelButton = overlay.querySelector(".hapus-cancel");

    function openPopup() {
        overlay.classList.add("show");
        cancelButton.focus();
    }

    function closePopup() {
        overlay.classList.remove("show");
    }

    /* ===== Event ===== */

    // Tahan submit form, tampilkan popup dulu
    form.addEventListener("submit", function (e) {
        e.preventDefault();
        openPopup();
    });

    // Kalau dikonfirmasi, kirim form yang sama (CSRF token ikut terkirim)
    confirmButton.addEventListener("click", function () {
        confirmButton.disabled = true;
        confirmButton.textContent = "Menghapus...";
        HTMLFormElement.prototype.submit.call(form);
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