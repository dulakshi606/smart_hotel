<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

startSession();
requireRole("customer");

$customer_id = $_SESSION["user_id"];
$booking_id = $_GET["id"] ?? "";


/* =====================================================
   VALIDATE BOOKING ID
===================================================== */

if (!is_numeric($booking_id)) {
    header("Location: invoices.php");
    exit;
}

$booking_id = (int)$booking_id;


/* =====================================================
   GET BOOKING + CUSTOMER DETAILS
===================================================== */

$stmt = $pdo->prepare("
    SELECT
        b.id,
        b.booking_code,
        b.check_in,
        b.check_out,
        b.number_of_nights,
        b.adults,
        b.children,
        b.room_price,
        b.total_amount,
        b.booking_status,
        b.payment_status,
        b.special_request,
        b.created_at,

        r.room_number,
        r.floor_number,
        r.description AS room_description,

        rt.type_name,
        rt.capacity,

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
   BOOKING NOT FOUND
===================================================== */

if (!$booking) {
    header("Location: invoices.php");
    exit;
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
        Invoice <?= e($booking["booking_code"]) ?> - Velora Hotel
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

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f3f1ea;
            color: #172033;
            font-family: Arial, Helvetica, sans-serif;
        }


        /* =================================================
           TOP ACTION BAR
        ================================================= */

        .action-bar {
            background: #0b1f3a;
            padding: 15px 20px;
        }

        .action-bar .btn {
            border-radius: 8px;
        }


        /* =================================================
           INVOICE
        ================================================= */

        .invoice-wrapper {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 15px;
        }

        .invoice {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.10);
            overflow: hidden;
        }


        /* =================================================
           HEADER
        ================================================= */

        .invoice-header {
            background: #0b1f3a;
            color: #ffffff;
            padding: 35px 40px;
        }

        .hotel-name {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: 2px;
            color: #d4af37;
        }

        .hotel-subtitle {
            color: #ffffff;
            opacity: 0.75;
            font-size: 13px;
            letter-spacing: 1px;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h2 {
            margin: 0;
            font-size: 30px;
            font-weight: 700;
        }

        .invoice-code {
            color: #d4af37;
            font-weight: 700;
            margin-top: 5px;
        }


        /* =================================================
           CONTENT
        ================================================= */

        .invoice-content {
            padding: 40px;
        }

        .section-title {
            color: #0b1f3a;
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }

        .guest-box {
            background: #faf8f1;
            border: 1px solid #e9dfc0;
            border-radius: 10px;
            padding: 18px;
        }

        .guest-name {
            font-size: 18px;
            font-weight: 700;
            color: #0b1f3a;
            margin-bottom: 7px;
        }

        .guest-info {
            color: #666;
            font-size: 14px;
            line-height: 1.8;
        }


        /* =================================================
           BOOKING DETAILS
        ================================================= */

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }

        .details-table th {
            background: #0b1f3a;
            color: #ffffff;
            padding: 13px;
            font-size: 13px;
            text-align: left;
        }

        .details-table td {
            padding: 14px 13px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .details-table tr:last-child td {
            border-bottom: none;
        }


        /* =================================================
           ROOM INFORMATION
        ================================================= */

        .room-box {
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            padding: 18px;
            margin-top: 25px;
        }

        .room-number {
            font-size: 22px;
            font-weight: 800;
            color: #0b1f3a;
        }

        .room-type {
            color: #b38b20;
            font-weight: 700;
        }


        /* =================================================
           TOTAL
        ================================================= */

        .total-section {
            margin-top: 30px;
            margin-left: auto;
            max-width: 380px;
        }

        .amount-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            color: #555;
        }

        .grand-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            padding: 18px;
            background: #faf7ed;
            border: 1px solid #e5d59e;
            border-radius: 10px;
        }

        .grand-total-label {
            font-size: 15px;
            font-weight: 700;
            color: #0b1f3a;
        }

        .grand-total-price {
            font-size: 25px;
            font-weight: 800;
            color: #b38b20;
        }


        /* =================================================
           STATUS
        ================================================= */

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-checked {
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

        .payment-paid {
            background: #d1e7dd;
            color: #0f5132;
        }

        .payment-partial {
            background: #fff3cd;
            color: #856404;
        }

        .payment-pending {
            background: #f8d7da;
            color: #842029;
        }


        /* =================================================
           FOOTER
        ================================================= */

        .invoice-footer {
            background: #fafafa;
            border-top: 1px solid #eee;
            padding: 25px 40px;
            text-align: center;
            color: #777;
            font-size: 13px;
        }

        .thank-you {
            color: #0b1f3a;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 5px;
        }


        /* =================================================
           PRINT
        ================================================= */

        @media print {

            body {
                background: #ffffff;
            }

            .action-bar {
                display: none !important;
            }

            .invoice-wrapper {
                max-width: 100%;
                margin: 0;
                padding: 0;
            }

            .invoice {
                box-shadow: none;
                border-radius: 0;
            }

            .invoice-header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .details-table th,
            .grand-total,
            .guest-box {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

        }


        /* =================================================
           MOBILE
        ================================================= */

        @media (max-width: 768px) {

            .invoice-header {
                padding: 25px 20px;
            }

            .invoice-content {
                padding: 25px 20px;
            }

            .invoice-footer {
                padding: 20px;
            }

            .invoice-title {
                text-align: left;
                margin-top: 20px;
            }

            .hotel-name {
                font-size: 26px;
            }

            .details-table {
                font-size: 12px;
            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     ACTION BAR
===================================================== -->

<div class="action-bar">

    <div class="container d-flex justify-content-between align-items-center">

        <a
            href="invoices.php"
            class="btn btn-outline-light"
        >
            <i class="bi bi-arrow-left"></i>
            Back to Invoices
        </a>

        <button
            onclick="window.print()"
            class="btn btn-warning"
        >
            <i class="bi bi-printer"></i>
            Print Invoice
        </button>

    </div>

</div>


<!-- =====================================================
     INVOICE
===================================================== -->

<div class="invoice-wrapper">

    <div class="invoice">


        <!-- =================================================
             HEADER
        ================================================= -->

        <div class="invoice-header">

            <div class="row align-items-center">

                <div class="col-md-7">

                    <div class="hotel-name">
                        VELORA HOTEL
                    </div>

                    <div class="hotel-subtitle">
                        LUXURY • COMFORT • EXPERIENCE
                    </div>

                </div>

                <div class="col-md-5">

                    <div class="invoice-title">

                        <h2>
                            INVOICE
                        </h2>

                        <div class="invoice-code">
                            <?= e($booking["booking_code"]) ?>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             CONTENT
        ================================================= -->

        <div class="invoice-content">


            <!-- =================================================
                 GUEST INFORMATION
            ================================================= -->

            <div class="row g-4">

                <div class="col-md-6">

                    <div class="section-title">
                        Guest Information
                    </div>

                    <div class="guest-box">

                        <div class="guest-name">

                            <?= e($booking["full_name"]) ?>

                        </div>

                        <div class="guest-info">

                            <i class="bi bi-envelope"></i>
                            <?= e($booking["email"]) ?>

                            <br>

                            <?php if (!empty($booking["phone"])): ?>

                                <i class="bi bi-telephone"></i>
                                <?= e($booking["phone"]) ?>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     INVOICE INFORMATION
                ================================================= -->

                <div class="col-md-6">

                    <div class="section-title">
                        Invoice Information
                    </div>

                    <div class="guest-box">

                        <div class="guest-info">

                            <strong>Booking Code:</strong>
                            <?= e($booking["booking_code"]) ?>

                            <br>

                            <strong>Invoice Date:</strong>
                            <?= date(
                                "d M Y",
                                strtotime($booking["created_at"])
                            ) ?>

                            <br>

                            <strong>Booking Status:</strong>

                            <?php

                            $booking_status =
                                strtolower($booking["booking_status"]);

                            $status_class = "status-pending";

                            if ($booking_status === "confirmed") {
                                $status_class = "status-confirmed";
                            } elseif ($booking_status === "checked_in") {
                                $status_class = "status-checked";
                            } elseif ($booking_status === "checked_out") {
                                $status_class = "status-completed";
                            } elseif ($booking_status === "cancelled") {
                                $status_class = "status-cancelled";
                            }

                            ?>

                            <span class="status <?= $status_class ?>">

                                <?= ucwords(
                                    str_replace(
                                        "_",
                                        " ",
                                        $booking["booking_status"]
                                    )
                                ) ?>

                            </span>

                            <br>

                            <strong>Payment:</strong>

                            <?php

                            $payment_status =
                                strtolower($booking["payment_status"]);

                            $payment_class = "payment-pending";

                            if ($payment_status === "paid") {
                                $payment_class = "payment-paid";
                            } elseif ($payment_status === "partial") {
                                $payment_class = "payment-partial";
                            }

                            ?>

                            <span class="status <?= $payment_class ?>">

                                <?= ucfirst($booking["payment_status"]) ?>

                            </span>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 ROOM INFORMATION
            ================================================= -->

            <div class="room-box">

                <div class="section-title">
                    Accommodation Details
                </div>

                <div class="row">

                    <div class="col-md-6">

                        <div class="room-number">

                            Room <?= e($booking["room_number"]) ?>

                        </div>

                        <div class="room-type">

                            <?= e($booking["type_name"]) ?>

                        </div>

                        <div class="text-muted mt-2">

                            <i class="bi bi-people"></i>

                            Capacity:
                            <?= e($booking["capacity"]) ?> guests

                        </div>

                    </div>

                    <div class="col-md-6">

                        <div class="text-muted">

                            <strong>Check-in:</strong>

                            <?= date(
                                "d M Y",
                                strtotime($booking["check_in"])
                            ) ?>

                        </div>

                        <div class="text-muted mt-2">

                            <strong>Check-out:</strong>

                            <?= date(
                                "d M Y",
                                strtotime($booking["check_out"])
                            ) ?>

                        </div>

                        <div class="text-muted mt-2">

                            <strong>Stay:</strong>

                            <?= e($booking["number_of_nights"]) ?>

                            night(s)

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 PRICE TABLE
            ================================================= -->

            <table class="details-table">

                <thead>

                    <tr>

                        <th>
                            Description
                        </th>

                        <th>
                            Quantity
                        </th>

                        <th>
                            Rate
                        </th>

                        <th class="text-end">
                            Amount
                        </th>

                    </tr>

                </thead>

                <tbody>

                    <tr>

                        <td>

                            <?= e($booking["type_name"]) ?>

                            - Room
                            <?= e($booking["room_number"]) ?>

                        </td>

                        <td>

                            <?= e($booking["number_of_nights"]) ?>

                            night(s)

                        </td>

                        <td>

                            LKR
                            <?= number_format(
                                $booking["room_price"],
                                2
                            ) ?>

                        </td>

                        <td class="text-end">

                            LKR
                            <?= number_format(
                                $booking["total_amount"],
                                2
                            ) ?>

                        </td>

                    </tr>

                </tbody>

            </table>


            <!-- =================================================
                 GUESTS
            ================================================= -->

            <div class="row mt-4">

                <div class="col-md-6">

                    <div class="section-title">
                        Guest Details
                    </div>

                    <p class="mb-1">

                        <strong>Adults:</strong>

                        <?= e($booking["adults"]) ?>

                    </p>

                    <p class="mb-1">

                        <strong>Children:</strong>

                        <?= e($booking["children"]) ?>

                    </p>

                </div>


                <!-- =================================================
                     TOTAL
                ================================================= -->

                <div class="col-md-6">

                    <div class="total-section">

                        <div class="amount-row">

                            <span>
                                Room Charges
                            </span>

                            <span>
                                LKR
                                <?= number_format(
                                    $booking["total_amount"],
                                    2
                                ) ?>
                            </span>

                        </div>

                        <div class="grand-total">

                            <span class="grand-total-label">
                                TOTAL
                            </span>

                            <span class="grand-total-price">

                                LKR
                                <?= number_format(
                                    $booking["total_amount"],
                                    2
                                ) ?>

                            </span>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 SPECIAL REQUEST
            ================================================= -->

            <?php if (!empty($booking["special_request"])): ?>

                <div class="mt-4">

                    <div class="section-title">
                        Special Request
                    </div>

                    <div class="guest-box">

                        <?= nl2br(
                            e($booking["special_request"])
                        ) ?>

                    </div>

                </div>

            <?php endif; ?>


        </div>


        <!-- =================================================
             FOOTER
        ================================================= -->

        <div class="invoice-footer">

            <div class="thank-you">
                Thank you for choosing Velora Hotel!
            </div>

            We look forward to welcoming you.

            <br>

            <span>
                Please keep this invoice for your records.
            </span>

        </div>


    </div>

</div>


</body>

</html>