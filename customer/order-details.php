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
// GET ORDER ID
// =====================================================

$orderId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


if ($orderId <= 0) {
    header("Location: orders.php");
    exit;
}


// =====================================================
// GET ORDER
// IMPORTANT: user_id condition prevents another
// customer from viewing this order.
// =====================================================

$order = null;

$stmt = $conn->prepare("
    SELECT
        id,
        order_number,
        user_id,
        delivery_boy_id,
        prescription_id,

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

        customer_note,
        admin_note,

        created_at,
        updated_at

    FROM orders

    WHERE id = ?
    AND user_id = ?

    LIMIT 1
");


if ($stmt) {

    $stmt->bind_param(
        "ii",
        $orderId,
        $customerId
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $order = $result->fetch_assoc();
    }

    $stmt->close();
}


// =====================================================
// ORDER NOT FOUND
// =====================================================

if (!$order) {
    header("Location: orders.php");
    exit;
}


// =====================================================
// PAGE DATA
// =====================================================

$pageTitle = "Order Details";


$firstLetter = strtoupper(
    substr(
        trim($customerName),
        0,
        1
    )
);

if ($firstLetter === '') {
    $firstLetter = 'C';
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
            return 'Online Payment';

        default:
            return ucfirst((string) $method);
    }
}


function paymentStatusClass($status)
{
    switch (strtolower((string) $status)) {

        case 'paid':
            return 'payment-paid';

        case 'failed':
            return 'payment-failed';

        case 'refunded':
            return 'payment-refunded';

        default:
            return 'payment-pending';
    }
}


// =====================================================
// ORDER STATUS
// =====================================================

$currentStatus = strtolower(
    (string) $order['order_status']
);


// =====================================================
// STATUS STEPS
// =====================================================

$statusSteps = [
    'pending' => [
        'icon'  => '🕐',
        'title' => 'Order Placed',
        'text'  => 'Your order has been placed successfully.'
    ],

    'confirmed' => [
        'icon'  => '✓',
        'title' => 'Order Confirmed',
        'text'  => 'Your order has been confirmed.'
    ],

    'processing' => [
        'icon'  => '⚙️',
        'title' => 'Processing',
        'text'  => 'Your medicines are being prepared.'
    ],

    'ready' => [
        'icon'  => '📦',
        'title' => 'Ready for Delivery',
        'text'  => 'Your order is ready for dispatch.'
    ],

    'delivered' => [
        'icon'  => '✓',
        'title' => 'Delivered',
        'text'  => 'Your order has been delivered.'
    ]
];


// =====================================================
// DETERMINE COMPLETED STATUS STEPS
// =====================================================

$statusOrder = [
    'pending'    => 1,
    'confirmed'  => 2,
    'processing' => 3,
    'ready'      => 4,
    'delivered'  => 5
];


$currentStep = $statusOrder[$currentStatus] ?? 1;


// =====================================================
// ADDRESS
// =====================================================

$addressParts = array_filter([
    $order['delivery_address'] ?? '',
    $order['city'] ?? '',
    $order['state'] ?? '',
    $order['pincode'] ?? ''
]);

$fullAddress = implode(
    ', ',
    $addressParts
);


// =====================================================
// DATE
// =====================================================

$orderDate = '-';

if (!empty($order['created_at'])) {

    $timestamp = strtotime(
        $order['created_at']
    );

    if ($timestamp !== false) {

        $orderDate = date(
            'd M Y, h:i A',
            $timestamp
        );
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
        Order #<?= e($order['order_number']) ?>
        | Medicine Aapki Gaw Mein
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

            border-bottom:
                1px solid rgba(255,255,255,0.15);
        }


        .brand-icon {
            width: 45px;
            height: 45px;

            border-radius: 12px;

            background:
                rgba(255,255,255,0.16);

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
            padding:
                12px 12px 8px;

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

            transition: 0.2s;
        }


        .menu-item:hover {
            background:
                rgba(255,255,255,0.10);
        }


        .menu-item.active {
            background:
                rgba(255,255,255,0.18);

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

            border-bottom:
                1px solid #e8ece9;
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
           BACK BUTTON
        ================================================= */

        .back-row {
            margin-bottom: 18px;
        }


        .back-btn {
            display: inline-flex;

            align-items: center;

            gap: 7px;

            font-size: 12px;

            color: #238b39;

            font-weight: 500;
        }


        .back-btn:hover {
            color: #176b29;
        }


        /* =================================================
           ORDER HEADER
        ================================================= */

        .order-header {
            background: linear-gradient(
                110deg,
                #238b39,
                #51b848
            );

            color: #fff;

            border-radius: 15px;

            padding: 25px 28px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 20px;

            position: relative;

            overflow: hidden;
        }


        .order-header::after {
            content: "📦";

            position: absolute;

            right: 30px;
            top: 50%;

            transform:
                translateY(-50%);

            font-size: 75px;

            opacity: 0.10;
        }


        .order-header-left {
            position: relative;

            z-index: 1;
        }


        .order-header-left span {
            display: block;

            font-size: 11px;

            opacity: 0.82;

            margin-bottom: 5px;
        }


        .order-header-left h2 {
            font-size: 23px;

            font-weight: 500;

            margin-bottom: 7px;
        }


        .order-header-left p {
            font-size: 11px;

            opacity: 0.85;
        }


        .order-header-right {
            position: relative;

            z-index: 2;

            text-align: right;
        }


        .header-amount-label {
            display: block;

            font-size: 10px;

            opacity: 0.75;

            margin-bottom: 3px;
        }


        .header-amount {
            display: block;

            font-size: 24px;

            font-weight: 600;
        }


        .header-status {
            display: inline-flex;

            margin-top: 8px;

            padding: 6px 12px;

            border-radius: 20px;

            background:
                rgba(255,255,255,0.18);

            font-size: 10px;

            font-weight: 500;
        }


        /* =================================================
           GRID
        ================================================= */

        .page-grid {
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
                0 2px 8px
                rgba(30,50,35,0.03);

            overflow: hidden;
        }


        .card + .card {
            margin-top: 20px;
        }


        .card-header {
            padding: 18px 20px;

            border-bottom:
                1px solid #edf0ee;

            display: flex;

            align-items: center;

            justify-content: space-between;
        }


        .card-header h3 {
            font-size: 15px;

            font-weight: 500;

            color: #29362e;
        }


        /* =================================================
           STATUS TIMELINE
        ================================================= */

        .timeline {
            padding: 22px 25px;
        }


        .timeline-item {
            display: flex;

            gap: 15px;

            position: relative;

            padding-bottom: 23px;
        }


        .timeline-item:last-child {
            padding-bottom: 0;
        }


        .timeline-icon-wrapper {
            position: relative;

            width: 34px;

            flex-shrink: 0;
        }


        .timeline-icon {
            width: 34px;
            height: 34px;

            border-radius: 50%;

            display: flex;

            align-items: center;
            justify-content: center;

            background: #edf1ee;

            color: #8b958e;

            font-size: 13px;

            position: relative;

            z-index: 2;
        }


        .timeline-item.completed
        .timeline-icon {
            background: #e5f5e9;

            color: #238b39;
        }


        .timeline-item.current
        .timeline-icon {
            background: #238b39;

            color: #fff;

            box-shadow:
                0 0 0 5px #e4f4e7;
        }


        .timeline-line {
            position: absolute;

            left: 16px;

            top: 34px;

            width: 2px;

            height: calc(100% - 20px);

            background: #e7ece8;
        }


        .timeline-item.completed
        .timeline-line {
            background: #8dcd9a;
        }


        .timeline-content {
            padding-top: 1px;
        }


        .timeline-content h4 {
            font-size: 12px;

            font-weight: 500;

            color: #3a473f;

            margin-bottom: 4px;
        }


        .timeline-item.completed
        .timeline-content h4 {
            color: #238b39;
        }


        .timeline-content p {
            font-size: 10px;

            color: #929b95;

            line-height: 1.5;
        }


        /* =================================================
           CANCELLED
        ================================================= */

        .cancelled-box {
            margin: 20px;

            padding: 16px;

            border-radius: 10px;

            background: #fff0f0;

            border: 1px solid #ffd9d9;
        }


        .cancelled-box strong {
            display: block;

            font-size: 12px;

            color: #b83a3a;

            margin-bottom: 5px;
        }


        .cancelled-box p {
            font-size: 10px;

            color: #8d6a6a;

            line-height: 1.5;
        }


        /* =================================================
           DETAILS
        ================================================= */

        .details-body {
            padding: 20px;
        }


        .detail-grid {
            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 18px;
        }


        .detail-item label {
            display: block;

            font-size: 10px;

            color: #929b95;

            margin-bottom: 5px;
        }


        .detail-item strong {
            display: block;

            font-size: 12px;

            color: #354139;

            font-weight: 400;

            line-height: 1.6;
        }


        .detail-item.full {
            grid-column: 1 / -1;
        }


        /* =================================================
           PRICE BREAKDOWN
        ================================================= */

        .price-body {
            padding: 20px;
        }


        .price-row {
            display: flex;

            justify-content: space-between;

            align-items: center;

            padding: 9px 0;

            font-size: 11px;

            color: #7f8983;
        }


        .price-row strong {
            color: #39453e;

            font-weight: 500;
        }


        .price-row.discount strong {
            color: #238b39;
        }


        .price-total {
            border-top:
                1px solid #e5eae7;

            margin-top: 6px;

            padding-top: 14px;

            display: flex;

            align-items: center;

            justify-content: space-between;
        }


        .price-total span {
            font-size: 13px;

            color: #354139;

            font-weight: 500;
        }


        .price-total strong {
            font-size: 18px;

            color: #238b39;

            font-weight: 600;
        }


        /* =================================================
           PAYMENT
        ================================================= */

        .payment-box {
            padding: 18px 20px;
        }


        .payment-method {
            display: flex;

            align-items: center;

            gap: 12px;

            padding: 12px;

            background: #f8fbf9;

            border-radius: 9px;
        }


        .payment-icon {
            width: 40px;
            height: 40px;

            border-radius: 9px;

            background: #e6f5e9;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 18px;
        }


        .payment-info strong {
            display: block;

            font-size: 12px;

            color: #39453e;

            font-weight: 500;
        }


        .payment-info span {
            display: block;

            font-size: 10px;

            color: #8c9690;

            margin-top: 3px;
        }


        .payment-status {
            display: inline-flex;

            margin-top: 14px;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 500;
        }


        .payment-paid {
            background: #e2f6e7;

            color: #21823a;
        }


        .payment-pending {
            background: #fff3dc;

            color: #a56c00;
        }


        .payment-failed {
            background: #ffe7e7;

            color: #b83a3a;
        }


        .payment-refunded {
            background: #eeeaff;

            color: #6547a7;
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

            padding: 12px;

            border: 1px solid #edf0ee;

            border-radius: 9px;

            margin-bottom: 10px;

            transition: 0.2s;
        }


        .quick-action:last-child {
            margin-bottom: 0;
        }


        .quick-action:hover {
            background: #f8fcf9;

            border-color: #c5e3ca;
        }


        .quick-icon {
            width: 37px;
            height: 37px;

            border-radius: 9px;

            background: #e9f7ec;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 16px;
        }


        .quick-text strong {
            display: block;

            font-size: 11px;

            color: #38443c;

            font-weight: 500;
        }


        .quick-text span {
            display: block;

            font-size: 9px;

            color: #929b95;

            margin-top: 3px;
        }


        /* =================================================
           NOTES
        ================================================= */

        .note-box {
            padding: 18px 20px;
        }


        .note {
            padding: 12px;

            background: #f8fbf9;

            border-radius: 9px;

            font-size: 10px;

            line-height: 1.6;

            color: #707b74;

            margin-bottom: 10px;
        }


        .note:last-child {
            margin-bottom: 0;
        }


        .note strong {
            display: block;

            color: #46534b;

            font-size: 10px;

            margin-bottom: 4px;
        }


        /* =================================================
           BUTTONS
        ================================================= */

        .btn {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 9px 14px;

            border-radius: 8px;

            font-size: 10px;

            font-weight: 500;

            transition: 0.2s;
        }


        .btn-primary {
            background: #238b39;

            color: #fff;
        }


        .btn-primary:hover {
            background: #1c7330;
        }


        .btn-light {
            background: #eef8f0;

            color: #238b39;
        }


        .btn-light:hover {
            background: #238b39;

            color: #fff;
        }


        .button-row {
            display: flex;

            gap: 8px;

            flex-wrap: wrap;

            margin-top: 18px;
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 1100px) {

            .page-grid {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 850px) {

            .sidebar {
                width: 70px;
            }


            .brand {
                justify-content: center;

                padding:
                    18px 10px;
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


            .order-header {
                padding: 20px;

                display: block;
            }


            .order-header-right {
                text-align: left;

                margin-top: 18px;
            }


            .order-header::after {
                font-size: 50px;

                right: 10px;
            }


            .detail-grid {
                grid-template-columns: 1fr;
            }


            .detail-item.full {
                grid-column: auto;
            }


            .timeline {
                padding: 20px;
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
            class="menu-item"
        >
            <span class="menu-icon">🏠</span>
            <span>Dashboard</span>
        </a>


        <a
            href="orders.php"
            class="menu-item active"
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
                Order Details
            </h1>

            <p>
                View your complete order information
            </p>

        </div>


        <div class="profile">

            <div class="profile-avatar">
                <?= e($firstLetter) ?>
            </div>

            <div class="profile-info">

                <strong>
                    <?= e($customerName) ?>
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


        <!-- BACK -->

        <div class="back-row">

            <a
                href="orders.php"
                class="back-btn"
            >
                ← Back to My Orders
            </a>

        </div>


        <!-- =================================================
             ORDER HEADER
        ================================================= -->

        <div class="order-header">

            <div class="order-header-left">

                <span>
                    Order Number
                </span>

                <h2>
                    #<?= e($order['order_number']) ?>
                </h2>

                <p>
                    Placed on <?= e($orderDate) ?>
                </p>

            </div>


            <div class="order-header-right">

                <span class="header-amount-label">
                    Order Total
                </span>

                <span class="header-amount">

                    ₹<?= number_format(
                        (float) $order['total_amount'],
                        2
                    ) ?>

                </span>


                <span class="header-status">

                    <?= e(
                        orderStatusLabel(
                            $order['order_status']
                        )
                    ) ?>

                </span>

            </div>

        </div>


        <!-- =================================================
             PAGE GRID
        ================================================= -->

        <div class="page-grid">


            <!-- =================================================
                 LEFT
            ================================================= -->

            <div>


                <!-- =================================================
                     ORDER TRACKING
                ================================================= -->

                <div class="card">

                    <div class="card-header">

                        <h3>
                            Order Status
                        </h3>

                    </div>


                    <?php if ($currentStatus === 'cancelled'): ?>

                        <div class="cancelled-box">

                            <strong>
                                ❌ Order Cancelled
                            </strong>

                            <p>
                                This order has been cancelled.
                                Please contact us if you need
                                more information.
                            </p>

                        </div>

                    <?php else: ?>

                        <div class="timeline">


                            <?php

                            $stepNumber = 0;

                            foreach ($statusSteps as $statusKey => $step):

                                $stepNumber++;

                                $stepPosition =
                                    $statusOrder[$statusKey];

                                $isCompleted =
                                    $stepPosition < $currentStep;

                                $isCurrent =
                                    $stepPosition === $currentStep;

                                $class = '';

                                if ($isCompleted) {
                                    $class .= ' completed';
                                }

                                if ($isCurrent) {
                                    $class .= ' current';
                                }

                            ?>

                                <div
                                    class="timeline-item<?= e($class) ?>"
                                >


                                    <div
                                        class="timeline-icon-wrapper"
                                    >

                                        <div
                                            class="timeline-icon"
                                        >

                                            <?php if ($isCompleted): ?>

                                                ✓

                                            <?php else: ?>

                                                <?= e(
                                                    $step['icon']
                                                ) ?>

                                            <?php endif; ?>

                                        </div>


                                        <?php if (
                                            $stepNumber <
                                            count($statusSteps)
                                        ): ?>

                                            <div
                                                class="timeline-line"
                                            ></div>

                                        <?php endif; ?>

                                    </div>


                                    <div class="timeline-content">

                                        <h4>
                                            <?= e(
                                                $step['title']
                                            ) ?>
                                        </h4>

                                        <p>
                                            <?= e(
                                                $step['text']
                                            ) ?>
                                        </p>

                                    </div>


                                </div>

                            <?php endforeach; ?>


                        </div>

                    <?php endif; ?>

                </div>


                <!-- =================================================
                     CUSTOMER / DELIVERY DETAILS
                ================================================= -->

                <div class="card">

                    <div class="card-header">

                        <h3>
                            Delivery Details
                        </h3>

                    </div>


                    <div class="details-body">

                        <div class="detail-grid">


                            <div class="detail-item">

                                <label>
                                    Customer Name
                                </label>

                                <strong>
                                    <?= e(
                                        $order['customer_name']
                                    ) ?>
                                </strong>

                            </div>


                            <div class="detail-item">

                                <label>
                                    Mobile Number
                                </label>

                                <strong>
                                    <?= e(
                                        $order['customer_mobile']
                                    ) ?>
                                </strong>

                            </div>


                            <div
                                class="detail-item full"
                            >

                                <label>
                                    Delivery Address
                                </label>

                                <strong>

                                    <?= e(
                                        $fullAddress !== ''
                                            ? $fullAddress
                                            : 'Address not available'
                                    ) ?>

                                </strong>

                            </div>


                            <div class="detail-item">

                                <label>
                                    City
                                </label>

                                <strong>
                                    <?= e(
                                        $order['city'] ?: '-'
                                    ) ?>
                                </strong>

                            </div>


                            <div class="detail-item">

                                <label>
                                    State
                                </label>

                                <strong>
                                    <?= e(
                                        $order['state'] ?: '-'
                                    ) ?>
                                </strong>

                            </div>


                            <div class="detail-item">

                                <label>
                                    Pincode
                                </label>

                                <strong>
                                    <?= e(
                                        $order['pincode'] ?: '-'
                                    ) ?>
                                </strong>

                            </div>


                            <div class="detail-item">

                                <label>
                                    Order Date
                                </label>

                                <strong>
                                    <?= e($orderDate) ?>
                                </strong>

                            </div>


                        </div>


                        <?php if (
                            !empty(
                                trim(
                                    (string)
                                    $order['customer_note']
                                )
                            )
                        ): ?>

                            <div
                                class="note"
                                style="margin-top:18px;"
                            >

                                <strong>
                                    Customer Note
                                </strong>

                                <?= nl2br(
                                    e(
                                        $order['customer_note']
                                    )
                                ) ?>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- =================================================
                     ADMIN NOTE
                ================================================= -->

                <?php if (
                    !empty(
                        trim(
                            (string) $order['admin_note']
                        )
                    )
                ): ?>

                    <div class="card">

                        <div class="card-header">

                            <h3>
                                Order Note
                            </h3>

                        </div>


                        <div class="note-box">

                            <div class="note">

                                <strong>
                                    Message from Admin
                                </strong>

                                <?= nl2br(
                                    e(
                                        $order['admin_note']
                                    )
                                ) ?>

                            </div>

                        </div>

                    </div>

                <?php endif; ?>


            </div>


            <!-- =================================================
                 RIGHT
            ================================================= -->

            <div>


                <!-- =================================================
                     PRICE BREAKDOWN
                ================================================= -->

                <div class="card">

                    <div class="card-header">

                        <h3>
                            Order Summary
                        </h3>

                    </div>


                    <div class="price-body">


                        <div class="price-row">

                            <span>
                                Subtotal
                            </span>

                            <strong>

                                ₹<?= number_format(
                                    (float)
                                    $order['subtotal'],
                                    2
                                ) ?>

                            </strong>

                        </div>


                        <div class="price-row">

                            <span>
                                Delivery Charge
                            </span>

                            <strong>

                                ₹<?= number_format(
                                    (float)
                                    $order['delivery_charge'],
                                    2
                                ) ?>

                            </strong>

                        </div>


                        <div
                            class="price-row discount"
                        >

                            <span>
                                Discount
                            </span>

                            <strong>

                                - ₹<?= number_format(
                                    (float)
                                    $order['discount'],
                                    2
                                ) ?>

                            </strong>

                        </div>


                        <div class="price-total">

                            <span>
                                Total Amount
                            </span>

                            <strong>

                                ₹<?= number_format(
                                    (float)
                                    $order['total_amount'],
                                    2
                                ) ?>

                            </strong>

                        </div>


                    </div>

                </div>


                <!-- =================================================
                     PAYMENT
                ================================================= -->

                <div
                    class="card"
                    style="margin-top:20px;"
                >

                    <div class="card-header">

                        <h3>
                            Payment
                        </h3>

                    </div>


                    <div class="payment-box">


                        <div class="payment-method">

                            <div class="payment-icon">

                                <?php if (
                                    strtolower(
                                        (string)
                                        $order['payment_method']
                                    ) === 'cod'
                                ): ?>

                                    💵

                                <?php else: ?>

                                    💳

                                <?php endif; ?>

                            </div>


                            <div class="payment-info">

                                <strong>

                                    <?= e(
                                        paymentMethodLabel(
                                            $order[
                                                'payment_method'
                                            ]
                                        )
                                    ) ?>

                                </strong>

                                <span>
                                    Payment Method
                                </span>

                            </div>

                        </div>


                        <span
                            class="payment-status <?= e(
                                paymentStatusClass(
                                    $order[
                                        'payment_status'
                                    ]
                                )
                            ) ?>"
                        >

                            Payment:
                            <?= e(
                                ucfirst(
                                    $order[
                                        'payment_status'
                                    ]
                                )
                            ) ?>

                        </span>


                    </div>

                </div>


                <!-- =================================================
                     QUICK ACTIONS
                ================================================= -->

                <div
                    class="card"
                    style="margin-top:20px;"
                >

                    <div class="card-header">

                        <h3>
                            Quick Actions
                        </h3>

                    </div>


                    <div class="quick-actions">


                        <a
                            href="orders.php"
                            class="quick-action"
                        >

                            <div class="quick-icon">
                                📦
                            </div>

                            <div class="quick-text">

                                <strong>
                                    My Orders
                                </strong>

                                <span>
                                    View all your orders
                                </span>

                            </div>

                        </a>


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
                                    Order more medicines
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
                                    Check your cart
                                </span>

                            </div>

                        </a>


                        <a
                            href="../contact.php"
                            class="quick-action"
                        >

                            <div class="quick-icon">
                                💬
                            </div>

                            <div class="quick-text">

                                <strong>
                                    Need Help?
                                </strong>

                                <span>
                                    Contact our support
                                </span>

                            </div>

                        </a>


                    </div>

                </div>


                <!-- =================================================
                     BUTTONS
                ================================================= -->

                <div
                    class="button-row"
                    style="margin-top:20px;"
                >

                    <a
                        href="orders.php"
                        class="btn btn-light"
                    >
                        ← My Orders
                    </a>

                    <a
                        href="../medicines.php"
                        class="btn btn-primary"
                    >
                        💊 Shop Medicines
                    </a>

                </div>


            </div>


        </div>


    </section>

</main>


</body>

</html>