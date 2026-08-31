<?php

session_start();

require_once "../config/database.php";


/* =====================================================
   ADMIN AUTHENTICATION
===================================================== */

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    strtolower($_SESSION['role']) !== 'admin'
) {
    header("Location: login.php");
    exit;
}


/* =====================================================
   CSRF TOKEN
===================================================== */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];


/* =====================================================
   HELPER
===================================================== */

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =====================================================
   VARIABLES
===================================================== */

$message = "";
$error = "";

$action = $_GET['action'] ?? 'list';

$viewId = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

$search = trim($_GET['search'] ?? '');

$filter = strtolower(
    trim($_GET['filter'] ?? 'all')
);

if (!in_array(
    $filter,
    ['all', 'unread', 'read'],
    true
)) {
    $filter = 'all';
}


/* =====================================================
   PAGINATION
===================================================== */

$perPage = 10;

$page = isset($_GET['page'])
    ? max(1, (int)$_GET['page'])
    : 1;


/* =====================================================
   CONTACT STATISTICS
===================================================== */

$totalMessages = 0;
$unreadMessages = 0;
$readMessages = 0;


/* Total */

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM contact_messages"
);

if ($result) {
    $row = $result->fetch_assoc();

    $totalMessages = (int)(
        $row['total'] ?? 0
    );
}


/* Unread */

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM contact_messages
     WHERE LOWER(status) != 'read'"
);

if ($result) {
    $row = $result->fetch_assoc();

    $unreadMessages = (int)(
        $row['total'] ?? 0
    );
}


/* Read */

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM contact_messages
     WHERE LOWER(status) = 'read'"
);

if ($result) {
    $row = $result->fetch_assoc();

    $readMessages = (int)(
        $row['total'] ?? 0
    );
}


/* =====================================================
   POST REQUEST
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postedToken = $_POST['csrf_token'] ?? '';

    if (
        empty($postedToken) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals(
            $_SESSION['csrf_token'],
            $postedToken
        )
    ) {

        $error =
            "Invalid request. Please refresh the page and try again.";

    } else {

        $postAction =
            $_POST['action'] ?? '';

        $id = (int)(
            $_POST['id'] ?? 0
        );


        /* =================================================
           DELETE
        ================================================= */

        if ($postAction === 'delete') {

            if ($id <= 0) {

                $error =
                    "Invalid message ID.";

            } else {

                $stmt = $conn->prepare(
                    "DELETE FROM contact_messages
                     WHERE id = ?"
                );

                if (!$stmt) {

                    $error =
                        "Database error.";

                } else {

                    $stmt->bind_param(
                        "i",
                        $id
                    );

                    if ($stmt->execute()) {

                        if (
                            $stmt->affected_rows > 0
                        ) {

                            $message =
                                "Contact message successfully deleted.";

                        } else {

                            $error =
                                "Message not found.";
                        }

                    } else {

                        $error =
                            "Message delete nahi ho payi.";
                    }

                    $stmt->close();
                }
            }
        }


        /* =================================================
           STATUS
        ================================================= */

        elseif ($postAction === 'status') {

            $status = strtolower(
                trim(
                    $_POST['status'] ?? ''
                )
            );


            if ($id <= 0) {

                $error =
                    "Invalid message ID.";

            } elseif (
                !in_array(
                    $status,
                    ['read', 'unread'],
                    true
                )
            ) {

                $error =
                    "Invalid message status.";

            } else {

                $stmt = $conn->prepare(
                    "UPDATE contact_messages
                     SET status = ?,
                         updated_at = NOW()
                     WHERE id = ?"
                );

                if (!$stmt) {

                    $error =
                        "Database error.";

                } else {

                    $stmt->bind_param(
                        "si",
                        $status,
                        $id
                    );

                    if ($stmt->execute()) {

                        $message =
                            $status === 'read'
                                ? "Message marked as read."
                                : "Message marked as unread.";

                    } else {

                        $error =
                            "Message status update nahi ho paya.";
                    }

                    $stmt->close();
                }
            }
        }
    }
}


/* =====================================================
   VIEW MESSAGE
===================================================== */

$contactMessage = null;

if (
    $action === 'view' &&
    $viewId > 0
) {

    $stmt = $conn->prepare(
        "SELECT *
         FROM contact_messages
         WHERE id = ?
         LIMIT 1"
    );

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $viewId
        );

        $stmt->execute();

        $result =
            $stmt->get_result();

        if (
            $result &&
            $result->num_rows === 1
        ) {

            $contactMessage =
                $result->fetch_assoc();


            /* Automatically mark as read */

            if (
                strtolower(
                    (string)(
                        $contactMessage['status']
                        ?? ''
                    )
                ) !== 'read'
            ) {

                $updateStmt =
                    $conn->prepare(
                        "UPDATE contact_messages
                         SET status = 'read',
                             updated_at = NOW()
                         WHERE id = ?"
                    );

                if ($updateStmt) {

                    $updateStmt->bind_param(
                        "i",
                        $viewId
                    );

                    $updateStmt->execute();

                    $updateStmt->close();

                    $contactMessage['status'] =
                        'read';
                }
            }

        } else {

            $error =
                "Contact message nahi mili.";

            $action =
                'list';
        }

        $stmt->close();

    } else {

        $error =
            "Database error.";
    }
}


/* =====================================================
   FETCH LIST
===================================================== */

$messages = null;

$totalFiltered = 0;

$totalPages = 1;


if ($action === 'list') {

    $conditions = [];

    $params = [];

    $types = "";


    /* Search */

    if ($search !== '') {

        $conditions[] = "
            (
                name LIKE ?
                OR phone LIKE ?
                OR email LIKE ?
                OR subject LIKE ?
                OR message LIKE ?
            )
        ";

        $searchTerm =
            "%" . $search . "%";

        for ($i = 0; $i < 5; $i++) {

            $params[] =
                $searchTerm;

            $types .= "s";
        }
    }


    /* Filter */

    if ($filter === 'unread') {

        $conditions[] =
            "LOWER(status) != 'read'";

    } elseif ($filter === 'read') {

        $conditions[] =
            "LOWER(status) = 'read'";
    }


    $whereSql = "";

    if (!empty($conditions)) {

        $whereSql =
            " WHERE " .
            implode(
                " AND ",
                $conditions
            );
    }


    /* =================================================
       COUNT FILTERED
    ================================================= */

    $countSql = "
        SELECT COUNT(*) AS total
        FROM contact_messages
        $whereSql
    ";

    $countStmt =
        $conn->prepare($countSql);

    if ($countStmt) {

        if (!empty($params)) {

            $countStmt->bind_param(
                $types,
                ...$params
            );
        }

        $countStmt->execute();

        $countResult =
            $countStmt->get_result();

        if ($countResult) {

            $countRow =
                $countResult->fetch_assoc();

            $totalFiltered =
                (int)(
                    $countRow['total']
                    ?? 0
                );
        }

        $countStmt->close();
    }


    /* =================================================
       PAGINATION
    ================================================= */

    $totalPages = max(
        1,
        (int)ceil(
            $totalFiltered /
            $perPage
        )
    );

    if ($page > $totalPages) {
        $page = $totalPages;
    }

    $offset =
        ($page - 1) *
        $perPage;


    /* =================================================
       FETCH DATA
    ================================================= */

    $sql = "
        SELECT *
        FROM contact_messages
        $whereSql
        ORDER BY id DESC
        LIMIT ?, ?
    ";

    $stmt =
        $conn->prepare($sql);

    if ($stmt) {

        $dataParams =
            $params;

        $dataTypes =
            $types . "ii";

        $dataParams[] =
            $offset;

        $dataParams[] =
            $perPage;


        $stmt->bind_param(
            $dataTypes,
            ...$dataParams
        );

        $stmt->execute();

        $messages =
            $stmt->get_result();

        /*
         * Do not close statement here
         * before fetching all rows.
         */
    }
}


/* =====================================================
   PAGE TITLE
===================================================== */

$pageTitle =
    $action === 'view'
        ? 'View Contact Message'
        : 'Contact Messages';

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
    box-sizing: border-box;
}

body {
    margin: 0;
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
textarea,
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
    padding: 24px 20px;
    border-bottom:
        1px solid
        rgba(255,255,255,.12);
}

.brand-icon {
    width: 46px;
    height: 46px;
    background:
        rgba(255,255,255,.15);
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 23px;
    margin-bottom: 11px;
}

.sidebar-brand h2 {
    margin: 0;
    font-size: 17px;
    line-height: 1.4;
}

.sidebar-brand p {
    margin: 5px 0 0;
    font-size: 11px;
    color:
        rgba(255,255,255,.7);
}

.sidebar-menu {
    padding: 18px 12px;
}

.menu-title {
    color:
        rgba(255,255,255,.5);
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 10px 12px;
}

.sidebar-menu a {
    display: flex;
    align-items: center;
    gap: 11px;
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
    width: 23px;
    text-align: center;
}

.menu-badge {
    margin-left: auto;
    background: #fff;
    color: #238438;
    border-radius: 20px;
    padding: 3px 8px;
    font-size: 9px;
    font-weight: 700;
}


/* =====================================================
   MAIN
===================================================== */

.main {
    margin-left: 250px;
    width: calc(100% - 250px);
    min-height: 100vh;
}


/* =====================================================
   TOPBAR
===================================================== */

.topbar {
    height: 72px;
    background: #fff;
    border-bottom:
        1px solid #e8edf2;
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
    gap: 10px;
}

.topbar-title {
    font-size: 20px;
    font-weight: 600;
    color: #222;
}

.admin-user {
    font-size: 12px;
    color: #888;
}

.admin-user strong {
    color: #333;
}


/* =====================================================
   CONTENT
===================================================== */

.content {
    padding: 30px;
}

.page-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 22px;
}

.page-heading h1 {
    margin: 0;
    font-size: 24px;
    color: #222;
}

.page-heading p {
    margin: 5px 0 0;
    color: #999;
    font-size: 11px;
}


/* =====================================================
   STATISTICS
===================================================== */

.stats-grid {
    display: grid;
    grid-template-columns:
        repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 22px;
}

.stat-card {
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 11px;
    padding: 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: .2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow:
        0 8px 25px
        rgba(0,0,0,.06);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
    flex-shrink: 0;
}

.stat-green {
    background: #e8f7eb;
}

.stat-orange {
    background: #fff3e4;
}

.stat-blue {
    background: #e9f2ff;
}

.stat-card small {
    display: block;
    color: #999;
    font-size: 10px;
    margin-bottom: 5px;
}

.stat-card strong {
    display: block;
    font-size: 23px;
    color: #222;
}


/* =====================================================
   BUTTONS
===================================================== */

.btn {
    border: 0;
    border-radius: 7px;
    padding: 10px 15px;
    cursor: pointer;
    font-size: 11px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    transition: .2s;
}

.btn-primary {
    background: #51b848;
    color: #fff;
}

.btn-primary:hover {
    background: #3f9f39;
}

.btn-danger {
    background: #dc3545;
    color: #fff;
}

.btn-danger:hover {
    background: #bb2d3b;
}

.btn-secondary {
    background: #6c757d;
    color: #fff;
}

.btn-secondary:hover {
    background: #5c636a;
}

.btn-warning {
    background: #ffc107;
    color: #222;
}

.btn-warning:hover {
    background: #e0a800;
}

.btn-info {
    background: #0d6efd;
    color: #fff;
}

.btn-info:hover {
    background: #0b5ed7;
}

.btn-small {
    padding: 7px 9px;
    font-size: 9px;
}


/* =====================================================
   ALERT
===================================================== */

.alert {
    padding: 13px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 12px;
}

.alert-success {
    background: #e8f7eb;
    color: #26733a;
    border: 1px solid #c8e9ce;
}

.alert-danger {
    background: #fdecec;
    color: #a32834;
    border: 1px solid #f3c5ca;
}


/* =====================================================
   SEARCH / FILTER
===================================================== */

.search-box {
    background: #fff;
    padding: 17px;
    border-radius: 10px;
    margin-bottom: 20px;
    border: 1px solid #e9edf1;
}

.search-form {
    display: grid;
    grid-template-columns:
        minmax(200px, 1fr)
        150px
        auto
        auto;
    gap: 10px;
}

.form-control {
    width: 100%;
    border: 1px solid #dfe4e8;
    border-radius: 7px;
    padding: 10px 12px;
    outline: none;
    font-size: 11px;
    background: #fff;
}

.form-control:focus {
    border-color: #51b848;
    box-shadow:
        0 0 0 3px
        rgba(81,184,72,.10);
}


/* =====================================================
   FILTER TABS
===================================================== */

.filter-tabs {
    display: flex;
    gap: 7px;
    margin-bottom: 18px;
    flex-wrap: wrap;
}

.filter-tab {
    background: #fff;
    border: 1px solid #e3e8ec;
    border-radius: 20px;
    padding: 8px 14px;
    font-size: 10px;
    color: #777;
    transition: .2s;
}

.filter-tab:hover {
    border-color: #51b848;
    color: #278c3c;
}

.filter-tab.active {
    background: #51b848;
    color: #fff;
    border-color: #51b848;
}

.filter-count {
    margin-left: 4px;
    font-weight: 700;
}


/* =====================================================
   CARD
===================================================== */

.card {
    background: #fff;
    border-radius: 11px;
    border: 1px solid #e7ebef;
    overflow: hidden;
}


/* =====================================================
   TABLE
===================================================== */

.table-wrap {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1050px;
}

th,
td {
    padding: 13px 14px;
    text-align: left;
    border-bottom:
        1px solid #edf0f3;
    font-size: 12px;
}

th {
    background: #fafbfc;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .4px;
    color: #777;
    font-weight: 600;
}

tbody tr:hover {
    background: #fcfdfd;
}

tbody tr.unread-row {
    background: #f7fff8;
}

tbody tr.unread-row:hover {
    background: #f0fbf2;
}


/* =====================================================
   CONTACT
===================================================== */

.contact-name {
    font-weight: 600;
    color: #333;
}

.contact-email {
    color: #777;
    font-size: 10px;
    margin-top: 4px;
}

.phone {
    color: #555;
}

.subject {
    font-weight: 600;
    color: #333;
    max-width: 170px;
}

.message-preview {
    max-width: 260px;
    color: #777;
    line-height: 1.5;
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

.badge-success {
    background: #e4f7e7;
    color: #278139;
}

.badge-warning {
    background: #fff4d6;
    color: #8a6900;
}


/* =====================================================
   ACTIONS
===================================================== */

.action-buttons {
    display: flex;
    align-items: center;
    gap: 5px;
    flex-wrap: wrap;
}

.action-buttons form {
    margin: 0;
}


/* =====================================================
   PAGINATION
===================================================== */

.pagination-area {
    padding: 15px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    border-top: 1px solid #edf0f3;
}

.pagination-info {
    color: #999;
    font-size: 10px;
}

.pagination {
    display: flex;
    align-items: center;
    gap: 5px;
    flex-wrap: wrap;
}

.page-link {
    min-width: 31px;
    height: 31px;
    padding: 0 8px;
    border: 1px solid #e1e6ea;
    background: #fff;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: #666;
}

.page-link:hover {
    border-color: #51b848;
    color: #278c3c;
}

.page-link.active {
    background: #51b848;
    border-color: #51b848;
    color: #fff;
}

.page-link.disabled {
    opacity: .5;
    pointer-events: none;
}


/* =====================================================
   EMPTY
===================================================== */

.empty {
    padding: 65px 20px !important;
    text-align: center !important;
    color: #999;
}

.empty-icon {
    font-size: 38px;
    margin-bottom: 10px;
}


/* =====================================================
   MESSAGE VIEW
===================================================== */

.message-card {
    background: #fff;
    border-radius: 11px;
    border: 1px solid #e7ebef;
    padding: 30px;
}

.message-header {
    padding-bottom: 20px;
    border-bottom: 1px solid #edf0f3;
    margin-bottom: 25px;
}

.message-header h2 {
    margin: 0 0 12px;
    font-size: 20px;
    color: #222;
}

.message-meta {
    display: grid;
    grid-template-columns:
        repeat(2, minmax(0, 1fr));
    gap: 15px;
}

.meta-item {
    background: #f8fafb;
    border-radius: 8px;
    padding: 12px 14px;
}

.meta-label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: #999;
    margin-bottom: 5px;
}

.meta-value {
    font-size: 12px;
    color: #333;
    word-break: break-word;
}

.message-body {
    margin-top: 25px;
}

.message-body h3 {
    margin: 0 0 10px;
    font-size: 14px;
    color: #333;
}

.message-text {
    background: #fafbfc;
    border: 1px solid #edf0f3;
    border-radius: 8px;
    padding: 18px;
    font-size: 13px;
    line-height: 1.8;
    color: #555;
    white-space: pre-wrap;
    word-break: break-word;
}

.view-actions {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}


/* =====================================================
   MOBILE
===================================================== */

.mobile-menu-btn {
    display: none;
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 8px;
    background: #eaf7ec;
    color: #278c3c;
    font-size: 18px;
    cursor: pointer;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1100px) {

    .stats-grid {
        grid-template-columns:
            repeat(3, 1fr);
    }

    .search-form {
        grid-template-columns:
            1fr 150px auto auto;
    }
}


@media (max-width: 900px) {

    .sidebar {
        transform:
            translateX(-100%);
        transition: .25s;
    }

    .sidebar.show {
        transform:
            translateX(0);
    }

    .main {
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

    .stats-grid {
        grid-template-columns:
            repeat(3, 1fr);
    }
}


@media (max-width: 700px) {

    .stats-grid {
        grid-template-columns:
            1fr;
    }

    .search-form {
        grid-template-columns: 1fr;
    }

    .search-form .btn {
        width: 100%;
    }

    .page-heading {
        align-items: flex-start;
    }

    .message-meta {
        grid-template-columns: 1fr;
    }

    .pagination-area {
        flex-direction: column;
        align-items: flex-start;
    }
}


@media (max-width: 600px) {

    .topbar-title {
        font-size: 17px;
    }

    .admin-user {
        display: none;
    }

    .content {
        padding: 15px;
    }

    .page-heading {
        flex-direction: column;
    }

    .page-heading h1 {
        font-size: 20px;
    }

    .message-card {
        padding: 20px 15px;
    }

    .view-actions {
        flex-direction: column;
    }

    .view-actions .btn,
    .view-actions form,
    .view-actions form .btn {
        width: 100%;
    }

    .filter-tabs {
        gap: 5px;
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


        <a href="prescriptions.php">

            <span class="menu-icon">
                📄
            </span>

            Prescriptions

        </a>


        <a href="deliveries.php">

            <span class="menu-icon">
                🚚
            </span>

            Deliveries

        </a>


        <a
            href="contact-messages.php"
            class="active"
        >

            <span class="menu-icon">
                ✉️
            </span>

            <span>
                Contact Messages
            </span>

            <?php if (
                $unreadMessages > 0
            ): ?>

                <span class="menu-badge">
                    <?= number_format(
                        $unreadMessages
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

<main class="main">


<header class="topbar">

    <div class="topbar-left">

        <button
            class="mobile-menu-btn"
            type="button"
            onclick="toggleSidebar()"
        >
            ☰
        </button>


        <div class="topbar-title">
            Contact Messages
        </div>

    </div>


    <div class="admin-user">

        Welcome,

        <strong>
            <?= e(
                $_SESSION['name']
                ?? 'Admin'
            ) ?>
        </strong>

    </div>

</header>


<!-- =====================================================
     CONTENT
===================================================== -->

<div class="content">


<?php if ($message !== ''): ?>

    <div class="alert alert-success">

        <?= e($message) ?>

    </div>

<?php endif; ?>


<?php if ($error !== ''): ?>

    <div class="alert alert-danger">

        <?= e($error) ?>

    </div>

<?php endif; ?>


<!-- =====================================================
     LIST
===================================================== -->

<?php if ($action === 'list'): ?>


<div class="page-heading">

    <div>

        <h1>
            Contact Messages
        </h1>

        <p>
            Manage and respond to website contact enquiries.
        </p>

    </div>

</div>


<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="stats-grid">


    <div class="stat-card">

        <div class="stat-icon stat-blue">
            💬
        </div>

        <div>

            <small>
                Total Messages
            </small>

            <strong>
                <?= number_format(
                    $totalMessages
                ) ?>
            </strong>

        </div>

    </div>


    <div class="stat-card">

        <div class="stat-icon stat-orange">
            📩
        </div>

        <div>

            <small>
                Unread Messages
            </small>

            <strong>
                <?= number_format(
                    $unreadMessages
                ) ?>
            </strong>

        </div>

    </div>


    <div class="stat-card">

        <div class="stat-icon stat-green">
            ✅
        </div>

        <div>

            <small>
                Read Messages
            </small>

            <strong>
                <?= number_format(
                    $readMessages
                ) ?>
            </strong>

        </div>

    </div>


</div>


<!-- =====================================================
     SEARCH
===================================================== -->

<div class="search-box">

    <form
        method="GET"
        class="search-form"
    >

        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="Search name, phone, email, subject or message..."
            value="<?= e($search) ?>"
        >


        <select
            name="filter"
            class="form-control"
        >

            <option
                value="all"
                <?= $filter === 'all'
                    ? 'selected'
                    : '' ?>
            >
                All Messages
            </option>

            <option
                value="unread"
                <?= $filter === 'unread'
                    ? 'selected'
                    : '' ?>
            >
                Unread
            </option>

            <option
                value="read"
                <?= $filter === 'read'
                    ? 'selected'
                    : '' ?>
            >
                Read
            </option>

        </select>


        <button
            type="submit"
            class="btn btn-primary"
        >
            🔍 Search
        </button>


        <?php if (
            $search !== '' ||
            $filter !== 'all'
        ): ?>

            <a
                href="contact-messages.php"
                class="btn btn-secondary"
            >
                Clear
            </a>

        <?php endif; ?>

    </form>

</div>


<!-- =====================================================
     FILTER TABS
===================================================== -->

<div class="filter-tabs">

    <a
        href="contact-messages.php"
        class="filter-tab
            <?= $filter === 'all'
                ? 'active'
                : '' ?>"
    >

        All

        <span class="filter-count">
            <?= number_format(
                $totalMessages
            ) ?>
        </span>

    </a>


    <a
        href="contact-messages.php?filter=unread"
        class="filter-tab
            <?= $filter === 'unread'
                ? 'active'
                : '' ?>"
    >

        Unread

        <span class="filter-count">
            <?= number_format(
                $unreadMessages
            ) ?>
        </span>

    </a>


    <a
        href="contact-messages.php?filter=read"
        class="filter-tab
            <?= $filter === 'read'
                ? 'active'
                : '' ?>"
    >

        Read

        <span class="filter-count">
            <?= number_format(
                $readMessages
            ) ?>
        </span>

    </a>

</div>


<!-- =====================================================
     TABLE
===================================================== -->

<div class="card">

<div class="table-wrap">

<table>

<thead>

<tr>

    <th>#</th>

    <th>Contact</th>

    <th>Phone</th>

    <th>Subject</th>

    <th>Message</th>

    <th>Status</th>

    <th>Date</th>

    <th>Actions</th>

</tr>

</thead>


<tbody>


<?php if (
    $messages &&
    $messages->num_rows > 0
): ?>


<?php while (
    $row =
    $messages->fetch_assoc()
): ?>


<?php

$status =
    strtolower(
        trim(
            (string)(
                $row['status']
                ?? ''
            )
        )
    );

$isUnread =
    $status !== 'read';

?>


<tr
    class="<?= $isUnread
        ? 'unread-row'
        : '' ?>"
>


<!-- ID -->

<td>

<strong>
    #<?= (int)$row['id'] ?>
</strong>

</td>


<!-- CONTACT -->

<td>

<div class="contact-name">

<?= e(
    $row['name']
    ?? '-'
) ?>

</div>


<?php if (
    !empty(
        $row['email']
    )
): ?>

<div class="contact-email">

<?= e(
    $row['email']
) ?>

</div>

<?php endif; ?>

</td>


<!-- PHONE -->

<td>

<div class="phone">

<?= e(
    $row['phone']
    ?? '-'
) ?>

</div>

</td>


<!-- SUBJECT -->

<td>

<div class="subject">

<?= e(
    $row['subject']
    ?? '-'
) ?>

</div>

</td>


<!-- MESSAGE -->

<td>

<div class="message-preview">

<?php

$messageText =
    trim(
        (string)(
            $row['message']
            ?? ''
        )
    );

if (
    strlen($messageText) > 100
) {

    echo e(
        substr(
            $messageText,
            0,
            100
        )
    );

    echo '...';

} else {

    echo e(
        $messageText
        ?: '-'
    );
}

?>

</div>

</td>


<!-- STATUS -->

<td>

<?php if (
    $status === 'read'
): ?>

<span class="badge badge-success">
    ✓ Read
</span>

<?php else: ?>

<span class="badge badge-warning">
    ● Unread
</span>

<?php endif; ?>

</td>


<!-- DATE -->

<td>

<?php

$createdAt =
    $row['created_at']
    ?? '';

if (
    !empty($createdAt)
) {

    $timestamp =
        strtotime($createdAt);

    if (
        $timestamp !== false
    ) {

        echo e(
            date(
                'd M Y',
                $timestamp
            )
        );

        echo '<div style="
            font-size:10px;
            color:#999;
            margin-top:4px;
        ">';

        echo e(
            date(
                'h:i A',
                $timestamp
            )
        );

        echo '</div>';

    } else {

        echo e(
            $createdAt
        );
    }

} else {

    echo '-';
}

?>

</td>


<!-- ACTIONS -->

<td>

<div class="action-buttons">


<!-- VIEW -->

<a
    href="contact-messages.php?action=view&id=<?= (int)$row['id'] ?>"
    class="btn btn-info btn-small"
>
    👁 View
</a>


<!-- STATUS -->

<form method="POST">

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
    value="status"
>


<input
    type="hidden"
    name="id"
    value="<?= (int)$row['id'] ?>"
>


<input
    type="hidden"
    name="status"
    value="<?= $status === 'read'
        ? 'unread'
        : 'read' ?>"
>


<button
    type="submit"
    class="btn
        <?= $status === 'read'
            ? 'btn-warning'
            : 'btn-primary' ?>
        btn-small"
>

    <?= $status === 'read'
        ? '↩ Unread'
        : '✓ Read' ?>

</button>

</form>


<!-- DELETE -->

<form
    method="POST"
    onsubmit="
        return confirm(
            'Are you sure you want to permanently delete this message?'
        );
    "
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
    name="id"
    value="<?= (int)$row['id'] ?>"
>


<button
    type="submit"
    class="btn btn-danger btn-small"
>
    🗑 Delete
</button>

</form>


</div>

</td>


</tr>


<?php endwhile; ?>


<?php else: ?>


<tr>

<td
    colspan="8"
    class="empty"
>

<div class="empty-icon">
    💬
</div>


<?php if (
    $search !== ''
): ?>

No messages found for

<strong>
    "<?= e($search) ?>"
</strong>.

<?php elseif (
    $filter === 'unread'
): ?>

No unread messages found.

<?php elseif (
    $filter === 'read'
): ?>

No read messages found.

<?php else: ?>

No contact messages found yet.

<?php endif; ?>

</td>

</tr>


<?php endif; ?>


</tbody>

</table>

</div>


<!-- =====================================================
     PAGINATION
===================================================== -->

<?php if (
    $totalFiltered > 0
): ?>

<div class="pagination-area">


<div class="pagination-info">

Showing

<strong>
    <?= number_format(
        min(
            $offset + 1,
            $totalFiltered
        )
    ) ?>
</strong>

to

<strong>
    <?= number_format(
        min(
            $offset + $perPage,
            $totalFiltered
        )
    ) ?>
</strong>

of

<strong>
    <?= number_format(
        $totalFiltered
    ) ?>
</strong>

messages

</div>


<div class="pagination">


<?php

$queryBase = [];

if ($search !== '') {
    $queryBase['search'] =
        $search;
}

if ($filter !== 'all') {
    $queryBase['filter'] =
        $filter;
}

?>


<?php if (
    $page > 1
): ?>

<?php

$queryBase['page'] =
    $page - 1;

?>

<a
    href="contact-messages.php?<?= e(
        http_build_query(
            $queryBase
        )
    ) ?>"
    class="page-link"
>
    ←
</a>

<?php else: ?>

<span class="page-link disabled">
    ←
</span>

<?php endif; ?>


<?php

$startPage =
    max(
        1,
        $page - 2
    );

$endPage =
    min(
        $totalPages,
        $page + 2
    );

for (
    $p = $startPage;
    $p <= $endPage;
    $p++
):

    $queryBase['page'] =
        $p;

?>

<a
    href="contact-messages.php?<?= e(
        http_build_query(
            $queryBase
        )
    ) ?>"
    class="page-link
        <?= $p === $page
            ? 'active'
            : '' ?>"
>
    <?= $p ?>
</a>

<?php endfor; ?>


<?php if (
    $page < $totalPages
): ?>

<?php

$queryBase['page'] =
    $page + 1;

?>

<a
    href="contact-messages.php?<?= e(
        http_build_query(
            $queryBase
        )
    ) ?>"
    class="page-link"
>
    →
</a>

<?php else: ?>

<span class="page-link disabled">
    →
</span>

<?php endif; ?>


</div>

</div>

<?php endif; ?>


</div>


<!-- =====================================================
     VIEW MESSAGE
===================================================== -->

<?php else: ?>


<?php if (
    $contactMessage !== null
): ?>


<div class="page-heading">

    <div>

        <h1>
            Contact Message
        </h1>

        <p>
            View complete customer enquiry.
        </p>

    </div>


    <a
        href="contact-messages.php"
        class="btn btn-secondary"
    >
        ← Back
    </a>

</div>


<div class="message-card">


<!-- =================================================
     HEADER
================================================= -->

<div class="message-header">

    <h2>

        <?= e(
            $contactMessage['subject']
            ?? 'No Subject'
        ) ?>

    </h2>


<?php

$viewStatus =
    strtolower(
        trim(
            (string)(
                $contactMessage['status']
                ?? ''
            )
        )
    );

?>


<?php if (
    $viewStatus === 'read'
): ?>

<span class="badge badge-success">
    ✓ Read
</span>

<?php else: ?>

<span class="badge badge-warning">
    ● Unread
</span>

<?php endif; ?>

</div>


<!-- =================================================
     CONTACT INFORMATION
================================================= -->

<div class="message-meta">


<!-- NAME -->

<div class="meta-item">

    <div class="meta-label">
        Name
    </div>

    <div class="meta-value">

        <?= e(
            $contactMessage['name']
            ?? '-'
        ) ?>

    </div>

</div>


<!-- PHONE -->

<div class="meta-item">

    <div class="meta-label">
        Phone
    </div>

    <div class="meta-value">

        <?= e(
            $contactMessage['phone']
            ?? '-'
        ) ?>

    </div>

</div>


<!-- EMAIL -->

<div class="meta-item">

    <div class="meta-label">
        Email
    </div>

    <div class="meta-value">

        <?php if (
            !empty(
                $contactMessage['email']
            )
        ): ?>

            <a
                href="mailto:<?= e(
                    $contactMessage['email']
                ) ?>"
                style="
                    color:#278c3c;
                    font-weight:500;
                "
            >

                <?= e(
                    $contactMessage['email']
                ) ?>

            </a>

        <?php else: ?>

            -

        <?php endif; ?>

    </div>

</div>


<!-- RECEIVED -->

<div class="meta-item">

    <div class="meta-label">
        Received
    </div>

    <div class="meta-value">

        <?php

        $viewCreatedAt =
            $contactMessage[
                'created_at'
            ] ?? '';

        if (
            !empty($viewCreatedAt) &&
            strtotime(
                $viewCreatedAt
            ) !== false
        ) {

            echo e(
                date(
                    'd M Y, h:i A',
                    strtotime(
                        $viewCreatedAt
                    )
                )
            );

        } else {

            echo '-';
        }

        ?>

    </div>

</div>


</div>


<!-- =================================================
     MESSAGE
===================================================== -->

<div class="message-body">

    <h3>
        Customer Message
    </h3>


    <div class="message-text">

        <?= e(
            $contactMessage['message']
            ?? ''
        ) ?>

    </div>

</div>


<!-- =================================================
     ACTIONS
===================================================== -->

<div class="view-actions">


<a
    href="contact-messages.php"
    class="btn btn-secondary"
>
    ← Back to Messages
</a>


<!-- STATUS -->

<form method="POST">

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
    value="status"
>


<input
    type="hidden"
    name="id"
    value="<?= (int)(
        $contactMessage['id']
    ) ?>"
>


<input
    type="hidden"
    name="status"
    value="<?= $viewStatus === 'read'
        ? 'unread'
        : 'read' ?>"
>


<button
    type="submit"
    class="btn
        <?= $viewStatus === 'read'
            ? 'btn-warning'
            : 'btn-primary' ?>"
>

    <?= $viewStatus === 'read'
        ? '↩ Mark Unread'
        : '✓ Mark Read' ?>

</button>

</form>


<!-- DELETE -->

<form
    method="POST"
    onsubmit="
        return confirm(
            'Are you sure you want to permanently delete this message?'
        );
    "
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
    name="id"
    value="<?= (int)(
        $contactMessage['id']
    ) ?>"
>


<button
    type="submit"
    class="btn btn-danger"
>
    🗑 Delete Message
</button>

</form>


<?php if (
    !empty(
        $contactMessage['email']
    )
): ?>

<a
    href="mailto:<?= e(
        $contactMessage['email']
    ) ?>"
    class="btn btn-primary"
>
    ✉️ Reply by Email
</a>

<?php endif; ?>


</div>


</div>


<?php else: ?>


<div class="card">

    <div class="empty">

        <div class="empty-icon">
            ⚠️
        </div>

        Contact message not found.

        <br><br>

        <a
            href="contact-messages.php"
            class="btn btn-secondary"
        >
            ← Back to Messages
        </a>

    </div>

</div>


<?php endif; ?>


<?php endif; ?>


</div>

</main>

</div>


<script>

function toggleSidebar()
{
    const sidebar =
        document.getElementById(
            "sidebar"
        );

    sidebar.classList.toggle(
        "show"
    );
}


/* Close sidebar on mobile */

document
    .querySelectorAll(
        ".sidebar a"
    )
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
                        .getElementById(
                            "sidebar"
                        )
                        .classList.remove(
                            "show"
                        );
                }
            }
        );
    });

</script>


</body>

</html>
