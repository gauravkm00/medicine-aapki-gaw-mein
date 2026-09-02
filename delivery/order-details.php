<?php

session_start();

require_once "../config/database.php";


// =====================================================
// DELIVERY AUTHENTICATION
// =====================================================

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    strtolower((string)$_SESSION['role']) !== 'delivery'
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

$deliveryBoyName =
    $_SESSION['name'] ?? 'Delivery Boy';


// =====================================================
// DELIVERY ID / ORDER ID
// =====================================================

$deliveryId =
    isset($_GET['delivery_id'])
        ? (int)$_GET['delivery_id']
        : 0;

$orderId =
    isset($_GET['id'])
        ? (int)$_GET['id']
        : 0;


// =====================================================
// VALID REQUEST
// =====================================================

if (
    $deliveryId <= 0 &&
    $orderId <= 0
) {
    header("Location: orders.php");
    exit;
}


// =====================================================
// STATUS MESSAGE
// =====================================================

$statusMessage =
    $_SESSION['delivery_success'] ?? '';

$statusError =
    $_SESSION['delivery_error'] ?? '';

unset($_SESSION['delivery_success']);
unset($_SESSION['delivery_error']);


// =====================================================
// FETCH DELIVERY + ORDER
// =====================================================

$delivery = null;

$deliveryStmt = null;

$deliverySql = "
    SELECT

        /* DELIVERY */

        d.id AS delivery_id,
        d.order_id,
        d.delivery_person_id,
        d.status AS delivery_status,

        d.assigned_at,
        d.picked_up_at,
        d.out_for_delivery_at,
        d.delivered_at,

        d.delivery_otp,
        d.delivery_note,
        d.failure_reason,

        d.created_at AS delivery_created_at,
        d.updated_at AS delivery_updated_at,


        /* ORDER */

        o.id AS order_id,
        o.order_number,

        o.customer_name,
        o.customer_mobile,

        o.delivery_address,
        o.city,
        o.state,
        o.pincode,

        o.total_amount,

        o.payment_method,
        o.payment_status,

        o.order_status,

        o.admin_note,

        o.created_at AS order_created_at,
        o.updated_at AS order_updated_at


    FROM deliveries d


    INNER JOIN orders o
        ON o.id = d.order_id


    WHERE d.delivery_person_id = ?
";


// =====================================================
// FIND BY DELIVERY ID
// =====================================================

if ($deliveryId > 0) {

    $deliverySql .= "
        AND d.id = ?

        LIMIT 1
    ";

    $deliveryStmt =
        mysqli_prepare(
            $conn,
            $deliverySql
        );


    if ($deliveryStmt) {

        mysqli_stmt_bind_param(
            $deliveryStmt,
            "ii",
            $deliveryBoyId,
            $deliveryId
        );

    }

}


// =====================================================
// FIND BY ORDER ID
// =====================================================

else {

    $deliverySql .= "
        AND d.order_id = ?

        ORDER BY d.id DESC

        LIMIT 1
    ";

    $deliveryStmt =
        mysqli_prepare(
            $conn,
            $deliverySql
        );


    if ($deliveryStmt) {

        mysqli_stmt_bind_param(
            $deliveryStmt,
            "ii",
            $deliveryBoyId,
            $orderId
        );

    }

}


// =====================================================
// EXECUTE DELIVERY QUERY
// =====================================================

if ($deliveryStmt) {

    if (
        mysqli_stmt_execute(
            $deliveryStmt
        )
    ) {

        $deliveryResult =
            mysqli_stmt_get_result(
                $deliveryStmt
            );


        if ($deliveryResult) {

            $delivery =
                mysqli_fetch_assoc(
                    $deliveryResult
                );

        }

    }


    mysqli_stmt_close(
        $deliveryStmt
    );

}


// =====================================================
// DELIVERY NOT FOUND
// =====================================================

if (!$delivery) {

    http_response_code(404);

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
            Delivery Not Found | Medicine Aapki Gaw Mein
        </title>


        <style>

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }


            body {

                font-family:
                    Arial,
                    sans-serif;

                background: #f5f7fb;

                min-height: 100vh;

                display: flex;

                align-items: center;

                justify-content: center;

                padding: 20px;

            }


            .error-card {

                background: #fff;

                max-width: 500px;

                width: 100%;

                padding: 45px 30px;

                border-radius: 16px;

                text-align: center;

                border:
                    1px solid #edf0f4;

            }


            .error-icon {

                font-size: 55px;

                margin-bottom: 18px;

            }


            .error-card h2 {

                color: #333;

                margin-bottom: 10px;

            }


            .error-card p {

                color: #888;

                font-size: 14px;

                line-height: 1.6;

                margin-bottom: 25px;

            }


            .back-btn {

                display: inline-block;

                background: #238b39;

                color: #fff;

                text-decoration: none;

                padding: 12px 20px;

                border-radius: 8px;

                font-size: 13px;

                font-weight: 600;

            }

        </style>

    </head>


    <body>

        <div class="error-card">

            <div class="error-icon">
                📦
            </div>


            <h2>
                Delivery Not Found
            </h2>


            <p>

                The requested delivery does not exist
                or it is not assigned to you.

            </p>


            <a
                href="orders.php"
                class="back-btn"
            >

                ← Back to Orders

            </a>

        </div>

    </body>

    </html>

    <?php

    exit;
}


// =====================================================
// ACTUAL IDS
// =====================================================

$deliveryId =
    (int)$delivery['delivery_id'];

$orderId =
    (int)$delivery['order_id'];


// =====================================================
// CURRENT DELIVERY STATUS
// =====================================================

$currentDeliveryStatus =
    strtolower(
        trim(
            $delivery['delivery_status']
            ?? 'pending'
        )
    );


// =====================================================
// STATUS LABEL
// =====================================================

$statusLabel =
    ucwords(
        str_replace(
            '_',
            ' ',
            $currentDeliveryStatus
        )
    );


// =====================================================
// STATUS CLASS
// =====================================================

$statusClass =
    'status-' .
    str_replace(
        ' ',
        '_',
        $currentDeliveryStatus
    );


// =====================================================
// ORDER STATUS
// =====================================================

$orderStatus =
    strtolower(
        trim(
            $delivery['order_status']
            ?? 'pending'
        )
    );


$orderStatusClass =
    'status-' .
    str_replace(
        ' ',
        '_',
        $orderStatus
    );


// =====================================================
// PAYMENT STATUS
// =====================================================

$paymentStatus =
    strtolower(
        trim(
            $delivery['payment_status']
            ?? 'pending'
        )
    );


$paymentClass =
    'payment-' .
    $paymentStatus;


// =====================================================
// PAYMENT METHOD
// =====================================================

$paymentMethod =
    strtoupper(
        $delivery['payment_method']
        ?? 'COD'
    );


// =====================================================
// TOTAL AMOUNT
// =====================================================

$totalAmount =
    (float)(
        $delivery['total_amount']
        ?? 0
    );


// =====================================================
// ORDER ITEMS
// =====================================================

$orderItems = [];

$itemsSql = "
    SELECT

        id,
        order_id,
        medicine_id,
        medicine_name,
        quantity,
        unit_price,
        total_price,
        created_at

    FROM order_items

    WHERE order_id = ?

    ORDER BY id ASC
";


$itemsStmt =
    mysqli_prepare(
        $conn,
        $itemsSql
    );


if ($itemsStmt) {

    mysqli_stmt_bind_param(
        $itemsStmt,
        "i",
        $orderId
    );


    mysqli_stmt_execute(
        $itemsStmt
    );


    $itemsResult =
        mysqli_stmt_get_result(
            $itemsStmt
        );


    if ($itemsResult) {

        while (
            $item =
            mysqli_fetch_assoc(
                $itemsResult
            )
        ) {

            $orderItems[] =
                $item;

        }

    }


    mysqli_stmt_close(
        $itemsStmt
    );

}


// =====================================================
// TOTAL ITEMS
// =====================================================

$totalItems = 0;


foreach (
    $orderItems
    as $item
) {

    $totalItems +=
        (int)(
            $item['quantity']
            ?? 0
        );

}


// =====================================================
// FIRST LETTER
// =====================================================

$firstLetter =
    strtoupper(
        substr(
            trim($deliveryBoyName),
            0,
            1
        )
    );


if ($firstLetter === '') {
    $firstLetter = 'D';
}


// =====================================================
// DATE HELPER
// =====================================================

function formatDateTime($value)
{
    if (
        empty($value)
    ) {
        return '';
    }


    $timestamp =
        strtotime(
            $value
        );


    if (!$timestamp) {
        return '';
    }


    return date(
        'd M Y, h:i A',
        $timestamp
    );
}


// =====================================================
// PAGE TITLE
// =====================================================

$pageTitle =
    "Order Details";

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

    |

    <?= e(
        $delivery['order_number']
        ?? $orderId
    ) ?>

    |

    Medicine Aapki Gaw Mein

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
   BACK BUTTON
===================================================== */

.back-row {

    margin-bottom: 18px;

}


.back-btn {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    color: #238b39;

    font-size: 12px;

    font-weight: 600;

}


.back-btn:hover {

    color: #166b2d;

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

    background: #eaf8ed;

    color: #197333;

    border:
        1px solid #cdebd3;

}


.alert-error {

    background: #fff0f1;

    color: #a51d2d;

    border:
        1px solid #f2ced2;

}


/* =====================================================
   ORDER HEADER
===================================================== */

.order-header {

    background: #fff;

    border:
        1px solid #edf0f4;

    border-radius: 14px;

    padding: 22px;

    margin-bottom: 20px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;

}


.order-header-left h2 {

    font-size: 20px;

    color: #222;

}


.order-header-left p {

    color: #999;

    font-size: 11px;

    margin-top: 6px;

}


.order-header-right {

    text-align: right;

}


.status {

    display: inline-block;

    padding: 7px 12px;

    border-radius: 20px;

    font-size: 9px;

    font-weight: 600;

    text-transform: capitalize;

    white-space: nowrap;

}


/* =====================================================
   STATUS COLORS
===================================================== */

.status-pending {

    background: #fff7df;

    color: #a87900;

}


.status-assigned {

    background: #fff7df;

    color: #a87900;

}


.status-picked_up {

    background: #eef4ff;

    color: #315ea8;

}


.status-out_for_delivery {

    background: #eaf8ed;

    color: #238b39;

}


.status-delivered {

    background: #e6f7eb;

    color: #197333;

}


.status-failed {

    background: #fff0f1;

    color: #b42318;

}


.status-cancelled {

    background: #fff0f1;

    color: #a51d2d;

}


/* Order status */

.status-confirmed {

    background: #edf5ff;

    color: #2766a3;

}


.status-processing {

    background: #f0efff;

    color: #5a4eb0;

}


.status-ready {

    background: #fff1e7;

    color: #a65a18;

}


.order-date {

    color: #999;

    font-size: 10px;

    margin-top: 7px;

}


/* =====================================================
   GRID
===================================================== */

.details-grid {

    display: grid;

    grid-template-columns:
        minmax(0, 1.5fr)
        minmax(280px, .8fr);

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

    padding: 17px 20px;

    border-bottom:
        1px solid #edf0f4;

    display: flex;

    align-items: center;

    justify-content: space-between;

}


.card-header h3 {

    font-size: 14px;

    color: #222;

}


.card-header span {

    color: #999;

    font-size: 10px;

}


/* =====================================================
   ITEMS
===================================================== */

.items-table-wrapper {

    overflow-x: auto;

}


.items-table {

    width: 100%;

    border-collapse: collapse;

}


.items-table th {

    text-align: left;

    padding: 12px 18px;

    background: #fafbfc;

    color: #888;

    font-size: 9px;

    text-transform: uppercase;

    letter-spacing: .4px;

    white-space: nowrap;

}


.items-table td {

    padding: 14px 18px;

    border-top:
        1px solid #f0f2f5;

    font-size: 12px;

    vertical-align: middle;

}


.medicine-name {

    color: #333;

    font-weight: 600;

}


.medicine-id {

    color: #aaa;

    font-size: 9px;

    margin-top: 4px;

}


.quantity {

    font-weight: 600;

    color: #555;

}


.price {

    color: #555;

    white-space: nowrap;

}


.item-total {

    font-weight: 600;

    color: #222;

    white-space: nowrap;

}


/* =====================================================
   SUMMARY
===================================================== */

.summary {

    padding: 20px;

}


.summary-row {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

    padding: 8px 0;

    font-size: 12px;

    color: #666;

}


.summary-row.total {

    border-top:
        1px solid #edf0f4;

    margin-top: 8px;

    padding-top: 15px;

    color: #222;

    font-size: 15px;

    font-weight: 700;

}


.total-amount {

    color: #238b39;

}


/* =====================================================
   INFO
===================================================== */

.info-list {

    padding: 5px 20px 18px;

}


.info-item {

    padding: 13px 0;

    border-bottom:
        1px solid #f0f2f5;

}


.info-item:last-child {

    border-bottom: none;

}


.info-label {

    color: #999;

    font-size: 9px;

    text-transform: uppercase;

    letter-spacing: .5px;

    margin-bottom: 5px;

}


.info-value {

    color: #333;

    font-size: 12px;

    line-height: 1.6;

}


.customer-name {

    font-weight: 600;

}


.phone-link {

    color: #238b39;

    font-weight: 500;

}


/* =====================================================
   PAYMENT
===================================================== */

.payment {

    font-size: 11px;

    font-weight: 600;

}


.payment-paid {

    color: #197333;

}


.payment-pending {

    color: #a87900;

}


.payment-failed {

    color: #a51d2d;

}


.payment-refunded {

    color: #6c5a9b;

}


/* =====================================================
   DELIVERY ACTION
===================================================== */

.action-area {

    padding: 20px;

}


.action-description {

    color: #777;

    font-size: 11px;

    line-height: 1.6;

    margin-bottom: 15px;

}


.action-btn {

    display: flex;

    align-items: center;

    justify-content: center;

    width: 100%;

    min-height: 43px;

    border: none;

    border-radius: 8px;

    color: #fff;

    background: #238b39;

    font-family: inherit;

    font-size: 12px;

    font-weight: 600;

    cursor: pointer;

    transition: .2s;

    margin-top: 9px;

}


.action-btn:hover {

    background: #1b7330;

}


.action-btn.blue {

    background: #315ea8;

}


.action-btn.blue:hover {

    background: #274b86;

}


.action-btn.red {

    background: #b42318;

}


.action-btn.red:hover {

    background: #941b12;

}


.action-btn.disabled {

    background: #d8dde2;

    color: #7f8790;

    cursor: not-allowed;

}


/* =====================================================
   ACTION NOTE
===================================================== */

.action-note {

    margin-top: 15px;

    padding: 12px;

    background: #fafbfc;

    border:
        1px solid #edf0f4;

    border-radius: 8px;

    color: #777;

    font-size: 10px;

    line-height: 1.6;

}


/* =====================================================
   FAILURE AREA
===================================================== */

.failure-box {

    margin-top: 15px;

    padding-top: 15px;

    border-top:
        1px solid #edf0f4;

}


/* =====================================================
   TIMELINE
===================================================== */

.timeline {

    padding: 18px 20px 20px;

}


.timeline-item {

    position: relative;

    padding-left: 30px;

    padding-bottom: 22px;

}


.timeline-item:last-child {

    padding-bottom: 0;

}


.timeline-item::before {

    content: '';

    position: absolute;

    left: 7px;

    top: 18px;

    bottom: -2px;

    width: 2px;

    background: #e4e9ee;

}


.timeline-item:last-child::before {

    display: none;

}


.timeline-dot {

    position: absolute;

    left: 0;

    top: 2px;

    width: 16px;

    height: 16px;

    border-radius: 50%;

    background: #dfe4e8;

    border: 3px solid #fff;

    box-shadow:
        0 0 0 1px #dfe4e8;

}


.timeline-item.completed
.timeline-dot {

    background: #238b39;

    box-shadow:
        0 0 0 1px #238b39;

}


.timeline-title {

    font-size: 11px;

    font-weight: 600;

    color: #444;

}


.timeline-time {

    font-size: 9px;

    color: #999;

    margin-top: 4px;

}


.timeline-item.completed
.timeline-title {

    color: #238b39;

}


/* =====================================================
   FAILURE TIMELINE
===================================================== */

.timeline-item.failed
.timeline-dot {

    background: #b42318;

    box-shadow:
        0 0 0 1px #b42318;

}


.timeline-item.failed
.timeline-title {

    color: #b42318;

}


/* =====================================================
   NOTE
===================================================== */

.note-box {

    padding: 15px 20px 20px;

}


.note-content {

    background: #fafbfc;

    border:
        1px solid #edf0f4;

    border-radius: 8px;

    padding: 12px;

    color: #666;

    font-size: 11px;

    line-height: 1.6;

}


/* =====================================================
   EMPTY ITEMS
===================================================== */

.empty-items {

    text-align: center;

    padding: 40px 20px;

    color: #999;

    font-size: 12px;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1000px) {

    .details-grid {

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


    .order-header {

        display: block;

    }


    .order-header-right {

        text-align: left;

        margin-top: 15px;

    }


    .delivery-info {

        display: none;

    }


    .topbar-title h1 {

        font-size: 18px;

    }


    .order-header-left h2 {

        font-size: 17px;

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


        <a
            href="orders.php"
            class="active"
        >

            <span class="menu-icon">
                📦
            </span>

            My Orders

        </a>


        <a href="profile.php">

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
     MAIN
===================================================== -->

<main class="main-content">


<!-- =====================================================
     TOPBAR
===================================================== -->

<header class="topbar">


    <div class="topbar-title">

        <h1>
            Order Details
        </h1>

        <p>
            View assigned delivery information
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
     BACK
===================================================== -->

<div class="back-row">

    <a
        href="orders.php"
        class="back-btn"
    >

        ← Back to My Orders

    </a>

</div>


<!-- =====================================================
     ALERTS
===================================================== -->

<?php if ($statusMessage !== ''): ?>

    <div class="alert alert-success">

        ✓

        <?= e($statusMessage) ?>

    </div>

<?php endif; ?>


<?php if ($statusError !== ''): ?>

    <div class="alert alert-error">

        ⚠

        <?= e($statusError) ?>

    </div>

<?php endif; ?>


<!-- =====================================================
     ORDER HEADER
===================================================== -->

<div class="order-header">


    <div class="order-header-left">


        <h2>

            Order #

            <?= e(
                $delivery['order_number']
                ?? $orderId
            ) ?>

        </h2>


        <p>

            Placed on

            <?= e(
                formatDateTime(
                    $delivery['order_created_at']
                    ?? ''
                )
            ) ?>

        </p>


    </div>


    <div class="order-header-right">


        <span
            class="status <?= e($statusClass) ?>"
        >

            <?= e($statusLabel) ?>

        </span>


        <div class="order-date">

            Delivery ID:
            #<?= (int)$deliveryId ?>

        </div>


    </div>


</div>


<!-- =====================================================
     MAIN GRID
===================================================== -->

<div class="details-grid">


<!-- =====================================================
     LEFT COLUMN
===================================================== -->

<div>


<!-- =====================================================
     ORDER ITEMS
===================================================== -->

<div class="card">


    <div class="card-header">


        <h3>
            🛒 Order Items
        </h3>


        <span>

            <?= (int)$totalItems ?>

            Item<?= $totalItems !== 1 ? 's' : '' ?>

        </span>


    </div>


    <?php if (!empty($orderItems)): ?>


        <div class="items-table-wrapper">


            <table class="items-table">


                <thead>

                    <tr>

                        <th>
                            Medicine
                        </th>

                        <th>
                            Qty
                        </th>

                        <th>
                            Unit Price
                        </th>

                        <th>
                            Total
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php foreach (
                    $orderItems
                    as $item
                ): ?>


                    <tr>


                        <td>

                            <div class="medicine-name">

                                <?= e(
                                    $item['medicine_name']
                                    ?? 'Medicine'
                                ) ?>

                            </div>


                            <div class="medicine-id">

                                Medicine ID:

                                <?= (int)(
                                    $item['medicine_id']
                                    ?? 0
                                ) ?>

                            </div>

                        </td>


                        <td>

                            <span class="quantity">

                                ×

                                <?= (int)(
                                    $item['quantity']
                                    ?? 1
                                ) ?>

                            </span>

                        </td>


                        <td>

                            <span class="price">

                                ₹<?= number_format(
                                    (float)(
                                        $item['unit_price']
                                        ?? 0
                                    ),
                                    2
                                ) ?>

                            </span>

                        </td>


                        <td>

                            <span class="item-total">

                                ₹<?= number_format(
                                    (float)(
                                        $item['total_price']
                                        ?? 0
                                    ),
                                    2
                                ) ?>

                            </span>

                        </td>


                    </tr>


                <?php endforeach; ?>


                </tbody>


            </table>


        </div>


    <?php else: ?>


        <div class="empty-items">

            No items found for this order.

        </div>


    <?php endif; ?>


</div>


<!-- =====================================================
     ORDER SUMMARY
===================================================== -->

<div class="card">


    <div class="card-header">

        <h3>
            💰 Order Summary
        </h3>

    </div>


    <div class="summary">


        <div class="summary-row">

            <span>
                Total Items
            </span>

            <strong>
                <?= (int)$totalItems ?>
            </strong>

        </div>


        <div class="summary-row">

            <span>
                Payment Method
            </span>

            <strong>
                <?= e($paymentMethod) ?>
            </strong>

        </div>


        <div class="summary-row">

            <span>
                Payment Status
            </span>


            <span
                class="payment <?= e($paymentClass) ?>"
            >

                <?= e(
                    ucfirst(
                        $paymentStatus
                    )
                ) ?>

            </span>

        </div>


        <div class="summary-row total">

            <span>
                Order Total
            </span>


            <span class="total-amount">

                ₹<?= number_format(
                    $totalAmount,
                    2
                ) ?>

            </span>

        </div>


    </div>


</div>


<!-- =====================================================
     DELIVERY TIMELINE
===================================================== -->

<div class="card">


    <div class="card-header">

        <h3>
            🕒 Delivery Timeline
        </h3>

    </div>


    <div class="timeline">


        <!-- ASSIGNED -->

        <div
            class="timeline-item
            <?= !empty($delivery['assigned_at'])
                ? 'completed'
                : ''
            ?>"
        >

            <span class="timeline-dot"></span>


            <div class="timeline-title">

                Assigned

            </div>


            <?php if (
                !empty(
                    $delivery['assigned_at']
                )
            ): ?>

                <div class="timeline-time">

                    <?= e(
                        formatDateTime(
                            $delivery['assigned_at']
                        )
                    ) ?>

                </div>

            <?php endif; ?>

        </div>


        <!-- PICKED UP -->

        <div
            class="timeline-item
            <?= !empty($delivery['picked_up_at'])
                ? 'completed'
                : ''
            ?>"
        >

            <span class="timeline-dot"></span>


            <div class="timeline-title">

                Picked Up

            </div>


            <?php if (
                !empty(
                    $delivery['picked_up_at']
                )
            ): ?>

                <div class="timeline-time">

                    <?= e(
                        formatDateTime(
                            $delivery['picked_up_at']
                        )
                    ) ?>

                </div>

            <?php endif; ?>

        </div>


        <!-- OUT FOR DELIVERY -->

        <div
            class="timeline-item
            <?= !empty(
                $delivery['out_for_delivery_at']
            )
                ? 'completed'
                : ''
            ?>"
        >

            <span class="timeline-dot"></span>


            <div class="timeline-title">

                Out for Delivery

            </div>


            <?php if (
                !empty(
                    $delivery['out_for_delivery_at']
                )
            ): ?>

                <div class="timeline-time">

                    <?= e(
                        formatDateTime(
                            $delivery['out_for_delivery_at']
                        )
                    ) ?>

                </div>

            <?php endif; ?>

        </div>


        <!-- DELIVERED -->

        <div
            class="timeline-item
            <?= !empty(
                $delivery['delivered_at']
            )
                ? 'completed'
                : ''
            ?>"
        >

            <span class="timeline-dot"></span>


            <div class="timeline-title">

                Delivered

            </div>


            <?php if (
                !empty(
                    $delivery['delivered_at']
                )
            ): ?>

                <div class="timeline-time">

                    <?= e(
                        formatDateTime(
                            $delivery['delivered_at']
                        )
                    ) ?>

                </div>

            <?php endif; ?>

        </div>


        <!-- FAILED -->

        <?php if (
            $currentDeliveryStatus === 'failed'
        ): ?>

            <div class="timeline-item failed">

                <span class="timeline-dot"></span>


                <div class="timeline-title">

                    Delivery Failed

                </div>


                <div class="timeline-time">

                    <?= e(
                        formatDateTime(
                            $delivery['delivery_updated_at']
                            ?? ''
                        )
                    ) ?>

                </div>

            </div>

        <?php endif; ?>


    </div>


</div>


</div>


<!-- =====================================================
     RIGHT COLUMN
===================================================== -->

<div>


<!-- =====================================================
     CUSTOMER INFORMATION
===================================================== -->

<div class="card">


    <div class="card-header">

        <h3>
            👤 Customer Information
        </h3>

    </div>


    <div class="info-list">


        <div class="info-item">

            <div class="info-label">
                Customer Name
            </div>


            <div class="info-value customer-name">

                <?= e(
                    $delivery['customer_name']
                    ?? 'Customer'
                ) ?>

            </div>

        </div>


        <div class="info-item">

            <div class="info-label">
                Mobile Number
            </div>


            <div class="info-value">


                <?php if (
                    !empty(
                        $delivery['customer_mobile']
                    )
                ): ?>


                    <a
                        href="tel:<?= e(
                            $delivery['customer_mobile']
                        ) ?>"
                        class="phone-link"
                    >

                        📱

                        <?= e(
                            $delivery['customer_mobile']
                        ) ?>

                    </a>


                <?php else: ?>


                    N/A


                <?php endif; ?>


            </div>

        </div>


        <div class="info-item">

            <div class="info-label">
                Delivery Address
            </div>


            <div class="info-value">


                <?= e(
                    $delivery['delivery_address']
                    ?? 'N/A'
                ) ?>


                <?php if (
                    !empty(
                        $delivery['city']
                    )
                ): ?>

                    <br>

                    <?= e(
                        $delivery['city']
                    ) ?>


                    <?php if (
                        !empty(
                            $delivery['state']
                        )
                    ): ?>

                        ,
                        <?= e(
                            $delivery['state']
                        ) ?>

                    <?php endif; ?>


                    <?php if (
                        !empty(
                            $delivery['pincode']
                        )
                    ): ?>

                        -
                        <?= e(
                            $delivery['pincode']
                        ) ?>

                    <?php endif; ?>


                <?php endif; ?>


            </div>

        </div>


        <div class="info-item">

            <div class="info-label">
                Payment
            </div>


            <div class="info-value">

                <?= e($paymentMethod) ?>

                -

                <span
                    class="payment <?= e($paymentClass) ?>"
                >

                    <?= e(
                        ucfirst(
                            $paymentStatus
                        )
                    ) ?>

                </span>

            </div>

        </div>


    </div>


</div>


<!-- =====================================================
     DELIVERY ACTIONS
===================================================== -->

<div class="card">


    <div class="card-header">


        <h3>
            🚚 Delivery Action
        </h3>


        <span>
            <?= e($statusLabel) ?>
        </span>


    </div>


    <div class="action-area">


        <!-- =============================================
             PENDING
        ============================================== -->

        <?php if (
            $currentDeliveryStatus === 'pending'
        ): ?>


            <p class="action-description">

                This delivery is waiting for admin
                assignment. You cannot start delivery
                until the order is assigned to you.

            </p>


            <button
                type="button"
                class="action-btn disabled"
                disabled
            >

                ⏳ Waiting for Assignment

            </button>


        <!-- =============================================
             ASSIGNED
        ============================================== -->

        <?php elseif (
            $currentDeliveryStatus === 'assigned'
        ): ?>


            <p class="action-description">

                This order is assigned to you.
                Pick up the order from the store
                before starting the delivery.

            </p>


            <a
                href="pickup.php?delivery_id=<?= (int)$deliveryId ?>"
                class="action-btn"
            >

                📦 Mark as Picked Up

            </a>


            <div class="action-note">

                After picking up the medicine,
                the next step will be to mark the
                order as <strong>Out for Delivery</strong>.

            </div>


        <!-- =============================================
             PICKED UP
        ============================================== -->

        <?php elseif (
            $currentDeliveryStatus === 'picked_up'
        ): ?>


            <p class="action-description">

                You have picked up this order.
                Start the delivery when you leave
                for the customer's address.

            </p>


            <a
                href="out-for-delivery.php?delivery_id=<?= (int)$deliveryId ?>"
                class="action-btn blue"
            >

                🛵 Mark as Out for Delivery

            </a>


            <div class="action-note">

                A secure 6-digit delivery OTP will be
                generated when the order is moved to
                <strong>Out for Delivery</strong>.

            </div>


        <!-- =============================================
             OUT FOR DELIVERY
        ============================================== -->

        <?php elseif (
            $currentDeliveryStatus === 'out_for_delivery'
        ): ?>


            <p class="action-description">

                This order is currently out for delivery.
                After reaching the customer, ask the
                customer for the 6-digit OTP and verify
                it before completing the delivery.

            </p>


            <a
                href="deliver.php?delivery_id=<?= (int)$deliveryId ?>"
                class="action-btn"
            >

                ✓ Verify OTP & Complete Delivery

            </a>


            <div class="action-note">

                🔐 The delivery OTP is confidential.
                Never mark the order as delivered without
                verifying the OTP provided by the customer.

            </div>


            <div class="failure-box">


                <p class="action-description">

                    If you cannot complete this delivery,
                    you can mark it as failed with a
                    valid failure reason.

                </p>


                <a
                    href="failed.php?delivery_id=<?= (int)$deliveryId ?>"
                    class="action-btn red"
                >

                    ⚠ Mark Delivery Failed

                </a>


            </div>


        <!-- =============================================
             DELIVERED
        ============================================== -->

        <?php elseif (
            $currentDeliveryStatus === 'delivered'
        ): ?>


            <p class="action-description">

                This order has been successfully
                delivered to the customer.

            </p>


            <button
                type="button"
                class="action-btn disabled"
                disabled
            >

                ✓ Delivery Completed

            </button>


        <!-- =============================================
             FAILED
        ============================================== -->

        <?php elseif (
            $currentDeliveryStatus === 'failed'
        ): ?>


            <p class="action-description">

                This delivery has been marked as failed.

            </p>


            <button
                type="button"
                class="action-btn disabled"
                disabled
            >

                ⚠ Delivery Failed

            </button>


        <!-- =============================================
             CANCELLED
        ============================================== -->

        <?php elseif (
            $currentDeliveryStatus === 'cancelled'
        ): ?>


            <p class="action-description">

                This delivery has been cancelled.
                No further action is available.

            </p>


            <button
                type="button"
                class="action-btn disabled"
                disabled
            >

                ✕ Delivery Cancelled

            </button>


        <?php endif; ?>


    </div>


</div>


<!-- =====================================================
     DELIVERY INFORMATION
===================================================== -->

<div class="card">


    <div class="card-header">

        <h3>
            📋 Delivery Information
        </h3>

    </div>


    <div class="info-list">


        <div class="info-item">

            <div class="info-label">
                Delivery ID
            </div>


            <div class="info-value">

                #<?= (int)$deliveryId ?>

            </div>

        </div>


        <div class="info-item">

            <div class="info-label">
                Order Number
            </div>


            <div class="info-value">

                <?= e(
                    $delivery['order_number']
                    ?? $orderId
                ) ?>

            </div>

        </div>


        <div class="info-item">

            <div class="info-label">
                Assigned To
            </div>


            <div class="info-value">

                <?= e(
                    $deliveryBoyName
                ) ?>

            </div>

        </div>


        <div class="info-item">

            <div class="info-label">
                Delivery Status
            </div>


            <div class="info-value">

                <span
                    class="status <?= e($statusClass) ?>"
                >

                    <?= e($statusLabel) ?>

                </span>

            </div>

        </div>


        <div class="info-item">

            <div class="info-label">
                Order Status
            </div>


            <div class="info-value">

                <span
                    class="status <?= e($orderStatusClass) ?>"
                >

                    <?= e(
                        ucwords(
                            str_replace(
                                '_',
                                ' ',
                                $orderStatus
                            )
                        )
                    ) ?>

                </span>

            </div>

        </div>


        <?php if (
            !empty(
                $delivery['failure_reason']
            )
        ): ?>


            <div class="info-item">


                <div class="info-label">
                    Failure Reason
                </div>


                <div class="info-value">

                    <?= nl2br(
                        e(
                            $delivery['failure_reason']
                        )
                    ) ?>

                </div>


            </div>


        <?php endif; ?>


        <?php if (
            !empty(
                $delivery['delivery_note']
            )
        ): ?>


            <div class="info-item">


                <div class="info-label">
                    Delivery Note
                </div>


                <div class="info-value">

                    <?= nl2br(
                        e(
                            $delivery['delivery_note']
                        )
                    ) ?>

                </div>


            </div>


        <?php endif; ?>


        <?php if (
            !empty(
                $delivery['delivery_updated_at']
            )
        ): ?>


            <div class="info-item">


                <div class="info-label">
                    Last Updated
                </div>


                <div class="info-value">

                    <?= e(
                        formatDateTime(
                            $delivery['delivery_updated_at']
                        )
                    ) ?>

                </div>


            </div>


        <?php endif; ?>


    </div>


</div>


<!-- =====================================================
     ADMIN NOTE
===================================================== -->

<?php if (
    !empty(
        $delivery['admin_note']
    )
): ?>


<div class="card">


    <div class="card-header">

        <h3>
            📝 Admin Note
        </h3>

    </div>


    <div class="note-box">


        <div class="note-content">

            <?= nl2br(
                e(
                    $delivery['admin_note']
                )
            ) ?>

        </div>


    </div>


</div>


<?php endif; ?>


</div>


</div>


</div>


</main>


</div>


</body>

</html>