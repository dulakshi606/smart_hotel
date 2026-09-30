<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

startSession();
requireRole("admin");


// ==========================================
// UPDATE SERVICE REQUEST STATUS
// ==========================================

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $request_id = $_POST["request_id"] ?? "";
    $new_status = $_POST["request_status"] ?? "";

    $allowed_statuses = [
        "pending",
        "accepted",
        "in_progress",
        "completed",
        "cancelled"
    ];

    if (!is_numeric($request_id)) {

        $error = "Invalid service request.";

    } elseif (!in_array($new_status, $allowed_statuses)) {

        $error = "Invalid status selected.";

    } else {

        $request_id = (int)$request_id;

        $stmt = $pdo->prepare("
            UPDATE service_requests
            SET request_status = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $new_status,
            $request_id
        ]);

        if ($stmt->rowCount() > 0) {

            header("Location: service_requests.php?updated=1");
            exit;

        } else {

            $error = "No changes were made.";
        }
    }
}


// ==========================================
// SUCCESS MESSAGE
// ==========================================

if (isset($_GET["updated"])) {
    $success = "Service request status updated successfully.";
}


// ==========================================
// SERVICE REQUEST STATISTICS
// ==========================================

// Total
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM service_requests
");

$totalRequests = $stmt->fetch()["total"];


// Pending
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM service_requests
    WHERE request_status = 'pending'
");

$pendingRequests = $stmt->fetch()["total"];


// Accepted
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM service_requests
    WHERE request_status = 'accepted'
");

$acceptedRequests = $stmt->fetch()["total"];


// In Progress
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM service_requests
    WHERE request_status = 'in_progress'
");

$inProgressRequests = $stmt->fetch()["total"];


// Completed
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM service_requests
    WHERE request_status = 'completed'
");

$completedRequests = $stmt->fetch()["total"];


// Cancelled
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM service_requests
    WHERE request_status = 'cancelled'
");

$cancelledRequests = $stmt->fetch()["total"];


// ==========================================
// GET ALL SERVICE REQUESTS
// ==========================================

$stmt = $pdo->query("
    SELECT
        sr.id,
        sr.service_type,
        sr.description,
        sr.request_status,
        sr.created_at,

        b.booking_code,

        r.room_number,

        rt.type_name,

        u.full_name AS customer_name,
        u.email AS customer_email

    FROM service_requests sr

    INNER JOIN bookings b
        ON sr.booking_id = b.id

    INNER JOIN rooms r
        ON b.room_id = r.id

    INNER JOIN room_types rt
        ON r.room_type_id = rt.id

    INNER JOIN users u
        ON sr.customer_id = u.id

    ORDER BY sr.id DESC
");

$requests = $stmt->fetchAll();


// ==========================================
// STATUS HELPERS
// ==========================================

function requestStatusClass($status)
{
    return match ($status) {

        "pending" =>
            "bg-warning text-dark",

        "accepted" =>
            "bg-primary",

        "in_progress" =>
            "bg-info text-dark",

        "completed" =>
            "bg-success",

        "cancelled" =>
            "bg-danger",

        default =>
            "bg-secondary"
    };
}


function requestStatusIcon($status)
{
    return match ($status) {

        "pending" =>
            "bi-hourglass-split",

        "accepted" =>
            "bi-check-circle",

        "in_progress" =>
            "bi-arrow-repeat",

        "completed" =>
            "bi-check-circle-fill",

        "cancelled" =>
            "bi-x-circle",

        default =>
            "bi-question-circle"
    };
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Service Requests - Velora Hotel</title>


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

/* ==========================================
   PAGE
========================================== */

body {
    background: #f4f6f9;
}


/* ==========================================
   SIDEBAR
========================================== */

.sidebar {
    min-height: 100vh;
    background: #111827;
}

.sidebar .brand {

    color: white;

    font-size: 22px;

    font-weight: bold;

    padding: 25px 20px;

    display: block;

    text-decoration: none;
}

.sidebar a {

    color: #cbd5e1;

    text-decoration: none;

    display: block;

    padding: 12px 20px;
}

.sidebar a:hover,
.sidebar a.active {

    background: #2563eb;

    color: white;
}


/* ==========================================
   TOPBAR
========================================== */

.topbar {

    background: white;

    padding: 15px 25px;

    box-shadow: 0 1px 5px rgba(0,0,0,.08);
}


/* ==========================================
   CONTENT
========================================== */

.content {

    padding: 25px;
}


/* ==========================================
   STAT CARDS
========================================== */

.stat-card {

    border: none;

    border-radius: 14px;

    box-shadow: 0 2px 10px rgba(0,0,0,.06);
}

.stat-icon {

    width: 50px;

    height: 50px;

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 22px;
}


/* ==========================================
   REQUEST CARD
========================================== */

.request-card {

    border: none;

    border-radius: 15px;

    box-shadow: 0 2px 10px rgba(0,0,0,.06);

    transition: .2s;
}

.request-card:hover {

    transform: translateY(-2px);

    box-shadow: 0 5px 18px rgba(0,0,0,.09);
}


/* ==========================================
   SERVICE ICON
========================================== */

.service-icon {

    width: 48px;

    height: 48px;

    border-radius: 12px;

    background: #fef3c7;

    color: #b8860b;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;
}


/* ==========================================
   DESCRIPTION
========================================== */

.request-description {

    background: #f8fafc;

    border-radius: 10px;

    padding: 12px;

    min-height: 60px;
}


/* ==========================================
   STATUS SELECT
========================================== */

.status-select {

    border-radius: 8px;

    font-size: 14px;

    font-weight: 500;
}


/* ==========================================
   MOBILE
========================================== */

@media (max-width: 768px) {

    .sidebar {

        min-height: auto;
    }

    .content {

        padding: 15px;
    }

}

</style>

</head>


<body>


<div class="container-fluid">

<div class="row">


<!-- ==========================================
     SIDEBAR
========================================== -->

<div class="col-md-2 p-0 sidebar">


<a
    href="dashboard.php"
    class="brand"
>

    <i class="bi bi-building"></i>

    Velora Hotel

</a>


<a href="dashboard.php">

    <i class="bi bi-speedometer2 me-2"></i>

    Dashboard

</a>


<a href="rooms.php">

    <i class="bi bi-door-open me-2"></i>

    Rooms

</a>


<a href="room_types.php">

    <i class="bi bi-grid me-2"></i>

    Room Types

</a>


<a href="customers.php">

    <i class="bi bi-people me-2"></i>

    Customers

</a>


<a href="bookings.php">

    <i class="bi bi-calendar-check me-2"></i>

    Bookings

</a>


<a href="services.php">

    <i class="bi bi-stars me-2"></i>

    Services

</a>


<!-- NEW -->

<a
    href="service_requests.php"
    class="active"
>

    <i class="bi bi-bell me-2"></i>

    Service Requests

</a>


<a href="maintenance.php">

    <i class="bi bi-tools me-2"></i>

    Maintenance

</a>


<a href="reports.php">

    <i class="bi bi-bar-chart me-2"></i>

    Reports

</a>


<a href="facilities.php">

    <i class="bi bi-grid-3x3-gap me-2"></i>

    Room Facilities

</a>


<hr class="text-secondary">


<a href="../auth/logout.php">

    <i class="bi bi-box-arrow-right me-2"></i>

    Logout

</a>


</div>


<!-- ==========================================
     MAIN CONTENT
========================================== -->

<div class="col-md-10 p-0">


<!-- TOP BAR -->

<div class="topbar">

<div class="d-flex justify-content-between align-items-center">

<div>

<h5 class="mb-0">

Service Requests

</h5>

<small class="text-muted">

Velora Hotel Management System

</small>

</div>


<div>

<i class="bi bi-person-circle me-2"></i>

<?= e($_SESSION["full_name"]) ?>

</div>

</div>

</div>


<!-- ==========================================
     CONTENT
========================================== -->

<div class="content">


<h3 class="mb-4">

<i class="bi bi-bell me-2"></i>

Customer Service Requests

</h3>


<!-- ==========================================
     ALERTS
========================================== -->

<?php if ($success): ?>

<div class="alert alert-success">

<i class="bi bi-check-circle me-2"></i>

<?= e($success) ?>

</div>

<?php endif; ?>


<?php if ($error): ?>

<div class="alert alert-danger">

<i class="bi bi-exclamation-triangle me-2"></i>

<?= e($error) ?>

</div>

<?php endif; ?>


<!-- ==========================================
     STATISTICS
========================================== -->

<div class="row g-4 mb-4">


<!-- TOTAL -->

<div class="col-md-4 col-lg-2">

<div class="card stat-card">

<div class="card-body">

<div class="d-flex justify-content-between">

<div>

<small class="text-muted">

Total

</small>

<h3 class="mt-2 mb-0">

<?= $totalRequests ?>

</h3>

</div>

<div class="stat-icon bg-primary-subtle text-primary">

<i class="bi bi-list-check"></i>

</div>

</div>

</div>

</div>

</div>


<!-- PENDING -->

<div class="col-md-4 col-lg-2">

<div class="card stat-card">

<div class="card-body">

<div class="d-flex justify-content-between">

<div>

<small class="text-muted">

Pending

</small>

<h3 class="mt-2 mb-0">

<?= $pendingRequests ?>

</h3>

</div>

<div class="stat-icon bg-warning-subtle text-warning">

<i class="bi bi-hourglass-split"></i>

</div>

</div>

</div>

</div>

</div>


<!-- ACCEPTED -->

<div class="col-md-4 col-lg-2">

<div class="card stat-card">

<div class="card-body">

<div class="d-flex justify-content-between">

<div>

<small class="text-muted">

Accepted

</small>

<h3 class="mt-2 mb-0">

<?= $acceptedRequests ?>

</h3>

</div>

<div class="stat-icon bg-primary-subtle text-primary">

<i class="bi bi-check-circle"></i>

</div>

</div>

</div>

</div>

</div>


<!-- IN PROGRESS -->

<div class="col-md-4 col-lg-2">

<div class="card stat-card">

<div class="card-body">

<div class="d-flex justify-content-between">

<div>

<small class="text-muted">

In Progress

</small>

<h3 class="mt-2 mb-0">

<?= $inProgressRequests ?>

</h3>

</div>

<div class="stat-icon bg-info-subtle text-info">

<i class="bi bi-arrow-repeat"></i>

</div>

</div>

</div>

</div>

</div>


<!-- COMPLETED -->

<div class="col-md-4 col-lg-2">

<div class="card stat-card">

<div class="card-body">

<div class="d-flex justify-content-between">

<div>

<small class="text-muted">

Completed

</small>

<h3 class="mt-2 mb-0">

<?= $completedRequests ?>

</h3>

</div>

<div class="stat-icon bg-success-subtle text-success">

<i class="bi bi-check-circle-fill"></i>

</div>

</div>

</div>

</div>

</div>


<!-- CANCELLED -->

<div class="col-md-4 col-lg-2">

<div class="card stat-card">

<div class="card-body">

<div class="d-flex justify-content-between">

<div>

<small class="text-muted">

Cancelled

</small>

<h3 class="mt-2 mb-0">

<?= $cancelledRequests ?>

</h3>

</div>

<div class="stat-icon bg-danger-subtle text-danger">

<i class="bi bi-x-circle"></i>

</div>

</div>

</div>

</div>

</div>


</div>


<!-- ==========================================
     REQUESTS
========================================== -->

<div class="card request-card">

<div class="card-body">


<div class="d-flex justify-content-between align-items-center mb-4">

<div>

<h5 class="mb-1">

All Service Requests

</h5>

<small class="text-muted">

Manage customer service requests

</small>

</div>


<span class="badge bg-dark">

<?= count($requests) ?> Requests

</span>

</div>


<?php if (count($requests) > 0): ?>


<div class="table-responsive">

<table class="table table-hover align-middle">


<thead>

<tr>

<th>Customer</th>

<th>Booking</th>

<th>Room</th>

<th>Service</th>

<th>Description</th>

<th>Date</th>

<th>Status</th>

</tr>

</thead>


<tbody>


<?php foreach ($requests as $request): ?>


<tr>


<!-- CUSTOMER -->

<td>

<strong>

<?= e($request["customer_name"]) ?>

</strong>

<br>

<small class="text-muted">

<?= e($request["customer_email"]) ?>

</small>

</td>


<!-- BOOKING -->

<td>

<span class="fw-semibold">

<?= e($request["booking_code"]) ?>

</span>

</td>


<!-- ROOM -->

<td>

Room <?= e($request["room_number"]) ?>

<br>

<small class="text-muted">

<?= e($request["type_name"]) ?>

</small>

</td>


<!-- SERVICE -->

<td>

<div class="d-flex align-items-center gap-2">

<div class="service-icon">

<i class="bi bi-stars"></i>

</div>

<span>

<?= e($request["service_type"]) ?>

</span>

</div>

</td>


<!-- DESCRIPTION -->

<td style="min-width:220px;">

<div class="request-description">

<?php if (!empty($request["description"])): ?>

<?= nl2br(e($request["description"])) ?>

<?php else: ?>

<span class="text-muted">

No description provided.

</span>

<?php endif; ?>

</div>

</td>


<!-- DATE -->

<td style="min-width:150px;">

<?= date(
    "d M Y",
    strtotime($request["created_at"])
) ?>

<br>

<small class="text-muted">

<?= date(
    "h:i A",
    strtotime($request["created_at"])
) ?>

</small>

</td>


<!-- STATUS -->

<td style="min-width:170px;">


<div class="mb-2">

<span class="badge <?= requestStatusClass($request["request_status"]) ?>">

<i class="bi <?= requestStatusIcon($request["request_status"]) ?> me-1"></i>

<?= e(
    ucfirst(
        str_replace(
            "_",
            " ",
            $request["request_status"]
        )
    )
) ?>

</span>

</div>


<form
    method="POST"
    action="service_requests.php"
>


<input
    type="hidden"
    name="request_id"
    value="<?= $request["id"] ?>"
>


<select
    name="request_status"
    class="form-select form-select-sm status-select"
    onchange="this.form.submit()"
>


<option
    value="pending"
    <?= $request["request_status"] === "pending" ? "selected" : "" ?>
>
    Pending
</option>


<option
    value="accepted"
    <?= $request["request_status"] === "accepted" ? "selected" : "" ?>
>
    Accepted
</option>


<option
    value="in_progress"
    <?= $request["request_status"] === "in_progress" ? "selected" : "" ?>
>
    In Progress
</option>


<option
    value="completed"
    <?= $request["request_status"] === "completed" ? "selected" : "" ?>
>
    Completed
</option>


<option
    value="cancelled"
    <?= $request["request_status"] === "cancelled" ? "selected" : "" ?>
>
    Cancelled
</option>


</select>

</form>

</td>


</tr>


<?php endforeach; ?>


</tbody>

</table>

</div>


<?php else: ?>


<div class="text-center py-5">

<i
    class="bi bi-inbox"
    style="font-size:60px;color:#cbd5e1;"
></i>

<h5 class="mt-3">

No Service Requests

</h5>

<p class="text-muted">

Customer service requests will appear here.

</p>

</div>


<?php endif; ?>


</div>

</div>


</div>

</div>

</div>

</div>


</body>

</html>