<?php

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================
   CHECK LOGIN
========================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}


/* =========================
   GET BOOKING ID
========================= */

$booking_id = isset($_GET['booking_id'])
    ? (int)$_GET['booking_id']
    : 0;

if ($booking_id <= 0) {
    die("Invalid booking.");
}


/* =========================
   GET BOOKING
========================= */

$stmt = $pdo->prepare("
    SELECT
        b.id,
        b.booking_code,
        b.check_in,
        b.check_out,
        b.total_amount,
        r.room_number
    FROM bookings b
    INNER JOIN rooms r
        ON b.room_id = r.id
    WHERE b.id = ?
      AND b.customer_id = ?
");

$stmt->execute([
    $booking_id,
    $_SESSION['user_id']
]);

$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    die("Booking not found or access denied.");
}


/* =========================
   ADD SERVICE
========================= */

if (isset($_POST['add_service'])) {

    $service_id = (int)($_POST['service_id'] ?? 0);
    $quantity   = (int)($_POST['quantity'] ?? 1);

    if ($service_id <= 0 || $quantity <= 0) {

        $_SESSION['error'] =
            "Please select a valid service and quantity.";

        header(
            "Location: add_services.php?booking_id=" .
            $booking_id
        );

        exit;
    }


    /* GET SERVICE */

    $stmt = $pdo->prepare("
        SELECT
            id,
            service_name,
            price
        FROM services
        WHERE id = ?
          AND status = 'active'
    ");

    $stmt->execute([$service_id]);

    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$service) {

        $_SESSION['error'] =
            "Selected service is not available.";

        header(
            "Location: add_services.php?booking_id=" .
            $booking_id
        );

        exit;
    }


    /* CALCULATE */

    $unit_price = (float)$service['price'];

    $total_price = $unit_price * $quantity;


    /* INSERT */

    $stmt = $pdo->prepare("
        INSERT INTO service_orders
        (
            booking_id,
            service_id,
            quantity,
            unit_price,
            total_price,
            status
        )
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");

    $stmt->execute([
        $booking_id,
        $service_id,
        $quantity,
        $unit_price,
        $total_price
    ]);


    $_SESSION['success'] =
        "Service added successfully.";

    header(
        "Location: add_services.php?booking_id=" .
        $booking_id
    );

    exit;
}


/* =========================
   DELETE SERVICE ORDER
========================= */

if (isset($_GET['delete'])) {

    $order_id = (int)$_GET['delete'];

    $stmt = $pdo->prepare("
        DELETE FROM service_orders
        WHERE id = ?
          AND booking_id = ?
    ");

    $stmt->execute([
        $order_id,
        $booking_id
    ]);

    $_SESSION['success'] =
        "Service removed successfully.";

    header(
        "Location: add_services.php?booking_id=" .
        $booking_id
    );

    exit;
}


/* =========================
   GET ACTIVE SERVICES
========================= */

$stmt = $pdo->query("
    SELECT
        id,
        service_name,
        description,
        price
    FROM services
    WHERE status = 'active'
    ORDER BY service_name ASC
");

$services = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   GET ORDERED SERVICES
========================= */

$stmt = $pdo->prepare("
    SELECT
        so.id,
        so.quantity,
        so.unit_price,
        so.total_price,
        so.status,
        s.service_name
    FROM service_orders so
    INNER JOIN services s
        ON so.service_id = s.id
    WHERE so.booking_id = ?
    ORDER BY so.id DESC
");

$stmt->execute([$booking_id]);

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   SERVICE TOTAL
========================= */

$service_total = 0;

foreach ($orders as $order) {
    $service_total += (float)$order['total_price'];
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Extra Services - Velora Hotel</title>

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
    max-width: 1100px;
    margin: 40px auto;
    padding: 20px;
}

.header {
    margin-bottom: 25px;
}

.header h1 {
    font-size: 30px;
    margin-bottom: 8px;
}

.header p {
    color: #64748b;
}

.card {
    background: #fff;
    padding: 25px;
    border-radius: 16px;
    margin-bottom: 25px;
    box-shadow:
        0 5px 20px rgba(15,23,42,.08);
}

.booking-info {
    display: grid;
    grid-template-columns:
        repeat(4, 1fr);
    gap: 15px;
}

.info-box {
    background: #f8fafc;
    padding: 18px;
    border-radius: 10px;
}

.info-label {
    color: #64748b;
    font-size: 13px;
    margin-bottom: 5px;
}

.info-value {
    font-size: 17px;
    font-weight: 600;
}

.form-grid {
    display: grid;
    grid-template-columns: 2fr 1fr auto;
    gap: 15px;
    align-items: end;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 7px;
}

select,
input {
    width: 100%;
    padding: 12px;
    border: 1px solid #dbe3ef;
    border-radius: 8px;
    font-size: 15px;
    color: #0f172a;
    background: #fff;
}

.btn {
    display: inline-block;
    border: none;
    padding: 12px 18px;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
}

.btn-primary {
    background: #2563eb;
    color: #fff;
}

.btn-primary:hover {
    background: #1d4ed8;
}

.btn-danger {
    background: #dc2626;
    color: #fff;
}

.btn-secondary {
    background: #64748b;
    color: #fff;
}

.alert {
    padding: 14px 18px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.success {
    background: #dcfce7;
    color: #166534;
}

.error {
    background: #fee2e2;
    color: #991b1b;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    padding: 14px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
}

th {
    background: #f8fafc;
}

.price {
    font-weight: 600;
    color: #2563eb;
}

.total-box {
    margin-top: 20px;
    padding: 20px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    display: flex;
    justify-content: space-between;
    font-size: 20px;
    font-weight: bold;
}

.actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

@media(max-width: 750px) {

    .booking-info {
        grid-template-columns: 1fr 1fr;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    table {
        font-size: 13px;
    }

}

</style>

<link rel="stylesheet" href="../assets/css/velora.css">
</head>

<body>

<div class="container">


    <div class="header">

        <h1>🛎️ Extra Services</h1>

        <p>
            Add additional services to your hotel booking.
        </p>

    </div>


    <?php if (!empty($_SESSION['success'])): ?>

        <div class="alert success">

            <?= htmlspecialchars(
                $_SESSION['success']
            ) ?>

        </div>

        <?php unset($_SESSION['success']); ?>

    <?php endif; ?>


    <?php if (!empty($_SESSION['error'])): ?>

        <div class="alert error">

            <?= htmlspecialchars(
                $_SESSION['error']
            ) ?>

        </div>

        <?php unset($_SESSION['error']); ?>

    <?php endif; ?>


    <!-- BOOKING INFORMATION -->

    <div class="card">

        <h2>Booking Information</h2>

        <br>

        <div class="booking-info">

            <div class="info-box">

                <div class="info-label">
                    Booking Code
                </div>

                <div class="info-value">
                    <?= htmlspecialchars(
                        $booking['booking_code']
                    ) ?>
                </div>

            </div>


            <div class="info-box">

                <div class="info-label">
                    Room
                </div>

                <div class="info-value">
                    Room <?= htmlspecialchars(
                        $booking['room_number']
                    ) ?>
                </div>

            </div>


            <div class="info-box">

                <div class="info-label">
                    Check-in
                </div>

                <div class="info-value">
                    <?= htmlspecialchars(
                        $booking['check_in']
                    ) ?>
                </div>

            </div>


            <div class="info-box">

                <div class="info-label">
                    Check-out
                </div>

                <div class="info-value">
                    <?= htmlspecialchars(
                        $booking['check_out']
                    ) ?>
                </div>

            </div>

        </div>

    </div>


    <!-- ADD SERVICE -->

    <div class="card">

        <h2>➕ Add Service</h2>

        <br>

        <form method="POST">

            <div class="form-grid">


                <div class="form-group">

                    <label>
                        Select Service
                    </label>

                    <select
                        name="service_id"
                        required
                    >

                        <option value="">
                            -- Select Service --
                        </option>

                        <?php foreach (
                            $services
                            as $service
                        ): ?>

                            <option
                                value="<?= (int)$service['id'] ?>"
                            >

                                <?= htmlspecialchars(
                                    $service['service_name']
                                ) ?>

                                -
                                Rs.
                                <?= number_format(
                                    (float)$service['price'],
                                    2
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Quantity
                    </label>

                    <input
                        type="number"
                        name="quantity"
                        value="1"
                        min="1"
                        required
                    >

                </div>


                <button
                    type="submit"
                    name="add_service"
                    class="btn btn-primary"
                >
                    Add Service
                </button>


            </div>

        </form>

    </div>


    <!-- ORDERED SERVICES -->

    <div class="card">

        <h2>📋 Selected Services</h2>

        <br>

        <?php if (count($orders) > 0): ?>

            <div style="overflow-x:auto;">

                <table>

                    <thead>

                        <tr>

                            <th>Service</th>

                            <th>Quantity</th>

                            <th>Unit Price</th>

                            <th>Total</th>

                            <th>Status</th>

                            <th>Action</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach (
                        $orders
                        as $order
                    ): ?>

                        <tr>

                            <td>
                                <strong>
                                    <?= htmlspecialchars(
                                        $order['service_name']
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <?= (int)$order['quantity'] ?>
                            </td>

                            <td>
                                Rs.
                                <?= number_format(
                                    (float)$order['unit_price'],
                                    2
                                ) ?>
                            </td>

                            <td class="price">
                                Rs.
                                <?= number_format(
                                    (float)$order['total_price'],
                                    2
                                ) ?>
                            </td>

                            <td>
                                <?= ucfirst(
                                    $order['status']
                                ) ?>
                            </td>

                            <td>

                                <a
                                    href="add_services.php?booking_id=<?= $booking_id ?>&delete=<?= (int)$order['id'] ?>"
                                    class="btn btn-danger"
                                    onclick="return confirm('Remove this service?');"
                                >
                                    Remove
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <p style="color:#64748b;">
                No extra services have been added.
            </p>

        <?php endif; ?>


        <div class="total-box">

            <span>
                Extra Services Total
            </span>

            <span>
                Rs.
                <?= number_format(
                    $service_total,
                    2
                ) ?>
            </span>

        </div>


        <div class="actions">

            <a
                href="dashboard.php"
                class="btn btn-secondary"
            >
                ← Back to Dashboard
            </a>

        </div>

    </div>


</div>

</body>

</html>
