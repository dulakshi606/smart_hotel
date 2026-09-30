<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

startSession();
requireRole("admin");

/*
|--------------------------------------------------------------------------
| GET ORDERS
|--------------------------------------------------------------------------
| Adjust the selected columns only if your service_orders table uses
| different column names.
*/

$sql = "
    SELECT 
        so.id,
        so.customer_id,
        so.service_id,
        so.quantity,
        so.total_amount,
        so.status,
        so.created_at,

        u.full_name AS customer_name,

        s.service_name,

        b.booking_code,
        r.room_number

    FROM service_orders so

    LEFT JOIN users u 
        ON so.customer_id = u.id

    LEFT JOIN services s 
        ON so.service_id = s.id

    LEFT JOIN bookings b 
        ON so.booking_id = b.id

    LEFT JOIN rooms r 
        ON b.room_id = r.id

    ORDER BY so.created_at DESC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $orders = [];
    $error = $e->getMessage();
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

    <title>Orders - Velora Hotel</title>

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
            --warning: #f59e0b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--background);
            color: var(--text);
            font-family:
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Arial,
                sans-serif;
        }

        /* =====================================================
           NAVBAR
        ===================================================== */

        .velora-navbar {
            background: var(--navy);
            min-height: 76px;
            box-shadow: 0 3px 15px rgba(15, 23, 42, 0.12);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            color: #ffffff !important;
            font-weight: 700;
            font-size: 21px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gold);
            color: var(--navy);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
        }

        .navbar-nav .nav-link {
            color: #d7dce5 !important;
            font-weight: 500;
            padding: 27px 14px;
            position: relative;
            transition: 0.2s ease;
        }

        .navbar-nav .nav-link:hover {
            color: #ffffff !important;
            background: var(--navy-hover);
        }

        .navbar-nav .nav-link.active {
            color: var(--gold) !important;
        }

        .navbar-nav .nav-link.active::after {
            content: "";
            position: absolute;
            bottom: 14px;
            left: 14px;
            right: 14px;
            height: 2px;
            background: var(--gold);
            border-radius: 5px;
        }

        .admin-user {
            color: #d7dce5;
            padding: 0 14px;
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 14px;
        }

        .admin-user i {
            color: var(--gold);
        }

        /* =====================================================
           PAGE
        ===================================================== */

        .page-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 35px 25px 50px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 15px;
        }

        .page-title {
            margin: 0;
            font-size: 30px;
            font-weight: 700;
            color: var(--navy);
        }

        .page-subtitle {
            margin-top: 5px;
            color: var(--muted);
            font-size: 14px;
        }

        /* =====================================================
           CARD
        ===================================================== */

        .orders-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 18px rgba(15, 23, 42, 0.05);
        }

        .card-header-custom {
            padding: 20px 22px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header-title {
            font-weight: 700;
            color: var(--navy);
            margin: 0;
        }

        /* =====================================================
           TABLE
        ===================================================== */

        .table {
            margin-bottom: 0;
            vertical-align: middle;
        }

        .table thead th {
            background: var(--navy);
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
            padding: 14px 15px;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 15px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }

        .table tbody tr:hover {
            background: #faf9f5;
        }

        .order-id {
            font-weight: 700;
            color: var(--gold-dark);
        }

        .customer-name {
            font-weight: 600;
            color: var(--navy);
        }

        .service-name {
            font-weight: 600;
            color: var(--text);
        }

        .amount {
            font-weight: 700;
            color: var(--navy);
        }

        .badge-status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-processing {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-completed {
            background: #d1e7dd;
            color: #146c43;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #842029;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }

        .empty-state i {
            font-size: 45px;
            color: var(--gold);
            margin-bottom: 15px;
        }

        .error-box {
            background: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 991px) {

            .navbar-nav .nav-link {
                padding: 12px 15px;
            }

            .navbar-nav .nav-link.active::after {
                display: none;
            }

            .admin-user {
                padding: 12px 15px;
            }

            .page-header {
                align-items: flex-start;
                flex-direction: column;
            }

        }

        @media (max-width: 768px) {

            .page-container {
                padding: 25px 15px 40px;
            }

            .page-title {
                font-size: 25px;
            }

            .orders-card {
                border-radius: 12px;
            }

        }

    </style>

</head>

<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<nav class="navbar navbar-expand-lg velora-navbar">

    <div class="container-fluid px-4">

        <a
            class="navbar-brand"
            href="dashboard.php"
        >

            <span class="brand-icon">
                <i class="bi bi-building"></i>
            </span>

            Velora Hotel

        </a>


        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#adminNavbar"
            aria-controls="adminNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >

            <span class="navbar-toggler-icon"></span>

        </button>


        <div
            class="collapse navbar-collapse"
            id="adminNavbar"
        >

            <ul class="navbar-nav ms-auto align-items-lg-center">

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="dashboard.php"
                    >
                        <i class="bi bi-speedometer2 me-1"></i>
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
                        class="nav-link"
                        href="room_types.php"
                    >
                        <i class="bi bi-grid me-1"></i>
                        Room Types
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="customers.php"
                    >
                        <i class="bi bi-people me-1"></i>
                        Customers
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="bookings.php"
                    >
                        <i class="bi bi-calendar-check me-1"></i>
                        Bookings
                    </a>

                </li>


                <!-- ORDERS -->

                <li class="nav-item">

                    <a
                        class="nav-link active"
                        href="orders.php"
                    >
                        <i class="bi bi-receipt me-1"></i>
                        Orders
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="services.php"
                    >
                        <i class="bi bi-concierge-bell me-1"></i>
                        Services
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="facilities.php"
                    >
                        <i class="bi bi-stars me-1"></i>
                        Facilities
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="../auth/logout.php"
                    >
                        <i class="bi bi-box-arrow-right me-1"></i>
                        Logout
                    </a>

                </li>

            </ul>

        </div>

    </div>

</nav>


<!-- =========================================================
     PAGE CONTENT
========================================================= -->

<div class="page-container">


    <div class="page-header">

        <div>

            <h1 class="page-title">
                Customer Orders
            </h1>

            <div class="page-subtitle">
                View and manage orders placed by hotel customers.
            </div>

        </div>

    </div>


    <?php if (!empty($error)): ?>

        <div class="error-box">

            <i class="bi bi-exclamation-triangle me-2"></i>

            Unable to load orders.

        </div>

    <?php endif; ?>


    <div class="orders-card">


        <div class="card-header-custom">

            <h5 class="card-header-title">

                <i class="bi bi-receipt-cutoff me-2"></i>

                All Orders

            </h5>

            <span class="text-muted small">

                <?= count($orders); ?> orders

            </span>

        </div>


        <?php if (count($orders) > 0): ?>

            <div class="table-responsive">

                <table class="table">

                    <thead>

                        <tr>

                            <th>Order ID</th>

                            <th>Customer</th>

                            <th>Room</th>

                            <th>Service</th>

                            <th>Quantity</th>

                            <th>Total</th>

                            <th>Status</th>

                            <th>Date</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($orders as $order): ?>

                        <?php

                        $status = strtolower(
                            trim($order["status"] ?? "pending")
                        );

                        $statusClass = "status-" . $status;

                        ?>

                        <tr>

                            <td>

                                <span class="order-id">

                                    #<?= e($order["id"]); ?>

                                </span>

                            </td>


                            <td>

                                <span class="customer-name">

                                    <?= e(
                                        $order["customer_name"]
                                        ?? "Unknown Customer"
                                    ); ?>

                                </span>

                            </td>


                            <td>

                                <?php if (!empty($order["room_number"])): ?>

                                    Room <?= e($order["room_number"]); ?>

                                <?php else: ?>

                                    <span class="text-muted">
                                        -
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <span class="service-name">

                                    <?= e(
                                        $order["service_name"]
                                        ?? "Service"
                                    ); ?>

                                </span>

                            </td>


                            <td>

                                <?= e(
                                    $order["quantity"]
                                    ?? 1
                                ); ?>

                            </td>


                            <td>

                                <span class="amount">

                                    LKR
                                    <?= number_format(
                                        (float)(
                                            $order["total_amount"]
                                            ?? 0
                                        ),
                                        2
                                    ); ?>

                                </span>

                            </td>


                            <td>

                                <span
                                    class="badge-status <?= e($statusClass); ?>"
                                >

                                    <?= e($status); ?>

                                </span>

                            </td>


                            <td>

                                <?php

                                if (!empty($order["created_at"])) {

                                    echo date(
                                        "d M Y, h:i A",
                                        strtotime(
                                            $order["created_at"]
                                        )
                                    );

                                } else {

                                    echo "-";

                                }

                                ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty-state">

                <i class="bi bi-receipt"></i>

                <h5>
                    No Orders Found
                </h5>

                <p class="mb-0">
                    Customer orders will appear here when they place orders.
                </p>

            </div>

        <?php endif; ?>


    </div>

</div>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>