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

function formatFileSize($bytes)
{
    $bytes = (int) $bytes;

    if ($bytes <= 0) {
        return 'N/A';
    }

    if ($bytes < 1024) {
        return $bytes . ' B';
    }

    if ($bytes < 1024 * 1024) {
        return round($bytes / 1024, 2) . ' KB';
    }

    return round($bytes / (1024 * 1024), 2) . ' MB';
}


// =====================================================
// CUSTOMER DATA
// =====================================================

$customer = null;

$stmt = $conn->prepare("
    SELECT name, mobile, email
    FROM users
    WHERE id = ?
    LIMIT 1
");

if ($stmt) {
    $stmt->bind_param("i", $customerId);
    $stmt->execute();

    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();

    $stmt->close();
}

if (!$customer) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

$customerName = $customer['name'] ?: 'Customer';


// =====================================================
// PRESCRIPTION STATS
// =====================================================

$totalPrescriptions = 0;
$pendingPrescriptions = 0;
$approvedPrescriptions = 0;
$rejectedPrescriptions = 0;

$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'pending'), 0) AS pending,
        COALESCE(SUM(status = 'approved'), 0) AS approved,
        COALESCE(SUM(status = 'rejected'), 0) AS rejected
    FROM prescriptions
    WHERE user_id = ?
");

if ($stmt) {
    $stmt->bind_param("i", $customerId);
    $stmt->execute();

    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();

    if ($stats) {
        $totalPrescriptions = (int) $stats['total'];
        $pendingPrescriptions = (int) $stats['pending'];
        $approvedPrescriptions = (int) $stats['approved'];
        $rejectedPrescriptions = (int) $stats['rejected'];
    }

    $stmt->close();
}


// =====================================================
// FETCH PRESCRIPTIONS
// =====================================================

$prescriptions = [];

$stmt = $conn->prepare("
    SELECT
        id,
        file_name,
        original_file_name,
        file_type,
        file_size,
        status,
        pharmacist_note,
        verified_at,
        created_at
    FROM prescriptions
    WHERE user_id = ?
    ORDER BY created_at DESC
");

if ($stmt) {
    $stmt->bind_param("i", $customerId);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $prescriptions[] = $row;
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

    <title>My Prescriptions | Medicine Aapki Gaw Mein</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Rubik', sans-serif;
            background: #f5f7f6;
            color: #263238;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* =====================================================
           SIDEBAR
        ===================================================== */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(
                180deg,
                #1f8b38,
                #166b2d
            );
            color: #fff;
            padding: 22px 15px;
            z-index: 1000;
            overflow-y: auto;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 10px 25px;
            border-bottom: 1px solid rgba(255,255,255,.15);
            margin-bottom: 22px;
        }

        .brand-icon {
            width: 43px;
            height: 43px;
            background: rgba(255,255,255,.18);
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .brand-title {
            font-size: 16px;
            font-weight: 600;
            line-height: 1.2;
        }

        .brand-subtitle {
            font-size: 11px;
            opacity: .75;
            margin-top: 3px;
        }

        .menu-section {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 1px;
            opacity: .65;
            padding: 0 12px;
            margin: 20px 0 8px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 13px;
            border-radius: 9px;
            margin-bottom: 4px;
            font-size: 14px;
            transition: .2s;
        }

        .menu-item:hover {
            background: rgba(255,255,255,.12);
        }

        .menu-item.active {
            background: rgba(255,255,255,.18);
            font-weight: 500;
        }

        .menu-icon {
            width: 23px;
            text-align: center;
            font-size: 17px;
        }

        /* =====================================================
           MAIN
        ===================================================== */

        .main {
            margin-left: 250px;
            min-height: 100vh;
        }

        .topbar {
            height: 75px;
            background: #fff;
            border-bottom: 1px solid #e7ebe8;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
        }

        .topbar-title {
            font-size: 22px;
            font-weight: 600;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9f6ec;
            color: #1f8b38;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .user-info strong {
            display: block;
            font-size: 13px;
        }

        .user-info span {
            font-size: 11px;
            color: #8a9590;
        }

        .content {
            padding: 30px;
        }

        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 25px;
        }

        .page-header h1 {
            font-size: 25px;
            margin-bottom: 6px;
        }

        .page-header p {
            color: #7c8781;
            font-size: 13px;
        }

        .btn {
            border: none;
            border-radius: 9px;
            padding: 12px 17px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(
                135deg,
                #238b39,
                #51b848
            );
            color: #fff;
            box-shadow: 0 5px 14px rgba(35,139,57,.18);
        }

        .btn-primary:hover {
            opacity: .94;
        }

        /* =====================================================
           STATS
        ===================================================== */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #e8ece9;
            border-radius: 13px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,.035);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            background: #eaf7ed;
        }

        .stat-info small {
            display: block;
            color: #87918b;
            font-size: 11px;
            margin-bottom: 4px;
        }

        .stat-info strong {
            font-size: 23px;
        }

        /* =====================================================
           CARD
        ===================================================== */

        .card {
            background: #fff;
            border: 1px solid #e8ece9;
            border-radius: 13px;
            box-shadow: 0 4px 15px rgba(0,0,0,.035);
            overflow: hidden;
        }

        .card-header {
            padding: 19px 22px;
            border-bottom: 1px solid #edf0ee;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header h3 {
            font-size: 16px;
        }

        .card-header span {
            font-size: 12px;
            color: #87918b;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th {
            background: #fafcfb;
            color: #78837d;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .4px;
            font-weight: 600;
            text-align: left;
            padding: 14px 18px;
        }

        td {
            padding: 16px 18px;
            border-top: 1px solid #edf0ee;
            font-size: 13px;
            vertical-align: middle;
        }

        .file-name {
            display: flex;
            align-items: center;
            gap: 11px;
            font-weight: 500;
        }

        .file-icon {
            width: 38px;
            height: 38px;
            background: #eaf7ed;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .file-sub {
            color: #909a94;
            font-size: 11px;
            margin-top: 3px;
            font-weight: 400;
        }

        /* =====================================================
           STATUS
        ===================================================== */

        .status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }

        .status.pending {
            background: #fff7df;
            color: #a27400;
        }

        .status.approved {
            background: #e9f8ed;
            color: #21803a;
        }

        .status.rejected {
            background: #ffebeb;
            color: #c33737;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 11px;
            border: 1px solid #dce6df;
            border-radius: 7px;
            color: #238b39;
            font-size: 12px;
            font-weight: 500;
            background: #fff;
        }

        .action-btn:hover {
            background: #f0f8f2;
        }

        /* =====================================================
           EMPTY
        ===================================================== */

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            width: 65px;
            height: 65px;
            margin: 0 auto 15px;
            border-radius: 18px;
            background: #eaf7ed;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 29px;
        }

        .empty-state h3 {
            font-size: 17px;
            margin-bottom: 7px;
        }

        .empty-state p {
            color: #89938d;
            font-size: 13px;
            margin-bottom: 18px;
        }

        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1100px) {

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 850px) {

            .sidebar {
                width: 215px;
            }

            .main {
                margin-left: 215px;
            }

            .content {
                padding: 22px;
            }

            .page-header {
                align-items: flex-start;
                flex-direction: column;
            }

        }

        @media (max-width: 600px) {

            .sidebar {
                position: static;
                width: 100%;
                height: auto;
            }

            .main {
                margin-left: 0;
            }

            .topbar {
                padding: 0 18px;
            }

            .content {
                padding: 18px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .user-info {
                display: none;
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

        <div>
            <div class="brand-title">
                Medicine Aapki Gaw Mein
            </div>

            <div class="brand-subtitle">
                Customer Panel
            </div>
        </div>

    </div>


    <div class="menu-section">
        MAIN
    </div>

    <a
        href="index.php"
        class="menu-item"
    >
        <span class="menu-icon">🏠</span>
        <span>Dashboard</span>
    </a>

    <a
        href="orders.php"
        class="menu-item"
    >
        <span class="menu-icon">📦</span>
        <span>My Orders</span>
    </a>

    <a
        href="../medicines.php"
        class="menu-item"
    >
        <span class="menu-icon">💊</span>
        <span>Browse Medicines</span>
    </a>

    <a
        href="../cart.php"
        class="menu-item"
    >
        <span class="menu-icon">🛒</span>
        <span>My Cart</span>
    </a>


    <div class="menu-section">
        PRESCRIPTION
    </div>

    <a
        href="prescriptions.php"
        class="menu-item active"
    >
        <span class="menu-icon">📄</span>
        <span>My Prescriptions</span>
    </a>

    <a
        href="upload-prescription.php"
        class="menu-item"
    >
        <span class="menu-icon">⬆️</span>
        <span>Upload Prescription</span>
    </a>


    <div class="menu-section">
        ACCOUNT
    </div>

    <a
        href="profile.php"
        class="menu-item"
    >
        <span class="menu-icon">👤</span>
        <span>My Profile</span>
    </a>

    <a
        href="addresses.php"
        class="menu-item"
    >
        <span class="menu-icon">📍</span>
        <span>My Addresses</span>
    </a>

    <a
        href="change-password.php"
        class="menu-item"
    >
        <span class="menu-icon">🔐</span>
        <span>Change Password</span>
    </a>


    <div class="menu-section">
        MORE
    </div>

    <a
        href="../index.php"
        class="menu-item"
    >
        <span class="menu-icon">🌐</span>
        <span>Visit Website</span>
    </a>

    <a
        href="../logout.php"
        class="menu-item"
    >
        <span class="menu-icon">🚪</span>
        <span>Logout</span>
    </a>

</aside>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <div class="topbar-title">
            My Prescriptions
        </div>

        <div class="topbar-right">

            <div class="user-avatar">
                <?php
                echo e(
                    strtoupper(
                        substr($customerName, 0, 1)
                    )
                );
                ?>
            </div>

            <div class="user-info">

                <strong>
                    <?php echo e($customerName); ?>
                </strong>

                <span>
                    Customer
                </span>

            </div>

        </div>

    </div>


    <!-- CONTENT -->

    <div class="content">


        <!-- PAGE HEADER -->

        <div class="page-header">

            <div>

                <h1>
                    My Prescriptions
                </h1>

                <p>
                    Upload and manage your medical prescriptions.
                </p>

            </div>

            <a
                href="upload-prescription.php"
                class="btn btn-primary"
            >
                ⬆️ Upload Prescription
            </a>

        </div>


        <!-- STATS -->

        <div class="stats-grid">

            <div class="stat-card">

                <div class="stat-icon">
                    📄
                </div>

                <div class="stat-info">

                    <small>
                        Total Prescriptions
                    </small>

                    <strong>
                        <?php echo $totalPrescriptions; ?>
                    </strong>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ⏳
                </div>

                <div class="stat-info">

                    <small>
                        Pending
                    </small>

                    <strong>
                        <?php echo $pendingPrescriptions; ?>
                    </strong>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ✅
                </div>

                <div class="stat-info">

                    <small>
                        Approved
                    </small>

                    <strong>
                        <?php echo $approvedPrescriptions; ?>
                    </strong>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ❌
                </div>

                <div class="stat-info">

                    <small>
                        Rejected
                    </small>

                    <strong>
                        <?php echo $rejectedPrescriptions; ?>
                    </strong>

                </div>

            </div>

        </div>


        <!-- PRESCRIPTION LIST -->

        <div class="card">

            <div class="card-header">

                <h3>
                    Prescription History
                </h3>

                <span>
                    <?php echo $totalPrescriptions; ?> file(s)
                </span>

            </div>


            <?php if (empty($prescriptions)): ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        📄
                    </div>

                    <h3>
                        No prescriptions found
                    </h3>

                    <p>
                        Upload your first prescription to get started.
                    </p>

                    <a
                        href="upload-prescription.php"
                        class="btn btn-primary"
                    >
                        ⬆️ Upload Prescription
                    </a>

                </div>

            <?php else: ?>

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

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
                                    Action
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($prescriptions as $prescription): ?>

                                <?php

                                $status = strtolower(
                                    (string) $prescription['status']
                                );

                                $extension = strtolower(
                                    pathinfo(
                                        $prescription['original_file_name'] ?: $prescription['file_name'],
                                        PATHINFO_EXTENSION
                                    )
                                );

                                $fileIcon = ($extension === 'pdf')
                                    ? '📕'
                                    : '🖼️';

                                ?>

                                <tr>

                                    <td>

                                        <div class="file-name">

                                            <div class="file-icon">
                                                <?php echo $fileIcon; ?>
                                            </div>

                                            <div>

                                                <div>
                                                    <?php
                                                    echo e(
                                                        $prescription['original_file_name']
                                                        ?: $prescription['file_name']
                                                    );
                                                    ?>
                                                </div>

                                                <div class="file-sub">
                                                    Prescription #<?php echo (int) $prescription['id']; ?>
                                                </div>

                                            </div>

                                        </div>

                                    </td>


                                    <td>
                                        <?php
                                        echo e(
                                            strtoupper(
                                                $extension ?: 'FILE'
                                            )
                                        );
                                        ?>
                                    </td>


                                    <td>
                                        <?php
                                        echo formatFileSize(
                                            $prescription['file_size']
                                        );
                                        ?>
                                    </td>


                                    <td>

                                        <span
                                            class="status <?php echo e($status); ?>"
                                        >

                                            <?php if ($status === 'approved'): ?>

                                                ✓ Approved

                                            <?php elseif ($status === 'rejected'): ?>

                                                ✕ Rejected

                                            <?php else: ?>

                                                ⏳ Pending

                                            <?php endif; ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php
                                        echo e(
                                            date(
                                                'd M Y',
                                                strtotime(
                                                    $prescription['created_at']
                                                )
                                            )
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <a
                                            href="prescription-details.php?id=<?php echo (int) $prescription['id']; ?>"
                                            class="action-btn"
                                        >
                                            👁 View
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>

</main>

</body>
</html>