<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

startSession();
requireRole("customer");


// =====================================================
// GET BOOKING ID
// =====================================================

$booking_id = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;

if ($booking_id <= 0) {
    die("Invalid booking.");
}


// =====================================================
// CUSTOMER ID
// =====================================================

$customer_id = (int) ($_SESSION["user_id"] ?? 0);

if ($customer_id <= 0) {
    header("Location: ../auth/login.php");
    exit;
}


// =====================================================
// GET BOOKING
// =====================================================

$stmt = $pdo->prepare("
    SELECT
        b.id,
        b.booking_code,
        b.booking_status,
        b.payment_status,
        b.check_in,
        b.check_out,
        b.adults,
        b.children,
        b.number_of_nights,
        b.room_price,
        b.total_amount,
        b.special_request,

        r.room_number,

        rt.type_name,
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


if (!$booking) {
    die("Booking not found.");
}


// =====================================================
// NIGHTS
// =====================================================

$nights = (int) $booking["number_of_nights"];


// =====================================================
// BOOKING STATUS
// =====================================================

$status = strtolower(
    trim($booking["booking_status"])
);


$status_class = "status-pending";
$status_icon = "bi-clock";


if ($status === "confirmed") {

    $status_class = "status-confirmed";
    $status_icon = "bi-check-circle";

} elseif ($status === "checked_in") {

    $status_class = "status-checked";
    $status_icon = "bi-door-open";

} elseif ($status === "checked_out") {

    $status_class = "status-completed";
    $status_icon = "bi-check2-all";

} elseif ($status === "cancelled") {

    $status_class = "status-cancelled";
    $status_icon = "bi-x-circle";

}


// =====================================================
// PAYMENT STATUS
// =====================================================

$payment_status = strtolower(
    trim($booking["payment_status"])
);


$payment_class = "payment-pending";
$payment_icon = "bi-clock";


if ($payment_status === "paid") {

    $payment_class = "payment-paid";
    $payment_icon = "bi-check-circle-fill";

} elseif ($payment_status === "partial") {

    $payment_class = "payment-partial";
    $payment_icon = "bi-hourglass-split";

}


// =====================================================
// FORMATTED STATUS TEXT
// =====================================================

$booking_status_text = ucwords(
    str_replace(
        "_",
        " ",
        $booking["booking_status"]
    )
);


$payment_status_text = ucfirst(
    $booking["payment_status"]
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
        Booking Submitted | Velora Hotel
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

        :root {

            --navy: #071a3a;
            --navy-dark: #04101f;
            --navy-light: #102a4a;

            --gold: #c9a227;
            --gold-light: #e8cf70;
            --gold-dark: #a88418;

            --bg: #f6f5f1;
            --surface: #ffffff;

            --text: #172033;
            --muted: #64748b;

            --border: #e5e7eb;

            --success: #15803d;

        }


        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            min-height: 100vh;

            background:
                linear-gradient(
                    135deg,
                    #f8f9fb,
                    #f3f4f6
                );

            color:
                var(--text);

            font-family:
                "Segoe UI",
                Arial,
                sans-serif;

        }


        /* =================================================
           NAVBAR
        ================================================= */

        .navbar-custom {

            min-height: 76px;

            background:
                var(--navy);

            border-bottom:
                2px solid var(--gold);

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.13);

        }


        .brand {

            text-decoration: none;

            color: white;

            display: flex;

            align-items: center;

            gap: 12px;

        }


        .brand:hover {
            color: white;
        }


        .brand-icon {

            width: 45px;

            height: 45px;

            border-radius: 11px;

            display: flex;

            align-items: center;

            justify-content: center;

            color: var(--navy);

            font-size: 21px;

            background:
                linear-gradient(
                    135deg,
                    var(--gold),
                    var(--gold-light)
                );

        }


        .brand-title {

            color:
                var(--gold);

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size: 23px;

            font-weight: 700;

        }


        .brand-subtitle {

            display: block;

            font-size: 11px;

            color:
                rgba(255,255,255,.60);

        }


        .dashboard-btn {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding:
                9px 15px;

            border-radius: 9px;

            background:
                var(--gold);

            color:
                var(--navy);

            text-decoration: none;

            font-size: 13px;

            font-weight: 750;

            transition: .2s;

        }


        .dashboard-btn:hover {

            color:
                var(--navy);

            background:
                var(--gold-light);

            transform:
                translateY(-1px);

        }


        /* =================================================
           WRAPPER
        ================================================= */

        .success-wrapper {

            max-width:
                930px;

            margin:
                45px auto;

            padding:
                0 20px 60px;

        }


        /* =================================================
           MAIN CARD
        ================================================= */

        .success-card {

            background:
                var(--surface);

            border:
                1px solid var(--border);

            border-radius:
                22px;

            overflow:
                hidden;

            box-shadow:
                0 20px 55px
                rgba(15,23,42,.10);

        }


        /* =================================================
           SUCCESS HEADER
        ================================================= */

        .success-header {

            padding:
                45px 30px;

            text-align:
                center;

            background:
                linear-gradient(
                    135deg,
                    #fffdf5,
                    #f8fafc
                );

            border-bottom:
                1px solid var(--border);

        }


        .success-icon {

            width:
                84px;

            height:
                84px;

            margin:
                0 auto 20px;

            border-radius:
                50%;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            background:
                #ecfdf5;

            color:
                var(--success);

            font-size:
                43px;

            box-shadow:
                0 8px 25px
                rgba(21,128,61,.15);

        }


        .success-header h1 {

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size:
                31px;

            font-weight:
                700;

            margin-bottom:
                9px;

            color:
                var(--navy);

        }


        .success-header p {

            color:
                var(--muted);

            margin:
                0;

            font-size:
                14px;

        }


        /* =================================================
           BOOKING NUMBER
        ================================================= */

        .booking-number {

            display:
                inline-flex;

            align-items:
                center;

            gap:
                8px;

            margin-top:
                20px;

            padding:
                10px 17px;

            border-radius:
                30px;

            background:
                white;

            border:
                1px solid
                rgba(201,162,39,.55);

            color:
                var(--navy);

            font-size:
                13px;

            font-weight:
                800;

        }


        .booking-number i {

            color:
                var(--gold-dark);

        }


        /* =================================================
           CONTENT
        ================================================= */

        .success-content {

            padding:
                32px;

        }


        .section-title {

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size:
                22px;

            font-weight:
                700;

            margin-bottom:
                21px;

            color:
                var(--navy);

        }


        .section-title i {

            color:
                var(--gold-dark);

        }


        /* =================================================
           DETAIL BOX
        ================================================= */

        .detail-box {

            height:
                100%;

            padding:
                18px;

            border-radius:
                13px;

            background:
                #f8fafc;

            border:
                1px solid var(--border);

        }


        .detail-label {

            display:
                block;

            color:
                var(--muted);

            font-size:
                12px;

            margin-bottom:
                6px;

        }


        .detail-value {

            font-size:
                15px;

            font-weight:
                750;

            color:
                var(--navy);

        }


        .detail-icon {

            color:
                var(--gold-dark);

            margin-right:
                5px;

        }


        /* =================================================
           BOOKING STATUS
        ================================================= */

        .status-badge {

            display:
                inline-flex;

            align-items:
                center;

            gap:
                6px;

            padding:
                7px 13px;

            border-radius:
                20px;

            font-size:
                12px;

            font-weight:
                800;

        }


        .status-pending {

            background:
                #fef3c7;

            color:
                #92400e;

        }


        .status-confirmed {

            background:
                #dcfce7;

            color:
                #166534;

        }


        .status-checked {

            background:
                #dbeafe;

            color:
                #1e40af;

        }


        .status-completed {

            background:
                #e0e7ff;

            color:
                #3730a3;

        }


        .status-cancelled {

            background:
                #fee2e2;

            color:
                #991b1b;

        }


        /* =================================================
           PAYMENT STATUS
        ================================================= */

        .payment-badge {

            display:
                inline-flex;

            align-items:
                center;

            gap:
                6px;

            padding:
                7px 13px;

            border-radius:
                20px;

            font-size:
                12px;

            font-weight:
                800;

        }


        .payment-pending {

            background:
                #fef3c7;

            color:
                #92400e;

        }


        .payment-partial {

            background:
                #dbeafe;

            color:
                #1e40af;

        }


        .payment-paid {

            background:
                #dcfce7;

            color:
                #166534;

        }


        /* =================================================
           TOTAL
        ================================================= */

        .total-box {

            margin-top:
                25px;

            padding:
                22px;

            border-radius:
                14px;

            background:
                linear-gradient(
                    135deg,
                    #fffdf5,
                    #f8fafc
                );

            border:
                1px solid
                rgba(201,162,39,.35);

        }


        .total-box-inner {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                20px;

        }


        .total-label {

            color:
                var(--muted);

            font-size:
                14px;

        }


        .price-note {

            color:
                var(--muted);

            font-size:
                12px;

            margin-top:
                4px;

        }


        .total-amount {

            color:
                var(--gold-dark);

            font-size:
                27px;

            font-weight:
                850;

        }


        /* =================================================
           INFORMATION
        ================================================= */

        .pending-note {

            margin-top:
                22px;

            padding:
                15px 17px;

            border-radius:
                12px;

            background:
                #fffaf0;

            border:
                1px solid
                #ead9aa;

            color:
                #6b5a28;

            font-size:
                13px;

        }


        .pending-note i {

            color:
                var(--gold-dark);

        }


        .pending-note strong {

            color:
                var(--navy);

        }


        /* =================================================
           BUTTONS
        ================================================= */

        .action-buttons {

            margin-top:
                28px;

            display:
                grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap:
                12px;

        }


        .action-btn {

            min-height:
                49px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            gap:
                7px;

            border-radius:
                10px;

            text-decoration:
                none;

            font-size:
                14px;

            font-weight:
                750;

            transition:
                .2s;

        }


        .btn-gold {

            background:
                var(--gold);

            color:
                var(--navy);

            border:
                1px solid var(--gold);

        }


        .btn-gold:hover {

            background:
                var(--gold-light);

            color:
                var(--navy);

            transform:
                translateY(-2px);

        }


        .btn-navy {

            background:
                var(--navy);

            color:
                white;

            border:
                1px solid var(--navy);

        }


        .btn-navy:hover {

            background:
                var(--navy-light);

            color:
                white;

            transform:
                translateY(-2px);

        }


        .btn-outline {

            background:
                white;

            color:
                var(--navy);

            border:
                1px solid var(--border);

        }


        .btn-outline:hover {

            background:
                #f8fafc;

            color:
                var(--navy);

            border-color:
                var(--gold);

        }


        /* =================================================
           NOTE
        ================================================= */

        .security-note {

            margin-top:
                25px;

            padding:
                15px;

            border-radius:
                11px;

            background:
                #f8fafc;

            color:
                var(--muted);

            font-size:
                12px;

            text-align:
                center;

        }


        .security-note i {

            color:
                var(--success);

        }


        .security-note strong {

            color:
                var(--navy);

        }


        /* =================================================
           MOBILE
        ================================================= */

        @media (max-width: 700px) {

            .success-wrapper {

                margin-top:
                    25px;

            }


            .success-header {

                padding:
                    35px 20px;

            }


            .success-header h1 {

                font-size:
                    26px;

            }


            .success-content {

                padding:
                    21px;

            }


            .action-buttons {

                grid-template-columns:
                    1fr;

            }


            .brand-subtitle {

                display:
                    none;

            }


            .dashboard-btn span {

                display:
                    none;

            }


            .total-box-inner {

                flex-direction:
                    column;

                align-items:
                    flex-start;

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
            class="d-flex align-items-center justify-content-between"
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
                class="dashboard-btn"
            >

                <i class="bi bi-grid-1x2-fill"></i>

                <span>
                    Dashboard
                </span>

            </a>

        </div>

    </div>

</nav>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="success-wrapper">

    <div class="success-card">


        <!-- =================================================
             SUCCESS HEADER
        ================================================= -->

        <div class="success-header">


            <div class="success-icon">

                <i class="bi bi-check-lg"></i>

            </div>


            <h1>
                Booking Submitted Successfully!
            </h1>


            <p>

                Your reservation has been successfully
                recorded and is now awaiting hotel confirmation.

            </p>


            <div class="booking-number">

                <i class="bi bi-receipt"></i>

                Booking #

                <?= e(
                    $booking["booking_code"]
                ) ?>

            </div>

        </div>


        <!-- =================================================
             CONTENT
        ================================================= -->

        <div class="success-content">


            <h2 class="section-title">

                <i class="bi bi-calendar-check me-2"></i>

                Reservation Details

            </h2>


            <div class="row g-3">


                <!-- ROOM -->

                <div class="col-md-6">

                    <div class="detail-box">

                        <span class="detail-label">

                            <i
                                class="bi bi-door-open detail-icon"
                            ></i>

                            Room

                        </span>


                        <div class="detail-value">

                            Room
                            <?= e(
                                $booking["room_number"]
                            ) ?>

                        </div>

                    </div>

                </div>


                <!-- ROOM TYPE -->

                <div class="col-md-6">

                    <div class="detail-box">

                        <span class="detail-label">

                            <i
                                class="bi bi-stars detail-icon"
                            ></i>

                            Room Type

                        </span>


                        <div class="detail-value">

                            <?= e(
                                $booking["type_name"]
                            ) ?>

                        </div>

                    </div>

                </div>


                <!-- CHECK IN -->

                <div class="col-md-6">

                    <div class="detail-box">

                        <span class="detail-label">

                            <i
                                class="bi bi-calendar-event detail-icon"
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

                <div class="col-md-6">

                    <div class="detail-box">

                        <span class="detail-label">

                            <i
                                class="bi bi-calendar-check detail-icon"
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


                <!-- NIGHTS -->

                <div class="col-md-6">

                    <div class="detail-box">

                        <span class="detail-label">

                            <i
                                class="bi bi-moon-stars detail-icon"
                            ></i>

                            Stay Duration

                        </span>


                        <div class="detail-value">

                            <?= $nights ?>

                            <?= $nights === 1
                                ? "Night"
                                : "Nights"
                            ?>

                        </div>

                    </div>

                </div>


                <!-- GUESTS -->

                <div class="col-md-6">

                    <div class="detail-box">

                        <span class="detail-label">

                            <i
                                class="bi bi-people detail-icon"
                            ></i>

                            Guests

                        </span>


                        <div class="detail-value">

                            <?= (int) $booking["adults"] ?>

                            Adult<?= (int)$booking["adults"] === 1
                                ? ""
                                : "s"
                            ?>


                            <?php if (
                                (int)$booking["children"] > 0
                            ): ?>

                                ·

                                <?= (int) $booking["children"] ?>

                                Child<?= (int)$booking["children"] === 1
                                    ? ""
                                    : "ren"
                                ?>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>


                <!-- BOOKING STATUS -->

                <div class="col-md-6">

                    <div class="detail-box">

                        <span class="detail-label">

                            <i
                                class="bi bi-info-circle detail-icon"
                            ></i>

                            Booking Status

                        </span>


                        <div>

                            <span
                                class="status-badge <?= $status_class ?>"
                            >

                                <i
                                    class="bi <?= $status_icon ?>"
                                ></i>

                                <?= e(
                                    $booking_status_text
                                ) ?>

                            </span>

                        </div>

                    </div>

                </div>


                <!-- PAYMENT STATUS -->

                <div class="col-md-6">

                    <div class="detail-box">

                        <span class="detail-label">

                            <i
                                class="bi bi-credit-card detail-icon"
                            ></i>

                            Payment Status

                        </span>


                        <div>

                            <span
                                class="payment-badge <?= $payment_class ?>"
                            >

                                <i
                                    class="bi <?= $payment_icon ?>"
                                ></i>

                                <?= e(
                                    $payment_status_text
                                ) ?>

                            </span>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 TOTAL
            ================================================= -->

            <div class="total-box">

                <div class="total-box-inner">


                    <div>

                        <div class="total-label">

                            Total Booking Amount

                        </div>


                        <div class="price-note">

                            <?= $nights ?>

                            × Rs.

                            <?= number_format(
                                (float) $booking["room_price"],
                                2
                            ) ?>

                            per night

                        </div>

                    </div>


                    <div class="total-amount">

                        Rs.

                        <?= number_format(
                            (float) $booking["total_amount"],
                            2
                        ) ?>

                    </div>


                </div>

            </div>


            <!-- =================================================
                 PENDING INFORMATION
            ================================================= -->

            <?php if ($status === "pending"): ?>

                <div class="pending-note">

                    <i
                        class="bi bi-info-circle-fill me-2"
                    ></i>

                    Your booking is currently

                    <strong>
                        Pending
                    </strong>.

                    The hotel staff will review your
                    reservation and update the booking status.

                </div>

            <?php elseif ($status === "confirmed"): ?>

                <div class="pending-note">

                    <i
                        class="bi bi-check-circle-fill me-2"
                    ></i>

                    Your booking has been

                    <strong>
                        Confirmed
                    </strong>.

                    Please keep your booking code for
                    future reference.

                </div>

            <?php elseif ($status === "cancelled"): ?>

                <div class="pending-note">

                    <i
                        class="bi bi-exclamation-circle-fill me-2"
                    ></i>

                    This booking has been

                    <strong>
                        Cancelled
                    </strong>.

                    Please contact the hotel if you need
                    further assistance.

                </div>

            <?php endif; ?>


            <!-- =================================================
                 ACTION BUTTONS
            ================================================= -->

            <div class="action-buttons">


                <!-- MY BOOKINGS -->

                <a
                    href="bookings.php"
                    class="action-btn btn-gold"
                >

                    <i
                        class="bi bi-calendar-check"
                    ></i>

                    My Bookings

                </a>


                <!-- MEALS -->

                <a
                    href="meals.php"
                    class="action-btn btn-navy"
                >

                    <i
                        class="bi bi-cup-hot"
                    ></i>

                    Meals & Dining

                </a>


                <!-- ROOMS -->

                <a
                    href="rooms.php"
                    class="action-btn btn-outline"
                >

                    <i
                        class="bi bi-door-open"
                    ></i>

                    Browse Rooms

                </a>


                <!-- DASHBOARD -->

                <a
                    href="dashboard.php"
                    class="action-btn btn-outline"
                >

                    <i
                        class="bi bi-grid-1x2"
                    ></i>

                    Dashboard

                </a>

            </div>


            <!-- =================================================
                 SECURITY NOTE
            ================================================= -->

            <div class="security-note">

                <i
                    class="bi bi-shield-check me-1"
                ></i>

                Your booking has been securely recorded.

                Please keep your booking code

                <strong>
                    <?= e(
                        $booking["booking_code"]
                    ) ?>
                </strong>

                for future reference.

            </div>


        </div>

    </div>

</main>


</body>

</html>