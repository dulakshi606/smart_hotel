<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

requireRole("customer");

/* =====================================================
   GET ROOM ID
===================================================== */

$room_id = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;

$check_in = $_GET["check_in"] ?? "";
$check_out = $_GET["check_out"] ?? "";


/* =====================================================
   VALIDATE ROOM ID
===================================================== */

if ($room_id <= 0) {
    die("Invalid room ID.");
}


/* =====================================================
   GET ROOM DETAILS
===================================================== */

$stmt = $pdo->prepare("
    SELECT
        r.*,
        rt.type_name,
        rt.base_price AS room_type_price
    FROM rooms r
    INNER JOIN room_types rt
        ON r.room_type_id = rt.id
    WHERE r.id = ?
    LIMIT 1
");

$stmt->execute([$room_id]);

$room = $stmt->fetch(PDO::FETCH_ASSOC);


/* =====================================================
   ROOM NOT FOUND
===================================================== */

if (!$room) {
    die("Room not found.");
}


/* =====================================================
   ROOM PRICE
===================================================== */

$room_price = 0;

if (isset($room["room_type_price"])) {
    $room_price = (float) $room["room_type_price"];
}


/* =====================================================
   GET FACILITIES
===================================================== */

$facilities = [];

try {

    $facilityStmt = $pdo->prepare("
        SELECT f.*
        FROM room_facilities rf
        INNER JOIN facilities f
            ON rf.facility_id = f.id
        WHERE rf.room_id = ?
    ");

    $facilityStmt->execute([$room_id]);

    $facilities = $facilityStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $facilities = [];

}


/* =====================================================
   CALCULATE NIGHTS
===================================================== */

$nights = 0;

if ($check_in !== "" && $check_out !== "") {

    try {

        $start = new DateTime($check_in);
        $end = new DateTime($check_out);

        if ($end > $start) {

            $interval = $start->diff($end);

            $nights = $interval->days;

        }

    } catch (Exception $e) {

        $nights = 0;

    }
}


/* =====================================================
   TOTAL
===================================================== */

$total = $room_price * $nights;


/* =====================================================
   ROOM IMAGE
===================================================== */

$room_image = "";

$image_fields = [
    "image",
    "room_image",
    "image_path",
    "photo",
    "room_photo"
];

foreach ($image_fields as $field) {

    if (
        isset($room[$field]) &&
        !empty($room[$field])
    ) {

        $room_image = trim($room[$field]);

        break;

    }
}


/*
   If database contains only a filename,
   use ../uploads/rooms/
*/

if ($room_image !== "") {

    if (
        !str_starts_with($room_image, "http://") &&
        !str_starts_with($room_image, "https://") &&
        !str_starts_with($room_image, "/")
    ) {

        if (
            !str_starts_with($room_image, "../") &&
            !str_starts_with($room_image, "uploads/")
        ) {

            $room_image = "../uploads/rooms/" . $room_image;

        } elseif (
            str_starts_with($room_image, "uploads/")
        ) {

            $room_image = "../" . $room_image;

        }

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
        Room <?= e($room["room_number"]) ?> | Velora Hotel
    </title>


    <!-- =================================================
         BOOTSTRAP
    ================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =================================================
         BOOTSTRAP ICONS
    ================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <!-- =================================================
         VELORA HOTEL STYLE
         
         IMPORTANT:
         Do NOT load the old velora.css here.
         This page contains the complete styling so
         old CSS cannot override the design.
    ================================================== -->

    <style>

        /* =================================================
           ROOT COLORS
        ================================================= */

        :root {

            --navy: #0f2034;

            --navy-dark: #091522;

            --navy-light: #172a3a;

            --gold: #d4af37;

            --gold-light: #f1ce57;

            --cream: #f8f6f1;

            --white: #ffffff;

            --text: #172a3a;

            --muted: #64748b;

            --border: #e5e7eb;

            --success: #198754;

        }


        /* =================================================
           GLOBAL
        ================================================= */

        * {
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
        }


        body {

            margin: 0;

            padding: 0;

            background: var(--cream);

            color: var(--text);

            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;

        }


        a {
            text-decoration: none;
        }


        /* =================================================
           TOP NAVIGATION
        ================================================= */

        .topbar {

            background: var(--navy);

            min-height: 78px;

            padding: 0;

            display: flex;

            align-items: center;

            box-shadow:
                0 5px 20px
                rgba(9, 21, 34, 0.16);

            position: sticky;

            top: 0;

            z-index: 1000;

        }


        .topbar-inner {

            width: 100%;

            max-width: 1400px;

            margin: auto;

            padding:
                14px 32px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

        }


        /* =================================================
           BRAND
        ================================================= */

        .brand {

            display: flex;

            align-items: center;

            gap: 13px;

            color: var(--white);

            transition: 0.25s ease;

        }


        .brand:hover {
            color: var(--white);
        }


        .brand-icon {

            width: 46px;

            height: 46px;

            border-radius: 10px;

            background: var(--gold);

            color: var(--navy);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 22px;

            box-shadow:
                0 5px 15px
                rgba(212, 175, 55, 0.22);

        }


        .brand-name {

            display: block;

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size: 25px;

            font-weight: 700;

            line-height: 1.1;

            color: var(--gold);

        }


        .brand-subtitle {

            display: block;

            margin-top: 3px;

            font-size: 12px;

            color: rgba(255,255,255,0.62);

            letter-spacing: 0.3px;

        }


        /* =================================================
           NAV BUTTONS
        ================================================= */

        .nav-actions {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .nav-btn {

            min-height: 44px;

            padding:
                10px 17px;

            border-radius: 9px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            font-size: 14px;

            font-weight: 600;

            transition: all 0.25s ease;

        }


        .rooms-btn {

            color: var(--navy);

            background: var(--gold);

            border: 1px solid var(--gold);

        }


        .rooms-btn:hover {

            color: var(--navy);

            background: var(--gold-light);

            border-color: var(--gold-light);

            transform: translateY(-1px);

        }


        .dashboard-btn {

            color: var(--white);

            background: transparent;

            border: 1px solid
                rgba(255,255,255,0.35);

        }


        .dashboard-btn:hover {

            color: var(--navy);

            background: var(--white);

            border-color: var(--white);

        }


        /* =================================================
           MAIN CONTAINER
        ================================================= */

        .page-container {

            width: 100%;

            max-width: 1400px;

            margin: auto;

            padding:
                28px 32px 60px;

        }


        /* =================================================
           BREADCRUMB
        ================================================= */

        .breadcrumb-area {

            display: flex;

            align-items: center;

            flex-wrap: wrap;

            gap: 5px;

            margin-bottom: 22px;

            font-size: 14px;

        }


        .breadcrumb-link {

            color: var(--navy);

            font-weight: 600;

            transition: 0.2s ease;

        }


        .breadcrumb-link:hover {

            color: var(--gold);

        }


        .breadcrumb-current {

            color: var(--muted);

            font-weight: 500;

        }


        /* =================================================
           ROOM CARD
        ================================================= */

        .room-card {

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 18px;

            overflow: hidden;

            box-shadow:
                0 15px 40px
                rgba(15, 32, 52, 0.08);

        }


        /* =================================================
           ROOM VISUAL
        ================================================= */

        .room-visual {

            height: 390px;

            position: relative;

            overflow: hidden;

            background:
                linear-gradient(
                    135deg,
                    var(--navy-dark),
                    var(--navy-light)
                );

            display: flex;

            align-items: center;

            justify-content: center;

        }


        .room-visual.has-image {

            background:
                var(--navy);

        }


        .room-visual img {

            width: 100%;

            height: 100%;

            object-fit: cover;

            display: block;

        }


        .room-visual.has-image::after {

            content: "";

            position: absolute;

            inset: 0;

            background:
                linear-gradient(
                    to top,
                    rgba(9,21,34,0.45),
                    rgba(9,21,34,0.02)
                );

            pointer-events: none;

        }


        .room-placeholder {

            width: 145px;

            height: 145px;

            border-radius: 50%;

            border:
                1px solid
                rgba(212,175,55,0.4);

            background:
                rgba(255,255,255,0.05);

            display: flex;

            align-items: center;

            justify-content: center;

            color: var(--gold);

            font-size: 58px;

        }


        /* =================================================
           AVAILABILITY
        ================================================= */

        .availability-badge {

            position: absolute;

            top: 22px;

            right: 22px;

            z-index: 5;

            background: var(--white);

            color: var(--navy);

            padding:
                10px 16px;

            border-radius: 50px;

            font-size: 13px;

            font-weight: 700;

            box-shadow:
                0 8px 20px
                rgba(0,0,0,0.16);

        }


        .availability-badge i {

            color: var(--success);

        }


        /* =================================================
           ROOM CONTENT
        ================================================= */

        .room-content {

            padding: 32px;

        }


        .room-title-row {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;

        }


        .room-number {

            margin: 0;

            color: var(--navy);

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size: 38px;

            font-weight: 700;

            line-height: 1.15;

        }


        .room-type {

            margin-top: 9px;

            color: var(--gold);

            font-size: 16px;

            font-weight: 700;

        }


        .price-area {

            text-align: right;

            white-space: nowrap;

        }


        .price {

            color: var(--gold);

            font-size: 27px;

            font-weight: 800;

        }


        .price-unit {

            color: var(--muted);

            font-size: 13px;

        }


        /* =================================================
           DIVIDER
        ================================================= */

        .room-content hr {

            border: 0;

            border-top:
                1px solid var(--border);

            opacity: 1;

        }


        /* =================================================
           SECTION TITLES
        ================================================= */

        .info-title {

            margin-bottom: 18px;

            color: var(--navy);

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size: 23px;

            font-weight: 700;

        }


        .info-title i {

            color: var(--gold) !important;

        }


        /* =================================================
           INFO BOX
        ================================================= */

        .info-box {

            height: 100%;

            background: #fff;

            border:
                1px solid var(--border);

            border-radius: 12px;

            padding: 18px;

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;

        }


        .info-box:hover {

            transform: translateY(-2px);

            box-shadow:
                0 8px 25px
                rgba(15,32,52,0.07);

        }


        .info-icon {

            width: 42px;

            height: 42px;

            border-radius: 10px;

            background:
                rgba(212,175,55,0.13);

            color: var(--gold);

            display: flex;

            align-items: center;

            justify-content: center;

            margin-bottom: 12px;

            font-size: 19px;

        }


        .info-label {

            display: block;

            color: var(--muted);

            font-size: 12px;

            font-weight: 600;

            text-transform: uppercase;

            letter-spacing: 0.6px;

            margin-bottom: 5px;

        }


        .info-value {

            color: var(--navy);

            font-size: 17px;

            font-weight: 700;

        }


        /* =================================================
           FACILITIES
        ================================================= */

        .facility-item {

            min-height: 52px;

            display: flex;

            align-items: center;

            gap: 11px;

            padding:
                13px 15px;

            margin-bottom: 12px;

            background: #fff;

            border:
                1px solid var(--border);

            border-radius: 10px;

            color: var(--text);

            font-size: 14px;

            font-weight: 600;

        }


        .facility-item i {

            color: var(--gold);

            font-size: 17px;

        }


        /* =================================================
           BOOKING CARD
        ================================================= */

        .booking-card {

            background: var(--white);

            border:
                1px solid var(--border);

            border-radius: 18px;

            padding: 30px;

            box-shadow:
                0 15px 40px
                rgba(15,32,52,0.09);

            position: sticky;

            top: 100px;

        }


        .booking-title {

            margin: 0;

            color: var(--navy);

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size: 28px;

            font-weight: 700;

        }


        .booking-subtitle {

            margin:
                8px 0 25px;

            color: var(--muted);

            font-size: 14px;

            line-height: 1.6;

        }


        /* =================================================
           FORM LABEL
        ================================================= */

        .form-label {

            color: var(--navy);

            font-size: 14px;

            font-weight: 700;

            margin-bottom: 8px;

        }


        .form-label i {

            color: var(--gold);

        }


        /* =================================================
           INPUTS
        ================================================= */

        .form-control {

            min-height: 50px;

            border:
                1px solid #dfe3e8;

            border-radius: 10px;

            color: var(--text);

            background: var(--white);

            font-size: 14px;

            box-shadow: none;

        }


        .form-control:hover {

            border-color:
                #cbd1d8;

        }


        .form-control:focus {

            border-color:
                var(--gold);

            box-shadow:
                0 0 0 3px
                rgba(212,175,55,0.13);

        }


        /* =================================================
           CALCULATION
        ================================================= */

        .calculation {

            margin-top: 22px;

            padding-top: 18px;

            border-top:
                1px solid var(--border);

        }


        .calculation-row {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 13px;

            color: var(--muted);

            font-size: 14px;

        }


        .calculation-row strong {

            color: var(--navy);

            font-weight: 700;

        }


        /* =================================================
           TOTAL
        ================================================= */

        .total-box {

            margin-top: 20px;

            padding: 18px;

            border:
                1px solid var(--border);

            border-left:
                4px solid var(--gold);

            border-radius: 12px;

            background: #fffdf7;

        }


        .total-label {

            color: var(--navy);

            font-size: 15px;

            font-weight: 700;

        }


        .total-box small {

            color: var(--muted) !important;

            font-size: 12px;

        }


        .total-price {

            color: var(--gold);

            font-size: 22px;

            font-weight: 800;

            white-space: nowrap;

        }


        /* =================================================
           BOOKING BUTTON
        ================================================= */

        .booking-btn {

            width: 100%;

            min-height: 52px;

            margin-top: 20px;

            border: 0;

            border-radius: 10px;

            background: var(--gold);

            color: var(--navy);

            font-size: 15px;

            font-weight: 800;

            transition: all 0.25s ease;

            box-shadow:
                0 8px 20px
                rgba(212,175,55,0.18);

        }


        .booking-btn:hover {

            background: var(--gold-light);

            color: var(--navy);

            transform:
                translateY(-2px);

            box-shadow:
                0 12px 25px
                rgba(212,175,55,0.25);

        }


        .secure-note {

            margin-top: 14px;

            text-align: center;

            color: var(--muted);

            font-size: 12px;

        }


        .secure-note i {

            color: var(--gold);

        }


        /* =================================================
           EMPTY FACILITIES
        ================================================= */

        .empty-facilities {

            display: flex;

            align-items: center;

            gap: 15px;

            padding: 18px;

            border:
                1px solid var(--border);

            border-radius: 12px;

            background: #fff;

        }


        .empty-facilities .info-icon {

            margin: 0;

            flex-shrink: 0;

        }


        .empty-facilities strong {

            display: block;

            color: var(--navy);

            margin-bottom: 3px;

        }


        .empty-facilities p {

            margin: 0;

            color: var(--muted);

            font-size: 13px;

        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 991px) {

            .topbar-inner {

                padding:
                    12px 20px;

            }


            .page-container {

                padding:
                    22px 20px 45px;

            }


            .booking-card {

                position: static;

            }


            .room-visual {

                height: 330px;

            }

        }


        @media (max-width: 767px) {

            .topbar {

                min-height: auto;

            }


            .topbar-inner {

                flex-direction: column;

                align-items: stretch;

            }


            .brand {

                justify-content: center;

            }


            .nav-actions {

                justify-content: center;

            }


            .nav-btn {

                flex: 1;

            }


            .room-title-row {

                flex-direction: column;

            }


            .price-area {

                text-align: left;

            }


            .room-number {

                font-size: 32px;

            }


            .room-visual {

                height: 280px;

            }


            .room-content,

            .booking-card {

                padding: 22px;

            }

        }


        @media (max-width: 480px) {

            .brand-name {

                font-size: 21px;

            }


            .brand-subtitle {

                font-size: 11px;

            }


            .nav-btn {

                padding:
                    9px 10px;

                font-size: 12px;

            }


            .nav-btn span {

                display: none;

            }


            .room-visual {

                height: 240px;

            }


            .availability-badge {

                top: 14px;

                right: 14px;

                font-size: 11px;

                padding:
                    8px 11px;

            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     TOP NAVIGATION
===================================================== -->

<header class="topbar">

    <div class="topbar-inner">


        <!-- BRAND -->

        <a
            href="dashboard.php"
            class="brand"
        >

            <span class="brand-icon">

                <i class="bi bi-building"></i>

            </span>


            <span>

                <span class="brand-name">
                    Velora Hotel
                </span>

                <span class="brand-subtitle">
                    Hotel Management System
                </span>

            </span>

        </a>


        <!-- NAVIGATION -->

        <div class="nav-actions">

            <a
                href="rooms.php"
                class="nav-btn rooms-btn"
            >

                <i class="bi bi-door-open"></i>

                <span>
                    Back to Rooms
                </span>

            </a>


            <a
                href="dashboard.php"
                class="nav-btn dashboard-btn"
            >

                <i class="bi bi-grid-1x2"></i>

                <span>
                    Dashboard
                </span>

            </a>

        </div>


    </div>

</header>



<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<main class="page-container">


    <!-- BREADCRUMB -->

    <div class="breadcrumb-area">

        <a
            href="rooms.php"
            class="breadcrumb-link"
        >

            <i class="bi bi-arrow-left me-1"></i>

            Browse Rooms

        </a>


        <span class="text-muted mx-2">
            /
        </span>


        <span class="breadcrumb-current">

            Room
            <?= e($room["room_number"]) ?>

        </span>

    </div>



    <!-- =================================================
         MAIN GRID
    ================================================== -->

    <div class="row g-4">


        <!-- =================================================
             LEFT SIDE
        ================================================== -->

        <div class="col-lg-8">


            <div class="room-card">


                <!-- =================================================
                     ROOM IMAGE
                ================================================== -->

                <div
                    class="room-visual
                    <?= $room_image !== "" ? "has-image" : "" ?>"
                >


                    <?php if ($room_image !== ""): ?>

                        <img
                            src="<?= e($room_image) ?>"
                            alt="Room <?= e($room["room_number"]) ?>"
                        >

                    <?php else: ?>

                        <div class="room-placeholder">

                            <i class="bi bi-door-open"></i>

                        </div>

                    <?php endif; ?>


                    <!-- AVAILABILITY -->

                    <div class="availability-badge">

                        <i class="bi bi-check-circle-fill me-1"></i>

                        Available for Booking

                    </div>


                </div>



                <!-- =================================================
                     ROOM CONTENT
                ================================================== -->

                <div class="room-content">


                    <!-- TITLE + PRICE -->

                    <div class="room-title-row">


                        <div>

                            <h1 class="room-number">

                                Room
                                <?= e($room["room_number"]) ?>

                            </h1>


                            <div class="room-type">

                                <i class="bi bi-stars me-1"></i>

                                <?= e($room["type_name"]) ?>

                            </div>

                        </div>


                        <div class="price-area">

                            <span class="price">

                                Rs.
                                <?= number_format(
                                    $room_price,
                                    2
                                ) ?>

                            </span>


                            <span class="price-unit">
                                / night
                            </span>

                        </div>


                    </div>


                    <hr class="my-4">



                    <!-- =================================================
                         ROOM INFORMATION
                    ================================================== -->

                    <h3 class="info-title">

                        <i class="bi bi-info-circle me-2"></i>

                        Room Information

                    </h3>


                    <div class="row g-3 mb-4">


                        <!-- ROOM NUMBER -->

                        <div class="col-md-4">

                            <div class="info-box">

                                <div class="info-icon">

                                    <i class="bi bi-door-open"></i>

                                </div>


                                <span class="info-label">
                                    Room Number
                                </span>


                                <div class="info-value">

                                    <?= e(
                                        $room["room_number"]
                                    ) ?>

                                </div>

                            </div>

                        </div>



                        <!-- FLOOR -->

                        <?php if (
                            isset($room["floor_number"])
                        ): ?>

                            <div class="col-md-4">

                                <div class="info-box">

                                    <div class="info-icon">

                                        <i class="bi bi-building"></i>

                                    </div>


                                    <span class="info-label">
                                        Floor
                                    </span>


                                    <div class="info-value">

                                        <?= e(
                                            $room["floor_number"]
                                        ) ?>

                                    </div>

                                </div>

                            </div>

                        <?php endif; ?>



                        <!-- CAPACITY -->

                        <?php if (
                            isset($room["capacity"])
                        ): ?>

                            <div class="col-md-4">

                                <div class="info-box">

                                    <div class="info-icon">

                                        <i class="bi bi-people"></i>

                                    </div>


                                    <span class="info-label">
                                        Capacity
                                    </span>


                                    <div class="info-value">

                                        <?= e(
                                            $room["capacity"]
                                        ) ?>

                                        Guests

                                    </div>

                                </div>

                            </div>

                        <?php endif; ?>


                    </div>



                    <!-- =================================================
                         ROOM FACILITIES
                    ================================================== -->

                    <h3 class="info-title">

                        <i class="bi bi-stars me-2"></i>

                        Room Facilities

                    </h3>


                    <?php if (!empty($facilities)): ?>


                        <div class="row">


                            <?php foreach (
                                $facilities
                                as $facility
                            ): ?>


                                <div class="col-md-6">

                                    <div class="facility-item">

                                        <i class="bi bi-check-circle-fill"></i>


                                        <?php

                                        if (
                                            isset(
                                                $facility["facility_name"]
                                            )
                                        ) {

                                            echo e(
                                                $facility["facility_name"]
                                            );

                                        } elseif (
                                            isset(
                                                $facility["name"]
                                            )
                                        ) {

                                            echo e(
                                                $facility["name"]
                                            );

                                        } else {

                                            echo "Facility";

                                        }

                                        ?>

                                    </div>

                                </div>


                            <?php endforeach; ?>


                        </div>


                    <?php else: ?>


                        <div class="empty-facilities">


                            <div class="info-icon">

                                <i class="bi bi-info-circle"></i>

                            </div>


                            <div>

                                <strong>
                                    No facilities listed
                                </strong>


                                <p>
                                    Facilities have not been added
                                    to this room yet.
                                </p>

                            </div>


                        </div>


                    <?php endif; ?>


                </div>

            </div>


        </div>



        <!-- =================================================
             RIGHT SIDE - BOOKING
        ================================================== -->

        <div class="col-lg-4">


            <div class="booking-card">


                <h2 class="booking-title">
                    Book This Room
                </h2>


                <p class="booking-subtitle">
                    Select your stay dates to continue.
                </p>



                <form
                    method="GET"
                    action="booking.php"
                    id="bookingForm"
                >


                    <!-- ROOM ID -->

                    <input
                        type="hidden"
                        name="room_id"
                        value="<?= $room_id ?>"
                    >



                    <!-- =================================================
                         CHECK-IN
                    ================================================== -->

                    <div class="mb-3">

                        <label
                            class="form-label"
                            for="check_in"
                        >

                            <i class="bi bi-calendar-event me-1"></i>

                            Check-in Date

                        </label>


                        <input
                            type="date"
                            name="check_in"
                            id="check_in"
                            class="form-control"
                            value="<?= e($check_in) ?>"
                            min="<?= date("Y-m-d") ?>"
                            required
                        >

                    </div>



                    <!-- =================================================
                         CHECK-OUT
                    ================================================== -->

                    <div class="mb-3">

                        <label
                            class="form-label"
                            for="check_out"
                        >

                            <i class="bi bi-calendar-check me-1"></i>

                            Check-out Date

                        </label>


                        <input
                            type="date"
                            name="check_out"
                            id="check_out"
                            class="form-control"
                            value="<?= e($check_out) ?>"
                            min="<?= date(
                                "Y-m-d",
                                strtotime("+1 day")
                            ) ?>"
                            required
                        >

                    </div>



                    <!-- =================================================
                         CALCULATION
                    ================================================== -->

                    <div class="calculation">


                        <div class="calculation-row">

                            <span>
                                Number of nights
                            </span>


                            <strong id="nightDisplay">
                                <?= $nights ?>
                            </strong>

                        </div>


                        <div class="calculation-row">

                            <span>
                                Price per night
                            </span>


                            <strong>

                                Rs.
                                <?= number_format(
                                    $room_price,
                                    2
                                ) ?>

                            </strong>

                        </div>


                    </div>



                    <!-- =================================================
                         TOTAL
                    ================================================== -->

                    <div class="total-box">

                        <div
                            class="d-flex
                                   justify-content-between
                                   align-items-center
                                   gap-3"
                        >

                            <div>

                                <div class="total-label">
                                    Estimated Total
                                </div>


                                <small>
                                    Based on selected dates
                                </small>

                            </div>


                            <div
                                class="total-price"
                                id="totalDisplay"
                            >

                                Rs.
                                <?= number_format(
                                    $total,
                                    2
                                ) ?>

                            </div>

                        </div>

                    </div>



                    <!-- =================================================
                         BOOK BUTTON
                    ================================================== -->

                    <button
                        type="submit"
                        class="booking-btn"
                    >

                        <i class="bi bi-calendar-check me-2"></i>

                        Continue Booking

                    </button>


                    <div class="secure-note">

                        <i class="bi bi-shield-check me-1"></i>

                        Secure booking process

                    </div>


                </form>


            </div>


        </div>


    </div>


</main>



<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>


const checkIn =
    document.getElementById("check_in");


const checkOut =
    document.getElementById("check_out");


const nightDisplay =
    document.getElementById("nightDisplay");


const totalDisplay =
    document.getElementById("totalDisplay");


const bookingForm =
    document.getElementById("bookingForm");


const roomPrice =
    <?= json_encode($room_price) ?>;



/* =====================================================
   FORMAT MONEY
===================================================== */

function formatMoney(value) {

    return "Rs. " +
        Number(value).toLocaleString(
            "en-LK",
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );

}



/* =====================================================
   CALCULATE TOTAL
===================================================== */

function calculateTotal() {


    if (
        checkIn.value === "" ||
        checkOut.value === ""
    ) {

        nightDisplay.textContent = "0";

        totalDisplay.textContent =
            "Rs. 0.00";

        return;

    }


    const start =
        new Date(
            checkIn.value + "T00:00:00"
        );


    const end =
        new Date(
            checkOut.value + "T00:00:00"
        );


    const difference =
        end.getTime() -
        start.getTime();


    const nights =
        Math.round(
            difference /
            (1000 * 60 * 60 * 24)
        );


    if (nights <= 0) {

        nightDisplay.textContent =
            "0";

        totalDisplay.textContent =
            "Rs. 0.00";

        return;

    }


    const total =
        nights * Number(roomPrice);


    nightDisplay.textContent =
        nights;


    totalDisplay.textContent =
        formatMoney(total);

}



/* =====================================================
   CHECK-IN CHANGE
===================================================== */

checkIn.addEventListener(
    "change",
    function () {


        if (this.value) {


            const date =
                new Date(
                    this.value + "T00:00:00"
                );


            date.setDate(
                date.getDate() + 1
            );


            const year =
                date.getFullYear();


            const month =
                String(
                    date.getMonth() + 1
                ).padStart(2, "0");


            const day =
                String(
                    date.getDate()
                ).padStart(2, "0");


            const minimumCheckout =
                `${year}-${month}-${day}`;


            checkOut.min =
                minimumCheckout;


            if (
                checkOut.value &&
                checkOut.value <= this.value
            ) {

                checkOut.value = "";

            }

        }


        calculateTotal();

    }
);



/* =====================================================
   CHECK-OUT CHANGE
===================================================== */

checkOut.addEventListener(
    "change",
    calculateTotal
);



/* =====================================================
   FORM VALIDATION
===================================================== */

bookingForm.addEventListener(
    "submit",
    function (event) {


        if (
            !checkIn.value ||
            !checkOut.value
        ) {

            event.preventDefault();

            alert(
                "Please select both check-in and check-out dates."
            );

            return;

        }


        const start =
            new Date(
                checkIn.value + "T00:00:00"
            );


        const end =
            new Date(
                checkOut.value + "T00:00:00"
            );


        if (end <= start) {

            event.preventDefault();

            alert(
                "Check-out date must be after the check-in date."
            );

            return;

        }

    }
);



/* =====================================================
   INITIAL CALCULATION
===================================================== */

calculateTotal();

</script>


</body>

</html>