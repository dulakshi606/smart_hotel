<?php

require_once "config/db.php";
require_once "includes/functions.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Velora Hotel | Luxury Stay</title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         BOOTSTRAP ICONS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <!-- =====================================================
         VELORA CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="assets/css/velora.css"
    >


    <style>

        /* =====================================================
           RESET
        ===================================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        html,
        body {
            width: 100%;
            min-height: 100%;
        }


        html {
            scroll-behavior: smooth;
        }


        body {

            font-family:
                "Segoe UI",
                Arial,
                sans-serif;

            background: #0f2034;

            color: white;
        }


        a {
            text-decoration: none;
        }


        /* =====================================================
           NAVBAR
        ===================================================== */

        .navbar {

            height: 92px;

            background: #0f2034;

            padding: 0;

            position: relative;

            z-index: 1000;

            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, 0.15);
        }


        .navbar-brand {

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size: 30px;

            font-weight: 700;

            color: #d4af37 !important;

            letter-spacing: 0.3px;
        }


        .navbar-brand i {

            font-size: 25px;

            margin-right: 8px;
        }


        /* =====================================================
           NAVIGATION LINKS
        ===================================================== */

        .nav-link {

            color: #ffffff !important;

            font-size: 15px;

            font-weight: 600;

            margin-left: 24px;

            padding:
                8px 0 !important;

            transition: 0.3s;
        }


        .nav-link:hover {

            color: #d4af37 !important;
        }


        .nav-link.active {

            color: #d4af37 !important;
        }


        /* =====================================================
           LOGIN BUTTON
        ===================================================== */

        .btn-login {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            color: #d4af37;

            border:
                1px solid #d4af37;

            border-radius: 9px;

            padding:
                10px 21px;

            margin-left: 24px;

            font-size: 15px;

            font-weight: 600;

            transition: 0.3s;
        }


        .btn-login:hover {

            background: #d4af37;

            color: #101820;

            transform:
                translateY(-2px);
        }


        /* =====================================================
           REGISTER BUTTON
        ===================================================== */

        .btn-register {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            background: #d4af37;

            color: #101820;

            border:
                1px solid #d4af37;

            border-radius: 9px;

            padding:
                10px 23px;

            margin-left: 10px;

            font-size: 15px;

            font-weight: 700;

            transition: 0.3s;
        }


        .btn-register:hover {

            background: #f1ce57;

            border-color: #f1ce57;

            color: #101820;

            transform:
                translateY(-2px);
        }


        /* =====================================================
           HERO SECTION
        ===================================================== */

        .hero {

            min-height:
                calc(100vh - 92px);

            display: flex;

            align-items: center;

            position: relative;

            overflow: hidden;

            background:

                linear-gradient(
                    90deg,
                    rgba(5, 17, 29, 0.96) 0%,
                    rgba(5, 17, 29, 0.84) 42%,
                    rgba(5, 17, 29, 0.50) 75%,
                    rgba(5, 17, 29, 0.30) 100%
                ),

                url("assets/images/hotel.jpg");

            background-size: cover;

            background-position: center;

            background-repeat: no-repeat;
        }


        /* =====================================================
           HERO DARK OVERLAY
        ===================================================== */

        .hero::after {

            content: "";

            position: absolute;

            inset: 0;

            background:

                linear-gradient(
                    180deg,
                    rgba(0,0,0,0.05),
                    rgba(0,0,0,0.20)
                );

            pointer-events: none;
        }


        .hero .container {

            position: relative;

            z-index: 2;
        }


        /* =====================================================
           HERO CONTENT
        ===================================================== */

        .hero-content {

            max-width: 850px;

            padding-bottom: 20px;
        }


        /* =====================================================
           SMALL TITLE
        ===================================================== */

        .hero-small {

            display: flex;

            align-items: center;

            gap: 13px;

            color: #d4af37;

            text-transform: uppercase;

            letter-spacing: 4px;

            font-size: 13px;

            font-weight: 700;

            margin-bottom: 25px;
        }


        .hero-small::before {

            content: "";

            width: 42px;

            height: 2px;

            background: #d4af37;
        }


        /* =====================================================
           MAIN HEADING
        ===================================================== */

        .hero h1 {

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size:
                clamp(55px, 6vw, 82px);

            font-weight: 500;

            line-height: 1.02;

            letter-spacing: -2px;

            color: #ffffff;

            margin-bottom: 28px;
        }


        .hero h1 span {

            color: #d4af37;

            font-style: italic;
        }


        /* =====================================================
           DESCRIPTION
        ===================================================== */

        .hero-description {

            max-width: 720px;

            font-size: 18px;

            line-height: 1.8;

            color:
                rgba(255,255,255,0.88);

            margin-bottom: 34px;
        }


        /* =====================================================
           HERO BUTTONS
        ===================================================== */

        .hero-buttons {

            display: flex;

            align-items: center;

            gap: 12px;

            flex-wrap: wrap;
        }


        /* =====================================================
           EXPLORE ROOMS BUTTON
        ===================================================== */

        .btn-hero-login {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            background: #d4af37;

            color: #101820;

            border:
                2px solid #d4af37;

            border-radius: 9px;

            padding:
                14px 30px;

            font-size: 15px;

            font-weight: 700;

            transition: 0.3s;
        }


        .btn-hero-login:hover {

            background: #f1ce57;

            border-color: #f1ce57;

            color: #101820;

            transform:
                translateY(-3px);

            box-shadow:
                0 10px 25px
                rgba(0,0,0,0.25);
        }


        /* =====================================================
           BOOK YOUR STAY BUTTON
        ===================================================== */

        .btn-hero-register {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            background: transparent;

            color: white;

            border:
                1px solid
                rgba(255,255,255,0.75);

            border-radius: 9px;

            padding:
                14px 30px;

            font-size: 15px;

            font-weight: 600;

            transition: 0.3s;
        }


        .btn-hero-register:hover {

            background: white;

            border-color: white;

            color: #101820;

            transform:
                translateY(-3px);
        }


        /* =====================================================
           TRUST INFORMATION
        ===================================================== */

        .hero-trust {

            display: flex;

            align-items: center;

            gap: 32px;

            max-width: 760px;

            margin-top: 45px;

            padding-top: 25px;

            border-top:
                1px solid
                rgba(255,255,255,0.20);
        }


        .hero-trust-item {

            display: flex;

            align-items: center;

            gap: 9px;

            color:
                rgba(255,255,255,0.82);

            font-size: 13px;
        }


        .hero-trust-item i {

            color: #d4af37;

            font-size: 17px;
        }


        /* =====================================================
           MOBILE NAVBAR
        ===================================================== */

        .navbar-toggler {

            border:
                1px solid
                rgba(255,255,255,0.30);
        }


        .navbar-toggler:focus {

            box-shadow: none;
        }


        .navbar-toggler-icon {

            filter: brightness(0)
                    invert(1);
        }


        /* =====================================================
           TABLET
        ===================================================== */

        @media (max-width: 991px) {

            .navbar {

                height: auto;

                padding:
                    15px 0;
            }


            .navbar-brand {

                font-size: 25px;
            }


            .nav-link {

                margin-left: 0;

                margin-top: 5px;
            }


            .btn-login {

                margin-left: 0;

                margin-top: 10px;
            }


            .btn-register {

                margin-left: 0;

                margin-top: 10px;
            }


            .hero {

                min-height:
                    calc(100vh - 76px);

                background:

                    linear-gradient(
                        rgba(5,17,29,0.86),
                        rgba(5,17,29,0.90)
                    ),

                    url("assets/images/hotel.jpg");

                background-size: cover;

                background-position: center;
            }


            .hero-content {

                max-width: 720px;
            }

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 576px) {

            .navbar-brand {

                font-size: 22px;
            }


            .navbar-brand i {

                font-size: 20px;
            }


            .hero {

                min-height:
                    calc(100vh - 70px);

                padding:
                    60px 0;
            }


            .hero-content {

                text-align: center;

                margin: auto;
            }


            .hero-small {

                justify-content: center;

                font-size: 10px;

                letter-spacing: 2.5px;
            }


            .hero-small::before {

                width: 28px;
            }


            .hero h1 {

                font-size: 43px;

                letter-spacing: -1px;
            }


            .hero-description {

                font-size: 15px;

                line-height: 1.7;
            }


            .hero-buttons {

                flex-direction: column;

                width: 100%;
            }


            .btn-hero-login,
            .btn-hero-register {

                width: 100%;
            }


            .hero-trust {

                justify-content: center;

                flex-wrap: wrap;

                gap: 14px;
            }


            .hero-trust-item {

                font-size: 12px;
            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar navbar-expand-lg navbar-dark">

    <div class="container">


        <!-- =================================================
             HOTEL LOGO
        ================================================== -->

        <a
            class="navbar-brand"
            href="index.php"
        >

            <i class="bi bi-buildings"></i>

            Velora Hotel

        </a>


        <!-- =================================================
             MOBILE MENU BUTTON
        ================================================== -->

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
            aria-controls="mainNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >

            <span class="navbar-toggler-icon"></span>

        </button>


        <!-- =================================================
             NAVIGATION
        ================================================== -->

        <div
            class="collapse navbar-collapse"
            id="mainNavbar"
        >

            <ul
                class="navbar-nav ms-auto align-items-lg-center"
            >


                <!-- =================================================
                     HOME
                ================================================== -->

                <li class="nav-item">

                    <a
                        class="nav-link active"
                        href="index.php"
                    >

                        <i class="bi bi-house-door me-1"></i>

                        Home

                    </a>

                </li>


                <!-- =================================================
                     LOGIN
                ================================================== -->

                <li class="nav-item">

                    <a
                        class="btn-login"
                        href="auth/login.php"
                    >

                        <i
                            class="bi bi-person me-1"
                        ></i>

                        Login

                    </a>

                </li>


                <!-- =================================================
                     REGISTER
                ================================================== -->

                <li class="nav-item">

                    <a
                        class="btn-register"
                        href="auth/register.php"
                    >

                        <i
                            class="bi bi-person-plus me-1"
                        ></i>

                        Register

                    </a>

                </li>

            </ul>

        </div>

    </div>

</nav>



<!-- =====================================================
     HERO SECTION
===================================================== -->

<section class="hero">

    <div class="container">

        <div class="hero-content">


            <!-- =================================================
                 SMALL TITLE
            ================================================== -->

            <div class="hero-small">

                Welcome to Velora Hotel

            </div>


            <!-- =================================================
                 MAIN TITLE
            ================================================== -->

            <h1>

                Experience

                <span>Comfort</span>

                &amp; Luxury

            </h1>


            <!-- =================================================
                 DESCRIPTION
            ================================================== -->

            <p class="hero-description">

                Discover beautifully designed rooms,
                exceptional hospitality and a peaceful
                stay created around your comfort.

            </p>


            <!-- =================================================
                 HERO ACTION BUTTONS
            ================================================== -->

            <div class="hero-buttons">


                <!-- EXPLORE ROOMS -->

                <a
                    href="auth/login.php"
                    class="btn-hero-login"
                >

                    <i
                        class="bi bi-door-open me-2"
                    ></i>

                    Explore Rooms

                </a>


                <!-- BOOK YOUR STAY -->

                <a
                    href="auth/register.php"
                    class="btn-hero-register"
                >

                    <i
                        class="bi bi-calendar-check me-2"
                    ></i>

                    Book Your Stay

                </a>

            </div>


            <!-- =================================================
                 TRUST INFORMATION
            ================================================== -->

            <div class="hero-trust">


                <!-- COMFORTABLE ROOMS -->

                <div class="hero-trust-item">

                    <i class="bi bi-star-fill"></i>

                    <span>
                        Comfortable Rooms
                    </span>

                </div>


                <!-- SECURE BOOKING -->

                <div class="hero-trust-item">

                    <i class="bi bi-shield-check"></i>

                    <span>
                        Secure Booking
                    </span>

                </div>


                <!-- 24/7 SUPPORT -->

                <div class="hero-trust-item">

                    <i class="bi bi-headset"></i>

                    <span>
                        24/7 Support
                    </span>

                </div>


            </div>


        </div>

    </div>

</section>



<!-- =====================================================
     BOOTSTRAP JAVASCRIPT
===================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>