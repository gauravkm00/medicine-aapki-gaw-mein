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
// GET ID
// =====================================================

$id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


if ($id <= 0) {

    header("Location: hero.php");
    exit;

}


// =====================================================
// GET HERO
// =====================================================

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
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


if (!$hero) {

    header("Location: hero.php");
    exit;

}


$errors = [];


// =====================================================
// UPDATE
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $subtitle =
        trim($_POST['subtitle'] ?? '');

    $title =
        trim($_POST['title'] ?? '');

    $description =
        trim($_POST['description'] ?? '');

    $buttonText =
        trim($_POST['button_text'] ?? '');

    $buttonLink =
        trim($_POST['button_link'] ?? '');

    $status =
        isset($_POST['status'])
        ? 1
        : 0;


    if ($subtitle === '') {

        $errors[] =
            "Subtitle is required.";

    }


    if ($title === '') {

        $errors[] =
            "Hero title is required.";

    }


    $imageName =
        $hero['background_image'];


    // =================================================
    // NEW IMAGE
    // =================================================

    if (
        isset($_FILES['background_image']) &&
        $_FILES['background_image']['error']
        !== UPLOAD_ERR_NO_FILE
    ) {

        $file =
            $_FILES['background_image'];


        if (
            $file['error']
            !== UPLOAD_ERR_OK
        ) {

            $errors[] =
                "Image upload failed.";

        } else {

            $allowedTypes = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];


            $finfo =
                finfo_open(
                    FILEINFO_MIME_TYPE
                );


            $mimeType =
                finfo_file(
                    $finfo,
                    $file['tmp_name']
                );


            finfo_close($finfo);


            if (
                !in_array(
                    $mimeType,
                    $allowedTypes,
                    true
                )
            ) {

                $errors[] =
                    "Only JPG, PNG and WEBP images are allowed.";

            }


            if (
                $file['size']
                > 5 * 1024 * 1024
            ) {

                $errors[] =
                    "Image size must be less than 5MB.";

            }


            if (empty($errors)) {

                $uploadDir =
                    "../assets/images/hero/";


                if (!is_dir($uploadDir)) {

                    mkdir(
                        $uploadDir,
                        0755,
                        true
                    );

                }


                $extension = match ($mimeType) {

                    'image/jpeg' => 'jpg',

                    'image/png' => 'png',

                    'image/webp' => 'webp',

                    default => 'jpg'

                };


                $newImageName =
                    'hero_' .
                    time() .
                    '_' .
                    bin2hex(
                        random_bytes(4)
                    ) .
                    '.' .
                    $extension;


                if (
                    move_uploaded_file(
                        $file['tmp_name'],
                        $uploadDir .
                        $newImageName
                    )
                ) {

                    // Delete old image

                    if (
                        !empty($hero['background_image']) &&
                        file_exists(
                            $uploadDir .
                            $hero['background_image']
                        )
                    ) {

                        unlink(
                            $uploadDir .
                            $hero['background_image']
                        );

                    }


                    $imageName =
                        $newImageName;

                } else {

                    $errors[] =
                        "Unable to save new image.";

                }

            }

        }

    }


    // =================================================
    // UPDATE DATABASE
    // =================================================

    if (empty($errors)) {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE hero_sections
             SET
                subtitle = ?,
                title = ?,
                description = ?,
                button_text = ?,
                button_link = ?,
                background_image = ?,
                status = ?
             WHERE id = ?"
        );


        mysqli_stmt_bind_param(
            $stmt,
            "ssssssii",
            $subtitle,
            $title,
            $description,
            $buttonText,
            $buttonLink,
            $imageName,
            $status,
            $id
        );


        if (
            mysqli_stmt_execute($stmt)
        ) {

            mysqli_stmt_close($stmt);

            header(
                "Location: hero.php?success=updated"
            );

            exit;

        }


        mysqli_stmt_close($stmt);

        $errors[] =
            "Unable to update hero.";

    }


    // Keep entered values
    $hero['subtitle'] =
        $subtitle;

    $hero['title'] =
        $title;

    $hero['description'] =
        $description;

    $hero['button_text'] =
        $buttonText;

    $hero['button_link'] =
        $buttonLink;

    $hero['status'] =
        $status;

    $hero['background_image'] =
        $imageName;

}


$pageTitle = "Edit Hero";

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
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body {
    font-family:'Rubik',Arial,sans-serif;
    background:#f5f7fb;
    color:#333;
}

a {
    text-decoration:none;
}

.admin-wrapper {
    display:flex;
    min-height:100vh;
}

.sidebar {
    width:250px;
    background:linear-gradient(
        180deg,
        #1f8b38,
        #166b2d
    );
    color:#fff;
    position:fixed;
    left:0;
    top:0;
    bottom:0;
}

.sidebar-brand {
    padding:25px 20px;
    border-bottom:
        1px solid rgba(255,255,255,.12);
}

.brand-icon {
    width:48px;
    height:48px;
    background:
        rgba(255,255,255,.15);
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:25px;
    margin-bottom:12px;
}

.sidebar-brand h2 {
    font-size:17px;
    line-height:1.4;
}

.sidebar-brand p {
    font-size:11px;
    margin-top:5px;
    color:rgba(255,255,255,.75);
}

.sidebar-menu {
    padding:20px 12px;
}

.menu-title {
    color:rgba(255,255,255,.55);
    font-size:10px;
    text-transform:uppercase;
    letter-spacing:1px;
    padding:10px 12px;
}

.sidebar-menu a {
    display:flex;
    align-items:center;
    gap:12px;
    color:rgba(255,255,255,.85);
    padding:12px 13px;
    border-radius:8px;
    margin-bottom:4px;
    font-size:13px;
}

.sidebar-menu a:hover,
.sidebar-menu a.active {
    background:rgba(255,255,255,.15);
    color:#fff;
}

.menu-icon {
    width:24px;
    text-align:center;
}

.main-content {
    margin-left:250px;
    width:calc(100% - 250px);
}

.topbar {
    height:75px;
    background:#fff;
    border-bottom:1px solid #e9edf3;
    display:flex;
    align-items:center;
    padding:0 30px;
}

.topbar h1 {
    font-size:21px;
}

.topbar p {
    color:#999;
    font-size:12px;
    margin-top:3px;
}

.content {
    padding:30px;
}

.form-card {
    max-width:900px;
    background:#fff;
    border:1px solid #edf0f4;
    border-radius:13px;
    padding:25px;
}

.form-group {
    margin-bottom:20px;
}

.form-group label {
    display:block;
    font-size:12px;
    font-weight:600;
    margin-bottom:8px;
}

.form-control {
    width:100%;
    padding:12px 13px;
    border:1px solid #dfe4ea;
    border-radius:8px;
    outline:none;
    font-family:inherit;
    font-size:13px;
}

textarea.form-control {
    min-height:120px;
    resize:vertical;
}

.row {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
}

.current-image {
    width:100%;
    max-width:500px;
    height:220px;
    object-fit:cover;
    border-radius:10px;
    margin-bottom:12px;
}

.btn {
    display:inline-block;
    border:none;
    border-radius:8px;
    padding:12px 20px;
    font-size:12px;
    font-weight:600;
    cursor:pointer;
}

.btn-primary {
    background:#278c3c;
    color:#fff;
}

.btn-secondary {
    background:#f0f2f5;
    color:#555;
    margin-left:8px;
}

.alert {
    padding:13px 15px;
    background:#ffe9eb;
    color:#a51d2d;
    border-radius:8px;
    margin-bottom:20px;
    font-size:12px;
}

@media(max-width:900px) {

    .sidebar {
        transform:translateX(-100%);
    }

    .main-content {
        margin-left:0;
        width:100%;
    }

}

@media(max-width:600px) {

    .content {
        padding:15px;
    }

    .row {
        grid-template-columns:1fr;
    }

    .form-card {
        padding:18px;
    }

}

</style>

</head>

<body>

<div class="admin-wrapper">


<aside class="sidebar">

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


<main class="main-content">

    <header class="topbar">

        <div>

            <h1>
                Edit Hero
            </h1>

            <p>
                Update homepage hero banner
            </p>

        </div>

    </header>


    <div class="content">

        <div class="form-card">


            <?php if (!empty($errors)): ?>

                <div class="alert">

                    <?php foreach ($errors as $error): ?>

                        <div>
                            <?= htmlspecialchars($error) ?>
                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                enctype="multipart/form-data"
            >


                <div class="row">

                    <div class="form-group">

                        <label>
                            Subtitle
                        </label>

                        <input
                            type="text"
                            name="subtitle"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $hero['subtitle']
                            ) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Hero Title
                        </label>

                        <input
                            type="text"
                            name="title"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $hero['title']
                            ) ?>"
                            required
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label>
                        Description
                    </label>

                    <textarea
                        name="description"
                        class="form-control"
                    ><?= htmlspecialchars(
                        $hero['description'] ?? ''
                    ) ?></textarea>

                </div>


                <div class="row">

                    <div class="form-group">

                        <label>
                            Button Text
                        </label>

                        <input
                            type="text"
                            name="button_text"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $hero['button_text']
                            ) ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Button Link
                        </label>

                        <input
                            type="text"
                            name="button_link"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $hero['button_link']
                            ) ?>"
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label>
                        Current Background Image
                    </label>

                    <?php if (
                        !empty(
                            $hero['background_image']
                        )
                    ): ?>

                        <img
                            src="../assets/images/hero/<?= htmlspecialchars(
                                $hero['background_image']
                            ) ?>"
                            class="current-image"
                            alt="Hero"
                        >

                    <?php endif; ?>


                    <label>
                        Change Image (optional)
                    </label>

                    <input
                        type="file"
                        name="background_image"
                        class="form-control"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                </div>


                <div class="form-group">

                    <label>

                        <input
                            type="checkbox"
                            name="status"
                            value="1"
                            <?= $hero['status']
                                ? 'checked'
                                : ''
                            ?>
                        >

                        Active Hero

                    </label>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Update Hero
                </button>


                <a
                    href="hero.php"
                    class="btn btn-secondary"
                >
                    Cancel
                </a>


            </form>

        </div>

    </div>

</main>

</div>

</body>

</html>
