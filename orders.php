<?php

session_start();

$page_title = "My Orders | Medicine Aapki Gaw Mein";

require_once "config/database.php";


// =====================================================
// LOGIN CHECK
// =====================================================

if (
    !isset($_SESSION['user_id']) ||
    (int) $_SESSION['user_id'] <= 0
) {
    $_SESSION['redirect_after_login'] = 'order.php';

    $_SESSION['login_required_message'] =
        "Apne orders dekhne ke liye pehle login karein.";

    header("Location: login.php");
    exit;
}


$user_id = (int) $_SESSION['user_id'];


// =====================================================
// GET USER ORDERS
// =====================================================

$orders = [];

$sql = "
    SELECT
        id,
        order_number,
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
        created_at
    FROM orders
    WHERE user_id = ?
    ORDER BY id DESC
";


$stmt = mysqli_prepare($conn, $sql);


if (!$stmt) {

    die(
        "Order query prepare failed: "
        . mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);


if (!mysqli_stmt_execute($stmt)) {

    die(
        "Order query failed: "
        . mysqli_stmt_error($stmt)
    );
}


$result = mysqli_stmt_get_result($stmt);


while ($row = mysqli_fetch_assoc($result)) {

    $orders[] = $row;
}


mysqli_stmt_close($stmt);


// =====================================================
// HELPER FUNCTIONS
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
            return 'status-delivered';

        case 'cancelled':
            return 'status-cancelled';

        case 'completed':
            return 'status-delivered';

        default:
            return 'status-pending';
    }
}


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


function safe($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


// =====================================================
// HEADER
// =====================================================

require_once "includes/header.php";

?>

<style>

/* =====================================================
   ORDERS PAGE
===================================================== */

.orders-page {
    padding: 50px 0;
}

.orders-title {
    margin-bottom: 30px;
}

.orders-title h2 {
    font-size: 30px;
    font-weight: 700;
    color: #222;
    margin-bottom: 8px;
}

.orders-title p {
    color: #777;
    margin-bottom: 0;
}


/* =====================================================
   ORDER CARD
===================================================== */

.order-card {
    background: #ffffff;
    border: 1px solid #e5e5e5;
    border-radius: 10px;
    margin-bottom: 25px;
    overflow: hidden;
    box-shadow: 0 3px 15px rgba(0,0,0,0.05);
}

.order-card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e5e5e5;
    padding: 18px 20px;
}

.order-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
}

.order-number {
    font-size: 17px;
    font-weight: 700;
    color: #222;
}

.order-date {
    color: #777;
    font-size: 13px;
    margin-top: 4px;
}


/* =====================================================
   STATUS
===================================================== */

.status-badge {
    display: inline-block;
    padding: 6px 12px;
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
   ORDER BODY
===================================================== */

.order-card-body {
    padding: 20px;
}

.order-info {
    margin-bottom: 15px;
}

.order-info-label {
    color: #777;
    font-size: 13px;
    margin-bottom: 4px;
}

.order-info-value {
    color: #222;
    font-weight: 600;
}


/* =====================================================
   PAYMENT
===================================================== */

.payment-badge {
    display: inline-block;
    padding: 5px 10px;
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
   TOTAL
===================================================== */

.order-total {
    font-size: 22px;
    font-weight: 700;
    color: #0d6efd;
}


/* =====================================================
   BUTTON
===================================================== */

.view-order-btn {
    background: #0d6efd;
    color: #ffffff !important;
    padding: 9px 18px;
    border-radius: 5px;
    display: inline-block;
    text-decoration: none !important;
    font-weight: 600;
    font-size: 14px;
}

.view-order-btn:hover {
    background: #0b5ed7;
    color: #ffffff !important;
}


/* =====================================================
   EMPTY ORDERS
===================================================== */

.empty-orders {
    text-align: center;
    background: #ffffff;
    border: 1px solid #e5e5e5;
    border-radius: 10px;
    padding: 60px 25px;
}

.empty-orders .empty-icon {
    font-size: 55px;
    margin-bottom: 15px;
}

.empty-orders h3 {
    color: #333;
    margin-bottom: 10px;
}

.empty-orders p {
    color: #777;
    margin-bottom: 25px;
}

.shop-btn {
    display: inline-block;
    background: #0d6efd;
    color: #ffffff !important;
    padding: 11px 22px;
    border-radius: 5px;
    text-decoration: none !important;
    font-weight: 600;
}


/* =====================================================
   MOBILE
===================================================== */

@media (max-width: 767px) {

    .orders-page {
        padding: 30px 0;
    }

    .orders-title h2 {
        font-size: 25px;
    }

    .order-header-row {
        align-items: flex-start;
        flex-direction: column;
    }

    .order-card-body {
        padding: 16px;
    }

    .order-total {
        font-size: 19px;
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
                        My Orders
                    </h1>

                    <p>
                        Aapke sabhi orders yahan dikhenge.
                    </p>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- =====================================================
     ORDERS SECTION
===================================================== -->

<div class="site-section orders-page">

    <div class="container">


        <!-- =================================================
             TITLE
        ================================================== -->

        <div class="orders-title">

            <h2>
                My Orders
            </h2>

            <p>
                Apne order ki details aur status check karein.
            </p>

        </div>


        <?php if (empty($orders)): ?>


            <!-- =============================================
                 NO ORDERS
            ============================================== -->

            <div class="empty-orders">

                <div class="empty-icon">
                    📦
                </div>

                <h3>
                    No Orders Found
                </h3>

                <p>
                    Aapne abhi tak koi order place nahi kiya hai.
                </p>

                <a
                    href="shop.php"
                    class="shop-btn"
                >
                    Start Shopping
                </a>

            </div>


        <?php else: ?>


            <!-- =============================================
                 ORDERS LIST
            ============================================== -->

            <?php foreach ($orders as $order): ?>


                <div class="order-card">


                    <!-- =====================================
                         ORDER HEADER
                    ====================================== -->

                    <div class="order-card-header">

                        <div class="order-header-row">


                            <div>

                                <div class="order-number">

                                    Order #
                                    <?= safe(
                                        $order['order_number']
                                    ) ?>

                                </div>

                                <div class="order-date">

                                    <?= date(
                                        'd M Y, h:i A',
                                        strtotime(
                                            $order['created_at']
                                        )
                                    ) ?>

                                </div>

                            </div>


                            <div>

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


                        </div>

                    </div>


                    <!-- =====================================
                         ORDER BODY
                    ====================================== -->

                    <div class="order-card-body">

                        <div class="row">


                            <!-- =================================
                                 CUSTOMER
                            ================================== -->

                            <div class="col-md-4 mb-3">

                                <div class="order-info">

                                    <div
                                        class="order-info-label"
                                    >
                                        Customer
                                    </div>

                                    <div
                                        class="order-info-value"
                                    >

                                        <?= safe(
                                            $order['customer_name']
                                        ) ?>

                                    </div>

                                </div>

                            </div>


                            <!-- =================================
                                 MOBILE
                            ================================== -->

                            <div class="col-md-4 mb-3">

                                <div class="order-info">

                                    <div
                                        class="order-info-label"
                                    >
                                        Mobile
                                    </div>

                                    <div
                                        class="order-info-value"
                                    >

                                        <?= safe(
                                            $order['customer_mobile']
                                        ) ?>

                                    </div>

                                </div>

                            </div>


                            <!-- =================================
                                 PAYMENT
                            ================================== -->

                            <div class="col-md-4 mb-3">

                                <div class="order-info">

                                    <div
                                        class="order-info-label"
                                    >
                                        Payment
                                    </div>

                                    <div>

                                        <strong>
                                            <?= strtoupper(
                                                safe(
                                                    $order[
                                                        'payment_method'
                                                    ]
                                                )
                                            ) ?>
                                        </strong>

                                        <br>

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


                        <hr>


                        <!-- =====================================
                             ADDRESS + TOTAL
                        ====================================== -->

                        <div class="row align-items-center">


                            <!-- ADDRESS -->

                            <div class="col-md-7 mb-3">

                                <div
                                    class="order-info"
                                >

                                    <div
                                        class="order-info-label"
                                    >
                                        Delivery Address
                                    </div>

                                    <div
                                        class="order-info-value"
                                    >

                                        <?= safe(
                                            $order[
                                                'delivery_address'
                                            ]
                                        ) ?>

                                        <br>

                                        <?= safe(
                                            $order['city']
                                        ) ?>

                                        <?php if (
                                            !empty(
                                                $order['state']
                                            )
                                        ): ?>

                                            ,
                                            <?= safe(
                                                $order['state']
                                            ) ?>

                                        <?php endif; ?>

                                        -

                                        <?= safe(
                                            $order['pincode']
                                        ) ?>

                                    </div>

                                </div>

                            </div>


                            <!-- TOTAL -->

                            <div
                                class="
                                    col-md-2
                                    mb-3
                                "
                            >

                                <div
                                    class="order-info-label"
                                >
                                    Total
                                </div>

                                <div
                                    class="order-total"
                                >

                                    ₹<?= number_format(
                                        (float)
                                        $order[
                                            'total_amount'
                                        ],
                                        2
                                    ) ?>

                                </div>

                            </div>


                            <!-- VIEW -->

                            <div
                                class="
                                    col-md-3
                                    text-md-right
                                    mb-3
                                "
                            >

                                <a
                                    href="order-details.php?id=<?= (int) $order['id'] ?>"
                                    class="view-order-btn"
                                >

                                    View Details

                                </a>

                            </div>


                        </div>

                    </div>

                </div>


            <?php endforeach; ?>


        <?php endif; ?>


    </div>

</div>


<?php

require_once "includes/footer.php";

?>
