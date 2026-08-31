```php
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
// GET DELIVERY BOY ID
// =====================================================

$id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit;
}


// =====================================================
// FETCH DELIVERY BOY
// =====================================================

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        name,
        mobile,
        email,
        role,
        address,
        city,
        state,
        pincode,
        status,
        created_at,
        updated_at
     FROM users
     WHERE id = ?
     AND role = 'delivery'
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$deliveryBoy = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


// =====================================================
// NOT FOUND
// =====================================================

if (!$deliveryBoy) {
    header("Location: index.php");
    exit;
}


// =====================================================
// DATA
// =====================================================

$name       = $deliveryBoy['name'];
$mobile     = $deliveryBoy['mobile'];
$email      = $deliveryBoy['email'];
$address    = $deliveryBoy['address'];
$city       = $deliveryBoy['city'];
$state      = $deliveryBoy['state'];
$pincode    = $deliveryBoy['pincode'];
$status     = (int)$deliveryBoy['status'];
$createdAt  = $deliveryBoy['created_at'];
$updatedAt  = $deliveryBoy['updated_at'];


// =====================================================
// INITIAL
// =====================================================

$initial = strtoupper(
    substr(
        trim($name ?: 'D'),
        0,
        1
    )
);


// =====================================================
// PAGE TITLE
// =====================================================

$pageTitle = "Delivery Boy Details";

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
}


/* =====================================================
   HEADER
===================================================== */

.page-header {
    background: #fff;
    border-bottom: 1px solid #e9edf3;
    padding: 18px 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.back-btn {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    background: #eaf7ec;
    color: #238b39;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 600;
}

.header-title h1 {
    font-size: 20px;
    color: #222;
}

.header-title p {
    color: #999;
    font-size: 11px;
    margin-top: 4px;
}

.header-actions {
    display: flex;
    gap: 8px;
}


/* =====================================================
   BUTTONS
===================================================== */

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: #238b39;
    color: #fff;
}

.btn-primary:hover {
    background: #1d7530;
}

.btn-light {
    background: #eef2f5;
    color: #555;
}

.btn-danger {
    background: #ffe7e9;
    color: #c62839;
}


/* =====================================================
   CONTENT
===================================================== */

.content {
    padding: 30px;
    max-width: 1100px;
    margin: auto;
}


/* =====================================================
   PROFILE CARD
===================================================== */

.profile-card {
    background: #fff;
    border: 1px solid #edf0f4;
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 20px;
}

.profile-cover {
    height: 125px;
    background:
        linear-gradient(
            135deg,
            #4caf43,
            #238b39
        );
    position: relative;
}

.profile-cover::after {
    content: "";
    position: absolute;
    width: 180px;
    height: 180px;
    border: 25px solid rgba(255,255,255,.07);
    border-radius: 50%;
    right: -60px;
    top: -70px;
}

.profile-main {
    padding: 0 25px 25px;
    position: relative;
}

.profile-avatar {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: #eaf7ec;
    color: #238b39;
    border: 5px solid #fff;
    position: relative;
    margin-top: -45px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 700;
    box-shadow: 0 3px 12px rgba(0,0,0,.08);
}

.profile-info {
    margin-top: 12px;
}

.profile-info h2 {
    font-size: 22px;
    color: #222;
}

.profile-info p {
    color: #999;
    font-size: 12px;
    margin-top: 5px;
}

.status-badge {
    display: inline-block;
    margin-top: 12px;
    padding: 6px 11px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
}

.status-active {
    background: #dff5e3;
    color: #14752f;
}

.status-inactive {
    background: #ffe3e6;
    color: #a51d2d;
}


/* =====================================================
   GRID
===================================================== */

.details-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
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
    padding: 16px 20px;
    border-bottom: 1px solid #edf0f4;
}

.panel-header h3 {
    font-size: 14px;
    color: #222;
}

.panel-body {
    padding: 5px 20px 15px;
}


/* =====================================================
   DETAIL ROW
===================================================== */

.detail-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    padding: 14px 0;
    border-bottom: 1px solid #f0f2f5;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    color: #999;
    font-size: 11px;
    min-width: 110px;
}

.detail-value {
    color: #333;
    font-size: 12px;
    font-weight: 500;
    text-align: right;
    word-break: break-word;
}


/* =====================================================
   CONTACT
===================================================== */

.contact-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 0;
    border-bottom: 1px solid #f0f2f5;
}

.contact-item:last-child {
    border-bottom: none;
}

.contact-icon {
    width: 38px;
    height: 38px;
    border-radius: 9px;
    background: #eaf7ec;
    color: #238b39;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.contact-text strong {
    display: block;
    font-size: 12px;
}

.contact-text span {
    display: block;
    color: #888;
    font-size: 11px;
    margin-top: 3px;
    word-break: break-word;
}


/* =====================================================
   ACTION PANEL
===================================================== */

.action-panel {
    margin-top: 20px;
    background: #fff;
    border: 1px solid #edf0f4;
    border-radius: 12px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
}

.action-panel h3 {
    font-size: 14px;
    color: #222;
}

.action-panel p {
    color: #999;
    font-size: 11px;
    margin-top: 4px;
}

.actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}


/* =====================================================
   MOBILE
===================================================== */

@media (max-width: 700px) {

    .page-header {
        padding: 15px;
    }

    .content {
        padding: 15px;
    }

    .header-title h1 {
        font-size: 17px;
    }

    .header-title p {
        display: none;
    }

    .header-actions .btn-light {
        display: none;
    }

    .details-grid {
        grid-template-columns: 1fr;
    }

    .profile-main {
        padding-left: 18px;
        padding-right: 18px;
    }

    .action-panel {
        display: block;
    }

    .actions {
        margin-top: 14px;
    }

    .detail-row {
        display: block;
    }

    .detail-value {
        text-align: left;
        margin-top: 5px;
    }

}

</style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="page-header">

    <div class="header-left">

        <a
            href="index.php"
            class="back-btn"
            title="Back"
        >
            ←
        </a>

        <div class="header-title">

            <h1>
                Delivery Boy Details
            </h1>

            <p>
                View complete delivery partner information
            </p>

        </div>

    </div>


    <div class="header-actions">

        <a
            href="edit.php?id=<?= $id ?>"
            class="btn btn-primary"
        >
            ✏️ Edit
        </a>

        <a
            href="index.php"
            class="btn btn-light"
        >
            Back
        </a>

    </div>

</header>


<!-- =====================================================
     CONTENT
===================================================== -->

<div class="content">


<!-- =====================================================
     PROFILE
===================================================== -->

<div class="profile-card">

    <div class="profile-cover"></div>

    <div class="profile-main">

        <div class="profile-avatar">

            <?= e($initial) ?>

        </div>


        <div class="profile-info">

            <h2>
                <?= e($name) ?>
            </h2>

            <p>
                🚚 Delivery Partner
            </p>


            <?php if ($status === 1): ?>

                <span class="status-badge status-active">
                    ● Active
                </span>

            <?php else: ?>

                <span class="status-badge status-inactive">
                    ● Inactive
                </span>

            <?php endif; ?>

        </div>

    </div>

</div>


<!-- =====================================================
     DETAILS GRID
===================================================== -->

<div class="details-grid">


<!-- =====================================================
     PERSONAL INFORMATION
===================================================== -->

<div class="panel">

    <div class="panel-header">

        <h3>
            Personal Information
        </h3>

    </div>


    <div class="panel-body">


        <div class="detail-row">

            <span class="detail-label">
                Full Name
            </span>

            <span class="detail-value">
                <?= e($name) ?>
            </span>

        </div>


        <div class="detail-row">

            <span class="detail-label">
                Role
            </span>

            <span class="detail-value">
                Delivery Boy
            </span>

        </div>


        <div class="detail-row">

            <span class="detail-label">
                Account Status
            </span>

            <span class="detail-value">

                <?php if ($status === 1): ?>

                    <span style="color:#14752f;">
                        Active
                    </span>

                <?php else: ?>

                    <span style="color:#a51d2d;">
                        Inactive
                    </span>

                <?php endif; ?>

            </span>

        </div>


        <div class="detail-row">

            <span class="detail-label">
                Member Since
            </span>

            <span class="detail-value">

                <?= date(
                    "d M Y, h:i A",
                    strtotime($createdAt)
                ) ?>

            </span>

        </div>


        <?php if (!empty($updatedAt)): ?>

            <div class="detail-row">

                <span class="detail-label">
                    Last Updated
                </span>

                <span class="detail-value">

                    <?= date(
                        "d M Y, h:i A",
                        strtotime($updatedAt)
                    ) ?>

                </span>

            </div>

        <?php endif; ?>


    </div>

</div>


<!-- =====================================================
     CONTACT INFORMATION
===================================================== -->

<div class="panel">

    <div class="panel-header">

        <h3>
            Contact Information
        </h3>

    </div>


    <div class="panel-body">


        <div class="contact-item">

            <div class="contact-icon">
                📱
            </div>

            <div class="contact-text">

                <strong>
                    Mobile Number
                </strong>

                <span>
                    <?= e($mobile) ?>
                </span>

            </div>

        </div>


        <?php if (!empty($email)): ?>

            <div class="contact-item">

                <div class="contact-icon">
                    ✉️
                </div>

                <div class="contact-text">

                    <strong>
                        Email Address
                    </strong>

                    <span>
                        <?= e($email) ?>
                    </span>

                </div>

            </div>

        <?php endif; ?>


        <?php if (!empty($address)): ?>

            <div class="contact-item">

                <div class="contact-icon">
                    🏠
                </div>

                <div class="contact-text">

                    <strong>
                        Address
                    </strong>

                    <span>
                        <?= nl2br(e($address)) ?>
                    </span>

                </div>

            </div>

        <?php endif; ?>


    </div>

</div>


<!-- =====================================================
     LOCATION INFORMATION
===================================================== -->

<div class="panel">

    <div class="panel-header">

        <h3>
            Location Information
        </h3>

    </div>


    <div class="panel-body">


        <div class="detail-row">

            <span class="detail-label">
                City
            </span>

            <span class="detail-value">
                <?= e($city ?: 'Not provided') ?>
            </span>

        </div>


        <div class="detail-row">

            <span class="detail-label">
                State
            </span>

            <span class="detail-value">
                <?= e($state ?: 'Not provided') ?>
            </span>

        </div>


        <div class="detail-row">

            <span class="detail-label">
                Pincode
            </span>

            <span class="detail-value">
                <?= e($pincode ?: 'Not provided') ?>
            </span>

        </div>


        <div class="detail-row">

            <span class="detail-label">
                Full Address
            </span>

            <span class="detail-value">

                <?= !empty($address)
                    ? nl2br(e($address))
                    : 'Not provided'
                ?>

            </span>

        </div>


    </div>

</div>


<!-- =====================================================
     ACCOUNT INFORMATION
===================================================== -->

<div class="panel">

    <div class="panel-header">

        <h3>
            Account Information
        </h3>

    </div>


    <div class="panel-body">


        <div class="detail-row">

            <span class="detail-label">
                User ID
            </span>

            <span class="detail-value">
                #<?= e($id) ?>
            </span>

        </div>


        <div class="detail-row">

            <span class="detail-label">
                User Role
            </span>

            <span class="detail-value">
                <?= e($deliveryBoy['role']) ?>
            </span>

        </div>


        <div class="detail-row">

            <span class="detail-label">
                Created
            </span>

            <span class="detail-value">

                <?= date(
                    "d M Y",
                    strtotime($createdAt)
                ) ?>

            </span>

        </div>


        <div class="detail-row">

            <span class="detail-label">
                Status
            </span>

            <span class="detail-value">

                <?= $status === 1
                    ? 'Active'
                    : 'Inactive'
                ?>

            </span>

        </div>


    </div>

</div>


</div>


<!-- =====================================================
     ACTIONS
===================================================== -->

<div class="action-panel">

    <div>

        <h3>
            Manage Delivery Boy
        </h3>

        <p>
            Update account information or change account status.
        </p>

    </div>


    <div class="actions">

        <a
            href="edit.php?id=<?= $id ?>"
            class="btn btn-primary"
        >
            ✏️ Edit Details
        </a>


        <a
            href="toggle-status.php?id=<?= $id ?>"
            class="btn btn-light"
            onclick="
                return confirm(
                    'Are you sure you want to change this delivery boy status?'
                );
            "
        >

            <?= $status === 1
                ? '⏸️ Deactivate'
                : '▶️ Activate'
            ?>

        </a>


        <a
            href="delete.php?id=<?= $id ?>"
            class="btn btn-danger"
            onclick="
                return confirm(
                    'Are you sure you want to delete this delivery boy?'
                );
            "
        >
            🗑️ Delete
        </a>

    </div>

</div>


</div>


</body>

</html>
```
