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

$customerId = (int) $_SESSION['user_id'];

$customerName = $_SESSION['name'] ?? 'Customer';


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
// GET ORDER ID
// =====================================================

$orderId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


if ($orderId <= 0) {
    header("Location: orders.php");
    exit;
}


// =====================================================
// GET ORDER + LATEST DELIVERY
//
// IMPORTANT:
// Customer can only access his/her own order.
//
// deliveries table is the source of truth for
// delivery tracking.
// =====================================================

$order = null;

$stmt = $conn->prepare("
    SELECT

        /* =========================
           ORDER INFORMATION
        ========================= */

        o.id AS order_id,
        o.order_number,
        o.user_id,

        o.subtotal,
        o.delivery_charge,
        o.discount,
        o.total_amount,

        o.payment_method,
        o.payment_status,
        o.order_status,

        o.customer_name,
        o.customer_mobile,

        o.delivery_address,
        o.city,
        o.state,
        o.pincode,

        o.customer_note,
        o.admin_note,

        o.created_at AS order_created_at,
        o.updated_at AS order_updated_at,

        /* =========================
           DELIVERY INFORMATION
        ========================= */

        d.id AS delivery_id,
        d.delivery_person_id,
        d.status AS delivery_status,

        d.assigned_at,
        d.picked_up_at,
        d.out_for_delivery_at,
        d.delivered_at,

        d.delivery_otp,
        d.delivery_note,
        d.failure_reason,

        d.created_at AS delivery_created_at,
        d.updated_at AS delivery_updated_at

    FROM orders o

    LEFT JOIN deliveries d
        ON d.order_id = o.id

    WHERE o.id = ?
    AND o.user_id = ?

    ORDER BY d.id DESC

    LIMIT 1
");


if ($stmt) {

    $stmt->bind_param(
        "ii",
        $orderId,
        $customerId
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if (
        $result &&
        $result->num_rows > 0
    ) {
        $order = $result->fetch_assoc();
    }

    $stmt->close();
}


// =====================================================
// ORDER NOT FOUND
// =====================================================

if (!$order) {

    header("Location: orders.php");
    exit;
}


// =====================================================
// PAGE DATA
// =====================================================

$pageTitle = "Track Order";


// =====================================================
// FIRST LETTER
// =====================================================

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
// DELIVERY STATUS
//
// deliveries.status is PRIMARY source.
//
// If no delivery record exists,
// only use order status for a safe fallback.
// =====================================================

$deliveryStatus = strtolower(
    trim(
        (string) (
            $order['delivery_status'] ?? ''
        )
    )
);


// =====================================================
// FALLBACK WHEN DELIVERY RECORD DOES NOT EXIST
// =====================================================

if ($deliveryStatus === '') {

    $orderStatus = strtolower(
        trim(
            (string) (
                $order['order_status'] ?? ''
            )
        )
    );


    switch ($orderStatus) {

        case 'delivered':

            $deliveryStatus = 'delivered';

            break;


        case 'cancelled':

            $deliveryStatus = 'cancelled';

            break;


        case 'pending':
        case 'confirmed':
        case 'processing':
        case 'ready':

            /*
             * Delivery record does not exist yet.
             * Do NOT show "assigned".
             */
            $deliveryStatus = 'pending';

            break;


        default:

            $deliveryStatus = 'pending';

            break;
    }
}


// =====================================================
// STATUS LABEL
// =====================================================

function deliveryStatusLabel($status)
{
    switch (strtolower((string) $status)) {

        case 'pending':
            return 'Pending';

        case 'assigned':
            return 'Assigned';

        case 'picked_up':
            return 'Picked Up';

        case 'out_for_delivery':
            return 'Out for Delivery';

        case 'delivered':
            return 'Delivered';

        case 'failed':
            return 'Delivery Failed';

        case 'cancelled':
            return 'Cancelled';

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
// STATUS DESCRIPTION
// =====================================================

function deliveryStatusDescription($status)
{
    switch (strtolower((string) $status)) {

        case 'pending':
            return 'Your order is waiting for delivery assignment.';

        case 'assigned':
            return 'A delivery person has been assigned to your order.';

        case 'picked_up':
            return 'Your medicine package has been picked up by the delivery person.';

        case 'out_for_delivery':
            return 'Your order is on the way to your delivery address.';

        case 'delivered':
            return 'Your medicine order has been delivered successfully.';

        case 'failed':
            return 'We could not complete the delivery. Please contact support.';

        case 'cancelled':
            return 'This delivery has been cancelled.';

        default:
            return 'Your order is being processed.';
    }
}


// =====================================================
// STATUS ICON
// =====================================================

function deliveryStatusIcon($status)
{
    switch (strtolower((string) $status)) {

        case 'pending':
            return '🕐';

        case 'assigned':
            return '👤';

        case 'picked_up':
            return '📦';

        case 'out_for_delivery':
            return '🚚';

        case 'delivered':
            return '✅';

        case 'failed':
            return '⚠️';

        case 'cancelled':
            return '❌';

        default:
            return '📦';
    }
}


// =====================================================
// FORMAT DATE
// =====================================================

function formatTrackingDate($date)
{
    if (empty($date)) {
        return '';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return '';
    }

    return date(
        'd M Y, h:i A',
        $timestamp
    );
}


// =====================================================
// DELIVERY STEPS
// =====================================================

$deliverySteps = [

    'pending' => [

        'title' => 'Order Ready for Delivery',

        'text' =>
            'Your order is waiting for delivery assignment.',

        'icon' => '🕐',

        'timeField' => null

    ],


    'assigned' => [

        'title' => 'Delivery Person Assigned',

        'text' =>
            'A delivery person has been assigned to your order.',

        'icon' => '👤',

        'timeField' => 'assigned_at'

    ],


    'picked_up' => [

        'title' => 'Order Picked Up',

        'text' =>
            'Your medicine package has been picked up.',

        'icon' => '📦',

        'timeField' => 'picked_up_at'

    ],


    'out_for_delivery' => [

        'title' => 'Out for Delivery',

        'text' =>
            'Your order is on the way to your delivery address.',

        'icon' => '🚚',

        'timeField' => 'out_for_delivery_at'

    ],


    'delivered' => [

        'title' => 'Delivered',

        'text' =>
            'Your medicine has been delivered successfully.',

        'icon' => '✅',

        'timeField' => 'delivered_at'

    ]

];


// =====================================================
// STATUS POSITIONS
// =====================================================

$statusPositions = [

    'pending'          => 1,

    'assigned'         => 2,

    'picked_up'        => 3,

    'out_for_delivery' => 4,

    'delivered'        => 5

];


// =====================================================
// CURRENT POSITION
// =====================================================

$currentPosition =
    $statusPositions[$deliveryStatus]
    ?? 1;


// =====================================================
// ADDRESS
// =====================================================

$addressParts = array_filter(

    [

        $order['delivery_address'] ?? '',

        $order['city'] ?? '',

        $order['state'] ?? '',

        $order['pincode'] ?? ''

    ],

    function ($value) {

        return trim((string) $value) !== '';

    }

);


$fullAddress = implode(
    ', ',
    $addressParts
);


// =====================================================
// ORDER DATE
// =====================================================

$orderDate = formatTrackingDate(
    $order['order_created_at'] ?? ''
);


if ($orderDate === '') {
    $orderDate = '-';
}


// =====================================================
// LAST UPDATED
//
// Prefer delivery updated time.
// Then order updated time.
// Then order created time.
// =====================================================

$lastUpdated = '-';


$updatedSource = '';


if (!empty($order['delivery_updated_at'])) {

    $updatedSource =
        $order['delivery_updated_at'];

}
elseif (!empty($order['order_updated_at'])) {

    $updatedSource =
        $order['order_updated_at'];

}
elseif (!empty($order['order_created_at'])) {

    $updatedSource =
        $order['order_created_at'];
}


if ($updatedSource !== '') {

    $formattedLastUpdated =
        formatTrackingDate(
            $updatedSource
        );

    if ($formattedLastUpdated !== '') {

        $lastUpdated =
            $formattedLastUpdated;
    }
}


// =====================================================
// DELIVERY PERSON ASSIGNED?
// =====================================================

$hasDeliveryPerson =
    !empty(
        $order['delivery_person_id']
    );


// =====================================================
// FAILURE REASON
// =====================================================

$failureReason =
    trim(
        (string) (
            $order['failure_reason']
            ?? ''
        )
    );


// =====================================================
// DELIVERY NOTE
// =====================================================

$deliveryNote =
    trim(
        (string) (
            $order['delivery_note']
            ?? ''
        )
    );


// =====================================================
// PAYMENT METHOD LABEL
// =====================================================

$paymentMethod =
    strtolower(
        trim(
            (string) (
                $order['payment_method']
                ?? ''
            )
        )
    );


$paymentMethodLabel =

    $paymentMethod === 'online'
        ? 'Online Payment'
        : 'Cash on Delivery';


// =====================================================
// PAYMENT STATUS LABEL
// =====================================================

$paymentStatus =
    strtolower(
        trim(
            (string) (
                $order['payment_status']
                ?? ''
            )
        )
    );


$paymentStatusLabel =
    ucfirst($paymentStatus ?: 'pending');

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
        Track Order #<?= e($order['order_number']) ?>
        | Medicine Aapki Gaw Mein
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


        /* =================================================
           SIDEBAR
        ================================================= */

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
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
        }


        .brand {
            padding: 25px 20px;

            display: flex;

            align-items: center;

            gap: 13px;

            border-bottom:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.15
                );
        }


        .brand-icon {
            width: 45px;
            height: 45px;

            border-radius: 12px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.16
                );

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 24px;
        }


        .brand-text h2 {
            font-size: 16px;
            font-weight: 600;
            line-height: 1.3;
        }


        .brand-text span {
            display: block;

            margin-top: 3px;

            font-size: 11px;

            opacity: 0.75;
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

            opacity: 0.55;
        }


        .menu-item {
            display: flex;

            align-items: center;

            gap: 12px;

            padding: 12px 13px;

            margin-bottom: 5px;

            border-radius: 9px;

            font-size: 13px;

            transition: 0.2s;
        }


        .menu-item:hover {
            background:
                rgba(
                    255,
                    255,
                    255,
                    0.10
                );
        }


        .menu-item.active {
            background:
                rgba(
                    255,
                    255,
                    255,
                    0.18
                );

            font-weight: 500;
        }


        .menu-icon {
            width: 22px;
            text-align: center;
            font-size: 16px;
        }


        /* =================================================
           MAIN
        ================================================= */

        .main {
            margin-left: 250px;
            min-height: 100vh;
        }


        /* =================================================
           TOPBAR
        ================================================= */

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

            color: #233129;
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

            color: #29352e;
        }


        .profile-info span {
            display: block;

            font-size: 11px;

            color: #909a94;

            margin-top: 2px;
        }


        /* =================================================
           CONTENT
        ================================================= */

        .content {
            padding: 30px;
        }


        /* =================================================
           BACK
        ================================================= */

        .back-row {
            margin-bottom: 18px;
        }


        .back-btn {
            display: inline-flex;

            align-items: center;

            gap: 7px;

            color: #238b39;

            font-size: 12px;

            font-weight: 500;
        }


        .back-btn:hover {
            color: #176b29;
        }


        /* =================================================
           TRACKING HEADER
        ================================================= */

        .tracking-header {
            background:
                linear-gradient(
                    110deg,
                    #238b39,
                    #51b848
                );

            color: #fff;

            border-radius: 15px;

            padding: 26px 30px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 20px;

            position: relative;

            overflow: hidden;
        }


        .tracking-header::after {
            content: "🚚";

            position: absolute;

            right: 35px;

            top: 50%;

            transform:
                translateY(-50%);

            font-size: 75px;

            opacity: 0.10;
        }


        .tracking-header-left {
            position: relative;
            z-index: 2;
        }


        .tracking-header-left span {
            display: block;

            font-size: 11px;

            opacity: 0.8;

            margin-bottom: 5px;
        }


        .tracking-header-left h2 {
            font-size: 23px;

            font-weight: 500;

            margin-bottom: 6px;
        }


        .tracking-header-left p {
            font-size: 11px;

            opacity: 0.85;
        }


        .tracking-header-right {
            position: relative;

            z-index: 2;

            text-align: right;
        }


        .current-status {
            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding: 8px 13px;

            border-radius: 22px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.18
                );

            font-size: 11px;

            font-weight: 500;
        }


        /* =================================================
           GRID
        ================================================= */

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
                rgba(
                    30,
                    50,
                    35,
                    0.03
                );

            overflow: hidden;
        }


        .card + .card {
            margin-top: 20px;
        }


        .card-header {
            padding: 18px 20px;

            border-bottom:
                1px solid #edf0ee;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 10px;
        }


        .card-header h3 {
            font-size: 15px;

            font-weight: 500;

            color: #29362e;
        }


        /* =================================================
           TRACKING BODY
        ================================================= */

        .tracking-body {
            padding: 25px;
        }


        .tracking-step {
            display: flex;

            gap: 15px;

            position: relative;

            padding-bottom: 28px;
        }


        .tracking-step:last-child {
            padding-bottom: 0;
        }


        .step-icon-area {
            width: 42px;

            flex-shrink: 0;

            position: relative;
        }


        .step-icon {
            width: 42px;
            height: 42px;

            border-radius: 50%;

            background: #edf1ee;

            color: #919a94;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 18px;

            position: relative;

            z-index: 2;
        }


        .tracking-step.completed
        .step-icon {
            background: #e4f5e8;

            color: #238b39;
        }


        .tracking-step.current
        .step-icon {
            background: #238b39;

            color: #fff;

            box-shadow:
                0 0 0 6px #e4f4e7;
        }


        .step-line {
            position: absolute;

            left: 20px;

            top: 42px;

            width: 2px;

            height: calc(100% - 15px);

            background: #e6ebe8;
        }


        .tracking-step.completed
        .step-line {
            background: #86ca94;
        }


        .step-content {
            padding-top: 3px;

            flex: 1;
        }


        .step-content h4 {
            font-size: 13px;

            font-weight: 500;

            color: #87918b;

            margin-bottom: 5px;
        }


        .tracking-step.completed
        .step-content h4 {
            color: #238b39;
        }


        .tracking-step.current
        .step-content h4 {
            color: #238b39;

            font-weight: 600;
        }


        .step-content p {
            font-size: 10px;

            color: #969e99;

            line-height: 1.6;
        }


        .step-time {
            display: inline-block;

            margin-top: 7px;

            font-size: 9px;

            color: #7d8881;
        }


        .current-badge {
            display: inline-block;

            margin-top: 7px;

            margin-right: 5px;

            padding: 4px 8px;

            border-radius: 15px;

            background: #e5f5e9;

            color: #238b39;

            font-size: 9px;

            font-weight: 500;
        }


        /* =================================================
           ALERT
        ================================================= */

        .alert-box {
            margin: 20px;

            padding: 17px;

            border-radius: 10px;

            background: #fff0f0;

            border: 1px solid #ffd7d7;
        }


        .alert-box strong {
            display: block;

            color: #b83a3a;

            font-size: 12px;

            margin-bottom: 5px;
        }


        .alert-box p {
            color: #876b6b;

            font-size: 10px;

            line-height: 1.6;
        }


        .failure-reason {
            margin-top: 10px;

            padding: 10px;

            background: #fff;

            border-radius: 7px;

            border: 1px solid #f2d0d0;

            color: #765d5d;

            font-size: 10px;

            line-height: 1.5;
        }


        /* =================================================
           INFO
        ================================================= */

        .info-body {
            padding: 20px;
        }


        .info-grid {
            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 18px;
        }


        .info-item label {
            display: block;

            font-size: 10px;

            color: #929b95;

            margin-bottom: 5px;
        }


        .info-item strong {
            display: block;

            font-size: 12px;

            font-weight: 400;

            color: #39453e;

            line-height: 1.6;
        }


        .info-item.full {
            grid-column: 1 / -1;
        }


        /* =================================================
           NOTE BOX
        ================================================= */

        .note-box {
            margin-top: 18px;

            padding: 13px;

            background: #f8fcf9;

            border: 1px solid #e1eee4;

            border-radius: 9px;
        }


        .note-box strong {
            display: block;

            color: #238b39;

            font-size: 11px;

            margin-bottom: 5px;
        }


        .note-box p {
            color: #748078;

            font-size: 10px;

            line-height: 1.6;
        }


        /* =================================================
           SUMMARY
        ================================================= */

        .summary-body {
            padding: 20px;
        }


        .summary-row {
            display: flex;

            justify-content: space-between;

            align-items: center;

            padding: 9px 0;

            font-size: 11px;

            color: #7e8982;
        }


        .summary-row strong {
            color: #3c4841;

            font-weight: 500;
        }


        .summary-total {
            border-top:
                1px solid #e6ebe8;

            margin-top: 5px;

            padding-top: 14px;

            display: flex;

            justify-content: space-between;

            align-items: center;
        }


        .summary-total span {
            font-size: 13px;

            color: #354139;

            font-weight: 500;
        }


        .summary-total strong {
            font-size: 18px;

            color: #238b39;

            font-weight: 600;
        }


        /* =================================================
           PAYMENT
        ================================================= */

        .payment-box {
            margin-top: 16px;

            padding-top: 15px;

            border-top:
                1px solid #edf0ee;
        }


        .payment-row {
            display: flex;

            justify-content: space-between;

            gap: 10px;

            font-size: 10px;

            padding: 5px 0;

            color: #7f8983;
        }


        .payment-row strong {
            color: #39453e;

            font-weight: 500;
        }


        /* =================================================
           ADDRESS
        ================================================= */

        .address-body {
            padding: 20px;
        }


        .address-icon {
            width: 43px;
            height: 43px;

            border-radius: 10px;

            background: #e8f6eb;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 20px;

            margin-bottom: 12px;
        }


        .address-body strong {
            display: block;

            font-size: 12px;

            font-weight: 500;

            color: #354139;

            margin-bottom: 7px;
        }


        .address-body p {
            font-size: 10px;

            color: #818b85;

            line-height: 1.7;
        }


        /* =================================================
           ASSIGNMENT INFO
        ================================================= */

        .assignment-box {
            margin-top: 16px;

            padding: 12px;

            background: #f8fcf9;

            border: 1px solid #e1eee4;

            border-radius: 9px;
        }


        .assignment-box strong {
            display: block;

            font-size: 11px;

            color: #238b39;

            margin-bottom: 4px;
        }


        .assignment-box span {
            font-size: 10px;

            color: #7c8780;
        }


        /* =================================================
           QUICK ACTIONS
        ================================================= */

        .quick-actions {
            padding: 18px;
        }


        .quick-action {
            display: flex;

            align-items: center;

            gap: 12px;

            padding: 12px;

            border: 1px solid #edf0ee;

            border-radius: 9px;

            margin-bottom: 10px;

            transition: 0.2s;
        }


        .quick-action:last-child {
            margin-bottom: 0;
        }


        .quick-action:hover {
            background: #f8fcf9;

            border-color: #c5e3ca;
        }


        .quick-icon {
            width: 37px;
            height: 37px;

            border-radius: 9px;

            background: #e9f7ec;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 16px;
        }


        .quick-text strong {
            display: block;

            font-size: 11px;

            font-weight: 500;

            color: #38443c;
        }


        .quick-text span {
            display: block;

            font-size: 9px;

            color: #929b95;

            margin-top: 3px;
        }


        /* =================================================
           BUTTONS
        ================================================= */

        .button-row {
            display: flex;

            gap: 8px;

            flex-wrap: wrap;

            margin-top: 20px;
        }


        .btn {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 10px 14px;

            border-radius: 8px;

            font-size: 10px;

            font-weight: 500;

            transition: 0.2s;
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


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 1100px) {

            .page-grid {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 850px) {

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


            .menu-icon {
                font-size: 18px;
            }


            .main {
                margin-left: 70px;
            }


            .content {
                padding: 20px;
            }


            .topbar {
                padding: 0 20px;
            }

        }


        @media (max-width: 600px) {

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


            .tracking-header {
                display: block;

                padding: 21px;
            }


            .tracking-header-right {
                text-align: left;

                margin-top: 15px;
            }


            .tracking-header::after {
                font-size: 50px;

                right: 10px;
            }


            .tracking-body {
                padding: 20px;
            }


            .info-grid {
                grid-template-columns: 1fr;
            }


            .info-item.full {
                grid-column: auto;
            }


            .card-header {
                align-items: flex-start;

                flex-direction: column;
            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

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


        <a
            href="index.php"
            class="menu-item"
        >

            <span class="menu-icon">
                🏠
            </span>

            <span>
                Dashboard
            </span>

        </a>


        <a
            href="orders.php"
            class="menu-item active"
        >

            <span class="menu-icon">
                📦
            </span>

            <span>
                My Orders
            </span>

        </a>


        <a
            href="../medicines.php"
            class="menu-item"
        >

            <span class="menu-icon">
                💊
            </span>

            <span>
                Browse Medicines
            </span>

        </a>


        <a
            href="../cart.php"
            class="menu-item"
        >

            <span class="menu-icon">
                🛒
            </span>

            <span>
                My Cart
            </span>

        </a>


        <div class="menu-title">
            Prescription
        </div>


        <a
            href="prescriptions.php"
            class="menu-item"
        >

            <span class="menu-icon">
                📋
            </span>

            <span>
                My Prescriptions
            </span>

        </a>


        <a
            href="upload-prescription.php"
            class="menu-item"
        >

            <span class="menu-icon">
                📤
            </span>

            <span>
                Upload Prescription
            </span>

        </a>


        <div class="menu-title">
            Account
        </div>


        <a
            href="profile.php"
            class="menu-item"
        >

            <span class="menu-icon">
                👤
            </span>

            <span>
                My Profile
            </span>

        </a>


        <a
            href="addresses.php"
            class="menu-item"
        >

            <span class="menu-icon">
                📍
            </span>

            <span>
                My Addresses
            </span>

        </a>


        <a
            href="change-password.php"
            class="menu-item"
        >

            <span class="menu-icon">
                🔐
            </span>

            <span>
                Change Password
            </span>

        </a>


        <div class="menu-title">
            More
        </div>


        <a
            href="../index.php"
            class="menu-item"
        >

            <span class="menu-icon">
                🌐
            </span>

            <span>
                View Website
            </span>

        </a>


        <a
            href="../logout.php"
            class="menu-item"
        >

            <span class="menu-icon">
                🚪
            </span>

            <span>
                Logout
            </span>

        </a>


    </nav>

</aside>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


    <!-- =================================================
         TOPBAR
    ================================================= -->

    <header class="topbar">


        <div class="topbar-title">

            <h1>
                Track Order
            </h1>

            <p>
                Follow your medicine delivery
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


    <!-- =================================================
         CONTENT
    ================================================= -->

    <section class="content">


        <!-- =================================================
             BACK
        ================================================= -->

        <div class="back-row">

            <a
                href="order-details.php?id=<?= (int) $order['order_id'] ?>"
                class="back-btn"
            >
                ← Back to Order Details
            </a>

        </div>


        <!-- =================================================
             TRACKING HEADER
        ================================================= -->

        <div class="tracking-header">


            <div class="tracking-header-left">

                <span>
                    Order Number
                </span>


                <h2>
                    #<?= e($order['order_number']) ?>
                </h2>


                <p>
                    Ordered on <?= e($orderDate) ?>
                </p>

            </div>


            <div class="tracking-header-right">

                <span class="current-status">

                    <?= e(
                        deliveryStatusIcon(
                            $deliveryStatus
                        )
                    ) ?>

                    <?= e(
                        deliveryStatusLabel(
                            $deliveryStatus
                        )
                    ) ?>

                </span>

            </div>


        </div>


        <!-- =================================================
             PAGE GRID
        ================================================= -->

        <div class="page-grid">


            <!-- =================================================
                 LEFT COLUMN
            ================================================= -->

            <div>


                <!-- =================================================
                     DELIVERY TRACKING
                ================================================= -->

                <div class="card">


                    <div class="card-header">

                        <h3>
                            Delivery Tracking
                        </h3>


                        <span
                            style="
                                font-size:10px;
                                color:#929b95;
                            "
                        >

                            Last updated:
                            <?= e($lastUpdated) ?>

                        </span>

                    </div>


                    <?php if (
                        $deliveryStatus === 'cancelled'
                    ): ?>


                        <div class="alert-box">

                            <strong>
                                ❌ Delivery Cancelled
                            </strong>


                            <p>
                                This delivery has been cancelled.
                                Please contact our support team
                                if you need more information.
                            </p>


                            <?php if (
                                $failureReason !== ''
                            ): ?>

                                <div class="failure-reason">

                                    <strong
                                        style="
                                            color:#765d5d;
                                            margin-bottom:4px;
                                        "
                                    >
                                        Reason
                                    </strong>

                                    <?= e(
                                        $failureReason
                                    ) ?>

                                </div>

                            <?php endif; ?>

                        </div>


                    <?php elseif (
                        $deliveryStatus === 'failed'
                    ): ?>


                        <div class="alert-box">

                            <strong>
                                ⚠️ Delivery Failed
                            </strong>


                            <p>
                                We were unable to complete your
                                delivery. Please contact our
                                support team for assistance.
                            </p>


                            <?php if (
                                $failureReason !== ''
                            ): ?>

                                <div class="failure-reason">

                                    <strong
                                        style="
                                            color:#765d5d;
                                            margin-bottom:4px;
                                        "
                                    >
                                        Failure Reason
                                    </strong>

                                    <?= e(
                                        $failureReason
                                    ) ?>

                                </div>

                            <?php endif; ?>

                        </div>


                    <?php else: ?>


                        <div class="tracking-body">


                            <?php

                            $stepNumber = 0;

                            $totalSteps =
                                count($deliverySteps);


                            foreach (
                                $deliverySteps
                                as $statusKey => $step
                            ):

                                $stepNumber++;


                                $position =
                                    $statusPositions[
                                        $statusKey
                                    ];


                                $completed =
                                    $position <
                                    $currentPosition;


                                $current =
                                    $position ===
                                    $currentPosition;


                                $classes = '';


                                if ($completed) {

                                    $classes .=
                                        ' completed';
                                }


                                if ($current) {

                                    $classes .=
                                        ' current';
                                }


                                $stepTime = '';


                                if (
                                    !empty(
                                        $step['timeField']
                                    )
                                ) {

                                    $timeField =
                                        $step[
                                            'timeField'
                                        ];


                                    if (
                                        !empty(
                                            $order[
                                                $timeField
                                            ]
                                        )
                                    ) {

                                        $stepTime =
                                            formatTrackingDate(
                                                $order[
                                                    $timeField
                                                ]
                                            );
                                    }
                                }


                            ?>


                                <div
                                    class="tracking-step<?= e(
                                        $classes
                                    ) ?>"
                                >


                                    <div
                                        class="step-icon-area"
                                    >


                                        <div
                                            class="step-icon"
                                        >

                                            <?php if (
                                                $completed
                                            ): ?>

                                                ✓

                                            <?php else: ?>

                                                <?= e(
                                                    $step['icon']
                                                ) ?>

                                            <?php endif; ?>

                                        </div>


                                        <?php if (
                                            $stepNumber <
                                            $totalSteps
                                        ): ?>

                                            <div
                                                class="step-line"
                                            ></div>

                                        <?php endif; ?>


                                    </div>


                                    <div
                                        class="step-content"
                                    >


                                        <h4>

                                            <?= e(
                                                $step['title']
                                            ) ?>

                                        </h4>


                                        <p>

                                            <?= e(
                                                $step['text']
                                            ) ?>

                                        </p>


                                        <?php if (
                                            $stepTime !== ''
                                        ): ?>

                                            <span
                                                class="step-time"
                                            >
                                                🕒
                                                <?= e(
                                                    $stepTime
                                                ) ?>
                                            </span>

                                        <?php endif; ?>


                                        <?php if (
                                            $current
                                        ): ?>

                                            <span
                                                class="current-badge"
                                            >
                                                Current Status
                                            </span>

                                        <?php endif; ?>


                                    </div>


                                </div>


                            <?php endforeach; ?>


                        </div>


                    <?php endif; ?>


                </div>


                <!-- =================================================
                     DELIVERY INFORMATION
                ================================================= -->

                <div class="card">


                    <div class="card-header">

                        <h3>
                            Delivery Information
                        </h3>

                    </div>


                    <div class="info-body">


                        <div class="info-grid">


                            <div class="info-item">

                                <label>
                                    Customer
                                </label>

                                <strong>
                                    <?= e(
                                        $order['customer_name']
                                    ) ?>
                                </strong>

                            </div>


                            <div class="info-item">

                                <label>
                                    Mobile
                                </label>

                                <strong>
                                    <?= e(
                                        $order['customer_mobile']
                                    ) ?>
                                </strong>

                            </div>


                            <div class="info-item">

                                <label>
                                    Order Status
                                </label>

                                <strong>
                                    <?= e(
                                        ucfirst(
                                            str_replace(
                                                '_',
                                                ' ',
                                                (string)
                                                $order[
                                                    'order_status'
                                                ]
                                            )
                                        )
                                    ) ?>
                                </strong>

                            </div>


                            <div class="info-item">

                                <label>
                                    Delivery Status
                                </label>

                                <strong>
                                    <?= e(
                                        deliveryStatusLabel(
                                            $deliveryStatus
                                        )
                                    ) ?>
                                </strong>

                            </div>


                            <div class="info-item full">

                                <label>
                                    Delivery Address
                                </label>

                                <strong>

                                    <?= e(
                                        $fullAddress !== ''
                                            ? $fullAddress
                                            : 'Address not available'
                                    ) ?>

                                </strong>

                            </div>


                        </div>


                        <?php if (
                            $hasDeliveryPerson
                        ): ?>


                            <div class="assignment-box">

                                <strong>
                                    👤 Delivery Person Assigned
                                </strong>

                                <span>
                                    Your order has been assigned
                                    to a delivery person.
                                </span>

                            </div>


                        <?php elseif (
                            $deliveryStatus === 'pending'
                        ): ?>


                            <div class="assignment-box">

                                <strong>
                                    🕐 Waiting for Assignment
                                </strong>

                                <span>
                                    A delivery person will be
                                    assigned to your order soon.
                                </span>

                            </div>


                        <?php endif; ?>


                        <?php if (
                            $deliveryNote !== ''
                        ): ?>


                            <div class="note-box">

                                <strong>
                                    📝 Delivery Note
                                </strong>

                                <p>
                                    <?= nl2br(
                                        e($deliveryNote)
                                    ) ?>
                                </p>

                            </div>


                        <?php endif; ?>


                    </div>


                </div>


            </div>


            <!-- =================================================
                 RIGHT COLUMN
            ================================================= -->

            <div>


                <!-- =================================================
                     ORDER SUMMARY
                ================================================= -->

                <div class="card">


                    <div class="card-header">

                        <h3>
                            Order Summary
                        </h3>

                    </div>


                    <div class="summary-body">


                        <div class="summary-row">

                            <span>
                                Subtotal
                            </span>

                            <strong>

                                ₹<?= number_format(
                                    (float)
                                    $order['subtotal'],
                                    2
                                ) ?>

                            </strong>

                        </div>


                        <div class="summary-row">

                            <span>
                                Delivery
                            </span>

                            <strong>

                                ₹<?= number_format(
                                    (float)
                                    $order[
                                        'delivery_charge'
                                    ],
                                    2
                                ) ?>

                            </strong>

                        </div>


                        <div
                            class="summary-row"
                            style="color:#238b39;"
                        >

                            <span>
                                Discount
                            </span>

                            <strong
                                style="color:#238b39;"
                            >

                                - ₹<?= number_format(
                                    (float)
                                    $order['discount'],
                                    2
                                ) ?>

                            </strong>

                        </div>


                        <div class="summary-total">

                            <span>
                                Total
                            </span>

                            <strong>

                                ₹<?= number_format(
                                    (float)
                                    $order['total_amount'],
                                    2
                                ) ?>

                            </strong>

                        </div>


                        <!-- PAYMENT -->

                        <div class="payment-box">


                            <div class="payment-row">

                                <span>
                                    Payment
                                </span>

                                <strong>
                                    <?= e(
                                        $paymentMethodLabel
                                    ) ?>
                                </strong>

                            </div>


                            <div class="payment-row">

                                <span>
                                    Payment Status
                                </span>

                                <strong>
                                    <?= e(
                                        $paymentStatusLabel
                                    ) ?>
                                </strong>

                            </div>


                        </div>


                    </div>


                </div>


                <!-- =================================================
                     DELIVERY ADDRESS
                ================================================= -->

                <div
                    class="card"
                    style="margin-top:20px;"
                >


                    <div class="card-header">

                        <h3>
                            Delivery Address
                        </h3>

                    </div>


                    <div class="address-body">


                        <div class="address-icon">
                            📍
                        </div>


                        <strong>
                            <?= e(
                                $order['customer_name']
                            ) ?>
                        </strong>


                        <p>

                            <?= e(
                                $fullAddress !== ''
                                    ? $fullAddress
                                    : 'Address not available'
                            ) ?>

                        </p>


                    </div>


                </div>


                <!-- =================================================
                     QUICK ACTIONS
                ================================================= -->

                <div
                    class="card"
                    style="margin-top:20px;"
                >


                    <div class="card-header">

                        <h3>
                            Quick Actions
                        </h3>

                    </div>


                    <div class="quick-actions">


                        <a
                            href="order-details.php?id=<?= (int) $order['order_id'] ?>"
                            class="quick-action"
                        >

                            <div class="quick-icon">
                                📄
                            </div>


                            <div class="quick-text">

                                <strong>
                                    Order Details
                                </strong>

                                <span>
                                    View complete order
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


                            <div class="quick-text">

                                <strong>
                                    My Orders
                                </strong>

                                <span>
                                    View all orders
                                </span>

                            </div>

                        </a>


                        <a
                            href="../medicines.php"
                            class="quick-action"
                        >

                            <div class="quick-icon">
                                💊
                            </div>


                            <div class="quick-text">

                                <strong>
                                    Browse Medicines
                                </strong>

                                <span>
                                    Order more medicines
                                </span>

                            </div>

                        </a>


                        <a
                            href="../contact.php"
                            class="quick-action"
                        >

                            <div class="quick-icon">
                                💬
                            </div>


                            <div class="quick-text">

                                <strong>
                                    Need Help?
                                </strong>

                                <span>
                                    Contact support
                                </span>

                            </div>

                        </a>


                    </div>


                </div>


                <!-- =================================================
                     BUTTONS
                ================================================= -->

                <div class="button-row">


                    <a
                        href="order-details.php?id=<?= (int) $order['order_id'] ?>"
                        class="btn btn-light"
                    >
                        ← Order Details
                    </a>


                    <a
                        href="orders.php"
                        class="btn btn-primary"
                    >
                        My Orders
                    </a>


                </div>


            </div>


        </div>


    </section>


</main>


</body>

</html>