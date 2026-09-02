<?php

session_start();

$page_title = "About Us | Medicine Aapki Gaw Mein";

require_once "includes/header.php";

?>

<style>

/* =========================================================
   ABOUT PAGE
   MEDICINE AAPKI GAW MEIN
========================================================= */

.about-page {

    overflow: hidden;

}


/* =========================================================
   ROOT COLORS
========================================================= */

.about-page {

    --green: #159447;

    --dark-green: #087333;

    --light-green: #effaf3;

    --very-light: #f7faf8;

    --dark: #172b25;

    --text: #5f6e68;

    --border: #e5ece8;

}


/* =========================================================
   HERO
========================================================= */

.about-hero {

    position: relative;

    min-height: 480px;

    display: flex;

    align-items: center;

    background:
        linear-gradient(
            90deg,
            rgba(4,35,21,.90) 0%,
            rgba(5,50,29,.72) 45%,
            rgba(5,45,27,.30) 100%
        ),
        url('assets/images/hero_1.jpg')
        center center / cover no-repeat;

}


.about-hero-content {

    position: relative;

    z-index: 2;

    max-width: 760px;

    padding: 60px 0;

}


.about-hero-badge {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 9px 16px;

    margin-bottom: 20px;

    border-radius: 50px;

    background: rgba(255,255,255,.13);

    border: 1px solid rgba(255,255,255,.22);

    color: #fff;

    font-size: 11px;

    font-weight: 800;

    letter-spacing: 1.2px;

    text-transform: uppercase;

    backdrop-filter: blur(8px);

}


.about-hero-content h1 {

    color: #fff;

    font-size: 55px;

    line-height: 1.1;

    font-weight: 900;

    letter-spacing: -.7px;

    margin-bottom: 20px;

}


.about-hero-content p {

    max-width: 670px;

    color: rgba(255,255,255,.88);

    font-size: 16px;

    line-height: 1.85;

    margin-bottom: 28px;

}


.about-hero-buttons {

    display: flex;

    flex-wrap: wrap;

    gap: 10px;

}


.about-hero-btn {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 13px 22px;

    border-radius: 9px;

    background: #fff;

    color: var(--dark-green) !important;

    font-size: 12px;

    font-weight: 800;

    text-decoration: none !important;

    transition: .25s ease;

}


.about-hero-btn:hover {

    transform: translateY(-3px);

    box-shadow: 0 12px 30px rgba(0,0,0,.20);

}


.about-hero-btn-outline {

    background: rgba(255,255,255,.10);

    border: 1px solid rgba(255,255,255,.28);

    color: #fff !important;

}


/* =========================================================
   STATS
========================================================= */

.about-stats {

    position: relative;

    z-index: 5;

    margin-top: -45px;

}


.about-stat-card {

    height: 100%;

    padding: 25px 18px;

    text-align: center;

    border-radius: 16px;

    background: #fff;

    border: 1px solid var(--border);

    box-shadow:
        0 14px 40px rgba(17,58,38,.10);

    transition: .3s ease;

}


.about-stat-card:hover {

    transform: translateY(-5px);

}


.about-stat-icon {

    width: 45px;

    height: 45px;

    margin: 0 auto 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 12px;

    background: var(--light-green);

    color: var(--green);

    font-size: 20px;

}


.about-stat-card strong {

    display: block;

    color: var(--dark-green);

    font-size: 27px;

    font-weight: 900;

    line-height: 1.2;

    margin-bottom: 5px;

}


.about-stat-card span {

    color: #718079;

    font-size: 11px;

    font-weight: 600;

}


/* =========================================================
   COMMON SECTION
========================================================= */

.about-section {

    padding: 85px 0;

}


.about-section-light {

    background: var(--very-light);

}


.about-section-heading {

    margin-bottom: 45px;

}


.about-mini-title {

    display: inline-block;

    color: var(--green);

    font-size: 11px;

    font-weight: 900;

    text-transform: uppercase;

    letter-spacing: 1.5px;

    margin-bottom: 9px;

}


.about-section-heading h2 {

    color: var(--dark);

    font-size: 35px;

    line-height: 1.25;

    font-weight: 900;

    margin: 0 0 12px;

}


.about-section-heading p {

    max-width: 650px;

    margin: auto;

    color: var(--text);

    font-size: 14px;

    line-height: 1.8;

}


/* =========================================================
   STORY SECTION
========================================================= */

.about-story-image {

    position: relative;

}


.about-story-image img {

    width: 100%;

    height: 480px;

    object-fit: cover;

    border-radius: 22px;

    box-shadow:
        0 20px 50px rgba(0,0,0,.11);

}


.about-story-badge {

    position: absolute;

    right: -20px;

    bottom: 25px;

    padding: 20px 23px;

    border-radius: 15px;

    background:
        linear-gradient(
            135deg,
            var(--green),
            var(--dark-green)
        );

    color: #fff;

    box-shadow:
        0 14px 35px rgba(8,115,51,.25);

}


.about-story-badge strong {

    display: block;

    font-size: 25px;

    line-height: 1.1;

    font-weight: 900;

}


.about-story-badge span {

    display: block;

    margin-top: 5px;

    color: rgba(255,255,255,.82);

    font-size: 10px;

}


.about-story-content {

    padding-left: 45px;

}


.about-story-content h2 {

    color: var(--dark);

    font-size: 36px;

    line-height: 1.25;

    font-weight: 900;

    margin-bottom: 18px;

}


.about-story-content p {

    color: var(--text);

    font-size: 14px;

    line-height: 1.9;

    margin-bottom: 15px;

}


.about-check-list {

    list-style: none;

    padding: 0;

    margin: 25px 0 0;

}


.about-check-list li {

    position: relative;

    padding-left: 34px;

    margin-bottom: 13px;

    color: #34443d;

    font-size: 13px;

    font-weight: 600;

}


.about-check-list li::before {

    content: "✓";

    position: absolute;

    left: 0;

    top: -2px;

    width: 22px;

    height: 22px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background: #def4e5;

    color: var(--green);

    font-size: 11px;

    font-weight: 900;

}


/* =========================================================
   MISSION / VISION
========================================================= */

.about-purpose-card {

    height: 100%;

    padding: 35px;

    border-radius: 19px;

    background: #fff;

    border: 1px solid var(--border);

    box-shadow:
        0 10px 35px rgba(0,0,0,.045);

    transition: .3s ease;

}


.about-purpose-card:hover {

    transform: translateY(-7px);

    box-shadow:
        0 18px 45px rgba(0,0,0,.09);

}


.about-purpose-icon {

    width: 64px;

    height: 64px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 17px;

    background: var(--light-green);

    color: var(--green);

    font-size: 28px;

    margin-bottom: 22px;

}


.about-purpose-card h3 {

    color: var(--dark);

    font-size: 22px;

    font-weight: 900;

    margin-bottom: 12px;

}


.about-purpose-card p {

    color: var(--text);

    font-size: 13px;

    line-height: 1.85;

    margin: 0;

}


/* =========================================================
   VALUES
========================================================= */

.about-value-card {

    height: 100%;

    padding: 30px 23px;

    text-align: center;

    border-radius: 18px;

    background: #fff;

    border: 1px solid var(--border);

    transition: .3s ease;

}


.about-value-card:hover {

    transform: translateY(-7px);

    box-shadow:
        0 15px 40px rgba(0,0,0,.08);

}


.about-value-icon {

    width: 65px;

    height: 65px;

    margin: 0 auto 19px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 18px;

    background: var(--light-green);

    color: var(--green);

    font-size: 27px;

}


.about-value-card h3 {

    color: var(--dark);

    font-size: 17px;

    font-weight: 900;

    margin-bottom: 10px;

}


.about-value-card p {

    color: var(--text);

    font-size: 12px;

    line-height: 1.75;

    margin: 0;

}


/* =========================================================
   HOW IT WORKS
========================================================= */

.about-process {

    position: relative;

}


.about-process-card {

    position: relative;

    height: 100%;

    padding: 28px 20px;

    text-align: center;

}


.about-process-number {

    width: 62px;

    height: 62px;

    margin: 0 auto 20px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 18px;

    background:
        linear-gradient(
            135deg,
            var(--green),
            var(--dark-green)
        );

    color: #fff;

    font-size: 17px;

    font-weight: 900;

    box-shadow:
        0 10px 25px rgba(21,148,71,.20);

}


.about-process-card h3 {

    color: var(--dark);

    font-size: 17px;

    font-weight: 900;

    margin-bottom: 9px;

}


.about-process-card p {

    color: var(--text);

    font-size: 12px;

    line-height: 1.7;

    margin: 0;

}


.about-process-line {

    position: absolute;

    top: 58px;

    left: 73%;

    width: 54%;

    height: 1px;

    background: #d7eadc;

}


/* =========================================================
   COMMITMENT
========================================================= */

.about-commitment {

    background:
        linear-gradient(
            135deg,
            #f1faf4,
            #ffffff
        );

}


.commitment-visual {

    position: relative;

    min-height: 420px;

    overflow: hidden;

    border-radius: 24px;

    background:
        linear-gradient(
            135deg,
            #138b45,
            #075d2d
        );

    box-shadow:
        0 20px 50px rgba(8,115,51,.17);

}


.commitment-circle-one {

    position: absolute;

    width: 330px;

    height: 330px;

    right: -100px;

    top: -100px;

    border-radius: 50%;

    background: rgba(255,255,255,.07);

}


.commitment-circle-two {

    position: absolute;

    width: 220px;

    height: 220px;

    left: -80px;

    bottom: -90px;

    border-radius: 50%;

    background: rgba(255,255,255,.06);

}


.commitment-content {

    position: relative;

    z-index: 2;

    min-height: 420px;

    padding: 45px;

    display: flex;

    flex-direction: column;

    justify-content: center;

}


.commitment-icon {

    width: 70px;

    height: 70px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 19px;

    background: rgba(255,255,255,.14);

    color: #fff;

    font-size: 34px;

    margin-bottom: 22px;

}


.commitment-content h3 {

    color: #fff;

    font-size: 31px;

    font-weight: 900;

    line-height: 1.25;

    margin-bottom: 13px;

}


.commitment-content p {

    max-width: 480px;

    color: rgba(255,255,255,.82);

    font-size: 13px;

    line-height: 1.85;

    margin: 0;

}


/* =========================================================
   HEALTHCARE NOTE
========================================================= */

.about-note {

    padding: 28px;

    border-radius: 16px;

    background: #fff;

    border: 1px solid #e5ece8;

    margin-top: 25px;

}


.about-note-title {

    display: flex;

    align-items: center;

    gap: 10px;

    color: var(--dark);

    font-size: 14px;

    font-weight: 900;

    margin-bottom: 8px;

}


.about-note-icon {

    width: 30px;

    height: 30px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 8px;

    background: #fff2d9;

    color: #a66a00;

}


.about-note p {

    color: var(--text);

    font-size: 11px;

    line-height: 1.7;

    margin: 0;

}


/* =========================================================
   CTA
========================================================= */

.about-cta {

    padding: 75px 0;

}


.about-cta-box {

    position: relative;

    overflow: hidden;

    padding: 55px;

    border-radius: 25px;

    background:
        linear-gradient(
            135deg,
            #0c7737,
            #159447
        );

}


.about-cta-box::before {

    content: "";

    position: absolute;

    width: 300px;

    height: 300px;

    right: -100px;

    top: -140px;

    border-radius: 50%;

    background: rgba(255,255,255,.07);

}


.about-cta-box::after {

    content: "";

    position: absolute;

    width: 190px;

    height: 190px;

    left: -80px;

    bottom: -100px;

    border-radius: 50%;

    background: rgba(255,255,255,.06);

}


.about-cta-content {

    position: relative;

    z-index: 2;

}


.about-cta-content h2 {

    color: #fff;

    font-size: 32px;

    font-weight: 900;

    margin-bottom: 10px;

}


.about-cta-content p {

    max-width: 680px;

    margin: 0 auto;

    color: rgba(255,255,255,.83);

    font-size: 13px;

    line-height: 1.8;

}


.about-cta-buttons {

    display: flex;

    flex-wrap: wrap;

    justify-content: center;

    gap: 10px;

    margin-top: 25px;

}


.about-cta-primary {

    padding: 13px 24px;

    border-radius: 8px;

    background: #fff;

    color: var(--dark-green) !important;

    font-size: 12px;

    font-weight: 900;

    text-decoration: none !important;

}


.about-cta-secondary {

    padding: 12px 24px;

    border-radius: 8px;

    border: 1px solid rgba(255,255,255,.35);

    color: #fff !important;

    font-size: 12px;

    font-weight: 800;

    text-decoration: none !important;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 991px) {

    .about-hero {

        min-height: 420px;

    }


    .about-hero-content h1 {

        font-size: 43px;

    }


    .about-story-content {

        padding-left: 0;

        padding-top: 40px;

    }


    .about-story-badge {

        right: 15px;

    }


    .about-section {

        padding: 65px 0;

    }


    .about-process-line {

        display: none;

    }

}


@media (max-width: 767px) {

    .about-hero {

        min-height: 430px;

        text-align: center;

    }


    .about-hero-content {

        padding: 35px 15px;

    }


    .about-hero-content h1 {

        font-size: 34px;

    }


    .about-hero-content p {

        font-size: 14px;

    }


    .about-hero-buttons {

        justify-content: center;

    }


    .about-stats {

        margin-top: 0;

        padding-top: 25px;

    }


    .about-stat-card {

        margin-bottom: 15px;

    }


    .about-section-heading h2 {

        font-size: 28px;

    }


    .about-story-image img {

        height: 330px;

    }


    .about-story-badge {

        bottom: 15px;

        right: 15px;

        padding: 15px 18px;

    }


    .about-story-badge strong {

        font-size: 21px;

    }


    .commitment-visual {

        margin-top: 30px;

    }


    .commitment-content {

        padding: 35px;

    }


    .commitment-content h3 {

        font-size: 26px;

    }


    .about-cta-box {

        padding: 38px 25px;

    }


    .about-cta-content h2 {

        font-size: 26px;

    }

}


@media (max-width: 480px) {

    .about-hero-content h1 {

        font-size: 30px;

    }


    .about-hero-buttons {

        flex-direction: column;

        align-items: stretch;

    }


    .about-hero-btn {

        justify-content: center;

    }


    .about-story-content h2 {

        font-size: 28px;

    }


    .about-cta-buttons {

        flex-direction: column;

    }


    .about-cta-primary,
    .about-cta-secondary {

        text-align: center;

    }

}

</style>

<div class="about-page">

<!-- =========================================================
     HERO
========================================================= -->

<section class="about-hero">

<div class="container">

    <div class="about-hero-content">

        <div class="about-hero-badge">

            <span>✓</span>

            About Medicine Aapki Gaw Mein

        </div>


        <h1>

            Healthcare ko
            <br>
            aapke ghar ke paas

        </h1>


        <p>

            Medicine Aapki Gaw Mein ka mission hai
            medicines aur healthcare support ko
            simple, convenient aur accessible banana —
            khaas kar local communities ke liye.

        </p>


        <div class="about-hero-buttons">

            <a
                href="medicines.php"
                class="about-hero-btn"
            >

                Browse Medicines

                <span>→</span>

            </a>


            <a
                href="upload-prescription.php"
                class="
                    about-hero-btn
                    about-hero-btn-outline
                "
            >

                📄

                Upload Prescription

            </a>

        </div>

    </div>

</div>

</section>

<!-- =========================================================
     STATS
========================================================= -->

<section class="about-stats">

<div class="container">

    <div class="row">


        <div class="col-6 col-lg-3 mb-3">

            <div class="about-stat-card">

                <div class="about-stat-icon">

                    🏠

                </div>

                <strong>
                    Local
                </strong>

                <span>
                    Healthcare Support
                </span>

            </div>

        </div>


        <div class="col-6 col-lg-3 mb-3">

            <div class="about-stat-card">

                <div class="about-stat-icon">

                    💊

                </div>

                <strong>
                    Easy
                </strong>

                <span>
                    Medicine Access
                </span>

            </div>

        </div>


        <div class="col-6 col-lg-3 mb-3">

            <div class="about-stat-card">

                <div class="about-stat-icon">

                    📄

                </div>

                <strong>
                    Simple
                </strong>

                <span>
                    Prescription Upload
                </span>

            </div>

        </div>


        <div class="col-6 col-lg-3 mb-3">

            <div class="about-stat-card">

                <div class="about-stat-icon">

                    ❤️

                </div>

                <strong>
                    Customer
                </strong>

                <span>
                    Focused Service
                </span>

            </div>

        </div>


    </div>

</div>

</section>

<!-- =========================================================
     OUR STORY
========================================================= -->

<section class="about-section">

<div class="container">

    <div class="row align-items-center">


        <div class="col-lg-6">

            <div class="about-story-image">

                <img
                    src="assets/images/bg_1.jpg"
                    alt="Medicine Aapki Gaw Mein"
                    loading="lazy"
                >


                <div class="about-story-badge">

                    <strong>
                        Healthcare
                    </strong>

                    <span>
                        Closer to your home
                    </span>

                </div>

            </div>

        </div>


        <div class="col-lg-6">

            <div class="about-story-content">

                <span class="about-mini-title">

                    Who We Are

                </span>


                <h2>

                    Aapke area ka
                    local healthcare partner

                </h2>


                <p>

                    <strong>
                        Medicine Aapki Gaw Mein
                    </strong>
                    ek digital healthcare platform hai
                    jiska purpose medicines aur healthcare
                    products ko customers ke liye easy
                    aur convenient banana hai.

                </p>


                <p>

                    Hum technology ka use karke medicine
                    search, prescription submission aur
                    ordering process ko simple banane par
                    focus karte hain.

                </p>


                <ul class="about-check-list">

                    <li>
                        Easy medicine search and browsing
                    </li>

                    <li>
                        Convenient prescription upload
                    </li>

                    <li>
                        Simple medicine requirement submission
                    </li>

                    <li>
                        Local customer-focused service
                    </li>

                </ul>

            </div>

        </div>


    </div>

</div>

</section>

<!-- =========================================================
     MISSION / VISION
========================================================= -->

<section class="about-section about-section-light">

<div class="container">


    <div class="about-section-heading text-center">

        <span class="about-mini-title">

            Our Purpose

        </span>


        <h2>

            Mission & Vision

        </h2>


        <p>

            Healthcare ko simple, accessible aur
            customer-friendly banana hamara focus hai.

        </p>

    </div>


    <div class="row">


        <!-- MISSION -->

        <div class="col-md-6 mb-4">

            <div class="about-purpose-card">

                <div class="about-purpose-icon">

                    ❤️

                </div>


                <h3>

                    Our Mission

                </h3>


                <p>

                    Customers ko medicines aur healthcare
                    products tak convenient access provide
                    karna aur medicine search, prescription
                    submission aur ordering ko simple banana.

                </p>

            </div>

        </div>


        <!-- VISION -->

        <div class="col-md-6 mb-4">

            <div class="about-purpose-card">

                <div class="about-purpose-icon">

                    ⭐

                </div>


                <h3>

                    Our Vision

                </h3>


                <p>

                    Aisa local healthcare platform develop
                    karna jahan communities medicines aur
                    basic healthcare requirements ko
                    convenient way mein manage kar saken.

                </p>

            </div>

        </div>


    </div>

</div>

</section>

<!-- =========================================================
     OUR VALUES
========================================================= -->

<section class="about-section">

<div class="container">


    <div class="about-section-heading text-center">

        <span class="about-mini-title">

            What We Focus On

        </span>


        <h2>

            Built Around Your Convenience

        </h2>


        <p>

            Platform ko simple aur useful rakhne ke
            liye hum kuch important values par focus karte hain.

        </p>

    </div>


    <div class="row">


        <!-- VALUE 1 -->

        <div class="col-md-6 col-lg-3 mb-4">

            <div class="about-value-card">

                <div class="about-value-icon">

                    ✓

                </div>


                <h3>

                    Genuine Focus

                </h3>


                <p>

                    Quality medicines aur healthcare
                    products par focus.

                </p>

            </div>

        </div>


        <!-- VALUE 2 -->

        <div class="col-md-6 col-lg-3 mb-4">

            <div class="about-value-card">

                <div class="about-value-icon">

                    🚚

                </div>


                <h3>

                    Convenience

                </h3>


                <p>

                    Medicine requirements ko
                    easy aur convenient banana.

                </p>

            </div>

        </div>


        <!-- VALUE 3 -->

        <div class="col-md-6 col-lg-3 mb-4">

            <div class="about-value-card">

                <div class="about-value-icon">

                    🛡️

                </div>


                <h3>

                    Trust

                </h3>


                <p>

                    Customer trust aur responsible
                    healthcare practices ko importance.

                </p>

            </div>

        </div>


        <!-- VALUE 4 -->

        <div class="col-md-6 col-lg-3 mb-4">

            <div class="about-value-card">

                <div class="about-value-icon">

                    🤝

                </div>


                <h3>

                    Support

                </h3>


                <p>

                    Customers ke medicine requirements
                    ko better way mein support karna.

                </p>

            </div>

        </div>


    </div>

</div>

</section>

<!-- =========================================================
     HOW IT WORKS
========================================================= -->

<section class="about-section about-section-light">

<div class="container">


    <div class="about-section-heading text-center">

        <span class="about-mini-title">

            Simple Process

        </span>


        <h2>

            How It Works

        </h2>


        <p>

            Medicine requirement submit karna
            sirf kuch simple steps ka process hai.

        </p>

    </div>


    <div class="row">


        <!-- STEP 1 -->

        <div class="col-md-6 col-lg-3 mb-4">

            <div class="about-process-card">

                <div class="about-process-number">

                    01

                </div>


                <h3>

                    Find Medicine

                </h3>


                <p>

                    Required medicine ko
                    browse ya search karein.

                </p>


                <div class="about-process-line"></div>

            </div>

        </div>


        <!-- STEP 2 -->

        <div class="col-md-6 col-lg-3 mb-4">

            <div class="about-process-card">

                <div class="about-process-number">

                    02

                </div>


                <h3>

                    Upload Prescription

                </h3>


                <p>

                    Prescription medicines ke liye
                    prescription upload karein.

                </p>


                <div class="about-process-line"></div>

            </div>

        </div>


        <!-- STEP 3 -->

        <div class="col-md-6 col-lg-3 mb-4">

            <div class="about-process-card">

                <div class="about-process-number">

                    03

                </div>


                <h3>

                    Submit Requirement

                </h3>


                <p>

                    Apni medicine requirement
                    submit karein.

                </p>


                <div class="about-process-line"></div>

            </div>

        </div>


        <!-- STEP 4 -->

        <div class="col-md-6 col-lg-3 mb-4">

            <div class="about-process-card">

                <div class="about-process-number">

                    04

                </div>


                <h3>

                    Get Medicine

                </h3>


                <p>

                    Available service ke according
                    medicine receive karein.

                </p>

            </div>

        </div>


    </div>

</div>

</section>

<!-- =========================================================
     COMMITMENT
========================================================= -->

<section class="about-section about-commitment">

<div class="container">

    <div class="row align-items-center">


        <!-- CONTENT -->

        <div class="col-lg-6">

            <span class="about-mini-title">

                Our Commitment

            </span>


            <h2
                style="
                    color:#172b25;
                    font-size:35px;
                    font-weight:900;
                    line-height:1.25;
                    margin-bottom:15px;
                "
            >

                Aapka trust,
                hamari priority

            </h2>


            <p
                style="
                    color:#5f6e68;
                    font-size:14px;
                    line-height:1.85;
                    margin-bottom:15px;
                "
            >

                Healthcare mein trust aur responsibility
                bahut important hai. Isliye hum ek simple,
                transparent aur customer-friendly platform
                banane par focus karte hain.

            </p>


            <div class="about-note">

                <div class="about-note-title">

                    <span class="about-note-icon">

                        !

                    </span>

                    Important Healthcare Information

                </div>


                <p>

                    Prescription medicines ke case mein
                    valid prescription aur applicable
                    healthcare requirements ko follow karna
                    zaroori hai. Medicine use karne se pehle
                    doctor ya qualified healthcare professional
                    ki advice follow karein.

                </p>

            </div>


            <div style="margin-top:25px;">

                <a
                    href="medicines.php"
                    class="btn btn-primary px-4 py-3"
                >

                    Explore Medicines

                    <span class="ml-2">
                        →
                    </span>

                </a>

            </div>

        </div>


        <!-- VISUAL -->

        <div class="col-lg-6">

            <div class="commitment-visual">

                <div
                    class="
                        commitment-circle-one
                    "
                ></div>


                <div
                    class="
                        commitment-circle-two
                    "
                ></div>


                <div class="commitment-content">

                    <div class="commitment-icon">

                        +

                    </div>


                    <h3>

                        Healthcare
                        closer to home

                    </h3>


                    <p>

                        Medicines aur healthcare
                        convenience ko local communities
                        ke closer lane ke liye technology
                        ka simple use.

                    </p>

                </div>

            </div>

        </div>


    </div>

</div>


</section>

<!-- =========================================================
     FINAL CTA
========================================================= -->

<section class="about-cta">

<div class="container">

    <div class="about-cta-box">

        <div class="about-cta-content text-center">

            <h2>

                Need Medicines?

            </h2>


            <p>

                Apni required medicines explore karein
                ya prescription upload karke apni
                medicine requirement submit karein.

            </p>


            <div class="about-cta-buttons">

                <a
                    href="medicines.php"
                    class="about-cta-primary"
                >

                    Browse Medicines

                    →

                </a>


                <a
                    href="upload-prescription.php"
                    class="about-cta-secondary"
                >

                    📄

                    Upload Prescription

                </a>

            </div>

        </div>

    </div>

</div>


</section>

</div>

<?php

require_once "includes/footer.php";

?>
