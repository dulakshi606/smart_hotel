<?php
require_once __DIR__ . "/../config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Customers Management
|--------------------------------------------------------------------------
| Database table: users
| Customer condition: role = 'customer'
|--------------------------------------------------------------------------
*/

// Search
$search = trim($_GET['search'] ?? '');

// Status filter
$status = $_GET['status'] ?? 'all';

// Build query
$sql = "
    SELECT 
        u.id,
        u.full_name,
        u.email,
        u.phone,
        u.status,
        u.created_at,
        COUNT(b.id) AS total_bookings
    FROM users u
    LEFT JOIN bookings b ON b.customer_id = u.id
    WHERE u.role = 'customer'
";

$params = [];

// Search
if ($search !== '') {
    $sql .= "
        AND (
            u.full_name LIKE :search
            OR u.email LIKE :search
            OR u.phone LIKE :search
        )
    ";

    $params[':search'] = '%' . $search . '%';
}

// Status filter
if ($status !== 'all') {
    $sql .= " AND u.status = :status";
    $params[':status'] = $status;
}

$sql .= "
    GROUP BY 
        u.id,
        u.full_name,
        u.email,
        u.phone,
        u.status,
        u.created_at
    ORDER BY u.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Statistics
$countStmt = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'active') AS active,
        SUM(status = 'inactive') AS inactive
    FROM users
    WHERE role = 'customer'
");

$stats = $countStmt->fetch(PDO::FETCH_ASSOC);

$totalCustomers = (int)($stats['total'] ?? 0);
$activeCustomers = (int)($stats['active'] ?? 0);
$inactiveCustomers = (int)($stats['inactive'] ?? 0);

?>

<!DOCTYPE html>
<html lang="en">

<head>
<link rel="stylesheet" href="../assets/css/style.css">
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customers - Velora Hotel</title>

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

        /* =====================================================
           SIDEBAR
        ===================================================== */

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
            font-size: 24px;
            font-weight: 700;
            padding: 0 18px;
            margin-bottom: 55px;
        }

        .logo span {
            color: #06b6d4;
        }

        .menu-title {
            font-size: 12px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 16px 16px;
        }

        .menu {
            list-style: none;
            margin-bottom: 35px;
        }

        .menu li {
            margin-bottom: 7px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            border-radius: 10px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 15px;
            transition: 0.2s;
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
            width: 22px;
            text-align: center;
            font-size: 18px;
        }

        /* =====================================================
           MAIN
        ===================================================== */

        .main {
            margin-left: 270px;
            padding: 35px;
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title h1 {
            font-size: 30px;
            margin-bottom: 8px;
        }

        .page-title p {
            color: #64748b;
            font-size: 15px;
        }

        .add-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 13px 20px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: 0.2s;
        }

        .add-btn:hover {
            background: #1d4ed8;
        }

        /* =====================================================
           STATISTICS
        ===================================================== */

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            border: 1px solid #dbe3ef;
            border-radius: 14px;
            padding: 22px;
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #dbeafe;
            color: #2563eb;
            font-size: 22px;
        }

        .stat-card h3 {
            font-size: 25px;
            margin-bottom: 4px;
        }

        .stat-card p {
            color: #64748b;
            font-size: 13px;
        }

        /* =====================================================
           SEARCH
        ===================================================== */

        .filter-card {
            background: white;
            border: 1px solid #dbe3ef;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .filter-form {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .search-box {
            position: relative;
            flex: 1;
        }

        .search-box input {
            width: 100%;
            padding: 13px 15px;
            border: 1px solid #dbe3ef;
            border-radius: 9px;
            outline: none;
            font-size: 14px;
            color: #0f172a;
            background: white;
        }

        .search-box input:focus {
            border-color: #2563eb;
        }

        .status-select {
            padding: 13px 15px;
            border: 1px solid #dbe3ef;
            border-radius: 9px;
            background: white;
            color: #0f172a;
            font-size: 14px;
            outline: none;
            min-width: 150px;
        }

        .filter-btn {
            padding: 13px 20px;
            border: none;
            border-radius: 9px;
            background: #0f172a;
            color: white;
            cursor: pointer;
            font-weight: 600;
        }

        .clear-btn {
            padding: 13px 18px;
            border: 1px solid #dbe3ef;
            border-radius: 9px;
            background: white;
            color: #475569;
            text-decoration: none;
            font-size: 14px;
        }

        /* =====================================================
           TABLE
        ===================================================== */

        .table-card {
            background: white;
            border: 1px solid #dbe3ef;
            border-radius: 14px;
            overflow: hidden;
        }

        .table-header {
            padding: 22px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h2 {
            font-size: 18px;
        }

        .customer-count {
            background: #eff6ff;
            color: #2563eb;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8fafc;
            color: #64748b;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 15px 20px;
            text-align: left;
            white-space: nowrap;
        }

        td {
            padding: 17px 20px;
            border-top: 1px solid #eef2f7;
            font-size: 14px;
            vertical-align: middle;
        }

        tr:hover td {
            background: #f8fafc;
        }

        .customer-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
        }

        .customer-name {
            font-weight: 600;
            color: #0f172a;
        }

        .customer-id {
            color: #94a3b8;
            font-size: 12px;
            margin-top: 3px;
        }

        .email {
            color: #475569;
        }

        .phone {
            color: #475569;
        }

        .booking-badge {
            display: inline-block;
            background: #f1f5f9;
            color: #334155;
            padding: 6px 10px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status.active {
            background: #dcfce7;
            color: #15803d;
        }

        .status.inactive {
            background: #fee2e2;
            color: #b91c1c;
        }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .actions {
            display: flex;
            gap: 7px;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 15px;
            border: 1px solid #dbe3ef;
            background: white;
        }

        .view-btn {
            color: #2563eb;
        }

        .view-btn:hover {
            background: #eff6ff;
        }

        .edit-btn {
            color: #d97706;
        }

        .edit-btn:hover {
            background: #fffbeb;
        }

        .delete-btn {
            color: #dc2626;
        }

        .delete-btn:hover {
            background: #fef2f2;
        }

        /* =====================================================
           EMPTY STATE
        ===================================================== */

        .empty {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 45px;
            margin-bottom: 15px;
        }

        .empty h3 {
            margin-bottom: 8px;
        }

        .empty p {
            color: #64748b;
            font-size: 14px;
        }

        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1000px) {

            .stats {
                grid-template-columns: 1fr;
            }

            .main {
                margin-left: 0;
                padding: 20px;
            }

            .sidebar {
                display: none;
            }
        }

        @media (max-width: 700px) {

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }

            .status-select {
                width: 100%;
            }

            .filter-btn,
            .clear-btn {
                text-align: center;
            }
        }

    </style>

<link rel="stylesheet" href="../assets/css/velora.css">
</head>

<body>

<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar">

    <div class="logo">
        🏨 <span>Smart</span> Hotel
    </div>

    <div class="menu-title">Management</div>

    <ul class="menu">

        <li>
            <a href="dashboard.php">
                <span class="icon">📊</span>
                Dashboard
            </a>
        </li>

        <li>
            <a href="bookings.php">
                <span class="icon">📅</span>
                Bookings
            </a>
        </li>

        <li>
            <a href="rooms.php">
                <span class="icon">🚪</span>
                Rooms
            </a>
        </li>

        <li>
            <a href="room_types.php">
                <span class="icon">▦</span>
                Room Types
            </a>
        </li>

        <li>
            <a href="customers.php" class="active">
                <span class="icon">👥</span>
                Customers
            </a>
        </li>

    </ul>

    <div class="menu-title">System</div>

    <ul class="menu">

        <li>
            <a href="services.php">
                <span class="icon">✦</span>
                Services
            </a>
        </li>

        <li>
            <a href="reports.php">
                <span class="icon">▥</span>
                Reports
            </a>
        </li>

        <li>
            <a href="logout.php">
                <span class="icon">⇥</span>
                Logout
            </a>
        </li>

    </ul>

</aside>


<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<main class="main">

    <!-- Header -->

    <div class="page-header">

        <div class="page-title">

            <h1>Customers</h1>

            <p>
                Manage hotel customers and their booking information.
            </p>

        </div>

        <a href="customer_add.php" class="add-btn">
            ＋ Add Customer
        </a>

    </div>


    <!-- Statistics -->

    <div class="stats">

        <div class="stat-card">

            <div class="stat-icon">
                👥
            </div>

            <div>
                <h3>
                    <?= $totalCustomers ?>
                </h3>

                <p>
                    Total Customers
                </p>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ✓
            </div>

            <div>
                <h3>
                    <?= $activeCustomers ?>
                </h3>

                <p>
                    Active Customers
                </p>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                !
            </div>

            <div>
                <h3>
                    <?= $inactiveCustomers ?>
                </h3>

                <p>
                    Inactive Customers
                </p>
            </div>

        </div>

    </div>


    <!-- Search / Filter -->

    <div class="filter-card">

        <form method="GET" class="filter-form">

            <div class="search-box">

                <input
                    type="text"
                    name="search"
                    placeholder="Search by name, email or phone..."
                    value="<?= htmlspecialchars($search) ?>"
                >

            </div>


            <select name="status" class="status-select">

                <option value="all"
                    <?= $status === 'all' ? 'selected' : '' ?>>
                    All Status
                </option>

                <option value="active"
                    <?= $status === 'active' ? 'selected' : '' ?>>
                    Active
                </option>

                <option value="inactive"
                    <?= $status === 'inactive' ? 'selected' : '' ?>>
                    Inactive
                </option>

            </select>


            <button type="submit" class="filter-btn">
                Search
            </button>


            <a href="customers.php" class="clear-btn">
                Clear
            </a>

        </form>

    </div>


    <!-- Customers Table -->

    <div class="table-card">

        <div class="table-header">

            <h2>
                Customer List
            </h2>

            <span class="customer-count">
                <?= count($customers) ?> Result(s)
            </span>

        </div>


        <div class="table-wrapper">

            <?php if (!empty($customers)): ?>

                <table>

                    <thead>

                        <tr>

                            <th>Customer</th>

                            <th>Email</th>

                            <th>Phone</th>

                            <th>Bookings</th>

                            <th>Status</th>

                            <th>Registered</th>

                            <th>Actions</th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($customers as $customer): ?>

                            <?php

                            $name = $customer['full_name'];

                            $initials = '';

                            $nameParts = explode(' ', trim($name));

                            if (count($nameParts) >= 2) {
                                $initials =
                                    strtoupper(substr($nameParts[0], 0, 1)) .
                                    strtoupper(substr(end($nameParts), 0, 1));
                            } else {
                                $initials =
                                    strtoupper(substr($name, 0, 2));
                            }

                            ?>

                            <tr>

                                <!-- Customer -->

                                <td>

                                    <div class="customer-info">

                                        <div class="avatar">
                                            <?= htmlspecialchars($initials) ?>
                                        </div>

                                        <div>

                                            <div class="customer-name">
                                                <?= htmlspecialchars($customer['full_name']) ?>
                                            </div>

                                            <div class="customer-id">
                                                Customer #<?= (int)$customer['id'] ?>
                                            </div>

                                        </div>

                                    </div>

                                </td>


                                <!-- Email -->

                                <td>

                                    <span class="email">
                                        <?= htmlspecialchars($customer['email']) ?>
                                    </span>

                                </td>


                                <!-- Phone -->

                                <td>

                                    <span class="phone">
                                        <?= htmlspecialchars($customer['phone'] ?? 'Not provided') ?>
                                    </span>

                                </td>


                                <!-- Bookings -->

                                <td>

                                    <span class="booking-badge">

                                        <?= (int)$customer['total_bookings'] ?>

                                        <?= ((int)$customer['total_bookings'] === 1)
                                            ? 'Booking'
                                            : 'Bookings'
                                        ?>

                                    </span>

                                </td>


                                <!-- Status -->

                                <td>

                                    <span class="status <?= htmlspecialchars($customer['status']) ?>">

                                        <span class="status-dot"></span>

                                        <?= ucfirst(htmlspecialchars($customer['status'])) ?>

                                    </span>

                                </td>


                                <!-- Registered -->

                                <td>

                                    <?= date(
                                        'd M Y',
                                        strtotime($customer['created_at'])
                                    ) ?>

                                </td>


                                <!-- Actions -->

                                <td>

                                    <div class="actions">

                                        <a
                                            href="customer_view.php?id=<?= (int)$customer['id'] ?>"
                                            class="action-btn view-btn"
                                            title="View Customer"
                                        >
                                            👁
                                        </a>


                                        <a
                                            href="customer_edit.php?id=<?= (int)$customer['id'] ?>"
                                            class="action-btn edit-btn"
                                            title="Edit Customer"
                                        >
                                            ✎
                                        </a>


                                        <a
                                            href="customer_delete.php?id=<?= (int)$customer['id'] ?>"
                                            class="action-btn delete-btn"
                                            title="Delete Customer"
                                            onclick="return confirm('Are you sure you want to delete this customer?');"
                                        >
                                            🗑
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>


            <?php else: ?>

                <div class="empty">

                    <div class="empty-icon">
                        👥
                    </div>

                    <h3>
                        No Customers Found
                    </h3>

                    <p>
                        No customer records match your search.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>

</main>

</body>

</html>