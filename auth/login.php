<?php

require_once "../config/db.php";
require_once "../includes/functions.php";

startSession();

$error = "";


/* =====================================================
   REDIRECT IF ALREADY LOGGED IN
===================================================== */

if (isset($_SESSION["user_id"])) {

    if ($_SESSION["role"] === "admin") {

        header("Location: ../admin/dashboard.php");
        exit;
    }

    if ($_SESSION["role"] === "receptionist") {

        header("Location: ../receptionist/dashboard.php");
        exit;
    }

    if ($_SESSION["role"] === "customer") {

        header("Location: ../customer/dashboard.php");
        exit;
    }
}


/* =====================================================
   LOGIN
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");

    $password = $_POST["password"] ?? "";


    /* -------------------------------------------------
       VALIDATION
    ------------------------------------------------- */

    if ($email === "" || $password === "") {

        $error = "Please enter your email and password.";

    } else {

        /* -------------------------------------------------
           FIND ACTIVE USER
        ------------------------------------------------- */

        $stmt = $pdo->prepare("
            SELECT
                id,
                full_name,
                email,
                password,
                role,
                status
            FROM users
            WHERE email = ?
              AND status = 'active'
            LIMIT 1
        ");

        $stmt->execute([
            $email
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);


        /* -------------------------------------------------
           VERIFY PASSWORD
        ------------------------------------------------- */

        if (
            $user &&
            password_verify(
                $password,
                $user["password"]
            )
        ) {


            /* -------------------------------------------------
               SESSION
            ------------------------------------------------- */

            $_SESSION["user_id"] =
                $user["id"];

            $_SESSION["full_name"] =
                $user["full_name"];

            $_SESSION["email"] =
                $user["email"];

            $_SESSION["role"] =
                $user["role"];


            /* -------------------------------------------------
               ROLE-BASED REDIRECT
            ------------------------------------------------- */

            if ($user["role"] === "admin") {

                header(
                    "Location: ../admin/dashboard.php"
                );

                exit;
            }


            if ($user["role"] === "receptionist") {

                header(
                    "Location: ../receptionist/dashboard.php"
                );

                exit;
            }


            if ($user["role"] === "customer") {

                header(
                    "Location: ../customer/dashboard.php"
                );

                exit;
            }


            /* -------------------------------------------------
               UNKNOWN ROLE
            ------------------------------------------------- */

            $error =
                "Your account role is not configured correctly.";

        } else {

            $error =
                "Invalid email or password.";
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

    <title>
        Login | Velora Hotel
    </title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         BOOTSTRAP ICONS
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         VELORA CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/velora.css"
    >


    <style>

        /* =====================================================
           RESET
        ===================================================== */

        * {

            box-sizing: border-box;

            margin: 0;

            padding: 0;
        }


        /* =====================================================
           ROOT COLORS
        ===================================================== */

        :root {

            --navy:
                #071a3a;

            --navy-light:
                #0f2034;

            --gold:
                #d4af37;

            --gold-light:
                #f1ce57;

            --background:
                #f7f8fa;

            --surface:
                #ffffff;

            --text:
                #172033;

            --muted:
                #64748b;

            --border:
                #e5e7eb;

            --danger:
                #dc2626;
        }


        /* =====================================================
           BODY
        ===================================================== */

        body {

            min-height: 100vh;

            font-family:
                "Segoe UI",
                Arial,
                sans-serif;

            background:

                linear-gradient(
                    135deg,
                    #f7f8fa 0%,
                    #f4f2ea 50%,
                    #eef2f7 100%
                );

            color:
                var(--text);
        }


        /* =====================================================
           LOGIN PAGE
        ===================================================== */

        .login-wrapper {

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding:
                35px 15px;

            position: relative;

            overflow: hidden;
        }


        /* =====================================================
           DECORATIVE CIRCLES
        ===================================================== */

        .circle-one {

            position: absolute;

            width: 350px;

            height: 350px;

            background:
                rgba(212, 175, 55, 0.08);

            border-radius: 50%;

            top:
                -150px;

            left:
                -130px;
        }


        .circle-two {

            position: absolute;

            width: 420px;

            height: 420px;

            background:
                rgba(7, 26, 58, 0.07);

            border-radius: 50%;

            bottom:
                -190px;

            right:
                -170px;
        }


        /* =====================================================
           LOGIN CARD
        ===================================================== */

        .login-card {

            width: 100%;

            max-width: 450px;

            background:
                var(--surface);

            border:
                1px solid var(--border);

            border-radius:
                22px;

            padding:
                42px;

            position: relative;

            z-index: 2;

            box-shadow:
                0 20px 55px
                rgba(7, 26, 58, 0.12);
        }


        /* =====================================================
           TOP GOLD LINE
        ===================================================== */

        .login-card::before {

            content: "";

            position: absolute;

            top: 0;

            left: 50%;

            transform:
                translateX(-50%);

            width: 90px;

            height: 4px;

            background:
                var(--gold);

            border-radius:
                0 0 6px 6px;
        }


        /* =====================================================
           BRAND
        ===================================================== */

        .brand {

            text-align: center;

            margin-bottom: 30px;
        }


        .brand-icon {

            width: 68px;

            height: 68px;

            margin:
                0 auto 16px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius:
                18px;

            background:
                var(--navy);

            color:
                var(--gold);

            font-size:
                30px;

            box-shadow:
                0 10px 25px
                rgba(7, 26, 58, 0.18);
        }


        .brand h1 {

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size:
                29px;

            font-weight:
                700;

            color:
                var(--navy);

            margin:
                0;
        }


        .brand p {

            margin-top:
                7px;

            color:
                var(--muted);

            font-size:
                14px;
        }


        /* =====================================================
           LOGIN HEADING
        ===================================================== */

        .login-heading {

            margin-bottom:
                24px;
        }


        .login-heading h2 {

            color:
                var(--navy);

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size:
                25px;

            font-weight:
                700;

            margin-bottom:
                7px;
        }


        .login-heading p {

            color:
                var(--muted);

            margin:
                0;

            font-size:
                14px;
        }


        /* =====================================================
           ERROR
        ===================================================== */

        .login-alert {

            display: flex;

            align-items: center;

            gap: 10px;

            background:
                #fef2f2;

            color:
                #991b1b;

            border:
                1px solid #fecaca;

            border-radius:
                10px;

            padding:
                12px 14px;

            margin-bottom:
                20px;

            font-size:
                14px;
        }


        .login-alert i {

            font-size:
                17px;
        }


        /* =====================================================
           FORM GROUP
        ===================================================== */

        .form-group {

            margin-bottom:
                20px;
        }


        .form-label {

            display:
                block;

            font-size:
                14px;

            font-weight:
                600;

            color:
                #334155;

            margin-bottom:
                8px;
        }


        /* =====================================================
           INPUT WRAPPER
        ===================================================== */

        .input-wrapper {

            position:
                relative;
        }


        .input-icon {

            position:
                absolute;

            left:
                15px;

            top:
                50%;

            transform:
                translateY(-50%);

            color:
                #94a3b8;

            font-size:
                17px;

            z-index:
                2;
        }


        /* =====================================================
           INPUT
        ===================================================== */

        .form-control {

            height:
                52px;

            border:
                1px solid var(--border);

            border-radius:
                10px;

            padding-left:
                45px;

            padding-right:
                15px;

            font-size:
                15px;

            color:
                #000000;

            background:
                #ffffff;

            transition:
                0.2s;
        }


        .form-control::placeholder {

            color:
                #94a3b8;
        }


        .form-control:focus {

            color:
                #000000;

            background:
                #ffffff;

            border-color:
                var(--gold);

            box-shadow:
                0 0 0 4px
                rgba(212, 175, 55, 0.12);
        }


        /* =====================================================
           LOGIN BUTTON
        ===================================================== */

        .login-btn {

            width:
                100%;

            height:
                52px;

            border:
                none;

            border-radius:
                10px;

            background:
                var(--navy);

            color:
                white;

            font-size:
                15px;

            font-weight:
                700;

            transition:
                0.25s;

            box-shadow:
                0 7px 18px
                rgba(7, 26, 58, 0.18);
        }


        .login-btn:hover {

            background:
                #102b55;

            color:
                var(--gold);

            transform:
                translateY(-2px);

            box-shadow:
                0 10px 24px
                rgba(7, 26, 58, 0.24);
        }


        .login-btn i {

            color:
                var(--gold);
        }


        /* =====================================================
           REGISTER
        ===================================================== */

        .register-section {

            text-align:
                center;

            margin-top:
                25px;

            padding-top:
                22px;

            border-top:
                1px solid var(--border);

            color:
                var(--muted);

            font-size:
                14px;
        }


        .register-section a {

            color:
                #a88418;

            font-weight:
                700;

            text-decoration:
                none;

            margin-left:
                4px;

            transition:
                0.2s;
        }


        .register-section a:hover {

            color:
                var(--navy);

            text-decoration:
                underline;
        }


        /* =====================================================
           BACK HOME
        ===================================================== */

        .back-home {

            text-align:
                center;

            margin-top:
                20px;
        }


        .back-home a {

            display:
                inline-flex;

            align-items:
                center;

            gap:
                4px;

            color:
                var(--muted);

            text-decoration:
                none;

            font-size:
                13px;

            transition:
                0.2s;
        }


        .back-home a:hover {

            color:
                var(--gold-dark);

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 480px) {

            .login-wrapper {

                padding:
                    25px 15px;
            }


            .login-card {

                padding:
                    34px 22px;

                border-radius:
                    18px;
            }


            .brand h1 {

                font-size:
                    25px;
            }


            .login-heading h2 {

                font-size:
                    22px;
            }


            .brand-icon {

                width:
                    60px;

                height:
                    60px;

                font-size:
                    27px;
            }
        }

    </style>

</head>


<body>


<!-- =====================================================
     LOGIN WRAPPER
===================================================== -->

<div class="login-wrapper">


    <!-- DECORATIVE BACKGROUND -->

    <div class="circle-one"></div>

    <div class="circle-two"></div>


    <!-- =================================================
         LOGIN CARD
    ================================================== -->

    <div class="login-card">


        <!-- =================================================
             BRAND
        ================================================== -->

        <div class="brand">

            <div class="brand-icon">

                <i class="bi bi-buildings"></i>

            </div>


            <h1>
                Velora Hotel
            </h1>


            <p>
                Smart management. Better experience.
            </p>

        </div>


        <!-- =================================================
             LOGIN HEADING
        ================================================== -->

        <div class="login-heading">

            <h2>
                Welcome Back
            </h2>


            <p>
                Sign in to continue to your account.
            </p>

        </div>


        <!-- =================================================
             ERROR MESSAGE
        ================================================== -->

        <?php if ($error !== ""): ?>

            <div class="login-alert">

                <i class="bi bi-exclamation-circle"></i>

                <span>

                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>

                </span>

            </div>

        <?php endif; ?>


        <!-- =================================================
             LOGIN FORM
        ================================================== -->

        <form
            method="POST"
            action=""
        >


            <!-- =================================================
                 EMAIL
            ================================================== -->

            <div class="form-group">

                <label
                    for="email"
                    class="form-label"
                >

                    Email Address

                </label>


                <div class="input-wrapper">

                    <i
                        class="bi bi-envelope input-icon"
                    ></i>


                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="Enter your email address"
                        value="<?= htmlspecialchars(
                            $_POST["email"] ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>"
                        autocomplete="email"
                        required
                    >

                </div>

            </div>


            <!-- =================================================
                 PASSWORD
            ================================================== -->

            <div class="form-group">

                <label
                    for="password"
                    class="form-label"
                >

                    Password

                </label>


                <div class="input-wrapper">

                    <i
                        class="bi bi-lock input-icon"
                    ></i>


                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >

                </div>

            </div>


            <!-- =================================================
                 LOGIN BUTTON
            ================================================== -->

            <button
                type="submit"
                class="login-btn"
            >

                <i
                    class="bi bi-box-arrow-in-right me-2"
                ></i>

                Sign In

            </button>

        </form>


        <!-- =================================================
             REGISTER
        ================================================== -->

        <div class="register-section">

            Don't have an account?

            <a href="register.php">

                Create an account

            </a>

        </div>


        <!-- =================================================
             BACK TO HOME
        ================================================== -->

        <div class="back-home">

            <a href="../index.php">

                <i class="bi bi-arrow-left"></i>

                Back to Home

            </a>

        </div>


    </div>

</div>


</body>

</html>