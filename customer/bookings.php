<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

startSession();
requireRole("customer");

$customer_id = $_SESSION["user_id"];


/* =====================================================
   GET CUSTOMER BOOKINGS
===================================================== */

$stmt = $pdo->prepare("
    SELECT
        b.id,
        b.booking_code,
        b.check_in,
        b.check_out,
        b.adults,
        b.children,
        b.number_of_nights,
        b.room_price,
        b.total_amount,
        b.booking_status,
        b.payment_status,
        b.special_request,

        r.room_number,
        r.description,

        rt.type_name,
        rt.capacity,

        CASE
            WHEN EXISTS (
                SELECT 1
                FROM reviews rv
                WHERE rv.booking_id = b.id
                AND rv.customer_id = ?
            )
            THEN 1
            ELSE 0
        END AS has_review

    FROM bookings b

    INNER JOIN rooms r
        ON b.room_id = r.id

    INNER JOIN room_types rt
        ON r.room_type_id = rt.id

    WHERE b.customer_id = ?

    ORDER BY b.id DESC
");

$stmt->execute([
    $customer_id,
    $customer_id
]);

$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   STATUS CLASS
===================================================== */

function bookingStatusClass($status)
{
    switch (strtolower($status)) {

        case "confirmed":
            return "status-confirmed";

        case "checked_in":
            return "status-checked-in";

        case "checked_out":
            return "status-completed";

        case "cancelled":
            return "status-cancelled";

        case "pending":
        default:
            return "status-pending";
    }
}


/* =====================================================
   STATUS ICON
===================================================== */

function bookingStatusIcon($status)
{
    switch (strtolower($status)) {

        case "confirmed":
            return "bi-check-circle-fill";

        case "checked_in":
            return "bi-door-open-fill";

        case "checked_out":
            return "bi-check2-all";

        case "cancelled":
            return "bi-x-circle-fill";

        case "pending":
        default:
            return "bi-clock-fill";
    }
}


/* =====================================================
   PAYMENT CLASS
===================================================== */

function paymentStatusClass($status)
{
    switch (strtolower($status)) {

        case "paid":
            return "payment-paid";

        case "partial":
            return "payment-partial";

        case "pending":
        default:
            return "payment-pending";
    }
}


/* =====================================================
   SUMMARY COUNTS
===================================================== */

$totalBookings = count($bookings);

$pendingBookings = 0;
$confirmedBookings = 0;
$completedBookings = 0;

foreach ($bookings as $booking) {

    $status = strtolower($booking["booking_status"]);

    if ($status === "pending") {
        $pendingBookings++;
    }

    if ($status === "confirmed") {
        $confirmedBookings++;
    }

    if ($status === "checked_out") {
        $completedBookings++;
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

    <title>My Bookings | Velora Hotel</title>


    <!-- =================================================
         BOOTSTRAP
    ================================================= -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =================================================
         BOOTSTRAP ICONS
    ================================================= -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <!-- =================================================
         VELORA CSS
    ================================================= -->

    <link
        rel="stylesheet"
        href="../assets/css/velora.css"
    >


    <style>

        /* =================================================
           GLOBAL
        ================================================= */

        * {
            box-sizing: border-box;
        }

        body {

            margin: 0;

            font-family:
                "Segoe UI",
                Arial,
                sans-serif;

            background:
                #f6f5f1;

            color:
                #172033;

        }


        /* =================================================
           NAVBAR
        ================================================= */

        .navbar-custom {

            min-height: 76px;

            background:
                #0f172a;

            border-bottom:
                2px solid #c9a227;

            box-shadow:
                0 4px 18px
                rgba(15,23,42,.12);

        }


        .brand {

            text-decoration: none;

            color: white;

            display: flex;

            align-items: center;

            gap: 12px;

        }


        .brand-icon {

            width: 44px;

            height: 44px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 20px;

            color: #0f172a;

            background:
                linear-gradient(
                    135deg,
                    #c9a227,
                    #e8cf70
                );

        }


        .brand-title {

            font-size: 20px;

            font-weight: 800;

            color: white;

            letter-spacing: .2px;

        }


        .brand-subtitle {

            display: block;

            font-size: 11px;

            color: #cbd5e1;

        }


        /* =================================================
           PAGE
        ================================================= */

        .page-container {

            max-width: 1200px;

            margin: auto;

            padding:
                38px 20px 60px;

        }


        .page-heading {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;

        }


        .page-heading h1 {

            margin: 0 0 6px;

            font-size: 29px;

            font-weight: 800;

            color: #0f172a;

        }


        .page-heading p {

            margin: 0;

            color: #64748b;

            font-size: 14px;

        }


        /* =================================================
           MAIN BUTTON
        ================================================= */

        .main-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            padding:
                11px 18px;

            border-radius: 9px;

            background:
                linear-gradient(
                    135deg,
                    #c9a227,
                    #d4af37
                );

            color: #0f172a;

            text-decoration: none;

            font-size: 14px;

            font-weight: 800;

            border: none;

            transition: .2s;

        }


        .main-btn:hover {

            color: #0f172a;

            transform:
                translateY(-2px);

            box-shadow:
                0 8px 20px
                rgba(201,162,39,.25);

        }


        /* =================================================
           SUMMARY CARDS
        ================================================= */

        .summary-card {

            background: #ffffff;

            border:
                1px solid #e5e7eb;

            border-radius: 16px;

            padding: 20px;

            box-shadow:
                0 5px 18px
                rgba(15,23,42,.05);

            transition: .2s;

        }


        .summary-card:hover {

            transform:
                translateY(-2px);

            box-shadow:
                0 9px 25px
                rgba(15,23,42,.08);

        }


        .summary-icon {

            width: 48px;

            height: 48px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                rgba(201,162,39,.12);

            color: #b8952e;

            font-size: 21px;

        }


        .summary-number {

            font-size: 26px;

            font-weight: 800;

            margin-top: 12px;

            color: #0f172a;

        }


        .summary-label {

            color: #64748b;

            font-size: 13px;

        }


        /* =================================================
           BOOKING CARD
        ================================================= */

        .booking-card {

            background: #ffffff;

            border:
                1px solid #e5e7eb;

            border-radius: 18px;

            margin-top: 25px;

            overflow: hidden;

            box-shadow:
                0 7px 22px
                rgba(15,23,42,.05);

            transition: .2s;

        }


        .booking-card:hover {

            box-shadow:
                0 12px 32px
                rgba(15,23,42,.09);

        }


        /* =================================================
           BOOKING HEADER
        ================================================= */

        .booking-top {

            padding:
                19px 22px;

            border-bottom:
                1px solid #e5e7eb;

            display: flex;

            justify-content:
                space-between;

            align-items: center;

            background:
                #fffdf7;

        }


        .booking-code {

            color: #a88418;

            font-weight: 800;

            font-size: 14px;

        }


        /* =================================================
           STATUS
        ================================================= */

        .status {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding:
                7px 12px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 800;

        }


        .status-pending {

            color: #92400e;

            background: #fef3c7;

        }


        .status-confirmed {

            color: #166534;

            background: #dcfce7;

        }


        .status-checked-in {

            color: #075985;

            background: #e0f2fe;

        }


        .status-completed {

            color: #3730a3;

            background: #e0e7ff;

        }


        .status-cancelled {

            color: #991b1b;

            background: #fee2e2;

        }


        /* =================================================
           BOOKING BODY
        ================================================= */

        .booking-body {

            padding: 22px;

        }


        /* =================================================
           ROOM
        ================================================= */

        .room-section {

            display: flex;

            align-items: center;

            gap: 15px;

            margin-bottom: 22px;

        }


        .room-icon {

            width: 60px;

            height: 60px;

            flex-shrink: 0;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 14px;

            background:
                rgba(201,162,39,.12);

            color: #b8952e;

            font-size: 27px;

        }


        .room-name {

            font-size: 19px;

            font-weight: 800;

            color: #0f172a;

            margin-bottom: 3px;

        }


        .room-type {

            color: #64748b;

            font-size: 13px;

        }


        /* =================================================
           DETAIL BOX
        ================================================= */

        .detail-box {

            height: 100%;

            padding: 14px;

            background: #f8fafc;

            border:
                1px solid #e5e7eb;

            border-radius: 11px;

        }


        .detail-label {

            display: block;

            color: #64748b;

            font-size: 11px;

            margin-bottom: 5px;

        }


        .detail-value {

            font-size: 14px;

            font-weight: 700;

            color: #172033;

        }


        .detail-icon {

            color: #b8952e;

            margin-right: 4px;

        }


        /* =================================================
           PAYMENT BADGE
        ================================================= */

        .payment-badge {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding:
                5px 9px;

            border-radius: 15px;

            font-size: 11px;

            font-weight: 800;

        }


        .payment-paid {

            color: #166534;

            background: #dcfce7;

        }


        .payment-partial {

            color: #92400e;

            background: #fef3c7;

        }


        .payment-pending {

            color: #64748b;

            background: #e2e8f0;

        }


        /* =================================================
           PRICE
        ================================================= */

        .price-section {

            margin-top: 20px;

            padding: 18px;

            border-radius: 13px;

            background:
                linear-gradient(
                    135deg,
                    #fffdf5,
                    #faf9f3
                );

            border:
                1px solid
                rgba(201,162,39,.28);

            display: flex;

            justify-content:
                space-between;

            align-items: center;

            gap: 20px;

        }


        .price-label {

            color: #64748b;

            font-size: 12px;

            margin-bottom: 3px;

        }


        .price-small {

            color: #475569;

            font-size: 13px;

        }


        .price-value {

            color: #a88418;

            font-size: 22px;

            font-weight: 850;

        }


        /* =================================================
           ACTION BUTTONS
        ================================================= */

        .booking-actions {

            display: flex;

            justify-content:
                flex-end;

            align-items: center;

            gap: 9px;

            margin-top: 18px;

            padding-top: 18px;

            border-top:
                1px solid #e5e7eb;

            flex-wrap: wrap;

        }


        .action-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 6px;

            padding:
                9px 14px;

            border-radius: 8px;

            text-decoration: none;

            font-size: 12px;

            font-weight: 750;

            transition: .2s;

        }


        /* VIEW */

        .action-view {

            color: #0f172a;

            background:
                rgba(201,162,39,.12);

            border:
                1px solid
                rgba(201,162,39,.30);

        }


        .action-view:hover {

            color: #0f172a;

            background:
                rgba(201,162,39,.24);

            transform:
                translateY(-1px);

        }


        /* MEALS */

        .action-meals {

            color: #ffffff;

            background:
                #0f172a;

            border:
                1px solid #0f172a;

        }


        .action-meals:hover {

            color: #ffffff;

            background:
                #1e293b;

            transform:
                translateY(-1px);

        }


        /* EDIT */

        .action-edit {

            color: #0f172a;

            background: #f3f4f6;

            border:
                1px solid #d1d5db;

        }


        .action-edit:hover {

            color: #0f172a;

            background: #e5e7eb;

        }


        /* CANCEL */

        .action-cancel {

            color: #991b1b;

            background: #fef2f2;

            border:
                1px solid #fecaca;

        }


        .action-cancel:hover {

            color: white;

            background: #dc2626;

        }


        /* REVIEW */

        .action-review {

            color: #7c5a00;

            background: #fff8df;

            border:
                1px solid #e7c95d;

        }


        .action-review:hover {

            color: #5c4300;

            background: #f8e9a9;

            transform:
                translateY(-1px);

        }


        /* REVIEWED */

        .action-reviewed {

            color: #166534;

            background: #f0fdf4;

            border:
                1px solid #bbf7d0;

        }


        .action-reviewed:hover {

            color: #166534;

            background: #dcfce7;

        }


        /* =================================================
           EMPTY STATE
        ================================================= */

        .empty-state {

            background: #ffffff;

            border:
                1px solid #e5e7eb;

            border-radius: 18px;

            padding:
                60px 25px;

            margin-top: 25px;

            text-align: center;

            box-shadow:
                0 7px 22px
                rgba(15,23,42,.05);

        }


        .empty-icon {

            width: 75px;

            height: 75px;

            margin:
                0 auto 20px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                rgba(201,162,39,.12);

            color: #b8952e;

            font-size: 32px;

        }


        .empty-state h3 {

            font-size: 21px;

            font-weight: 800;

            color: #0f172a;

        }


        .empty-state p {

            max-width: 450px;

            margin:
                0 auto 20px;

            color: #64748b;

            font-size: 14px;

        }


        /* =================================================
           MOBILE
        ================================================= */

        @media (max-width: 768px) {

            .page-container {

                padding:
                    25px 15px 45px;

            }


            .page-heading {

                align-items:
                    flex-start;

                flex-direction:
                    column;

                gap: 15px;

            }


            .page-heading h1 {

                font-size: 24px;

            }


            .brand-subtitle {

                display: none;

            }


            .booking-top {

                align-items:
                    flex-start;

                flex-direction:
                    column;

                gap: 10px;

            }


            .price-section {

                align-items:
                    flex-start;

                flex-direction:
                    column;

            }


            .booking-actions {

                justify-content:
                    stretch;

                flex-direction:
                    column;

            }


            .action-btn {

                width: 100%;

            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar-custom">

    <div class="container-fluid px-3 px-md-4">

        <div
            class="d-flex justify-content-between align-items-center"
            style="min-height:76px;"
        >

            <!-- BRAND -->

            <a
                href="dashboard.php"
                class="brand"
            >

                <div class="brand-icon">

                    <i class="bi bi-building"></i>

                </div>

                <div>

                    <div class="brand-title">
                        Velora Hotel
                    </div>

                    <span class="brand-subtitle">
                        Hotel Management System
                    </span>

                </div>

            </a>


            <!-- DASHBOARD -->

            <a
                href="dashboard.php"
                class="main-btn"
            >

                <i class="bi bi-speedometer2"></i>

                Dashboard

            </a>

        </div>

    </div>

</nav>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="page-container">


    <!-- =================================================
         PAGE HEADING
    ================================================= -->

    <div class="page-heading">

        <div>

            <h1>
                My Bookings
            </h1>

            <p>
                View and manage your hotel reservations.
            </p>

        </div>


        <a
            href="rooms.php"
            class="main-btn"
        >

            <i class="bi bi-plus-lg"></i>

            New Booking

        </a>

    </div>


    <!-- =================================================
         SUMMARY CARDS
    ================================================= -->

    <div class="row g-3">


        <!-- TOTAL -->

        <div class="col-6 col-lg-3">

            <div class="summary-card">

                <div class="summary-icon">

                    <i class="bi bi-calendar-check"></i>

                </div>

                <div class="summary-number">
                    <?= $totalBookings ?>
                </div>

                <div class="summary-label">
                    Total Bookings
                </div>

            </div>

        </div>


        <!-- PENDING -->

        <div class="col-6 col-lg-3">

            <div class="summary-card">

                <div
                    class="summary-icon"
                    style="
                        color:#d97706;
                        background:#fff7ed;
                    "
                >

                    <i class="bi bi-clock"></i>

                </div>

                <div class="summary-number">
                    <?= $pendingBookings ?>
                </div>

                <div class="summary-label">
                    Pending
                </div>

            </div>

        </div>


        <!-- CONFIRMED -->

        <div class="col-6 col-lg-3">

            <div class="summary-card">

                <div
                    class="summary-icon"
                    style="
                        color:#166534;
                        background:#f0fdf4;
                    "
                >

                    <i class="bi bi-check-circle"></i>

                </div>

                <div class="summary-number">
                    <?= $confirmedBookings ?>
                </div>

                <div class="summary-label">
                    Confirmed
                </div>

            </div>

        </div>


        <!-- COMPLETED -->

        <div class="col-6 col-lg-3">

            <div class="summary-card">

                <div
                    class="summary-icon"
                    style="
                        color:#3730a3;
                        background:#eef2ff;
                    "
                >

                    <i class="bi bi-check2-all"></i>

                </div>

                <div class="summary-number">
                    <?= $completedBookings ?>
                </div>

                <div class="summary-label">
                    Completed
                </div>

            </div>

        </div>

    </div>


    <!-- =================================================
         BOOKINGS
    ================================================= -->

    <?php if (empty($bookings)): ?>


        <!-- EMPTY STATE -->

        <div class="empty-state">

            <div class="empty-icon">

                <i class="bi bi-calendar-x"></i>

            </div>

            <h3>
                No Bookings Yet
            </h3>

            <p>
                You haven't made any hotel reservations yet.
                Find a room and make your first booking.
            </p>

            <a
                href="rooms.php"
                class="main-btn"
            >

                <i class="bi bi-door-open"></i>

                Browse Available Rooms

            </a>

        </div>


    <?php else: ?>


        <!-- =================================================
             BOOKING LIST
        ================================================= -->

        <?php foreach ($bookings as $booking): ?>

            <?php

            $status = strtolower(
                $booking["booking_status"]
            );


            /* -------------------------------------------------
               EDIT / CANCEL
            ------------------------------------------------- */

            $canEdit = in_array(
                $status,
                [
                    "pending",
                    "confirmed"
                ],
                true
            );


            $canCancel = in_array(
                $status,
                [
                    "pending",
                    "confirmed"
                ],
                true
            );


            /* -------------------------------------------------
               MEALS
            ------------------------------------------------- */

            $canOrderMeals = in_array(
                $status,
                [
                    "confirmed",
                    "checked_in"
                ],
                true
            );


            /* -------------------------------------------------
               REVIEW
            ------------------------------------------------- */

            $canReview =
                $status === "checked_out";


            $hasReview =
                (int)$booking["has_review"] === 1;


            /* -------------------------------------------------
               PAYMENT
            ------------------------------------------------- */

            $paymentStatus =
                strtolower(
                    $booking["payment_status"]
                );

            ?>


            <!-- =================================================
                 BOOKING CARD
            ================================================= -->

            <div class="booking-card">


                <!-- =================================================
                     HEADER
                ================================================= -->

                <div class="booking-top">

                    <div class="booking-code">

                        <i class="bi bi-receipt me-1"></i>

                        Booking #

                        <?= e(
                            $booking["booking_code"]
                        ) ?>

                    </div>


                    <span
                        class="
                            status
                            <?= bookingStatusClass(
                                $booking["booking_status"]
                            ) ?>
                        "
                    >

                        <i
                            class="
                                bi
                                <?= bookingStatusIcon(
                                    $booking["booking_status"]
                                ) ?>
                            "
                        ></i>

                        <?= e(
                            ucwords(
                                str_replace(
                                    "_",
                                    " ",
                                    $booking["booking_status"]
                                )
                            )
                        ) ?>

                    </span>

                </div>


                <!-- =================================================
                     BODY
                ================================================= -->

                <div class="booking-body">


                    <!-- =================================================
                         ROOM
                    ================================================= -->

                    <div class="room-section">

                        <div class="room-icon">

                            <i class="bi bi-door-open"></i>

                        </div>


                        <div>

                            <div class="room-name">

                                Room
                                <?= e(
                                    $booking["room_number"]
                                ) ?>

                            </div>


                            <div class="room-type">

                                <i class="bi bi-stars me-1"></i>

                                <?= e(
                                    $booking["type_name"]
                                ) ?>

                                <span class="mx-1">
                                    •
                                </span>

                                <?= e(
                                    $booking["capacity"]
                                ) ?>

                                Guests

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         DETAILS
                    ================================================= -->

                    <div class="row g-3">


                        <!-- CHECK IN -->

                        <div class="col-6 col-md-3">

                            <div class="detail-box">

                                <span class="detail-label">

                                    <i
                                        class="
                                            bi
                                            bi-calendar-event
                                            detail-icon
                                        "
                                    ></i>

                                    Check-in

                                </span>

                                <div class="detail-value">

                                    <?= e(
                                        date(
                                            "d M Y",
                                            strtotime(
                                                $booking["check_in"]
                                            )
                                        )
                                    ) ?>

                                </div>

                            </div>

                        </div>


                        <!-- CHECK OUT -->

                        <div class="col-6 col-md-3">

                            <div class="detail-box">

                                <span class="detail-label">

                                    <i
                                        class="
                                            bi
                                            bi-calendar-check
                                            detail-icon
                                        "
                                    ></i>

                                    Check-out

                                </span>

                                <div class="detail-value">

                                    <?= e(
                                        date(
                                            "d M Y",
                                            strtotime(
                                                $booking["check_out"]
                                            )
                                        )
                                    ) ?>

                                </div>

                            </div>

                        </div>


                        <!-- DURATION -->

                        <div class="col-6 col-md-3">

                            <div class="detail-box">

                                <span class="detail-label">

                                    <i
                                        class="
                                            bi
                                            bi-moon-stars
                                            detail-icon
                                        "
                                    ></i>

                                    Duration

                                </span>

                                <div class="detail-value">

                                    <?= (int)$booking["number_of_nights"] ?>

                                    <?= (int)$booking["number_of_nights"] === 1
                                        ? "Night"
                                        : "Nights"
                                    ?>

                                </div>

                            </div>

                        </div>


                        <!-- PAYMENT -->

                        <div class="col-6 col-md-3">

                            <div class="detail-box">

                                <span class="detail-label">

                                    <i
                                        class="
                                            bi
                                            bi-credit-card
                                            detail-icon
                                        "
                                    ></i>

                                    Payment

                                </span>

                                <div class="detail-value">

                                    <span
                                        class="
                                            payment-badge
                                            <?= paymentStatusClass(
                                                $paymentStatus
                                            ) ?>
                                        "
                                    >

                                        <?php if ($paymentStatus === "paid"): ?>

                                            <i class="bi bi-check-circle"></i>

                                        <?php elseif ($paymentStatus === "partial"): ?>

                                            <i class="bi bi-dash-circle"></i>

                                        <?php else: ?>

                                            <i class="bi bi-clock"></i>

                                        <?php endif; ?>


                                        <?= e(
                                            ucfirst(
                                                $paymentStatus
                                            )
                                        ) ?>

                                    </span>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         GUEST INFORMATION
                    ================================================= -->

                    <div
                        class="row g-3 mt-1"
                    >

                        <!-- ADULTS -->

                        <div class="col-6 col-md-3">

                            <div class="detail-box">

                                <span class="detail-label">

                                    <i
                                        class="
                                            bi
                                            bi-person
                                            detail-icon
                                        "
                                    ></i>

                                    Adults

                                </span>

                                <div class="detail-value">

                                    <?= (int)$booking["adults"] ?>

                                </div>

                            </div>

                        </div>


                        <!-- CHILDREN -->

                        <div class="col-6 col-md-3">

                            <div class="detail-box">

                                <span class="detail-label">

                                    <i
                                        class="
                                            bi
                                            bi-people
                                            detail-icon
                                        "
                                    ></i>

                                    Children

                                </span>

                                <div class="detail-value">

                                    <?= (int)$booking["children"] ?>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         PRICE
                    ================================================= -->

                    <div class="price-section">

                        <div>

                            <div class="price-label">

                                Room Price / Night

                            </div>

                            <div class="price-small">

                                Rs.

                                <?= number_format(
                                    (float)$booking["room_price"],
                                    2
                                ) ?>

                                ×

                                <?= (int)$booking["number_of_nights"] ?>

                                nights

                            </div>

                        </div>


                        <div>

                            <div class="price-label">

                                Total Booking Amount

                            </div>

                            <div class="price-value">

                                Rs.

                                <?= number_format(
                                    (float)$booking["total_amount"],
                                    2
                                ) ?>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         ACTION BUTTONS
                    ================================================= -->

                    <div class="booking-actions">


                        <!-- VIEW -->

                        <a
                            href="booking_view.php?id=<?= (int)$booking["id"] ?>"
                            class="action-btn action-view"
                        >

                            <i class="bi bi-eye"></i>

                            View

                        </a>


                        <!-- =================================================
                             ORDER MEALS
                        ================================================= -->

                        <?php if ($canOrderMeals): ?>

                            <a
                                href="meals.php?booking_id=<?= (int)$booking["id"] ?>"
                                class="action-btn action-meals"
                            >

                                <i class="bi bi-cup-hot"></i>

                                Order Meals

                            </a>

                        <?php endif; ?>


                        <!-- =================================================
                             EDIT
                        ================================================= -->

                        <?php if ($canEdit): ?>

                            <a
                                href="booking_edit.php?id=<?= (int)$booking["id"] ?>"
                                class="action-btn action-edit"
                            >

                                <i class="bi bi-pencil-square"></i>

                                Edit

                            </a>

                        <?php endif; ?>


                        <!-- =================================================
                             CANCEL
                        ================================================= -->

                        <?php if ($canCancel): ?>

                            <a
                                href="booking_delete.php?id=<?= (int)$booking["id"] ?>"
                                class="action-btn action-cancel"
                                onclick="
                                    return confirm(
                                        'Are you sure you want to cancel this booking?'
                                    );
                                "
                            >

                                <i class="bi bi-x-circle"></i>

                                Cancel

                            </a>

                        <?php endif; ?>


                        <!-- =================================================
                             LEAVE REVIEW
                        ================================================= -->

                        <?php if ($canReview && !$hasReview): ?>

                            <a
                                href="review_add.php?booking_id=<?= (int)$booking["id"] ?>"
                                class="action-btn action-review"
                            >

                                <i class="bi bi-star-fill"></i>

                                Leave Review

                            </a>

                        <?php endif; ?>


                        <!-- =================================================
                             ALREADY REVIEWED
                        ================================================= -->

                        <?php if ($canReview && $hasReview): ?>

                            <a
                                href="reviews.php"
                                class="action-btn action-reviewed"
                            >

                                <i class="bi bi-check-circle-fill"></i>

                                Reviewed

                            </a>

                        <?php endif; ?>


                    </div>

                </div>

            </div>


        <?php endforeach; ?>


    <?php endif; ?>


</main>


</body>

</html>