```php
<?php

session_start();

require_once "../config/database.php";


// =====================================================
// ADMIN AUTHENTICATION
// =====================================================

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: login.php");
    exit;
}


// =====================================================
// ADMIN DATA
// =====================================================

$adminName   = $_SESSION['name'] ?? 'Administrator';
$adminMobile = $_SESSION['mobile'] ?? '';
$adminEmail  = $_SESSION['email'] ?? '';


// =====================================================
// HELPER FUNCTIONS
// =====================================================

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function getCount($conn, $sql)
{
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return (int)($row['total'] ?? 0);
    }

    return 0;
}


function getAmount($conn, $sql)
{
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return (float)($row['total'] ?? 0);
    }

    return 0;
}


// =====================================================
// DASHBOARD STATISTICS
// =====================================================


// -----------------------------------------------------
// MEDICINES
// -----------------------------------------------------

$totalMedicines = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM medicines
     WHERE status = 1"
);


$lowStock = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM medicines
     WHERE status = 1
     AND stock_quantity > 0
     AND stock_quantity <= 10"
);


$outOfStock = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM medicines
     WHERE status = 1
     AND stock_quantity = 0"
);


// -----------------------------------------------------
// CUSTOMERS
// -----------------------------------------------------

$totalCustomers = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'customer'
     AND status = 1"
);


// -----------------------------------------------------
// ORDERS
// -----------------------------------------------------

$totalOrders = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders"
);


$pendingOrders = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE order_status = 'pending'"
);


$processingOrders = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE order_status = 'processing'"
);


$confirmedOrders = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE order_status = 'confirmed'"
);


$readyOrders = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE order_status = 'ready'"
);


$completedOrders = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE order_status = 'delivered'"
);


$cancelledOrders = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE order_status = 'cancelled'"
);


// -----------------------------------------------------
// PAYMENTS
// -----------------------------------------------------

$pendingPayments = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE payment_status = 'pending'"
);


$paidOrders = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE payment_status = 'paid'"
);


$failedPayments = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE payment_status = 'failed'"
);


// -----------------------------------------------------
// REVENUE
// -----------------------------------------------------

$totalRevenue = getAmount(
    $conn,
    "SELECT COALESCE(SUM(total_amount), 0) AS total
     FROM orders
     WHERE order_status != 'cancelled'"
);


// -----------------------------------------------------
// TODAY
// -----------------------------------------------------

$todayOrders = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE DATE(created_at) = CURDATE()"
);


$todaySales = getAmount(
    $conn,
    "SELECT COALESCE(SUM(total_amount), 0) AS total
     FROM orders
     WHERE DATE(created_at) = CURDATE()
     AND order_status != 'cancelled'"
);


// -----------------------------------------------------
// THIS MONTH
// -----------------------------------------------------

$monthlyOrders = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE MONTH(created_at) = MONTH(CURDATE())
     AND YEAR(created_at) = YEAR(CURDATE())"
);


$monthlySales = getAmount(
    $conn,
    "SELECT COALESCE(SUM(total_amount), 0) AS total
     FROM orders
     WHERE MONTH(created_at) = MONTH(CURDATE())
     AND YEAR(created_at) = YEAR(CURDATE())
     AND order_status != 'cancelled'"
);


// -----------------------------------------------------
// PRESCRIPTIONS
// -----------------------------------------------------

$pendingPrescriptions = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM prescriptions
     WHERE status = 'pending'"
);


$totalPrescriptions = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM prescriptions"
);


// -----------------------------------------------------
// DELIVERIES
// -----------------------------------------------------

$activeDeliveries = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM deliveries
     WHERE status NOT IN (
        'delivered',
        'cancelled',
        'failed'
     )"
);


$deliveredOrders = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM deliveries
     WHERE status = 'delivered'"
);


// =====================================================
// RECENT ORDERS
// =====================================================

$recentOrders = [];

$sqlRecentOrders = "
    SELECT
        id,
        order_number,
        customer_name,
        customer_mobile,
        total_amount,
        payment_method,
        payment_status,
        order_status,
        created_at
    FROM orders
    ORDER BY id DESC
    LIMIT 8
";

$resultRecentOrders = mysqli_query(
    $conn,
    $sqlRecentOrders
);

if ($resultRecentOrders) {

    while ($row = mysqli_fetch_assoc($resultRecentOrders)) {

        $recentOrders[] = $row;

    }
}


// =====================================================
// RECENT PRESCRIPTIONS
// =====================================================

$recentPrescriptions = [];

$sqlPrescriptions = "
    SELECT
        p.id,
        p.user_id,
        p.original_file_name,
        p.file_type,
        p.status,
        p.created_at,
        u.name AS customer_name,
        u.mobile AS customer_mobile
    FROM prescriptions p
    LEFT JOIN users u
        ON u.id = p.user_id
    ORDER BY p.id DESC
    LIMIT 6
";

$resultPrescriptions = mysqli_query(
    $conn,
    $sqlPrescriptions
);

if ($resultPrescriptions) {

    while ($row = mysqli_fetch_assoc($resultPrescriptions)) {

        $recentPrescriptions[] = $row;

    }
}


// =====================================================
// LOW STOCK MEDICINES
// =====================================================

$lowStockMedicines = [];

$sqlLowStock = "
    SELECT
        id,
        name,
        generic_name,
        stock_quantity,
        selling_price
    FROM medicines
    WHERE status = 1
    AND stock_quantity <= 10
    ORDER BY stock_quantity ASC, id DESC
    LIMIT 6
";

$resultLowStock = mysqli_query(
    $conn,
    $sqlLowStock
);

if ($resultLowStock) {

    while ($row = mysqli_fetch_assoc($resultLowStock)) {

        $lowStockMedicines[] = $row;

    }
}


// =====================================================
// RECENT CUSTOMERS
// =====================================================

$recentCustomers = [];

$sqlRecentCustomers = "
    SELECT
        id,
        name,
        mobile,
        email,
        created_at
    FROM users
    WHERE role = 'customer'
    ORDER BY id DESC
    LIMIT 5
";

$resultRecentCustomers = mysqli_query(
    $conn,
    $sqlRecentCustomers
);

if ($resultRecentCustomers) {

    while ($row = mysqli_fetch_assoc($resultRecentCustomers)) {

        $recentCustomers[] = $row;

    }
}


// =====================================================
// ORDER STATUS SUMMARY
// =====================================================

$orderStatus = [
    'pending'    => $pendingOrders,
    'confirmed'  => $confirmedOrders,
    'processing' => $processingOrders,
    'ready'      => $readyOrders,
    'delivered'  => $completedOrders,
    'cancelled'  => $cancelledOrders
];

// =====================================================
// CONTACT MESSAGES
// =====================================================

$contactCount = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM contact_messages
     WHERE status IN ('new', 'unread', 'pending')"
);


// =====================================================
// ADMIN ALERTS
// =====================================================

$alerts = [];

if ($pendingOrders > 0) {

    $alerts[] = [
        'icon' => '📦',
        'title' => $pendingOrders . ' Pending Order(s)',
        'text' => 'Orders are waiting for processing.',
        'link' => 'orders.php',
        'class' => 'alert-warning'
    ];

}


if ($pendingPrescriptions > 0) {

    $alerts[] = [
        'icon' => '📄',
        'title' => $pendingPrescriptions . ' Prescription(s)',
        'text' => 'Prescription verification required.',
        'link' => 'prescriptions.php',
        'class' => 'alert-purple'
    ];

}


if ($lowStock > 0) {

    $alerts[] = [
        'icon' => '⚠️',
        'title' => $lowStock . ' Low Stock Medicine(s)',
        'text' => 'Check inventory and update stock.',
        'link' => 'medicines.php',
        'class' => 'alert-orange'
    ];

}


if ($outOfStock > 0) {

    $alerts[] = [
        'icon' => '🚫',
        'title' => $outOfStock . ' Out of Stock',
        'text' => 'Some medicines are currently unavailable.',
        'link' => 'medicines.php',
        'class' => 'alert-danger'
    ];

}


// =====================================================
// PAGE TITLE
// =====================================================

$pageTitle = "Admin Dashboard";

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

.menu-badge {
    margin-left: auto;
    background: #fff;
    color: #238438;
    border-radius: 20px;
    padding: 3px 7px;
    font-size: 9px;
    font-weight: 700;
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
   WELCOME
===================================================== */

.welcome-box {
    background: linear-gradient(
        135deg,
        #4caf43,
        #238b39
    );
    border-radius: 14px;
    padding: 25px 28px;
    color: #fff;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    overflow: hidden;
    position: relative;
}

.welcome-box::after {
    content: "";
    width: 200px;
    height: 200px;
    border: 30px solid rgba(255,255,255,.07);
    border-radius: 50%;
    position: absolute;
    right: -80px;
    top: -80px;
}

.welcome-box h2 {
    font-size: 23px;
    margin-bottom: 6px;
}

.welcome-box p {
    font-size: 13px;
    opacity: .85;
}

.date-box {
    position: relative;
    z-index: 2;
    font-size: 13px;
    text-align: right;
}


/* =====================================================
   STATS
===================================================== */

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 20px;
}

.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #edf0f4;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: .2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,.06);
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

.icon-green { background:#e8f7eb; }
.icon-blue { background:#e9f2ff; }
.icon-orange { background:#fff3e4; }
.icon-purple { background:#f1eaff; }
.icon-red { background:#ffebed; }

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
   REVENUE CARDS
===================================================== */

.revenue-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 25px;
}

.revenue-card {
    background: #fff;
    border: 1px solid #edf0f4;
    border-radius: 12px;
    padding: 20px;
}

.revenue-card-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.revenue-card small {
    color: #999;
    font-size: 10px;
    text-transform: uppercase;
}

.revenue-card h2 {
    font-size: 25px;
    margin-top: 8px;
    color: #238b39;
}

.revenue-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: #eaf7ec;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}


/* =====================================================
   SECTION
===================================================== */

.section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 15px;
}

.section-heading h2 {
    font-size: 17px;
    color: #222;
}

.view-all {
    color: #2d963e;
    font-size: 12px;
    font-weight: 600;
}


/* =====================================================
   QUICK ACTIONS
===================================================== */

.quick-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 30px;
}

.quick-action {
    background: #fff;
    border: 1px solid #edf0f4;
    border-radius: 11px;
    padding: 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: .2s;
}

.quick-action:hover {
    border-color: #51b848;
    box-shadow: 0 5px 18px rgba(81,184,72,.10);
}

.quick-icon {
    width: 42px;
    height: 42px;
    border-radius: 9px;
    background: #eaf7ec;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.quick-action strong {
    display: block;
    font-size: 13px;
}

.quick-action span {
    display: block;
    font-size: 10px;
    color: #999;
    margin-top: 3px;
}


/* =====================================================
   ALERTS
===================================================== */

.alert-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 25px;
}

.alert-box {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #fff;
    border: 1px solid #edf0f4;
    padding: 14px;
    border-radius: 10px;
}

.alert-icon {
    width: 40px;
    height: 40px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff3cd;
}

.alert-content {
    flex: 1;
}

.alert-content strong {
    display: block;
    font-size: 12px;
}

.alert-content span {
    display: block;
    color: #999;
    font-size: 10px;
    margin-top: 3px;
}

.alert-link {
    color: #278c3c;
    font-size: 11px;
    font-weight: 600;
}

.alert-purple .alert-icon {
    background: #f1eaff;
}

.alert-orange .alert-icon {
    background: #fff3e4;
}

.alert-danger .alert-icon {
    background: #ffebed;
}


/* =====================================================
   DASHBOARD GRID
===================================================== */

.dashboard-grid {
    display: grid;
    grid-template-columns:
        minmax(0, 2fr)
        minmax(300px, 1fr);
    gap: 20px;
}

.panel {
    background: #fff;
    border: 1px solid #edf0f4;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 20px;
}

.panel-header {
    padding: 17px 20px;
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
   TABLE
===================================================== */

.table-wrapper {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 650px;
}

th {
    background: #fafbfc;
    color: #888;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .4px;
    padding: 12px 15px;
    text-align: left;
}

td {
    padding: 13px 15px;
    border-top: 1px solid #f0f2f5;
    font-size: 12px;
    color: #444;
}

.order-number {
    font-weight: 600;
    color: #258b38;
}

.customer-name {
    font-weight: 600;
    color: #333;
}

.customer-mobile {
    color: #999;
    font-size: 10px;
    margin-top: 3px;
}


/* =====================================================
   BADGES
===================================================== */

.badge {
    display: inline-block;
    padding: 5px 8px;
    border-radius: 20px;
    font-size: 9px;
    font-weight: 600;
    text-transform: capitalize;
}

.badge-pending {
    color: #9a6700;
    background: #fff3cd;
}

.badge-confirmed {
    color: #1261a0;
    background: #dceeff;
}

.badge-processing {
    color: #6040a0;
    background: #eee7ff;
}

.badge-ready {
    color: #087f5b;
    background: #d9f8ec;
}

.badge-delivered {
    color: #14752f;
    background: #dff5e3;
}

.badge-cancelled {
    color: #a51d2d;
    background: #ffe3e6;
}

.badge-paid {
    color: #14752f;
    background: #dff5e3;
}

.badge-failed {
    color: #a51d2d;
    background: #ffe3e6;
}


/* =====================================================
   STATUS OVERVIEW
===================================================== */

.status-item {
    padding: 12px 18px;
}

.status-top {
    display: flex;
    justify-content: space-between;
    margin-bottom: 6px;
}

.status-top span {
    font-size: 11px;
    color: #666;
}

.status-top strong {
    font-size: 11px;
}

.progress {
    height: 7px;
    background: #f0f2f5;
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: #51b848;
    border-radius: 10px;
}


/* =====================================================
   LOW STOCK
===================================================== */

.medicine-row {
    padding: 14px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #f0f2f5;
}

.medicine-row:last-child {
    border-bottom: none;
}

.medicine-info strong {
    display: block;
    font-size: 12px;
    color: #333;
}

.medicine-info span {
    display: block;
    font-size: 10px;
    color: #999;
    margin-top: 3px;
}

.stock-count {
    font-size: 12px;
    font-weight: 700;
    padding: 6px 9px;
    border-radius: 6px;
    background: #fff0f0;
    color: #d63031;
}

.stock-good {
    background: #fff8e5;
    color: #ad7600;
}


/* =====================================================
   PRESCRIPTIONS
===================================================== */

.prescription-row {
    padding: 14px 18px;
    border-bottom: 1px solid #f0f2f5;
}

.prescription-row:last-child {
    border-bottom: none;
}

.prescription-top {
    display: flex;
    justify-content: space-between;
    gap: 10px;
}

.prescription-name {
    font-size: 12px;
    font-weight: 600;
    color: #333;
}

.prescription-file {
    font-size: 10px;
    color: #999;
    margin-top: 5px;
    word-break: break-all;
}


/* =====================================================
   CUSTOMER
===================================================== */

.customer-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 18px;
    border-bottom: 1px solid #f0f2f5;
}

.customer-row:last-child {
    border-bottom: none;
}

.customer-avatar {
    width: 36px;
    height: 36px;
    background: #e9f2ff;
    color: #2369a0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
}

.customer-details {
    flex: 1;
}

.customer-details strong {
    display: block;
    font-size: 12px;
}

.customer-details span {
    display: block;
    font-size: 10px;
    color: #999;
    margin-top: 3px;
}


/* =====================================================
   INVENTORY
===================================================== */

.inventory-item {
    padding: 14px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #f0f2f5;
}

.inventory-item:last-child {
    border-bottom: none;
}

.inventory-item span {
    color: #777;
    font-size: 11px;
}

.inventory-item strong {
    font-size: 13px;
}


/* =====================================================
   EMPTY
===================================================== */

.empty-state {
    padding: 35px 20px;
    text-align: center;
    color: #aaa;
    font-size: 12px;
}


/* =====================================================
   MOBILE
===================================================== */

@media (max-width:1200px) {

    .stats-grid {
        grid-template-columns: repeat(2,1fr);
    }

    .quick-grid {
        grid-template-columns: repeat(2,1fr);
    }

    .revenue-grid {
        grid-template-columns: repeat(2,1fr);
    }

}

@media (max-width:900px) {

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

    .dashboard-grid {
        grid-template-columns: 1fr;
    }

}

@media (max-width:600px) {

    .stats-grid,
    .quick-grid,
    .revenue-grid,
    .alert-grid {
        grid-template-columns: 1fr;
    }

    .welcome-box {
        display: block;
    }

    .date-box {
        text-align: left;
        margin-top: 15px;
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

    .welcome-box {
        padding: 20px;
    }

    .welcome-box h2 {
        font-size: 19px;
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


        <a
            href="index.php"
            class="active"
        >
            <span class="menu-icon">📊</span>
            Dashboard
        </a>


        <a href="medicines.php">
            <span class="menu-icon">💊</span>
            Medicines

            <?php if ($lowStock > 0): ?>
                <span class="menu-badge">
                    <?= $lowStock ?>
                </span>
            <?php endif; ?>
        </a>


        <a href="orders.php">
            <span class="menu-icon">📦</span>
            Orders

            <?php if ($pendingOrders > 0): ?>
                <span class="menu-badge">
                    <?= $pendingOrders ?>
                </span>
            <?php endif; ?>
        </a>


        <a href="prescriptions.php">
            <span class="menu-icon">📄</span>
            Prescriptions

            <?php if ($pendingPrescriptions > 0): ?>
                <span class="menu-badge">
                    <?= $pendingPrescriptions ?>
                </span>
            <?php endif; ?>
        </a>


        <a href="deliveries.php">
            <span class="menu-icon">🚚</span>
            Deliveries
        </a>


        <a href="hero.php">
            <span class="menu-icon">🖼️</span>
            Hero Section
        </a>


        <a href="testimonials.php">
            <span class="menu-icon">💬</span>
            Testimonials
        </a>

        <a href="contact-messages.php">

    <span class="menu-icon">✉️</span>

    <span>
        Contact Messages
    </span>

    <?php if ($contactCount > 0): ?>

        <span class="menu-badge">
            <?= number_format($contactCount) ?>
        </span>

    <?php endif; ?>

</a>


        <div class="menu-title">
            Account
        </div>


        <a href="../index.php">
            <span class="menu-icon">🌐</span>
            View Website
        </a>


        <a href="logout.php">
            <span class="menu-icon">🚪</span>
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
                Dashboard
            </h1>

            <p>
                Medicine Aapki Gaw Mein Admin Panel
            </p>

        </div>

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


<!-- =====================================================
     CONTENT
===================================================== -->

<div class="content">


<!-- =====================================================
     WELCOME
===================================================== -->

<div class="welcome-box">

    <div>

        <h2>
            Welcome back,
            <?= e($adminName) ?> 👋
        </h2>

        <p>
            Yahan se aap medicines, orders,
            prescriptions, deliveries aur inventory manage kar sakte hain.
        </p>

    </div>

    <div class="date-box">

        <?= date("l, d M Y") ?>

    </div>

</div>


<!-- =====================================================
     MAIN STATISTICS
===================================================== -->

<div class="stats-grid">


    <div class="stat-card">

        <div class="stat-icon icon-green">
            💊
        </div>

        <div>

            <h3>
                <?= number_format($totalMedicines) ?>
            </h3>

            <p>
                Total Medicines
            </p>

        </div>

    </div>


    <div class="stat-card">

        <div class="stat-icon icon-blue">
            👥
        </div>

        <div>

            <h3>
                <?= number_format($totalCustomers) ?>
            </h3>

            <p>
                Active Customers
            </p>

        </div>

    </div>


    <div class="stat-card">

        <div class="stat-icon icon-orange">
            📦
        </div>

        <div>

            <h3>
                <?= number_format($totalOrders) ?>
            </h3>

            <p>
                Total Orders
            </p>

        </div>

    </div>


    <div class="stat-card">

        <div class="stat-icon icon-purple">
            ⏳
        </div>

        <div>

            <h3>
                <?= number_format($pendingOrders) ?>
            </h3>

            <p>
                Pending Orders
            </p>

        </div>

    </div>


</div>


<!-- =====================================================
     SECONDARY STATISTICS
===================================================== -->

<div class="stats-grid">


    <div class="stat-card">

        <div class="stat-icon icon-green">
            ✅
        </div>

        <div>

            <h3>
                <?= number_format($completedOrders) ?>
            </h3>

            <p>
                Completed Orders
            </p>

        </div>

    </div>


    <div class="stat-card">

        <div class="stat-icon icon-red">
            ❌
        </div>

        <div>

            <h3>
                <?= number_format($cancelledOrders) ?>
            </h3>

            <p>
                Cancelled Orders
            </p>

        </div>

    </div>


    <div class="stat-card">

        <div class="stat-icon icon-blue">
            🚚
        </div>

        <div>

            <h3>
                <?= number_format($activeDeliveries) ?>
            </h3>

            <p>
                Active Deliveries
            </p>

        </div>

    </div>


    <div class="stat-card">

        <div class="stat-icon icon-orange">
            ⚠️
        </div>

        <div>

            <h3>
                <?= number_format($lowStock) ?>
            </h3>

            <p>
                Low Stock Medicines
            </p>

        </div>

    </div>


</div>


<!-- =====================================================
     REVENUE
===================================================== -->

<div class="revenue-grid">


    <div class="revenue-card">

        <div class="revenue-card-top">

            <div>

                <small>
                    Total Revenue
                </small>

                <h2>
                    ₹<?= number_format(
                        $totalRevenue,
                        2
                    ) ?>
                </h2>

            </div>

            <div class="revenue-icon">
                💰
            </div>

        </div>

    </div>


    <div class="revenue-card">

        <div class="revenue-card-top">

            <div>

                <small>
                    Today's Sales
                </small>

                <h2>
                    ₹<?= number_format(
                        $todaySales,
                        2
                    ) ?>
                </h2>

            </div>

            <div class="revenue-icon">
                📅
            </div>

        </div>

    </div>


    <div class="revenue-card">

        <div class="revenue-card-top">

            <div>

                <small>
                    Monthly Sales
                </small>

                <h2>
                    ₹<?= number_format(
                        $monthlySales,
                        2
                    ) ?>
                </h2>

            </div>

            <div class="revenue-icon">
                📈
            </div>

        </div>

    </div>


</div>


<!-- =====================================================
     ALERTS
===================================================== -->

<?php if (!empty($alerts)): ?>

    <div class="section-heading">

        <h2>
            Attention Required
        </h2>

    </div>


    <div class="alert-grid">

        <?php foreach ($alerts as $alert): ?>

            <div class="alert-box <?= e($alert['class']) ?>">

                <div class="alert-icon">

                    <?= e($alert['icon']) ?>

                </div>

                <div class="alert-content">

                    <strong>
                        <?= e($alert['title']) ?>
                    </strong>

                    <span>
                        <?= e($alert['text']) ?>
                    </span>

                </div>

                <a
                    href="<?= e($alert['link']) ?>"
                    class="alert-link"
                >
                    View →
                </a>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>


<!-- =====================================================
     QUICK ACTIONS
===================================================== -->

<div class="section-heading">

    <h2>
        Quick Actions
    </h2>

</div>


<div class="quick-grid">


    <a
        href="medicines.php"
        class="quick-action"
    >

        <div class="quick-icon">
            ➕
        </div>

        <div>

            <strong>
                Manage Medicines
            </strong>

            <span>
                Add / edit medicines
            </span>

        </div>

    </a>


    <a
        href="orders.php"
        class="quick-action"
    >

        <div class="quick-icon">
            📦
        </div>

        <div>

            <strong>
                Manage Orders
            </strong>

            <span>
                Process customer orders
            </span>

        </div>

    </a>


    <a
        href="prescriptions.php"
        class="quick-action"
    >

        <div class="quick-icon">
            📄
        </div>

        <div>

            <strong>
                Prescriptions
            </strong>

            <span>
                Review prescriptions
            </span>

        </div>

    </a>


    <a
        href="deliveries.php"
        class="quick-action"
    >

        <div class="quick-icon">
            🚚
        </div>

        <div>

            <strong>
                Deliveries
            </strong>

            <span>
                Track deliveries
            </span>

        </div>

    </a>


    <a
        href="hero.php"
        class="quick-action"
    >

        <div class="quick-icon">
            🖼️
        </div>

        <div>

            <strong>
                Hero Section
            </strong>

            <span>
                Manage homepage banners
            </span>

        </div>

    </a>


    <a
        href="testimonials.php"
        class="quick-action"
    >

        <div class="quick-icon">
            💬
        </div>

        <div>

            <strong>
                Testimonials
            </strong>

            <span>
                Manage customer reviews
            </span>

        </div>

    </a>


    <a
        href="../index.php"
        class="quick-action"
    >

        <div class="quick-icon">
            🌐
        </div>

        <div>

            <strong>
                View Website
            </strong>

            <span>
                Open public website
            </span>

        </div>

    </a>


    <a
        href="logout.php"
        class="quick-action"
    >

        <div class="quick-icon">
            🚪
        </div>

        <div>

            <strong>
                Logout
            </strong>

            <span>
                End admin session
            </span>

        </div>

    </a>


</div>


<!-- =====================================================
     DASHBOARD GRID
===================================================== -->

<div class="dashboard-grid">


<!-- =====================================================
     LEFT COLUMN
===================================================== -->

<div>


<!-- =====================================================
     RECENT ORDERS
===================================================== -->

<div class="panel">

    <div class="panel-header">

        <h3>
            Recent Orders
        </h3>

        <a
            href="orders.php"
            class="view-all"
        >
            View All →
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
                            Customer
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
                            Date
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php foreach ($recentOrders as $order): ?>

                    <?php

                    $status =
                        strtolower(
                            $order['order_status'] ?? ''
                        );

                    $allowedStatuses = [
                        'pending',
                        'confirmed',
                        'processing',
                        'ready',
                        'delivered',
                        'cancelled'
                    ];

                    $statusClass =
                        in_array(
                            $status,
                            $allowedStatuses,
                            true
                        )
                            ? 'badge-' . $status
                            : 'badge-pending';

                    ?>


                    <tr>

                        <td>

                            <span class="order-number">

                                #<?= e(
                                    $order['order_number']
                                ) ?>

                            </span>

                        </td>


                        <td>

                            <div class="customer-name">

                                <?= e(
                                    $order['customer_name']
                                ) ?>

                            </div>

                            <div class="customer-mobile">

                                <?= e(
                                    $order['customer_mobile']
                                ) ?>

                            </div>

                        </td>


                        <td>

                            <strong>

                                ₹<?= number_format(
                                    (float)$order['total_amount'],
                                    2
                                ) ?>

                            </strong>

                        </td>


                        <td>

                            <?php

                            $paymentStatus =
                                strtolower(
                                    $order['payment_status'] ?? ''
                                );

                            $paymentClass =
                                $paymentStatus === 'paid'
                                    ? 'badge-paid'
                                    : (
                                        $paymentStatus === 'failed'
                                            ? 'badge-failed'
                                            : 'badge-pending'
                                    );

                            ?>

                            <span
                                class="badge <?= $paymentClass ?>"
                            >

                                <?= e(
                                    $order['payment_status']
                                ) ?>

                            </span>

                        </td>


                        <td>

                            <span
                                class="badge <?= $statusClass ?>"
                            >

                                <?= e(
                                    $order['order_status']
                                ) ?>

                            </span>

                        </td>


                        <td>

                            <?= date(
                                "d M",
                                strtotime(
                                    $order['created_at']
                                )
                            ) ?>

                        </td>

                    </tr>


                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php else: ?>

        <div class="empty-state">
            No orders available yet.
        </div>

    <?php endif; ?>

</div>


<!-- =====================================================
     RECENT PRESCRIPTIONS
===================================================== -->

<div class="panel">

    <div class="panel-header">

        <h3>
            Recent Prescriptions
        </h3>

        <a
            href="prescriptions.php"
            class="view-all"
        >
            View All →
        </a>

    </div>


    <?php if (!empty($recentPrescriptions)): ?>

        <?php foreach (
            $recentPrescriptions
            as $prescription
        ): ?>

            <div class="prescription-row">

                <div class="prescription-top">

                    <div>

                        <div class="prescription-name">

                            <?= e(
                                $prescription['customer_name']
                                ?? 'Customer'
                            ) ?>

                        </div>


                        <div class="prescription-file">

                            📄

                            <?= e(
                                $prescription['original_file_name']
                            ) ?>

                        </div>

                    </div>


                    <span
                        class="badge
                        <?= strtolower(
                            $prescription['status']
                        ) === 'pending'
                            ? 'badge-pending'
                            : 'badge-confirmed'
                        ?>"
                    >

                        <?= e(
                            $prescription['status']
                        ) ?>

                    </span>

                </div>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="empty-state">
            No prescriptions available.
        </div>

    <?php endif; ?>

</div>


<!-- =====================================================
     RECENT CUSTOMERS
===================================================== -->

<div class="panel">

    <div class="panel-header">

        <h3>
            Recent Customers
        </h3>

        <span class="view-all">
            <?= number_format($totalCustomers) ?>
            Total
        </span>

    </div>


    <?php if (!empty($recentCustomers)): ?>

        <?php foreach ($recentCustomers as $customer): ?>

            <div class="customer-row">

                <div class="customer-avatar">

                    <?= e(
                        strtoupper(
                            substr(
                                $customer['name'] ?? 'C',
                                0,
                                1
                            )
                        )
                    ) ?>

                </div>


                <div class="customer-details">

                    <strong>

                        <?= e(
                            $customer['name']
                        ) ?>

                    </strong>

                    <span>

                        <?= e(
                            $customer['mobile']
                            ?: ($customer['email'] ?? '')
                        ) ?>

                    </span>

                </div>


                <span
                    style="
                        font-size:10px;
                        color:#999;
                    "
                >

                    <?= date(
                        "d M",
                        strtotime(
                            $customer['created_at']
                        )
                    ) ?>

                </span>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="empty-state">
            No customers found.
        </div>

    <?php endif; ?>

</div>


</div>


<!-- =====================================================
     RIGHT COLUMN
===================================================== -->

<div>


<!-- =====================================================
     TODAY SUMMARY
===================================================== -->

<div class="panel">

    <div class="panel-header">

        <h3>
            Today's Summary
        </h3>

    </div>


    <div>

        <div class="inventory-item">

            <span>
                Today's Orders
            </span>

            <strong style="color:#278c3c;">
                <?= number_format($todayOrders) ?>
            </strong>

        </div>


        <div class="inventory-item">

            <span>
                Today's Sales
            </span>

            <strong style="color:#278c3c;">
                ₹<?= number_format(
                    $todaySales,
                    2
                ) ?>
            </strong>

        </div>


        <div class="inventory-item">

            <span>
                Monthly Orders
            </span>

            <strong>
                <?= number_format(
                    $monthlyOrders
                ) ?>
            </strong>

        </div>


        <div class="inventory-item">

            <span>
                Active Deliveries
            </span>

            <strong style="color:#2369a0;">
                <?= number_format(
                    $activeDeliveries
                ) ?>
            </strong>

        </div>

    </div>

</div>


<!-- =====================================================
     ORDER STATUS
===================================================== -->

<div class="panel">

    <div class="panel-header">

        <h3>
            Order Status
        </h3>

        <a
            href="orders.php"
            class="view-all"
        >
            Manage →
        </a>

    </div>


    <?php

    $statusTotal =
        max(
            1,
            $totalOrders
        );

    ?>


    <?php foreach ($orderStatus as $name => $count): ?>

        <div class="status-item">

            <div class="status-top">

                <span>
                    <?= e(ucfirst($name)) ?>
                </span>

                <strong>
                    <?= number_format($count) ?>
                </strong>

            </div>


            <div class="progress">

                <div
                    class="progress-bar"
                    style="
                        width:
                        <?= min(
                            100,
                            ($count / $statusTotal) * 100
                        ) ?>%;
                    "
                ></div>

            </div>

        </div>

    <?php endforeach; ?>

</div>


<!-- =====================================================
     LOW STOCK
===================================================== -->

<div class="panel">

    <div class="panel-header">

        <h3>
            Low Stock
        </h3>

        <a
            href="medicines.php"
            class="view-all"
        >
            Manage →
        </a>

    </div>


    <?php if (!empty($lowStockMedicines)): ?>

        <?php foreach (
            $lowStockMedicines
            as $medicine
        ): ?>

            <div class="medicine-row">

                <div class="medicine-info">

                    <strong>

                        <?= e(
                            $medicine['name']
                        ) ?>

                    </strong>

                    <span>

                        ₹<?= number_format(
                            (float)$medicine['selling_price'],
                            2
                        ) ?>

                    </span>

                </div>


                <span
                    class="stock-count
                    <?= $medicine['stock_quantity'] > 5
                        ? 'stock-good'
                        : ''
                    ?>"
                >

                    <?= (int)
                        $medicine['stock_quantity']
                    ?>

                    left

                </span>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="empty-state">
            No low-stock medicines.
        </div>

    <?php endif; ?>

</div>


<!-- =====================================================
     INVENTORY SUMMARY
===================================================== -->

<div class="panel">

    <div class="panel-header">

        <h3>
            Inventory
        </h3>

    </div>


    <div class="inventory-item">

        <span>
            Available Medicines
        </span>

        <strong style="color:#278c3c;">
            <?= number_format(
                $totalMedicines
            ) ?>
        </strong>

    </div>


    <div class="inventory-item">

        <span>
            Low Stock
        </span>

        <strong style="color:#d68a00;">
            <?= number_format(
                $lowStock
            ) ?>
        </strong>

    </div>


    <div class="inventory-item">

        <span>
            Out of Stock
        </span>

        <strong style="color:#d63031;">
            <?= number_format(
                $outOfStock
            ) ?>
        </strong>

    </div>

</div>


<!-- =====================================================
     PAYMENT SUMMARY
===================================================== -->

<div class="panel">

    <div class="panel-header">

        <h3>
            Payment Summary
        </h3>

    </div>


    <div class="inventory-item">

        <span>
            Paid Orders
        </span>

        <strong style="color:#278c3c;">
            <?= number_format(
                $paidOrders
            ) ?>
        </strong>

    </div>


    <div class="inventory-item">

        <span>
            Pending Payments
        </span>

        <strong style="color:#d68a00;">
            <?= number_format(
                $pendingPayments
            ) ?>
        </strong>

    </div>


    <div class="inventory-item">

        <span>
            Failed Payments
        </span>

        <strong style="color:#d63031;">
            <?= number_format(
                $failedPayments
            ) ?>
        </strong>

    </div>

</div>


</div>


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


// Close sidebar after clicking a link on mobile

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
```
