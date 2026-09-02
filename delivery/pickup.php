<?php

session_start();

require_once "../config/database.php";


// =====================================================
// DELIVERY BOY AUTHENTICATION
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
$deliveryBoyName = $_SESSION['name'] ?? 'Delivery Boy';


// =====================================================
// DELIVERY ID
// =====================================================

$deliveryId = isset($_POST['delivery_id'])
    ? (int)$_POST['delivery_id']
    : (int)($_GET['delivery_id'] ?? 0);

if ($deliveryId <= 0) {
    $_SESSION['delivery_error'] = "Invalid delivery request.";
    header("Location: orders.php");
    exit;
}


// =====================================================
// FETCH DELIVERY
// =====================================================

$sql = "
    SELECT
        d.id AS delivery_id,
        d.order_id,
        d.delivery_person_id,
        d.status AS delivery_status,
        d.assigned_at,
        d.picked_up_at,

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

    WHERE d.id = ?
      AND d.delivery_person_id = ?

    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database error.");
}

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $deliveryId,
    $deliveryBoyId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$delivery = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


// =====================================================
// DELIVERY NOT FOUND
// =====================================================

if (!$delivery) {
    $_SESSION['delivery_error'] =
        "Delivery not found or this delivery is not assigned to you.";

    header("Location: orders.php");
    exit;
}


// =====================================================
// PROCESS PICKUP
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
     * Only ASSIGNED delivery can be picked up.
     */

    if ($delivery['delivery_status'] !== 'assigned') {

        $_SESSION['delivery_error'] =
            "This delivery cannot be picked up because its current status is " .
            str_replace('_', ' ', $delivery['delivery_status']) . ".";

        header(
            "Location: order-details.php?delivery_id=" .
            $deliveryId
        );

        exit;
    }


    // =================================================
    // TRANSACTION
    // =================================================

    mysqli_begin_transaction($conn);

    try {

        // ---------------------------------------------
        // UPDATE DELIVERY STATUS
        // ---------------------------------------------

        $updateDeliverySql = "
            UPDATE deliveries

            SET
                status = 'picked_up',
                picked_up_at = NOW(),
                updated_at = NOW()

            WHERE id = ?
              AND delivery_person_id = ?
              AND status = 'assigned'
        ";

        $updateDeliveryStmt = mysqli_prepare(
            $conn,
            $updateDeliverySql
        );

        if (!$updateDeliveryStmt) {
            throw new Exception(
                "Unable to prepare delivery update."
            );
        }

        mysqli_stmt_bind_param(
            $updateDeliveryStmt,
            "ii",
            $deliveryId,
            $deliveryBoyId
        );

        if (!mysqli_stmt_execute($updateDeliveryStmt)) {
            mysqli_stmt_close($updateDeliveryStmt);

            throw new Exception(
                "Unable to update delivery status."
            );
        }

        $affectedRows = mysqli_stmt_affected_rows(
            $updateDeliveryStmt
        );

        mysqli_stmt_close($updateDeliveryStmt);


        // ---------------------------------------------
        // VERIFY UPDATE
        // ---------------------------------------------

        if ($affectedRows !== 1) {

            throw new Exception(
                "Delivery status could not be changed. " .
                "It may have already been updated."
            );
        }


        // ---------------------------------------------
        // COMMIT
        // ---------------------------------------------

        mysqli_commit($conn);


        // ---------------------------------------------
        // SUCCESS
        // ---------------------------------------------

        $_SESSION['delivery_success'] =
            "Order #" .
            $delivery['order_number'] .
            " has been marked as picked up successfully.";

        header(
            "Location: order-details.php?delivery_id=" .
            $deliveryId
        );

        exit;

    } catch (Throwable $e) {

        mysqli_rollback($conn);

        $_SESSION['delivery_error'] =
            $e->getMessage();

        header(
            "Location: order-details.php?delivery_id=" .
            $deliveryId
        );

        exit;
    }
}


// =====================================================
// FLASH MESSAGES
// =====================================================

$successMessage = $_SESSION['delivery_success'] ?? '';
$errorMessage   = $_SESSION['delivery_error'] ?? '';

unset($_SESSION['delivery_success']);
unset($_SESSION['delivery_error']);


// =====================================================
// STATUS
// =====================================================

$currentStatus = $delivery['delivery_status'];

$statusLabel = ucwords(
    str_replace(
        '_',
        ' ',
        $currentStatus
    )
);

$statusClass = str_replace(
    '_',
    '-',
    strtolower($currentStatus)
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
        Pickup Order | Delivery Boy
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
            font-family: 'Rubik', sans-serif;
            background: #f5f7f8;
            color: #263238;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .layout {
            min-height: 100vh;
            display: flex;
        }


        /* =================================================
           SIDEBAR
        ================================================= */

        .sidebar {
            width: 250px;
            background: linear-gradient(
                180deg,
                #0b8f55,
                #087443
            );

            color: #fff;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            padding: 25px 18px;
        }

        .logo {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo-icon {
            width: 58px;
            height: 58px;
            margin: auto;
            background: #fff;
            color: #0b8f55;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 700;
        }

        .logo h2 {
            margin-top: 12px;
            font-size: 18px;
        }

        .logo p {
            font-size: 12px;
            opacity: .8;
            margin-top: 4px;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .menu a {
            padding: 13px 15px;
            border-radius: 9px;
            font-size: 14px;
            transition: .2s;
        }

        .menu a:hover,
        .menu a.active {
            background: rgba(255,255,255,.15);
        }


        /* =================================================
           MAIN
        ================================================= */

        .main {
            margin-left: 250px;
            width: calc(100% - 250px);
            min-height: 100vh;
        }


        /* =================================================
           TOPBAR
        ================================================= */

        .topbar {
            height: 70px;
            background: #fff;
            border-bottom: 1px solid #e5e7e9;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 30px;
        }

        .topbar h3 {
            font-size: 18px;
        }

        .user-box {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #e9f8f1;
            color: #087443;

            display: flex;
            align-items: center;
            justify-content: center;

            font-weight: 600;
        }

        .user-name {
            font-size: 14px;
            font-weight: 500;
        }


        /* =================================================
           CONTENT
        ================================================= */

        .content {
            padding: 30px;
            max-width: 1100px;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            font-size: 26px;
            margin-bottom: 7px;
        }

        .page-header p {
            color: #718096;
            font-size: 14px;
        }


        /* =================================================
           ALERTS
        ================================================= */

        .alert {
            padding: 14px 17px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #e8f8ef;
            color: #087443;
            border: 1px solid #bce8d0;
        }

        .alert-error {
            background: #fff0f1;
            color: #b42318;
            border: 1px solid #f4c7cb;
        }


        /* =================================================
           CARD
        ================================================= */

        .card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e7eaec;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 18px rgba(0,0,0,.04);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 25px;
        }

        .order-title h2 {
            font-size: 21px;
            margin-bottom: 7px;
        }

        .order-title p {
            font-size: 13px;
            color: #718096;
        }


        /* =================================================
           STATUS
        ================================================= */

        .status {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-assigned {
            background: #fff7df;
            color: #a87900;
        }

        .status-picked-up {
            background: #eef4ff;
            color: #315ea8;
        }

        .status-out-for-delivery {
            background: #eaf7ff;
            color: #08739b;
        }

        .status-delivered {
            background: #e8f8ef;
            color: #087443;
        }

        .status-failed {
            background: #fff0f1;
            color: #b42318;
        }

        .status-cancelled {
            background: #f1f3f5;
            color: #6b7280;
        }


        /* =================================================
           INFO GRID
        ================================================= */

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .info-item {
            background: #f8faf9;
            padding: 16px;
            border-radius: 10px;
        }

        .info-label {
            color: #718096;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .info-value {
            font-size: 14px;
            font-weight: 500;
            line-height: 1.5;
        }


        /* =================================================
           PICKUP BOX
        ================================================= */

        .pickup-box {
            text-align: center;
            background: #f0fbf6;
            border: 1px solid #cceedd;
            border-radius: 14px;
            padding: 30px;
        }

        .pickup-icon {
            width: 65px;
            height: 65px;
            margin: 0 auto 15px;

            background: #0b8f55;
            color: #fff;

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 28px;
        }

        .pickup-box h3 {
            font-size: 20px;
            margin-bottom: 8px;
        }

        .pickup-box p {
            color: #718096;
            font-size: 14px;
            margin-bottom: 22px;
        }


        /* =================================================
           BUTTONS
        ================================================= */

        .btn-row {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            border: 0;
            cursor: pointer;
            padding: 12px 20px;
            border-radius: 9px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
        }

        .btn-primary {
            background: #0b8f55;
            color: #fff;
        }

        .btn-primary:hover {
            background: #087443;
        }

        .btn-secondary {
            background: #edf0f2;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #e1e5e8;
        }


        /* =================================================
           NOTE
        ================================================= */

        .note {
            margin-top: 20px;
            padding: 14px;
            background: #fff8e7;
            border: 1px solid #f4dfaa;
            border-radius: 9px;
            color: #8a6800;
            font-size: 13px;
            line-height: 1.6;
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 900px) {

            .sidebar {
                width: 220px;
            }

            .main {
                margin-left: 220px;
                width: calc(100% - 220px);
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                min-height: auto;
            }

            .layout {
                display: block;
            }

            .main {
                margin-left: 0;
                width: 100%;
            }

            .menu {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .menu a {
                flex: 1;
                min-width: 120px;
                text-align: center;
            }

            .topbar {
                padding: 0 18px;
            }

            .content {
                padding: 20px;
            }

            .order-header {
                flex-direction: column;
            }
        }

        @media (max-width: 500px) {

            .user-name {
                display: none;
            }

            .content {
                padding: 15px;
            }

            .card {
                padding: 18px;
            }

            .pickup-box {
                padding: 22px 15px;
            }
        }

    </style>

</head>

<body>

<div class="layout">


    <!-- =================================================
         SIDEBAR
    ================================================= -->

    <aside class="sidebar">

        <div class="logo">

            <div class="logo-icon">
                💊
            </div>

            <h2>
                Medicine Aapki Gaw Mein
            </h2>

            <p>
                Delivery Panel
            </p>

        </div>


        <nav class="menu">

            <a href="index.php">
                🏠 Dashboard
            </a>

            <a href="orders.php" class="active">
                📦 My Orders
            </a>

            <a href="profile.php">
                👤 Profile
            </a>

            <a href="logout.php">
                🚪 Logout
            </a>

        </nav>

    </aside>


    <!-- =================================================
         MAIN
    ================================================= -->

    <main class="main">


        <!-- TOPBAR -->

        <header class="topbar">

            <h3>
                Pickup Order
            </h3>

            <div class="user-box">

                <div class="avatar">
                    <?= e(strtoupper(substr($deliveryBoyName, 0, 1))) ?>
                </div>

                <div class="user-name">
                    <?= e($deliveryBoyName) ?>
                </div>

            </div>

        </header>


        <!-- CONTENT -->

        <section class="content">


            <div class="page-header">

                <h1>
                    Pickup Order
                </h1>

                <p>
                    Confirm that you have collected this order from the pickup location.
                </p>

            </div>


            <?php if ($successMessage): ?>

                <div class="alert alert-success">
                    <?= e($successMessage) ?>
                </div>

            <?php endif; ?>


            <?php if ($errorMessage): ?>

                <div class="alert alert-error">
                    <?= e($errorMessage) ?>
                </div>

            <?php endif; ?>


            <!-- =================================================
                 ORDER SUMMARY
            ================================================= -->

            <div class="card">

                <div class="order-header">

                    <div class="order-title">

                        <h2>
                            Order #<?= e($delivery['order_number']) ?>
                        </h2>

                        <p>
                            Order placed on
                            <?= date(
                                'd M Y, h:i A',
                                strtotime($delivery['created_at'])
                            ) ?>
                        </p>

                    </div>

                    <span
                        class="status status-<?= e($statusClass) ?>"
                    >
                        <?= e($statusLabel) ?>
                    </span>

                </div>


                <div class="info-grid">


                    <div class="info-item">

                        <div class="info-label">
                            Customer
                        </div>

                        <div class="info-value">
                            <?= e($delivery['customer_name']) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            Mobile
                        </div>

                        <div class="info-value">
                            <?= e($delivery['customer_mobile']) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            Delivery Address
                        </div>

                        <div class="info-value">

                            <?= e($delivery['delivery_address']) ?>

                            <br>

                            <?= e($delivery['city']) ?>,
                            <?= e($delivery['state']) ?> -
                            <?= e($delivery['pincode']) ?>

                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            Order Amount
                        </div>

                        <div class="info-value">
                            ₹<?= number_format(
                                (float)$delivery['total_amount'],
                                2
                            ) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            Payment Method
                        </div>

                        <div class="info-value">
                            <?= e(
                                strtoupper(
                                    (string)$delivery['payment_method']
                                )
                            ) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            Payment Status
                        </div>

                        <div class="info-value">
                            <?= e(
                                ucwords(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $delivery['payment_status']
                                    )
                                )
                            ) ?>
                        </div>

                    </div>


                </div>

            </div>


            <!-- =================================================
                 PICKUP ACTION
            ================================================= -->

            <div class="card">

                <?php if ($currentStatus === 'assigned'): ?>

                    <div class="pickup-box">

                        <div class="pickup-icon">
                            📦
                        </div>

                        <h3>
                            Ready for Pickup
                        </h3>

                        <p>
                            Please collect the order from the pickup
                            location and confirm below.
                        </p>


                        <form
                            method="POST"
                            onsubmit="return confirm(
                                'Have you physically collected this order?'
                            );"
                        >

                            <input
                                type="hidden"
                                name="delivery_id"
                                value="<?= (int)$deliveryId ?>"
                            >

                            <div class="btn-row">

                                <a
                                    href="order-details.php?delivery_id=<?= (int)$deliveryId ?>"
                                    class="btn btn-secondary"
                                >
                                    ← Back
                                </a>

                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >
                                    ✓ Confirm Pickup
                                </button>

                            </div>

                        </form>


                        <div class="note">

                            <strong>Important:</strong>
                            Only confirm pickup after you have actually
                            received the order from the pickup location.

                        </div>

                    </div>


                <?php elseif ($currentStatus === 'picked_up'): ?>

                    <div class="pickup-box">

                        <div class="pickup-icon">
                            ✓
                        </div>

                        <h3>
                            Order Already Picked Up
                        </h3>

                        <p>
                            This order has already been marked as picked up.
                        </p>

                        <div class="btn-row">

                            <a
                                href="order-details.php?delivery_id=<?= (int)$deliveryId ?>"
                                class="btn btn-primary"
                            >
                                View Order
                            </a>

                        </div>

                    </div>


                <?php else: ?>

                    <div class="pickup-box">

                        <div class="pickup-icon">
                            ℹ
                        </div>

                        <h3>
                            Pickup Not Available
                        </h3>

                        <p>
                            This delivery is currently
                            <strong><?= e($statusLabel) ?></strong>
                            and cannot be picked up.
                        </p>

                        <div class="btn-row">

                            <a
                                href="order-details.php?delivery_id=<?= (int)$deliveryId ?>"
                                class="btn btn-primary"
                            >
                                View Order
                            </a>

                        </div>

                    </div>

                <?php endif; ?>

            </div>


        </section>

    </main>

</div>

</body>

</html>