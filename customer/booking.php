<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

startSession();
requireRole("customer");

$error = "";

$customer_id = (int) ($_SESSION["user_id"] ?? 0);

if ($customer_id <= 0) {
    header("Location: ../auth/login.php");
    exit;
}


/* =====================================================
   GET DATA
===================================================== */

$room_id = isset($_GET["room_id"])
    ? (int) $_GET["room_id"]
    : 0;

$check_in = trim($_GET["check_in"] ?? "");
$check_out = trim($_GET["check_out"] ?? "");


/* =====================================================
   VALIDATE ROOM ID
===================================================== */

if ($room_id <= 0) {
    die("Invalid room.");
}


/* =====================================================
   GET ROOM
===================================================== */

$stmt = $pdo->prepare("
    SELECT
        r.id,
        r.room_number,
        r.status,
        r.description,
        r.room_type_id,
        rt.type_name,
        rt.base_price
    FROM rooms r
    INNER JOIN room_types rt
        ON r.room_type_id = rt.id
    WHERE r.id = ?
    LIMIT 1
");

$stmt->execute([$room_id]);

$room = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$room) {
    die("Room not found.");
}


/* =====================================================
   ROOM PRICE
===================================================== */

$price_per_night = (float) $room["base_price"];


/* =====================================================
   DEFAULT VALUES
===================================================== */

$display_adults = 1;
$display_children = 0;
$display_special_request = "";


/* =====================================================
   CALCULATE DISPLAY NIGHTS
===================================================== */

$nights = 0;

if ($check_in !== "" && $check_out !== "") {

    try {

        $start = new DateTime($check_in);
        $end = new DateTime($check_out);

        if ($end > $start) {

            $nights = $start->diff($end)->days;

        }

    } catch (Exception $e) {

        $nights = 0;

    }
}


$total_amount = $price_per_night * $nights;


/* =====================================================
   CONFIRM BOOKING
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* -------------------------------------------------
       KEEP FORM VALUES
    ------------------------------------------------- */

    $posted_room_id = (int) ($_POST["room_id"] ?? 0);

    $posted_check_in = trim(
        $_POST["check_in"] ?? ""
    );

    $posted_check_out = trim(
        $_POST["check_out"] ?? ""
    );

    $adults = (int) ($_POST["adults"] ?? 1);

    $children = (int) ($_POST["children"] ?? 0);

    $special_request = trim(
        $_POST["special_request"] ?? ""
    );


    /* -------------------------------------------------
       DISPLAY VALUES AFTER POST
    ------------------------------------------------- */

    $check_in = $posted_check_in;
    $check_out = $posted_check_out;

    $display_adults = max(1, $adults);
    $display_children = max(0, $children);
    $display_special_request = $special_request;


    /* =================================================
       BASIC VALIDATION
    ================================================= */

    if ($posted_room_id !== $room_id) {

        $error = "Invalid room selected.";

    } elseif ($posted_check_in === "" || $posted_check_out === "") {

        $error = "Please select valid check-in and check-out dates.";

    } elseif ($adults < 1 || $adults > 20) {

        $error = "Adults must be between 1 and 20.";

    } elseif ($children < 0 || $children > 20) {

        $error = "Children must be between 0 and 20.";

    } elseif (strlen($special_request) > 1000) {

        $error = "Special request is too long.";

    } else {

        try {

            /* =========================================
               DATE VALIDATION
            ========================================= */

            $start = new DateTime($posted_check_in);
            $end = new DateTime($posted_check_out);

            $today = new DateTime(
                date("Y-m-d")
            );


            /* Check-in cannot be before today */

            if ($start < $today) {

                $error =
                    "Check-in date cannot be before today.";

            }

            /* Check-out must be after check-in */

            elseif ($end <= $start) {

                $error =
                    "Check-out date must be after check-in date.";

            }

            else {

                $booking_nights =
                    $start->diff($end)->days;


                if ($booking_nights <= 0) {

                    $error =
                        "Invalid number of nights.";

                }

                else {

                    /* =====================================
                       START TRANSACTION
                    ===================================== */

                    $pdo->beginTransaction();


                    /* =====================================
                       LOCK & RE-CHECK ROOM
                    ===================================== */

                    $roomStmt = $pdo->prepare("
                        SELECT
                            r.id,
                            r.room_number,
                            r.status,
                            rt.type_name,
                            rt.base_price
                        FROM rooms r
                        INNER JOIN room_types rt
                            ON r.room_type_id = rt.id
                        WHERE r.id = ?
                        LIMIT 1
                        FOR UPDATE
                    ");

                    $roomStmt->execute([
                        $posted_room_id
                    ]);

                    $currentRoom =
                        $roomStmt->fetch(PDO::FETCH_ASSOC);


                    if (!$currentRoom) {

                        throw new Exception(
                            "Room not found."
                        );

                    }


                    /* =====================================
                       CHECK ROOM STATUS
                    ===================================== */

                    if ($currentRoom["status"] !== "available") {

                        throw new Exception(
                            "This room is currently unavailable."
                        );

                    }


                    /* =====================================
                       CHECK BOOKING OVERLAP
                    ===================================== */

                    $availabilityStmt = $pdo->prepare("
                        SELECT id
                        FROM bookings
                        WHERE room_id = ?
                        AND booking_status IN (
                            'pending',
                            'confirmed',
                            'checked_in'
                        )
                        AND check_in < ?
                        AND check_out > ?
                        LIMIT 1
                    ");

                    $availabilityStmt->execute([
                        $posted_room_id,
                        $posted_check_out,
                        $posted_check_in
                    ]);


                    $existingBooking =
                        $availabilityStmt->fetch(
                            PDO::FETCH_ASSOC
                        );


                    if ($existingBooking) {

                        throw new Exception(
                            "Sorry, this room is no longer available for the selected dates. Please choose different dates."
                        );

                    }


                    /* =====================================
                       FINAL PRICE FROM DATABASE
                    ===================================== */

                    $final_price =
                        (float) $currentRoom["base_price"];


                    $final_total =
                        $final_price *
                        $booking_nights;


                    /* =====================================
                       GENERATE UNIQUE BOOKING CODE
                    ===================================== */

                    do {

                        $booking_code =
                            "BK-" .
                            date("Ymd") .
                            "-" .
                            strtoupper(
                                substr(
                                    bin2hex(
                                        random_bytes(4)
                                    ),
                                    0,
                                    8
                                )
                            );


                        $codeStmt = $pdo->prepare("
                            SELECT id
                            FROM bookings
                            WHERE booking_code = ?
                            LIMIT 1
                        ");

                        $codeStmt->execute([
                            $booking_code
                        ]);

                        $codeExists =
                            $codeStmt->fetch(
                                PDO::FETCH_ASSOC
                            );

                    } while ($codeExists);


                    /* =====================================
                       INSERT BOOKING
                    ===================================== */

                    $bookingStmt = $pdo->prepare("
                        INSERT INTO bookings
                        (
                            booking_code,
                            customer_id,
                            room_id,
                            check_in,
                            check_out,
                            adults,
                            children,
                            number_of_nights,
                            room_price,
                            total_amount,
                            booking_status,
                            payment_status,
                            special_request
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            'pending',
                            'pending',
                            ?
                        )
                    ");


                    $bookingStmt->execute([

                        $booking_code,

                        $customer_id,

                        $posted_room_id,

                        $posted_check_in,

                        $posted_check_out,

                        $adults,

                        $children,

                        $booking_nights,

                        $final_price,

                        $final_total,

                        $special_request

                    ]);


                    /* =====================================
                       GET BOOKING ID
                    ===================================== */

                    $booking_id =
                        (int) $pdo->lastInsertId();


                    if ($booking_id <= 0) {

                        throw new Exception(
                            "Unable to create booking."
                        );

                    }


                    /* =====================================
                       COMMIT
                    ===================================== */

                    $pdo->commit();


                    /* =====================================
                       REDIRECT
                    ===================================== */

                    header(
                        "Location: booking_success.php?id="
                        . $booking_id
                    );

                    exit;

                }

            }

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error =
                "Unable to process your booking. Please try again.";

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error =
                $e->getMessage();

        }

    }

}


/* =====================================================
   RECALCULATE DISPLAY VALUES
===================================================== */

$nights = 0;

if ($check_in !== "" && $check_out !== "") {

    try {

        $displayStart =
            new DateTime($check_in);

        $displayEnd =
            new DateTime($check_out);


        if ($displayEnd > $displayStart) {

            $nights =
                $displayStart
                    ->diff($displayEnd)
                    ->days;

        }

    } catch (Exception $e) {

        $nights = 0;

    }

}


$total_amount =
    $price_per_night * $nights;


/* =====================================================
   TODAY FOR DATE INPUT
===================================================== */

$today_value = date("Y-m-d");

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Confirm Booking | Velora Hotel</title>


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


    <style>

        :root {

            --navy: #071a33;
            --navy-dark: #04101f;
            --navy-light: #102a4a;

            --gold: #d4af63;
            --gold-dark: #b58b3a;
            --gold-light: #e8cb8a;

            --cream: #f7f4ee;

            --white: #ffffff;

            --text: #172033;
            --muted: #667085;

            --border: #e5e1d8;

        }


        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            background:
                var(--cream);

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

            min-height: 78px;

            background:
                var(--navy);

            box-shadow:
                0 5px 20px
                rgba(0,0,0,0.14);

        }


        .brand {

            text-decoration: none;

            display: flex;

            align-items: center;

            gap: 13px;

            color: white;

        }


        .brand:hover {
            color: white;
        }


        .brand-icon {

            width: 46px;

            height: 46px;

            border-radius: 10px;

            background:
                var(--gold);

            color:
                var(--navy);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 22px;

        }


        .brand-title {

            color:
                var(--gold);

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size: 25px;

            font-weight: 700;

        }


        .brand-subtitle {

            display: block;

            color:
                rgba(255,255,255,0.60);

            font-size: 11px;

            margin-top: 2px;

        }


        .back-btn {

            text-decoration: none;

            color:
                var(--navy);

            background:
                var(--gold);

            border:
                1px solid var(--gold);

            padding:
                10px 17px;

            border-radius:
                9px;

            font-weight:
                700;

            font-size:
                14px;

            transition:
                0.25s;

        }


        .back-btn:hover {

            background:
                var(--gold-light);

            color:
                var(--navy);

            transform:
                translateY(-1px);

        }


        /* =================================================
           PAGE
        ================================================= */

        .page-container {

            max-width:
                1180px;

            margin:
                auto;

            padding:
                38px 20px 65px;

        }


        .page-heading {

            margin-bottom:
                27px;

        }


        .page-heading h1 {

            color:
                var(--navy);

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size:
                34px;

            font-weight:
                700;

            margin-bottom:
                7px;

        }


        .page-heading p {

            color:
                var(--muted);

            margin:
                0;

        }


        /* =================================================
           ALERT
        ================================================= */

        .custom-alert {

            border-radius:
                12px;

            border:
                1px solid #f5c2c7;

            background:
                #fff1f2;

            color:
                #842029;

            padding:
                14px 17px;

            margin-bottom:
                22px;

            font-size:
                14px;

        }


        /* =================================================
           CARD
        ================================================= */

        .card-custom {

            background:
                var(--white);

            border:
                1px solid var(--border);

            border-radius:
                17px;

            box-shadow:
                0 10px 30px
                rgba(7,26,51,0.07);

        }


        /* =================================================
           ROOM SUMMARY
        ================================================= */

        .room-summary {

            padding:
                30px;

        }


        .room-icon {

            width:
                68px;

            height:
                68px;

            border-radius:
                14px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            background:
                rgba(212,175,99,0.14);

            color:
                var(--gold-dark);

            font-size:
                31px;

            flex-shrink:
                0;

        }


        .room-title {

            color:
                var(--navy);

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size:
                25px;

            font-weight:
                700;

            margin-bottom:
                4px;

        }


        .room-type {

            color:
                var(--gold-dark);

            font-weight:
                700;

        }


        .available {

            display:
                inline-flex;

            align-items:
                center;

            gap:
                5px;

            color:
                #166534;

            background:
                #dcfce7;

            border:
                1px solid #bbf7d0;

            padding:
                7px 11px;

            border-radius:
                20px;

            font-size:
                12px;

            font-weight:
                700;

            white-space:
                nowrap;

        }


        hr {

            border-color:
                var(--border);

            opacity:
                1;

        }


        /* =================================================
           SECTION
        ================================================= */

        .section-heading {

            color:
                var(--navy);

            font-size:
                20px;

            font-weight:
                750;

            margin-bottom:
                15px;

        }


        .section-heading i {

            color:
                var(--gold-dark);

        }


        /* =================================================
           DETAIL BOX
        ================================================= */

        .detail-box {

            height:
                100%;

            background:
                #faf9f6;

            border:
                1px solid var(--border);

            border-radius:
                11px;

            padding:
                16px;

        }


        .detail-label {

            display:
                block;

            color:
                var(--muted);

            font-size:
                12px;

            margin-bottom:
                5px;

        }


        .detail-icon {

            color:
                var(--gold-dark);

            margin-right:
                5px;

        }


        .detail-value {

            color:
                var(--navy);

            font-size:
                15px;

            font-weight:
                700;

        }


        /* =================================================
           INFORMATION BOX
        ================================================= */

        .information-box {

            margin-top:
                22px;

            padding:
                15px;

            border-radius:
                11px;

            background:
                #fffaf0;

            border:
                1px solid #ead9aa;

        }


        .information-box i {

            color:
                var(--gold-dark);

        }


        .information-box strong {

            color:
                var(--navy);

        }


        .information-box p {

            color:
                var(--muted);

        }


        /* =================================================
           BOOKING CARD
        ================================================= */

        .booking-card {

            padding:
                30px;

        }


        .section-title {

            color:
                var(--navy);

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size:
                25px;

            font-weight:
                700;

            margin-bottom:
                5px;

        }


        .section-subtitle {

            color:
                var(--muted);

            font-size:
                13px;

            margin-bottom:
                22px;

        }


        /* =================================================
           FORM
        ================================================= */

        .form-label {

            color:
                var(--navy);

            font-size:
                13px;

            font-weight:
                700;

            margin-bottom:
                7px;

        }


        .form-control {

            min-height:
                47px;

            border:
                1px solid #d8d5cd;

            border-radius:
                9px;

            color:
                #172033;

            background:
                white;

        }


        .form-control:focus {

            border-color:
                var(--gold);

            box-shadow:
                0 0 0 3px
                rgba(212,175,99,0.15);

        }


        textarea.form-control {

            min-height:
                100px;

            resize:
                vertical;

        }


        /* =================================================
           PRICE SUMMARY
        ================================================= */

        .price-summary {

            background:
                #faf9f6;

            border:
                1px solid var(--border);

            border-radius:
                13px;

            padding:
                19px;

            margin-top:
                22px;

        }


        .price-row {

            display:
                flex;

            justify-content:
                space-between;

            align-items:
                center;

            gap:
                15px;

            margin-bottom:
                12px;

            font-size:
                14px;

        }


        .price-row span {

            color:
                var(--muted);

        }


        .price-row strong {

            color:
                var(--navy);

        }


        .total-row {

            border-top:
                1px solid var(--border);

            padding-top:
                16px;

            margin-top:
                16px;

            display:
                flex;

            justify-content:
                space-between;

            align-items:
                center;

        }


        .total-label {

            color:
                var(--navy);

            font-weight:
                750;

        }


        .total-price {

            color:
                var(--gold-dark);

            font-size:
                23px;

            font-weight:
                800;

        }


        /* =================================================
           CONFIRM BUTTON
        ================================================= */

        .confirm-btn {

            width:
                100%;

            min-height:
                52px;

            border:
                none;

            border-radius:
                10px;

            margin-top:
                20px;

            background:
                var(--gold);

            color:
                var(--navy);

            font-size:
                15px;

            font-weight:
                800;

            transition:
                0.25s;

            box-shadow:
                0 7px 18px
                rgba(212,175,99,0.20);

        }


        .confirm-btn:hover:not(:disabled) {

            background:
                var(--gold-light);

            color:
                var(--navy);

            transform:
                translateY(-2px);

        }


        .confirm-btn:disabled {

            opacity:
                0.55;

            cursor:
                not-allowed;

            box-shadow:
                none;

        }


        .secure-note {

            text-align:
                center;

            color:
                var(--muted);

            font-size:
                11px;

            margin-top:
                13px;

        }


        .secure-note i {

            color:
                var(--gold-dark);

        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 768px) {

            .page-container {

                padding:
                    28px 15px 45px;

            }


            .page-heading h1 {

                font-size:
                    28px;

            }


            .room-summary,
            .booking-card {

                padding:
                    21px;

            }


            .available {

                display:
                    none;

            }


            .brand-subtitle {

                display:
                    none;

            }


            .brand-title {

                font-size:
                    21px;

            }

        }


        @media (max-width: 480px) {

            .back-btn {

                padding:
                    8px 11px;

                font-size:
                    12px;

            }


            .room-title {

                font-size:
                    21px;

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
            style="min-height:78px;"
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


            <!-- BACK -->

            <a
                href="room_details.php?id=<?= $room_id ?>&check_in=<?= urlencode($check_in) ?>&check_out=<?= urlencode($check_out) ?>"
                class="back-btn"
            >

                <i class="bi bi-arrow-left me-1"></i>

                Back to Room

            </a>

        </div>

    </div>

</nav>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="page-container">


    <!-- PAGE HEADING -->

    <div class="page-heading">

        <h1>
            Confirm Your Booking
        </h1>

        <p>
            Review your room and stay details before
            confirming your reservation.
        </p>

    </div>


    <!-- ERROR -->

    <?php if ($error !== ""): ?>

        <div class="custom-alert">

            <i class="bi bi-exclamation-triangle-fill me-2"></i>

            <?= e($error) ?>

        </div>

    <?php endif; ?>


    <div class="row g-4">


        <!-- =================================================
             LEFT SIDE
        ================================================= -->

        <div class="col-lg-7">

            <div class="card-custom room-summary">


                <!-- ROOM HEADER -->

                <div
                    class="d-flex align-items-center gap-3 mb-4"
                >

                    <div class="room-icon">

                        <i class="bi bi-door-open"></i>

                    </div>


                    <div class="flex-grow-1">

                        <h2 class="room-title">

                            Room
                            <?= e(
                                $room["room_number"]
                            ) ?>

                        </h2>


                        <div class="room-type">

                            <i class="bi bi-stars me-1"></i>

                            <?= e(
                                $room["type_name"]
                            ) ?>

                        </div>

                    </div>


                    <?php if ($room["status"] === "available"): ?>

                        <span class="available">

                            <i class="bi bi-check-circle-fill"></i>

                            Available

                        </span>

                    <?php endif; ?>

                </div>


                <hr class="mb-4">


                <!-- STAY DETAILS -->

                <h5 class="section-heading">

                    <i class="bi bi-info-circle me-2"></i>

                    Stay Details

                </h5>


                <div class="row g-3">


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

                                <?php if ($check_in !== ""): ?>

                                    <?= e(
                                        date(
                                            "d M Y",
                                            strtotime($check_in)
                                        )
                                    ) ?>

                                <?php else: ?>

                                    Not selected

                                <?php endif; ?>

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

                                <?php if ($check_out !== ""): ?>

                                    <?= e(
                                        date(
                                            "d M Y",
                                            strtotime($check_out)
                                        )
                                    ) ?>

                                <?php else: ?>

                                    Not selected

                                <?php endif; ?>

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

                                Number of Nights

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


                    <!-- PRICE -->

                    <div class="col-md-6">

                        <div class="detail-box">

                            <span class="detail-label">

                                <i
                                    class="bi bi-cash-stack detail-icon"
                                ></i>

                                Price per Night

                            </span>


                            <div class="detail-value">

                                Rs.
                                <?= number_format(
                                    $price_per_night,
                                    2
                                ) ?>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- INFORMATION -->

                <div class="information-box">

                    <div class="d-flex gap-2">

                        <i
                            class="bi bi-info-circle-fill"
                        ></i>

                        <div>

                            <strong>
                                Booking Information
                            </strong>

                            <p
                                class="small mb-0 mt-1"
                            >

                                Your reservation will be saved
                                with a pending status. The hotel
                                can then review and process your
                                booking.

                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             RIGHT SIDE
        ================================================= -->

        <div class="col-lg-5">

            <div class="card-custom booking-card">


                <h2 class="section-title">
                    Booking Summary
                </h2>


                <p class="section-subtitle">

                    Enter your guest details and review
                    the final amount.

                </p>


                <form
                    method="POST"
                    id="bookingForm"
                >


                    <!-- ROOM ID -->

                    <input
                        type="hidden"
                        name="room_id"
                        value="<?= $room_id ?>"
                    >


                    <!-- CHECK IN -->

                    <input
                        type="hidden"
                        name="check_in"
                        value="<?= e($check_in) ?>"
                    >


                    <!-- CHECK OUT -->

                    <input
                        type="hidden"
                        name="check_out"
                        value="<?= e($check_out) ?>"
                    >


                    <!-- ADULTS -->

                    <div class="mb-3">

                        <label
                            for="adults"
                            class="form-label"
                        >

                            <i
                                class="bi bi-person-fill me-1"
                            ></i>

                            Adults

                        </label>


                        <input
                            type="number"
                            id="adults"
                            name="adults"
                            class="form-control"
                            min="1"
                            max="20"
                            value="<?= $display_adults ?>"
                            required
                        >

                    </div>


                    <!-- CHILDREN -->

                    <div class="mb-3">

                        <label
                            for="children"
                            class="form-label"
                        >

                            <i
                                class="bi bi-people me-1"
                            ></i>

                            Children

                        </label>


                        <input
                            type="number"
                            id="children"
                            name="children"
                            class="form-control"
                            min="0"
                            max="20"
                            value="<?= $display_children ?>"
                        >

                    </div>


                    <!-- SPECIAL REQUEST -->

                    <div class="mb-3">

                        <label
                            for="special_request"
                            class="form-label"
                        >

                            <i
                                class="bi bi-chat-left-text me-1"
                            ></i>

                            Special Request

                            <span
                                class="text-muted fw-normal"
                            >
                                (Optional)
                            </span>

                        </label>


                        <textarea
                            id="special_request"
                            name="special_request"
                            class="form-control"
                            maxlength="1000"
                            placeholder="Any special request for your stay..."
                        ><?= e($display_special_request) ?></textarea>

                    </div>


                    <!-- PRICE SUMMARY -->

                    <div class="price-summary">


                        <div class="price-row">

                            <span>
                                Room
                            </span>

                            <strong>

                                <?= e(
                                    $room["type_name"]
                                ) ?>

                            </strong>

                        </div>


                        <div class="price-row">

                            <span>
                                Price per night
                            </span>

                            <strong>

                                Rs.
                                <?= number_format(
                                    $price_per_night,
                                    2
                                ) ?>

                            </strong>

                        </div>


                        <div class="price-row">

                            <span>
                                Number of nights
                            </span>

                            <strong>

                                <?= $nights ?>

                            </strong>

                        </div>


                        <div class="total-row">

                            <span class="total-label">

                                Total Amount

                            </span>


                            <span class="total-price">

                                Rs.
                                <?= number_format(
                                    $total_amount,
                                    2
                                ) ?>

                            </span>

                        </div>

                    </div>


                    <!-- CONFIRM BUTTON -->

                    <button
                        type="submit"
                        class="confirm-btn"
                        id="confirmButton"
                        <?= $nights <= 0 ? "disabled" : "" ?>
                    >

                        <i
                            class="bi bi-check-circle me-2"
                        ></i>

                        Confirm Booking

                    </button>


                    <div class="secure-note">

                        <i
                            class="bi bi-shield-check me-1"
                        ></i>

                        Your booking information is securely
                        processed.

                    </div>


                </form>

            </div>

        </div>

    </div>

</main>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<script>

/* =====================================================
   PREVENT DOUBLE SUBMISSION
===================================================== */

const bookingForm =
    document.getElementById("bookingForm");

const confirmButton =
    document.getElementById("confirmButton");


if (bookingForm && confirmButton) {

    bookingForm.addEventListener(
        "submit",
        function () {

            confirmButton.disabled = true;

            confirmButton.innerHTML =
                '<i class="bi bi-hourglass-split me-2"></i>' +
                'Processing Booking...';

        }
    );

}

</script>


</body>

</html>