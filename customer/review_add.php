<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

startSession();
requireRole("customer");

$customer_id = $_SESSION["user_id"];

$booking_id = isset($_GET["booking_id"])
    ? (int) $_GET["booking_id"]
    : (int) ($_POST["booking_id"] ?? 0);

$error = "";


/* =====================================================
   CHECK BOOKING
===================================================== */

$stmt = $pdo->prepare("
    SELECT
        b.id,
        b.booking_code,
        b.booking_status,
        b.check_in,
        b.check_out,
        b.room_id,
        r.room_number,
        rt.type_name
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
   BOOKING VALIDATION
===================================================== */

if (!$booking) {

    die("Booking not found.");

}


if ($booking["booking_status"] !== "checked_out") {

    die("You can only review a completed booking.");

}


/* =====================================================
   CHECK EXISTING REVIEW
===================================================== */

$stmt = $pdo->prepare("
    SELECT id
    FROM reviews
    WHERE booking_id = ?
    AND customer_id = ?
    LIMIT 1
");

$stmt->execute([
    $booking_id,
    $customer_id
]);

$existingReview = $stmt->fetch(PDO::FETCH_ASSOC);


if ($existingReview) {

    header(
        "Location: reviews.php?already_reviewed=1"
    );

    exit;

}


/* =====================================================
   SUBMIT REVIEW
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $rating = (int) ($_POST["rating"] ?? 0);

    $comment = trim(
        $_POST["comment"] ?? ""
    );


    /* ---------------------------------------------
       VALIDATE RATING
    --------------------------------------------- */

    if ($rating < 1 || $rating > 5) {

        $error = "Please select a rating between 1 and 5 stars.";

    }


    /* ---------------------------------------------
       VALIDATE COMMENT
    --------------------------------------------- */

    elseif (strlen($comment) > 1000) {

        $error = "Your review must be less than 1000 characters.";

    }


    /* ---------------------------------------------
       INSERT REVIEW
    --------------------------------------------- */

    else {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO reviews
                (
                    booking_id,
                    customer_id,
                    room_id,
                    rating,
                    comment
                )
                VALUES
                (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $booking_id,
                $customer_id,
                $booking["room_id"],
                $rating,
                $comment !== ""
                    ? $comment
                    : null
            ]);


            header(
                "Location: reviews.php?success=1"
            );

            exit;

        }

        catch (PDOException $e) {

            $error = "Unable to submit your review. Please try again.";

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

    <title>Leave a Review | Velora Hotel</title>


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

        body {

            background:
                #f8fafc;

        }


        .review-wrapper {

            max-width: 750px;

            margin:
                45px auto;

            padding:
                0 20px 50px;

        }


        .review-card {

            background:
                #ffffff;

            border-radius:
                18px;

            border:
                1px solid #e5e7eb;

            box-shadow:
                0 10px 30px rgba(0,0,0,.07);

            overflow:
                hidden;

        }


        /* ================================================
           HEADER
        ================================================ */

        .review-header {

            background:
                #0f172a;

            padding:
                28px;

            color:
                white;

        }


        .review-header h1 {

            margin: 0;

            font-size: 25px;

            font-weight: 750;

        }


        .review-header p {

            margin:
                7px 0 0;

            color:
                rgba(255,255,255,.70);

            font-size: 14px;

        }


        /* ================================================
           BOOKING INFO
        ================================================ */

        .booking-info {

            padding:
                22px 28px;

            background:
                #fffbeb;

            border-bottom:
                1px solid #f3e8b3;

        }


        .room-title {

            font-size:
                20px;

            font-weight:
                750;

            color:
                #0f172a;

        }


        .booking-code {

            color:
                #a47d18;

            font-size:
                13px;

            margin-top:
                4px;

        }


        /* ================================================
           FORM
        ================================================ */

        .review-form {

            padding:
                30px;

        }


        .form-label {

            font-weight:
                700;

            color:
                #0f172a;

            margin-bottom:
                9px;

        }


        /* ================================================
           STAR RATING
        ================================================ */

        .rating-area {

            margin-bottom:
                28px;

        }


        .stars {

            display:
                flex;

            flex-direction:
                row;

            gap:
                7px;

            margin-top:
                8px;

        }


        .stars input {

            display:
                none;

        }


        .stars label {

            font-size:
                42px;

            color:
                #d1d5db;

            cursor:
                pointer;

            transition:
                .15s;

            line-height:
                1;

        }


        .stars label:hover,
        .stars label.hovered {

            color:
                #d4af37;

            transform:
                scale(1.08);

        }


        .stars label.selected {

            color:
                #d4af37;

        }


        .rating-text {

            margin-top:
                10px;

            font-size:
                13px;

            color:
                #64748b;

        }


        /* ================================================
           TEXTAREA
        ================================================ */

        textarea {

            width:
                100%;

            min-height:
                150px;

            resize:
                vertical;

            border:
                1px solid #d1d5db;

            border-radius:
                10px;

            padding:
                13px 14px;

            font-size:
                14px;

            outline:
                none;

        }


        textarea:focus {

            border-color:
                #d4af37;

            box-shadow:
                0 0 0 3px
                rgba(212,175,55,.12);

        }


        .character-count {

            text-align:
                right;

            color:
                #64748b;

            font-size:
                12px;

            margin-top:
                5px;

        }


        /* ================================================
           BUTTONS
        ================================================ */

        .form-actions {

            display:
                flex;

            gap:
                10px;

            margin-top:
                25px;

        }


        .btn-submit {

            flex:
                1;

            border:
                none;

            border-radius:
                9px;

            padding:
                12px 20px;

            background:
                #d4af37;

            color:
                #0f172a;

            font-weight:
                750;

            transition:
                .2s;

        }


        .btn-submit:hover {

            background:
                #c39d28;

            transform:
                translateY(-1px);

        }


        .btn-back {

            border:
                1px solid #d1d5db;

            border-radius:
                9px;

            padding:
                12px 20px;

            color:
                #0f172a;

            background:
                #ffffff;

            text-decoration:
                none;

            font-weight:
                650;

            text-align:
                center;

        }


        .btn-back:hover {

            background:
                #f3f4f6;

            color:
                #0f172a;

        }


        /* ================================================
           ALERT
        ================================================ */

        .error-alert {

            margin:
                20px 30px 0;

            padding:
                12px 15px;

            border-radius:
                9px;

            color:
                #991b1b;

            background:
                #fef2f2;

            border:
                1px solid #fecaca;

            font-size:
                14px;

        }


        /* ================================================
           MOBILE
        ================================================ */

        @media (max-width: 600px) {

            .review-wrapper {

                margin-top:
                    25px;

                padding:
                    0 12px 40px;

            }


            .review-header {

                padding:
                    23px;

            }


            .review-form {

                padding:
                    23px;

            }


            .booking-info {

                padding:
                    20px 23px;

            }


            .stars label {

                font-size:
                    35px;

            }


            .form-actions {

                flex-direction:
                    column;

            }

        }

    </style>

</head>


<body>


<div class="review-wrapper">


    <!-- ================================================
         CARD
    ================================================ -->

    <div class="review-card">


        <!-- HEADER -->

        <div class="review-header">

            <h1>

                <i class="bi bi-star-fill me-2"></i>

                Leave a Review

            </h1>


            <p>

                Share your experience at Velora Hotel.

            </p>

        </div>


        <!-- ============================================
             BOOKING INFORMATION
        ============================================ -->

        <div class="booking-info">


            <div class="room-title">

                Room
                <?= e($booking["room_number"]) ?>

                ·

                <?= e($booking["type_name"]) ?>

            </div>


            <div class="booking-code">

                <i class="bi bi-receipt me-1"></i>

                Booking #

                <?= e($booking["booking_code"]) ?>

                &nbsp; • &nbsp;

                <?= e(
                    date(
                        "d M Y",
                        strtotime($booking["check_in"])
                    )
                ) ?>

                -

                <?= e(
                    date(
                        "d M Y",
                        strtotime($booking["check_out"])
                    )
                ) ?>

            </div>

        </div>


        <!-- ERROR -->

        <?php if ($error !== ""): ?>

            <div class="error-alert">

                <i class="bi bi-exclamation-circle me-1"></i>

                <?= e($error) ?>

            </div>

        <?php endif; ?>


        <!-- ============================================
             FORM
        ============================================ -->

        <form
            method="POST"
            class="review-form"
        >


            <input
                type="hidden"
                name="booking_id"
                value="<?= (int)$booking_id ?>"
            >


            <!-- ========================================
                 RATING
            ======================================== -->

            <div class="rating-area">


                <label class="form-label">

                    How would you rate your stay?

                </label>


                <div class="stars">


                    <!-- 1 STAR -->

                    <input
                        type="radio"
                        id="star1"
                        name="rating"
                        value="1"
                    >

                    <label
                        for="star1"
                        data-rating="1"
                    >

                        ★

                    </label>


                    <!-- 2 STARS -->

                    <input
                        type="radio"
                        id="star2"
                        name="rating"
                        value="2"
                    >

                    <label
                        for="star2"
                        data-rating="2"
                    >

                        ★

                    </label>


                    <!-- 3 STARS -->

                    <input
                        type="radio"
                        id="star3"
                        name="rating"
                        value="3"
                    >

                    <label
                        for="star3"
                        data-rating="3"
                    >

                        ★

                    </label>


                    <!-- 4 STARS -->

                    <input
                        type="radio"
                        id="star4"
                        name="rating"
                        value="4"
                    >

                    <label
                        for="star4"
                        data-rating="4"
                    >

                        ★

                    </label>


                    <!-- 5 STARS -->

                    <input
                        type="radio"
                        id="star5"
                        name="rating"
                        value="5"
                    >

                    <label
                        for="star5"
                        data-rating="5"
                    >

                        ★

                    </label>


                </div>


                <div
                    class="rating-text"
                    id="ratingText"
                >

                    Select a rating

                </div>

            </div>


            <!-- ========================================
                 COMMENT
            ======================================== -->

            <div>


                <label
                    for="comment"
                    class="form-label"
                >

                    Your Review

                </label>


                <textarea
                    id="comment"
                    name="comment"
                    maxlength="1000"
                    placeholder="Tell us about your stay..."
                ><?= e($_POST["comment"] ?? "") ?></textarea>


                <div class="character-count">

                    <span id="characterCount">0</span>
                    / 1000

                </div>

            </div>


            <!-- ========================================
                 BUTTONS
            ======================================== -->

            <div class="form-actions">


                <a
                    href="bookings.php"
                    class="btn-back"
                >

                    <i class="bi bi-arrow-left me-1"></i>

                    Back

                </a>


                <button
                    type="submit"
                    class="btn-submit"
                >

                    <i class="bi bi-send me-1"></i>

                    Submit Review

                </button>

            </div>


        </form>

    </div>

</div>


<script>


/* =====================================================
   STAR RATING
===================================================== */

const starLabels =
    document.querySelectorAll(
        ".stars label"
    );

const ratingInputs =
    document.querySelectorAll(
        ".stars input"
    );

const ratingText =
    document.getElementById(
        "ratingText"
    );


const ratingMessages = {

    1: "Poor",

    2: "Fair",

    3: "Good",

    4: "Very Good",

    5: "Excellent"

};


/* =====================================================
   UPDATE STARS
===================================================== */

function updateStars(value) {

    starLabels.forEach(function(label) {

        const rating =
            parseInt(
                label.dataset.rating
            );

        if (rating <= value) {

            label.classList.add(
                "selected"
            );

        } else {

            label.classList.remove(
                "selected"
            );

        }

    });


    if (value > 0) {

        ratingText.textContent =
            value +
            " Star" +
            (value > 1 ? "s" : "") +
            " - " +
            ratingMessages[value];

    }

}


/* =====================================================
   CLICK STAR
===================================================== */

ratingInputs.forEach(function(input) {

    input.addEventListener(
        "change",
        function() {

            updateStars(
                parseInt(
                    this.value
                )
            );

        }
    );

});


/* =====================================================
   HOVER STAR
===================================================== */

starLabels.forEach(function(label) {

    label.addEventListener(
        "mouseenter",
        function() {

            const value =
                parseInt(
                    this.dataset.rating
                );


            starLabels.forEach(
                function(star) {

                    const rating =
                        parseInt(
                            star.dataset.rating
                        );

                    if (rating <= value) {

                        star.classList.add(
                            "hovered"
                        );

                    } else {

                        star.classList.remove(
                            "hovered"
                        );

                    }

                }
            );

        }
    );


    label.addEventListener(
        "mouseleave",
        function() {

            starLabels.forEach(
                function(star) {

                    star.classList.remove(
                        "hovered"
                    );

                }
            );

        }
    );

});


/* =====================================================
   CHARACTER COUNTER
===================================================== */

const comment =
    document.getElementById(
        "comment"
    );

const characterCount =
    document.getElementById(
        "characterCount"
    );


function updateCharacterCount() {

    characterCount.textContent =
        comment.value.length;

}


comment.addEventListener(
    "input",
    updateCharacterCount
);


updateCharacterCount();


</script>


</body>

</html>