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
// CUSTOMER ID
// =====================================================

$customerId = (int) $_SESSION['user_id'];


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
// GET CUSTOMER
// =====================================================

$customer = null;

$stmt = $conn->prepare("
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
        created_at
    FROM users
    WHERE id = ?
    AND role = 'customer'
    LIMIT 1
");

if ($stmt) {

    $stmt->bind_param(
        "i",
        $customerId
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $customer = $result->fetch_assoc();
    }

    $stmt->close();
}


// =====================================================
// CUSTOMER NOT FOUND
// =====================================================

if (!$customer) {

    session_destroy();

    header("Location: ../login.php");

    exit;
}


// =====================================================
// DATA
// =====================================================

$customerName =
    $customer['name'] ?: 'Customer';

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
// MEMBER SINCE
// =====================================================

$memberSince = '-';

if (!empty($customer['created_at'])) {

    $timestamp = strtotime(
        $customer['created_at']
    );

    if ($timestamp !== false) {

        $memberSince =
            date(
                'd M Y',
                $timestamp
            );
    }
}


// =====================================================
// ADDRESS
// =====================================================

$addressParts = array_filter([
    $customer['address'] ?? '',
    $customer['city'] ?? '',
    $customer['state'] ?? '',
    $customer['pincode'] ?? ''
]);

$fullAddress =
    implode(
        ', ',
        $addressParts
    );


// =====================================================
// PAGE
// =====================================================

$pageTitle = "My Profile";

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
    My Profile | Medicine Aapki Gaw Mein
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


/* SIDEBAR */

.sidebar {
    position: fixed;
    top: 0;
    left: 0;

    width: 250px;
    height: 100vh;

    background:
        linear-gradient(
            180deg,
            #1f8b38,
            #166b2d
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
        1px solid
        rgba(255,255,255,.15);
}

.brand-icon {
    width: 45px;
    height: 45px;

    border-radius: 12px;

    background:
        rgba(255,255,255,.16);

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 24px;
}

.brand-text h2 {
    font-size: 16px;
    font-weight: 600;
}

.brand-text span {
    display: block;

    margin-top: 3px;

    font-size: 11px;

    opacity: .75;
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

    opacity: .55;
}

.menu-item {
    display: flex;

    align-items: center;

    gap: 12px;

    padding: 12px 13px;

    margin-bottom: 5px;

    border-radius: 9px;

    font-size: 13px;

    transition: .2s;
}

.menu-item:hover {
    background:
        rgba(255,255,255,.10);
}

.menu-item.active {
    background:
        rgba(255,255,255,.18);

    font-weight: 500;
}

.menu-icon {
    width: 22px;
    text-align: center;
    font-size: 16px;
}


/* MAIN */

.main {
    margin-left: 250px;
    min-height: 100vh;
}


/* TOPBAR */

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
}

.profile-info span {
    display: block;

    font-size: 11px;

    color: #909a94;

    margin-top: 2px;
}


/* CONTENT */

.content {
    padding: 30px;
}


/* PROFILE HEADER */

.profile-header {
    background:
        linear-gradient(
            110deg,
            #238b39,
            #51b848
        );

    color: #fff;

    border-radius: 15px;

    padding: 30px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    margin-bottom: 20px;
}

.profile-main {
    display: flex;

    align-items: center;

    gap: 18px;
}

.big-avatar {
    width: 72px;
    height: 72px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.18);

    border:
        2px solid
        rgba(255,255,255,.3);

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 27px;

    font-weight: 600;
}

.profile-header h2 {
    font-size: 22px;
    font-weight: 500;

    margin-bottom: 5px;
}

.profile-header p {
    font-size: 11px;
    opacity: .85;
}


/* GRID */

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
        rgba(30,50,35,.03);

    overflow: hidden;
}

.card + .card {
    margin-top: 20px;
}

.card-header {
    padding: 18px 20px;

    border-bottom:
        1px solid #edf0ee;
}

.card-header h3 {
    font-size: 15px;
    font-weight: 500;
}

.card-body {
    padding: 20px;
}


/* INFO */

.info-grid {
    display: grid;

    grid-template-columns:
        repeat(2,1fr);

    gap: 20px;
}

.info-item label {
    display: block;

    font-size: 10px;

    color: #929b95;

    margin-bottom: 6px;
}

.info-item strong {
    display: block;

    font-size: 12px;

    font-weight: 400;

    color: #39453e;

    line-height: 1.6;
}

.info-item.full {
    grid-column: 1/-1;
}


/* BUTTON */

.btn {
    display: inline-flex;

    align-items: center;
    justify-content: center;

    padding: 10px 15px;

    border-radius: 8px;

    font-size: 10px;

    font-weight: 500;

    transition: .2s;
}

.btn-primary {
    background: #238b39;
    color: #fff;
}

.btn-primary:hover {
    background: #1b7130;
}

.btn-light {
    background: #eaf7ed;
    color: #238b39;
}

.btn-light:hover {
    background: #238b39;
    color: #fff;
}


/* SIDE */

.side-item {
    display: flex;

    align-items: center;

    gap: 12px;

    padding: 13px 0;

    border-bottom:
        1px solid #edf0ee;
}

.side-item:last-child {
    border-bottom: 0;
}

.side-icon {
    width: 40px;
    height: 40px;

    border-radius: 9px;

    background: #e9f7ec;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 17px;
}

.side-item strong {
    display: block;

    font-size: 11px;

    font-weight: 500;

    color: #38443c;
}

.side-item span {
    display: block;

    font-size: 9px;

    color: #929b95;

    margin-top: 3px;
}

.action-buttons {
    display: flex;

    gap: 8px;

    flex-wrap: wrap;

    margin-top: 20px;
}


/* RESPONSIVE */

@media(max-width:1100px) {

    .page-grid {
        grid-template-columns: 1fr;
    }

}

@media(max-width:850px) {

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

    .main {
        margin-left: 70px;
    }

    .content {
        padding: 20px;
    }

}

@media(max-width:600px) {

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

    .profile-header {
        padding: 22px;
    }

    .big-avatar {
        width: 58px;
        height: 58px;
    }

    .profile-header h2 {
        font-size: 18px;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .info-item.full {
        grid-column: auto;
    }

}

</style>

</head>

<body>


<!-- SIDEBAR -->

<aside class="sidebar">

<div class="brand">

<div class="brand-icon">💊</div>

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

<a href="index.php" class="menu-item">

<span class="menu-icon">🏠</span>

<span>Dashboard</span>

</a>

<a href="orders.php" class="menu-item">

<span class="menu-icon">📦</span>

<span>My Orders</span>

</a>

<a href="../medicines.php" class="menu-item">

<span class="menu-icon">💊</span>

<span>Browse Medicines</span>

</a>

<a href="../cart.php" class="menu-item">

<span class="menu-icon">🛒</span>

<span>My Cart</span>

</a>


<div class="menu-title">
Prescription
</div>

<a href="prescriptions.php" class="menu-item">

<span class="menu-icon">📋</span>

<span>My Prescriptions</span>

</a>

<a href="upload-prescription.php" class="menu-item">

<span class="menu-icon">📤</span>

<span>Upload Prescription</span>

</a>


<div class="menu-title">
Account
</div>

<a
    href="profile.php"
    class="menu-item active"
>

<span class="menu-icon">👤</span>

<span>My Profile</span>

</a>

<a href="addresses.php" class="menu-item">

<span class="menu-icon">📍</span>

<span>My Addresses</span>

</a>

<a href="change-password.php" class="menu-item">

<span class="menu-icon">🔐</span>

<span>Change Password</span>

</a>


<div class="menu-title">
More
</div>

<a href="../index.php" class="menu-item">

<span class="menu-icon">🌐</span>

<span>View Website</span>

</a>

<a href="../logout.php" class="menu-item">

<span class="menu-icon">🚪</span>

<span>Logout</span>

</a>

</nav>

</aside>


<!-- MAIN -->

<main class="main">


<header class="topbar">

<div class="topbar-title">

<h1>
My Profile
</h1>

<p>
Manage your personal information
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


<section class="content">


<div class="profile-header">

<div class="profile-main">

<div class="big-avatar">
<?= e($firstLetter) ?>
</div>

<div>

<h2>
<?= e($customer['name']) ?>
</h2>

<p>
Customer • Member since <?= e($memberSince) ?>
</p>

</div>

</div>


<a
    href="edit-profile.php"
    class="btn btn-light"
>
✏️ Edit Profile
</a>

</div>


<div class="page-grid">


<div>


<div class="card">

<div class="card-header">

<h3>
Personal Information
</h3>

</div>


<div class="card-body">

<div class="info-grid">


<div class="info-item">

<label>
Full Name
</label>

<strong>
<?= e($customer['name']) ?>
</strong>

</div>


<div class="info-item">

<label>
Mobile Number
</label>

<strong>
<?= e($customer['mobile']) ?>
</strong>

</div>


<div class="info-item">

<label>
Email Address
</label>

<strong>
<?= e(
    $customer['email']
    ?: 'Not provided'
) ?>
</strong>

</div>


<div class="info-item">

<label>
Account Status
</label>

<strong>
<?= !empty($customer['status'])
    ? 'Active'
    : 'Inactive'
?>
</strong>

</div>


</div>


<div class="action-buttons">

<a
    href="edit-profile.php"
    class="btn btn-primary"
>
✏️ Edit Profile
</a>

<a
    href="change-password.php"
    class="btn btn-light"
>
🔐 Change Password
</a>

</div>

</div>

</div>


<div class="card">

<div class="card-header">

<h3>
Address Information
</h3>

</div>


<div class="card-body">

<div class="info-grid">


<div class="info-item full">

<label>
Address
</label>

<strong>
<?= e(
    $customer['address']
    ?: 'Not provided'
) ?>
</strong>

</div>


<div class="info-item">

<label>
City
</label>

<strong>
<?= e(
    $customer['city']
    ?: 'Not provided'
) ?>
</strong>

</div>


<div class="info-item">

<label>
State
</label>

<strong>
<?= e(
    $customer['state']
    ?: 'Not provided'
) ?>
</strong>

</div>


<div class="info-item">

<label>
Pincode
</label>

<strong>
<?= e(
    $customer['pincode']
    ?: 'Not provided'
) ?>
</strong>

</div>


</div>

</div>

</div>


</div>


<div>


<div class="card">

<div class="card-header">

<h3>
Account
</h3>

</div>


<div class="card-body">


<div class="side-item">

<div class="side-icon">
📱
</div>

<div>

<strong>
Mobile Number
</strong>

<span>
<?= e($customer['mobile']) ?>
</span>

</div>

</div>


<div class="side-item">

<div class="side-icon">
📧
</div>

<div>

<strong>
Email
</strong>

<span>
<?= e(
    $customer['email']
    ?: 'Not provided'
) ?>
</span>

</div>

</div>


<div class="side-item">

<div class="side-icon">
📅
</div>

<div>

<strong>
Member Since
</strong>

<span>
<?= e($memberSince) ?>
</span>

</div>

</div>


</div>

</div>


<div class="card">

<div class="card-header">

<h3>
Quick Actions
</h3>

</div>


<div class="card-body">


<a
    href="addresses.php"
    class="side-item"
>

<div class="side-icon">
📍
</div>

<div>

<strong>
My Addresses
</strong>

<span>
Manage delivery addresses
</span>

</div>

</a>


<a
    href="orders.php"
    class="side-item"
>

<div class="side-icon">
📦
</div>

<div>

<strong>
My Orders
</strong>

<span>
View your orders
</span>

</div>

</a>


<a
    href="../cart.php"
    class="side-item"
>

<div class="side-icon">
🛒
</div>

<div>

<strong>
My Cart
</strong>

<span>
View shopping cart
</span>

</div>

</a>


</div>

</div>


</div>


</div>

</section>

</main>

</body>

</html>