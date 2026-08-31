<?php

session_start();

require_once "../config/database.php";


// =====================================================
// DELIVERY AUTHENTICATION
// =====================================================

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'delivery'
) {
    header("Location: login.php");
    exit;
}


// =====================================================
// HELPER
// =====================================================

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


// =====================================================
// DELIVERY BOY DATA
// =====================================================

$deliveryBoyId =
    (int)$_SESSION['user_id'];


// =====================================================
// MESSAGES
// =====================================================

$successMessage = '';
$errorMessage   = '';


// =====================================================
// FETCH PROFILE
// =====================================================

$profile = [];

$stmt = mysqli_prepare(
    $conn,
    "
    SELECT
        user_id,
        username,
        name,
        email,
        mobile,
        address,
        password
    FROM users
    WHERE user_id = ?
      AND role = 'delivery'
    LIMIT 1
    "
);


if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $deliveryBoyId
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    if ($result) {

        $profile =
            mysqli_fetch_assoc($result) ?: [];

    }

    mysqli_stmt_close($stmt);
}


// =====================================================
// PROFILE NOT FOUND
// =====================================================

if (empty($profile)) {

    session_destroy();

    header("Location: login.php");

    exit;
}


// =====================================================
// DEFAULT VALUES
// =====================================================

$deliveryBoyName =
    $profile['name'] ??
    $_SESSION['name'] ??
    'Delivery Boy';

$username =
    $profile['username'] ?? '';

$email =
    $profile['email'] ?? '';

$mobile =
    $profile['mobile'] ?? '';

$address =
    $profile['address'] ?? '';


// =====================================================
// UPDATE PROFILE
// =====================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action'])
) {


    $action =
        $_POST['action'];


    // =================================================
    // UPDATE BASIC PROFILE
    // =================================================

    if ($action === 'update_profile') {


        $name =
            trim(
                $_POST['name'] ?? ''
            );


        $email =
            trim(
                $_POST['email'] ?? ''
            );


        $mobile =
            trim(
                $_POST['mobile'] ?? ''
            );


        $address =
            trim(
                $_POST['address'] ?? ''
            );


        // ---------------------------------------------
        // VALIDATION
        // ---------------------------------------------

        if ($name === '') {

            $errorMessage =
                'Please enter your full name.';

        }

        elseif (
            $email !== '' &&
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $errorMessage =
                'Please enter a valid email address.';

        }

        elseif (
            $mobile !== '' &&
            !preg_match(
                '/^[0-9+\-\s]{10,15}$/',
                $mobile
            )
        ) {

            $errorMessage =
                'Please enter a valid mobile number.';

        }

        else {


            // -----------------------------------------
            // CHECK EMAIL DUPLICATE
            // -----------------------------------------

            $emailCheck = mysqli_prepare(
                $conn,
                "
                SELECT user_id
                FROM users
                WHERE email = ?
                  AND user_id != ?
                LIMIT 1
                "
            );


            $emailExists = false;


            if ($emailCheck) {

                mysqli_stmt_bind_param(
                    $emailCheck,
                    "si",
                    $email,
                    $deliveryBoyId
                );

                mysqli_stmt_execute(
                    $emailCheck
                );

                $emailResult =
                    mysqli_stmt_get_result(
                        $emailCheck
                    );

                $emailExists =
                    $emailResult &&
                    mysqli_num_rows(
                        $emailResult
                    ) > 0;

                mysqli_stmt_close(
                    $emailCheck
                );

            }


            if (
                $email !== '' &&
                $emailExists
            ) {

                $errorMessage =
                    'This email address is already in use.';

            }

            else {


                // -------------------------------------
                // UPDATE DATABASE
                // -------------------------------------

                $updateStmt =
                    mysqli_prepare(
                        $conn,
                        "
                        UPDATE users
                        SET
                            name = ?,
                            email = ?,
                            mobile = ?,
                            address = ?
                        WHERE user_id = ?
                          AND role = 'delivery'
                        "
                    );


                if ($updateStmt) {


                    mysqli_stmt_bind_param(
                        $updateStmt,
                        "ssssi",
                        $name,
                        $email,
                        $mobile,
                        $address,
                        $deliveryBoyId
                    );


                    if (
                        mysqli_stmt_execute(
                            $updateStmt
                        )
                    ) {

                        $successMessage =
                            'Profile updated successfully.';


                        // Update current values

                        $deliveryBoyName =
                            $name;

                        $username =
                            $profile['username'] ?? '';

                        $profile['name'] =
                            $name;

                        $profile['email'] =
                            $email;

                        $profile['mobile'] =
                            $mobile;

                        $profile['address'] =
                            $address;


                        // Update session name

                        $_SESSION['name'] =
                            $name;

                    }

                    else {

                        $errorMessage =
                            'Unable to update profile. Please try again.';

                    }


                    mysqli_stmt_close(
                        $updateStmt
                    );

                }

                else {

                    $errorMessage =
                        'Database error while updating profile.';

                }

            }

        }

    }


    // =================================================
    // CHANGE PASSWORD
    // =================================================

    elseif ($action === 'change_password') {


        $currentPassword =
            $_POST['current_password']
            ?? '';


        $newPassword =
            $_POST['new_password']
            ?? '';


        $confirmPassword =
            $_POST['confirm_password']
            ?? '';


        // ---------------------------------------------
        // VALIDATION
        // ---------------------------------------------

        if (
            $currentPassword === '' ||
            $newPassword === '' ||
            $confirmPassword === ''
        ) {

            $errorMessage =
                'Please fill all password fields.';

        }

        elseif (
            !password_verify(
                $currentPassword,
                $profile['password']
            )
        ) {

            $errorMessage =
                'Current password is incorrect.';

        }

        elseif (
            strlen($newPassword) < 6
        ) {

            $errorMessage =
                'New password must contain at least 6 characters.';

        }

        elseif (
            $newPassword !== $confirmPassword
        ) {

            $errorMessage =
                'New password and confirm password do not match.';

        }

        elseif (
            password_verify(
                $newPassword,
                $profile['password']
            )
        ) {

            $errorMessage =
                'New password must be different from the current password.';

        }

        else {


            // -----------------------------------------
            // HASH PASSWORD
            // -----------------------------------------

            $hashedPassword =
                password_hash(
                    $newPassword,
                    PASSWORD_DEFAULT
                );


            // -----------------------------------------
            // UPDATE PASSWORD
            // -----------------------------------------

            $passwordStmt =
                mysqli_prepare(
                    $conn,
                    "
                    UPDATE users
                    SET password = ?
                    WHERE user_id = ?
                      AND role = 'delivery'
                    "
                );


            if ($passwordStmt) {


                mysqli_stmt_bind_param(
                    $passwordStmt,
                    "si",
                    $hashedPassword,
                    $deliveryBoyId
                );


                if (
                    mysqli_stmt_execute(
                        $passwordStmt
                    )
                ) {

                    $successMessage =
                        'Password changed successfully.';

                    $profile['password'] =
                        $hashedPassword;

                }

                else {

                    $errorMessage =
                        'Unable to change password. Please try again.';

                }


                mysqli_stmt_close(
                    $passwordStmt
                );

            }

            else {

                $errorMessage =
                    'Database error while changing password.';

            }

        }

    }

}


// =====================================================
// FIRST LETTER
// =====================================================

$firstLetter =
    strtoupper(
        substr(
            $deliveryBoyName,
            0,
            1
        )
    );


// =====================================================
// PAGE TITLE
// =====================================================

$pageTitle =
    "My Profile";

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
    <?= e($pageTitle) ?>
    | Medicine Aapki Gaw Mein
</title>


<link
    href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap"
    rel="stylesheet"
>


<style>

/* =====================================================
   RESET
===================================================== */

* {

    margin: 0;

    padding: 0;

    box-sizing: border-box;

}


body {

    font-family:
        'Rubik',
        Arial,
        sans-serif;

    background: #f5f7fb;

    color: #333;

}


a {

    text-decoration: none;

    color: inherit;

}


/* =====================================================
   LAYOUT
===================================================== */

.delivery-wrapper {

    display: flex;

    min-height: 100vh;

}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {

    width: 250px;

    background:
        linear-gradient(
            180deg,
            #1f8b38,
            #166b2d
        );

    color: #fff;

    position: fixed;

    left: 0;

    top: 0;

    bottom: 0;

    z-index: 1000;

    overflow-y: auto;

}


.sidebar-brand {

    padding: 25px 20px;

    border-bottom:
        1px solid
        rgba(255,255,255,.12);

}


.brand-icon {

    width: 48px;

    height: 48px;

    background:
        rgba(255,255,255,.15);

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 25px;

    margin-bottom: 12px;

}


.sidebar-brand h2 {

    font-size: 17px;

    line-height: 1.4;

}


.sidebar-brand p {

    font-size: 11px;

    margin-top: 5px;

    color:
        rgba(255,255,255,.75);

}


/* =====================================================
   MENU
===================================================== */

.sidebar-menu {

    padding: 18px 12px;

}


.menu-title {

    color:
        rgba(255,255,255,.55);

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: 1px;

    padding: 10px 12px;

    margin-top: 5px;

}


.sidebar-menu a {

    display: flex;

    align-items: center;

    gap: 12px;

    color:
        rgba(255,255,255,.85);

    padding: 12px 13px;

    border-radius: 8px;

    margin-bottom: 4px;

    font-size: 13px;

    transition: .2s;

}


.sidebar-menu a:hover,
.sidebar-menu a.active {

    background:
        rgba(255,255,255,.15);

    color: #fff;

}


.menu-icon {

    width: 24px;

    text-align: center;

    font-size: 16px;

}


/* =====================================================
   MAIN
===================================================== */

.main-content {

    margin-left: 250px;

    width:
        calc(100% - 250px);

    min-height: 100vh;

}


/* =====================================================
   TOPBAR
===================================================== */

.topbar {

    height: 75px;

    background: #fff;

    border-bottom:
        1px solid #e9edf3;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 30px;

}


.topbar-title h1 {

    font-size: 21px;

    color: #222;

    font-weight: 600;

}


.topbar-title p {

    color: #999;

    font-size: 12px;

    margin-top: 3px;

}


.delivery-profile {

    display: flex;

    align-items: center;

    gap: 10px;

}


.delivery-avatar {

    width: 40px;

    height: 40px;

    background: #e8f7eb;

    color: #278c3c;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: 700;

}


.delivery-info strong {

    display: block;

    font-size: 13px;

}


.delivery-info span {

    display: block;

    color: #999;

    font-size: 11px;

    margin-top: 2px;

}


/* =====================================================
   CONTENT
===================================================== */

.content {

    padding: 30px;

}


/* =====================================================
   PAGE HEADER
===================================================== */

.page-header {

    margin-bottom: 22px;

}


.page-header h2 {

    font-size: 20px;

    color: #222;

}


.page-header p {

    color: #999;

    font-size: 12px;

    margin-top: 5px;

}


/* =====================================================
   ALERT
===================================================== */

.alert {

    padding: 13px 16px;

    border-radius: 9px;

    margin-bottom: 20px;

    font-size: 12px;

    font-weight: 500;

}


.alert-success {

    background: #e9f8ed;

    color: #197333;

    border:
        1px solid #c8e8cf;

}


.alert-error {

    background: #fff0f1;

    color: #a51d2d;

    border:
        1px solid #f2c7cc;

}


/* =====================================================
   PROFILE GRID
===================================================== */

.profile-grid {

    display: grid;

    grid-template-columns:
        minmax(0, 1.35fr)
        minmax(300px, .65fr);

    gap: 20px;

}


/* =====================================================
   CARD
===================================================== */

.card {

    background: #fff;

    border:
        1px solid #edf0f4;

    border-radius: 14px;

    overflow: hidden;

    margin-bottom: 20px;

}


.card:last-child {

    margin-bottom: 0;

}


.card-header {

    padding: 18px 20px;

    border-bottom:
        1px solid #edf0f4;

}


.card-header h3 {

    font-size: 15px;

    color: #222;

}


.card-header p {

    font-size: 11px;

    color: #999;

    margin-top: 4px;

}


.card-body {

    padding: 22px;

}


/* =====================================================
   PROFILE HEADER
===================================================== */

.profile-summary {

    display: flex;

    align-items: center;

    gap: 16px;

    padding-bottom: 20px;

    margin-bottom: 20px;

    border-bottom:
        1px solid #edf0f4;

}


.large-avatar {

    width: 70px;

    height: 70px;

    flex-shrink: 0;

    border-radius: 18px;

    background:
        linear-gradient(
            135deg,
            #e7f7ea,
            #d4efda
        );

    color: #238b39;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 27px;

    font-weight: 700;

}


.profile-summary h3 {

    font-size: 18px;

    color: #222;

}


.profile-summary p {

    color: #999;

    font-size: 11px;

    margin-top: 5px;

}


.role-badge {

    display: inline-block;

    margin-top: 7px;

    padding: 5px 9px;

    border-radius: 15px;

    background: #eaf7ed;

    color: #238b39;

    font-size: 9px;

    font-weight: 600;

}


/* =====================================================
   FORM
===================================================== */

.form-grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 16px;

}


.form-group {

    margin-bottom: 2px;

}


.form-group.full {

    grid-column: 1 / -1;

}


.form-group label {

    display: block;

    color: #555;

    font-size: 11px;

    font-weight: 600;

    margin-bottom: 7px;

}


.form-control {

    width: 100%;

    height: 44px;

    border:
        1px solid #dfe4e8;

    border-radius: 8px;

    padding: 0 13px;

    font-family: inherit;

    font-size: 12px;

    color: #333;

    outline: none;

    background: #fff;

}


textarea.form-control {

    height: 95px;

    padding-top: 12px;

    padding-bottom: 12px;

    resize: vertical;

}


.form-control:focus {

    border-color: #51b848;

    box-shadow:
        0 0 0 3px
        rgba(81,184,72,.10);

}


.form-control:disabled {

    background: #f7f8fa;

    color: #999;

    cursor: not-allowed;

}


.form-help {

    color: #aaa;

    font-size: 9px;

    margin-top: 5px;

}


/* =====================================================
   BUTTON
===================================================== */

.btn {

    height: 44px;

    border: none;

    border-radius: 8px;

    padding: 0 18px;

    font-family: inherit;

    font-size: 12px;

    font-weight: 600;

    cursor: pointer;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    transition: .2s;

}


.btn-primary {

    background: #238b39;

    color: #fff;

}


.btn-primary:hover {

    background: #1b7330;

}


.btn-password {

    background: #f1f4f6;

    color: #444;

    border:
        1px solid #dfe5e9;

}


.btn-password:hover {

    background: #e8ecef;

}


.form-actions {

    margin-top: 20px;

    display: flex;

    justify-content: flex-end;

}


/* =====================================================
   ACCOUNT INFO
===================================================== */

.info-list {

    display: flex;

    flex-direction: column;

}


.info-item {

    display: flex;

    align-items: flex-start;

    gap: 12px;

    padding: 14px 0;

    border-bottom:
        1px solid #f0f2f5;

}


.info-item:last-child {

    border-bottom: none;

}


.info-icon {

    width: 35px;

    height: 35px;

    flex-shrink: 0;

    border-radius: 9px;

    background: #f0f8f2;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 15px;

}


.info-content span {

    display: block;

    color: #999;

    font-size: 9px;

    margin-bottom: 4px;

    text-transform: uppercase;

    letter-spacing: .4px;

}


.info-content strong {

    display: block;

    color: #333;

    font-size: 11px;

    word-break: break-word;

}


/* =====================================================
   SECURITY CARD
===================================================== */

.security-icon {

    width: 45px;

    height: 45px;

    border-radius: 12px;

    background: #eef5ff;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 20px;

    margin-bottom: 13px;

}


.security-card h4 {

    color: #333;

    font-size: 14px;

    margin-bottom: 5px;

}


.security-card p {

    color: #999;

    font-size: 10px;

    line-height: 1.6;

}


/* =====================================================
   PASSWORD FORM
===================================================== */

.password-form {

    margin-top: 18px;

}


.password-group {

    margin-bottom: 13px;

}


.password-group label {

    display: block;

    color: #555;

    font-size: 10px;

    font-weight: 600;

    margin-bottom: 6px;

}


.password-input-wrapper {

    position: relative;

}


.password-input {

    width: 100%;

    height: 42px;

    border:
        1px solid #dfe4e8;

    border-radius: 8px;

    padding:
        0 40px 0 12px;

    font-family: inherit;

    font-size: 11px;

    outline: none;

}


.password-input:focus {

    border-color: #51b848;

    box-shadow:
        0 0 0 3px
        rgba(81,184,72,.10);

}


.toggle-password {

    position: absolute;

    right: 12px;

    top: 50%;

    transform: translateY(-50%);

    border: none;

    background: none;

    cursor: pointer;

    font-size: 14px;

    color: #888;

}


/* =====================================================
   STATUS
===================================================== */

.account-status {

    display: flex;

    align-items: center;

    gap: 8px;

    margin-top: 15px;

    padding:
        10px 12px;

    border-radius: 8px;

    background: #eaf8ed;

    color: #197333;

    font-size: 10px;

    font-weight: 600;

}


.status-dot {

    width: 7px;

    height: 7px;

    border-radius: 50%;

    background: #2e9d45;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1050px) {

    .profile-grid {

        grid-template-columns: 1fr;

    }

}


@media (max-width: 850px) {

    .sidebar {

        display: none;

    }

    .main-content {

        margin-left: 0;

        width: 100%;

    }

    .topbar {

        padding: 0 18px;

    }

    .content {

        padding: 20px;

    }

}


@media (max-width: 600px) {

    .content {

        padding: 15px;

    }

    .form-grid {

        grid-template-columns: 1fr;

    }

    .form-group.full {

        grid-column: auto;

    }

    .delivery-info {

        display: none;

    }

    .topbar-title h1 {

        font-size: 18px;

    }

    .card-body {

        padding: 17px;

    }

    .profile-summary {

        align-items: flex-start;

    }

}

</style>

</head>


<body>


<div class="delivery-wrapper">


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">


    <div class="sidebar-brand">


        <div class="brand-icon">
            🚚
        </div>


        <h2>
            Medicine Aapki<br>
            Gaw Mein
        </h2>


        <p>
            Delivery Panel
        </p>


    </div>


    <nav class="sidebar-menu">


        <div class="menu-title">
            Delivery Menu
        </div>


        <a href="index.php">

            <span class="menu-icon">
                📊
            </span>

            Dashboard

        </a>


        <a href="orders.php">

            <span class="menu-icon">
                📦
            </span>

            My Orders

        </a>


        <a
            href="profile.php"
            class="active"
        >

            <span class="menu-icon">
                👤
            </span>

            My Profile

        </a>


        <div class="menu-title">
            Account
        </div>


        <a href="../index.php">

            <span class="menu-icon">
                🌐
            </span>

            View Website

        </a>


        <a href="logout.php">

            <span class="menu-icon">
                🚪
            </span>

            Logout

        </a>


    </nav>


</aside>


<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<main class="main-content">


<!-- =====================================================
     TOPBAR
===================================================== -->

<header class="topbar">


    <div class="topbar-title">

        <h1>
            My Profile
        </h1>

        <p>
            Manage your delivery account
        </p>

    </div>


    <div class="delivery-profile">


        <div class="delivery-avatar">

            <?= e($firstLetter) ?>

        </div>


        <div class="delivery-info">

            <strong>
                <?= e($deliveryBoyName) ?>
            </strong>

            <span>
                Delivery Boy
            </span>

        </div>


    </div>


</header>


<!-- =====================================================
     CONTENT
===================================================== -->

<div class="content">


<!-- =====================================================
     PAGE HEADER
===================================================== -->

<div class="page-header">

    <h2>
        Account Settings
    </h2>

    <p>
        Update your personal information and account security.
    </p>

</div>


<!-- =====================================================
     ALERTS
===================================================== -->

<?php if ($successMessage !== ''): ?>

    <div class="alert alert-success">

        ✓
        <?= e($successMessage) ?>

    </div>

<?php endif; ?>


<?php if ($errorMessage !== ''): ?>

    <div class="alert alert-error">

        ⚠
        <?= e($errorMessage) ?>

    </div>

<?php endif; ?>


<!-- =====================================================
     PROFILE GRID
===================================================== -->

<div class="profile-grid">


<!-- =====================================================
     LEFT COLUMN
===================================================== -->

<div>


    <!-- ================================================
         PROFILE CARD
    ================================================= -->

    <div class="card">


        <div class="card-header">

            <h3>
                👤 Personal Information
            </h3>

            <p>
                Keep your profile information up to date.
            </p>

        </div>


        <div class="card-body">


            <div class="profile-summary">


                <div class="large-avatar">

                    <?= e($firstLetter) ?>

                </div>


                <div>

                    <h3>
                        <?= e($deliveryBoyName) ?>
                    </h3>

                    <p>
                        @<?= e($username) ?>
                    </p>

                    <span class="role-badge">
                        🚚 Delivery Boy
                    </span>

                </div>


            </div>


            <form
                method="POST"
                action="profile.php"
            >


                <input
                    type="hidden"
                    name="action"
                    value="update_profile"
                >


                <div class="form-grid">


                    <!-- NAME -->

                    <div class="form-group">

                        <label for="name">
                            Full Name
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-control"
                            value="<?= e(
                                $profile['name'] ?? ''
                            ) ?>"
                            maxlength="100"
                            required
                        >

                    </div>


                    <!-- USERNAME -->

                    <div class="form-group">

                        <label for="username">
                            Username
                        </label>

                        <input
                            type="text"
                            id="username"
                            class="form-control"
                            value="<?= e(
                                $username
                            ) ?>"
                            disabled
                        >

                        <div class="form-help">
                            Username cannot be changed.
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
                            class="form-control"
                            value="<?= e(
                                $profile['email'] ?? ''
                            ) ?>"
                            maxlength="150"
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
                            name="mobile"
                            class="form-control"
                            value="<?= e(
                                $profile['mobile'] ?? ''
                            ) ?>"
                            maxlength="15"
                        >

                    </div>


                    <!-- ADDRESS -->

                    <div class="form-group full">

                        <label for="address">
                            Address
                        </label>

                        <textarea
                            id="address"
                            name="address"
                            class="form-control"
                            maxlength="500"
                            placeholder="Enter your address..."
                        ><?= e(
                            $profile['address'] ?? ''
                        ) ?></textarea>

                    </div>


                </div>


                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        ✓ Save Changes
                    </button>

                </div>


            </form>


        </div>


    </div>


    <!-- ================================================
         PASSWORD CARD
    ================================================= -->

    <div class="card security-card">


        <div class="card-header">

            <h3>
                🔐 Change Password
            </h3>

            <p>
                Update your password to keep your account secure.
            </p>

        </div>


        <div class="card-body">


            <div class="security-icon">
                🔒
            </div>


            <h4>
                Account Security
            </h4>


            <p>
                Use a strong password with at least
                6 characters.
            </p>


            <form
                method="POST"
                action="profile.php"
                class="password-form"
            >


                <input
                    type="hidden"
                    name="action"
                    value="change_password"
                >


                <!-- CURRENT PASSWORD -->

                <div class="password-group">

                    <label for="current_password">
                        Current Password
                    </label>

                    <div class="password-input-wrapper">

                        <input
                            type="password"
                            id="current_password"
                            name="current_password"
                            class="password-input"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            onclick="togglePassword(
                                'current_password',
                                this
                            )"
                        >
                            👁
                        </button>

                    </div>

                </div>


                <!-- NEW PASSWORD -->

                <div class="password-group">

                    <label for="new_password">
                        New Password
                    </label>

                    <div class="password-input-wrapper">

                        <input
                            type="password"
                            id="new_password"
                            name="new_password"
                            class="password-input"
                            minlength="6"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            onclick="togglePassword(
                                'new_password',
                                this
                            )"
                        >
                            👁
                        </button>

                    </div>

                </div>


                <!-- CONFIRM PASSWORD -->

                <div class="password-group">

                    <label for="confirm_password">
                        Confirm New Password
                    </label>

                    <div class="password-input-wrapper">

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="password-input"
                            minlength="6"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            onclick="togglePassword(
                                'confirm_password',
                                this
                            )"
                        >
                            👁
                        </button>

                    </div>

                </div>


                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn btn-password"
                    >
                        🔑 Change Password
                    </button>

                </div>


            </form>


        </div>


    </div>


</div>


<!-- =====================================================
     RIGHT COLUMN
===================================================== -->

<div>


    <!-- ================================================
         ACCOUNT INFORMATION
    ================================================= -->

    <div class="card">


        <div class="card-header">

            <h3>
                📋 Account Information
            </h3>

            <p>
                Your account details.
            </p>

        </div>


        <div class="card-body">


            <div class="info-list">


                <!-- USERNAME -->

                <div class="info-item">

                    <div class="info-icon">
                        👤
                    </div>

                    <div class="info-content">

                        <span>
                            Username
                        </span>

                        <strong>
                            <?= e(
                                $username ?: 'Not available'
                            ) ?>
                        </strong>

                    </div>

                </div>


                <!-- EMAIL -->

                <div class="info-item">

                    <div class="info-icon">
                        ✉️
                    </div>

                    <div class="info-content">

                        <span>
                            Email
                        </span>

                        <strong>
                            <?= e(
                                $profile['email']
                                ?: 'Not available'
                            ) ?>
                        </strong>

                    </div>

                </div>


                <!-- MOBILE -->

                <div class="info-item">

                    <div class="info-icon">
                        📱
                    </div>

                    <div class="info-content">

                        <span>
                            Mobile
                        </span>

                        <strong>
                            <?= e(
                                $profile['mobile']
                                ?: 'Not available'
                            ) ?>
                        </strong>

                    </div>

                </div>


                <!-- ROLE -->

                <div class="info-item">

                    <div class="info-icon">
                        🚚
                    </div>

                    <div class="info-content">

                        <span>
                            Account Role
                        </span>

                        <strong>
                            Delivery Boy
                        </strong>

                    </div>

                </div>


            </div>


            <div class="account-status">

                <span class="status-dot"></span>

                Account Active

            </div>


        </div>


    </div>


    <!-- ================================================
         DELIVERY INFORMATION
    ================================================= -->

    <div class="card">


        <div class="card-header">

            <h3>
                🚚 Delivery Account
            </h3>

            <p>
                Your role in the delivery system.
            </p>

        </div>


        <div class="card-body">


            <div class="info-list">


                <div class="info-item">

                    <div class="info-icon">
                        📦
                    </div>

                    <div class="info-content">

                        <span>
                            Assigned Work
                        </span>

                        <strong>
                            Manage Assigned Orders
                        </strong>

                    </div>

                </div>


                <div class="info-item">

                    <div class="info-icon">
                        📍
                    </div>

                    <div class="info-content">

                        <span>
                            Delivery Area
                        </span>

                        <strong>
                            Assigned by Administrator
                        </strong>

                    </div>

                </div>


                <div class="info-item">

                    <div class="info-icon">
                        📞
                    </div>

                    <div class="info-content">

                        <span>
                            Support
                        </span>

                        <strong>
                            Contact Administrator
                        </strong>

                    </div>

                </div>


            </div>


        </div>


    </div>


</div>


</div>


</div>


</main>


</div>


<script>

/* =====================================================
   PASSWORD TOGGLE
===================================================== */

function togglePassword(
    inputId,
    button
) {

    const input =
        document.getElementById(inputId);


    if (!input) {
        return;
    }


    if (
        input.type === 'password'
    ) {

        input.type = 'text';

        button.textContent = '🙈';

    }

    else {

        input.type = 'password';

        button.textContent = '👁';

    }

}

</script>


</body>

</html>

