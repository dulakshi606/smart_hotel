<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

requireRole("customer");


// ==================================================
// GET BOOKING ID
// ==================================================

$booking_id =
    isset($_GET["id"])
    ? (int)$_GET["id"]
    : 0;


if ($booking_id <= 0) {

    die("Invalid booking.");

}


// ==================================================
// GET CUSTOMER ID
// ==================================================

$customer_id =
    $_SESSION["user_id"];


// ==================================================
// GET BOOKING
// ==================================================

$stmt = $pdo->prepare("
    SELECT

        b.*,

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


$booking =
    $stmt->fetch();


if (!$booking) {

    die("Booking not found.");

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

        Booking Confirmation - Velora Hotel

    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <style>

        body {

            background: #f4f6f9;

        }


        .confirmation-card {

            max-width: 800px;

            margin: 60px auto;

            background: white;

            border-radius: 20px;

            padding: 45px;

            box-shadow:
                0 8px 30px rgba(0,0,0,.08);

        }


        .success-icon {

            width: 80px;

            height: 80px;

            background: #dcfce7;

            color: #16a34a;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 42px;

            margin: auto;

        }


        .booking-code {

            background: #eff6ff;

            color: #2563eb;

            padding: 15px;

            border-radius: 10px;

            font-size: 22px;

            font-weight: bold;

            text-align: center;

        }


        .info-box {

            background: #f8fafc;

            border-radius: 12px;

            padding: 18px;

        }

    </style>

<link rel="stylesheet" href="../assets/css/velora.css">
</head>


<body>


<div class="container">


    <div class="confirmation-card">


        <!-- SUCCESS -->

        <div class="text-center">


            <div class="success-icon">

                <i class="bi bi-check-lg"></i>

            </div>


            <h2 class="fw-bold mt-4">

                Booking Successful!

            </h2>


            <p class="text-muted">

                Your room reservation has been
                successfully created.

            </p>


        </div>


        <!-- BOOKING CODE -->

        <div class="mt-4">


            <p class="text-center text-muted mb-2">

                Your Booking Code

            </p>


            <div class="booking-code">

                <?php

                echo e(
                    $booking["booking_code"]
                );

                ?>

            </div>


        </div>


        <hr class="my-4">


        <!-- ROOM -->

        <div class="row g-3">


            <div class="col-md-6">

                <div class="info-box">

                    <small class="text-muted">

                        Room

                    </small>

                    <h5 class="mb-0">

                        Room

                        <?php

                        echo e(
                            $booking["room_number"]
                        );

                        ?>

                    </h5>

                    <small>

                        <?php

                        echo e(
                            $booking["type_name"]
                        );

                        ?>

                    </small>

                </div>

            </div>


            <!-- STATUS -->

            <div class="col-md-6">

                <div class="info-box">

                    <small class="text-muted">

                        Booking Status

                    </small>

                    <h5>

                        <span class="badge bg-warning text-dark">

                            <?php

                            echo e(
                                ucfirst(
                                    $booking["booking_status"]
                                )
                            );

                            ?>

                        </span>

                    </h5>

                </div>

            </div>


            <!-- CHECK IN -->

            <div class="col-md-6">

                <div class="info-box">

                    <small class="text-muted">

                        Check-in

                    </small>

                    <h5 class="mb-0">

                        <?php

                        echo e(
                            $booking["check_in"]
                        );

                        ?>

                    </h5>

                </div>

            </div>


            <!-- CHECK OUT -->

            <div class="col-md-6">

                <div class="info-box">

                    <small class="text-muted">

                        Check-out

                    </small>

                    <h5 class="mb-0">

                        <?php

                        echo e(
                            $booking["check_out"]
                        );

                        ?>

                    </h5>

                </div>

            </div>


            <!-- NIGHTS -->

            <div class="col-md-6">

                <div class="info-box">

                    <small class="text-muted">

                        Number of Nights

                    </small>

                    <h5 class="mb-0">

                        <?php

                        echo e(
                            $booking["number_of_nights"]
                        );

                        ?>

                    </h5>

                </div>

            </div>


            <!-- PAYMENT -->

            <div class="col-md-6">

                <div class="info-box">

                    <small class="text-muted">

                        Payment Status

                    </small>

                    <h5>

                        <span class="badge bg-secondary">

                            <?php

                            echo e(
                                ucfirst(
                                    $booking["payment_status"]
                                )
                            );

                            ?>

                        </span>

                    </h5>

                </div>

            </div>


        </div>


        <!-- TOTAL -->

        <div class="alert alert-primary mt-4">

            <div class="d-flex justify-content-between">

                <strong>

                    Total Amount

                </strong>

                <strong>

                    Rs.

                    <?php

                    echo number_format(
                        $booking["total_amount"],
                        2
                    );

                    ?>

                </strong>

            </div>

        </div>


        <!-- BUTTONS -->

        <div class="row g-2 mt-3">


            <div class="col-md-6">

                <a
                    href="bookings.php"
                    class="btn btn-primary w-100"
                >

                    <i class="bi bi-calendar-check"></i>

                    My Bookings

                </a>

            </div>


            <div class="col-md-6">

                <a
                    href="rooms.php"
                    class="btn btn-outline-primary w-100"
                >

                    Browse More Rooms

                </a>

            </div>


        </div>


    </div>

</div>


</body>

</html>