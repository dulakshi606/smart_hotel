<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

startSession();
requireRole("customer");

/* =====================================================
   GET AVAILABLE MEALS
===================================================== */

$stmt = $pdo->query("
    SELECT
        id,
        meal_name,
        category,
        description,
        price,
        image,
        availability
    FROM meals
    WHERE availability = 'available'
    ORDER BY
        CASE category
            WHEN 'Breakfast' THEN 1
            WHEN 'Lunch' THEN 2
            WHEN 'Dinner' THEN 3
            WHEN 'Snacks' THEN 4
            WHEN 'Beverages' THEN 5
            ELSE 6
        END,
        meal_name ASC
");

$meals = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   GROUP MEALS BY CATEGORY
===================================================== */

$groupedMeals = [];

foreach ($meals as $meal) {

    $category = $meal["category"];

    if (!isset($groupedMeals[$category])) {
        $groupedMeals[$category] = [];
    }

    $groupedMeals[$category][] = $meal;
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

    <title>Meals & Dining - Velora Hotel</title>

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
            background: #f7f8fa;
            color: #172033;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        /* =====================================================
           NAVBAR
        ===================================================== */

        .navbar {
            background: #071a3a !important;
            padding: 15px 0;
            box-shadow: 0 4px 18px rgba(0,0,0,0.12);
        }

        .navbar-brand {
            color: #d4af37 !important;
            font-size: 25px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .navbar-brand span {
            color: #ffffff;
        }

        .nav-link {
            color: #ffffff !important;
            margin-left: 12px;
            font-weight: 500;
        }

        .nav-link:hover {
            color: #d4af37 !important;
        }

        .active-link {
            color: #d4af37 !important;
        }

        .logout-btn {
            border: 1px solid #d4af37;
            color: #d4af37 !important;
            border-radius: 8px;
            padding: 7px 15px !important;
        }

        .logout-btn:hover {
            background: #d4af37;
            color: #071a3a !important;
        }

        /* =====================================================
           HERO
        ===================================================== */

        .hero {
            background:
                linear-gradient(
                    rgba(7,26,58,0.88),
                    rgba(7,26,58,0.88)
                ),
                url("../assets/images/hotel.jpg");

            background-size: cover;
            background-position: center;

            padding: 75px 20px;
            text-align: center;
            color: white;
        }

        .hero-icon {
            width: 75px;
            height: 75px;
            margin: auto;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: rgba(212,175,55,0.15);
            border: 1px solid rgba(212,175,55,0.5);

            color: #d4af37;
            font-size: 34px;
        }

        .hero h1 {
            margin-top: 22px;
            font-size: 42px;
            font-weight: 700;
        }

        .hero p {
            max-width: 650px;
            margin: 12px auto 0;
            color: #e5e7eb;
            font-size: 17px;
        }

        /* =====================================================
           MAIN CONTAINER
        ===================================================== */

        .main-container {
            max-width: 1200px;
            margin: auto;
            padding: 55px 20px;
        }

        /* =====================================================
           INTRO
        ===================================================== */

        .intro {
            text-align: center;
            margin-bottom: 45px;
        }

        .intro h2 {
            color: #071a3a;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .intro p {
            color: #6b7280;
            margin: 0;
        }

        /* =====================================================
           CATEGORY
        ===================================================== */

        .category-section {
            margin-bottom: 55px;
        }

        .category-title {
            display: flex;
            align-items: center;
            gap: 12px;

            margin-bottom: 25px;

            color: #071a3a;
            font-size: 27px;
            font-weight: 700;
        }

        .category-title i {
            color: #d4af37;
            font-size: 27px;
        }

        .category-line {
            height: 1px;
            background: #e5e7eb;
            flex: 1;
        }

        /* =====================================================
           MEAL CARD
        ===================================================== */

        .meal-card {
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;

            border: 1px solid #e8eaf0;

            height: 100%;

            transition: 0.25s ease;

            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .meal-card:hover {
            transform: translateY(-6px);

            box-shadow:
                0 15px 35px rgba(7,26,58,0.13);

            border-color: #d4af37;
        }

        /* =====================================================
           IMAGE
        ===================================================== */

        .meal-image {
            height: 220px;
            width: 100%;
            object-fit: cover;
            background: #eef1f5;
        }

        .image-placeholder {
            height: 220px;

            display: flex;
            align-items: center;
            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #071a3a,
                    #102b59
                );

            color: #d4af37;
            font-size: 55px;
        }

        /* =====================================================
           CARD BODY
        ===================================================== */

        .meal-body {
            padding: 22px;
        }

        .meal-category {
            display: inline-block;

            background: #fff8df;
            color: #7c5a00;

            padding: 5px 11px;

            border-radius: 20px;

            font-size: 12px;
            font-weight: 700;

            margin-bottom: 10px;
        }

        .meal-name {
            color: #071a3a;
            font-size: 21px;
            font-weight: 700;

            margin-bottom: 8px;
        }

        .meal-description {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;

            min-height: 48px;

            margin-bottom: 18px;
        }

        .meal-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 10px;
        }

        .meal-price {
            color: #071a3a;
            font-size: 21px;
            font-weight: 700;
        }

        .meal-price small {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
        }

        .order-btn {
            background: #d4af37;
            color: #071a3a;

            border: none;
            border-radius: 8px;

            padding: 9px 15px;

            font-size: 14px;
            font-weight: 700;

            text-decoration: none;

            transition: 0.2s ease;
        }

        .order-btn:hover {
            background: #b99525;
            color: #ffffff;

            transform: translateY(-1px);
        }

        /* =====================================================
           EMPTY STATE
        ===================================================== */

        .empty-state {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;

            padding: 70px 20px;

            text-align: center;
        }

        .empty-state i {
            font-size: 55px;
            color: #d4af37;
        }

        .empty-state h3 {
            color: #071a3a;
            margin-top: 18px;
        }

        .empty-state p {
            color: #6b7280;
        }

        /* =====================================================
           FOOTER
        ===================================================== */

        footer {
            background: #071a3a;
            color: #ffffff;

            text-align: center;

            padding: 28px 20px;

            margin-top: 40px;
        }

        footer strong {
            color: #d4af37;
        }

        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 768px) {

            .hero {
                padding: 55px 20px;
            }

            .hero h1 {
                font-size: 32px;
            }

            .category-title {
                font-size: 23px;
            }

            .meal-image,
            .image-placeholder {
                height: 200px;
            }
        }

    </style>

</head>

<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar navbar-expand-lg">

    <div class="container">

        <a
            class="navbar-brand"
            href="dashboard.php"
        >
            <i class="bi bi-building"></i>
            Velora <span>Hotel</span>
        </a>


        <button
            class="navbar-toggler bg-light"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarMenu"
        >
            <span class="navbar-toggler-icon"></span>
        </button>


        <div
            class="collapse navbar-collapse"
            id="navbarMenu"
        >

            <ul class="navbar-nav ms-auto align-items-lg-center">

                <li class="nav-item">
                    <a
                        class="nav-link"
                        href="dashboard.php"
                    >
                        Dashboard
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link"
                        href="rooms.php"
                    >
                        Rooms
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link"
                        href="bookings.php"
                    >
                        My Bookings
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link active-link"
                        href="meals.php"
                    >
                        Meals & Dining
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link"
                        href="service_requests.php"
                    >
                        Services
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link"
                        href="profile.php"
                    >
                        Profile
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link logout-btn ms-lg-2"
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


<!-- =====================================================
     HERO
===================================================== -->

<section class="hero">

    <div class="hero-icon">
        <i class="bi bi-cup-hot-fill"></i>
    </div>

    <h1>Meals & Dining</h1>

    <p>
        Enjoy delicious meals and refreshing beverages
        delivered with the comfort and service of Velora Hotel.
    </p>

</section>


<!-- =====================================================
     MAIN
===================================================== -->

<div class="main-container">

    <div class="intro">

        <h2>Our Dining Selection</h2>

        <p>
            Explore our selection of freshly prepared meals,
            snacks and beverages.
        </p>

    </div>


    <?php if (empty($groupedMeals)): ?>

        <div class="empty-state">

            <i class="bi bi-cup-hot"></i>

            <h3>No Meals Available</h3>

            <p>
                Our dining menu is currently being updated.
                Please check again later.
            </p>

        </div>

    <?php else: ?>


        <?php foreach ($groupedMeals as $category => $categoryMeals): ?>

            <?php

            $categoryIcon = "bi-egg-fried";

            switch ($category) {

                case "Breakfast":
                    $categoryIcon = "bi-sunrise-fill";
                    break;

                case "Lunch":
                    $categoryIcon = "bi-brightness-high-fill";
                    break;

                case "Dinner":
                    $categoryIcon = "bi-moon-stars-fill";
                    break;

                case "Snacks":
                    $categoryIcon = "bi-cookie";
                    break;

                case "Beverages":
                    $categoryIcon = "bi-cup-straw";
                    break;
            }

            ?>


            <section class="category-section">

                <div class="category-title">

                    <i class="bi <?= e($categoryIcon); ?>"></i>

                    <?= e($category); ?>

                    <div class="category-line"></div>

                </div>


                <div class="row g-4">


                    <?php foreach ($categoryMeals as $meal): ?>

                        <div class="col-lg-4 col-md-6">

                            <div class="meal-card">


                                <?php

                                $image = trim($meal["image"] ?? "");

                                $imagePath = "";

                                if ($image !== "") {

                                    if (
                                        str_starts_with($image, "http://") ||
                                        str_starts_with($image, "https://")
                                    ) {

                                        $imagePath = $image;

                                    } else {

                                        $imagePath = "../uploads/meals/" . $image;

                                    }
                                }

                                ?>


                                <?php if ($imagePath !== ""): ?>

                                    <img
                                        src="<?= e($imagePath); ?>"
                                        alt="<?= e($meal["meal_name"]); ?>"
                                        class="meal-image"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                    >

                                    <div
                                        class="image-placeholder"
                                        style="display:none;"
                                    >
                                        <i class="bi bi-cup-hot-fill"></i>
                                    </div>

                                <?php else: ?>

                                    <div class="image-placeholder">

                                        <i class="bi bi-cup-hot-fill"></i>

                                    </div>

                                <?php endif; ?>


                                <div class="meal-body">

                                    <span class="meal-category">

                                        <?= e($meal["category"]); ?>

                                    </span>


                                    <div class="meal-name">

                                        <?= e($meal["meal_name"]); ?>

                                    </div>


                                    <div class="meal-description">

                                        <?= e(
                                            $meal["description"]
                                            ?: "Deliciously prepared by Velora Hotel."
                                        ); ?>

                                    </div>


                                    <div class="meal-bottom">

                                        <div class="meal-price">

                                            LKR
                                            <?= number_format(
                                                (float)$meal["price"],
                                                2
                                            ); ?>

                                            <small>/ item</small>

                                        </div>


                                        <a
                                            href="meal_order.php?meal_id=<?= (int)$meal["id"]; ?>"
                                            class="order-btn"
                                        >

                                            <i class="bi bi-cart-plus"></i>

                                            Order

                                        </a>

                                    </div>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>


                </div>

            </section>

        <?php endforeach; ?>


    <?php endif; ?>

</div>


<!-- =====================================================
     FOOTER
===================================================== -->

<footer>

    <div>

        <strong>Velora Hotel</strong>

        <span>
            &nbsp; | &nbsp;
            Meals & Dining
        </span>

    </div>

    <small>
        © <?= date("Y"); ?> Velora Hotel. All rights reserved.
    </small>

</footer>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>