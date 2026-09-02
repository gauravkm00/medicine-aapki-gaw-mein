<?php

session_start();

require_once "../config/database.php";


// =====================================================
// CUSTOMER AUTHENTICATION
// =====================================================

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    strtolower((string) $_SESSION['role']) !== 'customer'
) {
    header("Location: ../login.php");
    exit;
}

$customerId   = (int) $_SESSION['user_id'];
$customerName = $_SESSION['name'] ?? 'Customer';


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
// CSRF TOKEN
// =====================================================

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];


// =====================================================
// FORM VARIABLES
// =====================================================

$addressType = 'Home';
$fullName    = $customerName;
$mobile      = $_SESSION['mobile'] ?? '';
$address     = '';
$city        = 'Forbesganj';
$state       = 'Bihar';
$pincode     = '';
$isDefault   = 0;

$errors = [];


// =====================================================
// POST REQUEST
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // -------------------------------------------------
    // CSRF
    // -------------------------------------------------

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals(
            $_SESSION['csrf_token'],
            (string) $_POST['csrf_token']
        )
    ) {
        $errors[] = "Invalid request. Please try again.";
    }


    // -------------------------------------------------
    // GET FORM DATA
    // -------------------------------------------------

    $addressType = trim($_POST['address_type'] ?? 'Home');
    $fullName    = trim($_POST['full_name'] ?? '');
    $mobile      = trim($_POST['mobile'] ?? '');
    $address     = trim($_POST['address'] ?? '');
    $city        = trim($_POST['city'] ?? '');
    $state       = trim($_POST['state'] ?? '');
    $pincode     = trim($_POST['pincode'] ?? '');

    $isDefault = isset($_POST['is_default']) ? 1 : 0;


    // -------------------------------------------------
    // VALIDATION
    // -------------------------------------------------

    $allowedTypes = ['Home', 'Work', 'Other'];

    if (!in_array($addressType, $allowedTypes, true)) {
        $errors[] = "Please select a valid address type.";
    }

    if ($fullName === '') {
        $errors[] = "Full name is required.";
    } elseif (mb_strlen($fullName) < 2) {
        $errors[] = "Full name must contain at least 2 characters.";
    } elseif (mb_strlen($fullName) > 100) {
        $errors[] = "Full name cannot exceed 100 characters.";
    }


    if ($mobile === '') {
        $errors[] = "Mobile number is required.";
    } elseif (!preg_match('/^[0-9]{10,15}$/', $mobile)) {
        $errors[] = "Please enter a valid mobile number.";
    }


    if ($address === '') {
        $errors[] = "Address is required.";
    }


    if ($city === '') {
        $errors[] = "City is required.";
    }


    if ($state === '') {
        $errors[] = "State is required.";
    }


    if ($pincode === '') {
        $errors[] = "Pincode is required.";
    } elseif (!preg_match('/^[0-9]{4,10}$/', $pincode)) {
        $errors[] = "Please enter a valid pincode.";
    }


    // -------------------------------------------------
    // SAVE ADDRESS
    // -------------------------------------------------

    if (empty($errors)) {

        try {

            $conn->begin_transaction();


            // -----------------------------------------
            // IF DEFAULT ADDRESS
            // -----------------------------------------

            if ($isDefault === 1) {

                $stmt = $conn->prepare("
                    UPDATE addresses
                    SET is_default = 0
                    WHERE user_id = ?
                ");

                if (!$stmt) {
                    throw new Exception("Unable to prepare default address query.");
                }

                $stmt->bind_param(
                    "i",
                    $customerId
                );

                if (!$stmt->execute()) {
                    throw new Exception("Unable to update existing addresses.");
                }

                $stmt->close();
            }


            // -----------------------------------------
            // CHECK FIRST ADDRESS
            // -----------------------------------------

            $stmt = $conn->prepare("
                SELECT COUNT(*) AS total
                FROM addresses
                WHERE user_id = ?
            ");

            if (!$stmt) {
                throw new Exception("Unable to check existing addresses.");
            }

            $stmt->bind_param(
                "i",
                $customerId
            );

            $stmt->execute();

            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            $totalAddresses = (int) ($row['total'] ?? 0);

            $stmt->close();


            // -----------------------------------------
            // FIRST ADDRESS = DEFAULT
            // -----------------------------------------

            if ($totalAddresses === 0) {
                $isDefault = 1;
            }


            // -----------------------------------------
            // INSERT
            // -----------------------------------------

            $stmt = $conn->prepare("
                INSERT INTO addresses
                (
                    user_id,
                    address_type,
                    full_name,
                    mobile,
                    address,
                    city,
                    state,
                    pincode,
                    is_default
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception("Unable to prepare address query.");
            }

            $stmt->bind_param(
                "isssssssi",
                $customerId,
                $addressType,
                $fullName,
                $mobile,
                $address,
                $city,
                $state,
                $pincode,
                $isDefault
            );

            if (!$stmt->execute()) {
                throw new Exception("Unable to save address.");
            }

            $stmt->close();

            $conn->commit();


            // -----------------------------------------
            // SUCCESS
            // -----------------------------------------

            header("Location: addresses.php?added=1");
            exit;

        } catch (Throwable $e) {

            $conn->rollback();

            $errors[] = "Unable to save address. Please try again.";
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

    <title>Add Address | Medicine Aapki Gaw Mein</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Rubik', sans-serif;
            background: #f5f7f6;
            color: #263238;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(
                180deg,
                #1f8b38,
                #166b2d
            );
            color: #fff;
            z-index: 1000;
            overflow-y: auto;
        }

        .brand {
            padding: 22px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,.12);
        }

        .brand-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(255,255,255,.16);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
        }

        .brand-text strong {
            display: block;
            font-size: 15px;
        }

        .brand-text span {
            display: block;
            margin-top: 3px;
            font-size: 11px;
            opacity: .75;
        }

        .menu {
            padding: 18px 12px;
        }

        .menu-title {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 1px;
            opacity: .55;
            margin: 16px 10px 8px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 11px 13px;
            margin-bottom: 4px;
            border-radius: 9px;
            color: rgba(255,255,255,.82);
            font-size: 13px;
            transition: .2s;
        }

        .menu a:hover,
        .menu a.active {
            background: rgba(255,255,255,.15);
            color: #fff;
        }

        .menu-icon {
            width: 22px;
            text-align: center;
            font-size: 16px;
        }

        .main {
            margin-left: 250px;
            min-height: 100vh;
        }

        .topbar {
            height: 75px;
            background: #fff;
            border-bottom: 1px solid #e7ece9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
        }

        .topbar h1 {
            font-size: 21px;
            font-weight: 600;
        }

        .topbar p {
            font-size: 12px;
            color: #78909c;
            margin-top: 4px;
        }

        .profile-mini {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(
                135deg,
                #238b39,
                #51b848
            );
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .profile-mini strong {
            display: block;
            font-size: 13px;
        }

        .profile-mini span {
            display: block;
            color: #90a4ae;
            font-size: 11px;
            margin-top: 2px;
        }

        .content {
            padding: 30px;
            max-width: 1100px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #238b39;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 18px;
        }

        .card {
            background: #fff;
            border: 1px solid #e8eeea;
            border-radius: 13px;
            box-shadow: 0 4px 18px rgba(0,0,0,.035);
            overflow: hidden;
        }

        .card-header {
            padding: 20px 22px;
            border-bottom: 1px solid #edf1ef;
        }

        .card-header h2 {
            font-size: 17px;
            font-weight: 600;
        }

        .card-header p {
            color: #78909c;
            font-size: 12px;
            margin-top: 5px;
        }

        .form-body {
            padding: 24px 22px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 12px;
            font-weight: 500;
            color: #455a64;
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid #dce5e0;
            border-radius: 9px;
            padding: 11px 12px;
            font-family: inherit;
            font-size: 13px;
            color: #263238;
            outline: none;
            background: #fff;
            transition: .2s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #36a64b;
            box-shadow: 0 0 0 3px rgba(54,166,75,.08);
        }

        textarea {
            min-height: 105px;
            resize: vertical;
        }

        .hint {
            font-size: 10px;
            color: #90a4ae;
        }

        .default-box {
            grid-column: 1 / -1;
            background: #f3faf4;
            border: 1px solid #dcefe0;
            padding: 14px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .default-box input {
            width: 17px;
            height: 17px;
            accent-color: #238b39;
        }

        .default-box label {
            cursor: pointer;
            color: #263238;
        }

        .default-box small {
            display: block;
            margin-top: 3px;
            color: #78909c;
            font-size: 10px;
        }

        .errors {
            margin-bottom: 20px;
            padding: 13px 15px;
            border-radius: 9px;
            background: #fff1f1;
            border: 1px solid #ffd3d3;
            color: #c62828;
            font-size: 12px;
        }

        .errors div {
            margin-bottom: 4px;
        }

        .errors div:last-child {
            margin-bottom: 0;
        }

        .actions {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #edf1ef;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn {
            border: none;
            border-radius: 8px;
            padding: 11px 18px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .btn-secondary {
            background: #eef2ef;
            color: #546e7a;
        }

        .btn-primary {
            color: #fff;
            background: linear-gradient(
                135deg,
                #238b39,
                #51b848
            );
            box-shadow: 0 4px 10px rgba(35,139,57,.18);
        }

        @media (max-width: 850px) {

            .sidebar {
                width: 70px;
            }

            .brand {
                justify-content: center;
                padding: 15px 8px;
            }

            .brand-text,
            .menu-title,
            .menu a span:not(.menu-icon) {
                display: none;
            }

            .menu a {
                justify-content: center;
            }

            .main {
                margin-left: 70px;
            }
        }

        @media (max-width: 600px) {

            .topbar {
                padding: 0 15px;
            }

            .topbar h1 {
                font-size: 17px;
            }

            .profile-mini > div:last-child {
                display: none;
            }

            .content {
                padding: 18px 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .default-box {
                grid-column: auto;
            }

            .actions {
                flex-direction: column;
            }

            .actions .btn {
                width: 100%;
                justify-content: center;
            }
        }

    </style>

</head>

<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">

    <div class="brand">

        <div class="brand-icon">
            💊
        </div>

        <div class="brand-text">
            <strong>Medicine Aapki Gaw Mein</strong>
            <span>Customer Panel</span>
        </div>

    </div>


    <nav class="menu">

        <div class="menu-title">MAIN</div>

        <a href="index.php">
            <span class="menu-icon">📊</span>
            <span>Dashboard</span>
        </a>

        <a href="orders.php">
            <span class="menu-icon">📦</span>
            <span>My Orders</span>
        </a>

        <a href="../medicines.php">
            <span class="menu-icon">💊</span>
            <span>Browse Medicines</span>
        </a>

        <a href="../cart.php">
            <span class="menu-icon">🛒</span>
            <span>My Cart</span>
        </a>


        <div class="menu-title">PRESCRIPTION</div>

        <a href="prescriptions.php">
            <span class="menu-icon">📄</span>
            <span>Prescriptions</span>
        </a>

        <a href="upload-prescription.php">
            <span class="menu-icon">⬆️</span>
            <span>Upload Prescription</span>
        </a>


        <div class="menu-title">ACCOUNT</div>

        <a href="profile.php">
            <span class="menu-icon">👤</span>
            <span>My Profile</span>
        </a>

        <a href="addresses.php" class="active">
            <span class="menu-icon">📍</span>
            <span>My Addresses</span>
        </a>

        <a href="change-password.php">
            <span class="menu-icon">🔐</span>
            <span>Change Password</span>
        </a>


        <div class="menu-title">MORE</div>

        <a href="../index.php">
            <span class="menu-icon">🏠</span>
            <span>Visit Website</span>
        </a>

        <a href="../logout.php">
            <span class="menu-icon">🚪</span>
            <span>Logout</span>
        </a>

    </nav>

</aside>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


    <!-- TOPBAR -->

    <header class="topbar">

        <div>

            <h1>Add New Address</h1>

            <p>
                Save a delivery address for faster checkout
            </p>

        </div>


        <div class="profile-mini">

            <div class="avatar">
                <?= e(
                    strtoupper(
                        substr(
                            trim($customerName),
                            0,
                            1
                        )
                    )
                ) ?>
            </div>

            <div>

                <strong>
                    <?= e($customerName) ?>
                </strong>

                <span>
                    Customer
                </span>

            </div>

        </div>

    </header>


    <!-- CONTENT -->

    <section class="content">


        <a
            href="addresses.php"
            class="back-link"
        >
            ← Back to My Addresses
        </a>


        <div class="card">


            <div class="card-header">

                <h2>
                    📍 Address Details
                </h2>

                <p>
                    Enter the address where you want your medicines delivered.
                </p>

            </div>


            <div class="form-body">


                <?php if (!empty($errors)): ?>

                    <div class="errors">

                        <?php foreach ($errors as $error): ?>

                            <div>
                                • <?= e($error) ?>
                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>


                <form method="POST">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($csrfToken) ?>"
                    >


                    <div class="form-grid">


                        <!-- ADDRESS TYPE -->

                        <div class="form-group">

                            <label>
                                Address Type
                            </label>

                            <select name="address_type">

                                <option
                                    value="Home"
                                    <?= $addressType === 'Home' ? 'selected' : '' ?>
                                >
                                    🏠 Home
                                </option>

                                <option
                                    value="Work"
                                    <?= $addressType === 'Work' ? 'selected' : '' ?>
                                >
                                    💼 Work
                                </option>

                                <option
                                    value="Other"
                                    <?= $addressType === 'Other' ? 'selected' : '' ?>
                                >
                                    📍 Other
                                </option>

                            </select>

                        </div>


                        <!-- FULL NAME -->

                        <div class="form-group">

                            <label>
                                Full Name *
                            </label>

                            <input
                                type="text"
                                name="full_name"
                                maxlength="100"
                                value="<?= e($fullName) ?>"
                                placeholder="Enter recipient name"
                                required
                            >

                        </div>


                        <!-- MOBILE -->

                        <div class="form-group">

                            <label>
                                Mobile Number *
                            </label>

                            <input
                                type="text"
                                name="mobile"
                                maxlength="15"
                                value="<?= e($mobile) ?>"
                                placeholder="Enter mobile number"
                                inputmode="numeric"
                                required
                            >

                        </div>


                        <!-- PINCODE -->

                        <div class="form-group">

                            <label>
                                Pincode *
                            </label>

                            <input
                                type="text"
                                name="pincode"
                                maxlength="10"
                                value="<?= e($pincode) ?>"
                                placeholder="Enter pincode"
                                inputmode="numeric"
                                required
                            >

                        </div>


                        <!-- ADDRESS -->

                        <div class="form-group full">

                            <label>
                                Complete Address *
                            </label>

                            <textarea
                                name="address"
                                placeholder="House/Flat No., Street, Village/Area, Landmark..."
                                required
                            ><?= e($address) ?></textarea>

                        </div>


                        <!-- CITY -->

                        <div class="form-group">

                            <label>
                                City *
                            </label>

                            <input
                                type="text"
                                name="city"
                                maxlength="100"
                                value="<?= e($city) ?>"
                                placeholder="Enter city"
                                required
                            >

                        </div>


                        <!-- STATE -->

                        <div class="form-group">

                            <label>
                                State *
                            </label>

                            <input
                                type="text"
                                name="state"
                                maxlength="100"
                                value="<?= e($state) ?>"
                                placeholder="Enter state"
                                required
                            >

                        </div>


                        <!-- DEFAULT -->

                        <div class="default-box">

                            <input
                                type="checkbox"
                                id="is_default"
                                name="is_default"
                                value="1"
                                <?= $isDefault ? 'checked' : '' ?>
                            >

                            <label for="is_default">

                                <strong>
                                    Make this my default address
                                </strong>

                                <small>
                                    This address will be selected automatically during checkout.
                                </small>

                            </label>

                        </div>


                    </div>


                    <!-- ACTIONS -->

                    <div class="actions">

                        <a
                            href="addresses.php"
                            class="btn btn-secondary"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            💾 Save Address
                        </button>

                    </div>


                </form>


            </div>

        </div>


    </section>

</main>

</body>
</html>