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
// DELIVERY BOY
// =====================================================

$deliveryBoyId =
    (int)$_SESSION['user_id'];

$deliveryBoyName =
    $_SESSION['name'] ?? 'Delivery Boy';


// =====================================================
// DELIVERY ID
// =====================================================

$deliveryId =
    isset($_GET['delivery_id'])
        ? (int)$_GET['delivery_id']
        : (int)($_POST['delivery_id'] ?? 0);


if ($deliveryId <= 0) {

    header("Location: orders.php");
    exit;

}


// =====================================================
// FETCH DELIVERY + ORDER
// =====================================================

$delivery = null;


$sql = "
    SELECT

        d.id AS delivery_id,
        d.order_id,
        d.delivery_person_id,
        d.status AS delivery_status,

        d.delivery_otp,
        d.delivery_note,
        d.failure_reason,

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

        o.order_status

    FROM deliveries d

    INNER JOIN orders o
        ON o.id = d.order_id

    WHERE d.id = ?
    AND d.delivery_person_id = ?

    LIMIT 1
";


$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );


if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $deliveryId,
        $deliveryBoyId
    );


    mysqli_stmt_execute(
        $stmt
    );


    $result =
        mysqli_stmt_get_result(
            $stmt
        );


    if ($result) {

        $delivery =
            mysqli_fetch_assoc(
                $result
            );

    }


    mysqli_stmt_close(
        $stmt
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

                width: 100%;

                max-width: 500px;

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
                Delivery Not Found
            </h2>


            <p>

                This delivery does not exist or
                it is not assigned to you.

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
// CURRENT STATUS
// =====================================================

$currentStatus =
    strtolower(
        trim(
            $delivery['delivery_status']
            ?? ''
        )
    );


// =====================================================
// ONLY OUT FOR DELIVERY CAN BE DELIVERED
// =====================================================

if ($currentStatus !== 'out_for_delivery') {

    header(
        "Location: order-details.php?delivery_id=" .
        $deliveryId
    );

    exit;

}


// =====================================================
// ORDER DATA
// =====================================================

$orderId =
    (int)$delivery['order_id'];


$orderNumber =
    $delivery['order_number']
    ?? $orderId;


$customerName =
    $delivery['customer_name']
    ?? 'Customer';


$customerMobile =
    $delivery['customer_mobile']
    ?? '';


$address =
    $delivery['delivery_address']
    ?? '';


$city =
    $delivery['city']
    ?? '';


$state =
    $delivery['state']
    ?? '';


$pincode =
    $delivery['pincode']
    ?? '';


$totalAmount =
    (float)(
        $delivery['total_amount']
        ?? 0
    );


$paymentMethod =
    strtolower(
        trim(
            $delivery['payment_method']
            ?? 'cod'
        )
    );


$paymentStatus =
    strtolower(
        trim(
            $delivery['payment_status']
            ?? 'pending'
        )
    );


$isCod =
    ($paymentMethod === 'cod');


// =====================================================
// OTP MESSAGE
// =====================================================

$error = '';


// =====================================================
// SUCCESS MESSAGE
// =====================================================

$success =
    $_SESSION['delivery_success']
    ?? '';


unset(
    $_SESSION['delivery_success']
);


// =====================================================
// VERIFY OTP + COMPLETE DELIVERY
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    // =================================================
    // OTP
    // =================================================

    $otp =
        trim(
            $_POST['otp'] ?? ''
        );


    // =================================================
    // COD CASH CONFIRMATION
    // =================================================

    $cashReceived =
        isset(
            $_POST['cash_received']
        );


    // =================================================
    // BASIC OTP VALIDATION
    // =================================================

    if ($otp === '') {

        $error =
            "Please enter the delivery OTP.";

    }


    elseif (
        !preg_match(
            '/^[0-9]{6}$/',
            $otp
        )
    ) {

        $error =
            "Please enter a valid 6-digit OTP.";

    }


    // =================================================
    // OTP AVAILABLE CHECK
    // =================================================

    elseif (
        empty(
            $delivery['delivery_otp']
        )
    ) {

        $error =
            "Delivery OTP is not available for this order. Please contact admin.";

    }


    // =================================================
    // OTP VERIFY
    // =================================================

    elseif (
        !hash_equals(
            (string)$delivery['delivery_otp'],
            (string)$otp
        )
    ) {

        $error =
            "Invalid OTP. Please ask the customer for the correct OTP.";

    }


    // =================================================
    // COD CASH CHECK
    // =================================================

    elseif (
        $isCod &&
        !$cashReceived
    ) {

        $error =
            "Please confirm that the COD cash payment has been received.";

    }


    // =================================================
    // ALL VALID
    // =================================================

    else {


        mysqli_begin_transaction(
            $conn
        );


        try {


            // =============================================
            // RE-CHECK DELIVERY STATUS
            // Prevent duplicate/double completion
            // =============================================

            $lockDeliverySql = "
                SELECT
                    id,
                    order_id,
                    status,
                    delivery_otp

                FROM deliveries

                WHERE id = ?
                AND delivery_person_id = ?

                LIMIT 1

                FOR UPDATE
            ";


            $lockDeliveryStmt =
                mysqli_prepare(
                    $conn,
                    $lockDeliverySql
                );


            if (!$lockDeliveryStmt) {

                throw new Exception(
                    "Unable to verify delivery status."
                );

            }


            mysqli_stmt_bind_param(
                $lockDeliveryStmt,
                "ii",
                $deliveryId,
                $deliveryBoyId
            );


            if (
                !mysqli_stmt_execute(
                    $lockDeliveryStmt
                )
            ) {

                mysqli_stmt_close(
                    $lockDeliveryStmt
                );

                throw new Exception(
                    "Unable to verify delivery."
                );

            }


            $lockResult =
                mysqli_stmt_get_result(
                    $lockDeliveryStmt
                );


            $lockedDelivery =
                $lockResult
                    ? mysqli_fetch_assoc(
                        $lockResult
                    )
                    : null;


            mysqli_stmt_close(
                $lockDeliveryStmt
            );


            if (!$lockedDelivery) {

                throw new Exception(
                    "Delivery was not found or is not assigned to you."
                );

            }


            if (
                strtolower(
                    trim(
                        $lockedDelivery['status']
                    )
                ) !== 'out_for_delivery'
            ) {

                throw new Exception(
                    "This delivery has already been completed or its status has changed."
                );

            }


            // =============================================
            // RE-CHECK OTP FROM DATABASE
            // =============================================

            $databaseOtp =
                (string)(
                    $lockedDelivery['delivery_otp']
                    ?? ''
                );


            if (
                $databaseOtp === ''
            ) {

                throw new Exception(
                    "Delivery OTP is not available for this order."
                );

            }


            if (
                !hash_equals(
                    $databaseOtp,
                    (string)$otp
                )
            ) {

                throw new Exception(
                    "Invalid OTP. Please ask the customer for the correct OTP."
                );

            }


            // =============================================
            // UPDATE DELIVERY
            // =============================================

            $deliveryUpdateSql = "
                UPDATE deliveries

                SET
                    status = 'delivered',
                    delivered_at = NOW(),
                    updated_at = NOW()

                WHERE id = ?
                AND delivery_person_id = ?
                AND status = 'out_for_delivery'
            ";


            $deliveryUpdateStmt =
                mysqli_prepare(
                    $conn,
                    $deliveryUpdateSql
                );


            if (!$deliveryUpdateStmt) {

                throw new Exception(
                    "Unable to prepare delivery update."
                );

            }


            mysqli_stmt_bind_param(
                $deliveryUpdateStmt,
                "ii",
                $deliveryId,
                $deliveryBoyId
            );


            if (
                !mysqli_stmt_execute(
                    $deliveryUpdateStmt
                )
            ) {

                mysqli_stmt_close(
                    $deliveryUpdateStmt
                );

                throw new Exception(
                    "Unable to complete delivery."
                );

            }


            $affectedRows =
                mysqli_stmt_affected_rows(
                    $deliveryUpdateStmt
                );


            mysqli_stmt_close(
                $deliveryUpdateStmt
            );


            if ($affectedRows !== 1) {

                throw new Exception(
                    "Delivery status has already changed or delivery is no longer available."
                );

            }


            // =============================================
            // UPDATE ORDER STATUS
            // =============================================

            $orderUpdateSql = "
                UPDATE orders

                SET
                    order_status = 'delivered',
                    updated_at = NOW()

                WHERE id = ?
            ";


            $orderUpdateStmt =
                mysqli_prepare(
                    $conn,
                    $orderUpdateSql
                );


            if (!$orderUpdateStmt) {

                throw new Exception(
                    "Unable to prepare order update."
                );

            }


            mysqli_stmt_bind_param(
                $orderUpdateStmt,
                "i",
                $orderId
            );


            if (
                !mysqli_stmt_execute(
                    $orderUpdateStmt
                )
            ) {

                mysqli_stmt_close(
                    $orderUpdateStmt
                );

                throw new Exception(
                    "Unable to update order status."
                );

            }


            $orderAffectedRows =
                mysqli_stmt_affected_rows(
                    $orderUpdateStmt
                );


            mysqli_stmt_close(
                $orderUpdateStmt
            );


            // =============================================
            // COD PAYMENT UPDATE
            // =============================================

            if ($isCod) {


                /*
                 * COD order:
                 *
                 * pending → paid
                 *
                 * Only change payment status when it is
                 * still pending.
                 */


                $paymentUpdateSql = "
                    UPDATE orders

                    SET
                        payment_status = 'paid',
                        updated_at = NOW()

                    WHERE id = ?
                    AND payment_status = 'pending'
                ";


                $paymentUpdateStmt =
                    mysqli_prepare(
                        $conn,
                        $paymentUpdateSql
                    );


                if (!$paymentUpdateStmt) {

                    throw new Exception(
                        "Unable to prepare COD payment update."
                    );

                }


                mysqli_stmt_bind_param(
                    $paymentUpdateStmt,
                    "i",
                    $orderId
                );


                if (
                    !mysqli_stmt_execute(
                        $paymentUpdateStmt
                    )
                ) {

                    mysqli_stmt_close(
                        $paymentUpdateStmt
                    );

                    throw new Exception(
                        "Unable to update COD payment status."
                    );

                }


                $paymentAffectedRows =
                    mysqli_stmt_affected_rows(
                        $paymentUpdateStmt
                    );


                mysqli_stmt_close(
                    $paymentUpdateStmt
                );


                /*
                 * If it was already paid, that's okay.
                 *
                 * We do not fail the delivery.
                 */


            }


            // =============================================
            // COMMIT
            // =============================================

            mysqli_commit(
                $conn
            );


            // =============================================
            // SUCCESS MESSAGE
            // =============================================

            if ($isCod) {

                $_SESSION['delivery_success'] =
                    "Order #" .
                    $orderNumber .
                    " delivered successfully and COD payment marked as paid.";

            } else {

                $_SESSION['delivery_success'] =
                    "Order #" .
                    $orderNumber .
                    " delivered successfully.";

            }


            // =============================================
            // REDIRECT
            // =============================================

            header(
                "Location: order-details.php?delivery_id=" .
                $deliveryId
            );

            exit;


        } catch (
            Throwable $e
        ) {


            mysqli_rollback(
                $conn
            );


            $error =
                $e->getMessage();

        }

    }

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


if ($firstLetter === '') {

    $firstLetter = 'D';

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

    Complete Delivery |

    <?= e($orderNumber) ?>

    |

    Medicine Aapki Gaw Mein

</title>


<link
    href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap"
    rel="stylesheet"
>


<style>

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
   CARD
===================================================== */

.card {

    background: #fff;

    border:
        1px solid #edf0f4;

    border-radius: 14px;

    overflow: hidden;

    max-width: 700px;

    margin: 0 auto;

}


.card-header {

    padding: 20px;

    border-bottom:
        1px solid #edf0f4;

}


.card-header h2 {

    font-size: 18px;

    color: #222;

}


.card-header p {

    color: #999;

    font-size: 11px;

    margin-top: 6px;

}


/* =====================================================
   ORDER INFO
===================================================== */

.order-info {

    padding: 20px;

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 15px;

}


.info-box {

    background: #fafbfc;

    border:
        1px solid #edf0f4;

    border-radius: 9px;

    padding: 13px;

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

    font-weight: 500;

    line-height: 1.5;

}


.full {

    grid-column: 1 / -1;

}


/* =====================================================
   ALERT
===================================================== */

.alert {

    padding: 13px 16px;

    border-radius: 9px;

    margin: 20px 20px 0;

    font-size: 12px;

    font-weight: 500;

}


.alert-error {

    background: #fff0f1;

    color: #a51d2d;

    border:
        1px solid #f2ced2;

}


.alert-success {

    background: #eaf8ed;

    color: #197333;

    border:
        1px solid #cdebd3;

}


/* =====================================================
   OTP
===================================================== */

.otp-section {

    padding: 20px;

    border-top:
        1px solid #edf0f4;

}


.otp-title {

    text-align: center;

    margin-bottom: 15px;

}


.otp-title .icon {

    font-size: 35px;

    margin-bottom: 8px;

}


.otp-title h3 {

    font-size: 16px;

    color: #222;

}


.otp-title p {

    color: #888;

    font-size: 11px;

    line-height: 1.6;

    margin-top: 5px;

}


/* =====================================================
   OTP INPUT
===================================================== */

.otp-input {

    width: 100%;

    height: 55px;

    border:
        1px solid #dfe4e8;

    border-radius: 9px;

    text-align: center;

    font-family: inherit;

    font-size: 24px;

    font-weight: 700;

    letter-spacing: 8px;

    outline: none;

}


.otp-input:focus {

    border-color: #51b848;

    box-shadow:
        0 0 0 3px
        rgba(81,184,72,.10);

}


/* =====================================================
   COD PAYMENT
===================================================== */

.cod-section {

    margin-top: 15px;

    padding: 15px;

    background: #fff8e8;

    border:
        1px solid #f1dfad;

    border-radius: 9px;

}


.cod-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

    margin-bottom: 8px;

}


.cod-header strong {

    color: #6e5510;

    font-size: 12px;

}


.cod-amount {

    color: #8a6a16;

    font-size: 15px;

    font-weight: 700;

}


.cod-text {

    color: #806b31;

    font-size: 10px;

    line-height: 1.6;

    margin-bottom: 12px;

}


/* =====================================================
   CHECKBOX
===================================================== */

.cash-check {

    display: flex;

    align-items: flex-start;

    gap: 10px;

    padding: 11px;

    background: #fff;

    border:
        1px solid #eadfbf;

    border-radius: 8px;

    cursor: pointer;

}


.cash-check input {

    width: 17px;

    height: 17px;

    margin-top: 1px;

    accent-color: #238b39;

    cursor: pointer;

    flex-shrink: 0;

}


.cash-check span {

    color: #5e512f;

    font-size: 11px;

    line-height: 1.5;

    font-weight: 500;

}


/* =====================================================
   VERIFY BUTTON
===================================================== */

.verify-btn {

    width: 100%;

    height: 46px;

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


.verify-btn:hover {

    background: #1b7330;

}


/* =====================================================
   SECURITY NOTE
===================================================== */

.security-note {

    margin-top: 14px;

    padding: 12px;

    background: #f4f9f5;

    border:
        1px solid #dceee0;

    border-radius: 8px;

    color: #46704e;

    font-size: 10px;

    line-height: 1.5;

    text-align: center;

}


/* =====================================================
   RESPONSIVE
===================================================== */

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


    .order-info {

        grid-template-columns: 1fr;

    }


    .full {

        grid-column: auto;

    }


    .delivery-info {

        display: none;

    }


    .topbar-title h1 {

        font-size: 18px;

    }


    .otp-input {

        font-size: 20px;

        letter-spacing: 5px;

    }


    .cod-header {

        align-items: flex-start;

        flex-direction: column;

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


<header class="topbar">


    <div class="topbar-title">

        <h1>
            Complete Delivery
        </h1>

        <p>
            Verify customer OTP to complete order
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


<div class="content">


<div class="back-row">

    <a
        href="order-details.php?delivery_id=<?= (int)$deliveryId ?>"
        class="back-btn"
    >

        ← Back to Order

    </a>

</div>


<div class="card">


    <div class="card-header">

        <h2>
            🚚 Complete Order Delivery
        </h2>

        <p>
            Order #<?= e($orderNumber) ?>
        </p>

    </div>


    <?php if ($error !== ''): ?>

        <div class="alert alert-error">

            ⚠

            <?= e($error) ?>

        </div>

    <?php endif; ?>


    <?php if ($success !== ''): ?>

        <div class="alert alert-success">

            ✓

            <?= e($success) ?>

        </div>

    <?php endif; ?>


    <!-- =================================================
         ORDER INFORMATION
    ================================================= -->

    <div class="order-info">


        <div class="info-box">

            <div class="info-label">
                Customer
            </div>

            <div class="info-value">

                <?= e($customerName) ?>

            </div>

        </div>


        <div class="info-box">

            <div class="info-label">
                Order Total
            </div>

            <div class="info-value">

                ₹<?= number_format(
                    $totalAmount,
                    2
                ) ?>

            </div>

        </div>


        <div class="info-box">

            <div class="info-label">
                Mobile
            </div>

            <div class="info-value">

                <?php if ($customerMobile !== ''): ?>

                    <a
                        href="tel:<?= e($customerMobile) ?>"
                        style="color:#238b39;"
                    >

                        📱

                        <?= e($customerMobile) ?>

                    </a>

                <?php else: ?>

                    N/A

                <?php endif; ?>

            </div>

        </div>


        <div class="info-box">

            <div class="info-label">
                Payment
            </div>

            <div class="info-value">

                <?= e(
                    strtoupper(
                        $paymentMethod
                    )
                ) ?>

                -

                <?= e(
                    ucfirst(
                        $paymentStatus
                    )
                ) ?>

            </div>

        </div>


        <div class="info-box full">

            <div class="info-label">
                Delivery Address
            </div>

            <div class="info-value">

                <?= e($address) ?>


                <?php if ($city !== ''): ?>

                    <br>

                    <?= e($city) ?>

                <?php endif; ?>


                <?php if ($state !== ''): ?>

                    ,
                    <?= e($state) ?>

                <?php endif; ?>


                <?php if ($pincode !== ''): ?>

                    -
                    <?= e($pincode) ?>

                <?php endif; ?>

            </div>

        </div>


    </div>


    <!-- =================================================
         OTP SECTION
    ================================================= -->

    <div class="otp-section">


        <div class="otp-title">

            <div class="icon">
                🔐
            </div>


            <h3>
                Customer OTP Verification
            </h3>


            <p>

                Ask the customer for the
                <strong>6-digit OTP</strong>
                received on their registered mobile number.

                <br>

                Do not mark the order delivered
                without OTP verification.

            </p>

        </div>


        <form
            method="POST"
            autocomplete="off"
        >


            <input
                type="hidden"
                name="delivery_id"
                value="<?= (int)$deliveryId ?>"
            >


            <input
                type="text"
                name="otp"
                class="otp-input"
                maxlength="6"
                minlength="6"
                inputmode="numeric"
                pattern="[0-9]{6}"
                placeholder="••••••"
                autocomplete="one-time-code"
                required
            >


            <!-- =========================================
                 COD PAYMENT
            ========================================== -->

            <?php if ($isCod): ?>


                <div class="cod-section">


                    <div class="cod-header">


                        <strong>
                            💵 COD Payment Collection
                        </strong>


                        <span class="cod-amount">

                            ₹<?= number_format(
                                $totalAmount,
                                2
                            ) ?>

                        </span>


                    </div>


                    <div class="cod-text">

                        This is a Cash on Delivery order.
                        Collect the exact order amount from
                        the customer before completing delivery.

                    </div>


                    <label class="cash-check">


                        <input
                            type="checkbox"
                            name="cash_received"
                            value="1"
                            required
                        >


                        <span>

                            I confirm that I have received
                            the COD cash payment of
                            <strong>
                                ₹<?= number_format(
                                    $totalAmount,
                                    2
                                ) ?>
                            </strong>
                            from the customer.

                        </span>


                    </label>


                </div>


            <?php endif; ?>


            <button
                type="submit"
                class="verify-btn"
            >

                ✓

                Verify OTP &

                <?= $isCod
                    ? 'Confirm Cash & '
                    : ''
                ?>

                Complete Delivery

            </button>


        </form>


        <div class="security-note">

            🔒

            <?= $isCod
                ? 'OTP verification confirms delivery and the cash confirmation records the COD payment as paid.'
                : 'OTP is used only to confirm that the order has been received by the customer.'
            ?>

        </div>


    </div>


</div>


</div>


</main>


</div>


</body>

</html>