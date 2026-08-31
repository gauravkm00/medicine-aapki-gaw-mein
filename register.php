<?php

session_start();

require_once "config/database.php";

$error = "";
$success = "";


/* =====================================================
   REDIRECT TARGET
===================================================== */

$redirect_after_register =
    $_SESSION['redirect_after_login']
    ?? 'index.php';


/* =====================================================
   REGISTER
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name =
        trim($_POST["name"] ?? "");

    $mobile =
        trim($_POST["mobile"] ?? "");

    $email =
        trim($_POST["email"] ?? "");

    $password =
        $_POST["password"] ?? "";

    $address =
        trim($_POST["address"] ?? "");

    $pincode =
        trim($_POST["pincode"] ?? "");


    /* =================================================
       VALIDATION
    ================================================= */

    if (
        $name === "" ||
        $mobile === "" ||
        $password === ""
    ) {

        $error =
            "Please fill all required fields.";

    }

    elseif (
        strlen($name) < 2
    ) {

        $error =
            "Please enter a valid name.";

    }

    elseif (
        !preg_match(
            '/^[0-9]{10}$/',
            $mobile
        )
    ) {

        $error =
            "Please enter a valid 10-digit mobile number.";

    }

    elseif (
        $email !== "" &&
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "Please enter a valid email address.";

    }

    elseif (
        strlen($password) < 6
    ) {

        $error =
            "Password must contain at least 6 characters.";

    }

    elseif (
        $pincode !== "" &&
        !preg_match(
            '/^[0-9]{6}$/',
            $pincode
        )
    ) {

        $error =
            "Please enter a valid 6-digit pincode.";

    }

    else {


        /* =============================================
           CHECK MOBILE
        ============================================= */

        $check =
            $conn->prepare(
                "SELECT id
                 FROM users
                 WHERE mobile = ?
                 LIMIT 1"
            );


        if (!$check) {

            $error =
                "Something went wrong. Please try again.";

        }

        else {

            $check->bind_param(
                "s",
                $mobile
            );

            $check->execute();

            $result =
                $check->get_result();


            if (
                $result->num_rows > 0
            ) {

                $error =
                    "This mobile number is already registered.";

            }


            $check->close();
        }


        /* =============================================
           CHECK EMAIL
        ============================================= */

        if (
            $error === "" &&
            $email !== ""
        ) {

            $emailCheck =
                $conn->prepare(
                    "SELECT id
                     FROM users
                     WHERE email = ?
                     LIMIT 1"
                );


            if (!$emailCheck) {

                $error =
                    "Something went wrong. Please try again.";

            }

            else {

                $emailCheck->bind_param(
                    "s",
                    $email
                );

                $emailCheck->execute();

                $emailResult =
                    $emailCheck->get_result();


                if (
                    $emailResult->num_rows > 0
                ) {

                    $error =
                        "This email is already registered.";

                }


                $emailCheck->close();
            }
        }


        /* =============================================
           CREATE ACCOUNT
        ============================================= */

        if ($error === "") {


            $hashedPassword =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            $role =
                "customer";

            $city =
                "Forbesganj";

            $state =
                "Bihar";

            $status =
                1;


            $stmt =
                $conn->prepare(
                    "INSERT INTO users
                    (
                        name,
                        mobile,
                        email,
                        password,
                        role,
                        address,
                        city,
                        state,
                        pincode,
                        status
                    )
                    VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );


            if (!$stmt) {

                $error =
                    "Registration failed. Please try again.";

            }

            else {

                $stmt->bind_param(
                    "sssssssssi",
                    $name,
                    $mobile,
                    $email,
                    $hashedPassword,
                    $role,
                    $address,
                    $city,
                    $state,
                    $pincode,
                    $status
                );


                if (
                    $stmt->execute()
                ) {


                    /* =================================
                       AUTO LOGIN
                    ================================= */

                    $new_user_id =
                        (int)
                        $stmt->insert_id;


                    session_regenerate_id(
                        true
                    );


                    $_SESSION['user_id'] =
                        $new_user_id;

                    $_SESSION['name'] =
                        $name;

                    $_SESSION['mobile'] =
                        $mobile;

                    $_SESSION['email'] =
                        $email;


                    /* =================================
                       CHECKOUT REDIRECT
                    ================================= */

                    $redirect =
                        $_SESSION[
                            'redirect_after_login'
                        ]
                        ?? 'index.php';


                    unset(
                        $_SESSION[
                            'redirect_after_login'
                        ]
                    );


                    /*
                     * Safe internal redirect only
                     */

                    $allowed_redirects = [
                        'checkout.php',
                        'cart.php',
                        'profile.php',
                        'index.php'
                    ];


                    if (
                        !in_array(
                            $redirect,
                            $allowed_redirects,
                            true
                        )
                    ) {

                        $redirect =
                            'index.php';
                    }


                    header(
                        "Location: "
                        . $redirect
                    );

                    exit;

                }

                else {

                    $error =
                        "Registration failed. Please try again.";
                }


                $stmt->close();
            }
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
        content="
            width=device-width,
            initial-scale=1.0
        "
    >

    <meta
        name="description"
        content="
            Create your Medicine Aapki Gaw Mein
            customer account.
        "
    >

    <title>
        Create Account | Medicine Aapki Gaw Mein
    </title>


    <!-- =================================================
         GOOGLE FONT
    ================================================== -->

    <link
        href="
        https://fonts.googleapis.com/css2?family=Inter:
        wght@400;500;600;700;800&display=swap
        "
        rel="stylesheet"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
        }


        body {

            margin: 0;

            min-height: 100vh;

            font-family:
                "Inter",
                Arial,
                sans-serif;

            background:
                #f4f8f7;

            color:
                #17211f;
        }


        /* =================================================
           PAGE
        ================================================= */

        .register-page {

            min-height: 100vh;

            padding: 30px 18px;

            display: flex;

            align-items: center;

            justify-content: center;

            background:

                radial-gradient(
                    circle at 10% 10%,
                    rgba(
                        12,
                        166,
                        120,
                        0.10
                    ),
                    transparent 30%
                ),

                radial-gradient(
                    circle at 90% 90%,
                    rgba(
                        13,
                        110,
                        253,
                        0.07
                    ),
                    transparent 30%
                ),

                #f5f9f8;
        }


        /* =================================================
           MAIN CARD
        ================================================= */

        .register-card {

            width: 100%;

            max-width: 1050px;

            min-height: 650px;

            display: grid;

            grid-template-columns:
                40% 60%;

            background:
                #ffffff;

            border-radius:
                28px;

            overflow:
                hidden;

            box-shadow:

                0 30px 80px
                rgba(
                    15,
                    23,
                    42,
                    0.12
                );
        }


        /* =================================================
           LEFT BRAND PANEL
        ================================================= */

        .brand-panel {

            position: relative;

            padding:
                50px 42px;

            color:
                #ffffff;

            overflow:
                hidden;

            background:

                linear-gradient(
                    145deg,
                    #056b4d 0%,
                    #07936a 55%,
                    #0ca678 100%
                );
        }


        .brand-panel::before {

            content: "";

            position: absolute;

            width: 300px;

            height: 300px;

            border-radius: 50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.07
                );

            right:
                -130px;

            top:
                -120px;
        }


        .brand-panel::after {

            content: "";

            position: absolute;

            width: 230px;

            height: 230px;

            border-radius: 50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.06
                );

            left:
                -120px;

            bottom:
                -90px;
        }


        .brand-content {

            position:
                relative;

            z-index:
                2;
        }


        /* =================================================
           LOGO
        ================================================= */

        .brand-logo {

            width:
                72px;

            height:
                72px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                22px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.15
                );

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.20
                );

            font-size:
                36px;

            margin-bottom:
                28px;

            box-shadow:
                0 12px 30px
                rgba(
                    0,
                    0,
                    0,
                    0.10
                );
        }


        .brand-panel h1 {

            margin:
                0 0 15px;

            font-size:
                30px;

            line-height:
                1.18;

            font-weight:
                800;

            letter-spacing:
                -0.7px;
        }


        .brand-description {

            margin:
                0;

            max-width:
                330px;

            font-size:
                14px;

            line-height:
                1.75;

            color:
                rgba(
                    255,
                    255,
                    255,
                    0.88
                );
        }


        /* =================================================
           FEATURES
        ================================================= */

        .feature-list {

            margin-top:
                40px;
        }


        .feature {

            display:
                flex;

            align-items:
                center;

            gap:
                13px;

            margin-bottom:
                20px;

            font-size:
                13px;

            color:
                rgba(
                    255,
                    255,
                    255,
                    0.94
                );
        }


        .feature-icon {

            width:
                38px;

            height:
                38px;

            flex:
                0 0 38px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                12px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.13
                );

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.12
                );
        }


        /* =================================================
           TRUST
        ================================================= */

        .trust-box {

            position:
                absolute;

            left:
                42px;

            right:
                42px;

            bottom:
                35px;

            padding:
                15px;

            border-radius:
                14px;

            background:
                rgba(
                    0,
                    0,
                    0,
                    0.10
                );

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.10
                );

            font-size:
                11px;

            line-height:
                1.6;

            color:
                rgba(
                    255,
                    255,
                    255,
                    0.80
                );
        }


        /* =================================================
           FORM PANEL
        ================================================= */

        .form-panel {

            padding:
                45px 50px;

            display:
                flex;

            flex-direction:
                column;

            justify-content:
                center;
        }


        .form-heading {

            margin-bottom:
                25px;
        }


        .form-heading h2 {

            margin:
                0 0 7px;

            font-size:
                27px;

            line-height:
                1.25;

            font-weight:
                800;

            color:
                #111827;

            letter-spacing:
                -0.5px;
        }


        .form-heading p {

            margin:
                0;

            color:
                #6b7280;

            font-size:
                13px;
        }


        /* =================================================
           ALERT
        ================================================= */

        .alert {

            display:
                flex;

            align-items:
                flex-start;

            gap:
                10px;

            padding:
                13px 15px;

            border-radius:
                12px;

            margin-bottom:
                20px;

            font-size:
                13px;

            line-height:
                1.5;
        }


        .alert-danger {

            color:
                #991b1b;

            background:
                #fef2f2;

            border:
                1px solid
                #fecaca;
        }


        .alert-icon {

            font-size:
                16px;

            flex:
                0 0 auto;
        }


        /* =================================================
           FORM GRID
        ================================================= */

        .form-grid {

            display:
                grid;

            grid-template-columns:
                1fr 1fr;

            gap:
                17px;
        }


        .form-group {

            min-width:
                0;
        }


        .full-width {

            grid-column:
                1 / -1;
        }


        /* =================================================
           LABEL
        ================================================= */

        .form-label {

            display:
                block;

            margin-bottom:
                7px;

            color:
                #374151;

            font-size:
                12px;

            font-weight:
                700;
        }


        .required {

            color:
                #dc2626;
        }


        .optional {

            color:
                #9ca3af;

            font-weight:
                400;
        }


        /* =================================================
           INPUT
        ================================================= */

        .input-box {

            position:
                relative;
        }


        .input-icon {

            position:
                absolute;

            left:
                14px;

            top:
                50%;

            transform:
                translateY(-50%);

            color:
                #9ca3af;

            font-size:
                15px;

            pointer-events:
                none;
        }


        .form-control {

            width:
                100%;

            height:
                46px;

            padding:
                0 14px 0 41px;

            border:
                1px solid
                #e5e7eb;

            border-radius:
                11px;

            outline:
                none;

            background:
                #f9fafb;

            color:
                #111827;

            font-family:
                inherit;

            font-size:
                13px;

            transition:
                all .2s ease;
        }


        textarea.form-control {

            height:
                95px;

            padding:
                13px 14px;

            resize:
                vertical;
        }


        .form-control::placeholder {

            color:
                #a1a1aa;
        }


        .form-control:focus {

            background:
                #ffffff;

            border-color:
                #0ca678;

            box-shadow:
                0 0 0 4px
                rgba(
                    12,
                    166,
                    120,
                    0.09
                );
        }


        .form-control:read-only {

            background:
                #f3f4f6;

            color:
                #6b7280;

            cursor:
                not-allowed;
        }


        /* =================================================
           PASSWORD
        ================================================= */

        .password-control {

            padding-right:
                45px;
        }


        .password-toggle {

            position:
                absolute;

            right:
                12px;

            top:
                50%;

            transform:
                translateY(-50%);

            width:
                30px;

            height:
                30px;

            border:
                0;

            background:
                transparent;

            cursor:
                pointer;

            color:
                #6b7280;

            border-radius:
                7px;
        }


        .password-toggle:hover {

            background:
                #f3f4f6;
        }


        /* =================================================
           PASSWORD STRENGTH
        ================================================= */

        .password-strength {

            margin-top:
                7px;

            display:
                none;
        }


        .strength-bars {

            display:
                flex;

            gap:
                4px;
        }


        .strength-bar {

            height:
                3px;

            flex:
                1;

            border-radius:
                10px;

            background:
                #e5e7eb;

            transition:
                background .2s ease;
        }


        .strength-text {

            margin-top:
                4px;

            font-size:
                10px;

            color:
                #9ca3af;
        }


        /* =================================================
           BUTTON
        ================================================= */

        .register-button {

            width:
                100%;

            height:
                49px;

            margin-top:
                21px;

            border:
                0;

            border-radius:
                11px;

            background:
                linear-gradient(
                    135deg,
                    #087f5b,
                    #0ca678
                );

            color:
                #ffffff;

            font-family:
                inherit;

            font-size:
                14px;

            font-weight:
                700;

            cursor:
                pointer;

            box-shadow:
                0 9px 22px
                rgba(
                    8,
                    127,
                    91,
                    0.20
                );

            transition:
                all .2s ease;
        }


        .register-button:hover {

            transform:
                translateY(-1px);

            box-shadow:
                0 13px 28px
                rgba(
                    8,
                    127,
                    91,
                    0.27
                );
        }


        .register-button:disabled {

            opacity:
                .65;

            cursor:
                not-allowed;

            transform:
                none;
        }


        /* =================================================
           LOGIN
        ================================================= */

        .login-area {

            text-align:
                center;

            margin-top:
                19px;

            font-size:
                12px;

            color:
                #6b7280;
        }


        .login-area a {

            color:
                #087f5b;

            font-weight:
                700;

            text-decoration:
                none;
        }


        .login-area a:hover {

            text-decoration:
                underline;
        }


        /* =================================================
           HOME
        ================================================= */

        .home-link {

            display:
                block;

            text-align:
                center;

            margin-top:
                12px;

            color:
                #9ca3af;

            font-size:
                11px;

            text-decoration:
                none;
        }


        .home-link:hover {

            color:
                #087f5b;
        }


        /* =================================================
           MOBILE
        ================================================= */

        @media (
            max-width: 850px
        ) {

            .register-card {

                grid-template-columns:
                    1fr;

                max-width:
                    620px;
            }


            .brand-panel {

                padding:
                    35px 30px;
            }


            .brand-panel h1 {

                font-size:
                    26px;
            }


            .feature-list {

                display:
                    grid;

                grid-template-columns:
                    1fr 1fr;

                gap:
                    10px;

                margin-top:
                    25px;
            }


            .feature {

                margin:
                    0;
            }


            .trust-box {

                position:
                    static;

                margin-top:
                    25px;
            }


            .form-panel {

                padding:
                    35px 30px;
            }
        }


        @media (
            max-width: 550px
        ) {

            .register-page {

                padding:
                    12px;
            }


            .register-card {

                border-radius:
                    20px;
            }


            .brand-panel {

                padding:
                    28px 22px;

                text-align:
                    center;
            }


            .brand-logo {

                margin-left:
                    auto;

                margin-right:
                    auto;
            }


            .brand-panel h1 {

                font-size:
                    22px;
            }


            .brand-description {

                margin:
                    auto;
            }


            .feature-list {

                display:
                    none;
            }


            .trust-box {

                display:
                    none;
            }


            .form-panel {

                padding:
                    28px 18px;
            }


            .form-heading h2 {

                font-size:
                    23px;
            }


            .form-grid {

                grid-template-columns:
                    1fr;

                gap:
                    15px;
            }


            .full-width {

                grid-column:
                    auto;
            }
        }

    </style>

</head>


<body>


<div class="register-page">


    <div class="register-card">


        <!-- =================================================
             BRAND PANEL
        ================================================== -->

        <section class="brand-panel">

            <div class="brand-content">


                <div class="brand-logo">
                    💊
                </div>


                <h1>
                    Medicine Aapki
                    Gaw Mein
                </h1>


                <p class="brand-description">

                    Ghar baithe medicines order karein,
                    apne orders manage karein aur
                    doorstep delivery ka benefit lein.

                </p>


                <div class="feature-list">


                    <div class="feature">

                        <span class="feature-icon">
                            ✓
                        </span>

                        <span>
                            Easy & Secure Ordering
                        </span>

                    </div>


                    <div class="feature">

                        <span class="feature-icon">
                            ✓
                        </span>

                        <span>
                            Doorstep Medicine Delivery
                        </span>

                    </div>


                    <div class="feature">

                        <span class="feature-icon">
                            ✓
                        </span>

                        <span>
                            Track Your Orders
                        </span>

                    </div>


                    <div class="feature">

                        <span class="feature-icon">
                            ✓
                        </span>

                        <span>
                            Manage Your Account
                        </span>

                    </div>

                </div>


                <div class="trust-box">

                    🔒 Your password is securely
                    encrypted before being stored.

                </div>

            </div>

        </section>


        <!-- =================================================
             FORM PANEL
        ================================================== -->

        <section class="form-panel">


            <div class="form-heading">

                <h2>
                    Create your account
                </h2>

                <p>
                    Register karein aur medicines
                    easily order karein.
                </p>

            </div>


            <!-- =================================================
                 ERROR
            ================================================== -->

            <?php if ($error !== ""): ?>

                <div class="alert alert-danger">

                    <span class="alert-icon">
                        ⚠
                    </span>

                    <span>

                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </span>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                id="registerForm"
                autocomplete="on"
            >


                <div class="form-grid">


                    <!-- NAME -->

                    <div class="form-group">

                        <label
                            class="form-label"
                            for="name"
                        >
                            Full Name
                            <span class="required">*</span>
                        </label>


                        <div class="input-box">

                            <span class="input-icon">
                                👤
                            </span>


                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control"
                                placeholder="Enter your full name"
                                maxlength="100"
                                value="<?= htmlspecialchars(
                                    $_POST['name'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                autocomplete="name"
                                required
                            >

                        </div>

                    </div>


                    <!-- MOBILE -->

                    <div class="form-group">

                        <label
                            class="form-label"
                            for="mobile"
                        >
                            Mobile Number
                            <span class="required">*</span>
                        </label>


                        <div class="input-box">

                            <span class="input-icon">
                                📱
                            </span>


                            <input
                                type="text"
                                id="mobile"
                                name="mobile"
                                class="form-control"
                                placeholder="10 digit mobile"
                                maxlength="10"
                                inputmode="numeric"
                                pattern="[0-9]{10}"
                                value="<?= htmlspecialchars(
                                    $_POST['mobile'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                autocomplete="tel"
                                required
                            >

                        </div>

                    </div>


                    <!-- EMAIL -->

                    <div class="form-group">

                        <label
                            class="form-label"
                            for="email"
                        >
                            Email
                            <span class="optional">
                                (Optional)
                            </span>
                        </label>


                        <div class="input-box">

                            <span class="input-icon">
                                ✉
                            </span>


                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                placeholder="you@example.com"
                                maxlength="150"
                                value="<?= htmlspecialchars(
                                    $_POST['email'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                autocomplete="email"
                            >

                        </div>

                    </div>


                    <!-- PASSWORD -->

                    <div class="form-group">

                        <label
                            class="form-label"
                            for="password"
                        >
                            Password
                            <span class="required">*</span>
                        </label>


                        <div class="input-box">

                            <span class="input-icon">
                                🔒
                            </span>


                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="
                                    form-control
                                    password-control
                                "
                                placeholder="Minimum 6 characters"
                                minlength="6"
                                autocomplete="new-password"
                                required
                            >


                            <button
                                type="button"
                                class="password-toggle"
                                id="passwordToggle"
                                aria-label="Show password"
                            >
                                👁
                            </button>

                        </div>


                        <div
                            class="password-strength"
                            id="passwordStrength"
                        >

                            <div
                                class="strength-bars"
                            >

                                <span
                                    class="strength-bar"
                                ></span>

                                <span
                                    class="strength-bar"
                                ></span>

                                <span
                                    class="strength-bar"
                                ></span>

                                <span
                                    class="strength-bar"
                                ></span>

                            </div>


                            <div
                                class="strength-text"
                                id="strengthText"
                            >
                                Password strength
                            </div>

                        </div>

                    </div>


                    <!-- ADDRESS -->

                    <div
                        class="
                            form-group
                            full-width
                        "
                    >

                        <label
                            class="form-label"
                            for="address"
                        >
                            Delivery Address
                            <span class="optional">
                                (Optional)
                            </span>
                        </label>


                        <textarea
                            id="address"
                            name="address"
                            class="form-control"
                            maxlength="500"
                            placeholder="
                                House no, village, street,
                                landmark...
                            "
                        ><?= htmlspecialchars(
                            $_POST['address'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></textarea>

                    </div>


                    <!-- CITY -->

                    <div class="form-group">

                        <label
                            class="form-label"
                            for="city"
                        >
                            City
                        </label>


                        <div class="input-box">

                            <span class="input-icon">
                                📍
                            </span>


                            <input
                                type="text"
                                id="city"
                                class="form-control"
                                value="Forbesganj"
                                readonly
                            >

                        </div>

                    </div>


                    <!-- PINCODE -->

                    <div class="form-group">

                        <label
                            class="form-label"
                            for="pincode"
                        >
                            Pincode
                            <span class="optional">
                                (Optional)
                            </span>
                        </label>


                        <div class="input-box">

                            <span class="input-icon">
                                📮
                            </span>


                            <input
                                type="text"
                                id="pincode"
                                name="pincode"
                                class="form-control"
                                placeholder="6 digit pincode"
                                maxlength="6"
                                inputmode="numeric"
                                pattern="[0-9]{6}"
                                value="<?= htmlspecialchars(
                                    $_POST['pincode'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     SUBMIT
                ================================================== -->

                <button
                    type="submit"
                    class="register-button"
                    id="registerButton"
                >
                    Create Account →
                </button>


            </form>


            <!-- =================================================
                 LOGIN
            ================================================== -->

            <div class="login-area">

                Already have an account?

                <a href="login.php">
                    Login here
                </a>

            </div>


            <!-- =================================================
                 HOME
            ================================================== -->

            <a
                href="index.php"
                class="home-link"
            >
                ← Back to Medicine Aapki Gaw Mein
            </a>


        </section>

    </div>

</div>


<script>

/* =====================================================
   PASSWORD SHOW / HIDE
===================================================== */

const passwordInput =
    document.getElementById(
        "password"
    );

const passwordToggle =
    document.getElementById(
        "passwordToggle"
    );


if (
    passwordInput &&
    passwordToggle
) {

    passwordToggle.addEventListener(
        "click",
        function () {

            if (
                passwordInput.type ===
                "password"
            ) {

                passwordInput.type =
                    "text";

                passwordToggle.textContent =
                    "🙈";

                passwordToggle.setAttribute(
                    "aria-label",
                    "Hide password"
                );

            }

            else {

                passwordInput.type =
                    "password";

                passwordToggle.textContent =
                    "👁";

                passwordToggle.setAttribute(
                    "aria-label",
                    "Show password"
                );
            }

        }
    );

}


/* =====================================================
   PASSWORD STRENGTH
===================================================== */

const strengthBox =
    document.getElementById(
        "passwordStrength"
    );

const strengthText =
    document.getElementById(
        "strengthText"
    );

const strengthBars =
    document.querySelectorAll(
        ".strength-bar"
    );


if (passwordInput) {

    passwordInput.addEventListener(
        "input",
        function () {

            const password =
                this.value;

            if (
                password.length === 0
            ) {

                strengthBox.style.display =
                    "none";

                return;
            }


            strengthBox.style.display =
                "block";


            let score = 0;


            if (
                password.length >= 6
            ) {
                score++;
            }


            if (
                password.length >= 8
            ) {
                score++;
            }


            if (
                /[A-Z]/.test(password)
            ) {
                score++;
            }


            if (
                /[0-9]/.test(password)
            ) {
                score++;
            }


            if (
                /[^A-Za-z0-9]/.test(password)
            ) {
                score++;
            }


            score =
                Math.min(
                    score,
                    4
                );


            strengthBars.forEach(
                function (
                    bar,
                    index
                ) {

                    bar.style.background =
                        "#e5e7eb";

                    if (
                        index < score
                    ) {

                        if (
                            score <= 1
                        ) {

                            bar.style.background =
                                "#ef4444";

                        }

                        else if (
                            score <= 2
                        ) {

                            bar.style.background =
                                "#f59e0b";

                        }

                        else if (
                            score === 3
                        ) {

                            bar.style.background =
                                "#22c55e";

                        }

                        else {

                            bar.style.background =
                                "#087f5b";
                        }
                    }

                }
            );


            if (
                score <= 1
            ) {

                strengthText.textContent =
                    "Weak password";

                strengthText.style.color =
                    "#ef4444";

            }

            else if (
                score === 2
            ) {

                strengthText.textContent =
                    "Fair password";

                strengthText.style.color =
                    "#f59e0b";

            }

            else if (
                score === 3
            ) {

                strengthText.textContent =
                    "Good password";

                strengthText.style.color =
                    "#22c55e";

            }

            else {

                strengthText.textContent =
                    "Strong password";

                strengthText.style.color =
                    "#087f5b";
            }

        }
    );

}


/* =====================================================
   MOBILE NUMBER
===================================================== */

const mobileInput =
    document.getElementById(
        "mobile"
    );


if (mobileInput) {

    mobileInput.addEventListener(
        "input",
        function () {

            this.value =
                this.value
                    .replace(
                        /\D/g,
                        ""
                    )
                    .slice(
                        0,
                        10
                    );

        }
    );

}


/* =====================================================
   PINCODE
===================================================== */

const pincodeInput =
    document.getElementById(
        "pincode"
    );


if (pincodeInput) {

    pincodeInput.addEventListener(
        "input",
        function () {

            this.value =
                this.value
                    .replace(
                        /\D/g,
                        ""
                    )
                    .slice(
                        0,
                        6
                    );

        }
    );

}


/* =====================================================
   FORM SUBMIT
===================================================== */

const registerForm =
    document.getElementById(
        "registerForm"
    );

const registerButton =
    document.getElementById(
        "registerButton"
    );


if (
    registerForm &&
    registerButton
) {

    registerForm.addEventListener(
        "submit",
        function () {

            registerButton.disabled =
                true;

            registerButton.textContent =
                "Creating Account...";

        }
    );

}

</script>


</body>

</html>
