<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

requireRole("admin");


$id = (int) ($_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: room_types.php");
    exit;
}


// Get room type

$stmt = $pdo->prepare("
    SELECT *
    FROM room_types
    WHERE id = ?
");

$stmt->execute([$id]);

$roomType = $stmt->fetch();

if (!$roomType) {

    header("Location: room_types.php");
    exit;
}


$message = "";
$messageType = "danger";


// Update

if (isset($_POST["update_room_type"])) {

    $type_name = trim($_POST["type_name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $base_price = $_POST["base_price"] ?? "";
    $capacity = $_POST["capacity"] ?? "";


    if (
        empty($type_name) ||
        empty($base_price) ||
        empty($capacity)
    ) {

        $message = "Please fill all required fields.";

    } elseif (
        !is_numeric($base_price) ||
        $base_price <= 0
    ) {

        $message = "Invalid price.";

    } elseif (
        !is_numeric($capacity) ||
        $capacity <= 0
    ) {

        $message = "Invalid capacity.";

    } else {

        // Check duplicate name

        $check = $pdo->prepare("
            SELECT id
            FROM room_types
            WHERE type_name = ?
            AND id != ?
        ");

        $check->execute([
            $type_name,
            $id
        ]);

        if ($check->fetch()) {

            $message = "Another room type already uses this name.";

        } else {

            $stmt = $pdo->prepare("
                UPDATE room_types

                SET
                    type_name = ?,
                    description = ?,
                    base_price = ?,
                    capacity = ?

                WHERE id = ?
            ");

            $stmt->execute([
                $type_name,
                $description,
                $base_price,
                $capacity,
                $id
            ]);


            header(
                "Location: room_types.php?updated=1"
            );

            exit;
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

    <title>Edit Room Type</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

<link rel="stylesheet" href="../assets/css/velora.css">
</head>

<body class="bg-light">


<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-7">

            <div class="card shadow border-0">

                <div class="card-body p-4">

                    <div class="d-flex justify-content-between mb-4">

                        <h4>
                            Edit Room Type
                        </h4>

                        <a
                            href="room_types.php"
                            class="btn btn-secondary"
                        >
                            Back
                        </a>

                    </div>


                    <?php if ($message): ?>

                        <div class="alert alert-danger">

                            <?= e($message) ?>

                        </div>

                    <?php endif; ?>


                    <form method="POST">


                        <div class="mb-3">

                            <label class="form-label">
                                Room Type Name
                            </label>

                            <input
                                type="text"
                                name="type_name"
                                class="form-control"
                                value="<?= e(
                                    $roomType["type_name"]
                                ) ?>"
                                required
                            >

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Description
                            </label>

                            <textarea
                                name="description"
                                class="form-control"
                                rows="4"
                            ><?= e(
                                $roomType["description"]
                            ) ?></textarea>

                        </div>


                        <div class="row">


                            <div class="col-md-6">

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
                                        value="<?= e(
                                            $roomType["base_price"]
                                        ) ?>"
                                        required
                                    >

                                </div>

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">
                                    Capacity
                                </label>

                                <input
                                    type="number"
                                    name="capacity"
                                    class="form-control"
                                    min="1"
                                    value="<?= e(
                                        $roomType["capacity"]
                                    ) ?>"
                                    required
                                >

                            </div>

                        </div>


                        <div class="mt-4">

                            <button
                                type="submit"
                                name="update_room_type"
                                class="btn btn-primary"
                            >

                                Update Room Type

                            </button>


                            <a
                                href="room_types.php"
                                class="btn btn-secondary"
                            >

                                Cancel

                            </a>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>