<?php

session_start();

require_once "../config/database.php";


// =====================================================
// AUTH
// =====================================================

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    strtolower((string) $_SESSION['role']) !== 'customer'
) {
    header("Location: ../login.php");
    exit;
}


$customerId = (int) $_SESSION['user_id'];


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
// GET CUSTOMER
// =====================================================

$customer = null;

$stmt = $conn->prepare("
    SELECT
        id,
        name,
        password
    FROM users
    WHERE id = ?
    AND role = 'customer'
    LIMIT 1
");

$stmt->bind_param(
    "i",
    $customerId
);

$stmt->execute();

$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $customer = $result->fetch_assoc();
}

$stmt->close();


if (!$customer) {

    session_destroy();

    header("Location: ../login.php");

    exit;
}


// =====================================================
// VARIABLES
// =====================================================

$success = '';
$error = '';


// =====================================================
// CHANGE PASSWORD
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $currentPassword =
        $_POST['current_password'] ?? '';

    $newPassword =
        $_POST['new_password'] ?? '';

    $confirmPassword =
        $_POST['confirm_password'] ?? '';


    // -------------------------------------------------
    // VALIDATION
    // -------------------------------------------------

    if ($currentPassword === '') {

        $error =
            'Please enter your current password.';

    }
    elseif ($newPassword === '') {

        $error =
            'Please enter a new password.';

    }
    elseif (strlen($newPassword) < 6) {

        $error =
            'New password must be at least 6 characters.';

    }
    elseif ($confirmPassword === '') {

        $error =
            'Please confirm your new password.';

    }
    elseif ($newPassword !== $confirmPassword) {

        $error =
            'New password and confirm password do not match.';

    }
    elseif (
        password_verify(
            $newPassword,
            $customer['password']
        )
    ) {

        $error =
            'New password must be different from current password.';

    }


    // -------------------------------------------------
    // VERIFY CURRENT PASSWORD
    // -------------------------------------------------

    if ($error === '') {

        if (
            !password_verify(
                $currentPassword,
                $customer['password']
            )
        ) {

            $error =
                'Current password is incorrect.';
        }
    }


    // -------------------------------------------------
    // UPDATE PASSWORD
    // -------------------------------------------------

    if ($error === '') {

        $hashedPassword =
            password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );


        $stmt = $conn->prepare("
            UPDATE users
            SET
                password = ?,
                updated_at = NOW()
            WHERE id = ?
            AND role = 'customer'
        ");


        if ($stmt) {

            $stmt->bind_param(
                "si",
                $hashedPassword,
                $customerId
            );


            if ($stmt->execute()) {

                $success =
                    'Your password has been changed successfully.';

            } else {

                $error =
                    'Unable to change password. Please try again.';
            }


            $stmt->close();

        } else {

            $error =
                'Database error. Please try again.';
        }
    }
}


$customerName =
    $customer['name'] ?: 'Customer';


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
Change Password | Medicine Aapki Gaw Mein
</title>

<link
    href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
>

<style>

* {
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body {
    font-family:'Rubik',sans-serif;
    background:#f5f7f6;
    color:#26332a;
}

a {
    text-decoration:none;
    color:inherit;
}

.sidebar {
    position:fixed;
    top:0;
    left:0;

    width:250px;
    height:100vh;

    background:
        linear-gradient(
            180deg,
            #1f8b38,
            #166b2d
        );

    color:#fff;

    z-index:1000;

    overflow-y:auto;
}

.brand {
    padding:25px 20px;

    display:flex;
    align-items:center;

    gap:13px;

    border-bottom:
        1px solid
        rgba(255,255,255,.15);
}

.brand-icon {
    width:45px;
    height:45px;

    border-radius:12px;

    background:
        rgba(255,255,255,.16);

    display:flex;

    align-items:center;
    justify-content:center;

    font-size:24px;
}

.brand-text h2 {
    font-size:16px;
    font-weight:600;
}

.brand-text span {
    display:block;

    margin-top:3px;

    font-size:11px;

    opacity:.75;
}

.menu {
    padding:20px 12px;
}

.menu-title {
    padding:
        12px 12px 8px;

    font-size:10px;

    font-weight:600;

    letter-spacing:1px;

    text-transform:uppercase;

    opacity:.55;
}

.menu-item {
    display:flex;

    align-items:center;

    gap:12px;

    padding:12px 13px;

    margin-bottom:5px;

    border-radius:9px;

    font-size:13px;

    transition:.2s;
}

.menu-item:hover {
    background:
        rgba(255,255,255,.10);
}

.menu-item.active {
    background:
        rgba(255,255,255,.18);
}

.menu-icon {
    width:22px;

    text-align:center;

    font-size:16px;
}

.main {
    margin-left:250px;

    min-height:100vh;
}

.topbar {
    height:75px;

    background:#fff;

    display:flex;

    align-items:center;

    justify-content:space-between;

    padding:0 30px;

    border-bottom:
        1px solid #e8ece9;
}

.topbar-title h1 {
    font-size:21px;
    font-weight:600;
}

.topbar-title p {
    margin-top:3px;

    font-size:12px;

    color:#89938c;
}

.profile {
    display:flex;

    align-items:center;

    gap:10px;
}

.profile-avatar {
    width:39px;
    height:39px;

    border-radius:50%;

    background:#e4f4e7;

    color:#238b39;

    display:flex;

    align-items:center;
    justify-content:center;

    font-size:15px;

    font-weight:600;
}

.profile-info strong {
    display:block;

    font-size:13px;

    font-weight:500;
}

.profile-info span {
    display:block;

    font-size:11px;

    color:#909a94;

    margin-top:2px;
}

.content {
    padding:30px;
}

.back {
    display:inline-flex;

    margin-bottom:18px;

    color:#238b39;

    font-size:12px;

    font-weight:500;
}

.card {
    max-width:600px;

    background:#fff;

    border:1px solid #e7ece8;

    border-radius:13px;

    box-shadow:
        0 2px 8px
        rgba(30,50,35,.03);

    overflow:hidden;
}

.card-header {
    padding:20px;

    border-bottom:
        1px solid #edf0ee;
}

.card-header h3 {
    font-size:16px;

    font-weight:500;
}

.card-header p {
    margin-top:5px;

    font-size:10px;

    color:#929b95;
}

.form-body {
    padding:25px;
}

.form-group {
    margin-bottom:18px;
}

label {
    display:block;

    margin-bottom:7px;

    font-size:10px;

    color:#737e77;
}

input {
    width:100%;

    border:1px solid #dfe7e1;

    border-radius:8px;

    padding:11px 12px;

    font-family:inherit;

    font-size:11px;

    outline:none;
}

input:focus {
    border-color:#6fba7d;

    box-shadow:
        0 0 0 3px
        rgba(35,139,57,.08);
}

.password-help {
    margin-top:15px;

    padding:13px;

    border-radius:9px;

    background:#f8fcf9;

    border:1px solid #e1eee4;
}

.password-help strong {
    display:block;

    font-size:11px;

    color:#238b39;

    margin-bottom:5px;
}

.password-help p {
    font-size:9px;

    color:#7f8983;

    line-height:1.6;
}

.alert {
    padding:12px 15px;

    border-radius:8px;

    margin-bottom:18px;

    font-size:10px;
}

.alert.success {
    background:#e9f7ec;

    border:1px solid #cce8d1;

    color:#237238;
}

.alert.error {
    background:#fff0f0;

    border:1px solid #ffd7d7;

    color:#a43d3d;
}

.buttons {
    display:flex;

    gap:8px;

    margin-top:22px;
}

.btn {
    display:inline-flex;

    align-items:center;
    justify-content:center;

    padding:11px 17px;

    border:0;

    border-radius:8px;

    font-family:inherit;

    font-size:10px;

    font-weight:500;

    cursor:pointer;
}

.btn-primary {
    background:#238b39;
    color:#fff;
}

.btn-primary:hover {
    background:#1b7130;
}

.btn-light {
    background:#eaf7ed;
    color:#238b39;
}

.btn-light:hover {
    background:#238b39;
    color:#fff;
}

@media(max-width:850px) {

    .sidebar {
        width:70px;
    }

    .brand {
        justify-content:center;
        padding:18px 10px;
    }

    .brand-text,
    .menu-title,
    .menu-item span:not(.menu-icon) {
        display:none;
    }

    .menu-item {
        justify-content:center;
    }

    .main {
        margin-left:70px;
    }

    .content {
        padding:20px;
    }

}

@media(max-width:600px) {

    .content {
        padding:15px;
    }

    .topbar {
        height:68px;
        padding:0 15px;
    }

    .topbar-title h1 {
        font-size:17px;
    }

    .topbar-title p,
    .profile-info {
        display:none;
    }

    .form-body {
        padding:18px;
    }

}

</style>

</head>

<body>


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


<div class="menu-title">
Prescription
</div>

<a href="prescriptions.php" class="menu-item">

<span class="menu-icon">📋</span>
<span>My Prescriptions</span>

</a>

<a href="upload-prescription.php" class="menu-item">

<span class="menu-icon">📤</span>
<span>Upload Prescription</span>

</a>


<div class="menu-title">
Account
</div>

<a href="profile.php" class="menu-item">

<span class="menu-icon">👤</span>
<span>My Profile</span>

</a>

<a href="addresses.php" class="menu-item">

<span class="menu-icon">📍</span>
<span>My Addresses</span>

</a>

<a
    href="change-password.php"
    class="menu-item active"
>

<span class="menu-icon">🔐</span>
<span>Change Password</span>

</a>


<div class="menu-title">
More
</div>

<a href="../index.php" class="menu-item">

<span class="menu-icon">🌐</span>
<span>View Website</span>

</a>

<a href="../logout.php" class="menu-item">

<span class="menu-icon">🚪</span>
<span>Logout</span>

</a>

</nav>

</aside>


<main class="main">


<header class="topbar">

<div class="topbar-title">

<h1>
Change Password
</h1>

<p>
Keep your account secure
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


<section class="content">


<a
    href="profile.php"
    class="back"
>
← Back to Profile
</a>


<div class="card">


<div class="card-header">

<h3>
Change Your Password
</h3>

<p>
Use a strong password that you don't use elsewhere.
</p>

</div>


<div class="form-body">


<?php if ($success !== ''): ?>

<div class="alert success">
<?= e($success) ?>
</div>

<?php endif; ?>


<?php if ($error !== ''): ?>

<div class="alert error">
<?= e($error) ?>
</div>

<?php endif; ?>


<form method="POST">


<div class="form-group">

<label>
Current Password *
</label>

<input
    type="password"
    name="current_password"
    autocomplete="current-password"
    required
>

</div>


<div class="form-group">

<label>
New Password *
</label>

<input
    type="password"
    name="new_password"
    minlength="6"
    autocomplete="new-password"
    required
>

</div>


<div class="form-group">

<label>
Confirm New Password *
</label>

<input
    type="password"
    name="confirm_password"
    minlength="6"
    autocomplete="new-password"
    required
>

</div>


<div class="password-help">

<strong>
🔐 Password Requirements
</strong>

<p>
Your password must contain at least 6 characters.
For better security, use a combination of letters,
numbers and special characters.
</p>

</div>


<div class="buttons">

<button
    type="submit"
    class="btn btn-primary"
>
🔐 Change Password
</button>


<a
    href="profile.php"
    class="btn btn-light"
>
Cancel
</a>

</div>


</form>

</div>

</div>

</section>

</main>

</body>

</html>