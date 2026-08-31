```php
<?php

session_start();

$page_title = "Medicines | Medicine Aapki Gaw Mein";

require_once "config/database.php";


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
// SEARCH
// =====================================================

$search = trim($_GET['search'] ?? '');


// =====================================================
// FETCH MEDICINES
// =====================================================

$result = false;

if ($search !== '') {

    $sql = "
        SELECT *
        FROM medicines
        WHERE status = 1
        AND (
            name LIKE ?
            OR generic_name LIKE ?
            OR manufacturer LIKE ?
            OR category LIKE ?
            OR batch_number LIKE ?
        )
        ORDER BY id DESC
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {

        $searchTerm = "%" . $search . "%";

        mysqli_stmt_bind_param(
            $stmt,
            "sssss",
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
    }

} else {

    $sql = "
        SELECT *
        FROM medicines
        WHERE status = 1
        ORDER BY id DESC
    ";

    $result = mysqli_query($conn, $sql);
}


// =====================================================
// TOTAL MEDICINES
// =====================================================

$totalMedicines = 0;

if ($result) {
    $totalMedicines = mysqli_num_rows($result);
}


// =====================================================
// HEADER
// =====================================================

require_once "includes/header.php";

?>


<!-- =====================================================
     PAGE HERO
===================================================== -->

<section class="medicines-hero">

    <div class="medicines-hero-overlay"></div>

    <div class="container">

        <div class="row">

            <div class="col-lg-8 mx-auto text-center">

                <div class="medicines-hero-content">

                    <span class="hero-badge">
                        <i class="icon-heart"></i>
                        Trusted Local Healthcare
                    </span>

                    <h1>
                        Medicines
                    </h1>

                    <p>
                        Genuine medicines and healthcare products
                        for your everyday healthcare needs.
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     SEARCH SECTION
===================================================== -->

<section class="medicine-search-section">

    <div class="container">

        <div class="medicine-search-card">

            <div class="row align-items-center">

                <div class="col-lg-5 mb-3 mb-lg-0">

                    <div class="search-heading">

                        <span class="search-icon">
                            <i class="icon-search"></i>
                        </span>

                        <div>

                            <h3>
                                Find Your Medicine
                            </h3>

                            <p>
                                Search by medicine name, category or manufacturer
                            </p>

                        </div>

                    </div>

                </div>


                <div class="col-lg-7">

                    <form
                        method="GET"
                        action="medicines.php"
                        class="medicine-search-form"
                    >

                        <div class="medicine-search-input">

                            <i class="icon-search"></i>

                            <input
                                type="text"
                                name="search"
                                value="<?= e($search); ?>"
                                placeholder="Search medicine, generic name, category..."
                                autocomplete="off"
                            >

                            <?php if ($search !== ''): ?>

                                <a
                                    href="medicines.php"
                                    class="search-clear"
                                    title="Clear Search"
                                >
                                    ×
                                </a>

                            <?php endif; ?>

                            <button
                                type="submit"
                                class="search-button"
                            >
                                Search
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     MEDICINES SECTION
===================================================== -->

<section class="site-section medicines-section">

    <div class="container">


        <!-- =================================================
             SECTION HEADER
        ================================================= -->

        <div class="medicine-section-header">

            <div>

                <span class="section-small-title">
                    OUR MEDICINE COLLECTION
                </span>

                <h2>
                    <?php if ($search !== ''): ?>

                        Search Results

                    <?php else: ?>

                        Available Medicines

                    <?php endif; ?>
                </h2>

                <?php if ($search !== ''): ?>

                    <p class="search-result-text">

                        Showing medicines matching

                        <strong>
                            "<?= e($search); ?>"
                        </strong>

                    </p>

                <?php else: ?>

                    <p>
                        Explore our available medicines
                        and healthcare products.
                    </p>

                <?php endif; ?>

            </div>


            <div class="medicine-count">

                <span>
                    <?= $totalMedicines; ?>
                </span>

                <small>
                    Medicines
                </small>

            </div>

        </div>


        <!-- =================================================
             MEDICINE GRID
        ================================================= -->

        <div class="row">

            <?php if (
                $result &&
                mysqli_num_rows($result) > 0
            ): ?>


                <?php while (
                    $medicine = mysqli_fetch_assoc($result)
                ): ?>


                    <?php

                    // =================================================
                    // MEDICINE DATA
                    // =================================================

                    $medicineId =
                        (int) ($medicine['id'] ?? 0);

                    $medicineName =
                        trim($medicine['name'] ?? 'Medicine');

                    $genericName =
                        trim($medicine['generic_name'] ?? '');

                    $manufacturer =
                        trim($medicine['manufacturer'] ?? '');

                    $category =
                        trim($medicine['category'] ?? '');

                    $composition =
                        trim($medicine['composition'] ?? '');

                    $description =
                        trim($medicine['description'] ?? '');

                    $batchNumber =
                        trim($medicine['batch_number'] ?? '');

                    $image =
                        trim($medicine['image'] ?? '');

                    $mrp =
                        (float) ($medicine['mrp'] ?? 0);

                    $sellingPrice =
                        (float) ($medicine['selling_price'] ?? 0);

                    $stock =
                        (int) ($medicine['stock_quantity'] ?? 0);

                    $prescriptionRequired =
                        (int) ($medicine['prescription_required'] ?? 0);


                    // =================================================
                    // IMAGE
                    // =================================================

                    if ($image !== '') {

                        $imagePath =
                            "uploads/medicines/" .
                            basename($image);

                    } else {

                        $imagePath =
                            "assets/images/product_01.png";
                    }


                    $serverImagePath =
                        __DIR__ . "/" . $imagePath;


                    if (
                        $image !== '' &&
                        !is_file($serverImagePath)
                    ) {

                        $imagePath =
                            "assets/images/product_01.png";
                    }


                    // =================================================
                    // DISCOUNT
                    // =================================================

                    $discount = 0;

                    if (
                        $mrp > 0 &&
                        $sellingPrice > 0 &&
                        $sellingPrice < $mrp
                    ) {

                        $discount =
                            round(
                                (
                                    ($mrp - $sellingPrice)
                                    / $mrp
                                ) * 100
                            );
                    }


                    // =================================================
                    // EXPIRY
                    // =================================================

                    $expiryDate =
                        trim($medicine['expiry_date'] ?? '');

                    $isExpired = false;

                    $expirySoon = false;

                    if ($expiryDate !== '') {

                        $expiryTimestamp =
                            strtotime($expiryDate);

                        if ($expiryTimestamp !== false) {

                            if (
                                $expiryTimestamp <
                                strtotime('today')
                            ) {

                                $isExpired = true;

                            } elseif (
                                $expiryTimestamp <=
                                strtotime('+30 days')
                            ) {

                                $expirySoon = true;
                            }
                        }
                    }


                    // =================================================
                    // AVAILABILITY
                    // =================================================

                    $available =
                        $stock > 0 &&
                        !$isExpired;

                    ?>


                    <!-- =================================================
                         MEDICINE CARD
                    ================================================= -->

                    <div
                        class="
                            col-sm-6
                            col-lg-4
                            col-xl-4
                            mb-4
                        "
                    >

                        <div
                            class="
                                medicine-card
                                <?= !$available ? 'medicine-unavailable' : ''; ?>
                            "
                        >


                            <!-- =================================================
                                 BADGES
                            ================================================= -->

                            <div class="medicine-badges">

                                <?php if ($discount > 0): ?>

                                    <span class="discount-badge">

                                        <?= $discount; ?>% OFF

                                    </span>

                                <?php endif; ?>


                                <?php if (
                                    $prescriptionRequired === 1
                                ): ?>

                                    <span class="prescription-badge">

                                        <i class="icon-file-text"></i>

                                        Rx Required

                                    </span>

                                <?php endif; ?>

                            </div>


                            <!-- =================================================
                                 IMAGE
                            ================================================= -->

                            <a
                                href="medicine-details.php?id=<?= $medicineId; ?>"
                                class="medicine-image-wrapper"
                            >

                                <img
                                    src="<?= e($imagePath); ?>"
                                    alt="<?= e($medicineName); ?>"
                                    class="medicine-image"
                                    loading="lazy"
                                    onerror="
                                        this.onerror=null;
                                        this.src='assets/images/product_01.png';
                                    "
                                >

                            </a>


                            <!-- =================================================
                                 CARD CONTENT
                            ================================================= -->

                            <div class="medicine-card-body">


                                <!-- =================================================
                                     CATEGORY
                                ================================================= -->

                                <?php if ($category !== ''): ?>

                                    <span class="medicine-category">

                                        <?= e($category); ?>

                                    </span>

                                <?php endif; ?>


                                <!-- =================================================
                                     NAME
                                ================================================= -->

                                <h3 class="medicine-name">

                                    <a
                                        href="medicine-details.php?id=<?= $medicineId; ?>"
                                    >

                                        <?= e($medicineName); ?>

                                    </a>

                                </h3>


                                <!-- =================================================
                                     GENERIC NAME
                                ================================================= -->

                                <?php if ($genericName !== ''): ?>

                                    <p class="medicine-generic">

                                        <?= e($genericName); ?>

                                    </p>

                                <?php endif; ?>


                                <!-- =================================================
                                     MANUFACTURER
                                ================================================= -->

                                <?php if ($manufacturer !== ''): ?>

                                    <div class="medicine-info-row">

                                        <i class="icon-building"></i>

                                        <span>

                                            <?= e($manufacturer); ?>

                                        </span>

                                    </div>

                                <?php endif; ?>


                                <!-- =================================================
                                     COMPOSITION
                                ================================================= -->

                                <?php if ($composition !== ''): ?>

                                    <div class="medicine-composition">

                                        <strong>
                                            Composition
                                        </strong>

                                        <span>

                                            <?= e($composition); ?>

                                        </span>

                                    </div>

                                <?php endif; ?>


                                <!-- =================================================
                                     PRICE
                                ================================================= -->

                                <div class="medicine-price-row">

                                    <div class="medicine-price">

                                        <?php if (
                                            $mrp > 0 &&
                                            $mrp > $sellingPrice
                                        ): ?>

                                            <span class="medicine-mrp">

                                                ₹<?= number_format(
                                                    $mrp,
                                                    2
                                                ); ?>

                                            </span>

                                        <?php endif; ?>


                                        <span class="medicine-selling-price">

                                            ₹<?= number_format(
                                                $sellingPrice,
                                                2
                                            ); ?>

                                        </span>

                                    </div>


                                    <?php if ($discount > 0): ?>

                                        <span class="save-price">

                                            Save
                                            <?= $discount; ?>%

                                        </span>

                                    <?php endif; ?>

                                </div>


                                <!-- =================================================
                                     STOCK / EXPIRY
                                ================================================= -->

                                <div class="medicine-status">


                                    <?php if ($isExpired): ?>

                                        <span class="status-expired">

                                            <i class="icon-warning"></i>

                                            Expired

                                        </span>


                                    <?php elseif ($expirySoon): ?>

                                        <span class="status-expiring">

                                            <i class="icon-clock-o"></i>

                                            Expiring Soon

                                        </span>


                                    <?php elseif ($stock <= 0): ?>

                                        <span class="status-out">

                                            <i class="icon-close"></i>

                                            Out of Stock

                                        </span>


                                    <?php elseif ($stock <= 10): ?>

                                        <span class="status-low">

                                            <i class="icon-warning"></i>

                                            Only <?= $stock; ?> left

                                        </span>


                                    <?php else: ?>

                                        <span class="status-in">

                                            <i class="icon-check"></i>

                                            In Stock

                                        </span>

                                    <?php endif; ?>


                                    <?php if ($expiryDate !== ''): ?>

                                        <span class="expiry-date">

                                            Exp:
                                            <?= e($expiryDate); ?>

                                        </span>

                                    <?php endif; ?>

                                </div>


                                <!-- =================================================
                                     ACTION
                                ================================================= -->

                                <div class="medicine-actions">


                                    <?php if ($available): ?>


                                        <form
                                            method="POST"
                                            action="cart.php"
                                            class="add-cart-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="medicine_id"
                                                value="<?= $medicineId; ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="add"
                                            >

                                            <input
                                                type="hidden"
                                                name="quantity"
                                                value="1"
                                            >


                                            <button
                                                type="submit"
                                                class="btn-add-cart"
                                            >

                                                <i class="icon-shopping-cart"></i>

                                                Add to Cart

                                            </button>

                                        </form>


                                    <?php else: ?>


                                        <button
                                            type="button"
                                            class="btn-add-cart disabled"
                                            disabled
                                        >

                                            <i class="icon-ban"></i>

                                            Not Available

                                        </button>


                                    <?php endif; ?>


                                    <a
                                        href="medicine-details.php?id=<?= $medicineId; ?>"
                                        class="medicine-details-link"
                                    >

                                        View Details

                                        <i class="icon-arrow-right"></i>

                                    </a>

                                </div>


                            </div>

                        </div>

                    </div>


                <?php endwhile; ?>


            <?php else: ?>


                <!-- =================================================
                     EMPTY STATE
                ================================================= -->

                <div class="col-12">

                    <div class="medicine-empty-state">

                        <div class="empty-icon">

                            <i class="icon-search"></i>

                        </div>


                        <?php if ($search !== ''): ?>

                            <h3>
                                No Medicine Found
                            </h3>

                            <p>

                                We couldn't find any medicine
                                matching

                                <strong>
                                    "<?= e($search); ?>"
                                </strong>

                            </p>


                            <div class="empty-actions">

                                <a
                                    href="medicines.php"
                                    class="btn btn-primary"
                                >

                                    View All Medicines

                                </a>


                                <a
                                    href="upload-prescription.php"
                                    class="btn btn-outline-primary"
                                >

                                    Upload Prescription

                                </a>

                            </div>


                        <?php else: ?>

                            <h3>
                                No Medicines Available
                            </h3>

                            <p>
                                Medicines are currently unavailable.
                                Please check again later.
                            </p>

                        <?php endif; ?>

                    </div>

                </div>


            <?php endif; ?>

        </div>

    </div>

</section>


<!-- =====================================================
     PRESCRIPTION CTA
===================================================== -->

<section class="medicine-prescription-cta">

    <div class="container">

        <div class="prescription-cta-card">

            <div class="row align-items-center">

                <div class="col-lg-8">

                    <div class="cta-icon">

                        <i class="icon-file-text"></i>

                    </div>


                    <div class="cta-content">

                        <span>
                            Can't find your medicine?
                        </span>

                        <h2>
                            Upload Your Prescription
                        </h2>

                        <p>
                            Doctor ki prescription upload karein
                            aur hamari pharmacist team aapki
                            medicine requirement review karegi.
                        </p>

                    </div>

                </div>


                <div class="col-lg-4 text-lg-right mt-4 mt-lg-0">

                    <a
                        href="upload-prescription.php"
                        class="cta-button"
                    >

                        <i class="icon-upload"></i>

                        Upload Prescription

                    </a>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     HEALTHCARE TRUST STRIP
===================================================== -->

<section class="medicine-trust-section">

    <div class="container">

        <div class="row">


            <div class="col-md-4 mb-4 mb-md-0">

                <div class="trust-item">

                    <div class="trust-icon">
                        <i class="icon-check"></i>
                    </div>

                    <div>

                        <h4>
                            Genuine Medicines
                        </h4>

                        <p>
                            Quality medicines from
                            trusted manufacturers.
                        </p>

                    </div>

                </div>

            </div>


            <div class="col-md-4 mb-4 mb-md-0">

                <div class="trust-item">

                    <div class="trust-icon">
                        <i class="icon-lock"></i>
                    </div>

                    <div>

                        <h4>
                            Safe & Secure
                        </h4>

                        <p>
                            Your healthcare information
                            is handled securely.
                        </p>

                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="trust-item">

                    <div class="trust-icon">
                        <i class="icon-home"></i>
                    </div>

                    <div>

                        <h4>
                            Local Healthcare
                        </h4>

                        <p>
                            Convenient healthcare support
                            for your local community.
                        </p>

                    </div>

                </div>

            </div>


        </div>

    </div>

</section>


<!-- =====================================================
     PAGE CSS
===================================================== -->

<style>

/* =====================================================
   HERO
===================================================== */

.medicines-hero {

    position: relative;

    min-height: 330px;

    display: flex;

    align-items: center;

    background-image:
        url('assets/images/hero_1.jpg');

    background-size: cover;

    background-position: center;

    overflow: hidden;

}


.medicines-hero-overlay {

    position: absolute;

    inset: 0;

    background:
        linear-gradient(
            90deg,
            rgba(20, 90, 45, .92),
            rgba(20, 90, 45, .65),
            rgba(0, 0, 0, .35)
        );

}


.medicines-hero-content {

    position: relative;

    z-index: 2;

    color: #fff;

    padding: 50px 0;

}


.hero-badge {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 8px 16px;

    margin-bottom: 15px;

    border-radius: 30px;

    background: rgba(255,255,255,.16);

    border: 1px solid rgba(255,255,255,.25);

    font-size: 12px;

    font-weight: 600;

    letter-spacing: .4px;

}


.medicines-hero h1 {

    color: #fff;

    font-size: 52px;

    font-weight: 800;

    margin-bottom: 12px;

}


.medicines-hero p {

    color: rgba(255,255,255,.9);

    font-size: 17px;

    margin: 0;

}


/* =====================================================
   SEARCH
===================================================== */

.medicine-search-section {

    position: relative;

    margin-top: -42px;

    z-index: 10;

    padding-bottom: 20px;

}


.medicine-search-card {

    background: #fff;

    border-radius: 18px;

    padding: 24px 28px;

    box-shadow:
        0 15px 45px rgba(0,0,0,.12);

    border: 1px solid #edf1f5;

}


.search-heading {

    display: flex;

    align-items: center;

    gap: 14px;

}


.search-icon {

    width: 52px;

    height: 52px;

    min-width: 52px;

    border-radius: 14px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #e9f7ed;

    color: #278c3c;

    font-size: 22px;

}


.search-heading h3 {

    font-size: 18px;

    font-weight: 700;

    margin: 0 0 3px;

    color: #222;

}


.search-heading p {

    font-size: 12px;

    color: #7d8790;

    margin: 0;

}


.medicine-search-input {

    position: relative;

    display: flex;

    align-items: center;

    background: #f6f8fa;

    border: 1px solid #e5e9ed;

    border-radius: 12px;

    overflow: hidden;

    transition: .2s ease;

}


.medicine-search-input:focus-within {

    border-color: #51b848;

    background: #fff;

    box-shadow:
        0 0 0 3px rgba(81,184,72,.10);

}


.medicine-search-input > i {

    margin-left: 17px;

    color: #8c969e;

    font-size: 17px;

}


.medicine-search-input input {

    flex: 1;

    min-width: 0;

    border: 0;

    outline: 0;

    background: transparent;

    padding: 15px 12px;

    font-size: 13px;

    color: #222;

}


.medicine-search-input input::placeholder {

    color: #9ba4ab;

}


.search-button {

    border: 0;

    outline: 0;

    background: #278c3c;

    color: #fff;

    padding: 14px 24px;

    height: 100%;

    font-size: 13px;

    font-weight: 700;

    cursor: pointer;

    transition: .2s ease;

}


.search-button:hover {

    background: #176b2b;

}


.search-clear {

    color: #999;

    text-decoration: none;

    font-size: 24px;

    line-height: 1;

    margin-right: 12px;

}


.search-clear:hover {

    color: #333;

    text-decoration: none;

}


/* =====================================================
   SECTION HEADER
===================================================== */

.medicines-section {

    padding-top: 55px;

}


.medicine-section-header {

    display: flex;

    justify-content: space-between;

    align-items: flex-end;

    gap: 20px;

    margin-bottom: 35px;

}


.section-small-title {

    display: block;

    color: #278c3c;

    font-size: 10px;

    font-weight: 800;

    letter-spacing: 1.5px;

    margin-bottom: 7px;

}


.medicine-section-header h2 {

    font-size: 30px;

    font-weight: 800;

    margin-bottom: 6px;

    color: #20252a;

}


.medicine-section-header p {

    margin: 0;

    color: #7c858d;

    font-size: 13px;

}


.search-result-text strong {

    color: #278c3c;

}


.medicine-count {

    min-width: 95px;

    text-align: center;

    background: #f0f8f2;

    border: 1px solid #d9efdd;

    border-radius: 14px;

    padding: 12px 16px;

}


.medicine-count span {

    display: block;

    color: #278c3c;

    font-size: 24px;

    line-height: 1;

    font-weight: 800;

}


.medicine-count small {

    color: #68736c;

    font-size: 10px;

}


/* =====================================================
   MEDICINE CARD
===================================================== */

.medicine-card {

    height: 100%;

    background: #fff;

    border: 1px solid #e8edf0;

    border-radius: 18px;

    overflow: hidden;

    position: relative;

    display: flex;

    flex-direction: column;

    transition:
        transform .25s ease,
        box-shadow .25s ease,
        border-color .25s ease;

}


.medicine-card:hover {

    transform: translateY(-7px);

    border-color: #d5e8d9;

    box-shadow:
        0 18px 45px rgba(0,0,0,.10);

}


.medicine-badges {

    position: absolute;

    top: 14px;

    left: 14px;

    right: 14px;

    z-index: 3;

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 5px;

}


.discount-badge {

    display: inline-block;

    background: #278c3c;

    color: #fff;

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 10px;

    font-weight: 800;

}


.prescription-badge {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    background: #fff5d9;

    color: #946c00;

    padding: 6px 9px;

    border-radius: 20px;

    font-size: 9px;

    font-weight: 700;

}


.medicine-image-wrapper {

    height: 220px;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        linear-gradient(
            145deg,
            #f9fbfc,
            #f1f6f3
        );

    padding: 25px;

    overflow: hidden;

}


.medicine-image {

    width: 100%;

    height: 175px;

    object-fit: contain;

    transition: transform .3s ease;

}


.medicine-card:hover .medicine-image {

    transform: scale(1.06);

}


.medicine-card-body {

    padding: 20px;

    display: flex;

    flex-direction: column;

    flex: 1;

}


.medicine-category {

    display: inline-block;

    align-self: flex-start;

    color: #278c3c;

    background: #edf8ef;

    padding: 4px 9px;

    border-radius: 15px;

    font-size: 9px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .5px;

    margin-bottom: 9px;

}


.medicine-name {

    font-size: 18px;

    line-height: 1.3;

    font-weight: 700;

    margin: 0 0 5px;

    min-height: 46px;

}


.medicine-name a {

    color: #20252a;

    text-decoration: none;

}


.medicine-name a:hover {

    color: #278c3c;

}


.medicine-generic {

    color: #7b858d;

    font-size: 11px;

    line-height: 1.5;

    margin-bottom: 12px;

    min-height: 17px;

}


.medicine-info-row {

    display: flex;

    align-items: center;

    gap: 7px;

    color: #6f7880;

    font-size: 11px;

    margin-bottom: 10px;

}


.medicine-info-row i {

    color: #278c3c;

}


.medicine-composition {

    background: #f8fafb;

    border-radius: 8px;

    padding: 9px 10px;

    margin-bottom: 14px;

    font-size: 10px;

    line-height: 1.5;

}


.medicine-composition strong {

    display: block;

    color: #555f66;

    font-size: 9px;

    text-transform: uppercase;

    letter-spacing: .5px;

    margin-bottom: 2px;

}


.medicine-composition span {

    color: #7a848b;

    display: -webkit-box;

    -webkit-line-clamp: 2;

    -webkit-box-orient: vertical;

    overflow: hidden;

}


/* =====================================================
   PRICE
===================================================== */

.medicine-price-row {

    display: flex;

    align-items: center;

    justify-content: space-between;

    border-top: 1px solid #edf0f2;

    padding-top: 13px;

    margin-top: auto;

}


.medicine-price {

    display: flex;

    align-items: center;

    gap: 8px;

}


.medicine-mrp {

    color: #9ca3a8;

    text-decoration: line-through;

    font-size: 11px;

}


.medicine-selling-price {

    color: #278c3c;

    font-size: 21px;

    font-weight: 800;

}


.save-price {

    background: #edf8ef;

    color: #278c3c;

    padding: 4px 7px;

    border-radius: 6px;

    font-size: 9px;

    font-weight: 700;

}


/* =====================================================
   STATUS
===================================================== */

.medicine-status {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 8px;

    padding: 12px 0;

    font-size: 10px;

}


.status-in {

    color: #278c3c;

    font-weight: 700;

}


.status-low {

    color: #b27800;

    font-weight: 700;

}


.status-out {

    color: #d9534f;

    font-weight: 700;

}


.status-expired {

    color: #c9302c;

    font-weight: 700;

}


.status-expiring {

    color: #c28a00;

    font-weight: 700;

}


.expiry-date {

    color: #9ba2a7;

    font-size: 9px;

}


/* =====================================================
   ACTIONS
===================================================== */

.medicine-actions {

    display: flex;

    flex-direction: column;

    gap: 8px;

}


.add-cart-form {

    margin: 0;

}


.btn-add-cart {

    width: 100%;

    border: 0;

    border-radius: 9px;

    padding: 11px 15px;

    background: #278c3c;

    color: #fff;

    font-size: 12px;

    font-weight: 700;

    cursor: pointer;

    transition: .2s ease;

}


.btn-add-cart:hover {

    background: #176b2b;

    color: #fff;

}


.btn-add-cart.disabled,
.btn-add-cart:disabled {

    background: #adb5bd;

    cursor: not-allowed;

}


.medicine-details-link {

    display: flex;

    justify-content: center;

    align-items: center;

    gap: 5px;

    color: #68727a;

    font-size: 10px;

    font-weight: 700;

    text-decoration: none;

}


.medicine-details-link:hover {

    color: #278c3c;

    text-decoration: none;

}


/* =====================================================
   UNAVAILABLE
===================================================== */

.medicine-unavailable {

    opacity: .88;

}


.medicine-unavailable .medicine-image {

    filter: grayscale(.35);

}


/* =====================================================
   EMPTY STATE
===================================================== */

.medicine-empty-state {

    text-align: center;

    padding: 75px 25px;

    border-radius: 18px;

    background: #f8faf9;

    border: 1px dashed #d7e5da;

}


.empty-icon {

    width: 75px;

    height: 75px;

    border-radius: 50%;

    background: #e8f5eb;

    color: #278c3c;

    display: flex;

    align-items: center;

    justify-content: center;

    margin: 0 auto 20px;

    font-size: 28px;

}


.medicine-empty-state h3 {

    font-size: 22px;

    font-weight: 700;

    margin-bottom: 8px;

}


.medicine-empty-state p {

    color: #7c858d;

    font-size: 13px;

    margin-bottom: 25px;

}


.empty-actions {

    display: flex;

    justify-content: center;

    gap: 10px;

    flex-wrap: wrap;

}


/* =====================================================
   PRESCRIPTION CTA
===================================================== */

.medicine-prescription-cta {

    padding: 20px 0 55px;

}


.prescription-cta-card {

    position: relative;

    overflow: hidden;

    background:
        linear-gradient(
            135deg,
            #176b2b,
            #278c3c
        );

    color: #fff;

    border-radius: 20px;

    padding: 35px 40px;

    box-shadow:
        0 15px 35px rgba(39,140,60,.20);

}


.prescription-cta-card:after {

    content: "";

    position: absolute;

    width: 230px;

    height: 230px;

    border-radius: 50%;

    right: -90px;

    top: -100px;

    background: rgba(255,255,255,.08);

}


.cta-icon {

    width: 58px;

    height: 58px;

    border-radius: 15px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: rgba(255,255,255,.14);

    font-size: 25px;

    float: left;

    margin-right: 18px;

}


.cta-content {

    position: relative;

    z-index: 2;

}


.cta-content > span {

    font-size: 10px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: 1px;

    opacity: .8;

}


.cta-content h2 {

    color: #fff;

    font-size: 25px;

    font-weight: 800;

    margin: 4px 0 7px;

}


.cta-content p {

    color: rgba(255,255,255,.82);

    font-size: 12px;

    line-height: 1.7;

    margin: 0;

    max-width: 650px;

}


.cta-button {

    position: relative;

    z-index: 3;

    display: inline-flex;

    align-items: center;

    gap: 8px;

    background: #fff;

    color: #176b2b;

    border-radius: 9px;

    padding: 13px 20px;

    font-size: 11px;

    font-weight: 800;

    text-decoration: none;

    transition: .2s ease;

}


.cta-button:hover {

    color: #176b2b;

    text-decoration: none;

    transform: translateY(-2px);

}


/* =====================================================
   TRUST
===================================================== */

.medicine-trust-section {

    padding: 45px 0;

    background: #f7f9f8;

    border-top: 1px solid #edf0ee;

}


.trust-item {

    display: flex;

    align-items: flex-start;

    gap: 13px;

}


.trust-icon {

    width: 44px;

    height: 44px;

    min-width: 44px;

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #e7f5ea;

    color: #278c3c;

    font-size: 17px;

}


.trust-item h4 {

    font-size: 14px;

    font-weight: 700;

    margin: 0 0 4px;

}


.trust-item p {

    color: #7d858b;

    font-size: 11px;

    line-height: 1.6;

    margin: 0;

}


/* =====================================================
   MOBILE
===================================================== */

@media (max-width: 991px) {

    .medicines-hero {

        min-height: 290px;

    }


    .medicines-hero h1 {

        font-size: 42px;

    }


    .medicine-search-section {

        margin-top: -30px;

    }


    .medicine-section-header {

        align-items: flex-start;

    }

}


@media (max-width: 767px) {

    .medicines-hero {

        min-height: 260px;

    }


    .medicines-hero-content {

        padding: 40px 15px;

    }


    .medicines-hero h1 {

        font-size: 34px;

    }


    .medicines-hero p {

        font-size: 13px;

    }


    .medicine-search-card {

        padding: 20px;

    }


    .search-heading {

        margin-bottom: 15px;

    }


    .medicine-search-input input {

        font-size: 12px;

        padding: 13px 8px;

    }


    .search-button {

        padding: 13px 15px;

        font-size: 11px;

    }


    .medicine-section-header {

        display: block;

    }


    .medicine-count {

        display: inline-block;

        margin-top: 15px;

    }


    .medicine-section-header h2 {

        font-size: 25px;

    }


    .medicine-image-wrapper {

        height: 200px;

    }


    .medicine-image {

        height: 155px;

    }


    .prescription-cta-card {

        padding: 28px 22px;

    }


    .cta-icon {

        float: none;

        margin: 0 0 15px;

    }


    .cta-content h2 {

        font-size: 21px;

    }


    .cta-button {

        width: 100%;

        justify-content: center;

    }

}


@media (max-width: 480px) {

    .medicines-hero h1 {

        font-size: 30px;

    }


    .medicine-search-input {

        flex-wrap: wrap;

    }


    .medicine-search-input > i {

        margin-left: 13px;

    }


    .medicine-search-input input {

        width: calc(100% - 50px);

    }


    .search-button {

        width: 100%;

        margin-top: 4px;

        border-radius: 0;

    }


    .medicine-card-body {

        padding: 17px;

    }

}

</style>


<!-- =====================================================
     CART BUTTON JS
===================================================== -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const forms =
            document.querySelectorAll(
                '.add-cart-form'
            );


        forms.forEach(function (form) {

            form.addEventListener(
                'submit',
                function () {

                    const button =
                        form.querySelector(
                            '.btn-add-cart'
                        );


                    if (button) {

                        button.disabled = true;

                        button.innerHTML =
                            '<i class="icon-spinner"></i> Adding...';

                    }

                }
            );

        });


        // =================================================
        // SEARCH INPUT
        // =================================================

        const searchInput =
            document.querySelector(
                '.medicine-search-input input'
            );


        if (searchInput) {

            searchInput.addEventListener(
                'keydown',
                function (event) {

                    if (
                        event.key === 'Escape'
                    ) {

                        this.value = '';

                    }

                }
            );

        }

    }
);

</script>


<?php

require_once "includes/footer.php";

?>
```
