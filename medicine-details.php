<?php

$page_title = "Medicine Details | Medicine Aapki Gaw Mein";

require_once "config/database.php";


// =====================================================
// GET MEDICINE ID
// =====================================================

$medicine_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


// =====================================================
// VALIDATE ID
// =====================================================

if ($medicine_id <= 0) {

    header("Location: medicines.php");
    exit;

}


// =====================================================
// FETCH MEDICINE
// =====================================================

$sql = "SELECT *
        FROM medicines
        WHERE id = ?
        AND status = 1
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);


if (!$stmt) {

    die("Database query failed.");

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


// =====================================================
// MEDICINE NOT FOUND
// =====================================================

if (!$medicine) {

    header("Location: medicines.php");
    exit;

}


// =====================================================
// CALCULATE DISCOUNT
// =====================================================

$mrp = (float) $medicine['mrp'];

$selling_price = (float) $medicine['selling_price'];

$discount = 0;


if ($mrp > 0 && $mrp > $selling_price) {

    $discount = round(
        (($mrp - $selling_price) / $mrp) * 100
    );

}


// =====================================================
// STOCK
// =====================================================

$stock = (int) $medicine['stock_quantity'];


// =====================================================
// PAGE TITLE
// =====================================================

$page_title =
    htmlspecialchars($medicine['name'])
    . " | Medicine Aapki Gaw Mein";


require_once "includes/header.php";

?>

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
                        Medicine Details
                    </h1>

                    <p>
                        View medicine information before ordering.
                    </p>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- =====================================================
     MEDICINE DETAILS
===================================================== -->

<div class="site-section">

    <div class="container">


        <div class="row">


            <!-- =================================================
                 MEDICINE IMAGE
            ================================================== -->

            <div class="col-md-6 mb-5 mb-md-0">

                <div class="border text-center p-4">

                    <?php if (!empty($medicine['image'])): ?>

                        <img
                            src="assets/images/<?php
                            echo htmlspecialchars(
                                $medicine['image']
                            );
                            ?>"
                            alt="<?php
                            echo htmlspecialchars(
                                $medicine['name']
                            );
                            ?>"
                            class="img-fluid"
                            style="
                                width:100%;
                                height:400px;
                                object-fit:contain;
                            "
                        >

                    <?php else: ?>

                        <img
                            src="assets/images/product_01.png"
                            alt="Medicine"
                            class="img-fluid"
                            style="
                                width:100%;
                                height:400px;
                                object-fit:contain;
                            "
                        >

                    <?php endif; ?>

                </div>

            </div>


            <!-- =================================================
                 MEDICINE INFORMATION
            ================================================== -->

            <div class="col-md-6">


                <!-- Name -->

                <h2 class="text-black mb-3">

                    <?php
                    echo htmlspecialchars(
                        $medicine['name']
                    );
                    ?>

                </h2>


                <!-- Generic Name -->

                <?php if (!empty($medicine['generic_name'])): ?>

                    <p class="text-muted mb-2">

                        <strong>
                            Generic Name:
                        </strong>

                        <?php
                        echo htmlspecialchars(
                            $medicine['generic_name']
                        );
                        ?>

                    </p>

                <?php endif; ?>


                <!-- Manufacturer -->

                <?php if (!empty($medicine['manufacturer'])): ?>

                    <p class="text-muted mb-2">

                        <strong>
                            Manufacturer:
                        </strong>

                        <?php
                        echo htmlspecialchars(
                            $medicine['manufacturer']
                        );
                        ?>

                    </p>

                <?php endif; ?>


                <!-- Category -->

                <?php if (!empty($medicine['category'])): ?>

                    <p class="text-muted mb-3">

                        <strong>
                            Category:
                        </strong>

                        <?php
                        echo htmlspecialchars(
                            $medicine['category']
                        );
                        ?>

                    </p>

                <?php endif; ?>


                <!-- =================================================
                     PRICE
                ================================================== -->

                <div class="mb-4">


                    <?php if ($mrp > $selling_price): ?>

                        <span
                            class="text-muted"
                            style="font-size:18px;"
                        >

                            <del>

                                ₹<?php
                                echo number_format(
                                    $mrp,
                                    2
                                );
                                ?>

                            </del>

                        </span>

                        &nbsp;


                        <span
                            class="text-primary font-weight-bold"
                            style="font-size:28px;"
                        >

                            ₹<?php
                            echo number_format(
                                $selling_price,
                                2
                            );
                            ?>

                        </span>


                        <span
                            class="badge badge-success ml-2"
                        >

                            <?php
                            echo $discount;
                            ?>%
                            OFF

                        </span>


                    <?php else: ?>

                        <span
                            class="text-primary font-weight-bold"
                            style="font-size:28px;"
                        >

                            ₹<?php
                            echo number_format(
                                $selling_price,
                                2
                            );
                            ?>

                        </span>

                    <?php endif; ?>


                </div>


                <!-- =================================================
                     STOCK
                ================================================== -->

                <?php if ($stock > 0): ?>

                    <p class="text-success">

                        <strong>
                            ✓ In Stock
                        </strong>

                        <br>

                        <small>
                            <?php echo $stock; ?>
                            units available
                        </small>

                    </p>

                <?php else: ?>

                    <p class="text-danger">

                        <strong>
                            ✕ Out of Stock
                        </strong>

                    </p>

                <?php endif; ?>


                <!-- =================================================
                     PRESCRIPTION
                ================================================== -->

                <?php if (
                    $medicine['prescription_required'] == 1
                ): ?>

                    <div class="alert alert-warning">

                        <strong>
                            Prescription Required
                        </strong>

                        <br>

                        Doctor's prescription may be required
                        before this medicine can be ordered.

                        <br><br>

                        <a
                            href="prescription-upload.php"
                            class="btn btn-sm btn-warning"
                        >

                            Upload Prescription

                        </a>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     ADD TO CART
                ================================================== -->

                <?php if ($stock > 0): ?>

                    <form
                        method="POST"
                        action="cart.php"
                        class="mt-4"
                    >

                        <input
                            type="hidden"
                            name="medicine_id"
                            value="<?php
                            echo (int)
                                $medicine['id'];
                            ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="add"
                        >


                        <div class="row align-items-center">


                            <div class="col-md-4 mb-3">

                                <label>
                                    Quantity
                                </label>

                                <input
                                    type="number"
                                    name="quantity"
                                    value="1"
                                    min="1"
                                    max="<?php
                                    echo $stock;
                                    ?>"
                                    class="form-control"
                                >

                            </div>


                            <div class="col-md-8 mb-3">

                                <label>
                                    &nbsp;
                                </label>

                                <button
                                    type="submit"
                                    class="btn btn-primary btn-lg btn-block"
                                >

                                    <span
                                        class="icon-shopping-bag mr-2"
                                    ></span>

                                    Add to Cart

                                </button>

                            </div>


                        </div>

                    </form>

                <?php else: ?>

                    <button
                        type="button"
                        class="btn btn-secondary btn-lg"
                        disabled
                    >

                        Out of Stock

                    </button>

                <?php endif; ?>


            </div>

        </div>


        <!-- =====================================================
             MEDICINE INFORMATION
        ===================================================== -->

        <div class="row mt-5">

            <div class="col-12">


                <div class="border p-4">


                    <h3 class="h4 text-black mb-4">

                        Medicine Information

                    </h3>


                    <!-- Composition -->

                    <?php if (
                        !empty(
                            $medicine['composition']
                        )
                    ): ?>

                        <div class="mb-4">

                            <h4 class="h6 text-uppercase">

                                Composition

                            </h4>

                            <p>

                                <?php
                                echo nl2br(
                                    htmlspecialchars(
                                        $medicine[
                                            'composition'
                                        ]
                                    )
                                );
                                ?>

                            </p>

                        </div>

                    <?php endif; ?>


                    <!-- Description -->

                    <?php if (
                        !empty(
                            $medicine['description']
                        )
                    ): ?>

                        <div class="mb-4">

                            <h4 class="h6 text-uppercase">

                                Description

                            </h4>

                            <p>

                                <?php
                                echo nl2br(
                                    htmlspecialchars(
                                        $medicine[
                                            'description'
                                        ]
                                    )
                                );
                                ?>

                            </p>

                        </div>

                    <?php endif; ?>


                    <!-- Manufacturer -->

                    <?php if (
                        !empty(
                            $medicine['manufacturer']
                        )
                    ): ?>

                        <div class="mb-3">

                            <strong>
                                Manufacturer:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $medicine[
                                    'manufacturer'
                                ]
                            );
                            ?>

                        </div>

                    <?php endif; ?>


                    <!-- Batch -->

                    <?php if (
                        !empty(
                            $medicine['batch_number']
                        )
                    ): ?>

                        <div class="mb-3">

                            <strong>
                                Batch Number:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $medicine[
                                    'batch_number'
                                ]
                            );
                            ?>

                        </div>

                    <?php endif; ?>


                    <!-- Expiry -->

                    <?php if (
                        !empty(
                            $medicine['expiry_date']
                        )
                    ): ?>

                        <div class="mb-3">

                            <strong>
                                Expiry Date:
                            </strong>

                            <?php
                            echo date(
                                "d M Y",
                                strtotime(
                                    $medicine[
                                        'expiry_date'
                                    ]
                                )
                            );
                            ?>

                        </div>

                    <?php endif; ?>


                    <!-- Category -->

                    <?php if (
                        !empty(
                            $medicine['category']
                        )
                    ): ?>

                        <div class="mb-3">

                            <strong>
                                Category:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $medicine[
                                    'category'
                                ]
                            );
                            ?>

                        </div>

                    <?php endif; ?>


                </div>

            </div>

        </div>


        <!-- =====================================================
             NAVIGATION
        ===================================================== -->

        <div class="row mt-5">

            <div class="col-md-6">

                <a
                    href="medicines.php"
                    class="btn btn-outline-primary"
                >

                    ← Back to Medicines

                </a>

            </div>


            <div class="col-md-6 text-md-right mt-3 mt-md-0">

                <a
                    href="cart.php"
                    class="btn btn-primary"
                >

                    View Cart

                </a>

            </div>

        </div>


    </div>

</div>


<?php require_once "includes/footer.php"; ?>
