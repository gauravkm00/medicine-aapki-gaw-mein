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


$customerId =
    (int) $_SESSION['user_id'];


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
// CUSTOMER
// =====================================================

$customerName =
    $_SESSION['name'] ?? 'Customer';


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
// MESSAGE
// =====================================================

$success = '';

$error = '';


// =====================================================
// DELETE ADDRESS
// =====================================================

if (
    isset($_GET['delete']) &&
    (int) $_GET['delete'] > 0
) {

    $addressId =
        (int) $_GET['delete'];


    $stmt = $conn->prepare("
        DELETE FROM addresses
        WHERE id = ?
        AND user_id = ?
    ");


    if ($stmt) {

        $stmt->bind_param(
            "ii",
            $addressId,
            $customerId
        );

        if ($stmt->execute()) {

            $success =
                'Address deleted successfully.';

        } else {

            $error =
                'Unable to delete address.';
        }

        $stmt->close();
    }
}


// =====================================================
// SET DEFAULT
// =====================================================

if (
    isset($_GET['default']) &&
    (int) $_GET['default'] > 0
) {

    $addressId =
        (int) $_GET['default'];


    // Remove previous default

    $stmt = $conn->prepare("
        UPDATE addresses
        SET is_default = 0
        WHERE user_id = ?
    ");

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $customerId
        );

        $stmt->execute();

        $stmt->close();
    }


    // Set selected default

    $stmt = $conn->prepare("
        UPDATE addresses
        SET is_default = 1
        WHERE id = ?
        AND user_id = ?
    ");

    if ($stmt) {

        $stmt->bind_param(
            "ii",
            $addressId,
            $customerId
        );

        if ($stmt->execute()) {

            $success =
                'Default address updated successfully.';

        } else {

            $error =
                'Unable to update default address.';
        }

        $stmt->close();
    }
}


// =====================================================
// GET ADDRESSES
// =====================================================

$addresses = [];

$stmt = $conn->prepare("
    SELECT
        id,
        address_type,
        full_name,
        mobile,
        address,
        city,
        state,
        pincode,
        is_default,
        created_at
    FROM addresses
    WHERE user_id = ?
    ORDER BY
        is_default DESC,
        id DESC
");


if ($stmt) {

    $stmt->bind_param(
        "i",
        $customerId
    );

    $stmt->execute();

    $result =
        $stmt->get_result();


    if ($result) {

        while (
            $row =
            $result->fetch_assoc()
        ) {

            $addresses[] =
                $row;
        }
    }

    $stmt->close();
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
My Addresses | Medicine Aapki Gaw Mein
</title>

<link
    href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
>


<style>

* {
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body {
    font-family:'Rubik',sans-serif;

    background:#f5f7f6;

    color:#26332a;
}

a {
    text-decoration:none;
    color:inherit;
}


/* SIDEBAR */

.sidebar {
    position:fixed;

    top:0;
    left:0;

    width:250px;
    height:100vh;

    background:
        linear-gradient(
            180deg,
            #1f8b38,
            #166b2d
        );

    color:#fff;

    z-index:1000;

    overflow-y:auto;
}

.brand {
    padding:25px 20px;

    display:flex;

    align-items:center;

    gap:13px;

    border-bottom:
        1px solid
        rgba(255,255,255,.15);
}

.brand-icon {
    width:45px;
    height:45px;

    border-radius:12px;

    background:
        rgba(255,255,255,.16);

    display:flex;

    align-items:center;
    justify-content:center;

    font-size:24px;
}

.brand-text h2 {
    font-size:16px;
    font-weight:600;
}

.brand-text span {
    display:block;

    margin-top:3px;

    font-size:11px;

    opacity:.75;
}

.menu {
    padding:20px 12px;
}

.menu-title {
    padding:
        12px 12px 8px;

    font-size:10px;

    font-weight:600;

    letter-spacing:1px;

    text-transform:uppercase;

    opacity:.55;
}

.menu-item {
    display:flex;

    align-items:center;

    gap:12px;

    padding:12px 13px;

    margin-bottom:5px;

    border-radius:9px;

    font-size:13px;

    transition:.2s;
}

.menu-item:hover {
    background:
        rgba(255,255,255,.10);
}

.menu-item.active {
    background:
        rgba(255,255,255,.18);

    font-weight:500;
}

.menu-icon {
    width:22px;

    text-align:center;

    font-size:16px;
}


/* MAIN */

.main {
    margin-left:250px;

    min-height:100vh;
}


/* TOPBAR */

.topbar {
    height:75px;

    background:#fff;

    display:flex;

    align-items:center;

    justify-content:space-between;

    padding:0 30px;

    border-bottom:
        1px solid #e8ece9;
}

.topbar-title h1 {
    font-size:21px;
    font-weight:600;
}

.topbar-title p {
    margin-top:3px;

    font-size:12px;

    color:#89938c;
}

.profile {
    display:flex;

    align-items:center;

    gap:10px;
}

.profile-avatar {
    width:39px;
    height:39px;

    border-radius:50%;

    background:#e4f4e7;

    color:#238b39;

    display:flex;

    align-items:center;
    justify-content:center;

    font-size:15px;

    font-weight:600;
}

.profile-info strong {
    display:block;

    font-size:13px;

    font-weight:500;
}

.profile-info span {
    display:block;

    font-size:11px;

    color:#909a94;

    margin-top:2px;
}


/* CONTENT */

.content {
    padding:30px;
}


/* PAGE HEADER */

.page-header {
    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:15px;

    margin-bottom:20px;
}

.page-header h2 {
    font-size:20px;

    font-weight:500;

    color:#29362e;
}

.page-header p {
    margin-top:4px;

    font-size:11px;

    color:#929b95;
}


/* BUTTON */

.btn {
    display:inline-flex;

    align-items:center;

    justify-content:center;

    gap:6px;

    padding:10px 14px;

    border-radius:8px;

    font-size:10px;

    font-weight:500;

    transition:.2s;

    border:0;

    cursor:pointer;
}

.btn-primary {
    background:#238b39;

    color:#fff;
}

.btn-primary:hover {
    background:#1b7130;
}

.btn-light {
    background:#eaf7ed;

    color:#238b39;
}

.btn-light:hover {
    background:#238b39;

    color:#fff;
}

.btn-danger {
    background:#fff0f0;

    color:#b83a3a;
}

.btn-danger:hover {
    background:#b83a3a;
    color:#fff;
}


/* ALERT */

.alert {
    padding:13px 16px;

    border-radius:9px;

    margin-bottom:18px;

    font-size:10px;
}

.alert.success {
    background:#e9f7ec;

    border:1px solid #cce8d1;

    color:#237238;
}

.alert.error {
    background:#fff0f0;

    border:1px solid #ffd7d7;

    color:#a43d3d;
}


/* ADDRESS GRID */

.address-grid {
    display:grid;

    grid-template-columns:
        repeat(2,minmax(0,1fr));

    gap:20px;
}


/* CARD */

.address-card {
    background:#fff;

    border:1px solid #e7ece8;

    border-radius:13px;

    padding:20px;

    box-shadow:
        0 2px 8px
        rgba(30,50,35,.03);

    position:relative;
}

.address-card.default {
    border-color:#8dcb98;

    box-shadow:
        0 0 0 2px
        #e6f5e9;
}


/* CARD TOP */

.address-top {
    display:flex;

    align-items:flex-start;

    justify-content:space-between;

    gap:10px;

    margin-bottom:15px;
}

.address-type {
    display:flex;

    align-items:center;

    gap:9px;
}

.address-type-icon {
    width:38px;
    height:38px;

    border-radius:9px;

    background:#e8f6eb;

    display:flex;

    align-items:center;
    justify-content:center;

    font-size:17px;
}

.address-type strong {
    display:block;

    font-size:12px;

    font-weight:500;

    color:#354139;
}

.address-type span {
    display:block;

    font-size:9px;

    color:#929b95;

    margin-top:3px;
}

.default-badge {
    padding:5px 8px;

    border-radius:15px;

    background:#e5f5e9;

    color:#238b39;

    font-size:8px;

    font-weight:500;
}


/* DETAILS */

.address-details {
    padding-top:14px;

    border-top:
        1px solid #edf0ee;
}

.address-details strong {
    display:block;

    font-size:12px;

    font-weight:500;

    color:#354139;

    margin-bottom:7px;
}

.address-details p {
    font-size:10px;

    color:#7e8982;

    line-height:1.7;
}

.address-mobile {
    margin-top:8px;

    font-size:10px;

    color:#7e8982;
}


/* ACTIONS */

.address-actions {
    display:flex;

    gap:7px;

    flex-wrap:wrap;

    margin-top:16px;

    padding-top:14px;

    border-top:
        1px solid #edf0ee;
}

.address-actions .btn {
    font-size:9px;

    padding:8px 10px;
}


/* EMPTY */

.empty {
    background:#fff;

    border:1px solid #e7ece8;

    border-radius:13px;

    padding:50px 25px;

    text-align:center;
}

.empty-icon {
    width:65px;
    height:65px;

    margin:0 auto 15px;

    border-radius:50%;

    background:#e8f6eb;

    display:flex;

    align-items:center;
    justify-content:center;

    font-size:28px;
}

.empty h3 {
    font-size:16px;

    font-weight:500;

    color:#39453e;

    margin-bottom:6px;
}

.empty p {
    font-size:10px;

    color:#929b95;

    margin-bottom:18px;
}


/* RESPONSIVE */

@media(max-width:1000px) {

    .address-grid {
        grid-template-columns:1fr;
    }

}

@media(max-width:850px) {

    .sidebar {
        width:70px;
    }

    .brand {
        justify-content:center;

        padding:18px 10px;
    }

    .brand-text,
    .menu-title,
    .menu-item span:not(.menu-icon) {
        display:none;
    }

    .menu-item {
        justify-content:center;
    }

    .main {
        margin-left:70px;
    }

    .content {
        padding:20px;
    }

}

@media(max-width:600px) {

    .content {
        padding:15px;
    }

    .topbar {
        height:68px;

        padding:0 15px;
    }

    .topbar-title h1 {
        font-size:17px;
    }

    .topbar-title p,
    .profile-info {
        display:none;
    }

    .page-header {
        align-items:flex-start;

        flex-direction:column;
    }

    .page-header .btn {
        width:100%;
    }

}

</style>

</head>


<body>


<!-- SIDEBAR -->

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


<a href="index.php" class="menu-item">

<span class="menu-icon">🏠</span>

<span>
Dashboard
</span>

</a>


<a href="orders.php" class="menu-item">

<span class="menu-icon">📦</span>

<span>
My Orders
</span>

</a>


<a href="../medicines.php" class="menu-item">

<span class="menu-icon">💊</span>

<span>
Browse Medicines
</span>

</a>


<a href="../cart.php" class="menu-item">

<span class="menu-icon">🛒</span>

<span>
My Cart
</span>

</a>


<div class="menu-title">
Prescription
</div>


<a href="prescriptions.php" class="menu-item">

<span class="menu-icon">📋</span>

<span>
My Prescriptions
</span>

</a>


<a href="upload-prescription.php" class="menu-item">

<span class="menu-icon">📤</span>

<span>
Upload Prescription
</span>

</a>


<div class="menu-title">
Account
</div>


<a href="profile.php" class="menu-item">

<span class="menu-icon">👤</span>

<span>
My Profile
</span>

</a>


<a
    href="addresses.php"
    class="menu-item active"
>

<span class="menu-icon">📍</span>

<span>
My Addresses
</span>

</a>


<a href="change-password.php" class="menu-item">

<span class="menu-icon">🔐</span>

<span>
Change Password
</span>

</a>


<div class="menu-title">
More
</div>


<a href="../index.php" class="menu-item">

<span class="menu-icon">🌐</span>

<span>
View Website
</span>

</a>


<a href="../logout.php" class="menu-item">

<span class="menu-icon">🚪</span>

<span>
Logout
</span>

</a>


</nav>

</aside>


<!-- MAIN -->

<main class="main">


<header class="topbar">


<div class="topbar-title">

<h1>
My Addresses
</h1>

<p>
Manage your delivery addresses
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


<div class="page-header">


<div>

<h2>
Saved Addresses
</h2>

<p>
Choose where you want your medicines delivered.
</p>

</div>


<a
    href="add-address.php"
    class="btn btn-primary"
>
＋ Add New Address
</a>


</div>


<?php if ($success !== ''): ?>

<div class="alert success">
<?= e($success) ?>
</div>

<?php endif; ?>


<?php if ($error !== ''): ?>

<div class="alert error">
<?= e($error) ?>
</div>

<?php endif; ?>


<?php if (!empty($addresses)): ?>


<div class="address-grid">


<?php foreach ($addresses as $address): ?>


<?php

$type =
    strtolower(
        trim(
            (string)
            $address['address_type']
        )
    );


$typeIcon = '📍';


if ($type === 'home') {
    $typeIcon = '🏠';
}
elseif ($type === 'work') {
    $typeIcon = '🏢';
}

?>


<div
    class="address-card
    <?= !empty($address['is_default'])
        ? 'default'
        : ''
    ?>"
>


<div class="address-top">


<div class="address-type">


<div class="address-type-icon">

<?= e($typeIcon) ?>

</div>


<div>

<strong>
<?= e(
    $address['address_type']
) ?>
</strong>

<span>
Saved Address
</span>

</div>


</div>


<?php if (
    !empty($address['is_default'])
): ?>

<span class="default-badge">
Default
</span>

<?php endif; ?>


</div>


<div class="address-details">


<strong>
<?= e($address['full_name']) ?>
</strong>


<p>

<?= e($address['address']) ?>

<br>

<?= e($address['city']) ?>,
<?= e($address['state']) ?>
-
<?= e($address['pincode']) ?>

</p>


<div class="address-mobile">

📱 <?= e($address['mobile']) ?>

</div>


</div>


<div class="address-actions">


<a
    href="edit-address.php?id=<?= (int) $address['id'] ?>"
    class="btn btn-light"
>
✏️ Edit
</a>


<?php if (
    empty($address['is_default'])
): ?>

<a
    href="addresses.php?default=<?= (int) $address['id'] ?>"
    class="btn btn-light"
>
⭐ Set Default
</a>

<?php endif; ?>


<a
    href="addresses.php?delete=<?= (int) $address['id'] ?>"
    class="btn btn-danger"
    onclick="
        return confirm(
            'Are you sure you want to delete this address?'
        );
    "
>
🗑 Delete
</a>


</div>


</div>


<?php endforeach; ?>


</div>


<?php else: ?>


<div class="empty">


<div class="empty-icon">
📍
</div>


<h3>
No Saved Addresses
</h3>


<p>
You have not added any delivery address yet.
Add an address to make checkout faster.
</p>


<a
    href="add-address.php"
    class="btn btn-primary"
>
＋ Add New Address
</a>


</div>


<?php endif; ?>


</section>


</main>


</body>

</html>