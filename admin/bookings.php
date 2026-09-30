<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

requireRole("admin");


// =====================================================
// VARIABLES
// =====================================================

$success = "";
$error = "";


// =====================================================
// UPDATE BOOKING
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $booking_id = (int)($_POST["booking_id"] ?? 0);

    $action = $_POST["action"] ?? "";


    if ($booking_id <= 0) {

        $error = "Invalid booking.";

    } else {

        try {

            /*
             * =================================================
             * GET CUSTOMER INFORMATION
             * =================================================
             *
             * We need customer_id so that the notification
             * can be sent to the correct customer.
             */

            $customerStmt = $pdo->prepare("
                SELECT
                    b.customer_id,
                    b.booking_code,
                    r.room_number

                FROM bookings b

                INNER JOIN rooms r
                    ON b.room_id = r.id

                WHERE b.id = ?

                LIMIT 1
            ");

            $customerStmt->execute([
                $booking_id
            ]);

            $bookingInfo =
                $customerStmt->fetch(PDO::FETCH_ASSOC);


            if (!$bookingInfo) {

                $error = "Booking not found.";

            } else {

                $customer_id =
                    (int)$bookingInfo["customer_id"];

                $booking_code =
                    $bookingInfo["booking_code"];

                $room_number =
                    $bookingInfo["room_number"];


                // =============================================
                // CONFIRM BOOKING
                // =============================================

                if ($action === "confirm") {

                    $stmt = $pdo->prepare("
                        UPDATE bookings

                        SET booking_status = 'confirmed'

                        WHERE id = ?

                        AND booking_status = 'pending'
                    ");

                    $stmt->execute([
                        $booking_id
                    ]);


                    if ($stmt->rowCount() > 0) {

                        /*
                         * CREATE CUSTOMER NOTIFICATION
                         */

                        createNotification(
                            $pdo,
                            $customer_id,
                            "Booking Confirmed",
                            "Your booking {$booking_code} for Room {$room_number} has been confirmed by Velora Hotel."
                        );


                        $success =
                            "Booking confirmed successfully.";

                    } else {

                        $error =
                            "Booking cannot be confirmed.";

                    }

                }


                // =============================================
                // CANCEL BOOKING
                // =============================================

                elseif ($action === "cancel") {

                    $stmt = $pdo->prepare("
                        UPDATE bookings

                        SET booking_status = 'cancelled'

                        WHERE id = ?

                        AND booking_status IN
                        (
                            'pending',
                            'confirmed'
                        )
                    ");

                    $stmt->execute([
                        $booking_id
                    ]);


                    if ($stmt->rowCount() > 0) {

                        /*
                         * CREATE CUSTOMER NOTIFICATION
                         */

                        createNotification(
                            $pdo,
                            $customer_id,
                            "Booking Cancelled",
                            "Your booking {$booking_code} for Room {$room_number} has been cancelled."
                        );


                        $success =
                            "Booking cancelled successfully.";

                    } else {

                        $error =
                            "Booking cannot be cancelled.";

                    }

                }


                // =============================================
                // CHECK IN
                // =============================================

                elseif ($action === "check_in") {

                    $stmt = $pdo->prepare("
                        UPDATE bookings

                        SET booking_status = 'checked_in'

                        WHERE id = ?

                        AND booking_status = 'confirmed'
                    ");

                    $stmt->execute([
                        $booking_id
                    ]);


                    if ($stmt->rowCount() > 0) {

                        /*
                         * CREATE CUSTOMER NOTIFICATION
                         */

                        createNotification(
                            $pdo,
                            $customer_id,
                            "Check-in Completed",
                            "You have successfully checked in to Room {$room_number}. We hope you enjoy your stay at Velora Hotel."
                        );


                        $success =
                            "Guest checked in successfully.";

                    } else {

                        $error =
                            "Guest cannot be checked in. Booking must be confirmed first.";

                    }

                }


                // =============================================
                // CHECK OUT
                // =============================================

                elseif ($action === "check_out") {

                    $stmt = $pdo->prepare("
                        UPDATE bookings

                        SET booking_status = 'checked_out'

                        WHERE id = ?

                        AND booking_status = 'checked_in'
                    ");

                    $stmt->execute([
                        $booking_id
                    ]);


                    if ($stmt->rowCount() > 0) {

                        /*
                         * CREATE CUSTOMER NOTIFICATION
                         */

                        createNotification(
                            $pdo,
                            $customer_id,
                            "Check-out Completed",
                            "Your check-out from Room {$room_number} has been completed. Thank you for staying at Velora Hotel."
                        );


                        $success =
                            "Guest checked out successfully.";

                    } else {

                        $error =
                            "Guest cannot be checked out. Guest must be checked in first.";

                    }

                }


                // =============================================
                // PAYMENT STATUS
                // =============================================

                elseif ($action === "payment") {

                    $payment_status =
                        $_POST["payment_status"] ?? "";


                    $allowedPayments = [
                        "pending",
                        "partial",
                        "paid"
                    ];


                    if (!in_array(
                        $payment_status,
                        $allowedPayments,
                        true
                    )) {

                        $error =
                            "Invalid payment status.";

                    } else {

                        /*
                         * GET OLD PAYMENT STATUS
                         */

                        $oldPaymentStmt = $pdo->prepare("
                            SELECT payment_status
                            FROM bookings
                            WHERE id = ?
                            LIMIT 1
                        ");

                        $oldPaymentStmt->execute([
                            $booking_id
                        ]);

                        $oldPaymentStatus =
                            $oldPaymentStmt->fetchColumn();


                        /*
                         * UPDATE PAYMENT
                         */

                        $stmt = $pdo->prepare("
                            UPDATE bookings

                            SET payment_status = ?

                            WHERE id = ?
                        ");

                        $stmt->execute([
                            $payment_status,
                            $booking_id
                        ]);


                        /*
                         * ONLY SEND PAYMENT RECEIVED
                         * WHEN STATUS BECOMES PAID
                         */

                        if (
                            $payment_status === "paid"
                            &&
                            $oldPaymentStatus !== "paid"
                        ) {

                            createNotification(
                                $pdo,
                                $customer_id,
                                "Payment Received",
                                "Your payment for booking {$booking_code} has been received successfully. Thank you for choosing Velora Hotel."
                            );

                        }


                        $success =
                            "Payment status updated successfully.";

                    }

                }

            }

        } catch (PDOException $e) {

            $error =
                "Database error: " .
                $e->getMessage();

        }

    }

}


// =====================================================
// GET ALL BOOKINGS
// =====================================================

$stmt = $pdo->query("
    SELECT

        b.id,

        b.booking_code,

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

        b.created_at,

        r.room_number,

        rt.type_name,

        u.full_name,

        u.email,

        u.phone

    FROM bookings b

    INNER JOIN rooms r
        ON b.room_id = r.id

    INNER JOIN room_types rt
        ON r.room_type_id = rt.id

    INNER JOIN users u
        ON b.customer_id = u.id

    ORDER BY b.created_at DESC
");


$bookings =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


// =====================================================
// STATISTICS
// =====================================================

$totalBookings = count($bookings);

$pending = 0;

$confirmed = 0;

$checkedIn = 0;

$checkedOut = 0;

$cancelled = 0;

$totalRevenue = 0;


foreach ($bookings as $booking) {

    switch ($booking["booking_status"]) {

        case "pending":
            $pending++;
            break;

        case "confirmed":
            $confirmed++;
            break;

        case "checked_in":
            $checkedIn++;
            break;

        case "checked_out":
            $checkedOut++;
            break;

        case "cancelled":
            $cancelled++;
            break;
    }


    if (
        $booking["payment_status"] === "paid"
        &&
        $booking["booking_status"] !== "cancelled"
    ) {

        $totalRevenue +=
            (float)$booking["total_amount"];

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
        Booking Management - Velora Hotel
    </title>


    <!-- BOOTSTRAP -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- BOOTSTRAP ICONS -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <!-- VELORA THEME -->

    <link
        rel="stylesheet"
        href="../assets/css/velora.css"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            background: #f1f5f9;

            color: #0f172a;

            font-family:
                "Segoe UI",
                Arial,
                sans-serif;

        }


        /* =========================================
           SIDEBAR
        ========================================= */

        .sidebar {

            position: fixed;

            left: 0;

            top: 0;

            width: 250px;

            height: 100vh;

            background: #0f172a;

            color: white;

            padding: 25px 15px;

            overflow-y: auto;

            z-index: 1000;

        }


        .logo {

            font-size: 21px;

            font-weight: bold;

            padding:
                0 15px 30px;

        }


        .logo i {

            color: #d4af37;

        }


        .menu-title {

            color: #94a3b8;

            font-size: 12px;

            text-transform: uppercase;

            padding: 15px;

        }


        .sidebar a {

            display: flex;

            align-items: center;

            gap: 12px;

            color: #cbd5e1;

            text-decoration: none;

            padding:
                12px 15px;

            border-radius: 9px;

            margin-bottom: 5px;

            transition: .2s;

        }


        .sidebar a:hover,
        .sidebar a.active {

            background: #d4af37;

            color: #0f172a;

        }


        /* =========================================
           MAIN
        ========================================= */

        .main {

            margin-left: 250px;

            padding: 30px;

        }


        .page-title {

            font-size: 29px;

            font-weight: 700;

        }


        .page-title i {

            color: #d4af37;

        }


        .page-subtitle {

            color: #64748b;

        }


        /* =========================================
           STAT CARDS
        ========================================= */

        .stat-card {

            background: white;

            border-radius: 15px;

            padding: 20px;

            box-shadow:
                0 3px 12px
                rgba(0,0,0,.05);

            border:
                1px solid #e5e7eb;

        }


        .stat-icon {

            width: 45px;

            height: 45px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 10px;

            font-size: 20px;

        }


        .blue {

            background: #f3f0df;

            color: #b8952e;

        }


        .yellow {

            background: #fef3c7;

            color: #d97706;

        }


        .green {

            background: #dcfce7;

            color: #16a34a;

        }


        .red {

            background: #fee2e2;

            color: #dc2626;

        }


        .purple {

            background: #ede9fe;

            color: #7c3aed;

        }


        .stat-number {

            font-size: 25px;

            font-weight: 700;

            margin-top: 10px;

        }


        .stat-label {

            color: #64748b;

            font-size: 14px;

        }


        /* =========================================
           TABLE
        ========================================= */

        .table-card {

            background: white;

            border-radius: 16px;

            padding: 20px;

            box-shadow:
                0 3px 15px
                rgba(0,0,0,.05);

            border:
                1px solid #e5e7eb;

        }


        .table {

            vertical-align: middle;

        }


        .table thead th {

            background: #f8fafc;

            color: #475569;

            font-size: 13px;

            white-space: nowrap;

        }


        .customer-name {

            font-weight: 600;

        }


        .booking-code {

            color: #b8952e;

            font-weight: 600;

            font-size: 13px;

        }


        .room-number {

            font-weight: 700;

        }


        /* =========================================
           STATUS
        ========================================= */

        .status {

            display: inline-block;

            padding:
                5px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;

            white-space: nowrap;

        }


        .pending {

            background: #fef3c7;

            color: #92400e;

        }


        .confirmed {

            background: #dcfce7;

            color: #166534;

        }


        .checked-in {

            background: #dbeafe;

            color: #1e40af;

        }


        .checked-out {

            background: #e2e8f0;

            color: #334155;

        }


        .cancelled {

            background: #fee2e2;

            color: #991b1b;

        }


        /* =========================================
           NOTIFICATION SUCCESS
        ========================================= */

        .notification-info {

            font-size: 13px;

            color: #64748b;

            margin-top: 5px;

        }


        /* =========================================
           MOBILE
        ========================================= */

        @media(max-width:900px) {

            .sidebar {

                width: 70px;

                padding:
                    25px 10px;

            }


            .sidebar .menu-text,
            .menu-title,
            .logo-text {

                display: none;

            }


            .sidebar a {

                justify-content: center;

            }


            .logo {

                text-align: center;

                padding:
                    0 0 30px;

            }


            .main {

                margin-left: 70px;

                padding: 15px;

            }

        }

    </style>

</head>


<body>


<!-- ==================================================
     SIDEBAR
================================================== -->

<div class="sidebar">


    <div class="logo">

        <i class="bi bi-building"></i>

        <span class="logo-text">

            Velora Hotel

        </span>

    </div>


    <div class="menu-title">

        Management

    </div>


    <a href="dashboard.php">

        <i class="bi bi-speedometer2"></i>

        <span class="menu-text">

            Dashboard

        </span>

    </a>


    <a
        href="bookings.php"
        class="active"
    >

        <i class="bi bi-calendar-check"></i>

        <span class="menu-text">

            Bookings

        </span>

    </a>


    <a href="rooms.php">

        <i class="bi bi-door-open"></i>

        <span class="menu-text">

            Rooms

        </span>

    </a>


    <a href="customers.php">

        <i class="bi bi-people"></i>

        <span class="menu-text">

            Customers

        </span>

    </a>


    <div class="menu-title">

        System

    </div>


    <a href="payments.php">

        <i class="bi bi-credit-card"></i>

        <span class="menu-text">

            Payments

        </span>

    </a>


    <a href="reports.php">

        <i class="bi bi-bar-chart"></i>

        <span class="menu-text">

            Reports

        </span>

    </a>


    <a href="../auth/logout.php">

        <i class="bi bi-box-arrow-right"></i>

        <span class="menu-text">

            Logout

        </span>

    </a>


</div>


<!-- ==================================================
     MAIN
================================================== -->

<div class="main">


    <div class="mb-4">

        <div class="page-title">

            <i class="bi bi-calendar-check"></i>

            Booking Management

        </div>


        <div class="page-subtitle">

            Manage customer hotel reservations.

        </div>

    </div>


    <!-- ==================================================
         ALERTS
    ================================================== -->

    <?php if ($success): ?>

        <div
            class="alert alert-success alert-dismissible fade show"
        >

            <i class="bi bi-check-circle"></i>

            <?php echo e($success); ?>

            <div class="notification-info">

                Customer notification has also been created.

            </div>


            <button
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div
            class="alert alert-danger alert-dismissible fade show"
        >

            <i class="bi bi-exclamation-triangle"></i>

            <?php echo e($error); ?>


            <button
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- ==================================================
         STATISTICS
    ================================================== -->

    <div class="row g-3 mb-4">


        <div class="col-6 col-lg-3">

            <div class="stat-card">

                <div class="stat-icon blue">

                    <i class="bi bi-calendar-check"></i>

                </div>


                <div class="stat-number">

                    <?php echo $totalBookings; ?>

                </div>


                <div class="stat-label">

                    Total Bookings

                </div>

            </div>

        </div>


        <div class="col-6 col-lg-3">

            <div class="stat-card">

                <div class="stat-icon yellow">

                    <i class="bi bi-hourglass-split"></i>

                </div>


                <div class="stat-number">

                    <?php echo $pending; ?>

                </div>


                <div class="stat-label">

                    Pending

                </div>

            </div>

        </div>


        <div class="col-6 col-lg-3">

            <div class="stat-card">

                <div class="stat-icon green">

                    <i class="bi bi-person-check"></i>

                </div>


                <div class="stat-number">

                    <?php echo $checkedIn; ?>

                </div>


                <div class="stat-label">

                    Checked In

                </div>

            </div>

        </div>


        <div class="col-6 col-lg-3">

            <div class="stat-card">

                <div class="stat-icon purple">

                    <i class="bi bi-cash-stack"></i>

                </div>


                <div class="stat-number">

                    Rs.
                    <?php
                    echo number_format(
                        $totalRevenue,
                        0
                    );
                    ?>

                </div>


                <div class="stat-label">

                    Paid Revenue

                </div>

            </div>

        </div>


    </div>


    <!-- ==================================================
         BOOKING TABLE
    ================================================== -->

    <div class="table-card">


        <div
            class="
                d-flex
                justify-content-between
                align-items-center
                mb-3
            "
        >

            <h5 class="fw-bold mb-0">

                All Bookings

            </h5>


            <span class="text-muted">

                <?php echo $totalBookings; ?>

                booking(s)

            </span>

        </div>


        <div class="table-responsive">


            <table class="table table-hover">


                <thead>

                    <tr>

                        <th>Booking</th>

                        <th>Customer</th>

                        <th>Room</th>

                        <th>Stay</th>

                        <th>Total</th>

                        <th>Status</th>

                        <th>Payment</th>

                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (count($bookings) > 0): ?>


                    <?php foreach ($bookings as $booking): ?>


                        <?php

                        $status =
                            $booking["booking_status"];


                        $statusClass =
                            str_replace(
                                "_",
                                "-",
                                $status
                            );

                        ?>


                        <tr>


                            <!-- BOOKING -->

                            <td>

                                <div class="booking-code">

                                    <?php

                                    echo e(
                                        $booking["booking_code"]
                                    );

                                    ?>

                                </div>


                                <small class="text-muted">

                                    <?php

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $booking["created_at"]
                                        )
                                    );

                                    ?>

                                </small>

                            </td>


                            <!-- CUSTOMER -->

                            <td>

                                <div class="customer-name">

                                    <?php

                                    echo e(
                                        $booking["full_name"]
                                    );

                                    ?>

                                </div>


                                <small class="text-muted">

                                    <?php

                                    echo e(
                                        $booking["email"]
                                    );

                                    ?>

                                </small>

                            </td>


                            <!-- ROOM -->

                            <td>

                                <div class="room-number">

                                    Room

                                    <?php

                                    echo e(
                                        $booking["room_number"]
                                    );

                                    ?>

                                </div>


                                <small class="text-muted">

                                    <?php

                                    echo e(
                                        $booking["type_name"]
                                    );

                                    ?>

                                </small>

                            </td>


                            <!-- STAY -->

                            <td>

                                <div>

                                    <?php

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $booking["check_in"]
                                        )
                                    );

                                    ?>

                                </div>


                                <small class="text-muted">

                                    to

                                    <?php

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $booking["check_out"]
                                        )
                                    );

                                    ?>

                                </small>


                                <div>

                                    <small>

                                        <?php

                                        echo e(
                                            $booking[
                                                "number_of_nights"
                                            ]
                                        );

                                        ?>

                                        night(s)

                                    </small>

                                </div>

                            </td>


                            <!-- TOTAL -->

                            <td>

                                <strong>

                                    Rs.

                                    <?php

                                    echo number_format(
                                        (float)
                                        $booking[
                                            "total_amount"
                                        ],
                                        2
                                    );

                                    ?>

                                </strong>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <span
                                    class="
                                        status
                                        <?php
                                        echo e(
                                            $statusClass
                                        );
                                        ?>
                                    "
                                >

                                    <?php

                                    echo e(
                                        ucwords(
                                            str_replace(
                                                "_",
                                                " ",
                                                $status
                                            )
                                        )
                                    );

                                    ?>

                                </span>

                            </td>


                            <!-- PAYMENT -->

                            <td>

                                <?php

                                $payment =
                                    $booking[
                                        "payment_status"
                                    ];

                                ?>


                                <?php if (
                                    $payment === "paid"
                                ): ?>

                                    <span
                                        class="
                                            status
                                            confirmed
                                        "
                                    >

                                        Paid

                                    </span>

                                <?php elseif (
                                    $payment === "partial"
                                ): ?>

                                    <span
                                        class="
                                            status
                                            checked-in
                                        "
                                    >

                                        Partial

                                    </span>

                                <?php else: ?>

                                    <span
                                        class="
                                            status
                                            pending
                                        "
                                    >

                                        Pending

                                    </span>

                                <?php endif; ?>


                            </td>


                            <!-- ACTIONS -->

                            <td>


                                <div class="dropdown">


                                    <button
                                        class="
                                            btn
                                            btn-sm
                                            btn-outline-primary
                                            dropdown-toggle
                                        "
                                        data-bs-toggle="dropdown"
                                    >

                                        Manage

                                    </button>


                                    <ul
                                        class="
                                            dropdown-menu
                                            dropdown-menu-end
                                        "
                                    >


                                        <!-- CONFIRM -->

                                        <?php if (
                                            $status ===
                                            "pending"
                                        ): ?>

                                            <li>

                                                <form
                                                    method="POST"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="booking_id"
                                                        value="<?php echo (int)$booking["id"]; ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="confirm"
                                                    >

                                                    <button
                                                        class="
                                                            dropdown-item
                                                            text-success
                                                        "
                                                        type="submit"
                                                    >

                                                        <i
                                                            class="
                                                                bi
                                                                bi-check-circle
                                                            "
                                                        ></i>

                                                        Confirm Booking

                                                    </button>

                                                </form>

                                            </li>

                                        <?php endif; ?>


                                        <!-- CHECK IN -->

                                        <?php if (
                                            $status ===
                                            "confirmed"
                                        ): ?>

                                            <li>

                                                <form
                                                    method="POST"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="booking_id"
                                                        value="<?php echo (int)$booking["id"]; ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="check_in"
                                                    >

                                                    <button
                                                        class="
                                                            dropdown-item
                                                            text-primary
                                                        "
                                                        type="submit"
                                                    >

                                                        <i
                                                            class="
                                                                bi
                                                                bi-box-arrow-in-right
                                                            "
                                                        ></i>

                                                        Check In

                                                    </button>

                                                </form>

                                            </li>

                                        <?php endif; ?>


                                        <!-- CHECK OUT -->

                                        <?php if (
                                            $status ===
                                            "checked_in"
                                        ): ?>

                                            <li>

                                                <form
                                                    method="POST"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="booking_id"
                                                        value="<?php echo (int)$booking["id"]; ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="check_out"
                                                    >

                                                    <button
                                                        class="
                                                            dropdown-item
                                                            text-secondary
                                                        "
                                                        type="submit"
                                                    >

                                                        <i
                                                            class="
                                                                bi
                                                                bi-box-arrow-right
                                                            "
                                                        ></i>

                                                        Check Out

                                                    </button>

                                                </form>

                                            </li>

                                        <?php endif; ?>


                                        <li>

                                            <hr
                                                class="
                                                    dropdown-divider
                                                "
                                            >

                                        </li>


                                        <!-- PAYMENT -->

                                        <li>

                                            <h6
                                                class="
                                                    dropdown-header
                                                "
                                            >

                                                Payment Status

                                            </h6>

                                        </li>


                                        <!-- PAID -->

                                        <li>

                                            <form
                                                method="POST"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="booking_id"
                                                    value="<?php echo (int)$booking["id"]; ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="payment"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="payment_status"
                                                    value="paid"
                                                >

                                                <button
                                                    class="
                                                        dropdown-item
                                                        text-success
                                                    "
                                                    type="submit"
                                                >

                                                    <i
                                                        class="
                                                            bi
                                                            bi-check-lg
                                                        "
                                                    ></i>

                                                    Mark Paid

                                                </button>

                                            </form>

                                        </li>


                                        <!-- PARTIAL -->

                                        <li>

                                            <form
                                                method="POST"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="booking_id"
                                                    value="<?php echo (int)$booking["id"]; ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="payment"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="payment_status"
                                                    value="partial"
                                                >

                                                <button
                                                    class="
                                                        dropdown-item
                                                        text-primary
                                                    "
                                                    type="submit"
                                                >

                                                    <i
                                                        class="
                                                            bi
                                                            bi-dash-circle
                                                        "
                                                    ></i>

                                                    Partial Payment

                                                </button>

                                            </form>

                                        </li>


                                        <!-- PENDING -->

                                        <li>

                                            <form
                                                method="POST"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="booking_id"
                                                    value="<?php echo (int)$booking["id"]; ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="payment"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="payment_status"
                                                    value="pending"
                                                >

                                                <button
                                                    class="
                                                        dropdown-item
                                                        text-warning
                                                    "
                                                    type="submit"
                                                >

                                                    <i
                                                        class="
                                                            bi
                                                            bi-clock
                                                        "
                                                    ></i>

                                                    Mark Pending

                                                </button>

                                            </form>

                                        </li>


                                        <!-- CANCEL -->

                                        <?php if (
                                            $status ===
                                                "pending"
                                            ||
                                            $status ===
                                                "confirmed"
                                        ): ?>

                                            <li>

                                                <hr
                                                    class="
                                                        dropdown-divider
                                                    "
                                                >

                                            </li>


                                            <li>

                                                <form
                                                    method="POST"
                                                    onsubmit="
                                                        return confirm(
                                                            'Are you sure you want to cancel this booking?'
                                                        );
                                                    "
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="booking_id"
                                                        value="<?php echo (int)$booking["id"]; ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="cancel"
                                                    >

                                                    <button
                                                        class="
                                                            dropdown-item
                                                            text-danger
                                                        "
                                                        type="submit"
                                                    >

                                                        <i
                                                            class="
                                                                bi
                                                                bi-x-circle
                                                            "
                                                        ></i>

                                                        Cancel Booking

                                                    </button>

                                                </form>

                                            </li>

                                        <?php endif; ?>


                                    </ul>

                                </div>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="8"
                            class="text-center py-5"
                        >

                            <i
                                class="
                                    bi
                                    bi-calendar-x
                                    fs-1
                                    text-muted
                                "
                            ></i>


                            <div
                                class="
                                    mt-3
                                    text-muted
                                "
                            >

                                No bookings found.

                            </div>

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>


    </div>


</div>


<!-- BOOTSTRAP JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>