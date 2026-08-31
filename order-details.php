<?php

session_start();

$page_title = "Order Details | Medicine Aapki Gaw Mein";

require_once "config/database.php";


// =====================================================
// LOGIN CHECK
// =====================================================

if (
    !isset($_SESSION['user_id']) ||
    (int) $_SESSION['user_id'] <= 0
) {

    $_SESSION['redirect_after_login'] =
        'order-details.php?id=' .
        (int) ($_GET['id'] ?? 0);

    $_SESSION['login_required_message'] =
        "Order details dekhne ke liye pehle login karein.";

    header("Location: login.php");
    exit;
}


$user_id = (int) $_SESSION['user_id'];


// =====================================================
// ORDER ID CHECK
// =====================================================

$order_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


if ($order_id <= 0) {

    header("Location: order.php");
    exit;
}


// =====================================================
// HELPER
// =====================================================

function safe($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


// =====================================================
// STATUS CLASS
// =====================================================

function orderStatusClass($status)
{
    switch (strtolower($status)) {

        case 'pending':
            return 'status-pending';

        case 'confirmed':
            return 'status-confirmed';

        case 'processing':
            return 'status-processing';

        case 'shipped':
            return 'status-shipped';

        case 'delivered':
        case 'completed':
            return 'status-delivered';

        case 'cancelled':
            return 'status-cancelled';

        default:
            return 'status-pending';
    }
}


// =====================================================
// PAYMENT STATUS CLASS
// =====================================================

function paymentStatusClass($status)
{
    switch (strtolower($status)) {

        case 'paid':
            return 'payment-paid';

        case 'failed':
            return 'payment-failed';

        case 'refunded':
            return 'payment-refunded';

        case 'pending':
        default:
            return 'payment-pending';
    }
}


// =====================================================
// GET ORDER
// =====================================================
//
// IMPORTANT:
// user_id condition lagaya gaya hai.
// Isse koi user doosre user ka order
// ID change karke nahi dekh sakta.
//

$order_sql = "
    SELECT
        id,
        order_number,
        user_id,
        prescription_id,
        subtotal,
        delivery_charge,
        discount,
        total_amount,
        payment_method,
        payment_status,
        order_status,
        customer_name,
        customer_mobile,
        delivery_address,
        city,
        state,
        pincode,
        customer_note,
        admin_note,
        created_at,
        updated_at
    FROM orders
    WHERE id = ?
    AND user_id = ?
    LIMIT 1
";


$order_stmt = mysqli_prepare(
    $conn,
    $order_sql
);


if (!$order_stmt) {

    die(
        "Order query prepare failed: "
        . mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $order_stmt,
    "ii",
    $order_id,
    $user_id
);


if (
    !mysqli_stmt_execute(
        $order_stmt
    )
) {

    die(
        "Order query failed: "
        . mysqli_stmt_error($order_stmt)
    );
}


$order_result =
    mysqli_stmt_get_result(
        $order_stmt
    );


$order =
    mysqli_fetch_assoc(
        $order_result
    );


mysqli_stmt_close(
    $order_stmt
);


// =====================================================
// ORDER NOT FOUND
// =====================================================

if (!$order) {

    http_response_code(404);

    require_once "includes/header.php";

    ?>

    <div class="site-section">

        <div class="container">

            <div class="not-found-box">

                <div class="not-found-icon">
                    📦
                </div>

                <h2>
                    Order Not Found
                </h2>

                <p>
                    Ye order exist nahi karta ya aapko
                    is order ko dekhne ki permission nahi hai.
                </p>

                <a
                    href="order.php"
                    class="btn btn-primary"
                >
                    ← My Orders
                </a>

            </div>

        </div>

    </div>

    <?php

    require_once "includes/footer.php";

    exit;
}


// =====================================================
// GET ORDER ITEMS
// =====================================================

$items = [];


$item_sql = "
    SELECT
        id,
        order_id,
        medicine_id,
        medicine_name,
        quantity,
        unit_price,
        total_price,
        created_at
    FROM order_items
    WHERE order_id = ?
    ORDER BY id ASC
";


$item_stmt = mysqli_prepare(
    $conn,
    $item_sql
);


if (!$item_stmt) {

    die(
        "Order items query prepare failed: "
        . mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $item_stmt,
    "i",
    $order_id
);


if (
    !mysqli_stmt_execute(
        $item_stmt
    )
) {

    die(
        "Order items query failed: "
        . mysqli_stmt_error($item_stmt)
    );
}


$item_result =
    mysqli_stmt_get_result(
        $item_stmt
    );


while (
    $item = mysqli_fetch_assoc(
        $item_result
    )
) {

    $items[] = $item;
}


mysqli_stmt_close(
    $item_stmt
);


// =====================================================
// HEADER
// =====================================================

require_once "includes/header.php";

?>

<style>

/* =====================================================
   PAGE
===================================================== */

.order-details-page {
    padding: 50px 0;
}


/* =====================================================
   TOP HEADER
===================================================== */

.order-top {
    margin-bottom: 30px;
}

.order-top h2 {
    margin: 0 0 8px;
    font-size: 30px;
    font-weight: 700;
    color: #222;
}

.order-top p {
    margin: 0;
    color: #777;
}


/* =====================================================
   CARD
===================================================== */

.details-card {
    background: #ffffff;
    border: 1px solid #e5e5e5;
    border-radius: 10px;
    margin-bottom: 25px;
    overflow: hidden;
    box-shadow: 0 3px 15px rgba(0,0,0,0.05);
}

.details-card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e5e5e5;
    padding: 18px 20px;
}

.details-card-header h3 {
    margin: 0;
    font-size: 19px;
    font-weight: 700;
    color: #222;
}

.details-card-body {
    padding: 20px;
}


/* =====================================================
   ORDER NUMBER
===================================================== */

.order-number {
    font-size: 20px;
    font-weight: 700;
    color: #222;
}

.order-date {
    margin-top: 5px;
    color: #777;
    font-size: 13px;
}


/* =====================================================
   STATUS
===================================================== */

.status-badge {
    display: inline-block;
    padding: 7px 13px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 700;
    text-transform: capitalize;
}

.status-pending {
    color: #856404;
    background: #fff3cd;
}

.status-confirmed {
    color: #055160;
    background: #cff4fc;
}

.status-processing {
    color: #084298;
    background: #cfe2ff;
}

.status-shipped {
    color: #41464b;
    background: #e2e3e5;
}

.status-delivered {
    color: #0f5132;
    background: #d1e7dd;
}

.status-cancelled {
    color: #842029;
    background: #f8d7da;
}


/* =====================================================
   PAYMENT
===================================================== */

.payment-badge {
    display: inline-block;
    padding: 6px 11px;
    border-radius: 5px;
    font-size: 12px;
    font-weight: 600;
}

.payment-paid {
    color: #0f5132;
    background: #d1e7dd;
}

.payment-pending {
    color: #856404;
    background: #fff3cd;
}

.payment-failed {
    color: #842029;
    background: #f8d7da;
}

.payment-refunded {
    color: #055160;
    background: #cff4fc;
}


/* =====================================================
   INFO
===================================================== */

.info-label {
    color: #777;
    font-size: 13px;
    margin-bottom: 5px;
}

.info-value {
    color: #222;
    font-weight: 600;
    line-height: 1.6;
}


/* =====================================================
   ITEMS
===================================================== */

.item-row {
    padding: 15px 0;
    border-bottom: 1px solid #eeeeee;
}

.item-row:last-child {
    border-bottom: 0;
}

.item-name {
    font-weight: 700;
    color: #222;
}

.item-meta {
    color: #777;
    font-size: 13px;
    margin-top: 4px;
}

.item-total {
    font-weight: 700;
    color: #222;
}


/* =====================================================
   TOTAL
===================================================== */

.summary-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 0;
    color: #555;
}

.summary-row strong {
    color: #222;
}

.grand-total {
    font-size: 22px;
    font-weight: 700;
    color: #0d6efd !important;
}


/* =====================================================
   BUTTONS
===================================================== */

.back-btn {
    display: inline-block;
    padding: 10px 18px;
    background: #0d6efd;
    color: #fff !important;
    border-radius: 5px;
    text-decoration: none !important;
    font-weight: 600;
}

.back-btn:hover {
    background: #0b5ed7;
}

.shop-btn {
    display: inline-block;
    padding: 10px 18px;
    background: #198754;
    color: #fff !important;
    border-radius: 5px;
    text-decoration: none !important;
    font-weight: 600;
}


/* =====================================================
   NOT FOUND
===================================================== */

.not-found-box {
    text-align: center;
    padding: 70px 20px;
    border: 1px solid #e5e5e5;
    border-radius: 10px;
    background: #fff;
}

.not-found-icon {
    font-size: 60px;
    margin-bottom: 15px;
}

.not-found-box h2 {
    margin-bottom: 10px;
}

.not-found-box p {
    color: #777;
    margin-bottom: 25px;
}


/* =====================================================
   MOBILE
===================================================== */

@media (max-width: 767px) {

    .order-details-page {
        padding: 30px 0;
    }

    .order-top h2 {
        font-size: 25px;
    }

    .details-card-body {
        padding: 16px;
    }

    .order-number {
        font-size: 18px;
    }

}

</style>


<!-- =====================================================
     PAGE HEADER
===================================================== -->

<div
    class="site-blocks-cover inner-page"
    style="
        background-image:
        url('assets/images/hero_1.jpg');
    "
>

    <div class="container">

        <div class="row">

            <div
                class="col-lg-7 mx-auto align-self-center"
            >

                <div
                    class="site-block-cover-content text-center"
                >

                    <h1>
                        Order Details
                    </h1>

                    <p>
                        Aapke order ki complete details.
                    </p>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- =====================================================
     ORDER DETAILS
===================================================== -->

<div class="site-section order-details-page">

    <div class="container">


        <!-- =================================================
             TOP
        ================================================== -->

        <div class="order-top">

            <h2>
                Order Details
            </h2>

            <p>
                Order #<?= safe(
                    $order['order_number']
                ) ?>
            </p>

        </div>


        <!-- =================================================
             ORDER STATUS CARD
        ================================================== -->

        <div class="details-card">

            <div class="details-card-body">

                <div class="row align-items-center">

                    <div class="col-md-6 mb-3 mb-md-0">

                        <div class="order-number">

                            #<?= safe(
                                $order['order_number']
                            ) ?>

                        </div>

                        <div class="order-date">

                            Placed on

                            <?= date(
                                'd M Y, h:i A',
                                strtotime(
                                    $order['created_at']
                                )
                            ) ?>

                        </div>

                    </div>


                    <div class="col-md-6 text-md-right">

                        <div class="mb-2">

                            <span
                                class="
                                    status-badge
                                    <?= orderStatusClass(
                                        $order['order_status']
                                    ) ?>
                                "
                            >

                                <?= safe(
                                    $order['order_status']
                                ) ?>

                            </span>

                        </div>

                        <div>

                            <strong>
                                Payment:
                            </strong>

                            <?= strtoupper(
                                safe(
                                    $order[
                                        'payment_method'
                                    ]
                                )
                            ) ?>

                            &nbsp;

                            <span
                                class="
                                    payment-badge
                                    <?= paymentStatusClass(
                                        $order[
                                            'payment_status'
                                        ]
                                    ) ?>
                                "
                            >

                                <?= safe(
                                    $order[
                                        'payment_status'
                                    ]
                                ) ?>

                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             CUSTOMER + DELIVERY
        ================================================== -->

        <div class="row">


            <!-- =============================================
                 CUSTOMER INFORMATION
            ============================================== -->

            <div class="col-lg-6">

                <div class="details-card">

                    <div class="details-card-header">

                        <h3>
                            Customer Information
                        </h3>

                    </div>

                    <div class="details-card-body">


                        <div class="mb-3">

                            <div class="info-label">
                                Full Name
                            </div>

                            <div class="info-value">

                                <?= safe(
                                    $order[
                                        'customer_name'
                                    ]
                                ) ?>

                            </div>

                        </div>


                        <div class="mb-3">

                            <div class="info-label">
                                Mobile Number
                            </div>

                            <div class="info-value">

                                <?= safe(
                                    $order[
                                        'customer_mobile'
                                    ]
                                ) ?>

                            </div>

                        </div>


                        <div>

                            <div class="info-label">
                                Payment Method
                            </div>

                            <div class="info-value">

                                <?= strtoupper(
                                    safe(
                                        $order[
                                            'payment_method'
                                        ]
                                    )
                                ) ?>

                            </div>

                        </div>


                    </div>

                </div>

            </div>


            <!-- =============================================
                 DELIVERY INFORMATION
            ============================================== -->

            <div class="col-lg-6">

                <div class="details-card">

                    <div class="details-card-header">

                        <h3>
                            Delivery Information
                        </h3>

                    </div>

                    <div class="details-card-body">

                        <div class="mb-3">

                            <div class="info-label">
                                Delivery Address
                            </div>

                            <div class="info-value">

                                <?= nl2br(
                                    safe(
                                        $order[
                                            'delivery_address'
                                        ]
                                    )
                                ) ?>

                            </div>

                        </div>


                        <div class="mb-3">

                            <div class="info-label">
                                City / Village
                            </div>

                            <div class="info-value">

                                <?= safe(
                                    $order['city']
                                ) ?>

                            </div>

                        </div>


                        <div class="mb-3">

                            <div class="info-label">
                                State
                            </div>

                            <div class="info-value">

                                <?= safe(
                                    $order['state']
                                ) ?>

                            </div>

                        </div>


                        <div>

                            <div class="info-label">
                                Pincode
                            </div>

                            <div class="info-value">

                                <?= safe(
                                    $order['pincode']
                                ) ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             ORDER ITEMS
        ================================================== -->

        <div class="details-card">

            <div class="details-card-header">

                <h3>
                    Ordered Medicines
                </h3>

            </div>

            <div class="details-card-body">


                <?php if (empty($items)): ?>

                    <div class="text-muted">

                        Order items nahi mile.

                    </div>

                <?php else: ?>


                    <?php foreach ($items as $item): ?>

                        <div class="item-row">

                            <div class="row align-items-center">


                                <!-- MEDICINE -->

                                <div class="col-md-6 mb-2 mb-md-0">

                                    <div class="item-name">

                                        <?= safe(
                                            $item[
                                                'medicine_name'
                                            ]
                                        ) ?>

                                    </div>

                                    <div class="item-meta">

                                        Qty:
                                        <?= (int)
                                            $item[
                                                'quantity'
                                            ] ?>

                                        ×

                                        ₹<?= number_format(
                                            (float)
                                            $item[
                                                'unit_price'
                                            ],
                                            2
                                        ) ?>

                                    </div>

                                </div>


                                <!-- QUANTITY -->

                                <div
                                    class="
                                        col-md-2
                                        mb-2
                                        mb-md-0
                                    "
                                >

                                    <div class="info-label">
                                        Quantity
                                    </div>

                                    <strong>

                                        <?= (int)
                                            $item[
                                                'quantity'
                                            ] ?>

                                    </strong>

                                </div>


                                <!-- PRICE -->

                                <div
                                    class="
                                        col-md-2
                                        mb-2
                                        mb-md-0
                                    "
                                >

                                    <div class="info-label">
                                        Unit Price
                                    </div>

                                    <strong>

                                        ₹<?= number_format(
                                            (float)
                                            $item[
                                                'unit_price'
                                            ],
                                            2
                                        ) ?>

                                    </strong>

                                </div>


                                <!-- TOTAL -->

                                <div
                                    class="
                                        col-md-2
                                        text-md-right
                                    "
                                >

                                    <div class="info-label">
                                        Total
                                    </div>

                                    <div class="item-total">

                                        ₹<?= number_format(
                                            (float)
                                            $item[
                                                'total_price'
                                            ],
                                            2
                                        ) ?>

                                    </div>

                                </div>


                            </div>

                        </div>

                    <?php endforeach; ?>


                <?php endif; ?>


            </div>

        </div>


        <!-- =================================================
             ORDER SUMMARY
        ================================================== -->

        <div class="row">


            <div class="col-lg-7">

                <?php if (
                    !empty(
                        $order['customer_note']
                    )
                ): ?>

                    <div class="details-card">

                        <div class="details-card-header">

                            <h3>
                                Customer Note
                            </h3>

                        </div>

                        <div class="details-card-body">

                            <?= nl2br(
                                safe(
                                    $order[
                                        'customer_note'
                                    ]
                                )
                            ) ?>

                        </div>

                    </div>

                <?php endif; ?>


                <?php if (
                    !empty(
                        $order['admin_note']
                    )
                ): ?>

                    <div class="details-card">

                        <div class="details-card-header">

                            <h3>
                                Note From Admin
                            </h3>

                        </div>

                        <div class="details-card-body">

                            <?= nl2br(
                                safe(
                                    $order[
                                        'admin_note'
                                    ]
                                )
                            ) ?>

                        </div>

                    </div>

                <?php endif; ?>

            </div>


            <!-- =============================================
                 TOTAL SUMMARY
            ============================================== -->

            <div class="col-lg-5">

                <div class="details-card">

                    <div class="details-card-header">

                        <h3>
                            Order Summary
                        </h3>

                    </div>

                    <div class="details-card-body">


                        <div class="summary-row">

                            <span>
                                Subtotal
                            </span>

                            <strong>

                                ₹<?= number_format(
                                    (float)
                                    $order[
                                        'subtotal'
                                    ],
                                    2
                                ) ?>

                            </strong>

                        </div>


                        <div class="summary-row">

                            <span>
                                Delivery
                            </span>

                            <strong>

                                <?php if (
                                    (float)
                                    $order[
                                        'delivery_charge'
                                    ] > 0
                                ): ?>

                                    ₹<?= number_format(
                                        (float)
                                        $order[
                                            'delivery_charge'
                                        ],
                                        2
                                    ) ?>

                                <?php else: ?>

                                    <span
                                        class="text-success"
                                    >
                                        Free
                                    </span>

                                <?php endif; ?>

                            </strong>

                        </div>


                        <?php if (
                            (float)
                            $order['discount'] > 0
                        ): ?>

                            <div class="summary-row">

                                <span>
                                    Discount
                                </span>

                                <strong
                                    class="text-success"
                                >

                                    -₹<?= number_format(
                                        (float)
                                        $order[
                                            'discount'
                                        ],
                                        2
                                    ) ?>

                                </strong>

                            </div>

                        <?php endif; ?>


                        <hr>


                        <div class="summary-row">

                            <strong>
                                Total Amount
                            </strong>

                            <strong
                                class="grand-total"
                            >

                                ₹<?= number_format(
                                    (float)
                                    $order[
                                        'total_amount'
                                    ],
                                    2
                                ) ?>

                            </strong>

                        </div>


                    </div>

                </div>

            </div>


        </div>


        <!-- =================================================
             BUTTONS
        ================================================== -->

        <div class="mt-2">

            <a
                href="order.php"
                class="back-btn"
            >
                ← My Orders
            </a>


            <a
                href="shop.php"
                class="shop-btn ml-2"
            >
                Continue Shopping
            </a>

        </div>


    </div>

</div>


<?php

require_once "includes/footer.php";

?>
