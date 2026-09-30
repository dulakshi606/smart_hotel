<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

requireRole("admin");

$error = "";
$success = "";


// =====================================================
// GET ROOM ID
// =====================================================

$room_id = (int)($_GET["id"] ?? $_POST["room_id"] ?? 0);

if ($room_id <= 0) {
    die("Invalid room ID.");
}


// =====================================================
// GET ROOM
// =====================================================

$stmt = $pdo->prepare("
    SELECT
        r.*,
        rt.type_name
    FROM rooms r
    INNER JOIN room_types rt
        ON r.room_type_id = rt.id
    WHERE r.id = ?
    LIMIT 1
");

$stmt->execute([$room_id]);

$room = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$room) {
    die("Room not found.");
}


// =====================================================
// CREATE FACILITIES TABLE IF NEEDED
// =====================================================

$pdo->exec("
    CREATE TABLE IF NOT EXISTS facilities (

        id INT AUTO_INCREMENT PRIMARY KEY,

        facility_name VARCHAR(100) NOT NULL UNIQUE,

        description VARCHAR(255) NULL,

        created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP

    )
");


// =====================================================
// CREATE ROOM FACILITIES TABLE IF NEEDED
// =====================================================

$pdo->exec("
    CREATE TABLE IF NOT EXISTS room_facilities (

        id INT AUTO_INCREMENT PRIMARY KEY,

        room_id INT NOT NULL,

        facility_id INT NOT NULL,

        created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

        UNIQUE KEY unique_room_facility
        (room_id, facility_id),

        FOREIGN KEY (room_id)
        REFERENCES rooms(id)
        ON DELETE CASCADE,

        FOREIGN KEY (facility_id)
        REFERENCES facilities(id)
        ON DELETE CASCADE

    )
");


// =====================================================
// INSERT DEFAULT FACILITIES
// =====================================================

$defaultFacilities = [

    [
        "Wi-Fi",
        "Free high-speed wireless internet"
    ],

    [
        "Air Conditioning",
        "Room air conditioning"
    ],

    [
        "Television",
        "Smart or LED television"
    ],

    [
        "Hot Water",
        "24-hour hot water"
    ],

    [
        "Mini Bar",
        "In-room mini refrigerator and drinks"
    ],

    [
        "Balcony",
        "Private room balcony"
    ],

    [
        "Room Service",
        "24-hour room service"
    ],

    [
        "Hair Dryer",
        "Hair dryer available"
    ],

    [
        "Telephone",
        "In-room telephone"
    ],

    [
        "Free Parking",
        "Complimentary parking"
    ]

];


foreach ($defaultFacilities as $facility) {

    $stmt = $pdo->prepare("
        INSERT INTO facilities
        (
            facility_name,
            description
        )
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE
            description = VALUES(description)
    ");

    $stmt->execute([
        $facility[0],
        $facility[1]
    ]);
}

// =====================================================
// ADD NEW FACILITY
// =====================================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    ($_POST["action"] ?? "") === "add_facility"
) {

    $facility_name =
        trim($_POST["facility_name"] ?? "");

    $description =
        trim($_POST["description"] ?? "");


    if ($facility_name === "") {

        $error =
            "Facility name is required.";

    } else {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO facilities
                (
                    facility_name,
                    description
                )
                VALUES (?, ?)
            ");

            $stmt->execute([
                $facility_name,
                $description
            ]);

            $success =
                "Facility added successfully.";

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {

                $error =
                    "This facility already exists.";

            } else {

                $error =
                    "Database error.";

            }

        }

    }

}


// =====================================================
// SAVE ROOM FACILITIES
// =====================================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    ($_POST["action"] ?? "") === "save_facilities"
) {

    $selectedFacilities =
        $_POST["facilities"] ?? [];


    if (!is_array($selectedFacilities)) {

        $selectedFacilities = [];

    }


    try {

        $pdo->beginTransaction();


        // Remove old facilities

        $stmt = $pdo->prepare("
            DELETE FROM room_facilities
            WHERE room_id = ?
        ");

        $stmt->execute([
            $room_id
        ]);


        // Add selected facilities

        $stmt = $pdo->prepare("
            INSERT INTO room_facilities
            (
                room_id,
                facility_id
            )
            VALUES (?, ?)
        ");


        foreach (
            $selectedFacilities
            as $facility_id
        ) {

            $facility_id =
                (int)$facility_id;


            if ($facility_id > 0) {

                $stmt->execute([
                    $room_id,
                    $facility_id
                ]);

            }

        }


        $pdo->commit();


        $success =
            "Room facilities updated successfully.";

    } catch (PDOException $e) {

        if ($pdo->inTransaction()) {

            $pdo->rollBack();

        }


        $error =
            "Unable to update facilities.";

    }

}


// =====================================================
// GET ALL FACILITIES
// =====================================================

$stmt = $pdo->query("
    SELECT *
    FROM facilities
    ORDER BY facility_name
");

$facilities =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


// =====================================================
// GET CURRENT ROOM FACILITIES
// =====================================================

$stmt = $pdo->prepare("
    SELECT facility_id
    FROM room_facilities
    WHERE room_id = ?
");

$stmt->execute([
    $room_id
]);


$currentFacilities = [];

foreach (
    $stmt->fetchAll(PDO::FETCH_COLUMN)
    as $facility_id
) {

    $currentFacilities[] =
        (int)$facility_id;

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
        Room Facilities - Velora Hotel
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

            background: #f1f5f9;

            color: #0f172a;

            font-family: Arial, sans-serif;

        }


        /* ==========================================
           SIDEBAR
        ========================================== */

        .sidebar {

            position: fixed;

            left: 0;

            top: 0;

            width: 250px;

            height: 100vh;

            background: #0f172a;

            color: white;

            padding: 25px 15px;

        }


        .logo {

            color: white;

            font-size: 21px;

            font-weight: bold;

            padding: 0 15px 30px;

        }


        .logo i {

            color: #38bdf8;

        }


        .menu-title {

            color: #94a3b8;

            font-size: 11px;

            text-transform: uppercase;

            padding: 15px;

        }


        .sidebar a {

            display: flex;

            align-items: center;

            gap: 12px;

            color: #cbd5e1;

            text-decoration: none;

            padding: 12px 15px;

            border-radius: 9px;

            margin-bottom: 5px;

        }


        .sidebar a:hover,
        .sidebar a.active {

            background: #2563eb;

            color: white;

        }


        /* ==========================================
           MAIN
        ========================================== */

        .main {

            margin-left: 250px;

            padding: 30px;

        }


        .page-title {

            font-size: 28px;

            font-weight: 700;

        }


        .page-subtitle {

            color: #64748b;

        }


        /* ==========================================
           CARDS
        ========================================== */

        .card-box {

            background: white;

            border-radius: 16px;

            padding: 25px;

            box-shadow:
                0 4px 15px
                rgba(0,0,0,.05);

        }


        .room-info {

            background: #eff6ff;

            border: 1px solid #bfdbfe;

            border-radius: 14px;

            padding: 20px;

        }


        .room-number {

            font-size: 25px;

            font-weight: 700;

            color: #2563eb;

        }


        /* ==========================================
           FACILITY
        ========================================== */

        .facility-item {

            border: 1px solid #e2e8f0;

            border-radius: 12px;

            padding: 15px;

            transition: .2s;

        }


        .facility-item:hover {

            border-color: #2563eb;

            background: #f8fafc;

        }


        .facility-item.selected {

            border-color: #2563eb;

            background: #eff6ff;

        }


        .facility-icon {

            width: 42px;

            height: 42px;

            border-radius: 10px;

            background: #dbeafe;

            color: #2563eb;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 19px;

        }


        .facility-name {

            font-weight: 600;

        }


        .facility-description {

            color: #64748b;

            font-size: 13px;

        }


        /* ==========================================
           MOBILE
        ========================================== */

        @media(max-width:900px) {

            .sidebar {

                width: 70px;

            }


            .sidebar .logo-text,
            .sidebar .menu-text,
            .menu-title {

                display: none;

            }


            .sidebar a {

                justify-content: center;

            }


            .main {

                margin-left: 70px;

                padding: 15px;

            }

        }

    </style>

<link rel="stylesheet" href="../assets/css/velora.css">
</head>


<body>


<!-- ==========================================
     SIDEBAR
========================================== -->

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


    <a href="bookings.php">

        <i class="bi bi-calendar-check"></i>

        <span class="menu-text">

            Bookings

        </span>

    </a>


    <a
        href="rooms.php"
        class="active"
    >

        <i class="bi bi-door-open"></i>

        <span class="menu-text">

            Rooms

        </span>

    </a>


    <a href="room_types.php">

        <i class="bi bi-grid"></i>

        <span class="menu-text">

            Room Types

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


    <a href="services.php">

        <i class="bi bi-stars"></i>

        <span class="menu-text">

            Services

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


<!-- ==========================================
     MAIN
========================================== -->

<div class="main">


    <!-- HEADER -->

    <div class="mb-4">

        <div class="page-title">

            <i class="bi bi-stars text-primary"></i>

            Room Facilities

        </div>


        <div class="page-subtitle">

            Select the facilities available in this room.

        </div>

    </div>


    <!-- ==========================================
         ALERTS
    ========================================== -->

    <?php if ($success): ?>

        <div
            class="alert alert-success alert-dismissible fade show"
        >

            <i class="bi bi-check-circle"></i>

            <?= e($success) ?>


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

            <?= e($error) ?>


            <button
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- ==========================================
         ROOM INFORMATION
    ========================================== -->

    <div class="room-info mb-4">


        <div class="row align-items-center">


            <div class="col-md-8">


                <div class="room-number">

                    Room
                    <?= e(
                        $room["room_number"]
                    ) ?>

                </div>


                <div class="mt-1">

                    <?= e(
                        $room["type_name"]
                    ) ?>

                </div>


                <div class="text-muted mt-1">

                    Floor
                    <?= e(
                        $room["floor_number"]
                    ) ?>

                    &nbsp; • &nbsp;

                    Status:

                    <strong>

                        <?= e(
                            ucfirst(
                                $room["status"]
                            )
                        ) ?>

                    </strong>

                </div>


            </div>


            <div class="col-md-4 text-md-end mt-3 mt-md-0">


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


        </div>


    </div>


    <!-- ==========================================
         FACILITIES
    ========================================== -->

    <div class="card-box">


        <div
            class="d-flex justify-content-between align-items-center mb-4"
        >

            <div>

                <h5 class="fw-bold mb-1">

                    Available Facilities

                </h5>


                <div class="text-muted">

                    Select all facilities available in this room.

                </div>

            </div>


            <span class="badge bg-primary">

                <?= count(
                    $currentFacilities
                ) ?>

                Selected

            </span>

        </div>


        <form method="POST">


            <input
                type="hidden"
                name="room_id"
                value="<?= $room_id ?>"
            >


            <input
                type="hidden"
                name="action"
                value="save_facilities"
            >


            <div class="row g-3">


                <?php foreach (
                    $facilities
                    as $facility
                ): ?>


                    <?php

                    $facilityId =
                        (int)$facility["id"];

                    $isSelected =
                        in_array(
                            $facilityId,
                            $currentFacilities,
                            true
                        );

                    ?>


                    <div class="col-md-6 col-lg-4">


                        <label
                            class="
                                facility-item
                                w-100
                                h-100
                                d-flex
                                align-items-center
                                gap-3
                            "
                        >


                            <input
                                type="checkbox"
                                name="facilities[]"
                                value="<?= $facilityId ?>"
                                class="form-check-input facility-checkbox"
                                <?= $isSelected
                                    ? "checked"
                                    : ""
                                ?>
                            >


                            <div
                                class="facility-icon"
                            >

                                <i
                                    class="bi bi-check2-circle"
                                ></i>

                            </div>


                            <div>


                                <div
                                    class="facility-name"
                                >

                                    <?= e(
                                        $facility[
                                            "facility_name"
                                        ]
                                    ) ?>

                                </div>


                                <div
                                    class="facility-description"
                                >

                                    <?= e(
                                        $facility[
                                            "description"
                                        ]
                                    ) ?>

                                </div>


                            </div>


                        </label>


                    </div>


                <?php endforeach; ?>


            </div>


            <hr class="my-4">


            <div
                class="
                    d-flex
                    justify-content-between
                    align-items-center
                "
            >


                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    onclick="selectAllFacilities()"
                >

                    <i
                        class="bi bi-check-all"
                    ></i>

                    Select All

                </button>


                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    <i
                        class="bi bi-save"
                    ></i>

                    Save Facilities

                </button>


            </div>


        </form>


    </div>


    <!-- ==========================================
         ADD FACILITY
    ========================================== -->

    <div class="card-box mt-4">


        <h5 class="fw-bold mb-1">

            Add New Facility

        </h5>


        <p class="text-muted">

            Add a facility that is not currently listed.

        </p>


        <form method="POST">


            <input
                type="hidden"
                name="room_id"
                value="<?= $room_id ?>"
            >


            <input
                type="hidden"
                name="action"
                value="add_facility"
            >


            <div class="row">


                <div class="col-md-5 mb-3">


                    <label
                        class="form-label"
                    >

                        Facility Name

                    </label>


                    <input
                        type="text"
                        name="facility_name"
                        class="form-control"
                        placeholder="Example: Swimming Pool"
                        required
                    >

                </div>


                <div class="col-md-5 mb-3">


                    <label
                        class="form-label"
                    >

                        Description

                    </label>


                    <input
                        type="text"
                        name="description"
                        class="form-control"
                        placeholder="Facility description"
                    >

                </div>


                <div
                    class="col-md-2 d-flex align-items-end mb-3"
                >

                    <button
                        type="submit"
                        class="btn btn-success w-100"
                    >

                        <i
                            class="bi bi-plus-lg"
                        ></i>

                        Add

                    </button>

                </div>


            </div>


        </form>


    </div>


</div>


<script>

function selectAllFacilities() {

    const checkboxes =
        document.querySelectorAll(
            ".facility-checkbox"
        );


    checkboxes.forEach(
        function(checkbox) {

            checkbox.checked = true;

        }
    );

}

</script>


</body>

</html>