/* =========================================================
   BERANDA - MUSA Complaint
   Navbar, menu mobile, menu aktif, scroll reveal
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    document.documentElement.classList.remove("no-js");

    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const hasIO = "IntersectionObserver" in window;

    /* =========================
       NAVBAR + TOMBOL KE ATAS
    ========================= */

    const navbar = document.getElementById("navbar");
    const toTop = document.getElementById("toTop");

    function onScroll() {

        const y = window.scrollY || window.pageYOffset;

        navbar.classList.toggle("scrolled", y > 16);

        if (toTop) toTop.classList.toggle("show", y > 600);
    }

    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();

    if (toTop) {
        toTop.addEventListener("click", function () {
            window.scrollTo({ top: 0, behavior: reduceMotion ? "auto" : "smooth" });
        });
    }

    /* =========================
       MENU MOBILE
    ========================= */

    const navToggle = document.getElementById("navToggle");
    const navLinks = document.getElementById("navLinks");

    function tutupMenu() {
        navToggle.classList.remove("open");
        navLinks.classList.remove("open");
        navToggle.setAttribute("aria-expanded", "false");
    }

    navToggle.addEventListener("click", function () {

        const buka = !navLinks.classList.contains("open");

        navToggle.classList.toggle("open", buka);
        navLinks.classList.toggle("open", buka);
        navToggle.setAttribute("aria-expanded", buka ? "true" : "false");
    });

    navLinks.querySelectorAll("a").forEach(function (a) {
        a.addEventListener("click", tutupMenu);
    });

    document.addEventListener("click", function (e) {
        if (!navbar.contains(e.target)) tutupMenu();
    });

    window.addEventListener("resize", function () {
        if (window.innerWidth > 960) tutupMenu();
    });

    /* =========================
       MENU AKTIF SESUAI SECTION
    ========================= */

    const menuLinks = navLinks.querySelectorAll("a[href^='#']");

    if (hasIO) {

        const spy = new IntersectionObserver(function (entries) {

            entries.forEach(function (entry) {

                if (!entry.isIntersecting) return;

                const id = "#" + entry.target.id;

                menuLinks.forEach(function (a) {
                    a.classList.toggle("active", a.getAttribute("href") === id);
                });
            });

        }, { rootMargin: "-45% 0px -50% 0px" });

        document.querySelectorAll("main section[id]").forEach(function (s) {
            spy.observe(s);
        });
    }

    /* =========================
       SCROLL REVEAL
    ========================= */

    const reveals = document.querySelectorAll(".reveal, .steps");

    if (hasIO && !reduceMotion) {

        const io = new IntersectionObserver(function (entries, obs) {

            entries.forEach(function (entry) {

                if (!entry.isIntersecting) return;

                entry.target.classList.add("in");
                obs.unobserve(entry.target);
            });

        }, { threshold: 0.12, rootMargin: "0px 0px -40px 0px" });

        reveals.forEach(function (el) { io.observe(el); });

    } else {
        reveals.forEach(function (el) { el.classList.add("in"); });
    }

});