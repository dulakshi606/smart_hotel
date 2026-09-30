<?php
require_once "../config/db.php";
require_once "../includes/functions.php";

requireRole("admin");

$message = "";
$messageType = "success";

/* ADD FACILITY */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_facility"])) {

    $facility_name = trim($_POST["facility_name"] ?? "");
    $description = trim($_POST["description"] ?? "");

    if ($facility_name === "") {
        $message = "Facility name is required.";
        $messageType = "danger";
    } else {

        $check = $pdo->prepare(
            "SELECT id FROM facilities WHERE facility_name = ?"
        );
        $check->execute([$facility_name]);

        if ($check->fetch()) {
            $message = "This facility already exists.";
            $messageType = "danger";
        } else {

            $stmt = $pdo->prepare(
                "INSERT INTO facilities (facility_name, description)
                 VALUES (?, ?)"
            );

            $stmt->execute([
                $facility_name,
                $description
            ]);

            $message = "Facility added successfully.";
        }
    }
}


/* DELETE FACILITY */
if (isset($_GET["delete"])) {

    $id = (int) $_GET["delete"];

    try {

        $stmt = $pdo->prepare(
            "DELETE FROM facilities WHERE id = ?"
        );

        $stmt->execute([$id]);

        $message = "Facility deleted successfully.";

    } catch (PDOException $e) {

        $message = "Cannot delete this facility because it is assigned to a room.";
        $messageType = "danger";
    }
}


/* GET ALL FACILITIES */
$stmt = $pdo->query(
    "SELECT * FROM facilities ORDER BY id DESC"
);

$facilities = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
<link rel="stylesheet" href="../assets/css/style.css">
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Facilities - Velora Hotel</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

<link rel="stylesheet" href="../assets/css/velora.css">
</head>

<body class="bg-light">

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold">Room Facilities</h2>
            <p class="text-muted mb-0">
                Manage facilities available in hotel rooms
            </p>
        </div>

        <a href="dashboard.php"
           class="btn btn-secondary">
            ← Dashboard
        </a>

    </div>


    <?php if ($message): ?>

        <div class="alert alert-<?php echo e($messageType); ?>">
            <?php echo e($message); ?>
        </div>

    <?php endif; ?>


    <!-- ADD FACILITY -->

    <div class="card shadow-sm mb-4">

        <div class="card-header bg-primary text-white">

            <h5 class="mb-0">
                Add New Facility
            </h5>

        </div>

        <div class="card-body">

            <form method="POST">

                <div class="row">

                    <div class="col-md-5 mb-3">

                        <label class="form-label">
                            Facility Name
                        </label>

                        <input
                            type="text"
                            name="facility_name"
                            class="form-control"
                            placeholder="Example: Free WiFi"
                            required
                        >

                    </div>


                    <div class="col-md-5 mb-3">

                        <label class="form-label">
                            Description
                        </label>

                        <input
                            type="text"
                            name="description"
                            class="form-control"
                            placeholder="Example: High-speed internet"
                        >

                    </div>


                    <div class="col-md-2 mb-3 d-flex align-items-end">

                        <button
                            type="submit"
                            name="add_facility"
                            class="btn btn-primary w-100"
                        >
                            + Add
                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <!-- FACILITY LIST -->

    <div class="card shadow-sm">

        <div class="card-header">

            <h5 class="mb-0">
                All Facilities
            </h5>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle">

                    <thead class="table-light">

                        <tr>

                            <th>ID</th>

                            <th>Facility Name</th>

                            <th>Description</th>

                            <th width="120">
                                Action
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if (empty($facilities)): ?>

                        <tr>

                            <td colspan="4"
                                class="text-center text-muted">

                                No facilities found.

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($facilities as $facility): ?>

                            <tr>

                                <td>
                                    <?php echo e($facility["id"]); ?>
                                </td>

                                <td>
                                    <strong>
                                        <?php
                                        echo e(
                                            $facility["facility_name"]
                                        );
                                        ?>
                                    </strong>
                                </td>

                                <td>
                                    <?php
                                    echo e(
                                        $facility["description"]
                                    );
                                    ?>
                                </td>

                                <td>

                                    <a
                                        href="facilities.php?delete=<?php echo $facility["id"]; ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Delete this facility?');"
                                    >
                                        Delete
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

</body>

</html>