<?php

session_start();

$page_title = "Home | Medicine Aapki Gaw Mein";


// =====================================================
// DATABASE CONNECTION
// =====================================================

require_once "config/database.php";


// =====================================================
// HELPER
// =====================================================

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


// =====================================================
// DEFAULT HERO
// =====================================================

$defaultHero = [
    'subtitle'         => 'Genuine Medicines • Trusted Healthcare',
    'title'            => 'Medicine Aapki Gaw Mein',
    'description'      => 'Quality medicines and healthcare products delivered conveniently to your doorstep.',
    'button_text'      => 'Shop Medicines',
    'button_link'      => 'medicines.php',
    'background_image' => 'assets/images/hero_1.jpg'
];


// =====================================================
// FETCH ACTIVE HEROES
// =====================================================

$heroes = [];

$hero_sql = "
    SELECT
        id,
        subtitle,
        title,
        description,
        button_text,
        button_link,
        background_image
    FROM hero_sections
    WHERE status = 1
    ORDER BY id DESC
";

$hero_result = mysqli_query($conn, $hero_sql);

if ($hero_result) {

    while ($row = mysqli_fetch_assoc($hero_result)) {

        $heroes[] = $row;

    }

}


// =====================================================
// DEFAULT HERO IF DATABASE EMPTY
// =====================================================

if (empty($heroes)) {

    $heroes[] = $defaultHero;

}


// =====================================================
// FETCH POPULAR MEDICINES
// =====================================================

$popularMedicines = [];

$medicine_sql = "
    SELECT
        id,
        name,
        generic_name,
        manufacturer,
        category,
        image,
        mrp,
        selling_price,
        stock_quantity,
        prescription_required
    FROM medicines
    WHERE status = 1
    ORDER BY id DESC
    LIMIT 6
";

$medicine_result = mysqli_query(
    $conn,
    $medicine_sql
);

if ($medicine_result) {

    while ($row = mysqli_fetch_assoc($medicine_result)) {

        $popularMedicines[] = $row;

    }

}


// =====================================================
// FETCH TESTIMONIALS
// =====================================================

$testimonials = [];

$testimonial_sql = "
    SELECT
        id,
        customer_name,
        message,
        image
    FROM testimonials
    WHERE status = 1
    ORDER BY id DESC
";

$testimonial_result = mysqli_query(
    $conn,
    $testimonial_sql
);

if ($testimonial_result) {

    while ($row = mysqli_fetch_assoc($testimonial_result)) {

        $testimonials[] = $row;

    }

}


// =====================================================
// HEADER
// =====================================================

require_once "includes/header.php";

?>


<!-- =====================================================
     CUSTOM HOME PAGE CSS
===================================================== -->

<style>

/* =====================================================
   ROOT
===================================================== */

:root {

    --pharmacy-green: #159447;

    --pharmacy-dark: #087333;

    --pharmacy-light: #effaf3;

    --pharmacy-blue: #1877d2;

    --dark: #172b25;

    --muted: #6c7a75;

    --border: #e8eeeb;

    --white: #ffffff;

}


/* =====================================================
   GLOBAL
===================================================== */

.home-page {

    overflow: hidden;

}


.home-section {

    padding: 80px 0;

}


.section-heading {

    margin-bottom: 45px;

}


.section-heading .small-title {

    display: inline-block;

    color: var(--pharmacy-green);

    font-size: 12px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: 1.5px;

    margin-bottom: 10px;

}


.section-heading h2 {

    margin: 0;

    color: var(--dark);

    font-size: 34px;

    font-weight: 800;

}


.section-heading p {

    max-width: 650px;

    margin: 12px auto 0;

    color: var(--muted);

    font-size: 15px;

    line-height: 1.8;

}


/* =====================================================
   HERO
===================================================== */

.home-hero {

    position: relative;

}


.home-hero .carousel-item {

    background: #123c29;

}


.home-hero-slide {

    position: relative;

    min-height: 620px;

    display: flex;

    align-items: center;

    background-size: cover;

    background-position: center;

}


.home-hero-overlay {

    position: absolute;

    inset: 0;

    background:
        linear-gradient(
            90deg,
            rgba(4, 35, 21, .88) 0%,
            rgba(5, 50, 29, .72) 42%,
            rgba(5, 45, 27, .28) 100%
        );

}


.home-hero-content {

    position: relative;

    z-index: 5;

    max-width: 720px;

    padding: 70px 0;

}


.home-hero-badge {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 9px 16px;

    margin-bottom: 22px;

    border-radius: 50px;

    color: #fff;

    background: rgba(255,255,255,.13);

    border: 1px solid rgba(255,255,255,.22);

    backdrop-filter: blur(8px);

    font-size: 12px;

    font-weight: 700;

}


.home-hero-content h1 {

    color: #fff;

    font-size: 58px;

    line-height: 1.08;

    font-weight: 900;

    letter-spacing: -.8px;

    margin-bottom: 22px;

}


.home-hero-content p {

    color: rgba(255,255,255,.88);

    max-width: 650px;

    font-size: 17px;

    line-height: 1.8;

    margin-bottom: 30px;

}


.hero-actions {

    display: flex;

    flex-wrap: wrap;

    align-items: center;

    gap: 12px;

}


.hero-primary-btn {

    display: inline-flex;

    align-items: center;

    gap: 10px;

    padding: 14px 24px;

    border-radius: 9px;

    background: #fff;

    color: var(--pharmacy-dark) !important;

    font-size: 13px;

    font-weight: 800;

    text-decoration: none !important;

    transition: .25s ease;

}


.hero-primary-btn:hover {

    transform: translateY(-3px);

    box-shadow: 0 12px 30px rgba(0,0,0,.20);

}


.hero-secondary-btn {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 14px 22px;

    border-radius: 9px;

    background: rgba(255,255,255,.10);

    border: 1px solid rgba(255,255,255,.25);

    color: #fff !important;

    font-size: 13px;

    font-weight: 700;

    text-decoration: none !important;

    backdrop-filter: blur(5px);

}


.hero-secondary-btn:hover {

    background: rgba(255,255,255,.18);

}


/* HERO TRUST */

.hero-trust {

    display: flex;

    flex-wrap: wrap;

    gap: 22px;

    margin-top: 38px;

}


.hero-trust-item {

    display: flex;

    align-items: center;

    gap: 9px;

    color: rgba(255,255,255,.9);

    font-size: 12px;

    font-weight: 600;

}


.hero-trust-icon {

    width: 30px;

    height: 30px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background: rgba(255,255,255,.14);

}


/* HERO INDICATORS */

.home-hero .carousel-indicators {

    bottom: 28px;

    z-index: 8;

}


.home-hero .carousel-indicators li {

    width: 25px;

    height: 4px;

    border: 0;

    border-radius: 5px;

}


/* HERO ARROWS */

.home-hero .carousel-control-prev,
.home-hero .carousel-control-next {

    width: 7%;

    z-index: 7;

}


/* =====================================================
   QUICK SERVICES
===================================================== */

.service-section {

    margin-top: -45px;

    position: relative;

    z-index: 10;

}


.service-card {

    height: 100%;

    padding: 28px;

    border-radius: 18px;

    background: #fff;

    border: 1px solid var(--border);

    box-shadow:
        0 15px 45px rgba(21,72,48,.09);

    transition: .3s ease;

}


.service-card:hover {

    transform: translateY(-8px);

    box-shadow:
        0 20px 50px rgba(21,72,48,.15);

}


.service-top {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 24px;

}


.service-icon {

    width: 60px;

    height: 60px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 16px;

    font-size: 27px;

}


.service-delivery .service-icon {

    background: #e5f8eb;

}


.service-prescription .service-icon {

    background: #e7f1ff;

}


.service-trusted .service-icon {

    background: #fff4d9;

}


.service-tag {

    font-size: 10px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .8px;

    color: var(--muted);

}


.service-card h3 {

    color: var(--dark);

    font-size: 21px;

    font-weight: 800;

    line-height: 1.3;

    margin-bottom: 10px;

}


.service-card p {

    color: var(--muted);

    font-size: 13px;

    line-height: 1.75;

    margin-bottom: 20px;

}


.service-link {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    color: var(--pharmacy-green) !important;

    font-size: 12px;

    font-weight: 800;

    text-decoration: none !important;

}


.service-link span {

    transition: .2s ease;

}


.service-link:hover span {

    transform: translateX(5px);

}


/* =====================================================
   MEDICINES SECTION
===================================================== */

.medicines-section {

    background: #fff;

}


.medicine-box {

    height: 100%;

    position: relative;

    overflow: hidden;

    border-radius: 18px;

    background: #fff;

    border: 1px solid var(--border);

    transition: .3s ease;

}


.medicine-box:hover {

    transform: translateY(-6px);

    box-shadow:
        0 18px 45px rgba(0,0,0,.09);

}


.medicine-image-wrap {

    height: 240px;

    position: relative;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        linear-gradient(
            145deg,
            #f8fcf9,
            #edf8f1
        );

    padding: 25px;

}


.medicine-image {

    width: 100%;

    height: 100%;

    object-fit: contain;

    transition: .3s ease;

}


.medicine-box:hover .medicine-image {

    transform: scale(1.06);

}


.medicine-badge {

    position: absolute;

    top: 14px;

    left: 14px;

    padding: 6px 10px;

    border-radius: 30px;

    background: var(--pharmacy-green);

    color: #fff;

    font-size: 9px;

    font-weight: 800;

    text-transform: uppercase;

}


.medicine-prescription {

    position: absolute;

    top: 14px;

    right: 14px;

    padding: 6px 9px;

    border-radius: 30px;

    background: #fff;

    color: #c06b00;

    border: 1px solid #f2dfbd;

    font-size: 9px;

    font-weight: 800;

}


.medicine-body {

    padding: 22px;

}


.medicine-category {

    color: var(--pharmacy-green);

    font-size: 10px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .7px;

    margin-bottom: 7px;

}


.medicine-name {

    min-height: 48px;

    margin: 0 0 5px;

}


.medicine-name a {

    color: var(--dark) !important;

    font-size: 17px;

    font-weight: 800;

    line-height: 1.35;

    text-decoration: none !important;

}


.medicine-generic {

    min-height: 20px;

    color: #8a9691;

    font-size: 11px;

    margin-bottom: 16px;

}


.medicine-price-row {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

}


.medicine-price {

    display: flex;

    align-items: center;

    flex-wrap: wrap;

    gap: 7px;

}


.medicine-price strong {

    color: var(--pharmacy-dark);

    font-size: 18px;

}


.medicine-price del {

    color: #9ca5a1;

    font-size: 12px;

}


.medicine-view {

    width: 36px;

    height: 36px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 10px;

    background: var(--pharmacy-light);

    color: var(--pharmacy-green);

    text-decoration: none !important;

    transition: .2s ease;

}


.medicine-view:hover {

    background: var(--pharmacy-green);

    color: #fff;

}


/* =====================================================
   WHY CHOOSE US
===================================================== */

.why-section {

    background:
        linear-gradient(
            135deg,
            #f4fbf6,
            #ffffff
        );

}


.why-content h2 {

    color: var(--dark);

    font-size: 35px;

    font-weight: 850;

    line-height: 1.25;

    margin-bottom: 16px;

}


.why-content > p {

    color: var(--muted);

    font-size: 14px;

    line-height: 1.8;

}


.why-list {

    margin-top: 28px;

}


.why-item {

    display: flex;

    gap: 15px;

    margin-bottom: 20px;

}


.why-icon {

    width: 45px;

    height: 45px;

    min-width: 45px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 12px;

    background: #dff5e6;

    color: var(--pharmacy-green);

    font-weight: 900;

}


.why-item h4 {

    color: var(--dark);

    font-size: 15px;

    font-weight: 800;

    margin: 0 0 4px;

}


.why-item p {

    color: var(--muted);

    font-size: 12px;

    line-height: 1.6;

    margin: 0;

}


/* =====================================================
   HEALTHCARE VISUAL
===================================================== */

.health-visual {

    position: relative;

    min-height: 420px;

    border-radius: 25px;

    overflow: hidden;

    background:
        linear-gradient(
            135deg,
            #178d48,
            #075d2d
        );

    box-shadow:
        0 20px 50px rgba(8,115,51,.18);

}


.health-circle {

    position: absolute;

    width: 320px;

    height: 320px;

    right: -80px;

    top: -80px;

    border-radius: 50%;

    background: rgba(255,255,255,.08);

}


.health-circle-two {

    position: absolute;

    width: 230px;

    height: 230px;

    left: -70px;

    bottom: -80px;

    border-radius: 50%;

    background: rgba(255,255,255,.07);

}


.health-content {

    position: relative;

    z-index: 3;

    height: 100%;

    min-height: 420px;

    padding: 45px;

    display: flex;

    flex-direction: column;

    justify-content: center;

}


.health-content .medical-symbol {

    width: 75px;

    height: 75px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 20px;

    background: rgba(255,255,255,.15);

    color: #fff;

    font-size: 38px;

    margin-bottom: 25px;

}


.health-content h3 {

    color: #fff;

    font-size: 32px;

    font-weight: 850;

    line-height: 1.25;

}


.health-content p {

    max-width: 450px;

    color: rgba(255,255,255,.82);

    font-size: 14px;

    line-height: 1.8;

}


/* =====================================================
   HOW IT WORKS
===================================================== */

.steps-section {

    background: #fff;

}


.step-card {

    position: relative;

    height: 100%;

    padding: 30px 20px;

    text-align: center;

}


.step-number {

    position: absolute;

    top: 10px;

    right: 20px;

    color: #e7f4eb;

    font-size: 55px;

    line-height: 1;

    font-weight: 900;

}


.step-icon {

    position: relative;

    z-index: 2;

    width: 76px;

    height: 76px;

    margin: 0 auto 22px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 22px;

    background: var(--pharmacy-light);

    color: var(--pharmacy-green);

    font-size: 30px;

}


.step-card h3 {

    color: var(--dark);

    font-size: 17px;

    font-weight: 800;

}


.step-card p {

    color: var(--muted);

    font-size: 12px;

    line-height: 1.7;

}


.step-line {

    position: absolute;

    top: 68px;

    left: 73%;

    width: 54%;

    height: 1px;

    background: #dceee2;

}


/* =====================================================
   TESTIMONIALS
===================================================== */

.testimonial-section {

    background:
        #f7faf8;

}


.testimonial-card {

    height: 100%;

    padding: 30px;

    border-radius: 18px;

    background: #fff;

    border: 1px solid var(--border);

    box-shadow:
        0 8px 30px rgba(0,0,0,.04);

}


.testimonial-stars {

    color: #f5b400;

    font-size: 14px;

    letter-spacing: 2px;

    margin-bottom: 17px;

}


.testimonial-message {

    min-height: 105px;

    color: #596761;

    font-size: 13px;

    line-height: 1.8;

    font-style: italic;

}


.testimonial-user {

    display: flex;

    align-items: center;

    gap: 13px;

    margin-top: 20px;

    padding-top: 18px;

    border-top: 1px solid #edf1ef;

}


.testimonial-image {

    width: 48px;

    height: 48px;

    object-fit: cover;

    border-radius: 50%;

}


.testimonial-avatar {

    width: 48px;

    height: 48px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background: #e3f6e9;

    color: var(--pharmacy-green);

    font-size: 17px;

    font-weight: 800;

}


.testimonial-user h4 {

    color: var(--dark);

    font-size: 13px;

    font-weight: 800;

    margin: 0 0 3px;

}


.testimonial-user span {

    color: #8b9691;

    font-size: 10px;

}


/* =====================================================
   CTA
===================================================== */

.cta-section {

    padding: 0 0 80px;

}


.cta-box {

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


.cta-box::before {

    content: "";

    position: absolute;

    width: 300px;

    height: 300px;

    right: -90px;

    top: -140px;

    border-radius: 50%;

    background: rgba(255,255,255,.07);

}


.cta-box::after {

    content: "";

    position: absolute;

    width: 180px;

    height: 180px;

    left: -70px;

    bottom: -100px;

    border-radius: 50%;

    background: rgba(255,255,255,.06);

}


.cta-content {

    position: relative;

    z-index: 2;

}


.cta-content h2 {

    color: #fff;

    font-size: 31px;

    font-weight: 850;

    margin-bottom: 10px;

}


.cta-content p {

    max-width: 650px;

    color: rgba(255,255,255,.82);

    font-size: 14px;

    line-height: 1.7;

}


.cta-buttons {

    display: flex;

    flex-wrap: wrap;

    gap: 10px;

    margin-top: 22px;

}


.cta-btn-white {

    padding: 13px 23px;

    border-radius: 8px;

    background: #fff;

    color: var(--pharmacy-dark) !important;

    font-size: 12px;

    font-weight: 800;

    text-decoration: none !important;

}


.cta-btn-outline {

    padding: 13px 23px;

    border-radius: 8px;

    border: 1px solid rgba(255,255,255,.35);

    color: #fff !important;

    font-size: 12px;

    font-weight: 700;

    text-decoration: none !important;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 991px) {

    .home-hero-slide {

        min-height: 570px;

    }

    .home-hero-content h1 {

        font-size: 45px;

    }

    .service-section {

        margin-top: -25px;

    }

    .step-line {

        display: none;

    }

}


@media (max-width: 767px) {

    .home-section {

        padding: 60px 0;

    }

    .section-heading h2 {

        font-size: 27px;

    }

    .home-hero-slide {

        min-height: 560px;

        background-position: center;

    }

    .home-hero-content {

        padding: 45px 15px;

    }

    .home-hero-content h1 {

        font-size: 36px;

    }

    .home-hero-content p {

        font-size: 14px;

    }

    .hero-trust {

        gap: 12px;

    }

    .service-section {

        margin-top: 0;

        padding-top: 30px;

    }

    .service-card {

        margin-bottom: 15px;

    }

    .health-visual {

        margin-top: 35px;

    }

    .health-content {

        padding: 35px;

    }

    .health-content h3 {

        font-size: 27px;

    }

    .cta-box {

        padding: 35px 25px;

    }

    .cta-content h2 {

        font-size: 26px;

    }

}


@media (max-width: 480px) {

    .home-hero-slide {

        min-height: 530px;

    }

    .home-hero-content h1 {

        font-size: 31px;

    }

    .home-hero-badge {

        font-size: 10px;

    }

    .hero-actions {

        flex-direction: column;

        align-items: stretch;

    }

    .hero-primary-btn,
    .hero-secondary-btn {

        justify-content: center;

    }

    .hero-trust-item {

        font-size: 10px;

    }

    .medicine-image-wrap {

        height: 210px;

    }

}

</style>


<div class="home-page">


<!-- =====================================================
     HERO
===================================================== -->

<section class="home-hero">

    <div
        id="homeHeroCarousel"
        class="carousel slide"
        data-ride="carousel"
        data-interval="5000"
    >

        <?php if (count($heroes) > 1): ?>

            <ol class="carousel-indicators">

                <?php foreach ($heroes as $index => $hero): ?>

                    <li
                        data-target="#homeHeroCarousel"
                        data-slide-to="<?= $index ?>"
                        class="<?= $index === 0 ? 'active' : '' ?>"
                    ></li>

                <?php endforeach; ?>

            </ol>

        <?php endif; ?>


        <div class="carousel-inner">

            <?php foreach ($heroes as $index => $hero): ?>

                <?php

                $heroSubtitle =
                    !empty($hero['subtitle'])
                    ? $hero['subtitle']
                    : $defaultHero['subtitle'];

                $heroTitle =
                    !empty($hero['title'])
                    ? $hero['title']
                    : $defaultHero['title'];

                $heroDescription =
                    !empty($hero['description'])
                    ? $hero['description']
                    : $defaultHero['description'];

                $heroButtonText =
                    !empty($hero['button_text'])
                    ? $hero['button_text']
                    : $defaultHero['button_text'];

                $heroButtonLink =
                    !empty($hero['button_link'])
                    ? $hero['button_link']
                    : $defaultHero['button_link'];

                $heroBackground =
                    !empty($hero['background_image'])
                    ? $hero['background_image']
                    : $defaultHero['background_image'];

                ?>

                <div
                    class="
                        carousel-item
                        <?= $index === 0 ? 'active' : '' ?>
                    "
                >

                    <div
                        class="home-hero-slide"
                        style="
                            background-image:
                            url('<?= e($heroBackground) ?>');
                        "
                    >

                        <div class="home-hero-overlay"></div>


                        <div class="container">

                            <div class="row">

                                <div class="col-lg-8">

                                    <div class="home-hero-content">

                                        <div class="home-hero-badge">

                                            <span>✓</span>

                                            <?= e($heroSubtitle) ?>

                                        </div>


                                        <h1>

                                            <?= e($heroTitle) ?>

                                        </h1>


                                        <p>

                                            <?= nl2br(
                                                e($heroDescription)
                                            ) ?>

                                        </p>


                                        <div class="hero-actions">

                                            <a
                                                href="<?= e($heroButtonLink) ?>"
                                                class="hero-primary-btn"
                                            >

                                                <?= e($heroButtonText) ?>

                                                <span>→</span>

                                            </a>


                                            <a
                                                href="upload-prescription.php"
                                                class="hero-secondary-btn"
                                            >

                                                📄

                                                Upload Prescription

                                            </a>

                                        </div>


                                        <div class="hero-trust">

                                            <div class="hero-trust-item">

                                                <span
                                                    class="hero-trust-icon"
                                                >
                                                    ✓
                                                </span>

                                                Genuine Medicines

                                            </div>


                                            <div class="hero-trust-item">

                                                <span
                                                    class="hero-trust-icon"
                                                >
                                                    🚚
                                                </span>

                                                Local Delivery

                                            </div>


                                            <div class="hero-trust-item">

                                                <span
                                                    class="hero-trust-icon"
                                                >
                                                    🛡️
                                                </span>

                                                Trusted Service

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>


        <?php if (count($heroes) > 1): ?>

            <a
                class="carousel-control-prev"
                href="#homeHeroCarousel"
                role="button"
                data-slide="prev"
            >

                <span
                    class="carousel-control-prev-icon"
                    aria-hidden="true"
                ></span>

                <span class="sr-only">
                    Previous
                </span>

            </a>


            <a
                class="carousel-control-next"
                href="#homeHeroCarousel"
                role="button"
                data-slide="next"
            >

                <span
                    class="carousel-control-next-icon"
                    aria-hidden="true"
                ></span>

                <span class="sr-only">
                    Next
                </span>

            </a>

        <?php endif; ?>

    </div>

</section>


<!-- =====================================================
     SERVICE CARDS
===================================================== -->

<section class="service-section">

    <div class="container">

        <div class="row">


            <!-- DELIVERY -->

            <div class="col-lg-4 mb-4">

                <div class="service-card service-delivery">

                    <div class="service-top">

                        <div class="service-icon">
                            🚚
                        </div>

                        <span class="service-tag">
                            Forbesganj Local
                        </span>

                    </div>


                    <h3>
                        Fast Medicine Delivery
                    </h3>


                    <p>

                        Forbesganj aur nearby areas mein
                        medicines ko conveniently ghar tak
                        mangwayein.

                    </p>


                    <a
                        href="medicines.php"
                        class="service-link"
                    >

                        Explore Medicines

                        <span>→</span>

                    </a>

                </div>

            </div>


            <!-- PRESCRIPTION -->

            <div class="col-lg-4 mb-4">

                <div class="service-card service-prescription">

                    <div class="service-top">

                        <div class="service-icon">
                            📄
                        </div>

                        <span class="service-tag">
                            Easy & Secure
                        </span>

                    </div>


                    <h3>
                        Upload Your Prescription
                    </h3>


                    <p>

                        Doctor ki prescription upload karein
                        aur apni medicine requirement
                        easily submit karein.

                    </p>


                    <a
                        href="upload-prescription.php"
                        class="service-link"
                    >

                        Upload Prescription

                        <span>→</span>

                    </a>

                </div>

            </div>


            <!-- TRUST -->

            <div class="col-lg-4 mb-4">

                <div class="service-card service-trusted">

                    <div class="service-top">

                        <div class="service-icon">
                            ❤️
                        </div>

                        <span class="service-tag">
                            Healthcare Partner
                        </span>

                    </div>


                    <h3>
                        Trusted Healthcare
                    </h3>


                    <p>

                        Genuine medicines aur reliable
                        healthcare support aapke apne
                        area mein.

                    </p>


                    <a
                        href="about.php"
                        class="service-link"
                    >

                        Know More

                        <span>→</span>

                    </a>

                </div>

            </div>


        </div>

    </div>

</section>


<!-- =====================================================
     POPULAR MEDICINES
===================================================== -->

<section class="home-section medicines-section">

    <div class="container">


        <div class="section-heading text-center">

            <span class="small-title">
                Our Collection
            </span>

            <h2>
                Popular Medicines
            </h2>

            <p>

                Browse our latest medicines and
                healthcare products.

            </p>

        </div>


        <div class="row">

            <?php if (!empty($popularMedicines)): ?>

                <?php foreach ($popularMedicines as $medicine): ?>

                    <?php

                    $medicineName =
                        !empty($medicine['name'])
                        ? $medicine['name']
                        : 'Medicine';

                    $medicineImage =
                        !empty($medicine['image'])
                        ? 'uploads/medicines/' .
                          $medicine['image']
                        : 'assets/images/product_01.png';

                    $mrp =
                        isset($medicine['mrp'])
                        ? (float) $medicine['mrp']
                        : 0;

                    $sellingPrice =
                        isset($medicine['selling_price'])
                        ? (float) $medicine['selling_price']
                        : 0;

                    $discount = 0;

                    if (
                        $mrp > 0 &&
                        $sellingPrice > 0 &&
                        $mrp > $sellingPrice
                    ) {

                        $discount =
                            round(
                                (($mrp - $sellingPrice) / $mrp) * 100
                            );

                    }

                    ?>

                    <div class="col-sm-6 col-lg-4 mb-4">

                        <div class="medicine-box">


                            <!-- IMAGE -->

                            <div class="medicine-image-wrap">

                                <?php if ($discount > 0): ?>

                                    <span class="medicine-badge">

                                        <?= $discount ?>% OFF

                                    </span>

                                <?php else: ?>

                                    <span class="medicine-badge">

                                        Popular

                                    </span>

                                <?php endif; ?>


                                <?php if (
                                    !empty(
                                        $medicine[
                                            'prescription_required'
                                        ]
                                    )
                                    &&
                                    (
                                        $medicine[
                                            'prescription_required'
                                        ] == 1
                                    )
                                ): ?>

                                    <span
                                        class="medicine-prescription"
                                    >

                                        Rx Required

                                    </span>

                                <?php endif; ?>


                                <a href="medicines.php">

                                    <img
                                        src="<?= e(
                                            $medicineImage
                                        ) ?>"
                                        alt="<?= e(
                                            $medicineName
                                        ) ?>"
                                        class="medicine-image"
                                        loading="lazy"
                                    >

                                </a>

                            </div>


                            <!-- BODY -->

                            <div class="medicine-body">


                                <?php if (
                                    !empty(
                                        $medicine['category']
                                    )
                                ): ?>

                                    <div
                                        class="medicine-category"
                                    >

                                        <?= e(
                                            $medicine['category']
                                        ) ?>

                                    </div>

                                <?php else: ?>

                                    <div
                                        class="medicine-category"
                                    >

                                        Healthcare

                                    </div>

                                <?php endif; ?>


                                <h3 class="medicine-name">

                                    <a href="medicines.php">

                                        <?= e(
                                            $medicineName
                                        ) ?>

                                    </a>

                                </h3>


                                <div
                                    class="medicine-generic"
                                >

                                    <?php if (
                                        !empty(
                                            $medicine['generic_name']
                                        )
                                    ): ?>

                                        <?= e(
                                            $medicine[
                                                'generic_name'
                                            ]
                                        ) ?>

                                    <?php else: ?>

                                        Quality Healthcare Product

                                    <?php endif; ?>

                                </div>


                                <div
                                    class="
                                        medicine-price-row
                                    "
                                >

                                    <div
                                        class="medicine-price"
                                    >

                                        <?php if (
                                            $mrp > 0 &&
                                            $mrp > $sellingPrice
                                        ): ?>

                                            <del>

                                                ₹<?= number_format(
                                                    $mrp,
                                                    2
                                                ) ?>

                                            </del>

                                        <?php endif; ?>


                                        <strong>

                                            ₹<?= number_format(
                                                $sellingPrice,
                                                2
                                            ) ?>

                                        </strong>

                                    </div>


                                    <a
                                        href="medicines.php"
                                        class="medicine-view"
                                        title="View Medicines"
                                    >

                                        →

                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="col-12">

                    <div
                        class="
                            text-center
                            py-5
                            px-3
                        "
                    >

                        <div
                            class="
                                medicine-box
                                p-5
                            "
                        >

                            <h4>

                                Medicines Coming Soon

                            </h4>

                            <p class="text-muted">

                                Medicines will appear here
                                once they are added from
                                the admin panel.

                            </p>

                        </div>

                    </div>

                </div>

            <?php endif; ?>

        </div>


        <div class="text-center mt-4">

            <a
                href="medicines.php"
                class="
                    btn
                    btn-primary
                    px-4
                    py-3
                "
            >

                View All Medicines

                <span class="ml-2">
                    →
                </span>

            </a>

        </div>

    </div>

</section>


<!-- =====================================================
     WHY CHOOSE US
===================================================== -->

<section class="home-section why-section">

    <div class="container">

        <div class="row align-items-center">


            <!-- CONTENT -->

            <div class="col-lg-6">

                <div class="why-content">

                    <span
                        class="
                            section-heading
                            small-title
                        "
                        style="
                            display:inline-block;
                            color:#159447;
                            font-size:12px;
                            font-weight:800;
                            text-transform:uppercase;
                            letter-spacing:1.5px;
                            margin-bottom:10px;
                        "
                    >

                        Why Choose Us

                    </span>


                    <h2>

                        Healthcare Made
                        <br>
                        Simple & Reliable

                    </h2>


                    <p>

                        Medicine Aapki Gaw Mein ka goal hai
                        ki local customers ko medicines aur
                        healthcare products conveniently
                        available karayein.

                    </p>


                    <div class="why-list">


                        <div class="why-item">

                            <div class="why-icon">
                                ✓
                            </div>

                            <div>

                                <h4>
                                    Genuine Medicines
                                </h4>

                                <p>
                                    Quality-focused medicine
                                    and healthcare products.
                                </p>

                            </div>

                        </div>


                        <div class="why-item">

                            <div class="why-icon">
                                🚚
                            </div>

                            <div>

                                <h4>
                                    Local Convenience
                                </h4>

                                <p>
                                    Forbesganj aur nearby areas
                                    ke customers ke liye convenient
                                    service.
                                </p>

                            </div>

                        </div>


                        <div class="why-item">

                            <div class="why-icon">
                                📄
                            </div>

                            <div>

                                <h4>
                                    Easy Prescription Upload
                                </h4>

                                <p>
                                    Prescription upload karke
                                    medicine requirement submit
                                    karna simple hai.
                                </p>

                            </div>

                        </div>


                    </div>

                </div>

            </div>


            <!-- VISUAL -->

            <div class="col-lg-6">

                <div class="health-visual">

                    <div class="health-circle"></div>

                    <div class="health-circle-two"></div>


                    <div class="health-content">

                        <div class="medical-symbol">

                            +

                        </div>


                        <h3>

                            Your Local
                            Healthcare Partner

                        </h3>


                        <p>

                            Medicines, prescription support
                            aur healthcare convenience —
                            sab kuch aapke area ke liye.

                        </p>

                    </div>

                </div>

            </div>


        </div>

    </div>

</section>


<!-- =====================================================
     HOW IT WORKS
===================================================== -->

<section class="home-section steps-section">

    <div class="container">


        <div class="section-heading text-center">

            <span class="small-title">
                Simple Process
            </span>

            <h2>
                How It Works
            </h2>

            <p>

                Get your medicine in just a few
                simple steps.

            </p>

        </div>


        <div class="row">


            <!-- STEP 1 -->

            <div class="col-md-3">

                <div class="step-card">

                    <div class="step-number">
                        01
                    </div>

                    <div class="step-icon">

                        🔍

                    </div>

                    <h3>
                        Find Medicine
                    </h3>

                    <p>

                        Search and browse the
                        medicines you need.

                    </p>

                    <div class="step-line"></div>

                </div>

            </div>


            <!-- STEP 2 -->

            <div class="col-md-3">

                <div class="step-card">

                    <div class="step-number">
                        02
                    </div>

                    <div class="step-icon">

                        📄

                    </div>

                    <h3>
                        Upload Prescription
                    </h3>

                    <p>

                        Upload your doctor's
                        prescription securely.

                    </p>

                    <div class="step-line"></div>

                </div>

            </div>


            <!-- STEP 3 -->

            <div class="col-md-3">

                <div class="step-card">

                    <div class="step-number">
                        03
                    </div>

                    <div class="step-icon">

                        🛒

                    </div>

                    <h3>
                        Place Order
                    </h3>

                    <p>

                        Submit your medicine
                        requirement.

                    </p>

                    <div class="step-line"></div>

                </div>

            </div>


            <!-- STEP 4 -->

            <div class="col-md-3">

                <div class="step-card">

                    <div class="step-number">
                        04
                    </div>

                    <div class="step-icon">

                        🏠

                    </div>

                    <h3>
                        Get Medicine
                    </h3>

                    <p>

                        Receive your medicine
                        conveniently.

                    </p>

                </div>

            </div>


        </div>

    </div>

</section>


<!-- =====================================================
     TESTIMONIALS
===================================================== -->

<?php if (!empty($testimonials)): ?>

<section class="home-section testimonial-section">

    <div class="container">


        <div class="section-heading text-center">

            <span class="small-title">
                Customer Feedback
            </span>

            <h2>
                What Our Customers Say
            </h2>

            <p>

                We value the trust and feedback
                of our customers.

            </p>

        </div>


        <div class="row">


            <?php foreach ($testimonials as $testimonial): ?>

                <?php

                $customerName =
                    !empty($testimonial['customer_name'])
                    ? $testimonial['customer_name']
                    : 'Customer';

                $message =
                    !empty($testimonial['message'])
                    ? $testimonial['message']
                    : '';

                $firstLetter =
                    strtoupper(
                        substr(
                            trim($customerName),
                            0,
                            1
                        )
                    );

                ?>

                <div class="col-md-6 col-lg-4 mb-4">

                    <div class="testimonial-card">


                        <div class="testimonial-stars">

                            ★★★★★

                        </div>


                        <div
                            class="
                                testimonial-message
                            "
                        >

                            &ldquo;

                            <?= nl2br(
                                e($message)
                            ) ?>

                            &rdquo;

                        </div>


                        <div class="testimonial-user">


                            <?php if (
                                !empty(
                                    $testimonial['image']
                                )
                            ): ?>

                                <img
                                    src="uploads/testimonials/<?= e(
                                        $testimonial['image']
                                    ) ?>"
                                    alt="<?= e(
                                        $customerName
                                    ) ?>"
                                    class="
                                        testimonial-image
                                    "
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <div
                                    class="
                                        testimonial-avatar
                                    "
                                >

                                    <?= e(
                                        $firstLetter
                                    ) ?>

                                </div>

                            <?php endif; ?>


                            <div>

                                <h4>

                                    <?= e(
                                        $customerName
                                    ) ?>

                                </h4>

                                <span>
                                    Verified Customer
                                </span>

                            </div>


                        </div>

                    </div>

                </div>

            <?php endforeach; ?>


        </div>

    </div>

</section>

<?php endif; ?>


<!-- =====================================================
     FINAL CTA
===================================================== -->

<section class="cta-section">

    <div class="container">

        <div class="cta-box">

            <div class="cta-content">

                <h2>

                    Need Medicines?

                </h2>


                <p>

                    Find the medicines you need or
                    upload your prescription and submit
                    your requirement easily.

                </p>


                <div class="cta-buttons">

                    <a
                        href="medicines.php"
                        class="cta-btn-white"
                    >

                        Browse Medicines
                        →

                    </a>


                    <a
                        href="upload-prescription.php"
                        class="cta-btn-outline"
                    >

                        Upload Prescription

                    </a>

                </div>

            </div>

        </div>

    </div>

</section>


</div>


<?php

// =====================================================
// FOOTER
// =====================================================

require_once "includes/footer.php";

?>