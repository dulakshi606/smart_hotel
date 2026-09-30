<?php

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================
   CHECK SERVICE ID
========================= */

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: services.php");
    exit;
}


/* =========================
   GET SERVICE
========================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM services
    WHERE id = ?
");

$stmt->execute([$id]);

$service = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$service) {
    $_SESSION['error'] = "Service not found.";
    header("Location: services.php");
    exit;
}


/* =========================
   UPDATE SERVICE
========================= */

if (isset($_POST['update_service'])) {

    $service_name = trim($_POST['service_name'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $price        = (float)($_POST['price'] ?? 0);
    $status       = $_POST['status'] ?? 'active';


    if ($service_name === '') {

        $error = "Service name is required.";

    } elseif ($price < 0) {

        $error = "Price cannot be negative.";

    } else {

        $stmt = $pdo->prepare("
            UPDATE services
            SET
                service_name = ?,
                description = ?,
                price = ?,
                status = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $service_name,
            $description,
            $price,
            $status,
            $id
        ]);

        $_SESSION['success'] = "Service updated successfully.";

        header("Location: services.php");
        exit;
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>
<link rel="stylesheet" href="../assets/css/style.css">
<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Edit Service - Velora Hotel</title>

<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {

    font-family: Arial, sans-serif;

    background: #f1f5f9;

    color: #0f172a;

}

.container {

    max-width: 750px;

    margin: 50px auto;

    padding: 20px;

}

.card {

    background: #ffffff;

    padding: 30px;

    border-radius: 18px;

    box-shadow:
        0 10px 30px rgba(15, 23, 42, 0.08);

}

.header {

    margin-bottom: 25px;

}

.header h1 {

    font-size: 28px;

    margin-bottom: 6px;

}

.header p {

    color: #64748b;

}

.form-group {

    margin-bottom: 20px;

}

label {

    display: block;

    font-weight: 600;

    margin-bottom: 8px;

}

input,
textarea,
select {

    width: 100%;

    padding: 13px;

    border: 1px solid #dbe3ef;

    border-radius: 9px;

    font-size: 15px;

    color: #0f172a;

    background: #ffffff;

}

textarea {

    min-height: 120px;

    resize: vertical;

}

input:focus,
textarea:focus,
select:focus {

    outline: none;

    border-color: #2563eb;

    box-shadow:
        0 0 0 3px rgba(37, 99, 235, 0.10);

}

.buttons {

    display: flex;

    gap: 12px;

    margin-top: 25px;

}

.btn {

    display: inline-block;

    padding: 12px 20px;

    border-radius: 8px;

    border: none;

    text-decoration: none;

    cursor: pointer;

    font-size: 15px;

}

.btn-primary {

    background: #2563eb;

    color: #ffffff;

}

.btn-primary:hover {

    background: #1d4ed8;

}

.btn-secondary {

    background: #e2e8f0;

    color: #0f172a;

}

.btn-secondary:hover {

    background: #cbd5e1;

}

.error {

    background: #fee2e2;

    color: #991b1b;

    padding: 14px;

    border-radius: 8px;

    margin-bottom: 20px;

}

.current-price {

    margin-top: 7px;

    color: #64748b;

    font-size: 14px;

}

</style>

<link rel="stylesheet" href="../assets/css/velora.css">
</head>


<body>


<div class="container">


    <div class="card">


        <div class="header">

            <h1>✏️ Edit Service</h1>

            <p>
                Update hotel service information
            </p>

        </div>


        <?php if (!empty($error)): ?>

            <div class="error">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <!-- SERVICE NAME -->

            <div class="form-group">

                <label>
                    Service Name
                </label>

                <input
                    type="text"
                    name="service_name"
                    value="<?= htmlspecialchars(
                        $service['service_name']
                    ) ?>"
                    required
                >

            </div>


            <!-- DESCRIPTION -->

            <div class="form-group">

                <label>
                    Description
                </label>

                <textarea
                    name="description"
                ><?= htmlspecialchars(
                    $service['description'] ?? ''
                ) ?></textarea>

            </div>


            <!-- PRICE -->

            <div class="form-group">

                <label>
                    Price (Rs.)
                </label>

                <input
                    type="number"
                    name="price"
                    min="0"
                    step="0.01"
                    value="<?= htmlspecialchars(
                        $service['price']
                    ) ?>"
                    required
                >

                <div class="current-price">

                    Current price:
                    Rs.
                    <?= number_format(
                        (float)$service['price'],
                        2
                    ) ?>

                </div>

            </div>


            <!-- STATUS -->

            <div class="form-group">

                <label>
                    Status
                </label>

                <select name="status">

                    <option
                        value="active"
                        <?= $service['status'] === 'active'
                            ? 'selected'
                            : '' ?>
                    >
                        Active
                    </option>

                    <option
                        value="inactive"
                        <?= $service['status'] === 'inactive'
                            ? 'selected'
                            : '' ?>
                    >
                        Inactive
                    </option>

                </select>

            </div>


            <!-- BUTTONS -->

            <div class="buttons">

                <button
                    type="submit"
                    name="update_service"
                    class="btn btn-primary"
                >
                    💾 Update Service
                </button>

                <a
                    href="services.php"
                    class="btn btn-secondary"
                >
                    ← Back
                </a>

            </div>


        </form>


    </div>


</div>


</body>

</html>