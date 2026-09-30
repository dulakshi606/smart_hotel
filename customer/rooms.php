<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

startSession();
requireRole("customer");


/* =====================================================
   SEARCH VALUES
===================================================== */

$check_in  = trim($_GET["check_in"] ?? "");
$check_out = trim($_GET["check_out"] ?? "");

$rooms = [];

$searched = false;
$error = "";


/* =====================================================
   CHECK WHETHER IMAGE COLUMN EXISTS
===================================================== */

$hasImageColumn = false;

try {

    $columnCheck = $pdo->query("
        SHOW COLUMNS FROM rooms LIKE 'image'
    ");

    $hasImageColumn = ($columnCheck->fetch(PDO::FETCH_ASSOC) !== false);

} catch (PDOException $e) {

    $error = "Database Error: " . $e->getMessage();

}


/* =====================================================
   IMAGE SELECT FIELD
===================================================== */

$imageSelect = $hasImageColumn
    ? "r.image"
    : "NULL AS image";


/* =====================================================
   SEARCH AVAILABLE ROOMS
===================================================== */

if ($check_in !== "" && $check_out !== "") {

    $searched = true;

    $today = date("Y-m-d");


    /* =================================================
       CHECK-IN CANNOT BE BEFORE TODAY
    ================================================= */

    if ($check_in < $today) {

        $error = "Check-in date cannot be before today.";

    }


    /* =================================================
       CHECK-OUT MUST BE AFTER CHECK-IN
    ================================================= */

    elseif ($check_out <= $check_in) {

        $error = "Check-out date must be after the check-in date.";

    }


    /* =================================================
       SEARCH ROOMS
    ================================================= */

    else {

        try {

            $sql = "
                SELECT
                    r.id,
                    r.room_number,
                    r.description,
                    r.status,
                    $imageSelect,
                    rt.type_name,
                    rt.base_price,
                    rt.capacity

                FROM rooms r

                INNER JOIN room_types rt
                    ON r.room_type_id = rt.id

                WHERE r.status = 'available'

                /* ROOM 402 IS NOT DISPLAYED */
                AND r.room_number <> '402'

                /* EXCLUDE ALREADY BOOKED ROOMS */
                AND r.id NOT IN (

                    SELECT b.room_id

                    FROM bookings b

                    WHERE b.booking_status IN
                        ('pending', 'confirmed', 'checked_in')

                    AND b.check_in < ?

                    AND b.check_out > ?

                )

                ORDER BY
                    CAST(r.room_number AS UNSIGNED)
            ";


            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $check_out,
                $check_in
            ]);

            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);


        } catch (PDOException $e) {

            /* SHOW REAL DATABASE ERROR */
            $error = "Database Error: " . $e->getMessage();

        }

    }

}


/* =====================================================
   SHOW ALL AVAILABLE ROOMS
   WHEN NO SEARCH IS PERFORMED
===================================================== */

else {

    try {

        $sql = "
            SELECT
                r.id,
                r.room_number,
                r.description,
                r.status,
                $imageSelect,
                rt.type_name,
                rt.base_price,
                rt.capacity

            FROM rooms r

            INNER JOIN room_types rt
                ON r.room_type_id = rt.id

            WHERE r.status = 'available'

            /* ROOM 402 IS NOT DISPLAYED */
            AND r.room_number <> '402'

            ORDER BY
                CAST(r.room_number AS UNSIGNED)
        ";


        $stmt = $pdo->query($sql);

        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);


    } catch (PDOException $e) {

        /* SHOW REAL DATABASE ERROR */
        $error = "Database Error: " . $e->getMessage();

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

    <title>Browse Rooms - Velora Hotel</title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         BOOTSTRAP ICONS
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <style>

        /* =====================================================
           VELORA THEME
        ===================================================== */

        :root {

            --navy: #0f172a;
            --navy-light: #172033;
            --navy-hover: #1e293b;

            --gold: #c9a227;
            --gold-dark: #a88418;
            --gold-light: #f8edc5;

            --background: #f6f5f1;
            --surface: #ffffff;

            --text: #172033;
            --muted: #64748b;
            --border: #e5e7eb;

        }


        /* =====================================================
           GLOBAL
        ===================================================== */

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family:
                "Segoe UI",
                Arial,
                sans-serif;

            background: var(--background);

            color: var(--text);

        }


        /* =====================================================
           NAVBAR
        ===================================================== */

        .velora-navbar {

            background: var(--navy);

            min-height: 76px;

            padding: 0;

            box-shadow:
                0 4px 18px rgba(0, 0, 0, 0.12);

            position: sticky;

            top: 0;

            z-index: 1000;

        }


        .velora-brand {

            display: flex;

            align-items: center;

            gap: 10px;

            color: white !important;

            font-size: 21px;

            font-weight: 700;

            text-decoration: none;

        }


        .velora-brand:hover {

            color: white !important;

        }


        .brand-icon {

            width: 42px;

            height: 42px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: var(--gold);

            color: var(--navy);

            border-radius: 50%;

            font-size: 20px;

        }


        .velora-navbar .nav-link {

            color: #cbd5e1 !important;

            font-size: 14px;

            font-weight: 500;

            padding: 27px 13px !important;

            display: flex;

            align-items: center;

            gap: 7px;

            transition: 0.3s;

            position: relative;

        }


        .velora-navbar .nav-link i {

            font-size: 15px;

        }


        .velora-navbar .nav-link:hover {

            color: var(--gold) !important;

        }


        .velora-navbar .nav-link.active {

            color: var(--gold) !important;

        }


        .velora-navbar .nav-link.active::after {

            content: "";

            position: absolute;

            left: 13px;

            right: 13px;

            bottom: 0;

            height: 3px;

            background: var(--gold);

            border-radius: 3px 3px 0 0;

        }


        .logout-nav {

            color: #fca5a5 !important;

        }


        .logout-nav:hover {

            color: #ff6b6b !important;

        }


        .velora-navbar .navbar-toggler {

            border:
                1px solid
                rgba(201, 162, 39, 0.5);

            padding: 8px 10px;

        }


        .velora-navbar .navbar-toggler:focus {

            box-shadow:
                0 0 0 3px
                rgba(201, 162, 39, 0.2);

        }


        .velora-navbar .navbar-toggler-icon {

            filter:
                brightness(0) invert(1);

        }


        /* =====================================================
           HERO
        ===================================================== */

        .page-hero {

            background:
                linear-gradient(
                    135deg,
                    #0f172a,
                    #172033
                );

            padding: 55px 20px;

            text-align: center;

            color: white;

            position: relative;

            overflow: hidden;

        }


        .page-hero::after {

            content: "";

            position: absolute;

            width: 250px;

            height: 250px;

            right: -80px;

            top: -100px;

            border-radius: 50%;

            border:
                35px solid
                rgba(201, 162, 39, 0.12);

        }


        .page-hero h1 {

            font-size: 36px;

            font-weight: 700;

            margin-bottom: 10px;

            position: relative;

            z-index: 1;

        }


        .page-hero p {

            color: #cbd5e1;

            margin: 0;

            position: relative;

            z-index: 1;

        }


        /* =====================================================
           SEARCH
        ===================================================== */

        .search-wrapper {

            max-width: 1100px;

            margin:
                -30px auto 40px;

            padding: 0 20px;

            position: relative;

            z-index: 5;

        }


        .search-card {

            background: white;

            border:
                1px solid var(--border);

            border-radius: 16px;

            padding: 25px;

            box-shadow:
                0 10px 30px
                rgba(0, 0, 0, 0.08);

        }


        .search-title {

            color: var(--navy);

            font-size: 18px;

            font-weight: 700;

            margin-bottom: 18px;

        }


        .form-label {

            font-size: 13px;

            font-weight: 600;

            color: var(--text);

        }


        .form-control {

            min-height: 45px;

            border:
                1px solid var(--border);

            border-radius: 8px;

        }


        .form-control:focus {

            border-color: var(--gold);

            box-shadow:
                0 0 0 3px
                rgba(201, 162, 39, 0.12);

        }


        .search-btn {

            width: 100%;

            min-height: 45px;

            border: none;

            border-radius: 8px;

            background: var(--gold);

            color: var(--navy);

            font-weight: 700;

            transition: 0.3s;

        }


        .search-btn:hover {

            background: #e1bd3d;

            transform: translateY(-1px);

        }


        .clear-btn {

            width: 100%;

            min-height: 45px;

            border-radius: 8px;

            background: white;

            color: var(--navy);

            border:
                1px solid var(--border);

            font-weight: 600;

            text-decoration: none;

            display: flex;

            align-items: center;

            justify-content: center;

        }


        .clear-btn:hover {

            border-color: var(--gold);

            color: var(--gold-dark);

        }


        /* =====================================================
           CONTENT
        ===================================================== */

        .rooms-container {

            max-width: 1300px;

            margin: 0 auto;

            padding:
                0 20px 50px;

        }


        .results-title {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 20px;

        }


        .results-title h2 {

            font-size: 25px;

            font-weight: 700;

            color: var(--navy);

            margin: 0;

        }


        .room-count {

            background: var(--gold-light);

            color: var(--gold-dark);

            padding: 7px 13px;

            border-radius: 20px;

            font-size: 13px;

            font-weight: 700;

        }


        /* =====================================================
           ERROR
        ===================================================== */

        .error-box {

            background: #fff1f2;

            border:
                1px solid #fecdd3;

            color: #be123c;

            padding: 15px 18px;

            border-radius: 10px;

            margin-bottom: 25px;

            word-break: break-word;

        }


        /* =====================================================
           ROOM GRID
        ===================================================== */

        .rooms-grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 25px;

        }


        .room-card {

            background: white;

            border:
                1px solid var(--border);

            border-radius: 16px;

            overflow: hidden;

            transition: 0.3s;

            display: flex;

            flex-direction: column;

        }


        .room-card:hover {

            transform: translateY(-5px);

            border-color: var(--gold);

            box-shadow:
                0 12px 30px
                rgba(0, 0, 0, 0.10);

        }


        /* =====================================================
           IMAGE
        ===================================================== */

        .room-image {

            height: 220px;

            background:
                linear-gradient(
                    135deg,
                    #e5e7eb,
                    #f8fafc
                );

            position: relative;

            overflow: hidden;

        }


        .room-image img {

            width: 100%;

            height: 100%;

            object-fit: cover;

            display: block;

        }


        .image-placeholder {

            width: 100%;

            height: 100%;

            display: flex;

            align-items: center;

            justify-content: center;

            color: #94a3b8;

            font-size: 55px;

        }


        .available-badge {

            position: absolute;

            top: 15px;

            right: 15px;

            background: #dcfce7;

            color: #166534;

            padding: 6px 11px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 700;

        }


        /* =====================================================
           ROOM BODY
        ===================================================== */

        .room-body {

            padding: 22px;

            display: flex;

            flex-direction: column;

            flex: 1;

        }


        .room-number {

            color: var(--gold-dark);

            font-size: 13px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.5px;

            margin-bottom: 5px;

        }


        .room-name {

            color: var(--navy);

            font-size: 21px;

            font-weight: 700;

            margin-bottom: 10px;

        }


        .room-description {

            color: var(--muted);

            font-size: 14px;

            line-height: 1.6;

            margin-bottom: 18px;

        }


        .room-info {

            display: flex;

            flex-wrap: wrap;

            gap: 15px;

            margin-bottom: 18px;

            padding-bottom: 18px;

            border-bottom:
                1px solid var(--border);

        }


        .room-info-item {

            display: flex;

            align-items: center;

            gap: 6px;

            color: var(--muted);

            font-size: 13px;

        }


        .room-info-item i {

            color: var(--gold-dark);

            font-size: 16px;

        }


        /* =====================================================
           PRICE
        ===================================================== */

        .room-footer {

            margin-top: auto;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

        }


        .price-label {

            color: var(--muted);

            font-size: 12px;

        }


        .room-price {

            color: var(--navy);

            font-size: 21px;

            font-weight: 700;

        }


        .room-price small {

            color: var(--muted);

            font-size: 12px;

            font-weight: 400;

        }


        .details-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            background: var(--navy);

            color: white;

            text-decoration: none;

            padding: 10px 15px;

            border-radius: 8px;

            font-size: 13px;

            font-weight: 600;

            transition: 0.3s;

        }


        .details-btn:hover {

            background: var(--gold);

            color: var(--navy);

        }


        /* =====================================================
           NO ROOMS
        ===================================================== */

        .no-rooms {

            background: white;

            border:
                1px solid var(--border);

            border-radius: 16px;

            padding: 60px 20px;

            text-align: center;

        }


        .no-rooms-icon {

            width: 70px;

            height: 70px;

            margin: 0 auto 20px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background: var(--gold-light);

            color: var(--gold-dark);

            font-size: 30px;

        }


        .no-rooms h3 {

            color: var(--navy);

            font-size: 22px;

            margin-bottom: 8px;

        }


        .no-rooms p {

            color: var(--muted);

            margin: 0;

        }


        /* =====================================================
           FOOTER
        ===================================================== */

        .page-footer {

            background: var(--navy);

            color: #94a3b8;

            text-align: center;

            padding: 25px 15px;

            font-size: 13px;

        }


        .page-footer strong {

            color: var(--gold);

        }


        /* =====================================================
           TABLET
        ===================================================== */

        @media (max-width: 1100px) {

            .rooms-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        /* =====================================================
           MOBILE NAVBAR
        ===================================================== */

        @media (max-width: 991px) {

            .velora-navbar {

                padding: 10px 0;

            }


            .velora-navbar .nav-link {

                padding:
                    12px 10px !important;

            }


            .velora-navbar .nav-link.active::after {

                display: none;

            }


            .velora-navbar .nav-link.active {

                background:
                    rgba(201, 162, 39, 0.12);

                border-radius: 8px;

            }


            .velora-navbar .navbar-nav {

                padding-top: 10px;

                padding-bottom: 10px;

            }

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 768px) {

            .page-hero {

                padding: 40px 20px;

            }


            .page-hero h1 {

                font-size: 29px;

            }


            .search-wrapper {

                margin-top: -20px;

            }


            .search-card {

                padding: 20px;

            }


            .rooms-grid {

                grid-template-columns: 1fr;

            }


            .results-title {

                align-items: flex-start;

                flex-direction: column;

            }


            .room-image {

                height: 230px;

            }

        }


        /* =====================================================
           SMALL MOBILE
        ===================================================== */

        @media (max-width: 480px) {

            .velora-brand {

                font-size: 18px;

            }


            .brand-icon {

                width: 38px;

                height: 38px;

                font-size: 17px;

            }


            .room-footer {

                flex-direction: column;

                align-items: stretch;

            }


            .details-btn {

                width: 100%;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<nav class="navbar navbar-expand-lg velora-navbar">

    <div class="container-fluid px-lg-4">

        <a
            class="navbar-brand velora-brand"
            href="dashboard.php"
        >

            <span class="brand-icon">

                <i class="bi bi-building"></i>

            </span>

            <span>Velora Hotel</span>

        </a>


        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#customerNavbar"
            aria-controls="customerNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >

            <span class="navbar-toggler-icon"></span>

        </button>


        <div
            class="collapse navbar-collapse"
            id="customerNavbar"
        >

            <ul class="navbar-nav ms-auto align-items-lg-center">


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="dashboard.php"
                    >

                        <i class="bi bi-grid-1x2-fill"></i>

                        Dashboard

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link active"
                        href="rooms.php"
                    >

                        <i class="bi bi-door-open-fill"></i>

                        Rooms

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="bookings.php"
                    >

                        <i class="bi bi-calendar-check-fill"></i>

                        My Bookings

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="meals.php"
                    >

                        <i class="bi bi-cup-hot-fill"></i>

                        Meals & Dining

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="service_requests.php"
                    >

                        <i class="bi bi-bell-fill"></i>

                        Services

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="profile.php"
                    >

                        <i class="bi bi-person-fill"></i>

                        Profile

                    </a>

                </li>


                <li class="nav-item ms-lg-2">

                    <a
                        class="nav-link logout-nav"
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



<!-- =========================================================
     HERO
========================================================= -->

<section class="page-hero">

    <h1>

        Discover Our Rooms

    </h1>

    <p>

        Find the perfect room for a comfortable and memorable stay.

    </p>

</section>



<!-- =========================================================
     SEARCH
========================================================= -->

<div class="search-wrapper">

    <div class="search-card">

        <div class="search-title">

            <i class="bi bi-calendar2-check me-2"></i>

            Search Available Rooms

        </div>


        <form
            method="GET"
            action="rooms.php"
        >

            <div class="row g-3 align-items-end">


                <div class="col-md-4">

                    <label
                        for="check_in"
                        class="form-label"
                    >

                        Check-in

                    </label>

                    <input
                        type="date"
                        class="form-control"
                        id="check_in"
                        name="check_in"
                        value="<?= e($check_in); ?>"
                        min="<?= date("Y-m-d"); ?>"
                    >

                </div>


                <div class="col-md-4">

                    <label
                        for="check_out"
                        class="form-label"
                    >

                        Check-out

                    </label>

                    <input
                        type="date"
                        class="form-control"
                        id="check_out"
                        name="check_out"
                        value="<?= e($check_out); ?>"
                        min="<?= date("Y-m-d"); ?>"
                    >

                </div>


                <div class="col-md-2">

                    <button
                        type="submit"
                        class="search-btn"
                    >

                        <i class="bi bi-search me-1"></i>

                        Search

                    </button>

                </div>


                <div class="col-md-2">

                    <a
                        href="rooms.php"
                        class="clear-btn"
                    >

                        <i class="bi bi-arrow-counterclockwise me-1"></i>

                        Clear

                    </a>

                </div>

            </div>

        </form>

    </div>

</div>



<!-- =========================================================
     ROOMS
========================================================= -->

<div class="rooms-container">


    <?php if ($error !== ""): ?>

        <div class="error-box">

            <i class="bi bi-exclamation-circle me-2"></i>

            <?= e($error); ?>

        </div>

    <?php endif; ?>


    <div class="results-title">

        <h2>

            <?php if ($searched): ?>

                Available Rooms

            <?php else: ?>

                Our Rooms

            <?php endif; ?>

        </h2>


        <span class="room-count">

            <?= count($rooms); ?>

            Room<?= count($rooms) !== 1 ? "s" : ""; ?>

            Available

        </span>

    </div>



    <?php if (count($rooms) > 0): ?>

        <div class="rooms-grid">


            <?php foreach ($rooms as $room): ?>


                <?php

                /* =================================================
                   ROOM IMAGE
                ================================================= */

                $imageFile = trim($room["image"] ?? "");

                $imagePath = "";


                if ($imageFile !== "") {

                    if (
                        strpos($imageFile, "/") === 0 ||
                        strpos($imageFile, "../") === 0
                    ) {

                        $imagePath = $imageFile;

                    } else {

                        $imagePath =
                            "../uploads/rooms/" . $imageFile;

                    }

                }

                ?>


                <div class="room-card">


                    <!-- IMAGE -->

                    <div class="room-image">


                        <?php if ($imagePath !== ""): ?>

                            <img
                                src="<?= e($imagePath); ?>"
                                alt="Room <?= e($room["room_number"]); ?>"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                            >

                            <div
                                class="image-placeholder"
                                style="display:none;"
                            >

                                <i class="bi bi-door-open"></i>

                            </div>

                        <?php else: ?>

                            <div class="image-placeholder">

                                <i class="bi bi-door-open"></i>

                            </div>

                        <?php endif; ?>


                        <span class="available-badge">

                            <i class="bi bi-check-circle-fill me-1"></i>

                            Available

                        </span>

                    </div>



                    <!-- BODY -->

                    <div class="room-body">


                        <div class="room-number">

                            Room
                            <?= e($room["room_number"]); ?>

                        </div>


                        <div class="room-name">

                            <?= e($room["type_name"]); ?>

                        </div>


                        <div class="room-description">

                            <?php

                            $description =
                                trim($room["description"] ?? "");

                            if ($description !== "") {

                                echo e($description);

                            } else {

                                echo "Enjoy a comfortable stay with modern facilities and a relaxing atmosphere.";

                            }

                            ?>

                        </div>


                        <div class="room-info">


                            <div class="room-info-item">

                                <i class="bi bi-people-fill"></i>

                                <span>

                                    Up to
                                    <?= (int)$room["capacity"]; ?>

                                    guest<?= (int)$room["capacity"] !== 1 ? "s" : ""; ?>

                                </span>

                            </div>


                            <div class="room-info-item">

                                <i class="bi bi-house-door-fill"></i>

                                <span>

                                    <?= e($room["type_name"]); ?>

                                </span>

                            </div>

                        </div>



                        <div class="room-footer">


                            <div>

                                <div class="price-label">

                                    Starting from

                                </div>

                                <div class="room-price">

                                    LKR
                                    <?= number_format(
                                        (float)$room["base_price"],
                                        2
                                    ); ?>

                                    <small>
                                        / night
                                    </small>

                                </div>

                            </div>


                            <a
                                href="room_details.php?id=<?= (int)$room["id"]; ?>&check_in=<?= urlencode($check_in); ?>&check_out=<?= urlencode($check_out); ?>"
                                class="details-btn"
                            >

                                View Details

                                <i class="bi bi-arrow-right"></i>

                            </a>

                        </div>

                    </div>

                </div>


            <?php endforeach; ?>


        </div>


    <?php else: ?>


        <div class="no-rooms">

            <div class="no-rooms-icon">

                <i class="bi bi-door-closed"></i>

            </div>


            <?php if ($error !== ""): ?>

                <h3>

                    Unable to Load Rooms

                </h3>

                <p>

                    Please check the database error shown above.

                </p>

            <?php elseif ($searched): ?>

                <h3>

                    No Rooms Available

                </h3>

                <p>

                    No rooms are available for the selected dates.
                    Please try different dates.

                </p>

            <?php else: ?>

                <h3>

                    No Rooms Available

                </h3>

                <p>

                    There are currently no available rooms.

                </p>

            <?php endif; ?>

        </div>

    <?php endif; ?>


</div>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer class="page-footer">

    © <?= date("Y"); ?>

    <strong>Velora Hotel</strong>

    — Experience Comfort & Luxury

</footer>



<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>



<!-- =========================================================
     DATE VALIDATION
========================================================= -->

<script>

const checkIn =
    document.getElementById("check_in");

const checkOut =
    document.getElementById("check_out");


function updateCheckoutMin() {

    if (checkIn.value !== "") {

        checkOut.min = checkIn.value;

    }

}


checkIn.addEventListener(
    "change",
    updateCheckoutMin
);


updateCheckoutMin();


checkOut.addEventListener(
    "change",
    function () {

        if (
            checkIn.value !== "" &&
            checkOut.value !== "" &&
            checkOut.value <= checkIn.value
        ) {

            alert(
                "Check-out date must be after the check-in date."
            );

            checkOut.value = "";

        }

    }
);

</script>


</body>

</html>