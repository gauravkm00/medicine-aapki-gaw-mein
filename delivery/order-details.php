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

$deliveryBoyName =
    $_SESSION['name'] ?? 'Delivery Boy';


// =====================================================
// ORDER ID
// =====================================================

$orderId =
    isset($_GET['id'])
        ? (int)$_GET['id']
        : 0;


if ($orderId <= 0) {

    header("Location: orders.php");
    exit;

}


// =====================================================
// ALLOWED STATUS
// =====================================================

$allowedStatuses = [
    'pending',
    'confirmed',
    'processing',
    'ready',
    'out_for_delivery',
    'delivered',
    'cancelled'
];


// =====================================================
// UPDATE ORDER STATUS
// =====================================================

$statusMessage = '';
$statusError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $newStatus =
        trim(
            $_POST['order_status'] ?? ''
        );


    if (
        !in_array(
            $newStatus,
            $allowedStatuses,
            true
        )
    ) {

        $statusError =
            "Invalid order status.";

    } else {

        /*
         * Important:
         * Update only if this order belongs
         * to the logged-in delivery boy.
         */

        $updateSql = "
            UPDATE orders
            SET order_status = ?
            WHERE id = ?
            AND delivery_boy_id = ?
        ";


        $updateStmt =
            mysqli_prepare(
                $conn,
                $updateSql
            );


        if ($updateStmt) {

            mysqli_stmt_bind_param(
                $updateStmt,
                "sii",
                $newStatus,
                $orderId,
                $deliveryBoyId
            );


            if (
                mysqli_stmt_execute(
                    $updateStmt
                )
            ) {

                if (
                    mysqli_stmt_affected_rows(
                        $updateStmt
                    ) >= 0
                ) {

                    $statusMessage =
                        "Order status updated successfully.";

                }

            } else {

                $statusError =
                    "Unable to update order status.";

            }


            mysqli_stmt_close(
                $updateStmt
            );

        } else {

            $statusError =
                "Database error while updating status.";

        }

    }

}


// =====================================================
// FETCH ORDER
// =====================================================

$order = null;


$orderSql = "
    SELECT
        id,
        order_number,
        customer_name,
        customer_mobile,
        delivery_address,
        city,
        state,
        pincode,
        total_amount,
        payment_method,
        payment_status,
        order_status,
        created_at,
        updated_at
    FROM orders
    WHERE id = ?
    AND delivery_boy_id = ?
    LIMIT 1
";


$orderStmt =
    mysqli_prepare(
        $conn,
        $orderSql
    );


if ($orderStmt) {

    mysqli_stmt_bind_param(
        $orderStmt,
        "ii",
        $orderId,
        $deliveryBoyId
    );


    mysqli_stmt_execute(
        $orderStmt
    );


    $orderResult =
        mysqli_stmt_get_result(
            $orderStmt
        );


    if ($orderResult) {

        $order =
            mysqli_fetch_assoc(
                $orderResult
            );

    }


    mysqli_stmt_close(
        $orderStmt
    );

}


// =====================================================
// ORDER NOT FOUND
// =====================================================

if (!$order) {

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
        <title>Order Not Found | Medicine Aapki Gaw Mein</title>

        <style>

            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            body {
                font-family: Arial, sans-serif;
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
                border: 1px solid #edf0f4;
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
                Order Not Found
            </h2>

            <p>
                The requested order does not exist
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
// FETCH ORDER ITEMS
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
// ORDER DATA
// =====================================================

$orderStatus =
    strtolower(
        trim(
            $order['order_status']
            ?? 'pending'
        )
    );


$statusClass =
    'status-' .
    str_replace(
        ' ',
        '_',
        $orderStatus
    );


$statusLabel =
    ucwords(
        str_replace(
            '_',
            ' ',
            $orderStatus
        )
    );


$paymentStatus =
    strtolower(
        trim(
            $order['payment_status']
            ?? 'pending'
        )
    );


$paymentClass =
    'payment-' .
    $paymentStatus;


$paymentMethod =
    strtoupper(
        $order['payment_method']
        ?? 'COD'
    );


$totalAmount =
    (float)(
        $order['total_amount']
        ?? 0
    );


$totalItems =
    0;


foreach ($orderItems as $item) {

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
            $deliveryBoyName,
            0,
            1
        )
    );


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
        $order['order_number']
        ?? $order['id']
    ) ?>
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


.status-pending {

    background: #fff7df;

    color: #a87900;

}


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


.status-out_for_delivery {

    background: #eaf8ed;

    color: #238b39;

}


.status-delivered {

    background: #e6f7eb;

    color: #197333;

}


.status-cancelled {

    background: #fff0f1;

    color: #a51d2d;

}


.order-date {

    color: #999;

    font-size: 10px;

    margin-top: 7px;

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
   TOTAL
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

    line-height: 1.5;

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
   STATUS FORM
===================================================== */

.status-form {

    padding: 20px;

}


.status-form label {

    display: block;

    color: #555;

    font-size: 11px;

    font-weight: 600;

    margin-bottom: 7px;

}


.status-select {

    width: 100%;

    height: 43px;

    border:
        1px solid #dfe4e8;

    border-radius: 8px;

    padding: 0 12px;

    font-family: inherit;

    font-size: 12px;

    outline: none;

    background: #fff;

}


.status-select:focus {

    border-color: #51b848;

    box-shadow:
        0 0 0 3px
        rgba(81,184,72,.10);

}


.update-btn {

    width: 100%;

    height: 43px;

    border: none;

    border-radius: 8px;

    background: #238b39;

    color: #fff;

    font-family: inherit;

    font-size: 12px;

    font-weight: 600;

    cursor: pointer;

    margin-top: 12px;

}


.update-btn:hover {

    background: #1b7330;

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
            View assigned order information
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
                $order['order_number']
                ?? $order['id']
            ) ?>

        </h2>


        <p>

            Placed on

            <?= e(
                date(
                    'd M Y, h:i A',
                    strtotime(
                        $order['created_at']
                    )
                )
            ) ?>

        </p>

    </div>


    <div class="order-header-right">


        <span
            class="status
            <?= e($statusClass) ?>"
        >

            <?= e($statusLabel) ?>

        </span>


        <div class="order-date">

            Order ID:
            #<?= (int)$order['id'] ?>

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

            <?= $totalItems ?>

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
                <?= $totalItems ?>
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
                class="payment
                <?= e($paymentClass) ?>"
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
                    $order['customer_name']
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
                        $order['customer_mobile']
                    )
                ): ?>

                    <a
                        href="tel:<?= e(
                            $order['customer_mobile']
                        ) ?>"
                        class="phone-link"
                    >

                        📱
                        <?= e(
                            $order['customer_mobile']
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
                    $order['delivery_address']
                    ?? 'N/A'
                ) ?>


                <?php if (
                    !empty(
                        $order['city']
                    )
                ): ?>

                    <br>

                    <?= e(
                        $order['city']
                    ) ?>


                    <?php if (
                        !empty(
                            $order['state']
                        )
                    ): ?>

                        ,
                        <?= e(
                            $order['state']
                        ) ?>

                    <?php endif; ?>


                    <?php if (
                        !empty(
                            $order['pincode']
                        )
                    ): ?>

                        -
                        <?= e(
                            $order['pincode']
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
                    class="payment
                    <?= e($paymentClass) ?>"
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
     UPDATE STATUS
===================================================== -->

<div class="card">


    <div class="card-header">

        <h3>
            🔄 Update Order Status
        </h3>

    </div>


    <form
        method="POST"
        class="status-form"
    >


        <label for="order_status">

            Order Status

        </label>


        <select
            name="order_status"
            id="order_status"
            class="status-select"
        >


            <?php foreach (
                $allowedStatuses
                as $allowedStatus
            ): ?>


                <option
                    value="<?= e(
                        $allowedStatus
                    ) ?>"
                    <?= $orderStatus ===
                        $allowedStatus
                            ? 'selected'
                            : ''
                    ?>
                >

                    <?= e(
                        ucwords(
                            str_replace(
                                '_',
                                ' ',
                                $allowedStatus
                            )
                        )
                    ) ?>

                </option>


            <?php endforeach; ?>


        </select>


        <button
            type="submit"
            class="update-btn"
        >

            ✓ Update Status

        </button>


    </form>


</div>


<!-- =====================================================
     DELIVERY NOTE
===================================================== -->

<div class="card">


    <div class="card-header">

        <h3>
            🚚 Delivery Information
        </h3>

    </div>


    <div class="info-list">


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
                Current Status
            </div>

            <div class="info-value">

                <span
                    class="status
                    <?= e($statusClass) ?>"
                >

                    <?= e($statusLabel) ?>

                </span>

            </div>

        </div>


        <?php if (
            !empty(
                $order['updated_at']
            )
        ): ?>

            <div class="info-item">

                <div class="info-label">
                    Last Updated
                </div>

                <div class="info-value">

                    <?= e(
                        date(
                            'd M Y, h:i A',
                            strtotime(
                                $order['updated_at']
                            )
                        )
                    ) ?>

                </div>

            </div>

        <?php endif; ?>


    </div>


</div>


</div>


</div>


</div>


</main>


</div>


</body>

</html>
