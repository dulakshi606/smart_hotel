<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

startSession();
requireRole("customer");

$customer_id = $_SESSION["user_id"];
$booking_id = $_GET["id"] ?? "";

/* =====================================================
   VALIDATE BOOKING ID
===================================================== */

if (!is_numeric($booking_id)) {
    header("Location: bookings.php");
    exit;
}

$booking_id = (int)$booking_id;


/* =====================================================
   GET BOOKING
===================================================== */

$stmt = $pdo->prepare("
    SELECT
        id,
        booking_code,
        booking_status
    FROM bookings
    WHERE id = ?
    AND customer_id = ?
    LIMIT 1
");

$stmt->execute([
    $booking_id,
    $customer_id
]);

$booking = $stmt->fetch(PDO::FETCH_ASSOC);


/* =====================================================
   BOOKING NOT FOUND
===================================================== */

if (!$booking) {
    header("Location: bookings.php");
    exit;
}


/* =====================================================
   ONLY PENDING / CONFIRMED CAN BE CANCELLED
===================================================== */

if (!in_array($booking["booking_status"], ["pending", "confirmed"])) {

    header(
        "Location: booking_view.php?id=" .
        $booking_id .
        "&cancel_error=1"
    );

    exit;
}


/* =====================================================
   CANCEL BOOKING
===================================================== */

$update = $pdo->prepare("
    UPDATE bookings
    SET booking_status = 'cancelled'
    WHERE id = ?
    AND customer_id = ?
    AND booking_status IN ('pending', 'confirmed')
");

$update->execute([
    $booking_id,
    $customer_id
]);


/* =====================================================
   REDIRECT
===================================================== */

if ($update->rowCount() > 0) {

    header("Location: bookings.php?cancelled=1");
    exit;

} else {

    header("Location: bookings.php?cancel_error=1");
    exit;
}