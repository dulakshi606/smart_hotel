<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

startSession();
requireRole("customer");

$customer_id = $_SESSION["user_id"];

/* =====================================================
   GET CUSTOMER BOOKINGS
===================================================== */

$stmt = $pdo->prepare("
    SELECT
        b.id,
        b.booking_code,
        b.check_in,
        b.check_out,
        b.number_of_nights,
        b.room_price,
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

$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   STATUS HELPERS
===================================================== */

function invoiceStatusClass($status)
{
    switch (strtolower($status)) {

        case "confirmed":
            return "status-confirmed";

        case "checked_in":
            return "status-checked";

        case "checked_out":
            return "status-completed";

        case "cancelled":
            return "status-cancelled";

        default:
            return "status-pending";
    }
}

function invoicePaymentClass($status)
{
    switch (strtolower($status)) {

        case "paid":
            return "payment-paid";

        case "partial":
            return "payment-partial";

        default:
            return "payment-pending";
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

    <title>My Invoices - Velora Hotel</title>

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

        .invoice-header {
            background: linear-gradient(
                135deg,
                #0b1f3a,
                #132f52
            );
            color: #ffffff;
            padding: 45px 0;
        }

        .invoice-header h1 {
            font-weight: 700;
        }

        .invoice-card {
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid #e7dfc8;
            box-shadow: 0 8px 25px rgba(11, 31, 58, 0.08);
            overflow: hidden;
            transition: 0.25s ease;
        }

        .invoice-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(11, 31, 58, 0.12);
        }

        .invoice-top {
            background: #0b1f3a;
            color: #ffffff;
            padding: 18px 22px;
        }

        .booking-code {
            color: #d4af37;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .invoice-body {
            padding: 24px;
        }

        .room-name {
            color: #0b1f3a;
            font-size: 20px;
            font-weight: 700;
        }

        .detail-label {
            color: #777;
            font-size: 13px;
            margin-bottom: 3px;
        }

        .detail-value {
            font-weight: 600;
            color: #172033;
        }

        .total-box {
            background: #faf7ed;
            border: 1px solid #e7d9aa;
            border-radius: 12px;
            padding: 15px;
        }

        .total-label {
            color: #777;
            font-size: 13px;
        }

        .total-price {
            color: #b38b20;
            font-size: 24px;
            font-weight: 800;
        }

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

        .btn-gold {
            background: #d4af37;
            color: #0b1f3a;
            border: none;
            font-weight: 700;
        }

        .btn-gold:hover {
            background: #b9972e;
            color: #ffffff;
        }

        .empty-box {
            background: #ffffff;
            border-radius: 18px;
            padding: 60px 20px;
            text-align: center;
            border: 1px solid #e7dfc8;
        }

        .empty-icon {
            font-size: 55px;
            color: #d4af37;
            margin-bottom: 15px;
        }

    </style>

</head>

<body>


<!-- =====================================================
     HEADER
===================================================== -->

<section class="invoice-header">

    <div class="container">

        <div class="d-flex justify-content-between align-items-center">

            <div>

                <h1 class="mb-2">
                    <i class="bi bi-receipt"></i>
                    My Invoices
                </h1>

                <p class="mb-0 text-white-50">
                    View your booking invoices and payment details.
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


<!-- =====================================================
     INVOICES
===================================================== -->

<div class="container py-5">

<?php if (empty($bookings)): ?>

    <div class="empty-box">

        <div class="empty-icon">
            <i class="bi bi-receipt"></i>
        </div>

        <h3>No Invoices Yet</h3>

        <p class="text-muted">
            You don't have any bookings yet.
        </p>

        <a
            href="rooms.php"
            class="btn btn-gold px-4"
        >
            <i class="bi bi-building"></i>
            Browse Rooms
        </a>

    </div>

<?php else: ?>

    <div class="row g-4">

        <?php foreach ($bookings as $booking): ?>

            <div class="col-lg-6">

                <div class="invoice-card">

                    <!-- TOP -->

                    <div class="invoice-top">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <small class="text-white-50">
                                    Booking Invoice
                                </small>

                                <div class="booking-code">
                                    <?= e($booking["booking_code"]) ?>
                                </div>

                            </div>

                            <i
                                class="bi bi-receipt-cutoff"
                                style="font-size: 30px; color:#d4af37;"
                            ></i>

                        </div>

                    </div>


                    <!-- BODY -->

                    <div class="invoice-body">

                        <div class="mb-4">

                            <div class="detail-label">
                                ROOM
                            </div>

                            <div class="room-name">
                                Room <?= e($booking["room_number"]) ?>
                            </div>

                            <div class="text-muted">
                                <?= e($booking["type_name"]) ?>
                            </div>

                        </div>


                        <div class="row g-3 mb-4">

                            <div class="col-6">

                                <div class="detail-label">
                                    CHECK-IN
                                </div>

                                <div class="detail-value">
                                    <?= date("d M Y", strtotime($booking["check_in"])) ?>
                                </div>

                            </div>

                            <div class="col-6">

                                <div class="detail-label">
                                    CHECK-OUT
                                </div>

                                <div class="detail-value">
                                    <?= date("d M Y", strtotime($booking["check_out"])) ?>
                                </div>

                            </div>

                            <div class="col-6">

                                <div class="detail-label">
                                    NIGHTS
                                </div>

                                <div class="detail-value">
                                    <?= e($booking["number_of_nights"]) ?>
                                </div>

                            </div>

                            <div class="col-6">

                                <div class="detail-label">
                                    ROOM PRICE / NIGHT
                                </div>

                                <div class="detail-value">
                                    LKR <?= number_format($booking["room_price"], 2) ?>
                                </div>

                            </div>

                        </div>


                        <!-- TOTAL -->

                        <div class="total-box mb-4">

                            <div class="d-flex justify-content-between align-items-center">

                                <div>

                                    <div class="total-label">
                                        TOTAL AMOUNT
                                    </div>

                                    <div class="total-price">
                                        LKR
                                        <?= number_format($booking["total_amount"], 2) ?>
                                    </div>

                                </div>

                                <i
                                    class="bi bi-cash-stack"
                                    style="font-size:32px; color:#d4af37;"
                                ></i>

                            </div>

                        </div>


                        <!-- STATUS -->

                        <div class="d-flex flex-wrap gap-2 mb-4">

                            <span
                                class="status <?= invoiceStatusClass($booking["booking_status"]) ?>"
                            >
                                <?= ucwords(str_replace("_", " ", $booking["booking_status"])) ?>
                            </span>

                            <span
                                class="status <?= invoicePaymentClass($booking["payment_status"]) ?>"
                            >
                                Payment:
                                <?= ucfirst($booking["payment_status"]) ?>
                            </span>

                        </div>


                        <!-- BUTTON -->

                        <a
                            href="invoice_view.php?id=<?= (int)$booking["id"] ?>"
                            class="btn btn-gold w-100"
                        >
                            <i class="bi bi-file-earmark-text"></i>
                            View Invoice
                        </a>

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