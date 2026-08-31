<?php

session_start();

require_once "config/database.php";


// =====================================================
// LOGIN REQUIRED
// =====================================================

if (
    !isset($_SESSION['user_id']) ||
    (int) $_SESSION['user_id'] <= 0
) {

    $_SESSION['redirect_after_login'] = 'profile.php';

    $_SESSION['login_required_message'] =
        "Profile dekhne ke liye pehle login karein.";

    header("Location: login.php");
    exit;
}


$user_id = (int) $_SESSION['user_id'];


// =====================================================
// VARIABLES
// =====================================================

$error = "";
$success = "";


// =====================================================
// LOAD USER
// =====================================================

$stmt = $conn->prepare("
    SELECT
        id,
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
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

$stmt->close();


// =====================================================
// USER NOT FOUND
// =====================================================

if (!$user) {

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}


// =====================================================
// ACCOUNT STATUS
// =====================================================

if ((int) $user['status'] !== 1) {

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}


// =====================================================
// UPDATE PROFILE
// =====================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_profile'])
) {

    $name =
        trim($_POST['name'] ?? '');

    $email =
        trim($_POST['email'] ?? '');

    $address =
        trim($_POST['address'] ?? '');

    $city =
        trim($_POST['city'] ?? '');

    $state =
        trim($_POST['state'] ?? '');

    $pincode =
        trim($_POST['pincode'] ?? '');


    // =================================================
    // VALIDATION
    // =================================================

    if ($name === '') {

        $error =
            "Full name required hai.";

    }

    elseif (strlen($name) > 100) {

        $error =
            "Name maximum 100 characters ka ho sakta hai.";

    }

    elseif (
        $email !== '' &&
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "Please valid email address enter karein.";

    }

    elseif (
        $pincode !== '' &&
        !preg_match(
            '/^[0-9]{6}$/',
            $pincode
        )
    ) {

        $error =
            "Please valid 6 digit pincode enter karein.";

    }

    else {

        // =================================================
        // CHECK EMAIL DUPLICATE
        // =================================================

        if ($email !== '') {

            $email_stmt = $conn->prepare("
                SELECT id
                FROM users
                WHERE email = ?
                AND id != ?
                LIMIT 1
            ");

            $email_stmt->bind_param(
                "si",
                $email,
                $user_id
            );

            $email_stmt->execute();

            $email_result =
                $email_stmt->get_result();

            if (
                $email_result->num_rows > 0
            ) {

                $error =
                    "Ye email kisi aur account ke saath registered hai.";
            }

            $email_stmt->close();
        }


        // =================================================
        // UPDATE
        // =================================================

        if ($error === '') {

            $update_stmt = $conn->prepare("
                UPDATE users
                SET
                    name = ?,
                    email = ?,
                    address = ?,
                    city = ?,
                    state = ?,
                    pincode = ?
                WHERE id = ?
            ");

            $update_stmt->bind_param(
                "ssssssi",
                $name,
                $email,
                $address,
                $city,
                $state,
                $pincode,
                $user_id
            );


            if (
                $update_stmt->execute()
            ) {

                $success =
                    "Profile successfully update ho gaya.";

                // Session name update
                $_SESSION['name'] =
                    $name;


                // Local user data update
                $user['name'] =
                    $name;

                $user['email'] =
                    $email;

                $user['address'] =
                    $address;

                $user['city'] =
                    $city;

                $user['state'] =
                    $state;

                $user['pincode'] =
                    $pincode;

            } else {

                $error =
                    "Profile update nahi ho saka. Please try again.";
            }

            $update_stmt->close();
        }
    }
}


// =====================================================
// CHANGE PASSWORD
// =====================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['change_password'])
) {

    $current_password =
        $_POST['current_password'] ?? '';

    $new_password =
        $_POST['new_password'] ?? '';

    $confirm_password =
        $_POST['confirm_password'] ?? '';


    // =================================================
    // VALIDATION
    // =================================================

    if (
        $current_password === '' ||
        $new_password === '' ||
        $confirm_password === ''
    ) {

        $error =
            "Password ke sabhi fields required hain.";

    }

    elseif (
        !password_verify(
            $current_password,
            $user['password']
        )
    ) {

        $error =
            "Current password galat hai.";

    }

    elseif (
        strlen($new_password) < 6
    ) {

        $error =
            "New password minimum 6 characters ka hona chahiye.";

    }

    elseif (
        $new_password !== $confirm_password
    ) {

        $error =
            "New password aur confirm password match nahi kar rahe.";

    }

    elseif (
        $current_password === $new_password
    ) {

        $error =
            "New password current password se different hona chahiye.";

    }

    else {

        $new_hashed_password =
            password_hash(
                $new_password,
                PASSWORD_DEFAULT
            );


        $password_stmt = $conn->prepare("
            UPDATE users
            SET password = ?
            WHERE id = ?
        ");

        $password_stmt->bind_param(
            "si",
            $new_hashed_password,
            $user_id
        );


        if (
            $password_stmt->execute()
        ) {

            $success =
                "Password successfully change ho gaya.";

            // Refresh local password hash
            $user['password'] =
                $new_hashed_password;

        } else {

            $error =
                "Password change nahi ho saka.";
        }


        $password_stmt->close();
    }
}


// =====================================================
// INITIALS
// =====================================================

$name_parts =
    preg_split(
        '/\s+/',
        trim($user['name'])
    );

$initials = '';

foreach (
    array_slice(
        $name_parts,
        0,
        2
    ) as $part
) {

    if ($part !== '') {

        $initials .=
            strtoupper(
                substr(
                    $part,
                    0,
                    1
                )
            );
    }
}

if ($initials === '') {

    $initials = 'U';
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
        My Profile | Medicine Aapki Gaw Mein
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                #f4f7fb;

            color:
                #212529;
        }


        /* =================================================
           PAGE
        ================================================= */

        .profile-page {

            min-height: 100vh;

            padding:
                40px 20px 60px;
        }


        .profile-container {

            width: 100%;

            max-width: 1100px;

            margin: 0 auto;
        }


        /* =================================================
           TOP BAR
        ================================================= */

        .profile-top {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 25px;
        }


        .brand-area {

            display: flex;

            align-items: center;

            gap: 14px;
        }


        .brand-icon {

            width: 48px;

            height: 48px;

            border-radius: 14px;

            background:
                linear-gradient(
                    135deg,
                    #0d6efd,
                    #0a58ca
                );

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 25px;

            box-shadow:
                0 8px 20px
                rgba(13, 110, 253, .20);
        }


        .brand-text h1 {

            margin: 0;

            font-size: 21px;

            font-weight: 700;
        }


        .brand-text p {

            margin: 3px 0 0;

            color: #718096;

            font-size: 13px;
        }


        .back-home {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            text-decoration: none;

            color: #0d6efd;

            font-size: 14px;

            font-weight: 600;

            padding:
                10px 15px;

            background: #ffffff;

            border:
                1px solid #e2e8f0;

            border-radius: 9px;
        }


        .back-home:hover {

            background: #f8fbff;

            text-decoration: none;
        }


        /* =================================================
           ALERTS
        ================================================= */

        .alert {

            padding:
                14px 17px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-size: 14px;

            border: 1px solid transparent;
        }


        .alert-success {

            background: #ecfdf3;

            color: #087443;

            border-color: #bbf7d0;
        }


        .alert-danger {

            background: #fff1f2;

            color: #b42318;

            border-color: #fecdd3;
        }


        /* =================================================
           LAYOUT
        ================================================= */

        .profile-grid {

            display: grid;

            grid-template-columns:
                300px 1fr;

            gap: 24px;

            align-items: start;
        }


        /* =================================================
           SIDEBAR
        ================================================= */

        .profile-sidebar {

            background: #ffffff;

            border-radius: 18px;

            padding: 25px;

            border:
                1px solid #e8edf3;

            box-shadow:
                0 10px 35px
                rgba(20, 40, 70, .06);
        }


        .avatar {

            width: 92px;

            height: 92px;

            margin: 0 auto 15px;

            border-radius: 50%;

            background:
                linear-gradient(
                    135deg,
                    #0d6efd,
                    #6ea8fe
                );

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 31px;

            font-weight: 700;

            box-shadow:
                0 10px 25px
                rgba(13, 110, 253, .22);
        }


        .sidebar-name {

            text-align: center;

            font-size: 20px;

            font-weight: 700;

            margin-bottom: 5px;
        }


        .sidebar-mobile {

            text-align: center;

            color: #718096;

            font-size: 14px;

            margin-bottom: 20px;
        }


        .account-badge {

            display: flex;

            justify-content: center;

            margin-bottom: 22px;
        }


        .badge {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding:
                6px 11px;

            border-radius: 30px;

            font-size: 12px;

            font-weight: 600;

            background: #ecfdf3;

            color: #087443;
        }


        .sidebar-menu {

            border-top:
                1px solid #edf1f5;

            padding-top: 18px;
        }


        .sidebar-menu a {

            display: flex;

            align-items: center;

            gap: 11px;

            padding:
                12px 13px;

            border-radius: 9px;

            color: #4a5568;

            text-decoration: none;

            font-size: 14px;

            font-weight: 600;

            margin-bottom: 5px;
        }


        .sidebar-menu a:hover {

            background: #f1f6ff;

            color: #0d6efd;
        }


        .sidebar-menu a.logout {

            color: #dc3545;
        }


        .sidebar-menu a.logout:hover {

            background: #fff1f2;

            color: #dc3545;
        }


        /* =================================================
           MAIN CONTENT
        ================================================= */

        .profile-content {

            display: flex;

            flex-direction: column;

            gap: 22px;
        }


        .profile-card {

            background: #ffffff;

            border:
                1px solid #e8edf3;

            border-radius: 18px;

            padding: 28px;

            box-shadow:
                0 10px 35px
                rgba(20, 40, 70, .06);
        }


        .card-header {

            display: flex;

            align-items: center;

            gap: 13px;

            padding-bottom: 20px;

            margin-bottom: 22px;

            border-bottom:
                1px solid #edf1f5;
        }


        .card-header-icon {

            width: 42px;

            height: 42px;

            border-radius: 11px;

            background: #eef5ff;

            color: #0d6efd;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 19px;
        }


        .card-header h2 {

            margin: 0;

            font-size: 19px;

            font-weight: 700;
        }


        .card-header p {

            margin: 3px 0 0;

            font-size: 12px;

            color: #718096;
        }


        /* =================================================
           FORM
        ================================================= */

        .form-grid {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap:
                18px;
        }


        .form-group {

            margin: 0;
        }


        .form-group.full {

            grid-column:
                1 / -1;
        }


        label {

            display: block;

            margin-bottom: 7px;

            font-size: 13px;

            font-weight: 700;

            color: #344054;
        }


        .required {

            color: #dc3545;
        }


        input,
        textarea {

            width: 100%;

            border:
                1px solid #d9e0e8;

            border-radius: 10px;

            padding:
                12px 13px;

            background: #ffffff;

            color: #1f2937;

            font-size: 14px;

            outline: none;

            transition:
                border-color .2s,
                box-shadow .2s;
        }


        input:focus,
        textarea:focus {

            border-color: #0d6efd;

            box-shadow:
                0 0 0 3px
                rgba(13, 110, 253, .10);
        }


        input[readonly] {

            background: #f7f9fc;

            color: #667085;

            cursor: not-allowed;
        }


        textarea {

            min-height: 105px;

            resize: vertical;
        }


        .input-hint {

            margin-top: 5px;

            font-size: 11px;

            color: #98a2b3;
        }


        .form-actions {

            display: flex;

            justify-content: flex-end;

            margin-top: 22px;

            padding-top: 20px;

            border-top:
                1px solid #edf1f5;
        }


        .btn {

            border: 0;

            border-radius: 9px;

            padding:
                11px 20px;

            font-size: 14px;

            font-weight: 700;

            cursor: pointer;

            text-decoration: none;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;
        }


        .btn-primary {

            color: #ffffff;

            background:
                linear-gradient(
                    135deg,
                    #0d6efd,
                    #0a58ca
                );

            box-shadow:
                0 7px 18px
                rgba(13, 110, 253, .18);
        }


        .btn-primary:hover {

            color: #ffffff;

            background:
                linear-gradient(
                    135deg,
                    #0b5ed7,
                    #084db3
                );
        }


        /* =================================================
           PASSWORD CARD
        ================================================= */

        .password-note {

            background: #f8fafc;

            border:
                1px solid #e7edf3;

            padding:
                12px 14px;

            border-radius: 9px;

            color: #667085;

            font-size: 12px;

            margin-bottom: 20px;
        }


        /* =================================================
           QUICK ACTIONS
        ================================================= */

        .quick-actions {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 12px;
        }


        .quick-action {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 14px;

            border:
                1px solid #e5eaf0;

            border-radius: 11px;

            color: #344054;

            text-decoration: none;

            transition:
                all .2s;
        }


        .quick-action:hover {

            border-color: #b9d4ff;

            background: #f8fbff;

            text-decoration: none;

            transform:
                translateY(-1px);
        }


        .quick-icon {

            width: 39px;

            height: 39px;

            border-radius: 9px;

            background: #eef5ff;

            color: #0d6efd;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 17px;
        }


        .quick-action strong {

            display: block;

            font-size: 13px;

            margin-bottom: 2px;
        }


        .quick-action small {

            color: #98a2b3;

            font-size: 11px;
        }


        /* =================================================
           FOOTER
        ================================================= */

        .page-footer {

            text-align: center;

            color: #98a2b3;

            font-size: 12px;

            margin-top: 25px;
        }


        .page-footer strong {

            color: #0d6efd;
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 850px) {

            .profile-grid {

                grid-template-columns:
                    1fr;
            }


            .profile-sidebar {

                max-width: 100%;
            }

        }


        @media (max-width: 600px) {

            .profile-page {

                padding:
                    20px 12px 40px;
            }


            .profile-top {

                align-items:
                    flex-start;

                flex-direction:
                    column;
            }


            .back-home {

                width: 100%;

                justify-content:
                    center;
            }


            .profile-card {

                padding: 20px;
            }


            .form-grid {

                grid-template-columns:
                    1fr;

                gap: 15px;
            }


            .form-group.full {

                grid-column:
                    auto;
            }


            .quick-actions {

                grid-template-columns:
                    1fr;
            }


            .form-actions {

                display: block;
            }


            .form-actions .btn {

                width: 100%;
            }

        }

    </style>

</head>


<body>


<div class="profile-page">

    <div class="profile-container">


        <!-- =================================================
             TOP
        ================================================== -->

        <div class="profile-top">

            <div class="brand-area">

                <div class="brand-icon">
                    💊
                </div>

                <div class="brand-text">

                    <h1>
                        My Profile
                    </h1>

                    <p>
                        Medicine Aapki Gaw Mein
                    </p>

                </div>

            </div>


            <a
                href="index.php"
                class="back-home"
            >
                ← Continue Shopping
            </a>

        </div>


        <!-- =================================================
             ALERTS
        ================================================== -->

        <?php if ($success !== ''): ?>

            <div class="alert alert-success">

                ✓

                <?= htmlspecialchars(
                    $success,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        <?php endif; ?>


        <?php if ($error !== ''): ?>

            <div class="alert alert-danger">

                ⚠

                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             MAIN GRID
        ================================================== -->

        <div class="profile-grid">


            <!-- =================================================
                 SIDEBAR
            ================================================== -->

            <aside class="profile-sidebar">


                <div class="avatar">

                    <?= htmlspecialchars(
                        $initials,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>


                <div class="sidebar-name">

                    <?= htmlspecialchars(
                        $user['name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>


                <div class="sidebar-mobile">

                    +91
                    <?= htmlspecialchars(
                        $user['mobile'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>


                <div class="account-badge">

                    <span class="badge">

                        ● Active Customer

                    </span>

                </div>


                <div class="sidebar-menu">


                    <a href="profile.php">

                        👤

                        <span>
                            My Profile
                        </span>

                    </a>


                    <a href="orders.php">

                        📦

                        <span>
                            My Orders
                        </span>

                    </a>


                    <a href="cart.php">

                        🛒

                        <span>
                            My Cart
                        </span>

                    </a>


                    <a
                        href="index.php"
                    >

                        🏠

                        <span>
                            Home
                        </span>

                    </a>


                    <a
                        href="logout.php"
                        class="logout"
                        onclick="
                            return confirm(
                                'Kya aap logout karna chahte hain?'
                            );
                        "
                    >

                        ↪

                        <span>
                            Logout
                        </span>

                    </a>

                </div>

            </aside>


            <!-- =================================================
                 CONTENT
            ================================================== -->

            <main class="profile-content">


                <!-- =================================================
                     PROFILE INFORMATION
                ================================================== -->

                <div class="profile-card">


                    <div class="card-header">

                        <div class="card-header-icon">
                            👤
                        </div>

                        <div>

                            <h2>
                                Personal Information
                            </h2>

                            <p>
                                Apni profile aur delivery details update karein.
                            </p>

                        </div>

                    </div>


                    <form
                        method="POST"
                        action="profile.php"
                    >

                        <div class="form-grid">


                            <!-- NAME -->

                            <div class="form-group">

                                <label for="name">

                                    Full Name

                                    <span class="required">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    maxlength="100"
                                    value="<?= htmlspecialchars(
                                        $user['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    required
                                >

                            </div>


                            <!-- MOBILE -->

                            <div class="form-group">

                                <label for="mobile">

                                    Mobile Number

                                </label>

                                <input
                                    type="text"
                                    id="mobile"
                                    value="<?= htmlspecialchars(
                                        $user['mobile'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    readonly
                                >

                                <div class="input-hint">

                                    Mobile number change karne ke liye
                                    account verification required ho sakta hai.

                                </div>

                            </div>


                            <!-- EMAIL -->

                            <div class="form-group">

                                <label for="email">

                                    Email Address

                                </label>

                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    maxlength="150"
                                    placeholder="Enter email address"
                                    value="<?= htmlspecialchars(
                                        $user['email'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                            </div>


                            <!-- CITY -->

                            <div class="form-group">

                                <label for="city">

                                    City / Village

                                </label>

                                <input
                                    type="text"
                                    id="city"
                                    name="city"
                                    maxlength="100"
                                    placeholder="Forbesganj"
                                    value="<?= htmlspecialchars(
                                        $user['city'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                            </div>


                            <!-- STATE -->

                            <div class="form-group">

                                <label for="state">

                                    State

                                </label>

                                <input
                                    type="text"
                                    id="state"
                                    name="state"
                                    maxlength="100"
                                    placeholder="Bihar"
                                    value="<?= htmlspecialchars(
                                        $user['state'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                            </div>


                            <!-- PINCODE -->

                            <div class="form-group">

                                <label for="pincode">

                                    Pincode

                                </label>

                                <input
                                    type="text"
                                    id="pincode"
                                    name="pincode"
                                    maxlength="6"
                                    inputmode="numeric"
                                    pattern="[0-9]{6}"
                                    placeholder="6 digit pincode"
                                    value="<?= htmlspecialchars(
                                        $user['pincode'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                            </div>


                            <!-- ADDRESS -->

                            <div class="form-group full">

                                <label for="address">

                                    Delivery Address

                                </label>

                                <textarea
                                    id="address"
                                    name="address"
                                    maxlength="500"
                                    placeholder="House no, village, street, landmark..."
                                ><?= htmlspecialchars(
                                    $user['address'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?></textarea>

                            </div>


                        </div>


                        <div class="form-actions">

                            <button
                                type="submit"
                                name="update_profile"
                                class="btn btn-primary"
                            >

                                ✓

                                Save Changes

                            </button>

                        </div>

                    </form>

                </div>


                <!-- =================================================
                     PASSWORD
                ================================================== -->

                <div class="profile-card">


                    <div class="card-header">

                        <div class="card-header-icon">
                            🔒
                        </div>

                        <div>

                            <h2>
                                Change Password
                            </h2>

                            <p>
                                Apne account ko secure rakhein.
                            </p>

                        </div>

                    </div>


                    <div class="password-note">

                        🔐

                        Password change karne ke liye
                        current password enter karna zaroori hai.

                    </div>


                    <form
                        method="POST"
                        action="profile.php"
                        autocomplete="off"
                    >

                        <div class="form-grid">


                            <!-- CURRENT -->

                            <div class="form-group">

                                <label for="current_password">

                                    Current Password

                                </label>

                                <input
                                    type="password"
                                    id="current_password"
                                    name="current_password"
                                    placeholder="Current password"
                                    autocomplete="current-password"
                                    required
                                >

                            </div>


                            <!-- NEW -->

                            <div class="form-group">

                                <label for="new_password">

                                    New Password

                                </label>

                                <input
                                    type="password"
                                    id="new_password"
                                    name="new_password"
                                    minlength="6"
                                    placeholder="Minimum 6 characters"
                                    autocomplete="new-password"
                                    required
                                >

                            </div>


                            <!-- CONFIRM -->

                            <div class="form-group">

                                <label for="confirm_password">

                                    Confirm New Password

                                </label>

                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    minlength="6"
                                    placeholder="Repeat new password"
                                    autocomplete="new-password"
                                    required
                                >

                            </div>


                        </div>


                        <div class="form-actions">

                            <button
                                type="submit"
                                name="change_password"
                                class="btn btn-primary"
                            >

                                🔒

                                Change Password

                            </button>

                        </div>

                    </form>

                </div>


                <!-- =================================================
                     QUICK ACTIONS
                ================================================== -->

                <div class="profile-card">


                    <div class="card-header">

                        <div class="card-header-icon">
                            ⚡
                        </div>

                        <div>

                            <h2>
                                Quick Actions
                            </h2>

                            <p>
                                Frequently used options.
                            </p>

                        </div>

                    </div>


                    <div class="quick-actions">


                        <a
                            href="orders.php"
                            class="quick-action"
                        >

                            <div class="quick-icon">
                                📦
                            </div>

                            <div>

                                <strong>
                                    My Orders
                                </strong>

                                <small>
                                    Order history dekhein
                                </small>

                            </div>

                        </a>


                        <a
                            href="cart.php"
                            class="quick-action"
                        >

                            <div class="quick-icon">
                                🛒
                            </div>

                            <div>

                                <strong>
                                    Shopping Cart
                                </strong>

                                <small>
                                    Cart items dekhein
                                </small>

                            </div>

                        </a>


                        <a
                            href="medicines.php"
                            class="quick-action"
                        >

                            <div class="quick-icon">
                                💊
                            </div>

                            <div>

                                <strong>
                                    Medicines
                                </strong>

                                <small>
                                    Medicines browse karein
                                </small>

                            </div>

                        </a>


                        <a
                            href="prescription-upload.php"
                            class="quick-action"
                        >

                            <div class="quick-icon">
                                📄
                            </div>

                            <div>

                                <strong>
                                    Upload Prescription
                                </strong>

                                <small>
                                    Prescription upload karein
                                </small>

                            </div>

                        </a>


                    </div>

                </div>


            </main>

        </div>


        <!-- =================================================
             FOOTER
        ================================================== -->

        <div class="page-footer">

            © <?= date('Y') ?>

            <strong>
                Medicine Aapki Gaw Mein
            </strong>

            · Your trusted local medicine store

        </div>

    </div>

</div>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        // =================================================
        // PINCODE ONLY NUMBERS
        // =================================================

        const pincode =
            document.getElementById(
                'pincode'
            );


        if (pincode) {

            pincode.addEventListener(
                'input',
                function () {

                    this.value =
                        this.value
                            .replace(/\D/g, '')
                            .slice(0, 6);

                }
            );

        }


        // =================================================
        // PASSWORD MATCH
        // =================================================

        const passwordForm =
            document.querySelector(
                'button[name="change_password"]'
            )?.closest('form');


        if (passwordForm) {

            passwordForm.addEventListener(
                'submit',
                function (event) {

                    const newPassword =
                        document.getElementById(
                            'new_password'
                        ).value;

                    const confirmPassword =
                        document.getElementById(
                            'confirm_password'
                        ).value;


                    if (
                        newPassword !==
                        confirmPassword
                    ) {

                        event.preventDefault();

                        alert(
                            'New password aur confirm password match nahi kar rahe.'
                        );

                    }

                }
            );

        }

    }
);

</script>

</body>

</html>
