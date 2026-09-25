<?php
session_start();
require_once 'admin_guard.php';

ensureAdminColumn($conn);

$adminCount = getAdminCount($conn);
$error = '';
$success = '';

if ($adminCount > 0) {
    $error = 'Controller setup is closed because an admin account already exists.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $adminCount === 0) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!$name || !$email || !$username || strlen($password) < 8) {
        $error = 'Complete all fields. Password must be at least 8 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid controller email address.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $createdAt = date("Y-m-d H:i:s");
        $gender = '';
        $mobile = '';
        $dob = '';
        $region = '';
        $province = '';
        $city = '';
        $barangay = '';
        $isAdmin = 1;

        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $check->close();

        if ($existing) {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password_hash = ?, is_admin = 1 WHERE id = ?");
            $stmt->bind_param("sssi", $name, $email, $passwordHash, $existing['id']);
        } else {
            $stmt = $conn->prepare("INSERT INTO users
                (name, email, gender, mobile, dob, username, password_hash, region, province, city, barangay, created_at, is_admin)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssssssi", $name, $email, $gender, $mobile, $dob, $username, $passwordHash, $region, $province, $city, $barangay, $createdAt, $isAdmin);
        }

        if ($stmt->execute()) {
            $success = 'Controller credentials created. You can now log in.';
            $adminCount = 1;
        } else {
            $error = 'Could not create controller credentials.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Create Controller Credentials</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Chewy&family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --j-red: #df001b;
            --j-deep: #8f1017;
            --j-ink: #1f2937;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            background:
                radial-gradient(circle at 16% 14%, rgba(255, 205, 67, .25), transparent 28%),
                linear-gradient(135deg, #fff8e8 0%, #fff 48%, #ffe7e7 100%);
            font-family: 'Poppins', sans-serif;
            color: var(--j-ink);
            padding: 22px;
        }

        .setup-shell {
            width: min(100%, 1040px);
            display: grid;
            grid-template-columns: .9fr 1.1fr;
            background: #fff;
            border: 1px solid #f1c9c9;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 48px rgba(97, 0, 11, .14);
        }

        .setup-brand {
            background: linear-gradient(180deg, #df001b 0%, #ba0018 100%);
            color: #fff;
            padding: 42px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 620px;
        }

        .setup-brand img,
        .setup-logo {
            width: 84px;
            background: #fff;
            border-radius: 12px;
            padding: 8px;
            margin-bottom: 24px;
        }

        .setup-brand h1 {
            color: #fff;
            font-size: clamp(3rem, 7vw, 5.6rem);
            line-height: .95;
            margin-bottom: 18px;
        }

        .setup-brand p {
            font-weight: 800;
            line-height: 1.55;
            max-width: 380px;
        }

        .setup-badge {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            border-radius: 999px;
            background: #ffcd43;
            color: #6c2b00;
            font-weight: 900;
            padding: 10px 16px;
            margin-bottom: 22px;
        }

        .setup-form {
            padding: 48px 46px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        h1 {
            font-family: 'Chewy', cursive;
            color: var(--j-red);
            font-size: clamp(2.5rem, 6vw, 4.2rem);
            margin: 0;
        }

        .setup-form h2 {
            color: var(--j-deep);
            font-size: 2rem;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .setup-copy {
            color: #667085;
            font-weight: 600;
            line-height: 1.5;
        }

        label {
            font-weight: 900;
        }

        .form-control {
            border: 1px solid #dfc1c1;
            border-radius: 8px;
            min-height: 50px;
            padding: 12px 14px;
            font-weight: 700;
        }

        .form-control:focus {
            border-color: var(--j-red);
            box-shadow: 0 0 0 .2rem rgba(223, 0, 27, .15);
        }

        .form-text {
            color: #667085;
            font-weight: 600;
        }

        .setup-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .btn-jolli {
            background: var(--j-red);
            color: #fff;
            border: 0;
            border-radius: 8px;
            min-height: 52px;
            font-weight: 900;
            box-shadow: 0 10px 22px rgba(223, 0, 27, .18);
        }

        .btn-jolli:hover {
            background: #b90017;
            color: #fff;
        }

        .btn-outline-jolli {
            border: 1px solid #f1b5b9;
            color: var(--j-red);
            background: #fff;
            border-radius: 8px;
            min-height: 52px;
            font-weight: 900;
            text-decoration: none;
            display: inline-flex;
            justify-content: center;
            align-items: center;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: var(--j-red);
            font-weight: 900;
            padding: 4px 6px;
        }

        .security-list {
            display: grid;
            gap: 10px;
            margin-top: 28px;
        }

        .security-item {
            border: 1px solid rgba(255, 255, 255, .24);
            border-radius: 8px;
            padding: 12px 14px;
            background: rgba(255, 255, 255, .08);
            font-weight: 800;
        }

        @media (max-width: 860px) {
            .setup-shell {
                grid-template-columns: 1fr;
            }

            .setup-brand {
                min-height: auto;
                padding: 30px;
            }

            .setup-form {
                padding: 32px;
            }
        }

        @media (max-width: 620px) {
            body {
                padding: 12px;
                align-items: start;
            }

            .setup-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .setup-form {
                padding: 26px 20px;
            }
        }
    </style>
</head>

<body>
    <main class="setup-shell">
        <section class="setup-brand">
            <div>
                <img src="jollibeeplg.png" alt="JolliBeep logo">
                <div class="setup-badge">Private manager access</div>
                <h1>Controller Setup</h1>
                <p>Create the first secure account for the person who manages orders, store queues, and delivery schedules.</p>
            </div>

            <div class="security-list">
                <div class="security-item">Only one setup is allowed.</div>
                <div class="security-item">Credentials are hidden from customers.</div>
                <div class="security-item">Use this account for the control center.</div>
            </div>
        </section>

        <section class="setup-form">
            <img src="jollibeeplg.png" alt="JolliBeep logo" class="setup-logo d-md-none">
            <h2>Create Controller Credentials</h2>
            <p class="setup-copy mb-4">Set up the private login used to view customer orders by store and schedule delivery updates.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if ($adminCount === 0): ?>
                <form method="post" autocomplete="off">
                    <div class="setup-grid">
                        <div class="mb-3">
                            <label for="name" class="form-label">Controller Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? ''); ?>" autocomplete="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>" autocomplete="username" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Controller Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>" autocomplete="email" required>
                    </div>

                    <div class="setup-grid">
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="position-relative">
                                <input type="password" class="form-control pe-5" id="password" name="password" minlength="8" autocomplete="new-password" required>
                                <button type="button" class="toggle-password" data-toggle-password="password">Show</button>
                            </div>
                            <div class="form-text">At least 8 characters.</div>
                        </div>
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="position-relative">
                                <input type="password" class="form-control pe-5" id="confirm_password" name="confirm_password" minlength="8" autocomplete="new-password" required>
                                <button type="button" class="toggle-password" data-toggle-password="confirm_password">Show</button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-jolli w-100">Create Controller Credentials</button>
                </form>
            <?php endif; ?>

            <a href="page12_admin_login.php" class="btn-outline-jolli w-100 mt-3">Go to Controller Login</a>
        </section>
    </main>

    <script>
        document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
            button.addEventListener('click', function () {
                const field = document.getElementById(this.dataset.togglePassword);
                const isHidden = field.type === 'password';
                field.type = isHidden ? 'text' : 'password';
                this.textContent = isHidden ? 'Hide' : 'Show';
            });
        });
    </script>
</body>

</html>
