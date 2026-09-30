<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/functions.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   ADMIN ACCESS
========================= */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = "";
$error = "";

/* =========================
   DELETE PAYMENT
========================= */
if (isset($_GET['delete'])) {

    $id = (int) $_GET['delete'];

    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM payments WHERE id = ?");
            $stmt->execute([$id]);

            header("Location: payments.php?success=deleted");
            exit;

        } catch (PDOException $e) {
            $error = "Unable to delete payment.";
        }
    }
}

/* =========================
   SUCCESS MESSAGE
========================= */
if (isset($_GET['success'])) {

    if ($_GET['success'] === 'deleted') {
        $message = "Payment deleted successfully.";
    }

    if ($_GET['success'] === 'added') {
        $message = "Payment added successfully.";
    }
}

/* =========================
   ADD PAYMENT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {

    $booking_id = (int) ($_POST['booking_id'] ?? 0);
    $amount = (float) ($_POST['amount'] ?? 0);
    $payment_method = trim($_POST['payment_method'] ?? 'cash');
    $reference_number = trim($_POST['reference_number'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($booking_id <= 0) {
        $error = "Please enter a valid booking ID.";

    } elseif ($amount <= 0) {
        $error = "Payment amount must be greater than 0.";

    } else {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO payments
                (
                    booking_id,
                    amount,
                    payment_method,
                    reference_number,
                    notes
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $booking_id,
                $amount,
                $payment_method,
                $reference_number !== '' ? $reference_number : null,
                $notes !== '' ? $notes : null
            ]);

            header("Location: payments.php?success=added");
            exit;

        } catch (PDOException $e) {
            $error = "Unable to add payment.";
        }
    }
}

/* =========================
   GET PAYMENTS
========================= */

try {

    $stmt = $pdo->query("
        SELECT
            id,
            booking_id,
            amount,
            payment_method,
            payment_date,
            reference_number,
            notes
        FROM payments
        ORDER BY id DESC
    ");

    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $payments = [];
    $error = "Unable to load payments.";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
<link rel="stylesheet" href="../assets/css/style.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Payments - Velora Hotel</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
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
            width: 270px;
            height: 100vh;
            background: #0f172a;
            color: white;
            padding: 28px 16px;
            overflow-y: auto;
        }

        .logo {
            font-size: 23px;
            font-weight: 700;
            padding: 0 18px 40px;
        }

        .logo span {
            color: #38bdf8;
        }

        .menu-title {
            font-size: 12px;
            color: #94a3b8;
            text-transform: uppercase;
            margin: 20px 16px 12px;
            letter-spacing: .5px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #cbd5e1;
            padding: 14px 16px;
            margin-bottom: 6px;
            border-radius: 10px;
            transition: .2s;
        }

        .menu a:hover {
            background: #1e293b;
            color: white;
        }

        .menu a.active {
            background: #2563eb;
            color: white;
        }

        .icon {
            width: 24px;
            text-align: center;
        }

        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: 270px;
            padding: 35px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .page-header h1 {
            font-size: 30px;
            margin-bottom: 6px;
        }

        .page-header p {
            color: #64748b;
        }

        .add-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 13px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
        }

        .add-btn:hover {
            background: #1d4ed8;
        }

        /* =========================
           ALERT
        ========================= */

        .alert {
            padding: 15px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* =========================
           STAT CARDS
        ========================= */

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 22px;
            border-radius: 15px;
            border: 1px solid #e2e8f0;
        }

        .stat-title {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 27px;
            font-weight: 700;
        }

        /* =========================
           TABLE
        ========================= */

        .table-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .table-header {
            padding: 22px;
            border-bottom: 1px solid #e2e8f0;
        }

        .table-header h2 {
            font-size: 19px;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8fafc;
            color: #475569;
            font-size: 13px;
            text-align: left;
            padding: 15px 18px;
            white-space: nowrap;
        }

        td {
            padding: 16px 18px;
            border-top: 1px solid #e2e8f0;
            font-size: 14px;
            vertical-align: middle;
        }

        tr:hover td {
            background: #f8fafc;
        }

        .booking-badge {
            background: #dbeafe;
            color: #1d4ed8;
            padding: 6px 10px;
            border-radius: 7px;
            font-weight: 600;
        }

        .amount {
            font-weight: 700;
            color: #166534;
        }

        .method {
            padding: 6px 10px;
            border-radius: 7px;
            background: #f1f5f9;
            color: #334155;
            text-transform: capitalize;
        }

        .action-delete {
            display: inline-block;
            background: #fee2e2;
            color: #dc2626;
            text-decoration: none;
            padding: 7px 11px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
        }

        .action-delete:hover {
            background: #fecaca;
        }

        .empty {
            text-align: center;
            padding: 50px;
            color: #64748b;
        }

        /* =========================
           MODAL
        ========================= */

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            inset: 0;
            background: rgba(15, 23, 42, .55);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal.show {
            display: flex;
        }

        .modal-box {
            background: white;
            width: 100%;
            max-width: 550px;
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 20px 50px rgba(0,0,0,.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .modal-header h2 {
            font-size: 22px;
        }

        .close {
            border: none;
            background: #f1f5f9;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            font-size: 14px;
            color: #0f172a;
            background: white;
            outline: none;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.1);
        }

        .form-group textarea {
            min-height: 90px;
            resize: vertical;
        }

        .save-btn {
            width: 100%;
            background: #2563eb;
            color: white;
            border: none;
            padding: 13px;
            border-radius: 9px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }

        .save-btn:hover {
            background: #1d4ed8;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media(max-width: 900px) {

            .sidebar {
                width: 220px;
            }

            .main {
                margin-left: 220px;
                padding: 25px;
            }

            .stats {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width: 700px) {

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .main {
                margin-left: 0;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }

    </style>

<link rel="stylesheet" href="../assets/css/velora.css">
</head>

<body>

<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">

    <div class="logo">
        🏨 Smart <span>Hotel</span>
    </div>

    <div class="menu-title">Management</div>

    <nav class="menu">

        <a href="dashboard.php">
            <span class="icon">📊</span>
            Dashboard
        </a>

        <a href="bookings.php">
            <span class="icon">📅</span>
            Bookings
        </a>

        <a href="rooms.php">
            <span class="icon">🛏</span>
            Rooms
        </a>

        <a href="room_types.php">
            <span class="icon">🏷</span>
            Room Types
        </a>

        <a href="customers.php">
            <span class="icon">👥</span>
            Customers
        </a>

    </nav>

    <div class="menu-title">System</div>

    <nav class="menu">

        <a href="services.php">
            <span class="icon">✨</span>
            Services
        </a>

        <a href="service_orders.php">
            <span class="icon">🧾</span>
            Service Orders
        </a>

        <a href="payments.php" class="active">
            <span class="icon">💳</span>
            Payments
        </a>

        <a href="reports.php">
            <span class="icon">📈</span>
            Reports
        </a>

        <a href="../logout.php">
            <span class="icon">↪</span>
            Logout
        </a>

    </nav>

</aside>


<!-- =========================
     MAIN CONTENT
========================= -->

<main class="main">

    <div class="page-header">

        <div>
            <h1>Payments</h1>
            <p>Manage hotel booking payments and transactions.</p>
        </div>

        <button class="add-btn" onclick="openModal()">
            + Add Payment
        </button>

    </div>


    <?php if ($message): ?>

        <div class="alert success">
            ✓ <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="alert error">
            ⚠ <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <!-- =========================
         STATISTICS
    ========================= -->

    <?php

    $totalPayments = count($payments);

    $totalAmount = 0;

    foreach ($payments as $payment) {
        $totalAmount += (float) $payment['amount'];
    }

    ?>

    <div class="stats">

        <div class="stat-card">

            <div class="stat-title">
                Total Transactions
            </div>

            <div class="stat-value">
                <?= $totalPayments ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-title">
                Total Payments
            </div>

            <div class="stat-value">
                Rs. <?= number_format($totalAmount, 2) ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-title">
                Average Payment
            </div>

            <div class="stat-value">

                Rs.
                <?= $totalPayments > 0
                    ? number_format($totalAmount / $totalPayments, 2)
                    : '0.00'
                ?>

            </div>

        </div>

    </div>


    <!-- =========================
         PAYMENTS TABLE
    ========================= -->

    <div class="table-card">

        <div class="table-header">

            <h2>Payment Transactions</h2>

        </div>

        <div class="table-container">

            <?php if (count($payments) > 0): ?>

                <table>

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Booking</th>

                            <th>Amount</th>

                            <th>Payment Method</th>

                            <th>Payment Date</th>

                            <th>Reference</th>

                            <th>Notes</th>

                            <th>Action</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($payments as $payment): ?>

                        <tr>

                            <td>
                                #<?= (int) $payment['id'] ?>
                            </td>

                            <td>
                                <span class="booking-badge">
                                    Booking #<?= (int) $payment['booking_id'] ?>
                                </span>
                            </td>

                            <td class="amount">
                                Rs. <?= number_format((float) $payment['amount'], 2) ?>
                            </td>

                            <td>

                                <span class="method">
                                    <?= htmlspecialchars(
                                        str_replace('_', ' ', $payment['payment_method'])
                                    ) ?>
                                </span>

                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    date(
                                        'Y-m-d H:i',
                                        strtotime($payment['payment_date'])
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= $payment['reference_number']
                                    ? htmlspecialchars($payment['reference_number'])
                                    : '—'
                                ?>
                            </td>

                            <td>
                                <?= $payment['notes']
                                    ? htmlspecialchars($payment['notes'])
                                    : '—'
                                ?>
                            </td>

                            <td>

                                <a
                                    class="action-delete"
                                    href="payments.php?delete=<?= (int) $payment['id'] ?>"
                                    onclick="return confirm('Are you sure you want to delete this payment?');"
                                >
                                    Delete
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <div class="empty">

                    <div style="font-size:45px;margin-bottom:10px;">
                        💳
                    </div>

                    <h3>No payments found</h3>

                    <p style="margin-top:8px;">
                        Payment transactions will appear here.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>

</main>


<!-- =========================
     ADD PAYMENT MODAL
========================= -->

<div class="modal" id="paymentModal">

    <div class="modal-box">

        <div class="modal-header">

            <h2>Add Payment</h2>

            <button class="close" onclick="closeModal()">
                ×
            </button>

        </div>


        <form method="POST">

            <div class="form-group">

                <label>
                    Booking ID
                </label>

                <input
                    type="number"
                    name="booking_id"
                    min="1"
                    required
                    placeholder="Example: 2"
                >

            </div>


            <div class="form-group">

                <label>
                    Amount (Rs.)
                </label>

                <input
                    type="number"
                    name="amount"
                    min="0.01"
                    step="0.01"
                    required
                    placeholder="Example: 25000.00"
                >

            </div>


            <div class="form-group">

                <label>
                    Payment Method
                </label>

                <select name="payment_method">

                    <option value="cash">
                        Cash
                    </option>

                    <option value="card">
                        Card
                    </option>

                    <option value="bank_transfer">
                        Bank Transfer
                    </option>

                    <option value="online">
                        Online
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Reference Number
                </label>

                <input
                    type="text"
                    name="reference_number"
                    placeholder="Optional"
                >

            </div>


            <div class="form-group">

                <label>
                    Notes
                </label>

                <textarea
                    name="notes"
                    placeholder="Optional payment notes..."
                ></textarea>

            </div>


            <button
                type="submit"
                name="add_payment"
                class="save-btn"
            >
                Save Payment
            </button>

        </form>

    </div>

</div>


<script>

function openModal() {
    document.getElementById("paymentModal").classList.add("show");
}

function closeModal() {
    document.getElementById("paymentModal").classList.remove("show");
}

window.onclick = function(event) {

    const modal = document.getElementById("paymentModal");

    if (event.target === modal) {
        closeModal();
    }

};

</script>

</body>

</html>