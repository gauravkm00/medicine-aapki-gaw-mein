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

$adminName = $_SESSION['name'] ?? 'Administrator';


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
// ALLOWED STATUSES
// =====================================================

$allowedStatuses = [
    'pending',
    'assigned',
    'picked_up',
    'in_transit',
    'out_for_delivery',
    'delivered',
    'cancelled',
    'failed'
];


// =====================================================
// CONTACT MESSAGE COUNT
// =====================================================

$contactCount = 0;

$contactSql = "
    SELECT COUNT(*) AS total
    FROM contact_messages
    WHERE status IN ('new', 'unread', 'pending')
";

$contactResult = mysqli_query($conn, $contactSql);

if ($contactResult) {

    $contactRow = mysqli_fetch_assoc($contactResult);

    $contactCount = (int) (
        $contactRow['total'] ?? 0
    );
}


// =====================================================
// COUNT HELPER
// =====================================================

function getCount($conn, $sql)
{
    $result = mysqli_query($conn, $sql);

    if ($result) {

        $row = mysqli_fetch_assoc($result);

        return (int) (
            $row['total'] ?? 0
        );
    }

    return 0;
}


// =====================================================
// DELIVERY COUNTS
// =====================================================

$totalDeliveries = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM deliveries"
);

$pendingDeliveries = getCount(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM deliveries
    WHERE status = 'pending'
    "
);

$assignedDeliveries = getCount(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM deliveries
    WHERE status = 'assigned'
    "
);

$pickupDeliveries = getCount(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM deliveries
    WHERE status = 'picked_up'
    "
);

$inTransitDeliveries = getCount(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM deliveries
    WHERE status = 'in_transit'
    "
);

$outForDelivery = getCount(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM deliveries
    WHERE status = 'out_for_delivery'
    "
);

$deliveredDeliveries = getCount(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM deliveries
    WHERE status = 'delivered'
    "
);

$cancelledDeliveries = getCount(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM deliveries
    WHERE status = 'cancelled'
    "
);

$failedDeliveries = getCount(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM deliveries
    WHERE status = 'failed'
    "
);


// =====================================================
// STATUS UPDATE
// =====================================================

$message = '';
$messageType = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_status'])
) {

    $deliveryId = (int) (
        $_POST['delivery_id'] ?? 0
    );

    $newStatus = trim(
        $_POST['status'] ?? ''
    );


    // -------------------------------------------------
    // VALIDATION
    // -------------------------------------------------

    if (
        $deliveryId <= 0 ||
        !in_array(
            $newStatus,
            $allowedStatuses,
            true
        )
    ) {

        $message = "Invalid delivery information.";
        $messageType = "error";

    } else {


        // -------------------------------------------------
        // CHECK DELIVERY
        // -------------------------------------------------

        $checkSql = "
            SELECT id, status
            FROM deliveries
            WHERE id = ?
            LIMIT 1
        ";

        $checkStmt = mysqli_prepare(
            $conn,
            $checkSql
        );


        if (!$checkStmt) {

            $message = "Database error: " .
                mysqli_error($conn);

            $messageType = "error";

        } else {

            mysqli_stmt_bind_param(
                $checkStmt,
                "i",
                $deliveryId
            );

            mysqli_stmt_execute(
                $checkStmt
            );

            $checkResult =
                mysqli_stmt_get_result(
                    $checkStmt
                );


            if (
                !$checkResult ||
                mysqli_num_rows($checkResult) === 0
            ) {

                $message =
                    "Delivery record not found.";

                $messageType = "error";

            } else {


                // -------------------------------------------------
                // UPDATE BASE STATUS
                // -------------------------------------------------

                $updateSql = "
                    UPDATE deliveries
                    SET
                        status = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ";


                // -------------------------------------------------
                // STATUS-SPECIFIC TIMESTAMPS
                // -------------------------------------------------

                if ($newStatus === 'picked_up') {

                    $updateSql = "
                        UPDATE deliveries
                        SET
                            status = ?,
                            picked_up_at = COALESCE(
                                picked_up_at,
                                NOW()
                            ),
                            updated_at = NOW()
                        WHERE id = ?
                    ";

                } elseif (
                    $newStatus === 'out_for_delivery'
                ) {

                    $updateSql = "
                        UPDATE deliveries
                        SET
                            status = ?,
                            out_for_delivery_at = COALESCE(
                                out_for_delivery_at,
                                NOW()
                            ),
                            updated_at = NOW()
                        WHERE id = ?
                    ";

                } elseif (
                    $newStatus === 'delivered'
                ) {

                    $updateSql = "
                        UPDATE deliveries
                        SET
                            status = ?,
                            delivered_at = COALESCE(
                                delivered_at,
                                NOW()
                            ),
                            updated_at = NOW()
                        WHERE id = ?
                    ";

                }


                // -------------------------------------------------
                // EXECUTE UPDATE
                // -------------------------------------------------

                $stmt = mysqli_prepare(
                    $conn,
                    $updateSql
                );


                if (!$stmt) {

                    $message =
                        "Database error: " .
                        mysqli_error($conn);

                    $messageType = "error";

                } else {

                    mysqli_stmt_bind_param(
                        $stmt,
                        "si",
                        $newStatus,
                        $deliveryId
                    );


                    if (
                        mysqli_stmt_execute(
                            $stmt
                        )
                    ) {

                        $message =
                            "Delivery #"
                            . $deliveryId
                            . " status updated to "
                            . ucwords(
                                str_replace(
                                    '_',
                                    ' ',
                                    $newStatus
                                )
                            )
                            . ".";

                        $messageType = "success";

                    } else {

                        $message =
                            "Unable to update delivery status: "
                            . mysqli_stmt_error($stmt);

                        $messageType = "error";
                    }


                    mysqli_stmt_close($stmt);
                }
            }


            mysqli_stmt_close(
                $checkStmt
            );
        }
    }


    // -------------------------------------------------
    // REDIRECT TO PREVENT RESUBMISSION
    // -------------------------------------------------

    if ($messageType === 'success') {

        $redirectUrl =
            "deliveries.php?updated=1";

        header(
            "Location: " . $redirectUrl
        );

        exit;
    }
}


// =====================================================
// SUCCESS MESSAGE AFTER REDIRECT
// =====================================================

if (
    isset($_GET['updated']) &&
    $_GET['updated'] == '1'
) {

    $message =
        "Delivery status updated successfully.";

    $messageType = "success";
}


// =====================================================
// SEARCH & FILTER
// =====================================================

$search = trim(
    $_GET['search'] ?? ''
);

$statusFilter = trim(
    $_GET['status'] ?? ''
);


// =====================================================
// FETCH DELIVERIES
// =====================================================

$deliveries = [];


$sql = "
    SELECT
        d.id,
        d.order_id,
        d.delivery_person_id,
        d.status,
        d.assigned_at,
        d.picked_up_at,
        d.out_for_delivery_at,
        d.delivered_at,
        d.delivery_otp,
        d.delivery_note,
        d.failure_reason,
        d.created_at,
        d.updated_at,

        o.order_number,
        o.customer_name,
        o.customer_mobile,
        o.total_amount,
        o.payment_method,
        o.payment_status

    FROM deliveries d

    LEFT JOIN orders o
        ON o.id = d.order_id

    WHERE 1 = 1
";


$params = [];
$types = '';


// =====================================================
// SEARCH
// =====================================================

if ($search !== '') {

    $sql .= "
        AND (
            CAST(d.id AS CHAR) LIKE ?
            OR CAST(d.order_id AS CHAR) LIKE ?
            OR CAST(d.delivery_person_id AS CHAR) LIKE ?
            OR o.order_number LIKE ?
            OR o.customer_name LIKE ?
            OR o.customer_mobile LIKE ?
        )
    ";

    $searchValue =
        '%' . $search . '%';


    for ($i = 0; $i < 6; $i++) {

        $params[] =
            $searchValue;
    }


    $types .= 'ssssss';
}


// =====================================================
// STATUS FILTER
// =====================================================

if (
    $statusFilter !== '' &&
    in_array(
        $statusFilter,
        $allowedStatuses,
        true
    )
) {

    $sql .= "
        AND d.status = ?
    ";

    $params[] =
        $statusFilter;

    $types .= 's';
}


// =====================================================
// ORDER
// =====================================================

$sql .= "
    ORDER BY d.id DESC
    LIMIT 100
";


// =====================================================
// EXECUTE QUERY
// =====================================================

if (!empty($params)) {

    $stmt = mysqli_prepare(
        $conn,
        $sql
    );


    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            $types,
            ...$params
        );

        mysqli_stmt_execute(
            $stmt
        );

        $result =
            mysqli_stmt_get_result(
                $stmt
            );


        if ($result) {

            while (
                $row =
                mysqli_fetch_assoc($result)
            ) {

                $deliveries[] =
                    $row;
            }
        }


        mysqli_stmt_close(
            $stmt
        );
    }

} else {

    $result = mysqli_query(
        $conn,
        $sql
    );


    if ($result) {

        while (
            $row =
            mysqli_fetch_assoc($result)
        ) {

            $deliveries[] =
                $row;
        }
    }
}


// =====================================================
// PAGE TITLE
// =====================================================

$pageTitle = "Deliveries";

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
    <?= e($pageTitle) ?> |
    Medicine Aapki Gaw Mein
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


html {
    scroll-behavior: smooth;
}


body {
    font-family: "Rubik", Arial, sans-serif;
    background: #f5f7fb;
    color: #27313a;
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
    min-height: 100vh;
}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {
    width: 255px;
    position: fixed;
    inset: 0 auto 0 0;
    z-index: 1000;

    background:
        linear-gradient(
            180deg,
            #238b3c 0%,
            #176b2d 100%
        );

    color: #fff;

    overflow-y: auto;

    transition:
        transform .28s ease;
}


.sidebar-brand {
    padding: 25px 22px;

    border-bottom:
        1px solid
        rgba(255,255,255,.12);
}


.brand-top {
    display: flex;
    align-items: center;
    gap: 12px;
}


.brand-icon {
    width: 45px;
    height: 45px;

    flex-shrink: 0;

    border-radius: 12px;

    display: flex;
    align-items: center;
    justify-content: center;

    background:
        rgba(255,255,255,.15);

    font-size: 23px;
}


.sidebar-brand h2 {
    font-size: 15px;
    line-height: 1.35;
    font-weight: 700;
}


.sidebar-brand p {
    font-size: 10px;

    color:
        rgba(255,255,255,.65);

    margin-top: 4px;
}


.sidebar-menu {
    padding: 17px 12px 25px;
}


.menu-title {
    color:
        rgba(255,255,255,.5);

    font-size: 9px;

    text-transform: uppercase;

    letter-spacing: 1.1px;

    padding:
        12px 12px 8px;
}


.sidebar-menu a {
    display: flex;

    align-items: center;

    gap: 11px;

    padding:
        11px 13px;

    margin-bottom: 4px;

    border-radius: 9px;

    font-size: 12px;

    color:
        rgba(255,255,255,.84);

    transition:
        background .2s,
        color .2s,
        transform .2s;
}


.sidebar-menu a:hover {
    background:
        rgba(255,255,255,.11);

    color: #fff;

    transform:
        translateX(2px);
}


.sidebar-menu a.active {
    background:
        rgba(255,255,255,.18);

    color: #fff;

    box-shadow:
        inset 3px 0 0 #fff;
}


.menu-icon {
    width: 25px;
    text-align: center;
    font-size: 15px;
}


.menu-badge {
    margin-left: auto;

    min-width: 20px;

    padding: 3px 6px;

    text-align: center;

    border-radius: 20px;

    background: #fff;

    color: #238b3c;

    font-size: 8px;

    font-weight: 700;
}


/* =====================================================
   MAIN
===================================================== */

.main-content {
    margin-left: 255px;
    min-height: 100vh;
}


/* =====================================================
   TOPBAR
===================================================== */

.topbar {
    height: 72px;

    position: sticky;
    top: 0;

    z-index: 100;

    background: #fff;

    border-bottom:
        1px solid #e9edf3;

    padding:
        0 28px;

    display: flex;

    align-items: center;

    justify-content: space-between;
}


.topbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
}


.mobile-menu-btn {
    display: none;

    width: 38px;
    height: 38px;

    border: 0;

    border-radius: 9px;

    background: #eaf7ec;

    color: #238b3c;

    cursor: pointer;

    font-size: 18px;
}


.topbar-title h1 {
    font-size: 19px;
    color: #222;
    font-weight: 600;
}


.topbar-title p {
    color: #9ba2a8;
    font-size: 10px;
    margin-top: 3px;
}


.admin-profile {
    display: flex;
    align-items: center;
    gap: 10px;
}


.admin-avatar {
    width: 39px;
    height: 39px;

    border-radius: 50%;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #e8f7eb;

    color: #238b3c;

    font-size: 14px;

    font-weight: 700;
}


.admin-info strong {
    display: block;

    font-size: 12px;

    color: #333;
}


.admin-info span {
    display: block;

    margin-top: 2px;

    color: #999;

    font-size: 9px;
}


/* =====================================================
   CONTENT
===================================================== */

.content {
    padding: 27px;
}


/* =====================================================
   PAGE HEADER
===================================================== */

.page-header {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 20px;
}


.page-header h2 {
    color: #222;

    font-size: 21px;

    font-weight: 600;
}


.page-header p {
    color: #999;

    font-size: 11px;

    margin-top: 5px;
}


.page-date {
    color: #8d959c;

    font-size: 10px;

    background: #fff;

    padding: 8px 11px;

    border-radius: 7px;

    border: 1px solid #edf0f4;
}


/* =====================================================
   ALERT
===================================================== */

.alert {
    display: flex;

    align-items: center;

    min-height: 43px;

    padding: 10px 14px;

    border-radius: 9px;

    margin-bottom: 18px;

    font-size: 11px;
}


.alert-success {
    color: #1d7330;

    background: #eaf8ed;

    border: 1px solid #ccebd2;
}


.alert-error {
    color: #a3212e;

    background: #ffeaed;

    border: 1px solid #f5c9ce;
}


/* =====================================================
   STATS
===================================================== */

.stats-grid {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 14px;

    margin-bottom: 20px;
}


.stat-card {
    background: #fff;

    border: 1px solid #edf0f4;

    border-radius: 12px;

    padding: 16px;

    display: flex;

    align-items: center;

    gap: 13px;

    transition:
        transform .2s,
        box-shadow .2s;
}


.stat-card:hover {
    transform:
        translateY(-2px);

    box-shadow:
        0 8px 24px
        rgba(0,0,0,.055);
}


.stat-icon {
    width: 46px;
    height: 46px;

    flex-shrink: 0;

    border-radius: 11px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 19px;
}


.icon-blue {
    background: #eaf3ff;
}


.icon-orange {
    background: #fff3e4;
}


.icon-purple {
    background: #f1eaff;
}


.icon-green {
    background: #e9f8ec;
}


.stat-card h3 {
    color: #222;

    font-size: 20px;

    line-height: 1;

    margin-bottom: 5px;
}


.stat-card p {
    color: #9b9fa4;

    font-size: 9px;
}


/* =====================================================
   FILTER
===================================================== */

.filter-panel {
    background: #fff;

    border: 1px solid #edf0f4;

    border-radius: 12px;

    padding: 16px;

    margin-bottom: 18px;
}


.filter-form {
    display: grid;

    grid-template-columns:
        minmax(180px, 1fr)
        200px
        auto
        auto;

    gap: 9px;

    align-items: end;
}


.form-group label {
    display: block;

    color: #707880;

    font-size: 9px;

    font-weight: 600;

    margin-bottom: 6px;
}


.form-control {
    width: 100%;

    height: 39px;

    border:
        1px solid #dfe4ea;

    border-radius: 8px;

    background: #fff;

    color: #333;

    padding: 0 11px;

    font-size: 11px;

    outline: none;

    transition: .2s;
}


.form-control:focus {
    border-color: #51b848;

    box-shadow:
        0 0 0 3px
        rgba(81,184,72,.08);
}


.btn {
    height: 39px;

    border: 0;

    border-radius: 8px;

    padding: 0 16px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    cursor: pointer;

    font-size: 10px;

    font-weight: 600;

    transition: .2s;
}


.btn-primary {
    background: #278c3c;
    color: #fff;
}


.btn-primary:hover {
    background: #1f7932;
}


.btn-light {
    background: #f1f3f5;
    color: #555;
}


.btn-light:hover {
    background: #e6e9ec;
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
    min-height: 57px;

    padding: 0 18px;

    border-bottom:
        1px solid #edf0f4;

    display: flex;

    align-items: center;

    justify-content: space-between;
}


.panel-header h3 {
    color: #252a2e;

    font-size: 14px;

    font-weight: 600;
}


.panel-header span {
    color: #999;

    font-size: 9px;
}


/* =====================================================
   TABLE
===================================================== */

.table-wrapper {
    width: 100%;

    overflow-x: auto;

    scrollbar-width: thin;
}


table {
    width: 100%;

    min-width: 1250px;

    border-collapse: collapse;
}


th {
    padding: 12px 13px;

    background: #fafbfc;

    color: #8b9298;

    font-size: 8px;

    text-transform: uppercase;

    letter-spacing: .5px;

    text-align: left;

    white-space: nowrap;
}


td {
    padding: 12px 13px;

    border-top:
        1px solid #f0f2f5;

    color: #454b50;

    font-size: 10px;

    vertical-align: middle;
}


tbody tr {
    transition:
        background .15s;
}


tbody tr:hover {
    background: #fbfdfb;
}


.delivery-id {
    font-weight: 700;

    color: #555;
}


.order-number {
    color: #278c3c;

    font-weight: 700;
}


.customer-name {
    color: #333;

    font-size: 10px;

    font-weight: 600;
}


.customer-mobile {
    color: #999;

    font-size: 8px;

    margin-top: 3px;
}


.amount {
    color: #333;

    font-size: 10px;

    font-weight: 700;
}


.payment-status {
    font-size: 8px;

    margin-top: 3px;

    color: #999;
}


.delivery-person {
    color: #333;

    font-size: 10px;

    font-weight: 600;
}


.delivery-person small {
    display: block;

    color: #999;

    font-size: 8px;

    margin-top: 3px;
}


.not-assigned {
    color: #aaa;

    font-size: 9px;
}


/* =====================================================
   STATUS BADGES
===================================================== */

.badge {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-height: 23px;

    padding: 4px 8px;

    border-radius: 20px;

    font-size: 8px;

    font-weight: 600;

    white-space: nowrap;

    text-transform: capitalize;
}


.badge-pending {
    color: #956800;

    background: #fff4d1;
}


.badge-assigned {
    color: #236aa0;

    background: #e4f1ff;
}


.badge-picked_up {
    color: #6241a1;

    background: #eee8ff;
}


.badge-in_transit {
    color: #17639b;

    background: #e0f0ff;
}


.badge-out_for_delivery {
    color: #087a58;

    background: #d9f7ec;
}


.badge-delivered {
    color: #187432;

    background: #def4e3;
}


.badge-cancelled {
    color: #a32936;

    background: #ffe3e6;
}


.badge-failed {
    color: #fff;

    background: #c0392b;
}


/* =====================================================
   STATUS FORM
===================================================== */

.status-form {
    display: flex;

    align-items: center;

    gap: 5px;
}


.status-select {
    width: 145px;

    height: 31px;

    border:
        1px solid #dfe4ea;

    border-radius: 6px;

    background: #fff;

    padding: 0 7px;

    color: #555;

    font-size: 8px;

    outline: none;
}


.status-select:focus {
    border-color: #51b848;
}


.update-btn {
    height: 31px;

    border: 0;

    border-radius: 6px;

    padding: 0 9px;

    background: #278c3c;

    color: #fff;

    cursor: pointer;

    font-size: 8px;

    font-weight: 600;
}


.update-btn:hover {
    background: #1f7932;
}


/* =====================================================
   TIMELINE
===================================================== */

.time-info {
    color: #777;

    font-size: 8px;

    line-height: 1.5;
}


.time-info strong {
    color: #555;
}


/* =====================================================
   CREATED
===================================================== */

.created-date {
    color: #777;

    font-size: 9px;
}


.created-time {
    color: #aaa;

    display: block;

    margin-top: 2px;

    font-size: 8px;
}


/* =====================================================
   EMPTY
===================================================== */

.empty-state {
    padding: 55px 20px;

    text-align: center;

    color: #aaa;
}


.empty-icon {
    width: 58px;
    height: 58px;

    margin:
        0 auto 12px;

    border-radius: 50%;

    background: #f1f7f2;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 27px;
}


.empty-state strong {
    display: block;

    color: #777;

    font-size: 12px;
}


.empty-state p {
    margin-top: 5px;

    font-size: 9px;
}


/* =====================================================
   SUMMARY
===================================================== */

.summary-grid {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);
}


.summary-item {
    padding: 17px 20px;

    border-right:
        1px solid #edf0f4;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;
}


.summary-item:last-child {
    border-right: 0;
}


.summary-item span {
    color: #777;

    font-size: 10px;
}


.summary-item strong {
    color: #278c3c;

    font-size: 16px;
}


/* =====================================================
   OVERLAY
===================================================== */

.sidebar-overlay {
    display: none;

    position: fixed;

    inset: 0;

    z-index: 900;

    background:
        rgba(0,0,0,.35);
}


/* =====================================================
   RESPONSIVE 1200
===================================================== */

@media (max-width: 1200px) {

    .stats-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }

    .filter-form {
        grid-template-columns:
            1fr 1fr;
    }

    .filter-form .btn {
        width: 100%;
    }

    .summary-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }

    .summary-item:nth-child(2) {
        border-right: 0;
    }

    .summary-item:nth-child(-n+2) {
        border-bottom:
            1px solid #edf0f4;
    }
}


/* =====================================================
   RESPONSIVE 900
===================================================== */

@media (max-width: 900px) {

    .sidebar {
        transform:
            translateX(-100%);
    }

    .sidebar.show {
        transform:
            translateX(0);
    }

    .sidebar-overlay.show {
        display: block;
    }

    .main-content {
        margin-left: 0;
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


/* =====================================================
   RESPONSIVE 600
===================================================== */

@media (max-width: 600px) {

    .topbar {
        height: 65px;
    }

    .topbar-title h1 {
        font-size: 16px;
    }

    .topbar-title p {
        display: none;
    }

    .admin-info {
        display: none;
    }

    .admin-avatar {
        width: 36px;
        height: 36px;
    }

    .content {
        padding: 14px;
    }

    .page-header {
        align-items: flex-start;

        flex-direction: column;

        margin-bottom: 17px;
    }

    .page-header h2 {
        font-size: 18px;
    }

    .page-date {
        width: 100%;

        text-align: center;
    }

    .stats-grid {
        grid-template-columns: 1fr;

        gap: 10px;
    }

    .stat-card {
        padding: 14px;
    }

    .filter-panel {
        padding: 13px;
    }

    .filter-form {
        grid-template-columns: 1fr;
    }

    .panel-header {
        padding: 0 13px;
    }

    .panel-header h3 {
        font-size: 13px;
    }

    .summary-grid {
        grid-template-columns: 1fr;
    }

    .summary-item,
    .summary-item:nth-child(2) {
        border-right: 0;

        border-bottom:
            1px solid #edf0f4;
    }

    .summary-item:last-child {
        border-bottom: 0;
    }

    .table-wrapper {
        overflow-x: auto;
    }
}


/* =====================================================
   VERY SMALL
===================================================== */

@media (max-width: 380px) {

    .topbar {
        padding: 0 12px;
    }

    .content {
        padding: 10px;
    }

    .page-header h2 {
        font-size: 17px;
    }

    .stat-card h3 {
        font-size: 18px;
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

        <div class="brand-top">

            <div class="brand-icon">
                💊
            </div>

            <div>

                <h2>
                    Medicine Aapki<br>
                    Gaw Mein
                </h2>

                <p>
                    Administration Panel
                </p>

            </div>

        </div>

    </div>


    <nav class="sidebar-menu">


        <div class="menu-title">
            Main Menu
        </div>


        <a href="index.php">

            <span class="menu-icon">
                📊
            </span>

            <span>
                Dashboard
            </span>

        </a>


        <a href="medicines.php">

            <span class="menu-icon">
                💊
            </span>

            <span>
                Medicines
            </span>

        </a>


        <a href="orders.php">

            <span class="menu-icon">
                📦
            </span>

            <span>
                Orders
            </span>

        </a>


        <a href="prescriptions.php">

            <span class="menu-icon">
                📄
            </span>

            <span>
                Prescriptions
            </span>

        </a>


        <a
            href="deliveries.php"
            class="active"
        >

            <span class="menu-icon">
                🚚
            </span>

            <span>
                Deliveries
            </span>

            <?php if ($pendingDeliveries > 0): ?>

                <span class="menu-badge">

                    <?= number_format(
                        $pendingDeliveries
                    ) ?>

                </span>

            <?php endif; ?>

        </a>


        <a href="hero.php">

            <span class="menu-icon">
                🖼️
            </span>

            <span>
                Hero Section
            </span>

        </a>


        <a href="testimonials.php">

            <span class="menu-icon">
                💬
            </span>

            <span>
                Testimonials
            </span>

        </a>


        <a href="contact-messages.php">

            <span class="menu-icon">
                ✉️
            </span>

            <span>
                Contact Messages
            </span>

            <?php if ($contactCount > 0): ?>

                <span class="menu-badge">

                    <?= number_format(
                        $contactCount
                    ) ?>

                </span>

            <?php endif; ?>

        </a>


        <div class="menu-title">
            Account
        </div>


        <a href="../index.php">

            <span class="menu-icon">
                🌐
            </span>

            <span>
                View Website
            </span>

        </a>


        <a href="logout.php">

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
     MOBILE OVERLAY
===================================================== -->

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
    onclick="toggleSidebar()"
></div>


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
            type="button"
            class="mobile-menu-btn"
            onclick="toggleSidebar()"
        >
            ☰
        </button>


        <div class="topbar-title">

            <h1>
                Deliveries
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


    <!-- PAGE HEADER -->

    <div class="page-header">

        <div>

            <h2>
                🚚 Delivery Management
            </h2>

            <p>
                Track and manage customer deliveries.
            </p>

        </div>


        <div class="page-date">

            <?= date("l, d M Y") ?>

        </div>

    </div>


    <!-- ALERT -->

    <?php if ($message !== ''): ?>

        <div
            class="alert
            <?= $messageType === 'success'
                ? 'alert-success'
                : 'alert-error'
            ?>"
        >

            <?= e($message) ?>

        </div>

    <?php endif; ?>


    <!-- =================================================
         STATISTICS
    ================================================= -->

    <div class="stats-grid">


        <div class="stat-card">

            <div class="stat-icon icon-blue">
                🚚
            </div>

            <div>

                <h3>
                    <?= number_format(
                        $totalDeliveries
                    ) ?>
                </h3>

                <p>
                    Total Deliveries
                </p>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon icon-orange">
                ⏳
            </div>

            <div>

                <h3>
                    <?= number_format(
                        $pendingDeliveries
                    ) ?>
                </h3>

                <p>
                    Pending Deliveries
                </p>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon icon-purple">
                🚛
            </div>

            <div>

                <h3>
                    <?= number_format(
                        $inTransitDeliveries
                    ) ?>
                </h3>

                <p>
                    In Transit
                </p>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon icon-green">
                ✅
            </div>

            <div>

                <h3>
                    <?= number_format(
                        $deliveredDeliveries
                    ) ?>
                </h3>

                <p>
                    Delivered
                </p>

            </div>

        </div>


    </div>


    <!-- =================================================
         FILTER
    ================================================= -->

    <div class="filter-panel">


        <form
            method="GET"
            class="filter-form"
        >


            <div class="form-group">

                <label>
                    Search Delivery
                </label>

                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Delivery ID, Order, Customer, Mobile..."
                    value="<?= e($search) ?>"
                >

            </div>


            <div class="form-group">

                <label>
                    Delivery Status
                </label>

                <select
                    name="status"
                    class="form-control"
                >

                    <option value="">
                        All Statuses
                    </option>


                    <?php foreach (
                        $allowedStatuses
                        as $optionStatus
                    ): ?>

                        <option
                            value="<?= e(
                                $optionStatus
                            ) ?>"
                            <?= $statusFilter === $optionStatus
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= e(
                                ucwords(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $optionStatus
                                    )
                                )
                            ) ?>

                        </option>

                    <?php endforeach; ?>


                </select>

            </div>


            <button
                type="submit"
                class="btn btn-primary"
            >
                🔍 Search
            </button>


            <a
                href="deliveries.php"
                class="btn btn-light"
            >
                ↻ Reset
            </a>


        </form>


    </div>


    <!-- =================================================
         DELIVERY TABLE
    ================================================= -->

    <div class="panel">


        <div class="panel-header">

            <h3>
                All Deliveries
            </h3>

            <span>

                <?= number_format(
                    count($deliveries)
                ) ?>

                Records

            </span>

        </div>


        <?php if (!empty($deliveries)): ?>


            <div class="table-wrapper">


                <table>


                    <thead>

                        <tr>

                            <th>
                                Delivery
                            </th>

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
                                Delivery Person
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Timeline
                            </th>

                            <th>
                                Created
                            </th>

                            <th>
                                Update Status
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach (
                        $deliveries
                        as $delivery
                    ): ?>


                        <?php

                        $status =
                            strtolower(
                                trim(
                                    $delivery['status']
                                    ?? 'pending'
                                )
                            );


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


                            <!-- DELIVERY -->

                            <td>

                                <span class="delivery-id">

                                    #
                                    <?= e(
                                        $delivery['id']
                                    ) ?>

                                </span>

                            </td>


                            <!-- ORDER -->

                            <td>

                                <?php if (
                                    !empty(
                                        $delivery['order_number']
                                    )
                                ): ?>

                                    <span class="order-number">

                                        #
                                        <?= e(
                                            $delivery['order_number']
                                        ) ?>

                                    </span>

                                <?php else: ?>

                                    <span class="not-assigned">
                                        Order #<?= e(
                                            $delivery['order_id']
                                        ) ?>
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- CUSTOMER -->

                            <td>

                                <div class="customer-name">

                                    <?= e(
                                        $delivery[
                                            'customer_name'
                                        ]
                                        ?? 'Customer'
                                    ) ?>

                                </div>


                                <?php if (
                                    !empty(
                                        $delivery[
                                            'customer_mobile'
                                        ]
                                    )
                                ): ?>

                                    <div
                                        class="customer-mobile"
                                    >

                                        <?= e(
                                            $delivery[
                                                'customer_mobile'
                                            ]
                                        ) ?>

                                    </div>

                                <?php endif; ?>


                            </td>


                            <!-- AMOUNT -->

                            <td>

                                <span class="amount">

                                    ₹<?= number_format(
                                        (float) (
                                            $delivery[
                                                'total_amount'
                                            ] ?? 0
                                        ),
                                        2
                                    ) ?>

                                </span>


                                <?php if (
                                    !empty(
                                        $delivery[
                                            'payment_method'
                                        ]
                                    )
                                ): ?>

                                    <div class="payment-status">

                                        <?= e(
                                            $delivery[
                                                'payment_method'
                                            ]
                                        ) ?>

                                        <?php if (
                                            !empty(
                                                $delivery[
                                                    'payment_status'
                                                ]
                                            )
                                        ): ?>

                                            •
                                            <?= e(
                                                $delivery[
                                                    'payment_status'
                                                ]
                                            ) ?>

                                        <?php endif; ?>

                                    </div>

                                <?php endif; ?>


                            </td>


                            <!-- DELIVERY PERSON -->

                            <td>


                                <?php if (
                                    !empty(
                                        $delivery[
                                            'delivery_person_id'
                                        ]
                                    )
                                ): ?>

                                    <div class="delivery-person">

                                        Person #<?= e(
                                            $delivery[
                                                'delivery_person_id'
                                            ]
                                        ) ?>

                                    </div>

                                    <small>
                                        Delivery Person ID
                                    </small>

                                <?php else: ?>

                                    <span class="not-assigned">

                                        Not Assigned

                                    </span>

                                <?php endif; ?>


                            </td>


                            <!-- STATUS -->

                            <td>

                                <span
                                    class="badge
                                    <?= e(
                                        $statusClass
                                    ) ?>"
                                >

                                    <?= e(
                                        ucwords(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $status
                                            )
                                        )
                                    ) ?>

                                </span>

                            </td>


                            <!-- TIMELINE -->

                            <td>

                                <div class="time-info">


                                    <?php if (
                                        !empty(
                                            $delivery[
                                                'assigned_at'
                                            ]
                                        )
                                    ): ?>

                                        <strong>
                                            Assigned:
                                        </strong>

                                        <?= date(
                                            "d M, h:i A",
                                            strtotime(
                                                $delivery[
                                                    'assigned_at'
                                                ]
                                            )
                                        ) ?>

                                        <br>

                                    <?php endif; ?>


                                    <?php if (
                                        !empty(
                                            $delivery[
                                                'picked_up_at'
                                            ]
                                        )
                                    ): ?>

                                        <strong>
                                            Pickup:
                                        </strong>

                                        <?= date(
                                            "d M, h:i A",
                                            strtotime(
                                                $delivery[
                                                    'picked_up_at'
                                                ]
                                            )
                                        ) ?>

                                        <br>

                                    <?php endif; ?>


                                    <?php if (
                                        !empty(
                                            $delivery[
                                                'out_for_delivery_at'
                                            ]
                                        )
                                    ): ?>

                                        <strong>
                                            Out:
                                        </strong>

                                        <?= date(
                                            "d M, h:i A",
                                            strtotime(
                                                $delivery[
                                                    'out_for_delivery_at'
                                                ]
                                            )
                                        ) ?>

                                        <br>

                                    <?php endif; ?>


                                    <?php if (
                                        !empty(
                                            $delivery[
                                                'delivered_at'
                                            ]
                                        )
                                    ): ?>

                                        <strong>
                                            Delivered:
                                        </strong>

                                        <?= date(
                                            "d M, h:i A",
                                            strtotime(
                                                $delivery[
                                                    'delivered_at'
                                                ]
                                            )
                                        ) ?>

                                    <?php endif; ?>


                                    <?php if (
                                        empty(
                                            $delivery[
                                                'assigned_at'
                                            ]
                                        ) &&
                                        empty(
                                            $delivery[
                                                'picked_up_at'
                                            ]
                                        ) &&
                                        empty(
                                            $delivery[
                                                'out_for_delivery_at'
                                            ]
                                        ) &&
                                        empty(
                                            $delivery[
                                                'delivered_at'
                                            ]
                                        )
                                    ): ?>

                                        <span
                                            class="not-assigned"
                                        >
                                            No activity
                                        </span>

                                    <?php endif; ?>


                                </div>

                            </td>


                            <!-- CREATED -->

                            <td>


                                <?php if (
                                    !empty(
                                        $delivery[
                                            'created_at'
                                        ]
                                    )
                                ): ?>

                                    <span class="created-date">

                                        <?= date(
                                            "d M Y",
                                            strtotime(
                                                $delivery[
                                                    'created_at'
                                                ]
                                            )
                                        ) ?>

                                    </span>


                                    <span class="created-time">

                                        <?= date(
                                            "h:i A",
                                            strtotime(
                                                $delivery[
                                                    'created_at'
                                                ]
                                            )
                                        ) ?>

                                    </span>


                                <?php else: ?>

                                    <span
                                        class="not-assigned"
                                    >
                                        —
                                    </span>

                                <?php endif; ?>


                            </td>


                            <!-- UPDATE STATUS -->

                            <td>


                                <form
                                    method="POST"
                                    class="status-form"
                                >


                                    <input
                                        type="hidden"
                                        name="delivery_id"
                                        value="<?= (int)
                                            $delivery['id'] ?>"
                                    >


                                    <select
                                        name="status"
                                        class="status-select"
                                    >


                                        <?php foreach (
                                            $allowedStatuses
                                            as $optionStatus
                                        ): ?>


                                            <option
                                                value="<?= e(
                                                    $optionStatus
                                                ) ?>"
                                                <?= $status ===
                                                    $optionStatus
                                                    ? 'selected'
                                                    : ''
                                                ?>
                                            >

                                                <?= e(
                                                    ucwords(
                                                        str_replace(
                                                            '_',
                                                            ' ',
                                                            $optionStatus
                                                        )
                                                    )
                                                ) ?>

                                            </option>


                                        <?php endforeach; ?>


                                    </select>


                                    <button
                                        type="submit"
                                        name="update_status"
                                        value="1"
                                        class="update-btn"
                                    >
                                        Update
                                    </button>


                                </form>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>


                </table>


            </div>


        <?php else: ?>


            <div class="empty-state">


                <div class="empty-icon">
                    🚚
                </div>


                <strong>
                    No deliveries found
                </strong>


                <p>
                    No delivery records match your current search or filter.
                </p>


            </div>


        <?php endif; ?>


    </div>


    <!-- =================================================
         STATUS SUMMARY
    ================================================= -->

    <div
        class="panel"
        style="margin-top:18px;"
    >


        <div class="panel-header">

            <h3>
                Delivery Status Summary
            </h3>

        </div>


        <div class="summary-grid">


            <div class="summary-item">

                <span>
                    Pending
                </span>

                <strong>
                    <?= number_format(
                        $pendingDeliveries
                    ) ?>
                </strong>

            </div>


            <div class="summary-item">

                <span>
                    Assigned
                </span>

                <strong>
                    <?= number_format(
                        $assignedDeliveries
                    ) ?>
                </strong>

            </div>


            <div class="summary-item">

                <span>
                    Picked Up
                </span>

                <strong>
                    <?= number_format(
                        $pickupDeliveries
                    ) ?>
                </strong>

            </div>


            <div class="summary-item">

                <span>
                    In Transit
                </span>

                <strong>
                    <?= number_format(
                        $inTransitDeliveries
                    ) ?>
                </strong>

            </div>


            <div class="summary-item">

                <span>
                    Out for Delivery
                </span>

                <strong>
                    <?= number_format(
                        $outForDelivery
                    ) ?>
                </strong>

            </div>


            <div class="summary-item">

                <span>
                    Delivered
                </span>

                <strong>
                    <?= number_format(
                        $deliveredDeliveries
                    ) ?>
                </strong>

            </div>


            <div class="summary-item">

                <span>
                    Cancelled
                </span>

                <strong>
                    <?= number_format(
                        $cancelledDeliveries
                    ) ?>
                </strong>

            </div>


            <div class="summary-item">

                <span>
                    Failed
                </span>

                <strong
                    style="color:#d63031;"
                >
                    <?= number_format(
                        $failedDeliveries
                    ) ?>
                </strong>

            </div>


        </div>


    </div>


</div>


</main>


</div>


<script>


// =====================================================
// SIDEBAR
// =====================================================

function toggleSidebar()
{
    const sidebar =
        document.getElementById(
            "sidebar"
        );

    const overlay =
        document.getElementById(
            "sidebarOverlay"
        );

    sidebar.classList.toggle(
        "show"
    );

    overlay.classList.toggle(
        "show"
    );
}


// =====================================================
// CLOSE SIDEBAR
// =====================================================

document
    .querySelectorAll(
        ".sidebar a"
    )
    .forEach(
        function(link)
        {

            link.addEventListener(
                "click",
                function()
                {

                    if (
                        window.innerWidth <= 900
                    ) {

                        document
                            .getElementById(
                                "sidebar"
                            )
                            .classList.remove(
                                "show"
                            );

                        document
                            .getElementById(
                                "sidebarOverlay"
                            )
                            .classList.remove(
                                "show"
                            );
                    }

                }
            );

        }
    );


// =====================================================
// ESCAPE KEY
// =====================================================

document.addEventListener(
    "keydown",
    function(event)
    {

        if (
            event.key === "Escape"
        ) {

            document
                .getElementById(
                    "sidebar"
                )
                .classList.remove(
                    "show"
                );

            document
                .getElementById(
                    "sidebarOverlay"
                )
                .classList.remove(
                    "show"
                );
        }

    }
);


</script>


</body>

</html>

