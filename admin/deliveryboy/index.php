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
// ADMIN DATA
// =====================================================

$adminName = $_SESSION['name'] ?? 'Administrator';


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
// SEARCH & FILTER
// =====================================================

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'all';


// =====================================================
// STATISTICS
// =====================================================

function getCount($conn, $sql)
{
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $row = mysqli_fetch_assoc($result);

        return (int)($row['total'] ?? 0);
    }

    return 0;
}


$totalDeliveryBoys = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'delivery'"
);


$activeDeliveryBoys = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'delivery'
     AND status = 1"
);


$inactiveDeliveryBoys = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'delivery'
     AND status = 0"
);


// =====================================================
// BUILD QUERY
// =====================================================

$where = [
    "role = 'delivery'"
];


if ($search !== '') {

    $safeSearch = mysqli_real_escape_string(
        $conn,
        $search
    );

    $where[] = "
        (
            name LIKE '%$safeSearch%'
            OR mobile LIKE '%$safeSearch%'
            OR email LIKE '%$safeSearch%'
            OR city LIKE '%$safeSearch%'
            OR state LIKE '%$safeSearch%'
        )
    ";
}


if ($status === 'active') {

    $where[] = "status = 1";

} elseif ($status === 'inactive') {

    $where[] = "status = 0";
}


$whereSql = implode(
    " AND ",
    $where
);


// =====================================================
// FETCH DELIVERY BOYS
// =====================================================

$deliveryBoys = [];


$sql = "
    SELECT
        id,
        name,
        mobile,
        email,
        address,
        city,
        state,
        pincode,
        status,
        created_at,
        updated_at
    FROM users
    WHERE $whereSql
    ORDER BY id DESC
";


$result = mysqli_query(
    $conn,
    $sql
);


if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $deliveryBoys[] = $row;

    }
}


// =====================================================
// PAGE TITLE
// =====================================================

$pageTitle = "Delivery Boys";

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


button,
input,
select {
    font-family: inherit;
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
   DELIVERY SUB MENU
===================================================== */

.sub-menu {
    margin: -1px 0 7px 0;
    padding-left: 25px;
}


.sub-menu a {
    font-size: 12px;
    padding: 9px 12px;
    margin-bottom: 2px;
    color: rgba(255,255,255,.72);
}


.sub-menu a.active {
    background: rgba(255,255,255,.12);
    color: #fff;
}


.sub-menu .menu-icon {
    font-size: 13px;
    width: 20px;
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
    position: sticky;
    top: 0;
    z-index: 100;
}


.topbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
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


.mobile-menu-btn {
    display: none;
    border: none;
    background: #eaf7ec;
    color: #268c3a;
    width: 38px;
    height: 38px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 18px;
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
    gap: 20px;
    margin-bottom: 25px;
}


.page-header h2 {
    font-size: 22px;
    color: #222;
}


.page-header p {
    font-size: 12px;
    color: #999;
    margin-top: 5px;
}


.add-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #238b39;
    color: #fff;
    padding: 11px 17px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    transition: .2s;
}


.add-btn:hover {
    background: #166b2d;
    transform: translateY(-1px);
}


/* =====================================================
   STATISTICS
===================================================== */

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 25px;
}


.stat-card {
    background: #fff;
    border: 1px solid #edf0f4;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}


.stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}


.icon-green {
    background: #e8f7eb;
}


.icon-blue {
    background: #e9f2ff;
}


.icon-red {
    background: #ffebed;
}


.stat-card h3 {
    font-size: 22px;
    color: #222;
    line-height: 1;
    margin-bottom: 6px;
}


.stat-card p {
    font-size: 11px;
    color: #999;
}


/* =====================================================
   PANEL
===================================================== */

.panel {
    background: #fff;
    border: 1px solid #edf0f4;
    border-radius: 12px;
    overflow: hidden;
}


.panel-header {
    padding: 18px 20px;
    border-bottom: 1px solid #edf0f4;
    display: flex;
    align-items: center;
    justify-content: space-between;
}


.panel-header h3 {
    font-size: 15px;
    color: #222;
}


/* =====================================================
   FILTERS
===================================================== */

.filters {
    padding: 18px 20px;
    background: #fafbfc;
    border-bottom: 1px solid #edf0f4;
}


.filter-form {
    display: flex;
    align-items: center;
    gap: 10px;
}


.search-box {
    flex: 1;
    position: relative;
}


.search-box input {
    width: 100%;
    height: 42px;
    border: 1px solid #e0e5eb;
    border-radius: 8px;
    padding: 0 14px 0 40px;
    outline: none;
    font-size: 12px;
    background: #fff;
}


.search-box input:focus {
    border-color: #51b848;
}


.search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    font-size: 14px;
}


.filter-select {
    height: 42px;
    border: 1px solid #e0e5eb;
    border-radius: 8px;
    padding: 0 35px 0 12px;
    background: #fff;
    color: #555;
    outline: none;
    font-size: 12px;
    cursor: pointer;
}


.filter-btn {
    height: 42px;
    border: none;
    background: #238b39;
    color: #fff;
    padding: 0 18px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}


.reset-btn {
    height: 42px;
    border: 1px solid #e0e5eb;
    background: #fff;
    color: #666;
    padding: 0 15px;
    border-radius: 8px;
    font-size: 12px;
}


/* =====================================================
   TABLE
===================================================== */

.table-wrapper {
    width: 100%;
    overflow-x: auto;
}


table {
    width: 100%;
    border-collapse: collapse;
    min-width: 950px;
}


th {
    background: #fafbfc;
    color: #888;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .4px;
    padding: 13px 15px;
    text-align: left;
    white-space: nowrap;
}


td {
    padding: 14px 15px;
    border-top: 1px solid #f0f2f5;
    font-size: 12px;
    color: #444;
    vertical-align: middle;
}


/* =====================================================
   DELIVERY BOY
===================================================== */

.delivery-person {
    display: flex;
    align-items: center;
    gap: 10px;
}


.delivery-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e8f7eb;
    color: #238b39;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    flex-shrink: 0;
}


.delivery-details strong {
    display: block;
    font-size: 12px;
    color: #333;
}


.delivery-details span {
    display: block;
    font-size: 10px;
    color: #999;
    margin-top: 3px;
}


.mobile-number {
    font-weight: 600;
    color: #444;
}


.location {
    max-width: 180px;
}


.location strong {
    display: block;
    font-size: 11px;
    color: #555;
}


.location span {
    display: block;
    font-size: 10px;
    color: #999;
    margin-top: 3px;
}


/* =====================================================
   BADGES
===================================================== */

.badge {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 9px;
    font-weight: 600;
}


.badge-active {
    color: #14752f;
    background: #dff5e3;
}


.badge-inactive {
    color: #a51d2d;
    background: #ffe3e6;
}


/* =====================================================
   ACTIONS
===================================================== */

.actions {
    display: flex;
    align-items: center;
    gap: 5px;
}


.action-btn {
    width: 32px;
    height: 32px;
    border-radius: 7px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e8ebef;
    background: #fff;
    font-size: 13px;
    transition: .2s;
}


.action-view {
    color: #2369a0;
}


.action-view:hover {
    background: #e9f2ff;
    border-color: #cfe1f7;
}


.action-edit {
    color: #ad7600;
}


.action-edit:hover {
    background: #fff8e5;
    border-color: #f1dfae;
}


.action-status {
    color: #238b39;
}


.action-status:hover {
    background: #e8f7eb;
    border-color: #c9eacb;
}


.action-delete {
    color: #d63031;
}


.action-delete:hover {
    background: #ffebed;
    border-color: #f3c8cc;
}


/* =====================================================
   EMPTY
===================================================== */

.empty-state {
    padding: 60px 20px;
    text-align: center;
}


.empty-icon {
    width: 65px;
    height: 65px;
    margin: 0 auto 15px;
    border-radius: 50%;
    background: #e8f7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 27px;
}


.empty-state h3 {
    font-size: 15px;
    color: #555;
    margin-bottom: 6px;
}


.empty-state p {
    font-size: 11px;
    color: #999;
    margin-bottom: 18px;
}


/* =====================================================
   RESULT INFO
===================================================== */

.result-info {
    padding: 14px 20px;
    border-top: 1px solid #edf0f4;
    color: #999;
    font-size: 11px;
}


/* =====================================================
   MOBILE
===================================================== */

@media (max-width: 1000px) {

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

}


@media (max-width: 900px) {

    .sidebar {
        transform: translateX(-100%);
        transition: .25s;
    }


    .sidebar.show {
        transform: translateX(0);
    }


    .main-content {
        margin-left: 0;
        width: 100%;
    }


    .mobile-menu-btn {
        display: block;
    }


    .topbar {
        padding: 0 18px;
    }


    .content {
        padding: 20px;
    }

}


@media (max-width: 650px) {

    .stats-grid {
        grid-template-columns: 1fr;
    }


    .page-header {
        display: block;
    }


    .add-btn {
        margin-top: 15px;
    }


    .filter-form {
        flex-direction: column;
        align-items: stretch;
    }


    .search-box {
        width: 100%;
    }


    .filter-select,
    .filter-btn,
    .reset-btn {
        width: 100%;
    }


    .admin-info {
        display: none;
    }


    .topbar-title h1 {
        font-size: 17px;
    }


    .content {
        padding: 15px;
    }

}

</style>

</head>


<body>


<div class="admin-wrapper">


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside
    class="sidebar"
    id="sidebar"
>

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

            <span class="menu-icon">
                📊
            </span>

            Dashboard

        </a>


        <a href="../medicines.php">

            <span class="menu-icon">
                💊
            </span>

            Medicines

        </a>


        <a href="../orders.php">

            <span class="menu-icon">
                📦
            </span>

            Orders

        </a>


        <a href="../prescriptions.php">

            <span class="menu-icon">
                📄
            </span>

            Prescriptions

        </a>


        <!-- =========================================
             DELIVERIES
        ========================================== -->

        <a
            href="../deliveries.php"
            class="active"
        >

            <span class="menu-icon">
                🚚
            </span>

            Deliveries

        </a>


        <div class="sub-menu">

            <a href="../deliveries.php">

                <span class="menu-icon">
                    📦
                </span>

                All Deliveries

            </a>


            <a
                href="index.php"
                class="active"
            >

                <span class="menu-icon">
                    🛵
                </span>

                Delivery Boys

            </a>


            <a href="../delivery/tracking.php">

                <span class="menu-icon">
                    📍
                </span>

                Delivery Tracking

            </a>

        </div>


        <a href="../hero.php">

            <span class="menu-icon">
                🖼️
            </span>

            Hero Section

        </a>


        <a href="../testimonials.php">

            <span class="menu-icon">
                💬
            </span>

            Testimonials

        </a>


        <a href="../contact-messages.php">

            <span class="menu-icon">
                ✉️
            </span>

            Contact Messages

        </a>


        <div class="menu-title">
            Account
        </div>


        <a href="../../index.php">

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
===================================================== -->

<main class="main-content">


<!-- =====================================================
     TOPBAR
===================================================== -->

<header class="topbar">

    <div class="topbar-left">

        <button
            class="mobile-menu-btn"
            onclick="toggleSidebar()"
        >
            ☰
        </button>


        <div class="topbar-title">

            <h1>
                Delivery Boys
            </h1>

            <p>
                Manage delivery partners
            </p>

        </div>

    </div>


    <div class="admin-profile">

        <div class="admin-avatar">

            <?= e(
                strtoupper(
                    substr(
                        $adminName,
                        0,
                        1
                    )
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
            Delivery Boys
        </h2>

        <p>
            Manage your delivery partners and their account status.
        </p>

    </div>


    <a
        href="add.php"
        class="add-btn"
    >

        <span>
            ➕
        </span>

        Add Delivery Boy

    </a>

</div>


<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="stats-grid">


    <div class="stat-card">

        <div class="stat-icon icon-green">
            🛵
        </div>

        <div>

            <h3>
                <?= number_format(
                    $totalDeliveryBoys
                ) ?>
            </h3>

            <p>
                Total Delivery Boys
            </p>

        </div>

    </div>


    <div class="stat-card">

        <div class="stat-icon icon-blue">
            ✅
        </div>

        <div>

            <h3>
                <?= number_format(
                    $activeDeliveryBoys
                ) ?>
            </h3>

            <p>
                Active Delivery Boys
            </p>

        </div>

    </div>


    <div class="stat-card">

        <div class="stat-icon icon-red">
            ⛔
        </div>

        <div>

            <h3>
                <?= number_format(
                    $inactiveDeliveryBoys
                ) ?>
            </h3>

            <p>
                Inactive Delivery Boys
            </p>

        </div>

    </div>


</div>


<!-- =====================================================
     DELIVERY BOYS PANEL
===================================================== -->

<div class="panel">


    <div class="panel-header">

        <h3>
            All Delivery Boys
        </h3>

        <span
            style="
                font-size:11px;
                color:#999;
            "
        >

            <?= number_format(
                count($deliveryBoys)
            ) ?>

            Result(s)

        </span>

    </div>


<!-- =====================================================
     FILTERS
===================================================== -->

<div class="filters">

    <form
        method="GET"
        class="filter-form"
    >


        <div class="search-box">

            <span class="search-icon">
                🔍
            </span>

            <input
                type="text"
                name="search"
                value="<?= e($search) ?>"
                placeholder="Search by name, mobile, email, city or state..."
            >

        </div>


        <select
            name="status"
            class="filter-select"
        >

            <option
                value="all"
                <?= $status === 'all'
                    ? 'selected'
                    : '' ?>
            >
                All Status
            </option>


            <option
                value="active"
                <?= $status === 'active'
                    ? 'selected'
                    : '' ?>
            >
                Active
            </option>


            <option
                value="inactive"
                <?= $status === 'inactive'
                    ? 'selected'
                    : '' ?>
            >
                Inactive
            </option>

        </select>


        <button
            type="submit"
            class="filter-btn"
        >
            Search
        </button>


        <?php if (
            $search !== '' ||
            $status !== 'all'
        ): ?>

            <a
                href="index.php"
                class="reset-btn"
            >
                Reset
            </a>

        <?php endif; ?>


    </form>

</div>


<!-- =====================================================
     TABLE
===================================================== -->

<?php if (!empty($deliveryBoys)): ?>


<div class="table-wrapper">

<table>


<thead>

<tr>

    <th>
        Delivery Boy
    </th>

    <th>
        Mobile
    </th>

    <th>
        Email
    </th>

    <th>
        Location
    </th>

    <th>
        Status
    </th>

    <th>
        Joined
    </th>

    <th>
        Actions
    </th>

</tr>

</thead>


<tbody>


<?php foreach (
    $deliveryBoys
    as $boy
): ?>


<tr>


<!-- DELIVERY BOY -->

<td>

    <div class="delivery-person">


        <div class="delivery-avatar">

            <?= e(
                strtoupper(
                    substr(
                        $boy['name'] ?? 'D',
                        0,
                        1
                    )
                )
            ) ?>

        </div>


        <div class="delivery-details">

            <strong>
                <?= e(
                    $boy['name']
                ) ?>
            </strong>

            <span>
                ID #<?= (int)$boy['id'] ?>
            </span>

        </div>


    </div>

</td>


<!-- MOBILE -->

<td>

    <span class="mobile-number">

        <?= e(
            $boy['mobile']
        ) ?>

    </span>

</td>


<!-- EMAIL -->

<td>

    <?= e(
        $boy['email']
        ?: '—'
    ) ?>

</td>


<!-- LOCATION -->

<td>

    <div class="location">

        <strong>

            <?= e(
                $boy['city']
                ?: '—'
            ) ?>

        </strong>


        <span>

            <?= e(
                $boy['state']
                ?: ''
            ) ?>


            <?php if (
                !empty($boy['pincode'])
            ): ?>

                - <?= e(
                    $boy['pincode']
                ) ?>

            <?php endif; ?>

        </span>

    </div>

</td>


<!-- STATUS -->

<td>

    <?php if (
        (int)$boy['status'] === 1
    ): ?>

        <span class="badge badge-active">
            Active
        </span>

    <?php else: ?>

        <span class="badge badge-inactive">
            Inactive
        </span>

    <?php endif; ?>

</td>


<!-- JOINED -->

<td>

    <?= !empty($boy['created_at'])
        ? date(
            "d M Y",
            strtotime(
                $boy['created_at']
            )
        )
        : '—'
    ?>

</td>


<!-- ACTIONS -->

<td>

    <div class="actions">


        <a
            href="view.php?id=<?= (int)$boy['id'] ?>"
            class="action-btn action-view"
            title="View"
        >
            👁️
        </a>


        <a
            href="edit.php?id=<?= (int)$boy['id'] ?>"
            class="action-btn action-edit"
            title="Edit"
        >
            ✏️
        </a>


        <a
            href="toggle-status.php?id=<?= (int)$boy['id'] ?>"
            class="action-btn action-status"
            title="<?= (int)$boy['status'] === 1
                ? 'Deactivate'
                : 'Activate'
            ?>"
            onclick="
                return confirm(
                    'Are you sure you want to change this delivery boy status?'
                );
            "
        >

            <?= (int)$boy['status'] === 1
                ? '⛔'
                : '✅'
            ?>

        </a>


        <a
            href="delete.php?id=<?= (int)$boy['id'] ?>"
            class="action-btn action-delete"
            title="Delete"
            onclick="
                return confirm(
                    'Are you sure you want to delete this delivery boy? This action cannot be undone.'
                );
            "
        >
            🗑️
        </a>


    </div>

</td>


</tr>


<?php endforeach; ?>


</tbody>

</table>

</div>


<div class="result-info">

    Showing
    <strong>
        <?= number_format(
            count($deliveryBoys)
        ) ?>
    </strong>

    delivery boy(s)

    <?php if ($search !== ''): ?>

        matching
        <strong>
            "<?= e($search) ?>"
        </strong>

    <?php endif; ?>

</div>


<?php else: ?>


<!-- =====================================================
     EMPTY STATE
===================================================== -->

<div class="empty-state">


    <div class="empty-icon">
        🛵
    </div>


    <?php if (
        $search !== '' ||
        $status !== 'all'
    ): ?>

        <h3>
            No Delivery Boys Found
        </h3>

        <p>
            No delivery boy matches your current search or filter.
        </p>


        <a
            href="index.php"
            class="add-btn"
        >
            Clear Filters
        </a>


    <?php else: ?>

        <h3>
            No Delivery Boys Yet
        </h3>

        <p>
            Start by adding your first delivery boy.
        </p>


        <a
            href="add.php"
            class="add-btn"
        >
            ➕ Add Delivery Boy
        </a>

    <?php endif; ?>


</div>


<?php endif; ?>


</div>


</div>


</main>


</div>


<script>

function toggleSidebar()
{
    const sidebar =
        document.getElementById("sidebar");

    sidebar.classList.toggle("show");
}


// Close sidebar on mobile

document
    .querySelectorAll(".sidebar a")
    .forEach(function(link)
    {
        link.addEventListener(
            "click",
            function()
            {
                if (
                    window.innerWidth <= 900
                ) {
                    document
                        .getElementById("sidebar")
                        .classList.remove("show");
                }
            }
        );
    });

</script>


</body>

</html>

