<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

requireRole("customer");


/*
|--------------------------------------------------------------------------
| SEARCH VALUES
|--------------------------------------------------------------------------
*/

$check_in  = $_GET["check_in"] ?? "";
$check_out = $_GET["check_out"] ?? "";

$rooms = [];

$searched = false;

$error = "";


/*
|--------------------------------------------------------------------------
| SEARCH AVAILABLE ROOMS
|--------------------------------------------------------------------------
*/

if ($check_in !== "" && $check_out !== "") {

    $searched = true;

    $today = date("Y-m-d");


    /*
    |----------------------------------------------------------------------
    | DATE VALIDATION
    |----------------------------------------------------------------------
    */

    if ($check_in < $today) {

        $error = "Check-in date cannot be before today.";

    } elseif ($check_out <= $check_in) {

        $error = "Check-out date must be after check-in date.";

    } else {


        /*
        |------------------------------------------------------------------
        | GET AVAILABLE ROOMS
        |------------------------------------------------------------------
        |
        | IMPORTANT:
        | capacity comes from room_types table.
        |
        */

        $stmt = $pdo->prepare("
            SELECT

                r.id,
                r.room_number,
                r.floor_number,
                r.status,
                r.description,
                r.image,

                rt.type_name,
                rt.base_price AS price,
                rt.capacity

            FROM rooms r

            INNER JOIN room_types rt
                ON r.room_type_id = rt.id

            WHERE r.status = 'available'

            /* Hide room 402 */

            AND r.room_number <> '402'

            /* Do not show rooms already booked */

            AND r.id NOT IN (

                SELECT b.room_id

                FROM bookings b

                WHERE b.booking_status IN
                (
                    'pending',
                    'confirmed',
                    'checked_in'
                )

                AND b.check_in < ?

                AND b.check_out > ?

            )

            ORDER BY
                CAST(r.room_number AS UNSIGNED) ASC
        ");


        $stmt->execute([
            $check_out,
            $check_in
        ]);


        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}


/*
|--------------------------------------------------------------------------
| SHOW ALL AVAILABLE ROOMS
|--------------------------------------------------------------------------
*/

if (!$searched) {

    $stmt = $pdo->query("
        SELECT

            r.id,
            r.room_number,
            r.floor_number,
            r.status,
            r.description,
            r.image,

            rt.type_name,
            rt.base_price AS price,
            rt.capacity

        FROM rooms r

        INNER JOIN room_types rt
            ON r.room_type_id = rt.id

        WHERE r.status = 'available'

        /* Hide room 402 */

        AND r.room_number <> '402'

        ORDER BY
            CAST(r.room_number AS UNSIGNED) ASC
    ");


    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    <title>Our Rooms - Velora Hotel</title>


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

        /*
        |--------------------------------------------------------------------------
        | GENERAL
        |--------------------------------------------------------------------------
        */

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            background: #f7f3eb;

            color: #172033;

            font-family:
                Arial,
                Helvetica,
                sans-serif;
        }


        /*
        |--------------------------------------------------------------------------
        | NAVBAR
        |--------------------------------------------------------------------------
        */

        .navbar-velora {

            background: #0b1f3a;

            padding: 15px 0;

            box-shadow:
                0 4px 18px
                rgba(0, 0, 0, 0.12);
        }


        .navbar-brand {

            color: #d4af63 !important;

            font-size: 25px;

            font-weight: 700;

            letter-spacing: 0.5px;
        }


        .navbar-brand i {

            margin-right: 8px;
        }


        .nav-link {

            color: #ffffff !important;

            font-weight: 500;

            margin-left: 12px;

            transition: 0.3s;
        }


        .nav-link:hover {

            color: #d4af63 !important;
        }


        .logout-btn {

            border: 1px solid #d4af63;

            color: #d4af63 !important;

            border-radius: 7px;

            padding: 7px 16px !important;
        }


        .logout-btn:hover {

            background: #d4af63;

            color: #0b1f3a !important;
        }


        /*
        |--------------------------------------------------------------------------
        | HERO
        |--------------------------------------------------------------------------
        */

        .hero {

            background:
                linear-gradient(
                    rgba(11,31,58,0.90),
                    rgba(11,31,58,0.90)
                );

            padding: 65px 20px;

            text-align: center;

            color: white;
        }


        .hero h1 {

            font-size: 44px;

            font-weight: 700;

            margin-bottom: 12px;
        }


        .hero h1 span {

            color: #d4af63;
        }


        .hero p {

            color: #d9dee7;

            font-size: 17px;

            margin: 0;
        }


        /*
        |--------------------------------------------------------------------------
        | SEARCH BOX
        |--------------------------------------------------------------------------
        */

        .search-wrapper {

            max-width: 1000px;

            margin: -35px auto 50px;

            padding: 0 20px;

            position: relative;

            z-index: 10;
        }


        .search-box {

            background: white;

            border-radius: 14px;

            padding: 25px;

            box-shadow:
                0 8px 30px
                rgba(11,31,58,0.12);
        }


        .search-box label {

            font-weight: 600;

            color: #26364d;

            margin-bottom: 7px;
        }


        .search-box .form-control {

            height: 47px;

            border: 1px solid #d9dee7;

            border-radius: 8px;
        }


        .search-box .form-control:focus {

            border-color: #d4af63;

            box-shadow:
                0 0 0 0.2rem
                rgba(212,175,99,0.18);
        }


        .search-btn {

            height: 47px;

            background: #0b1f3a;

            border: none;

            color: white;

            border-radius: 8px;

            font-weight: 600;

            width: 100%;

            transition: 0.3s;
        }


        .search-btn:hover {

            background: #d4af63;

            color: #0b1f3a;
        }


        .clear-btn {

            height: 47px;

            display: flex;

            align-items: center;

            justify-content: center;

            border: 1px solid #d4af63;

            color: #0b1f3a;

            border-radius: 8px;

            text-decoration: none;

            font-weight: 600;
        }


        .clear-btn:hover {

            background: #d4af63;

            color: #0b1f3a;
        }


        /*
        |--------------------------------------------------------------------------
        | MAIN CONTENT
        |--------------------------------------------------------------------------
        */

        .rooms-section {

            max-width: 1250px;

            margin: auto;

            padding: 0 20px 70px;
        }


        .section-title {

            text-align: center;

            margin-bottom: 35px;
        }


        .section-title h2 {

            color: #0b1f3a;

            font-size: 32px;

            font-weight: 700;

            margin-bottom: 8px;
        }


        .section-title h2 span {

            color: #d4af63;
        }


        .section-title p {

            color: #6b7280;

            margin: 0;
        }


        /*
        |--------------------------------------------------------------------------
        | ALERT
        |--------------------------------------------------------------------------
        */

        .custom-alert {

            max-width: 1000px;

            margin: 0 auto 30px;

            border-radius: 10px;
        }


        /*
        |--------------------------------------------------------------------------
        | ROOM CARD
        |--------------------------------------------------------------------------
        */

        .room-card {

            background: white;

            border-radius: 14px;

            overflow: hidden;

            height: 100%;

            box-shadow:
                0 6px 25px
                rgba(11,31,58,0.08);

            transition:
                transform 0.3s ease,
                box-shadow 0.3s ease;
        }


        .room-card:hover {

            transform: translateY(-6px);

            box-shadow:
                0 14px 35px
                rgba(11,31,58,0.15);
        }


        /*
        |--------------------------------------------------------------------------
        | ROOM IMAGE
        |--------------------------------------------------------------------------
        */

        .room-image {

            width: 100%;

            height: 230px;

            overflow: hidden;

            background: #f3eee4;

            position: relative;
        }


        .room-image img {

            width: 100%;

            height: 100%;

            object-fit: cover;

            display: block;

            transition:
                transform 0.5s ease;
        }


        .room-card:hover
        .room-image img {

            transform: scale(1.06);
        }


        .room-image-placeholder {

            width: 100%;

            height: 230px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #f3eee4;

            color: #b58b3a;

            font-size: 55px;
        }


        /*
        |--------------------------------------------------------------------------
        | ROOM CONTENT
        |--------------------------------------------------------------------------
        */

        .room-content {

            padding: 22px;
        }


        .room-number {

            color: #d4af63;

            font-size: 14px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 1px;
        }


        .room-type {

            color: #0b1f3a;

            font-size: 23px;

            font-weight: 700;

            margin-top: 5px;

            margin-bottom: 10px;
        }


        .room-description {

            color: #6b7280;

            font-size: 14px;

            line-height: 1.6;

            min-height: 45px;
        }


        /*
        |--------------------------------------------------------------------------
        | ROOM DETAILS
        |--------------------------------------------------------------------------
        */

        .room-info {

            display: flex;

            justify-content: space-between;

            gap: 10px;

            margin-top: 18px;

            padding-top: 15px;

            border-top:
                1px solid #eeeeee;
        }


        .info-item {

            color: #4b5563;

            font-size: 14px;

            display: flex;

            align-items: center;

            gap: 5px;
        }


        .info-item i {

            color: #b58b3a;
        }


        /*
        |--------------------------------------------------------------------------
        | PRICE
        |--------------------------------------------------------------------------
        */

        .room-price {

            margin-top: 18px;

            color: #0b1f3a;

            font-size: 21px;

            font-weight: 700;
        }


        .room-price small {

            color: #6b7280;

            font-size: 12px;

            font-weight: 400;
        }


        /*
        |--------------------------------------------------------------------------
        | BUTTON
        |--------------------------------------------------------------------------
        */

        .details-btn {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            width: 100%;

            margin-top: 18px;

            padding: 11px 15px;

            background: #0b1f3a;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-weight: 600;

            transition: 0.3s;
        }


        .details-btn:hover {

            background: #d4af63;

            color: #0b1f3a;
        }


        /*
        |--------------------------------------------------------------------------
        | NO ROOMS
        |--------------------------------------------------------------------------
        */

        .no-rooms {

            background: white;

            border-radius: 14px;

            padding: 60px 25px;

            text-align: center;

            box-shadow:
                0 6px 25px
                rgba(11,31,58,0.07);
        }


        .no-rooms i {

            font-size: 55px;

            color: #d4af63;

            margin-bottom: 15px;
        }


        .no-rooms h4 {

            color: #0b1f3a;

            font-weight: 700;
        }


        .no-rooms p {

            color: #6b7280;
        }


        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        footer {

            background: #0b1f3a;

            color: #cbd5e1;

            text-align: center;

            padding: 25px 15px;
        }


        footer strong {

            color: #d4af63;
        }


        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 768px) {

            .hero {

                padding: 45px 15px;
            }


            .hero h1 {

                font-size: 32px;
            }


            .search-wrapper {

                margin-top: -25px;
            }


            .room-info {

                flex-direction: column;

                align-items: flex-start;
            }

        }

    </style>

</head>


<body>


<!-- ==============================================================
     NAVBAR
================================================================ -->

<nav class="navbar navbar-expand-lg navbar-velora">

    <div class="container">


        <a
            class="navbar-brand"
            href="dashboard.php"
        >

            <i class="bi bi-building"></i>

            Velora Hotel

        </a>


        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarNav"
        >

            <i
                class="bi bi-list"
                style="color:white;font-size:25px;"
            ></i>

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
                        class="nav-link logout-btn"
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



<!-- ==============================================================
     HERO
================================================================ -->

<section class="hero">

    <h1>

        Discover Our
        <span>Rooms</span>

    </h1>


    <p>

        Experience comfort, elegance and exceptional hospitality at Velora Hotel.

    </p>

</section>



<!-- ==============================================================
     SEARCH
================================================================ -->

<div class="search-wrapper">

    <div class="search-box">


        <form
            method="GET"
            action="rooms.php"
        >

            <div class="row g-3 align-items-end">


                <!-- CHECK IN -->

                <div class="col-md-4">

                    <label for="check_in">

                        <i class="bi bi-calendar-check me-1"></i>

                        Check-in Date

                    </label>


                    <input
                        type="date"
                        id="check_in"
                        name="check_in"
                        class="form-control"
                        value="<?= e($check_in) ?>"
                        required
                    >

                </div>



                <!-- CHECK OUT -->

                <div class="col-md-4">

                    <label for="check_out">

                        <i class="bi bi-calendar-x me-1"></i>

                        Check-out Date

                    </label>


                    <input
                        type="date"
                        id="check_out"
                        name="check_out"
                        class="form-control"
                        value="<?= e($check_out) ?>"
                        required
                    >

                </div>



                <!-- SEARCH -->

                <div class="col-md-2">

                    <button
                        type="submit"
                        class="search-btn"
                    >

                        <i class="bi bi-search me-1"></i>

                        Search

                    </button>

                </div>



                <!-- CLEAR -->

                <div class="col-md-2">

                    <a
                        href="rooms.php"
                        class="clear-btn"
                    >

                        <i class="bi bi-arrow-clockwise me-1"></i>

                        Clear

                    </a>

                </div>

            </div>

        </form>

    </div>

</div>



<!-- ==============================================================
     ROOMS SECTION
================================================================ -->

<section class="rooms-section">


    <!-- ERROR -->

    <?php if ($error): ?>

        <div class="alert alert-danger custom-alert">

            <i class="bi bi-exclamation-triangle me-2"></i>

            <?= e($error) ?>

        </div>

    <?php endif; ?>



    <!-- TITLE -->

    <div class="section-title">

        <?php if ($searched && !$error): ?>

            <h2>

                Available
                <span>Rooms</span>

            </h2>


            <p>

                Rooms available for your selected dates.

            </p>

        <?php else: ?>

            <h2>

                Our
                <span>Rooms</span>

            </h2>


            <p>

                Choose the perfect room for your stay.

            </p>

        <?php endif; ?>

    </div>



    <!-- ==========================================================
         ROOM GRID
    =========================================================== -->

    <?php if (count($rooms) > 0): ?>

        <div class="row g-4">


            <?php foreach ($rooms as $room): ?>


                <div class="col-lg-4 col-md-6">


                    <div class="room-card">


                        <!-- ROOM IMAGE -->

                        <div class="room-image">


                            <?php if (
                                !empty($room["image"])
                            ): ?>


                                <img
                                    src="../uploads/rooms/<?= e($room["image"]) ?>"
                                    alt="Room <?= e($room["room_number"]) ?>"
                                    loading="lazy"
                                >


                            <?php else: ?>


                                <div class="room-image-placeholder">

                                    <i class="bi bi-door-open"></i>

                                </div>


                            <?php endif; ?>


                        </div>



                        <!-- ROOM CONTENT -->

                        <div class="room-content">


                            <!-- ROOM NUMBER -->

                            <div class="room-number">

                                Room
                                <?= e($room["room_number"]) ?>

                            </div>



                            <!-- ROOM TYPE -->

                            <div class="room-type">

                                <?= e($room["type_name"]) ?>

                            </div>



                            <!-- DESCRIPTION -->

                            <div class="room-description">

                                <?php

                                if (
                                    !empty($room["description"])
                                ) {

                                    echo e(
                                        $room["description"]
                                    );

                                } else {

                                    echo "Enjoy a comfortable and relaxing stay at Velora Hotel.";

                                }

                                ?>

                            </div>



                            <!-- ROOM INFO -->

                            <div class="room-info">


                                <!-- CAPACITY -->

                                <div class="info-item">

                                    <i class="bi bi-people-fill"></i>

                                    <?= (int) $room["capacity"] ?>

                                    Guests

                                </div>



                                <!-- FLOOR -->

                                <div class="info-item">

                                    <i class="bi bi-building"></i>

                                    Floor
                                    <?= e($room["floor_number"]) ?>

                                </div>


                            </div>



                            <!-- PRICE -->

                            <div class="room-price">

                                Rs.
                                <?= number_format(
                                    (float) $room["price"],
                                    2
                                ) ?>


                                <small>

                                    / night

                                </small>

                            </div>



                            <!-- DETAILS -->

                            <a
                                href="room_details.php?id=<?= (int) $room["id"] ?>&check_in=<?= urlencode($check_in) ?>&check_out=<?= urlencode($check_out) ?>"
                                class="details-btn"
                            >

                                View Room Details

                                <i class="bi bi-arrow-right"></i>

                            </a>


                        </div>


                    </div>


                </div>


            <?php endforeach; ?>


        </div>


    <?php else: ?>


        <!-- ======================================================
             NO ROOMS
        ======================================================= -->

        <div class="no-rooms">


            <i class="bi bi-door-closed"></i>


            <h4>

                No Rooms Available

            </h4>


            <?php if ($searched): ?>

                <p>

                    No rooms are available for the selected dates.

                    Please try different dates.

                </p>


            <?php else: ?>

                <p>

                    There are currently no available rooms.

                </p>


            <?php endif; ?>


        </div>


    <?php endif; ?>


</section>



<!-- ==============================================================
     FOOTER
================================================================ -->

<footer>

    <div>

        © <?= date("Y") ?>

        <strong>Velora Hotel</strong>

        — Experience Comfort & Elegance

    </div>

</footer>



<!-- ==============================================================
     BOOTSTRAP JS
================================================================ -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>



<!-- ==============================================================
     DATE VALIDATION
================================================================ -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const checkIn =
            document.getElementById("check_in");

        const checkOut =
            document.getElementById("check_out");


        if (!checkIn || !checkOut) {
            return;
        }


        /*
        |--------------------------------------------------------------
        | TODAY
        |--------------------------------------------------------------
        */

        const today =
            new Date()
                .toISOString()
                .split("T")[0];


        checkIn.min = today;


        /*
        |--------------------------------------------------------------
        | CHECK-IN CHANGE
        |--------------------------------------------------------------
        */

        checkIn.addEventListener(
            "change",
            function () {

                checkOut.min =
                    checkIn.value;

                if (
                    checkOut.value &&
                    checkOut.value <= checkIn.value
                ) {

                    checkOut.value = "";

                }

            }
        );


        /*
        |--------------------------------------------------------------
        | FORM VALIDATION
        |--------------------------------------------------------------
        */

        const form =
            checkIn.closest("form");


        form.addEventListener(
            "submit",
            function (event) {

                if (
                    !checkIn.value ||
                    !checkOut.value
                ) {

                    return;

                }


                if (
                    checkOut.value <=
                    checkIn.value
                ) {

                    event.preventDefault();


                    alert(
                        "Check-out date must be after check-in date."
                    );

                }

            }
        );

    }
);

</script>


</body>

</html>