<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

startSession();
requireRole("customer");

$guests = "";
$budget = "";
$room_type = "";
$check_in = "";
$check_out = "";

$rooms = [];
$room_types = [];

$error = "";
$searched = false;


/* =====================================================
   GET ROOM TYPES
===================================================== */

$typeStmt = $pdo->query("
    SELECT
        id,
        type_name,
        base_price,
        capacity
    FROM room_types
    ORDER BY base_price ASC
");

$room_types = $typeStmt->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   ROOM RECOMMENDATION
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $searched = true;

    $guests = trim($_POST["guests"] ?? "");
    $budget = trim($_POST["budget"] ?? "");
    $room_type = trim($_POST["room_type"] ?? "");
    $check_in = trim($_POST["check_in"] ?? "");
    $check_out = trim($_POST["check_out"] ?? "");


    /* =================================================
       VALIDATION
    ================================================= */

    if ($guests === "" || !is_numeric($guests) || (int)$guests < 1) {

        $error = "Please enter a valid number of guests.";

    } elseif ($budget === "" || !is_numeric($budget) || (float)$budget <= 0) {

        $error = "Please enter a valid maximum budget.";

    } elseif ($check_in === "" || $check_out === "") {

        $error = "Please select both check-in and check-out dates.";

    } else {

        $today = date("Y-m-d");

        if ($check_in < $today) {

            $error = "Check-in date cannot be in the past.";

        } elseif ($check_out <= $check_in) {

            $error = "Check-out date must be after check-in date.";

        } else {

            $guests = (int)$guests;
            $budget = (float)$budget;


            /* =============================================
               BUILD QUERY
            ============================================= */

            $sql = "
                SELECT
                    r.id,
                    r.room_number,
                    r.floor_number,
                    r.status,
                    r.description,
                    r.image,
                    rt.type_name,
                    rt.base_price,
                    rt.capacity

                FROM rooms r

                INNER JOIN room_types rt
                    ON r.room_type_id = rt.id

                WHERE r.status = 'available'

                AND r.room_number <> '402'

                AND rt.capacity >= ?

                AND rt.base_price <= ?

                AND NOT EXISTS (
                    SELECT 1
                    FROM bookings b
                    WHERE b.room_id = r.id

                    AND b.booking_status IN (
                        'pending',
                        'confirmed',
                        'checked_in'
                    )

                    AND b.check_in < ?

                    AND b.check_out > ?
                )
            ";


            $params = [
                $guests,
                $budget,
                $check_out,
                $check_in
            ];


            /* =============================================
               FILTER BY ROOM TYPE
            ============================================= */

            if ($room_type !== "") {

                $sql .= "
                    AND rt.type_name = ?
                ";

                $params[] = $room_type;
            }


            /* =============================================
               ORDER RESULTS
            ============================================= */

            $sql .= "
                ORDER BY
                    rt.base_price ASC,
                    r.room_number ASC
            ";


            /* =============================================
               EXECUTE
            ============================================= */

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);


            if (!$rooms) {

                $error = "No suitable rooms were found for your requirements.";

            }
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

    <title>Smart Room Recommendation - Velora Hotel</title>


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
                Tahoma,
                Geneva,
                Verdana,
                sans-serif;

            background: #f6f5f1;

            color: #172033;
        }


        /* =================================================
           THEME
        ================================================= */

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

            --success: #198754;

            --danger: #dc3545;
        }


        /* =================================================
           TOP BAR
        ================================================= */

        .topbar {

            height: 72px;

            background: var(--navy);

            color: white;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 35px;

            border-bottom: 2px solid var(--gold);
        }


        .brand {

            display: flex;

            align-items: center;

            gap: 12px;

            font-size: 22px;

            font-weight: 700;

            letter-spacing: 1px;
        }


        .brand-icon {

            width: 42px;

            height: 42px;

            border-radius: 50%;

            background: var(--gold);

            color: var(--navy);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 21px;
        }


        .top-links {

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .top-links a {

            text-decoration: none;

            color: white;

            padding: 9px 15px;

            border-radius: 8px;

            font-size: 14px;

            transition: 0.3s;
        }


        .top-links a:hover {

            background: var(--navy-hover);

            color: var(--gold);
        }


        /* =================================================
           PAGE
        ================================================= */

        .page-container {

            max-width: 1250px;

            margin: 0 auto;

            padding: 40px 25px 60px;
        }


        /* =================================================
           PAGE HEADER
        ================================================= */

        .page-header {

            margin-bottom: 30px;
        }


        .page-header h1 {

            font-size: 32px;

            font-weight: 700;

            margin-bottom: 8px;

            color: var(--navy);
        }


        .page-header p {

            color: var(--muted);

            margin: 0;

            font-size: 15px;
        }


        .gold-line {

            width: 65px;

            height: 3px;

            background: var(--gold);

            margin-top: 15px;

            border-radius: 5px;
        }


        /* =================================================
           SEARCH CARD
        ================================================= */

        .search-card {

            background: var(--surface);

            border: 1px solid var(--border);

            border-radius: 18px;

            padding: 30px;

            box-shadow:
                0 10px 30px rgba(15, 23, 42, 0.07);

            margin-bottom: 35px;
        }


        .search-title {

            display: flex;

            align-items: center;

            gap: 12px;

            margin-bottom: 25px;
        }


        .search-title-icon {

            width: 45px;

            height: 45px;

            background: var(--gold-light);

            color: var(--gold-dark);

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 20px;
        }


        .search-title h3 {

            margin: 0;

            font-size: 20px;

            font-weight: 700;

            color: var(--navy);
        }


        .form-label {

            font-size: 13px;

            font-weight: 600;

            color: var(--text);

            margin-bottom: 8px;
        }


        .form-control,
        .form-select {

            height: 48px;

            border: 1px solid #d8dde5;

            border-radius: 9px;

            padding: 0 14px;

            font-size: 14px;

            color: #172033;

            background: white;
        }


        .form-control:focus,
        .form-select:focus {

            border-color: var(--gold);

            box-shadow:
                0 0 0 3px rgba(201, 162, 39, 0.15);
        }


        .recommend-btn {

            width: 100%;

            height: 48px;

            border: none;

            border-radius: 9px;

            background: var(--gold);

            color: var(--navy);

            font-weight: 700;

            transition: 0.3s;
        }


        .recommend-btn:hover {

            background: var(--gold-dark);

            color: white;

            transform: translateY(-1px);
        }


        /* =================================================
           ALERT
        ================================================= */

        .alert-custom {

            border: none;

            border-radius: 10px;

            padding: 14px 18px;

            margin-bottom: 25px;

            font-size: 14px;
        }


        /* =================================================
           RESULTS HEADER
        ================================================= */

        .results-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 20px;
        }


        .results-header h2 {

            font-size: 23px;

            font-weight: 700;

            margin: 0;

            color: var(--navy);
        }


        .result-count {

            background: var(--gold-light);

            color: var(--gold-dark);

            padding: 7px 13px;

            border-radius: 20px;

            font-size: 13px;

            font-weight: 700;
        }


        /* =================================================
           ROOM GRID
        ================================================= */

        .room-grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 25px;
        }


        /* =================================================
           ROOM CARD
        ================================================= */

        .room-card {

            background: white;

            border: 1px solid var(--border);

            border-radius: 16px;

            overflow: hidden;

            box-shadow:
                0 8px 25px rgba(15, 23, 42, 0.06);

            transition: 0.3s;
        }


        .room-card:hover {

            transform: translateY(-5px);

            box-shadow:
                0 15px 35px rgba(15, 23, 42, 0.12);
        }


        /* =================================================
           ROOM IMAGE
        ================================================= */

        .room-image {

            height: 210px;

            background: #e5e7eb;

            position: relative;

            overflow: hidden;
        }


        .room-image img {

            width: 100%;

            height: 100%;

            object-fit: cover;

            display: block;
        }


        .no-image {

            width: 100%;

            height: 100%;

            display: flex;

            align-items: center;

            justify-content: center;

            flex-direction: column;

            color: var(--muted);

            background: #eef0f2;
        }


        .no-image i {

            font-size: 42px;

            margin-bottom: 8px;

            color: #9ca3af;
        }


        .room-number {

            position: absolute;

            top: 14px;

            left: 14px;

            background: rgba(15, 23, 42, 0.92);

            color: white;

            padding: 7px 12px;

            border-radius: 7px;

            font-size: 12px;

            font-weight: 700;
        }


        /* =================================================
           ROOM BODY
        ================================================= */

        .room-body {

            padding: 22px;
        }


        .room-type {

            color: var(--gold-dark);

            font-size: 12px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.7px;

            margin-bottom: 6px;
        }


        .room-title {

            font-size: 21px;

            font-weight: 700;

            color: var(--navy);

            margin-bottom: 9px;
        }


        .room-description {

            color: var(--muted);

            font-size: 13px;

            line-height: 1.6;

            min-height: 42px;

            margin-bottom: 17px;
        }


        /* =================================================
           ROOM DETAILS
        ================================================= */

        .room-details {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 10px;

            padding: 14px 0;

            border-top: 1px solid var(--border);

            border-bottom: 1px solid var(--border);

            margin-bottom: 17px;
        }


        .detail-item {

            display: flex;

            align-items: center;

            gap: 7px;

            color: var(--muted);

            font-size: 12px;
        }


        .detail-item i {

            color: var(--gold-dark);

            font-size: 15px;
        }


        /* =================================================
           PRICE
        ================================================= */

        .room-bottom {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;
        }


        .price-label {

            color: var(--muted);

            font-size: 11px;

            margin-bottom: 2px;
        }


        .price {

            color: var(--navy);

            font-size: 21px;

            font-weight: 800;
        }


        .price small {

            color: var(--muted);

            font-size: 11px;

            font-weight: 400;
        }


        .view-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            text-decoration: none;

            background: var(--navy);

            color: white;

            padding: 10px 16px;

            border-radius: 8px;

            font-size: 13px;

            font-weight: 600;

            transition: 0.3s;

            white-space: nowrap;
        }


        .view-btn:hover {

            background: var(--gold);

            color: var(--navy);
        }


        /* =================================================
           EMPTY RESULT
        ================================================= */

        .empty-card {

            background: white;

            border: 1px solid var(--border);

            border-radius: 16px;

            padding: 55px 25px;

            text-align: center;

            box-shadow:
                0 8px 25px rgba(15, 23, 42, 0.05);
        }


        .empty-icon {

            width: 70px;

            height: 70px;

            margin: 0 auto 18px;

            border-radius: 50%;

            background: var(--gold-light);

            color: var(--gold-dark);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 30px;
        }


        .empty-card h3 {

            color: var(--navy);

            font-size: 20px;

            margin-bottom: 8px;
        }


        .empty-card p {

            color: var(--muted);

            font-size: 14px;

            margin: 0;
        }


        /* =================================================
           INFO BOX
        ================================================= */

        .info-box {

            background: var(--navy);

            color: white;

            border-radius: 16px;

            padding: 22px 25px;

            margin-top: 35px;

            display: flex;

            align-items: center;

            gap: 15px;
        }


        .info-icon {

            color: var(--gold);

            font-size: 25px;
        }


        .info-box strong {

            color: var(--gold);
        }


        .info-box p {

            margin: 3px 0 0;

            font-size: 13px;

            color: #cbd5e1;

            line-height: 1.5;
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 1000px) {

            .room-grid {

                grid-template-columns:
                    repeat(2, 1fr);
            }
        }


        @media (max-width: 768px) {

            .topbar {

                height: auto;

                min-height: 70px;

                padding: 15px 20px;

                flex-direction: column;

                align-items: flex-start;

                gap: 12px;
            }


            .top-links {

                width: 100%;

                flex-wrap: wrap;
            }


            .page-container {

                padding: 30px 18px 50px;
            }


            .search-card {

                padding: 22px;
            }


            .room-grid {

                grid-template-columns: 1fr;
            }


            .page-header h1 {

                font-size: 27px;
            }
        }


        @media (max-width: 500px) {

            .room-bottom {

                flex-direction: column;

                align-items: stretch;
            }


            .view-btn {

                width: 100%;
            }


            .results-header {

                align-items: flex-start;

                gap: 10px;

                flex-direction: column;
            }
        }

    </style>

</head>


<body>


<!-- =====================================================
     TOP BAR
===================================================== -->

<header class="topbar">

    <div class="brand">

        <div class="brand-icon">

            <i class="bi bi-buildings"></i>

        </div>

        Velora Hotel

    </div>


    <div class="top-links">

        <a href="dashboard.php">

            <i class="bi bi-grid me-1"></i>

            Dashboard

        </a>


        <a href="rooms.php">

            <i class="bi bi-door-open me-1"></i>

            Browse Rooms

        </a>


        <a href="bookings.php">

            <i class="bi bi-calendar-check me-1"></i>

            My Bookings

        </a>

    </div>

</header>



<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<main class="page-container">


    <!-- PAGE HEADER -->

    <div class="page-header">

        <h1>

            <i
                class="bi bi-stars"
                style="color:#c9a227;"
            ></i>

            Smart Room Recommendation

        </h1>

        <p>

            Tell us what you need and we'll find suitable rooms for your stay.

        </p>

        <div class="gold-line"></div>

    </div>



    <!-- =================================================
         SEARCH FORM
    ================================================= -->

    <div class="search-card">

        <div class="search-title">

            <div class="search-title-icon">

                <i class="bi bi-sliders"></i>

            </div>

            <div>

                <h3>

                    Find Your Perfect Room

                </h3>

            </div>

        </div>


        <form
            method="POST"
            action=""
        >

            <div class="row g-3">


                <!-- GUESTS -->

                <div class="col-lg-3 col-md-6">

                    <label
                        for="guests"
                        class="form-label"
                    >

                        Number of Guests

                    </label>


                    <input
                        type="number"
                        class="form-control"
                        id="guests"
                        name="guests"
                        min="1"
                        value="<?= e($guests); ?>"
                        placeholder="e.g. 2"
                        required
                    >

                </div>



                <!-- BUDGET -->

                <div class="col-lg-3 col-md-6">

                    <label
                        for="budget"
                        class="form-label"
                    >

                        Maximum Budget (LKR)

                    </label>


                    <input
                        type="number"
                        class="form-control"
                        id="budget"
                        name="budget"
                        min="1"
                        step="0.01"
                        value="<?= e($budget); ?>"
                        placeholder="e.g. 15000"
                        required
                    >

                </div>



                <!-- ROOM TYPE -->

                <div class="col-lg-3 col-md-6">

                    <label
                        for="room_type"
                        class="form-label"
                    >

                        Room Type

                    </label>


                    <select
                        class="form-select"
                        id="room_type"
                        name="room_type"
                    >

                        <option value="">

                            Any Room Type

                        </option>


                        <?php foreach ($room_types as $type): ?>

                            <option
                                value="<?= e($type["type_name"]); ?>"
                                <?= $room_type === $type["type_name"] ? "selected" : ""; ?>
                            >

                                <?= e($type["type_name"]); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>



                <!-- CHECK IN -->

                <div class="col-lg-3 col-md-6">

                    <label
                        for="check_in"
                        class="form-label"
                    >

                        Check-in Date

                    </label>


                    <input
                        type="date"
                        class="form-control"
                        id="check_in"
                        name="check_in"
                        value="<?= e($check_in); ?>"
                        min="<?= date("Y-m-d"); ?>"
                        required
                    >

                </div>



                <!-- CHECK OUT -->

                <div class="col-lg-3 col-md-6">

                    <label
                        for="check_out"
                        class="form-label"
                    >

                        Check-out Date

                    </label>


                    <input
                        type="date"
                        class="form-control"
                        id="check_out"
                        name="check_out"
                        value="<?= e($check_out); ?>"
                        min="<?= date("Y-m-d", strtotime("+1 day")); ?>"
                        required
                    >

                </div>



                <!-- BUTTON -->

                <div class="col-lg-3 col-md-6 d-flex align-items-end">

                    <button
                        type="submit"
                        class="recommend-btn"
                    >

                        <i class="bi bi-search me-2"></i>

                        Find Recommended Rooms

                    </button>

                </div>

            </div>

        </form>

    </div>



    <!-- =================================================
         ERROR
    ================================================= -->

    <?php if ($error !== ""): ?>

        <div
            class="alert alert-warning alert-custom"
            role="alert"
        >

            <i class="bi bi-info-circle me-2"></i>

            <?= e($error); ?>

        </div>

    <?php endif; ?>



    <!-- =================================================
         RESULTS
    ================================================= -->

    <?php if ($searched && !$error && count($rooms) > 0): ?>


        <div class="results-header">

            <h2>

                Recommended Rooms

            </h2>


            <span class="result-count">

                <?= count($rooms); ?>

                room<?= count($rooms) != 1 ? "s" : ""; ?>

                found

            </span>

        </div>



        <div class="room-grid">


            <?php foreach ($rooms as $room): ?>


                <?php

                /* ==========================================
                   ROOM IMAGE
                ========================================== */

                $imagePath = "";


                if (!empty($room["image"])) {

                    $imagePath =
                        "../uploads/rooms/" .
                        $room["image"];
                }


                /*
                 * If image field already contains a path,
                 * remove duplicate uploads/rooms path.
                 */

                if (
                    !empty($room["image"]) &&
                    (
                        str_contains(
                            $room["image"],
                            "/"
                        ) ||
                        str_contains(
                            $room["image"],
                            "\\"
                        )
                    )
                ) {

                    $imagePath = "../" . ltrim(
                        str_replace(
                            "\\",
                            "/",
                            $room["image"]
                        ),
                        "/"
                    );
                }

                ?>


                <div class="room-card">


                    <!-- ROOM IMAGE -->

                    <div class="room-image">


                        <?php if ($imagePath !== ""): ?>

                            <img
                                src="<?= e($imagePath); ?>"
                                alt="Room <?= e($room["room_number"]); ?>"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                            >


                            <div
                                class="no-image"
                                style="display:none;"
                            >

                                <i class="bi bi-image"></i>

                                <span>No Image</span>

                            </div>


                        <?php else: ?>


                            <div class="no-image">

                                <i class="bi bi-image"></i>

                                <span>No Image Available</span>

                            </div>


                        <?php endif; ?>


                        <div class="room-number">

                            Room
                            <?= e($room["room_number"]); ?>

                        </div>


                    </div>



                    <!-- ROOM BODY -->

                    <div class="room-body">


                        <div class="room-type">

                            <?= e($room["type_name"]); ?>

                        </div>


                        <div class="room-title">

                            Room
                            <?= e($room["room_number"]); ?>

                        </div>


                        <div class="room-description">

                            <?php

                            $description =
                                trim(
                                    $room["description"] ?? ""
                                );

                            if ($description === "") {

                                echo "A comfortable room designed for a relaxing stay.";

                            } else {

                                echo e($description);
                            }

                            ?>

                        </div>



                        <!-- DETAILS -->

                        <div class="room-details">


                            <div class="detail-item">

                                <i class="bi bi-people"></i>

                                <span>

                                    Up to
                                    <?= e($room["capacity"]); ?>
                                    guests

                                </span>

                            </div>


                            <div class="detail-item">

                                <i class="bi bi-layers"></i>

                                <span>

                                    Floor
                                    <?= e($room["floor_number"]); ?>

                                </span>

                            </div>


                            <div class="detail-item">

                                <i class="bi bi-calendar-check"></i>

                                <span>

                                    Available

                                </span>

                            </div>


                            <div class="detail-item">

                                <i class="bi bi-check-circle"></i>

                                <span>

                                    Recommended

                                </span>

                            </div>


                        </div>



                        <!-- PRICE + VIEW -->

                        <div class="room-bottom">


                            <div>

                                <div class="price-label">

                                    Starting from

                                </div>


                                <div class="price">

                                    LKR
                                    <?= number_format(
                                        (float)$room["base_price"],
                                        2
                                    ); ?>

                                    <small>/ night</small>

                                </div>

                            </div>



                            <!-- =================================================
                                 IMPORTANT CHANGED LINK
                            ================================================= -->

                            <a
                                href="room_details.php?id=<?= e($room["id"]); ?>&check_in=<?= urlencode($check_in); ?>&check_out=<?= urlencode($check_out); ?>"
                                class="view-btn"
                            >

                                <i class="bi bi-eye me-1"></i>

                                View Room

                            </a>


                        </div>


                    </div>

                </div>


            <?php endforeach; ?>


        </div>


    <?php elseif ($searched && $error === ""): ?>


        <!-- EMPTY RESULTS -->

        <div class="empty-card">

            <div class="empty-icon">

                <i class="bi bi-search"></i>

            </div>


            <h3>

                No Suitable Rooms Found

            </h3>


            <p>

                Try increasing your budget, reducing the number of guests,
                or selecting different dates.

            </p>

        </div>


    <?php endif; ?>



    <!-- =================================================
         INFORMATION BOX
    ================================================= -->

    <div class="info-box">

        <div class="info-icon">

            <i class="bi bi-lightbulb"></i>

        </div>


        <div>

            <strong>

                How Smart Recommendation Works

            </strong>


            <p>

                We check your number of guests, budget, preferred room type,
                selected dates, room capacity and current booking availability
                to find rooms that match your requirements.

            </p>

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


    if (checkIn && checkOut) {

        checkIn.addEventListener(
            "change",
            function () {

                if (this.value) {

                    const date =
                        new Date(this.value);

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


                    const minimumDate =
                        `${year}-${month}-${day}`;


                    checkOut.min =
                        minimumDate;


                    if (
                        checkOut.value &&
                        checkOut.value <= this.value
                    ) {

                        checkOut.value =
                            minimumDate;
                    }

                }

            }
        );

    }

</script>


</body>

</html>