<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

startSession();
requireRole("customer");

$customer_id = $_SESSION["user_id"];

$success = "";
$error = "";


/* =====================================================
   HANDLE NEW SERVICE REQUEST
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $booking_id = $_POST["booking_id"] ?? "";
    $service_type = trim($_POST["service_type"] ?? "");
    $description = trim($_POST["description"] ?? "");

    $allowed_services = [
        "Housekeeping",
        "Room Service",
        "Laundry",
        "Extra Bed",
        "Extra Towels",
        "Airport Pickup",
        "Other"
    ];

    /* Validate booking */

    if (!is_numeric($booking_id)) {

        $error = "Please select a valid booking.";

    } elseif (!in_array($service_type, $allowed_services)) {

        $error = "Please select a valid service.";

    } else {

        /*
         * Make sure the booking belongs to this customer
         * and is not cancelled.
         */

        $booking_check = $pdo->prepare("
            SELECT id
            FROM bookings
            WHERE id = ?
            AND customer_id = ?
            AND booking_status <> 'cancelled'
            LIMIT 1
        ");

        $booking_check->execute([
            $booking_id,
            $customer_id
        ]);

        $booking = $booking_check->fetch(PDO::FETCH_ASSOC);


        if (!$booking) {

            $error = "The selected booking is not valid.";

        } else {

            /* Insert request */

            $stmt = $pdo->prepare("
                INSERT INTO service_requests
                (
                    customer_id,
                    booking_id,
                    service_type,
                    description,
                    request_status
                )
                VALUES (?, ?, ?, ?, 'pending')
            ");

            $stmt->execute([
                $customer_id,
                $booking_id,
                $service_type,
                $description !== "" ? $description : null
            ]);

            $success = "Your service request has been submitted successfully.";
        }
    }
}


/* =====================================================
   GET CUSTOMER BOOKINGS
===================================================== */

$booking_stmt = $pdo->prepare("
    SELECT
        b.id,
        b.booking_code,
        b.check_in,
        b.check_out,
        b.booking_status,
        r.room_number,
        rt.type_name
    FROM bookings b
    INNER JOIN rooms r
        ON b.room_id = r.id
    INNER JOIN room_types rt
        ON r.room_type_id = rt.id
    WHERE b.customer_id = ?
    AND b.booking_status <> 'cancelled'
    ORDER BY b.id DESC
");

$booking_stmt->execute([$customer_id]);

$bookings = $booking_stmt->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   GET CUSTOMER SERVICE REQUESTS
===================================================== */

$request_stmt = $pdo->prepare("
    SELECT
        sr.id,
        sr.service_type,
        sr.description,
        sr.request_status,
        sr.created_at,

        b.booking_code,

        r.room_number,

        rt.type_name

    FROM service_requests sr

    INNER JOIN bookings b
        ON sr.booking_id = b.id

    INNER JOIN rooms r
        ON b.room_id = r.id

    INNER JOIN room_types rt
        ON r.room_type_id = rt.id

    WHERE sr.customer_id = ?

    ORDER BY sr.id DESC
");

$request_stmt->execute([$customer_id]);

$requests = $request_stmt->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   STATUS CLASS
===================================================== */

function serviceStatusClass($status)
{
    switch (strtolower($status)) {

        case "accepted":
            return "status-accepted";

        case "in_progress":
            return "status-progress";

        case "completed":
            return "status-completed";

        case "cancelled":
            return "status-cancelled";

        default:
            return "status-pending";
    }
}


/* =====================================================
   STATUS ICON
===================================================== */

function serviceStatusIcon($status)
{
    switch (strtolower($status)) {

        case "accepted":
            return "bi-check-circle";

        case "in_progress":
            return "bi-arrow-repeat";

        case "completed":
            return "bi-check2-all";

        case "cancelled":
            return "bi-x-circle";

        default:
            return "bi-clock";
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

    <title>Hotel Services - Velora Hotel</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="../assets/css/velora.css"
    >

    <style>

        body {
            background: #f7f5ef;
            color: #172033;
        }

        /* =================================================
           HEADER
        ================================================= */

        .service-header {
            background: linear-gradient(
                135deg,
                #0b1f3a,
                #132f52
            );

            color: white;
            padding: 45px 0;
        }

        .service-header h1 {
            font-weight: 700;
        }


        /* =================================================
           MAIN CARD
        ================================================= */

        .service-card {
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid #e7dfc8;
            box-shadow: 0 8px 25px rgba(11, 31, 58, 0.08);
        }

        .service-card-header {
            background: #0b1f3a;
            color: white;
            padding: 18px 22px;
            border-radius: 18px 18px 0 0;
        }


        /* =================================================
           FORM
        ================================================= */

        .form-label {
            font-weight: 600;
            color: #0b1f3a;
        }

        .form-control,
        .form-select {
            border: 1px solid #ddd5bd;
            border-radius: 10px;
            padding: 11px 13px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #d4af37;
            box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.15);
        }

        .btn-gold {
            background: #d4af37;
            color: #0b1f3a;
            border: none;
            font-weight: 700;
            border-radius: 10px;
        }

        .btn-gold:hover {
            background: #b9972e;
            color: white;
        }


        /* =================================================
           SERVICE ITEMS
        ================================================= */

        .service-option {
            border: 1px solid #e4dcc6;
            border-radius: 12px;
            padding: 15px;
            height: 100%;
            transition: 0.2s;
        }

        .service-option:hover {
            border-color: #d4af37;
            background: #fffdf7;
        }

        .service-icon {
            font-size: 28px;
            color: #d4af37;
            margin-bottom: 8px;
        }


        /* =================================================
           REQUEST CARD
        ================================================= */

        .request-card {
            background: white;
            border: 1px solid #e7dfc8;
            border-radius: 15px;
            padding: 20px;
            height: 100%;
            box-shadow: 0 5px 18px rgba(11, 31, 58, 0.06);
        }

        .request-service {
            font-size: 19px;
            font-weight: 700;
            color: #0b1f3a;
        }

        .booking-code {
            color: #b38b20;
            font-weight: 700;
        }


        /* =================================================
           STATUS
        ================================================= */

        .status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-accepted {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-progress {
            background: #cff4fc;
            color: #055160;
        }

        .status-completed {
            background: #e2e3e5;
            color: #41464b;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #842029;
        }


        /* =================================================
           EMPTY
        ================================================= */

        .empty-box {
            background: white;
            border: 1px solid #e7dfc8;
            border-radius: 16px;
            padding: 50px 20px;
            text-align: center;
        }

        .empty-icon {
            font-size: 50px;
            color: #d4af37;
            margin-bottom: 15px;
        }

    </style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<section class="service-header">

    <div class="container">

        <div class="d-flex justify-content-between align-items-center">

            <div>

                <h1 class="mb-2">

                    <i class="bi bi-bell"></i>

                    Hotel Services

                </h1>

                <p class="mb-0 text-white-50">

                    Request hotel services during your stay.

                </p>

            </div>


            <a
                href="dashboard.php"
                class="btn btn-outline-light"
            >

                <i class="bi bi-arrow-left"></i>

                Dashboard

            </a>

        </div>

    </div>

</section>


<div class="container py-5">


<!-- =====================================================
     ALERTS
===================================================== -->

<?php if ($success): ?>

    <div
        class="alert alert-success alert-dismissible fade show"
        role="alert"
    >

        <i class="bi bi-check-circle"></i>

        <?= e($success) ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>

<?php endif; ?>


<?php if ($error): ?>

    <div
        class="alert alert-danger alert-dismissible fade show"
        role="alert"
    >

        <i class="bi bi-exclamation-circle"></i>

        <?= e($error) ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>

<?php endif; ?>


<!-- =====================================================
     NEW SERVICE REQUEST
===================================================== -->

<div class="service-card mb-5">

    <div class="service-card-header">

        <h4 class="mb-0">

            <i class="bi bi-plus-circle"></i>

            Request a Hotel Service

        </h4>

    </div>


    <div class="p-4">

        <?php if (empty($bookings)): ?>

            <div class="empty-box">

                <div class="empty-icon">

                    <i class="bi bi-calendar-x"></i>

                </div>

                <h5>No Active Booking</h5>

                <p class="text-muted mb-0">

                    You need an active booking before requesting
                    hotel services.

                </p>

            </div>

        <?php else: ?>

            <form
                method="POST"
                action=""
            >

                <div class="row g-4">


                    <!-- BOOKING -->

                    <div class="col-md-6">

                        <label class="form-label">

                            Select Booking

                        </label>

                        <select
                            name="booking_id"
                            class="form-select"
                            required
                        >

                            <option value="">
                                -- Select your booking --
                            </option>

                            <?php foreach ($bookings as $booking): ?>

                                <option
                                    value="<?= (int)$booking["id"] ?>"
                                >

                                    <?= e($booking["booking_code"]) ?>

                                    -
                                    Room <?= e($booking["room_number"]) ?>

                                    -
                                    <?= e($booking["type_name"]) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- SERVICE -->

                    <div class="col-md-6">

                        <label class="form-label">

                            Service Type

                        </label>

                        <select
                            name="service_type"
                            class="form-select"
                            required
                        >

                            <option value="">
                                -- Select a service --
                            </option>

                            <option value="Housekeeping">
                                🧹 Housekeeping
                            </option>

                            <option value="Room Service">
                                🛎️ Room Service
                            </option>

                            <option value="Laundry">
                                🧺 Laundry
                            </option>

                            <option value="Extra Bed">
                                🛏️ Extra Bed
                            </option>

                            <option value="Extra Towels">
                                🧴 Extra Towels
                            </option>

                            <option value="Airport Pickup">
                                🚕 Airport Pickup
                            </option>

                            <option value="Other">
                                💬 Other Request
                            </option>

                        </select>

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="col-12">

                        <label class="form-label">

                            Additional Details
                            <span class="text-muted fw-normal">
                                (Optional)
                            </span>

                        </label>

                        <textarea
                            name="description"
                            class="form-control"
                            rows="4"
                            maxlength="1000"
                            placeholder="Tell us any additional details about your request..."
                        ></textarea>

                    </div>


                    <!-- SUBMIT -->

                    <div class="col-12">

                        <button
                            type="submit"
                            class="btn btn-gold px-4"
                        >

                            <i class="bi bi-send"></i>

                            Submit Service Request

                        </button>

                    </div>

                </div>

            </form>

        <?php endif; ?>

    </div>

</div>


<!-- =====================================================
     MY REQUESTS
===================================================== -->

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h3 class="mb-1">
            My Service Requests
        </h3>

        <p class="text-muted mb-0">
            Track the services you have requested.
        </p>

    </div>

</div>


<?php if (empty($requests)): ?>

    <div class="empty-box">

        <div class="empty-icon">

            <i class="bi bi-bell-slash"></i>

        </div>

        <h5>No Service Requests</h5>

        <p class="text-muted">
            Your service requests will appear here.
        </p>

    </div>

<?php else: ?>

    <div class="row g-4">

        <?php foreach ($requests as $request): ?>

            <div class="col-lg-6">

                <div class="request-card">

                    <div class="d-flex justify-content-between align-items-start mb-3">

                        <div>

                            <div class="request-service">

                                <?= e($request["service_type"]) ?>

                            </div>

                            <div class="booking-code">

                                <?= e($request["booking_code"]) ?>

                            </div>

                        </div>


                        <span
                            class="status <?= serviceStatusClass($request["request_status"]) ?>"
                        >

                            <i
                                class="bi <?= serviceStatusIcon($request["request_status"]) ?>"
                            ></i>

                            <?= ucwords(
                                str_replace(
                                    "_",
                                    " ",
                                    $request["request_status"]
                                )
                            ) ?>

                        </span>

                    </div>


                    <div class="small text-muted mb-3">

                        <i class="bi bi-door-open"></i>

                        Room <?= e($request["room_number"]) ?>

                        &nbsp; • &nbsp;

                        <?= e($request["type_name"]) ?>

                    </div>


                    <?php if (!empty($request["description"])): ?>

                        <div class="border-top pt-3 mb-3">

                            <div class="small text-muted mb-1">
                                Request Details
                            </div>

                            <div>
                                <?= nl2br(
                                    e($request["description"])
                                ) ?>
                            </div>

                        </div>

                    <?php endif; ?>


                    <div class="border-top pt-3">

                        <small class="text-muted">

                            <i class="bi bi-clock"></i>

                            Requested:

                            <?= date(
                                "d M Y, h:i A",
                                strtotime($request["created_at"])
                            ) ?>

                        </small>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>