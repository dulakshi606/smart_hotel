<?php

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================
   UPDATE SERVICE STATUS
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['update_status'])) {

    $order_id = (int)($_POST['order_id'] ?? 0);
    $status   = $_POST['status'] ?? '';

    $allowed_statuses = [
        'pending',
        'confirmed',
        'completed',
        'cancelled'
    ];

    if (
        $order_id > 0 &&
        in_array($status, $allowed_statuses, true)
    ) {

        $stmt = $pdo->prepare("
            UPDATE service_orders
            SET status = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $status,
            $order_id
        ]);

        $_SESSION['success'] =
            "Service order status updated successfully.";

    } else {

        $_SESSION['error'] =
            "Invalid service order.";
    }

    header("Location: service_orders.php");
    exit;
}


/* =========================
   DELETE SERVICE ORDER
========================= */

if (isset($_GET['delete'])) {

    $order_id = (int)$_GET['delete'];

    if ($order_id > 0) {

        $stmt = $pdo->prepare("
            DELETE FROM service_orders
            WHERE id = ?
        ");

        $stmt->execute([$order_id]);

        $_SESSION['success'] =
            "Service order deleted successfully.";
    }

    header("Location: service_orders.php");
    exit;
}


/* =========================
   GET SERVICE ORDERS
========================= */

$stmt = $pdo->query("
    SELECT
        so.id,
        so.booking_id,
        so.service_id,
        so.quantity,
        so.unit_price,
        so.total_price,
        so.status,
        so.created_at,

        s.service_name,

        b.booking_code,
        b.check_in,
        b.check_out,

        u.full_name,
        u.email

    FROM service_orders so

    INNER JOIN services s
        ON so.service_id = s.id

    INNER JOIN bookings b
        ON so.booking_id = b.id

    INNER JOIN users u
        ON b.customer_id = u.id

    ORDER BY so.id DESC
");

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   CALCULATE TOTAL
========================= */

$grand_total = 0;

foreach ($orders as &$order) {

    $quantity = (int)$order['quantity'];
    $unit_price = (float)$order['unit_price'];

    // Always calculate total from quantity × unit price
    $order['calculated_total'] = $quantity * $unit_price;

    if ($order['status'] !== 'cancelled') {
        $grand_total += $order['calculated_total'];
    }
}

unset($order);

?>

<!DOCTYPE html>

<html lang="en">

<head>
<link rel="stylesheet" href="../assets/css/style.css">
<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Service Orders - Velora Hotel</title>

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


/* =========================
   SIDEBAR
========================= */

.sidebar {

    position: fixed;

    left: 0;

    top: 0;

    width: 250px;

    height: 100vh;

    background: #0f172a;

    padding: 30px 15px;

    color: white;
}

.logo {

    font-size: 24px;

    font-weight: bold;

    padding: 0 15px;

    margin-bottom: 40px;
}

.logo span {

    color: #06b6d4;
}

.menu-title {

    color: #94a3b8;

    font-size: 12px;

    margin: 25px 15px 10px;

    text-transform: uppercase;
}

.menu a {

    display: block;

    padding: 13px 15px;

    margin-bottom: 5px;

    border-radius: 9px;

    color: #cbd5e1;

    text-decoration: none;

    font-size: 14px;
}

.menu a:hover,
.menu a.active {

    background: #2563eb;

    color: white;
}


/* =========================
   MAIN
========================= */

.main {

    margin-left: 250px;

    padding: 35px;
}

.header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 30px;
}

.header h1 {

    font-size: 30px;
}

.header p {

    color: #64748b;

    margin-top: 5px;
}


/* =========================
   ALERT
========================= */

.alert {

    padding: 14px 18px;

    border-radius: 9px;

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


/* =========================
   STAT CARDS
========================= */

.stats {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 20px;

    margin-bottom: 25px;
}

.stat-card {

    background: white;

    padding: 22px;

    border-radius: 15px;

    box-shadow:
        0 5px 20px rgba(15,23,42,.06);
}

.stat-title {

    color: #64748b;

    font-size: 14px;

    margin-bottom: 8px;
}

.stat-value {

    font-size: 25px;

    font-weight: bold;
}


/* =========================
   CARD
========================= */

.card {

    background: white;

    border-radius: 16px;

    padding: 25px;

    box-shadow:
        0 5px 20px rgba(15,23,42,.06);
}


/* =========================
   TABLE
========================= */

.table-wrapper {

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1100px;
}

th {

    background: #f8fafc;

    color: #475569;

    font-size: 13px;

    text-align: left;

    padding: 14px;

    white-space: nowrap;
}

td {

    padding: 14px;

    border-top: 1px solid #e2e8f0;

    vertical-align: top;
}

.customer {

    font-weight: bold;
}

.email {

    color: #64748b;

    font-size: 12px;

    margin-top: 4px;
}

.booking {

    font-weight: bold;

    color: #2563eb;
}

.price {

    font-weight: bold;

    color: #2563eb;

    white-space: nowrap;
}


/* =========================
   STATUS
========================= */

.status {

    display: inline-block;

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;
}

.status-pending {

    background: #fef3c7;

    color: #92400e;
}

.status-confirmed {

    background: #dbeafe;

    color: #1e40af;
}

.status-completed {

    background: #dcfce7;

    color: #166534;
}

.status-cancelled {

    background: #fee2e2;

    color: #991b1b;
}


/* =========================
   FORM
========================= */

.status-form {

    display: flex;

    gap: 7px;

    align-items: center;
}

.status-form select {

    padding: 8px;

    border: 1px solid #dbe3ef;

    border-radius: 7px;

    font-size: 12px;

    color: #0f172a;

    background: white;
}

.update-btn {

    border: none;

    background: #2563eb;

    color: white;

    padding: 8px 11px;

    border-radius: 7px;

    cursor: pointer;

    font-size: 12px;
}

.update-btn:hover {

    background: #1d4ed8;
}

.delete {

    display: inline-block;

    margin-top: 8px;

    color: #dc2626;

    font-size: 12px;

    text-decoration: none;

    font-weight: bold;
}


/* =========================
   TOTAL
========================= */

.total {

    margin-top: 20px;

    padding: 18px;

    background: #eff6ff;

    border: 1px solid #bfdbfe;

    border-radius: 10px;

    display: flex;

    justify-content: space-between;

    font-size: 18px;

    font-weight: bold;
}


/* =========================
   EMPTY
========================= */

.empty {

    text-align: center;

    padding: 60px;

    color: #64748b;
}


/* =========================
   RESPONSIVE
========================= */

@media(max-width: 900px) {

    .sidebar {

        width: 210px;
    }

    .main {

        margin-left: 210px;

        padding: 20px;
    }

    .stats {

        grid-template-columns: 1fr;
    }

}

</style>

<link rel="stylesheet" href="../assets/css/velora.css">
</head>

<body>


<!-- =========================
     SIDEBAR
========================= -->

<div class="sidebar">

    <div class="logo">

        🏨 Smart <span>Hotel</span>

    </div>


    <div class="menu">

        <div class="menu-title">
            Management
        </div>

        <a href="dashboard.php">
            📊 Dashboard
        </a>

        <a href="bookings.php">
            📅 Bookings
        </a>

        <a href="rooms.php">
            🛏 Rooms
        </a>

        <a href="room_types.php">
            ▦ Room Types
        </a>

        <a href="customers.php">
            👥 Customers
        </a>


        <div class="menu-title">
            Services
        </div>

        <a href="services.php">
            ✨ Services
        </a>

        <a href="service_orders.php"
           class="active">
            🛎 Service Orders
        </a>


        <div class="menu-title">
            System
        </div>

        <a href="reports.php">
            📈 Reports
        </a>

        <a href="logout.php">
            🚪 Logout
        </a>

    </div>

</div>


<!-- =========================
     MAIN
========================= -->

<div class="main">


    <div class="header">

        <div>

            <h1>
                Service Orders
            </h1>

            <p>
                Manage customer hotel service requests.
            </p>

        </div>

    </div>


    <!-- ALERTS -->

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


    <!-- =========================
         STATS
    ========================= -->

    <?php

    $total_orders = count($orders);

    $pending_orders = 0;

    $completed_orders = 0;

    foreach ($orders as $order) {

        if ($order['status'] === 'pending') {
            $pending_orders++;
        }

        if ($order['status'] === 'completed') {
            $completed_orders++;
        }
    }

    ?>


    <div class="stats">

        <div class="stat-card">

            <div class="stat-title">
                Total Orders
            </div>

            <div class="stat-value">
                <?= $total_orders ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-title">
                Pending Orders
            </div>

            <div class="stat-value">
                <?= $pending_orders ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-title">
                Completed Orders
            </div>

            <div class="stat-value">
                <?= $completed_orders ?>
            </div>

        </div>

    </div>


    <!-- =========================
         ORDERS
    ========================= -->

    <div class="card">

        <h2>
            Customer Service Requests
        </h2>

        <br>


        <?php if (count($orders) > 0): ?>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Order
                            </th>

                            <th>
                                Customer
                            </th>

                            <th>
                                Booking
                            </th>

                            <th>
                                Service
                            </th>

                            <th>
                                Qty
                            </th>

                            <th>
                                Unit Price
                            </th>

                            <th>
                                Total
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach (
                        $orders
                        as $order
                    ): ?>


                        <tr>


                            <td>

                                #<?= (int)$order['id'] ?>

                            </td>


                            <td>

                                <div class="customer">

                                    <?= htmlspecialchars(
                                        $order['full_name']
                                    ) ?>

                                </div>

                                <div class="email">

                                    <?= htmlspecialchars(
                                        $order['email']
                                    ) ?>

                                </div>

                            </td>


                            <td>

                                <div class="booking">

                                    <?= htmlspecialchars(
                                        $order['booking_code']
                                    ) ?>

                                </div>

                                <div class="email">

                                    <?= htmlspecialchars(
                                        $order['check_in']
                                    ) ?>

                                    →

                                    <?= htmlspecialchars(
                                        $order['check_out']
                                    ) ?>

                                </div>

                            </td>


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
    (float)$order['calculated_total'],
    2
) ?>

                            </td>


                            <td>

                                <span class="status
                                    status-<?= htmlspecialchars(
                                        $order['status']
                                    ) ?>">

                                    <?= ucfirst(
                                        $order['status']
                                    ) ?>

                                </span>

                            </td>


                            <td>


                                <form
                                    method="POST"
                                    class="status-form"
                                >

                                    <input
                                        type="hidden"
                                        name="order_id"
                                        value="<?= (int)$order['id'] ?>"
                                    >


                                    <select
                                        name="status"
                                    >

                                        <option
                                            value="pending"
                                            <?= $order['status'] === 'pending'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Pending
                                        </option>


                                        <option
                                            value="confirmed"
                                            <?= $order['status'] === 'confirmed'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Confirmed
                                        </option>


                                        <option
                                            value="completed"
                                            <?= $order['status'] === 'completed'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Completed
                                        </option>


                                        <option
                                            value="cancelled"
                                            <?= $order['status'] === 'cancelled'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Cancelled
                                        </option>

                                    </select>


                                    <button
                                        type="submit"
                                        name="update_status"
                                        class="update-btn"
                                    >
                                        Update
                                    </button>

                                </form>


                                <a
                                    href="service_orders.php?delete=<?= (int)$order['id'] ?>"
                                    class="delete"
                                    onclick="return confirm('Delete this service order?');"
                                >
                                    Delete
                                </a>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>

                </table>

            </div>


            <div class="total">

                <span>
                    Service Revenue
                </span>

                <span>
                    Rs.
                    <?= number_format(
                        $grand_total,
                        2
                    ) ?>
                </span>

            </div>


        <?php else: ?>


            <div class="empty">

                <h3>
                    No Service Orders
                </h3>

                <p>
                    Customer service requests will appear here.
                </p>

            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>