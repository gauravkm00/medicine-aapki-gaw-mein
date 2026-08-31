<?php

session_start();

$page_title = "Contact Us | Medicine Aapki Gaw Mein";

require_once "config/database.php";


// =========================================================
// FORM MESSAGE
// =========================================================

$success_message = '';
$error_message = '';


// =========================================================
// CONTACT FORM SUBMISSION
// =========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');


    // =====================================================
    // VALIDATION
    // =====================================================

    if ($name === '') {

        $error_message = "Please enter your name.";

    } elseif ($phone === '') {

        $error_message = "Please enter your phone number.";

    } elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {

        $error_message = "Please enter a valid phone number.";

    } elseif (
        $email !== '' &&
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $error_message = "Please enter a valid email address.";

    } elseif ($subject === '') {

        $error_message = "Please select your query type.";

    } elseif ($message === '') {

        $error_message = "Please enter your message.";

    }


    // =====================================================
    // SAVE MESSAGE
    // =====================================================

    if ($error_message === '') {

        try {

            $sql = "
                INSERT INTO contact_messages
                (
                    name,
                    phone,
                    email,
                    subject,
                    message,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    'unread'
                )
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {

                throw new Exception(
                    "Database prepare failed."
                );

            }


            $stmt->bind_param(
                "sssss",
                $name,
                $phone,
                $email,
                $subject,
                $message
            );


            if ($stmt->execute()) {

                $success_message =
                    "Thank you! Your message has been submitted successfully. We will contact you soon.";


                // Clear form after successful submission

                $name = '';
                $phone = '';
                $email = '';
                $subject = '';
                $message = '';

            } else {

                $error_message =
                    "Something went wrong. Please try again.";

            }


            $stmt->close();

        } catch (Exception $e) {

            $error_message =
                "Unable to submit your message right now. Please try again later.";

        }

    }

}


// =========================================================
// HEADER
// =========================================================

require_once "includes/header.php";

?>

<style>

/* =========================================================
   CONTACT PAGE
   MEDICINE AAPKI GAW MEIN
========================================================= */

.contact-page {

    overflow: hidden;

    --green: #159447;

    --dark-green: #087333;

    --light-green: #effaf3;

    --very-light: #f7faf8;

    --dark: #172b25;

    --text: #5f6e68;

    --border: #e4ece8;

}


/* =========================================================
   CONTACT HERO
========================================================= */

.contact-hero {

    position: relative;

    min-height: 390px;

    display: flex;

    align-items: center;

    background:
        linear-gradient(
            90deg,
            rgba(4,35,21,.91),
            rgba(6,65,35,.70),
            rgba(6,50,28,.30)
        ),
        url('assets/images/hero_1.jpg')
        center center / cover no-repeat;

}


.contact-hero-content {

    max-width: 720px;

    padding: 60px 0;

}


.contact-badge {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 9px 16px;

    margin-bottom: 18px;

    border-radius: 50px;

    background: rgba(255,255,255,.12);

    border: 1px solid rgba(255,255,255,.22);

    color: #fff;

    font-size: 10px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: 1.3px;

}


.contact-hero h1 {

    color: #fff;

    font-size: 52px;

    line-height: 1.12;

    font-weight: 900;

    margin-bottom: 17px;

}


.contact-hero p {

    max-width: 620px;

    color: rgba(255,255,255,.86);

    font-size: 15px;

    line-height: 1.85;

    margin: 0;

}


/* =========================================================
   CONTACT INFO CARDS
========================================================= */

.contact-info-wrapper {

    margin-top: -45px;

    position: relative;

    z-index: 5;

}


.contact-info-card {

    height: 100%;

    padding: 25px 20px;

    display: flex;

    align-items: center;

    gap: 15px;

    background: #fff;

    border: 1px solid var(--border);

    border-radius: 16px;

    box-shadow:
        0 14px 40px rgba(17,58,38,.09);

    transition: .3s ease;

}


.contact-info-card:hover {

    transform: translateY(-6px);

    box-shadow:
        0 18px 45px rgba(17,58,38,.13);

}


.contact-info-icon {

    width: 52px;

    height: 52px;

    min-width: 52px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 14px;

    background: var(--light-green);

    color: var(--green);

    font-size: 22px;

}


.contact-info-content span {

    display: block;

    color: #819089;

    font-size: 9px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .8px;

    margin-bottom: 4px;

}


.contact-info-content strong {

    display: block;

    color: var(--dark);

    font-size: 14px;

    font-weight: 800;

    line-height: 1.5;

}


.contact-info-content a {

    color: var(--dark);

    text-decoration: none;

}


.contact-info-content a:hover {

    color: var(--green);

}


/* =========================================================
   MAIN CONTACT SECTION
========================================================= */

.contact-section {

    padding: 85px 0;

}


.contact-section-title {

    margin-bottom: 35px;

}


.contact-mini-title {

    display: inline-block;

    color: var(--green);

    font-size: 10px;

    font-weight: 900;

    text-transform: uppercase;

    letter-spacing: 1.5px;

    margin-bottom: 9px;

}


.contact-section-title h2 {

    color: var(--dark);

    font-size: 34px;

    font-weight: 900;

    line-height: 1.25;

    margin-bottom: 12px;

}


.contact-section-title p {

    color: var(--text);

    font-size: 13px;

    line-height: 1.8;

}


/* =========================================================
   CONTACT FORM
========================================================= */

.contact-form-card {

    padding: 35px;

    background: #fff;

    border: 1px solid var(--border);

    border-radius: 20px;

    box-shadow:
        0 12px 40px rgba(0,0,0,.055);

}


.contact-form-card h3 {

    color: var(--dark);

    font-size: 21px;

    font-weight: 900;

    margin-bottom: 8px;

}


.contact-form-card .form-intro {

    color: var(--text);

    font-size: 12px;

    line-height: 1.7;

    margin-bottom: 25px;

}


.contact-form-card label {

    color: #35453e;

    font-size: 11px;

    font-weight: 800;

    margin-bottom: 7px;

}


.contact-form-card .form-control {

    min-height: 48px;

    border: 1px solid #dfe8e3;

    border-radius: 9px;

    background: #fbfdfc;

    color: var(--dark);

    font-size: 12px;

    box-shadow: none;

    transition: .2s ease;

}


.contact-form-card textarea.form-control {

    min-height: 130px;

    resize: vertical;

}


.contact-form-card .form-control:focus {

    border-color: var(--green);

    background: #fff;

    box-shadow:
        0 0 0 3px rgba(21,148,71,.09);

}


.contact-submit {

    min-height: 48px;

    padding: 0 25px;

    border: 0;

    border-radius: 9px;

    background:
        linear-gradient(
            135deg,
            var(--green),
            var(--dark-green)
        );

    color: #fff;

    font-size: 12px;

    font-weight: 800;

    transition: .25s ease;

}


.contact-submit:hover {

    transform: translateY(-2px);

    box-shadow:
        0 10px 25px rgba(8,115,51,.20);

}


/* =========================================================
   RIGHT INFORMATION PANEL
========================================================= */

.contact-side {

    height: 100%;

}


.contact-side-card {

    height: 100%;

    padding: 35px;

    border-radius: 20px;

    background:
        linear-gradient(
            145deg,
            #f1faf4,
            #ffffff
        );

    border: 1px solid var(--border);

}


.contact-side-card h3 {

    color: var(--dark);

    font-size: 23px;

    font-weight: 900;

    margin-bottom: 12px;

}


.contact-side-card > p {

    color: var(--text);

    font-size: 13px;

    line-height: 1.8;

    margin-bottom: 25px;

}


/* =========================================================
   DETAIL ROW
========================================================= */

.contact-detail {

    display: flex;

    gap: 13px;

    padding: 17px 0;

    border-bottom: 1px solid #e2ebe6;

}


.contact-detail:last-child {

    border-bottom: 0;

}


.contact-detail-icon {

    width: 40px;

    height: 40px;

    min-width: 40px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 11px;

    background: #fff;

    color: var(--green);

    font-size: 17px;

    box-shadow:
        0 4px 15px rgba(0,0,0,.04);

}


.contact-detail-content span {

    display: block;

    color: #7c8b84;

    font-size: 9px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .7px;

    margin-bottom: 3px;

}


.contact-detail-content strong {

    color: var(--dark);

    font-size: 13px;

    font-weight: 800;

    line-height: 1.6;

}


.contact-detail-content p {

    color: var(--text);

    font-size: 11px;

    line-height: 1.6;

    margin: 2px 0 0;

}


/* =========================================================
   SERVICE AREA
========================================================= */

.service-area {

    margin-top: 25px;

    padding: 20px;

    border-radius: 13px;

    background: #fff;

    border: 1px solid #e2ebe6;

}


.service-area-title {

    display: flex;

    align-items: center;

    gap: 8px;

    color: var(--dark);

    font-size: 12px;

    font-weight: 900;

    margin-bottom: 7px;

}


.service-area-title span {

    width: 27px;

    height: 27px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 8px;

    background: var(--light-green);

    color: var(--green);

}


.service-area p {

    color: var(--text);

    font-size: 11px;

    line-height: 1.7;

    margin: 0;

}


/* =========================================================
   MAP / LOCATION AREA
========================================================= */

.location-section {

    padding: 75px 0;

    background: var(--very-light);

}


.location-box {

    min-height: 330px;

    position: relative;

    overflow: hidden;

    border-radius: 22px;

    background:
        linear-gradient(
            135deg,
            #0d7c3b,
            #159447
        );

    box-shadow:
        0 18px 45px rgba(8,115,51,.13);

}


.location-pattern {

    position: absolute;

    inset: 0;

    opacity: .09;

    background-image:
        linear-gradient(
            rgba(255,255,255,.6) 1px,
            transparent 1px
        ),
        linear-gradient(
            90deg,
            rgba(255,255,255,.6) 1px,
            transparent 1px
        );

    background-size: 35px 35px;

}


.location-content {

    position: relative;

    z-index: 2;

    min-height: 330px;

    padding: 45px;

    display: flex;

    flex-direction: column;

    justify-content: center;

}


.location-pin {

    width: 62px;

    height: 62px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 18px;

    background: rgba(255,255,255,.14);

    color: #fff;

    font-size: 28px;

    margin-bottom: 18px;

}


.location-content h3 {

    color: #fff;

    font-size: 29px;

    font-weight: 900;

    margin-bottom: 10px;

}


.location-content p {

    max-width: 600px;

    color: rgba(255,255,255,.83);

    font-size: 13px;

    line-height: 1.8;

    margin-bottom: 20px;

}


.location-button {

    display: inline-flex;

    align-items: center;

    width: fit-content;

    padding: 12px 21px;

    border-radius: 8px;

    background: #fff;

    color: var(--dark-green) !important;

    font-size: 11px;

    font-weight: 900;

    text-decoration: none !important;

    transition: .25s ease;

}


.location-button:hover {

    transform: translateY(-3px);

    box-shadow:
        0 10px 25px rgba(0,0,0,.18);

}


/* =========================================================
   WHY CONTACT US
========================================================= */

.contact-reasons {

    padding: 80px 0;

}


.reason-card {

    height: 100%;

    padding: 27px 22px;

    text-align: center;

    border: 1px solid var(--border);

    border-radius: 17px;

    background: #fff;

    transition: .3s ease;

}


.reason-card:hover {

    transform: translateY(-6px);

    box-shadow:
        0 14px 35px rgba(0,0,0,.07);

}


.reason-icon {

    width: 55px;

    height: 55px;

    margin: 0 auto 15px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 15px;

    background: var(--light-green);

    color: var(--green);

    font-size: 23px;

}


.reason-card h3 {

    color: var(--dark);

    font-size: 16px;

    font-weight: 900;

    margin-bottom: 8px;

}


.reason-card p {

    color: var(--text);

    font-size: 11px;

    line-height: 1.7;

    margin: 0;

}


/* =========================================================
   FINAL CTA
========================================================= */

.contact-cta {

    padding: 0 0 75px;

}


.contact-cta-box {

    padding: 50px;

    border-radius: 23px;

    background:
        linear-gradient(
            135deg,
            #0c7737,
            #159447
        );

    text-align: center;

}


.contact-cta-box h2 {

    color: #fff;

    font-size: 30px;

    font-weight: 900;

    margin-bottom: 10px;

}


.contact-cta-box p {

    max-width: 650px;

    margin: 0 auto;

    color: rgba(255,255,255,.83);

    font-size: 13px;

    line-height: 1.8;

}


.contact-cta-buttons {

    display: flex;

    justify-content: center;

    flex-wrap: wrap;

    gap: 10px;

    margin-top: 23px;

}


.contact-cta-btn {

    padding: 13px 23px;

    border-radius: 8px;

    font-size: 11px;

    font-weight: 900;

    text-decoration: none !important;

}


.contact-cta-primary {

    background: #fff;

    color: var(--dark-green) !important;

}


.contact-cta-secondary {

    border: 1px solid rgba(255,255,255,.35);

    color: #fff !important;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 991px) {

    .contact-hero h1 {

        font-size: 43px;

    }


    .contact-section {

        padding: 65px 0;

    }


    .contact-side {

        margin-top: 30px;

    }

}


@media (max-width: 767px) {

    .contact-hero {

        min-height: 390px;

        text-align: center;

    }


    .contact-hero-content {

        padding: 45px 15px;

    }


    .contact-hero h1 {

        font-size: 34px;

    }


    .contact-hero p {

        font-size: 13px;

    }


    .contact-info-wrapper {

        margin-top: 0;

        padding-top: 25px;

    }


    .contact-info-card {

        margin-bottom: 15px;

    }


    .contact-section-title h2 {

        font-size: 28px;

    }


    .contact-form-card,
    .contact-side-card {

        padding: 25px 20px;

    }


    .location-content {

        padding: 35px 25px;

    }


    .location-content h3 {

        font-size: 25px;

    }


    .contact-cta-box {

        padding: 38px 23px;

    }


    .contact-cta-box h2 {

        font-size: 25px;

    }


    .contact-cta-buttons {

        flex-direction: column;

    }

}


@media (max-width: 480px) {

    .contact-hero h1 {

        font-size: 30px;

    }


    .contact-info-card {

        padding: 20px 16px;

    }


    .contact-submit {

        width: 100%;

    }

}

</style>

<div class="contact-page">

<!-- =========================================================
     HERO
========================================================= -->

<section class="contact-hero">

```
<div class="container">

    <div class="contact-hero-content">

        <div class="contact-badge">

            <span>●</span>

            Medicine Aapki Gaw Mein

        </div>


        <h1>

            We’re here to
            <br>
            help you

        </h1>


        <p>

            Medicine, prescription ya website se related
            koi bhi query ho, humse contact karein.
            Aapki requirement ko simple aur convenient
            banane ke liye hum yahan hain.

        </p>

    </div>

</div>
```

</section>

<!-- =========================================================
     QUICK CONTACT CARDS
========================================================= -->

<section class="contact-info-wrapper">

```
<div class="container">

    <div class="row">


        <!-- PHONE -->

        <div class="col-md-4 mb-3">

            <div class="contact-info-card">

                <div class="contact-info-icon">

                    ☎

                </div>


                <div class="contact-info-content">

                    <span>
                        Call Us
                    </span>

                    <strong>

                        <a href="tel:+919999999999">

                            +91 99999 99999

                        </a>

                    </strong>

                </div>

            </div>

        </div>


        <!-- EMAIL -->

        <div class="col-md-4 mb-3">

            <div class="contact-info-card">

                <div class="contact-info-icon">

                    ✉

                </div>


                <div class="contact-info-content">

                    <span>
                        Email Us
                    </span>

                    <strong>

                        <a href="mailto:info@medicineaapkigawmein.com">

                            info@medicineaapkigawmein.com

                        </a>

                    </strong>

                </div>

            </div>

        </div>


        <!-- LOCATION -->

        <div class="col-md-4 mb-3">

            <div class="contact-info-card">

                <div class="contact-info-icon">

                    📍

                </div>


                <div class="contact-info-content">

                    <span>
                        Service Area
                    </span>

                    <strong>

                        Forbesganj, Bihar

                    </strong>

                </div>

            </div>

        </div>


    </div>

</div>
```

</section>

<!-- =========================================================
     CONTACT FORM + DETAILS
========================================================= -->

<section class="contact-section">

```
<div class="container">

    <div class="row">


        <!-- =================================================
             FORM
        ================================================= -->

        <div class="col-lg-7">

            <div class="contact-form-card">

                        <?php if ($success_message !== ''): ?>

                            <div class="alert alert-success">
                                ✓ <?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?>
                            </div>

                        <?php endif; ?>


                        <?php if ($error_message !== ''): ?>

                            <div class="alert alert-danger">
                                ⚠ <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                            </div>

                        <?php endif; ?>


                <div class="contact-section-title">

                    <span class="contact-mini-title">

                        Send Us A Message

                    </span>


                    <h2>

                        How can we help?

                    </h2>


                    <p>

                        Apni query ya requirement neeche
                        submit karein. Required details
                        provide karne ki koshish karein.

                    </p>

                </div>


                <form
                    action="contact.php"
                    method="POST"
                >

                    <div class="row">


                        <!-- NAME -->

                        <div class="col-md-6 mb-3">

                            <label for="name">

                                Your Name

                            </label>


                            <input
                                type="text"
                                name="name"
                                id="name"
                                class="form-control"
                                placeholder="Enter your name"
                                required
                            >

                        </div>


                        <!-- PHONE -->

                        <div class="col-md-6 mb-3">

                            <label for="phone">

                                Phone Number

                            </label>


                            <input
                                type="tel"
                                name="phone"
                                id="phone"
                                class="form-control"
                                placeholder="Enter phone number"
                                required
                            >

                        </div>


                        <!-- EMAIL -->

                        <div class="col-md-6 mb-3">

                            <label for="email">

                                Email Address

                            </label>


                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="form-control"
                                placeholder="Enter email address"
                            >

                        </div>


                        <!-- SUBJECT -->

                        <div class="col-md-6 mb-3">

                            <label for="subject">

                                Subject

                            </label>


                            <select
                                name="subject"
                                id="subject"
                                class="form-control"
                                required
                            >

                                <option value="">

                                    Select query type

                                </option>

                                <option value="Medicine Query">

                                    Medicine Query

                                </option>

                                <option value="Prescription">

                                    Prescription

                                </option>

                                <option value="Order">

                                    Order Related

                                </option>

                                <option value="Delivery">

                                    Delivery Related

                                </option>

                                <option value="General">

                                    General Query

                                </option>

                                <option value="Other">

                                    Other

                                </option>

                            </select>

                        </div>


                        <!-- MESSAGE -->

                        <div class="col-12 mb-3">

                            <label for="message">

                                Message

                            </label>


                            <textarea
                                name="message"
                                id="message"
                                class="form-control"
                                placeholder="Write your message..."
                                required
                            ></textarea>

                        </div>


                        <!-- SUBMIT -->

                        <div class="col-12 mt-2">

                            <button
                                type="submit"
                                class="contact-submit"
                            >

                                Send Message

                                <span class="ml-2">
                                    →
                                </span>

                            </button>

                        </div>


                    </div>

                </form>

            </div>

        </div>


        <!-- =================================================
             CONTACT DETAILS
        ================================================= -->

        <div class="col-lg-5">

            <div class="contact-side">

                <div class="contact-side-card">


                    <span class="contact-mini-title">

                        Get In Touch

                    </span>


                    <h3>

                        Let’s talk about
                        your requirement

                    </h3>


                    <p>

                        Medicine ya healthcare requirement
                        se related assistance ke liye
                        humse contact karein.

                    </p>


                    <!-- ADDRESS -->

                    <div class="contact-detail">

                        <div class="contact-detail-icon">

                            📍

                        </div>


                        <div class="contact-detail-content">

                            <span>
                                Location
                            </span>

                            <strong>

                                Forbesganj, Bihar, India

                            </strong>

                            <p>

                                Local healthcare service area

                            </p>

                        </div>

                    </div>


                    <!-- PHONE -->

                    <div class="contact-detail">

                        <div class="contact-detail-icon">

                            ☎

                        </div>


                        <div class="contact-detail-content">

                            <span>
                                Phone
                            </span>

                            <strong>

                                <a
                                    href="tel:+919999999999"
                                    style="
                                        color:inherit;
                                        text-decoration:none;
                                    "
                                >

                                    +91 99999 99999

                                </a>

                            </strong>

                            <p>

                                Customer support

                            </p>

                        </div>

                    </div>


                    <!-- EMAIL -->

                    <div class="contact-detail">

                        <div class="contact-detail-icon">

                            ✉

                        </div>


                        <div class="contact-detail-content">

                            <span>
                                Email
                            </span>

                            <strong>

                                <a
                                    href="mailto:info@medicineaapkigawmein.com"
                                    style="
                                        color:inherit;
                                        text-decoration:none;
                                    "
                                >

                                    info@medicineaapkigawmein.com

                                </a>

                            </strong>

                            <p>

                                Send us your query anytime

                            </p>

                        </div>

                    </div>


                    <!-- SERVICE AREA -->

                    <div class="service-area">

                        <div class="service-area-title">

                            <span>

                                🏠

                            </span>

                            Local Service

                        </div>


                        <p>

                            Forbesganj aur aas-paas ke
                            areas ke customers ke liye
                            convenient medicine support.

                        </p>

                    </div>


                </div>

            </div>

        </div>


    </div>

</div>
```

</section>

<!-- =========================================================
     LOCATION CTA
========================================================= -->

<section class="location-section">

```
<div class="container">

    <div class="location-box">

        <div class="location-pattern"></div>


        <div class="location-content">

            <div class="location-pin">

                📍

            </div>


            <h3>

                Serving Forbesganj & Nearby Areas

            </h3>


            <p>

                Medicine Aapki Gaw Mein local customers
                ke liye medicines aur healthcare support
                ko convenient banane ke liye designed hai.

            </p>


            <a
                href="medicines.php"
                class="location-button"
            >

                Explore Medicines

                <span class="ml-2">
                    →
                </span>

            </a>

        </div>

    </div>

</div>
```

</section>

<!-- =========================================================
     WHY CONTACT US
========================================================= -->

<section class="contact-reasons">

```
<div class="container">


    <div class="contact-section-title text-center">

        <span class="contact-mini-title">

            Need Assistance?

        </span>


        <h2>

            We can help with

        </h2>


        <p>

            Apni requirement ke according
            right option choose karein.

        </p>

    </div>


    <div class="row">


        <!-- MEDICINE -->

        <div class="col-md-6 col-lg-3 mb-4">

            <div class="reason-card">

                <div class="reason-icon">

                    💊

                </div>


                <h3>

                    Medicine Query

                </h3>


                <p>

                    Medicine availability ya
                    product related query.

                </p>

            </div>

        </div>


        <!-- PRESCRIPTION -->

        <div class="col-md-6 col-lg-3 mb-4">

            <div class="reason-card">

                <div class="reason-icon">

                    📄

                </div>


                <h3>

                    Prescription

                </h3>


                <p>

                    Prescription upload aur
                    medicine requirement related help.

                </p>

            </div>

        </div>


        <!-- ORDER -->

        <div class="col-md-6 col-lg-3 mb-4">

            <div class="reason-card">

                <div class="reason-icon">

                    🛒

                </div>


                <h3>

                    Order Support

                </h3>


                <p>

                    Medicine order aur
                    requirement related assistance.

                </p>

            </div>

        </div>


        <!-- GENERAL -->

        <div class="col-md-6 col-lg-3 mb-4">

            <div class="reason-card">

                <div class="reason-icon">

                    💬

                </div>


                <h3>

                    General Support

                </h3>


                <p>

                    Website ya general healthcare
                    related questions.

                </p>

            </div>

        </div>


    </div>

</div>
```

</section>

<!-- =========================================================
     FINAL CTA
========================================================= -->

<section class="contact-cta">

```
<div class="container">

    <div class="contact-cta-box">

        <h2>

            Need Medicine Right Now?

        </h2>


        <p>

            Medicine search karein ya prescription
            upload karke apni requirement submit karein.

        </p>


        <div class="contact-cta-buttons">

            <a
                href="medicines.php"
                class="
                    contact-cta-btn
                    contact-cta-primary
                "
            >

                Browse Medicines

                →

            </a>


            <a
                href="upload-prescription.php"
                class="
                    contact-cta-btn
                    contact-cta-secondary
                "
            >

                📄

                Upload Prescription

            </a>

        </div>

    </div>

</div>


</section>

</div>

<?php

require_once "includes/footer.php";

?>
