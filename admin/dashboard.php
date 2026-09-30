<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

requireRole("admin");


// =====================================================
// DASHBOARD STATISTICS
// =====================================================


// Total Rooms
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM rooms
");

$totalRooms = $stmt->fetch()["total"];


// Available Rooms
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM rooms
    WHERE status = 'available'
");

$availableRooms = $stmt->fetch()["total"];


// Occupied Rooms
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM rooms
    WHERE status = 'occupied'
");

$occupiedRooms = $stmt->fetch()["total"];


// Maintenance Rooms
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM rooms
    WHERE status = 'maintenance'
");

$maintenanceRooms = $stmt->fetch()["total"];


// Cleaning Rooms
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM rooms
    WHERE status = 'cleaning'
");

$cleaningRooms = $stmt->fetch()["total"];


// Total Customers
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'customer'
");

$totalCustomers = $stmt->fetch()["total"];


// Today's Bookings
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE DATE(created_at) = CURDATE()
");

$todayBookings = $stmt->fetch()["total"];


// Today's Check-ins
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE check_in = CURDATE()
    AND booking_status IN ('confirmed', 'checked_in')
");

$todayCheckins = $stmt->fetch()["total"];


// Today's Check-outs
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE check_out = CURDATE()
    AND booking_status IN ('checked_in', 'checked_out')
");

$todayCheckouts = $stmt->fetch()["total"];


// Total Revenue
$stmt = $pdo->query("
    SELECT COALESCE(SUM(amount), 0) AS total
    FROM payments
");

$totalRevenue = $stmt->fetch()["total"];


// =====================================================
// RECENT BOOKINGS
// =====================================================

$stmt = $pdo->query("
    SELECT
        b.id,
        b.booking_code,
        b.check_in,
        b.check_out,
        b.total_amount,
        b.booking_status,

        u.full_name AS customer_name,

        r.room_number,

        rt.type_name

    FROM bookings b

    INNER JOIN users u
        ON b.customer_id = u.id

    INNER JOIN rooms r
        ON b.room_id = r.id

    INNER JOIN room_types rt
        ON r.room_type_id = rt.id

    ORDER BY b.created_at DESC

    LIMIT 8
");

$recentBookings = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Dashboard - Velora Hotel</title>


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

        /* =====================================================
           VELORA ADMIN DASHBOARD
        ===================================================== */

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

        }


        body {

            margin: 0;

            background: var(--background);

            color: var(--text);

            font-family:
                Arial,
                Helvetica,
                sans-serif;

        }


        /* =====================================================
           NAVBAR
        ===================================================== */

        .velora-navbar {

            background: var(--navy);

            min-height: 76px;

            padding: 0;

            box-shadow:
                0 4px 18px rgba(0, 0, 0, 0.12);

            position: sticky;

            top: 0;

            z-index: 1000;

        }


        .velora-brand {

            display: flex;

            align-items: center;

            gap: 10px;

            color: white !important;

            font-size: 21px;

            font-weight: 700;

            text-decoration: none;

        }


        .velora-brand:hover {

            color: white !important;

        }


        .brand-icon {

            width: 42px;

            height: 42px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: var(--gold);

            color: var(--navy);

            border-radius: 50%;

            font-size: 20px;

        }


        .velora-navbar .nav-link {

            color: #cbd5e1 !important;

            font-size: 14px;

            font-weight: 500;

            padding: 27px 12px !important;

            display: flex;

            align-items: center;

            gap: 7px;

            transition: 0.3s;

            position: relative;

        }


        .velora-navbar .nav-link i {

            font-size: 15px;

        }


        .velora-navbar .nav-link:hover {

            color: var(--gold) !important;

        }


        .velora-navbar .nav-link.active {

            color: var(--gold) !important;

        }


        .velora-navbar .nav-link.active::after {

            content: "";

            position: absolute;

            left: 12px;

            right: 12px;

            bottom: 0;

            height: 3px;

            background: var(--gold);

            border-radius:
                3px
                3px
                0
                0;

        }


        .logout-nav {

            color: #fca5a5 !important;

        }


        .logout-nav:hover {

            color: #ff6b6b !important;

        }


        .velora-navbar .navbar-toggler {

            border:
                1px solid
                rgba(201, 162, 39, 0.5);

            padding: 8px 10px;

        }


        .velora-navbar .navbar-toggler:focus {

            box-shadow:
                0 0 0 3px
                rgba(201, 162, 39, 0.2);

        }


        .velora-navbar .navbar-toggler-icon {

            filter:
                brightness(0)
                invert(1);

        }


        /* =====================================================
           ADMIN USER AREA
        ===================================================== */

        .admin-user {

            display: flex;

            align-items: center;

            gap: 8px;

            margin-left: 12px;

            padding-left: 15px;

            border-left:
                1px solid
                rgba(255,255,255,0.15);

            color: #e2e8f0;

            font-size: 14px;

        }


        .admin-user i {

            color: var(--gold);

            font-size: 19px;

        }


        /* =====================================================
           MAIN CONTENT
        ===================================================== */

        .dashboard-container {

            max-width: 1500px;

            margin: 0 auto;

            padding:
                35px
                30px
                50px;

        }


        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .page-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 28px;

        }


        .page-title {

            margin: 0;

            font-size: 28px;

            font-weight: 700;

            color: var(--navy);

        }


        .page-subtitle {

            margin-top: 6px;

            color: var(--muted);

            font-size: 14px;

        }


        /* =====================================================
           WELCOME CARD
        ===================================================== */

        .welcome-card {

            background:
                linear-gradient(
                    135deg,
                    var(--navy),
                    #172a4d
                );

            border-radius: 18px;

            padding: 28px 30px;

            color: white;

            margin-bottom: 30px;

            box-shadow:
                0 8px 25px
                rgba(15, 23, 42, 0.15);

            position: relative;

            overflow: hidden;

        }


        .welcome-card::after {

            content: "";

            position: absolute;

            width: 180px;

            height: 180px;

            border-radius: 50%;

            background:
                rgba(201,162,39,0.12);

            right: -50px;

            top: -70px;

        }


        .welcome-card h3 {

            margin: 0 0 7px;

            font-size: 24px;

            font-weight: 700;

        }


        .welcome-card p {

            margin: 0;

            color: #cbd5e1;

            font-size: 14px;

        }


        .welcome-icon {

            position: absolute;

            right: 35px;

            top: 50%;

            transform: translateY(-50%);

            font-size: 65px;

            color:
                rgba(201,162,39,0.75);

        }


        /* =====================================================
           STAT CARDS
        ===================================================== */

        .stat-card {

            background: var(--surface);

            border: 1px solid var(--border);

            border-radius: 16px;

            padding: 22px;

            height: 100%;

            box-shadow:
                0 4px 15px
                rgba(15,23,42,0.05);

            transition:
                transform 0.25s,
                box-shadow 0.25s;

        }


        .stat-card:hover {

            transform: translateY(-4px);

            box-shadow:
                0 8px 24px
                rgba(15,23,42,0.10);

        }


        .stat-top {

            display: flex;

            align-items: flex-start;

            justify-content: space-between;

        }


        .stat-label {

            color: var(--muted);

            font-size: 13px;

            font-weight: 500;

        }


        .stat-value {

            margin-top: 7px;

            color: var(--navy);

            font-size: 29px;

            font-weight: 700;

        }


        .stat-icon {

            width: 48px;

            height: 48px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 12px;

            background: var(--gold-light);

            color: var(--gold-dark);

            font-size: 21px;

        }


        /* =====================================================
           GOLD STAT ICON VARIATIONS
        ===================================================== */

        .stat-icon.navy {

            background:
                rgba(15,23,42,0.08);

            color: var(--navy);

        }


        .stat-icon.green {

            background:
                #dcfce7;

            color:
                #15803d;

        }


        .stat-icon.orange {

            background:
                #fef3c7;

            color:
                #b45309;

        }


        .stat-icon.red {

            background:
                #fee2e2;

            color:
                #b91c1c;

        }


        /* =====================================================
           QUICK ACCESS
        ===================================================== */

        .section-title {

            color: var(--navy);

            font-size: 20px;

            font-weight: 700;

            margin-bottom: 18px;

        }


        .quick-card {

            display: flex;

            align-items: center;

            gap: 15px;

            background: white;

            border:
                1px solid
                var(--border);

            border-radius: 14px;

            padding: 17px;

            text-decoration: none;

            color: var(--text);

            height: 100%;

            transition: 0.25s;

        }


        .quick-card:hover {

            color: var(--text);

            border-color: var(--gold);

            transform: translateY(-3px);

            box-shadow:
                0 7px 20px
                rgba(15,23,42,0.08);

        }


        .quick-icon {

            width: 44px;

            height: 44px;

            flex-shrink: 0;

            display: flex;

            align-items: center;

            justify-content: center;

            background: var(--gold-light);

            color: var(--gold-dark);

            border-radius: 11px;

            font-size: 19px;

        }


        .quick-card strong {

            display: block;

            font-size: 14px;

        }


        .quick-card small {

            display: block;

            color: var(--muted);

            margin-top: 3px;

            font-size: 12px;

        }


        /* =====================================================
           RECENT BOOKINGS
        ===================================================== */

        .booking-card {

            background: white;

            border:
                1px solid
                var(--border);

            border-radius: 16px;

            box-shadow:
                0 4px 15px
                rgba(15,23,42,0.05);

            overflow: hidden;

        }


        .booking-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding:
                20px
                22px;

            border-bottom:
                1px solid
                var(--border);

        }


        .booking-header h5 {

            margin: 0;

            font-size: 18px;

            font-weight: 700;

            color: var(--navy);

        }


        .view-all {

            color: var(--gold-dark);

            font-size: 13px;

            font-weight: 600;

            text-decoration: none;

        }


        .view-all:hover {

            color: var(--gold);

        }


        .booking-table {

            margin: 0;

        }


        .booking-table thead th {

            background:
                #fafaf8;

            color: var(--muted);

            font-size: 12px;

            font-weight: 600;

            text-transform: uppercase;

            letter-spacing: 0.3px;

            padding:
                14px
                18px;

            border-bottom:
                1px solid
                var(--border);

            white-space: nowrap;

        }


        .booking-table tbody td {

            padding:
                15px
                18px;

            vertical-align: middle;

            border-color:
                #f0f1f3;

            font-size: 13px;

        }


        .booking-table tbody tr:hover {

            background:
                #fffdf5;

        }


        .booking-code {

            color: var(--navy);

            font-weight: 700;

        }


        .room-number {

            font-weight: 600;

            color: var(--navy);

        }


        .room-type {

            color: var(--muted);

            font-size: 11px;

        }


        .amount {

            color: var(--navy);

            font-weight: 700;

            white-space: nowrap;

        }


        /* =====================================================
           STATUS BADGES
        ===================================================== */

        .status-badge {

            display: inline-block;

            padding:
                6px
                10px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 600;

            white-space: nowrap;

        }


        .status-pending {

            background: #fef3c7;

            color: #92400e;

        }


        .status-confirmed {

            background: #dcfce7;

            color: #166534;

        }


        .status-checked-in {

            background: #dbeafe;

            color: #1e40af;

        }


        .status-checked-out {

            background: #e5e7eb;

            color: #374151;

        }


        .status-cancelled {

            background: #fee2e2;

            color: #991b1b;

        }


        /* =====================================================
           REVENUE CARD
        ===================================================== */

        .revenue-card {

            background:
                linear-gradient(
                    135deg,
                    #fffdf4,
                    #ffffff
                );

            border:
                1px solid
                #eadb9b;

            border-radius: 16px;

            padding: 24px;

            height: 100%;

        }


        .revenue-label {

            color: var(--muted);

            font-size: 13px;

        }


        .revenue-value {

            color: var(--navy);

            font-size: 27px;

            font-weight: 700;

            margin-top: 7px;

        }


        .revenue-icon {

            width: 48px;

            height: 48px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background: var(--gold-light);

            color: var(--gold-dark);

            font-size: 21px;

        }


        /* =====================================================
           FOOTER
        ===================================================== */

        .dashboard-footer {

            text-align: center;

            padding:
                25px 15px;

            color: var(--muted);

            font-size: 12px;

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 991px) {

            .velora-navbar {

                padding:
                    10px 0;

            }


            .velora-navbar .nav-link {

                padding:
                    12px 10px !important;

            }


            .velora-navbar
            .nav-link.active::after {

                display: none;

            }


            .velora-navbar
            .nav-link.active {

                background:
                    rgba(201,162,39,0.12);

                border-radius: 8px;

            }


            .velora-navbar .navbar-nav {

                padding-top: 10px;

                padding-bottom: 10px;

            }


            .admin-user {

                margin:
                    8px 0 0;

                padding:
                    12px 10px;

                border-left: none;

                border-top:
                    1px solid
                    rgba(255,255,255,0.12);

            }


            .dashboard-container {

                padding:
                    25px
                    18px
                    40px;

            }


            .page-title {

                font-size: 23px;

            }


            .welcome-card {

                padding: 24px;

            }


            .welcome-icon {

                display: none;

            }


        }


        @media (max-width: 575px) {

            .dashboard-container {

                padding:
                    20px
                    14px
                    35px;

            }


            .page-header {

                margin-bottom: 20px;

            }


            .welcome-card h3 {

                font-size: 21px;

            }


            .stat-value {

                font-size: 25px;

            }


            .booking-header {

                padding:
                    17px;

            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     TOP NAVBAR
===================================================== -->

<nav class="navbar navbar-expand-lg velora-navbar">

    <div class="container-fluid px-lg-4">


        <!-- BRAND -->

        <a
            class="navbar-brand velora-brand"
            href="dashboard.php"
        >

            <span class="brand-icon">

                <i class="bi bi-building"></i>

            </span>

            <span>
                Velora Hotel
            </span>

        </a>


        <!-- MOBILE BUTTON -->

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


        <!-- NAVIGATION -->

        <div
            class="collapse navbar-collapse"
            id="adminNavbar"
        >

            <ul class="navbar-nav ms-auto align-items-lg-center">


                <!-- DASHBOARD -->

                <li class="nav-item">

                    <a
                        class="nav-link active"
                        href="dashboard.php"
                    >

                        <i class="bi bi-grid-1x2-fill"></i>

                        Dashboard

                    </a>

                </li>


                <!-- ROOMS -->

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="rooms.php"
                    >

                        <i class="bi bi-door-open-fill"></i>

                        Rooms

                    </a>

                </li>


                <!-- ROOM TYPES -->

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="room_types.php"
                    >

                        <i class="bi bi-grid-fill"></i>

                        Room Types

                    </a>

                </li>


                <!-- CUSTOMERS -->

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="customers.php"
                    >

                        <i class="bi bi-people-fill"></i>

                        Customers

                    </a>

                </li>


                <!-- BOOKINGS -->

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="bookings.php"
                    >

                        <i class="bi bi-calendar-check-fill"></i>

                        Bookings

                    </a>

                </li>


                <!-- SERVICES -->

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="services.php"
                    >

                        <i class="bi bi-bell-fill"></i>

                        Services

                    </a>

                </li>


                <!-- ROOM FACILITIES -->

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="facilities.php"
                    >

                        <i class="bi bi-stars"></i>

                        Facilities

                    </a>

                </li>


                <!-- LOGOUT -->

                <li class="nav-item ms-lg-2">

                    <a
                        class="nav-link logout-nav"
                        href="../auth/logout.php"
                    >

                        <i class="bi bi-box-arrow-right"></i>

                        Logout

                    </a>

                </li>


                <!-- ADMIN NAME -->

                <li class="nav-item">

                    <div class="admin-user">

                        <i class="bi bi-person-circle"></i>

                        <span>
                            <?= e($_SESSION["full_name"]) ?>
                        </span>

                    </div>

                </li>


            </ul>

        </div>

    </div>

</nav>



<!-- =====================================================
     MAIN DASHBOARD
===================================================== -->

<main class="dashboard-container">


    <!-- PAGE HEADER -->

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Admin Dashboard
            </h1>

            <div class="page-subtitle">

                Manage your hotel operations from one place.

            </div>

        </div>

    </div>



    <!-- =================================================
         WELCOME CARD
    ================================================== -->

    <div class="welcome-card">

        <h3>
            Welcome, <?= e($_SESSION["full_name"]) ?> 👋
        </h3>

        <p>
            Here's an overview of your Velora Hotel today.
        </p>


        <div class="welcome-icon">

            <i class="bi bi-buildings-fill"></i>

        </div>

    </div>



    <!-- =================================================
         HOTEL STATISTICS
    ================================================== -->

    <div class="section-title">
        Hotel Overview
    </div>


    <div class="row g-4">


        <!-- TOTAL ROOMS -->

        <div class="col-12 col-sm-6 col-xl-3">

            <div class="stat-card">

                <div class="stat-top">

                    <div>

                        <div class="stat-label">
                            Total Rooms
                        </div>

                        <div class="stat-value">
                            <?= $totalRooms ?>
                        </div>

                    </div>


                    <div class="stat-icon navy">

                        <i class="bi bi-building"></i>

                    </div>

                </div>

            </div>

        </div>



        <!-- AVAILABLE ROOMS -->

        <div class="col-12 col-sm-6 col-xl-3">

            <div class="stat-card">

                <div class="stat-top">

                    <div>

                        <div class="stat-label">
                            Available Rooms
                        </div>

                        <div class="stat-value">
                            <?= $availableRooms ?>
                        </div>

                    </div>


                    <div class="stat-icon green">

                        <i class="bi bi-check-circle-fill"></i>

                    </div>

                </div>

            </div>

        </div>



        <!-- OCCUPIED ROOMS -->

        <div class="col-12 col-sm-6 col-xl-3">

            <div class="stat-card">

                <div class="stat-top">

                    <div>

                        <div class="stat-label">
                            Occupied Rooms
                        </div>

                        <div class="stat-value">
                            <?= $occupiedRooms ?>
                        </div>

                    </div>


                    <div class="stat-icon orange">

                        <i class="bi bi-person-fill"></i>

                    </div>

                </div>

            </div>

        </div>



        <!-- CLEANING ROOMS -->

        <div class="col-12 col-sm-6 col-xl-3">

            <div class="stat-card">

                <div class="stat-top">

                    <div>

                        <div class="stat-label">
                            Cleaning
                        </div>

                        <div class="stat-value">
                            <?= $cleaningRooms ?>
                        </div>

                    </div>


                    <div class="stat-icon">

                        <i class="bi bi-stars"></i>

                    </div>

                </div>

            </div>

        </div>


    </div>



    <!-- =================================================
         SECOND STATISTICS ROW
    ================================================== -->

    <div class="row g-4 mt-1">


        <!-- CUSTOMERS -->

        <div class="col-12 col-sm-6 col-xl-3">

            <div class="stat-card">

                <div class="stat-top">

                    <div>

                        <div class="stat-label">
                            Total Customers
                        </div>

                        <div class="stat-value">
                            <?= $totalCustomers ?>
                        </div>

                    </div>


                    <div class="stat-icon navy">

                        <i class="bi bi-people-fill"></i>

                    </div>

                </div>

            </div>

        </div>



        <!-- TODAY BOOKINGS -->

        <div class="col-12 col-sm-6 col-xl-3">

            <div class="stat-card">

                <div class="stat-top">

                    <div>

                        <div class="stat-label">
                            Today's Bookings
                        </div>

                        <div class="stat-value">
                            <?= $todayBookings ?>
                        </div>

                    </div>


                    <div class="stat-icon">

                        <i class="bi bi-calendar-check-fill"></i>

                    </div>

                </div>

            </div>

        </div>



        <!-- TODAY CHECKINS -->

        <div class="col-12 col-sm-6 col-xl-3">

            <div class="stat-card">

                <div class="stat-top">

                    <div>

                        <div class="stat-label">
                            Today's Check-ins
                        </div>

                        <div class="stat-value">
                            <?= $todayCheckins ?>
                        </div>

                    </div>


                    <div class="stat-icon green">

                        <i class="bi bi-box-arrow-in-right"></i>

                    </div>

                </div>

            </div>

        </div>



        <!-- TODAY CHECKOUTS -->

        <div class="col-12 col-sm-6 col-xl-3">

            <div class="stat-card">

                <div class="stat-top">

                    <div>

                        <div class="stat-label">
                            Today's Check-outs
                        </div>

                        <div class="stat-value">
                            <?= $todayCheckouts ?>
                        </div>

                    </div>


                    <div class="stat-icon orange">

                        <i class="bi bi-box-arrow-right"></i>

                    </div>

                </div>

            </div>

        </div>


    </div>



    <!-- =================================================
         REVENUE + MAINTENANCE
    ================================================== -->

    <div class="row g-4 mt-1">


        <!-- TOTAL REVENUE -->

        <div class="col-12 col-lg-6">

            <div class="revenue-card">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <div class="revenue-label">
                            Total Revenue
                        </div>

                        <div class="revenue-value">

                            Rs.
                            <?= number_format(
                                $totalRevenue,
                                2
                            ) ?>

                        </div>

                    </div>


                    <div class="revenue-icon">

                        <i class="bi bi-cash-stack"></i>

                    </div>

                </div>

            </div>

        </div>



        <!-- MAINTENANCE -->

        <div class="col-12 col-lg-6">

            <div class="revenue-card">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <div class="revenue-label">
                            Rooms Under Maintenance
                        </div>

                        <div class="revenue-value">

                            <?= $maintenanceRooms ?>

                        </div>

                    </div>


                    <div class="revenue-icon">

                        <i class="bi bi-tools"></i>

                    </div>

                </div>

            </div>

        </div>


    </div>



    <!-- =================================================
         QUICK ACCESS
    ================================================== -->

    <div class="section-title mt-5">
        Quick Access
    </div>


    <div class="row g-3">


        <!-- ROOMS -->

        <div class="col-12 col-sm-6 col-lg-3">

            <a
                href="rooms.php"
                class="quick-card"
            >

                <div class="quick-icon">

                    <i class="bi bi-door-open-fill"></i>

                </div>

                <div>

                    <strong>
                        Manage Rooms
                    </strong>

                    <small>
                        Add and manage hotel rooms
                    </small>

                </div>

            </a>

        </div>



        <!-- ROOM TYPES -->

        <div class="col-12 col-sm-6 col-lg-3">

            <a
                href="room_types.php"
                class="quick-card"
            >

                <div class="quick-icon">

                    <i class="bi bi-grid-fill"></i>

                </div>

                <div>

                    <strong>
                        Room Types
                    </strong>

                    <small>
                        Manage room categories
                    </small>

                </div>

            </a>

        </div>



        <!-- CUSTOMERS -->

        <div class="col-12 col-sm-6 col-lg-3">

            <a
                href="customers.php"
                class="quick-card"
            >

                <div class="quick-icon">

                    <i class="bi bi-people-fill"></i>

                </div>

                <div>

                    <strong>
                        Customers
                    </strong>

                    <small>
                        View registered customers
                    </small>

                </div>

            </a>

        </div>



        <!-- BOOKINGS -->

        <div class="col-12 col-sm-6 col-lg-3">

            <a
                href="bookings.php"
                class="quick-card"
            >

                <div class="quick-icon">

                    <i class="bi bi-calendar-check-fill"></i>

                </div>

                <div>

                    <strong>
                        Bookings
                    </strong>

                    <small>
                        Manage customer bookings
                    </small>

                </div>

            </a>

        </div>



        <!-- SERVICES -->

        <div class="col-12 col-sm-6 col-lg-3">

            <a
                href="services.php"
                class="quick-card"
            >

                <div class="quick-icon">

                    <i class="bi bi-bell-fill"></i>

                </div>

                <div>

                    <strong>
                        Services
                    </strong>

                    <small>
                        Manage hotel services
                    </small>

                </div>

            </a>

        </div>



        <!-- FACILITIES -->

        <div class="col-12 col-sm-6 col-lg-3">

            <a
                href="facilities.php"
                class="quick-card"
            >

                <div class="quick-icon">

                    <i class="bi bi-stars"></i>

                </div>

                <div>

                    <strong>
                        Room Facilities
                    </strong>

                    <small>
                        Manage room facilities
                    </small>

                </div>

            </a>

        </div>



        <!-- CHECK INS -->

        <div class="col-12 col-sm-6 col-lg-3">

            <a
                href="bookings.php"
                class="quick-card"
            >

                <div class="quick-icon">

                    <i class="bi bi-box-arrow-in-right"></i>

                </div>

                <div>

                    <strong>
                        Check-ins
                    </strong>

                    <small>
                        View today's check-ins
                    </small>

                </div>

            </a>

        </div>



        <!-- CHECK OUTS -->

        <div class="col-12 col-sm-6 col-lg-3">

            <a
                href="bookings.php"
                class="quick-card"
            >

                <div class="quick-icon">

                    <i class="bi bi-box-arrow-right"></i>

                </div>

                <div>

                    <strong>
                        Check-outs
                    </strong>

                    <small>
                        View today's check-outs
                    </small>

                </div>

            </a>

        </div>


    </div>



    <!-- =================================================
         RECENT BOOKINGS
    ================================================== -->

    <div class="section-title mt-5">
        Recent Bookings
    </div>


    <div class="booking-card">


        <!-- HEADER -->

        <div class="booking-header">

            <h5>
                Latest Reservations
            </h5>


            <a
                href="bookings.php"
                class="view-all"
            >

                View All
                <i class="bi bi-arrow-right"></i>

            </a>

        </div>



        <!-- TABLE -->

        <div class="table-responsive">

            <table class="table booking-table align-middle">

                <thead>

                    <tr>

                        <th>
                            Booking
                        </th>

                        <th>
                            Customer
                        </th>

                        <th>
                            Room
                        </th>

                        <th>
                            Check-in
                        </th>

                        <th>
                            Check-out
                        </th>

                        <th>
                            Amount
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (count($recentBookings) > 0): ?>


                    <?php foreach ($recentBookings as $booking): ?>


                        <?php

                        $status =
                            $booking["booking_status"];


                        $statusClass = match ($status) {

                            "pending" =>
                                "status-pending",

                            "confirmed" =>
                                "status-confirmed",

                            "checked_in" =>
                                "status-checked-in",

                            "checked_out" =>
                                "status-checked-out",

                            "cancelled" =>
                                "status-cancelled",

                            default =>
                                "status-pending"

                        };


                        $statusText =
                            ucfirst(
                                str_replace(
                                    "_",
                                    " ",
                                    $status
                                )
                            );

                        ?>


                        <tr>


                            <!-- BOOKING -->

                            <td>

                                <span class="booking-code">

                                    <?= e(
                                        $booking["booking_code"]
                                    ) ?>

                                </span>

                            </td>



                            <!-- CUSTOMER -->

                            <td>

                                <?= e(
                                    $booking["customer_name"]
                                ) ?>

                            </td>



                            <!-- ROOM -->

                            <td>

                                <div class="room-number">

                                    Room
                                    <?= e(
                                        $booking["room_number"]
                                    ) ?>

                                </div>


                                <div class="room-type">

                                    <?= e(
                                        $booking["type_name"]
                                    ) ?>

                                </div>

                            </td>



                            <!-- CHECK IN -->

                            <td>

                                <?= e(
                                    $booking["check_in"]
                                ) ?>

                            </td>



                            <!-- CHECK OUT -->

                            <td>

                                <?= e(
                                    $booking["check_out"]
                                ) ?>

                            </td>



                            <!-- AMOUNT -->

                            <td>

                                <span class="amount">

                                    Rs.
                                    <?= number_format(
                                        $booking["total_amount"],
                                        2
                                    ) ?>

                                </span>

                            </td>



                            <!-- STATUS -->

                            <td>

                                <span
                                    class="
                                        status-badge
                                        <?= $statusClass ?>
                                    "
                                >

                                    <?= e(
                                        $statusText
                                    ) ?>

                                </span>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="7"
                            class="text-center py-5 text-muted"
                        >

                            <i
                                class="bi bi-calendar-x"
                                style="font-size: 30px;"
                            ></i>

                            <div class="mt-2">

                                No bookings yet.

                            </div>

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </div>



</main>



<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="dashboard-footer">

    © <?= date("Y") ?> Velora Hotel.
    All Rights Reserved.

</footer>



<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>