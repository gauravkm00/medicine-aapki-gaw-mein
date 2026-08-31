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
// DELETE HERO
// =====================================================

if (
    isset($_GET['delete']) &&
    is_numeric($_GET['delete'])
) {

    $id = (int) $_GET['delete'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT background_image
         FROM hero_sections
         WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $hero = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);


    if ($hero) {

        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM hero_sections
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $id
        );

        mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);


        // Delete uploaded image
        if (
            !empty($hero['background_image']) &&
            file_exists(
                "../assets/images/hero/" .
                $hero['background_image']
            )
        ) {

            unlink(
                "../assets/images/hero/" .
                $hero['background_image']
            );

        }

    }

    header("Location: hero.php?success=deleted");
    exit;
}


// =====================================================
// CHANGE STATUS
// =====================================================

if (
    isset($_GET['toggle']) &&
    is_numeric($_GET['toggle'])
) {

    $id = (int) $_GET['toggle'];

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE hero_sections
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

    header("Location: hero.php?success=status");
    exit;
}


// =====================================================
// GET HEROES
// =====================================================

$heroes = [];

$result = mysqli_query(
    $conn,
    "SELECT *
     FROM hero_sections
     ORDER BY id DESC"
);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $heroes[] = $row;

    }

}


$pageTitle = "Hero Management";

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

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */

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
                1px solid rgba(255,255,255,0.12);
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
            color: rgba(255,255,255,0.75);
        }

        .sidebar-menu {
            padding: 20px 12px;
        }

        .menu-title {
            color: rgba(255,255,255,0.55);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 10px 12px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255,255,255,0.85);
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

        /* MAIN */

        .main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
            min-height: 100vh;
        }

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

        /* CONTENT */

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

        /* ALERT */

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

        /* HERO GRID */

        .hero-grid {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .hero-card {
            background: #fff;
            border: 1px solid #edf0f4;
            border-radius: 13px;
            overflow: hidden;
        }

        .hero-image {
            height: 230px;
            background-size: cover;
            background-position: center;
            background-color: #ddd;
            position: relative;
        }

        .status {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 6px 10px;
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

        .hero-body {
            padding: 20px;
        }

        .hero-subtitle {
            color: #278c3c;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .hero-body h3 {
            font-size: 20px;
            color: #222;
            margin-bottom: 8px;
        }

        .hero-description {
            color: #888;
            font-size: 12px;
            line-height: 1.6;
            min-height: 38px;
        }

        .hero-button {
            margin-top: 12px;
            display: inline-block;
            background: #f0f7f1;
            color: #278c3c;
            padding: 7px 11px;
            border-radius: 6px;
            font-size: 10px;
        }

        .hero-actions {
            padding-top: 15px;
            margin-top: 15px;
            border-top: 1px solid #edf0f4;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .empty-state {
            background: #fff;
            border: 1px solid #edf0f4;
            border-radius: 12px;
            padding: 50px 20px;
            text-align: center;
            color: #999;
            font-size: 13px;
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

        @media (max-width: 1000px) {

            .hero-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 900px) {

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

        }

    </style>

</head>

<body>

<div class="admin-wrapper">

    <!-- SIDEBAR -->

    <aside class="sidebar" id="sidebar">

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
                <span class="menu-icon">📊</span>
                Dashboard
            </a>

            <a href="medicines.php">
                <span class="menu-icon">💊</span>
                Medicines
            </a>

            <a href="orders.php">
                <span class="menu-icon">📦</span>
                Orders
            </a>

            <a href="prescriptions.php">
                <span class="menu-icon">📄</span>
                Prescriptions
            </a>

            <a href="deliveries.php">
                <span class="menu-icon">🚚</span>
                Deliveries
            </a>

            <a href="hero.php" class="active">
                <span class="menu-icon">🖼️</span>
                Hero Section
            </a>

            <div class="menu-title">
                Account
            </div>

            <a href="../index.php">
                <span class="menu-icon">🌐</span>
                View Website
            </a>

            <a href="logout.php">
                <span class="menu-icon">🚪</span>
                Logout
            </a>

        </nav>

    </aside>


    <!-- MAIN -->

    <main class="main-content">

        <header class="topbar">

            <div style="display:flex;align-items:center;gap:12px;">

                <button
                    class="mobile-menu-btn"
                    onclick="toggleSidebar()"
                >
                    ☰
                </button>

                <div class="topbar-title">

                    <h1>
                        Hero Section
                    </h1>

                    <p>
                        Manage homepage hero banners
                    </p>

                </div>

            </div>

            <div class="admin-profile">

                <div class="admin-avatar">

                    <?= htmlspecialchars(
                        strtoupper(
                            substr($adminName, 0, 1)
                        )
                    ) ?>

                </div>

                <div class="admin-info">

                    <strong>
                        <?= htmlspecialchars($adminName) ?>
                    </strong>

                    <span>
                        Administrator
                    </span>

                </div>

            </div>

        </header>


        <div class="content">


            <div class="page-header">

                <div>

                    <h2>
                        Hero Sections
                    </h2>

                    <p>
                        Homepage par dikhne wale hero banners manage karein.
                    </p>

                </div>

                <a
                    href="hero-add.php"
                    class="btn btn-primary"
                >
                    + Add New Hero
                </a>

            </div>


            <?php if (isset($_GET['success'])): ?>

                <div class="alert alert-success">

                    <?php

                    if ($_GET['success'] === 'deleted') {

                        echo "Hero successfully deleted.";

                    } elseif ($_GET['success'] === 'status') {

                        echo "Hero status updated.";

                    }

                    ?>

                </div>

            <?php endif; ?>


            <?php if (!empty($heroes)): ?>

                <div class="hero-grid">

                    <?php foreach ($heroes as $hero): ?>

                        <div class="hero-card">


                           <div class="hero-image"
                                style="
                                    background-image:
                                    url('../<?= htmlspecialchars($hero['background_image']) ?>');
                                "
                            >

                                <span
                                    class="status
                                    <?= $hero['status']
                                        ? 'status-active'
                                        : 'status-inactive'
                                    ?>"
                                >

                                    <?= $hero['status']
                                        ? 'Active'
                                        : 'Inactive'
                                    ?>

                                </span>

                            </div>


                            <div class="hero-body">

                                <div class="hero-subtitle">

                                    <?= htmlspecialchars(
                                        $hero['subtitle']
                                    ) ?>

                                </div>


                                <h3>

                                    <?= htmlspecialchars(
                                        $hero['title']
                                    ) ?>

                                </h3>


                                <div class="hero-description">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $hero['description'] ?? ''
                                        )
                                    ) ?>

                                </div>


                                <?php if (
                                    !empty($hero['button_text'])
                                ): ?>

                                    <span class="hero-button">

                                        <?= htmlspecialchars(
                                            $hero['button_text']
                                        ) ?>

                                        →

                                    </span>

                                <?php endif; ?>


                                <div class="hero-actions">

                                    <a
                                        href="hero-edit.php?id=<?= (int)$hero['id'] ?>"
                                        class="btn btn-edit"
                                    >
                                        ✏️ Edit
                                    </a>


                                    <a
                                        href="hero.php?toggle=<?= (int)$hero['id'] ?>"
                                        class="btn btn-status"
                                    >
                                        <?= $hero['status']
                                            ? '⏸ Disable'
                                            : '▶ Activate'
                                        ?>
                                    </a>


                                    <a
                                        href="hero.php?delete=<?= (int)$hero['id'] ?>"
                                        class="btn btn-delete"
                                        onclick="
                                            return confirm(
                                                'Are you sure you want to delete this hero?'
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
                        No Hero Sections
                    </h3>

                    <p style="margin-top:8px;">
                        Abhi koi hero section add nahi hai.
                    </p>

                    <a
                        href="hero-add.php"
                        class="btn btn-primary"
                        style="margin-top:18px;"
                    >
                        + Add Hero
                    </a>

                </div>

            <?php endif; ?>


        </div>

    </main>

</div>


<script>

function toggleSidebar()
{
    document
        .getElementById("sidebar")
        .classList
        .toggle("show");
}

</script>

</body>

</html>
