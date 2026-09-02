
<!-- =========================================================
     PREMIUM FOOTER
========================================================= -->

<footer class="premium-footer">

    <!-- Top CTA -->
    <div class="footer-cta-wrap">

        <div class="container">

            <div class="footer-cta">

                <div class="footer-cta-icon">
                    💊
                </div>

                <div class="footer-cta-content">

                    <h3>
                        Need Medicines at Your Doorstep?
                    </h3>

                    <p>
                        Order your medicines easily and get them delivered
                        at your doorstep.
                    </p>

                </div>

                <div class="footer-cta-action">

                    <a href="medicines.php" class="footer-shop-btn">
                        Shop Medicines
                        <span>→</span>
                    </a>

                </div>

            </div>

        </div>

    </div>


    <!-- Main Footer -->
    <div class="premium-footer-main">

        <div class="container">

            <div class="row g-5">


                <!-- =================================================
                     BRAND
                ================================================== -->

                <div class="col-xl-4 col-lg-4 col-md-6">

                    <div class="footer-brand-box">

                        <a href="index.php" class="footer-brand">

                            <div class="footer-brand-icon">
                                💊
                            </div>

                            <div class="footer-brand-text">

                                <strong>
                                    Medicine Aapki Gaw Mein
                                </strong>

                                <span>
                                    Your Trusted Medicine Partner
                                </span>

                            </div>

                        </a>


                        <p class="footer-description">

                            Your trusted local medicine delivery service
                            in Forbesganj. Get genuine medicines,
                            convenient ordering and doorstep delivery
                            with ease.

                        </p>


                        <!-- Trust Points -->

                        <div class="footer-trust-list">

                            <div class="footer-trust-item">
                                <span>✓</span>
                                <label>Genuine Medicines</label>
                            </div>

                            <div class="footer-trust-item">
                                <span>✓</span>
                                <label>Easy & Secure Ordering</label>
                            </div>

                            <div class="footer-trust-item">
                                <span>✓</span>
                                <label>Doorstep Delivery</label>
                            </div>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     QUICK LINKS
                ================================================== -->

                <div class="col-xl-2 col-lg-2 col-md-6">

                    <div class="footer-column">

                        <h4>
                            Quick Links
                        </h4>

                        <div class="footer-heading-line"></div>

                        <ul>

                            <li>
                                <a href="index.php">
                                    <span>›</span>
                                    Home
                                </a>
                            </li>

                            <li>
                                <a href="medicines.php">
                                    <span>›</span>
                                    Medicines
                                </a>
                            </li>

                            <li>
                                <a href="cart.php">
                                    <span>›</span>
                                    My Cart
                                </a>
                            </li>

                            <li>
                                <a href="contact.php">
                                    <span>›</span>
                                    Contact Us
                                </a>
                            </li>

                        </ul>

                    </div>

                </div>


                <!-- =================================================
                     CUSTOMER
                ================================================== -->

                <div class="col-xl-2 col-lg-2 col-md-6">

                    <div class="footer-column">

                        <h4>
                            Customer
                        </h4>

                        <div class="footer-heading-line"></div>

                        <ul>

                            <?php if (isset($_SESSION['user_id'])): ?>

                                <li>
                                    <a href="customer/index.php">
                                        <span>›</span>
                                        Dashboard
                                    </a>
                                </li>

                                <li>
                                    <a href="customer/orders.php">
                                        <span>›</span>
                                        My Orders
                                    </a>
                                </li>

                                <li>
                                    <a href="customer/profile.php">
                                        <span>›</span>
                                        My Profile
                                    </a>
                                </li>

                                <li>
                                    <a href="logout.php">
                                        <span>›</span>
                                        Logout
                                    </a>
                                </li>

                            <?php else: ?>

                                <li>
                                    <a href="login.php">
                                        <span>›</span>
                                        Login
                                    </a>
                                </li>

                                <li>
                                    <a href="register.php">
                                        <span>›</span>
                                        Create Account
                                    </a>
                                </li>

                            <?php endif; ?>

                        </ul>


                        <!-- Delivery Partner -->

                        <a href="delivery/login.php"
                           class="delivery-partner-link">

                            <span class="delivery-partner-icon">
                                🚚
                            </span>

                            <span>
                                Delivery Partner
                                <small>Login here →</small>
                            </span>

                        </a>

                    </div>

                </div>


                <!-- =================================================
                     CONTACT
                ================================================== -->

                <div class="col-xl-4 col-lg-4 col-md-6">

                    <div class="footer-column">

                        <h4>
                            Contact Us
                        </h4>

                        <div class="footer-heading-line"></div>


                        <!-- Location -->

                        <div class="footer-contact-card">

                            <div class="footer-contact-icon">
                                📍
                            </div>

                            <div>

                                <span>
                                    Our Location
                                </span>

                                <p>
                                    Forbesganj, Bihar, India
                                </p>

                            </div>

                        </div>


                        <!-- Phone -->

                        <div class="footer-contact-card">

                            <div class="footer-contact-icon">
                                📞
                            </div>

                            <div>

                                <span>
                                    Call Us
                                </span>

                                <p>

                                    <a href="tel:+91XXXXXXXXXX">
                                        +91 XXXXXXXXXX
                                    </a>

                                </p>

                            </div>

                        </div>


                        <!-- Email -->

                        <div class="footer-contact-card">

                            <div class="footer-contact-icon">
                                ✉️
                            </div>

                            <div>

                                <span>
                                    Email Us
                                </span>

                                <p>

                                    <a href="mailto:support@medicineaapki.in">
                                        support@medicineaapki.in
                                    </a>

                                </p>

                            </div>

                        </div>


                        <!-- Working -->

                        <div class="footer-contact-card">

                            <div class="footer-contact-icon">
                                🕐
                            </div>

                            <div>

                                <span>
                                    Service Hours
                                </span>

                                <p>
                                    Mon - Sun · 9:00 AM - 9:00 PM
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 TRUST STRIP
            ================================================== -->

            <div class="footer-trust-strip">

                <div class="footer-trust-box">

                    <div class="footer-trust-big-icon">
                        🛡️
                    </div>

                    <div>
                        <strong>
                            Safe & Secure
                        </strong>

                        <span>
                            Secure ordering experience
                        </span>
                    </div>

                </div>


                <div class="footer-trust-box">

                    <div class="footer-trust-big-icon">
                        💊
                    </div>

                    <div>
                        <strong>
                            Genuine Medicines
                        </strong>

                        <span>
                            Quality medicines
                        </span>
                    </div>

                </div>


                <div class="footer-trust-box">

                    <div class="footer-trust-big-icon">
                        🚚
                    </div>

                    <div>
                        <strong>
                            Doorstep Delivery
                        </strong>

                        <span>
                            Delivered to your door
                        </span>
                    </div>

                </div>


                <div class="footer-trust-box">

                    <div class="footer-trust-big-icon">
                        🤝
                    </div>

                    <div>
                        <strong>
                            Customer Support
                        </strong>

                        <span>
                            We're here to help
                        </span>
                    </div>

                </div>

            </div>


            <!-- =================================================
                 BOTTOM
            ================================================== -->

            <div class="footer-bottom">

                <div class="footer-copyright">

                    ©
                    <script>
                        document.write(
                            new Date().getFullYear()
                        );
                    </script>

                    <strong>
                        Medicine Aapki Gaw Mein
                    </strong>

                    <span>
                        · All Rights Reserved
                    </span>

                </div>


                <div class="footer-policies">

                    <a href="privacy-policy.php">
                        Privacy Policy
                    </a>

                    <a href="terms.php">
                        Terms & Conditions
                    </a>

                    <a href="delivery-policy.php">
                        Delivery Policy
                    </a>

                </div>

            </div>

        </div>

    </div>

</footer>


<!-- =========================================================
     PREMIUM FOOTER CSS
========================================================= -->

<style>

/* =========================================================
   FOOTER BASE
========================================================= */

.premium-footer {
    position: relative;
    margin-top: 70px;
    font-family: 'Rubik', sans-serif;
}


/* =========================================================
   CTA
========================================================= */

.footer-cta-wrap {
    position: relative;
    z-index: 5;
    margin-bottom: -45px;
}

.footer-cta {
    min-height: 125px;

    padding: 25px 35px;

    display: flex;
    align-items: center;

    gap: 22px;

    border-radius: 18px;

    background:
        linear-gradient(
            135deg,
            #ffffff 0%,
            #f5fff7 100%
        );

    border: 1px solid #e0eee3;

    box-shadow:
        0 15px 45px rgba(24, 105, 43, .14);
}

.footer-cta-icon {

    width: 70px;
    height: 70px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 18px;

    background:
        linear-gradient(
            135deg,
            #238b39,
            #51b848
        );

    box-shadow:
        0 10px 25px rgba(35, 139, 57, .25);

    font-size: 32px;
}

.footer-cta-content {
    flex: 1;
}

.footer-cta-content h3 {

    margin: 0 0 5px;

    color: #173b22;

    font-size: 21px;
    font-weight: 700;
}

.footer-cta-content p {

    margin: 0;

    color: #66776b;

    font-size: 13px;
}

.footer-shop-btn {

    display: inline-flex;

    align-items: center;

    gap: 12px;

    padding: 13px 21px;

    color: #fff !important;

    background:
        linear-gradient(
            135deg,
            #238b39,
            #51b848
        );

    border-radius: 10px;

    text-decoration: none !important;

    font-size: 13px;
    font-weight: 600;

    box-shadow:
        0 8px 20px rgba(35,139,57,.22);

    transition: all .25s ease;
}

.footer-shop-btn span {
    font-size: 18px;
}

.footer-shop-btn:hover {

    color: #fff !important;

    transform: translateY(-2px);

    box-shadow:
        0 12px 25px rgba(35,139,57,.3);
}


/* =========================================================
   MAIN FOOTER
========================================================= */

.premium-footer-main {

    padding-top: 105px;

    background:
        radial-gradient(
            circle at 85% 10%,
            rgba(81,184,72,.16),
            transparent 30%
        ),
        linear-gradient(
            135deg,
            #0d4720 0%,
            #176b2d 50%,
            #0c421d 100%
        );

    color: #d8eadc;
}


/* =========================================================
   BRAND
========================================================= */

.footer-brand {

    display: inline-flex;

    align-items: center;

    gap: 13px;

    text-decoration: none !important;

    margin-bottom: 20px;
}

.footer-brand-icon {

    width: 53px;
    height: 53px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 15px;

    background: rgba(255,255,255,.13);

    border: 1px solid rgba(255,255,255,.18);

    font-size: 27px;

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.12);
}

.footer-brand-text strong {

    display: block;

    color: #fff;

    font-size: 18px;
    font-weight: 700;
}

.footer-brand-text span {

    display: block;

    margin-top: 3px;

    color: #a9d5b1;

    font-size: 10px;

    letter-spacing: .4px;
}

.footer-description {

    max-width: 390px;

    margin: 0 0 20px;

    color: #c0dbc5;

    font-size: 13px;

    line-height: 1.9;
}


/* =========================================================
   TRUST LIST
========================================================= */

.footer-trust-list {

    display: flex;

    flex-direction: column;

    gap: 9px;
}

.footer-trust-item {

    display: flex;

    align-items: center;

    gap: 9px;

    color: #d9edde;

    font-size: 12px;
}

.footer-trust-item span {

    width: 19px;
    height: 19px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: rgba(184,230,191,.15);

    color: #b8e6bf;

    font-size: 11px;
}


/* =========================================================
   COLUMN
========================================================= */

.footer-column h4 {

    margin: 3px 0 8px;

    color: #fff;

    font-size: 15px;

    font-weight: 700;
}

.footer-heading-line {

    width: 32px;
    height: 3px;

    margin-bottom: 22px;

    border-radius: 10px;

    background: #6ecb75;
}


/* =========================================================
   LINKS
========================================================= */

.footer-column ul {

    padding: 0;
    margin: 0;

    list-style: none;
}

.footer-column ul li {

    margin-bottom: 12px;
}

.footer-column ul li a {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    color: #c4ddc9;

    text-decoration: none;

    font-size: 13px;

    transition: all .2s ease;
}

.footer-column ul li a span {

    color: #73c77b;

    font-size: 18px;

    line-height: 1;
}

.footer-column ul li a:hover {

    color: #fff;

    transform: translateX(4px);
}


/* =========================================================
   DELIVERY PARTNER
========================================================= */

.delivery-partner-link {

    display: flex;

    align-items: center;

    gap: 10px;

    margin-top: 23px;

    padding: 10px 11px;

    color: #fff !important;

    text-decoration: none !important;

    border-radius: 11px;

    background: rgba(255,255,255,.07);

    border: 1px solid rgba(255,255,255,.1);

    transition: all .25s ease;
}

.delivery-partner-link:hover {

    background: rgba(255,255,255,.12);

    transform: translateY(-2px);
}

.delivery-partner-icon {

    width: 32px;
    height: 32px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 8px;

    background: rgba(255,255,255,.1);

    font-size: 15px;
}

.delivery-partner-link span:last-child {

    font-size: 11px;
    font-weight: 600;
}

.delivery-partner-link small {

    display: block;

    margin-top: 2px;

    color: #a8d5af;

    font-size: 9px;
    font-weight: 400;
}


/* =========================================================
   CONTACT
========================================================= */

.footer-contact-card {

    display: flex;

    align-items: center;

    gap: 11px;

    margin-bottom: 13px;

    padding: 9px;

    border-radius: 10px;

    transition: background .2s ease;
}

.footer-contact-card:hover {

    background: rgba(255,255,255,.05);
}

.footer-contact-icon {

    width: 36px;
    height: 36px;

    flex-shrink: 0;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 9px;

    background: rgba(255,255,255,.09);

    font-size: 15px;
}

.footer-contact-card span {

    display: block;

    margin-bottom: 2px;

    color: #8fc89a;

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: .5px;
}

.footer-contact-card p {

    margin: 0;

    color: #d2e5d5;

    font-size: 12px;
}

.footer-contact-card a {

    color: #d2e5d5;

    text-decoration: none;
}

.footer-contact-card a:hover {

    color: #fff;
}


/* =========================================================
   TRUST STRIP
========================================================= */

.footer-trust-strip {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 12px;

    margin-top: 48px;

    padding: 16px 0;

    border-top:
        1px solid rgba(255,255,255,.09);

    border-bottom:
        1px solid rgba(255,255,255,.09);
}

.footer-trust-box {

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 8px 12px;

    border-radius: 10px;

    transition: background .2s ease;
}

.footer-trust-box:hover {

    background: rgba(255,255,255,.05);
}

.footer-trust-big-icon {

    width: 38px;
    height: 38px;

    flex-shrink: 0;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 10px;

    background: rgba(255,255,255,.08);

    font-size: 17px;
}

.footer-trust-box strong {

    display: block;

    color: #fff;

    font-size: 11px;
}

.footer-trust-box span {

    display: block;

    margin-top: 3px;

    color: #9fc7a7;

    font-size: 9px;
}


/* =========================================================
   FOOTER BOTTOM
========================================================= */

.footer-bottom {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    padding: 22px 0;

    color: #9fc5a6;

    font-size: 10px;
}

.footer-copyright strong {

    color: #d9ebdc;
}

.footer-policies {

    display: flex;

    align-items: center;

    gap: 20px;
}

.footer-policies a {

    color: #a8c9ae;

    text-decoration: none;

    transition: color .2s ease;
}

.footer-policies a:hover {

    color: #fff;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 991px) {

    .footer-cta {

        padding: 22px 25px;
    }

    .footer-cta-content h3 {

        font-size: 18px;
    }

    .footer-trust-strip {

        grid-template-columns:
            repeat(2, 1fr);
    }

}


@media (max-width: 767px) {

    .premium-footer {

        margin-top: 50px;
    }

    .footer-cta-wrap {

        margin-bottom: -35px;
    }

    .footer-cta {

        flex-wrap: wrap;

        padding: 20px;

        border-radius: 15px;
    }

    .footer-cta-icon {

        width: 55px;
        height: 55px;

        border-radius: 13px;

        font-size: 25px;
    }

    .footer-cta-content {

        flex: 1;

        min-width: 190px;
    }

    .footer-cta-content h3 {

        font-size: 16px;
    }

    .footer-cta-content p {

        font-size: 11px;
    }

    .footer-cta-action {

        width: 100%;
    }

    .footer-shop-btn {

        width: 100%;

        justify-content: center;
    }

    .premium-footer-main {

        padding-top: 80px;
    }

    .footer-description {

        max-width: 100%;
    }

    .footer-trust-strip {

        grid-template-columns: 1fr;

    }

    .footer-bottom {

        flex-direction: column;

        text-align: center;

        padding-bottom: 25px;
    }

    .footer-policies {

        flex-wrap: wrap;

        justify-content: center;

        gap: 8px 15px;
    }

}


@media (max-width: 480px) {

    .footer-cta {

        margin-left: 5px;
        margin-right: 5px;
    }

    .footer-brand-text strong {

        font-size: 16px;
    }

    .footer-brand-text span {

        font-size: 9px;
    }

}
</style>

