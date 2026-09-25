<?php
session_start();
require_once 'admin_guard.php';

ensureAdminColumn($conn);
$adminCount = getAdminCount($conn);

if (!empty($_SESSION['admin_logged_in'])) {
    header("Location: page11_manager_orders.php");
    exit();
}

$error = '';
if (isset($_GET['admin_required'])) {
    $error = 'Please log in as a system controller first.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['admin_username'] ?? '');
    $password = $_POST['admin_password'] ?? '';

    $stmt = $conn->prepare("SELECT id, name, username, password_hash, is_admin FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ((int) $row['is_admin'] === 1 && password_verify($password, $row['password_hash'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id'] = $row['id'];
            $_SESSION['admin_name'] = $row['name'] ?: $row['username'];
            header("Location: page11_manager_orders.php");
            exit();
        }
    }

    $stmt->close();
    $error = 'Invalid controller account or password.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>JolliBeep Controller Login</title>
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
            background: linear-gradient(135deg, #fff8e8 0%, #fff 48%, #ffe7e7 100%);
            color: var(--j-ink);
            font-family: 'Poppins', sans-serif;
            padding: 22px;
        }

        .admin-card {
            width: min(100%, 960px);
            display: grid;
            grid-template-columns: .95fr 1.05fr;
            background: #fff;
            border: 1px solid #f1c9c9;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 48px rgba(97, 0, 11, .14);
        }

        .admin-brand {
            background: #df001b;
            color: #fff;
            padding: 42px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 520px;
        }

        .admin-brand img {
            width: 96px;
            background: #fff;
            border-radius: 12px;
            padding: 8px;
            margin-bottom: 22px;
        }

        .admin-brand h1 {
            font-family: 'Chewy', cursive;
            font-size: clamp(3rem, 7vw, 5.6rem);
            margin: 0;
            letter-spacing: 0;
        }

        .admin-brand p {
            font-weight: 800;
            line-height: 1.55;
            max-width: 420px;
        }

        .admin-form {
            padding: 52px 46px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .admin-form h2 {
            color: var(--j-deep);
            font-weight: 900;
            font-size: 2.2rem;
            margin-bottom: 8px;
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

        .btn-admin {
            background: var(--j-red);
            color: #fff;
            border: 0;
            border-radius: 8px;
            min-height: 52px;
            font-weight: 900;
            box-shadow: 0 10px 22px rgba(223, 0, 27, .18);
        }

        .btn-admin:hover {
            background: #b90017;
            color: #fff;
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
        }

        .small-note {
            color: #667085;
            font-weight: 700;
            font-size: .92rem;
        }

        @media (max-width: 820px) {
            .admin-card {
                grid-template-columns: 1fr;
            }

            .admin-brand {
                min-height: 260px;
                padding: 30px;
            }

            .admin-form {
                padding: 32px;
            }
        }
    </style>
</head>

<body>
    <main class="admin-card">
        <section class="admin-brand">
            <div>
                <img src="jollibeeplg.png" alt="JolliBeep logo">
                <h1>Control Center</h1>
                <p>Manage incoming orders, store queues, preparation status, and delivery schedules.</p>
            </div>
            <p class="mb-0">For JolliBeep system controllers only.</p>
        </section>

        <section class="admin-form">
            <h2>Controller Login</h2>
            <p class="text-muted mb-4">Sign in with private controller credentials.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label for="admin_username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="admin_username" name="admin_username" autocomplete="username" required>
                </div>
                <div class="mb-4">
                    <label for="admin_password" class="form-label">Password</label>
                    <div class="position-relative">
                        <input type="password" class="form-control pe-5" id="admin_password" name="admin_password" autocomplete="current-password" required>
                        <button type="button" class="toggle-password" id="togglePassword">Show</button>
                    </div>
                </div>
                <button type="submit" class="btn btn-admin w-100">Open Control Center</button>
            </form>

            <?php if ($adminCount === 0): ?>
                <p class="small-note mt-4 mb-0">No controller account exists yet. <a href="page13_controller_setup.php">Create controller credentials</a>.</p>
            <?php endif; ?>
        </section>
    </main>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const field = document.getElementById('admin_password');
            const isHidden = field.type === 'password';
            field.type = isHidden ? 'text' : 'password';
            this.textContent = isHidden ? 'Hide' : 'Show';
        });
    </script>
</body>

</html>
