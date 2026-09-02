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

    return round(
        $bytes / (1024 * 1024),
        2
    ) . ' MB';
}


// =====================================================
// GET PRESCRIPTION ID
// =====================================================

$prescriptionId =
    isset($_GET['id'])
        ? (int) $_GET['id']
        : 0;


if ($prescriptionId <= 0) {

    header(
        "Location: prescriptions.php"
    );

    exit;
}


// =====================================================
// FETCH PRESCRIPTION
// CUSTOMER OWNERSHIP CHECK
// =====================================================

$prescription = null;

$stmt = $conn->prepare("
    SELECT
        id,
        user_id,
        file_name,
        original_file_name,
        file_type,
        file_size,
        status,
        pharmacist_note,
        verified_by,
        verified_at,
        created_at,
        updated_at
    FROM prescriptions
    WHERE id = ?
      AND user_id = ?
    LIMIT 1
");


if ($stmt) {

    $stmt->bind_param(
        "ii",
        $prescriptionId,
        $customerId
    );

    $stmt->execute();

    $result =
        $stmt->get_result();

    $prescription =
        $result->fetch_assoc();

    $stmt->close();
}


if (!$prescription) {

    header(
        "Location: prescriptions.php"
    );

    exit;
}


// =====================================================
// FILE INFORMATION
// =====================================================

$fileName =
    $prescription['file_name'];

$originalFileName =
    $prescription['original_file_name']
    ?: $fileName;

$fileType =
    strtolower(
        (string) $prescription['file_type']
    );


$fileUrl =
    "../uploads/prescriptions/" .
    rawurlencode($fileName);


$extension =
    strtolower(
        pathinfo(
            $fileName,
            PATHINFO_EXTENSION
        )
    );


$isImage =
    in_array(
        $extension,
        [
            'jpg',
            'jpeg',
            'png',
            'webp'
        ],
        true
    );


$isPdf =
    $extension === 'pdf';


$status =
    strtolower(
        (string) $prescription['status']
    );


// =====================================================
// SUCCESS MESSAGE
// =====================================================

$uploaded =
    isset($_GET['uploaded']) &&
    $_GET['uploaded'] === '1';


// =====================================================
// CUSTOMER NAME
// =====================================================

$customer = null;

$stmt = $conn->prepare("
    SELECT name
    FROM users
    WHERE id = ?
    LIMIT 1
");

if ($stmt) {

    $stmt->bind_param(
        "i",
        $customerId
    );

    $stmt->execute();

    $result =
        $stmt->get_result();

    $customer =
        $result->fetch_assoc();

    $stmt->close();
}


if ($customer && !empty($customer['name'])) {
    $customerName =
        $customer['name'];
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
        Prescription Details | Medicine Aapki Gaw Mein
    </title>

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
           HEADER
        ===================================================== */

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 22px;
        }

        .page-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .page-header p {
            color: #7c8781;
            font-size: 13px;
        }

        .btn {
            border: none;
            border-radius: 9px;
            padding: 11px 15px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .btn-primary {
            background: linear-gradient(
                135deg,
                #238b39,
                #51b848
            );
            color: #fff;
        }

        .btn-secondary {
            background: #edf2ee;
            color: #52605a;
        }

        /* =====================================================
           SUCCESS
        ===================================================== */

        .success {
            background: #eaf8ed;
            border: 1px solid #ccebd2;
            color: #23763a;
            padding: 13px 15px;
            border-radius: 9px;
            font-size: 13px;
            margin-bottom: 20px;
        }

        /* =====================================================
           GRID
        ===================================================== */

        .details-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(280px, .8fr);
            gap: 22px;
        }

        .card {
            background: #fff;
            border: 1px solid #e8ece9;
            border-radius: 13px;
            box-shadow: 0 4px 15px rgba(0,0,0,.035);
            overflow: hidden;
        }

        .card-header {
            padding: 18px 21px;
            border-bottom: 1px solid #edf0ee;
        }

        .card-header h3 {
            font-size: 16px;
        }

        .card-body {
            padding: 20px;
        }

        /* =====================================================
           PREVIEW
        ===================================================== */

        .preview-box {
            min-height: 430px;
            background: #f7f9f8;
            border: 1px solid #e3e9e5;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
        }

        .preview-image {
            max-width: 100%;
            max-height: 650px;
            object-fit: contain;
            border-radius: 6px;
            box-shadow: 0 4px 15px rgba(0,0,0,.08);
        }

        .pdf-preview {
            width: 100%;
            height: 600px;
            border: none;
            border-radius: 7px;
            background: #fff;
        }

        .file-placeholder {
            text-align: center;
        }

        .file-placeholder-icon {
            font-size: 55px;
            margin-bottom: 12px;
        }

        .file-placeholder h3 {
            font-size: 16px;
            margin-bottom: 7px;
        }

        .file-placeholder p {
            color: #87918b;
            font-size: 12px;
            margin-bottom: 18px;
        }

        /* =====================================================
           STATUS
        ===================================================== */

        .status-box {
            padding: 17px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .status-box.pending {
            background: #fff9e8;
            border: 1px solid #f5e3a8;
        }

        .status-box.approved {
            background: #ecf9ef;
            border: 1px solid #ccebd3;
        }

        .status-box.rejected {
            background: #fff0f0;
            border: 1px solid #f3d0d0;
        }

        .status-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .status-description {
            color: #6f7973;
            font-size: 12px;
            line-height: 1.6;
        }

        /* =====================================================
           DETAILS
        ===================================================== */

        .detail-row {
            padding: 13px 0;
            border-bottom: 1px solid #edf0ee;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #89938d;
            font-size: 11px;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 13px;
            font-weight: 500;
            word-break: break-word;
        }

        /* =====================================================
           NOTE
        ===================================================== */

        .note-box {
            margin-top: 20px;
            padding: 15px;
            background: #f7faf8;
            border: 1px solid #e4ebe6;
            border-radius: 9px;
        }

        .note-box h4 {
            font-size: 12px;
            margin-bottom: 8px;
        }

        .note-box p {
            color: #657069;
            font-size: 12px;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        /* =====================================================
           ACTIONS
        ===================================================== */

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            margin-top: 20px;
        }

        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1000px) {

            .details-grid {
                grid-template-columns: 1fr;
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

            .user-info {
                display: none;
            }

            .page-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .preview-box {
                min-height: 300px;
            }

            .pdf-preview {
                height: 450px;
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

    <a href="profile.php" class="menu-item">
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


    <div class="menu-section">
        MORE
    </div>

    <a href="../index.php" class="menu-item">
        <span class="menu-icon">🌐</span>
        <span>Visit Website</span>
    </a>

    <a href="../logout.php" class="menu-item">
        <span class="menu-icon">🚪</span>
        <span>Logout</span>
    </a>

</aside>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


    <div class="topbar">

        <div class="topbar-title">
            Prescription Details
        </div>

        <div class="topbar-right">

            <div class="user-avatar">

                <?php
                echo e(
                    strtoupper(
                        substr(
                            $customerName,
                            0,
                            1
                        )
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


    <div class="content">


        <!-- PAGE HEADER -->

        <div class="page-header">

            <div>

                <h1>
                    Prescription #<?php echo (int) $prescription['id']; ?>
                </h1>

                <p>
                    View your uploaded prescription and verification status.
                </p>

            </div>

            <a
                href="prescriptions.php"
                class="btn btn-secondary"
            >
                ← Back to Prescriptions
            </a>

        </div>


        <?php if ($uploaded): ?>

            <div class="success">

                ✓ Prescription uploaded successfully.
                Your prescription is now waiting for pharmacist verification.

            </div>

        <?php endif; ?>


        <div class="details-grid">


            <!-- =================================================
                 PREVIEW
            ================================================= -->

            <div class="card">

                <div class="card-header">

                    <h3>
                        Prescription Preview
                    </h3>

                </div>


                <div class="card-body">

                    <div class="preview-box">


                        <?php if ($isImage): ?>

                            <img
                                src="<?php echo e($fileUrl); ?>"
                                alt="Prescription"
                                class="preview-image"
                            >


                        <?php elseif ($isPdf): ?>

                            <iframe
                                src="<?php echo e($fileUrl); ?>"
                                class="pdf-preview"
                                title="Prescription PDF"
                            ></iframe>


                        <?php else: ?>

                            <div class="file-placeholder">

                                <div class="file-placeholder-icon">
                                    📄
                                </div>

                                <h3>
                                    Prescription File
                                </h3>

                                <p>
                                    Preview is not available for this file.
                                </p>

                                <a
                                    href="<?php echo e($fileUrl); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-primary"
                                >
                                    👁 Open File
                                </a>

                            </div>

                        <?php endif; ?>


                    </div>


                    <div class="actions">

                        <a
                            href="<?php echo e($fileUrl); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn btn-primary"
                        >
                            ↗ Open File
                        </a>

                        <a
                            href="prescriptions.php"
                            class="btn btn-secondary"
                        >
                            ← Back
                        </a>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 DETAILS
            ================================================= -->

            <div class="card">

                <div class="card-header">

                    <h3>
                        Prescription Information
                    </h3>

                </div>


                <div class="card-body">


                    <!-- STATUS -->

                    <div
                        class="status-box <?php echo e($status); ?>"
                    >

                        <div class="status-title">

                            <?php if ($status === 'approved'): ?>

                                ✅ Approved

                            <?php elseif ($status === 'rejected'): ?>

                                ❌ Rejected

                            <?php else: ?>

                                ⏳ Pending Review

                            <?php endif; ?>

                        </div>


                        <div class="status-description">

                            <?php if ($status === 'approved'): ?>

                                Your prescription has been verified by our pharmacist.

                            <?php elseif ($status === 'rejected'): ?>

                                Your prescription was rejected. Please review the pharmacist note and upload a new prescription if required.

                            <?php else: ?>

                                Your prescription has been received and is waiting for pharmacist verification.

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- FILE NAME -->

                    <div class="detail-row">

                        <div class="detail-label">
                            File Name
                        </div>

                        <div class="detail-value">
                            <?php echo e($originalFileName); ?>
                        </div>

                    </div>


                    <!-- FILE TYPE -->

                    <div class="detail-row">

                        <div class="detail-label">
                            File Type
                        </div>

                        <div class="detail-value">

                            <?php
                            echo e(
                                $fileType
                                ?: strtoupper($extension)
                            );
                            ?>

                        </div>

                    </div>


                    <!-- FILE SIZE -->

                    <div class="detail-row">

                        <div class="detail-label">
                            File Size
                        </div>

                        <div class="detail-value">

                            <?php
                            echo formatFileSize(
                                $prescription['file_size']
                            );
                            ?>

                        </div>

                    </div>


                    <!-- UPLOADED -->

                    <div class="detail-row">

                        <div class="detail-label">
                            Uploaded On
                        </div>

                        <div class="detail-value">

                            <?php
                            echo e(
                                date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $prescription['created_at']
                                    )
                                )
                            );
                            ?>

                        </div>

                    </div>


                    <!-- VERIFIED -->

                    <div class="detail-row">

                        <div class="detail-label">
                            Verified On
                        </div>

                        <div class="detail-value">

                            <?php if (!empty($prescription['verified_at'])): ?>

                                <?php
                                echo e(
                                    date(
                                        'd M Y, h:i A',
                                        strtotime(
                                            $prescription['verified_at']
                                        )
                                    )
                                );
                                ?>

                            <?php else: ?>

                                Not verified yet

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- PHARMACIST NOTE -->

                    <?php if (!empty($prescription['pharmacist_note'])): ?>

                        <div class="note-box">

                            <h4>
                                📝 Pharmacist Note
                            </h4>

                            <p>
                                <?php
                                echo e(
                                    $prescription['pharmacist_note']
                                );
                                ?>
                            </p>

                        </div>

                    <?php endif; ?>


                    <!-- REJECTED ACTION -->

                    <?php if ($status === 'rejected'): ?>

                        <div class="actions">

                            <a
                                href="upload-prescription.php"
                                class="btn btn-primary"
                            >
                                ⬆️ Upload New Prescription
                            </a>

                        </div>

                    <?php endif; ?>


                </div>

            </div>


        </div>

    </div>

</main>

</body>
</html>