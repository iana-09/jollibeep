<?php
require_once 'db_connection.php';
session_start();

// If POST request and no session yet, process the registration
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Clear previous registration session (if any)
    unset($_SESSION['registration_success']);
    $name = trim($_POST['name']);
    $region = $_POST['region'] ?? '';
    $province = $_POST['province'] ?? '';
    $city = $_POST['city'] ?? '';
    $barangay = $_POST['barangay'] ?? '';
    $email = trim($_POST['email']);
    $gender = $_POST['gender'] ?? null;

    if (!$gender) {
        echo "<script>alert('Please select a gender.'); window.history.back();</script>";
        exit();
    }
    $mobile = trim($_POST['mobile']);
    $dob = $_POST['dob'];
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $created_at = date("Y-m-d H:i:s");

    // Check for duplicate username/email
    $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check->bind_param("ss", $username, $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<script>
            alert('Username or Email already exists!');
            window.history.back();
        </script>";
        exit();
    }

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users 
        (name, email, gender, mobile, dob, username, password_hash, region, province, city, barangay, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "ssssssssssss",
        $name,
        $email,
        $gender,
        $mobile,
        $dob,
        $username,
        $password_hash,
        $region,
        $province,
        $city,
        $barangay,
        $created_at
    );

    if ($stmt->execute()) {
        // Store data to session for display
        $_SESSION['registration_success'] = compact(
            'name',
            'region',
            'province',
            'city',
            'barangay',
            'email',
            'gender',
            'mobile',
            'dob',
            'username'
        );

        // Redirect to self (GET method) to avoid re-insertion on refresh
        header("Location: page2_registration_result.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
        exit();
    }
}
if (isset($_SESSION['registration_success'])) {
    // Extract data from session and keep it for refresh
    extract($_SESSION['registration_success']);
} else {
    // If no registration data, redirect back
    header("Location: page1_registration.php");
    exit();
} ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Welcome to JolliBeep</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Raleway&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Chewy&display=swap" rel="stylesheet">
    <style>
        @keyframes fadeSlideUp {
            0% {
                opacity: 0;
                transform: translateY(40px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .welcome-message {
            animation: fadeSlideUp 1s ease-out forwards;
            opacity: 0;
            background-color: #fff;
            border: 1px solid #f0d0d0;
            border-top: 8px solid #df001b;
            border-radius: 12px;
            padding: 42px 28px;
            max-width: 760px;
            margin: 40px auto 30px;
            box-shadow: 0 18px 44px rgba(99, 20, 20, 0.12);
        }

        body {
            font-family: 'Raleway', sans-serif;
            background: linear-gradient(135deg, #fff8e8 0%, #fff 55%, #ffe8e8 100%);
            padding-top: 50px;
        }

        h1.page-title {
            font-family: 'Chewy', cursive;
            font-size: clamp(2.5rem, 6vw, 4.6rem);
            color: #df001b;
            text-align: center;
            font-weight: bold;
            text-transform: none;
            text-shadow: none;
            margin-top: 18px;
        }

        .btn-danger {
            background-color: #df001b;
            border: none;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 1px;
            transition: background-color 0.3s ease;
            border-radius: 8px;
            padding: 12px 20px;
        }

        .btn-danger:hover {
            background-color: #a80000;
        }

        .alert-warning,
        .alert-danger {
            background-color: #ffe5e5;
            border-color: #f5bcbc;
            color: #b20000;
            font-weight: 600;
            text-align: center;
            margin-top: 40px;
        }
    </style>
</head>

<body>
    <?php include 'menu-links.php'; ?>

    <div class="container">
        <h1 class="page-title">You are all set!</h1>

        <div class="welcome-message text-center">
            <img src="jollibeeplg.png" alt="Jollibee Logo" width="100" class="mb-3">
            <h2 class="text-danger fw-bold">Welcome to JolliBeep!</h2>
            <p class="mt-3 fw-semibold text-dark">Your account is ready. Log in to start building your cart and ordering your favorites.</p>
            <div class="mt-4 d-grid gap-3 d-sm-flex justify-content-center">
                <a href="login.php" class="btn btn-danger btn-lg px-5">Go to Login</a>
                <a href="page5_jollibee_orderform.php" class="btn btn-outline-danger btn-lg px-5">View Menu</a>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
