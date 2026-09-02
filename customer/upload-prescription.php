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


// =====================================================
// CSRF TOKEN
// =====================================================

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(
        random_bytes(32)
    );
}

$csrfToken = $_SESSION['csrf_token'];


// =====================================================
// FORM VARIABLES
// =====================================================

$errors = [];


// =====================================================
// HANDLE UPLOAD
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    // =================================================
    // CSRF CHECK
    // =================================================

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !hash_equals(
            $_SESSION['csrf_token'],
            $submittedToken
        )
    ) {
        $errors[] = "Invalid security token. Please try again.";
    }


    // =================================================
    // CHECK FILE
    // =================================================

    if (
        !isset($_FILES['prescription']) ||
        !is_array($_FILES['prescription'])
    ) {
        $errors[] = "Please select a prescription file.";
    } else {

        $file = $_FILES['prescription'];

        if (
            !isset($file['error']) ||
            $file['error'] !== UPLOAD_ERR_OK
        ) {

            switch ($file['error'] ?? UPLOAD_ERR_NO_FILE) {

                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = "File size is too large.";
                    break;

                case UPLOAD_ERR_NO_FILE:
                    $errors[] = "Please select a prescription file.";
                    break;

                default:
                    $errors[] = "Unable to upload the file. Please try again.";
                    break;
            }
        }


        // =============================================
        // FILE SIZE
        // =============================================

        if (
            isset($file['size']) &&
            (int) $file['size'] > 5 * 1024 * 1024
        ) {
            $errors[] = "Maximum file size allowed is 5 MB.";
        }


        // =============================================
        // ORIGINAL FILE NAME
        // =============================================

        $originalFileName = basename(
            (string) ($file['name'] ?? '')
        );

        if ($originalFileName === '') {
            $errors[] = "Invalid file name.";
        }


        // =============================================
        // EXTENSION
        // =============================================

        $extension = strtolower(
            pathinfo(
                $originalFileName,
                PATHINFO_EXTENSION
            )
        );

        $allowedExtensions = [
            'jpg',
            'jpeg',
            'png',
            'webp',
            'pdf'
        ];

        if (!in_array($extension, $allowedExtensions, true)) {
            $errors[] =
                "Invalid file type. Allowed: JPG, JPEG, PNG, WEBP and PDF.";
        }


        // =============================================
        // MIME TYPE
        // =============================================

        $mimeType = '';

        if (
            isset($file['tmp_name']) &&
            is_uploaded_file($file['tmp_name'])
        ) {

            if (function_exists('finfo_open')) {

                $finfo = finfo_open(FILEINFO_MIME_TYPE);

                if ($finfo) {

                    $mimeType = finfo_file(
                        $finfo,
                        $file['tmp_name']
                    );

                    finfo_close($finfo);
                }
            }

            // Fallback
            if ($mimeType === '') {
                $mimeType = $file['type'] ?? '';
            }
        }


        $allowedMimeTypes = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png'  => ['png'],
            'image/webp' => ['webp'],
            'application/pdf' => ['pdf']
        ];


        if (
            !isset(
                $allowedMimeTypes[$mimeType]
            ) ||
            !in_array(
                $extension,
                $allowedMimeTypes[$mimeType],
                true
            )
        ) {
            $errors[] =
                "The uploaded file type could not be verified. Please upload a valid JPG, PNG, WEBP or PDF file.";
        }

    }


    // =================================================
    // PROCESS UPLOAD
    // =================================================

    if (empty($errors)) {

        $uploadDirectory =
            dirname(__DIR__) .
            DIRECTORY_SEPARATOR .
            "uploads" .
            DIRECTORY_SEPARATOR .
            "prescriptions" .
            DIRECTORY_SEPARATOR;


        // =============================================
        // CREATE DIRECTORY
        // =============================================

        if (!is_dir($uploadDirectory)) {

            if (
                !mkdir(
                    $uploadDirectory,
                    0755,
                    true
                )
            ) {
                $errors[] =
                    "Upload directory could not be created.";
            }
        }


        // =============================================
        // GENERATE SAFE FILE NAME
        // =============================================

        if (empty($errors)) {

            try {

                $randomName = bin2hex(
                    random_bytes(16)
                );

            } catch (Exception $e) {

                $randomName = md5(
                    uniqid(
                        (string) mt_rand(),
                        true
                    )
                );
            }


            $generatedFileName =
                'prescription_' .
                $customerId .
                '_' .
                $randomName .
                '.' .
                $extension;


            $destination =
                $uploadDirectory .
                $generatedFileName;


            // =========================================
            // MOVE FILE
            // =========================================

            if (
                !move_uploaded_file(
                    $file['tmp_name'],
                    $destination
                )
            ) {

                $errors[] =
                    "Failed to save the uploaded prescription.";

            } else {


                // =====================================
                // DATABASE INSERT
                // =====================================

                $fileSize = (int) $file['size'];

                $stmt = $conn->prepare("
                    INSERT INTO prescriptions
                    (
                        user_id,
                        file_name,
                        original_file_name,
                        file_type,
                        file_size,
                        status
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'pending'
                    )
                ");


                if (!$stmt) {

                    // Delete uploaded file
                    if (is_file($destination)) {
                        unlink($destination);
                    }

                    $errors[] =
                        "Database error. Please try again.";

                } else {

                    $stmt->bind_param(
                        "isssi",
                        $customerId,
                        $generatedFileName,
                        $originalFileName,
                        $mimeType,
                        $fileSize
                    );


                    if ($stmt->execute()) {

                        $prescriptionId =
                            (int) $stmt->insert_id;

                        $stmt->close();


                        // Regenerate CSRF token
                        $_SESSION['csrf_token'] =
                            bin2hex(
                                random_bytes(32)
                            );


                        header(
                            "Location: prescription-details.php?id=" .
                            $prescriptionId .
                            "&uploaded=1"
                        );

                        exit;

                    } else {

                        if (is_file($destination)) {
                            unlink($destination);
                        }

                        $errors[] =
                            "Prescription could not be saved. Please try again.";

                        $stmt->close();
                    }
                }
            }
        }
    }
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

    <title>Upload Prescription | Medicine Aapki Gaw Mein</title>

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

        /* =====================================================
           ALERT
        ===================================================== */

        .alert {
            padding: 13px 15px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .alert-error {
            background: #fff0f0;
            color: #b52d2d;
            border: 1px solid #ffd4d4;
        }

        .alert-error ul {
            padding-left: 18px;
        }

        .alert-error li {
            margin: 3px 0;
        }

        /* =====================================================
           CARD
        ===================================================== */

        .card {
            background: #fff;
            border: 1px solid #e8ece9;
            border-radius: 13px;
            box-shadow: 0 4px 15px rgba(0,0,0,.035);
            max-width: 850px;
            overflow: hidden;
        }

        .card-header {
            padding: 20px 23px;
            border-bottom: 1px solid #edf0ee;
        }

        .card-header h3 {
            font-size: 17px;
            margin-bottom: 5px;
        }

        .card-header p {
            color: #87918b;
            font-size: 12px;
        }

        .card-body {
            padding: 25px;
        }

        /* =====================================================
           UPLOAD BOX
        ===================================================== */

        .upload-box {
            border: 2px dashed #b8d8bf;
            background: #f8fcf9;
            border-radius: 12px;
            padding: 45px 25px;
            text-align: center;
            transition: .2s;
        }

        .upload-box:hover {
            border-color: #51b848;
            background: #f3faf4;
        }

        .upload-icon {
            width: 65px;
            height: 65px;
            background: #e7f5ea;
            border-radius: 18px;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 29px;
        }

        .upload-box h3 {
            font-size: 17px;
            margin-bottom: 7px;
        }

        .upload-box p {
            color: #87918b;
            font-size: 12px;
            margin-bottom: 20px;
        }

        .file-input {
            display: none;
        }

        .choose-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(
                135deg,
                #238b39,
                #51b848
            );
            color: #fff;
            padding: 12px 20px;
            border-radius: 9px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
        }

        .selected-file {
            display: none;
            margin-top: 18px;
            padding: 12px 14px;
            background: #fff;
            border: 1px solid #dfe9e1;
            border-radius: 9px;
            font-size: 12px;
            color: #46514b;
        }

        .selected-file strong {
            color: #238b39;
        }

        /* =====================================================
           INFO
        ===================================================== */

        .info-box {
            margin-top: 20px;
            padding: 15px;
            background: #f7faf8;
            border-radius: 10px;
            border: 1px solid #e5ebe7;
        }

        .info-box h4 {
            font-size: 13px;
            margin-bottom: 9px;
        }

        .info-box ul {
            padding-left: 18px;
            color: #68746d;
            font-size: 12px;
            line-height: 1.8;
        }

        /* =====================================================
           BUTTONS
        ===================================================== */

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 22px;
        }

        .btn {
            border: none;
            border-radius: 9px;
            padding: 12px 18px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
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
            background: #f0f3f1;
            color: #59645e;
        }

        /* =====================================================
           RESPONSIVE
        ===================================================== */

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

            .card-body {
                padding: 18px;
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

    <a href="prescriptions.php" class="menu-item">
        <span class="menu-icon">📄</span>
        <span>My Prescriptions</span>
    </a>

    <a
        href="upload-prescription.php"
        class="menu-item active"
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
            Upload Prescription
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


    <div class="content">


        <div class="page-header">

            <h1>
                Upload Prescription
            </h1>

            <p>
                Upload a clear image or PDF of your doctor's prescription.
            </p>

        </div>


        <?php if (!empty($errors)): ?>

            <div class="alert alert-error">

                <ul>

                    <?php foreach ($errors as $error): ?>

                        <li>
                            <?php echo e($error); ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <div class="card">

            <div class="card-header">

                <h3>
                    Prescription File
                </h3>

                <p>
                    Your prescription will be reviewed by our pharmacist.
                </p>

            </div>


            <div class="card-body">

                <form
                    method="POST"
                    enctype="multipart/form-data"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php echo e($csrfToken); ?>"
                    >


                    <div class="upload-box">

                        <div class="upload-icon">
                            📄
                        </div>

                        <h3>
                            Select Prescription
                        </h3>

                        <p>
                            JPG, JPEG, PNG, WEBP or PDF • Maximum 5 MB
                        </p>


                        <label
                            for="prescription"
                            class="choose-btn"
                        >
                            📁 Choose File
                        </label>

                        <input
                            type="file"
                            id="prescription"
                            name="prescription"
                            class="file-input"
                            accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
                            required
                        >


                        <div
                            id="selectedFile"
                            class="selected-file"
                        >
                        </div>

                    </div>


                    <div class="info-box">

                        <h4>
                            📌 Before uploading
                        </h4>

                        <ul>

                            <li>
                                Make sure the prescription is clearly visible.
                            </li>

                            <li>
                                The doctor's name, medicines and dosage should be readable.
                            </li>

                            <li>
                                Only JPG, JPEG, PNG, WEBP and PDF files are accepted.
                            </li>

                            <li>
                                Maximum file size is 5 MB.
                            </li>

                            <li>
                                Your prescription will initially have <strong>Pending</strong> status.
                            </li>

                        </ul>

                    </div>


                    <div class="form-actions">

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            ⬆️ Upload Prescription
                        </button>

                        <a
                            href="prescriptions.php"
                            class="btn btn-secondary"
                        >
                            Cancel
                        </a>

                    </div>

                </form>

            </div>

        </div>

    </div>

</main>


<script>

    const fileInput =
        document.getElementById('prescription');

    const selectedFile =
        document.getElementById('selectedFile');


    fileInput.addEventListener(
        'change',
        function () {

            if (
                this.files &&
                this.files.length > 0
            ) {

                const file =
                    this.files[0];

                const sizeMB =
                    (
                        file.size /
                        (1024 * 1024)
                    ).toFixed(2);


                selectedFile.style.display =
                    'block';


                selectedFile.innerHTML =
                    '📄 <strong>' +
                    escapeHtml(file.name) +
                    '</strong> — ' +
                    sizeMB +
                    ' MB';

            } else {

                selectedFile.style.display =
                    'none';

                selectedFile.innerHTML =
                    '';

            }

        }
    );


    function escapeHtml(value) {

        const div =
            document.createElement('div');

        div.textContent =
            value;

        return div.innerHTML;
    }

</script>

</body>
</html>