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

$customerId     = (int) $_SESSION['user_id'];
$customerName   = $_SESSION['name'] ?? 'Customer';
$customerMobile = $_SESSION['mobile'] ?? '';

$pageTitle = "My Orders";

$firstLetter = strtoupper(
    substr(
        trim($customerName),
        0,
        1
    )
);


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
// FILTERS
// =====================================================

$search = trim($_GET['search'] ?? '');

$status = strtolower(
    trim($_GET['status'] ?? '')
);


// Allowed order statuses

$allowedStatuses = [
    'pending',
    'confirmed',
    'processing',
    'ready',
    'out_for_delivery',
    'delivered',
    'cancelled'
];


// Invalid status remove

if (
    $status !== '' &&
    !in_array($status, $allowedStatuses, true)
) {
    $status = '';
}


// =====================================================
// PAGINATION
// =====================================================

$perPage = 10;

$currentPage = isset($_GET['page'])
    ? max(1, (int) $_GET['page'])
    : 1;

$offset = ($currentPage - 1) * $perPage;


// =====================================================
// BUILD WHERE CONDITION
// =====================================================

$where = [
    "o.user_id = ?"
];

$params = [
    $customerId
];

$types = "i";


// =====================================================
// SEARCH FILTER
// =====================================================

if ($search !== '') {

    $where[] = "
        (
            o.order_number LIKE ?
            OR CAST(o.id AS CHAR) LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "ss";
}


// =====================================================
// STATUS FILTER
// =====================================================

if ($status !== '') {

    $where[] = "
        o.order_status = ?
    ";

    $params[] = $status;

    $types .= "s";
}


$whereSQL = implode(
    " AND ",
    $where
);


// =====================================================
// TOTAL ORDERS
// =====================================================

$totalOrders = 0;

$countSQL = "
    SELECT COUNT(*) AS total
    FROM orders o
    WHERE $whereSQL
";

$countStmt = $conn->prepare($countSQL);

if ($countStmt) {

    $countStmt->bind_param(
        $types,
        ...$params
    );

    $countStmt->execute();

    $countResult = $countStmt->get_result();

    if ($countRow = $countResult->fetch_assoc()) {

        $totalOrders = (int) $countRow['total'];
    }

    $countStmt->close();
}


// =====================================================
// TOTAL PAGES
// =====================================================

$totalPages = max(
    1,
    (int) ceil(
        $totalOrders / $perPage
    )
);


// Prevent invalid page

if ($currentPage > $totalPages) {

    $currentPage = $totalPages;

    $offset = ($currentPage - 1) * $perPage;
}


// =====================================================
// FETCH ORDERS
// =====================================================

$orders = [];

$orderSQL = "
    SELECT
        o.id,
        o.order_number,
        o.subtotal,
        o.delivery_charge,
        o.discount,
        o.total_amount,
        o.payment_method,
        o.payment_status,
        o.order_status,
        o.created_at
    FROM orders o
    WHERE $whereSQL
    ORDER BY o.id DESC
    LIMIT ?, ?
";


$orderStmt = $conn->prepare($orderSQL);

if ($orderStmt) {

    $orderParams = $params;

    $orderParams[] = $offset;
    $orderParams[] = $perPage;

    $orderTypes = $types . "ii";

    $orderStmt->bind_param(
        $orderTypes,
        ...$orderParams
    );

    $orderStmt->execute();

    $orderResult = $orderStmt->get_result();

    while ($row = $orderResult->fetch_assoc()) {

        $orders[] = $row;
    }

    $orderStmt->close();
}


// =====================================================
// ORDER STATISTICS
// =====================================================

$stats = [

    'total' => 0,

    'pending' => 0,

    'processing' => 0,

    'delivered' => 0

];


$statsSQL = "
    SELECT
        COUNT(*) AS total,

        SUM(
            CASE
                WHEN order_status = 'pending'
                THEN 1
                ELSE 0
            END
        ) AS pending,

        SUM(
            CASE
                WHEN order_status IN (
                    'confirmed',
                    'processing',
                    'ready',
                    'out_for_delivery'
                )
                THEN 1
                ELSE 0
            END
        ) AS processing,

        SUM(
            CASE
                WHEN order_status = 'delivered'
                THEN 1
                ELSE 0
            END
        ) AS delivered

    FROM orders

    WHERE user_id = ?
";


$statsStmt = $conn->prepare($statsSQL);

if ($statsStmt) {

    $statsStmt->bind_param(
        "i",
        $customerId
    );

    $statsStmt->execute();

    $statsResult = $statsStmt->get_result();

    if ($statsRow = $statsResult->fetch_assoc()) {

        $stats['total'] =
            (int) ($statsRow['total'] ?? 0);

        $stats['pending'] =
            (int) ($statsRow['pending'] ?? 0);

        $stats['processing'] =
            (int) ($statsRow['processing'] ?? 0);

        $stats['delivered'] =
            (int) ($statsRow['delivered'] ?? 0);
    }

    $statsStmt->close();
}


// =====================================================
// STATUS CLASS
// =====================================================

function getStatusClass($status)
{
    switch (strtolower((string) $status)) {

        case 'confirmed':
            return 'status-confirmed';

        case 'processing':
            return 'status-processing';

        case 'ready':
            return 'status-ready';

        case 'out_for_delivery':
            return 'status-out';

        case 'delivered':
            return 'status-delivered';

        case 'cancelled':
            return 'status-cancelled';

        case 'pending':
        default:
            return 'status-pending';
    }
}


// =====================================================
// STATUS TEXT
// =====================================================

function getStatusText($status)
{
    switch (strtolower((string) $status)) {

        case 'out_for_delivery':
            return 'Out for Delivery';

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

        case 'pending':
            return 'Pending';

        default:
            return ucfirst(
                str_replace(
                    '_',
                    ' ',
                    (string) $status
                )
            );
    }
}


// =====================================================
// PAYMENT TEXT
// =====================================================

function getPaymentClass($status)
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


function getPaymentText($status)
{
    switch (strtolower((string) $status)) {

        case 'paid':
            return 'Paid';

        case 'failed':
            return 'Failed';

        case 'refunded':
            return 'Refunded';

        default:
            return 'Pending';
    }
}


// =====================================================
// PAGE URL
// =====================================================

function pageUrl($page)
{
    $query = $_GET;

    $query['page'] = $page;

    return '?' . http_build_query($query);
}


// =====================================================
// START / END RECORD
// =====================================================

$startRecord = $totalOrders > 0
    ? $offset + 1
    : 0;

$endRecord = min(
    $offset + count($orders),
    $totalOrders
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
        My Orders | Medicine Aapki Gaw Mein
    </title>


    <!-- =================================================
         GOOGLE FONT
    ================================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <style>

        /* =================================================
           RESET
        ================================================= */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {

            font-family: 'Rubik', sans-serif;

            background: #f5f7fb;

            color: #222;

            min-height: 100vh;
        }


        a {
            text-decoration: none;
        }


        /* =================================================
           SIDEBAR
        ================================================= */

        .sidebar {

            position: fixed;

            left: 0;
            top: 0;

            width: 250px;
            height: 100vh;

            background:
                linear-gradient(
                    180deg,
                    #1f8b38 0%,
                    #166b2d 100%
                );

            color: #fff;

            z-index: 1000;

            overflow-y: auto;

            box-shadow:
                4px 0 20px
                rgba(0, 0, 0, 0.08);
        }


        /* =================================================
           BRAND
        ================================================= */

        .brand {

            padding: 25px 20px;

            display: flex;

            align-items: center;

            gap: 12px;

            border-bottom:
                1px solid
                rgba(255,255,255,0.12);
        }


        .brand-icon {

            width: 45px;
            height: 45px;

            border-radius: 12px;

            background:
                rgba(255,255,255,0.15);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 24px;
        }


        .brand-text h2 {

            font-size: 16px;

            font-weight: 600;

            line-height: 1.2;
        }


        .brand-text span {

            display: block;

            margin-top: 4px;

            font-size: 11px;

            opacity: 0.75;

            text-transform: uppercase;

            letter-spacing: 0.5px;
        }


        /* =================================================
           MENU
        ================================================= */

        .menu {

            padding: 20px 12px;
        }


        .menu-title {

            padding:
                12px 12px 7px;

            font-size: 10px;

            font-weight: 600;

            text-transform: uppercase;

            letter-spacing: 1px;

            color:
                rgba(255,255,255,0.55);
        }


        .menu a {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 12px 13px;

            margin-bottom: 4px;

            border-radius: 9px;

            color:
                rgba(255,255,255,0.85);

            font-size: 13px;

            transition: 0.2s;
        }


        .menu a:hover {

            background:
                rgba(255,255,255,0.10);

            color: #fff;

            transform:
                translateX(2px);
        }


        .menu a.active {

            background:
                rgba(255,255,255,0.17);

            color: #fff;

            font-weight: 500;

            box-shadow:
                inset 3px 0 0 #fff;
        }


        .menu-icon {

            width: 22px;

            text-align: center;

            font-size: 17px;
        }


        /* =================================================
           MAIN CONTENT
        ================================================= */

        .main-content {

            margin-left: 250px;

            width:
                calc(100% - 250px);

            min-height: 100vh;
        }


        /* =================================================
           TOPBAR
        ================================================= */

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

            font-size: 20px;

            font-weight: 600;

            color: #222;
        }


        .topbar-title p {

            margin-top: 3px;

            font-size: 12px;

            color: #8a94a6;
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

            background:
                linear-gradient(
                    135deg,
                    #238b39,
                    #51b848
                );

            color: #fff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-weight: 600;

            font-size: 15px;
        }


        .profile-info strong {

            display: block;

            font-size: 13px;

            font-weight: 600;

            color: #222;
        }


        .profile-info span {

            display: block;

            font-size: 11px;

            color: #8a94a6;

            margin-top: 2px;
        }


        /* =================================================
           CONTENT
        ================================================= */

        .content {

            padding: 30px;
        }


        /* =================================================
           PAGE HEADER
        ================================================= */

        .page-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 22px;
        }


        .page-header h2 {

            font-size: 22px;

            font-weight: 600;

            color: #222;
        }


        .page-header p {

            margin-top: 5px;

            font-size: 13px;

            color: #8a94a6;
        }


        .browse-btn {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding: 10px 15px;

            border-radius: 8px;

            background:
                linear-gradient(
                    135deg,
                    #238b39,
                    #51b848
                );

            color: #fff;

            font-size: 12px;

            font-weight: 500;

            transition: 0.2s;
        }


        .browse-btn:hover {

            color: #fff;

            transform:
                translateY(-1px);

            box-shadow:
                0 6px 15px
                rgba(35,139,57,0.20);
        }


        /* =================================================
           STATS
        ================================================= */

        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

            margin-bottom: 22px;
        }


        .stat-card {

            background: #fff;

            border:
                1px solid #e9edf3;

            border-radius: 13px;

            padding: 18px 20px;

            display: flex;

            align-items: center;

            justify-content: space-between;
        }


        .stat-label {

            display: block;

            color: #8a94a6;

            font-size: 11px;

            margin-bottom: 6px;
        }


        .stat-number {

            display: block;

            color: #222;

            font-size: 22px;

            font-weight: 600;
        }


        .stat-icon {

            width: 43px;
            height: 43px;

            border-radius: 10px;

            background: #eaf7ed;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 19px;
        }


        /* =================================================
           CARD
        ================================================= */

        .card {

            background: #fff;

            border:
                1px solid #e9edf3;

            border-radius: 13px;

            overflow: hidden;
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

            font-size: 15px;

            font-weight: 600;

            color: #222;
        }


        .order-count {

            font-size: 11px;

            color: #8a94a6;
        }


        /* =================================================
           FILTER
        ================================================= */

        .filter-area {

            padding: 16px 20px;

            border-bottom:
                1px solid #edf0f4;

            background: #fafbfc;
        }


        .filter-form {

            display: flex;

            align-items: center;

            gap: 10px;

            flex-wrap: wrap;
        }


        .search-box {

            position: relative;

            flex: 1;

            min-width: 220px;
        }


        .search-icon {

            position: absolute;

            left: 12px;

            top: 50%;

            transform:
                translateY(-50%);

            color: #8a94a6;

            font-size: 13px;
        }


        .search-box input {

            width: 100%;

            height: 38px;

            padding:
                0 12px 0 36px;

            border:
                1px solid #dfe4eb;

            border-radius: 7px;

            outline: none;

            background: #fff;

            font-family: inherit;

            font-size: 11px;
        }


        .search-box input:focus {

            border-color: #69bd76;

            box-shadow:
                0 0 0 3px
                rgba(35,139,57,0.07);
        }


        .filter-select {

            height: 38px;

            min-width: 160px;

            padding: 0 12px;

            border:
                1px solid #dfe4eb;

            border-radius: 7px;

            outline: none;

            background: #fff;

            font-family: inherit;

            font-size: 11px;

            color: #444;
        }


        .filter-btn {

            height: 38px;

            padding:
                0 16px;

            border: none;

            border-radius: 7px;

            background: #238b39;

            color: #fff;

            cursor: pointer;

            font-family: inherit;

            font-size: 11px;

            font-weight: 500;
        }


        .filter-btn:hover {

            background: #166b2d;
        }


        .clear-btn {

            height: 38px;

            padding:
                0 14px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border:
                1px solid #dfe4eb;

            border-radius: 7px;

            background: #fff;

            color: #666;

            font-size: 11px;
        }


        /* =================================================
           TABLE
        ================================================= */

        .table-wrapper {

            width: 100%;

            overflow-x: auto;
        }


        .orders-table {

            width: 100%;

            border-collapse: collapse;
        }


        .orders-table th {

            padding:
                12px 18px;

            background: #fafbfc;

            color: #8a94a6;

            font-size: 10px;

            font-weight: 500;

            text-transform: uppercase;

            letter-spacing: 0.4px;

            text-align: left;

            white-space: nowrap;
        }


        .orders-table td {

            padding:
                14px 18px;

            border-top:
                1px solid #f0f2f5;

            font-size: 12px;

            color: #444;

            white-space: nowrap;
        }


        .orders-table tbody tr:hover td {

            background: #fcfdfd;
        }


        .order-number {

            color: #238b39;

            font-weight: 600;
        }


        .order-id {

            color: #9aa3b1;

            font-size: 10px;

            margin-top: 3px;
        }


        .order-date {

            color: #444;

            font-size: 12px;
        }


        .order-time {

            color: #9aa3b1;

            font-size: 10px;

            margin-top: 3px;
        }


        .amount {

            color: #222;

            font-weight: 600;
        }


        /* =================================================
           STATUS
        ================================================= */

        .status {

            display: inline-flex;

            align-items: center;

            padding:
                5px 9px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 500;
        }


        .status-pending {

            background: #fff6df;

            color: #b77b00;
        }


        .status-confirmed {

            background: #eef5ff;

            color: #377dce;
        }


        .status-processing {

            background: #eaf2ff;

            color: #377dce;
        }


        .status-ready {

            background: #f0edff;

            color: #6952c7;
        }


        .status-out {

            background: #fff0df;

            color: #c46b00;
        }


        .status-delivered {

            background: #e9f8ef;

            color: #238b49;
        }


        .status-cancelled {

            background: #ffe9e9;

            color: #d34a4a;
        }


        /* =================================================
           PAYMENT
        ================================================= */

        .payment {

            font-size: 11px;

            font-weight: 500;
        }


        .payment-paid {

            color: #238b49;
        }


        .payment-pending {

            color: #b77b00;
        }


        .payment-failed {

            color: #d34a4a;
        }


        .payment-refunded {

            color: #6952c7;
        }


        .payment-method {

            margin-top: 3px;

            color: #9aa3b1;

            font-size: 10px;

            text-transform: uppercase;
        }


        /* =================================================
           ACTIONS
        ================================================= */

        .action-buttons {

            display: flex;

            align-items: center;

            gap: 6px;
        }


        .action-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                6px 10px;

            border-radius: 6px;

            background: #edf8ef;

            color: #238b39;

            font-size: 10px;

            font-weight: 500;

            transition: 0.2s;
        }


        .action-btn:hover {

            background: #238b39;

            color: #fff;
        }


        .track-btn {

            background: #eef5ff;

            color: #377dce;
        }


        .track-btn:hover {

            background: #377dce;

            color: #fff;
        }


        /* =================================================
           EMPTY STATE
        ================================================= */

        .empty-state {

            text-align: center;

            padding: 65px 20px;
        }


        .empty-icon {

            width: 70px;

            height: 70px;

            margin:
                0 auto 15px;

            border-radius: 50%;

            background: #eaf7ed;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 32px;
        }


        .empty-state h4 {

            font-size: 16px;

            font-weight: 600;

            color: #444;

            margin-bottom: 7px;
        }


        .empty-state p {

            color: #8a94a6;

            font-size: 12px;

            margin-bottom: 18px;
        }


        .empty-btn {

            display: inline-flex;

            padding:
                10px 16px;

            border-radius: 7px;

            background: #238b39;

            color: #fff;

            font-size: 11px;

            font-weight: 500;
        }


        .empty-btn:hover {

            color: #fff;

            background: #166b2d;
        }


        /* =================================================
           PAGINATION
        ================================================= */

        .pagination-area {

            padding:
                15px 20px;

            border-top:
                1px solid #edf0f4;

            display: flex;

            align-items: center;

            justify-content: space-between;

            flex-wrap: wrap;

            gap: 15px;
        }


        .pagination-info {

            font-size: 11px;

            color: #8a94a6;
        }


        .pagination {

            display: flex;

            align-items: center;

            gap: 5px;
        }


        .pagination a,
        .pagination span {

            min-width: 30px;

            height: 30px;

            padding:
                0 8px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border:
                1px solid #e1e5eb;

            border-radius: 6px;

            background: #fff;

            color: #666;

            font-size: 10px;
        }


        .pagination a:hover {

            color: #238b39;

            border-color: #8bc996;
        }


        .pagination .active {

            color: #fff;

            background: #238b39;

            border-color: #238b39;
        }


        .pagination .disabled {

            opacity: 0.45;

            pointer-events: none;
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 1100px) {

            .stats-grid {

                grid-template-columns:
                    repeat(2, 1fr);
            }
        }


        @media (max-width: 850px) {

            .sidebar {

                width: 220px;
            }


            .main-content {

                margin-left: 220px;

                width:
                    calc(100% - 220px);
            }


            .content {

                padding: 20px;
            }


            .topbar {

                padding:
                    0 20px;
            }
        }


        @media (max-width: 650px) {

            .sidebar {

                position: relative;

                width: 100%;

                height: auto;
            }


            .main-content {

                margin-left: 0;

                width: 100%;
            }


            .brand {

                padding: 18px;
            }


            .menu {

                padding:
                    10px 12px 15px;
            }


            .topbar {

                height: auto;

                padding: 15px;

                gap: 12px;
            }


            .profile-info {

                display: none;
            }


            .content {

                padding: 15px;
            }


            .page-header {

                display: block;
            }


            .browse-btn {

                margin-top: 12px;
            }


            .stats-grid {

                grid-template-columns: 1fr;

                gap: 12px;
            }


            .filter-form {

                flex-direction: column;

                align-items: stretch;
            }


            .search-box {

                width: 100%;

                min-width: 0;
            }


            .filter-select,
            .filter-btn,
            .clear-btn {

                width: 100%;
            }


            .pagination-area {

                justify-content: center;
            }
        }

    </style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
====================================================== -->

<aside class="sidebar">


    <!-- BRAND -->

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


    <!-- MENU -->

    <nav class="menu">


        <div class="menu-title">
            Main
        </div>


        <a href="index.php">

            <span class="menu-icon">
                🏠
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


        <a href="../medicines.php">

            <span class="menu-icon">
                💊
            </span>

            Browse Medicines

        </a>


        <a href="../cart.php">

            <span class="menu-icon">
                🛒
            </span>

            My Cart

        </a>


        <div class="menu-title">
            Prescription
        </div>


        <a href="prescriptions.php">

            <span class="menu-icon">
                📋
            </span>

            My Prescriptions

        </a>


        <a href="upload-prescription.php">

            <span class="menu-icon">
                📤
            </span>

            Upload Prescription

        </a>


        <div class="menu-title">
            Account
        </div>


        <a href="profile.php">

            <span class="menu-icon">
                👤
            </span>

            My Profile

        </a>


        <a href="addresses.php">

            <span class="menu-icon">
                📍
            </span>

            My Addresses

        </a>


        <a href="change-password.php">

            <span class="menu-icon">
                🔐
            </span>

            Change Password

        </a>


        <div class="menu-title">
            More
        </div>


        <a href="../index.php">

            <span class="menu-icon">
                🌐
            </span>

            View Website

        </a>


        <a href="../logout.php">

            <span class="menu-icon">
                🚪
            </span>

            Logout

        </a>

    </nav>

</aside>



<!-- =====================================================
     MAIN
====================================================== -->

<main class="main-content">


    <!-- =================================================
         TOPBAR
    ================================================== -->

    <header class="topbar">


        <div class="topbar-title">

            <h1>
                My Orders
            </h1>

            <p>
                View and track all your medicine orders
            </p>

        </div>


        <div class="profile-mini">

            <div class="avatar">
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
    ================================================== -->

    <section class="content">


        <!-- PAGE HEADER -->

        <div class="page-header">


            <div>

                <h2>
                    My Orders
                </h2>

                <p>
                    Check your order history, payment and delivery status.
                </p>

            </div>


            <a
                href="../medicines.php"
                class="browse-btn"
            >
                💊 Browse Medicines
            </a>


        </div>



        <!-- =================================================
             STATISTICS
        ================================================== -->

        <div class="stats-grid">


            <!-- TOTAL -->

            <div class="stat-card">

                <div>

                    <span class="stat-label">
                        Total Orders
                    </span>

                    <strong class="stat-number">
                        <?= $stats['total'] ?>
                    </strong>

                </div>


                <div class="stat-icon">
                    📦
                </div>

            </div>



            <!-- PENDING -->

            <div class="stat-card">

                <div>

                    <span class="stat-label">
                        Pending Orders
                    </span>

                    <strong class="stat-number">
                        <?= $stats['pending'] ?>
                    </strong>

                </div>


                <div class="stat-icon">
                    ⏳
                </div>

            </div>



            <!-- PROCESSING -->

            <div class="stat-card">

                <div>

                    <span class="stat-label">
                        Active Orders
                    </span>

                    <strong class="stat-number">
                        <?= $stats['processing'] ?>
                    </strong>

                </div>


                <div class="stat-icon">
                    🚚
                </div>

            </div>



            <!-- DELIVERED -->

            <div class="stat-card">

                <div>

                    <span class="stat-label">
                        Delivered Orders
                    </span>

                    <strong class="stat-number">
                        <?= $stats['delivered'] ?>
                    </strong>

                </div>


                <div class="stat-icon">
                    ✅
                </div>

            </div>


        </div>



        <!-- =================================================
             ORDERS CARD
        ================================================== -->

        <div class="card">


            <div class="card-header">


                <h3>
                    Order History
                </h3>


                <span class="order-count">

                    <?= $totalOrders ?>

                    <?= $totalOrders === 1
                        ? 'Order'
                        : 'Orders'
                    ?>

                </span>


            </div>



            <!-- =================================================
                 FILTER
            ================================================== -->

            <div class="filter-area">


                <form
                    method="GET"
                    class="filter-form"
                >


                    <div class="search-box">

                        <span class="search-icon">
                            🔎
                        </span>


                        <input
                            type="text"
                            name="search"
                            value="<?= e($search) ?>"
                            placeholder="Search by order number..."
                        >

                    </div>


                    <select
                        name="status"
                        class="filter-select"
                    >

                        <option value="">
                            All Orders
                        </option>


                        <option
                            value="pending"
                            <?= $status === 'pending'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Pending
                        </option>


                        <option
                            value="confirmed"
                            <?= $status === 'confirmed'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Confirmed
                        </option>


                        <option
                            value="processing"
                            <?= $status === 'processing'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Processing
                        </option>


                        <option
                            value="ready"
                            <?= $status === 'ready'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Ready
                        </option>


                        <option
                            value="out_for_delivery"
                            <?= $status === 'out_for_delivery'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Out for Delivery
                        </option>


                        <option
                            value="delivered"
                            <?= $status === 'delivered'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Delivered
                        </option>


                        <option
                            value="cancelled"
                            <?= $status === 'cancelled'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Cancelled
                        </option>

                    </select>


                    <button
                        type="submit"
                        class="filter-btn"
                    >
                        Filter
                    </button>


                    <?php if (
                        $search !== '' ||
                        $status !== ''
                    ): ?>

                        <a
                            href="orders.php"
                            class="clear-btn"
                        >
                            Clear
                        </a>

                    <?php endif; ?>


                </form>

            </div>



            <!-- =================================================
                 ORDERS
            ================================================== -->

            <?php if (!empty($orders)): ?>


                <div class="table-wrapper">


                    <table class="orders-table">


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


                            <?php foreach ($orders as $order): ?>


                                <?php

                                $orderStatus =
                                    strtolower(
                                        (string)
                                        $order['order_status']
                                    );

                                ?>


                                <tr>


                                    <!-- ORDER -->

                                    <td>

                                        <div class="order-number">

                                            #<?= e(
                                                $order['order_number']
                                            ) ?>

                                        </div>


                                        <div class="order-id">

                                            Order ID:
                                            <?= (int) $order['id'] ?>

                                        </div>

                                    </td>



                                    <!-- DATE -->

                                    <td>

                                        <div class="order-date">

                                            <?= date(
                                                'd M Y',
                                                strtotime(
                                                    $order['created_at']
                                                )
                                            ) ?>

                                        </div>


                                        <div class="order-time">

                                            <?= date(
                                                'h:i A',
                                                strtotime(
                                                    $order['created_at']
                                                )
                                            ) ?>

                                        </div>

                                    </td>



                                    <!-- AMOUNT -->

                                    <td>

                                        <span class="amount">

                                            ₹<?= number_format(
                                                (float)
                                                $order['total_amount'],
                                                2
                                            ) ?>

                                        </span>

                                    </td>



                                    <!-- PAYMENT -->

                                    <td>


                                        <div
                                            class="
                                                payment
                                                <?= e(
                                                    getPaymentClass(
                                                        $order['payment_status']
                                                    )
                                                ) ?>
                                            "
                                        >

                                            <?= e(
                                                getPaymentText(
                                                    $order['payment_status']
                                                )
                                            ) ?>

                                        </div>


                                        <div class="payment-method">

                                            <?= e(
                                                strtoupper(
                                                    (string)
                                                    $order['payment_method']
                                                )
                                            ) ?>

                                        </div>


                                    </td>



                                    <!-- STATUS -->

                                    <td>


                                        <span
                                            class="
                                                status
                                                <?= e(
                                                    getStatusClass(
                                                        $order['order_status']
                                                    )
                                                ) ?>
                                            "
                                        >

                                            <?= e(
                                                getStatusText(
                                                    $order['order_status']
                                                )
                                            ) ?>

                                        </span>


                                    </td>



                                    <!-- ACTION -->

                                    <td>


                                        <div class="action-buttons">


                                            <a
                                                href="
                                                    order-details.php?id=<?= (int) $order['id'] ?>
                                                "
                                                class="action-btn"
                                            >
                                                👁 View
                                            </a>


                                            <?php if (
                                                !in_array(
                                                    $orderStatus,
                                                    [
                                                        'delivered',
                                                        'cancelled'
                                                    ],
                                                    true
                                                )
                                            ): ?>


                                                <a
                                                    href="
                                                        track-order.php?id=<?= (int) $order['id'] ?>
                                                    "
                                                    class="action-btn track-btn"
                                                >
                                                    🚚 Track
                                                </a>


                                            <?php elseif (
                                                $orderStatus === 'delivered'
                                            ): ?>


                                                <a
                                                    href="
                                                        track-order.php?id=<?= (int) $order['id'] ?>
                                                    "
                                                    class="action-btn track-btn"
                                                >
                                                    📍 Track
                                                </a>


                                            <?php endif; ?>


                                        </div>


                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>

                    </table>


                </div>



                <!-- =================================================
                     PAGINATION
                ================================================== -->

                <?php if ($totalPages > 1): ?>


                    <div class="pagination-area">


                        <div class="pagination-info">

                            Showing
                            <strong>
                                <?= $startRecord ?>
                            </strong>

                            -
                            <strong>
                                <?= $endRecord ?>
                            </strong>

                            of
                            <strong>
                                <?= $totalOrders ?>
                            </strong>

                            orders

                        </div>


                        <div class="pagination">


                            <!-- PREVIOUS -->

                            <?php if ($currentPage > 1): ?>

                                <a
                                    href="<?= e(
                                        pageUrl(
                                            $currentPage - 1
                                        )
                                    ) ?>"
                                >
                                    ‹
                                </a>

                            <?php else: ?>

                                <span class="disabled">
                                    ‹
                                </span>

                            <?php endif; ?>



                            <!-- PAGE NUMBERS -->

                            <?php

                            $startPage = max(
                                1,
                                $currentPage - 2
                            );

                            $endPage = min(
                                $totalPages,
                                $currentPage + 2
                            );

                            ?>


                            <?php if ($startPage > 1): ?>

                                <a href="<?= e(
                                    pageUrl(1)
                                ) ?>">
                                    1
                                </a>


                                <?php if ($startPage > 2): ?>

                                    <span>
                                        ...
                                    </span>

                                <?php endif; ?>

                            <?php endif; ?>



                            <?php for (
                                $i = $startPage;
                                $i <= $endPage;
                                $i++
                            ): ?>


                                <?php if (
                                    $i === $currentPage
                                ): ?>

                                    <span class="active">
                                        <?= $i ?>
                                    </span>

                                <?php else: ?>

                                    <a
                                        href="<?= e(
                                            pageUrl($i)
                                        ) ?>"
                                    >
                                        <?= $i ?>
                                    </a>

                                <?php endif; ?>


                            <?php endfor; ?>



                            <?php if (
                                $endPage < $totalPages
                            ): ?>


                                <?php if (
                                    $endPage < $totalPages - 1
                                ): ?>

                                    <span>
                                        ...
                                    </span>

                                <?php endif; ?>


                                <a
                                    href="<?= e(
                                        pageUrl(
                                            $totalPages
                                        )
                                    ) ?>"
                                >
                                    <?= $totalPages ?>
                                </a>


                            <?php endif; ?>



                            <!-- NEXT -->

                            <?php if (
                                $currentPage < $totalPages
                            ): ?>

                                <a
                                    href="<?= e(
                                        pageUrl(
                                            $currentPage + 1
                                        )
                                    ) ?>"
                                >
                                    ›
                                </a>

                            <?php else: ?>

                                <span class="disabled">
                                    ›
                                </span>

                            <?php endif; ?>


                        </div>


                    </div>


                <?php endif; ?>


            <?php else: ?>


                <!-- =================================================
                     EMPTY STATE
                ================================================== -->

                <div class="empty-state">


                    <div class="empty-icon">
                        📦
                    </div>


                    <?php if (
                        $search !== '' ||
                        $status !== ''
                    ): ?>

                        <h4>
                            No Orders Found
                        </h4>

                        <p>
                            No orders match your current search or filter.
                        </p>


                        <a
                            href="orders.php"
                            class="empty-btn"
                        >
                            View All Orders
                        </a>


                    <?php else: ?>

                        <h4>
                            No Orders Yet
                        </h4>

                        <p>
                            You haven't placed any medicine orders yet.
                        </p>


                        <a
                            href="../medicines.php"
                            class="empty-btn"
                        >
                            Browse Medicines
                        </a>

                    <?php endif; ?>


                </div>


            <?php endif; ?>


        </div>


    </section>


</main>


</body>

</html>