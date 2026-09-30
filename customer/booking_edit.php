<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

startSession();
requireRole("customer");

$customer_id = $_SESSION["user_id"];
$booking_id = $_GET["id"] ?? "";

if (!is_numeric($booking_id)) {
    header("Location: bookings.php");
    exit;
}

/* =====================================================
   GET BOOKING
===================================================== */

$stmt = $pdo->prepare("
    SELECT
        b.id,
        b.booking_code,
        b.room_id,
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
        r.floor_number,

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
    header("Location: bookings.php");
    exit;
}


/* =====================================================
   ONLY PENDING / CONFIRMED CAN BE EDITED
===================================================== */

if (!in_array(
    strtolower($booking["booking_status"]),
    ["pending", "confirmed"]
)) {
    header(
        "Location: booking_view.php?id=" .
        (int)$booking_id
    );
    exit;
}


/* =====================================================
   VARIABLES
===================================================== */

$error = "";

$check_in = $booking["check_in"];
$check_out = $booking["check_out"];
$adults = (int)$booking["adults"];
$children = (int)$booking["children"];
$special_request = $booking["special_request"] ?? "";


/* =====================================================
   UPDATE BOOKING
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $check_in = trim($_POST["check_in"] ?? "");
    $check_out = trim($_POST["check_out"] ?? "");

    $adults = (int)($_POST["adults"] ?? 1);
    $children = (int)($_POST["children"] ?? 0);

    $special_request = trim(
        $_POST["special_request"] ?? ""
    );


    /* =================================================
       VALIDATE DATES
    ================================================= */

    if ($check_in === "" || $check_out === "") {

        $error = "Please select both check-in and check-out dates.";

    } elseif ($check_out <= $check_in) {

        $error = "Check-out date must be after check-in date.";

    }


    /* =================================================
       VALIDATE GUESTS
    ================================================= */

    if ($error === "") {

        if ($adults < 1) {

            $error = "At least one adult is required.";

        } elseif ($children < 0) {

            $error = "Children cannot be negative.";

        } elseif (
            ($adults + $children) >
            (int)$booking["capacity"]
        ) {

            $error =
                "This room can accommodate a maximum of " .
                (int)$booking["capacity"] .
                " guests.";

        }

    }


    /* =================================================
       CALCULATE NIGHTS
    ================================================= */

    if ($error === "") {

        try {

            $checkInDate = new DateTime($check_in);
            $checkOutDate = new DateTime($check_out);

            $interval = $checkInDate->diff(
                $checkOutDate
            );

            $number_of_nights = $interval->days;

            if ($number_of_nights < 1) {

                $error =
                    "The booking must be at least one night.";

            }

        } catch (Exception $e) {

            $error = "Invalid date selected.";

        }

    }


    /* =================================================
       CHECK ROOM AVAILABILITY
    ================================================= */

    if ($error === "") {

        $availabilityStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM bookings
            WHERE room_id = ?

            AND id <> ?

            AND booking_status IN (
                'pending',
                'confirmed',
                'checked_in'
            )

            AND check_in < ?
            AND check_out > ?
        ");

        $availabilityStmt->execute([
            $booking["room_id"],
            $booking_id,
            $check_out,
            $check_in
        ]);

        $existingBookings =
            (int)$availabilityStmt->fetchColumn();


        if ($existingBookings > 0) {

            $error =
                "This room is not available for the selected dates. " .
                "Please choose different dates.";

        }

    }


    /* =================================================
       CALCULATE TOTAL
    ================================================= */

    if ($error === "") {

        $room_price = (float)$booking["room_price"];

        $total_amount =
            $room_price * $number_of_nights;

    }


    /* =================================================
       UPDATE DATABASE
    ================================================= */

    if ($error === "") {

        try {

            $pdo->beginTransaction();

            $updateStmt = $pdo->prepare("
                UPDATE bookings

                SET
                    check_in = ?,
                    check_out = ?,
                    adults = ?,
                    children = ?,
                    number_of_nights = ?,
                    total_amount = ?,
                    special_request = ?

                WHERE id = ?
                AND customer_id = ?

                AND booking_status IN (
                    'pending',
                    'confirmed'
                )
            ");

            $updateStmt->execute([

                $check_in,
                $check_out,
                $adults,
                $children,
                $number_of_nights,
                $total_amount,
                $special_request !== ""
                    ? $special_request
                    : null,

                $booking_id,
                $customer_id

            ]);

            $pdo->commit();


            /* =========================================
               SUCCESS
            ========================================= */

            header(
                "Location: booking_view.php?id=" .
                (int)$booking_id .
                "&updated=1"
            );

            exit;


        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error =
                "Unable to update the booking. " .
                "Please try again.";

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
        Edit Booking - Velora Hotel
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <!-- Velora CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/velora.css"
    >


    <style>

        body {
            background: #f8fafc;
        }


        /* =================================================
           NAVBAR
        ================================================= */

        .velora-navbar {
            background: #0f172a;
            box-shadow:
                0 4px 20px rgba(15, 23, 42, 0.15);
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
           PAGE
        ================================================= */

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
           MAIN CARD
        ================================================= */

        .edit-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            box-shadow:
                0 10px 35px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }


        /* =================================================
           HEADER
        ================================================= */

        .edit-header {
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


        .edit-header h3 {
            margin: 5px 0 0;
            font-weight: 700;
        }


        /* =================================================
           BODY
        ================================================= */

        .edit-body {
            padding: 30px;
        }


        /* =================================================
           ROOM INFORMATION
        ================================================= */

        .room-summary {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 30px;
        }


        .room-type {
            color: #d4af37;
            font-weight: 600;
        }


        .room-number {
            color: #0f172a;
            font-size: 21px;
            font-weight: 700;
        }


        .room-details {
            color: #64748b;
            margin-top: 5px;
        }


        .room-price {
            color: #d4af37;
            font-size: 20px;
            font-weight: 700;
        }


        /* =================================================
           FORM
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


        .form-label {
            color: #0f172a;
            font-weight: 600;
        }


        .form-control,
        .form-select {
            border: 1px solid #d1d5db;
            border-radius: 9px;
            padding: 11px 13px;
        }


        .form-control:focus,
        .form-select:focus {
            border-color: #d4af37;
            box-shadow:
                0 0 0 0.2rem rgba(212, 175, 55, 0.15);
        }


        .input-icon {
            color: #d4af37;
            margin-right: 5px;
        }


        .form-text {
            color: #64748b;
        }


        /* =================================================
           ALERT
        ================================================= */

        .error-alert {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 25px;
        }


        /* =================================================
           TOTAL
        ================================================= */

        .total-box {
            background: #0f172a;
            border-radius: 14px;
            padding: 20px;
            color: #ffffff;
        }


        .total-label {
            color: #cbd5e1;
            font-size: 14px;
        }


        .total-value {
            color: #d4af37;
            font-size: 28px;
            font-weight: 700;
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
            padding: 11px 20px;
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
            padding: 11px 20px;
        }


        .btn-outline-velora:hover {
            background: #0f172a;
            color: #ffffff;
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 768px) {

            .page-wrapper {
                padding: 25px 0 45px;
            }

            .edit-header {
                padding: 22px;
            }

            .edit-body {
                padding: 20px;
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

                        <i
                            class="bi bi-box-arrow-right me-1"
                        ></i>

                        Logout

                    </a>

                </li>

            </ul>

        </div>

    </div>

</nav>



<!-- =====================================================
     MAIN
===================================================== -->

<main class="page-wrapper">

    <div class="container">


        <!-- PAGE TITLE -->

        <div class="mb-4">

            <h2 class="page-title">

                <i class="bi bi-pencil-square me-2"></i>

                Edit Booking

            </h2>


            <p class="page-subtitle mb-0">

                Update your reservation details.

            </p>

        </div>



        <!-- =================================================
             CARD
        ================================================= -->

        <div class="edit-card">


            <!-- HEADER -->

            <div class="edit-header">

                <div class="booking-code">

                    Booking Code

                </div>


                <h3>

                    <?= e($booking["booking_code"]) ?>

                </h3>

            </div>



            <!-- BODY -->

            <div class="edit-body">


                <!-- ERROR -->

                <?php if ($error !== ""): ?>

                    <div class="error-alert">

                        <i
                            class="
                                bi
                                bi-exclamation-triangle-fill
                                me-2
                            "
                        ></i>

                        <?= e($error) ?>

                    </div>

                <?php endif; ?>



                <!-- =================================================
                     ROOM SUMMARY
                ================================================= -->

                <div class="room-summary">

                    <div class="row align-items-center">

                        <div class="col-md-8">

                            <div class="room-type">

                                <?= e($booking["type_name"]) ?>

                            </div>


                            <div class="room-number">

                                Room
                                <?= e($booking["room_number"]) ?>

                            </div>


                            <div class="room-details">

                                <i
                                    class="bi bi-building me-1"
                                ></i>

                                Floor
                                <?= e($booking["floor_number"]) ?>


                                <span class="mx-2">|</span>


                                <i
                                    class="bi bi-people me-1"
                                ></i>

                                Maximum
                                <?= e($booking["capacity"]) ?>
                                guests

                            </div>

                        </div>


                        <div
                            class="
                                col-md-4
                                text-md-end
                                mt-3
                                mt-md-0
                            "
                        >

                            <div class="small text-muted">

                                Room Price / Night

                            </div>


                            <div class="room-price">

                                Rs.
                                <?= number_format(
                                    (float)$booking["room_price"],
                                    2
                                ) ?>

                            </div>

                        </div>

                    </div>

                </div>



                <!-- =================================================
                     FORM
                ================================================= -->

                <form
                    method="POST"
                    action=""
                    id="editBookingForm"
                >


                    <!-- =================================================
                         DATES
                    ================================================= -->

                    <div class="mb-5">

                        <h4 class="section-title">

                            <i
                                class="
                                    bi
                                    bi-calendar-event
                                "
                            ></i>

                            Stay Dates

                        </h4>


                        <div class="gold-divider"></div>


                        <div class="row g-4">


                            <!-- CHECK IN -->

                            <div class="col-md-6">

                                <label
                                    for="check_in"
                                    class="form-label"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-calendar-check
                                            input-icon
                                        "
                                    ></i>

                                    Check-in Date

                                </label>


                                <input
                                    type="date"
                                    name="check_in"
                                    id="check_in"
                                    class="form-control"
                                    value="<?= e($check_in) ?>"
                                    required
                                >

                            </div>



                            <!-- CHECK OUT -->

                            <div class="col-md-6">

                                <label
                                    for="check_out"
                                    class="form-label"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-calendar-x
                                            input-icon
                                        "
                                    ></i>

                                    Check-out Date

                                </label>


                                <input
                                    type="date"
                                    name="check_out"
                                    id="check_out"
                                    class="form-control"
                                    value="<?= e($check_out) ?>"
                                    required
                                >

                            </div>

                        </div>

                    </div>



                    <!-- =================================================
                         GUESTS
                    ================================================= -->

                    <div class="mb-5">

                        <h4 class="section-title">

                            <i
                                class="
                                    bi
                                    bi-people-fill
                                "
                            ></i>

                            Guest Information

                        </h4>


                        <div class="gold-divider"></div>


                        <div class="row g-4">


                            <!-- ADULTS -->

                            <div class="col-md-6">

                                <label
                                    for="adults"
                                    class="form-label"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-person-fill
                                            input-icon
                                        "
                                    ></i>

                                    Adults

                                </label>


                                <input
                                    type="number"
                                    name="adults"
                                    id="adults"
                                    class="form-control"
                                    min="1"
                                    max="<?= (int)$booking["capacity"] ?>"
                                    value="<?= (int)$adults ?>"
                                    required
                                >

                            </div>



                            <!-- CHILDREN -->

                            <div class="col-md-6">

                                <label
                                    for="children"
                                    class="form-label"
                                >

                                    <i
                                        class="
                                            bi
                                            bi-person-hearts
                                            input-icon
                                        "
                                    ></i>

                                    Children

                                </label>


                                <input
                                    type="number"
                                    name="children"
                                    id="children"
                                    class="form-control"
                                    min="0"
                                    max="<?= (int)$booking["capacity"] ?>"
                                    value="<?= (int)$children ?>"
                                    required
                                >

                            </div>


                        </div>


                        <div class="form-text mt-2">

                            Maximum guests for this room:
                            <strong>
                                <?= (int)$booking["capacity"] ?>
                            </strong>

                        </div>

                    </div>



                    <!-- =================================================
                         SPECIAL REQUEST
                    ================================================= -->

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


                        <textarea
                            name="special_request"
                            id="special_request"
                            class="form-control"
                            rows="5"
                            placeholder="Enter any special requests..."
                        ><?= e($special_request) ?></textarea>


                        <div class="form-text mt-2">

                            Optional. You can leave this empty.

                        </div>

                    </div>



                    <!-- =================================================
                         TOTAL PREVIEW
                    ================================================= -->

                    <div class="mb-4">

                        <div class="total-box">

                            <div class="total-label">

                                Current Room Price

                            </div>


                            <div class="total-value">

                                Rs.
                                <?= number_format(
                                    (float)$booking["room_price"],
                                    2
                                ) ?>

                                <span
                                    class="
                                        fs-6
                                        text-light
                                        opacity-75
                                    "
                                >

                                    / night

                                </span>

                            </div>


                            <div
                                class="
                                    small
                                    text-light
                                    opacity-75
                                    mt-1
                                "
                            >

                                Total amount will be recalculated
                                automatically based on the number
                                of nights.

                            </div>

                        </div>

                    </div>



                    <!-- =================================================
                         BUTTONS
                    ================================================= -->

                    <div
                        class="
                            d-flex
                            flex-wrap
                            gap-2
                            justify-content-between
                            pt-3
                            border-top
                        "
                    >


                        <a
                            href="
                                booking_view.php?id=
                                <?= (int)$booking["id"] ?>
                            "
                            class="btn btn-outline-velora"
                        >

                            <i
                                class="
                                    bi
                                    bi-arrow-left
                                    me-1
                                "
                            ></i>

                            Back

                        </a>


                        <button
                            type="submit"
                            class="btn btn-velora"
                        >

                            <i
                                class="
                                    bi
                                    bi-check-circle
                                    me-1
                                "
                            ></i>

                            Save Changes

                        </button>


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

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const checkIn =
            document.getElementById("check_in");

        const checkOut =
            document.getElementById("check_out");

        const adults =
            document.getElementById("adults");

        const children =
            document.getElementById("children");


        /* =============================================
           DATE VALIDATION
        ============================================= */

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


        /* =============================================
           GUEST CAPACITY
        ============================================= */

        function validateGuests() {

            const adultCount =
                parseInt(adults.value) || 0;

            const childCount =
                parseInt(children.value) || 0;

            const capacity =
                <?= (int)$booking["capacity"] ?>;


            if (
                adultCount + childCount >
                capacity
            ) {

                children.setCustomValidity(
                    "Total guests cannot exceed " +
                    capacity +
                    "."
                );

            } else {

                children.setCustomValidity("");

            }

        }


        adults.addEventListener(
            "input",
            validateGuests
        );


        children.addEventListener(
            "input",
            validateGuests
        );


        /* =============================================
           FORM SUBMIT
        ============================================= */

        document
            .getElementById("editBookingForm")
            .addEventListener(
                "submit",
                function (event) {

                    if (
                        checkIn.value === "" ||
                        checkOut.value === ""
                    ) {

                        return;

                    }


                    if (
                        checkOut.value <=
                        checkIn.value
                    ) {

                        event.preventDefault();

                        alert(
                            "Check-out date must be after " +
                            "check-in date."
                        );

                        return;

                    }


                    validateGuests();


                    if (
                        !this.checkValidity()
                    ) {

                        event.preventDefault();

                    }

                }
            );

    }
);

</script>


<!-- Bootstrap JS -->

<script
    src="
        https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/
        dist/js/bootstrap.bundle.min.js
    "
></script>


</body>

</html>