<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

startSession();
requireRole("customer");

$customerName = $_SESSION["full_name"] ?? "Guest";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Customer Dashboard - Velora Hotel</title>


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

        /* =====================================================
           VELORA HOTEL THEME
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

            --success: #198754;
            --danger: #dc3545;

        }


        /* =====================================================
           GLOBAL
        ===================================================== */

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family:
                "Segoe UI",
                Arial,
                sans-serif;

            background: var(--background);

            color: var(--text);

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


        /* =====================================================
           BRAND
        ===================================================== */

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


        /* =====================================================
           NAVIGATION LINKS
        ===================================================== */

        .velora-navbar .nav-link {

            color: #cbd5e1 !important;

            font-size: 14px;

            font-weight: 500;

            padding: 27px 13px !important;

            display: flex;

            align-items: center;

            gap: 7px;

            transition:
                color 0.3s ease,
                background 0.3s ease;

            position: relative;

        }


        .velora-navbar .nav-link i {

            font-size: 15px;

        }


        /* HOVER */

        .velora-navbar .nav-link:hover {

            color: var(--gold) !important;

        }


        /* ACTIVE */

        .velora-navbar .nav-link.active {

            color: var(--gold) !important;

        }


        /* ACTIVE GOLD LINE */

        .velora-navbar .nav-link.active::after {

            content: "";

            position: absolute;

            left: 13px;

            right: 13px;

            bottom: 0;

            height: 3px;

            background: var(--gold);

            border-radius:
                3px 3px 0 0;

        }


        /* =====================================================
           LOGOUT
        ===================================================== */

        .velora-navbar .logout-nav {

            color: #fca5a5 !important;

        }


        .velora-navbar .logout-nav:hover {

            color: #ff6b6b !important;

        }


        /* =====================================================
           MOBILE NAVBAR BUTTON
        ===================================================== */

        .velora-navbar .navbar-toggler {

            border:
                1px solid rgba(201, 162, 39, 0.5);

            padding: 8px 10px;

        }


        .velora-navbar .navbar-toggler:focus {

            box-shadow:
                0 0 0 3px
                rgba(201, 162, 39, 0.2);

        }


        .velora-navbar .navbar-toggler-icon {

            filter:
                brightness(0) invert(1);

        }


        /* =====================================================
           MAIN CONTENT
        ===================================================== */

        .main-content {

            padding: 35px;

            max-width: 1600px;

            margin: 0 auto;

        }


        /* =====================================================
           TOP BAR
        ===================================================== */

        .topbar {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 30px;

        }


        .topbar h2 {

            margin: 0;

            font-weight: 700;

            color: var(--navy);

        }


        .topbar p {

            margin: 5px 0 0;

            color: var(--muted);

        }


        /* =====================================================
           USER BOX
        ===================================================== */

        .user-box {

            display: flex;

            align-items: center;

            gap: 10px;

            background: white;

            padding: 10px 15px;

            border-radius: 10px;

            border:
                1px solid var(--border);

        }


        .user-icon {

            width: 38px;

            height: 38px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background: var(--gold-light);

            color: var(--gold-dark);

        }


        /* =====================================================
           WELCOME CARD
        ===================================================== */

        .welcome-card {

            background:
                linear-gradient(
                    135deg,
                    #0f172a,
                    #172033
                );

            border-radius: 18px;

            padding: 35px;

            color: white;

            position: relative;

            overflow: hidden;

            margin-bottom: 30px;

        }


        .welcome-card::after {

            content: "";

            position: absolute;

            width: 220px;

            height: 220px;

            right: -70px;

            top: -70px;

            border-radius: 50%;

            border:
                35px solid
                rgba(201,162,39,0.15);

        }


        .welcome-card h1 {

            font-size: 30px;

            font-weight: 700;

            margin-bottom: 10px;

            position: relative;

            z-index: 1;

        }


        .welcome-card p {

            color: #cbd5e1;

            margin-bottom: 20px;

            max-width: 750px;

            position: relative;

            z-index: 1;

        }


        /* =====================================================
           PRIMARY BUTTON
        ===================================================== */

        .primary-btn {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            background: var(--gold);

            color: var(--navy);

            padding: 11px 20px;

            border-radius: 8px;

            text-decoration: none;

            font-weight: 600;

            transition: 0.3s;

            position: relative;

            z-index: 1;

        }


        .primary-btn:hover {

            background: #e1bd3d;

            color: var(--navy);

            transform: translateY(-2px);

        }


        /* =====================================================
           DASHBOARD CARDS
        ===================================================== */

        .dashboard-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 20px;

            margin-bottom: 30px;

        }


        .dashboard-card {

            background: var(--surface);

            border:
                1px solid var(--border);

            border-radius: 15px;

            padding: 25px;

            text-decoration: none;

            color: var(--text);

            transition: 0.3s;

            position: relative;

            overflow: hidden;

        }


        .dashboard-card:hover {

            transform: translateY(-5px);

            border-color: var(--gold);

            box-shadow:
                0 10px 25px
                rgba(0,0,0,0.08);

            color: var(--text);

        }


        .icon-box {

            width: 55px;

            height: 55px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: var(--gold-light);

            color: var(--gold-dark);

            font-size: 24px;

            margin-bottom: 18px;

        }


        .dashboard-card h4 {

            font-size: 18px;

            font-weight: 700;

            margin-bottom: 8px;

        }


        .dashboard-card p {

            color: var(--muted);

            font-size: 14px;

            margin: 0;

            padding-right: 15px;

        }


        .arrow {

            position: absolute;

            right: 20px;

            bottom: 20px;

            color: var(--gold);

            font-size: 20px;

        }


        /* =====================================================
           QUICK ACCESS
        ===================================================== */

        .section-title {

            font-size: 21px;

            font-weight: 700;

            color: var(--navy);

            margin-bottom: 15px;

        }


        .quick-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 15px;

            margin-bottom: 30px;

        }


        .quick-link {

            background: white;

            border:
                1px solid var(--border);

            border-radius: 12px;

            padding: 18px;

            display: flex;

            align-items: center;

            gap: 15px;

            text-decoration: none;

            color: var(--text);

            transition: 0.3s;

        }


        .quick-link:hover {

            border-color: var(--gold);

            color: var(--text);

            transform: translateY(-2px);

        }


        .quick-link i {

            color: var(--gold-dark);

            font-size: 22px;

        }


        .quick-link span {

            font-weight: 600;

        }


        /* =====================================================
           RECOMMENDATION
        ===================================================== */

        .recommend-card {

            background: white;

            border:
                1px solid var(--border);

            border-radius: 15px;

            padding: 25px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

        }


        .recommend-left {

            display: flex;

            align-items: center;

            gap: 20px;

        }


        .recommend-icon {

            width: 60px;

            height: 60px;

            border-radius: 14px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: var(--gold-light);

            color: var(--gold-dark);

            font-size: 26px;

            flex-shrink: 0;

        }


        .recommend-card h4 {

            margin: 0 0 5px;

            font-weight: 700;

        }


        .recommend-card p {

            margin: 0;

            color: var(--muted);

            font-size: 14px;

        }


        /* =====================================================
           FOOTER
        ===================================================== */

        .dashboard-footer {

            margin-top: 40px;

            padding: 25px 0 10px;

            border-top:
                1px solid var(--border);

            text-align: center;

            color: var(--muted);

            font-size: 14px;

        }


        .dashboard-footer strong {

            color: var(--gold-dark);

        }


        /* =====================================================
           LARGE SCREEN
        ===================================================== */

        @media (max-width: 1200px) {

            .dashboard-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

            .quick-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        /* =====================================================
           TABLET / MOBILE
        ===================================================== */

        @media (max-width: 991px) {

            .velora-navbar {

                padding: 10px 0;

            }


            .velora-navbar .nav-link {

                padding:
                    12px 10px !important;

            }


            .velora-navbar .nav-link.active::after {

                display: none;

            }


            .velora-navbar .nav-link.active {

                background:
                    rgba(201, 162, 39, 0.12);

                border-radius: 8px;

            }


            .velora-navbar .navbar-nav {

                padding-top: 10px;

                padding-bottom: 10px;

            }


            .velora-navbar .logout-nav {

                margin-top: 5px;

            }

        }


        /* =====================================================
           SMALL MOBILE
        ===================================================== */

        @media (max-width: 768px) {

            .main-content {

                padding: 20px;

            }


            .topbar {

                flex-direction: column;

                align-items: flex-start;

                gap: 15px;

            }


            .topbar h2 {

                font-size: 24px;

            }


            .user-box {

                width: 100%;

            }


            .dashboard-grid,
            .quick-grid {

                grid-template-columns: 1fr;

            }


            .welcome-card {

                padding: 25px;

            }


            .welcome-card h1 {

                font-size: 25px;

            }


            .recommend-card {

                flex-direction: column;

                align-items: flex-start;

            }


            .recommend-left {

                align-items: flex-start;

            }


            .recommend-card .primary-btn {

                width: 100%;

                justify-content: center;

            }

        }


        /* =====================================================
           VERY SMALL MOBILE
        ===================================================== */

        @media (max-width: 480px) {

            .velora-brand {

                font-size: 18px;

            }


            .brand-icon {

                width: 38px;

                height: 38px;

                font-size: 17px;

            }


            .main-content {

                padding: 15px;

            }


            .welcome-card {

                padding: 22px;

            }


            .dashboard-card {

                padding: 20px;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     COMMON VELORA NAVBAR
========================================================= -->

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


        <!-- MOBILE TOGGLE -->

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#customerNavbar"
            aria-controls="customerNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >

            <span class="navbar-toggler-icon"></span>

        </button>


        <!-- NAVIGATION -->

        <div
            class="collapse navbar-collapse"
            id="customerNavbar"
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


                <!-- BOOKINGS -->

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="bookings.php"
                    >

                        <i class="bi bi-calendar-check-fill"></i>

                        My Bookings

                    </a>

                </li>


                <!-- MEALS -->

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="meals.php"
                    >

                        <i class="bi bi-cup-hot-fill"></i>

                        Meals & Dining

                    </a>

                </li>


                <!-- SERVICES -->

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="service_requests.php"
                    >

                        <i class="bi bi-bell-fill"></i>

                        Services

                    </a>

                </li>


                <!-- PROFILE -->

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="profile.php"
                    >

                        <i class="bi bi-person-fill"></i>

                        Profile

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

            </ul>

        </div>

    </div>

</nav>



<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<div class="main-content">


    <!-- =====================================================
         TOP BAR
    ===================================================== -->

    <div class="topbar">

        <div>

            <h2>

                Customer Dashboard

            </h2>

            <p>

                Manage your hotel experience from one place.

            </p>

        </div>


        <!-- USER -->

        <div class="user-box">

            <div class="user-icon">

                <i class="bi bi-person-fill"></i>

            </div>

            <span>

                <?= e($customerName); ?>

            </span>

        </div>

    </div>



    <!-- =====================================================
         WELCOME CARD
    ===================================================== -->

    <div class="welcome-card">


        <h1>

            Welcome back,
            <?= e($customerName); ?>!

        </h1>


        <p>

            Discover comfortable rooms, manage your bookings,
            enjoy delicious meals, request hotel services
            and view your invoices.

        </p>


        <a
            href="rooms.php"
            class="primary-btn"
        >

            <i class="bi bi-search"></i>

            Browse Available Rooms

        </a>

    </div>



    <!-- =====================================================
         MAIN DASHBOARD CARDS
    ===================================================== -->

    <div class="dashboard-grid">


        <!-- ROOMS -->

        <a
            href="rooms.php"
            class="dashboard-card"
        >

            <div class="icon-box">

                <i class="bi bi-door-open-fill"></i>

            </div>


            <h4>

                Browse Rooms

            </h4>


            <p>

                Explore our available rooms and
                choose the perfect stay.

            </p>


            <span class="arrow">

                <i class="bi bi-arrow-right"></i>

            </span>

        </a>



        <!-- BOOKINGS -->

        <a
            href="bookings.php"
            class="dashboard-card"
        >

            <div class="icon-box">

                <i class="bi bi-calendar-check-fill"></i>

            </div>


            <h4>

                My Bookings

            </h4>


            <p>

                View, edit or cancel your hotel bookings.

            </p>


            <span class="arrow">

                <i class="bi bi-arrow-right"></i>

            </span>

        </a>



        <!-- MEALS -->

        <a
            href="meals.php"
            class="dashboard-card"
        >

            <div class="icon-box">

                <i class="bi bi-cup-hot-fill"></i>

            </div>


            <h4>

                Meals & Dining

            </h4>


            <p>

                Explore delicious meals, customize
                your choices and place a food order.

            </p>


            <span class="arrow">

                <i class="bi bi-arrow-right"></i>

            </span>

        </a>



        <!-- INVOICES -->

        <a
            href="invoices.php"
            class="dashboard-card"
        >

            <div class="icon-box">

                <i class="bi bi-receipt-cutoff"></i>

            </div>


            <h4>

                My Invoices

            </h4>


            <p>

                View your booking invoices and
                payment information.

            </p>


            <span class="arrow">

                <i class="bi bi-arrow-right"></i>

            </span>

        </a>



        <!-- SERVICES -->

        <a
            href="service_requests.php"
            class="dashboard-card"
        >

            <div class="icon-box">

                <i class="bi bi-bell-fill"></i>

            </div>


            <h4>

                Service Requests

            </h4>


            <p>

                Request housekeeping, laundry,
                room service and more.

            </p>


            <span class="arrow">

                <i class="bi bi-arrow-right"></i>

            </span>

        </a>

    </div>



    <!-- =====================================================
         QUICK ACCESS
    ===================================================== -->

    <h3 class="section-title">

        Quick Access

    </h3>


    <div class="quick-grid">


        <!-- MEALS -->

        <a
            href="meals.php"
            class="quick-link"
        >

            <i class="bi bi-cup-hot-fill"></i>

            <span>

                Meals & Dining

            </span>

        </a>



        <!-- INVOICES -->

        <a
            href="invoices.php"
            class="quick-link"
        >

            <i class="bi bi-receipt"></i>

            <span>

                My Invoices

            </span>

        </a>



        <!-- SERVICES -->

        <a
            href="service_requests.php"
            class="quick-link"
        >

            <i class="bi bi-bell"></i>

            <span>

                Service Requests

            </span>

        </a>



        <!-- PROFILE -->

        <a
            href="profile.php"
            class="quick-link"
        >

            <i class="bi bi-person-circle"></i>

            <span>

                My Profile

            </span>

        </a>

    </div>



    <!-- =====================================================
         SMART ROOM RECOMMENDATION
    ===================================================== -->

    <h3 class="section-title">

        Smart Recommendation

    </h3>


    <div class="recommend-card">


        <div class="recommend-left">


            <div class="recommend-icon">

                <i class="bi bi-stars"></i>

            </div>


            <div>

                <h4>

                    Find Your Ideal Room

                </h4>


                <p>

                    Get a room recommendation based on
                    your preferences and requirements.

                </p>

            </div>

        </div>


        <a
            href="recommend.php"
            class="primary-btn"
        >

            <i class="bi bi-magic"></i>

            Get Recommendation

        </a>

    </div>



    <!-- =====================================================
         FOOTER
    ===================================================== -->

    <div class="dashboard-footer">

        © <?= date("Y"); ?>

        <strong>Velora Hotel</strong>

        — Experience Comfort & Luxury

    </div>


</div>



<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>