<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($page_title)) {
    $page_title = "Medicine Aapki Gaw Mein";
}

$current_page = basename($_SERVER['PHP_SELF']);

$is_logged_in = (
    isset($_SESSION['user_id']) &&
    (int) $_SESSION['user_id'] > 0
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <title><?= htmlspecialchars($page_title) ?></title>

    <meta charset="utf-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <!-- =====================================================
         GOOGLE FONT
    ====================================================== -->

    <link
        href="https://fonts.googleapis.com/css?family=Rubik:400,500,600,700|Crimson+Text:400,400i"
        rel="stylesheet">


    <!-- =====================================================
         CSS
    ====================================================== -->

    <link rel="stylesheet"
          href="assets/fonts/icomoon/style.css">

    <link rel="stylesheet"
          href="assets/css/bootstrap.min.css">

    <link rel="stylesheet"
          href="assets/css/magnific-popup.css">

    <link rel="stylesheet"
          href="assets/css/jquery-ui.css">

    <link rel="stylesheet"
          href="assets/css/owl.carousel.min.css">

    <link rel="stylesheet"
          href="assets/css/owl.theme.default.min.css">

    <link rel="stylesheet"
          href="assets/css/aos.css">

    <link rel="stylesheet"
          href="assets/css/style.css">


<style>

/* =========================================================
   GLOBAL
========================================================= */

.site-navbar {
    position: relative;
    z-index: 1000;
    width: 100%;
    background: #ffffff;
    border-bottom: 1px solid #eeeeee;
}

.site-navbar .container {
    position: relative;
}


/* =========================================================
   NAVBAR ROW
========================================================= */

.navbar-row {
    display: flex !important;
    align-items: center !important;
    width: 100%;
    min-height: 70px;
}


/* =========================================================
   LOGO
========================================================= */

.site-navbar .logo {
    flex: 0 0 315px;
    width: 315px;
    max-width: 315px;
    min-width: 315px;
}

.site-navbar .site-logo {
    width: 100%;
}

.site-navbar .site-logo a {
    display: block;

    width: 100%;

    color: #111111;

    font-family: "Rubik", sans-serif;

    font-size: 19px;
    font-weight: 700;

    letter-spacing: 1.5px;

    line-height: 1.2;

    text-decoration: none;

    white-space: nowrap;

    transition: color .2s ease;
}

.site-navbar .site-logo a:hover {
    color: #51b56d;
}


/* =========================================================
   DESKTOP NAVIGATION
========================================================= */

.desktop-navigation {
    flex: 1 1 auto;

    min-width: 0;

    margin: 0;

    padding: 0;
}

.desktop-navigation .site-navigation {
    width: 100%;
    margin: 0;
    padding: 0;
}

.desktop-navigation .site-menu {
    display: flex !important;

    align-items: center !important;

    justify-content: flex-end !important;

    flex-wrap: nowrap !important;

    width: 100%;

    margin: 0 !important;
    padding: 0 !important;

    list-style: none !important;
}

.desktop-navigation .site-menu > li {
    display: block !important;

    flex: 0 0 auto !important;

    margin: 0 !important;
    padding: 0 !important;
}

.desktop-navigation .site-menu > li > a {
    display: block;

    padding: 10px 8px;

    color: #222222;

    font-family: "Rubik", sans-serif;

    font-size: 12px;

    font-weight: 500;

    letter-spacing: .5px;

    line-height: 1.2;

    white-space: nowrap;

    text-decoration: none;

    transition: color .2s ease;
}

.desktop-navigation .site-menu > li > a:hover {
    color: #51b56d;
}

.desktop-navigation .site-menu > li.active > a {
    color: #51b56d;

    font-weight: 700;
}


/* =========================================================
   RIGHT NAVIGATION / ICONS
========================================================= */

.nav-icons {
    display: flex !important;

    align-items: center !important;

    justify-content: flex-end !important;

    flex: 0 0 auto !important;

    margin-left: 8px;

    white-space: nowrap;
}


/* Search / Cart / Profile */

.nav-icons .icons-btn {
    position: relative;

    display: inline-flex !important;

    align-items: center !important;

    justify-content: center !important;

    width: 35px;
    height: 35px;

    margin-left: 2px;

    color: #222222;

    text-decoration: none;
}

.nav-icons .icons-btn:hover {
    color: #51b56d;
}


/* Cart Number */

.nav-icons .number {
    position: absolute;

    top: -4px;
    right: -4px;

    display: flex;

    align-items: center;
    justify-content: center;

    min-width: 17px;
    height: 17px;

    padding: 0 4px;

    border-radius: 50%;

    background: #51b56d;

    color: #ffffff;

    font-size: 9px;

    font-weight: 700;

    line-height: 17px;

    text-align: center;
}


/* =========================================================
   DESKTOP LOGIN / REGISTER
========================================================= */

.nav-icons .btn {
    margin-left: 5px !important;

    padding: 7px 10px !important;

    border-radius: 4px;

    font-family: "Rubik", sans-serif;

    font-size: 11px !important;

    font-weight: 500;

    white-space: nowrap;
}


/* =========================================================
   MOBILE HAMBURGER
========================================================= */

.mobile-menu-toggle {
    display: none;

    align-items: center;
    justify-content: center;

    width: 40px;
    height: 40px;

    margin-left: 7px;

    border: 0;

    border-radius: 6px;

    background: #51b56d;

    color: #ffffff !important;

    font-size: 22px;

    line-height: 1;

    text-decoration: none !important;

    cursor: pointer;

    box-shadow: 0 3px 8px rgba(81,181,109,.25);

    transition: all .2s ease;
}

.mobile-menu-toggle:hover {
    background: #3e9d59;

    color: #ffffff !important;
}


/* =========================================================
   MOBILE OVERLAY
========================================================= */

.mobile-menu-overlay {
    position: fixed;

    top: 0;
    right: 0;
    bottom: 0;
    left: 0;

    width: 100%;
    height: 100vh;

    background: rgba(0,0,0,.45);

    opacity: 0;

    visibility: hidden;

    z-index: 9998;

    transition:
        opacity .3s ease,
        visibility .3s ease;
}

.mobile-menu-overlay.active {
    opacity: 1;

    visibility: visible;
}


/* =========================================================
   MOBILE DRAWER
========================================================= */

.mobile-menu-drawer {
    position: fixed;

    top: 0;
    right: 0;

    width: 320px;
    max-width: 88%;

    height: 100vh;

    background: #ffffff;

    z-index: 9999;

    overflow-y: auto;

    box-shadow: -10px 0 35px rgba(0,0,0,.15);

    transform: translateX(100%);

    transition: transform .3s ease;
}

.mobile-menu-drawer.active {
    transform: translateX(0);
}


/* =========================================================
   MOBILE DRAWER HEADER
========================================================= */

.mobile-menu-header {
    display: flex;

    align-items: center;

    justify-content: space-between;

    min-height: 70px;

    padding: 15px 18px;

    border-bottom: 1px solid #eeeeee;
}

.mobile-menu-logo {
    color: #111111;

    font-family: "Rubik", sans-serif;

    font-size: 17px;

    font-weight: 700;

    letter-spacing: .8px;
}

.mobile-menu-close {
    display: flex;

    align-items: center;

    justify-content: center;

    width: 38px;
    height: 38px;

    border: 0;

    border-radius: 50%;

    background: #f3f3f3;

    color: #333333;

    font-size: 25px;

    line-height: 1;

    cursor: pointer;

    transition: all .2s ease;
}

.mobile-menu-close:hover {
    background: #51b56d;

    color: #ffffff;
}


/* =========================================================
   MOBILE MENU LINKS
========================================================= */

.mobile-menu-links {
    padding: 15px;
}

.mobile-menu-links ul {
    list-style: none;

    margin: 0;
    padding: 0;
}

.mobile-menu-links li {
    margin: 3px 0;
}

.mobile-menu-links li a {
    display: flex;

    align-items: center;

    width: 100%;

    padding: 13px 14px;

    border-radius: 8px;

    color: #333333;

    font-family: "Rubik", sans-serif;

    font-size: 15px;

    font-weight: 500;

    text-decoration: none;

    transition: all .2s ease;
}

.mobile-menu-links li a:hover,
.mobile-menu-links li.active a {
    background: #eef9f1;

    color: #51b56d;
}

.mobile-menu-links .menu-icon {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    width: 30px;

    margin-right: 7px;

    font-size: 17px;
}


/* =========================================================
   MOBILE DIVIDER
========================================================= */

.mobile-divider {
    width: 100%;

    height: 1px;

    margin: 4px 0 18px;

    background: #eeeeee;
}


/* =========================================================
   MOBILE ACCOUNT
========================================================= */

.mobile-account {
    padding: 0 15px 25px;
}

.mobile-account a {
    display: block;

    width: 100%;

    margin-bottom: 10px;

    padding: 11px 15px;

    border-radius: 6px;

    font-family: "Rubik", sans-serif;

    font-size: 14px;

    font-weight: 500;

    text-align: center;

    text-decoration: none;
}


/* Login */

.mobile-login {
    background: #51b56d;

    color: #ffffff !important;
}

.mobile-login:hover {
    background: #3e9d59;

    color: #ffffff !important;
}


/* Register */

.mobile-register {
    border: 1px solid #51b56d;

    background: #ffffff;

    color: #51b56d !important;
}


/* Profile */

.mobile-profile {
    background: #eef9f1;

    color: #51b56d !important;
}


/* Logout */

.mobile-logout {
    border: 1px solid #dc3545;

    background: #ffffff;

    color: #dc3545 !important;
}


/* =========================================================
   BODY LOCK
========================================================= */

body.mobile-menu-open {
    overflow: hidden !important;
}


/* =========================================================
   LARGE DESKTOP
========================================================= */

@media (min-width: 1200px) {

    .site-navbar .logo {
        flex: 0 0 315px;

        width: 315px;

        max-width: 315px;

        min-width: 315px;
    }

    .site-navbar .site-logo a {
        font-size: 19px;
    }

    .desktop-navigation .site-menu > li > a {
        padding-left: 8px;
        padding-right: 8px;

        font-size: 12px;
    }
}


/* =========================================================
   LAPTOP
========================================================= */

@media (min-width: 992px) and (max-width: 1199px) {

    .site-navbar .logo {
        flex: 0 0 265px;

        width: 265px;

        max-width: 265px;

        min-width: 265px;
    }

    .site-navbar .site-logo a {
        font-size: 15px;

        letter-spacing: 1px;
    }

    .desktop-navigation .site-menu > li > a {
        padding-left: 5px;
        padding-right: 5px;

        font-size: 11px;
    }

    .nav-icons {
        margin-left: 4px;
    }

    .nav-icons .btn {
        padding-left: 7px !important;
        padding-right: 7px !important;

        font-size: 10px !important;
    }
}


/* =========================================================
   TABLET + MOBILE
========================================================= */

@media (max-width: 991px) {

    .site-navbar {
        padding: 7px 0 !important;
    }

    .navbar-row {
        min-height: 55px;
    }


    /* Logo */

    .site-navbar .logo {
        flex: 1 1 auto;

        width: auto;

        min-width: 0;

        max-width: none;
    }

    .site-navbar .site-logo a {
        font-size: 16px;

        letter-spacing: .7px;

        white-space: nowrap;

        overflow: hidden;

        text-overflow: ellipsis;
    }


    /* Hide desktop menu */

    .desktop-navigation {
        display: none !important;
    }


    /* Hide desktop account buttons */

    .nav-icons > .btn {
        display: none !important;
    }


    /* Icons */

    .nav-icons {
        flex: 0 0 auto;

        margin-left: 5px;
    }

    .nav-icons .icons-btn {
        width: 34px;

        height: 34px;

        margin-left: 1px;
    }


    /* Show hamburger */

    .mobile-menu-toggle {
        display: inline-flex !important;
    }
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 575px) {

    .site-navbar .container {
        padding-left: 15px;
        padding-right: 15px;
    }

    .site-navbar .site-logo a {
        font-size: 14px;

        letter-spacing: .5px;
    }

    .nav-icons .icons-btn {
        width: 32px;

        height: 32px;
    }

    .mobile-menu-toggle {
        width: 38px;

        height: 38px;

        font-size: 21px;
    }

    .mobile-menu-drawer {
        width: 300px;
    }
}


/* =========================================================
   SMALL MOBILE
========================================================= */

@media (max-width: 380px) {

    .site-navbar .site-logo a {
        font-size: 13px;

        letter-spacing: .2px;
    }

    .nav-icons .icons-btn {
        width: 30px;

        height: 30px;
    }

    .mobile-menu-toggle {
        width: 36px;

        height: 36px;

        font-size: 20px;
    }

    .mobile-menu-drawer {
        width: 285px;
    }
}

</style>

</head>


<body>

<div class="site-wrap">


<!-- =========================================================
     NAVBAR
========================================================= -->

<header class="site-navbar">


    <!-- =====================================================
         SEARCH
    ====================================================== -->

    <div class="search-wrap">

        <div class="container">

            <a href="#"
               class="search-close js-search-close">

                <span class="icon-close2"></span>

            </a>

            <form action="medicines.php"
                  method="get">

                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Search medicines..."
                >

            </form>

        </div>

    </div>


    <!-- =====================================================
         NAVBAR
    ====================================================== -->

    <div class="container">

        <div class="navbar-row">


            <!-- =================================================
                 LOGO
            ================================================== -->

            <div class="logo">

                <div class="site-logo">

                    <a href="index.php">

                        Medicine Aapki Gaw Mein

                    </a>

                </div>

            </div>


            <!-- =================================================
                 DESKTOP MENU
            ================================================== -->

            <div class="desktop-navigation">

                <nav class="site-navigation"
                     role="navigation">

                    <ul class="site-menu">


                        <!-- HOME -->

                        <li class="<?= $current_page === 'index.php' ? 'active' : '' ?>">

                            <a href="index.php">
                                Home
                            </a>

                        </li>


                        <!-- MEDICINES -->

                        <li class="<?= $current_page === 'medicines.php' ? 'active' : '' ?>">

                            <a href="medicines.php">
                                Medicines
                            </a>

                        </li>


                        <!-- PRESCRIPTION -->

                        <li class="<?= $current_page === 'upload-prescription.php' ? 'active' : '' ?>">

                            <a href="upload-prescription.php">
                                Upload Prescription
                            </a>

                        </li>


                        <!-- ABOUT -->

                        <li class="<?= $current_page === 'about.php' ? 'active' : '' ?>">

                            <a href="about.php">
                                About
                            </a>

                        </li>


                        <!-- CONTACT -->

                        <li class="<?= $current_page === 'contact.php' ? 'active' : '' ?>">

                            <a href="contact.php">
                                Contact
                            </a>

                        </li>

                    </ul>

                </nav>

            </div>


            <!-- =================================================
                 RIGHT SIDE
            ================================================== -->

            <div class="nav-icons">


                <!-- SEARCH -->

                <a href="#"
                   class="icons-btn js-search-open"
                   title="Search">

                    <span class="icon-search"></span>

                </a>


                <!-- CART -->

                <a href="cart.php"
                   class="icons-btn bag"
                   title="Shopping Cart">

                    <span class="icon-shopping-bag"></span>

                    <span class="number">
                        0
                    </span>

                </a>


                <?php if ($is_logged_in): ?>


                    <!-- PROFILE ICON -->

                    <a href="profile.php"
                       class="icons-btn d-none d-lg-inline-flex"
                       title="My Profile">

                        <span class="icon-user"></span>

                    </a>


                    <!-- PROFILE -->

                    <a href="profile.php"
                       class="btn btn-sm btn-outline-primary">

                        Profile

                    </a>


                    <!-- LOGOUT -->

                    <a href="logout.php"
                       class="btn btn-sm btn-outline-danger">

                        Logout

                    </a>


                <?php else: ?>


                    <!-- LOGIN -->

                    <a href="login.php"
                       class="btn btn-sm btn-primary">

                        Login

                    </a>


                    <!-- REGISTER -->

                    <a href="register.php"
                       class="btn btn-sm btn-outline-primary">

                        Register

                    </a>


                <?php endif; ?>


                <!-- =================================================
                     MOBILE MENU BUTTON
                ================================================== -->

                <a href="#"
                   id="mobileMenuToggle"
                   class="mobile-menu-toggle"
                   aria-label="Open Menu"
                   aria-expanded="false">

                    ☰

                </a>

            </div>

        </div>

    </div>

</header>


<!-- =========================================================
     MOBILE OVERLAY
========================================================= -->

<div id="mobileMenuOverlay"
     class="mobile-menu-overlay">
</div>


<!-- =========================================================
     MOBILE DRAWER
========================================================= -->

<aside id="mobileMenuDrawer"
       class="mobile-menu-drawer"
       aria-hidden="true">


    <!-- =====================================================
         DRAWER HEADER
    ====================================================== -->

    <div class="mobile-menu-header">

        <div class="mobile-menu-logo">

            Medicine Aapki Gaw Mein

        </div>


        <button
            type="button"
            id="mobileMenuClose"
            class="mobile-menu-close"
            aria-label="Close Menu">

            ×

        </button>

    </div>


    <!-- =====================================================
         MENU LINKS
    ====================================================== -->

    <div class="mobile-menu-links">

        <ul>


            <!-- HOME -->

            <li class="<?= $current_page === 'index.php' ? 'active' : '' ?>">

                <a href="index.php">

                    <span class="menu-icon">
                        🏠
                    </span>

                    Home

                </a>

            </li>


            <!-- MEDICINES -->

            <li class="<?= $current_page === 'medicines.php' ? 'active' : '' ?>">

                <a href="medicines.php">

                    <span class="menu-icon">
                        💊
                    </span>

                    Medicines

                </a>

            </li>


            <!-- UPLOAD PRESCRIPTION -->

            <li class="<?= $current_page === 'upload-prescription.php' ? 'active' : '' ?>">

                <a href="upload-prescription.php">

                    <span class="menu-icon">
                        📋
                    </span>

                    Upload Prescription

                </a>

            </li>


            <!-- ABOUT -->

            <li class="<?= $current_page === 'about.php' ? 'active' : '' ?>">

                <a href="about.php">

                    <span class="menu-icon">
                        ℹ️
                    </span>

                    About

                </a>

            </li>


            <!-- CONTACT -->

            <li class="<?= $current_page === 'contact.php' ? 'active' : '' ?>">

                <a href="contact.php">

                    <span class="menu-icon">
                        📞
                    </span>

                    Contact

                </a>

            </li>


            <!-- CART -->

            <li class="<?= $current_page === 'cart.php' ? 'active' : '' ?>">

                <a href="cart.php">

                    <span class="menu-icon">
                        🛒
                    </span>

                    Cart

                </a>

            </li>

        </ul>

    </div>


    <!-- =====================================================
         ACCOUNT
    ====================================================== -->

    <div class="mobile-account">

        <div class="mobile-divider"></div>


        <?php if ($is_logged_in): ?>


            <!-- PROFILE -->

            <a href="profile.php"
               class="mobile-profile">

                👤 &nbsp; My Profile

            </a>


            <!-- LOGOUT -->

            <a href="logout.php"
               class="mobile-logout">

                ↪ &nbsp; Logout

            </a>


        <?php else: ?>


            <!-- LOGIN -->

            <a href="login.php"
               class="mobile-login">

                Login

            </a>


            <!-- REGISTER -->

            <a href="register.php"
               class="mobile-register">

                Create Account

            </a>


        <?php endif; ?>

    </div>

</aside>


<!-- =========================================================
     MOBILE MENU JAVASCRIPT
========================================================= -->

<script>

document.addEventListener("DOMContentLoaded", function () {

    const toggle =
        document.getElementById("mobileMenuToggle");

    const close =
        document.getElementById("mobileMenuClose");

    const drawer =
        document.getElementById("mobileMenuDrawer");

    const overlay =
        document.getElementById("mobileMenuOverlay");


    /* =====================================================
       OPEN
    ====================================================== */

    function openMobileMenu(event) {

        if (event) {
            event.preventDefault();
        }

        if (!drawer || !overlay) {
            return;
        }

        drawer.classList.add("active");

        overlay.classList.add("active");

        document.body.classList.add(
            "mobile-menu-open"
        );

        if (toggle) {

            toggle.setAttribute(
                "aria-expanded",
                "true"
            );

        }

        drawer.setAttribute(
            "aria-hidden",
            "false"
        );
    }


    /* =====================================================
       CLOSE
    ====================================================== */

    function closeMobileMenu(event) {

        if (event) {
            event.preventDefault();
        }

        if (!drawer || !overlay) {
            return;
        }

        drawer.classList.remove("active");

        overlay.classList.remove("active");

        document.body.classList.remove(
            "mobile-menu-open"
        );

        if (toggle) {

            toggle.setAttribute(
                "aria-expanded",
                "false"
            );

        }

        drawer.setAttribute(
            "aria-hidden",
            "true"
        );
    }


    /* =====================================================
       TOGGLE CLICK
    ====================================================== */

    if (toggle) {

        toggle.addEventListener(
            "click",
            openMobileMenu
        );

    }


    /* =====================================================
       CLOSE BUTTON
    ====================================================== */

    if (close) {

        close.addEventListener(
            "click",
            closeMobileMenu
        );

    }


    /* =====================================================
       OVERLAY CLICK
    ====================================================== */

    if (overlay) {

        overlay.addEventListener(
            "click",
            closeMobileMenu
        );

    }


    /* =====================================================
       ESC KEY
    ====================================================== */

    document.addEventListener(
        "keydown",
        function (event) {

            if (event.key === "Escape") {

                closeMobileMenu();

            }

        }
    );


    /* =====================================================
       MOBILE LINK CLICK
    ====================================================== */

    const mobileLinks =
        document.querySelectorAll(
            ".mobile-menu-drawer a"
        );

    mobileLinks.forEach(function (link) {

        link.addEventListener(
            "click",
            function () {

                closeMobileMenu();

            }
        );

    });

});

</script>