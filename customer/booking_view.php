<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

startSession();
requireRole("customer");

$customer_id = $_SESSION["user_id"];
$booking_id = $_GET["id"] ?? "";


/* =====================================================
   VALIDATE BOOKING ID
===================================================== */

if (!is_numeric($booking_id)) {
    header("Location: bookings.php");
    exit;
}


/* =====================================================
   GET BOOKING DETAILS
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
        b.created_at,

        r.room_number,
        r.floor_number,
        r.description AS room_description,

        rt.type_name,
        rt.description AS type_description,
        rt.capacity

    FROM bookings b

    INNER JOIN rooms r
        ON b.room_id = r.id

    INNER JOIN room_types rt
        ON r.room_type_id = rt.id

    WHERE b.id = ?
    AND b.customer_id = ?

    LIMIT 1
");

$stmt->execute([
    $booking_id,
    $customer_id
]);

$booking = $stmt->fetch(PDO::FETCH_ASSOC);


/* =====================================================
   BOOKING NOT FOUND
===================================================== */

if (!$booking) {
    header("Location: bookings.php");
    exit;
}


/* =====================================================
   STATUS CLASS
===================================================== */

function bookingViewStatusClass($status)
{
    switch ($status) {

        case "confirmed":
            return "status-confirmed";

        case "pending":
            return "status-pending";

        case "checked_in":
            return "status-checked";

        case "checked_out":
            return "status-completed";

        case "cancelled":
            return "status-cancelled";

        default:
            return "status-default";
    }
}


/* =====================================================
   STATUS ICON
===================================================== */

function bookingViewStatusIcon($status)
{
    switch ($status) {

        case "confirmed":
            return "bi-check-circle-fill";

        case "pending":
            return "bi-clock-fill";

        case "checked_in":
            return "bi-door-open-fill";

        case "checked_out":
            return "bi-check2-all";

        case "cancelled":
            return "bi-x-circle-fill";

        default:
            return "bi-info-circle-fill";
    }
}


/* =====================================================
   EDIT / CANCEL PERMISSION
===================================================== */

$canEdit = in_array(
    strtolower($booking["booking_status"]),
    ["pending", "confirmed"]
);

$canCancel = in_array(
    strtolower($booking["booking_status"]),
    ["pending", "confirmed"]
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
        Booking Details - Velora Hotel
    </title>


    <!-- Bootstrap 5 -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <!-- Velora Main CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/velora.css"
    >


    <style>

        /* =================================================
           PAGE
        ================================================= */

        body {
            background: #f8fafc;
        }


        .page-wrapper {
            padding: 45px 0 70px;
        }


        .page-title {
            color: #0f172a;
            font-weight: 700;
        }


        .page-subtitle {
            color: #64748b;
        }


        /* =================================================
           NAVBAR
        ================================================= */

        .velora-navbar {
            background: #0f172a;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.15);
        }


        .navbar-brand {
            color: #d4af37 !important;
            font-weight: 700;
            letter-spacing: 1px;
        }


        .navbar-brand i {
            color: #d4af37;
        }


        .nav-link {
            color: #e2e8f0 !important;
        }


        .nav-link:hover,
        .nav-link.active {
            color: #d4af37 !important;
        }


        /* =================================================
           MAIN CONTAINER
        ================================================= */

        .booking-container {
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 35px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }


        /* =================================================
           BOOKING HEADER
        ================================================= */

        .booking-header {
            background: #0f172a;
            color: #ffffff;
            padding: 28px 30px;
        }


        .booking-code {
            color: #d4af37;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }


        .booking-header h3 {
            margin: 5px 0 0;
            font-weight: 700;
        }


        /* =================================================
           STATUS
        ================================================= */

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 14px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
        }


        .status-confirmed {
            background: #dcfce7;
            color: #166534;
        }


        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }


        .status-checked {
            background: #dbeafe;
            color: #1e40af;
        }


        .status-completed {
            background: #ede9fe;
            color: #6d28d9;
        }


        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }


        .status-default {
            background: #f1f5f9;
            color: #475569;
        }


        /* =================================================
           BODY
        ================================================= */

        .booking-body {
            padding: 30px;
        }


        /* =================================================
           SECTION
        ================================================= */

        .section-title {
            color: #0f172a;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 10px;
        }


        .section-title i {
            color: #d4af37;
            margin-right: 8px;
        }


        .gold-divider {
            width: 55px;
            height: 3px;
            background: #d4af37;
            border-radius: 5px;
            margin-bottom: 25px;
        }


        /* =================================================
           ROOM CARD
        ================================================= */

        .room-card {
            border: 1px solid #e5e7eb;
            border-radius: 15px;
            padding: 22px;
            background: #ffffff;
        }


        .room-type {
            color: #d4af37;
            font-weight: 600;
            font-size: 15px;
        }


        .room-number {
            color: #0f172a;
            font-size: 23px;
            font-weight: 700;
            margin-top: 3px;
        }


        .room-description {
            color: #64748b;
            line-height: 1.7;
            margin-top: 12px;
        }


        /* =================================================
           INFO BOX
        ================================================= */

        .info-box {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 18px;
            height: 100%;
        }


        .info-label {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 7px;
        }


        .info-value {
            color: #0f172a;
            font-size: 16px;
            font-weight: 600;
        }


        .info-icon {
            color: #d4af37;
            font-size: 19px;
            margin-right: 7px;
        }


        /* =================================================
           PRICE BOX
        ================================================= */

        .price-box {
            background: #0f172a;
            border-radius: 15px;
            padding: 25px;
            color: #ffffff;
            height: 100%;
        }


        .price-label {
            color: #cbd5e1;
            font-size: 14px;
        }


        .price-value {
            color: #d4af37;
            font-size: 30px;
            font-weight: 700;
            margin-top: 5px;
        }


        /* =================================================
           SPECIAL REQUEST
        ================================================= */

        .request-box {
            background: #fffbeb;
            border: 1px solid #f5df91;
            border-radius: 14px;
            padding: 18px;
            color: #475569;
            line-height: 1.7;
        }


        /* =================================================
           BUTTONS
        ================================================= */

        .btn-velora {
            background: #d4af37;
            border: 1px solid #d4af37;
            color: #0f172a;
            font-weight: 600;
            border-radius: 9px;
            padding: 10px 18px;
        }


        .btn-velora:hover {
            background: #b8941f;
            border-color: #b8941f;
            color: #ffffff;
        }


        .btn-outline-velora {
            border: 1px solid #0f172a;
            color: #0f172a;
            background: #ffffff;
            font-weight: 600;
            border-radius: 9px;
            padding: 10px 18px;
        }


        .btn-outline-velora:hover {
            background: #0f172a;
            color: #ffffff;
        }


        .btn-danger-soft {
            border: 1px solid #dc2626;
            color: #dc2626;
            background: #ffffff;
            font-weight: 600;
            border-radius: 9px;
            padding: 10px 18px;
        }


        .btn-danger-soft:hover {
            background: #dc2626;
            color: #ffffff;
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 768px) {

            .page-wrapper {
                padding: 25px 0 45px;
            }

            .booking-header {
                padding: 22px;
            }

            .booking-body {
                padding: 20px;
            }

            .booking-header .text-md-end {
                text-align: left !important;
                margin-top: 15px;
            }

            .price-value {
                font-size: 25px;
            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar navbar-expand-lg velora-navbar">

    <div class="container">

        <a
            class="navbar-brand"
            href="dashboard.php"
        >

            <i class="bi bi-building-fill me-2"></i>

            Velora Hotel

        </a>


        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarNav"
        >

            <span class="navbar-toggler-icon"></span>

        </button>


        <div
            class="collapse navbar-collapse"
            id="navbarNav"
        >

            <ul class="navbar-nav ms-auto align-items-lg-center">

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="dashboard.php"
                    >

                        <i class="bi bi-house-door me-1"></i>

                        Dashboard

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="rooms.php"
                    >

                        <i class="bi bi-door-open me-1"></i>

                        Rooms

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link active"
                        href="bookings.php"
                    >

                        <i class="bi bi-calendar-check me-1"></i>

                        My Bookings

                    </a>

                </li>


                <li class="nav-item ms-lg-2">

                    <a
                        class="btn btn-velora btn-sm"
                        href="../auth/logout.php"
                    >

                        <i class="bi bi-box-arrow-right me-1"></i>

                        Logout

                    </a>

                </li>

            </ul>

        </div>

    </div>

</nav>



<!-- =====================================================
     MAIN PAGE
===================================================== -->

<main class="page-wrapper">

    <div class="container">


        <!-- PAGE TITLE -->

        <div class="mb-4">

            <h2 class="page-title">

                <i class="bi bi-receipt me-2"></i>

                Booking Details

            </h2>

            <p class="page-subtitle mb-0">

                View complete information about your reservation.

            </p>

        </div>



        <!-- =================================================
             BOOKING CARD
        ================================================= -->

        <div class="booking-container">


            <!-- HEADER -->

            <div class="booking-header">

                <div class="row align-items-center">

                    <div class="col-md-8">

                        <div class="booking-code">

                            Booking Code

                        </div>

                        <h3>

                            <?= e($booking["booking_code"]) ?>

                        </h3>

                    </div>


                    <div class="col-md-4 text-md-end">

                        <span
                            class="status-badge <?= e(
                                bookingViewStatusClass(
                                    $booking["booking_status"]
                                )
                            ) ?>"
                        >

                            <i
                                class="bi <?= e(
                                    bookingViewStatusIcon(
                                        $booking["booking_status"]
                                    )
                                ) ?>"
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

                </div>

            </div>



            <!-- BODY -->

            <div class="booking-body">


                <!-- =================================================
                     ROOM INFORMATION
                ================================================= -->

                <div class="mb-5">

                    <h4 class="section-title">

                        <i class="bi bi-door-open-fill"></i>

                        Room Information

                    </h4>

                    <div class="gold-divider"></div>


                    <div class="room-card">

                        <div class="row align-items-center">

                            <div class="col-md-8">

                                <div class="room-type">

                                    <?= e($booking["type_name"]) ?>

                                </div>


                                <div class="room-number">

                                    Room <?= e($booking["room_number"]) ?>

                                </div>


                                <div class="text-muted mt-2">

                                    <i class="bi bi-building me-1"></i>

                                    Floor
                                    <?= e($booking["floor_number"]) ?>


                                    <span class="mx-2">|</span>


                                    <i class="bi bi-people me-1"></i>

                                    Capacity:
                                    <?= e($booking["capacity"]) ?>
                                    guests

                                </div>


                                <?php if (!empty($booking["room_description"])): ?>

                                    <div class="room-description">

                                        <?= e(
                                            $booking["room_description"]
                                        ) ?>

                                    </div>

                                <?php endif; ?>

                            </div>


                            <div class="col-md-4 text-md-end mt-3 mt-md-0">

                                <div class="text-muted small">

                                    Room Price / Night

                                </div>


                                <div
                                    style="
                                        color:#d4af37;
                                        font-size:22px;
                                        font-weight:700;
                                    "
                                >

                                    Rs.
                                    <?= number_format(
                                        (float)$booking["room_price"],
                                        2
                                    ) ?>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>



                <!-- =================================================
                     STAY INFORMATION
                ================================================= -->

                <div class="mb-5">

                    <h4 class="section-title">

                        <i class="bi bi-calendar-event"></i>

                        Stay Information

                    </h4>

                    <div class="gold-divider"></div>


                    <div class="row g-3">


                        <div class="col-md-4">

                            <div class="info-box">

                                <div class="info-label">

                                    <i
                                        class="
                                            bi
                                            bi-calendar-check
                                            info-icon
                                        "
                                    ></i>

                                    Check-in

                                </div>


                                <div class="info-value">

                                    <?= date(
                                        "d M Y",
                                        strtotime(
                                            $booking["check_in"]
                                        )
                                    ) ?>

                                </div>

                            </div>

                        </div>



                        <div class="col-md-4">

                            <div class="info-box">

                                <div class="info-label">

                                    <i
                                        class="
                                            bi
                                            bi-calendar-x
                                            info-icon
                                        "
                                    ></i>

                                    Check-out

                                </div>


                                <div class="info-value">

                                    <?= date(
                                        "d M Y",
                                        strtotime(
                                            $booking["check_out"]
                                        )
                                    ) ?>

                                </div>

                            </div>

                        </div>



                        <div class="col-md-4">

                            <div class="info-box">

                                <div class="info-label">

                                    <i
                                        class="
                                            bi
                                            bi-moon-stars
                                            info-icon
                                        "
                                    ></i>

                                    Number of Nights

                                </div>


                                <div class="info-value">

                                    <?= e(
                                        $booking["number_of_nights"]
                                    ) ?>

                                    Night(s)

                                </div>

                            </div>

                        </div>

                    </div>

                </div>



                <!-- =================================================
                     GUEST INFORMATION
                ================================================= -->

                <div class="mb-5">

                    <h4 class="section-title">

                        <i class="bi bi-people-fill"></i>

                        Guest Information

                    </h4>

                    <div class="gold-divider"></div>


                    <div class="row g-3">


                        <div class="col-md-6">

                            <div class="info-box">

                                <div class="info-label">

                                    <i
                                        class="
                                            bi
                                            bi-person-fill
                                            info-icon
                                        "
                                    ></i>

                                    Adults

                                </div>


                                <div class="info-value">

                                    <?= e(
                                        $booking["adults"]
                                    ) ?>

                                    Adult(s)

                                </div>

                            </div>

                        </div>



                        <div class="col-md-6">

                            <div class="info-box">

                                <div class="info-label">

                                    <i
                                        class="
                                            bi
                                            bi-person-hearts
                                            info-icon
                                        "
                                    ></i>

                                    Children

                                </div>


                                <div class="info-value">

                                    <?= e(
                                        $booking["children"]
                                    ) ?>

                                    Child(ren)

                                </div>

                            </div>

                        </div>

                    </div>

                </div>



                <!-- =================================================
                     PAYMENT INFORMATION
                ================================================= -->

                <div class="mb-5">

                    <h4 class="section-title">

                        <i class="bi bi-credit-card-fill"></i>

                        Payment Information

                    </h4>

                    <div class="gold-divider"></div>


                    <div class="row g-4">


                        <div class="col-md-6">

                            <div class="info-box">

                                <div class="info-label">

                                    Payment Status

                                </div>


                                <div class="info-value">

                                    <i
                                        class="
                                            bi
                                            bi-wallet2
                                            info-icon
                                        "
                                    ></i>

                                    <?= e(
                                        ucwords(
                                            str_replace(
                                                "_",
                                                " ",
                                                $booking["payment_status"]
                                            )
                                        )
                                    ) ?>

                                </div>

                            </div>

                        </div>



                        <div class="col-md-6">

                            <div class="price-box">

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


                                <div
                                    class="
                                        small
                                        text-light
                                        opacity-75
                                        mt-1
                                    "
                                >

                                    <?= e(
                                        $booking["number_of_nights"]
                                    ) ?>

                                    night(s)

                                    ×

                                    Rs.
                                    <?= number_format(
                                        (float)$booking["room_price"],
                                        2
                                    ) ?>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>



                <!-- =================================================
                     SPECIAL REQUEST
                ================================================= -->

                <?php if (!empty($booking["special_request"])): ?>

                    <div class="mb-5">

                        <h4 class="section-title">

                            <i
                                class="
                                    bi
                                    bi-chat-left-text-fill
                                "
                            ></i>

                            Special Request

                        </h4>


                        <div class="gold-divider"></div>


                        <div class="request-box">

                            <i
                                class="
                                    bi
                                    bi-quote
                                    me-2
                                "
                            ></i>

                            <?= nl2br(
                                e($booking["special_request"])
                            ) ?>

                        </div>

                    </div>

                <?php endif; ?>



                <!-- =================================================
                     BOOKING INFORMATION
                ================================================= -->

                <div class="mb-4">

                    <h4 class="section-title">

                        <i class="bi bi-info-circle-fill"></i>

                        Booking Information

                    </h4>

                    <div class="gold-divider"></div>


                    <div class="row g-3">


                        <div class="col-md-6">

                            <div class="info-box">

                                <div class="info-label">

                                    Booking Created

                                </div>


                                <div class="info-value">

                                    <?php if (
                                        !empty(
                                            $booking["created_at"]
                                        )
                                    ): ?>

                                        <?= date(
                                            "d M Y, h:i A",
                                            strtotime(
                                                $booking["created_at"]
                                            )
                                        ) ?>

                                    <?php else: ?>

                                        N/A

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>



                        <div class="col-md-6">

                            <div class="info-box">

                                <div class="info-label">

                                    Booking Status

                                </div>


                                <div class="info-value">

                                    <span
                                        class="
                                            status-badge
                                            <?= e(
                                                bookingViewStatusClass(
                                                    $booking[
                                                        "booking_status"
                                                    ]
                                                )
                                            ) ?>
                                        "
                                    >

                                        <i
                                            class="
                                                bi
                                                <?= e(
                                                    bookingViewStatusIcon(
                                                        $booking[
                                                            "booking_status"
                                                        ]
                                                    )
                                                ) ?>
                                            "
                                        ></i>

                                        <?= e(
                                            ucwords(
                                                str_replace(
                                                    "_",
                                                    " ",
                                                    $booking[
                                                        "booking_status"
                                                    ]
                                                )
                                            )
                                        ) ?>

                                    </span>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>



                <!-- =================================================
                     ACTION BUTTONS
                ================================================= -->

                <div
                    class="
                        d-flex
                        flex-wrap
                        gap-2
                        justify-content-between
                        align-items-center
                        pt-3
                        border-top
                    "
                >


                    <!-- BACK -->

                    <a
                        href="bookings.php"
                        class="btn btn-outline-velora"
                    >

                        <i
                            class="
                                bi
                                bi-arrow-left
                                me-1
                            "
                        ></i>

                        Back to My Bookings

                    </a>



                    <!-- RIGHT BUTTONS -->

                    <div
                        class="
                            d-flex
                            flex-wrap
                            gap-2
                        "
                    >


                        <?php if ($canEdit): ?>

                            <a
                                href="
                                    booking_edit.php?id=
                                    <?= (int)$booking["id"] ?>
                                "
                                class="btn btn-velora"
                            >

                                <i
                                    class="
                                        bi
                                        bi-pencil-square
                                        me-1
                                    "
                                ></i>

                                Edit Booking

                            </a>

                        <?php endif; ?>



                        <?php if ($canCancel): ?>

                            <a
                                href="
                                    booking_delete.php?id=
                                    <?= (int)$booking["id"] ?>
                                "
                                class="btn btn-danger-soft"
                                onclick="
                                    return confirm(
                                        'Are you sure you want to cancel this booking?'
                                    );
                                "
                            >

                                <i
                                    class="
                                        bi
                                        bi-x-circle
                                        me-1
                                    "
                                ></i>

                                Cancel Booking

                            </a>

                        <?php endif; ?>


                    </div>

                </div>


            </div>

        </div>

    </div>

</main>



<!-- =====================================================
     BOOTSTRAP JS
===================================================== -->

<script
    src="
        https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/
        dist/js/bootstrap.bundle.min.js
    "
></script>


</body>

</html>