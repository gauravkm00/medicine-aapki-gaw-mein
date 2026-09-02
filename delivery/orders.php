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
// FILTERS
// =====================================================

$search =
    trim(
        $_GET['search'] ?? ''
    );

$status =
    trim(
        $_GET['status'] ?? ''
    );


// =====================================================
// ALLOWED DELIVERY STATUSES
// =====================================================

$allowedStatuses = [
    'assigned',
    'picked_up',
    'out_for_delivery',
    'delivered',
    'failed',
    'cancelled'
];


// =====================================================
// VALIDATE STATUS
// =====================================================

if (
    $status !== '' &&
    !in_array(
        $status,
        $allowedStatuses,
        true
    )
) {
    $status = '';
}


// =====================================================
// FETCH DELIVERIES / ORDERS
// =====================================================

$orders = [];


// =====================================================
// BASE QUERY
// =====================================================
//
// `deliveries` is the source of truth for delivery workflow.
//
// Delivery boy:
//     deliveries.delivery_person_id
//
// Delivery status:
//     deliveries.status
//
// Order/customer information:
//     orders table
// =====================================================

$sql = "
    SELECT

        /* =========================
           DELIVERY DATA
        ========================= */

        d.id AS delivery_id,
        d.order_id,
        d.delivery_person_id,

        d.status AS delivery_status,

        d.assigned_at,
        d.picked_up_at,
        d.out_for_delivery_at,
        d.delivered_at,

        d.delivery_note,
        d.failure_reason,

        /* =========================
           ORDER DATA
        ========================= */

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

        o.created_at

    FROM deliveries d

    INNER JOIN orders o
        ON o.id = d.order_id

    WHERE
        d.delivery_person_id = ?
";


// =====================================================
// SEARCH FILTER
// =====================================================

if ($search !== '') {

    $sql .= "
        AND (
            o.order_number LIKE ?
            OR o.customer_name LIKE ?
            OR o.customer_mobile LIKE ?
            OR o.delivery_address LIKE ?
            OR o.city LIKE ?
            OR o.state LIKE ?
            OR o.pincode LIKE ?
        )
    ";
}


// =====================================================
// DELIVERY STATUS FILTER
// =====================================================

if ($status !== '') {

    $sql .= "
        AND d.status = ?
    ";
}


// =====================================================
// ORDER BY
// =====================================================

$sql .= "
    ORDER BY d.id DESC
";


// =====================================================
// PREPARE STATEMENT
// =====================================================

$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );


// =====================================================
// EXECUTE QUERY
// =====================================================

if ($stmt) {

    // =================================================
    // SEARCH + STATUS
    // =================================================

    if (
        $search !== '' &&
        $status !== ''
    ) {

        $searchValue =
            '%' . $search . '%';

        mysqli_stmt_bind_param(
            $stmt,
            "issssssss",
            $deliveryBoyId,
            $searchValue,
            $searchValue,
            $searchValue,
            $searchValue,
            $searchValue,
            $searchValue,
            $searchValue,
            $status
        );
    }

    // =================================================
    // SEARCH ONLY
    // =================================================

    elseif ($search !== '') {

        $searchValue =
            '%' . $search . '%';

        mysqli_stmt_bind_param(
            $stmt,
            "isssssss",
            $deliveryBoyId,
            $searchValue,
            $searchValue,
            $searchValue,
            $searchValue,
            $searchValue,
            $searchValue,
            $searchValue
        );
    }

    // =================================================
    // STATUS ONLY
    // =================================================

    elseif ($status !== '') {

        mysqli_stmt_bind_param(
            $stmt,
            "is",
            $deliveryBoyId,
            $status
        );
    }

    // =================================================
    // NO FILTER
    // =================================================

    else {

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $deliveryBoyId
        );
    }


    // =================================================
    // EXECUTE
    // =================================================

    if (
        mysqli_stmt_execute($stmt)
    ) {

        $result =
            mysqli_stmt_get_result($stmt);

        if ($result) {

            while (
                $row =
                mysqli_fetch_assoc($result)
            ) {

                $orders[] = $row;
            }
        }
    }


    mysqli_stmt_close($stmt);
}


// =====================================================
// TOTAL ORDERS
// =====================================================

$totalOrders =
    count($orders);


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
    "My Orders";

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


.order-count {

    background: #eaf7ed;

    color: #238b39;

    padding: 8px 13px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: 600;

}


/* =====================================================
   FILTER CARD
===================================================== */

.filter-card {

    background: #fff;

    border:
        1px solid #edf0f4;

    border-radius: 14px;

    padding: 18px;

    margin-bottom: 20px;

}


.filter-form {

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        220px
        auto
        auto;

    gap: 12px;

    align-items: end;

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


.form-control:focus {

    border-color: #51b848;

    box-shadow:
        0 0 0 3px
        rgba(81,184,72,.10);

}


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


.btn-search {

    background: #238b39;

    color: #fff;

}


.btn-search:hover {

    background: #1b7330;

}


.btn-reset {

    background: #f1f4f6;

    color: #555;

    border:
        1px solid #e0e5e9;

}


.btn-reset:hover {

    background: #e8ecef;

}


/* =====================================================
   ORDERS CARD
===================================================== */

.card {

    background: #fff;

    border:
        1px solid #edf0f4;

    border-radius: 14px;

    overflow: hidden;

}


.card-header {

    padding: 18px 20px;

    border-bottom:
        1px solid #edf0f4;

    display: flex;

    align-items: center;

    justify-content: space-between;

}


.card-header h3 {

    font-size: 15px;

    color: #222;

}


.card-header span {

    color: #999;

    font-size: 11px;

}


/* =====================================================
   TABLE
===================================================== */

.table-wrapper {

    width: 100%;

    overflow-x: auto;

}


.orders-table {

    width: 100%;

    border-collapse: collapse;

}


.orders-table th {

    text-align: left;

    padding: 13px 18px;

    background: #fafbfc;

    color: #888;

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: .4px;

    white-space: nowrap;

}


.orders-table td {

    padding: 15px 18px;

    border-top:
        1px solid #f0f2f5;

    font-size: 12px;

    color: #555;

    vertical-align: middle;

}


.orders-table tbody tr {

    transition: .15s;

}


.orders-table tbody tr:hover {

    background: #fafdfb;

}


.order-number {

    color: #222;

    font-weight: 600;

}


.order-date {

    color: #999;

    font-size: 10px;

    margin-top: 4px;

}


.customer-name {

    color: #333;

    font-weight: 500;

}


.customer-mobile {

    color: #999;

    font-size: 10px;

    margin-top: 4px;

}


.address {

    max-width: 230px;

    line-height: 1.5;

    color: #666;

}


.amount {

    color: #222;

    font-weight: 600;

    white-space: nowrap;

}


/* =====================================================
   STATUS
===================================================== */

.status {

    display: inline-block;

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 9px;

    font-weight: 600;

    text-transform: capitalize;

    white-space: nowrap;

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


/* =====================================================
   PAYMENT
===================================================== */

.payment {

    font-size: 10px;

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
   ACTION
===================================================== */

.view-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding: 7px 11px;

    border:
        1px solid #dfe5e9;

    border-radius: 6px;

    color: #238b39;

    font-size: 10px;

    font-weight: 600;

    white-space: nowrap;

    transition: .2s;

}


.view-btn:hover {

    background: #eef8f0;

    border-color: #b9ddc0;

}


/* =====================================================
   EMPTY
===================================================== */

.empty-state {

    text-align: center;

    padding: 65px 20px;

    color: #999;

}


.empty-icon {

    font-size: 45px;

    margin-bottom: 15px;

}


.empty-state h4 {

    color: #555;

    font-size: 15px;

    margin-bottom: 6px;

}


.empty-state p {

    font-size: 11px;

    line-height: 1.6;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1050px) {

    .filter-form {

        grid-template-columns:
            1fr 1fr;

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

    .page-header {

        display: block;

    }

    .order-count {

        display: inline-block;

        margin-top: 10px;

    }

    .filter-form {

        grid-template-columns: 1fr;

    }

    .delivery-info {

        display: none;

    }

    .topbar-title h1 {

        font-size: 18px;

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
     MAIN CONTENT
===================================================== -->

<main class="main-content">


<!-- =====================================================
     TOPBAR
===================================================== -->

<header class="topbar">


    <div class="topbar-title">

        <h1>
            My Orders
        </h1>

        <p>
            Manage your assigned deliveries
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


    <div>

        <h2>
            Assigned Orders
        </h2>

        <p>
            View and manage orders assigned to you.
        </p>

    </div>


    <div class="order-count">

        <?= $totalOrders ?>

        Order<?= $totalOrders !== 1 ? 's' : '' ?>

    </div>


</div>


<!-- =====================================================
     FILTER
===================================================== -->

<div class="filter-card">


<form
    method="GET"
    action="orders.php"
    class="filter-form"
>


    <!-- SEARCH -->

    <div class="form-group">

        <label for="search">
            Search Orders
        </label>


        <input
            type="text"
            id="search"
            name="search"
            class="form-control"
            placeholder="Order number, customer, mobile..."
            value="<?= e($search) ?>"
        >

    </div>


    <!-- STATUS -->

    <div class="form-group">

        <label for="status">
            Delivery Status
        </label>


        <select
            id="status"
            name="status"
            class="form-control"
        >

            <option value="">
                All Status
            </option>


            <?php foreach (
                $allowedStatuses
                as $allowedStatus
            ): ?>


                <option
                    value="<?= e($allowedStatus) ?>"
                    <?= $status === $allowedStatus
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

    </div>


    <!-- SEARCH BUTTON -->

    <button
        type="submit"
        class="btn btn-search"
    >
        🔍 Search
    </button>


    <!-- RESET -->

    <a
        href="orders.php"
        class="btn btn-reset"
    >
        ↻ Reset
    </a>


</form>


</div>


<!-- =====================================================
     ORDERS CARD
===================================================== -->

<div class="card">


    <div class="card-header">


        <h3>
            📦 My Assigned Orders
        </h3>


        <span>

            <?php if (
                $search !== '' ||
                $status !== ''
            ): ?>

                Filtered Results

            <?php else: ?>

                All Assigned Orders

            <?php endif; ?>

        </span>


    </div>


    <div class="table-wrapper">


    <?php if (!empty($orders)): ?>


        <table class="orders-table">


            <thead>

                <tr>

                    <th>
                        Order
                    </th>

                    <th>
                        Customer
                    </th>

                    <th>
                        Address
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


            <?php foreach ($orders as $order): ?>


                <?php

                // =================================================
                // DELIVERY STATUS
                // =================================================

                $deliveryStatus =
                    strtolower(
                        trim(
                            $order['delivery_status']
                            ?? 'assigned'
                        )
                    );


                $statusClass =
                    'status-' .
                    str_replace(
                        ' ',
                        '_',
                        $deliveryStatus
                    );


                $statusLabel =
                    ucwords(
                        str_replace(
                            '_',
                            ' ',
                            $deliveryStatus
                        )
                    );


                // =================================================
                // PAYMENT STATUS
                // =================================================

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


                // =================================================
                // PAYMENT METHOD
                // =================================================

                $paymentMethod =
                    strtoupper(
                        $order['payment_method']
                        ?? 'COD'
                    );

                ?>


                <tr>


                    <!-- =================================================
                         ORDER
                    ================================================= -->

                    <td>


                        <div class="order-number">

                            #<?= e(
                                $order['order_number']
                                ?? $order['order_id']
                            ) ?>

                        </div>


                        <div class="order-date">

                            <?= e(
                                date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $order['created_at']
                                    )
                                )
                            ) ?>

                        </div>


                    </td>


                    <!-- =================================================
                         CUSTOMER
                    ================================================= -->

                    <td>


                        <div class="customer-name">

                            <?= e(
                                $order['customer_name']
                                ?? 'Customer'
                            ) ?>

                        </div>


                        <div class="customer-mobile">

                            📱

                            <?= e(
                                $order['customer_mobile']
                                ?? 'N/A'
                            ) ?>

                        </div>


                    </td>


                    <!-- =================================================
                         ADDRESS
                    ================================================= -->

                    <td>


                        <div class="address">

                            <?= e(
                                $order['delivery_address']
                                ?? ''
                            ) ?>


                            <?php if (
                                !empty(
                                    $order['city']
                                )
                            ): ?>

                                <br>

                                <?= e(
                                    $order['city']
                                ) ?>,


                                <?= e(
                                    $order['state']
                                ) ?>


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


                    </td>


                    <!-- =================================================
                         AMOUNT
                    ================================================= -->

                    <td>


                        <span class="amount">

                            ₹<?= number_format(
                                (float)(
                                    $order['total_amount']
                                    ?? 0
                                ),
                                2
                            ) ?>

                        </span>


                    </td>


                    <!-- =================================================
                         PAYMENT
                    ================================================= -->

                    <td>


                        <div>

                            <span class="payment">

                                <?= e(
                                    $paymentMethod
                                ) ?>

                            </span>

                        </div>


                        <div>

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


                    </td>


                    <!-- =================================================
                         DELIVERY STATUS
                    ================================================= -->

                    <td>


                        <span
                            class="status
                            <?= e($statusClass) ?>"
                        >

                            <?= e(
                                $statusLabel
                            ) ?>

                        </span>


                    </td>


                    <!-- =================================================
                         ACTION
                    ================================================= -->

                    <td>


                        <a
                            href="order-details.php?id=<?= (int)$order['order_id'] ?>&delivery_id=<?= (int)$order['delivery_id'] ?>"
                            class="view-btn"
                        >

                            👁 View

                        </a>


                    </td>


                </tr>


            <?php endforeach; ?>


            </tbody>


        </table>


    <?php else: ?>


        <div class="empty-state">


            <div class="empty-icon">
                📦
            </div>


            <h4>

                <?php if (
                    $search !== '' ||
                    $status !== ''
                ): ?>

                    No Matching Orders

                <?php else: ?>

                    No Orders Assigned

                <?php endif; ?>

            </h4>


            <p>

                <?php if (
                    $search !== '' ||
                    $status !== ''
                ): ?>

                    Try changing your search
                    or delivery status filter.

                <?php else: ?>

                    You don't have any
                    orders assigned yet.

                <?php endif; ?>

            </p>


        </div>


    <?php endif; ?>


    </div>


</div>


</div>


</main>


</div>


</body>

</html>