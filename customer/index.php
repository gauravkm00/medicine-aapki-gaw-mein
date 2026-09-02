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


// =====================================================
// CUSTOMER DATA
// =====================================================

$customerId = (int) $_SESSION['user_id'];

$customerName   = $_SESSION['name'] ?? 'Customer';
$customerMobile = $_SESSION['mobile'] ?? '';
$customerEmail  = $_SESSION['email'] ?? '';


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
// PAGE DATA
// =====================================================

$pageTitle = "Customer Dashboard";

$firstLetter = strtoupper(
    substr(trim($customerName), 0, 1)
);

if ($firstLetter === '') {
    $firstLetter = 'C';
}


// =====================================================
// CUSTOMER PROFILE DATA
// =====================================================

$profile = [
    'name'    => $customerName,
    'mobile'  => $customerMobile,
    'email'   => $customerEmail,
    'address' => '',
    'city'    => '',
    'state'   => '',
    'pincode' => ''
];


$stmt = $conn->prepare("
    SELECT
        name,
        mobile,
        email,
        address,
        city,
        state,
        pincode
    FROM users
    WHERE id = ?
    LIMIT 1
");

if ($stmt) {

    $stmt->bind_param("i", $customerId);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $profile = $result->fetch_assoc();
    }

    $stmt->close();
}


// =====================================================
// ORDER STATISTICS
// =====================================================

$totalOrders       = 0;
$pendingOrders     = 0;
$processingOrders  = 0;
$deliveredOrders   = 0;
$cancelledOrders   = 0;
$totalSpent        = 0;


$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_orders,

        COALESCE(
            SUM(
                order_status IN ('pending', 'confirmed')
            ),
            0
        ) AS pending_orders,

        COALESCE(
            SUM(
                order_status IN ('processing', 'ready')
            ),
            0
        ) AS processing_orders,

        COALESCE(
            SUM(
                order_status = 'delivered'
            ),
            0
        ) AS delivered_orders,

        COALESCE(
            SUM(
                order_status = 'cancelled'
            ),
            0
        ) AS cancelled_orders,

        COALESCE(
            SUM(
                CASE
                    WHEN order_status != 'cancelled'
                    THEN total_amount
                    ELSE 0
                END
            ),
            0
        ) AS total_spent

    FROM orders
    WHERE user_id = ?
");


if ($stmt) {

    $stmt->bind_param("i", $customerId);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {

        $stats = $result->fetch_assoc();

        $totalOrders      = (int) ($stats['total_orders'] ?? 0);
        $pendingOrders    = (int) ($stats['pending_orders'] ?? 0);
        $processingOrders = (int) ($stats['processing_orders'] ?? 0);
        $deliveredOrders  = (int) ($stats['delivered_orders'] ?? 0);
        $cancelledOrders  = (int) ($stats['cancelled_orders'] ?? 0);
        $totalSpent       = (float) ($stats['total_spent'] ?? 0);
    }

    $stmt->close();
}


// =====================================================
// RECENT ORDERS
// =====================================================

$recentOrders = [];

$stmt = $conn->prepare("
    SELECT
        id,
        order_number,
        subtotal,
        delivery_charge,
        discount,
        total_amount,
        payment_method,
        payment_status,
        order_status,
        customer_name,
        customer_mobile,
        delivery_address,
        city,
        state,
        pincode,
        created_at
    FROM orders
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 8
");


if ($stmt) {

    $stmt->bind_param("i", $customerId);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result) {

        while ($row = $result->fetch_assoc()) {
            $recentOrders[] = $row;
        }
    }

    $stmt->close();
}


// =====================================================
// STATUS HELPERS
// =====================================================

function orderStatusClass($status)
{
    switch (strtolower((string) $status)) {

        case 'pending':
            return 'status-pending';

        case 'confirmed':
            return 'status-confirmed';

        case 'processing':
            return 'status-processing';

        case 'ready':
            return 'status-ready';

        case 'delivered':
            return 'status-delivered';

        case 'cancelled':
            return 'status-cancelled';

        default:
            return 'status-default';
    }
}


function orderStatusLabel($status)
{
    switch (strtolower((string) $status)) {

        case 'pending':
            return 'Pending';

        case 'confirmed':
            return 'Confirmed';

        case 'processing':
            return 'Processing';

        case 'ready':
            return 'Ready';

        case 'delivered':
            return 'Delivered';

        case 'cancelled':
            return 'Cancelled';

        default:
            return ucfirst((string) $status);
    }
}


function paymentMethodLabel($method)
{
    switch (strtolower((string) $method)) {

        case 'cod':
            return 'Cash on Delivery';

        case 'online':
            return 'Online';

        default:
            return ucfirst((string) $method);
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
        <?= e($pageTitle) ?> | Medicine Aapki Gaw Mein
    </title>

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
            color: #26332a;
        }


        a {
            text-decoration: none;
            color: inherit;
        }


        /* =================================================
           SIDEBAR
        ================================================= */

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;

            background: linear-gradient(
                180deg,
                #1f8b38 0%,
                #166b2d 100%
            );

            color: #fff;
            z-index: 1000;

            overflow-y: auto;
        }


        .brand {
            padding: 25px 20px;
            display: flex;
            align-items: center;
            gap: 13px;
            border-bottom: 1px solid rgba(255,255,255,0.15);
        }


        .brand-icon {
            width: 45px;
            height: 45px;

            border-radius: 12px;

            background: rgba(255,255,255,0.16);

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 24px;
        }


        .brand-text h2 {
            font-size: 16px;
            font-weight: 600;
            line-height: 1.3;
        }


        .brand-text span {
            display: block;
            margin-top: 3px;

            font-size: 11px;
            opacity: 0.75;
        }


        .menu {
            padding: 20px 12px;
        }


        .menu-title {
            padding: 12px 12px 8px;

            font-size: 10px;
            font-weight: 600;

            letter-spacing: 1px;
            text-transform: uppercase;

            opacity: 0.55;
        }


        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;

            padding: 12px 13px;
            margin-bottom: 5px;

            border-radius: 9px;

            font-size: 13px;
            font-weight: 400;

            transition: 0.2s;
        }


        .menu-item:hover {
            background: rgba(255,255,255,0.10);
        }


        .menu-item.active {
            background: rgba(255,255,255,0.18);
            font-weight: 500;
        }


        .menu-icon {
            width: 22px;
            text-align: center;
            font-size: 16px;
        }


        /* =================================================
           MAIN
        ================================================= */

        .main {
            margin-left: 250px;
            min-height: 100vh;
        }


        /* =================================================
           TOPBAR
        ================================================= */

        .topbar {
            height: 75px;

            background: #fff;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 30px;

            border-bottom: 1px solid #e8ece9;
        }


        .topbar-title h1 {
            font-size: 21px;
            font-weight: 600;
            color: #233129;
        }


        .topbar-title p {
            margin-top: 3px;

            font-size: 12px;
            color: #89938c;
        }


        .profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }


        .profile-avatar {
            width: 39px;
            height: 39px;

            border-radius: 50%;

            background: #e4f4e7;
            color: #238b39;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 15px;
            font-weight: 600;
        }


        .profile-info strong {
            display: block;

            font-size: 13px;
            font-weight: 500;

            color: #29352e;
        }


        .profile-info span {
            display: block;

            font-size: 11px;
            color: #909a94;

            margin-top: 2px;
        }


        /* =================================================
           CONTENT
        ================================================= */

        .content {
            padding: 30px;
        }


        /* =================================================
           WELCOME CARD
        ================================================= */

        .welcome-card {
            position: relative;

            background: linear-gradient(
                110deg,
                #238b39,
                #51b848
            );

            border-radius: 15px;

            padding: 27px 30px;

            color: #fff;

            overflow: hidden;

            margin-bottom: 25px;
        }


        .welcome-card::after {
            content: "💊";

            position: absolute;

            right: 35px;
            top: 50%;

            transform: translateY(-50%);

            font-size: 70px;

            opacity: 0.12;
        }


        .welcome-card h2 {
            font-size: 23px;
            font-weight: 500;

            margin-bottom: 7px;
        }


        .welcome-card p {
            font-size: 13px;
            opacity: 0.9;

            max-width: 600px;
            line-height: 1.6;
        }


        /* =================================================
           STATS
        ================================================= */

        .stats-grid {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

            margin-bottom: 25px;
        }


        .stat-card {
            background: #fff;

            border: 1px solid #e7ece8;

            border-radius: 13px;

            padding: 20px;

            display: flex;
            align-items: center;

            gap: 15px;

            box-shadow:
                0 2px 8px rgba(30,50,35,0.03);
        }


        .stat-icon {
            width: 48px;
            height: 48px;

            border-radius: 11px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 22px;

            flex-shrink: 0;
        }


        .stat-icon.green {
            background: #e7f6ea;
        }


        .stat-icon.orange {
            background: #fff3df;
        }


        .stat-icon.blue {
            background: #e8f1ff;
        }


        .stat-icon.purple {
            background: #f1eaff;
        }


        .stat-info span {
            display: block;

            font-size: 11px;
            color: #8c9790;

            margin-bottom: 5px;
        }


        .stat-info strong {
            display: block;

            font-size: 23px;
            font-weight: 600;

            color: #28342d;
        }


        /* =================================================
           MAIN GRID
        ================================================= */

        .dashboard-grid {
            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                300px;

            gap: 20px;
        }


        .card {
            background: #fff;

            border: 1px solid #e7ece8;

            border-radius: 13px;

            box-shadow:
                0 2px 8px rgba(30,50,35,0.03);

            overflow: hidden;
        }


        .card-header {
            padding: 19px 20px;

            border-bottom: 1px solid #edf0ee;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }


        .card-header h3 {
            font-size: 15px;
            font-weight: 500;
            color: #29362e;
        }


        .card-header a {
            font-size: 11px;
            color: #238b39;
            font-weight: 500;
        }


        /* =================================================
           ORDERS TABLE
        ================================================= */

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }


        table {
            width: 100%;
            border-collapse: collapse;
        }


        th {
            background: #fafcfb;

            padding: 12px 18px;

            text-align: left;

            font-size: 10px;
            font-weight: 600;

            color: #87928b;

            text-transform: uppercase;

            letter-spacing: 0.4px;

            white-space: nowrap;
        }


        td {
            padding: 14px 18px;

            border-top: 1px solid #f0f2f1;

            font-size: 12px;

            color: #4d5952;

            white-space: nowrap;
        }


        .order-number {
            color: #238b39;
            font-weight: 500;
        }


        .amount {
            font-weight: 600;
            color: #28342d;
        }


        .date {
            color: #8b958f;
            font-size: 11px;
        }


        /* =================================================
           STATUS
        ================================================= */

        .status {
            display: inline-flex;
            align-items: center;

            padding: 5px 9px;

            border-radius: 20px;

            font-size: 10px;
            font-weight: 500;
        }


        .status-pending {
            background: #fff4dc;
            color: #a86d00;
        }


        .status-confirmed {
            background: #e7f1ff;
            color: #2868ad;
        }


        .status-processing {
            background: #eeeaff;
            color: #6547a7;
        }


        .status-ready {
            background: #e8f7ec;
            color: #238b39;
        }


        .status-delivered {
            background: #dff6e5;
            color: #198038;
        }


        .status-cancelled {
            background: #ffe7e7;
            color: #b83a3a;
        }


        .status-default {
            background: #f0f2f1;
            color: #68726c;
        }


        .payment {
            font-size: 10px;
            color: #7e8982;
        }


        .view-btn {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            padding: 7px 11px;

            border-radius: 7px;

            background: #eef8f0;
            color: #238b39;

            font-size: 10px;
            font-weight: 500;

            transition: 0.2s;
        }


        .view-btn:hover {
            background: #238b39;
            color: #fff;
        }


        /* =================================================
           QUICK ACTIONS
        ================================================= */

        .quick-actions {
            padding: 18px;
        }


        .quick-action {
            display: flex;
            align-items: center;

            gap: 12px;

            padding: 13px;

            border: 1px solid #edf0ee;

            border-radius: 10px;

            margin-bottom: 10px;

            transition: 0.2s;
        }


        .quick-action:last-child {
            margin-bottom: 0;
        }


        .quick-action:hover {
            border-color: #bde0c4;
            background: #f8fcf9;
        }


        .quick-icon {
            width: 38px;
            height: 38px;

            border-radius: 9px;

            background: #e9f7ec;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 17px;

            flex-shrink: 0;
        }


        .quick-text strong {
            display: block;

            font-size: 12px;
            font-weight: 500;

            color: #344139;
        }


        .quick-text span {
            display: block;

            font-size: 10px;

            color: #929b95;

            margin-top: 3px;
        }


        /* =================================================
           ACCOUNT CARD
        ================================================= */

        .account-info {
            padding: 18px;
        }


        .account-row {
            padding: 10px 0;

            border-bottom: 1px solid #edf0ee;
        }


        .account-row:last-child {
            border-bottom: none;
        }


        .account-row label {
            display: block;

            font-size: 10px;

            color: #929b95;

            margin-bottom: 4px;
        }


        .account-row strong {
            display: block;

            font-size: 12px;

            color: #37433b;

            font-weight: 400;

            line-height: 1.5;
        }


        /* =================================================
           ORDER SUMMARY
        ================================================= */

        .summary-box {
            margin-top: 20px;

            padding: 18px;

            background: #f8fbf9;

            border-radius: 10px;
        }


        .summary-box h4 {
            font-size: 12px;

            font-weight: 500;

            color: #3b4740;

            margin-bottom: 12px;
        }


        .summary-row {
            display: flex;

            justify-content: space-between;

            padding: 7px 0;

            font-size: 11px;

            color: #7d8781;
        }


        .summary-row strong {
            color: #344038;
        }


        /* =================================================
           EMPTY STATE
        ================================================= */

        .empty-state {
            padding: 45px 20px;

            text-align: center;
        }


        .empty-icon {
            font-size: 45px;

            margin-bottom: 12px;
        }


        .empty-state h3 {
            font-size: 15px;

            font-weight: 500;

            color: #38443c;

            margin-bottom: 6px;
        }


        .empty-state p {
            font-size: 11px;

            color: #8c9690;

            margin-bottom: 17px;
        }


        .primary-btn {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            padding: 10px 17px;

            border-radius: 8px;

            background: #238b39;

            color: #fff;

            font-size: 11px;

            font-weight: 500;
        }


        .primary-btn:hover {
            background: #1d7631;
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 1100px) {

            .stats-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }


            .dashboard-grid {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 850px) {

            .sidebar {
                width: 70px;
            }


            .brand {
                justify-content: center;
                padding: 18px 10px;
            }


            .brand-text,
            .menu-title,
            .menu-item span:not(.menu-icon) {
                display: none;
            }


            .menu-item {
                justify-content: center;
                padding: 13px 8px;
            }


            .menu-icon {
                font-size: 18px;
            }


            .main {
                margin-left: 70px;
            }


            .content {
                padding: 20px;
            }


            .topbar {
                padding: 0 20px;
            }

        }


        @media (max-width: 600px) {

            .stats-grid {
                grid-template-columns: 1fr;
            }


            .content {
                padding: 15px;
            }


            .topbar {
                height: 68px;
                padding: 0 15px;
            }


            .topbar-title h1 {
                font-size: 17px;
            }


            .topbar-title p {
                display: none;
            }


            .profile-info {
                display: none;
            }


            .welcome-card {
                padding: 22px;
            }


            .welcome-card h2 {
                font-size: 19px;
            }


            .welcome-card::after {
                right: 15px;
                font-size: 50px;
            }


            .stat-card {
                padding: 17px;
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

            <h2>
                Medicine Aapki Gaw Mein
            </h2>

            <span>
                Customer Panel
            </span>

        </div>

    </div>


    <nav class="menu">

        <div class="menu-title">
            Main Menu
        </div>


        <a
            href="index.php"
            class="menu-item active"
        >
            <span class="menu-icon">🏠</span>
            <span>Dashboard</span>
        </a>


        <a
            href="orders.php"
            class="menu-item"
        >
            <span class="menu-icon">📦</span>
            <span>My Orders</span>
        </a>


        <a
            href="../medicines.php"
            class="menu-item"
        >
            <span class="menu-icon">💊</span>
            <span>Browse Medicines</span>
        </a>


        <a
            href="../cart.php"
            class="menu-item"
        >
            <span class="menu-icon">🛒</span>
            <span>My Cart</span>
        </a>


        <div class="menu-title">
            Prescription
        </div>


        <a
            href="prescriptions.php"
            class="menu-item"
        >
            <span class="menu-icon">📋</span>
            <span>My Prescriptions</span>
        </a>


        <a
            href="upload-prescription.php"
            class="menu-item"
        >
            <span class="menu-icon">📤</span>
            <span>Upload Prescription</span>
        </a>


        <div class="menu-title">
            Account
        </div>


        <a
            href="profile.php"
            class="menu-item"
        >
            <span class="menu-icon">👤</span>
            <span>My Profile</span>
        </a>


        <a
            href="addresses.php"
            class="menu-item"
        >
            <span class="menu-icon">📍</span>
            <span>My Addresses</span>
        </a>


        <a
            href="change-password.php"
            class="menu-item"
        >
            <span class="menu-icon">🔐</span>
            <span>Change Password</span>
        </a>


        <div class="menu-title">
            More
        </div>


        <a
            href="../index.php"
            class="menu-item"
        >
            <span class="menu-icon">🌐</span>
            <span>View Website</span>
        </a>


        <a
            href="../logout.php"
            class="menu-item"
        >
            <span class="menu-icon">🚪</span>
            <span>Logout</span>
        </a>

    </nav>

</aside>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


    <!-- =================================================
         TOPBAR
    ================================================= -->

    <header class="topbar">

        <div class="topbar-title">

            <h1>
                Customer Dashboard
            </h1>

            <p>
                Manage your medicines, orders and account
            </p>

        </div>


        <div class="profile">

            <div class="profile-avatar">
                <?= e($firstLetter) ?>
            </div>

            <div class="profile-info">

                <strong>
                    <?= e($profile['name'] ?? $customerName) ?>
                </strong>

                <span>
                    Customer
                </span>

            </div>

        </div>

    </header>


    <!-- =================================================
         CONTENT
    ================================================= -->

    <section class="content">


        <!-- =================================================
             WELCOME
        ================================================= -->

        <div class="welcome-card">

            <h2>
                Welcome back, <?= e($profile['name'] ?? $customerName) ?>! 👋
            </h2>

            <p>
                Order your medicines easily and track all your
                orders from one place. We are here to help you
                get your medicines delivered to your doorstep.
            </p>

        </div>


        <!-- =================================================
             STATS
        ================================================= -->

        <div class="stats-grid">


            <!-- TOTAL ORDERS -->

            <div class="stat-card">

                <div class="stat-icon green">
                    📦
                </div>

                <div class="stat-info">

                    <span>
                        Total Orders
                    </span>

                    <strong>
                        <?= number_format($totalOrders) ?>
                    </strong>

                </div>

            </div>


            <!-- PENDING -->

            <div class="stat-card">

                <div class="stat-icon orange">
                    ⏳
                </div>

                <div class="stat-info">

                    <span>
                        Pending Orders
                    </span>

                    <strong>
                        <?= number_format($pendingOrders) ?>
                    </strong>

                </div>

            </div>


            <!-- PROCESSING -->

            <div class="stat-card">

                <div class="stat-icon blue">
                    🚚
                </div>

                <div class="stat-info">

                    <span>
                        Processing
                    </span>

                    <strong>
                        <?= number_format($processingOrders) ?>
                    </strong>

                </div>

            </div>


            <!-- DELIVERED -->

            <div class="stat-card">

                <div class="stat-icon purple">
                    ✅
                </div>

                <div class="stat-info">

                    <span>
                        Delivered
                    </span>

                    <strong>
                        <?= number_format($deliveredOrders) ?>
                    </strong>

                </div>

            </div>

        </div>


        <!-- =================================================
             MAIN DASHBOARD GRID
        ================================================= -->

        <div class="dashboard-grid">


            <!-- =================================================
                 LEFT SIDE
            ================================================= -->

            <div>


                <!-- RECENT ORDERS -->

                <div class="card">

                    <div class="card-header">

                        <h3>
                            Recent Orders
                        </h3>

                        <a href="orders.php">
                            View All Orders →
                        </a>

                    </div>


                    <?php if (!empty($recentOrders)): ?>

                        <div class="table-wrapper">

                            <table>

                                <thead>

                                    <tr>

                                        <th>
                                            Order
                                        </th>

                                        <th>
                                            Date
                                        </th>

                                        <th>
                                            Amount
                                        </th>

                                        <th>
                                            Payment
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                        <th>
                                            Action
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php foreach ($recentOrders as $order): ?>

                                        <tr>


                                            <!-- ORDER -->

                                            <td>

                                                <span class="order-number">

                                                    #<?= e(
                                                        $order['order_number']
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- DATE -->

                                            <td>

                                                <span class="date">

                                                    <?= e(
                                                        date(
                                                            'd M Y',
                                                            strtotime(
                                                                $order['created_at']
                                                            )
                                                        )
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- AMOUNT -->

                                            <td>

                                                <span class="amount">

                                                    ₹<?= number_format(
                                                        (float) $order['total_amount'],
                                                        2
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- PAYMENT -->

                                            <td>

                                                <span class="payment">

                                                    <?= e(
                                                        paymentMethodLabel(
                                                            $order['payment_method']
                                                        )
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- STATUS -->

                                            <td>

                                                <span class="status <?= e(
                                                    orderStatusClass(
                                                        $order['order_status']
                                                    )
                                                ) ?>">

                                                    <?= e(
                                                        orderStatusLabel(
                                                            $order['order_status']
                                                        )
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- ACTION -->

                                            <td>

                                                <a
                                                    href="order-details.php?id=<?= (int) $order['id'] ?>"
                                                    class="view-btn"
                                                >
                                                    View
                                                </a>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php else: ?>


                        <!-- EMPTY -->

                        <div class="empty-state">

                            <div class="empty-icon">
                                📦
                            </div>

                            <h3>
                                No Orders Yet
                            </h3>

                            <p>
                                You haven't placed any medicine order yet.
                            </p>

                            <a
                                href="../medicines.php"
                                class="primary-btn"
                            >
                                Browse Medicines
                            </a>

                        </div>

                    <?php endif; ?>

                </div>


                <!-- =================================================
                     ORDER SUMMARY
                ================================================= -->

                <div class="card" style="margin-top:20px;">

                    <div class="card-header">

                        <h3>
                            Order Summary
                        </h3>

                    </div>


                    <div class="summary-box" style="margin:18px;">

                        <div class="summary-row">

                            <span>
                                Total Orders
                            </span>

                            <strong>
                                <?= number_format($totalOrders) ?>
                            </strong>

                        </div>


                        <div class="summary-row">

                            <span>
                                Delivered Orders
                            </span>

                            <strong>
                                <?= number_format($deliveredOrders) ?>
                            </strong>

                        </div>


                        <div class="summary-row">

                            <span>
                                Processing Orders
                            </span>

                            <strong>
                                <?= number_format($processingOrders) ?>
                            </strong>

                        </div>


                        <div class="summary-row">

                            <span>
                                Cancelled Orders
                            </span>

                            <strong>
                                <?= number_format($cancelledOrders) ?>
                            </strong>

                        </div>


                        <div
                            class="summary-row"
                            style="
                                border-top:1px solid #e4eae5;
                                margin-top:5px;
                                padding-top:12px;
                            "
                        >

                            <span>
                                Total Spent
                            </span>

                            <strong style="color:#238b39;">

                                ₹<?= number_format(
                                    $totalSpent,
                                    2
                                ) ?>

                            </strong>

                        </div>

                    </div>

                </div>


            </div>


            <!-- =================================================
                 RIGHT SIDE
            ================================================= -->

            <div>


                <!-- QUICK ACTIONS -->

                <div class="card">

                    <div class="card-header">

                        <h3>
                            Quick Actions
                        </h3>

                    </div>


                    <div class="quick-actions">


                        <a
                            href="../medicines.php"
                            class="quick-action"
                        >

                            <div class="quick-icon">
                                💊
                            </div>

                            <div class="quick-text">

                                <strong>
                                    Browse Medicines
                                </strong>

                                <span>
                                    Find and order medicines
                                </span>

                            </div>

                        </a>


                        <a
                            href="../cart.php"
                            class="quick-action"
                        >

                            <div class="quick-icon">
                                🛒
                            </div>

                            <div class="quick-text">

                                <strong>
                                    My Cart
                                </strong>

                                <span>
                                    Review your cart
                                </span>

                            </div>

                        </a>


                        <a
                            href="upload-prescription.php"
                            class="quick-action"
                        >

                            <div class="quick-icon">
                                📤
                            </div>

                            <div class="quick-text">

                                <strong>
                                    Upload Prescription
                                </strong>

                                <span>
                                    Upload your prescription
                                </span>

                            </div>

                        </a>


                        <a
                            href="prescriptions.php"
                            class="quick-action"
                        >

                            <div class="quick-icon">
                                📋
                            </div>

                            <div class="quick-text">

                                <strong>
                                    My Prescriptions
                                </strong>

                                <span>
                                    View your prescriptions
                                </span>

                            </div>

                        </a>


                        <a
                            href="profile.php"
                            class="quick-action"
                        >

                            <div class="quick-icon">
                                👤
                            </div>

                            <div class="quick-text">

                                <strong>
                                    My Profile
                                </strong>

                                <span>
                                    Manage your profile
                                </span>

                            </div>

                        </a>


                    </div>

                </div>


                <!-- ACCOUNT INFORMATION -->

                <div
                    class="card"
                    style="margin-top:20px;"
                >

                    <div class="card-header">

                        <h3>
                            Account Information
                        </h3>

                    </div>


                    <div class="account-info">


                        <div class="account-row">

                            <label>
                                Name
                            </label>

                            <strong>
                                <?= e(
                                    $profile['name'] ?? '-'
                                ) ?>
                            </strong>

                        </div>


                        <div class="account-row">

                            <label>
                                Mobile
                            </label>

                            <strong>
                                <?= e(
                                    $profile['mobile'] ?? '-'
                                ) ?>
                            </strong>

                        </div>


                        <div class="account-row">

                            <label>
                                Email
                            </label>

                            <strong>
                                <?= e(
                                    $profile['email'] ?? '-'
                                ) ?>
                            </strong>

                        </div>


                        <div class="account-row">

                            <label>
                                Address
                            </label>

                            <strong>

                                <?php

                                $fullAddress = trim(
                                    implode(
                                        ', ',
                                        array_filter([
                                            $profile['address'] ?? '',
                                            $profile['city'] ?? '',
                                            $profile['state'] ?? '',
                                            $profile['pincode'] ?? ''
                                        ])
                                    )
                                );

                                echo e(
                                    $fullAddress !== ''
                                        ? $fullAddress
                                        : 'Address not added'
                                );

                                ?>

                            </strong>

                        </div>


                        <a
                            href="profile.php"
                            class="primary-btn"
                            style="
                                width:100%;
                                margin-top:10px;
                            "
                        >
                            Manage Profile
                        </a>

                    </div>

                </div>


                <!-- HELP -->

                <div
                    class="card"
                    style="margin-top:20px;"
                >

                    <div class="card-header">

                        <h3>
                            Need Help?
                        </h3>

                    </div>


                    <div
                        style="
                            padding:20px;
                            text-align:center;
                        "
                    >

                        <div
                            style="
                                font-size:32px;
                                margin-bottom:8px;
                            "
                        >
                            💚
                        </div>


                        <p
                            style="
                                font-size:11px;
                                color:#87928b;
                                line-height:1.6;
                                margin-bottom:14px;
                            "
                        >
                            Need help with your order or
                            medicine? Contact our support team.
                        </p>


                        <a
                            href="../contact.php"
                            class="primary-btn"
                        >
                            Contact Us
                        </a>

                    </div>

                </div>


            </div>


        </div>


    </section>

</main>


</body>

</html>