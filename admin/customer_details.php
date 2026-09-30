<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

requireRole("admin");

$customer_id = (int)($_GET["id"] ?? 0);

if ($customer_id <= 0) {
    redirect("customers.php");
}


/* CUSTOMER */

$stmt = $pdo->prepare("
    SELECT
        id,
        full_name,
        email,
        phone,
        role,
        status,
        created_at
    FROM users
    WHERE id = ?
    AND role = 'customer'
");

$stmt->execute([$customer_id]);

$customer = $stmt->fetch();

if (!$customer) {
    die("Customer not found.");
}


/* BOOKING HISTORY */

$stmt = $pdo->prepare("
    SELECT
        b.id,
        b.booking_code,
        b.check_in AS check_in_date,
        b.check_out AS check_out_date,
        b.number_of_nights,
        b.total_amount,
        b.booking_status,
        b.payment_status,
        r.room_number,
        rt.type_name
   
        FROM bookings b
    
INNER JOIN rooms r
        ON b.room_id = r.id

    INNER JOIN room_types rt
        ON r.room_type_id = rt.id

    WHERE b.customer_id = ?

    ORDER BY b.id DESC
");

$stmt->execute([$customer_id]);

$bookings = $stmt->fetchAll();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<link rel="stylesheet" href="../assets/css/style.css">

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Customer Details - Velora Hotel
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

<link rel="stylesheet" href="../assets/css/velora.css">
</head>

<body class="bg-light">

<div class="container py-4">

    <!-- HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold">
                Customer Details
            </h2>

            <p class="text-muted mb-0">

                <?php
                echo e($customer["full_name"]);
                ?>

            </p>

        </div>

        <a
            href="customers.php"
            class="btn btn-secondary"
        >

            ← Customers

        </a>

    </div>


    <!-- CUSTOMER INFORMATION -->

    <div class="card shadow-sm mb-4">

        <div class="card-header bg-primary text-white">

            <h5 class="mb-0">
                Customer Information
            </h5>

        </div>


        <div class="card-body">

            <div class="row">

                <div class="col-md-6 mb-3">

                    <strong>
                        Full Name
                    </strong>

                    <div class="text-muted">

                        <?php
                        echo e(
                            $customer["full_name"]
                        );
                        ?>

                    </div>

                </div>


                <div class="col-md-6 mb-3">

                    <strong>
                        Email
                    </strong>

                    <div class="text-muted">

                        <?php
                        echo e(
                            $customer["email"]
                        );
                        ?>

                    </div>

                </div>


                <div class="col-md-6 mb-3">

                    <strong>
                        Phone
                    </strong>

                    <div class="text-muted">

                        <?php
                        echo e(
                            $customer["phone"]
                        );
                        ?>

                    </div>

                </div>


                <div class="col-md-6 mb-3">

                    <strong>
                        Account Status
                    </strong>

                    <div>

                        <?php if ($customer["status"] === "active"): ?>

                            <span class="badge bg-success">
                                Active
                            </span>

                        <?php else: ?>

                            <span class="badge bg-danger">
                                Inactive
                            </span>

                        <?php endif; ?>

                    </div>

                </div>


                <div class="col-md-6">

                    <strong>
                        Registered Date
                    </strong>

                    <div class="text-muted">

                        <?php
                        echo e(
                            date(
                                "Y-m-d",
                                strtotime(
                                    $customer["created_at"]
                                )
                            )
                        );
                        ?>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- BOOKING HISTORY -->

    <div class="card shadow-sm">

        <div class="card-header">

            <h5 class="mb-0">
                Booking History
            </h5>

        </div>


        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-hover">

                    <thead class="table-light">

                        <tr>

                            <th>Booking Code</th>

                            <th>Room</th>

                            <th>Check In</th>

                            <th>Check Out</th>

                            <th>Nights</th>

                            <th>Total</th>

                            <th>Booking Status</th>

                            <th>Payment</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($bookings)): ?>

                        <tr>

                            <td
                                colspan="8"
                                class="text-center text-muted py-4"
                            >

                                This customer has no bookings yet.

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($bookings as $booking): ?>

                            <tr>

                                <td>

                                    <strong>

                                        <?php
                                        echo e(
                                            $booking["booking_code"]
                                        );
                                        ?>

                                    </strong>

                                </td>


                                <td>

                                    Room
                                    <?php
                                    echo e(
                                        $booking["room_number"]
                                    );
                                    ?>

                                    <br>

                                    <small class="text-muted">

                                        <?php
                                        echo e(
                                            $booking["type_name"]
                                        );
                                        ?>

                                    </small>

                                </td>


                                <td>

                                    <?php
                                    echo e(
                                        $booking["check_in_date"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo e(
                                        $booking["check_out_date"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo e(
                                        $booking["number_of_nights"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    Rs.
                                    <?php
                                    echo number_format(
                                        $booking["total_amount"],
                                        2
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo e(
                                        $booking["booking_status"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo e(
                                        $booking["payment_status"]
                                    );
                                    ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

</body>

</html>