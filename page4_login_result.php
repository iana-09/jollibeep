<?php
session_start();
require_once 'db_connection.php';

$isValid = false;
$enteredUsername = '';
$loginTime = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $enteredUsername = trim($_POST['username'] ?? '');
    $enteredPassword = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, name, city, password_hash FROM users WHERE username = ?");
    $stmt->bind_param("s", $enteredUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($enteredPassword, $row['password_hash'])) {
            $isValid = true;
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $enteredUsername;
            $_SESSION['customer_name'] = $row['name'];
            $_SESSION['registered_city'] = $row['city'] ?? '';
            date_default_timezone_set("Asia/Manila");
            $_SESSION['login_time'] = date("F d, Y h:i A");
        }
    }

    $stmt->close();
} elseif (!empty($_SESSION['username'])) {
    $isValid = true;
    $enteredUsername = $_SESSION['username'];
}

$loginTime = $_SESSION['login_time'] ?? '';
$displayName = $_SESSION['customer_name'] ?? $enteredUsername;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>JolliBeep Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Chewy&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --j-red: #d71920;
            --j-deep: #9b1117;
            --j-yellow: #ffd33d;
            --j-cream: #fff8e8;
            --j-ink: #321315;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--j-cream);
            color: var(--j-ink);
        }

        .dashboard-shell {
            max-width: 1040px;
            margin: 0 auto;
            padding: 38px 18px 48px;
        }

        .dashboard-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 280px;
            gap: 24px;
            background: #fff;
            border: 1px solid #f0d0d0;
            border-radius: 8px;
            padding: 28px;
            box-shadow: 0 16px 38px rgba(99, 20, 20, .1);
        }

        h1 {
            font-family: 'Chewy', cursive;
            font-size: clamp(2.6rem, 6vw, 5rem);
            color: var(--j-red);
            margin: 0;
        }

        .status-pill {
            display: inline-flex;
            width: max-content;
            background: #eaffef;
            color: #176b2c;
            border-radius: 999px;
            padding: 7px 12px;
            font-weight: 900;
            margin-bottom: 12px;
        }

        .logo-box {
            background: #fff8e8;
            border: 1px solid #f0d0d0;
            border-radius: 8px;
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .logo-box img {
            max-width: 180px;
            width: 100%;
            height: auto;
        }

        .action-grid {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 22px;
        }

        .btn-jolli {
            background: var(--j-red);
            color: #fff;
            border: 0;
            border-radius: 8px;
            padding: 12px 18px;
            font-weight: 900;
            text-decoration: none;
        }

        .btn-jolli:hover {
            background: var(--j-deep);
            color: #fff;
        }

        .btn-outline-jolli {
            border: 1px solid #e3a4a4;
            color: var(--j-red);
            background: #fff;
            border-radius: 8px;
            padding: 12px 18px;
            font-weight: 800;
            text-decoration: none;
        }

        .error-card {
            max-width: 620px;
            margin: 60px auto;
            background: #fff;
            border-left: 8px solid var(--j-red);
            border-radius: 8px;
            padding: 28px;
            box-shadow: 0 16px 38px rgba(99, 20, 20, .1);
        }

        @media (max-width: 760px) {
            .dashboard-card {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include 'menu-links.php'; ?>

    <main class="dashboard-shell">
        <?php if ($isValid): ?>
            <section class="dashboard-card">
                <div>
                    <span class="status-pill">Logged in</span>
                    <h1>Hello, <?= htmlspecialchars($displayName); ?></h1>
                    <p class="lead mt-2 mb-0">Your account is ready. Start a cart, choose pickup or delivery, and review your receipt before checkout.</p>
                    <?php if ($loginTime): ?>
                        <p class="text-muted mt-3 mb-0"><strong>Login time:</strong> <?= htmlspecialchars($loginTime); ?></p>
                    <?php endif; ?>
                    <div class="action-grid">
                        <a href="page5_jollibee_orderform.php" class="btn-jolli">Start Ordering</a>
                        <a href="page8_profile.php" class="btn-outline-jolli">View Profile</a>
                        <form action="logout.php" method="post">
                            <button type="submit" class="btn-outline-jolli">Logout</button>
                        </form>
                    </div>
                </div>
                <div class="logo-box">
                    <img src="jollibeeplg.png" alt="JolliBeep mascot">
                </div>
            </section>
        <?php else: ?>
            <section class="error-card text-center">
                <h1 class="h2 text-danger fw-bold">Login failed</h1>
                <p class="mb-4">The username or password did not match an account.</p>
                <a href="login.php" class="btn-jolli">Try Again</a>
            </section>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>
</body>

</html>
