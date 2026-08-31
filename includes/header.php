<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($page_title)) {
    $page_title = "Medicine Aapki Gaw Mein";
}

$current_page = basename($_SERVER['PHP_SELF']);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <title><?= htmlspecialchars($page_title) ?></title>

    <meta charset="utf-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1, shrink-to-fit=no">


    <!-- Google Fonts -->

    <link
        href="https://fonts.googleapis.com/css?family=Rubik:400,700|Crimson+Text:400,400i"
        rel="stylesheet">



    <!-- CSS -->

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
/* ===============================
   NAVBAR INLINE FIX
================================ */

@media (min-width: 992px) {

    /* Main row */
    .site-navbar .container > .d-flex {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        flex-wrap: nowrap !important;
        width: 100%;
    }

    /* Logo */
    .site-navbar .logo {
        flex: 0 0 300px !important;
        width: 300px !important;
        max-width: 300px !important;
    }

    .site-navbar .site-logo a {
        display: block !important;
        white-space: nowrap !important;
        font-size: 20px !important;
        letter-spacing: 3px !important;
    }

    /* Navigation */
    .site-navbar .main-nav {
        flex: 1 1 auto !important;
        min-width: 0 !important;
    }

    .site-navbar .site-navigation {
        width: 100%;
    }

    .site-navbar .site-navigation .site-menu {
        display: flex !important;
        align-items: center !important;
        justify-content: flex-end !important;
        flex-wrap: nowrap !important;
        white-space: nowrap !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .site-navbar .site-navigation .site-menu > li {
        display: block !important;
        flex: 0 0 auto !important;
        padding: 5px 2px !important;
        margin: 0 !important;
    }

    .site-navbar .site-navigation .site-menu > li > a {
        display: block !important;
        padding: 8px 7px !important;
        font-size: 13px !important;
        letter-spacing: .03em !important;
        white-space: nowrap !important;
    }

    /* Icons */
    .site-navbar .icons {
        display: flex !important;
        align-items: center !important;
        flex: 0 0 auto !important;
        margin-left: 10px !important;
        white-space: nowrap !important;
    }

    .site-navbar .icons .icons-btn {
        margin-left: 3px;
    }

}


/* =================================
   MEDIUM DESKTOP
================================= */

@media (min-width: 992px) and (max-width: 1199px) {

    .site-navbar .logo {
        flex-basis: 250px !important;
        width: 250px !important;
    }

    .site-navbar .site-logo a {
        font-size: 17px !important;
        letter-spacing: 2px !important;
    }

    .site-navbar .site-navigation .site-menu > li > a {
        padding-left: 5px !important;
        padding-right: 5px !important;
        font-size: 12px !important;
    }

}


/* =================================
   MOBILE
================================= */

@media (max-width: 991px) {

    .site-navbar .site-logo a {
        font-size: 18px !important;
        letter-spacing: 2px !important;
        white-space: nowrap !important;
    }

}
</style>
          

</head>

<body>

<div class="site-wrap">


    <!-- =========================
         NAVBAR
    ========================== -->

    <div class="site-navbar py-2">


        <!-- Search -->

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


        <!-- Navigation -->

        <div class="container">

            <div class="d-flex align-items-center justify-content-between">


                <!-- Logo -->

                <div class="logo">

                    <div class="site-logo">

                        <a href="index.php"
                           class="js-logo-clone">

                            Medicine Aapki Gaw Mein

                        </a>

                    </div>

                </div>


                <!-- Main Navigation -->

                <div class="main-nav d-none d-lg-block">

                    <nav
                        class="site-navigation text-right text-md-center"
                        role="navigation">

                        <ul class="site-menu js-clone-nav d-none d-lg-block">


                            <!-- Home -->

                            <li class="<?= $current_page === 'index.php' ? 'active' : '' ?>">

                                <a href="index.php">
                                    Home
                                </a>

                            </li>


                            <!-- Medicines -->

                            <li class="<?= $current_page === 'medicines.php' ? 'active' : '' ?>">

                                <a href="medicines.php">
                                    Medicines
                                </a>

                            </li>


                            <!-- Prescription -->

                            <li class="<?= $current_page === 'upload-prescription.php' ? 'active' : '' ?>">

                                <a href="upload-prescription.php">
                                    Upload Prescription
                                </a>

                            </li>


                            <!-- About -->

                            <li class="<?= $current_page === 'about.php' ? 'active' : '' ?>">

                                <a href="about.php">
                                    About
                                </a>

                            </li>


                            <!-- Contact -->

                            <li class="<?= $current_page === 'contact.php' ? 'active' : '' ?>">

                                <a href="contact.php">
                                    Contact
                                </a>

                            </li>

                        </ul>

                    </nav>

                </div>


                <!-- Right Icons -->

<div class="icons">

    <!-- Search -->

    <a href="#"
       class="icons-btn d-inline-block js-search-open">

        <span class="icon-search"></span>

    </a>


    <!-- Cart -->

    <a href="cart.php"
       class="icons-btn d-inline-block bag">

        <span class="icon-shopping-bag"></span>

        <span class="number">
            0
        </span>

    </a>


    <?php if (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0): ?>

        <!-- Logged In User -->

        <a href="profile.php"
           class="icons-btn d-inline-block"
           title="My Profile">

            <span class="icon-user"></span>

        </a>

        <a href="profile.php"
           class="btn btn-sm btn-outline-primary ml-2">
            Profile
        </a>

        <a href="logout.php"
           class="btn btn-sm btn-outline-danger ml-1">
            Logout
        </a>

    <?php else: ?>

        <!-- Login -->

        <a href="login.php"
           class="btn btn-sm btn-primary ml-2">
            Login
        </a>


        <!-- Register -->

        <a href="register.php"
           class="btn btn-sm btn-outline-primary ml-1">
            Register
        </a>

    <?php endif; ?>


    <!-- Mobile Menu -->

    <a href="#"
       class="site-menu-toggle js-menu-toggle ml-3 d-inline-block d-lg-none">

        <span class="icon-menu"></span>

    </a>

</div>


            </div>

        </div>

    </div>
