<?php

session_start();

require_once "../config/database.php";


// ======================================
// ALREADY LOGGED-IN ADMIN
// ======================================

if (
    isset($_SESSION['user_id'], $_SESSION['role']) &&
    $_SESSION['role'] === 'admin'
) {
    header("Location: index.php");
    exit;
}


// ======================================
// VARIABLES
// ======================================

$error = "";

$mobile = "";


// ======================================
// LOGIN
// ======================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $mobile = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';


    // ----------------------------------
    // VALIDATION
    // ----------------------------------

    if ($mobile === "" || $password === "") {

        $error = "Mobile number aur password dono required hain.";

    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {

        $error = "Please valid 10-digit mobile number enter karein.";

    } else {


        // ----------------------------------
        // FIND ADMIN
        // ----------------------------------

        $sql = "SELECT
                    id,
                    name,
                    mobile,
                    email,
                    password,
                    role,
                    status
                FROM users
                WHERE mobile = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);


        if ($stmt) {

            $stmt->bind_param("s", $mobile);

            $stmt->execute();

            $result = $stmt->get_result();


            // ----------------------------------
            // USER FOUND
            // ----------------------------------

            if ($result && $result->num_rows === 1) {

                $user = $result->fetch_assoc();


                // ----------------------------------
                // ACCOUNT STATUS
                // ----------------------------------

                if ((int)$user['status'] !== 1) {

                    $error =
                        "Aapka account inactive hai. Please administrator se contact karein.";

                }


                // ----------------------------------
                // ADMIN CHECK
                // ----------------------------------

                elseif ($user['role'] !== 'admin') {

                    $error =
                        "Is account ko admin panel access nahi hai.";

                }


                // ----------------------------------
                // PASSWORD CHECK
                // ----------------------------------

                elseif (
                    !password_verify(
                        $password,
                        $user['password']
                    )
                ) {

                    $error =
                        "Mobile number ya password galat hai.";

                }


                // ----------------------------------
                // SUCCESS
                // ----------------------------------

                else {

                    session_regenerate_id(true);


                    $_SESSION['user_id'] =
                        (int)$user['id'];

                    $_SESSION['name'] =
                        $user['name'];

                    $_SESSION['mobile'] =
                        $user['mobile'];

                    $_SESSION['email'] =
                        $user['email'];

                    $_SESSION['role'] =
                        $user['role'];


                    header("Location: index.php");

                    exit;
                }

            } else {

                $error =
                    "Mobile number ya password galat hai.";

            }


            $stmt->close();

        } else {

            $error =
                "Something went wrong. Please try again.";

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
        Admin Login | Medicine Aapki Gaw Mein
    </title>


    <!-- ======================================
         GOOGLE FONT
    ======================================= -->

    <link
        href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <style>

        /* =====================================
           RESET
        ===================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        /* =====================================
           BODY
        ===================================== */

        body {

            min-height: 100vh;

            font-family: 'Rubik', Arial, sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #e9f8ed 0%,
                    #f7fbf8 50%,
                    #e8f6ec 100%
                );

            color: #333;

        }


        /* =====================================
           BACKGROUND
        ===================================== */

        .page-wrapper {

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 30px 15px;

            position: relative;

            overflow: hidden;

        }


        .circle {

            position: absolute;

            border-radius: 50%;

            background: rgba(81, 184, 72, 0.10);

            pointer-events: none;

        }


        .circle-one {

            width: 350px;

            height: 350px;

            top: -150px;

            left: -100px;

        }


        .circle-two {

            width: 450px;

            height: 450px;

            bottom: -220px;

            right: -150px;

        }


        /* =====================================
           LOGIN CARD
        ===================================== */

        .login-card {

            width: 100%;

            max-width: 1000px;

            min-height: 590px;

            background: #ffffff;

            border-radius: 18px;

            overflow: hidden;

            display: grid;

            grid-template-columns: 45% 55%;

            box-shadow:
                0 20px 60px rgba(0, 0, 0, 0.12);

            position: relative;

            z-index: 2;

        }


        /* =====================================
           LEFT PANEL
        ===================================== */

        .login-left {

            background:
                linear-gradient(
                    145deg,
                    #4caf43,
                    #238b39
                );

            color: #ffffff;

            padding: 55px 45px;

            display: flex;

            flex-direction: column;

            justify-content: center;

            position: relative;

            overflow: hidden;

        }


        .login-left::before {

            content: "";

            position: absolute;

            width: 260px;

            height: 260px;

            border-radius: 50%;

            border: 35px solid rgba(255,255,255,0.08);

            right: -100px;

            top: -100px;

        }


        .login-left::after {

            content: "";

            position: absolute;

            width: 220px;

            height: 220px;

            border-radius: 50%;

            border: 30px solid rgba(255,255,255,0.07);

            left: -110px;

            bottom: -90px;

        }


        .brand-icon {

            width: 82px;

            height: 82px;

            background: rgba(255,255,255,0.16);

            border-radius: 20px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 43px;

            margin-bottom: 28px;

            position: relative;

            z-index: 2;

        }


        .login-left h1 {

            font-size: 31px;

            line-height: 1.25;

            font-weight: 700;

            margin-bottom: 18px;

            position: relative;

            z-index: 2;

        }


        .login-left h1 span {

            display: block;

            color: #eaffeb;

        }


        .login-left > p {

            font-size: 15px;

            line-height: 1.8;

            color: rgba(255,255,255,0.88);

            max-width: 350px;

            position: relative;

            z-index: 2;

        }


        /* =====================================
           FEATURES
        ===================================== */

        .features {

            margin-top: 32px;

            position: relative;

            z-index: 2;

        }


        .feature {

            display: flex;

            align-items: center;

            margin-bottom: 16px;

            font-size: 14px;

            color: #ffffff;

        }


        .feature-icon {

            width: 30px;

            height: 30px;

            border-radius: 50%;

            background: rgba(255,255,255,0.16);

            display: flex;

            align-items: center;

            justify-content: center;

            margin-right: 12px;

            font-size: 14px;

        }


        /* =====================================
           RIGHT PANEL
        ===================================== */

        .login-right {

            padding: 55px 65px;

            display: flex;

            flex-direction: column;

            justify-content: center;

        }


        .login-heading {

            margin-bottom: 32px;

        }


        .login-heading h2 {

            font-size: 29px;

            font-weight: 700;

            color: #222;

            margin-bottom: 8px;

        }


        .login-heading p {

            color: #888;

            font-size: 14px;

        }


        /* =====================================
           ERROR
        ===================================== */

        .alert-error {

            background: #fff1f1;

            border: 1px solid #f5c6cb;

            color: #a12834;

            padding: 13px 15px;

            border-radius: 8px;

            margin-bottom: 22px;

            font-size: 13px;

            line-height: 1.5;

        }


        /* =====================================
           FORM
        ===================================== */

        .form-group {

            margin-bottom: 22px;

        }


        .form-label {

            display: block;

            font-size: 14px;

            font-weight: 600;

            color: #333;

            margin-bottom: 9px;

        }


        .input-wrapper {

            position: relative;

        }


        .input-icon {

            position: absolute;

            left: 15px;

            top: 50%;

            transform: translateY(-50%);

            color: #999;

            font-size: 17px;

        }


        .form-control {

            width: 100%;

            height: 52px;

            border: 1px solid #dfe3e6;

            border-radius: 8px;

            padding: 0 15px 0 45px;

            font-family: inherit;

            font-size: 14px;

            color: #333;

            outline: none;

            transition: all 0.2s ease;

            background: #fff;

        }


        .form-control:focus {

            border-color: #51b848;

            box-shadow:
                0 0 0 3px rgba(81,184,72,0.10);

        }


        .form-control::placeholder {

            color: #aaa;

        }


        /* =====================================
           PASSWORD
        ===================================== */

        .password-toggle {

            position: absolute;

            right: 15px;

            top: 50%;

            transform: translateY(-50%);

            border: none;

            background: transparent;

            cursor: pointer;

            color: #888;

            font-size: 14px;

            padding: 5px;

        }


        .password-toggle:hover {

            color: #51b848;

        }


        /* =====================================
           LOGIN BUTTON
        ===================================== */

        .btn-login {

            width: 100%;

            height: 52px;

            border: none;

            border-radius: 8px;

            background:
                linear-gradient(
                    135deg,
                    #51b848,
                    #3a9d3d
                );

            color: #ffffff;

            font-family: inherit;

            font-size: 15px;

            font-weight: 600;

            cursor: pointer;

            transition: all 0.2s ease;

            box-shadow:
                0 5px 15px rgba(81,184,72,0.22);

        }


        .btn-login:hover {

            transform: translateY(-1px);

            box-shadow:
                0 8px 20px rgba(81,184,72,0.28);

        }


        .btn-login:active {

            transform: translateY(0);

        }


        /* =====================================
           FOOTER
        ===================================== */

        .login-footer {

            text-align: center;

            margin-top: 25px;

            padding-top: 20px;

            border-top: 1px solid #eeeeee;

            color: #999;

            font-size: 12px;

            line-height: 1.7;

        }


        .back-link {

            display: inline-block;

            margin-top: 8px;

            color: #51b848;

            text-decoration: none;

            font-weight: 500;

        }


        .back-link:hover {

            color: #338b31;

            text-decoration: underline;

        }


        /* =====================================
           MOBILE
        ===================================== */

        @media (max-width: 850px) {

            .login-card {

                max-width: 500px;

                grid-template-columns: 1fr;

            }


            .login-left {

                padding: 35px 30px;

                text-align: center;

                align-items: center;

            }


            .login-left h1 {

                font-size: 26px;

            }


            .login-left > p {

                max-width: 450px;

            }


            .features {

                display: none;

            }


            .login-right {

                padding: 40px 30px;

            }

        }


        @media (max-width: 480px) {

            .page-wrapper {

                padding: 15px;

            }


            .login-card {

                border-radius: 12px;

            }


            .login-left {

                padding: 30px 22px;

            }


            .brand-icon {

                width: 65px;

                height: 65px;

                font-size: 34px;

                margin-bottom: 20px;

            }


            .login-left h1 {

                font-size: 23px;

            }


            .login-right {

                padding: 30px 22px;

            }


            .login-heading h2 {

                font-size: 25px;

            }

        }

    </style>

</head>


<body>


<div class="page-wrapper">


    <div class="circle circle-one"></div>

    <div class="circle circle-two"></div>


    <div class="login-card">


        <!-- =================================
             LEFT
        ================================== -->

        <div class="login-left">


            <div class="brand-icon">
                💊
            </div>


            <h1>

                Medicine Aapki
                <span>Gaw Mein</span>

            </h1>


            <p>

                Welcome to the administration
                panel. Manage medicines, orders,
                prescriptions and deliveries from
                one place.

            </p>


            <div class="features">


                <div class="feature">

                    <div class="feature-icon">
                        ✓
                    </div>

                    <span>
                        Manage Medicines
                    </span>

                </div>


                <div class="feature">

                    <div class="feature-icon">
                        ✓
                    </div>

                    <span>
                        Manage Customer Orders
                    </span>

                </div>


                <div class="feature">

                    <div class="feature-icon">
                        ✓
                    </div>

                    <span>
                        Track Prescriptions & Deliveries
                    </span>

                </div>


            </div>


        </div>


        <!-- =================================
             RIGHT
        ================================== -->

        <div class="login-right">


            <div class="login-heading">

                <h2>
                    Admin Login
                </h2>

                <p>
                    Sign in to access your dashboard
                </p>

            </div>


            <!-- ERROR -->

            <?php if ($error !== ""): ?>

                <div class="alert-error">

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <!-- FORM -->

            <form
                method="POST"
                action=""
                autocomplete="off"
            >


                <!-- MOBILE -->

                <div class="form-group">

                    <label
                        class="form-label"
                        for="mobile"
                    >
                        Mobile Number
                    </label>


                    <div class="input-wrapper">

                        <span class="input-icon">
                            📱
                        </span>


                        <input
                            type="text"
                            id="mobile"
                            name="mobile"
                            class="form-control"
                            placeholder="Enter 10-digit mobile number"
                            maxlength="10"
                            inputmode="numeric"
                            autocomplete="username"
                            value="<?= htmlspecialchars($mobile) ?>"
                            required
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
                    </label>


                    <div class="input-wrapper">

                        <span class="input-icon">
                            🔒
                        </span>


                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >


                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword()"
                            id="passwordToggle"
                        >
                            Show
                        </button>

                    </div>

                </div>


                <!-- LOGIN -->

                <button
                    type="submit"
                    class="btn-login"
                >

                    Login to Admin Panel

                </button>


            </form>


            <!-- FOOTER -->

            <div class="login-footer">

                <div>
                    Secure Admin Access
                </div>

                <div>

                    © <?= date('Y') ?>

                    Medicine Aapki Gaw Mein

                </div>


                <a
                    href="../index.php"
                    class="back-link"
                >
                    ← Back to Website
                </a>

            </div>


        </div>


    </div>

</div>


<script>

function togglePassword() {

    const password =
        document.getElementById("password");

    const button =
        document.getElementById("passwordToggle");


    if (password.type === "password") {

        password.type = "text";

        button.textContent = "Hide";

    } else {

        password.type = "password";

        button.textContent = "Show";

    }

}


// Only allow numbers in mobile field

document
    .getElementById("mobile")
    .addEventListener("input", function () {

        this.value =
            this.value.replace(/\D/g, '').slice(0, 10);

    });

</script>


</body>

</html>
