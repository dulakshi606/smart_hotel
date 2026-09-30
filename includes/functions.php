<?php

/* =====================================================
   SESSION
===================================================== */

function startSession()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}


/* =====================================================
   LOGIN
===================================================== */

function requireLogin()
{
    startSession();

    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit;
    }
}


/* =====================================================
   ROLE CHECK
===================================================== */

function requireRole($role)
{
    startSession();

    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit;
    }

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        header("Location: ../auth/login.php");
        exit;
    }
}


/* =====================================================
   ESCAPE HTML
===================================================== */

function e($value)
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =====================================================
   CREATE NOTIFICATION
=====================================================

   Your notifications table contains:

   id
   user_id
   title
   message
   is_read
   created_at

===================================================== */

function createNotification(
    $pdo,
    $userId,
    $title,
    $message
) {
    try {

        $stmt = $pdo->prepare("
            INSERT INTO notifications
            (
                user_id,
                title,
                message,
                is_read
            )
            VALUES
            (
                ?,
                ?,
                ?,
                0
            )
        ");

        return $stmt->execute([
            $userId,
            $title,
            $message
        ]);

    } catch (PDOException $e) {

        /*
         * Notification errors should not
         * stop the booking process.
         */

        return false;
    }
}