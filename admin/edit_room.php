<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

requireRole("admin");


$error = "";
$success = "";


// =====================================================
// GET ROOM ID
// =====================================================

$id = (int)($_GET["id"] ?? 0);


if ($id <= 0) {
    die("Invalid room ID.");
}


// =====================================================
// GET ROOM
// =====================================================

$stmt = $pdo->prepare("
    SELECT *
    FROM rooms
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$room = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$room) {
    die("Room not found.");
}


// =====================================================
// GET ROOM TYPES
// =====================================================

$stmt = $pdo->query("
    SELECT *
    FROM room_types
    ORDER BY type_name
");

$roomTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);


// =====================================================
// UPDATE ROOM
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $room_number =
        trim($_POST["room_number"] ?? "");

    $room_type_id =
        (int)($_POST["room_type_id"] ?? 0);

    $floor_number =
        (int)($_POST["floor_number"] ?? 1);

    $status =
        $_POST["status"] ?? "available";

    $description =
        trim($_POST["description"] ?? "");


    // ================================================
    // VALIDATION
    // ================================================

    if (
        $room_number === "" ||
        $room_type_id <= 0 ||
        $floor_number <= 0
    ) {

        $error =
            "Please fill all required fields.";

    } else {


        // ============================================
        // CHECK DUPLICATE ROOM NUMBER
        // ============================================

        $check = $pdo->prepare("
            SELECT id
            FROM rooms
            WHERE room_number = ?
            AND id != ?
            LIMIT 1
        ");

        $check->execute([
            $room_number,
            $id
        ]);


        if ($check->fetch()) {

            $error =
                "This room number already exists.";

        } else {


            // ========================================
            // CHECK ROOM TYPE
            // ========================================

            $checkType = $pdo->prepare("
                SELECT id
                FROM room_types
                WHERE id = ?
                LIMIT 1
            ");

            $checkType->execute([
                $room_type_id
            ]);


            if (!$checkType->fetch()) {

                $error =
                    "Invalid room type.";

            } else {


                // ====================================
                // UPDATE
                // ====================================

                $update = $pdo->prepare("
                    UPDATE rooms

                    SET
                        room_number = ?,
                        room_type_id = ?,
                        floor_number = ?,
                        status = ?,
                        description = ?

                    WHERE id = ?
                ");


                $update->execute([
                    $room_number,
                    $room_type_id,
                    $floor_number,
                    $status,
                    $description,
                    $id
                ]);


                // ====================================
                // REDIRECT
                // ====================================

                header(
                    "Location: rooms.php?updated=1"
                );

                exit;

            }

        }

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>
<link rel="stylesheet" href="../assets/css/style.css">
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Edit Room - Velora Hotel
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

            background: #f1f5f9;

            color: #0f172a;

            font-family: Arial, sans-serif;

        }


        .sidebar {

            min-height: 100vh;

            background: #111827;

        }


        .brand {

            color: white;

            font-size: 22px;

            font-weight: bold;

            padding: 25px 20px;

            display: block;

            text-decoration: none;

        }


        .sidebar a {

            color: #cbd5e1;

            text-decoration: none;

            display: block;

            padding: 12px 20px;

        }


        .sidebar a:hover,
        .sidebar a.active {

            background: #2563eb;

            color: white;

        }


        .topbar {

            background: white;

            padding: 15px 25px;

            box-shadow:
                0 1px 5px
                rgba(0,0,0,.08);

        }


        .content {

            padding: 30px;

        }


        .form-card {

            background: white;

            border-radius: 15px;

            padding: 30px;

            box-shadow:
                0 4px 15px
                rgba(0,0,0,.06);

            max-width: 900px;

        }


        .page-title {

            font-size: 26px;

            font-weight: 700;

        }


        .form-label {

            font-weight: 600;

        }

    </style>

<link rel="stylesheet" href="../assets/css/velora.css">
</head>


<body>


<div class="container-fluid">

    <div class="row">


        <!-- ==========================================
             SIDEBAR
        =========================================== -->

        <div class="col-md-2 p-0 sidebar">


            <a
                href="dashboard.php"
                class="brand"
            >

                <i class="bi bi-building"></i>

                Velora Hotel

            </a>


            <a href="dashboard.php">

                <i class="bi bi-speedometer2 me-2"></i>

                Dashboard

            </a>


            <a
                href="rooms.php"
                class="active"
            >

                <i class="bi bi-door-open me-2"></i>

                Rooms

            </a>


            <a href="room_types.php">

                <i class="bi bi-grid me-2"></i>

                Room Types

            </a>


            <a href="customers.php">

                <i class="bi bi-people me-2"></i>

                Customers

            </a>


            <a href="bookings.php">

                <i class="bi bi-calendar-check me-2"></i>

                Bookings

            </a>


            <a href="services.php">

                <i class="bi bi-stars me-2"></i>

                Services

            </a>


            <a href="maintenance.php">

                <i class="bi bi-tools me-2"></i>

                Maintenance

            </a>


            <a href="reports.php">

                <i class="bi bi-bar-chart me-2"></i>

                Reports

            </a>


            <hr class="text-secondary">


            <a href="../auth/logout.php">

                <i class="bi bi-box-arrow-right me-2"></i>

                Logout

            </a>


        </div>


        <!-- ==========================================
             MAIN
        =========================================== -->

        <div class="col-md-10 p-0">


            <!-- TOPBAR -->

            <div class="topbar">

                <div
                    class="d-flex justify-content-between align-items-center"
                >

                    <div>

                        <h5 class="mb-0">

                            Edit Room

                        </h5>

                        <small class="text-muted">

                            Update room information

                        </small>

                    </div>


                    <div>

                        <i
                            class="bi bi-person-circle me-2"
                        ></i>

                        <?= e(
                            $_SESSION["full_name"]
                        ) ?>

                    </div>

                </div>

            </div>


            <!-- CONTENT -->

            <div class="content">


                <div class="mb-4">

                    <div class="page-title">

                        <i
                            class="bi bi-pencil-square text-primary"
                        ></i>

                        Edit Room

                    </div>

                    <div class="text-muted">

                        Update the details of Room
                        <?= e(
                            $room["room_number"]
                        ) ?>

                    </div>

                </div>


                <!-- ERROR -->

                <?php if ($error): ?>

                    <div
                        class="alert alert-danger"
                    >

                        <i
                            class="bi bi-exclamation-triangle"
                        ></i>

                        <?= e($error) ?>

                    </div>

                <?php endif; ?>


                <!-- FORM -->

                <div class="form-card">


                    <form method="POST">


                        <div class="row">


                            <!-- ROOM NUMBER -->

                            <div
                                class="col-md-6 mb-3"
                            >

                                <label
                                    class="form-label"
                                >

                                    Room Number

                                </label>


                                <input
                                    type="text"
                                    name="room_number"
                                    class="form-control"
                                    value="<?= e(
                                        $room["room_number"]
                                    ) ?>"
                                    required
                                >

                            </div>


                            <!-- ROOM TYPE -->

                            <div
                                class="col-md-6 mb-3"
                            >

                                <label
                                    class="form-label"
                                >

                                    Room Type

                                </label>


                                <select
                                    name="room_type_id"
                                    class="form-select"
                                    required
                                >

                                    <option value="">

                                        Select Room Type

                                    </option>


                                    <?php
                                    foreach (
                                        $roomTypes
                                        as $type
                                    ):
                                    ?>

                                        <option
                                            value="<?= $type["id"] ?>"
                                            <?= (
                                                $room[
                                                    "room_type_id"
                                                ]
                                                ==
                                                $type["id"]
                                            )
                                            ? "selected"
                                            : ""
                                            ?>
                                        >

                                            <?= e(
                                                $type[
                                                    "type_name"
                                                ]
                                            ) ?>

                                            -
                                            Rs.

                                            <?= number_format(
                                                $type[
                                                    "base_price"
                                                ],
                                                2
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>


                                </select>

                            </div>


                            <!-- FLOOR -->

                            <div
                                class="col-md-6 mb-3"
                            >

                                <label
                                    class="form-label"
                                >

                                    Floor Number

                                </label>


                                <input
                                    type="number"
                                    name="floor_number"
                                    class="form-control"
                                    min="1"
                                    value="<?= e(
                                        $room[
                                            "floor_number"
                                        ]
                                    ) ?>"
                                    required
                                >

                            </div>


                            <!-- STATUS -->

                            <div
                                class="col-md-6 mb-3"
                            >

                                <label
                                    class="form-label"
                                >

                                    Room Status

                                </label>


                                <select
                                    name="status"
                                    class="form-select"
                                >

                                    <option
                                        value="available"
                                        <?= $room[
                                            "status"
                                        ] === "available"
                                            ? "selected"
                                            : ""
                                        ?>
                                    >

                                        Available

                                    </option>


                                    <option
                                        value="occupied"
                                        <?= $room[
                                            "status"
                                        ] === "occupied"
                                            ? "selected"
                                            : ""
                                        ?>
                                    >

                                        Occupied

                                    </option>


                                    <option
                                        value="maintenance"
                                        <?= $room[
                                            "status"
                                        ] === "maintenance"
                                            ? "selected"
                                            : ""
                                        ?>
                                    >

                                        Maintenance

                                    </option>


                                    <option
                                        value="cleaning"
                                        <?= $room[
                                            "status"
                                        ] === "cleaning"
                                            ? "selected"
                                            : ""
                                        ?>
                                    >

                                        Cleaning

                                    </option>

                                </select>

                            </div>


                            <!-- DESCRIPTION -->

                            <div
                                class="col-12 mb-3"
                            >

                                <label
                                    class="form-label"
                                >

                                    Description

                                </label>


                                <textarea
                                    name="description"
                                    class="form-control"
                                    rows="4"
                                    placeholder="Room description..."
                                ><?= e(
                                    $room["description"]
                                    ?? ""
                                ) ?></textarea>

                            </div>


                        </div>


                        <!-- BUTTONS -->

                        <div
                            class="d-flex gap-2 mt-3"
                        >


                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <i
                                    class="bi bi-save"
                                ></i>

                                Save Changes

                            </button>


                            <a
                                href="rooms.php"
                                class="btn btn-secondary"
                            >

                                <i
                                    class="bi bi-arrow-left"
                                ></i>

                                Back to Rooms

                            </a>


                        </div>


                    </form>


                </div>


            </div>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>