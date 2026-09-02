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

$deliveryBoyId = (int)$_SESSION['user_id'];

$deliveryBoyName =
    $_SESSION['name'] ?? 'Delivery Boy';

$deliveryBoyMobile =
    $_SESSION['mobile'] ?? '';


// =====================================================
// DEFAULT COUNTS
// =====================================================

$totalOrders     = 0;
$pendingOrders   = 0;
$outForDelivery  = 0;
$deliveredOrders = 0;

$recentOrders = [];


// =====================================================
// DELIVERY COUNTS
// =====================================================
//
// IMPORTANT:
// Delivery workflow is controlled by deliveries table.
//
// deliveries.delivery_person_id
// deliveries.status
//
// orders.delivery_boy_id is NOT used here.
// =====================================================


// -----------------------------------------------------
// TOTAL ASSIGNED DELIVERIES
// -----------------------------------------------------

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM deliveries
     WHERE delivery_person_id = ?"
);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $deliveryBoyId
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {

        $row = mysqli_fetch_assoc($result);

        $totalOrders =
            (int)($row['total'] ?? 0);
    }

    mysqli_stmt_close($stmt);
}


// -----------------------------------------------------
// PENDING / ASSIGNED DELIVERIES
// -----------------------------------------------------
//
// Pending means delivery is waiting for pickup.
//
// Normally admin assignment creates:
// deliveries.status = assigned
//
// pending is also included so older/unassigned workflow
// records don't disappear from the dashboard.
// -----------------------------------------------------

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM deliveries
     WHERE delivery_person_id = ?
     AND status IN ('pending', 'assigned')"
);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $deliveryBoyId
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {

        $row = mysqli_fetch_assoc($result);

        $pendingOrders =
            (int)($row['total'] ?? 0);
    }

    mysqli_stmt_close($stmt);
}


// -----------------------------------------------------
// OUT FOR DELIVERY
// -----------------------------------------------------

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM deliveries
     WHERE delivery_person_id = ?
     AND status = 'out_for_delivery'"
);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $deliveryBoyId
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {

        $row = mysqli_fetch_assoc($result);

        $outForDelivery =
            (int)($row['total'] ?? 0);
    }

    mysqli_stmt_close($stmt);
}


// -----------------------------------------------------
// DELIVERED DELIVERIES
// -----------------------------------------------------

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM deliveries
     WHERE delivery_person_id = ?
     AND status = 'delivered'"
);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $deliveryBoyId
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {

        $row = mysqli_fetch_assoc($result);

        $deliveredOrders =
            (int)($row['total'] ?? 0);
    }

    mysqli_stmt_close($stmt);
}


// =====================================================
// RECENT DELIVERIES
// =====================================================
//
// deliveries = delivery workflow
// orders     = customer/order information
// =====================================================

$stmt = mysqli_prepare(
    $conn,
    "SELECT

        d.id AS delivery_id,
        d.order_id,
        d.status AS delivery_status,

        o.id,
        o.order_number,
        o.customer_name,
        o.customer_mobile,
        o.delivery_address,
        o.total_amount,
        o.created_at

     FROM deliveries d

     INNER JOIN orders o
        ON o.id = d.order_id

     WHERE d.delivery_person_id = ?

     ORDER BY d.id DESC

     LIMIT 8"
);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $deliveryBoyId
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {

        while ($row = mysqli_fetch_assoc($result)) {

            $recentOrders[] = $row;
        }
    }

    mysqli_stmt_close($stmt);
}


// =====================================================
// PAGE TITLE
// =====================================================

$pageTitle = "Delivery Dashboard";


// =====================================================
// INITIAL
// =====================================================

$firstLetter =
    strtoupper(
        substr(
            $deliveryBoyName,
            0,
            1
        )
    );

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

    margin-bottom: 25px;

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
   PROFILE CARD
===================================================== */

.welcome-card {

    background:
        linear-gradient(
            135deg,
            #238b39,
            #51b848
        );

    color: #fff;

    border-radius: 14px;

    padding: 24px 26px;

    margin-bottom: 25px;

    display: flex;

    align-items: center;

    justify-content: space-between;

}


.welcome-card h3 {

    font-size: 18px;

    margin-bottom: 7px;

}


.welcome-card p {

    font-size: 12px;

    color:
        rgba(255,255,255,.85);

}


.welcome-icon {

    width: 65px;

    height: 65px;

    border-radius: 16px;

    background:
        rgba(255,255,255,.15);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 34px;

}


/* =====================================================
   STATS
===================================================== */

.stats-grid {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 18px;

    margin-bottom: 25px;

}


.stat-card {

    background: #fff;

    border:
        1px solid #edf0f4;

    border-radius: 13px;

    padding: 20px;

    display: flex;

    align-items: center;

    gap: 14px;

}


.stat-icon {

    width: 48px;

    height: 48px;

    border-radius: 11px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;

    background: #eef8f0;

}


.stat-info span {

    display: block;

    font-size: 11px;

    color: #999;

    margin-bottom: 5px;

}


.stat-info strong {

    display: block;

    font-size: 23px;

    color: #222;

}


/* =====================================================
   MAIN GRID
===================================================== */

.dashboard-grid {

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        300px;

    gap: 22px;

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

}


.card-header {

    padding: 19px 20px;

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


.view-all {

    font-size: 11px;

    color: #238b39;

    font-weight: 600;

}


.card-body {

    padding: 0;

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


.order-number {

    color: #222;

    font-weight: 600;

}


.customer-name {

    color: #333;

    font-weight: 500;

}


.amount {

    color: #222;

    font-weight: 600;

}


.status {

    display: inline-block;

    padding: 5px 9px;

    border-radius: 20px;

    font-size: 9px;

    font-weight: 600;

    text-transform: capitalize;

}


.status-pending {

    background: #fff7df;

    color: #a87900;

}


.status-assigned {

    background: #edf5ff;

    color: #2766a3;

}


.status-picked_up {

    background: #fff4df;

    color: #a56500;

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

    color: #a51d2d;

}


.status-cancelled {

    background: #fff0f1;

    color: #a51d2d;

}


.details-btn {

    display: inline-block;

    padding: 6px 10px;

    border:
        1px solid #dfe5e9;

    border-radius: 6px;

    color: #238b39;

    font-size: 10px;

    font-weight: 600;

}


.details-btn:hover {

    background: #eef8f0;

}


/* =====================================================
   EMPTY
===================================================== */

.empty-state {

    text-align: center;

    padding: 45px 20px;

    color: #999;

}


.empty-icon {

    font-size: 38px;

    margin-bottom: 12px;

}


.empty-state h4 {

    color: #555;

    font-size: 14px;

    margin-bottom: 5px;

}


.empty-state p {

    font-size: 11px;

}


/* =====================================================
   QUICK ACTIONS
===================================================== */

.quick-actions {

    padding: 20px;

}


.quick-action {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 13px 12px;

    border:
        1px solid #edf0f4;

    border-radius: 9px;

    margin-bottom: 10px;

    transition: .2s;

}


.quick-action:last-child {

    margin-bottom: 0;

}


.quick-action:hover {

    border-color: #51b848;

    background: #f7fcf8;

}


.quick-action-icon {

    width: 38px;

    height: 38px;

    border-radius: 9px;

    background: #eef8f0;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 17px;

}


.quick-action-text strong {

    display: block;

    color: #333;

    font-size: 12px;

}


.quick-action-text span {

    display: block;

    color: #999;

    font-size: 10px;

    margin-top: 3px;

}


/* =====================================================
   DELIVERY INFO
===================================================== */

.info-box {

    margin: 20px;

    padding: 15px;

    background: #f7f9fb;

    border-radius: 10px;

}


.info-row {

    display: flex;

    justify-content: space-between;

    gap: 10px;

    padding: 8px 0;

    border-bottom:
        1px solid #e9edf1;

}


.info-row:last-child {

    border-bottom: none;

}


.info-row span {

    color: #999;

    font-size: 10px;

}


.info-row strong {

    color: #444;

    font-size: 11px;

    text-align: right;

}


/* =====================================================
   MOBILE SIDEBAR BUTTON
===================================================== */

.mobile-header {

    display: none;

}


/* =====================================================
   RESPONSIVE
===================================================== */

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

    .stats-grid {

        grid-template-columns: 1fr;

    }

    .page-header {

        display: block;

    }

    .welcome-card {

        padding: 20px;

    }

    .delivery-info {

        display: none;

    }

    .topbar-title h1 {

        font-size: 18px;

    }

    .content {

        padding: 15px;

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


        <a
            href="index.php"
            class="active"
        >

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
            Delivery Dashboard
        </h1>

        <p>
            Medicine Aapki Gaw Mein Delivery Panel
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
            Welcome, <?= e($deliveryBoyName) ?> 👋
        </h2>

        <p>
            Here's an overview of your delivery activities.
        </p>

    </div>

</div>


<!-- =====================================================
     WELCOME CARD
===================================================== -->

<div class="welcome-card">


    <div>

        <h3>
            Ready for today's deliveries?
        </h3>

        <p>
            Check your assigned orders and keep
            delivery status updated.
        </p>

    </div>


    <div class="welcome-icon">
        🚚
    </div>


</div>


<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="stats-grid">


    <!-- TOTAL -->

    <div class="stat-card">

        <div class="stat-icon">
            📦
        </div>

        <div class="stat-info">

            <span>
                Total Assigned
            </span>

            <strong>
                <?= $totalOrders ?>
            </strong>

        </div>

    </div>


    <!-- PENDING -->

    <div class="stat-card">

        <div class="stat-icon">
            ⏳
        </div>

        <div class="stat-info">

            <span>
                Pending
            </span>

            <strong>
                <?= $pendingOrders ?>
            </strong>

        </div>

    </div>


    <!-- OUT FOR DELIVERY -->

    <div class="stat-card">

        <div class="stat-icon">
            🚚
        </div>

        <div class="stat-info">

            <span>
                Out for Delivery
            </span>

            <strong>
                <?= $outForDelivery ?>
            </strong>

        </div>

    </div>


    <!-- DELIVERED -->

    <div class="stat-card">

        <div class="stat-icon">
            ✅
        </div>

        <div class="stat-info">

            <span>
                Delivered
            </span>

            <strong>
                <?= $deliveredOrders ?>
            </strong>

        </div>

    </div>


</div>


<!-- =====================================================
     DASHBOARD GRID
===================================================== -->

<div class="dashboard-grid">


<!-- =====================================================
     RECENT ORDERS
===================================================== -->

<div class="card">


    <div class="card-header">

        <h3>
            Recent Assigned Orders
        </h3>


        <a
            href="orders.php"
            class="view-all"
        >
            View All →
        </a>

    </div>


    <div class="card-body">


    <?php if (!empty($recentOrders)): ?>


        <div class="table-wrapper">


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
                            Amount
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


                    <?php

                    $status =
                        strtolower(
                            trim(
                                $order['delivery_status'] ?? 'pending'
                            )
                        );

                    $statusClass =
                        'status-' .
                        str_replace(
                            ' ',
                            '_',
                            $status
                        );

                    ?>


                    <tr>


                        <td>

                            <span class="order-number">

                                #<?= e(
                                    $order['order_number']
                                    ?? $order['id']
                                ) ?>

                            </span>

                        </td>


                        <td>

                            <span class="customer-name">

                                <?= e(
                                    $order['customer_name']
                                    ?? 'Customer'
                                ) ?>

                            </span>

                        </td>


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


                        <td>

                            <span
                                class="status
                                <?= e($statusClass) ?>"
                            >

                                <?= e(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $status ?: 'pending'
                                    )
                                ) ?>

                            </span>

                        </td>


                        <td>

                            <a
                                href="order-details.php?delivery_id=<?= (int)$order['delivery_id'] ?>"
                                class="details-btn"
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


        <div class="empty-state">


            <div class="empty-icon">
                📦
            </div>


            <h4>
                No Orders Assigned
            </h4>


            <p>
                You don't have any assigned orders yet.
            </p>


        </div>


    <?php endif; ?>


    </div>


</div>


<!-- =====================================================
     RIGHT SIDE
===================================================== -->

<div>


<!-- =====================================================
     QUICK ACTIONS
===================================================== -->

<div class="card">


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

            <div class="quick-action-icon">
                📦
            </div>

            <div class="quick-action-text">

                <strong>
                    My Orders
                </strong>

                <span>
                    View assigned orders
                </span>

            </div>

        </a>


        <a
            href="profile.php"
            class="quick-action"
        >

            <div class="quick-action-icon">
                👤
            </div>

            <div class="quick-action-text">

                <strong>
                    My Profile
                </strong>

                <span>
                    Manage your profile
                </span>

            </div>

        </a>


        <a
            href="logout.php"
            class="quick-action"
        >

            <div class="quick-action-icon">
                🚪
            </div>

            <div class="quick-action-text">

                <strong>
                    Logout
                </strong>

                <span>
                    Sign out securely
                </span>

            </div>

        </a>


    </div>

</div>


<!-- =====================================================
     DELIVERY PROFILE
===================================================== -->

<div
    class="card"
    style="margin-top: 20px;"
>


    <div class="card-header">

        <h3>
            My Information
        </h3>

    </div>


    <div class="info-box">


        <div class="info-row">

            <span>
                Name
            </span>

            <strong>
                <?= e($deliveryBoyName) ?>
            </strong>

        </div>


        <div class="info-row">

            <span>
                Mobile
            </span>

            <strong>
                <?= e(
                    $deliveryBoyMobile ?: 'Not available'
                ) ?>
            </strong>

        </div>


        <div class="info-row">

            <span>
                Role
            </span>

            <strong>
                Delivery Boy
            </strong>

        </div>


    </div>


</div>


</div>


</div>


</div>


</div>


</main>


</div>


</body>

</html>