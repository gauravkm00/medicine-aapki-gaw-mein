<?php

session_start();

$page_title = "Checkout | Medicine Aapki Gaw Mein";

require_once "config/database.php";


// =====================================================
// HELPER
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
// CSRF TOKEN
// =====================================================

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));

}


// =====================================================
// SUCCESS MESSAGE DATA
// =====================================================

$success_order = null;


// =====================================================
// SHOW SUCCESS MESSAGE
// =====================================================

if (
    isset($_GET['success']) &&
    $_GET['success'] === '1' &&
    isset($_SESSION['order_success'])
) {

    $success_order =
        $_SESSION['order_success'];

    unset($_SESSION['order_success']);
}


// =====================================================
// LOGIN REQUIRED
// =====================================================

if ($success_order === null) {

    if (
        !isset($_SESSION['user_id']) ||
        (int)$_SESSION['user_id'] <= 0
    ) {

        $_SESSION['redirect_after_login'] =
            'checkout.php';

        $_SESSION['login_required_message'] =
            "Order place karne ke liye pehle login ya register karein.";

        header("Location: login.php");
        exit;
    }
}


// =====================================================
// USER
// =====================================================

$user_id =
    isset($_SESSION['user_id'])
        ? (int)$_SESSION['user_id']
        : 0;


// =====================================================
// VARIABLES
// =====================================================

$error = "";

$customer_name =
    $_SESSION['name'] ?? '';

$customer_mobile =
    $_SESSION['mobile'] ?? '';


// =====================================================
// SUCCESS PAGE
// =====================================================

if ($success_order !== null) {

    require_once "includes/header.php";

?>

<style>

/* =====================================================
   SUCCESS PAGE
===================================================== */

.success-page {
    padding: 80px 0;
    background:
        linear-gradient(
            135deg,
            #f4fbf6,
            #ffffff
        );
}

.success-card {
    position: relative;
    overflow: hidden;
    background: #fff;
    border: 1px solid #e8eeeb;
    border-radius: 25px;
    padding: 55px 40px;
    text-align: center;
    box-shadow:
        0 20px 55px rgba(21,72,48,.10);
}

.success-card::before {
    content: "";
    position: absolute;
    width: 230px;
    height: 230px;
    right: -90px;
    top: -100px;
    border-radius: 50%;
    background: rgba(21,148,71,.06);
}

.success-card::after {
    content: "";
    position: absolute;
    width: 180px;
    height: 180px;
    left: -80px;
    bottom: -90px;
    border-radius: 50%;
    background: rgba(21,148,71,.05);
}

.success-content {
    position: relative;
    z-index: 2;
}

.success-icon {
    width: 92px;
    height: 92px;
    margin: 0 auto 25px;
    border-radius: 50%;
    background: #e3f7e9;
    color: #159447;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 45px;
    font-weight: 900;
    box-shadow:
        0 10px 25px rgba(21,148,71,.12);
}

.success-card h2 {
    color: #172b25;
    font-size: 31px;
    font-weight: 850;
    margin-bottom: 15px;
}

.success-description {
    max-width: 600px;
    margin: 0 auto;
    color: #6c7a75;
    font-size: 14px;
    line-height: 1.8;
}

.order-number-box {
    max-width: 420px;
    margin: 30px auto 20px;
    padding: 20px;
    background: #f4fbf6;
    border: 1px dashed #159447;
    border-radius: 15px;
}

.order-number-label {
    display: block;
    margin-bottom: 7px;
    color: #7a8882;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.order-number {
    color: #087333;
    font-size: 23px;
    font-weight: 850;
}

.order-id {
    color: #6c7a75;
    font-size: 13px;
    margin-bottom: 28px;
}

.order-id strong {
    color: #172b25;
}

.success-buttons {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 12px;
}

.success-btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 24px;
    border-radius: 9px;
    background: #159447;
    color: #fff !important;
    font-size: 12px;
    font-weight: 800;
    text-decoration: none !important;
    transition: .25s ease;
}

.success-btn-primary:hover {
    background: #087333;
    transform: translateY(-2px);
    box-shadow:
        0 10px 25px rgba(21,148,71,.20);
}

.success-btn-outline {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 24px;
    border-radius: 9px;
    border: 1px solid #159447;
    background: #fff;
    color: #159447 !important;
    font-size: 12px;
    font-weight: 800;
    text-decoration: none !important;
    transition: .25s ease;
}

.success-btn-outline:hover {
    background: #effaf3;
    transform: translateY(-2px);
}

@media(max-width:576px) {

    .success-page {
        padding: 45px 0;
    }

    .success-card {
        padding: 40px 20px;
        border-radius: 20px;
    }

    .success-card h2 {
        font-size: 25px;
    }

    .success-buttons {
        flex-direction: column;
    }

    .success-btn-primary,
    .success-btn-outline {
        width: 100%;
    }

}

</style>


<!-- =====================================================
     SUCCESS HERO
===================================================== -->

<div
    class="site-blocks-cover inner-page"
    style="
        background-image:
        linear-gradient(
            90deg,
            rgba(4,35,21,.88),
            rgba(5,50,29,.55)
        ),
        url('assets/images/hero_1.jpg');
    "
>

    <div class="container">

        <div class="row">

            <div class="col-lg-7 mx-auto align-self-center">

                <div class="site-block-cover-content text-center">

                    <div
                        style="
                            display:inline-flex;
                            align-items:center;
                            gap:8px;
                            padding:8px 15px;
                            border-radius:50px;
                            background:rgba(255,255,255,.12);
                            border:1px solid rgba(255,255,255,.20);
                            color:#fff;
                            font-size:11px;
                            font-weight:700;
                            margin-bottom:15px;
                        "
                    >
                        ✓ Order Confirmed
                    </div>

                    <h1>
                        Order Successful
                    </h1>

                    <p>
                        Thank you for shopping with us.
                    </p>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- =====================================================
     SUCCESS
===================================================== -->

<div class="success-page">

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-lg-7 col-md-9">

                <div class="success-card">

                    <div class="success-content">

                        <div class="success-icon">
                            ✓
                        </div>


                        <h2>
                            Order Successfully Placed!
                        </h2>


                        <p class="success-description">

                            Aapka order successfully place ho gaya hai.
                            Hum aapke order ko jaldi process karenge
                            aur aapko delivery ke liye contact karenge.

                        </p>


                        <div class="order-number-box">

                            <span class="order-number-label">
                                Order Number
                            </span>

                            <div class="order-number">

                                <?= e(
                                    $success_order['order_number']
                                ) ?>

                            </div>

                        </div>


                        <div class="order-id">

                            <strong>
                                Order ID:
                            </strong>

                            #<?= (int)$success_order['order_id'] ?>

                        </div>


                        <div class="success-buttons">

                            <a
                                href="index.php"
                                class="success-btn-primary"
                            >
                                🛍 Continue Shopping
                            </a>


                            <a
                                href="orders.php"
                                class="success-btn-outline"
                            >
                                📦 My Orders
                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<?php

    require_once "includes/footer.php";

    exit;
}


// =====================================================
// CART CHECK
// =====================================================

if (
    !isset($_SESSION['cart']) ||
    empty($_SESSION['cart'])
) {

    header("Location: cart.php");
    exit;
}


// =====================================================
// CART VARIABLES
// =====================================================

$cart_items = [];

$subtotal = 0.00;

$total_items = 0;


// =====================================================
// MEDICINE IDS
// =====================================================

$medicine_ids =
    array_keys($_SESSION['cart']);

$medicine_ids =
    array_map('intval', $medicine_ids);

$medicine_ids =
    array_filter(
        $medicine_ids,
        function ($id) {
            return $id > 0;
        }
    );


if (empty($medicine_ids)) {

    $_SESSION['cart'] = [];

    header("Location: cart.php");
    exit;
}


$id_list =
    implode(',', $medicine_ids);


// =====================================================
// GET MEDICINES
// =====================================================

$sql = "
    SELECT
        id,
        name,
        generic_name,
        manufacturer,
        category,
        image,
        mrp,
        selling_price,
        stock_quantity,
        prescription_required
    FROM medicines
    WHERE id IN ($id_list)
    AND status = 1
    ORDER BY id DESC
";


$result =
    mysqli_query(
        $conn,
        $sql
    );


if (!$result) {

    die(
        "Medicine query failed: "
        . mysqli_error($conn)
    );
}


// =====================================================
// BUILD CART
// =====================================================

while (
    $medicine =
    mysqli_fetch_assoc($result)
) {

    $medicine_id =
        (int)$medicine['id'];


    $quantity =
        isset(
            $_SESSION['cart'][$medicine_id]
        )
            ? (int)$_SESSION['cart'][$medicine_id]
            : 0;


    if ($quantity <= 0) {
        continue;
    }


    $stock =
        (int)$medicine['stock_quantity'];


    if ($stock <= 0) {

        unset(
            $_SESSION['cart'][$medicine_id]
        );

        continue;
    }


    if ($quantity > $stock) {

        $quantity = $stock;

        $_SESSION['cart'][$medicine_id] =
            $quantity;
    }


    $price =
        (float)$medicine['selling_price'];


    $item_total =
        $price * $quantity;


    $medicine['cart_quantity'] =
        $quantity;

    $medicine['item_total'] =
        $item_total;


    $cart_items[] =
        $medicine;


    $subtotal +=
        $item_total;


    $total_items +=
        $quantity;
}


// =====================================================
// EMPTY CART
// =====================================================

if (empty($cart_items)) {

    $_SESSION['cart'] = [];

    header("Location: cart.php");
    exit;
}


// =====================================================
// TOTALS
// =====================================================

$delivery_charge = 0.00;

$discount = 0.00;

$total_amount =
    $subtotal
    + $delivery_charge
    - $discount;


// =====================================================
// PLACE ORDER
// =====================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    // =================================================
    // CSRF VALIDATION
    // =================================================

    $submitted_token =
        $_POST['csrf_token'] ?? '';


    if (
        !hash_equals(
            $_SESSION['csrf_token'],
            $submitted_token
        )
    ) {

        $error =
            "Invalid request. Please page refresh karke dobara try karein.";

    } else {

        $customer_name =
            trim(
                $_POST['customer_name'] ?? ''
            );


        $customer_mobile =
            trim(
                $_POST['customer_mobile'] ?? ''
            );


        $address =
            trim(
                $_POST['address'] ?? ''
            );


        $city =
            trim(
                $_POST['city'] ?? ''
            );


        $state =
            trim(
                $_POST['state'] ?? ''
            );


        $pincode =
            trim(
                $_POST['pincode'] ?? ''
            );


        $payment_method =
            trim(
                $_POST['payment_method'] ?? 'cod'
            );


        // =================================================
        // VALIDATION
        // =================================================

        if (
            $customer_name === '' ||
            $customer_mobile === '' ||
            $address === '' ||
            $city === '' ||
            $pincode === ''
        ) {

            $error =
                "Please sabhi required fields fill karein.";

        }


        elseif (
            !preg_match(
                '/^[0-9]{10}$/',
                $customer_mobile
            )
        ) {

            $error =
                "Please valid 10-digit mobile number enter karein.";

        }


        elseif (
            !preg_match(
                '/^[0-9]{6}$/',
                $pincode
            )
        ) {

            $error =
                "Please valid 6-digit pincode enter karein.";

        }


        elseif (
            !in_array(
                $payment_method,
                ['cod'],
                true
            )
        ) {

            $error =
                "Currently only Cash on Delivery available hai.";

        }


        else {

            $payment_status =
                'pending';

            $order_status =
                'pending';


            // =================================================
            // ORDER NUMBER
            // =================================================

            try {

                $order_number =
                    'MAGM-'
                    . date('YmdHis')
                    . '-'
                    . random_int(
                        1000,
                        9999
                    );

            } catch (Exception $e) {

                $order_number =
                    'MAGM-'
                    . date('YmdHis')
                    . '-'
                    . mt_rand(
                        1000,
                        9999
                    );
            }


            // =================================================
            // TRANSACTION
            // =================================================

            mysqli_begin_transaction($conn);


            try {

                // =============================================
                // CREATE ORDER
                // =============================================

                $order_sql = "
                    INSERT INTO orders
                    (
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
                        pincode
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        NULL,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ";


                $order_stmt =
                    mysqli_prepare(
                        $conn,
                        $order_sql
                    );


                if (!$order_stmt) {

                    throw new Exception(
                        "Order query prepare failed: "
                        . mysqli_error($conn)
                    );
                }


                mysqli_stmt_bind_param(
                    $order_stmt,
                    "siddddsssssssss",
                    $order_number,
                    $user_id,
                    $subtotal,
                    $delivery_charge,
                    $discount,
                    $total_amount,
                    $payment_method,
                    $payment_status,
                    $order_status,
                    $customer_name,
                    $customer_mobile,
                    $address,
                    $city,
                    $state,
                    $pincode
                );


                if (
                    !mysqli_stmt_execute(
                        $order_stmt
                    )
                ) {

                    throw new Exception(
                        "Order create failed: "
                        . mysqli_stmt_error(
                            $order_stmt
                        )
                    );
                }


                $order_id =
                    mysqli_insert_id($conn);


                mysqli_stmt_close(
                    $order_stmt
                );


                if ($order_id <= 0) {

                    throw new Exception(
                        "Order ID generate nahi hua."
                    );
                }


                // =============================================
                // ORDER ITEMS
                // =============================================

                foreach ($cart_items as $item) {

                    $medicine_id =
                        (int)$item['id'];


                    $medicine_name =
                        trim(
                            $item['name']
                        );


                    $quantity =
                        (int)$item['cart_quantity'];


                    $unit_price =
                        (float)$item['selling_price'];


                    $total_price =
                        $unit_price * $quantity;


                    // =========================================
                    // FINAL STOCK LOCK
                    // =========================================

                    $stock_sql = "
                        SELECT
                            id,
                            name,
                            stock_quantity,
                            selling_price
                        FROM medicines
                        WHERE id = ?
                        AND status = 1
                        FOR UPDATE
                    ";


                    $stock_stmt =
                        mysqli_prepare(
                            $conn,
                            $stock_sql
                        );


                    if (!$stock_stmt) {

                        throw new Exception(
                            "Stock query prepare failed: "
                            . mysqli_error($conn)
                        );
                    }


                    mysqli_stmt_bind_param(
                        $stock_stmt,
                        "i",
                        $medicine_id
                    );


                    if (
                        !mysqli_stmt_execute(
                            $stock_stmt
                        )
                    ) {

                        throw new Exception(
                            "Stock check failed: "
                            . mysqli_stmt_error(
                                $stock_stmt
                            )
                        );
                    }


                    $stock_result =
                        mysqli_stmt_get_result(
                            $stock_stmt
                        );


                    $stock_row =
                        mysqli_fetch_assoc(
                            $stock_result
                        );


                    mysqli_stmt_close(
                        $stock_stmt
                    );


                    if (!$stock_row) {

                        throw new Exception(
                            "Medicine unavailable: "
                            . $medicine_name
                        );
                    }


                    $current_stock =
                        (int)$stock_row['stock_quantity'];


                    if (
                        $current_stock < $quantity
                    ) {

                        throw new Exception(
                            $medicine_name
                            . " ka stock available nahi hai. Please cart update karein."
                        );
                    }


                    // =========================================
                    // INSERT ORDER ITEM
                    // =========================================

                    $item_sql = "
                        INSERT INTO order_items
                        (
                            order_id,
                            medicine_id,
                            medicine_name,
                            quantity,
                            unit_price,
                            total_price
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?
                        )
                    ";


                    $item_stmt =
                        mysqli_prepare(
                            $conn,
                            $item_sql
                        );


                    if (!$item_stmt) {

                        throw new Exception(
                            "Order item query prepare failed: "
                            . mysqli_error($conn)
                        );
                    }


                    mysqli_stmt_bind_param(
                        $item_stmt,
                        "iisidd",
                        $order_id,
                        $medicine_id,
                        $medicine_name,
                        $quantity,
                        $unit_price,
                        $total_price
                    );


                    if (
                        !mysqli_stmt_execute(
                            $item_stmt
                        )
                    ) {

                        throw new Exception(
                            "Order item create failed: "
                            . mysqli_stmt_error(
                                $item_stmt
                            )
                        );
                    }


                    mysqli_stmt_close(
                        $item_stmt
                    );


                    // =========================================
                    // REDUCE STOCK
                    // =========================================

                    $update_stock_sql = "
                        UPDATE medicines
                        SET stock_quantity =
                            stock_quantity - ?
                        WHERE id = ?
                        AND status = 1
                        AND stock_quantity >= ?
                    ";


                    $stock_update_stmt =
                        mysqli_prepare(
                            $conn,
                            $update_stock_sql
                        );


                    if (!$stock_update_stmt) {

                        throw new Exception(
                            "Stock update prepare failed: "
                            . mysqli_error($conn)
                        );
                    }


                    mysqli_stmt_bind_param(
                        $stock_update_stmt,
                        "iii",
                        $quantity,
                        $medicine_id,
                        $quantity
                    );


                    if (
                        !mysqli_stmt_execute(
                            $stock_update_stmt
                        )
                    ) {

                        throw new Exception(
                            "Stock update failed: "
                            . mysqli_stmt_error(
                                $stock_update_stmt
                            )
                        );
                    }


                    if (
                        mysqli_stmt_affected_rows(
                            $stock_update_stmt
                        ) !== 1
                    ) {

                        mysqli_stmt_close(
                            $stock_update_stmt
                        );

                        throw new Exception(
                            "Insufficient stock for "
                            . $medicine_name
                        );
                    }


                    mysqli_stmt_close(
                        $stock_update_stmt
                    );

                }


               // =============================================
// CREATE DELIVERY RECORD
// =============================================
// Delivery boy yahan assign nahi hoga.
// Admin orders.php se delivery boy assign karega.

$delivery_sql = "
    INSERT INTO deliveries
    (
        order_id,
        status
    )
    VALUES
    (
        ?,
        'pending'
    )
";


$delivery_stmt =
    mysqli_prepare(
        $conn,
        $delivery_sql
    );


if (!$delivery_stmt) {

    throw new Exception(
        "Delivery query prepare failed: "
        . mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $delivery_stmt,
    "i",
    $order_id
);


if (
    !mysqli_stmt_execute(
        $delivery_stmt
    )
) {

    throw new Exception(
        "Delivery create failed: "
        . mysqli_stmt_error(
            $delivery_stmt
        )
    );
}


if (
    mysqli_stmt_affected_rows(
        $delivery_stmt
    ) !== 1
) {

    mysqli_stmt_close(
        $delivery_stmt
    );

    throw new Exception(
        "Delivery record create nahi hua."
    );
}


mysqli_stmt_close(
    $delivery_stmt
);


// =============================================
// COMMIT
// =============================================

mysqli_commit($conn);


                // =============================================
                // CLEAR CART
                // =============================================

                $_SESSION['cart'] = [];


                // =============================================
                // SUCCESS
                // =============================================

                $_SESSION['order_success'] = [

                    'order_id' =>
                        $order_id,

                    'order_number' =>
                        $order_number

                ];


                // Regenerate token after successful order

                $_SESSION['csrf_token'] =
                    bin2hex(random_bytes(32));


                header(
                    "Location: checkout.php?success=1"
                );

                exit;


            } catch (Exception $e) {

                mysqli_rollback($conn);

                $error =
                    $e->getMessage();

            }

        }

    }

}


// =====================================================
// HEADER
// =====================================================

require_once "includes/header.php";

?>

<style>

/* =====================================================
   ROOT
===================================================== */

:root {

    --pharmacy-green: #159447;

    --pharmacy-dark: #087333;

    --pharmacy-light: #effaf3;

    --pharmacy-blue: #1877d2;

    --dark: #172b25;

    --muted: #6c7a75;

    --border: #e8eeeb;

    --white: #ffffff;

}


/* =====================================================
   CHECKOUT PAGE
===================================================== */

.checkout-page {

    padding: 70px 0 85px;

    background:
        linear-gradient(
            135deg,
            #f4fbf6,
            #ffffff
        );

}


/* =====================================================
   ALERT
===================================================== */

.checkout-alert {

    display: flex;

    align-items: flex-start;

    gap: 12px;

    margin-bottom: 25px;

    padding: 16px 18px;

    border-radius: 14px;

    background: #fff5f5;

    border: 1px solid #ffd8d8;

    color: #a61b1b;

    font-size: 13px;

    line-height: 1.6;

}

.checkout-alert-icon {

    width: 32px;

    height: 32px;

    min-width: 32px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background: #ffe2e2;

}


/* =====================================================
   CARD
===================================================== */

.checkout-card {

    height: 100%;

    background: #fff;

    border: 1px solid var(--border);

    border-radius: 20px;

    box-shadow:
        0 15px 45px rgba(21,72,48,.07);

    overflow: hidden;

}


/* =====================================================
   CARD BODY
===================================================== */

.checkout-card-body {

    padding: 32px;

}


/* =====================================================
   SECTION TITLE
===================================================== */

.checkout-title {

    display: flex;

    align-items: center;

    gap: 13px;

    margin-bottom: 25px;

}


.checkout-title-icon {

    width: 48px;

    height: 48px;

    min-width: 48px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 14px;

    background: var(--pharmacy-light);

    color: var(--pharmacy-green);

    font-size: 21px;

}


.checkout-title h3 {

    margin: 0;

    color: var(--dark);

    font-size: 21px;

    font-weight: 800;

}


.checkout-title p {

    margin: 4px 0 0;

    color: #8a9691;

    font-size: 11px;

}


/* =====================================================
   USER INFO
===================================================== */

.user-info-box {

    display: flex;

    align-items: center;

    gap: 12px;

    margin-bottom: 30px;

    padding: 15px 17px;

    border-radius: 13px;

    background: #f4fbf6;

    border: 1px solid #dcefe2;

}


.user-info-icon {

    width: 40px;

    height: 40px;

    min-width: 40px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background: #dff5e6;

    color: var(--pharmacy-green);

}


.user-info-content small {

    display: block;

    color: #89958f;

    font-size: 10px;

    margin-bottom: 2px;

}


.user-info-content strong {

    color: var(--pharmacy-dark);

    font-size: 13px;

}


/* =====================================================
   FORM
===================================================== */

.checkout-form-group {

    margin-bottom: 20px;

}


.checkout-form-group label {

    display: block;

    margin-bottom: 8px;

    color: #293832;

    font-size: 12px;

    font-weight: 800;

}


.checkout-form-control {

    width: 100%;

    min-height: 49px;

    padding: 11px 14px;

    border: 1px solid #dfe8e2;

    border-radius: 11px;

    outline: none;

    background: #fbfdfc;

    color: #202d27;

    font-size: 13px;

    transition: .2s ease;

}


.checkout-form-control:hover {

    border-color: #c9ddd0;

}


.checkout-form-control:focus {

    background: #fff;

    border-color: var(--pharmacy-green);

    box-shadow:
        0 0 0 4px rgba(21,148,71,.08);

}


textarea.checkout-form-control {

    min-height: 115px;

    resize: vertical;

}


/* =====================================================
   PAYMENT
===================================================== */

.payment-option {

    position: relative;

    margin-bottom: 12px;

}


.payment-option input {

    position: absolute;

    opacity: 0;

    pointer-events: none;

}


.payment-label {

    display: block;

    margin: 0;

    padding: 16px;

    border: 1px solid #dfe8e2;

    border-radius: 14px;

    background: #fff;

    cursor: pointer;

    transition: .25s ease;

}


.payment-label:hover {

    border-color: #b9d7c3;

    background: #fbfefc;

}


.payment-option input:checked + .payment-label {

    border-color: var(--pharmacy-green);

    background: #f4fbf6;

    box-shadow:
        0 0 0 2px rgba(21,148,71,.05);

}


.payment-option input:disabled + .payment-label {

    opacity: .58;

    cursor: not-allowed;

}


.payment-content {

    display: flex;

    align-items: center;

    gap: 14px;

}


.payment-icon {

    width: 45px;

    height: 45px;

    min-width: 45px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 12px;

    background: var(--pharmacy-light);

    font-size: 20px;

}


.payment-text {

    flex: 1;

}


.payment-text strong {

    display: block;

    color: var(--dark);

    font-size: 13px;

    font-weight: 800;

}


.payment-text small {

    display: block;

    margin-top: 4px;

    color: #89958f;

    font-size: 10px;

    line-height: 1.5;

}


.payment-check {

    width: 21px;

    height: 21px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    border: 1px solid #ccd9d0;

    color: transparent;

    font-size: 11px;

}


.payment-option input:checked
+ .payment-label
.payment-check {

    background: var(--pharmacy-green);

    border-color: var(--pharmacy-green);

    color: #fff;

}


.coming-soon {

    display: inline-block;

    margin-left: 5px;

    padding: 3px 7px;

    border-radius: 20px;

    background: #fff2d8;

    color: #a76700;

    font-size: 8px;

    font-weight: 800;

    text-transform: uppercase;

}


/* =====================================================
   ORDER SUMMARY
===================================================== */

.order-summary {

    position: sticky;

    top: 20px;

}


/* =====================================================
   ORDER ITEMS
===================================================== */

.order-item {

    display: flex;

    align-items: center;

    gap: 13px;

    padding: 14px 0;

    border-bottom: 1px solid #edf1ee;

}


.order-item-image {

    width: 60px;

    height: 60px;

    min-width: 60px;

    display: flex;

    align-items: center;

    justify-content: center;

    overflow: hidden;

    border-radius: 12px;

    background:
        linear-gradient(
            145deg,
            #f8fcf9,
            #edf8f1
        );

}


.order-item-image img {

    width: 100%;

    height: 100%;

    object-fit: contain;

    padding: 7px;

}


.order-item-info {

    flex: 1;

    min-width: 0;

}


.order-item-name {

    color: var(--dark);

    font-size: 12px;

    font-weight: 800;

    line-height: 1.4;

}


.order-item-meta {

    margin-top: 4px;

    color: #8a9691;

    font-size: 10px;

}


.order-item-price {

    color: var(--pharmacy-dark);

    font-size: 13px;

    font-weight: 800;

    white-space: nowrap;

}


/* =====================================================
   SUMMARY
===================================================== */

.summary-box {

    margin-top: 23px;

    padding-top: 20px;

    border-top: 1px solid #e9efeb;

}


.summary-row {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 13px;

    color: #6c7a75;

    font-size: 12px;

}


.summary-row strong {

    color: #25332d;

    font-size: 12px;

}


.summary-total {

    margin-top: 18px;

    padding-top: 18px;

    border-top: 1px solid #e4ebe6;

}


.summary-total span {

    color: var(--dark);

    font-size: 14px;

    font-weight: 800;

}


.summary-total strong {

    color: var(--pharmacy-green);

    font-size: 24px;

    font-weight: 900;

}


/* =====================================================
   ORDER BUTTON
===================================================== */

.place-order-btn {

    width: 100%;

    min-height: 54px;

    margin-top: 10px;

    border: 0;

    border-radius: 10px;

    background:
        linear-gradient(
            135deg,
            #159447,
            #087333
        );

    color: #fff;

    font-size: 13px;

    font-weight: 800;

    box-shadow:
        0 10px 25px rgba(21,148,71,.20);

    transition: .25s ease;

}


.place-order-btn:hover {

    transform: translateY(-2px);

    box-shadow:
        0 14px 30px rgba(21,148,71,.25);

}


.place-order-btn:disabled {

    opacity: .7;

    cursor: not-allowed;

    transform: none;

}


/* =====================================================
   BACK CART
===================================================== */

.back-cart {

    display: block;

    margin-top: 14px;

    text-align: center;

    color: var(--pharmacy-green) !important;

    font-size: 11px;

    font-weight: 800;

    text-decoration: none !important;

}


.back-cart:hover {

    color: var(--pharmacy-dark) !important;

}


/* =====================================================
   SECURITY
===================================================== */

.checkout-security {

    display: flex;

    gap: 9px;

    margin-top: 18px;

    padding: 13px;

    border-radius: 11px;

    background: #f7faf8;

    color: #75827c;

    font-size: 10px;

    line-height: 1.7;

}


.checkout-security-icon {

    color: var(--pharmacy-green);

    font-size: 14px;

}


/* =====================================================
   TRUST BOX
===================================================== */

.checkout-trust {

    display: flex;

    justify-content: center;

    flex-wrap: wrap;

    gap: 15px;

    margin-top: 15px;

    padding: 15px;

    border: 1px solid var(--border);

    border-radius: 13px;

    background: #fff;

}


.checkout-trust-item {

    display: flex;

    align-items: center;

    gap: 5px;

    color: #7b8982;

    font-size: 9px;

    font-weight: 700;

}


.checkout-trust-item span {

    color: var(--pharmacy-green);

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width:991px) {

    .order-summary {

        position: static;

    }

}


@media(max-width:767px) {

    .checkout-page {

        padding: 45px 0 60px;

    }

    .checkout-card-body {

        padding: 23px;

    }

    .checkout-title h3 {

        font-size: 18px;

    }

}


@media(max-width:480px) {

    .checkout-page {

        padding: 30px 0 45px;

    }

    .checkout-card-body {

        padding: 18px;

    }

    .checkout-title {

        gap: 10px;

    }

    .checkout-title-icon {

        width: 42px;

        height: 42px;

        min-width: 42px;

        font-size: 18px;

    }

    .checkout-title h3 {

        font-size: 17px;

    }

    .checkout-title p {

        font-size: 10px;

    }

    .order-item-image {

        width: 52px;

        height: 52px;

        min-width: 52px;

    }

    .order-item-name {

        font-size: 11px;

    }

    .order-item-price {

        font-size: 12px;

    }

    .summary-total strong {

        font-size: 21px;

    }

}

</style>


<!-- =====================================================
     CHECKOUT HERO
===================================================== -->

<div
    class="site-blocks-cover inner-page"
    style="
        background-image:
        linear-gradient(
            90deg,
            rgba(4,35,21,.88),
            rgba(5,50,29,.55)
        ),
        url('assets/images/hero_1.jpg');
    "
>

    <div class="container">

        <div class="row">

            <div class="col-lg-7 mx-auto align-self-center">

                <div class="site-block-cover-content text-center">

                    <div
                        style="
                            display:inline-flex;
                            align-items:center;
                            gap:8px;
                            padding:8px 15px;
                            border-radius:50px;
                            background:rgba(255,255,255,.12);
                            border:1px solid rgba(255,255,255,.20);
                            color:#fff;
                            font-size:11px;
                            font-weight:700;
                            margin-bottom:15px;
                        "
                    >
                        🛒 Secure Checkout
                    </div>

                    <h1>
                        Checkout
                    </h1>

                    <p>
                        Complete your medicine order securely.
                    </p>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- =====================================================
     CHECKOUT
===================================================== -->

<div class="checkout-page">

    <div class="container">


        <!-- =================================================
             ERROR
        ================================================== -->

        <?php if ($error !== ''): ?>

            <div class="checkout-alert">

                <div class="checkout-alert-icon">
                    ⚠
                </div>

                <div>

                    <strong>
                        Order not placed
                    </strong>

                    <br>

                    <?= e($error) ?>

                </div>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action="checkout.php"
            id="checkoutForm"
            autocomplete="off"
        >

            <!-- CSRF -->

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(
                    $_SESSION['csrf_token']
                ) ?>"
            >


            <div class="row">


                <!-- =================================================
                     LEFT
                ================================================== -->

                <div class="col-lg-7 mb-4">


                    <div class="checkout-card">

                        <div class="checkout-card-body">


                            <!-- USER INFO -->

                            <div class="user-info-box">

                                <div class="user-info-icon">
                                    ✓
                                </div>

                                <div class="user-info-content">

                                    <small>
                                        Logged in as
                                    </small>

                                    <strong>
                                        <?= e($customer_name) ?>
                                    </strong>

                                    <?php if (
                                        $customer_mobile !== ''
                                    ): ?>

                                        <span
                                            style="
                                                color:#718078;
                                                font-size:11px;
                                                margin-left:5px;
                                            "
                                        >
                                            •
                                            <?= e(
                                                $customer_mobile
                                            ) ?>
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>


                            <!-- DELIVERY TITLE -->

                            <div class="checkout-title">

                                <div class="checkout-title-icon">
                                    📍
                                </div>

                                <div>

                                    <h3>
                                        Delivery Information
                                    </h3>

                                    <p>
                                        Enter where you want your medicines delivered.
                                    </p>

                                </div>

                            </div>


                            <!-- NAME -->

                            <div class="checkout-form-group">

                                <label for="customer_name">
                                    Full Name *
                                </label>

                                <input
                                    type="text"
                                    id="customer_name"
                                    name="customer_name"
                                    class="checkout-form-control"
                                    value="<?= e(
                                        $_POST['customer_name']
                                        ?? $customer_name
                                    ) ?>"
                                    maxlength="100"
                                    placeholder="Enter your full name"
                                    required
                                >

                            </div>


                            <!-- MOBILE -->

                            <div class="checkout-form-group">

                                <label for="customer_mobile">
                                    Mobile Number *
                                </label>

                                <input
                                    type="text"
                                    id="customer_mobile"
                                    name="customer_mobile"
                                    class="checkout-form-control"
                                    value="<?= e(
                                        $_POST['customer_mobile']
                                        ?? $customer_mobile
                                    ) ?>"
                                    maxlength="10"
                                    inputmode="numeric"
                                    pattern="[0-9]{10}"
                                    placeholder="10-digit mobile number"
                                    required
                                >

                            </div>


                            <!-- ADDRESS -->

                            <div class="checkout-form-group">

                                <label for="address">
                                    Full Delivery Address *
                                </label>

                                <textarea
                                    id="address"
                                    name="address"
                                    class="checkout-form-control"
                                    maxlength="500"
                                    placeholder="House no, village, street, landmark..."
                                    required
                                ><?= e(
                                    $_POST['address'] ?? ''
                                ) ?></textarea>

                            </div>


                            <!-- CITY + STATE -->

                            <div class="row">

                                <div class="col-md-6">

                                    <div class="checkout-form-group">

                                        <label for="city">
                                            City / Village *
                                        </label>

                                        <input
                                            type="text"
                                            id="city"
                                            name="city"
                                            class="checkout-form-control"
                                            value="<?= e(
                                                $_POST['city'] ?? ''
                                            ) ?>"
                                            maxlength="100"
                                            placeholder="Forbesganj"
                                            required
                                        >

                                    </div>

                                </div>


                                <div class="col-md-6">

                                    <div class="checkout-form-group">

                                        <label for="state">
                                            State
                                        </label>

                                        <input
                                            type="text"
                                            id="state"
                                            name="state"
                                            class="checkout-form-control"
                                            value="<?= e(
                                                $_POST['state']
                                                ?? 'Bihar'
                                            ) ?>"
                                            maxlength="100"
                                            placeholder="Bihar"
                                        >

                                    </div>

                                </div>

                            </div>


                            <!-- PINCODE -->

                            <div class="checkout-form-group">

                                <label for="pincode">
                                    Pincode *
                                </label>

                                <input
                                    type="text"
                                    id="pincode"
                                    name="pincode"
                                    class="checkout-form-control"
                                    value="<?= e(
                                        $_POST['pincode'] ?? ''
                                    ) ?>"
                                    maxlength="6"
                                    inputmode="numeric"
                                    pattern="[0-9]{6}"
                                    placeholder="6-digit pincode"
                                    required
                                >

                            </div>


                            <!-- PAYMENT TITLE -->

                            <div
                                class="checkout-title"
                                style="margin-top:35px;"
                            >

                                <div class="checkout-title-icon">
                                    💳
                                </div>

                                <div>

                                    <h3>
                                        Payment Method
                                    </h3>

                                    <p>
                                        Choose your preferred payment option.
                                    </p>

                                </div>

                            </div>


                            <!-- COD -->

                            <div class="payment-option">

                                <input
                                    type="radio"
                                    id="payment_cod"
                                    name="payment_method"
                                    value="cod"
                                    checked
                                >


                                <label
                                    for="payment_cod"
                                    class="payment-label"
                                >

                                    <div class="payment-content">

                                        <div class="payment-icon">
                                            💵
                                        </div>

                                        <div class="payment-text">

                                            <strong>
                                                Cash on Delivery
                                            </strong>

                                            <small>
                                                Delivery ke time payment karein.
                                            </small>

                                        </div>

                                        <div class="payment-check">
                                            ✓
                                        </div>

                                    </div>

                                </label>

                            </div>


                            <!-- ONLINE PAYMENT -->

                            <div class="payment-option">

                                <input
                                    type="radio"
                                    id="payment_online"
                                    name="payment_method"
                                    value="online"
                                    disabled
                                >


                                <label
                                    for="payment_online"
                                    class="payment-label"
                                >

                                    <div class="payment-content">

                                        <div class="payment-icon">
                                            💳
                                        </div>

                                        <div class="payment-text">

                                            <strong>

                                                Online Payment

                                                <span class="coming-soon">
                                                    Coming Soon
                                                </span>

                                            </strong>

                                            <small>
                                                Online payment gateway
                                                integration ke baad available hoga.
                                            </small>

                                        </div>

                                        <div class="payment-check">
                                            ✓
                                        </div>

                                    </div>

                                </label>

                            </div>


                        </div>

                    </div>

                </div>


                <!-- =================================================
                     RIGHT
                ================================================== -->

                <div class="col-lg-5">


                    <div class="checkout-card order-summary">

                        <div class="checkout-card-body">


                            <!-- TITLE -->

                            <div class="checkout-title">

                                <div class="checkout-title-icon">
                                    🛒
                                </div>

                                <div>

                                    <h3>
                                        Your Order
                                    </h3>

                                    <p>
                                        Review your medicines before ordering.
                                    </p>

                                </div>

                            </div>


                            <!-- ITEMS -->

                            <?php foreach (
                                $cart_items
                                as $item
                            ): ?>

                                <?php

                                $itemImage =
                                    !empty($item['image'])
                                    ? 'uploads/medicines/'
                                      . $item['image']
                                    : 'assets/images/product_01.png';

                                ?>

                                <div class="order-item">


                                    <!-- IMAGE -->

                                    <div class="order-item-image">

                                        <img
                                            src="<?= e(
                                                $itemImage
                                            ) ?>"
                                            alt="<?= e(
                                                $item['name']
                                            ) ?>"
                                            loading="lazy"
                                        >

                                    </div>


                                    <!-- INFO -->

                                    <div class="order-item-info">

                                        <div class="order-item-name">

                                            <?= e(
                                                $item['name']
                                            ) ?>

                                        </div>


                                        <div class="order-item-meta">

                                            Qty:
                                            <?= (int)
                                                $item['cart_quantity']
                                            ?>

                                            ×

                                            ₹<?= number_format(
                                                (float)
                                                $item['selling_price'],
                                                2
                                            ) ?>

                                        </div>

                                    </div>


                                    <!-- PRICE -->

                                    <div class="order-item-price">

                                        ₹<?= number_format(
                                            (float)
                                            $item['item_total'],
                                            2
                                        ) ?>

                                    </div>

                                </div>

                            <?php endforeach; ?>


                            <!-- SUMMARY -->

                            <div class="summary-box">


                                <div class="summary-row">

                                    <span>
                                        Total Items
                                    </span>

                                    <strong>
                                        <?= (int)$total_items ?>
                                    </strong>

                                </div>


                                <div class="summary-row">

                                    <span>
                                        Subtotal
                                    </span>

                                    <strong>
                                        ₹<?= number_format(
                                            $subtotal,
                                            2
                                        ) ?>
                                    </strong>

                                </div>


                                <div class="summary-row">

                                    <span>
                                        Delivery
                                    </span>

                                    <strong
                                        style="
                                            color:#159447;
                                        "
                                    >
                                        Free
                                    </strong>

                                </div>


                                <?php if ($discount > 0): ?>

                                    <div class="summary-row">

                                        <span>
                                            Discount
                                        </span>

                                        <strong
                                            style="
                                                color:#159447;
                                            "
                                        >

                                            -₹<?= number_format(
                                                $discount,
                                                2
                                            ) ?>

                                        </strong>

                                    </div>

                                <?php endif; ?>


                                <!-- TOTAL -->

                                <div
                                    class="
                                        summary-row
                                        summary-total
                                    "
                                >

                                    <span>
                                        Total Amount
                                    </span>

                                    <strong>

                                        ₹<?= number_format(
                                            $total_amount,
                                            2
                                        ) ?>

                                    </strong>

                                </div>


                            </div>


                            <!-- PLACE ORDER -->

                            <button
                                type="submit"
                                id="placeOrderBtn"
                                class="place-order-btn"
                            >

                                🛍 Place Order

                            </button>


                            <!-- BACK CART -->

                            <a
                                href="cart.php"
                                class="back-cart"
                            >

                                ← Back to Cart

                            </a>


                            <!-- SECURITY -->

                            <div class="checkout-security">

                                <div class="checkout-security-icon">
                                    🔒
                                </div>

                                <div>

                                    Your order information is securely
                                    processed. Please make sure your delivery
                                    details are correct before placing the order.

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         TRUST
                    ================================================== -->

                    <div class="checkout-trust">

                        <div class="checkout-trust-item">

                            <span>✓</span>
                            Secure Ordering

                        </div>

                        <div class="checkout-trust-item">

                            <span>✓</span>
                            Local Delivery

                        </div>

                        <div class="checkout-trust-item">

                            <span>✓</span>
                            Genuine Medicines

                        </div>

                    </div>


                </div>

            </div>

        </form>

    </div>

</div>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        // =================================================
        // MOBILE
        // =================================================

        const mobile =
            document.getElementById(
                'customer_mobile'
            );


        if (mobile) {

            mobile.addEventListener(
                'input',
                function () {

                    this.value =
                        this.value
                            .replace(/\D/g, '')
                            .slice(0, 10);

                }
            );

        }


        // =================================================
        // PINCODE
        // =================================================

        const pincode =
            document.getElementById(
                'pincode'
            );


        if (pincode) {

            pincode.addEventListener(
                'input',
                function () {

                    this.value =
                        this.value
                            .replace(/\D/g, '')
                            .slice(0, 6);

                }
            );

        }


        // =================================================
        // PLACE ORDER
        // =================================================

        const form =
            document.getElementById(
                'checkoutForm'
            );


        const button =
            document.getElementById(
                'placeOrderBtn'
            );


        if (form && button) {

            form.addEventListener(
                'submit',
                function (event) {


                    if (
                        !form.checkValidity()
                    ) {

                        return;

                    }


                    button.disabled =
                        true;


                    button.innerHTML =
                        '⏳ Placing Order...';

                }
            );

        }

    }
);

</script>


<?php

require_once "includes/footer.php";

?>