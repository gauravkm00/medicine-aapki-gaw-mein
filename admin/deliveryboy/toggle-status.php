
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

function redirectToIndex($type, $message)
{
    $_SESSION[$type] = $message;

    header("Location: index.php");
    exit;
}


// =====================================================
// GET DELIVERY BOY ID
// =====================================================

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


// =====================================================
// VALIDATE ID
// =====================================================

if (!$id || $id <= 0) {

    redirectToIndex(
        'error',
        'Invalid delivery boy ID.'
    );
}


// =====================================================
// CHECK DATABASE CONNECTION
// =====================================================

if (!$conn) {

    redirectToIndex(
        'error',
        'Database connection failed.'
    );
}


// =====================================================
// FETCH DELIVERY BOY
// =====================================================

$sql = "
    SELECT
        id,
        name,
        mobile,
        email,
        role,
        status
    FROM users
    WHERE id = ?
    AND role = 'delivery'
    LIMIT 1
";


$stmt = mysqli_prepare(
    $conn,
    $sql
);


if (!$stmt) {

    redirectToIndex(
        'error',
        'Unable to prepare database query.'
    );
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);


if (!mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    redirectToIndex(
        'error',
        'Unable to fetch delivery boy.'
    );
}


$result = mysqli_stmt_get_result($stmt);

$deliveryBoy = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


// =====================================================
// DELIVERY BOY NOT FOUND
// =====================================================

if (!$deliveryBoy) {

    redirectToIndex(
        'error',
        'Delivery boy not found.'
    );
}


// =====================================================
// CURRENT STATUS
// =====================================================

$currentStatus = (int) $deliveryBoy['status'];


// =====================================================
// NEW STATUS
// =====================================================

$newStatus = $currentStatus === 1
    ? 0
    : 1;


// =====================================================
// UPDATE DELIVERY BOY STATUS
// =====================================================

$updateSql = "
    UPDATE users
    SET
        status = ?,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = ?
    AND role = 'delivery'
    LIMIT 1
";


$updateStmt = mysqli_prepare(
    $conn,
    $updateSql
);


if (!$updateStmt) {

    redirectToIndex(
        'error',
        'Unable to prepare status update.'
    );
}


mysqli_stmt_bind_param(
    $updateStmt,
    "ii",
    $newStatus,
    $id
);


// =====================================================
// EXECUTE UPDATE
// =====================================================

if (!mysqli_stmt_execute($updateStmt)) {

    mysqli_stmt_close($updateStmt);

    redirectToIndex(
        'error',
        'Unable to update delivery boy status.'
    );
}


// =====================================================
// CHECK AFFECTED ROWS
// =====================================================

$affectedRows = mysqli_stmt_affected_rows(
    $updateStmt
);


mysqli_stmt_close($updateStmt);


// =====================================================
// UPDATE SUCCESS
// =====================================================

if ($affectedRows >= 0) {

    $deliveryBoyName =
        $deliveryBoy['name']
        ?: 'Delivery boy';


    // -------------------------------------------------
    // ACTIVATED
    // -------------------------------------------------

    if ($newStatus === 1) {

        $_SESSION['success'] =
            $deliveryBoyName .
            ' has been activated successfully.';

    }


    // -------------------------------------------------
    // DEACTIVATED
    // -------------------------------------------------

    else {

        $_SESSION['success'] =
            $deliveryBoyName .
            ' has been deactivated successfully.';

    }


    header("Location: index.php");

    exit;
}


// =====================================================
// FALLBACK ERROR
// =====================================================

$_SESSION['error'] =
    'Delivery boy status could not be changed.';

header("Location: index.php");

exit;

