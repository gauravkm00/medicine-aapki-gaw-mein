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
// UPLOAD DIRECTORY
// =====================================================

$upload_dir = "../uploads/testimonials/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}


// =====================================================
// DELETE TESTIMONIAL
// =====================================================

if (
    isset($_GET['delete']) &&
    is_numeric($_GET['delete'])
) {

    $id = (int) $_GET['delete'];


    // Get image
    $stmt = mysqli_prepare(
        $conn,
        "SELECT image
         FROM testimonials
         WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $testimonial = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);


    // Delete record
    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM testimonials
         WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id
    );

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);


    // Delete image
    if (
        $testimonial &&
        !empty($testimonial['image']) &&
        file_exists(
            $upload_dir . $testimonial['image']
        )
    ) {

        unlink(
            $upload_dir . $testimonial['image']
        );
    }


    header(
        "Location: testimonials.php?success=deleted"
    );

    exit;
}


// =====================================================
// TOGGLE STATUS
// =====================================================

if (
    isset($_GET['toggle']) &&
    is_numeric($_GET['toggle'])
) {

    $id = (int) $_GET['toggle'];


    $stmt = mysqli_prepare(
        $conn,
        "UPDATE testimonials
         SET status = IF(status = 1, 0, 1)
         WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id
    );

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);


    header(
        "Location: testimonials.php?success=status"
    );

    exit;
}


// =====================================================
// ADD TESTIMONIAL
// =====================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['add_testimonial'])
) {

    $customer_name = trim(
        $_POST['customer_name'] ?? ''
    );

    $message = trim(
        $_POST['message'] ?? ''
    );

    $status = isset($_POST['status'])
        ? 1
        : 0;

    $image_name = null;


    // -----------------------------------------------
    // VALIDATION
    // -----------------------------------------------

    if (
        $customer_name === '' ||
        $message === ''
    ) {

        header(
            "Location: testimonials.php?error=required"
        );

        exit;
    }


    // -----------------------------------------------
    // IMAGE UPLOAD
    // -----------------------------------------------

    if (
        isset($_FILES['image']) &&
        $_FILES['image']['error'] === UPLOAD_ERR_OK
    ) {

        $original_name =
            $_FILES['image']['name'];

        $tmp_name =
            $_FILES['image']['tmp_name'];

        $file_size =
            $_FILES['image']['size'];


        $extension = strtolower(
            pathinfo(
                $original_name,
                PATHINFO_EXTENSION
            )
        );


        $allowed_extensions = [
            'jpg',
            'jpeg',
            'png',
            'webp'
        ];


        if (
            !in_array(
                $extension,
                $allowed_extensions
            )
        ) {

            header(
                "Location: testimonials.php?error=invalid_image"
            );

            exit;
        }


        if (
            $file_size > 5 * 1024 * 1024
        ) {

            header(
                "Location: testimonials.php?error=large_image"
            );

            exit;
        }


        $image_name =
            uniqid(
                'testimonial_',
                true
            )
            . '.'
            . $extension;


        if (
            !move_uploaded_file(
                $tmp_name,
                $upload_dir . $image_name
            )
        ) {

            header(
                "Location: testimonials.php?error=upload_failed"
            );

            exit;
        }
    }


    // -----------------------------------------------
    // INSERT
    // -----------------------------------------------

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO testimonials
        (
            customer_name,
            message,
            image,
            status
        )
        VALUES (?, ?, ?, ?)"
    );


    mysqli_stmt_bind_param(
        $stmt,
        "sssi",
        $customer_name,
        $message,
        $image_name,
        $status
    );


    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);


    header(
        "Location: testimonials.php?success=added"
    );

    exit;
}


// =====================================================
// FETCH TESTIMONIALS
// =====================================================

$testimonials = [];

$result = mysqli_query(
    $conn,
    "SELECT
        id,
        customer_name,
        message,
        image,
        status,
        created_at
     FROM testimonials
     ORDER BY id DESC"
);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $testimonials[] = $row;

    }
}


$pageTitle = "Testimonials Management";

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
        <?= htmlspecialchars($pageTitle) ?>
        | Medicine Aapki Gaw Mein
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
            font-family: 'Rubik', Arial, sans-serif;
            background: #f5f7fb;
            color: #333;
        }


        a {
            text-decoration: none;
        }


        /* =================================================
           ADMIN WRAPPER
        ================================================= */

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }


        /* =================================================
           SIDEBAR
        ================================================= */

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

            border-bottom:
                1px solid
                rgba(255,255,255,0.12);
        }


        .brand-icon {
            width: 48px;
            height: 48px;

            background:
                rgba(255,255,255,0.15);

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
                rgba(255,255,255,0.75);
        }


        .sidebar-menu {
            padding: 20px 12px;
        }


        .menu-title {
            color:
                rgba(255,255,255,0.55);

            font-size: 10px;

            text-transform: uppercase;

            letter-spacing: 1px;

            padding: 10px 12px;
        }


        .sidebar-menu a {
            display: flex;

            align-items: center;

            gap: 12px;

            color:
                rgba(255,255,255,0.85);

            padding: 12px 13px;

            border-radius: 8px;

            margin-bottom: 4px;

            font-size: 13px;
        }


        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background:
                rgba(255,255,255,0.15);

            color: #fff;
        }


        .menu-icon {
            width: 24px;

            text-align: center;

            font-size: 16px;
        }


        /* =================================================
           MAIN CONTENT
        ================================================= */

        .main-content {
            margin-left: 250px;

            width:
                calc(100% - 250px);

            min-height: 100vh;
        }


        /* =================================================
           TOPBAR
        ================================================= */

        .topbar {
            height: 75px;

            background: #fff;

            border-bottom:
                1px solid #e9edf3;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 30px;

            position: sticky;

            top: 0;

            z-index: 100;
        }


        .topbar-title h1 {
            font-size: 21px;

            color: #222;
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


        /* =================================================
           CONTENT
        ================================================= */

        .content {
            padding: 30px;
        }


        .page-header {
            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 20px;
        }


        .page-header h2 {
            font-size: 20px;

            color: #222;
        }


        .page-header p {
            color: #999;

            font-size: 12px;

            margin-top: 5px;
        }


        /* =================================================
           BUTTONS
        ================================================= */

        .btn {
            border: none;

            border-radius: 8px;

            padding: 11px 18px;

            font-size: 12px;

            font-weight: 600;

            cursor: pointer;

            display: inline-block;
        }


        .btn-primary {
            background: #278c3c;

            color: #fff;
        }


        .btn-primary:hover {
            background: #1f7831;
        }


        .btn-edit {
            background: #eaf3ff;

            color: #1769aa;
        }


        .btn-delete {
            background: #ffe9eb;

            color: #c62828;
        }


        .btn-status {
            background: #fff4db;

            color: #a66b00;
        }


        /* =================================================
           ALERT
        ================================================= */

        .alert {
            padding: 13px 16px;

            border-radius: 8px;

            margin-bottom: 20px;

            font-size: 12px;
        }


        .alert-success {
            background: #e4f7e8;

            color: #207532;
        }


        .alert-danger {
            background: #ffe8eb;

            color: #b42332;
        }


        /* =================================================
           TESTIMONIAL GRID
        ================================================= */

        .testimonial-grid {
            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );

            gap: 20px;
        }


        .testimonial-card {
            background: #fff;

            border:
                1px solid #edf0f4;

            border-radius: 13px;

            overflow: hidden;
        }


        .testimonial-top {
            padding: 20px;

            display: flex;

            align-items: center;

            gap: 15px;

            border-bottom:
                1px solid #edf0f4;
        }


        .customer-image {
            width: 65px;
            height: 65px;

            border-radius: 50%;

            object-fit: cover;

            border:
                3px solid #e8f7eb;
        }


        .customer-placeholder {
            width: 65px;
            height: 65px;

            border-radius: 50%;

            background: #e8f7eb;

            color: #278c3c;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 25px;

            font-weight: 700;
        }


        .customer-info {
            flex: 1;
        }


        .customer-info h3 {
            font-size: 16px;

            color: #222;

            margin-bottom: 5px;
        }


        .customer-date {
            color: #999;

            font-size: 11px;
        }


        .testimonial-status {
            padding: 5px 9px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 600;
        }


        .status-active {
            background: #dff5e3;

            color: #197631;
        }


        .status-inactive {
            background: #ffe3e6;

            color: #a51d2d;
        }


        .testimonial-body {
            padding: 20px;
        }


        .testimonial-message {
            color: #666;

            font-size: 13px;

            line-height: 1.7;

            min-height: 70px;
        }


        .quote {
            color: #278c3c;

            font-size: 28px;

            line-height: 1;
        }


        .testimonial-actions {
            padding-top: 15px;

            margin-top: 15px;

            border-top:
                1px solid #edf0f4;

            display: flex;

            gap: 8px;

            flex-wrap: wrap;
        }


        .empty-state {
            background: #fff;

            border:
                1px solid #edf0f4;

            border-radius: 12px;

            padding: 50px 20px;

            text-align: center;

            color: #999;

            font-size: 13px;
        }


        /* =================================================
           MOBILE MENU
        ================================================= */

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


        /* =================================================
           MODAL
        ================================================= */

        .modal {
            display: none;

            position: fixed;

            z-index: 2000;

            left: 0;
            top: 0;

            width: 100%;
            height: 100%;

            background:
                rgba(0,0,0,0.55);

            align-items: center;

            justify-content: center;

            padding: 20px;
        }


        .modal.show {
            display: flex;
        }


        .modal-content {
            width: 100%;

            max-width: 600px;

            background: #fff;

            border-radius: 13px;

            overflow: hidden;

            box-shadow:
                0 20px 60px
                rgba(0,0,0,0.2);
        }


        .modal-header {
            padding: 18px 20px;

            border-bottom:
                1px solid #edf0f4;

            display: flex;

            justify-content: space-between;

            align-items: center;
        }


        .modal-header h3 {
            font-size: 17px;

            color: #222;
        }


        .modal-close {
            border: none;

            background: none;

            font-size: 25px;

            color: #999;

            cursor: pointer;
        }


        .modal-body {
            padding: 20px;
        }


        .form-group {
            margin-bottom: 18px;
        }


        .form-group label {
            display: block;

            font-size: 12px;

            font-weight: 600;

            color: #444;

            margin-bottom: 7px;
        }


        .form-control {
            width: 100%;

            padding: 11px 13px;

            border:
                1px solid #dfe4ea;

            border-radius: 7px;

            font-family: inherit;

            font-size: 12px;

            outline: none;
        }


        .form-control:focus {
            border-color: #278c3c;
        }


        textarea.form-control {
            resize: vertical;

            min-height: 120px;
        }


        .form-file {
            width: 100%;

            font-size: 12px;
        }


        .form-help {
            display: block;

            color: #999;

            font-size: 10px;

            margin-top: 5px;
        }


        .form-check {
            display: flex;

            align-items: center;

            gap: 8px;

            font-size: 12px;
        }


        .form-check input {
            width: 16px;
            height: 16px;
        }


        .modal-footer {
            padding: 15px 20px;

            border-top:
                1px solid #edf0f4;

            display: flex;

            justify-content: flex-end;

            gap: 8px;
        }


        .btn-secondary {
            background: #edf0f4;

            color: #555;
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 1000px) {

            .testimonial-grid {
                grid-template-columns: 1fr;
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

        }


        @media (max-width: 600px) {

            .content {
                padding: 15px;
            }


            .page-header {
                display: block;
            }


            .page-header .btn {
                margin-top: 15px;
            }


            .admin-info {
                display: none;
            }


            .testimonial-top {
                align-items: flex-start;
            }

        }

    </style>

</head>


<body>


<div class="admin-wrapper">


    <!-- =================================================
         SIDEBAR
    ================================================= -->

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


            <a href="hero.php">

                <span class="menu-icon">
                    🖼️
                </span>

                Hero Section

            </a>


            <a
                href="testimonials.php"
                class="active"
            >

                <span class="menu-icon">
                    💬
                </span>

                Testimonials

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



    <!-- =================================================
         MAIN CONTENT
    ================================================= -->

    <main class="main-content">


        <!-- =================================================
             TOPBAR
        ================================================= -->

        <header class="topbar">


            <div
                style="
                    display:flex;
                    align-items:center;
                    gap:12px;
                "
            >


                <button
                    class="mobile-menu-btn"
                    onclick="toggleSidebar()"
                >
                    ☰
                </button>


                <div class="topbar-title">

                    <h1>
                        Testimonials
                    </h1>

                    <p>
                        Manage customer testimonials
                    </p>

                </div>


            </div>


            <div class="admin-profile">


                <div class="admin-avatar">

                    <?= htmlspecialchars(
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

                        <?= htmlspecialchars(
                            $adminName
                        ) ?>

                    </strong>


                    <span>
                        Administrator
                    </span>

                </div>


            </div>


        </header>



        <!-- =================================================
             CONTENT
        ================================================= -->

        <div class="content">


            <div class="page-header">


                <div>

                    <h2>
                        Customer Testimonials
                    </h2>

                    <p>
                        Website par dikhne wale customer reviews manage karein.
                    </p>

                </div>


                <button
                    class="btn btn-primary"
                    onclick="openModal()"
                >

                    + Add Testimonial

                </button>


            </div>



            <!-- =================================================
                 ALERTS
            ================================================= -->

            <?php if (isset($_GET['success'])): ?>

                <div class="alert alert-success">

                    <?php

                    if (
                        $_GET['success'] === 'added'
                    ) {

                        echo "Testimonial successfully added.";

                    } elseif (
                        $_GET['success'] === 'deleted'
                    ) {

                        echo "Testimonial successfully deleted.";

                    } elseif (
                        $_GET['success'] === 'status'
                    ) {

                        echo "Testimonial status updated.";

                    }

                    ?>

                </div>

            <?php endif; ?>


            <?php if (isset($_GET['error'])): ?>

                <div class="alert alert-danger">

                    <?php

                    if (
                        $_GET['error'] === 'required'
                    ) {

                        echo "Customer name and testimonial message are required.";

                    } elseif (
                        $_GET['error'] === 'invalid_image'
                    ) {

                        echo "Only JPG, JPEG, PNG and WEBP images are allowed.";

                    } elseif (
                        $_GET['error'] === 'large_image'
                    ) {

                        echo "Image size must be less than 5 MB.";

                    } elseif (
                        $_GET['error'] === 'upload_failed'
                    ) {

                        echo "Image upload failed.";

                    }

                    ?>

                </div>

            <?php endif; ?>



            <!-- =================================================
                 TESTIMONIALS
            ================================================= -->

            <?php if (!empty($testimonials)): ?>


                <div class="testimonial-grid">


                    <?php foreach (
                        $testimonials as $testimonial
                    ): ?>


                        <div class="testimonial-card">


                            <!-- TOP -->

                            <div class="testimonial-top">


                                <?php if (
                                    !empty(
                                        $testimonial['image']
                                    )
                                ): ?>


                                    <img
                                        src="../uploads/testimonials/<?= htmlspecialchars(
                                            $testimonial['image']
                                        ) ?>"
                                        class="customer-image"
                                        alt="<?= htmlspecialchars(
                                            $testimonial['customer_name']
                                        ) ?>"
                                    >


                                <?php else: ?>


                                    <div class="customer-placeholder">

                                        <?= htmlspecialchars(
                                            strtoupper(
                                                substr(
                                                    $testimonial['customer_name'],
                                                    0,
                                                    1
                                                )
                                            )
                                        ) ?>

                                    </div>


                                <?php endif; ?>


                                <div class="customer-info">


                                    <h3>

                                        <?= htmlspecialchars(
                                            $testimonial[
                                                'customer_name'
                                            ]
                                        ) ?>

                                    </h3>


                                    <div class="customer-date">

                                        <?= date(
                                            'd M Y',
                                            strtotime(
                                                $testimonial[
                                                    'created_at'
                                                ]
                                            )
                                        ) ?>

                                    </div>


                                </div>


                                <span
                                    class="
                                        testimonial-status
                                        <?= $testimonial['status']
                                            ? 'status-active'
                                            : 'status-inactive'
                                        ?>
                                    "
                                >

                                    <?= $testimonial['status']
                                        ? 'Active'
                                        : 'Inactive'
                                    ?>

                                </span>


                            </div>



                            <!-- BODY -->

                            <div class="testimonial-body">


                                <div class="quote">
                                    “
                                </div>


                                <div class="testimonial-message">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $testimonial[
                                                'message'
                                            ]
                                        )
                                    ) ?>

                                </div>



                                <!-- ACTIONS -->

                                <div class="testimonial-actions">


                                    <a
                                        href="testimonial-edit.php?id=<?= (int) $testimonial['id'] ?>"
                                        class="btn btn-edit"
                                    >

                                        ✏️ Edit

                                    </a>


                                    <a
                                        href="testimonials.php?toggle=<?= (int) $testimonial['id'] ?>"
                                        class="btn btn-status"
                                    >

                                        <?= $testimonial['status']
                                            ? '⏸ Disable'
                                            : '▶ Activate'
                                        ?>

                                    </a>


                                    <a
                                        href="testimonials.php?delete=<?= (int) $testimonial['id'] ?>"
                                        class="btn btn-delete"
                                        onclick="
                                            return confirm(
                                                'Are you sure you want to delete this testimonial?'
                                            );
                                        "
                                    >

                                        🗑 Delete

                                    </a>


                                </div>


                            </div>


                        </div>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <div class="empty-state">


                    <h3>
                        No Testimonials
                    </h3>


                    <p
                        style="
                            margin-top:8px;
                        "
                    >
                        Abhi koi customer testimonial add nahi hai.
                    </p>


                    <button
                        class="btn btn-primary"
                        style="
                            margin-top:18px;
                        "
                        onclick="openModal()"
                    >

                        + Add Testimonial

                    </button>


                </div>


            <?php endif; ?>


        </div>


    </main>


</div>



<!-- =================================================
     ADD TESTIMONIAL MODAL
================================================= -->

<div
    class="modal"
    id="addTestimonialModal"
>


    <div class="modal-content">


        <div class="modal-header">


            <h3>
                Add Testimonial
            </h3>


            <button
                class="modal-close"
                onclick="closeModal()"
            >

                &times;

            </button>


        </div>



        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <div class="modal-body">


                <!-- CUSTOMER NAME -->

                <div class="form-group">


                    <label>
                        Customer Name
                    </label>


                    <input
                        type="text"
                        name="customer_name"
                        class="form-control"
                        placeholder="Enter customer name"
                        required
                    >


                </div>



                <!-- MESSAGE -->

                <div class="form-group">


                    <label>
                        Testimonial
                    </label>


                    <textarea
                        name="message"
                        class="form-control"
                        placeholder="Enter customer testimonial"
                        required
                    ></textarea>


                </div>



                <!-- IMAGE -->

                <div class="form-group">


                    <label>
                        Customer Image
                    </label>


                    <input
                        type="file"
                        name="image"
                        class="form-file"
                        accept=".jpg,.jpeg,.png,.webp"
                    >


                    <small class="form-help">

                        JPG, JPEG, PNG or WEBP.
                        Maximum 5 MB.

                    </small>


                </div>



                <!-- STATUS -->

                <div class="form-check">


                    <input
                        type="checkbox"
                        name="status"
                        value="1"
                        id="testimonialStatus"
                        checked
                    >


                    <label
                        for="testimonialStatus"
                    >

                        Active

                    </label>


                </div>


            </div>



            <div class="modal-footer">


                <button
                    type="button"
                    class="btn btn-secondary"
                    onclick="closeModal()"
                >

                    Cancel

                </button>


                <button
                    type="submit"
                    name="add_testimonial"
                    class="btn btn-primary"
                >

                    Save Testimonial

                </button>


            </div>


        </form>


    </div>


</div>



<script>


// =====================================================
// SIDEBAR
// =====================================================

function toggleSidebar()
{
    document
        .getElementById("sidebar")
        .classList
        .toggle("show");
}


// =====================================================
// MODAL
// =====================================================

function openModal()
{
    document
        .getElementById("addTestimonialModal")
        .classList
        .add("show");
}


function closeModal()
{
    document
        .getElementById("addTestimonialModal")
        .classList
        .remove("show");
}


// =====================================================
// CLOSE MODAL ON BACKGROUND CLICK
// =====================================================

document
    .getElementById("addTestimonialModal")
    .addEventListener(
        "click",
        function(event)
        {
            if (
                event.target === this
            ) {
                closeModal();
            }
        }
    );


</script>


</body>

</html>