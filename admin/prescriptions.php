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
// CSRF TOKEN
// =====================================================

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));

}

$csrfToken = $_SESSION['csrf_token'];


// =====================================================
// STATUS UPDATE
// =====================================================

$message = '';
$messageType = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action'])
) {

    // -------------------------------------------------
    // CSRF CHECK
    // -------------------------------------------------

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals(
            $_SESSION['csrf_token'],
            $_POST['csrf_token']
        )
    ) {

        $message = "Invalid security token.";
        $messageType = "error";

    } else {

        $action = $_POST['action'];

        $prescriptionId =
            isset($_POST['prescription_id'])
                ? (int)$_POST['prescription_id']
                : 0;


        // =================================================
        // APPROVE
        // =================================================

        if (
            $action === 'approve' &&
            $prescriptionId > 0
        ) {

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE prescriptions
                 SET status = 'approved'
                 WHERE id = ?"
            );

            if ($stmt) {

                mysqli_stmt_bind_param(
                    $stmt,
                    "i",
                    $prescriptionId
                );

                if (mysqli_stmt_execute($stmt)) {

                    $message =
                        "Prescription approved successfully.";

                    $messageType = "success";

                } else {

                    $message =
                        "Unable to approve prescription.";

                    $messageType = "error";
                }

                mysqli_stmt_close($stmt);

            }

        }


        // =================================================
        // REJECT
        // =================================================

        elseif (
            $action === 'reject' &&
            $prescriptionId > 0
        ) {

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE prescriptions
                 SET status = 'rejected'
                 WHERE id = ?"
            );

            if ($stmt) {

                mysqli_stmt_bind_param(
                    $stmt,
                    "i",
                    $prescriptionId
                );

                if (mysqli_stmt_execute($stmt)) {

                    $message =
                        "Prescription rejected.";

                    $messageType = "success";

                } else {

                    $message =
                        "Unable to reject prescription.";

                    $messageType = "error";
                }

                mysqli_stmt_close($stmt);

            }

        }


        // =================================================
        // SET PENDING
        // =================================================

        elseif (
            $action === 'pending' &&
            $prescriptionId > 0
        ) {

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE prescriptions
                 SET status = 'pending'
                 WHERE id = ?"
            );

            if ($stmt) {

                mysqli_stmt_bind_param(
                    $stmt,
                    "i",
                    $prescriptionId
                );

                if (mysqli_stmt_execute($stmt)) {

                    $message =
                        "Prescription moved to pending.";

                    $messageType = "success";

                } else {

                    $message =
                        "Unable to update prescription.";

                    $messageType = "error";
                }

                mysqli_stmt_close($stmt);

            }

        }


        // =================================================
        // DELETE
        // =================================================

        elseif (
            $action === 'delete' &&
            $prescriptionId > 0
        ) {

            // ---------------------------------------------
            // Get file before deleting database record
            // ---------------------------------------------

           $fileName = '';

           $stmt = mysqli_prepare(
                $conn,
                "SELECT file_name
                 FROM prescriptions
                 WHERE id = ?
                 LIMIT 1"
            );

            if ($stmt) {

                mysqli_stmt_bind_param(
                    $stmt,
                    "i",
                    $prescriptionId
                );

                mysqli_stmt_execute($stmt);

                $result =
                    mysqli_stmt_get_result($stmt);

                if ($result) {

                    $row =
                        mysqli_fetch_assoc($result);

                    $fileName =
                        $row['file_name'] ?? '';

                }

                mysqli_stmt_close($stmt);
            }


            // ---------------------------------------------
            // Delete database record
            // ---------------------------------------------

            $stmt = mysqli_prepare(
                $conn,
                "DELETE FROM prescriptions
                 WHERE id = ?"
            );

            if ($stmt) {

                mysqli_stmt_bind_param(
                    $stmt,
                    "i",
                    $prescriptionId
                );

                if (mysqli_stmt_execute($stmt)) {

                    // -------------------------------------
                    // Delete physical file
                    // -------------------------------------

                    if ($fileName !== '') {

                        $filePath =
                            __DIR__ .
                            "/uploads/prescriptions/" .
                            basename($fileName);

                        if (is_file($filePath)) {
                            @unlink($filePath);
                        }
                    }

                    $message =
                        "Prescription deleted successfully.";

                    $messageType = "success";

                } else {

                    $message =
                        "Unable to delete prescription.";

                    $messageType = "error";
                }

                mysqli_stmt_close($stmt);

            }

        }

    }
}


// =====================================================
// FILTERS
// =====================================================

$search =
    trim($_GET['search'] ?? '');

$statusFilter =
    strtolower(
        trim($_GET['status'] ?? '')
    );

$allowedFilters = [
    '',
    'pending',
    'approved',
    'rejected'
];

if (!in_array($statusFilter, $allowedFilters, true)) {
    $statusFilter = '';
}


// =====================================================
// STATISTICS
// =====================================================

function getCount($conn, $sql)
{
    $result = mysqli_query($conn, $sql);

    if ($result) {

        $row = mysqli_fetch_assoc($result);

        return (int)(
            $row['total'] ?? 0
        );
    }

    return 0;
}


$totalPrescriptions = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM prescriptions"
);


$pendingPrescriptions = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM prescriptions
     WHERE status = 'pending'"
);


$approvedPrescriptions = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM prescriptions
     WHERE status = 'approved'"
);


$rejectedPrescriptions = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM prescriptions
     WHERE status = 'rejected'"
);


// =====================================================
// FETCH PRESCRIPTIONS
// =====================================================

$prescriptions = [];

$sql = "
    SELECT
        p.id,
        p.user_id,
        p.file_name,
        p.original_file_name,
        p.file_type,
        p.file_size,
        p.status,
        p.created_at,
        u.name AS customer_name,
        u.mobile AS customer_mobile,
        u.email AS customer_email
    FROM prescriptions p
    LEFT JOIN users u
        ON u.id = p.user_id
    WHERE 1 = 1
";


// =====================================================
// STATUS FILTER
// =====================================================

if ($statusFilter !== '') {

    $safeStatus =
        mysqli_real_escape_string(
            $conn,
            $statusFilter
        );

    $sql .= "
        AND p.status = '$safeStatus'
    ";
}


// =====================================================
// SEARCH
// =====================================================

if ($search !== '') {

    $safeSearch =
        mysqli_real_escape_string(
            $conn,
            $search
        );

    $sql .= "
        AND (
            u.name LIKE '%$safeSearch%'
            OR u.mobile LIKE '%$safeSearch%'
            OR u.email LIKE '%$safeSearch%'
            OR p.original_file_name LIKE '%$safeSearch%'
        )
    ";
}


// =====================================================
// ORDER
// =====================================================

$sql .= "
    ORDER BY p.id DESC
";


$result =
    mysqli_query(
        $conn,
        $sql
    );


if ($result) {

    while (
        $row =
        mysqli_fetch_assoc($result)
    ) {

        $prescriptions[] = $row;

    }
}


// =====================================================
// FILE HELPERS
// =====================================================

function formatFileSize($bytes)
{
    $bytes = (int)$bytes;

    if ($bytes <= 0) {
        return '-';
    }

    $units = [
        'B',
        'KB',
        'MB',
        'GB'
    ];

    $i = 0;

    while (
        $bytes >= 1024 &&
        $i < count($units) - 1
    ) {

        $bytes /= 1024;
        $i++;
    }

    return number_format(
        $bytes,
        $i === 0 ? 0 : 2
    ) . ' ' . $units[$i];
}


function getStatusClass($status)
{
    $status =
        strtolower(
            (string)$status
        );

    switch ($status) {

        case 'approved':
            return 'status-approved';

        case 'rejected':
            return 'status-rejected';

        case 'pending':
        default:
            return 'status-pending';
    }
}


function getFileIcon($fileType)
{
    $type =
        strtolower(
            (string)$fileType
        );

    if (strpos($type, 'pdf') !== false) {
        return '📕';
    }

    if (
        strpos($type, 'png') !== false ||
        strpos($type, 'jpg') !== false ||
        strpos($type, 'jpeg') !== false ||
        strpos($type, 'webp') !== false
    ) {
        return '🖼️';
    }

    return '📄';
}


// =====================================================
// PAGE TITLE
// =====================================================

$pageTitle =
    "Prescription Management";

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
   PAGE HEADER
===================================================== */

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
}

.page-heading h2 {
    font-size: 22px;
    color: #222;
}

.page-heading p {
    font-size: 12px;
    color: #999;
    margin-top: 5px;
}


/* =====================================================
   MESSAGE
===================================================== */

.message {
    padding: 13px 16px;
    border-radius: 9px;
    margin-bottom: 20px;
    font-size: 12px;
    font-weight: 500;
}

.message-success {
    background: #e8f7eb;
    color: #237d35;
    border: 1px solid #ccebd2;
}

.message-error {
    background: #ffe9eb;
    color: #a51d2d;
    border: 1px solid #f7c9ce;
}


/* =====================================================
   STATS
===================================================== */

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 22px;
}

.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #edf0f4;
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
    flex-shrink: 0;
}

.icon-blue {
    background: #e9f2ff;
}

.icon-orange {
    background: #fff3e4;
}

.icon-green {
    background: #e8f7eb;
}

.icon-red {
    background: #ffebed;
}

.stat-card h3 {
    font-size: 21px;
    color: #222;
    line-height: 1;
    margin-bottom: 6px;
}

.stat-card p {
    font-size: 11px;
    color: #999;
}


/* =====================================================
   FILTER PANEL
===================================================== */

.filter-panel {
    background: #fff;
    border: 1px solid #edf0f4;
    border-radius: 12px;
    padding: 18px;
    margin-bottom: 20px;
}

.filter-form {
    display: flex;
    gap: 10px;
    align-items: center;
}

.search-box {
    flex: 1;
    position: relative;
}

.search-box input {
    width: 100%;
    height: 42px;
    border: 1px solid #e1e5ea;
    border-radius: 8px;
    padding: 0 14px 0 40px;
    outline: none;
    font-size: 12px;
    color: #333;
}

.search-box input:focus {
    border-color: #51b848;
}

.search-icon {
    position: absolute;
    left: 14px;
    top: 12px;
    font-size: 15px;
    color: #999;
}

.filter-select {
    height: 42px;
    min-width: 150px;
    border: 1px solid #e1e5ea;
    border-radius: 8px;
    padding: 0 12px;
    background: #fff;
    outline: none;
    font-size: 12px;
    color: #555;
}

.filter-btn {
    height: 42px;
    border: none;
    background: #238b39;
    color: #fff;
    border-radius: 8px;
    padding: 0 18px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

.filter-btn:hover {
    background: #1c7630;
}

.reset-btn {
    height: 42px;
    border: 1px solid #e1e5ea;
    background: #fff;
    color: #666;
    border-radius: 8px;
    padding: 0 15px;
    font-size: 12px;
    cursor: pointer;
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
    padding: 17px 20px;
    border-bottom: 1px solid #edf0f4;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.panel-header h3 {
    font-size: 15px;
    color: #222;
}

.result-count {
    font-size: 11px;
    color: #999;
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
    min-width: 1050px;
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
   CUSTOMER
===================================================== */

.customer-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.customer-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #e9f2ff;
    color: #2369a0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
}

.customer-details strong {
    display: block;
    font-size: 12px;
    color: #333;
}

.customer-details span {
    display: block;
    color: #999;
    font-size: 10px;
    margin-top: 3px;
}


/* =====================================================
   FILE
===================================================== */

.file-cell {
    display: flex;
    align-items: center;
    gap: 9px;
    max-width: 260px;
}

.file-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: #f1eaff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.file-details {
    min-width: 0;
}

.file-name {
    font-size: 11px;
    font-weight: 600;
    color: #333;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.file-meta {
    font-size: 9px;
    color: #999;
    margin-top: 3px;
}


/* =====================================================
   BADGES
===================================================== */

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 9px;
    font-weight: 600;
    text-transform: capitalize;
}

.status-pending {
    background: #fff3cd;
    color: #9a6700;
}

.status-approved {
    background: #dff5e3;
    color: #14752f;
}

.status-rejected {
    background: #ffe3e6;
    color: #a51d2d;
}


/* =====================================================
   ACTIONS
===================================================== */

.actions {
    display: flex;
    align-items: center;
    gap: 5px;
    flex-wrap: wrap;
}

.action-btn {
    border: none;
    border-radius: 6px;
    padding: 7px 9px;
    font-size: 10px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.view-btn {
    background: #e9f2ff;
    color: #2369a0;
}

.download-btn {
    background: #f1eaff;
    color: #6040a0;
}

.approve-btn {
    background: #e8f7eb;
    color: #237d35;
}

.reject-btn {
    background: #fff3e4;
    color: #a15c00;
}

.pending-btn {
    background: #fff3cd;
    color: #8a6800;
}

.delete-btn {
    background: #ffe9eb;
    color: #a51d2d;
}

.action-btn:hover {
    opacity: .8;
}


/* =====================================================
   EMPTY
===================================================== */

.empty-state {
    padding: 60px 20px;
    text-align: center;
    color: #aaa;
}

.empty-icon {
    font-size: 40px;
    margin-bottom: 10px;
}

.empty-state h3 {
    font-size: 15px;
    color: #777;
    margin-bottom: 5px;
}

.empty-state p {
    font-size: 11px;
}


/* =====================================================
   CONFIRM MODAL
===================================================== */

.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 3000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-overlay.show {
    display: flex;
}

.modal-box {
    width: 100%;
    max-width: 400px;
    background: #fff;
    border-radius: 14px;
    padding: 25px;
    box-shadow: 0 20px 50px rgba(0,0,0,.15);
}

.modal-box h3 {
    font-size: 17px;
    margin-bottom: 8px;
}

.modal-box p {
    color: #888;
    font-size: 12px;
    line-height: 1.6;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 20px;
}

.modal-cancel {
    border: 1px solid #ddd;
    background: #fff;
    color: #666;
    padding: 9px 15px;
    border-radius: 7px;
    cursor: pointer;
    font-size: 11px;
}

.modal-confirm {
    border: none;
    background: #d63031;
    color: #fff;
    padding: 9px 15px;
    border-radius: 7px;
    cursor: pointer;
    font-size: 11px;
}


/* =====================================================
   MOBILE
===================================================== */

@media (max-width:1200px) {

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
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

}

@media (max-width:650px) {

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .page-header {
        display: block;
    }

    .page-heading {
        margin-bottom: 5px;
    }

    .filter-form {
        display: grid;
        grid-template-columns: 1fr;
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


        <a href="index.php">

            <span class="menu-icon">
                📊
            </span>

            Dashboard

        </a>


        <a href="medicines.php">

            <span class="menu-icon">
                💊
            </span>

            Medicines

        </a>


        <a href="orders.php">

            <span class="menu-icon">
                📦
            </span>

            Orders

        </a>


        <a
            href="prescriptions.php"
            class="active"
        >

            <span class="menu-icon">
                📄
            </span>

            Prescriptions

            <?php if ($pendingPrescriptions > 0): ?>

                <span class="menu-badge">

                    <?= number_format(
                        $pendingPrescriptions
                    ) ?>

                </span>

            <?php endif; ?>

        </a>


        <a href="deliveries.php">

            <span class="menu-icon">
                🚚
            </span>

            Deliveries

        </a>


        <a href="hero.php">

            <span class="menu-icon">
                🖼️
            </span>

            Hero Section

        </a>


        <a href="testimonials.php">

            <span class="menu-icon">
                💬
            </span>

            Testimonials

        </a>


        <a href="contact-messages.php">

            <span class="menu-icon">
                ✉️
            </span>

            Contact Messages

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
                Prescriptions
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


<!-- =====================================================
     PAGE HEADER
===================================================== -->

<div class="page-header">

    <div class="page-heading">

        <h2>
            Prescription Management
        </h2>

        <p>
            Review and manage customer uploaded prescriptions.
        </p>

    </div>

</div>


<!-- =====================================================
     MESSAGE
===================================================== -->

<?php if ($message !== ''): ?>

    <div
        class="
            message
            <?= $messageType === 'success'
                ? 'message-success'
                : 'message-error'
            ?>
        "
    >

        <?= e($message) ?>

    </div>

<?php endif; ?>


<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="stats-grid">


    <div class="stat-card">

        <div class="stat-icon icon-blue">
            📄
        </div>

        <div>

            <h3>
                <?= number_format(
                    $totalPrescriptions
                ) ?>
            </h3>

            <p>
                Total Prescriptions
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
                    $pendingPrescriptions
                ) ?>
            </h3>

            <p>
                Pending Review
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
                    $approvedPrescriptions
                ) ?>
            </h3>

            <p>
                Approved
            </p>

        </div>

    </div>


    <div class="stat-card">

        <div class="stat-icon icon-red">
            ❌
        </div>

        <div>

            <h3>
                <?= number_format(
                    $rejectedPrescriptions
                ) ?>
            </h3>

            <p>
                Rejected
            </p>

        </div>

    </div>


</div>


<!-- =====================================================
     FILTER
===================================================== -->

<div class="filter-panel">

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
                placeholder="Search customer, mobile, email or file name..."
                value="<?= e($search) ?>"
            >

        </div>


        <select
            name="status"
            class="filter-select"
        >

            <option
                value=""
                <?= $statusFilter === ''
                    ? 'selected'
                    : ''
                ?>
            >
                All Status
            </option>

            <option
                value="pending"
                <?= $statusFilter === 'pending'
                    ? 'selected'
                    : ''
                ?>
            >
                Pending
            </option>

            <option
                value="approved"
                <?= $statusFilter === 'approved'
                    ? 'selected'
                    : ''
                ?>
            >
                Approved
            </option>

            <option
                value="rejected"
                <?= $statusFilter === 'rejected'
                    ? 'selected'
                    : ''
                ?>
            >
                Rejected
            </option>

        </select>


        <button
            type="submit"
            class="filter-btn"
        >
            Search
        </button>


        <a
            href="prescriptions.php"
            class="reset-btn"
        >
            Reset
        </a>


    </form>

</div>


<!-- =====================================================
     PRESCRIPTION TABLE
===================================================== -->

<div class="panel">


    <div class="panel-header">

        <h3>
            Customer Prescriptions
        </h3>

        <span class="result-count">

            <?= number_format(
                count($prescriptions)
            ) ?>

            result(s)

        </span>

    </div>


    <?php if (!empty($prescriptions)): ?>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>
                            Customer
                        </th>

                        <th>
                            Prescription
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            Size
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Uploaded
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php foreach (
                    $prescriptions
                    as $prescription
                ): ?>


                    <?php

                    $customerName =
                        $prescription['customer_name']
                        ?: 'Customer';

                    $customerMobile =
                        $prescription['customer_mobile']
                        ?: '';

                    $customerEmail =
                        $prescription['customer_email']
                        ?: '';

                    $fileName =
                        $prescription['file_name']
                        ?? '';

                    $originalFileName =
                        $prescription['original_file_name']
                        ?: 'Prescription';

                    $fileType =
                        $prescription['file_type']
                        ?: 'Unknown';

                    $fileSize =
                        formatFileSize(
                            $prescription['file_size']
                            ?? 0
                        );

                    $status =
                        strtolower(
                            $prescription['status']
                            ?? 'pending'
                        );

                    $fileIcon =
                        getFileIcon(
                            $fileType
                        );


                    /*
                     * Uploaded prescription files
                     * are stored in:
                     *
                     * uploads/prescriptions/
                     */

                    $fileUrl =
                        'uploads/prescriptions/' .
                        rawurlencode(
                            basename($fileName)
                        );

                    ?>


                    <tr>


                        <!-- =================================
                             CUSTOMER
                        ================================== -->

                        <td>

                            <div class="customer-cell">


                                <div class="customer-avatar">

                                    <?= e(
                                        strtoupper(
                                            substr(
                                                $customerName,
                                                0,
                                                1
                                            )
                                        )
                                    ) ?>

                                </div>


                                <div class="customer-details">

                                    <strong>

                                        <?= e(
                                            $customerName
                                        ) ?>

                                    </strong>


                                    <?php if ($customerMobile): ?>

                                        <span>

                                            📱

                                            <?= e(
                                                $customerMobile
                                            ) ?>

                                        </span>

                                    <?php elseif ($customerEmail): ?>

                                        <span>

                                            ✉️

                                            <?= e(
                                                $customerEmail
                                            ) ?>

                                        </span>

                                    <?php endif; ?>

                                </div>


                            </div>

                        </td>


                        <!-- =================================
                             FILE
                        ================================== -->

                        <td>

                            <div class="file-cell">


                                <div class="file-icon">

                                    <?= $fileIcon ?>

                                </div>


                                <div class="file-details">

                                    <div
                                        class="file-name"
                                        title="<?= e(
                                            $originalFileName
                                        ) ?>"
                                    >

                                        <?= e(
                                            $originalFileName
                                        ) ?>

                                    </div>


                                    <div class="file-meta">

                                        Prescription ID:
                                        #<?= (int)
                                            $prescription['id']
                                        ?>

                                    </div>

                                </div>


                            </div>

                        </td>


                        <!-- =================================
                             TYPE
                        ================================== -->

                        <td>

                            <?= e(
                                strtoupper(
                                    $fileType
                                )
                            ) ?>

                        </td>


                        <!-- =================================
                             SIZE
                        ================================== -->

                        <td>

                            <?= e(
                                $fileSize
                            ) ?>

                        </td>


                        <!-- =================================
                             STATUS
                        ================================== -->

                        <td>

                            <span
                                class="
                                    status-badge
                                    <?= e(
                                        getStatusClass(
                                            $status
                                        )
                                    ) ?>
                                "
                            >

                                <?php if (
                                    $status === 'approved'
                                ): ?>

                                    ✓

                                <?php elseif (
                                    $status === 'rejected'
                                ): ?>

                                    ✕
                                
                                <?php else: ?>

                                    ⏳

                                <?php endif; ?>


                                <?= e(
                                    ucfirst($status)
                                ) ?>

                            </span>

                        </td>


                        <!-- =================================
                             DATE
                        ================================== -->

                        <td>

                            <?php

                            $createdAt =
                                strtotime(
                                    $prescription['created_at']
                                    ?? ''
                                );

                            ?>

                            <?php if ($createdAt): ?>

                                <strong
                                    style="
                                        display:block;
                                        font-size:11px;
                                    "
                                >

                                    <?= date(
                                        "d M Y",
                                        $createdAt
                                    ) ?>

                                </strong>

                                <span
                                    style="
                                        display:block;
                                        color:#999;
                                        font-size:9px;
                                        margin-top:3px;
                                    "
                                >

                                    <?= date(
                                        "h:i A",
                                        $createdAt
                                    ) ?>

                                </span>

                            <?php else: ?>

                                -

                            <?php endif; ?>

                        </td>


                        <!-- =================================
                             ACTIONS
                        ================================== -->

                        <td>

                            <div class="actions">


                                <?php if ($fileName !== ''): ?>


                                    <!-- VIEW -->

                                    <a
                                        href="<?= e(
                                            $fileUrl
                                        ) ?>"
                                        target="_blank"
                                        class="action-btn view-btn"
                                        title="View Prescription"
                                    >
                                        👁 View
                                    </a>


                                    <!-- DOWNLOAD -->

                                    <a
                                        href="<?= e(
                                            $fileUrl
                                        ) ?>"
                                        download="<?= e(
                                            $originalFileName
                                        ) ?>"
                                        class="action-btn download-btn"
                                        title="Download Prescription"
                                    >
                                        ⬇ Download
                                    </a>


                                <?php endif; ?>


                                <!-- APPROVE -->

                                <?php if (
                                    $status !== 'approved'
                                ): ?>

                                    <form
                                        method="POST"
                                        style="display:inline;"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(
                                                $csrfToken
                                            ) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="approve"
                                        >

                                        <input
                                            type="hidden"
                                            name="prescription_id"
                                            value="<?= (int)
                                                $prescription['id']
                                            ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="action-btn approve-btn"
                                            title="Approve"
                                        >
                                            ✓ Approve
                                        </button>

                                    </form>

                                <?php endif; ?>


                                <!-- REJECT -->

                                <?php if (
                                    $status !== 'rejected'
                                ): ?>

                                    <form
                                        method="POST"
                                        style="display:inline;"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(
                                                $csrfToken
                                            ) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="reject"
                                        >

                                        <input
                                            type="hidden"
                                            name="prescription_id"
                                            value="<?= (int)
                                                $prescription['id']
                                            ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="action-btn reject-btn"
                                            title="Reject"
                                        >
                                            ✕ Reject
                                        </button>

                                    </form>

                                <?php endif; ?>


                                <!-- PENDING -->

                                <?php if (
                                    $status !== 'pending'
                                ): ?>

                                    <form
                                        method="POST"
                                        style="display:inline;"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(
                                                $csrfToken
                                            ) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="pending"
                                        >

                                        <input
                                            type="hidden"
                                            name="prescription_id"
                                            value="<?= (int)
                                                $prescription['id']
                                            ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="action-btn pending-btn"
                                            title="Move to Pending"
                                        >
                                            ⏳ Pending
                                        </button>

                                    </form>

                                <?php endif; ?>


                                <!-- DELETE -->

                                <button
                                    type="button"
                                    class="action-btn delete-btn"
                                    onclick="confirmDelete(
                                        <?= (int)
                                            $prescription['id']
                                        ?>
                                    )"
                                    title="Delete Prescription"
                                >
                                    🗑 Delete
                                </button>


                            </div>

                        </td>


                    </tr>


                <?php endforeach; ?>


                </tbody>

            </table>

        </div>


    <?php else: ?>


        <div class="empty-state">

            <div class="empty-icon">
                📄
            </div>

            <h3>
                No Prescriptions Found
            </h3>

            <p>

                <?php if (
                    $search !== '' ||
                    $statusFilter !== ''
                ): ?>

                    Try changing your search or filter.

                <?php else: ?>

                    No customer prescriptions have been uploaded yet.

                <?php endif; ?>

            </p>

        </div>


    <?php endif; ?>


</div>


</div>


</main>


</div>


<!-- =====================================================
     DELETE MODAL
===================================================== -->

<div
    class="modal-overlay"
    id="deleteModal"
>

    <div class="modal-box">

        <h3>
            Delete Prescription?
        </h3>

        <p>
            This action will permanently delete the
            prescription record and uploaded file.
            This cannot be undone.
        </p>


        <div class="modal-actions">

            <button
                type="button"
                class="modal-cancel"
                onclick="closeDeleteModal()"
            >
                Cancel
            </button>


            <form
                method="POST"
                id="deleteForm"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(
                        $csrfToken
                    ) ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="delete"
                >

                <input
                    type="hidden"
                    name="prescription_id"
                    id="deletePrescriptionId"
                    value=""
                >

                <button
                    type="submit"
                    class="modal-confirm"
                >
                    Delete
                </button>

            </form>

        </div>

    </div>

</div>


<script>

// =====================================================
// SIDEBAR
// =====================================================

function toggleSidebar()
{
    const sidebar =
        document.getElementById("sidebar");

    sidebar.classList.toggle("show");
}


// =====================================================
// CLOSE MOBILE SIDEBAR
// =====================================================

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
                        .classList
                        .remove("show");

                }
            }
        );
    });


// =====================================================
// DELETE MODAL
// =====================================================

function confirmDelete(id)
{
    document
        .getElementById(
            "deletePrescriptionId"
        )
        .value = id;

    document
        .getElementById(
            "deleteModal"
        )
        .classList
        .add("show");
}


function closeDeleteModal()
{
    document
        .getElementById(
            "deleteModal"
        )
        .classList
        .remove("show");
}


// =====================================================
// CLOSE MODAL ON OUTSIDE CLICK
// =====================================================

document
    .getElementById("deleteModal")
    .addEventListener(
        "click",
        function(event)
        {
            if (
                event.target === this
            ) {
                closeDeleteModal();
            }
        }
    );

</script>


</body>

</html>

