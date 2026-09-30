<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

startSession();
requireRole("customer");


/* =====================================================
   CUSTOMER
===================================================== */

$customerId = (int)($_SESSION["user_id"] ?? 0);

if ($customerId <= 0) {
    header("Location: ../auth/login.php");
    exit;
}


/* =====================================================
   ORDER ID
===================================================== */

$orderId = isset($_GET["order_id"])
    ? (int)$_GET["order_id"]
    : 0;

if ($orderId <= 0) {
    header("Location: meals.php");
    exit;
}


/* =====================================================
   GET ORDER
===================================================== */

$stmt = $pdo->prepare("
    SELECT
        mo.id,
        mo.total_amount,
        mo.order_status,
        mo.created_at,
        mo.booking_id,

        b.booking_code,
        b.room_id,
        b.check_in,
        b.check_out,

        r.room_number,

        rt.type_name

    FROM meal_orders mo

    INNER JOIN bookings b
        ON b.id = mo.booking_id

    INNER JOIN rooms r
        ON r.id = b.room_id

    INNER JOIN room_types rt
        ON rt.id = r.room_type_id

    WHERE mo.id = ?
      AND mo.customer_id = ?

    LIMIT 1
");

$stmt->execute([
    $orderId,
    $customerId
]);

$order = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$order) {
    header("Location: meals.php");
    exit;
}


/* =====================================================
   GET ORDER ITEMS
===================================================== */

$itemStmt = $pdo->prepare("
    SELECT
        moi.id,
        moi.meal_id,
        moi.quantity,
        moi.price,
        moi.subtotal,
        m.meal_name

    FROM meal_order_items moi

    INNER JOIN meals m
        ON m.id = moi.meal_id

    WHERE moi.order_id = ?

    ORDER BY moi.id ASC
");

$itemStmt->execute([
    $orderId
]);

$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   GET ORDER OPTIONS
===================================================== */

$optionStmt = $pdo->prepare("
    SELECT
        moo.id,
        moo.order_item_id,
        moo.option_name,
        moo.price

    FROM meal_order_options moo

    INNER JOIN meal_order_items moi
        ON moi.id = moo.order_item_id

    WHERE moi.order_id = ?

    ORDER BY moo.order_item_id ASC, moo.id ASC
");

$optionStmt->execute([
    $orderId
]);

$orderOptions = $optionStmt->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   GROUP OPTIONS BY ITEM
===================================================== */

$optionsByItem = [];

foreach ($orderOptions as $option) {

    $itemId = (int)$option["order_item_id"];

    if (!isset($optionsByItem[$itemId])) {
        $optionsByItem[$itemId] = [];
    }

    $optionsByItem[$itemId][] = $option;
}


/* =====================================================
   ORDER STATUS
===================================================== */

$orderStatus = strtolower(
    trim((string)$order["order_status"])
);

$statusClass = "status-pending";

$statusIcon = "bi-hourglass-split";

$statusText = "Pending";

switch ($orderStatus) {

    case "preparing":

        $statusClass = "status-preparing";
        $statusIcon = "bi-fire";
        $statusText = "Preparing";

        break;

    case "ready":

        $statusClass = "status-ready";
        $statusIcon = "bi-check-circle";
        $statusText = "Ready";

        break;

    case "delivered":

        $statusClass = "status-delivered";
        $statusIcon = "bi-check2-all";
        $statusText = "Delivered";

        break;

    case "cancelled":

        $statusClass = "status-cancelled";
        $statusIcon = "bi-x-circle";
        $statusText = "Cancelled";

        break;

    default:

        $statusClass = "status-pending";
        $statusIcon = "bi-hourglass-split";
        $statusText = "Pending";

        break;
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
        Meal Order Confirmed - Velora Hotel
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <!-- Velora CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/velora.css"
    >


    <style>

        /* =====================================================
           PAGE
        ===================================================== */

        body {

            margin: 0;

            background: #f7f8fa;

            color: #172033;

            font-family:
                "Segoe UI",
                Arial,
                sans-serif;
        }


        /* =====================================================
           NAVBAR
        ===================================================== */

        .navbar {

            background: #071a3a !important;

            padding: 15px 0;

            box-shadow:
                0 4px 18px
                rgba(0, 0, 0, 0.12);
        }


        .navbar-brand {

            color: #d4af37 !important;

            font-size: 25px;

            font-weight: 700;

            letter-spacing: 0.3px;
        }


        .navbar-brand span {

            color: white;
        }


        .navbar-brand i {

            margin-right: 5px;
        }


        .nav-link {

            color: white !important;

            margin-left: 12px;

            font-weight: 500;

            transition: 0.2s;
        }


        .nav-link:hover {

            color: #d4af37 !important;
        }


        .nav-link.active {

            color: #d4af37 !important;

            font-weight: 700;
        }


        /* =====================================================
           MAIN
        ===================================================== */

        .main-container {

            max-width: 950px;

            margin: auto;

            padding:
                55px 20px 70px;
        }


        /* =====================================================
           SUCCESS CARD
        ===================================================== */

        .success-card {

            background: white;

            border-radius: 22px;

            padding: 45px;

            text-align: center;

            box-shadow:
                0 12px 40px
                rgba(7, 26, 58, 0.08);

            border:
                1px solid #e5e7eb;
        }


        /* =====================================================
           SUCCESS ICON
        ===================================================== */

        .success-icon {

            width: 85px;

            height: 85px;

            margin: auto;

            border-radius: 50%;

            background: #edf9f1;

            color: #198754;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 45px;

            box-shadow:
                0 8px 20px
                rgba(25, 135, 84, 0.12);
        }


        .success-card h1 {

            color: #071a3a;

            margin-top: 22px;

            margin-bottom: 10px;

            font-weight: 700;
        }


        .success-text {

            color: #64748b;

            margin-bottom: 0;

            line-height: 1.7;
        }


        /* =====================================================
           STATUS
        ===================================================== */

        .status-badge {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            margin-top: 18px;

            padding:
                8px 16px;

            border-radius: 50px;

            font-size: 14px;

            font-weight: 700;
        }


        .status-pending {

            background: #fff7df;

            color: #997500;
        }


        .status-preparing {

            background: #fff0df;

            color: #a55b00;
        }


        .status-ready {

            background: #e9f8ef;

            color: #168044;
        }


        .status-delivered {

            background: #e7f1ff;

            color: #1d5fa7;
        }


        .status-cancelled {

            background: #fdecec;

            color: #b42318;
        }


        /* =====================================================
           ORDER INFORMATION
        ===================================================== */

        .order-info {

            margin-top: 30px;

            padding: 24px;

            border-radius: 16px;

            background: #f7f8fa;

            text-align: left;

            border:
                1px solid #e5e7eb;
        }


        .section-title {

            color: #071a3a;

            font-size: 18px;

            font-weight: 700;

            margin-bottom: 15px;
        }


        .info-row {

            display: flex;

            justify-content: space-between;

            gap: 20px;

            padding: 11px 0;

            border-bottom:
                1px solid #e5e7eb;
        }


        .info-row:last-child {

            border-bottom: none;
        }


        .info-label {

            color: #64748b;
        }


        .info-value {

            color: #071a3a;

            font-weight: 700;

            text-align: right;
        }


        /* =====================================================
           ITEMS
        ===================================================== */

        .items-section {

            margin-top: 28px;

            text-align: left;
        }


        .item-card {

            margin-top: 15px;

            padding: 22px;

            background: white;

            border:
                1px solid #e5e7eb;

            border-radius: 16px;

            transition: 0.2s;
        }


        .item-card:hover {

            border-color: #d4af37;

            box-shadow:
                0 8px 25px
                rgba(7, 26, 58, 0.06);
        }


        .item-header {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;
        }


        .item-title {

            color: #071a3a;

            font-size: 19px;

            font-weight: 700;
        }


        .item-price {

            color: #071a3a;

            font-weight: 700;

            white-space: nowrap;
        }


        .quantity {

            color: #64748b;

            font-size: 14px;

            margin-top: 5px;
        }


        /* =====================================================
           OPTIONS
        ===================================================== */

        .option-title {

            margin-top: 17px;

            margin-bottom: 8px;

            color: #071a3a;

            font-size: 14px;

            font-weight: 700;
        }


        .option-list {

            margin: 0;

            padding: 0;

            list-style: none;
        }


        .option-list li {

            padding: 6px 0;

            color: #64748b;

            font-size: 14px;
        }


        .option-list i {

            color: #d4af37;

            margin-right: 6px;
        }


        .item-total {

            margin-top: 16px;

            padding-top: 13px;

            border-top:
                1px solid #e5e7eb;

            color: #071a3a;

            font-weight: 700;

            text-align: right;
        }


        /* =====================================================
           TOTAL
        ===================================================== */

        .total-box {

            margin-top: 28px;

            padding: 22px 25px;

            background: #071a3a;

            color: white;

            border-radius: 16px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;
        }


        .total-label {

            font-weight: 600;

            font-size: 16px;
        }


        .total-amount {

            color: #d4af37;

            font-size: 26px;

            font-weight: 700;

            white-space: nowrap;
        }


        /* =====================================================
           BUTTONS
        ===================================================== */

        .action-buttons {

            margin-top: 28px;

            display: flex;

            justify-content: center;

            flex-wrap: wrap;

            gap: 10px;
        }


        .btn-gold {

            background: #d4af37;

            color: #071a3a;

            border: none;

            padding:
                12px 23px;

            border-radius: 9px;

            font-weight: 700;

            text-decoration: none;

            transition: 0.2s;
        }


        .btn-gold:hover {

            background: #b99525;

            color: white;

            transform: translateY(-1px);
        }


        .btn-outline-gold {

            border:
                1px solid #d4af37;

            color: #071a3a;

            padding:
                11px 23px;

            border-radius: 9px;

            font-weight: 700;

            text-decoration: none;

            transition: 0.2s;
        }


        .btn-outline-gold:hover {

            background: #d4af37;

            color: #071a3a;
        }


        .btn-dark-outline {

            border:
                1px solid #071a3a;

            color: #071a3a;

            padding:
                11px 23px;

            border-radius: 9px;

            font-weight: 700;

            text-decoration: none;

            transition: 0.2s;
        }


        .btn-dark-outline:hover {

            background: #071a3a;

            color: white;
        }


        /* =====================================================
           FOOTER
        ===================================================== */

        footer {

            margin-top: 10px;

            background: #071a3a;

            color: white;

            text-align: center;

            padding: 28px 20px;
        }


        footer strong {

            color: #d4af37;

            font-size: 17px;
        }


        footer div {

            margin-top: 4px;

            color: #dbe3ef;
        }


        footer small {

            display: block;

            margin-top: 7px;

            color: #94a3b8;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 700px) {

            .main-container {

                padding:
                    35px 15px 50px;
            }


            .success-card {

                padding: 28px 18px;
            }


            .success-card h1 {

                font-size: 28px;
            }


            .order-info {

                padding: 18px;
            }


            .info-row {

                flex-direction: column;

                gap: 3px;
            }


            .info-value {

                text-align: left;
            }


            .item-header {

                flex-direction: column;

                gap: 5px;
            }


            .total-box {

                flex-direction: column;

                align-items: flex-start;
            }


            .total-amount {

                font-size: 24px;
            }


            .action-buttons {

                flex-direction: column;
            }


            .action-buttons a {

                width: 100%;

                text-align: center;
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


        <div class="d-flex align-items-center">

            <a
                class="nav-link d-none d-md-inline-block"
                href="dashboard.php"
            >
                Dashboard
            </a>


            <a
                class="nav-link d-none d-md-inline-block"
                href="rooms.php"
            >
                Rooms
            </a>


            <a
                class="nav-link d-none d-md-inline-block"
                href="bookings.php"
            >
                My Bookings
            </a>


            <a
                class="nav-link active d-none d-md-inline-block"
                href="meals.php"
            >
                Meals & Dining
            </a>

        </div>

    </div>

</nav>


<!-- =====================================================
     MAIN
===================================================== -->

<div class="main-container">


    <div class="success-card">


        <!-- SUCCESS ICON -->

        <div class="success-icon">

            <i class="bi bi-check-lg"></i>

        </div>


        <!-- TITLE -->

        <h1>
            Meal Order Confirmed!
        </h1>


        <p class="success-text">

            Your meal order has been successfully placed.
            Our hotel team will prepare it shortly.

        </p>


        <!-- STATUS -->

        <div class="status-badge <?= e($statusClass); ?>">

            <i class="bi <?= e($statusIcon); ?>"></i>

            <?= e($statusText); ?>

        </div>


        <!-- =================================================
             ORDER INFORMATION
        ================================================== -->

        <div class="order-info">

            <div class="section-title">

                <i class="bi bi-receipt"></i>

                Order Information

            </div>


            <div class="info-row">

                <span class="info-label">
                    Order Number
                </span>

                <span class="info-value">

                    #<?= (int)$order["id"]; ?>

                </span>

            </div>


            <div class="info-row">

                <span class="info-label">
                    Booking Code
                </span>

                <span class="info-value">

                    <?= e($order["booking_code"]); ?>

                </span>

            </div>


            <div class="info-row">

                <span class="info-label">
                    Room
                </span>

                <span class="info-value">

                    <?= e($order["room_number"]); ?>

                </span>

            </div>


            <div class="info-row">

                <span class="info-label">
                    Room Type
                </span>

                <span class="info-value">

                    <?= e($order["type_name"]); ?>

                </span>

            </div>


            <div class="info-row">

                <span class="info-label">
                    Stay
                </span>

                <span class="info-value">

                    <?= e($order["check_in"]); ?>

                    &nbsp;–&nbsp;

                    <?= e($order["check_out"]); ?>

                </span>

            </div>


            <div class="info-row">

                <span class="info-label">
                    Ordered At
                </span>

                <span class="info-value">

                    <?= e($order["created_at"]); ?>

                </span>

            </div>

        </div>


        <!-- =================================================
             ORDER ITEMS
        ================================================== -->

        <?php if (!empty($items)): ?>

            <div class="items-section">

                <div class="section-title">

                    <i class="bi bi-cup-hot"></i>

                    Your Meal

                </div>


                <?php foreach ($items as $item): ?>

                    <?php

                    $itemId =
                        (int)$item["id"];

                    ?>

                    <div class="item-card">


                        <div class="item-header">

                            <div>

                                <div class="item-title">

                                    <?= e(
                                        $item["meal_name"]
                                    ); ?>

                                </div>


                                <div class="quantity">

                                    Quantity:
                                    <?= (int)$item["quantity"]; ?>

                                </div>

                            </div>


                            <div class="item-price">

                                LKR
                                <?= number_format(
                                    (float)$item["price"],
                                    2
                                ); ?>

                            </div>

                        </div>


                        <!-- OPTIONS -->

                        <?php if (
                            isset($optionsByItem[$itemId])
                            &&
                            !empty(
                                $optionsByItem[$itemId]
                            )
                        ): ?>

                            <div class="option-title">

                                Selected Options

                            </div>


                            <ul class="option-list">

                                <?php foreach (
                                    $optionsByItem[$itemId]
                                    as $option
                                ): ?>

                                    <li>

                                        <i
                                            class="bi bi-check-circle-fill"
                                        ></i>

                                        <?= e(
                                            $option["option_name"]
                                        ); ?>


                                        <?php if (
                                            (float)$option["price"] > 0
                                        ): ?>

                                            <span>

                                                — + LKR
                                                <?= number_format(
                                                    (float)$option["price"],
                                                    2
                                                ); ?>

                                            </span>

                                        <?php else: ?>

                                            <span>
                                                — Included
                                            </span>

                                        <?php endif; ?>

                                    </li>

                                <?php endforeach; ?>

                            </ul>

                        <?php endif; ?>


                        <!-- ITEM TOTAL -->

                        <div class="item-total">

                            Item Total:

                            LKR
                            <?= number_format(
                                (float)$item["subtotal"],
                                2
                            ); ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             TOTAL
        ================================================== -->

        <div class="total-box">

            <span class="total-label">

                <i class="bi bi-wallet2"></i>

                Total Order Amount

            </span>


            <span class="total-amount">

                LKR
                <?= number_format(
                    (float)$order["total_amount"],
                    2
                ); ?>

            </span>

        </div>


        <!-- =================================================
             ACTION BUTTONS
        ================================================== -->

        <div class="action-buttons">


            <a
                href="meals.php"
                class="btn-gold"
            >

                <i class="bi bi-cup-hot"></i>

                Order Another Meal

            </a>


            <a
                href="bookings.php"
                class="btn-outline-gold"
            >

                <i class="bi bi-calendar-check"></i>

                My Bookings

            </a>


            <a
                href="dashboard.php"
                class="btn-dark-outline"
            >

                <i class="bi bi-house"></i>

                Dashboard

            </a>

        </div>


    </div>

</div>


<!-- =====================================================
     FOOTER
===================================================== -->

<footer>

    <strong>
        Velora Hotel
    </strong>


    <div>
        Meals & Dining
    </div>


    <small>

        © <?= date("Y"); ?>
        Velora Hotel.
        All rights reserved.

    </small>

</footer>


</body>

</html>