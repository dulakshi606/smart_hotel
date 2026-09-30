<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

startSession();
requireRole("customer");

$customer_id = $_SESSION["user_id"];

$message = "";
$error = "";

/* =========================
   GET CUSTOMER DETAILS
========================= */

$stmt = $pdo->prepare("
    SELECT
        id,
        full_name,
        email,
        phone,
        role,
        status
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$customer_id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: dashboard.php");
    exit;
}


/* =========================
   UPDATE PROFILE
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["update_profile"])) {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");

    if ($full_name === "" || $email === "") {

        $error = "Name and email are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        /* Check duplicate email */

        $check = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            AND id <> ?
            LIMIT 1
        ");

        $check->execute([
            $email,
            $customer_id
        ]);

        if ($check->fetch()) {

            $error = "This email address is already in use.";

        } else {

            $update = $pdo->prepare("
                UPDATE users
                SET
                    full_name = ?,
                    email = ?,
                    phone = ?
                WHERE id = ?
            ");

            $update->execute([
                $full_name,
                $email,
                $phone,
                $customer_id
            ]);

            $_SESSION["full_name"] = $full_name;

            $user["full_name"] = $full_name;
            $user["email"] = $email;
            $user["phone"] = $phone;

            $message = "Profile updated successfully.";
        }
    }
}


/* =========================
   CHANGE PASSWORD
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["change_password"])) {

    $current_password =
        $_POST["current_password"] ?? "";

    $new_password =
        $_POST["new_password"] ?? "";

    $confirm_password =
        $_POST["confirm_password"] ?? "";

    if (
        $current_password === "" ||
        $new_password === "" ||
        $confirm_password === ""
    ) {

        $error = "Please fill in all password fields.";

    } elseif (
        !password_verify(
            $current_password,
            $user["password"]
        )
    ) {

        $error = "Current password is incorrect.";

    } elseif (strlen($new_password) < 6) {

        $error = "New password must contain at least 6 characters.";

    } elseif ($new_password !== $confirm_password) {

        $error = "New passwords do not match.";

    } else {

        $hashed_password =
            password_hash(
                $new_password,
                PASSWORD_DEFAULT
            );

        $updatePassword = $pdo->prepare("
            UPDATE users
            SET password = ?
            WHERE id = ?
        ");

        $updatePassword->execute([
            $hashed_password,
            $customer_id
        ]);

        $message = "Password changed successfully.";
    }
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

    <title>My Profile - Velora Hotel</title>

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


    <style>

        :root {

            --navy: #0f172a;
            --navy-light: #172033;
            --navy-hover: #1e293b;

            --gold: #c9a227;
            --gold-dark: #a88418;
            --gold-light: #f8edc5;

            --background: #f6f5f1;
            --surface: #ffffff;

            --text: #172033;
            --muted: #64748b;

            --border: #e5e7eb;

            --success: #198754;
            --danger: #dc3545;
        }


        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family:
                "Segoe UI",
                Arial,
                sans-serif;

            background:
                var(--background);

            color:
                var(--text);
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

            background:
                var(--navy);

            padding: 25px 15px;

            z-index: 1000;

            box-shadow:
                5px 0 20px
                rgba(0,0,0,0.08);
        }


        .logo {

            text-align: center;

            padding-bottom: 25px;

            margin-bottom: 20px;

            border-bottom:
                1px solid
                rgba(255,255,255,0.1);
        }


        .logo-icon {

            width: 50px;
            height: 50px;

            margin:
                0 auto 10px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background:
                var(--gold);

            color:
                var(--navy);

            font-size: 24px;
        }


        .logo h3 {

            color: white;

            margin: 0;

            font-size: 22px;

            font-weight: 700;
        }


        .logo span {

            color:
                var(--gold);

            font-size: 12px;

            letter-spacing: 2px;
        }


        .sidebar-menu {

            list-style: none;

            padding: 0;
            margin: 0;
        }


        .sidebar-menu li {

            margin-bottom: 7px;
        }


        .sidebar-menu a {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 13px 15px;

            border-radius: 8px;

            text-decoration: none;

            color: #cbd5e1;

            transition: 0.3s;
        }


        .sidebar-menu a:hover,
        .sidebar-menu a.active {

            background:
                var(--navy-hover);

            color:
                var(--gold);
        }


        .sidebar-menu i {

            font-size: 18px;

            width: 22px;
        }


        .logout-link {

            margin-top: 25px;

            border-top:
                1px solid
                rgba(255,255,255,0.1);

            padding-top: 20px;
        }


        .logout-link a {

            color:
                #fca5a5;
        }


        .logout-link a:hover {

            background:
                rgba(220,53,69,0.12);

            color:
                #ff6b6b;
        }


        /* =========================
           MAIN CONTENT
        ========================= */

        .main-content {

            margin-left: 250px;

            padding: 35px;
        }


        /* =========================
           TOP BAR
        ========================= */

        .topbar {

            display: flex;

            justify-content:
                space-between;

            align-items: center;

            margin-bottom: 30px;
        }


        .topbar h2 {

            margin: 0;

            font-weight: 700;

            color:
                var(--navy);
        }


        .topbar p {

            margin: 5px 0 0;

            color:
                var(--muted);
        }


        .user-box {

            display: flex;

            align-items: center;

            gap: 10px;

            background:
                white;

            padding: 10px 15px;

            border-radius: 10px;

            border:
                1px solid
                var(--border);
        }


        .user-icon {

            width: 38px;
            height: 38px;

            border-radius: 50%;

            display: flex;

            align-items: center;
            justify-content: center;

            background:
                var(--gold-light);

            color:
                var(--gold-dark);
        }


        /* =========================
           PROFILE LAYOUT
        ========================= */

        .profile-grid {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 25px;
        }


        .profile-card {

            background:
                var(--surface);

            border:
                1px solid
                var(--border);

            border-radius: 16px;

            padding: 30px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,0.03);
        }


        .profile-header {

            display: flex;

            align-items: center;

            gap: 18px;

            margin-bottom: 25px;
        }


        .profile-avatar {

            width: 70px;
            height: 70px;

            border-radius: 50%;

            display: flex;

            align-items: center;
            justify-content: center;

            background:
                var(--gold-light);

            color:
                var(--gold-dark);

            font-size: 32px;
        }


        .profile-header h3 {

            margin: 0;

            font-weight: 700;

            color:
                var(--navy);
        }


        .profile-header p {

            margin: 5px 0 0;

            color:
                var(--muted);
        }


        .form-label {

            font-weight: 600;

            color:
                var(--text);

            margin-bottom: 7px;
        }


        .form-control {

            padding: 11px 13px;

            border:
                1px solid
                var(--border);

            border-radius: 8px;
        }


        .form-control:focus {

            border-color:
                var(--gold);

            box-shadow:
                0 0 0 3px
                rgba(201,162,39,0.12);
        }


        .gold-btn {

            background:
                var(--gold);

            border: none;

            color:
                var(--navy);

            font-weight: 700;

            padding: 11px 20px;

            border-radius: 8px;

            transition: 0.3s;
        }


        .gold-btn:hover {

            background:
                #e1bd3d;

            color:
                var(--navy);

            transform:
                translateY(-1px);
        }


        .section-title {

            font-size: 20px;

            font-weight: 700;

            color:
                var(--navy);

            margin-bottom: 20px;
        }


        .info-box {

            background:
                #faf9f5;

            border:
                1px solid
                var(--border);

            border-radius: 10px;

            padding: 15px;

            margin-bottom: 15px;
        }


        .info-label {

            font-size: 12px;

            color:
                var(--muted);

            margin-bottom: 3px;
        }


        .info-value {

            font-weight: 600;

            color:
                var(--text);
        }


        /* =========================
           ALERTS
        ========================= */

        .alert {

            border-radius: 10px;

            margin-bottom: 25px;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 900px) {

            .profile-grid {

                grid-template-columns:
                    1fr;
            }

        }


        @media (max-width: 768px) {

            .sidebar {

                position: relative;

                width: 100%;

                height: auto;
            }


            .main-content {

                margin-left: 0;

                padding: 20px;
            }


            .topbar {

                flex-direction: column;

                align-items: flex-start;

                gap: 15px;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<div class="sidebar">

    <div class="logo">

        <div class="logo-icon">

            <i class="bi bi-building"></i>

        </div>

        <h3>Velora Hotel</h3>

        <span>HOTEL MANAGEMENT</span>

    </div>


    <ul class="sidebar-menu">

        <li>

            <a href="dashboard.php">

                <i class="bi bi-grid-1x2-fill"></i>

                Dashboard

            </a>

        </li>


        <li>

            <a href="rooms.php">

                <i class="bi bi-door-open-fill"></i>

                Browse Rooms

            </a>

        </li>


        <li>

            <a href="bookings.php">

                <i class="bi bi-calendar-check-fill"></i>

                My Bookings

            </a>

        </li>


        <li>

            <a href="invoices.php">

                <i class="bi bi-receipt-cutoff"></i>

                My Invoices

            </a>

        </li>


        <li>

            <a href="service_requests.php">

                <i class="bi bi-bell-fill"></i>

                Service Requests

            </a>

        </li>


        <li>

            <a href="profile.php"
               class="active">

                <i class="bi bi-person-fill"></i>

                My Profile

            </a>

        </li>


        <li class="logout-link">

            <a href="../auth/logout.php">

                <i class="bi bi-box-arrow-right"></i>

                Logout

            </a>

        </li>

    </ul>

</div>


<!-- =========================
     MAIN CONTENT
========================= -->

<div class="main-content">


    <!-- TOP BAR -->

    <div class="topbar">

        <div>

            <h2>My Profile</h2>

            <p>
                Manage your personal information and account.
            </p>

        </div>


        <div class="user-box">

            <div class="user-icon">

                <i class="bi bi-person-fill"></i>

            </div>

            <span>
                <?= e($user["full_name"]); ?>
            </span>

        </div>

    </div>


    <?php if ($message !== ""): ?>

        <div class="alert alert-success">

            <i class="bi bi-check-circle-fill me-2"></i>

            <?= e($message); ?>

        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="alert alert-danger">

            <i class="bi bi-exclamation-circle-fill me-2"></i>

            <?= e($error); ?>

        </div>

    <?php endif; ?>


    <div class="profile-grid">


        <!-- =========================
             PROFILE INFORMATION
        ========================= -->

        <div class="profile-card">

            <div class="profile-header">

                <div class="profile-avatar">

                    <i class="bi bi-person-fill"></i>

                </div>


                <div>

                    <h3>
                        <?= e($user["full_name"]); ?>
                    </h3>

                    <p>
                        Customer Account
                    </p>

                </div>

            </div>


            <h4 class="section-title">

                Personal Information

            </h4>


            <form method="POST">


                <div class="mb-3">

                    <label class="form-label">

                        Full Name

                    </label>

                    <input
                        type="text"
                        name="full_name"
                        class="form-control"
                        value="<?= e($user["full_name"]); ?>"
                        required
                    >

                </div>


                <div class="mb-3">

                    <label class="form-label">

                        Email Address

                    </label>

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        value="<?= e($user["email"]); ?>"
                        required
                    >

                </div>


                <div class="mb-4">

                    <label class="form-label">

                        Phone Number

                    </label>

                    <input
                        type="text"
                        name="phone"
                        class="form-control"
                        value="<?= e($user["phone"] ?? ""); ?>"
                        placeholder="Enter phone number"
                    >

                </div>


                <button
                    type="submit"
                    name="update_profile"
                    class="gold-btn"
                >

                    <i class="bi bi-check2-circle me-2"></i>

                    Save Changes

                </button>

            </form>

        </div>


        <!-- =========================
             CHANGE PASSWORD
        ========================= -->

        <div class="profile-card">

            <h4 class="section-title">

                <i class="bi bi-shield-lock-fill me-2"></i>

                Change Password

            </h4>


            <p class="text-muted mb-4">

                Keep your account secure by using a
                strong password.

            </p>


            <form method="POST">


                <div class="mb-3">

                    <label class="form-label">

                        Current Password

                    </label>

                    <input
                        type="password"
                        name="current_password"
                        class="form-control"
                        required
                    >

                </div>


                <div class="mb-3">

                    <label class="form-label">

                        New Password

                    </label>

                    <input
                        type="password"
                        name="new_password"
                        class="form-control"
                        minlength="6"
                        required
                    >

                    <small class="text-muted">

                        Minimum 6 characters.

                    </small>

                </div>


                <div class="mb-4">

                    <label class="form-label">

                        Confirm New Password

                    </label>

                    <input
                        type="password"
                        name="confirm_password"
                        class="form-control"
                        minlength="6"
                        required
                    >

                </div>


                <button
                    type="submit"
                    name="change_password"
                    class="gold-btn"
                >

                    <i class="bi bi-key-fill me-2"></i>

                    Change Password

                </button>

            </form>

        </div>


    </div>


    <!-- =========================
         ACCOUNT INFORMATION
    ========================= -->

    <div class="profile-card mt-4">

        <h4 class="section-title">

            <i class="bi bi-person-vcard-fill me-2"></i>

            Account Information

        </h4>


        <div class="row">


            <div class="col-md-4">

                <div class="info-box">

                    <div class="info-label">
                        Account Role
                    </div>

                    <div class="info-value">

                        Customer

                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="info-box">

                    <div class="info-label">
                        Account Status
                    </div>

                    <div class="info-value text-success">

                        <i class="bi bi-check-circle-fill me-1"></i>

                        <?= e(ucfirst($user["status"] ?? "Active")); ?>

                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="info-box">

                    <div class="info-label">
                        Email
                    </div>

                    <div class="info-value">

                        <?= e($user["email"]); ?>

                    </div>

                </div>

            </div>


        </div>

    </div>


</div>


</body>

</html>