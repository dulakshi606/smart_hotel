<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

startSession();

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (
        empty($full_name) ||
        empty($email) ||
        empty($password)
    ) {

        $message = "Please fill all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Invalid email address.";

    } elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";

    } elseif (strlen($password) < 6) {

        $message = "Password must contain at least 6 characters.";

    } else {

        $check = $pdo->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        $check->execute([$email]);

        if ($check->fetch()) {

            $message = "Email already registered.";

        } else {

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare(
                "INSERT INTO users
                (full_name, email, phone, password, role)
                VALUES (?, ?, ?, ?, 'customer')"
            );

            $stmt->execute([
                $full_name,
                $email,
                $phone,
                $hashedPassword
            ]);

            header("Location: login.php?registered=1");
            exit;
        }
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

    <title>Create Account | Velora Hotel</title>

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

        /* =========================
           COLOR THEME
        ========================= */

        :root {

            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --accent: #06b6d4;

            --background: #f1f5f9;
            --surface: #ffffff;

            --text: #0f172a;
            --muted: #64748b;

            --border: #e2e8f0;

            --danger: #dc2626;

        }


        /* =========================
           GLOBAL
        ========================= */

        * {
            box-sizing: border-box;
        }

        body {

            margin: 0;

            min-height: 100vh;

            font-family:
                "Segoe UI",
                Arial,
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #eff6ff 0%,
                    #f8fafc 50%,
                    #ecfeff 100%
                );

            color: var(--text);

        }


        /* =========================
           REGISTER WRAPPER
        ========================= */

        .register-wrapper {

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 35px 15px;

            position: relative;

            overflow: hidden;

        }


        /* =========================
           BACKGROUND CIRCLES
        ========================= */

        .circle-one {

            position: absolute;

            width: 320px;
            height: 320px;

            background:
                rgba(37, 99, 235, 0.08);

            border-radius: 50%;

            top: -120px;
            left: -120px;

        }

        .circle-two {

            position: absolute;

            width: 380px;
            height: 380px;

            background:
                rgba(6, 182, 212, 0.08);

            border-radius: 50%;

            bottom: -150px;
            right: -150px;

        }


        /* =========================
           REGISTER CARD
        ========================= */

        .register-card {

            width: 100%;

            max-width: 500px;

            background: var(--surface);

            border: 1px solid var(--border);

            border-radius: 20px;

            padding: 40px;

            box-shadow:
                0 20px 50px
                rgba(15, 23, 42, 0.10);

            position: relative;

            z-index: 2;

        }


        /* =========================
           BRAND
        ========================= */

        .brand {

            text-align: center;

            margin-bottom: 28px;

        }

        .brand-icon {

            width: 62px;
            height: 62px;

            margin: 0 auto 14px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 16px;

            background:
                linear-gradient(
                    135deg,
                    var(--primary),
                    var(--accent)
                );

            color: white;

            font-size: 28px;

            box-shadow:
                0 8px 20px
                rgba(37, 99, 235, 0.20);

        }

        .brand h1 {

            font-size: 26px;

            font-weight: 750;

            margin: 0;

            color: var(--text);

        }

        .brand p {

            margin-top: 6px;

            color: var(--muted);

            font-size: 14px;

        }


        /* =========================
           HEADING
        ========================= */

        .register-heading {

            margin-bottom: 24px;

        }

        .register-heading h2 {

            font-size: 24px;

            font-weight: 700;

            margin-bottom: 6px;

        }

        .register-heading p {

            color: var(--muted);

            font-size: 14px;

            margin: 0;

        }


        /* =========================
           ERROR MESSAGE
        ========================= */

        .register-alert {

            display: flex;

            align-items: center;

            gap: 10px;

            background: #fef2f2;

            color: #991b1b;

            border: 1px solid #fecaca;

            border-radius: 10px;

            padding: 12px 14px;

            margin-bottom: 20px;

            font-size: 14px;

        }


        /* =========================
           FORM
        ========================= */

        .form-group {

            margin-bottom: 18px;

        }

        .form-label {

            font-size: 14px;

            font-weight: 600;

            color: #334155;

            margin-bottom: 8px;

        }


        /* =========================
           INPUT
        ========================= */

        .input-wrapper {

            position: relative;

        }

        .input-icon {

            position: absolute;

            left: 15px;

            top: 50%;

            transform: translateY(-50%);

            color: #94a3b8;

            font-size: 17px;

            z-index: 2;

        }

        .form-control {

            height: 50px;

            border: 1px solid var(--border);

            border-radius: 10px;

            padding-left: 45px;

            padding-right: 15px;

            font-size: 15px;

            color: #000000;

            background: #ffffff;

            transition: 0.2s;

        }

        .form-control::placeholder {

            color: #94a3b8;

        }

        .form-control:focus {

            color: #000000;

            background: #ffffff;

            border-color: var(--primary);

            box-shadow:
                0 0 0 4px
                rgba(37, 99, 235, 0.10);

        }


        /* =========================
           CREATE ACCOUNT BUTTON
        ========================= */

        .register-btn {

            width: 100%;

            height: 50px;

            border: none;

            border-radius: 10px;

            background:
                linear-gradient(
                    135deg,
                    var(--primary),
                    var(--primary-dark)
                );

            color: white;

            font-size: 15px;

            font-weight: 700;

            transition: 0.25s;

            box-shadow:
                0 6px 15px
                rgba(37, 99, 235, 0.20);

        }

        .register-btn:hover {

            background:
                linear-gradient(
                    135deg,
                    var(--primary-dark),
                    #1e40af
                );

            transform: translateY(-2px);

            box-shadow:
                0 10px 22px
                rgba(37, 99, 235, 0.25);

        }


        /* =========================
           LOGIN SECTION
        ========================= */

        .login-section {

            text-align: center;

            margin-top: 24px;

            padding-top: 20px;

            border-top: 1px solid var(--border);

            color: var(--muted);

            font-size: 14px;

        }

        .login-section a {

            color: var(--primary);

            font-weight: 700;

            text-decoration: none;

            margin-left: 4px;

        }

        .login-section a:hover {

            color: var(--primary-dark);

            text-decoration: underline;

        }


        /* =========================
           BACK HOME
        ========================= */

        .back-home {

            text-align: center;

            margin-top: 18px;

        }

        .back-home a {

            color: var(--muted);

            text-decoration: none;

            font-size: 13px;

        }

        .back-home a:hover {

            color: var(--primary);

        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 500px) {

            .register-card {

                padding: 30px 22px;

                border-radius: 16px;

            }

            .brand h1 {

                font-size: 23px;

            }

            .register-heading h2 {

                font-size: 22px;

            }

        }

    </style>

<link rel="stylesheet" href="../assets/css/velora.css">
</head>


<body>


<div class="register-wrapper">


    <!-- Background Decorations -->

    <div class="circle-one"></div>

    <div class="circle-two"></div>


    <!-- Register Card -->

    <div class="register-card">


        <!-- Brand -->

        <div class="brand">

            <div class="brand-icon">

                <i class="bi bi-building"></i>

            </div>

            <h1>
                Velora Hotel
            </h1>

            <p>
                Smart management. Better experience.
            </p>

        </div>


        <!-- Heading -->

        <div class="register-heading">

            <h2>
                Create Your Account
            </h2>

            <p>
                Register as a customer and start booking your room.
            </p>

        </div>


        <!-- Error -->

        <?php if ($message): ?>

            <div class="register-alert">

                <i class="bi bi-exclamation-circle"></i>

                <span>
                    <?= e($message) ?>
                </span>

            </div>

        <?php endif; ?>


        <!-- Registration Form -->

        <form method="POST">


            <!-- Full Name -->

            <div class="form-group">

                <label class="form-label">
                    Full Name
                </label>

                <div class="input-wrapper">

                    <i class="bi bi-person input-icon"></i>

                    <input
                        type="text"
                        name="full_name"
                        class="form-control"
                        placeholder="Enter your full name"
                        value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                        autocomplete="name"
                        required
                    >

                </div>

            </div>


            <!-- Email -->

            <div class="form-group">

                <label class="form-label">
                    Email Address
                </label>

                <div class="input-wrapper">

                    <i class="bi bi-envelope input-icon"></i>

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        placeholder="Enter your email address"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        autocomplete="email"
                        required
                    >

                </div>

            </div>


            <!-- Phone -->

            <div class="form-group">

                <label class="form-label">
                    Phone Number
                </label>

                <div class="input-wrapper">

                    <i class="bi bi-telephone input-icon"></i>

                    <input
                        type="text"
                        name="phone"
                        class="form-control"
                        placeholder="Enter your phone number"
                        value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                        autocomplete="tel"
                    >

                </div>

            </div>


            <!-- Password -->

            <div class="form-group">

                <label class="form-label">
                    Password
                </label>

                <div class="input-wrapper">

                    <i class="bi bi-lock input-icon"></i>

                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        placeholder="Create a password"
                        autocomplete="new-password"
                        required
                    >

                </div>

            </div>


            <!-- Confirm Password -->

            <div class="form-group">

                <label class="form-label">
                    Confirm Password
                </label>

                <div class="input-wrapper">

                    <i class="bi bi-shield-lock input-icon"></i>

                    <input
                        type="password"
                        name="confirm_password"
                        class="form-control"
                        placeholder="Confirm your password"
                        autocomplete="new-password"
                        required
                    >

                </div>

            </div>


            <!-- Create Account -->

            <button
                type="submit"
                class="register-btn"
            >

                <i class="bi bi-person-plus me-2"></i>

                Create Account

            </button>

        </form>


        <!-- Login -->

        <div class="login-section">

            Already have an account?

            <a href="login.php">
                Sign In
            </a>

        </div>


        <!-- Home -->

        <div class="back-home">

            <a href="../index.php">

                <i class="bi bi-arrow-left me-1"></i>

                Back to Home

            </a>

        </div>


    </div>

</div>


</body>

</html>