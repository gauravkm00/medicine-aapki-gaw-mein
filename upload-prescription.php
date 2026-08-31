<?php

session_start();

$page_title = "Upload Prescription | Medicine Aapki Gaw Mein";

require_once "config/database.php";


// =====================================================
// LOGIN / REGISTER REQUIRED
// =====================================================

if (
    !isset($_SESSION['user_id']) ||
    (int) $_SESSION['user_id'] <= 0
) {

    $_SESSION['redirect_after_login'] =
        'upload-prescription.php';

    $_SESSION['login_required_message'] =
        "Prescription upload karne ke liye pehle login ya register karein.";

    header("Location: login.php");

    exit;
}


// =====================================================
// USER ID
// =====================================================

$user_id = (int) $_SESSION['user_id'];


// =====================================================
// VARIABLES
// =====================================================

$error = "";

$success = "";


// =====================================================
// UPLOAD DIRECTORY
// =====================================================

$upload_dir = __DIR__ . "/uploads/prescriptions/";


// =====================================================
// CREATE UPLOAD DIRECTORY
// =====================================================

if (!is_dir($upload_dir)) {

    if (!mkdir($upload_dir, 0755, true)) {

        $error =
            "Prescription upload folder create nahi ho saka.";
    }
}


// =====================================================
// FORM SUBMIT
// =====================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $error === ''
) {


    // =================================================
    // CHECK FILE
    // =================================================

    if (
        !isset($_FILES['prescription']) ||
        !is_array($_FILES['prescription'])
    ) {

        $error =
            "Please prescription file select karein.";

    }

    elseif (
        $_FILES['prescription']['error']
        === UPLOAD_ERR_NO_FILE
    ) {

        $error =
            "Please prescription file select karein.";

    }

    elseif (
        $_FILES['prescription']['error']
        !== UPLOAD_ERR_OK
    ) {

        $error =
            "Prescription upload failed. Please dobara try karein.";

    }

    else {


        // =================================================
        // FILE DATA
        // =================================================

        $original_file_name =
            trim(
                $_FILES['prescription']['name']
            );


        $tmp_name =
            $_FILES['prescription']['tmp_name'];


        $file_size =
            (int) $_FILES['prescription']['size'];


        // =================================================
        // MAXIMUM FILE SIZE
        // =================================================

        $max_file_size =
            5 * 1024 * 1024;


        if ($file_size <= 0) {

            $error =
                "Invalid prescription file.";

        }

        elseif ($file_size > $max_file_size) {

            $error =
                "Prescription file maximum 5 MB ka hona chahiye.";

        }

        else {


            // =================================================
            // MIME TYPE
            // =================================================

            if (!class_exists('finfo')) {

                $error =
                    "Server par FileInfo extension enabled nahi hai.";

            }

            else {

                $finfo =
                    new finfo(
                        FILEINFO_MIME_TYPE
                    );


                $file_type =
                    $finfo->file(
                        $tmp_name
                    );


                // =================================================
                // ALLOWED MIME TYPES
                // =================================================

                $allowed_types = [

                    'image/jpeg' => 'jpg',

                    'image/png' => 'png',

                    'image/webp' => 'webp',

                    'application/pdf' => 'pdf'

                ];


                if (
                    !isset(
                        $allowed_types[$file_type]
                    )
                ) {

                    $error =
                        "Sirf JPG, PNG, WEBP ya PDF prescription upload karein.";

                }

                else {


                    // =================================================
                    // EXTENSION
                    // =================================================

                    $extension =
                        $allowed_types[$file_type];


                    // =================================================
                    // SECURE RANDOM FILE NAME
                    // =================================================

                    try {

                        $random_name =
                            bin2hex(
                                random_bytes(20)
                            );

                    }

                    catch (Exception $e) {

                        $random_name =
                            sha1(
                                uniqid(
                                    '',
                                    true
                                )
                            );

                    }


                    $file_name =
                        "prescription_"
                        . $user_id
                        . "_"
                        . $random_name
                        . "."
                        . $extension;


                    // =================================================
                    // FULL FILE PATH
                    // =================================================

                    $destination =
                        $upload_dir
                        . $file_name;


                    // =================================================
                    // MOVE FILE
                    // =================================================

                    if (
                        !move_uploaded_file(
                            $tmp_name,
                            $destination
                        )
                    ) {

                        $error =
                            "Prescription file save nahi ho saki.";

                    }

                    else {


                        // =================================================
                        // DATABASE INSERT
                        // =================================================

                        $sql = "

                            INSERT INTO prescriptions

                            (
                                user_id,
                                file_name,
                                original_file_name,
                                file_type,
                                file_size,
                                status
                            )

                            VALUES

                            (
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                'pending'
                            )

                        ";


                        $stmt =
                            mysqli_prepare(
                                $conn,
                                $sql
                            );


                        if (!$stmt) {


                            // =========================================
                            // DELETE FILE
                            // =========================================

                            if (
                                file_exists(
                                    $destination
                                )
                            ) {

                                unlink(
                                    $destination
                                );

                            }


                            $error =
                                "Database query prepare failed: "
                                . mysqli_error($conn);

                        }

                        else {


                            // =========================================
                            // BIND
                            // =========================================

                            mysqli_stmt_bind_param(
                                $stmt,
                                "isssi",
                                $user_id,
                                $file_name,
                                $original_file_name,
                                $file_type,
                                $file_size
                            );


                            // =========================================
                            // EXECUTE
                            // =========================================

                            if (
                                mysqli_stmt_execute(
                                    $stmt
                                )
                            ) {

                                $success =
                                    "Prescription successfully upload ho gayi hai. "
                                    . "Hamari pharmacist team ise review karegi.";

                            }

                            else {


                                // =====================================
                                // DELETE FILE
                                // =====================================

                                if (
                                    file_exists(
                                        $destination
                                    )
                                ) {

                                    unlink(
                                        $destination
                                    );

                                }


                                $error =
                                    "Prescription database mein save nahi ho saki: "
                                    . mysqli_stmt_error(
                                        $stmt
                                    );

                            }


                            mysqli_stmt_close(
                                $stmt
                            );

                        }

                    }

                }

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

/* =========================================================
   PRESCRIPTION PAGE
========================================================= */

.prescription-page {

    --green: #159447;

    --dark-green: #087333;

    --deep-green: #075c2a;

    --light-green: #effaf3;

    --soft-green: #f7fbf8;

    --dark: #172b25;

    --text: #65736d;

    --border: #e1ebe5;

    background: #fff;

    overflow: hidden;

}


/* =========================================================
   HERO
========================================================= */

.prescription-hero {

    min-height: 390px;

    position: relative;

    display: flex;

    align-items: center;

    background:

        linear-gradient(
            90deg,
            rgba(3,39,22,.94),
            rgba(4,78,39,.76),
            rgba(4,60,31,.30)
        ),

        url('assets/images/hero_1.jpg')
        center center / cover no-repeat;

}


.prescription-hero-content {

    max-width: 700px;

    padding: 65px 0;

}


.prescription-badge {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 9px 16px;

    margin-bottom: 18px;

    border-radius: 50px;

    color: #fff;

    background: rgba(255,255,255,.12);

    border: 1px solid rgba(255,255,255,.22);

    font-size: 10px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: 1.3px;

}


.prescription-hero h1 {

    color: #fff;

    font-size: 50px;

    line-height: 1.12;

    font-weight: 900;

    margin-bottom: 15px;

}


.prescription-hero p {

    max-width: 620px;

    color: rgba(255,255,255,.86);

    font-size: 14px;

    line-height: 1.85;

    margin-bottom: 0;

}


/* =========================================================
   MAIN SECTION
========================================================= */

.prescription-section {

    padding: 75px 0 85px;

    background: #fff;

}


/* =========================================================
   UPLOAD CARD
========================================================= */

.upload-card {

    padding: 38px;

    background: #fff;

    border: 1px solid var(--border);

    border-radius: 22px;

    box-shadow:
        0 15px 45px rgba(18,67,40,.07);

}


.upload-card-header {

    margin-bottom: 28px;

}


.upload-eyebrow {

    display: inline-block;

    color: var(--green);

    font-size: 10px;

    font-weight: 900;

    text-transform: uppercase;

    letter-spacing: 1.4px;

    margin-bottom: 8px;

}


.upload-card-header h2 {

    color: var(--dark);

    font-size: 29px;

    font-weight: 900;

    margin-bottom: 9px;

}


.upload-card-header p {

    color: var(--text);

    font-size: 12px;

    line-height: 1.75;

    margin-bottom: 0;

}


/* =========================================================
   ALERTS
========================================================= */

.prescription-alert {

    padding: 15px 17px;

    border-radius: 11px;

    margin-bottom: 20px;

    font-size: 11px;

    line-height: 1.65;

}


.prescription-alert strong {

    font-size: 12px;

}


.prescription-alert-success {

    background: #eefaf2;

    border: 1px solid #cdebd7;

    color: #176b37;

}


.prescription-alert-error {

    background: #fff2f2;

    border: 1px solid #f3cccc;

    color: #9c3030;

}


/* =========================================================
   DROP AREA
========================================================= */

.upload-drop-area {

    position: relative;

    min-height: 230px;

    padding: 35px 25px;

    border: 2px dashed #b9d9c5;

    border-radius: 17px;

    background:
        linear-gradient(
            145deg,
            #f7fcf8,
            #ffffff
        );

    text-align: center;

    cursor: pointer;

    transition: .25s ease;

}


.upload-drop-area:hover,
.upload-drop-area.drag-active {

    border-color: var(--green);

    background: #f0faf3;

    transform: translateY(-2px);

}


.upload-drop-icon {

    width: 68px;

    height: 68px;

    margin: 0 auto 15px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 19px;

    background: var(--light-green);

    color: var(--green);

    font-size: 29px;

}


.upload-drop-area h3 {

    color: var(--dark);

    font-size: 17px;

    font-weight: 900;

    margin-bottom: 6px;

}


.upload-drop-area p {

    color: var(--text);

    font-size: 11px;

    margin-bottom: 14px;

}


.upload-browse {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding: 10px 18px;

    border-radius: 8px;

    background: var(--green);

    color: #fff;

    font-size: 11px;

    font-weight: 800;

}


.upload-browse:hover {

    color: #fff;

}


.upload-file-input {

    position: absolute;

    width: 1px;

    height: 1px;

    opacity: 0;

    pointer-events: none;

}


/* =========================================================
   FILE PREVIEW
========================================================= */

.file-preview {

    display: none;

    align-items: center;

    gap: 13px;

    margin-top: 15px;

    padding: 13px 15px;

    border-radius: 11px;

    background: #f3f9f5;

    border: 1px solid #dcebe1;

}


.file-preview.active {

    display: flex;

}


.file-preview-icon {

    width: 40px;

    height: 40px;

    min-width: 40px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 10px;

    background: #fff;

    color: var(--green);

    font-size: 18px;

}


.file-preview-content {

    flex: 1;

    min-width: 0;

}


.file-preview-name {

    display: block;

    color: var(--dark);

    font-size: 11px;

    font-weight: 800;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;

}


.file-preview-size {

    color: #839088;

    font-size: 9px;

}


.file-remove {

    border: 0;

    background: transparent;

    color: #b14a4a;

    font-size: 16px;

    cursor: pointer;

}


/* =========================================================
   INFO STRIP
========================================================= */

.upload-info-strip {

    display: flex;

    flex-wrap: wrap;

    gap: 10px;

    margin-top: 20px;

}


.upload-info-item {

    flex: 1;

    min-width: 140px;

    padding: 12px;

    border-radius: 10px;

    background: #f7faf8;

    border: 1px solid #e7eee9;

}


.upload-info-item strong {

    display: block;

    color: var(--dark);

    font-size: 10px;

    font-weight: 900;

    margin-bottom: 3px;

}


.upload-info-item span {

    color: var(--text);

    font-size: 9px;

}


/* =========================================================
   SUBMIT BUTTON
========================================================= */

.upload-submit {

    width: 100%;

    min-height: 52px;

    margin-top: 22px;

    border: 0;

    border-radius: 10px;

    background:
        linear-gradient(
            135deg,
            var(--green),
            var(--dark-green)
        );

    color: #fff;

    font-size: 12px;

    font-weight: 900;

    transition: .25s ease;

}


.upload-submit:hover {

    transform: translateY(-2px);

    box-shadow:
        0 10px 25px rgba(8,115,51,.22);

}


.upload-submit:disabled {

    opacity: .75;

    cursor: not-allowed;

    transform: none;

}


/* =========================================================
   SECONDARY BUTTON
========================================================= */

.browse-button {

    display: flex;

    align-items: center;

    justify-content: center;

    width: 100%;

    min-height: 48px;

    margin-top: 10px;

    border: 1px solid #cfe1d5;

    border-radius: 10px;

    background: #fff;

    color: var(--dark-green) !important;

    font-size: 11px;

    font-weight: 800;

    text-decoration: none !important;

    transition: .2s ease;

}


.browse-button:hover {

    background: var(--light-green);

}


/* =========================================================
   SIDE PANEL
========================================================= */

.prescription-side {

    height: 100%;

    padding: 35px;

    border-radius: 22px;

    background:
        linear-gradient(
            145deg,
            #effaf3,
            #ffffff
        );

    border: 1px solid var(--border);

}


.prescription-side-badge {

    width: 55px;

    height: 55px;

    display: flex;

    align-items: center;

    justify-content: center;

    margin-bottom: 18px;

    border-radius: 16px;

    background: #fff;

    color: var(--green);

    font-size: 24px;

    box-shadow:
        0 8px 25px rgba(0,0,0,.05);

}


.prescription-side h3 {

    color: var(--dark);

    font-size: 24px;

    font-weight: 900;

    line-height: 1.3;

    margin-bottom: 10px;

}


.prescription-side > p {

    color: var(--text);

    font-size: 12px;

    line-height: 1.8;

    margin-bottom: 25px;

}


/* =========================================================
   STEPS
========================================================= */

.prescription-step {

    display: flex;

    gap: 13px;

    padding: 15px 0;

    border-bottom: 1px solid #dce9e0;

}


.prescription-step:last-child {

    border-bottom: 0;

}


.step-number {

    width: 31px;

    height: 31px;

    min-width: 31px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 9px;

    background: #fff;

    color: var(--green);

    font-size: 11px;

    font-weight: 900;

}


.step-content strong {

    display: block;

    color: var(--dark);

    font-size: 11px;

    font-weight: 900;

    margin-bottom: 3px;

}


.step-content span {

    display: block;

    color: var(--text);

    font-size: 10px;

    line-height: 1.6;

}


/* =========================================================
   SAFETY NOTE
========================================================= */

.safety-note {

    margin-top: 22px;

    padding: 16px;

    border-radius: 12px;

    background: #fff;

    border: 1px solid #dfeae3;

}


.safety-note-title {

    display: flex;

    align-items: center;

    gap: 7px;

    color: var(--dark);

    font-size: 10px;

    font-weight: 900;

    margin-bottom: 6px;

}


.safety-note p {

    color: var(--text);

    font-size: 9px;

    line-height: 1.7;

    margin: 0;

}


/* =========================================================
   BOTTOM FEATURES
========================================================= */

.prescription-features {

    padding: 75px 0;

    background: #f8fbf9;

}


.feature-heading {

    text-align: center;

    margin-bottom: 38px;

}


.feature-heading span {

    display: inline-block;

    color: var(--green);

    font-size: 10px;

    font-weight: 900;

    text-transform: uppercase;

    letter-spacing: 1.4px;

    margin-bottom: 7px;

}


.feature-heading h2 {

    color: var(--dark);

    font-size: 30px;

    font-weight: 900;

    margin-bottom: 8px;

}


.feature-heading p {

    color: var(--text);

    font-size: 12px;

}


/* FEATURE CARD */

.prescription-feature-card {

    height: 100%;

    padding: 25px 21px;

    text-align: center;

    background: #fff;

    border: 1px solid var(--border);

    border-radius: 16px;

    transition: .3s ease;

}


.prescription-feature-card:hover {

    transform: translateY(-6px);

    box-shadow:
        0 14px 35px rgba(0,0,0,.07);

}


.prescription-feature-icon {

    width: 54px;

    height: 54px;

    margin: 0 auto 14px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 15px;

    background: var(--light-green);

    color: var(--green);

    font-size: 22px;

}


.prescription-feature-card h3 {

    color: var(--dark);

    font-size: 15px;

    font-weight: 900;

    margin-bottom: 7px;

}


.prescription-feature-card p {

    color: var(--text);

    font-size: 10px;

    line-height: 1.7;

    margin: 0;

}


/* =========================================================
   CTA
========================================================= */

.prescription-cta {

    padding: 0 0 75px;

    background: #f8fbf9;

}


.prescription-cta-box {

    padding: 45px 30px;

    border-radius: 22px;

    text-align: center;

    background:
        linear-gradient(
            135deg,
            #087333,
            #159447
        );

}


.prescription-cta-box h2 {

    color: #fff;

    font-size: 28px;

    font-weight: 900;

    margin-bottom: 9px;

}


.prescription-cta-box p {

    max-width: 600px;

    margin: auto;

    color: rgba(255,255,255,.83);

    font-size: 12px;

    line-height: 1.8;

}


.prescription-cta-button {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    margin-top: 20px;

    padding: 12px 22px;

    border-radius: 8px;

    background: #fff;

    color: var(--dark-green) !important;

    font-size: 11px;

    font-weight: 900;

    text-decoration: none !important;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 991px) {

    .prescription-hero h1 {

        font-size: 42px;

    }


    .prescription-side {

        margin-top: 30px;

    }

}


@media (max-width: 767px) {

    .prescription-hero {

        min-height: 370px;

        text-align: center;

    }


    .prescription-hero-content {

        padding: 45px 15px;

    }


    .prescription-hero h1 {

        font-size: 34px;

    }


    .prescription-hero p {

        font-size: 12px;

    }


    .prescription-section {

        padding: 50px 0 60px;

    }


    .upload-card {

        padding: 25px 19px;

        border-radius: 17px;

    }


    .upload-card-header h2 {

        font-size: 25px;

    }


    .prescription-side {

        padding: 27px 20px;

    }


    .upload-info-strip {

        display: block;

    }


    .upload-info-item {

        margin-bottom: 8px;

    }


    .feature-heading h2 {

        font-size: 26px;

    }

}


@media (max-width: 480px) {

    .prescription-hero h1 {

        font-size: 29px;

    }


    .upload-drop-area {

        min-height: 210px;

        padding: 28px 16px;

    }


    .upload-drop-icon {

        width: 58px;

        height: 58px;

    }

}

</style>

<div class="prescription-page">

<!-- =========================================================
     HERO
========================================================= -->

<section class="prescription-hero">

```
<div class="container">

    <div class="prescription-hero-content">

        <div class="prescription-badge">

            <span>●</span>

            Secure Prescription Service

        </div>


        <h1>

            Upload your
            <br>
            prescription

        </h1>


        <p>

            Doctor ki prescription securely upload karein
            aur apni medicine requirement submit karein.
            Hamari team aapki prescription review karegi.

        </p>

    </div>

</div>
```

</section>

<!-- =========================================================
     MAIN UPLOAD
========================================================= -->

<section class="prescription-section">

```
<div class="container">

    <div class="row align-items-stretch">


        <!-- =================================================
             UPLOAD FORM
        ================================================= -->

        <div class="col-lg-7">

            <div class="upload-card">


                <div class="upload-card-header">

                    <span class="upload-eyebrow">

                        Prescription Upload

                    </span>


                    <h2>

                        Upload your prescription

                    </h2>


                    <p>

                        Prescription ki clear photo ya PDF
                        select karke upload karein.

                    </p>

                </div>


                <!-- =========================================
                     SUCCESS
                ========================================= -->

                <?php if ($success !== ''): ?>

                    <div class="
                        prescription-alert
                        prescription-alert-success
                    ">

                        <strong>

                            ✓ Upload Successful

                        </strong>

                        <br>

                        <?= htmlspecialchars(
                            $success,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                <?php endif; ?>


                <!-- =========================================
                     ERROR
                ========================================= -->

                <?php if ($error !== ''): ?>

                    <div class="
                        prescription-alert
                        prescription-alert-error
                    ">

                        <strong>

                            ! Upload Failed

                        </strong>

                        <br>

                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                <?php endif; ?>


                <!-- =========================================
                     FORM
                ========================================= -->

                <form
                    method="POST"
                    action="upload-prescription.php"
                    enctype="multipart/form-data"
                    autocomplete="off"
                    id="prescriptionForm"
                >


                    <!-- =====================================
                         DROP AREA
                    ===================================== -->

                    <div
                        class="upload-drop-area"
                        id="uploadDropArea"
                    >

                        <div class="upload-drop-icon">

                            ↑

                        </div>


                        <h3>

                            Drag & Drop your prescription

                        </h3>


                        <p>

                            or click below to choose a file

                        </p>


                        <span class="upload-browse">

                            Choose Prescription

                        </span>


                        <input
                            type="file"
                            id="prescription"
                            name="prescription"
                            class="upload-file-input"
                            accept="
                                image/jpeg,
                                image/png,
                                image/webp,
                                application/pdf
                            "
                            required
                        >

                    </div>


                    <!-- =====================================
                         FILE PREVIEW
                    ===================================== -->

                    <div
                        class="file-preview"
                        id="filePreview"
                    >

                        <div class="file-preview-icon">

                            📄

                        </div>


                        <div class="file-preview-content">

                            <span
                                class="file-preview-name"
                                id="fileName"
                            >
                            </span>


                            <span
                                class="file-preview-size"
                                id="fileSize"
                            >
                            </span>

                        </div>


                        <button
                            type="button"
                            class="file-remove"
                            id="removeFile"
                            aria-label="Remove file"
                        >

                            ×

                        </button>

                    </div>


                    <!-- =====================================
                         FILE INFO
                    ===================================== -->

                    <div class="upload-info-strip">


                        <div class="upload-info-item">

                            <strong>

                                File Types

                            </strong>

                            <span>

                                JPG, PNG, WEBP, PDF

                            </span>

                        </div>


                        <div class="upload-info-item">

                            <strong>

                                Maximum Size

                            </strong>

                            <span>

                                5 MB

                            </span>

                        </div>


                        <div class="upload-info-item">

                            <strong>

                                Status

                            </strong>

                            <span>

                                Secure & Private

                            </span>

                        </div>


                    </div>


                    <!-- =====================================
                         IMPORTANT INFORMATION
                    ===================================== -->

                    <div
                        class="prescription-alert prescription-alert-success"
                        style="margin-top:20px;margin-bottom:0;"
                    >

                        <strong>

                            Important Information

                        </strong>


                        <ul
                            style="
                                margin:7px 0 0;
                                padding-left:18px;
                            "
                        >

                            <li>

                                Prescription clear aur readable honi chahiye.

                            </li>


                            <li>

                                Sirf valid doctor prescription upload karein.

                            </li>


                            <li>

                                Pharmacist team prescription review karegi.

                            </li>


                            <li>

                                Medicine requirement approval ke baad process
                                ki ja sakti hai.

                            </li>

                        </ul>

                    </div>


                    <!-- =====================================
                         SUBMIT
                    ===================================== -->

                    <button
                        type="submit"
                        id="uploadPrescriptionBtn"
                        class="upload-submit"
                    >

                        Upload Prescription

                        <span style="margin-left:7px;">

                            →

                        </span>

                    </button>


                    <!-- =====================================
                         MEDICINES
                    ===================================== -->

                    <a
                        href="medicines.php"
                        class="browse-button"
                    >

                        Browse Medicines

                        <span style="margin-left:7px;">

                            →

                        </span>

                    </a>


                </form>

            </div>

        </div>


        <!-- =================================================
             RIGHT SIDE
        ================================================= -->

        <div class="col-lg-5">

            <div class="prescription-side">


                <div class="prescription-side-badge">

                    💊

                </div>


                <h3>

                    How prescription
                    upload works

                </h3>


                <p>

                    Bas kuch simple steps mein apni prescription
                    requirement submit karein.

                </p>


                <!-- STEP 1 -->

                <div class="prescription-step">

                    <div class="step-number">

                        01

                    </div>


                    <div class="step-content">

                        <strong>

                            Prescription Ready Karein

                        </strong>

                        <span>

                            Doctor ki prescription ki clear
                            image ya PDF ready rakhein.

                        </span>

                    </div>

                </div>


                <!-- STEP 2 -->

                <div class="prescription-step">

                    <div class="step-number">

                        02

                    </div>


                    <div class="step-content">

                        <strong>

                            Upload Karein

                        </strong>

                        <span>

                            Prescription select karke
                            secure upload karein.

                        </span>

                    </div>

                </div>


                <!-- STEP 3 -->

                <div class="prescription-step">

                    <div class="step-number">

                        03

                    </div>


                    <div class="step-content">

                        <strong>

                            Pharmacist Review

                        </strong>

                        <span>

                            Hamari team uploaded prescription
                            ko review karegi.

                        </span>

                    </div>

                </div>


                <!-- STEP 4 -->

                <div class="prescription-step">

                    <div class="step-number">

                        04

                    </div>


                    <div class="step-content">

                        <strong>

                            Medicine Requirement

                        </strong>

                        <span>

                            Approval ke baad requirement
                            process ki ja sakti hai.

                        </span>

                    </div>

                </div>


                <!-- =========================================
                     SAFETY
                ========================================= -->

                <div class="safety-note">

                    <div class="safety-note-title">

                        <span>

                            🛡️

                        </span>

                        Your information matters

                    </div>


                    <p>

                        Prescription ko sirf medicine requirement
                        process aur review ke purpose ke liye
                        submit karein. Clear aur valid document
                        upload karein.

                    </p>

                </div>


            </div>

        </div>


    </div>

</div>
```

</section>

<!-- =========================================================
     FEATURES
========================================================= -->

<section class="prescription-features">

```
<div class="container">


    <div class="feature-heading">

        <span>

            Simple • Convenient • Local

        </span>


        <h2>

            A better way to submit prescriptions

        </h2>


        <p>

            Medicine Aapki Gaw Mein ke saath
            prescription process ko simple banayein.

        </p>

    </div>


    <div class="row">


        <!-- =============================================
             FEATURE 1
        ============================================== -->

        <div class="col-md-6 col-lg-3 mb-4">

            <div class="prescription-feature-card">

                <div class="prescription-feature-icon">

                    🔒

                </div>


                <h3>

                    Secure Upload

                </h3>


                <p>

                    Prescription ko secure manner mein
                    submit karein.

                </p>

            </div>

        </div>


        <!-- =============================================
             FEATURE 2
        ============================================== -->

        <div class="col-md-6 col-lg-3 mb-4">

            <div class="prescription-feature-card">

                <div class="prescription-feature-icon">

                    ⚡

                </div>


                <h3>

                    Easy Process

                </h3>


                <p>

                    Simple upload process ke saath
                    requirement submit karein.

                </p>

            </div>

        </div>


        <!-- =============================================
             FEATURE 3
        ============================================== -->

        <div class="col-md-6 col-lg-3 mb-4">

            <div class="prescription-feature-card">

                <div class="prescription-feature-icon">

                    👨‍⚕️

                </div>


                <h3>

                    Team Review

                </h3>


                <p>

                    Uploaded prescription ko team
                    review karegi.

                </p>

            </div>

        </div>


        <!-- =============================================
             FEATURE 4
        ============================================== -->

        <div class="col-md-6 col-lg-3 mb-4">

            <div class="prescription-feature-card">

                <div class="prescription-feature-icon">

                    🏠

                </div>


                <h3>

                    Local Support

                </h3>


                <p>

                    Forbesganj aur nearby customers
                    ke liye convenient support.

                </p>

            </div>

        </div>


    </div>

</div>
```

</section>

<!-- =========================================================
     FINAL CTA
========================================================= -->

<section class="prescription-cta">

```
<div class="container">

    <div class="prescription-cta-box">

        <h2>

            Looking for a medicine?

        </h2>


        <p>

            Available medicines browse karein ya
            prescription upload karke apni requirement
            submit karein.

        </p>


        <a
            href="medicines.php"
            class="prescription-cta-button"
        >

            Browse Medicines

            <span style="margin-left:7px;">

                →

            </span>

        </a>

    </div>

</div>
```

</section>

</div>

<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        const dropArea =
            document.getElementById(
                'uploadDropArea'
            );


        const fileInput =
            document.getElementById(
                'prescription'
            );


        const filePreview =
            document.getElementById(
                'filePreview'
            );


        const fileName =
            document.getElementById(
                'fileName'
            );


        const fileSize =
            document.getElementById(
                'fileSize'
            );


        const removeFile =
            document.getElementById(
                'removeFile'
            );


        const form =
            document.getElementById(
                'prescriptionForm'
            );


        const submitButton =
            document.getElementById(
                'uploadPrescriptionBtn'
            );


        const maxSize =
            5 * 1024 * 1024;


        const allowedTypes = [

            'image/jpeg',

            'image/png',

            'image/webp',

            'application/pdf'

        ];


        // =====================================================
        // FORMAT FILE SIZE
        // =====================================================

        function formatFileSize(bytes) {

            if (bytes < 1024) {

                return bytes + ' B';

            }


            if (bytes < 1024 * 1024) {

                return (
                    (bytes / 1024).toFixed(1)
                    + ' KB'
                );

            }


            return (
                (bytes / (1024 * 1024)).toFixed(2)
                + ' MB'
            );

        }


        // =====================================================
        // HANDLE FILE
        // =====================================================

        function handleFile(file) {


            if (!file) {

                return;

            }


            // ================================================
            // TYPE
            // ================================================

            if (
                !allowedTypes.includes(
                    file.type
                )
            ) {

                alert(
                    'Sirf JPG, PNG, WEBP ya PDF file upload karein.'
                );

                fileInput.value = '';

                filePreview.classList.remove(
                    'active'
                );

                return;

            }


            // ================================================
            // SIZE
            // ================================================

            if (
                file.size > maxSize
            ) {

                alert(
                    'Prescription file maximum 5 MB ka hona chahiye.'
                );

                fileInput.value = '';

                filePreview.classList.remove(
                    'active'
                );

                return;

            }


            // ================================================
            // PREVIEW
            // ================================================

            fileName.textContent =
                file.name;


            fileSize.textContent =
                formatFileSize(
                    file.size
                );


            filePreview.classList.add(
                'active'
            );

        }


        // =====================================================
        // CLICK UPLOAD AREA
        // =====================================================

        dropArea.addEventListener(
            'click',
            function () {

                fileInput.click();

            }
        );


        // =====================================================
        // FILE CHANGE
        // =====================================================

        fileInput.addEventListener(
            'change',
            function () {

                if (
                    this.files &&
                    this.files.length > 0
                ) {

                    handleFile(
                        this.files[0]
                    );

                }

            }
        );


        // =====================================================
        // DRAG OVER
        // =====================================================

        dropArea.addEventListener(
            'dragover',
            function (event) {

                event.preventDefault();

                dropArea.classList.add(
                    'drag-active'
                );

            }
        );


        // =====================================================
        // DRAG LEAVE
        // =====================================================

        dropArea.addEventListener(
            'dragleave',
            function () {

                dropArea.classList.remove(
                    'drag-active'
                );

            }
        );


        // =====================================================
        // DROP
        // =====================================================

        dropArea.addEventListener(
            'drop',
            function (event) {

                event.preventDefault();

                dropArea.classList.remove(
                    'drag-active'
                );


                const files =
                    event.dataTransfer.files;


                if (
                    files &&
                    files.length > 0
                ) {

                    fileInput.files =
                        files;

                    handleFile(
                        files[0]
                    );

                }

            }
        );


        // =====================================================
        // REMOVE FILE
        // =====================================================

        removeFile.addEventListener(
            'click',
            function (event) {

                event.stopPropagation();

                fileInput.value = '';

                filePreview.classList.remove(
                    'active'
                );

            }
        );


        // =====================================================
        // PREVENT DOUBLE SUBMIT
        // =====================================================

        form.addEventListener(
            'submit',
            function (event) {


                if (
                    !fileInput.files ||
                    fileInput.files.length === 0
                ) {

                    event.preventDefault();

                    alert(
                        'Please prescription file select karein.'
                    );

                    return;

                }


                const file =
                    fileInput.files[0];


                if (
                    !allowedTypes.includes(
                        file.type
                    )
                ) {

                    event.preventDefault();

                    alert(
                        'Sirf JPG, PNG, WEBP ya PDF file upload karein.'
                    );

                    return;

                }


                if (
                    file.size > maxSize
                ) {

                    event.preventDefault();

                    alert(
                        'Prescription file maximum 5 MB ka hona chahiye.'
                    );

                    return;

                }


                submitButton.disabled =
                    true;


                submitButton.innerHTML =
                    'Uploading Prescription...';

            }
        );

    }
);

</script>

<?php

require_once "includes/footer.php";

?>
