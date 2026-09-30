<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

startSession();
requireRole("customer");

$customer_id = $_SESSION["user_id"];


/* =====================================================
   GET CUSTOMER REVIEWS
===================================================== */

$stmt = $pdo->prepare("
    SELECT
        rv.id,
        rv.booking_id,
        rv.rating,
        rv.comment,
        rv.created_at,

        b.booking_code,
        b.check_in,
        b.check_out,

        r.room_number,

        rt.type_name

    FROM reviews rv

    INNER JOIN bookings b
        ON rv.booking_id = b.id

    INNER JOIN rooms r
        ON b.room_id = r.id

    INNER JOIN room_types rt
        ON r.room_type_id = rt.id

    WHERE rv.customer_id = ?

    ORDER BY rv.created_at DESC
");

$stmt->execute([
    $customer_id
]);

$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   CALCULATE SUMMARY
===================================================== */

$totalReviews = count($reviews);

$totalRating = 0;

foreach ($reviews as $review) {

    $totalRating += (int)$review["rating"];

}

$averageRating = $totalReviews > 0
    ? $totalRating / $totalReviews
    : 0;


/* =====================================================
   STAR DISPLAY
===================================================== */

function displayStars($rating)
{
    $html = "";

    for ($i = 1; $i <= 5; $i++) {

        if ($i <= $rating) {

            $html .=
                '<i class="bi bi-star-fill"></i>';

        } else {

            $html .=
                '<i class="bi bi-star"></i>';

        }

    }

    return $html;
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

    <title>My Reviews | Velora Hotel</title>


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
           GENERAL
        ================================================= */

        * {

            box-sizing: border-box;

        }


        body {

            margin: 0;

            background:
                #f8fafc;

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
                #0f172a;

            border-bottom:
                1px solid
                rgba(255,255,255,.08);

        }


        .brand {

            display: flex;

            align-items: center;

            gap: 12px;

            text-decoration: none;

        }


        .brand-icon {

            width: 42px;

            height: 42px;

            border-radius: 11px;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                #d4af37;

            color:
                #0f172a;

            font-size: 20px;

        }


        .brand-title {

            color:
                #d4af37;

            font-size: 20px;

            font-weight: 750;

        }


        .brand-subtitle {

            display: block;

            color:
                rgba(255,255,255,.60);

            font-size: 11px;

        }


        /* =================================================
           PAGE
        ================================================= */

        .page-container {

            max-width: 1100px;

            margin: auto;

            padding:
                40px 20px 60px;

        }


        .page-heading {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 28px;

        }


        .page-heading h1 {

            margin: 0 0 5px;

            font-size: 28px;

            font-weight: 750;

            color:
                #0f172a;

        }


        .page-heading p {

            margin: 0;

            color:
                #64748b;

            font-size: 14px;

        }


        /* =================================================
           BUTTON
        ================================================= */

        .main-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            padding:
                10px 17px;

            border-radius: 8px;

            background:
                #d4af37;

            color:
                #0f172a;

            text-decoration: none;

            font-size: 13px;

            font-weight: 750;

            transition: .2s;

        }


        .main-btn:hover {

            color:
                #0f172a;

            background:
                #c5a12f;

            transform:
                translateY(-1px);

        }


        /* =================================================
           SUCCESS MESSAGE
        ================================================= */

        .success-alert {

            display: flex;

            align-items: center;

            gap: 9px;

            padding:
                13px 16px;

            margin-bottom:
                22px;

            border-radius:
                10px;

            background:
                #f0fdf4;

            border:
                1px solid #bbf7d0;

            color:
                #166534;

            font-size:
                14px;

        }


        /* =================================================
           SUMMARY CARD
        ================================================= */

        .summary-card {

            background:
                #ffffff;

            border:
                1px solid #e5e7eb;

            border-radius:
                16px;

            padding:
                22px;

            margin-bottom:
                28px;

            box-shadow:
                0 7px 22px
                rgba(0,0,0,.05);

        }


        .summary-icon {

            width:
                52px;

            height:
                52px;

            border-radius:
                12px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            background:
                rgba(212,175,55,.13);

            color:
                #b8952e;

            font-size:
                23px;

        }


        .summary-number {

            margin-top:
                10px;

            font-size:
                24px;

            font-weight:
                800;

            color:
                #0f172a;

        }


        .summary-label {

            color:
                #64748b;

            font-size:
                13px;

        }


        .average-rating {

            display:
                flex;

            align-items:
                center;

            gap:
                8px;

            color:
                #d4af37;

            font-size:
                21px;

        }


        .average-number {

            color:
                #0f172a;

            font-size:
                22px;

            font-weight:
                800;

        }


        /* =================================================
           REVIEW CARD
        ================================================= */

        .review-card {

            background:
                #ffffff;

            border:
                1px solid #e5e7eb;

            border-radius:
                16px;

            margin-bottom:
                20px;

            overflow:
                hidden;

            box-shadow:
                0 7px 22px
                rgba(0,0,0,.05);

            transition:
                .2s;

        }


        .review-card:hover {

            transform:
                translateY(-2px);

            box-shadow:
                0 10px 28px
                rgba(0,0,0,.08);

        }


        /* =================================================
           REVIEW TOP
        ================================================= */

        .review-top {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                15px;

            padding:
                20px 22px;

            border-bottom:
                1px solid #e5e7eb;

        }


        .room-info {

            display:
                flex;

            align-items:
                center;

            gap:
                13px;

        }


        .room-icon {

            width:
                50px;

            height:
                50px;

            flex-shrink:
                0;

            border-radius:
                11px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            background:
                rgba(212,175,55,.12);

            color:
                #b8952e;

            font-size:
                22px;

        }


        .room-number {

            font-size:
                17px;

            font-weight:
                750;

            color:
                #0f172a;

        }


        .room-type {

            margin-top:
                3px;

            color:
                #64748b;

            font-size:
                13px;

        }


        /* =================================================
           STARS
        ================================================= */

        .review-rating {

            text-align:
                right;

        }


        .stars {

            color:
                #d4af37;

            font-size:
                18px;

            letter-spacing:
                1px;

        }


        .rating-number {

            display:
                block;

            margin-top:
                3px;

            color:
                #64748b;

            font-size:
                12px;

        }


        /* =================================================
           REVIEW BODY
        ================================================= */

        .review-body {

            padding:
                22px;

        }


        .comment {

            margin:
                0;

            color:
                #334155;

            font-size:
                14px;

            line-height:
                1.7;

            white-space:
                pre-wrap;

        }


        .no-comment {

            color:
                #94a3b8;

            font-style:
                italic;

            font-size:
                13px;

        }


        /* =================================================
           REVIEW FOOTER
        ================================================= */

        .review-footer {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                15px;

            margin-top:
                18px;

            padding-top:
                15px;

            border-top:
                1px solid #f1f5f9;

            color:
                #64748b;

            font-size:
                12px;

        }


        .booking-reference {

            color:
                #a47d18;

            font-weight:
                650;

        }


        /* =================================================
           EMPTY STATE
        ================================================= */

        .empty-state {

            background:
                #ffffff;

            border:
                1px solid #e5e7eb;

            border-radius:
                17px;

            padding:
                65px 25px;

            text-align:
                center;

            box-shadow:
                0 7px 22px
                rgba(0,0,0,.05);

        }


        .empty-icon {

            width:
                78px;

            height:
                78px;

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
                rgba(212,175,55,.12);

            color:
                #b8952e;

            font-size:
                32px;

        }


        .empty-state h3 {

            margin-bottom:
                8px;

            font-size:
                21px;

            font-weight:
                750;

            color:
                #0f172a;

        }


        .empty-state p {

            max-width:
                460px;

            margin:
                0 auto 22px;

            color:
                #64748b;

            font-size:
                14px;

            line-height:
                1.6;

        }


        /* =================================================
           MOBILE
        ================================================= */

        @media (max-width: 700px) {

            .page-container {

                padding:
                    25px 15px 45px;

            }


            .page-heading {

                align-items:
                    flex-start;

                flex-direction:
                    column;

            }


            .page-heading h1 {

                font-size:
                    24px;

            }


            .review-top {

                align-items:
                    flex-start;

                flex-direction:
                    column;

            }


            .review-rating {

                text-align:
                    left;

            }


            .review-footer {

                align-items:
                    flex-start;

                flex-direction:
                    column;

            }


            .brand-subtitle {

                display:
                    none;

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

                My Reviews

            </h1>


            <p>

                View the reviews and ratings you have submitted.

            </p>

        </div>


        <a
            href="bookings.php"
            class="main-btn"
        >

            <i class="bi bi-calendar-check"></i>

            My Bookings

        </a>

    </div>


    <!-- =================================================
         SUCCESS MESSAGE
    ================================================= -->

    <?php if (
        isset($_GET["success"]) &&
        $_GET["success"] == "1"
    ): ?>

        <div class="success-alert">

            <i class="bi bi-check-circle-fill"></i>

            Your review was submitted successfully. Thank you
            for sharing your experience!

        </div>

    <?php endif; ?>


    <!-- =================================================
         ALREADY REVIEWED MESSAGE
    ================================================= -->

    <?php if (
        isset($_GET["already_reviewed"]) &&
        $_GET["already_reviewed"] == "1"
    ): ?>

        <div class="success-alert">

            <i class="bi bi-info-circle-fill"></i>

            You have already submitted a review for this booking.

        </div>

    <?php endif; ?>


    <?php if (!empty($reviews)): ?>


        <!-- =================================================
             SUMMARY
        ================================================= -->

        <div class="summary-card">

            <div class="row g-4 align-items-center">


                <!-- TOTAL REVIEWS -->

                <div class="col-md-6">

                    <div class="d-flex align-items-center gap-3">

                        <div class="summary-icon">

                            <i class="bi bi-chat-square-text-fill"></i>

                        </div>


                        <div>

                            <div class="summary-number">

                                <?= $totalReviews ?>

                            </div>


                            <div class="summary-label">

                                Review<?= $totalReviews != 1 ? "s" : "" ?> Submitted

                            </div>

                        </div>

                    </div>

                </div>


                <!-- AVERAGE -->

                <div class="col-md-6">

                    <div class="d-flex align-items-center gap-3">

                        <div class="summary-icon">

                            <i class="bi bi-star-fill"></i>

                        </div>


                        <div>

                            <div class="average-rating">

                                <span class="average-number">

                                    <?= number_format(
                                        $averageRating,
                                        1
                                    ) ?>

                                </span>


                                <span>

                                    <?= displayStars(
                                        round($averageRating)
                                    ) ?>

                                </span>

                            </div>


                            <div class="summary-label">

                                Your Average Rating

                            </div>

                        </div>

                    </div>

                </div>


            </div>

        </div>


        <!-- =================================================
             REVIEW LIST
        ================================================= -->

        <?php foreach ($reviews as $review): ?>


            <div class="review-card">


                <!-- REVIEW TOP -->

                <div class="review-top">


                    <!-- ROOM -->

                    <div class="room-info">


                        <div class="room-icon">

                            <i class="bi bi-door-open"></i>

                        </div>


                        <div>

                            <div class="room-number">

                                Room
                                <?= e(
                                    $review["room_number"]
                                ) ?>

                            </div>


                            <div class="room-type">

                                <i class="bi bi-stars me-1"></i>

                                <?= e(
                                    $review["type_name"]
                                ) ?>

                            </div>

                        </div>


                    </div>


                    <!-- RATING -->

                    <div class="review-rating">


                        <div class="stars">

                            <?= displayStars(
                                (int)$review["rating"]
                            ) ?>

                        </div>


                        <span class="rating-number">

                            <?= (int)$review["rating"] ?>
                            / 5

                        </span>


                    </div>


                </div>


                <!-- REVIEW BODY -->

                <div class="review-body">


                    <?php if (
                        trim(
                            $review["comment"] ?? ""
                        ) !== ""
                    ): ?>


                        <p class="comment">

                            “<?= e(
                                $review["comment"]
                            ) ?>”

                        </p>


                    <?php else: ?>


                        <div class="no-comment">

                            No written comment was provided.

                        </div>


                    <?php endif; ?>


                    <!-- FOOTER -->

                    <div class="review-footer">


                        <div>

                            <i class="bi bi-receipt me-1"></i>

                            Booking

                            <span class="booking-reference">

                                #<?= e(
                                    $review["booking_code"]
                                ) ?>

                            </span>

                        </div>


                        <div>

                            <i class="bi bi-calendar3 me-1"></i>

                            <?= e(
                                date(
                                    "d M Y",
                                    strtotime(
                                        $review["created_at"]
                                    )
                                )
                            ) ?>

                        </div>


                    </div>


                </div>


            </div>


        <?php endforeach; ?>


    <?php else: ?>


        <!-- =================================================
             EMPTY STATE
        ================================================= -->

        <div class="empty-state">


            <div class="empty-icon">

                <i class="bi bi-star"></i>

            </div>


            <h3>

                No Reviews Yet

            </h3>


            <p>

                After you complete a hotel stay, you can rate
                your experience and leave a review here.

            </p>


            <a
                href="bookings.php"
                class="main-btn"
            >

                <i class="bi bi-calendar-check"></i>

                View My Bookings

            </a>


        </div>


    <?php endif; ?>


</main>


</body>

</html>