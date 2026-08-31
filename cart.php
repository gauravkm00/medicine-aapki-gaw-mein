<?php

session_start();

$page_title = "Shopping Cart | Medicine Aapki Gaw Mein";

require_once "config/database.php";


// =====================================================
// INITIALIZE CART
// =====================================================

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}


// =====================================================
// ADD TO CART
// =====================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'add'
) {

    $medicine_id = (int)($_POST['medicine_id'] ?? 0);

    $requested_quantity = (int)($_POST['quantity'] ?? 1);


    if ($medicine_id <= 0) {

        header("Location: medicines.php");
        exit;

    }


    // Minimum quantity

    if ($requested_quantity < 1) {
        $requested_quantity = 1;
    }


    // -----------------------------------------------------
    // FETCH MEDICINE
    // -----------------------------------------------------

    $sql = "SELECT id, name, stock_quantity, status
            FROM medicines
            WHERE id = ?
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);


    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $medicine_id
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $medicine = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);


        if ($medicine && (int)$medicine['status'] === 1) {

            $stock = (int)$medicine['stock_quantity'];


            if ($stock > 0) {

                // Existing quantity

                $current_quantity = isset(
                    $_SESSION['cart'][$medicine_id]
                )
                    ? (int)$_SESSION['cart'][$medicine_id]
                    : 0;


                // New quantity

                $new_quantity =
                    $current_quantity + $requested_quantity;


                // Never exceed stock

                if ($new_quantity > $stock) {
                    $new_quantity = $stock;
                }


                $_SESSION['cart'][$medicine_id] =
                    $new_quantity;

            }

        }

    }


    header("Location: cart.php");
    exit;
}


// =====================================================
// UPDATE CART
// =====================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'update'
) {

    if (
        isset($_POST['quantity']) &&
        is_array($_POST['quantity'])
    ) {

        foreach ($_POST['quantity'] as $medicine_id => $quantity) {

            $medicine_id = (int)$medicine_id;
            $quantity = (int)$quantity;


            if ($medicine_id <= 0) {
                continue;
            }


            // Quantity 0 = remove

            if ($quantity <= 0) {

                unset($_SESSION['cart'][$medicine_id]);

                continue;
            }


            // -------------------------------------------------
            // CHECK CURRENT STOCK
            // -------------------------------------------------

            $sql = "SELECT stock_quantity, status
                    FROM medicines
                    WHERE id = ?
                    LIMIT 1";

            $stmt = mysqli_prepare($conn, $sql);


            if (!$stmt) {
                continue;
            }


            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $medicine_id
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            $medicine = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);


            // Medicine unavailable

            if (
                !$medicine ||
                (int)$medicine['status'] !== 1
            ) {

                unset($_SESSION['cart'][$medicine_id]);

                continue;
            }


            $stock = (int)$medicine['stock_quantity'];


            // Out of stock

            if ($stock <= 0) {

                unset($_SESSION['cart'][$medicine_id]);

                continue;
            }


            // Never exceed stock

            if ($quantity > $stock) {
                $quantity = $stock;
            }


            $_SESSION['cart'][$medicine_id] =
                $quantity;

        }

    }


    header("Location: cart.php");
    exit;
}


// =====================================================
// REMOVE ITEM
// =====================================================

if (isset($_GET['remove'])) {

    $remove_id = (int)$_GET['remove'];


    if ($remove_id > 0) {

        unset($_SESSION['cart'][$remove_id]);

    }


    header("Location: cart.php");
    exit;
}


// =====================================================
// CLEAR CART
// =====================================================

if (
    isset($_GET['clear']) &&
    $_GET['clear'] === '1'
) {

    $_SESSION['cart'] = [];

    header("Location: cart.php");
    exit;
}


// =====================================================
// FETCH CART ITEMS
// =====================================================

$cart_items = [];

$subtotal = 0;

$total_items = 0;

$total_mrp = 0;

$total_discount = 0;


if (!empty($_SESSION['cart'])) {

    $medicine_ids = array_keys($_SESSION['cart']);

    $medicine_ids = array_map('intval', $medicine_ids);

    $medicine_ids = array_filter($medicine_ids);


    if (!empty($medicine_ids)) {

        $id_list = implode(',', $medicine_ids);


        $sql = "SELECT
                    id,
                    name,
                    generic_name,
                    manufacturer,
                    category,
                    composition,
                    description,
                    batch_number,
                    expiry_date,
                    mrp,
                    selling_price,
                    stock_quantity,
                    prescription_required,
                    image,
                    status
                FROM medicines
                WHERE id IN ($id_list)
                AND status = 1
                ORDER BY id DESC";


        $result = mysqli_query($conn, $sql);


        if ($result) {

            while ($medicine = mysqli_fetch_assoc($result)) {

                $medicine_id =
                    (int)$medicine['id'];


                // Session quantity

                $quantity = isset(
                    $_SESSION['cart'][$medicine_id]
                )
                    ? (int)$_SESSION['cart'][$medicine_id]
                    : 0;


                if ($quantity <= 0) {

                    unset(
                        $_SESSION['cart'][$medicine_id]
                    );

                    continue;
                }


                // -------------------------------------------------
                // STOCK VALIDATION
                // -------------------------------------------------

                $stock =
                    (int)$medicine['stock_quantity'];


                if ($stock <= 0) {

                    unset(
                        $_SESSION['cart'][$medicine_id]
                    );

                    continue;
                }


                // Stock reduced after adding

                if ($quantity > $stock) {

                    $quantity = $stock;

                    $_SESSION['cart'][$medicine_id] =
                        $quantity;

                }


                // -------------------------------------------------
                // PRICE
                // -------------------------------------------------

                $mrp =
                    (float)$medicine['mrp'];

                $selling_price =
                    (float)$medicine['selling_price'];


                $item_total =
                    $selling_price * $quantity;


                $item_mrp_total =
                    $mrp * $quantity;


                $item_discount =
                    $item_mrp_total - $item_total;


                if ($item_discount < 0) {
                    $item_discount = 0;
                }


                // -------------------------------------------------
                // ADD CART DATA
                // -------------------------------------------------

                $medicine['cart_quantity'] =
                    $quantity;

                $medicine['item_total'] =
                    $item_total;

                $medicine['item_mrp_total'] =
                    $item_mrp_total;

                $medicine['item_discount'] =
                    $item_discount;


                $cart_items[] =
                    $medicine;


                // -------------------------------------------------
                // TOTALS
                // -------------------------------------------------

                $subtotal += $item_total;

                $total_mrp += $item_mrp_total;

                $total_discount += $item_discount;

                $total_items += $quantity;

            }

        }

    }

}


// =====================================================
// CART COUNT
// =====================================================

$cart_count = 0;

foreach ($_SESSION['cart'] as $qty) {

    $cart_count += (int)$qty;

}

?>

<?php require_once "includes/header.php"; ?>


<style>

/* =====================================================
   CART PAGE
===================================================== */

.cart-page .cart-table {
    background: #fff;
}

.cart-page .medicine-image {
    width: 80px;
    height: 80px;
    object-fit: contain;
    background: #fafafa;
    border: 1px solid #eee;
    border-radius: 6px;
    padding: 5px;
}

.cart-page .medicine-name {
    font-weight: 600;
    color: #222;
}

.cart-page .quantity-input {
    width: 85px;
    text-align: center;
}

.cart-page .cart-summary {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 4px;
}

.cart-page .summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.cart-page .grand-total {
    font-size: 21px;
}

.cart-page .discount-text {
    color: #28a745;
}

.cart-page .empty-cart {
    padding: 70px 25px;
    background: #fff;
    border: 1px solid #eee;
}

.cart-page .empty-cart-icon {
    font-size: 55px;
    color: #51b848;
    margin-bottom: 20px;
}

@media (max-width: 767px) {

    .cart-page .medicine-image {
        width: 60px;
        height: 60px;
    }

    .cart-page table {
        min-width: 700px;
    }

}

</style>


<!-- =====================================================
     PAGE HEADER
===================================================== -->

<div class="site-blocks-cover inner-page"
     style="background-image: url('assets/images/hero_1.jpg');">

    <div class="container">

        <div class="row">

            <div class="col-lg-7 mx-auto align-self-center">

                <div class="site-block-cover-content text-center">

                    <h1>
                        Shopping Cart
                    </h1>

                    <p>
                        Review your medicines before checkout.
                    </p>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- =====================================================
     CART SECTION
===================================================== -->

<div class="site-section cart-page">

    <div class="container">


        <?php if (!empty($cart_items)): ?>


            <form
                method="POST"
                action="cart.php"
            >

                <input
                    type="hidden"
                    name="action"
                    value="update"
                >


                <div class="row">


                    <!-- =================================================
                         LEFT - CART ITEMS
                    ================================================== -->

                    <div class="col-lg-8 mb-5 mb-lg-0">


                        <div class="mb-3">

                            <h3 class="h4 text-black">

                                Your Cart

                            </h3>

                            <p class="text-muted">

                                <?= (int)$total_items ?>
                                item(s) in your cart.

                            </p>

                        </div>


                        <div class="table-responsive">

                            <table
                                class="table table-bordered cart-table"
                            >

                                <thead>

                                    <tr>

                                        <th>
                                            Medicine
                                        </th>

                                        <th>
                                            Price
                                        </th>

                                        <th>
                                            Quantity
                                        </th>

                                        <th>
                                            Total
                                        </th>

                                        <th>
                                            Remove
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>


                                <?php foreach (
                                    $cart_items as $item
                                ): ?>


                                    <tr>


                                        <!-- =================================
                                             MEDICINE
                                        ================================== -->

                                        <td>

                                            <div class="d-flex align-items-center">


                                                <?php if (
                                                    !empty(
                                                        $item['image']
                                                    )
                                                ): ?>

                                                    <img
                                                        src="assets/images/<?php
                                                        echo htmlspecialchars(
                                                            $item['image']
                                                        );
                                                        ?>"
                                                        alt="<?php
                                                        echo htmlspecialchars(
                                                            $item['name']
                                                        );
                                                        ?>"
                                                        class="medicine-image mr-3"
                                                    >

                                                <?php else: ?>

                                                    <img
                                                        src="assets/images/product_01.png"
                                                        alt="Medicine"
                                                        class="medicine-image mr-3"
                                                    >

                                                <?php endif; ?>


                                                <div>

                                                    <a
                                                        href="medicine-details.php?id=<?=
                                                        (int)$item['id']
                                                        ?>"
                                                        class="medicine-name"
                                                    >

                                                        <?php
                                                        echo htmlspecialchars(
                                                            $item['name']
                                                        );
                                                        ?>

                                                    </a>


                                                    <?php if (
                                                        !empty(
                                                            $item['generic_name']
                                                        )
                                                    ): ?>

                                                        <br>

                                                        <small class="text-muted">

                                                            <?php
                                                            echo htmlspecialchars(
                                                                $item[
                                                                    'generic_name'
                                                                ]
                                                            );
                                                            ?>

                                                        </small>

                                                    <?php endif; ?>


                                                    <?php if (
                                                        !empty(
                                                            $item['manufacturer']
                                                        )
                                                    ): ?>

                                                        <br>

                                                        <small class="text-muted">

                                                            <?php
                                                            echo htmlspecialchars(
                                                                $item[
                                                                    'manufacturer'
                                                                ]
                                                            );
                                                            ?>

                                                        </small>

                                                    <?php endif; ?>


                                                    <?php if (
                                                        $item[
                                                            'prescription_required'
                                                        ] == 1
                                                    ): ?>

                                                        <br>

                                                        <small
                                                            class="text-warning"
                                                        >

                                                            ⚠ Prescription
                                                            Required

                                                        </small>

                                                    <?php endif; ?>

                                                </div>

                                            </div>

                                        </td>


                                        <!-- =================================
                                             PRICE
                                        ================================== -->

                                        <td>

                                            <?php
                                            $item_mrp =
                                                (float)$item['mrp'];

                                            $item_price =
                                                (float)$item[
                                                    'selling_price'
                                                ];
                                            ?>


                                            <?php if (
                                                $item_mrp >
                                                $item_price
                                            ): ?>

                                                <del class="text-muted">

                                                    ₹<?= number_format(
                                                        $item_mrp,
                                                        2
                                                    ) ?>

                                                </del>

                                                <br>

                                            <?php endif; ?>


                                            <strong class="text-primary">

                                                ₹<?= number_format(
                                                    $item_price,
                                                    2
                                                ) ?>

                                            </strong>

                                        </td>


                                        <!-- =================================
                                             QUANTITY
                                        ================================== -->

                                        <td>

                                            <input
                                                type="number"
                                                name="quantity[<?= (int)$item['id'] ?>]"
                                                value="<?= (int)$item['cart_quantity'] ?>"
                                                min="0"
                                                max="<?= (int)$item['stock_quantity'] ?>"
                                                class="form-control quantity-input"
                                            >


                                            <small class="text-muted">

                                                Max:
                                                <?= (int)$item['stock_quantity'] ?>

                                            </small>

                                        </td>


                                        <!-- =================================
                                             TOTAL
                                        ================================== -->

                                        <td>

                                            <strong>

                                                ₹<?= number_format(
                                                    (float)$item[
                                                        'item_total'
                                                    ],
                                                    2
                                                ) ?>

                                            </strong>

                                        </td>


                                        <!-- =================================
                                             REMOVE
                                        ================================== -->

                                        <td>

                                            <a
                                                href="cart.php?remove=<?= (int)$item['id'] ?>"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Remove this medicine from cart?');"
                                            >

                                                Remove

                                            </a>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                                </tbody>

                            </table>

                        </div>


                        <!-- =================================================
                             CART ACTIONS
                        ================================================== -->

                        <div
                            class="d-flex justify-content-between flex-wrap mt-4"
                        >


                            <a
                                href="cart.php?clear=1"
                                class="btn btn-outline-danger mb-2"
                                onclick="return confirm('Clear entire cart?');"
                            >

                                Clear Cart

                            </a>


                            <button
                                type="submit"
                                class="btn btn-primary mb-2"
                            >

                                Update Cart

                            </button>


                        </div>


                    </div>


                    <!-- =================================================
                         RIGHT - SUMMARY
                    ================================================== -->

                    <div class="col-lg-4">


                        <div class="cart-summary p-4">


                            <h3 class="h4 text-black mb-4">

                                Cart Summary

                            </h3>


                            <!-- ITEMS -->

                            <div class="summary-row">

                                <span>
                                    Total Items
                                </span>

                                <strong>

                                    <?= (int)$total_items ?>

                                </strong>

                            </div>


                            <!-- MRP -->

                            <?php if (
                                $total_mrp >
                                $subtotal
                            ): ?>

                                <div class="summary-row">

                                    <span>
                                        MRP Total
                                    </span>

                                    <span>

                                        <del class="text-muted">

                                            ₹<?= number_format(
                                                $total_mrp,
                                                2
                                            ) ?>

                                        </del>

                                    </span>

                                </div>


                                <!-- SAVINGS -->

                                <div class="summary-row discount-text">

                                    <span>
                                        You Save
                                    </span>

                                    <strong>

                                        ₹<?= number_format(
                                            $total_discount,
                                            2
                                        ) ?>

                                    </strong>

                                </div>

                            <?php endif; ?>


                            <!-- SUBTOTAL -->

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


                            <hr>


                            <!-- DELIVERY -->

                            <div class="summary-row">

                                <span>
                                    Delivery
                                </span>

                                <span class="text-success">

                                    Calculated at checkout

                                </span>

                            </div>


                            <hr>


                            <!-- TOTAL -->

                            <div class="summary-row grand-total">

                                <strong>
                                    Total
                                </strong>

                                <strong class="text-primary">

                                    ₹<?= number_format(
                                        $subtotal,
                                        2
                                    ) ?>

                                </strong>

                            </div>


                            <!-- CHECKOUT -->

                            <a
                                href="checkout.php"
                                class="btn btn-primary btn-lg btn-block mt-4"
                            >

                                Proceed to Checkout

                            </a>


                            <!-- CONTINUE -->

                            <a
                                href="medicines.php"
                                class="btn btn-outline-primary btn-block mt-2"
                            >

                                Continue Shopping

                            </a>


                        </div>


                        <!-- =================================================
                             PRESCRIPTION NOTICE
                        ================================================== -->

                        <?php

                        $prescription_needed = false;

                        foreach ($cart_items as $item) {

                            if (
                                (int)$item[
                                    'prescription_required'
                                ] === 1
                            ) {

                                $prescription_needed = true;

                                break;

                            }

                        }

                        ?>


                        <?php if ($prescription_needed): ?>

                            <div
                                class="alert alert-warning mt-4"
                            >

                                <strong>
                                    Prescription Required
                                </strong>

                                <br>

                                Your cart contains one or more
                                prescription medicines.

                                <br><br>

                                <a
                                    href="prescription-upload.php"
                                    class="btn btn-sm btn-warning"
                                >

                                    Upload Prescription

                                </a>

                            </div>

                        <?php endif; ?>


                    </div>


                </div>

            </form>


        <?php else: ?>


            <!-- =================================================
                 EMPTY CART
            ================================================== -->

            <div class="row">

                <div class="col-md-8 mx-auto">


                    <div class="empty-cart text-center">


                        <div class="empty-cart-icon">

                            <span class="icon-shopping-bag"></span>

                        </div>


                        <h2 class="mb-3">

                            Your Cart is Empty

                        </h2>


                        <p class="text-muted mb-4">

                            Aapne abhi tak koi medicine
                            cart mein add nahi ki hai.

                        </p>


                        <a
                            href="medicines.php"
                            class="btn btn-primary px-5 py-3"
                        >

                            Browse Medicines

                        </a>


                    </div>


                </div>

            </div>


        <?php endif; ?>


    </div>

</div>


<?php require_once "includes/footer.php"; ?>
