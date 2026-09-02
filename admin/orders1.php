<?php

session_start();

require_once "../config/database.php";


/* =====================================================
   ADMIN AUTHENTICATION
===================================================== */

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
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
   VARIABLES
===================================================== */

$message = "";
$error = "";

$action = $_GET['action'] ?? 'list';

$edit_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

$search = trim($_GET['search'] ?? '');

$orderStatus = trim($_GET['order_status'] ?? '');

$paymentStatus = trim($_GET['payment_status'] ?? '');

$order = null;
$orders = null;
$orderItems = null;


/* =====================================================
   ALLOWED ORDER STATUS
===================================================== */

$allowedOrderStatuses = [
    'pending',
    'confirmed',
    'processing',
    'ready',
    'out_for_delivery',
    'delivered',
    'cancelled'
];


/* =====================================================
   ALLOWED PAYMENT STATUS
===================================================== */

$allowedPaymentStatuses = [
    'pending',
    'paid',
    'failed',
    'refunded'
];


/* =====================================================
   DELIVERY STATUS
===================================================== */

$allowedDeliveryStatuses = [
    'pending',
    'assigned',
    'picked_up',
    'out_for_delivery',
    'delivered',
    'failed',
    'cancelled'
];


/* =====================================================
   ORDER → DELIVERY STATUS MAPPING
===================================================== */

function getDeliveryStatusFromOrderStatus($orderStatus)
{
    switch ($orderStatus) {

        case 'confirmed':
        case 'processing':
        case 'ready':
            return 'assigned';

        case 'out_for_delivery':
            return 'out_for_delivery';

        case 'delivered':
            return 'delivered';

        case 'cancelled':
            return 'cancelled';

        case 'pending':
        default:
            return 'pending';
    }
}


/* =====================================================
   MONEY FORMAT
===================================================== */

function formatMoney($amount)
{
    return '₹' . number_format(
        (float)$amount,
        2
    );
}


/* =====================================================
   ORDER STATUS BADGE
===================================================== */

function getOrderStatusBadge($status)
{
    $status = strtolower(trim((string)$status));

    $labels = [

        'pending' => [
            'Pending',
            'warning'
        ],

        'confirmed' => [
            'Confirmed',
            'info'
        ],

        'processing' => [
            'Processing',
            'info'
        ],

        'ready' => [
            'Ready',
            'success'
        ],

        'out_for_delivery' => [
            'Out for Delivery',
            'warning'
        ],

        'delivered' => [
            'Delivered',
            'success'
        ],

        'cancelled' => [
            'Cancelled',
            'danger'
        ]
    ];

    return $labels[$status] ?? [
        ucfirst(
            str_replace(
                '_',
                ' ',
                $status
            )
        ),
        'secondary'
    ];
}


/* =====================================================
   PAYMENT STATUS BADGE
===================================================== */

function getPaymentStatusBadge($status)
{
    $status = strtolower(trim((string)$status));

    $labels = [

        'pending' => [
            'Pending',
            'warning'
        ],

        'paid' => [
            'Paid',
            'success'
        ],

        'failed' => [
            'Failed',
            'danger'
        ],

        'refunded' => [
            'Refunded',
            'info'
        ]
    ];

    return $labels[$status] ?? [
        ucfirst($status),
        'secondary'
    ];
}


/* =====================================================
   POST REQUEST
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* =================================================
       CSRF CHECK
    ================================================= */

    $postedToken = $_POST['csrf_token'] ?? '';

    if (
        empty($postedToken) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals(
            $_SESSION['csrf_token'],
            $postedToken
        )
    ) {

        $error = "Invalid request. Please refresh the page and try again.";

    } else {

        /* =================================================
           UPDATE ORDER
        ================================================= */

        if (($_POST['action'] ?? '') === 'update_order') {

            $id = (int)($_POST['id'] ?? 0);

            $newOrderStatus = trim(
                $_POST['order_status'] ?? ''
            );

            $newPaymentStatus = trim(
                $_POST['payment_status'] ?? ''
            );

            $adminNote = trim(
                $_POST['admin_note'] ?? ''
            );


            /* =============================================
               VALIDATION
            ============================================= */

            if ($id <= 0) {

                $error = "Invalid order ID.";

            } elseif (
                !in_array(
                    $newOrderStatus,
                    $allowedOrderStatuses,
                    true
                )
            ) {

                $error = "Invalid order status.";

            } elseif (
                !in_array(
                    $newPaymentStatus,
                    $allowedPaymentStatuses,
                    true
                )
            ) {

                $error = "Invalid payment status.";

            } else {

                /* =========================================
                   GET EXISTING ORDER
                ========================================= */

                $checkOrder = $conn->prepare(
                    "SELECT
                        id,
                        order_number,
                        order_status,
                        payment_status
                     FROM orders
                     WHERE id = ?
                     LIMIT 1"
                );

                if (!$checkOrder) {

                    $error =
                        "Database error while checking order.";

                } else {

                    $checkOrder->bind_param(
                        "i",
                        $id
                    );

                    if (!$checkOrder->execute()) {

                        $error =
                            "Unable to check order.";

                        $checkOrder->close();

                    } else {

                        $orderResult =
                            $checkOrder->get_result();

                        if (
                            !$orderResult ||
                            $orderResult->num_rows !== 1
                        ) {

                            $error =
                                "Order not found.";

                            $checkOrder->close();

                        } else {

                            $existingOrder =
                                $orderResult->fetch_assoc();

                            $checkOrder->close();


                            /* =================================
                               CALCULATE DELIVERY STATUS
                            ================================= */

                            $newDeliveryStatus =
                                getDeliveryStatusFromOrderStatus(
                                    $newOrderStatus
                                );


                            /* =================================
                               START TRANSACTION
                            ================================= */

                            $conn->begin_transaction();


                            try {

                                /* =================================
                                   1. UPDATE ORDER
                                ================================= */

                                $updateOrder = $conn->prepare(
                                    "UPDATE orders
                                     SET
                                        order_status = ?,
                                        payment_status = ?,
                                        admin_note = ?,
                                        updated_at = CURRENT_TIMESTAMP
                                     WHERE id = ?"
                                );


                                if (!$updateOrder) {

                                    throw new Exception(
                                        "Order update prepare failed."
                                    );
                                }


                                $updateOrder->bind_param(
                                    "sssi",
                                    $newOrderStatus,
                                    $newPaymentStatus,
                                    $adminNote,
                                    $id
                                );


                                if (
                                    !$updateOrder->execute()
                                ) {

                                    throw new Exception(
                                        "Order update failed: " .
                                        $updateOrder->error
                                    );
                                }


                                $updateOrder->close();


                                /* =================================
                                   2. CHECK DELIVERY
                                ================================= */

                                $checkDelivery = $conn->prepare(
                                    "SELECT
                                        id,
                                        status,
                                        delivery_person_id
                                     FROM deliveries
                                     WHERE order_id = ?
                                     LIMIT 1"
                                );


                                if (!$checkDelivery) {

                                    throw new Exception(
                                        "Delivery check prepare failed."
                                    );
                                }


                                $checkDelivery->bind_param(
                                    "i",
                                    $id
                                );


                                if (
                                    !$checkDelivery->execute()
                                ) {

                                    throw new Exception(
                                        "Unable to check delivery."
                                    );
                                }


                                $deliveryResult =
                                    $checkDelivery->get_result();


                                $existingDelivery = null;


                                if (
                                    $deliveryResult &&
                                    $deliveryResult->num_rows > 0
                                ) {

                                    $existingDelivery =
                                        $deliveryResult->fetch_assoc();
                                }


                                $checkDelivery->close();


                                /* =================================
                                   3. UPDATE EXISTING DELIVERY
                                ================================= */

                                if ($existingDelivery) {

                                    $deliveryId =
                                        (int)$existingDelivery['id'];


                                    /* =================================
                                       PENDING
                                    ================================= */

                                    if (
                                        $newDeliveryStatus ===
                                        'pending'
                                    ) {

                                        $updateDelivery =
                                            $conn->prepare(
                                                "UPDATE deliveries
                                                 SET
                                                    status = 'pending',
                                                    updated_at =
                                                        CURRENT_TIMESTAMP
                                                 WHERE id = ?"
                                            );


                                        if (!$updateDelivery) {

                                            throw new Exception(
                                                "Pending delivery update prepare failed."
                                            );
                                        }


                                        $updateDelivery->bind_param(
                                            "i",
                                            $deliveryId
                                        );


                                    /* =================================
                                       ASSIGNED
                                    ================================= */

                                    } elseif (
                                        $newDeliveryStatus ===
                                        'assigned'
                                    ) {

                                        $updateDelivery =
                                            $conn->prepare(
                                                "UPDATE deliveries
                                                 SET
                                                    status = 'assigned',
                                                    assigned_at =
                                                        COALESCE(
                                                            assigned_at,
                                                            CURRENT_TIMESTAMP
                                                        ),
                                                    updated_at =
                                                        CURRENT_TIMESTAMP
                                                 WHERE id = ?"
                                            );


                                        if (!$updateDelivery) {

                                            throw new Exception(
                                                "Assigned delivery update prepare failed."
                                            );
                                        }


                                        $updateDelivery->bind_param(
                                            "i",
                                            $deliveryId
                                        );


                                    /* =================================
                                       OUT FOR DELIVERY
                                    ================================= */

                                    } elseif (
                                        $newDeliveryStatus ===
                                        'out_for_delivery'
                                    ) {

                                        $updateDelivery =
                                            $conn->prepare(
                                                "UPDATE deliveries
                                                 SET
                                                    status = 'out_for_delivery',

                                                    assigned_at =
                                                        COALESCE(
                                                            assigned_at,
                                                            CURRENT_TIMESTAMP
                                                        ),

                                                    out_for_delivery_at =
                                                        COALESCE(
                                                            out_for_delivery_at,
                                                            CURRENT_TIMESTAMP
                                                        ),

                                                    updated_at =
                                                        CURRENT_TIMESTAMP

                                                 WHERE id = ?"
                                            );


                                        if (!$updateDelivery) {

                                            throw new Exception(
                                                "Out for delivery update prepare failed."
                                            );
                                        }


                                        $updateDelivery->bind_param(
                                            "i",
                                            $deliveryId
                                        );


                                    /* =================================
                                       DELIVERED
                                    ================================= */

                                    } elseif (
                                        $newDeliveryStatus ===
                                        'delivered'
                                    ) {

                                        $updateDelivery =
                                            $conn->prepare(
                                                "UPDATE deliveries
                                                 SET
                                                    status = 'delivered',

                                                    assigned_at =
                                                        COALESCE(
                                                            assigned_at,
                                                            CURRENT_TIMESTAMP
                                                        ),

                                                    out_for_delivery_at =
                                                        COALESCE(
                                                            out_for_delivery_at,
                                                            CURRENT_TIMESTAMP
                                                        ),

                                                    delivered_at =
                                                        COALESCE(
                                                            delivered_at,
                                                            CURRENT_TIMESTAMP
                                                        ),

                                                    updated_at =
                                                        CURRENT_TIMESTAMP

                                                 WHERE id = ?"
                                            );


                                        if (!$updateDelivery) {

                                            throw new Exception(
                                                "Delivered update prepare failed."
                                            );
                                        }


                                        $updateDelivery->bind_param(
                                            "i",
                                            $deliveryId
                                        );


                                    /* =================================
                                       CANCELLED
                                    ================================= */

                                    } elseif (
                                        $newDeliveryStatus ===
                                        'cancelled'
                                    ) {

                                        $updateDelivery =
                                            $conn->prepare(
                                                "UPDATE deliveries
                                                 SET
                                                    status = 'cancelled',
                                                    updated_at =
                                                        CURRENT_TIMESTAMP
                                                 WHERE id = ?"
                                            );


                                        if (!$updateDelivery) {

                                            throw new Exception(
                                                "Cancelled delivery update prepare failed."
                                            );
                                        }


                                        $updateDelivery->bind_param(
                                            "i",
                                            $deliveryId
                                        );


                                    /* =================================
                                       OTHER DELIVERY STATUS
                                    ================================= */

                                    } else {

                                        if (
                                            !in_array(
                                                $newDeliveryStatus,
                                                $allowedDeliveryStatuses,
                                                true
                                            )
                                        ) {

                                            throw new Exception(
                                                "Invalid delivery status."
                                            );
                                        }


                                        $updateDelivery =
                                            $conn->prepare(
                                                "UPDATE deliveries
                                                 SET
                                                    status = ?,
                                                    updated_at =
                                                        CURRENT_TIMESTAMP
                                                 WHERE id = ?"
                                            );


                                        if (!$updateDelivery) {

                                            throw new Exception(
                                                "Delivery update prepare failed."
                                            );
                                        }


                                        $updateDelivery->bind_param(
                                            "si",
                                            $newDeliveryStatus,
                                            $deliveryId
                                        );
                                    }


                                    /* =================================
                                       EXECUTE DELIVERY UPDATE
                                    ================================= */

                                    if (
                                        !$updateDelivery->execute()
                                    ) {

                                        throw new Exception(
                                            "Delivery update failed: " .
                                            $updateDelivery->error
                                        );
                                    }


                                    $updateDelivery->close();


                                } else {

                                    /* =================================
                                       4. CREATE DELIVERY RECORD
                                    ================================= */

                                    $assignedAt = null;

                                    $outForDeliveryAt = null;

                                    $deliveredAt = null;


                                    /* =================================
                                       TIMESTAMPS
                                    ================================= */

                                    if (
                                        $newDeliveryStatus ===
                                        'assigned'
                                    ) {

                                        $assignedAt =
                                            date(
                                                'Y-m-d H:i:s'
                                            );

                                    } elseif (
                                        $newDeliveryStatus ===
                                        'out_for_delivery'
                                    ) {

                                        $assignedAt =
                                            date(
                                                'Y-m-d H:i:s'
                                            );

                                        $outForDeliveryAt =
                                            date(
                                                'Y-m-d H:i:s'
                                            );

                                    } elseif (
                                        $newDeliveryStatus ===
                                        'delivered'
                                    ) {

                                        $assignedAt =
                                            date(
                                                'Y-m-d H:i:s'
                                            );

                                        $outForDeliveryAt =
                                            date(
                                                'Y-m-d H:i:s'
                                            );

                                        $deliveredAt =
                                            date(
                                                'Y-m-d H:i:s'
                                            );
                                    }


                                    /* =================================
                                       INSERT DELIVERY
                                    ================================= */

                                    $insertDelivery =
                                        $conn->prepare(
                                            "INSERT INTO deliveries
                                            (
                                                order_id,
                                                delivery_person_id,
                                                status,
                                                assigned_at,
                                                picked_up_at,
                                                out_for_delivery_at,
                                                delivered_at,
                                                delivery_otp,
                                                delivery_note,
                                                failure_reason,
                                                created_at,
                                                updated_at
                                            )
                                            VALUES
                                            (
                                                ?,
                                                NULL,
                                                ?,
                                                ?,
                                                NULL,
                                                ?,
                                                ?,
                                                NULL,
                                                NULL,
                                                NULL,
                                                CURRENT_TIMESTAMP,
                                                CURRENT_TIMESTAMP
                                            )"
                                        );


                                    if (!$insertDelivery) {

                                        throw new Exception(
                                            "Delivery creation prepare failed: " .
                                            $conn->error
                                        );
                                    }


                                    $insertDelivery->bind_param(
                                        "issss",
                                        $id,
                                        $newDeliveryStatus,
                                        $assignedAt,
                                        $outForDeliveryAt,
                                        $deliveredAt
                                    );


                                    if (
                                        !$insertDelivery->execute()
                                    ) {

                                        throw new Exception(
                                            "Delivery record could not be created: " .
                                            $insertDelivery->error
                                        );
                                    }


                                    $insertDelivery->close();
                                }


                                /* =================================
                                   5. COMMIT
                                ================================= */

                                $conn->commit();


                                $message =
                                    "Order and delivery status updated successfully.";


                                $action =
                                    'view';

                                $edit_id =
                                    $id;


                            } catch (Throwable $e) {

                                /* =================================
                                   ROLLBACK
                                ================================= */

                                $conn->rollback();


                                $error =
                                    $e->getMessage();


                                $action =
                                    'view';

                                $edit_id =
                                    $id;
                            }
                        }
                    }
                }
            }
        }
    }
}


/* =====================================================
   FETCH SINGLE ORDER
===================================================== */

if (
    $action === 'view' &&
    $edit_id > 0
) {

    /* =================================================
       FETCH ORDER
    ================================================= */

    $stmt = $conn->prepare(
        "SELECT *
         FROM orders
         WHERE id = ?
         LIMIT 1"
    );


    if ($stmt) {

        $stmt->bind_param(
            "i",
            $edit_id
        );

        $stmt->execute();

        $result =
            $stmt->get_result();


        if (
            $result &&
            $result->num_rows === 1
        ) {

            $order =
                $result->fetch_assoc();


            /* =========================================
               FETCH ORDER ITEMS
            ========================================= */

            $stmtItems =
                $conn->prepare(
                    "SELECT
                        id,
                        medicine_id,
                        medicine_name,
                        quantity,
                        unit_price,
                        total_price
                     FROM order_items
                     WHERE order_id = ?
                     ORDER BY id ASC"
                );


            if ($stmtItems) {

                $stmtItems->bind_param(
                    "i",
                    $edit_id
                );

                $stmtItems->execute();

                $orderItems =
                    $stmtItems->get_result();

                $stmtItems->close();

            } else {

                $error =
                    "Order items load nahi ho paye.";
            }


        } else {

            $error =
                "Order nahi mila.";

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
   FETCH ORDERS
===================================================== */

if ($action === 'list') {

    $conditions = [];
    $params = [];
    $types = "";


    /* =================================================
       SEARCH
    ================================================= */

    if ($search !== '') {

        $conditions[] =
            "(
                order_number LIKE ?
                OR customer_name LIKE ?
                OR customer_mobile LIKE ?
                OR city LIKE ?
                OR pincode LIKE ?
            )";

        $searchTerm =
            "%" . $search . "%";

        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;

        $types .= "sssss";
    }


    /* =================================================
       ORDER STATUS FILTER
    ================================================= */

    if (
        $orderStatus !== '' &&
        in_array(
            $orderStatus,
            $allowedOrderStatuses,
            true
        )
    ) {

        $conditions[] =
            "order_status = ?";

        $params[] =
            $orderStatus;

        $types .= "s";
    }


    /* =================================================
       PAYMENT STATUS FILTER
    ================================================= */

    if (
        $paymentStatus !== '' &&
        in_array(
            $paymentStatus,
            $allowedPaymentStatuses,
            true
        )
    ) {

        $conditions[] =
            "payment_status = ?";

        $params[] =
            $paymentStatus;

        $types .= "s";
    }


    /* =================================================
       BUILD QUERY
    ================================================= */

    $sql =
        "SELECT *
         FROM orders";


    if (!empty($conditions)) {

        $sql .=
            " WHERE " .
            implode(
                " AND ",
                $conditions
            );
    }


    $sql .=
        " ORDER BY id DESC";


    $stmt =
        $conn->prepare($sql);


    if ($stmt) {

        if (!empty($params)) {

            $stmt->bind_param(
                $types,
                ...$params
            );
        }


        $stmt->execute();

        $orders =
            $stmt->get_result();

    } else {

        $error =
            "Database error while loading orders.";
    }
}


/* =====================================================
   PAGE TITLE
===================================================== */

$pageTitle =
    $action === 'view'
        ? 'Order Details'
        : 'Orders';

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
    | Admin Panel
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
    padding: 24px 20px;
    border-bottom: 1px solid rgba(255,255,255,.12);
}

.brand-icon {
    width: 46px;
    height: 46px;
    background: rgba(255,255,255,.15);
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
    color: rgba(255,255,255,.7);
}

.sidebar-menu {
    padding: 18px 12px;
}

.menu-title {
    color: rgba(255,255,255,.5);
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 10px 12px;
}

.sidebar-menu a {
    display: flex;
    align-items: center;
    gap: 11px;
    color: rgba(255,255,255,.85);
    padding: 12px 13px;
    border-radius: 8px;
    margin-bottom: 4px;
    font-size: 13px;
    transition: .2s;
}

.sidebar-menu a:hover,
.sidebar-menu a.active {
    background: rgba(255,255,255,.15);
    color: #fff;
}

.menu-icon {
    width: 23px;
    text-align: center;
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
    border-bottom: 1px solid #e8edf2;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 30px;
    position: sticky;
    top: 0;
    z-index: 100;
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


/* =====================================================
   BUTTONS
===================================================== */

.btn {
    border: 0;
    border-radius: 7px;
    padding: 10px 16px;
    cursor: pointer;
    font-size: 12px;
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

.btn-secondary {
    background: #6c757d;
    color: #fff;
}

.btn-warning {
    background: #ffc107;
    color: #222;
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
   FILTER
===================================================== */

.filter-box {
    background: #fff;
    padding: 17px;
    border-radius: 10px;
    margin-bottom: 20px;
    border: 1px solid #e9edf1;
}

.filter-form {
    display: grid;
    grid-template-columns:
        minmax(220px, 1fr)
        180px
        180px
        auto
        auto;
    gap: 10px;
}

.form-control {
    width: 100%;
    min-height: 43px;
    border: 1px solid #d9dde2;
    border-radius: 7px;
    padding: 0 12px;
    outline: none;
    font-size: 12px;
    background: #fff;
    color: #333;
}

textarea.form-control {
    min-height: 100px;
    padding-top: 11px;
    resize: vertical;
}

.form-control:focus {
    border-color: #51b848;
    box-shadow:
        0 0 0 3px
        rgba(81,184,72,.1);
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
    min-width: 1100px;
}

th,
td {
    padding: 13px 14px;
    text-align: left;
    border-bottom: 1px solid #edf0f3;
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


/* =====================================================
   ORDER
===================================================== */

.order-number {
    font-weight: 700;
    color: #278c3c;
}

.customer-name {
    font-weight: 600;
    color: #333;
}

.customer-mobile {
    color: #888;
    font-size: 10px;
    margin-top: 4px;
}

.address {
    max-width: 240px;
    line-height: 1.5;
    color: #666;
    font-size: 11px;
}

.amount {
    color: #278c3c;
    font-weight: 700;
    white-space: nowrap;
}

.action-buttons {
    display: flex;
    align-items: center;
    gap: 5px;
}


/* =====================================================
   BADGES
===================================================== */

.badge {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
    white-space: nowrap;
}

.badge-success {
    background: #e4f7e7;
    color: #278139;
}

.badge-danger {
    background: #fde7e9;
    color: #b42b38;
}

.badge-warning {
    background: #fff4d6;
    color: #8a6900;
}

.badge-info {
    background: #e6f1ff;
    color: #1769aa;
}

.badge-secondary {
    background: #edf0f2;
    color: #667;
}


/* =====================================================
   DETAILS
===================================================== */

.details-card {
    background: #fff;
    border-radius: 11px;
    border: 1px solid #e7ebef;
    padding: 25px;
}

.details-grid {
    display: grid;
    grid-template-columns:
        repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.detail-box {
    border: 1px solid #edf0f3;
    border-radius: 9px;
    padding: 17px;
}

.detail-box.full {
    grid-column: 1 / -1;
}

.detail-title {
    color: #888;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: 7px;
}

.detail-value {
    color: #333;
    font-size: 13px;
    font-weight: 500;
    line-height: 1.6;
}


/* =====================================================
   PRICE TABLE
===================================================== */

.price-table {
    width: 100%;
    min-width: 0;
}

.price-table td {
    padding: 8px 0;
    border: 0;
}

.price-table td:last-child {
    text-align: right;
    font-weight: 600;
}

.total-row td {
    border-top: 1px solid #ddd;
    padding-top: 13px;
    font-size: 15px;
    color: #278c3c;
}


/* =====================================================
   UPDATE
===================================================== */

.update-card {
    background: #f9fafb;
    border: 1px solid #e8ecef;
    border-radius: 10px;
    padding: 20px;
}

.update-grid {
    display: grid;
    grid-template-columns:
        repeat(2, minmax(0, 1fr));
    gap: 15px;
}

.form-group label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 7px;
    color: #444;
}

.form-actions {
    margin-top: 18px;
    display: flex;
    gap: 10px;
}


/* =====================================================
   EMPTY
===================================================== */

.empty {
    padding: 60px 20px !important;
    text-align: center !important;
    color: #999;
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

@media (max-width: 1050px) {

    .filter-form {
        grid-template-columns: 1fr 1fr;
    }

    .filter-form .search-input {
        grid-column: 1 / -1;
    }

    .details-grid,
    .update-grid {
        grid-template-columns: 1fr;
    }

    .detail-box.full {
        grid-column: auto;
    }
}


@media (max-width: 800px) {

    .sidebar {
        transform: translateX(-100%);
        transition: .25s;
    }

    .sidebar.show {
        transform: translateX(0);
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
        padding: 18px;
    }

    .topbar-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }
}


@media (max-width: 600px) {

    .topbar-title {
        font-size: 17px;
    }

    .admin-user {
        display: none;
    }

    .page-heading {
        flex-direction: column;
        align-items: flex-start;
    }

    .filter-form {
        grid-template-columns: 1fr;
    }

    .filter-form .search-input {
        grid-column: auto;
    }

    .details-card {
        padding: 15px;
    }

    .content {
        padding: 15px;
    }

    .action-buttons {
        flex-direction: column;
        align-items: stretch;
    }

    .form-actions {
        flex-direction: column;
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


        <a
            href="orders.php"
            class="active"
        >

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
            Orders
        </div>

    </div>


    <div class="admin-user">

        Welcome,

        <strong>
            <?= htmlspecialchars(
                $_SESSION['name'] ?? 'Admin'
            ) ?>
        </strong>

    </div>

</header>


<div class="content">


<!-- =====================================================
     ALERTS
===================================================== -->

<?php if ($message !== ''): ?>

    <div class="alert alert-success">

        <?= htmlspecialchars($message) ?>

    </div>

<?php endif; ?>


<?php if ($error !== ''): ?>

    <div class="alert alert-danger">

        <?= htmlspecialchars($error) ?>

    </div>

<?php endif; ?>


<!-- =====================================================
     ORDER LIST
===================================================== -->

<?php if ($action === 'list'): ?>


<div class="page-heading">

    <h1>
        Order Management
    </h1>

</div>


<!-- FILTER -->

<div class="filter-box">

<form
    method="GET"
    class="filter-form"
>

    <input
        type="text"
        name="search"
        class="form-control search-input"
        placeholder="Search order number, customer, mobile, city..."
        value="<?= htmlspecialchars($search) ?>"
    >


    <select
        name="order_status"
        class="form-control"
    >

        <option value="">
            All Order Status
        </option>

        <?php foreach (
            $allowedOrderStatuses as $status
        ): ?>

            <option
                value="<?= htmlspecialchars($status) ?>"
                <?= $orderStatus === $status
                    ? 'selected'
                    : ''
                ?>
            >

                <?= htmlspecialchars(
                    ucwords(
                        str_replace(
                            '_',
                            ' ',
                            $status
                        )
                    )
                ) ?>

            </option>

        <?php endforeach; ?>

    </select>


    <select
        name="payment_status"
        class="form-control"
    >

        <option value="">
            All Payment Status
        </option>

        <?php foreach (
            $allowedPaymentStatuses as $status
        ): ?>

            <option
                value="<?= htmlspecialchars($status) ?>"
                <?= $paymentStatus === $status
                    ? 'selected'
                    : ''
                ?>
            >

                <?= htmlspecialchars(
                    ucfirst($status)
                ) ?>

            </option>

        <?php endforeach; ?>

    </select>


    <button
        type="submit"
        class="btn btn-primary"
    >
        Search
    </button>


    <?php if (
        $search !== '' ||
        $orderStatus !== '' ||
        $paymentStatus !== ''
    ): ?>

        <a
            href="orders.php"
            class="btn btn-secondary"
        >
            Clear
        </a>

    <?php endif; ?>

</form>

</div>


<!-- TABLE -->

<div class="card">

<div class="table-wrap">

<table>

<thead>

<tr>

    <th>
        Order
    </th>

    <th>
        Customer
    </th>

    <th>
        Address
    </th>

    <th>
        Amount
    </th>

    <th>
        Payment
    </th>

    <th>
        Order Status
    </th>

    <th>
        Date
    </th>

    <th>
        Action
    </th>

</tr>

</thead>


<tbody>


<?php if (
    $orders &&
    $orders->num_rows > 0
): ?>


<?php while (
    $row =
    $orders->fetch_assoc()
): ?>


<?php

$orderBadge =
    getOrderStatusBadge(
        $row['order_status']
    );

$paymentBadge =
    getPaymentStatusBadge(
        $row['payment_status']
    );

?>


<tr>


<td>

    <div class="order-number">

        #<?= htmlspecialchars(
            $row['order_number']
        ) ?>

    </div>

    <div
        style="
            font-size:10px;
            color:#999;
            margin-top:4px;
        "
    >

        ID:
        <?= (int)$row['id'] ?>

    </div>

</td>


<td>

    <div class="customer-name">

        <?= htmlspecialchars(
            $row['customer_name']
        ) ?>

    </div>

    <div class="customer-mobile">

        <?= htmlspecialchars(
            $row['customer_mobile']
        ) ?>

    </div>

</td>


<td>

    <div class="address">

        <?= nl2br(
            htmlspecialchars(
                $row['delivery_address']
            )
        ) ?>

        <br>

        <?= htmlspecialchars(
            $row['city']
        ) ?>,

        <?= htmlspecialchars(
            $row['state']
        ) ?>


        <?php if (
            !empty($row['pincode'])
        ): ?>

            -
            <?= htmlspecialchars(
                $row['pincode']
            ) ?>

        <?php endif; ?>

    </div>

</td>


<td>

    <div class="amount">

        <?= formatMoney(
            $row['total_amount']
        ) ?>

    </div>

    <div
        style="
            font-size:10px;
            color:#999;
            margin-top:4px;
        "
    >

        <?= htmlspecialchars(
            strtoupper(
                $row['payment_method']
            )
        ) ?>

    </div>

</td>


<td>

    <span
        class="
            badge
            badge-<?= htmlspecialchars(
                $paymentBadge[1]
            ) ?>
        "
    >

        <?= htmlspecialchars(
            $paymentBadge[0]
        ) ?>

    </span>

</td>


<td>

    <span
        class="
            badge
            badge-<?= htmlspecialchars(
                $orderBadge[1]
            ) ?>
        "
    >

        <?= htmlspecialchars(
            $orderBadge[0]
        ) ?>

    </span>

</td>


<td>

    <div
        style="
            font-size:11px;
            color:#555;
        "
    >

        <?= htmlspecialchars(
            date(
                'd M Y',
                strtotime(
                    $row['created_at']
                )
            )
        ) ?>

    </div>

    <div
        style="
            font-size:10px;
            color:#999;
            margin-top:4px;
        "
    >

        <?= htmlspecialchars(
            date(
                'h:i A',
                strtotime(
                    $row['created_at']
                )
            )
        ) ?>

    </div>

</td>


<td>

    <div class="action-buttons">

        <a
            href="orders.php?action=view&id=<?= (int)$row['id'] ?>"
            class="btn btn-primary"
        >
            View
        </a>

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

    📦

    <br><br>

    <?php if (
        $search !== '' ||
        $orderStatus !== '' ||
        $paymentStatus !== ''
    ): ?>

        No orders found
        matching your filters.

    <?php else: ?>

        No orders found.

    <?php endif; ?>

</td>

</tr>


<?php endif; ?>


</tbody>

</table>

</div>

</div>


<!-- =====================================================
     VIEW ORDER
===================================================== -->

<?php else: ?>


<?php if ($order): ?>


<?php

$orderBadge =
    getOrderStatusBadge(
        $order['order_status']
    );

$paymentBadge =
    getPaymentStatusBadge(
        $order['payment_status']
    );

?>


<div class="page-heading">

    <h1>

        Order #

        <?= htmlspecialchars(
            $order['order_number']
        ) ?>

    </h1>


    <a
        href="orders.php"
        class="btn btn-secondary"
    >
        ← Back to Orders
    </a>

</div>


<div class="details-card">


<div class="details-grid">


<!-- ORDER NUMBER -->

<div class="detail-box">

    <div class="detail-title">
        Order Number
    </div>

    <div
        class="detail-value"
        style="
            color:#278c3c;
            font-weight:700;
        "
    >

        #<?= htmlspecialchars(
            $order['order_number']
        ) ?>

    </div>

</div>


<!-- ORDER DATE -->

<div class="detail-box">

    <div class="detail-title">
        Order Date
    </div>

    <div class="detail-value">

        <?= htmlspecialchars(
            date(
                'd M Y, h:i A',
                strtotime(
                    $order['created_at']
                )
            )
        ) ?>

    </div>

</div>


<!-- CUSTOMER -->

<div class="detail-box">

    <div class="detail-title">
        Customer
    </div>

    <div class="detail-value">

        <?= htmlspecialchars(
            $order['customer_name']
        ) ?>

        <br>

        <span
            style="
                color:#888;
                font-size:11px;
            "
        >

            <?= htmlspecialchars(
                $order['customer_mobile']
            ) ?>

        </span>

    </div>

</div>


<!-- PAYMENT -->

<div class="detail-box">

    <div class="detail-title">
        Payment
    </div>

    <div class="detail-value">

        <?= htmlspecialchars(
            strtoupper(
                $order['payment_method']
            )
        ) ?>

        <br><br>

        <span
            class="
                badge
                badge-<?= htmlspecialchars(
                    $paymentBadge[1]
                ) ?>
            "
        >

            <?= htmlspecialchars(
                $paymentBadge[0]
            ) ?>

        </span>

    </div>

</div>


<!-- ORDER STATUS -->

<div class="detail-box">

    <div class="detail-title">
        Order Status
    </div>

    <div class="detail-value">

        <span
            class="
                badge
                badge-<?= htmlspecialchars(
                    $orderBadge[1]
                ) ?>
            "
        >

            <?= htmlspecialchars(
                $orderBadge[0]
            ) ?>

        </span>

    </div>

</div>


<!-- USER ID -->

<div class="detail-box">

    <div class="detail-title">
        User ID
    </div>

    <div class="detail-value">

        #<?= (int)$order['user_id'] ?>

    </div>

</div>


<!-- ADDRESS -->

<div class="detail-box full">

    <div class="detail-title">
        Delivery Address
    </div>

    <div class="detail-value">

        <?= nl2br(
            htmlspecialchars(
                $order['delivery_address']
            )
        ) ?>

        <br>

        <?= htmlspecialchars(
            $order['city']
        ) ?>,

        <?= htmlspecialchars(
            $order['state']
        ) ?>


        <?php if (
            !empty($order['pincode'])
        ): ?>

            -
            <?= htmlspecialchars(
                $order['pincode']
            ) ?>

        <?php endif; ?>

    </div>

</div>


<!-- CUSTOMER NOTE -->

<?php if (
    !empty(
        $order['customer_note']
    )
): ?>

<div class="detail-box full">

    <div class="detail-title">
        Customer Note
    </div>

    <div class="detail-value">

        <?= nl2br(
            htmlspecialchars(
                $order['customer_note']
            )
        ) ?>

    </div>

</div>

<?php endif; ?>


<!-- =================================================
     ORDER ITEMS
================================================= -->

<div class="detail-box full">

    <div class="detail-title">
        Ordered Medicines
    </div>


    <?php if (
        $orderItems &&
        $orderItems->num_rows > 0
    ): ?>

        <div
            style="
                width:100%;
                overflow-x:auto;
            "
        >

            <table
                style="
                    width:100%;
                    min-width:650px;
                    border-collapse:collapse;
                    margin-top:10px;
                "
            >

                <thead>

                    <tr>

                        <th
                            style="
                                padding:11px 8px;
                                text-align:left;
                                background:#f8f9fa;
                                color:#777;
                                font-size:10px;
                                border-bottom:1px solid #e5e8eb;
                            "
                        >
                            Medicine
                        </th>


                        <th
                            style="
                                padding:11px 8px;
                                text-align:center;
                                background:#f8f9fa;
                                color:#777;
                                font-size:10px;
                                border-bottom:1px solid #e5e8eb;
                            "
                        >
                            Qty
                        </th>


                        <th
                            style="
                                padding:11px 8px;
                                text-align:right;
                                background:#f8f9fa;
                                color:#777;
                                font-size:10px;
                                border-bottom:1px solid #e5e8eb;
                            "
                        >
                            Unit Price
                        </th>


                        <th
                            style="
                                padding:11px 8px;
                                text-align:right;
                                background:#f8f9fa;
                                color:#777;
                                font-size:10px;
                                border-bottom:1px solid #e5e8eb;
                            "
                        >
                            Total
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php while (
                    $item =
                    $orderItems->fetch_assoc()
                ): ?>

                    <tr>

                        <td
                            style="
                                padding:12px 8px;
                                border-bottom:1px solid #edf0f3;
                            "
                        >

                            <div
                                style="
                                    font-size:12px;
                                    font-weight:600;
                                    color:#333;
                                "
                            >

                                <?= htmlspecialchars(
                                    $item['medicine_name']
                                ) ?>

                            </div>


                            <div
                                style="
                                    font-size:10px;
                                    color:#999;
                                    margin-top:3px;
                                "
                            >

                                Medicine ID:
                                <?= (int)$item[
                                    'medicine_id'
                                ] ?>

                            </div>

                        </td>


                        <td
                            style="
                                padding:12px 8px;
                                text-align:center;
                                border-bottom:1px solid #edf0f3;
                                font-weight:600;
                            "
                        >

                            <?= (int)$item[
                                'quantity'
                            ] ?>

                        </td>


                        <td
                            style="
                                padding:12px 8px;
                                text-align:right;
                                border-bottom:1px solid #edf0f3;
                            "
                        >

                            <?= formatMoney(
                                $item['unit_price']
                            ) ?>

                        </td>


                        <td
                            style="
                                padding:12px 8px;
                                text-align:right;
                                border-bottom:1px solid #edf0f3;
                                font-weight:700;
                                color:#278c3c;
                            "
                        >

                            <?= formatMoney(
                                $item['total_price']
                            ) ?>

                        </td>

                    </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        </div>


    <?php else: ?>

        <div
            style="
                padding:25px 10px;
                text-align:center;
                color:#999;
                font-size:12px;
            "
        >

            💊 No medicines found for this order.

        </div>

    <?php endif; ?>

</div>


<!-- PRICE -->

<div class="detail-box full">

    <div class="detail-title">
        Order Amount
    </div>


    <table class="price-table">

        <tr>

            <td>
                Subtotal
            </td>

            <td>

                <?= formatMoney(
                    $order['subtotal']
                ) ?>

            </td>

        </tr>


        <tr>

            <td>
                Delivery Charge
            </td>

            <td>

                <?= formatMoney(
                    $order['delivery_charge']
                ) ?>

            </td>

        </tr>


        <tr>

            <td>
                Discount
            </td>

            <td style="color:#dc3545;">

                -
                <?= formatMoney(
                    $order['discount']
                ) ?>

            </td>

        </tr>


        <tr class="total-row">

            <td>
                Total Amount
            </td>

            <td>

                <?= formatMoney(
                    $order['total_amount']
                ) ?>

            </td>

        </tr>

    </table>

</div>


<!-- ADMIN NOTE -->

<?php if (
    !empty(
        $order['admin_note']
    )
): ?>

<div class="detail-box full">

    <div class="detail-title">
        Current Admin Note
    </div>

    <div class="detail-value">

        <?= nl2br(
            htmlspecialchars(
                $order['admin_note']
            )
        ) ?>

    </div>

</div>

<?php endif; ?>


</div>


<!-- =================================================
     UPDATE ORDER
================================================= -->

<div
    style="
        margin-top:25px;
        padding-top:25px;
        border-top:1px solid #edf0f3;
    "
>

    <h3
        style="
            margin:0 0 15px;
            font-size:16px;
        "
    >
        Update Order
    </h3>


    <div class="update-card">


        <form method="POST">


            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $csrfToken
                ) ?>"
            >


            <input
                type="hidden"
                name="action"
                value="update_order"
            >


            <input
                type="hidden"
                name="id"
                value="<?= (int)$order['id'] ?>"
            >


            <div class="update-grid">


                <!-- ORDER STATUS -->

                <div class="form-group">

                    <label>
                        Order Status
                    </label>


                    <select
                        name="order_status"
                        class="form-control"
                        required
                    >

                        <?php foreach (
                            $allowedOrderStatuses
                            as $status
                        ): ?>

                            <option
                                value="<?= htmlspecialchars($status) ?>"
                                <?= $order['order_status'] === $status
                                    ? 'selected'
                                    : ''
                                ?>
                            >

                                <?= htmlspecialchars(
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $status
                                        )
                                    )
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- PAYMENT STATUS -->

                <div class="form-group">

                    <label>
                        Payment Status
                    </label>


                    <select
                        name="payment_status"
                        class="form-control"
                        required
                    >

                        <?php foreach (
                            $allowedPaymentStatuses
                            as $status
                        ): ?>

                            <option
                                value="<?= htmlspecialchars($status) ?>"
                                <?= $order['payment_status'] === $status
                                    ? 'selected'
                                    : ''
                                ?>
                            >

                                <?= htmlspecialchars(
                                    ucfirst($status)
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- ADMIN NOTE -->

                <div
                    class="form-group"
                    style="
                        grid-column:1/-1;
                    "
                >

                    <label>
                        Admin Note
                    </label>


                    <textarea
                        name="admin_note"
                        class="form-control"
                        placeholder="Add internal note about this order..."
                    ><?= htmlspecialchars(
                        $order['admin_note'] ?? ''
                    ) ?></textarea>

                </div>


            </div>


            <div class="form-actions">


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Update Order
                </button>


                <a
                    href="orders.php"
                    class="btn btn-secondary"
                >
                    Cancel
                </a>


            </div>


        </form>


    </div>

</div>


</div>


<?php else: ?>


<div class="card">

    <div class="empty">

        📦

        <br><br>

        Order details unavailable.

        <br><br>

        <a
            href="orders.php"
            class="btn btn-secondary"
        >
            ← Back to Orders
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
        document.getElementById("sidebar");

    sidebar.classList.toggle("show");
}

</script>


</body>

</html>