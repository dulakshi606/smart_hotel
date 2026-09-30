<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   ADD SERVICE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {

    $service_name = trim($_POST['service_name'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $price        = floatval($_POST['price'] ?? 0);

    if ($service_name === '') {
        $_SESSION['error'] = "Service name is required.";
    } elseif ($price < 0) {
        $_SESSION['error'] = "Price cannot be negative.";
    } else {

        $stmt = $pdo->prepare("
            INSERT INTO services
            (service_name, description, price)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $service_name,
            $description,
            $price
        ]);

        $_SESSION['success'] = "Service added successfully.";
    }

    header("Location: services.php");
    exit;
}


/* =========================
   DELETE SERVICE
========================= */
if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['success'] = "Service deleted successfully.";
    }

    header("Location: services.php");
    exit;
}


/* =========================
   FETCH SERVICES
========================= */
$stmt = $pdo->query("
    SELECT *
    FROM services
    ORDER BY id DESC
");

$services = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" href="../assets/css/style.css">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Services - Velora Hotel</title>

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

/* SIDEBAR */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 270px;
    height: 100vh;
    background: #0f172a;
    color: white;
    padding: 30px 16px;
}

.logo {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 45px;
    padding-left: 18px;
}

.logo span {
    color: #06b6d4;
}

.menu-title {
    color: #94a3b8;
    font-size: 12px;
    margin: 25px 16px 12px;
    text-transform: uppercase;
}

.menu a {
    display: block;
    text-decoration: none;
    color: #cbd5e1;
    padding: 14px 18px;
    border-radius: 10px;
    margin-bottom: 6px;
    font-size: 15px;
}

.menu a:hover,
.menu a.active {
    background: #2563eb;
    color: white;
}

/* MAIN */

.main {
    margin-left: 270px;
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
    margin-top: 6px;
}

/* BUTTON */

.btn {
    border: none;
    padding: 12px 20px;
    border-radius: 9px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
}

.btn-primary {
    background: #2563eb;
    color: white;
}

.btn-primary:hover {
    background: #1d4ed8;
}

/* ALERT */

.alert {
    padding: 14px 18px;
    border-radius: 10px;
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

/* CARD */

.card {
    background: white;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    margin-bottom: 25px;
}

.card h2 {
    margin-bottom: 20px;
}

/* FORM */

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group.full {
    grid-column: 1 / -1;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 8px;
}

input,
textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #dbe3ef;
    border-radius: 8px;
    font-size: 14px;
    color: #0f172a;
    background: white;
}

textarea {
    min-height: 100px;
    resize: vertical;
}

input:focus,
textarea:focus {
    outline: none;
    border-color: #2563eb;
}

/* TABLE */

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #f8fafc;
    text-align: left;
    padding: 15px;
    color: #475569;
    font-size: 14px;
}

td {
    padding: 15px;
    border-top: 1px solid #e2e8f0;
    vertical-align: middle;
}

.service-name {
    font-weight: bold;
}

.description {
    color: #64748b;
    max-width: 400px;
}

.price {
    font-weight: bold;
    color: #2563eb;
}

.delete {
    color: #dc2626;
    text-decoration: none;
    font-weight: bold;
}

.delete:hover {
    text-decoration: underline;
}

/* EMPTY */

.empty {
    text-align: center;
    padding: 50px;
    color: #64748b;
}

/* RESPONSIVE */

@media(max-width: 800px) {

    .sidebar {
        width: 220px;
    }

    .main {
        margin-left: 220px;
        padding: 20px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

}

</style>

<link rel="stylesheet" href="../assets/css/velora.css">
</head>

<body>

<!-- SIDEBAR -->

<div class="sidebar">

    <div class="logo">
        🏨 Smart <span>Hotel</span>
    </div>

    <div class="menu">

        <div class="menu-title">Management</div>

        <a href="dashboard.php">📊 Dashboard</a>

        <a href="bookings.php">📅 Bookings</a>

        <a href="rooms.php">🛏 Rooms</a>

        <a href="room_types.php">▦ Room Types</a>

        <a href="customers.php">👥 Customers</a>

        <div class="menu-title">System</div>

        <a href="services.php" class="active">✨ Services</a>

        <a href="reports.php">📈 Reports</a>

        <a href="logout.php">🚪 Logout</a>

    </div>

</div>


<!-- MAIN -->

<div class="main">

    <div class="header">

        <div>
            <h1>Hotel Services</h1>
            <p>Manage additional services offered by the hotel.</p>
        </div>

    </div>


    <?php if (!empty($_SESSION['success'])): ?>

        <div class="alert success">
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>

        <?php unset($_SESSION['success']); ?>

    <?php endif; ?>


    <?php if (!empty($_SESSION['error'])): ?>

        <div class="alert error">
            <?= htmlspecialchars($_SESSION['error']) ?>
        </div>

        <?php unset($_SESSION['error']); ?>

    <?php endif; ?>


    <!-- ADD SERVICE -->

    <div class="card">

        <h2>Add New Service</h2>

        <form method="POST">

            <div class="form-grid">

                <div class="form-group">

                    <label>Service Name</label>

                    <input
                        type="text"
                        name="service_name"
                        placeholder="Example: Airport Pickup"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Price (Rs.)</label>

                    <input
                        type="number"
                        name="price"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        required
                    >

                </div>


                <div class="form-group full">

                    <label>Description</label>

                    <textarea
                        name="description"
                        placeholder="Describe this service..."
                    ></textarea>

                </div>

            </div>


            <button
                type="submit"
                name="add_service"
                class="btn btn-primary"
            >
                + Add Service
            </button>

        </form>

    </div>


    <!-- SERVICES LIST -->

    <div class="card">

        <h2>Available Services</h2>

        <?php if (count($services) > 0): ?>

            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>
                            <th>#</th>
                            <th>Service</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($services as $service): ?>

                        <tr>

                            <td>
                                <?= (int)$service['id'] ?>
                            </td>

                            <td class="service-name">
                                <?= htmlspecialchars($service['service_name']) ?>
                            </td>

                            <td class="description">
                                <?= htmlspecialchars($service['description'] ?? '') ?>
                            </td>

                            <td class="price">
                                Rs. <?= number_format((float)$service['price'], 2) ?>
                            </td>

                            <td>

                                <a
                                    class="delete"
                                    href="services.php?delete=<?= (int)$service['id'] ?>"
                                    onclick="return confirm('Are you sure you want to delete this service?');"
                                >
                                    Delete
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty">

                <h3>No Services Found</h3>

                <p>Add your first hotel service above.</p>

            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>