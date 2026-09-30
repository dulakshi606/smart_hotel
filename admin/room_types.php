<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

requireRole("admin");

$message = "";
$messageType = "success";


/*
|--------------------------------------------------------------------------
| ROOM TYPE UPDATED MESSAGE
|--------------------------------------------------------------------------
*/

if (isset($_GET["updated"])) {

    $message = "Room type updated successfully.";
    $messageType = "success";
}


/*
|--------------------------------------------------------------------------
| ADD ROOM TYPE
|--------------------------------------------------------------------------
*/

if (isset($_POST["add_room_type"])) {

    $type_name = trim($_POST["type_name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $base_price = trim($_POST["base_price"] ?? "");
    $capacity = trim($_POST["capacity"] ?? "");


    /*
    |----------------------------------------------------------------------
    | VALIDATION
    |----------------------------------------------------------------------
    */

    if (
        $type_name === "" ||
        $base_price === "" ||
        $capacity === ""
    ) {

        $message = "Please fill all required fields.";
        $messageType = "danger";

    } elseif (
        !is_numeric($base_price) ||
        $base_price <= 0
    ) {

        $message = "Please enter a valid room price.";
        $messageType = "danger";

    } elseif (
        !is_numeric($capacity) ||
        $capacity <= 0
    ) {

        $message = "Please enter a valid room capacity.";
        $messageType = "danger";

    } else {


        /*
        |------------------------------------------------------------------
        | CHECK DUPLICATE ROOM TYPE
        |------------------------------------------------------------------
        */

        $check = $pdo->prepare("
            SELECT id
            FROM room_types
            WHERE type_name = ?
            LIMIT 1
        ");

        $check->execute([$type_name]);


        if ($check->fetch()) {

            $message = "This room type already exists.";
            $messageType = "danger";

        } else {


            /*
            |--------------------------------------------------------------
            | INSERT ROOM TYPE
            |--------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                INSERT INTO room_types
                (
                    type_name,
                    description,
                    base_price,
                    capacity
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

            $stmt->execute([
                $type_name,
                $description,
                $base_price,
                $capacity
            ]);


            $message = "Room type added successfully.";
            $messageType = "success";
        }
    }
}


/*
|--------------------------------------------------------------------------
| DELETE ROOM TYPE
|--------------------------------------------------------------------------
*/

if (isset($_GET["delete"])) {

    $id = (int) $_GET["delete"];


    /*
    |----------------------------------------------------------------------
    | CHECK WHETHER ROOMS USE THIS ROOM TYPE
    |----------------------------------------------------------------------
    */

    $check = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM rooms
        WHERE room_type_id = ?
    ");

    $check->execute([$id]);

    $roomCount = (int) $check->fetchColumn();


    if ($roomCount > 0) {

        $message =
            "This room type cannot be deleted because rooms are using it.";

        $messageType = "danger";

    } else {

        $stmt = $pdo->prepare("
            DELETE FROM room_types
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        $message = "Room type deleted successfully.";
        $messageType = "success";
    }
}


/*
|--------------------------------------------------------------------------
| GET ALL ROOM TYPES
|--------------------------------------------------------------------------
|
| IMPORTANT:
| capacity comes from room_types table.
|
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        rt.id,
        rt.type_name,
        rt.description,
        rt.base_price,
        rt.capacity,
        COUNT(r.id) AS room_count

    FROM room_types rt

    LEFT JOIN rooms r
        ON rt.id = r.room_type_id

    GROUP BY
        rt.id,
        rt.type_name,
        rt.description,
        rt.base_price,
        rt.capacity

    ORDER BY rt.id DESC
");

$roomTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Room Types - Velora Hotel</title>


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

        /*
        |--------------------------------------------------------------------------
        | PAGE
        |--------------------------------------------------------------------------
        */

        body {
            background: #f6f2ea;
            color: #172033;
        }


        /*
        |--------------------------------------------------------------------------
        | SIDEBAR
        |--------------------------------------------------------------------------
        */

        .sidebar {
            min-height: 100vh;
            background: #0b1f3a;
            box-shadow: 3px 0 15px rgba(0, 0, 0, 0.08);
        }


        .brand {
            color: #d4af63 !important;
            font-size: 23px;
            font-weight: 700;
            padding: 25px 20px;
            display: block;
            text-decoration: none;
            letter-spacing: 0.5px;
        }


        .sidebar a {
            color: #d7dde7;
            text-decoration: none;
            display: block;
            padding: 13px 20px;
            transition: all 0.25s ease;
        }


        .sidebar a:hover {
            background: rgba(212, 175, 99, 0.15);
            color: #d4af63;
        }


        .sidebar a.active {
            background: #d4af63;
            color: #0b1f3a;
            font-weight: 600;
        }


        .sidebar hr {
            border-color: rgba(255,255,255,0.15);
        }


        /*
        |--------------------------------------------------------------------------
        | TOP BAR
        |--------------------------------------------------------------------------
        */

        .topbar {
            background: #ffffff;
            padding: 17px 28px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }


        .topbar h5 {
            color: #0b1f3a;
            font-weight: 700;
        }


        /*
        |--------------------------------------------------------------------------
        | CONTENT
        |--------------------------------------------------------------------------
        */

        .content {
            padding: 30px;
        }


        /*
        |--------------------------------------------------------------------------
        | CARDS
        |--------------------------------------------------------------------------
        */

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 6px 25px rgba(11, 31, 58, 0.07) !important;
        }


        .card-body {
            padding: 25px;
        }


        .card h5 {
            color: #0b1f3a;
            font-weight: 700;
        }


        /*
        |--------------------------------------------------------------------------
        | FORM
        |--------------------------------------------------------------------------
        */

        .form-label {
            font-weight: 600;
            color: #26364d;
        }


        .form-control {
            border: 1px solid #d9dee7;
            border-radius: 8px;
            padding: 10px 12px;
        }


        .form-control:focus {
            border-color: #d4af63;
            box-shadow: 0 0 0 0.2rem rgba(212,175,99,0.18);
        }


        .input-group-text {
            background: #0b1f3a;
            color: #d4af63;
            border: 1px solid #0b1f3a;
            font-weight: 600;
        }


        /*
        |--------------------------------------------------------------------------
        | BUTTON
        |--------------------------------------------------------------------------
        */

        .btn-primary {
            background: #0b1f3a;
            border-color: #0b1f3a;
            border-radius: 8px;
            font-weight: 600;
        }


        .btn-primary:hover {
            background: #d4af63;
            border-color: #d4af63;
            color: #0b1f3a;
        }


        .btn-warning {
            background: #d4af63;
            border-color: #d4af63;
            color: #0b1f3a;
        }


        .btn-warning:hover {
            background: #b8924e;
            border-color: #b8924e;
            color: white;
        }


        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        .table {
            margin-bottom: 0;
        }


        .table thead th {
            background: #f5f1e8;
            color: #0b1f3a;
            font-weight: 700;
            border-bottom: 2px solid #d4af63;
            padding: 14px;
        }


        .table tbody td {
            padding: 14px;
            vertical-align: middle;
        }


        .table-hover tbody tr:hover {
            background: #fcfaf5;
        }


        /*
        |--------------------------------------------------------------------------
        | BADGES
        |--------------------------------------------------------------------------
        */

        .capacity-badge {
            background: #e7f3f5;
            color: #176b78;
            padding: 7px 11px;
            border-radius: 20px;
            font-weight: 600;
        }


        .room-count-badge {
            background: #eef1f5;
            color: #344054;
            padding: 7px 11px;
            border-radius: 20px;
            font-weight: 600;
        }


        .room-type-count {
            background: #0b1f3a;
            color: #d4af63;
            padding: 7px 13px;
            border-radius: 20px;
            font-weight: 600;
        }


        /*
        |--------------------------------------------------------------------------
        | DESCRIPTION
        |--------------------------------------------------------------------------
        */

        .description-text {
            max-width: 300px;
            display: block;
        }


        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 768px) {

            .sidebar {
                min-height: auto;
            }

            .content {
                padding: 15px;
            }

            .topbar {
                padding: 15px;
            }

        }

    </style>

</head>


<body>


<div class="container-fluid">

    <div class="row">


        <!-- =========================================================
             SIDEBAR
        ========================================================== -->

        <div class="col-md-2 p-0 sidebar">


            <!-- BRAND -->

            <a
                href="dashboard.php"
                class="brand"
            >

                <i class="bi bi-building me-2"></i>

                Velora Hotel

            </a>


            <!-- DASHBOARD -->

            <a href="dashboard.php">

                <i class="bi bi-speedometer2 me-2"></i>

                Dashboard

            </a>


            <!-- ROOMS -->

            <a href="rooms.php">

                <i class="bi bi-door-open me-2"></i>

                Rooms

            </a>


            <!-- ROOM TYPES -->

            <a
                href="room_types.php"
                class="active"
            >

                <i class="bi bi-grid me-2"></i>

                Room Types

            </a>


            <!-- CUSTOMERS -->

            <a href="customers.php">

                <i class="bi bi-people me-2"></i>

                Customers

            </a>


            <!-- BOOKINGS -->

            <a href="bookings.php">

                <i class="bi bi-calendar-check me-2"></i>

                Bookings

            </a>


            <!-- SERVICES -->

            <a href="services.php">

                <i class="bi bi-stars me-2"></i>

                Services

            </a>


            <!-- MAINTENANCE -->

            <a href="maintenance.php">

                <i class="bi bi-tools me-2"></i>

                Maintenance

            </a>


            <!-- REPORTS -->

            <a href="reports.php">

                <i class="bi bi-bar-chart me-2"></i>

                Reports

            </a>


            <hr class="text-secondary mx-3">


            <!-- LOGOUT -->

            <a href="../auth/logout.php">

                <i class="bi bi-box-arrow-right me-2"></i>

                Logout

            </a>

        </div>



        <!-- =========================================================
             MAIN CONTENT
        ========================================================== -->

        <div class="col-md-10 p-0">


            <!-- =====================================================
                 TOP BAR
            ====================================================== -->

            <div class="topbar">

                <div class="d-flex justify-content-between align-items-center">


                    <div>

                        <h5 class="mb-0">

                            Room Type Management

                        </h5>

                        <small class="text-muted">

                            Manage hotel room categories, prices and capacity

                        </small>

                    </div>


                    <div>

                        <i class="bi bi-person-circle me-2"></i>

                        <?= e($_SESSION["full_name"] ?? "Administrator") ?>

                    </div>

                </div>

            </div>



            <!-- =====================================================
                 CONTENT
            ====================================================== -->

            <div class="content">


                <!-- =================================================
                     ALERT MESSAGE
                ================================================== -->

                <?php if ($message): ?>

                    <div
                        class="alert alert-<?= e($messageType) ?> alert-dismissible fade show"
                        role="alert"
                    >

                        <i
                            class="bi
                            <?= $messageType === "success"
                                ? "bi-check-circle"
                                : "bi-exclamation-triangle"
                            ?>
                            me-2"
                        ></i>

                        <?= e($message) ?>


                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="alert"
                        ></button>

                    </div>

                <?php endif; ?>



                <!-- =================================================
                     ADD ROOM TYPE
                ================================================== -->

                <div class="card mb-4">

                    <div class="card-body">


                        <h5 class="mb-4">

                            <i
                                class="bi bi-plus-circle me-2"
                                style="color:#d4af63;"
                            ></i>

                            Add New Room Type

                        </h5>


                        <form
                            method="POST"
                            autocomplete="off"
                        >

                            <div class="row">


                                <!-- ROOM TYPE -->

                                <div class="col-md-4 mb-3">

                                    <label class="form-label">

                                        Room Type Name

                                    </label>


                                    <input
                                        type="text"
                                        name="type_name"
                                        class="form-control"
                                        placeholder="Example: Deluxe Room"
                                        required
                                    >

                                </div>



                                <!-- PRICE -->

                                <div class="col-md-3 mb-3">

                                    <label class="form-label">

                                        Price Per Night

                                    </label>


                                    <div class="input-group">

                                        <span class="input-group-text">

                                            Rs.

                                        </span>


                                        <input
                                            type="number"
                                            name="base_price"
                                            class="form-control"
                                            min="1"
                                            step="0.01"
                                            placeholder="15000"
                                            required
                                        >

                                    </div>

                                </div>



                                <!-- CAPACITY -->

                                <div class="col-md-2 mb-3">

                                    <label class="form-label">

                                        Capacity

                                    </label>


                                    <input
                                        type="number"
                                        name="capacity"
                                        class="form-control"
                                        min="1"
                                        max="20"
                                        placeholder="2"
                                        required
                                    >

                                </div>



                                <!-- BUTTON -->

                                <div class="col-md-3 mb-3 d-flex align-items-end">

                                    <button
                                        type="submit"
                                        name="add_room_type"
                                        class="btn btn-primary w-100"
                                    >

                                        <i class="bi bi-plus-lg me-1"></i>

                                        Add Room Type

                                    </button>

                                </div>



                                <!-- DESCRIPTION -->

                                <div class="col-md-12">

                                    <label class="form-label">

                                        Description

                                    </label>


                                    <textarea
                                        name="description"
                                        class="form-control"
                                        rows="3"
                                        placeholder="Describe the room type..."
                                    ></textarea>

                                </div>

                            </div>

                        </form>

                    </div>

                </div>



                <!-- =================================================
                     ROOM TYPES TABLE
                ================================================== -->

                <div class="card">

                    <div class="card-body">


                        <div
                            class="d-flex justify-content-between align-items-center mb-4"
                        >

                            <div>

                                <h5 class="mb-1">

                                    All Room Types

                                </h5>

                                <small class="text-muted">

                                    Available room categories in Velora Hotel

                                </small>

                            </div>


                            <span class="room-type-count">

                                <?= count($roomTypes) ?>

                                Room Types

                            </span>

                        </div>



                        <div class="table-responsive">


                            <table
                                class="table table-hover align-middle"
                            >

                                <thead>

                                    <tr>

                                        <th>#</th>

                                        <th>Room Type</th>

                                        <th>Description</th>

                                        <th>Capacity</th>

                                        <th>Price / Night</th>

                                        <th>Total Rooms</th>

                                        <th>Actions</th>

                                    </tr>

                                </thead>


                                <tbody>


                                <?php if (!empty($roomTypes)): ?>


                                    <?php foreach (
                                        $roomTypes
                                        as $index => $type
                                    ): ?>


                                        <tr>


                                            <!-- NUMBER -->

                                            <td>

                                                <?= $index + 1 ?>

                                            </td>



                                            <!-- ROOM TYPE -->

                                            <td>

                                                <strong>

                                                    <?= e(
                                                        $type["type_name"]
                                                    ) ?>

                                                </strong>

                                            </td>



                                            <!-- DESCRIPTION -->

                                            <td>

                                                <span
                                                    class="description-text text-muted"
                                                >

                                                    <?php

                                                    if (
                                                        !empty(
                                                            $type["description"]
                                                        )
                                                    ) {

                                                        echo e(
                                                            $type["description"]
                                                        );

                                                    } else {

                                                        echo "No description";

                                                    }

                                                    ?>

                                                </span>

                                            </td>



                                            <!-- CAPACITY -->

                                            <td>

                                                <span
                                                    class="capacity-badge"
                                                >

                                                    <i
                                                        class="bi bi-people-fill me-1"
                                                    ></i>

                                                    <?= (int) $type["capacity"] ?>

                                                    Guests

                                                </span>

                                            </td>



                                            <!-- PRICE -->

                                            <td>

                                                <strong>

                                                    Rs.
                                                    <?= number_format(
                                                        (float) $type["base_price"],
                                                        2
                                                    ) ?>

                                                </strong>

                                            </td>



                                            <!-- ROOM COUNT -->

                                            <td>

                                                <span
                                                    class="room-count-badge"
                                                >

                                                    <i
                                                        class="bi bi-door-open me-1"
                                                    ></i>

                                                    <?= (int) $type["room_count"] ?>

                                                    Rooms

                                                </span>

                                            </td>



                                            <!-- ACTIONS -->

                                            <td>

                                                <div class="d-flex gap-2">


                                                    <!-- EDIT -->

                                                    <a
                                                        href="edit_room_type.php?id=<?= (int) $type["id"] ?>"
                                                        class="btn btn-sm btn-warning"
                                                        title="Edit Room Type"
                                                    >

                                                        <i
                                                            class="bi bi-pencil"
                                                        ></i>

                                                    </a>



                                                    <!-- DELETE -->

                                                    <a
                                                        href="room_types.php?delete=<?= (int) $type["id"] ?>"
                                                        class="btn btn-sm btn-danger"
                                                        title="Delete Room Type"
                                                        onclick="return confirm('Are you sure you want to delete this room type?');"
                                                    >

                                                        <i
                                                            class="bi bi-trash"
                                                        ></i>

                                                    </a>

                                                </div>

                                            </td>


                                        </tr>


                                    <?php endforeach; ?>


                                <?php else: ?>


                                    <tr>

                                        <td
                                            colspan="7"
                                            class="text-center text-muted py-5"
                                        >

                                            <i
                                                class="bi bi-grid fs-1 d-block mb-3"
                                            ></i>

                                            No room types found.

                                        </td>

                                    </tr>


                                <?php endif; ?>


                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>


            </div>

        </div>

    </div>

</div>



<!-- =============================================================
     BOOTSTRAP JS
============================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>