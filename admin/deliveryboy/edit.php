```php
<?php

session_start();

require_once "../../config/database.php";


// =====================================================
// ADMIN AUTHENTICATION
// =====================================================

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../login.php");
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
// GET DELIVERY BOY ID
// =====================================================

$id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


if ($id <= 0) {

    $_SESSION['error_message'] =
        "Invalid delivery boy ID.";

    header("Location: index.php");
    exit;
}


// =====================================================
// VARIABLES
// =====================================================

$errors = [];

$name     = '';
$mobile   = '';
$email    = '';
$address  = '';
$city     = 'Forbesganj';
$state    = 'Bihar';
$pincode  = '';


// =====================================================
// FETCH DELIVERY BOY
// =====================================================

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        name,
        mobile,
        email,
        address,
        city,
        state,
        pincode,
        role,
        status
     FROM users
     WHERE id = ?
     AND role = 'delivery'
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$deliveryBoy = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$deliveryBoy) {

    $_SESSION['error_message'] =
        "Delivery boy not found.";

    header("Location: index.php");
    exit;
}


// =====================================================
// LOAD EXISTING DATA
// =====================================================

$name    = $deliveryBoy['name'] ?? '';
$mobile  = $deliveryBoy['mobile'] ?? '';
$email   = $deliveryBoy['email'] ?? '';
$address = $deliveryBoy['address'] ?? '';
$city    = $deliveryBoy['city'] ?? 'Forbesganj';
$state   = $deliveryBoy['state'] ?? 'Bihar';
$pincode = $deliveryBoy['pincode'] ?? '';


// =====================================================
// FORM SUBMIT
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name    = trim($_POST['name'] ?? '');
    $mobile  = trim($_POST['mobile'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city    = trim($_POST['city'] ?? '');
    $state   = trim($_POST['state'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');

    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';


    // =================================================
    // VALIDATION
    // =================================================

    if ($name === '') {

        $errors[] =
            "Delivery boy name is required.";

    }


    if ($mobile === '') {

        $errors[] =
            "Mobile number is required.";

    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {

        $errors[] =
            "Enter a valid 10 digit mobile number.";

    }


    if (
        $email !== '' &&
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $errors[] =
            "Enter a valid email address.";

    }


    // =================================================
    // PASSWORD VALIDATION
    // =================================================
    // Password is optional during edit.

    if ($password !== '') {

        if (strlen($password) < 6) {

            $errors[] =
                "Password must be at least 6 characters.";

        }

        if ($password !== $confirmPassword) {

            $errors[] =
                "Password and confirm password do not match.";

        }

    }


    // =================================================
    // PINCODE VALIDATION
    // =================================================

    if (
        $pincode !== '' &&
        !preg_match('/^[0-9]{6}$/', $pincode)
    ) {

        $errors[] =
            "Enter a valid 6 digit pincode.";

    }


    // =================================================
    // CHECK MOBILE
    // =================================================

    if (empty($errors)) {

        $checkMobile = mysqli_prepare(
            $conn,
            "SELECT id
             FROM users
             WHERE mobile = ?
             AND id != ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $checkMobile,
            "si",
            $mobile,
            $id
        );

        mysqli_stmt_execute($checkMobile);

        $result = mysqli_stmt_get_result($checkMobile);

        if (mysqli_num_rows($result) > 0) {

            $errors[] =
                "This mobile number is already registered.";

        }

        mysqli_stmt_close($checkMobile);
    }


    // =================================================
    // CHECK EMAIL
    // =================================================

    if (
        empty($errors) &&
        $email !== ''
    ) {

        $checkEmail = mysqli_prepare(
            $conn,
            "SELECT id
             FROM users
             WHERE email = ?
             AND id != ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $checkEmail,
            "si",
            $email,
            $id
        );

        mysqli_stmt_execute($checkEmail);

        $result = mysqli_stmt_get_result($checkEmail);

        if (mysqli_num_rows($result) > 0) {

            $errors[] =
                "This email address is already registered.";

        }

        mysqli_stmt_close($checkEmail);
    }


    // =================================================
    // UPDATE DELIVERY BOY
    // =================================================

    if (empty($errors)) {

        if ($password !== '') {

            // -----------------------------------------
            // UPDATE WITH NEW PASSWORD
            // -----------------------------------------

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            $stmt = mysqli_prepare(
                $conn,
                "UPDATE users
                 SET
                    name = ?,
                    mobile = ?,
                    email = ?,
                    password = ?,
                    address = ?,
                    city = ?,
                    state = ?,
                    pincode = ?
                 WHERE id = ?
                 AND role = 'delivery'"
            );


            mysqli_stmt_bind_param(
                $stmt,
                "ssssssssi",
                $name,
                $mobile,
                $email,
                $hashedPassword,
                $address,
                $city,
                $state,
                $pincode,
                $id
            );

        } else {

            // -----------------------------------------
            // UPDATE WITHOUT CHANGING PASSWORD
            // -----------------------------------------

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE users
                 SET
                    name = ?,
                    mobile = ?,
                    email = ?,
                    address = ?,
                    city = ?,
                    state = ?,
                    pincode = ?
                 WHERE id = ?
                 AND role = 'delivery'"
            );


            mysqli_stmt_bind_param(
                $stmt,
                "sssssssi",
                $name,
                $mobile,
                $email,
                $address,
                $city,
                $state,
                $pincode,
                $id
            );
        }


        if (mysqli_stmt_execute($stmt)) {

            mysqli_stmt_close($stmt);

            $_SESSION['success_message'] =
                "Delivery boy updated successfully.";

            header("Location: index.php");
            exit;

        } else {

            $errors[] =
                "Unable to update delivery boy. Please try again.";

            mysqli_stmt_close($stmt);
        }
    }
}


// =====================================================
// ADMIN DATA
// =====================================================

$adminName =
    $_SESSION['name'] ?? 'Administrator';


// =====================================================
// PAGE TITLE
// =====================================================

$pageTitle =
    "Edit Delivery Boy";

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
    font-family: 'Rubik', Arial, sans-serif;
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

.admin-wrapper {
    display: flex;
    min-height: 100vh;
}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {
    width: 250px;
    background: linear-gradient(
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
    border-bottom: 1px solid rgba(255,255,255,.12);
}

.brand-icon {
    width: 48px;
    height: 48px;
    background: rgba(255,255,255,.15);
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
    color: rgba(255,255,255,.75);
}

.sidebar-menu {
    padding: 18px 12px;
}

.menu-title {
    color: rgba(255,255,255,.55);
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
    color: rgba(255,255,255,.85);
    padding: 12px 13px;
    border-radius: 8px;
    margin-bottom: 4px;
    font-size: 13px;
    transition: .2s;
}

.sidebar-menu a:hover,
.sidebar-menu a.active {
    background: rgba(255,255,255,.15);
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
    width: calc(100% - 250px);
    min-height: 100vh;
}


/* =====================================================
   TOPBAR
===================================================== */

.topbar {
    height: 75px;
    background: #fff;
    border-bottom: 1px solid #e9edf3;
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

.admin-profile {
    display: flex;
    align-items: center;
    gap: 10px;
}

.admin-avatar {
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

.admin-info strong {
    display: block;
    font-size: 13px;
}

.admin-info span {
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
    display: flex;
    align-items: center;
    justify-content: space-between;
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

.back-btn {
    background: #fff;
    border: 1px solid #e3e7ed;
    color: #555;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
}

.back-btn:hover {
    border-color: #51b848;
    color: #238b39;
}


/* =====================================================
   FORM CARD
===================================================== */

.form-card {
    background: #fff;
    border: 1px solid #edf0f4;
    border-radius: 14px;
    max-width: 1000px;
    overflow: hidden;
}

.form-header {
    padding: 20px 25px;
    border-bottom: 1px solid #edf0f4;
}

.form-header h3 {
    font-size: 16px;
    color: #222;
}

.form-header p {
    color: #999;
    font-size: 11px;
    margin-top: 5px;
}

.form-body {
    padding: 25px;
}


/* =====================================================
   ERROR
===================================================== */

.error-box {
    background: #fff0f1;
    border: 1px solid #ffd5d9;
    color: #a51d2d;
    border-radius: 9px;
    padding: 14px 16px;
    margin-bottom: 20px;
    font-size: 12px;
}

.error-box ul {
    margin-left: 18px;
}

.error-box li {
    margin: 4px 0;
}


/* =====================================================
   GRID
===================================================== */

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full {
    grid-column: 1 / -1;
}

.form-group label {
    font-size: 12px;
    font-weight: 600;
    color: #444;
    margin-bottom: 7px;
}

.required {
    color: #e33445;
}

.form-control {
    width: 100%;
    border: 1px solid #dfe4ea;
    border-radius: 8px;
    padding: 11px 13px;
    font-family: inherit;
    font-size: 12px;
    color: #333;
    outline: none;
    background: #fff;
    transition: .2s;
}

.form-control:focus {
    border-color: #51b848;
    box-shadow: 0 0 0 3px rgba(81,184,72,.10);
}

textarea.form-control {
    min-height: 90px;
    resize: vertical;
}

.form-help {
    color: #999;
    font-size: 10px;
    margin-top: 5px;
}


/* =====================================================
   PASSWORD NOTE
===================================================== */

.password-note {
    background: #f8faf9;
    border: 1px solid #e4eee6;
    color: #66736a;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 11px;
    margin-bottom: 20px;
}


/* =====================================================
   ACTIONS
===================================================== */

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 25px;
    margin-top: 25px;
    border-top: 1px solid #edf0f4;
}

.btn {
    border: none;
    border-radius: 8px;
    padding: 11px 20px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

.btn-cancel {
    background: #f1f3f5;
    color: #555;
}

.btn-cancel:hover {
    background: #e7e9ec;
}

.btn-primary {
    background: #238b39;
    color: #fff;
}

.btn-primary:hover {
    background: #1d7530;
}


/* =====================================================
   MOBILE
===================================================== */

@media (max-width: 900px) {

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


@media (max-width: 650px) {

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-group.full {
        grid-column: auto;
    }

    .page-header {
        display: block;
    }

    .back-btn {
        display: inline-block;
        margin-top: 12px;
    }

    .admin-info {
        display: none;
    }

    .form-body {
        padding: 18px;
    }

    .form-actions {
        flex-direction: column;
    }

    .btn {
        width: 100%;
    }

}

</style>

</head>


<body>


<div class="admin-wrapper">


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">

    <div class="sidebar-brand">

        <div class="brand-icon">
            💊
        </div>

        <h2>
            Medicine Aapki<br>
            Gaw Mein
        </h2>

        <p>
            Administration Panel
        </p>

    </div>


    <nav class="sidebar-menu">

        <div class="menu-title">
            Main Menu
        </div>


        <a href="../index.php">
            <span class="menu-icon">📊</span>
            Dashboard
        </a>


        <a href="../medicines.php">
            <span class="menu-icon">💊</span>
            Medicines
        </a>


        <a href="../orders.php">
            <span class="menu-icon">📦</span>
            Orders
        </a>


        <a href="../prescriptions.php">
            <span class="menu-icon">📄</span>
            Prescriptions
        </a>


        <a href="index.php" class="active">
            <span class="menu-icon">🚚</span>
            Delivery Boys
        </a>


        <a href="../hero.php">
            <span class="menu-icon">🖼️</span>
            Hero Section
        </a>


        <a href="../testimonials.php">
            <span class="menu-icon">💬</span>
            Testimonials
        </a>


        <a href="../contact-messages.php">
            <span class="menu-icon">✉️</span>
            Contact Messages
        </a>


        <div class="menu-title">
            Account
        </div>


        <a href="../../index.php">
            <span class="menu-icon">🌐</span>
            View Website
        </a>


        <a href="../logout.php">
            <span class="menu-icon">🚪</span>
            Logout
        </a>

    </nav>

</aside>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="main-content">


<header class="topbar">

    <div class="topbar-title">

        <h1>
            Edit Delivery Boy
        </h1>

        <p>
            Medicine Aapki Gaw Mein Admin Panel
        </p>

    </div>


    <div class="admin-profile">

        <div class="admin-avatar">

            <?= e(
                strtoupper(
                    substr($adminName, 0, 1)
                )
            ) ?>

        </div>


        <div class="admin-info">

            <strong>
                <?= e($adminName) ?>
            </strong>

            <span>
                Administrator
            </span>

        </div>

    </div>

</header>


<div class="content">


<!-- =====================================================
     PAGE HEADER
===================================================== -->

<div class="page-header">

    <div>

        <h2>
            Edit Delivery Boy
        </h2>

        <p>
            Update delivery staff account information.
        </p>

    </div>


    <a
        href="index.php"
        class="back-btn"
    >
        ← Back to Delivery Boys
    </a>

</div>


<!-- =====================================================
     FORM
===================================================== -->

<div class="form-card">


<div class="form-header">

    <h3>
        Delivery Boy Information
    </h3>

    <p>
        Update the details below and save the changes.
    </p>

</div>


<div class="form-body">


<?php if (!empty($errors)): ?>

    <div class="error-box">

        <strong>
            Please fix the following:
        </strong>

        <ul>

            <?php foreach ($errors as $error): ?>

                <li>
                    <?= e($error) ?>
                </li>

            <?php endforeach; ?>

        </ul>

    </div>

<?php endif; ?>


<div class="password-note">

    🔐 <strong>Password:</strong>
    Leave the password fields blank if you do not want to change
    the current password.

</div>


<form
    method="POST"
    action=""
    autocomplete="off"
>


<div class="form-grid">


<!-- NAME -->

<div class="form-group">

    <label>
        Full Name
        <span class="required">*</span>
    </label>

    <input
        type="text"
        name="name"
        class="form-control"
        value="<?= e($name) ?>"
        placeholder="Enter delivery boy name"
        maxlength="100"
        required
    >

</div>


<!-- MOBILE -->

<div class="form-group">

    <label>
        Mobile Number
        <span class="required">*</span>
    </label>

    <input
        type="text"
        name="mobile"
        class="form-control"
        value="<?= e($mobile) ?>"
        placeholder="Enter 10 digit mobile number"
        maxlength="10"
        inputmode="numeric"
        required
    >

</div>


<!-- EMAIL -->

<div class="form-group">

    <label>
        Email Address
    </label>

    <input
        type="email"
        name="email"
        class="form-control"
        value="<?= e($email) ?>"
        placeholder="Enter email address"
        maxlength="150"
    >

</div>


<!-- PINCODE -->

<div class="form-group">

    <label>
        Pincode
    </label>

    <input
        type="text"
        name="pincode"
        class="form-control"
        value="<?= e($pincode) ?>"
        placeholder="Enter 6 digit pincode"
        maxlength="6"
        inputmode="numeric"
    >

</div>


<!-- PASSWORD -->

<div class="form-group">

    <label>
        New Password
    </label>

    <input
        type="password"
        name="password"
        class="form-control"
        placeholder="Leave blank to keep current password"
        minlength="6"
    >

    <span class="form-help">
        Enter a password only if you want to change it.
    </span>

</div>


<!-- CONFIRM PASSWORD -->

<div class="form-group">

    <label>
        Confirm New Password
    </label>

    <input
        type="password"
        name="confirm_password"
        class="form-control"
        placeholder="Re-enter new password"
        minlength="6"
    >

</div>


<!-- ADDRESS -->

<div class="form-group full">

    <label>
        Address
    </label>

    <textarea
        name="address"
        class="form-control"
        placeholder="Enter complete address"
    ><?= e($address) ?></textarea>

</div>


<!-- CITY -->

<div class="form-group">

    <label>
        City
    </label>

    <input
        type="text"
        name="city"
        class="form-control"
        value="<?= e($city) ?>"
        placeholder="Enter city"
        maxlength="100"
    >

</div>


<!-- STATE -->

<div class="form-group">

    <label>
        State
    </label>

    <input
        type="text"
        name="state"
        class="form-control"
        value="<?= e($state) ?>"
        placeholder="Enter state"
        maxlength="100"
    >

</div>


</div>


<!-- =====================================================
     ACTIONS
===================================================== -->

<div class="form-actions">

    <a
        href="index.php"
        class="btn btn-cancel"
    >
        Cancel
    </a>


    <button
        type="submit"
        class="btn btn-primary"
    >
        💾 Update Delivery Boy
    </button>

</div>


</form>


</div>

</div>


</div>

</main>

</div>

</body>

</html>
```
