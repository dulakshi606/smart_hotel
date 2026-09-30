<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

startSession();
requireRole("customer");


/* =====================================================
   CUSTOMER ID
===================================================== */

$customerId = (int)($_SESSION["user_id"] ?? 0);

if ($customerId <= 0) {
    header("Location: ../auth/login.php");
    exit;
}


/* =====================================================
   GET MEAL ID
===================================================== */

$mealId = isset($_GET["meal_id"])
    ? (int)$_GET["meal_id"]
    : (int)($_POST["meal_id"] ?? 0);

if ($mealId <= 0) {
    header("Location: meals.php");
    exit;
}


/* =====================================================
   GET MEAL
===================================================== */

$stmt = $pdo->prepare("
    SELECT
        id,
        meal_name,
        category,
        description,
        price,
        image,
        availability
    FROM meals
    WHERE id = ?
      AND availability = 'available'
    LIMIT 1
");

$stmt->execute([$mealId]);

$meal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$meal) {
    header("Location: meals.php");
    exit;
}


/* =====================================================
   GET MEAL OPTIONS
===================================================== */

$stmt = $pdo->prepare("
    SELECT
        id,
        option_group,
        option_name,
        price,
        selection_type
    FROM meal_options
    WHERE meal_id = ?
    ORDER BY id ASC
");

$stmt->execute([$mealId]);

$options = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   GROUP OPTIONS
===================================================== */

$groupedOptions = [];

foreach ($options as $option) {

    $group = $option["option_group"];

    if (!isset($groupedOptions[$group])) {
        $groupedOptions[$group] = [];
    }

    $groupedOptions[$group][] = $option;
}


/* =====================================================
   ERROR
===================================================== */

$error = "";


/* =====================================================
   PROCESS ORDER
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $bookingId = (int)($_POST["booking_id"] ?? 0);

    $selectedOptions = $_POST["options"] ?? [];


    /* =================================================
       VALIDATE BOOKING
    ================================================= */

    if ($bookingId <= 0) {

        $error = "Please select a hotel booking.";

    } else {

        $bookingStmt = $pdo->prepare("
            SELECT
                b.id,
                b.booking_code,
                b.room_id,
                b.check_in,
                b.check_out,
                b.booking_status,
                r.room_number
            FROM bookings b
            INNER JOIN rooms r
                ON r.id = b.room_id
            WHERE b.id = ?
              AND b.customer_id = ?
              AND b.booking_status IN (
                    'pending',
                    'confirmed',
                    'checked_in'
              )
            LIMIT 1
        ");

        $bookingStmt->execute([
            $bookingId,
            $customerId
        ]);

        $booking = $bookingStmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {

            $error =
                "The selected booking is not valid or is no longer active.";

        }
    }


    /* =================================================
       VALIDATE REQUIRED OPTIONS
    ================================================= */

    if ($error === "" && !empty($groupedOptions)) {

        foreach ($groupedOptions as $groupName => $groupOptions) {

            if (
                !isset($selectedOptions[$groupName]) ||
                (int)$selectedOptions[$groupName] <= 0
            ) {

                $error =
                    "Please select an option from: "
                    . $groupName;

                break;
            }
        }
    }


    /* =================================================
       COLLECT SELECTED OPTION IDS
    ================================================= */

    $selectedOptionIds = [];

    if ($error === "") {

        foreach ($selectedOptions as $groupName => $optionId) {

            $optionId = (int)$optionId;

            if ($optionId > 0) {
                $selectedOptionIds[] = $optionId;
            }
        }

        $selectedOptionIds =
            array_values(
                array_unique($selectedOptionIds)
            );
    }


    /* =================================================
       VERIFY SELECTED OPTIONS
    ================================================= */

    $selectedOptionRows = [];

    if (
        $error === "" &&
        !empty($selectedOptionIds)
    ) {

        $placeholders = implode(
            ",",
            array_fill(
                0,
                count($selectedOptionIds),
                "?"
            )
        );

        $optionStmt = $pdo->prepare("
            SELECT
                id,
                meal_id,
                option_group,
                option_name,
                price
            FROM meal_options
            WHERE meal_id = ?
              AND id IN ($placeholders)
        ");

        $params = array_merge(
            [$mealId],
            $selectedOptionIds
        );

        $optionStmt->execute($params);

        $selectedOptionRows =
            $optionStmt->fetchAll(PDO::FETCH_ASSOC);


        /* =================================================
           CHECK ALL OPTIONS EXIST
        ================================================= */

        if (
            count($selectedOptionRows)
            !== count($selectedOptionIds)
        ) {

            $error =
                "One or more selected meal options are invalid.";

        }
    }


    /* =================================================
       CHECK ONE OPTION PER GROUP
    ================================================= */

    if ($error === "" && !empty($selectedOptionRows)) {

        $groupsSelected = [];

        foreach ($selectedOptionRows as $option) {

            $groupName = $option["option_group"];

            if (isset($groupsSelected[$groupName])) {

                $error =
                    "Please select only one option from each category.";

                break;
            }

            $groupsSelected[$groupName] = true;
        }
    }


    /* =================================================
       CALCULATE TOTAL USING DATABASE VALUES
    ================================================= */

    $basePrice = (float)$meal["price"];

    $optionsTotal = 0;

    if ($error === "") {

        foreach ($selectedOptionRows as $option) {

            $optionsTotal +=
                (float)$option["price"];
        }
    }

    $finalTotal =
        $basePrice + $optionsTotal;


    /* =================================================
       SAVE ORDER
    ================================================= */

    if ($error === "") {

        try {

            $pdo->beginTransaction();


            /* =============================================
               RE-CHECK MEAL AVAILABILITY
            ============================================= */

            $mealCheckStmt = $pdo->prepare("
                SELECT
                    id,
                    price,
                    availability
                FROM meals
                WHERE id = ?
                FOR UPDATE
            ");

            $mealCheckStmt->execute([
                $mealId
            ]);

            $currentMeal =
                $mealCheckStmt->fetch(PDO::FETCH_ASSOC);

            if (
                !$currentMeal ||
                $currentMeal["availability"] !== "available"
            ) {

                throw new Exception(
                    "This meal is no longer available."
                );
            }


            /* =============================================
               USE CURRENT DATABASE PRICE
            ============================================= */

            $basePrice =
                (float)$currentMeal["price"];

            $optionsTotal = 0;

            foreach ($selectedOptionRows as $option) {

                $optionsTotal +=
                    (float)$option["price"];
            }

            $finalTotal =
                $basePrice + $optionsTotal;


            /* =============================================
               CREATE MAIN ORDER
            ============================================= */

            $orderStmt = $pdo->prepare("
                INSERT INTO meal_orders
                (
                    customer_id,
                    booking_id,
                    total_amount,
                    order_status
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    'pending'
                )
            ");

            $orderStmt->execute([
                $customerId,
                $bookingId,
                $finalTotal
            ]);

            $orderId =
                (int)$pdo->lastInsertId();


            /* =============================================
               CREATE ORDER ITEM
            ============================================= */

            $itemStmt = $pdo->prepare("
                INSERT INTO meal_order_items
                (
                    order_id,
                    meal_id,
                    quantity,
                    price,
                    subtotal
                )
                VALUES
                (
                    ?,
                    ?,
                    1,
                    ?,
                    ?
                )
            ");

            $itemStmt->execute([
                $orderId,
                $mealId,
                $basePrice,
                $finalTotal
            ]);

            $orderItemId =
                (int)$pdo->lastInsertId();


            /* =============================================
               SAVE SELECTED OPTIONS
            ============================================= */

            if (!empty($selectedOptionRows)) {

                $optionInsertStmt = $pdo->prepare("
                    INSERT INTO meal_order_options
                    (
                        order_item_id,
                        option_id,
                        option_name,
                        price
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ");

                foreach (
                    $selectedOptionRows
                    as $selectedOption
                ) {

                    $optionInsertStmt->execute([
                        $orderItemId,
                        (int)$selectedOption["id"],
                        $selectedOption["option_name"],
                        (float)$selectedOption["price"]
                    ]);
                }
            }


            /* =============================================
               COMMIT
            ============================================= */

            $pdo->commit();


            /* =============================================
               SUCCESS PAGE
            ============================================= */

            header(
                "Location: meal_order_success.php?order_id="
                . $orderId
            );

            exit;


        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error =
                "Unable to place your meal order. "
                . "Please try again.";
        }
    }
}


/* =====================================================
   GET ACTIVE CUSTOMER BOOKINGS
===================================================== */

$bookingStmt = $pdo->prepare("
    SELECT
        b.id,
        b.booking_code,
        b.check_in,
        b.check_out,
        b.booking_status,
        r.room_number
    FROM bookings b
    INNER JOIN rooms r
        ON r.id = b.room_id
    WHERE b.customer_id = ?
      AND b.booking_status IN (
            'pending',
            'confirmed',
            'checked_in'
      )
    ORDER BY
        b.check_in DESC,
        b.id DESC
");

$bookingStmt->execute([
    $customerId
]);

$bookings =
    $bookingStmt->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   MEAL IMAGE
===================================================== */

$image = trim(
    $meal["image"] ?? ""
);

$imagePath = "";

if ($image !== "") {

    if (
        str_starts_with($image, "http://") ||
        str_starts_with($image, "https://")
    ) {

        $imagePath = $image;

    } else {

        $imagePath =
            "../uploads/meals/" . $image;
    }
}

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
    <?= e($meal["meal_name"]); ?> - Velora Hotel
</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    rel="stylesheet"
>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #f7f8fa;
    color: #172033;
    font-family: "Segoe UI", Arial, sans-serif;
}


/* =====================================================
   NAVBAR
===================================================== */

.navbar {
    background: #071a3a !important;
    padding: 15px 0;
    box-shadow: 0 4px 18px rgba(0,0,0,0.12);
}

.navbar-brand {
    color: #d4af37 !important;
    font-size: 25px;
    font-weight: 700;
    letter-spacing: 1px;
}

.navbar-brand span {
    color: #ffffff;
}

.nav-link {
    color: #ffffff !important;
    margin-left: 12px;
    font-weight: 500;
}

.nav-link:hover,
.active-link {
    color: #d4af37 !important;
}

.logout-btn {
    border: 1px solid #d4af37;
    color: #d4af37 !important;
    border-radius: 8px;
    padding: 7px 15px !important;
}

.logout-btn:hover {
    background: #d4af37;
    color: #071a3a !important;
}


/* =====================================================
   HEADER
===================================================== */

.page-header {
    background:
        linear-gradient(
            rgba(7,26,58,0.94),
            rgba(7,26,58,0.94)
        );

    padding: 55px 20px;

    text-align: center;

    color: white;
}

.page-header h1 {
    font-size: 36px;
    font-weight: 700;
    margin-bottom: 8px;
}

.page-header p {
    color: #dce3ef;
    margin: 0;
}


/* =====================================================
   MAIN
===================================================== */

.main-container {
    max-width: 1150px;
    margin: auto;
    padding: 45px 20px;
}


/* =====================================================
   MEAL PREVIEW
===================================================== */

.meal-preview {
    background: white;
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    box-shadow: 0 8px 25px rgba(0,0,0,0.06);
    margin-bottom: 30px;
}

.meal-preview-image,
.image-placeholder {
    width: 100%;
    height: 330px;
}

.meal-preview-image {
    object-fit: cover;
}

.image-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;

    background:
        linear-gradient(
            135deg,
            #071a3a,
            #102b59
        );

    color: #d4af37;
    font-size: 75px;
}

.meal-preview-body {
    padding: 28px;
}

.meal-category {
    display: inline-block;

    background: #fff8df;
    color: #7c5a00;

    padding: 6px 13px;

    border-radius: 20px;

    font-size: 12px;
    font-weight: 700;

    margin-bottom: 12px;
}

.meal-title {
    color: #071a3a;
    font-size: 30px;
    font-weight: 700;
    margin-bottom: 10px;
}

.meal-description {
    color: #6b7280;
    line-height: 1.7;
    margin-bottom: 18px;
}

.base-price {
    color: #071a3a;
    font-size: 24px;
    font-weight: 700;
}


/* =====================================================
   OPTIONS
===================================================== */

.options-card {
    background: white;
    border-radius: 18px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 8px 25px rgba(0,0,0,0.06);

    padding: 30px;
    margin-bottom: 25px;
}

.options-title {
    color: #071a3a;
    font-size: 25px;
    font-weight: 700;
    margin-bottom: 8px;
}

.options-subtitle {
    color: #6b7280;
    margin-bottom: 30px;
}

.option-group {
    margin-bottom: 32px;
}

.option-group:last-child {
    margin-bottom: 0;
}

.option-group-title {
    color: #071a3a;
    font-size: 18px;
    font-weight: 700;

    margin-bottom: 14px;
    padding-bottom: 10px;

    border-bottom: 1px solid #e5e7eb;
}

.option-item {
    position: relative;
    margin-bottom: 10px;
}

.option-radio {
    position: absolute;
    opacity: 0;
}

.option-label {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 15px;

    padding: 15px 17px;

    border: 1px solid #e1e5eb;
    border-radius: 10px;

    cursor: pointer;

    transition: 0.2s ease;

    background: #ffffff;
}

.option-label:hover {
    border-color: #d4af37;
    background: #fffdf5;
}

.option-radio:checked + .option-label {
    border-color: #d4af37;
    background: #fff8df;

    box-shadow:
        0 3px 10px rgba(212,175,55,0.12);
}

.option-name {
    display: flex;
    align-items: center;
    gap: 10px;

    color: #172033;
    font-weight: 500;
}

.option-name i {
    color: #d4af37;
    font-size: 18px;
}

.option-price {
    color: #071a3a;
    font-weight: 700;
    white-space: nowrap;
}

.free-price {
    color: #198754;
}


/* =====================================================
   BOOKING
===================================================== */

.booking-card {
    background: #ffffff;

    border: 1px solid #e5e7eb;
    border-radius: 18px;

    padding: 30px;

    box-shadow:
        0 8px 25px rgba(0,0,0,0.06);
}

.booking-title {
    color: #071a3a;
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 8px;
}

.booking-subtitle {
    color: #6b7280;
    margin-bottom: 20px;
}

.booking-option {
    display: block;

    border: 1px solid #e1e5eb;
    border-radius: 12px;

    padding: 16px;

    margin-bottom: 12px;

    cursor: pointer;

    transition: 0.2s ease;
}

.booking-option:hover {
    border-color: #d4af37;
    background: #fffdf5;
}

.booking-radio {
    margin-right: 10px;
}

.booking-option:has(
    .booking-radio:checked
) {
    border-color: #d4af37;
    background: #fff8df;
}

.booking-code {
    color: #071a3a;
    font-weight: 700;
}

.booking-info {
    color: #6b7280;
    font-size: 14px;
    margin-top: 5px;
}


/* =====================================================
   SUMMARY
===================================================== */

.summary-card {
    background: #071a3a;

    color: white;

    border-radius: 18px;

    padding: 28px;

    position: sticky;
    top: 20px;

    box-shadow:
        0 10px 30px rgba(7,26,58,0.18);
}

.summary-title {
    color: #d4af37;

    font-size: 21px;
    font-weight: 700;

    margin-bottom: 20px;
}

.summary-row {
    display: flex;

    justify-content: space-between;

    gap: 15px;

    padding: 10px 0;

    border-bottom:
        1px solid rgba(255,255,255,0.12);
}

.summary-label {
    color: #dce3ef;
}

.summary-value {
    color: white;
    font-weight: 600;
    text-align: right;
}

.summary-total {
    display: flex;

    justify-content: space-between;
    align-items: center;

    margin-top: 20px;
    padding-top: 18px;

    border-top:
        1px solid rgba(255,255,255,0.2);
}

.total-label {
    font-size: 16px;
}

.total-price {
    color: #d4af37;

    font-size: 28px;
    font-weight: 700;
}

.confirm-btn {
    width: 100%;

    margin-top: 22px;

    padding: 13px 20px;

    border: none;
    border-radius: 9px;

    background: #d4af37;
    color: #071a3a;

    font-weight: 700;
    font-size: 15px;

    transition: 0.2s ease;
}

.confirm-btn:hover:not(:disabled) {
    background: #b99525;
    color: white;

    transform: translateY(-1px);
}

.confirm-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.back-btn {
    display: inline-block;

    margin-top: 15px;

    width: 100%;

    text-align: center;

    padding: 11px 20px;

    border: 1px solid #d4af37;
    border-radius: 9px;

    color: #d4af37;

    text-decoration: none;

    font-weight: 600;

    transition: 0.2s ease;
}

.back-btn:hover {
    background: #d4af37;
    color: #071a3a;
}


/* =====================================================
   FOOTER
===================================================== */

footer {
    background: #071a3a;

    color: white;

    text-align: center;

    padding: 28px 20px;

    margin-top: 30px;
}

footer strong {
    color: #d4af37;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 991px) {

    .summary-card {
        position: static;
        margin-top: 25px;
    }
}

@media (max-width: 768px) {

    .page-header h1 {
        font-size: 28px;
    }

    .meal-title {
        font-size: 25px;
    }

    .meal-preview-image,
    .image-placeholder {
        height: 250px;
    }

    .options-card,
    .booking-card {
        padding: 20px;
    }
}

</style>

</head>

<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar navbar-expand-lg">

    <div class="container">

        <a
            class="navbar-brand"
            href="dashboard.php"
        >
            <i class="bi bi-building"></i>
            Velora <span>Hotel</span>
        </a>


        <button
            class="navbar-toggler bg-light"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarMenu"
        >
            <span class="navbar-toggler-icon"></span>
        </button>


        <div
            class="collapse navbar-collapse"
            id="navbarMenu"
        >

            <ul class="navbar-nav ms-auto align-items-lg-center">

                <li class="nav-item">
                    <a
                        class="nav-link"
                        href="dashboard.php"
                    >
                        Dashboard
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link"
                        href="rooms.php"
                    >
                        Rooms
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link"
                        href="bookings.php"
                    >
                        My Bookings
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link active-link"
                        href="meals.php"
                    >
                        Meals & Dining
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link"
                        href="service_requests.php"
                    >
                        Services
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link"
                        href="profile.php"
                    >
                        Profile
                    </a>
                </li>

                <li class="nav-item">

                    <a
                        class="nav-link logout-btn ms-lg-2"
                        href="../auth/logout.php"
                    >
                        <i class="bi bi-box-arrow-right"></i>
                        Logout
                    </a>

                </li>

            </ul>

        </div>

    </div>

</nav>


<!-- =====================================================
     HEADER
===================================================== -->

<section class="page-header">

    <h1>
        Customize Your Meal
    </h1>

    <p>
        Choose your preferred options and enjoy your meal your way.
    </p>

</section>


<!-- =====================================================
     MAIN
===================================================== -->

<div class="main-container">


    <?php if ($error !== ""): ?>

        <div class="alert alert-danger">

            <i class="bi bi-exclamation-triangle"></i>

            <?= e($error); ?>

        </div>

    <?php endif; ?>


    <form
        id="mealOrderForm"
        method="post"
        action="meal_order.php?meal_id=<?= $mealId; ?>"
    >

        <input
            type="hidden"
            name="meal_id"
            value="<?= $mealId; ?>"
        >


        <div class="row g-4">


            <!-- =================================================
                 LEFT SIDE
            ================================================= -->

            <div class="col-lg-8">


                <!-- MEAL PREVIEW -->

                <div class="meal-preview">


                    <?php if ($imagePath !== ""): ?>

                        <img
                            src="<?= e($imagePath); ?>"
                            alt="<?= e($meal["meal_name"]); ?>"
                            class="meal-preview-image"
                            onerror="
                                this.style.display='none';
                                this.nextElementSibling.style.display='flex';
                            "
                        >

                        <div
                            class="image-placeholder"
                            style="display:none;"
                        >
                            <i class="bi bi-cup-hot-fill"></i>
                        </div>

                    <?php else: ?>

                        <div class="image-placeholder">

                            <i class="bi bi-cup-hot-fill"></i>

                        </div>

                    <?php endif; ?>


                    <div class="meal-preview-body">

                        <span class="meal-category">

                            <?= e($meal["category"]); ?>

                        </span>


                        <div class="meal-title">

                            <?= e($meal["meal_name"]); ?>

                        </div>


                        <div class="meal-description">

                            <?= e(
                                $meal["description"]
                                ?: "Deliciously prepared by Velora Hotel."
                            ); ?>

                        </div>


                        <div class="base-price">

                            Base Price:

                            LKR
                            <?= number_format(
                                (float)$meal["price"],
                                2
                            ); ?>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     OPTIONS
                ================================================= -->

                <div class="options-card">

                    <div class="options-title">

                        <i class="bi bi-sliders"></i>

                        Customize Your Meal

                    </div>


                    <div class="options-subtitle">

                        Select one option from each category.

                    </div>


                    <?php if (empty($groupedOptions)): ?>

                        <div class="alert alert-warning">

                            <i class="bi bi-info-circle"></i>

                            No customization options are available
                            for this meal.

                        </div>

                    <?php else: ?>


                        <?php foreach (
                            $groupedOptions
                            as $groupName => $groupOptions
                        ): ?>

                            <div class="option-group">


                                <div class="option-group-title">

                                    <?= e($groupName); ?>

                                </div>


                                <?php foreach (
                                    $groupOptions
                                    as $option
                                ): ?>

                                    <?php

                                    $optionPrice =
                                        (float)$option["price"];

                                    $optionId =
                                        (int)$option["id"];

                                    ?>


                                    <div class="option-item">


                                        <input
                                            type="radio"
                                            class="option-radio"
                                            id="option_<?= $optionId; ?>"
                                            name="options[<?= e($groupName); ?>]"
                                            value="<?= $optionId; ?>"
                                            data-price="<?= $optionPrice; ?>"
                                            <?= (
                                                isset($_POST["options"][$groupName])
                                                &&
                                                (int)$_POST["options"][$groupName]
                                                === $optionId
                                            )
                                                ? "checked"
                                                : ""
                                            ?>
                                        >


                                        <label
                                            class="option-label"
                                            for="option_<?= $optionId; ?>"
                                        >

                                            <span class="option-name">

                                                <i class="bi bi-check-circle"></i>

                                                <?= e(
                                                    $option["option_name"]
                                                ); ?>

                                            </span>


                                            <span
                                                class="
                                                    option-price
                                                    <?= $optionPrice == 0
                                                        ? 'free-price'
                                                        : '';
                                                    ?>
                                                "
                                            >

                                                <?php if ($optionPrice == 0): ?>

                                                    Free

                                                <?php else: ?>

                                                    + LKR
                                                    <?= number_format(
                                                        $optionPrice,
                                                        2
                                                    ); ?>

                                                <?php endif; ?>

                                            </span>

                                        </label>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>


                <!-- =================================================
                     BOOKING
                ================================================= -->

                <div class="booking-card">

                    <div class="booking-title">

                        <i class="bi bi-door-open"></i>

                        Select Your Booking

                    </div>


                    <div class="booking-subtitle">

                        Select the hotel booking where you want
                        this meal delivered.

                    </div>


                    <?php if (empty($bookings)): ?>

                        <div class="alert alert-warning">

                            <i class="bi bi-info-circle"></i>

                            You don't have an active hotel booking
                            available for meal ordering.

                            <br><br>

                            <a
                                href="rooms.php"
                                class="btn btn-sm btn-warning"
                            >
                                Browse Rooms
                            </a>

                        </div>

                    <?php else: ?>


                        <?php foreach ($bookings as $booking): ?>

                            <label class="booking-option">


                                <input
                                    type="radio"
                                    class="booking-radio"
                                    name="booking_id"
                                    value="<?= (int)$booking["id"]; ?>"
                                    <?= (
                                        isset($_POST["booking_id"])
                                        &&
                                        (int)$_POST["booking_id"]
                                        === (int)$booking["id"]
                                    )
                                        ? "checked"
                                        : ""
                                    ?>
                                    required
                                >


                                <span class="booking-code">

                                    Booking:
                                    <?= e(
                                        $booking["booking_code"]
                                    ); ?>

                                </span>


                                <div class="booking-info">

                                    <i class="bi bi-door-open"></i>

                                    Room
                                    <?= e(
                                        $booking["room_number"]
                                    ); ?>

                                    &nbsp; | &nbsp;

                                    <i class="bi bi-calendar-check"></i>

                                    <?= e(
                                        $booking["check_in"]
                                    ); ?>

                                    →

                                    <?= e(
                                        $booking["check_out"]
                                    ); ?>

                                    &nbsp; | &nbsp;

                                    <?= e(
                                        ucfirst(
                                            str_replace(
                                                "_",
                                                " ",
                                                $booking["booking_status"]
                                            )
                                        )
                                    ); ?>

                                </div>

                            </label>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </div>


            <!-- =================================================
                 RIGHT SIDE
            ================================================= -->

            <div class="col-lg-4">


                <div class="summary-card">


                    <div class="summary-title">

                        <i class="bi bi-receipt"></i>

                        Order Summary

                    </div>


                    <div class="summary-row">

                        <span class="summary-label">
                            Meal
                        </span>

                        <span class="summary-value">

                            <?= e(
                                $meal["meal_name"]
                            ); ?>

                        </span>

                    </div>


                    <div class="summary-row">

                        <span class="summary-label">
                            Base Price
                        </span>

                        <span class="summary-value">

                            LKR
                            <?= number_format(
                                (float)$meal["price"],
                                2
                            ); ?>

                        </span>

                    </div>


                    <div class="summary-row">

                        <span class="summary-label">
                            Options
                        </span>

                        <span
                            class="summary-value"
                            id="optionsTotal"
                        >
                            LKR 0.00
                        </span>

                    </div>


                    <div class="summary-total">

                        <span class="total-label">
                            Total
                        </span>

                        <span
                            class="total-price"
                            id="totalPrice"
                        >

                            LKR
                            <?= number_format(
                                (float)$meal["price"],
                                2
                            ); ?>

                        </span>

                    </div>


                    <button
                        type="submit"
                        class="confirm-btn"
                        <?= empty($bookings)
                            ? "disabled"
                            : "";
                        ?>
                    >

                        <i class="bi bi-cart-check"></i>

                        Confirm Meal Order

                    </button>


                    <a
                        href="meals.php"
                        class="back-btn"
                    >

                        <i class="bi bi-arrow-left"></i>

                        Back to Meals

                    </a>

                </div>

            </div>

        </div>

    </form>

</div>


<!-- =====================================================
     FOOTER
===================================================== -->

<footer>

    <div>

        <strong>Velora Hotel</strong>

        <span>
            &nbsp; | &nbsp;
            Meals & Dining
        </span>

    </div>


    <small>

        © <?= date("Y"); ?> Velora Hotel.
        All rights reserved.

    </small>

</footer>


<script>

/* =====================================================
   PRICE CALCULATION
===================================================== */

const basePrice =
    <?= (float)$meal["price"]; ?>;

const totalPriceElement =
    document.getElementById("totalPrice");

const optionsTotalElement =
    document.getElementById("optionsTotal");


function calculateTotal() {

    let optionsTotal = 0;


    const selectedOptions =
        document.querySelectorAll(
            ".option-radio:checked"
        );


    selectedOptions.forEach(function(option) {

        const price =
            parseFloat(
                option.dataset.price
            ) || 0;

        optionsTotal += price;

    });


    const total =
        basePrice + optionsTotal;


    optionsTotalElement.textContent =
        "LKR " +
        optionsTotal.toLocaleString(
            "en-LK",
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );


    totalPriceElement.textContent =
        "LKR " +
        total.toLocaleString(
            "en-LK",
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );
}


/* =====================================================
   OPTION CHANGE
===================================================== */

document
    .querySelectorAll(".option-radio")
    .forEach(function(option) {

        option.addEventListener(
            "change",
            calculateTotal
        );

    });


/* =====================================================
   INITIAL CALCULATION
===================================================== */

calculateTotal();

</script>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>