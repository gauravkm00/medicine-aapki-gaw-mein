<?php

session_start();

require_once "../config/database.php";


/* =====================================================
   ADMIN AUTHENTICATION
===================================================== */

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    strtolower($_SESSION['role']) !== 'admin'
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

$medicine = null;
$medicines = null;


/* =====================================================
   IMAGE CONFIGURATION
===================================================== */

/*
|--------------------------------------------------------------------------
| Physical upload directory
|--------------------------------------------------------------------------
*/

$medicineUploadDir =
    __DIR__ . "/../uploads/medicines/";


/*
|--------------------------------------------------------------------------
| Browser URL path
|--------------------------------------------------------------------------
|
| medicines.php is inside admin folder.
| uploads folder is one level outside admin.
|
*/

$medicineImageUrl =
    "../uploads/medicines/";


/* =====================================================
   HELPER - DELETE IMAGE
===================================================== */

function deleteMedicineImage($filename)
{
    global $medicineUploadDir;

    if (empty($filename)) {
        return;
    }

    $filename = basename($filename);

    $filePath =
        $medicineUploadDir . $filename;

    if (is_file($filePath)) {
        @unlink($filePath);
    }
}


/* =====================================================
   HELPER - UPLOAD IMAGE
===================================================== */

function uploadMedicineImage($file)
{
    global $medicineUploadDir;

    if (
        !isset($file) ||
        !isset($file['error'])
    ) {
        return null;
    }


    /* No file selected */

    if (
        $file['error'] ===
        UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }


    /* Upload error */

    if (
        $file['error'] !==
        UPLOAD_ERR_OK
    ) {
        return false;
    }


    /* Maximum 5 MB */

    if (
        (int)$file['size'] >
        5 * 1024 * 1024
    ) {
        return false;
    }


    /* Allowed extensions */

    $allowedExtensions = [
        'jpg',
        'jpeg',
        'png',
        'webp'
    ];


    $extension =
        strtolower(
            pathinfo(
                $file['name'],
                PATHINFO_EXTENSION
            )
        );


    if (
        !in_array(
            $extension,
            $allowedExtensions,
            true
        )
    ) {
        return false;
    }


    /* =================================================
       MIME VALIDATION
    ================================================= */

    $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    $mimeType = "";


    if (
        function_exists('finfo_open')
    ) {

        $finfo =
            finfo_open(
                FILEINFO_MIME_TYPE
            );

        if ($finfo) {

            $mimeType =
                finfo_file(
                    $finfo,
                    $file['tmp_name']
                );

            finfo_close($finfo);
        }
    }


    if (
        $mimeType !== '' &&
        !in_array(
            $mimeType,
            $allowedMimeTypes,
            true
        )
    ) {
        return false;
    }


    /* =================================================
       REAL IMAGE VALIDATION
    ================================================= */

    if (
        @getimagesize(
            $file['tmp_name']
        ) === false
    ) {
        return false;
    }


    /* =================================================
       CREATE DIRECTORY
    ================================================= */

    if (
        !is_dir(
            $medicineUploadDir
        )
    ) {

        if (
            !mkdir(
                $medicineUploadDir,
                0755,
                true
            )
        ) {
            return false;
        }
    }


    /* =================================================
       UNIQUE FILE NAME
    ================================================= */

    try {

        $randomPart =
            bin2hex(
                random_bytes(8)
            );

    } catch (Exception $e) {

        $randomPart =
            uniqid();
    }


    $filename =
        "medicine_" .
        date("YmdHis") .
        "_" .
        $randomPart .
        "." .
        $extension;


    $destination =
        $medicineUploadDir .
        $filename;


    /* =================================================
       MOVE FILE
    ================================================= */

    if (
        move_uploaded_file(
            $file['tmp_name'],
            $destination
        )
    ) {

        return $filename;
    }


    return false;
}


/* =====================================================
   POST REQUEST
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] ===
    'POST'
) {


    /* =================================================
       CSRF CHECK
    ================================================= */

    $postedToken =
        $_POST['csrf_token'] ?? '';


    if (
        empty($postedToken) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals(
            $_SESSION['csrf_token'],
            $postedToken
        )
    ) {

        $error =
            "Invalid request. Please refresh the page and try again.";

    } else {


        /* =================================================
           DELETE MEDICINE
        ================================================= */

        if (
            ($_POST['action'] ?? '') ===
            'delete'
        ) {

            $id =
                (int)(
                    $_POST['id'] ?? 0
                );


            if ($id <= 0) {

                $error =
                    "Invalid medicine ID.";

            } else {


                /* -----------------------------------------
                   GET OLD IMAGE
                ----------------------------------------- */

                $oldImage = null;


                $stmt =
                    $conn->prepare(
                        "SELECT image
                         FROM medicines
                         WHERE id = ?
                         LIMIT 1"
                    );


                if ($stmt) {

                    $stmt->bind_param(
                        "i",
                        $id
                    );

                    $stmt->execute();

                    $result =
                        $stmt->get_result();


                    if (
                        $result &&
                        $result->num_rows === 1
                    ) {

                        $row =
                            $result->fetch_assoc();

                        $oldImage =
                            $row['image'] ?? null;
                    }

                    $stmt->close();

                } else {

                    $error =
                        "Database error.";
                }


                /* -----------------------------------------
                   DELETE RECORD
                ----------------------------------------- */

                if ($error === '') {

                    $stmt =
                        $conn->prepare(
                            "DELETE FROM medicines
                             WHERE id = ?"
                        );


                    if (!$stmt) {

                        $error =
                            "Database error.";

                    } else {

                        $stmt->bind_param(
                            "i",
                            $id
                        );


                        if (
                            $stmt->execute()
                        ) {

                            /*
                             * Delete physical image
                             */

                            if (
                                !empty(
                                    $oldImage
                                )
                            ) {

                                deleteMedicineImage(
                                    $oldImage
                                );
                            }


                            $message =
                                "Medicine successfully deleted.";

                        } else {

                            $error =
                                "Medicine delete nahi ho payi.";
                        }


                        $stmt->close();
                    }
                }
            }
        }


        /* =================================================
           SAVE MEDICINE
        ================================================= */

        elseif (
            ($_POST['action'] ?? '') ===
            'save'
        ) {


            $id =
                (int)(
                    $_POST['id'] ?? 0
                );


            /* -----------------------------------------
               FORM DATA
            ----------------------------------------- */

            $name =
                trim(
                    $_POST['name'] ?? ''
                );


            $generic_name =
                trim(
                    $_POST['generic_name'] ?? ''
                );


            $manufacturer =
                trim(
                    $_POST['manufacturer'] ?? ''
                );


            $category =
                trim(
                    $_POST['category'] ?? ''
                );


            $composition =
                trim(
                    $_POST['composition'] ?? ''
                );


            $description =
                trim(
                    $_POST['description'] ?? ''
                );


            $batch_number =
                trim(
                    $_POST['batch_number'] ?? ''
                );


            $expiry_date =
                trim(
                    $_POST['expiry_date'] ?? ''
                );


            $mrp =
                (float)(
                    $_POST['mrp'] ?? 0
                );


            $selling_price =
                (float)(
                    $_POST['selling_price'] ?? 0
                );


            $stock_quantity =
                (int)(
                    $_POST['stock_quantity'] ?? 0
                );


            $prescription_required =
                isset(
                    $_POST['prescription_required']
                )
                    ? 1
                    : 0;


            $status =
                isset(
                    $_POST['status']
                )
                    ? 1
                    : 0;


            /* -----------------------------------------
               VALIDATION
            ----------------------------------------- */

            if (
                $name === ''
            ) {

                $error =
                    "Medicine name required hai.";

            } elseif (
                strlen($name) > 255
            ) {

                $error =
                    "Medicine name too long hai.";

            } elseif (
                $mrp < 0
            ) {

                $error =
                    "MRP valid hona chahiye.";

            } elseif (
                $selling_price < 0
            ) {

                $error =
                    "Selling price valid hona chahiye.";

            } elseif (
                $mrp > 0 &&
                $selling_price > $mrp
            ) {

                $error =
                    "Selling price MRP se zyada nahi ho sakti.";

            } elseif (
                $stock_quantity < 0
            ) {

                $error =
                    "Stock quantity invalid hai.";
            }


            /* -----------------------------------------
               EXPIRY DATE VALIDATION
            ----------------------------------------- */

            if (
                $error === '' &&
                $expiry_date !== ''
            ) {

                $dateObject =
                    DateTime::createFromFormat(
                        'Y-m-d',
                        $expiry_date
                    );


                if (
                    !$dateObject ||
                    $dateObject->format('Y-m-d') !==
                    $expiry_date
                ) {

                    $error =
                        "Expiry date valid nahi hai.";
                }
            }


            /* -----------------------------------------
               IMAGE UPLOAD
            ----------------------------------------- */

            $newImage = null;


            if (
                $error === '' &&
                isset($_FILES['image']) &&
                $_FILES['image']['error'] !==
                    UPLOAD_ERR_NO_FILE
            ) {

                $newImage =
                    uploadMedicineImage(
                        $_FILES['image']
                    );


                if (
                    $newImage === false
                ) {

                    $error =
                        "Image upload failed. JPG, JPEG, PNG ya WEBP image use karein. Maximum size 5 MB hai.";
                }
            }


            /* =================================================
               INSERT
            ================================================= */

            if (
                $error === '' &&
                $id === 0
            ) {


                $sql =
                    "INSERT INTO medicines
                    (
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
                    )
                    VALUES
                    (
                        ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?
                    )";


                $stmt =
                    $conn->prepare(
                        $sql
                    );


                if (!$stmt) {

                    $error =
                        "Database error: medicine prepare failed.";

                } else {


                    /*
                     * 8 strings
                     * 2 doubles
                     * 2 integers
                     * 1 string
                     * 1 integer
                     */

                    $stmt->bind_param(
                        "ssssssssddiisi",
                        $name,
                        $generic_name,
                        $manufacturer,
                        $category,
                        $composition,
                        $description,
                        $batch_number,
                        $expiry_date,
                        $mrp,
                        $selling_price,
                        $stock_quantity,
                        $prescription_required,
                        $newImage,
                        $status
                    );


                    if (
                        $stmt->execute()
                    ) {

                        $message =
                            "Medicine successfully added.";

                        $action =
                            'list';

                    } else {

                        $error =
                            "Medicine add nahi ho payi.";

                        /*
                         * Remove uploaded image
                         * if database insert failed.
                         */

                        if (
                            !empty($newImage)
                        ) {

                            deleteMedicineImage(
                                $newImage
                            );
                        }
                    }


                    $stmt->close();
                }
            }


            /* =================================================
               UPDATE
            ================================================= */

            elseif (
                $error === '' &&
                $id > 0
            ) {


                /* -----------------------------------------
                   GET OLD IMAGE
                ----------------------------------------- */

                $oldImage = null;


                $stmt =
                    $conn->prepare(
                        "SELECT image
                         FROM medicines
                         WHERE id = ?
                         LIMIT 1"
                    );


                if ($stmt) {

                    $stmt->bind_param(
                        "i",
                        $id
                    );

                    $stmt->execute();

                    $result =
                        $stmt->get_result();


                    if (
                        $result &&
                        $result->num_rows === 1
                    ) {

                        $row =
                            $result->fetch_assoc();

                        $oldImage =
                            $row['image'] ?? null;

                    } else {

                        $error =
                            "Medicine nahi mili.";
                    }


                    $stmt->close();

                } else {

                    $error =
                        "Database error.";
                }


                /* =================================================
                   UPDATE WITH NEW IMAGE
                ================================================= */

                if (
                    $error === '' &&
                    $newImage !== null
                ) {


                    $sql =
                        "UPDATE medicines SET

                            name = ?,
                            generic_name = ?,
                            manufacturer = ?,
                            category = ?,
                            composition = ?,
                            description = ?,
                            batch_number = ?,
                            expiry_date = ?,
                            mrp = ?,
                            selling_price = ?,
                            stock_quantity = ?,
                            prescription_required = ?,
                            image = ?,
                            status = ?

                        WHERE id = ?";


                    $stmt =
                        $conn->prepare(
                            $sql
                        );


                    if (!$stmt) {

                        $error =
                            "Database error: update prepare failed.";

                        deleteMedicineImage(
                            $newImage
                        );

                    } else {


                        $stmt->bind_param(
                            "ssssssssddiisii",
                            $name,
                            $generic_name,
                            $manufacturer,
                            $category,
                            $composition,
                            $description,
                            $batch_number,
                            $expiry_date,
                            $mrp,
                            $selling_price,
                            $stock_quantity,
                            $prescription_required,
                            $newImage,
                            $status,
                            $id
                        );


                        if (
                            $stmt->execute()
                        ) {


                            /*
                             * Delete old image
                             */

                            if (
                                !empty($oldImage) &&
                                $oldImage !== $newImage
                            ) {

                                deleteMedicineImage(
                                    $oldImage
                                );
                            }


                            $message =
                                "Medicine successfully updated.";

                            $action =
                                'list';

                        } else {

                            $error =
                                "Medicine update nahi ho payi.";

                            /*
                             * Delete newly uploaded
                             * image if DB update failed.
                             */

                            deleteMedicineImage(
                                $newImage
                            );
                        }


                        $stmt->close();
                    }
                }


                /* =================================================
                   UPDATE WITHOUT NEW IMAGE
                ================================================= */

                elseif (
                    $error === '' &&
                    $newImage === null
                ) {


                    $sql =
                        "UPDATE medicines SET

                            name = ?,
                            generic_name = ?,
                            manufacturer = ?,
                            category = ?,
                            composition = ?,
                            description = ?,
                            batch_number = ?,
                            expiry_date = ?,
                            mrp = ?,
                            selling_price = ?,
                            stock_quantity = ?,
                            prescription_required = ?,
                            status = ?

                        WHERE id = ?";


                    $stmt =
                        $conn->prepare(
                            $sql
                        );


                    if (!$stmt) {

                        $error =
                            "Database error: update prepare failed.";

                    } else {


                        /*
                         * 8 strings
                         * 2 doubles
                         * 4 integers
                         *
                         * stock
                         * prescription
                         * status
                         * id
                         */

                        $stmt->bind_param(
                            "ssssssssddiiii",
                            $name,
                            $generic_name,
                            $manufacturer,
                            $category,
                            $composition,
                            $description,
                            $batch_number,
                            $expiry_date,
                            $mrp,
                            $selling_price,
                            $stock_quantity,
                            $prescription_required,
                            $status,
                            $id
                        );


                        if (
                            $stmt->execute()
                        ) {

                            $message =
                                "Medicine successfully updated.";

                            $action =
                                'list';

                        } else {

                            $error =
                                "Medicine update nahi ho payi.";
                        }


                        $stmt->close();
                    }
                }
            }
        }
    }
}


/* =====================================================
   EDIT MEDICINE DATA
===================================================== */

if (
    $action === 'edit' &&
    $edit_id > 0
) {


    $stmt =
        $conn->prepare(
            "SELECT *
             FROM medicines
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

            $medicine =
                $result->fetch_assoc();

        } else {

            $error =
                "Medicine nahi mili.";

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
   FETCH MEDICINES
===================================================== */

if (
    $action === 'list'
) {


    if (
        $search !== ''
    ) {


        $searchTerm =
            "%" .
            $search .
            "%";


        $stmt =
            $conn->prepare(
                "SELECT *
                 FROM medicines

                 WHERE name LIKE ?
                    OR generic_name LIKE ?
                    OR manufacturer LIKE ?
                    OR category LIKE ?
                    OR batch_number LIKE ?

                 ORDER BY id DESC"
            );


        if ($stmt) {

            $stmt->bind_param(
                "sssss",
                $searchTerm,
                $searchTerm,
                $searchTerm,
                $searchTerm,
                $searchTerm
            );

            $stmt->execute();

            $medicines =
                $stmt->get_result();

            $stmt->close();
        }

    } else {

        $medicines =
            $conn->query(
                "SELECT *
                 FROM medicines
                 ORDER BY id DESC"
            );
    }
}


/* =====================================================
   PAGE TITLE
===================================================== */

$pageTitle =
    $action === 'edit'
        ? 'Edit Medicine'
        : (
            $action === 'add'
                ? 'Add Medicine'
                : 'Medicines'
        );

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
    background:
        linear-gradient(
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
    border-bottom:
        1px solid
        rgba(255,255,255,.12);
}

.brand-icon {
    width: 46px;
    height: 46px;
    background:
        rgba(255,255,255,.15);
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
    color:
        rgba(255,255,255,.7);
}

.sidebar-menu {
    padding: 18px 12px;
}

.menu-title {
    color:
        rgba(255,255,255,.5);
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 10px 12px;
}

.sidebar-menu a {
    display: flex;
    align-items: center;
    gap: 11px;
    color:
        rgba(255,255,255,.85);
    padding: 12px 13px;
    border-radius: 8px;
    margin-bottom: 4px;
    font-size: 13px;
    transition: .2s;
}

.sidebar-menu a:hover,
.sidebar-menu a.active {
    background:
        rgba(255,255,255,.15);
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
    border-bottom:
        1px solid #e8edf2;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 30px;
    position: sticky;
    top: 0;
    z-index: 100;
}

.topbar-left {
    display: flex;
    align-items: center;
    gap: 10px;
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

.btn-danger:hover {
    background: #bb2d3b;
}

.btn-secondary {
    background: #6c757d;
    color: #fff;
}

.btn-secondary:hover {
    background: #5c636a;
}

.btn-warning {
    background: #ffc107;
    color: #222;
}

.btn-warning:hover {
    background: #e0a800;
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
   SEARCH
===================================================== */

.search-box {
    background: #fff;
    padding: 17px;
    border-radius: 10px;
    margin-bottom: 20px;
    border: 1px solid #e9edf1;
}

.search-form {
    display: flex;
    gap: 10px;
}

.search-form input {
    flex: 1;
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
    min-width: 1050px;
}

th,
td {
    padding: 13px 14px;
    text-align: left;
    border-bottom:
        1px solid #edf0f3;
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
   MEDICINE IMAGE
===================================================== */

.medicine-img {
    width: 55px;
    height: 55px;
    object-fit: contain;
    border: 1px solid #e6e6e6;
    border-radius: 7px;
    padding: 4px;
    background: #fff;
}

.medicine-name {
    font-weight: 600;
    color: #333;
}

.generic-name {
    color: #888;
    font-size: 10px;
    margin-top: 4px;
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


/* =====================================================
   FORM
===================================================== */

.form-card {
    background: #fff;
    border-radius: 11px;
    padding: 30px;
    border: 1px solid #e7ebef;
}

.form-grid {
    display: grid;
    grid-template-columns:
        repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.form-group {
    min-width: 0;
}

.form-group.full {
    grid-column: 1 / -1;
}

.form-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 7px;
    color: #444;
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
    height: 105px;
    padding-top: 11px;
    resize: vertical;
}

.form-control:focus {
    border-color: #51b848;
    box-shadow:
        0 0 0 3px
        rgba(81,184,72,.1);
}

.checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    cursor: pointer;
}

.checkbox input {
    width: 17px;
    height: 17px;
    accent-color: #51b848;
}

.current-image {
    margin-top: 10px;
}

.current-image small {
    color: #888;
    font-size: 10px;
}

.current-image img {
    display: block;
    width: 100px;
    height: 100px;
    object-fit: contain;
    border: 1px solid #ddd;
    padding: 5px;
    border-radius: 8px;
    margin-top: 5px;
}

.form-actions {
    margin-top: 25px;
    padding-top: 20px;
    border-top:
        1px solid #eee;
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
   MOBILE MENU
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

@media (max-width: 1000px) {

    .sidebar {
        width: 220px;
    }

    .main {
        margin-left: 220px;
        width: calc(100% - 220px);
    }

    .content {
        padding: 22px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-group.full {
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

    .search-form {
        flex-direction: column;
    }

    .form-card {
        padding: 20px 15px;
    }

    .content {
        padding: 15px;
    }

    .action-buttons {
        flex-direction: column;
        align-items: stretch;
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


        <a
            href="medicines.php"
            class="active"
        >

            <span class="menu-icon">
                💊
            </span>

            Medicines

        </a>


        <a href="orders.php">

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
            Medicines
        </div>

    </div>


    <div class="admin-user">

        Welcome,

        <strong>
            <?= htmlspecialchars(
                $_SESSION['name'] ??
                'Admin'
            ) ?>
        </strong>

    </div>

</header>


<!-- =====================================================
     CONTENT
===================================================== -->

<div class="content">


<?php if ($message !== ''): ?>

    <div class="alert alert-success">

        <?= htmlspecialchars(
            $message
        ) ?>

    </div>

<?php endif; ?>


<?php if ($error !== ''): ?>

    <div class="alert alert-danger">

        <?= htmlspecialchars(
            $error
        ) ?>

    </div>

<?php endif; ?>


<!-- =====================================================
     LIST
===================================================== -->

<?php if ($action === 'list'): ?>


<div class="page-heading">

    <h1>
        Medicine Management
    </h1>


    <a
        href="medicines.php?action=add"
        class="btn btn-primary"
    >
        + Add Medicine
    </a>

</div>


<!-- SEARCH -->

<div class="search-box">

    <form
        method="GET"
        class="search-form"
    >

        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="Search medicine, generic name, manufacturer, category, batch..."
            value="<?= htmlspecialchars(
                $search
            ) ?>"
        >


        <button
            type="submit"
            class="btn btn-primary"
        >
            Search
        </button>


        <?php if ($search !== ''): ?>

            <a
                href="medicines.php"
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

    <th>Image</th>

    <th>Medicine</th>

    <th>Category</th>

    <th>Manufacturer</th>

    <th>Price</th>

    <th>Stock</th>

    <th>Expiry</th>

    <th>Status</th>

    <th>Actions</th>

</tr>

</thead>


<tbody>


<?php if (
    $medicines &&
    $medicines->num_rows > 0
): ?>


<?php while (
    $row =
    $medicines->fetch_assoc()
): ?>


<tr>


<!-- IMAGE -->

<td>

<?php if (
    !empty($row['image'])
): ?>

    <img
        src="<?= $medicineImageUrl . htmlspecialchars(
            basename($row['image'])
        ) ?>"
        class="medicine-img"
        alt="<?= htmlspecialchars(
            $row['name']
        ) ?>"
        onerror="this.onerror=null;this.src='../assets/images/product_01.png';"
    >

<?php else: ?>

    <img
        src="../assets/images/product_01.png"
        class="medicine-img"
        alt="Medicine"
    >

<?php endif; ?>

</td>


<!-- MEDICINE -->

<td>

<div class="medicine-name">

<?= htmlspecialchars(
    $row['name']
) ?>

</div>


<?php if (
    !empty(
        $row['generic_name']
    )
): ?>

<div class="generic-name">

<?= htmlspecialchars(
    $row['generic_name']
) ?>

</div>

<?php endif; ?>


<?php if (
    (int)$row[
        'prescription_required'
    ] === 1
): ?>

<div style="margin-top:6px;">

<span class="badge badge-warning">
    Prescription
</span>

</div>

<?php endif; ?>

</td>


<!-- CATEGORY -->

<td>

<?= htmlspecialchars(
    $row['category'] ?? '-'
) ?>

</td>


<!-- MANUFACTURER -->

<td>

<?= htmlspecialchars(
    $row['manufacturer'] ?? '-'
) ?>

</td>


<!-- PRICE -->

<td>

<?php if (
    (float)$row['mrp'] >
    (float)$row['selling_price']
): ?>

<del
    style="
        color:#999;
        font-size:10px;
    "
>

₹<?= number_format(
    (float)$row['mrp'],
    2
) ?>

</del>

<br>

<?php endif; ?>


<strong
    style="
        color:#278c3c;
    "
>

₹<?= number_format(
    (float)$row['selling_price'],
    2
) ?>

</strong>

</td>


<!-- STOCK -->

<td>

<?php

$stock =
    (int)$row[
        'stock_quantity'
    ];

?>


<?php if (
    $stock <= 0
): ?>

<span class="badge badge-danger">
    Out of Stock
</span>

<?php elseif (
    $stock <= 10
): ?>

<span class="badge badge-warning">
    <?= $stock ?> Low
</span>

<?php else: ?>

<span class="badge badge-success">
    <?= $stock ?>
</span>

<?php endif; ?>

</td>


<!-- EXPIRY -->

<td>

<?php

$expiry =
    $row['expiry_date'] ?? '';

if (
    !empty($expiry)
) {

    $expiryTimestamp =
        strtotime($expiry);


    if (
        $expiryTimestamp !== false &&
        $expiryTimestamp <
        strtotime('today')
    ) {

        echo
            '<span class="badge badge-danger">Expired</span>';

        echo
            '<div style="font-size:10px;color:#999;margin-top:4px;">';

        echo
            htmlspecialchars($expiry);

        echo
            '</div>';

    } elseif (
        $expiryTimestamp !== false &&
        $expiryTimestamp <=
        strtotime('+30 days')
    ) {

        echo
            '<span class="badge badge-warning">';

        echo
            htmlspecialchars($expiry);

        echo
            '</span>';

    } else {

        echo
            htmlspecialchars($expiry);
    }

} else {

    echo '-';
}

?>

</td>


<!-- STATUS -->

<td>

<?php if (
    (int)$row['status'] === 1
): ?>

<span class="badge badge-success">
    Active
</span>

<?php else: ?>

<span class="badge badge-danger">
    Inactive
</span>

<?php endif; ?>

</td>


<!-- ACTIONS -->

<td>

<div class="action-buttons">


<a
    href="medicines.php?action=edit&id=<?= (int)$row['id'] ?>"
    class="btn btn-warning"
>
    Edit
</a>


<form
    method="POST"
    onsubmit="
        return confirm(
            'Kya aap is medicine ko permanently delete karna chahte hain?'
        );
    "
>

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
    value="delete"
>


<input
    type="hidden"
    name="id"
    value="<?= (int)$row['id'] ?>"
>


<button
    type="submit"
    class="btn btn-danger"
>
    Delete
</button>

</form>


</div>

</td>


</tr>


<?php endwhile; ?>


<?php else: ?>


<tr>

<td
    colspan="9"
    class="empty"
>

💊

<br><br>


<?php if (
    $search !== ''
): ?>

No medicines found for

<strong>
    "<?= htmlspecialchars(
        $search
    ) ?>"
</strong>.

<?php else: ?>

No medicines found.

<?php endif; ?>

</td>

</tr>


<?php endif; ?>


</tbody>

</table>

</div>

</div>


<!-- =====================================================
     ADD / EDIT
===================================================== -->

<?php else: ?>


<?php

$isEdit =
    $action === 'edit' &&
    $medicine !== null;

?>


<div class="page-heading">

<h1>

<?= $isEdit
    ? 'Edit Medicine'
    : 'Add New Medicine'
?>

</h1>


<a
    href="medicines.php"
    class="btn btn-secondary"
>
    ← Back
</a>

</div>


<div class="form-card">


<form
    method="POST"
    enctype="multipart/form-data"
>


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
    value="save"
>


<input
    type="hidden"
    name="id"
    value="<?= $isEdit
        ? (int)$medicine['id']
        : 0 ?>"
>


<div class="form-grid">


<!-- NAME -->

<div class="form-group">

<label class="form-label">
    Medicine Name *
</label>

<input
    type="text"
    name="name"
    class="form-control"
    required
    maxlength="255"
    value="<?= htmlspecialchars(
        $medicine['name'] ?? ''
    ) ?>"
>

</div>


<!-- GENERIC -->

<div class="form-group">

<label class="form-label">
    Generic Name
</label>

<input
    type="text"
    name="generic_name"
    class="form-control"
    maxlength="255"
    value="<?= htmlspecialchars(
        $medicine[
            'generic_name'
        ] ?? ''
    ) ?>"
>

</div>


<!-- MANUFACTURER -->

<div class="form-group">

<label class="form-label">
    Manufacturer
</label>

<input
    type="text"
    name="manufacturer"
    class="form-control"
    maxlength="255"
    value="<?= htmlspecialchars(
        $medicine[
            'manufacturer'
        ] ?? ''
    ) ?>"
>

</div>


<!-- CATEGORY -->

<div class="form-group">

<label class="form-label">
    Category
</label>

<input
    type="text"
    name="category"
    class="form-control"
    maxlength="100"
    placeholder="Tablet, Syrup, Capsule..."
    value="<?= htmlspecialchars(
        $medicine[
            'category'
        ] ?? ''
    ) ?>"
>

</div>


<!-- BATCH -->

<div class="form-group">

<label class="form-label">
    Batch Number
</label>

<input
    type="text"
    name="batch_number"
    class="form-control"
    maxlength="100"
    value="<?= htmlspecialchars(
        $medicine[
            'batch_number'
        ] ?? ''
    ) ?>"
>

</div>


<!-- EXPIRY -->

<div class="form-group">

<label class="form-label">
    Expiry Date
</label>

<input
    type="date"
    name="expiry_date"
    class="form-control"
    value="<?= htmlspecialchars(
        $medicine[
            'expiry_date'
        ] ?? ''
    ) ?>"
>

</div>


<!-- MRP -->

<div class="form-group">

<label class="form-label">
    MRP
</label>

<input
    type="number"
    step="0.01"
    min="0"
    name="mrp"
    class="form-control"
    value="<?= htmlspecialchars(
        $medicine[
            'mrp'
        ] ?? '0.00'
    ) ?>"
>

</div>


<!-- SELLING PRICE -->

<div class="form-group">

<label class="form-label">
    Selling Price
</label>

<input
    type="number"
    step="0.01"
    min="0"
    name="selling_price"
    class="form-control"
    value="<?= htmlspecialchars(
        $medicine[
            'selling_price'
        ] ?? '0.00'
    ) ?>"
>

</div>


<!-- STOCK -->

<div class="form-group">

<label class="form-label">
    Stock Quantity
</label>

<input
    type="number"
    min="0"
    name="stock_quantity"
    class="form-control"
    value="<?= htmlspecialchars(
        $medicine[
            'stock_quantity'
        ] ?? '0'
    ) ?>"
>

</div>


<!-- IMAGE -->

<div class="form-group">

<label class="form-label">
    Medicine Image
</label>

<input
    type="file"
    name="image"
    class="form-control"
    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
>


<div
    style="
        color:#999;
        font-size:10px;
        margin-top:6px;
    "
>
    JPG, JPEG, PNG, WEBP — Maximum 5 MB
</div>


<?php if (
    $isEdit &&
    !empty(
        $medicine['image']
    )
): ?>

<div class="current-image">

<small>
    Current Image
</small>


<img
    src="<?= $medicineImageUrl . htmlspecialchars(
        basename(
            $medicine['image']
        )
    ) ?>"
    alt="Current medicine image"
    onerror="this.onerror=null;this.src='../assets/images/product_01.png';"
>

</div>

<?php endif; ?>


</div>


<!-- COMPOSITION -->

<div class="form-group full">

<label class="form-label">
    Composition
</label>

<textarea
    name="composition"
    class="form-control"
    placeholder="Medicine composition..."
><?= htmlspecialchars(
    $medicine[
        'composition'
    ] ?? ''
) ?></textarea>

</div>


<!-- DESCRIPTION -->

<div class="form-group full">

<label class="form-label">
    Description
</label>

<textarea
    name="description"
    class="form-control"
    placeholder="Medicine description..."
><?= htmlspecialchars(
    $medicine[
        'description'
    ] ?? ''
) ?></textarea>

</div>


<!-- PRESCRIPTION -->

<div class="form-group">

<label class="checkbox">

<input
    type="checkbox"
    name="prescription_required"
    value="1"
    <?= (
        $isEdit &&
        (int)$medicine[
            'prescription_required'
        ] === 1
    )
        ? 'checked'
        : ''
    ?>
>

Prescription Required

</label>

</div>


<!-- STATUS -->

<div class="form-group">

<label class="checkbox">

<input
    type="checkbox"
    name="status"
    value="1"
    <?= (
        !$isEdit ||
        (int)$medicine[
            'status'
        ] === 1
    )
        ? 'checked'
        : ''
    ?>
>

Active Medicine

</label>

</div>


</div>


<!-- FORM ACTIONS -->

<div class="form-actions">

<button
    type="submit"
    class="btn btn-primary"
>

<?= $isEdit
    ? 'Update Medicine'
    : 'Save Medicine'
?>

</button>


<a
    href="medicines.php"
    class="btn btn-secondary"
>
    Cancel
</a>

</div>


</form>

</div>


<?php endif; ?>


</div>

</main>

</div>


<script>

function toggleSidebar()
{
    const sidebar =
        document.getElementById(
            "sidebar"
        );

    sidebar.classList.toggle(
        "show"
    );
}

</script>


</body>

</html>