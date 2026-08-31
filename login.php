<?php

session_start();

require_once "config/database.php";

$error = "";


// =====================================================
// ALREADY LOGGED IN
// =====================================================

if (
    isset($_SESSION["user_id"]) &&
    (int) $_SESSION["user_id"] > 0
) {

    if (
        isset($_SESSION["redirect_after_login"]) &&
        $_SESSION["redirect_after_login"] !== ''
    ) {

        $redirect = $_SESSION["redirect_after_login"];

        unset($_SESSION["redirect_after_login"]);
        unset($_SESSION["login_required_message"]);

        $allowed_pages = [
            'checkout.php',
            'cart.php',
            'index.php'
        ];

        $redirect_file = basename(
            parse_url($redirect, PHP_URL_PATH)
        );

        if (in_array($redirect_file, $allowed_pages, true)) {

            header("Location: " . $redirect_file);
            exit;
        }
    }

    header("Location: index.php");
    exit;
}


// =====================================================
// LOGIN REQUIRED MESSAGE
// =====================================================

$login_required_message =
    $_SESSION["login_required_message"] ?? "";


// =====================================================
// LOGIN
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $mobile =
        trim($_POST["mobile"] ?? "");

    $password =
        $_POST["password"] ?? "";


    // =================================================
    // VALIDATION
    // =================================================

    if (
        $mobile === "" ||
        $password === ""
    ) {

        $error =
            "Mobile number aur password required hai.";

    } elseif (
        !preg_match('/^[0-9]{10}$/', $mobile)
    ) {

        $error =
            "Please valid 10-digit mobile number enter karein.";

    } else {

        // =================================================
        // FIND USER
        // =================================================

        $stmt = $conn->prepare("
            SELECT
                id,
                name,
                mobile,
                email,
                password,
                role,
                status
            FROM users
            WHERE mobile = ?
            LIMIT 1
        ");

        if (!$stmt) {

            $error =
                "Database error. Please try again.";

        } else {

            $stmt->bind_param(
                "s",
                $mobile
            );

            $stmt->execute();

            $result =
                $stmt->get_result();


            if ($result->num_rows === 1) {

                $user =
                    $result->fetch_assoc();


                // =============================================
                // ACCOUNT STATUS
                // =============================================

                if (
                    (int)$user["status"] !== 1
                ) {

                    $error =
                        "Aapka account inactive hai.";

                }

                // =============================================
                // PASSWORD
                // =============================================

                elseif (
                    !password_verify(
                        $password,
                        $user["password"]
                    )
                ) {

                    $error =
                        "Mobile number ya password galat hai.";

                }

                // =============================================
                // LOGIN SUCCESS
                // =============================================

                else {

                    session_regenerate_id(true);

                    $_SESSION["user_id"] =
                        (int)$user["id"];

                    $_SESSION["name"] =
                        $user["name"];

                    $_SESSION["mobile"] =
                        $user["mobile"];

                    $_SESSION["role"] =
                        $user["role"];


                    // =========================================
                    // REDIRECT AFTER LOGIN
                    // =========================================

                    if (
                        isset($_SESSION["redirect_after_login"]) &&
                        $_SESSION["redirect_after_login"] !== ''
                    ) {

                        $redirect =
                            $_SESSION["redirect_after_login"];

                        $allowed_pages = [
                            'checkout.php',
                            'cart.php',
                            'index.php'
                        ];

                        $redirect_file =
                            basename(
                                parse_url(
                                    $redirect,
                                    PHP_URL_PATH
                                )
                            );

                        unset(
                            $_SESSION["redirect_after_login"]
                        );

                        unset(
                            $_SESSION["login_required_message"]
                        );

                        if (
                            in_array(
                                $redirect_file,
                                $allowed_pages,
                                true
                            )
                        ) {

                            header(
                                "Location: " . $redirect_file
                            );

                            exit;
                        }
                    }


                    // =========================================
                    // ADMIN
                    // =========================================

                    if (
                        $user["role"] === "admin"
                    ) {

                        header(
                            "Location: admin/index.php"
                        );

                        exit;
                    }


                    // =========================================
                    // CUSTOMER
                    // =========================================

                    header(
                        "Location: index.php"
                    );

                    exit;
                }

            } else {

                $error =
                    "Mobile number ya password galat hai.";
            }

            $stmt->close();
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Login | Medicine Aapki Gaw Mein
</title>


<style>

/* =====================================================
   RESET
===================================================== */

* {
    box-sizing: border-box;
}

html,
body {
    margin: 0;
    padding: 0;
    min-height: 100%;
}


/* =====================================================
   BODY
===================================================== */

body {

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background:
        linear-gradient(
            135deg,
            #eef7ff 0%,
            #f8fbff 50%,
            #eefbf6 100%
        );

    color: #212529;
}


/* =====================================================
   PAGE
===================================================== */

.login-page {

    min-height: 100vh;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 30px 18px;

    position: relative;

    overflow: hidden;
}


/* =====================================================
   BACKGROUND DECORATION
===================================================== */

.login-page::before {

    content: "";

    position: absolute;

    width: 420px;
    height: 420px;

    border-radius: 50%;

    background:
        rgba(13, 110, 253, 0.08);

    top: -180px;
    left: -160px;
}

.login-page::after {

    content: "";

    position: absolute;

    width: 380px;
    height: 380px;

    border-radius: 50%;

    background:
        rgba(25, 135, 84, 0.07);

    bottom: -170px;
    right: -150px;
}


/* =====================================================
   MAIN CARD
===================================================== */

.login-card {

    width: 100%;

    max-width: 950px;

    min-height: 570px;

    background: #ffffff;

    border-radius: 24px;

    overflow: hidden;

    display: grid;

    grid-template-columns:
        0.95fr 1.05fr;

    box-shadow:
        0 25px 70px
        rgba(20, 50, 80, 0.13);

    position: relative;

    z-index: 2;
}


/* =====================================================
   LEFT BRAND PANEL
===================================================== */

.login-brand {

    background:
        linear-gradient(
            145deg,
            #0d6efd,
            #0757c9
        );

    color: #ffffff;

    padding: 55px 45px;

    display: flex;

    flex-direction: column;

    justify-content: center;

    position: relative;

    overflow: hidden;
}


.login-brand::before {

    content: "";

    position: absolute;

    width: 280px;
    height: 280px;

    border: 1px solid
        rgba(255,255,255,0.14);

    border-radius: 50%;

    right: -120px;
    top: -80px;
}


.login-brand::after {

    content: "";

    position: absolute;

    width: 220px;
    height: 220px;

    border: 1px solid
        rgba(255,255,255,0.10);

    border-radius: 50%;

    left: -110px;
    bottom: -90px;
}


/* =====================================================
   BRAND LOGO
===================================================== */

.brand-icon {

    width: 72px;
    height: 72px;

    border-radius: 20px;

    background:
        rgba(255,255,255,0.16);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 38px;

    margin-bottom: 28px;

    box-shadow:
        inset 0 0 0 1px
        rgba(255,255,255,0.15);
}


.login-brand h1 {

    margin: 0;

    font-size: 34px;

    line-height: 1.2;

    font-weight: 700;

    letter-spacing: -0.5px;
}


.login-brand p {

    margin:
        16px 0 30px;

    font-size: 15px;

    line-height: 1.7;

    color:
        rgba(255,255,255,0.85);

    max-width: 360px;
}


/* =====================================================
   FEATURES
===================================================== */

.feature {

    display: flex;

    align-items: center;

    gap: 12px;

    margin-bottom: 15px;

    font-size: 14px;

    color:
        rgba(255,255,255,0.92);
}


.feature-icon {

    width: 28px;
    height: 28px;

    border-radius: 50%;

    background:
        rgba(255,255,255,0.14);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 13px;
}


/* =====================================================
   RIGHT LOGIN PANEL
===================================================== */

.login-content {

    padding:
        55px 55px 45px;

    display: flex;

    flex-direction: column;

    justify-content: center;
}


/* =====================================================
   TOP
===================================================== */

.login-top {

    margin-bottom: 30px;
}


.login-top h2 {

    margin: 0 0 8px;

    font-size: 30px;

    color: #172033;

    font-weight: 700;
}


.login-top p {

    margin: 0;

    color: #7b8493;

    font-size: 14px;
}


/* =====================================================
   ALERT
===================================================== */

.alert {

    padding:
        13px 15px;

    border-radius: 10px;

    font-size: 13px;

    line-height: 1.5;

    margin-bottom: 18px;
}


.alert-info {

    background: #eaf4ff;

    border:
        1px solid #cfe5ff;

    color: #0759b5;
}


.alert-danger {

    background: #fff0f0;

    border:
        1px solid #ffd1d1;

    color: #b42318;
}


/* =====================================================
   FORM
===================================================== */

.form-group {

    margin-bottom: 20px;
}


.form-group label {

    display: block;

    margin-bottom: 8px;

    color: #293241;

    font-size: 14px;

    font-weight: 600;
}


/* =====================================================
   INPUT WRAPPER
===================================================== */

.input-box {

    position: relative;
}


.input-icon {

    position: absolute;

    left: 15px;

    top: 50%;

    transform:
        translateY(-50%);

    color: #8a94a6;

    font-size: 16px;

    pointer-events: none;
}


.input-box input {

    width: 100%;

    height: 52px;

    border:
        1px solid #dce1e8;

    border-radius: 11px;

    padding:
        0 15px 0 45px;

    font-size: 15px;

    color: #1f2937;

    outline: none;

    background: #fbfcfe;

    transition:
        0.2s ease;
}


.input-box input::placeholder {

    color: #a2a9b5;
}


.input-box input:focus {

    background: #ffffff;

    border-color: #0d6efd;

    box-shadow:
        0 0 0 4px
        rgba(13,110,253,0.08);
}


/* =====================================================
   PASSWORD TOGGLE
===================================================== */

.password-toggle {

    position: absolute;

    right: 14px;

    top: 50%;

    transform:
        translateY(-50%);

    border: 0;

    background: transparent;

    color: #8a94a6;

    cursor: pointer;

    font-size: 14px;
}


/* =====================================================
   LOGIN BUTTON
===================================================== */

.btn-login {

    width: 100%;

    height: 53px;

    border: 0;

    border-radius: 11px;

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #0757c9
        );

    color: #ffffff;

    font-size: 15px;

    font-weight: 700;

    cursor: pointer;

    box-shadow:
        0 8px 20px
        rgba(13,110,253,0.20);

    transition:
        0.2s ease;
}


.btn-login:hover {

    transform:
        translateY(-1px);

    box-shadow:
        0 12px 25px
        rgba(13,110,253,0.25);
}


.btn-login:disabled {

    opacity: 0.7;

    cursor: not-allowed;

    transform: none;
}


/* =====================================================
   DIVIDER
===================================================== */

.divider {

    display: flex;

    align-items: center;

    gap: 12px;

    margin: 25px 0;

    color: #a1a8b3;

    font-size: 12px;
}


.divider::before,
.divider::after {

    content: "";

    height: 1px;

    flex: 1;

    background: #e8ebef;
}


/* =====================================================
   REGISTER BOX
===================================================== */

.register-box {

    padding:
        16px;

    border:
        1px solid #e5e9ef;

    border-radius: 12px;

    background:
        #fafbfd;

    text-align: center;

    font-size: 14px;

    color: #6c7480;
}


.register-box a {

    color: #0d6efd;

    font-weight: 700;

    text-decoration: none;

    margin-left: 4px;
}


.register-box a:hover {

    text-decoration: underline;
}


/* =====================================================
   HOME LINK
===================================================== */

.home-link {

    display: block;

    text-align: center;

    margin-top: 22px;

    color: #7a8492;

    font-size: 13px;

    text-decoration: none;
}


.home-link:hover {

    color: #0d6efd;
}


/* =====================================================
   FOOTER
===================================================== */

.login-footer {

    text-align: center;

    margin-top: 25px;

    color: #a0a7b1;

    font-size: 11px;
}


/* =====================================================
   MOBILE
===================================================== */

@media (max-width: 800px) {

    .login-card {

        max-width: 500px;

        grid-template-columns: 1fr;

        min-height: auto;
    }


    .login-brand {

        padding: 35px 30px;

        text-align: center;

        align-items: center;
    }


    .login-brand h1 {

        font-size: 27px;
    }


    .login-brand p {

        margin-bottom: 20px;
    }


    .feature {

        display: none;
    }


    .login-content {

        padding:
            35px 25px 30px;
    }
}


@media (max-width: 480px) {

    .login-page {

        padding: 15px;
    }


    .login-card {

        border-radius: 18px;
    }


    .login-brand {

        padding: 30px 20px;
    }


    .brand-icon {

        width: 60px;
        height: 60px;

        font-size: 31px;

        margin-bottom: 20px;
    }


    .login-brand h1 {

        font-size: 24px;
    }


    .login-content {

        padding:
            30px 20px 25px;
    }


    .login-top h2 {

        font-size: 25px;
    }
}

</style>

</head>


<body>


<div class="login-page">


    <div class="login-card">


        <!-- =================================================
             LEFT BRAND
        ================================================== -->

        <div class="login-brand">


            <div class="brand-icon">
                💊
            </div>


            <h1>
                Medicine<br>
                Aapki Gaw Mein
            </h1>


            <p>
                Apni medicines ghar baithe order karein
                aur trusted healthcare service ka
                fayda uthayein.
            </p>


            <div class="feature">

                <span class="feature-icon">
                    ✓
                </span>

                Easy & Secure Ordering

            </div>


            <div class="feature">

                <span class="feature-icon">
                    ✓
                </span>

                Fast Local Delivery

            </div>


            <div class="feature">

                <span class="feature-icon">
                    ✓
                </span>

                Order Tracking

            </div>


        </div>


        <!-- =================================================
             LOGIN CONTENT
        ================================================== -->

        <div class="login-content">


            <div class="login-top">

                <h2>
                    Welcome Back 👋
                </h2>

                <p>
                    Apne account mein login karein
                    aur shopping continue karein.
                </p>

            </div>


            <!-- LOGIN REQUIRED -->

            <?php if ($login_required_message !== ''): ?>

                <div class="alert alert-info">

                    <?= htmlspecialchars(
                        $login_required_message,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>

            <?php endif; ?>


            <!-- ERROR -->

            <?php if ($error !== ''): ?>

                <div class="alert alert-danger">

                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 LOGIN FORM
            ================================================== -->

            <form
                method="POST"
                action="login.php"
                id="loginForm"
                autocomplete="off"
            >


                <!-- MOBILE -->

                <div class="form-group">

                    <label for="mobile">
                        Mobile Number
                    </label>


                    <div class="input-box">

                        <span class="input-icon">
                            📱
                        </span>


                        <input
                            type="text"
                            id="mobile"
                            name="mobile"
                            maxlength="10"
                            inputmode="numeric"
                            pattern="[0-9]{10}"
                            placeholder="Enter 10 digit mobile number"
                            value="<?= htmlspecialchars(
                                $_POST['mobile'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            required
                        >

                    </div>

                </div>


                <!-- PASSWORD -->

                <div class="form-group">

                    <label for="password">
                        Password
                    </label>


                    <div class="input-box">

                        <span class="input-icon">
                            🔒
                        </span>


                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                        >


                        <button
                            type="button"
                            class="password-toggle"
                            id="togglePassword"
                            aria-label="Show password"
                        >
                            👁
                        </button>

                    </div>

                </div>


                <!-- LOGIN -->

                <button
                    type="submit"
                    class="btn-login"
                    id="loginButton"
                >

                    Login to Account

                </button>

            </form>


            <!-- DIVIDER -->

            <div class="divider">
                <span>NEW CUSTOMER?</span>
            </div>


            <!-- REGISTER -->

            <div class="register-box">

                Don't have an account?

                <a href="register.php">
                    Create Account
                </a>

            </div>


            <!-- HOME -->

            <a
                href="index.php"
                class="home-link"
            >
                ← Back to Home
            </a>


            <!-- FOOTER -->

            <div class="login-footer">

                © <?= date('Y') ?>
                Medicine Aapki Gaw Mein

            </div>


        </div>

    </div>

</div>


<script>

/* =====================================================
   MOBILE NUMBER
===================================================== */

const mobile =
    document.getElementById('mobile');

if (mobile) {

    mobile.addEventListener(
        'input',
        function () {

            this.value =
                this.value
                    .replace(/\D/g, '')
                    .slice(0, 10);

        }
    );
}


/* =====================================================
   PASSWORD SHOW / HIDE
===================================================== */

const password =
    document.getElementById('password');

const togglePassword =
    document.getElementById('togglePassword');

if (
    password &&
    togglePassword
) {

    togglePassword.addEventListener(
        'click',
        function () {

            if (
                password.type === 'password'
            ) {

                password.type = 'text';

                this.textContent = '🙈';

            } else {

                password.type = 'password';

                this.textContent = '👁';
            }

        }
    );
}


/* =====================================================
   PREVENT DOUBLE SUBMIT
===================================================== */

const loginForm =
    document.getElementById('loginForm');

const loginButton =
    document.getElementById('loginButton');

if (
    loginForm &&
    loginButton
) {

    loginForm.addEventListener(
        'submit',
        function () {

            loginButton.disabled = true;

            loginButton.textContent =
                'Logging in...';

        }
    );
}

</script>


</body>

</html>
