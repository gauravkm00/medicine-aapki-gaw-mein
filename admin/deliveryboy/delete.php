<?php

session_start();

require_once "../../config/database.php";


// =====================================================
// ADMIN AUTHENTICATION
// =====================================================

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../login.php");
    exit;
}


// =====================================================
// HELPER FUNCTION
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
// GET DELIVERY BOY ID
// =====================================================

$id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


// =====================================================
// INVALID ID
// =====================================================

if ($id <= 0) {

    header("Location: index.php?error=invalid_id");

    exit;
}


// =====================================================
// PREVENT SELF DELETE
// =====================================================

if ($id === (int)$_SESSION['user_id']) {

    header("Location: index.php?error=self_delete");

    exit;
}


// =====================================================
// FETCH DELIVERY BOY
// =====================================================

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        name,
        mobile,
        email,
        role,
        address,
        city,
        state,
        pincode,
        status,
        created_at
     FROM users
     WHERE id = ?
     AND role = 'delivery'
     LIMIT 1"
);


if (!$stmt) {

    header("Location: index.php?error=database");

    exit;
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);


mysqli_stmt_execute($stmt);


$result = mysqli_stmt_get_result($stmt);


$deliveryBoy = mysqli_fetch_assoc($result);


mysqli_stmt_close($stmt);


// =====================================================
// DELIVERY BOY NOT FOUND
// =====================================================

if (!$deliveryBoy) {

    header("Location: index.php?error=not_found");

    exit;
}


// =====================================================
// DELETE REQUEST
// =====================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['confirm_delete'])
) {


    // -------------------------------------------------
    // Verify ID again
    // -------------------------------------------------

    $deleteId = isset($_POST['id'])
        ? (int)$_POST['id']
        : 0;


    if ($deleteId <= 0 || $deleteId !== $id) {

        header("Location: index.php?error=invalid_id");

        exit;
    }


    // -------------------------------------------------
    // Prevent self delete again
    // -------------------------------------------------

    if ($deleteId === (int)$_SESSION['user_id']) {

        header("Location: index.php?error=self_delete");

        exit;
    }


    // -------------------------------------------------
    // Delete only delivery role
    // -------------------------------------------------

    $deleteStmt = mysqli_prepare(
        $conn,
        "DELETE FROM users
         WHERE id = ?
         AND role = 'delivery'
         LIMIT 1"
    );


    if (!$deleteStmt) {

        header("Location: index.php?error=delete_failed");

        exit;
    }


    mysqli_stmt_bind_param(
        $deleteStmt,
        "i",
        $deleteId
    );


    $deleteSuccess =
        mysqli_stmt_execute($deleteStmt);


    $affectedRows =
        mysqli_stmt_affected_rows($deleteStmt);


    mysqli_stmt_close($deleteStmt);


    // -------------------------------------------------
    // SUCCESS
    // -------------------------------------------------

    if (
        $deleteSuccess &&
        $affectedRows > 0
    ) {

        header(
            "Location: index.php?success=deleted"
        );

        exit;
    }


    // -------------------------------------------------
    // FAILED
    // -------------------------------------------------

    header(
        "Location: index.php?error=delete_failed"
    );

    exit;
}


// =====================================================
// PAGE DATA
// =====================================================

$name = $deliveryBoy['name'] ?? 'Delivery Boy';

$mobile = $deliveryBoy['mobile'] ?? '';

$email = $deliveryBoy['email'] ?? '';

$address = $deliveryBoy['address'] ?? '';

$city = $deliveryBoy['city'] ?? '';

$state = $deliveryBoy['state'] ?? '';

$pincode = $deliveryBoy['pincode'] ?? '';

$status = (int)($deliveryBoy['status'] ?? 0);

$createdAt = $deliveryBoy['created_at'] ?? '';


// =====================================================
// INITIAL
// =====================================================

$initial = strtoupper(
    substr(
        trim($name ?: 'D'),
        0,
        1
    )
);


// =====================================================
// PAGE TITLE
// =====================================================

$pageTitle = "Delete Delivery Boy";

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
}


/* =====================================================
   HEADER
===================================================== */

.page-header {

    min-height: 75px;

    background: #fff;

    border-bottom: 1px solid #e9edf3;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 30px;

}


.header-left {

    display: flex;

    align-items: center;

    gap: 12px;

}


.back-btn {

    width: 40px;

    height: 40px;

    border-radius: 9px;

    background: #eaf7ec;

    color: #238b39;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 19px;

    font-weight: 600;

    transition: .2s;

}


.back-btn:hover {

    background: #dff3e2;

}


.header-title h1 {

    font-size: 20px;

    color: #222;

    font-weight: 600;

}


.header-title p {

    font-size: 11px;

    color: #999;

    margin-top: 4px;

}


/* =====================================================
   MAIN
===================================================== */

.content {

    width: 100%;

    max-width: 850px;

    margin: 0 auto;

    padding: 35px 25px;

}


/* =====================================================
   DELETE CARD
===================================================== */

.delete-card {

    background: #fff;

    border: 1px solid #edf0f4;

    border-radius: 14px;

    overflow: hidden;

    box-shadow:
        0 5px 25px rgba(0,0,0,.04);

}


/* =====================================================
   WARNING HEADER
===================================================== */

.warning-header {

    background: #fff5f5;

    border-bottom: 1px solid #f7d8db;

    padding: 25px;

    text-align: center;

}


.warning-icon {

    width: 70px;

    height: 70px;

    margin: 0 auto 15px;

    border-radius: 50%;

    background: #ffe3e6;

    color: #c62839;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 30px;

}


.warning-header h2 {

    font-size: 20px;

    color: #a51d2d;

    margin-bottom: 7px;

}


.warning-header p {

    font-size: 12px;

    color: #777;

    line-height: 1.6;

}


/* =====================================================
   DELIVERY PROFILE
===================================================== */

.profile-section {

    padding: 25px;

}


.profile {

    display: flex;

    align-items: center;

    gap: 15px;

    padding: 18px;

    background: #fafbfc;

    border: 1px solid #edf0f4;

    border-radius: 11px;

}


.avatar {

    width: 58px;

    height: 58px;

    border-radius: 50%;

    background: #eaf7ec;

    color: #238b39;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;

    font-weight: 700;

    flex-shrink: 0;

}


.profile-info {

    flex: 1;

}


.profile-info h3 {

    font-size: 15px;

    color: #222;

}


.profile-info p {

    font-size: 11px;

    color: #999;

    margin-top: 4px;

}


.status {

    display: inline-block;

    margin-top: 7px;

    padding: 4px 8px;

    border-radius: 20px;

    font-size: 9px;

    font-weight: 600;

}


.status-active {

    background: #dff5e3;

    color: #14752f;

}


.status-inactive {

    background: #ffe3e6;

    color: #a51d2d;

}


/* =====================================================
   DETAILS
===================================================== */

.details {

    margin-top: 18px;

    display: grid;

    grid-template-columns: repeat(2, 1fr);

    gap: 1px;

    background: #edf0f4;

    border: 1px solid #edf0f4;

    border-radius: 10px;

    overflow: hidden;

}


.detail {

    background: #fff;

    padding: 14px 16px;

}


.detail-label {

    display: block;

    color: #999;

    font-size: 10px;

    margin-bottom: 5px;

}


.detail-value {

    display: block;

    color: #333;

    font-size: 12px;

    font-weight: 500;

    word-break: break-word;

}


/* =====================================================
   WARNING MESSAGE
===================================================== */

.warning-box {

    margin-top: 20px;

    padding: 14px 16px;

    background: #fff8e5;

    border: 1px solid #ffe4a3;

    border-radius: 9px;

    color: #856404;

    font-size: 11px;

    line-height: 1.6;

}


.warning-box strong {

    display: block;

    margin-bottom: 4px;

    font-size: 12px;

}


/* =====================================================
   ACTIONS
===================================================== */

.actions {

    padding: 20px 25px 25px;

    border-top: 1px solid #edf0f4;

    display: flex;

    justify-content: flex-end;

    gap: 10px;

}


.btn {

    border: none;

    cursor: pointer;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    min-height: 42px;

    padding: 0 18px;

    border-radius: 8px;

    font-family: inherit;

    font-size: 12px;

    font-weight: 600;

    transition: .2s;

}


.btn-cancel {

    background: #eef2f5;

    color: #555;

}


.btn-cancel:hover {

    background: #e2e7eb;

}


.btn-delete {

    background: #d63031;

    color: #fff;

}


.btn-delete:hover {

    background: #b92324;

}


.btn-delete:active {

    transform: scale(.98);

}


/* =====================================================
   FOOTER NOTE
===================================================== */

.footer-note {

    text-align: center;

    margin-top: 18px;

    color: #aaa;

    font-size: 10px;

}


/* =====================================================
   MOBILE
===================================================== */

@media (max-width: 600px) {

    .page-header {

        padding: 15px;

    }


    .header-title h1 {

        font-size: 17px;

    }


    .header-title p {

        display: none;

    }


    .content {

        padding: 20px 15px;

    }


    .profile-section {

        padding: 18px;

    }


    .details {

        grid-template-columns: 1fr;

    }


    .actions {

        padding: 18px;

        display: grid;

        grid-template-columns: 1fr 1fr;

    }


    .btn {

        width: 100%;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     PAGE HEADER
===================================================== -->

<header class="page-header">

    <div class="header-left">

        <a
            href="index.php"
            class="back-btn"
            title="Back"
        >
            ←
        </a>


        <div class="header-title">

            <h1>
                Delete Delivery Boy
            </h1>

            <p>
                Remove delivery partner from the system
            </p>

        </div>

    </div>

</header>


<!-- =====================================================
     CONTENT
===================================================== -->

<main class="content">


<div class="delete-card">


<!-- =====================================================
     WARNING
===================================================== -->

<div class="warning-header">

    <div class="warning-icon">
        🗑️
    </div>


    <h2>
        Delete Delivery Boy?
    </h2>


    <p>
        You are about to permanently delete this
        delivery boy account.
        <br>
        This action cannot be undone.
    </p>

</div>


<!-- =====================================================
     PROFILE & DETAILS
===================================================== -->

<div class="profile-section">


<div class="profile">


    <div class="avatar">

        <?= e($initial) ?>

    </div>


    <div class="profile-info">

        <h3>
            <?= e($name) ?>
        </h3>


        <p>
            🚚 Delivery Partner
        </p>


        <?php if ($status === 1): ?>

            <span class="status status-active">
                ● Active
            </span>

        <?php else: ?>

            <span class="status status-inactive">
                ● Inactive
            </span>

        <?php endif; ?>


    </div>

</div>


<!-- =====================================================
     DETAILS
===================================================== -->

<div class="details">


    <div class="detail">

        <span class="detail-label">
            Mobile Number
        </span>

        <span class="detail-value">
            <?= e($mobile ?: 'Not provided') ?>
        </span>

    </div>


    <div class="detail">

        <span class="detail-label">
            Email Address
        </span>

        <span class="detail-value">
            <?= e($email ?: 'Not provided') ?>
        </span>

    </div>


    <div class="detail">

        <span class="detail-label">
            City
        </span>

        <span class="detail-value">
            <?= e($city ?: 'Not provided') ?>
        </span>

    </div>


    <div class="detail">

        <span class="detail-label">
            State
        </span>

        <span class="detail-value">
            <?= e($state ?: 'Not provided') ?>
        </span>

    </div>


    <div class="detail">

        <span class="detail-label">
            Pincode
        </span>

        <span class="detail-value">
            <?= e($pincode ?: 'Not provided') ?>
        </span>

    </div>


    <div class="detail">

        <span class="detail-label">
            Member Since
        </span>

        <span class="detail-value">

            <?php if (!empty($createdAt)): ?>

                <?= date(
                    "d M Y",
                    strtotime($createdAt)
                ) ?>

            <?php else: ?>

                Not available

            <?php endif; ?>

        </span>

    </div>


    <div
        class="detail"
        style="grid-column:1/-1;"
    >

        <span class="detail-label">
            Address
        </span>

        <span class="detail-value">

            <?= !empty($address)
                ? nl2br(e($address))
                : 'Not provided'
            ?>

        </span>

    </div>


</div>


<!-- =====================================================
     WARNING MESSAGE
===================================================== -->

<div class="warning-box">

    <strong>
        ⚠️ Important
    </strong>

    Deleting this account will permanently remove
    the delivery boy from the
    <strong style="display:inline;">
        users
    </strong>
    table.

    Make sure this delivery boy is no longer required
    before continuing.

</div>


</div>


<!-- =====================================================
     ACTIONS
===================================================== -->

<div class="actions">


    <a
        href="index.php"
        class="btn btn-cancel"
    >
        ← Cancel
    </a>


    <form
        method="POST"
        action="delete.php?id=<?= $id ?>"
        style="margin:0;"
        onsubmit="
            return confirm(
                'Are you absolutely sure you want to permanently delete <?= e(addslashes($name)) ?>?'
            );
        "
    >

        <input
            type="hidden"
            name="id"
            value="<?= $id ?>"
        >


        <input
            type="hidden"
            name="confirm_delete"
            value="1"
        >


        <button
            type="submit"
            class="btn btn-delete"
        >
            🗑️ Delete Delivery Boy
        </button>

    </form>


</div>


</div>


<div class="footer-note">

    Medicine Aapki Gaw Mein • Administration Panel

</div>


</main>


</body>

</html>

