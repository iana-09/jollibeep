<?php
session_start();
require_once 'db_connection.php';

if (empty($_SESSION['user_id'])) {
    header("Location: login.php?login_required=1");
    exit();
}

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = preg_replace('/\D/', '', $_POST['mobile'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';

    if (!$name || !$username || !$email || !$mobile || !$gender || !$dob) {
        $message = 'Please complete all required account fields.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } elseif (!preg_match('/^09\d{9}$/', $mobile)) {
        $message = 'Mobile number must start with 09 and contain 11 digits.';
        $messageType = 'error';
    } elseif ($newPassword !== '' && !preg_match('/^(?=.*\d)(?=.*[\W_]).{8,}$/', $newPassword)) {
        $message = 'New password must be at least 8 characters and include 1 number and 1 special character.';
        $messageType = 'error';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ?");
        $check->bind_param("ssi", $username, $email, $_SESSION['user_id']);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = 'Username or email is already used by another account.';
            $messageType = 'error';
        } else {
            if ($newPassword !== '') {
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, gender = ?, mobile = ?, dob = ?, username = ?, password_hash = ?, region = ?, province = ?, city = ?, barangay = ? WHERE id = ?");
                $stmt->bind_param("sssssssssssi", $name, $email, $gender, $mobile, $dob, $username, $passwordHash, $region, $province, $city, $barangay, $_SESSION['user_id']);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, gender = ?, mobile = ?, dob = ?, username = ?, region = ?, province = ?, city = ?, barangay = ? WHERE id = ?");
                $stmt->bind_param("ssssssssssi", $name, $email, $gender, $mobile, $dob, $username, $region, $province, $city, $barangay, $_SESSION['user_id']);
            }

            if ($stmt->execute()) {
                $_SESSION['username'] = $username;
                $_SESSION['customer_name'] = $name;
                $_SESSION['registered_city'] = $city;
                $message = 'Profile updated successfully.';
                $messageType = 'success';
            } else {
                $message = 'Could not update profile right now.';
                $messageType = 'error';
            }

            $stmt->close();
        }

        $check->close();
    }
}

$profile = null;
$stmt = $conn->prepare("SELECT name, email, gender, mobile, dob, username, region, province, city, barangay, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $profile = $row;
}
$stmt->close();

if (!$profile) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

function cleanProfile($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function addressLabel($fileName, $codeKey, $nameKey, $value)
{
    $value = (string) $value;
    if ($value === '') {
        return '';
    }

    $path = __DIR__ . DIRECTORY_SEPARATOR . 'addresses' . DIRECTORY_SEPARATOR . $fileName;
    if (!is_file($path)) {
        return $value;
    }

    $items = json_decode(file_get_contents($path), true);
    if (!is_array($items)) {
        return $value;
    }

    foreach ($items as $item) {
        if ((string) ($item[$codeKey] ?? '') === $value) {
            return (string) ($item[$nameKey] ?? $value);
        }
    }

    return $value;
}

$addressDisplay = [
    'region' => addressLabel('region.json', 'region_code', 'region_name', $profile['region']),
    'province' => addressLabel('province.json', 'province_code', 'province_name', $profile['province']),
    'city' => addressLabel('city.json', 'city_code', 'city_name', $profile['city']),
    'barangay' => addressLabel('barangay.json', 'brgy_code', 'brgy_name', $profile['barangay']),
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>JolliBeep Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Chewy&family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --j-red: #df001b;
            --j-deep: #9b1117;
            --j-yellow: #ffd33d;
            --j-cream: #fff8e8;
            --j-ink: #111827;
            --j-muted: #667085;
            --j-line: #e7eaf0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #fff8e8 0%, #fff 55%, #ffe8e8 100%);
            color: var(--j-ink);
        }

        .profile-shell {
            max-width: 1040px;
            margin: 0 auto;
            padding: 38px 18px 52px;
        }

        .profile-hero {
            background: linear-gradient(135deg, #df001b, #b40016);
            color: #fff;
            border-radius: 12px;
            padding: 30px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 130px;
            gap: 18px;
            align-items: center;
            box-shadow: 0 16px 34px rgba(97, 0, 11, .16);
        }

        .profile-hero h1 {
            font-family: 'Chewy', cursive;
            font-size: clamp(2.5rem, 6vw, 4.8rem);
            margin: 0;
        }

        .profile-hero p {
            margin: 8px 0 0;
            font-weight: 700;
        }

        .profile-hero img {
            width: 120px;
            height: auto;
            background: #fff;
            border-radius: 12px;
            padding: 8px;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-top: 22px;
        }

        .profile-card {
            background: #fff;
            border: 1px solid var(--j-line);
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
        }

        .profile-card h2 {
            color: var(--j-deep);
            font-size: 1.25rem;
            font-weight: 900;
            margin-bottom: 16px;
        }

        .profile-row {
            display: grid;
            gap: 7px;
            padding: 9px 0;
        }

        .profile-row label {
            color: var(--j-muted);
            font-weight: 800;
        }

        .profile-row input,
        .profile-row select {
            width: 100%;
            border: 1px solid #d9dee8;
            border-radius: 10px;
            padding: 11px 12px;
            font: inherit;
            font-weight: 700;
        }

        .field-with-button {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 8px;
        }

        .field-toggle {
            border: 1px solid #f0a4ad;
            color: var(--j-red);
            background: #fff;
            border-radius: 10px;
            padding: 0 14px;
            font-weight: 900;
        }

        .profile-message {
            margin-top: 18px;
            border-radius: 10px;
            padding: 12px 14px;
            font-weight: 800;
        }

        .profile-message.success {
            background: #eaffef;
            color: #176b2c;
        }

        .profile-message.error {
            background: #fff0f1;
            color: #b50016;
        }

        .profile-actions {
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
            border: 1px solid #f0a4ad;
            color: var(--j-red);
            background: #fff;
            border-radius: 8px;
            padding: 12px 18px;
            font-weight: 900;
            text-decoration: none;
        }

        @media (max-width: 760px) {
            .profile-hero,
            .profile-grid {
                grid-template-columns: 1fr;
            }

            .profile-hero img {
                width: 92px;
            }

            .field-with-button {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include 'menu-links.php'; ?>

    <main class="profile-shell">
        <section class="profile-hero">
            <div>
                <h1>My Profile</h1>
                <p>Welcome back, <?= cleanProfile($profile['name']); ?>. Review your saved JolliBeep account details here.</p>
            </div>
            <img src="jollibeeplg.png" alt="JolliBeep logo">
        </section>

        <?php if ($message): ?>
            <div class="profile-message <?= cleanProfile($messageType); ?>"><?= cleanProfile($message); ?></div>
        <?php endif; ?>

        <form method="post" action="page8_profile.php">
            <section class="profile-grid">
                <div class="profile-card">
                    <h2>Account Details</h2>
                    <div class="profile-row">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?= cleanProfile($profile['name']); ?>" required>
                    </div>
                    <div class="profile-row">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?= cleanProfile($profile['username']); ?>" required>
                    </div>
                    <div class="profile-row">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= cleanProfile($profile['email']); ?>" required>
                    </div>
                    <div class="profile-row">
                        <label for="mobile">Mobile</label>
                        <div class="field-with-button">
                            <input type="password" id="mobile" name="mobile" value="<?= cleanProfile($profile['mobile']); ?>" required pattern="09[0-9]{9}">
                            <button class="field-toggle" type="button" data-toggle-field="mobile">View</button>
                        </div>
                    </div>
                    <div class="profile-row">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" required>
                            <?php foreach (['Male', 'Female', 'Other'] as $genderOption): ?>
                                <option value="<?= cleanProfile($genderOption); ?>" <?= $profile['gender'] === $genderOption ? 'selected' : ''; ?>><?= cleanProfile($genderOption); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="profile-row">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob" value="<?= cleanProfile($profile['dob']); ?>" required>
                    </div>
                    <div class="profile-row">
                        <label for="new_password">Password</label>
                        <div class="field-with-button">
                            <input type="password" id="new_password" name="new_password" placeholder="Hidden. Enter new password to update.">
                            <button class="field-toggle" type="button" data-toggle-field="new_password">View</button>
                        </div>
                        <small class="text-muted">Leave blank to keep your current password.</small>
                    </div>
                </div>

                <div class="profile-card">
                    <h2>Saved Address</h2>
                    <div class="profile-row">
                        <label for="region">Region</label>
                        <input type="text" id="region" name="region" value="<?= cleanProfile($addressDisplay['region']); ?>">
                    </div>
                    <div class="profile-row">
                        <label for="province">Province</label>
                        <input type="text" id="province" name="province" value="<?= cleanProfile($addressDisplay['province']); ?>">
                    </div>
                    <div class="profile-row">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" value="<?= cleanProfile($addressDisplay['city']); ?>">
                    </div>
                    <div class="profile-row">
                        <label for="barangay">Barangay</label>
                        <input type="text" id="barangay" name="barangay" value="<?= cleanProfile($addressDisplay['barangay']); ?>">
                    </div>
                    <div class="profile-row">
                        <label>Member Since</label>
                        <input type="text" value="<?= cleanProfile($profile['created_at']); ?>" readonly>
                    </div>
                </div>
            </section>

            <div class="profile-actions">
                <button type="submit" class="btn-jolli">Save Changes</button>
                <a href="page5_jollibee_orderform.php" class="btn-outline-jolli">Start Ordering</a>
                <a href="page7_stores.php" class="btn-outline-jolli">Find Stores</a>
            </div>
        </form>

        <div class="profile-actions">
            <form action="logout.php" method="post">
                <button type="submit" class="btn-outline-jolli">Logout</button>
            </form>
        </div>
    </main>

    <?php include 'footer.php'; ?>
    <script>
        document.querySelectorAll('[data-toggle-field]').forEach(button => {
            button.addEventListener('click', () => {
                const field = document.getElementById(button.dataset.toggleField);
                const hidden = field.type === 'password';
                field.type = hidden ? 'text' : 'password';
                button.textContent = hidden ? 'Hide' : 'View';
            });
        });
    </script>
</body>

</html>
