<?php
session_start();

$error = isset($_GET['login_required']) ? 'Please log in before placing an order.' : '';
$isLoggedIn = !empty($_SESSION['username']);
$displayName = $_SESSION['customer_name'] ?? $_SESSION['username'] ?? '';
$initialPanel = 'choice';
if (isset($_GET['mode']) && $_GET['mode'] === 'register') {
    $initialPanel = 'register';
} elseif (isset($_GET['mode']) && $_GET['mode'] === 'login') {
    $initialPanel = 'login';
} elseif ($error) {
    $initialPanel = 'login';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>JolliBeep Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Chewy&family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --j-red: #df001b;
            --j-deep: #9b1117;
            --j-yellow: #ffd33d;
            --j-ink: #2b3038;
            --j-muted: #666f7d;
            --j-line: #f1caca;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            color: var(--j-ink);
            background: #fff;
            overflow-x: hidden;
        }

        .home-background-frame {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            border: 0;
            z-index: 0;
            background: #fff;
            pointer-events: none;
        }

        .home-background-dim {
            position: fixed;
            inset: 0;
            z-index: 1;
            background: rgba(15, 18, 22, .46);
            backdrop-filter: blur(2px);
            pointer-events: none;
        }

        .account-shell {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 34px 18px;
        }

        .account-modal {
            width: min(100%, 720px);
            max-height: calc(100vh - 68px);
            overflow-y: auto;
            background: #fff;
            border-radius: 14px;
            padding: clamp(30px, 5vw, 58px);
            position: relative;
            text-align: center;
            box-shadow: 0 28px 80px rgba(0, 0, 0, .24);
            animation: modalIn .28s ease both;
        }

        .account-modal.register-mode { width: min(100%, 980px); }
        .account-modal.login-mode { width: min(100%, 720px); }

        @keyframes modalIn {
            from { opacity: 0; transform: translateY(18px) scale(.985); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .modal-close-link {
            position: absolute;
            top: 22px;
            right: 22px;
            width: 46px;
            height: 46px;
            border: 1px solid #e7eaf0;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #7b7f87;
            text-decoration: none;
            font-size: 2rem;
            line-height: 1;
            background: #fff;
        }

        .modal-close-link:hover,
        .modal-close-link:focus-visible {
            color: var(--j-red);
            border-color: #f3b7bd;
            outline: none;
        }

        .account-logo {
            width: 220px;
            max-width: 70%;
            height: 88px;
            object-fit: contain;
            margin: 26px auto 22px;
            display: block;
        }

        .auth-panel {
            display: none;
            animation: panelIn .24s ease both;
        }

        .auth-panel.active { display: block; }

        @keyframes panelIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .account-modal h1 {
            color: var(--j-ink);
            font-size: clamp(1.9rem, 5vw, 2.75rem);
            line-height: 1.12;
            font-weight: 900;
            margin: 0 0 14px;
        }

        .account-modal p {
            color: var(--j-muted);
            max-width: 560px;
            margin: 0 auto 30px;
            font-size: 1.06rem;
            line-height: 1.55;
            font-weight: 500;
        }

        .login-alert {
            max-width: 560px;
            margin: 0 auto 18px;
            border: 1px solid #ffb5bc;
            border-radius: 12px;
            background: #fff4f5;
            color: var(--j-deep);
            font-weight: 800;
            padding: 12px 14px;
        }

        .account-actions {
            display: grid;
            gap: 18px;
            max-width: 560px;
            margin: 0 auto;
        }

        .register-btn,
        .continue-btn,
        .login-text-btn,
        .submit-login-btn,
        .back-choice-btn {
            min-height: 56px;
            border-radius: 999px;
            font-weight: 900;
            font-size: 1.04rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: transform .18s ease, background .18s ease, color .18s ease, border-color .18s ease;
        }

        .register-btn,
        .submit-login-btn {
            background: var(--j-red);
            color: #fff;
            border: 0;
            box-shadow: 0 12px 24px rgba(223, 0, 27, .2);
        }

        .register-btn:hover,
        .submit-login-btn:hover,
        .register-btn:focus-visible,
        .submit-login-btn:focus-visible {
            background: var(--j-deep);
            color: #fff;
            transform: translateY(-1px);
            outline: none;
        }

        .login-text-btn,
        .back-choice-btn {
            min-height: 44px;
            border: 0;
            background: transparent;
            color: var(--j-red);
            cursor: pointer;
        }

        .login-text-btn:hover,
        .login-text-btn:focus-visible,
        .back-choice-btn:hover,
        .back-choice-btn:focus-visible {
            color: var(--j-deep);
            outline: none;
        }

        .divider {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 16px;
            color: #8c9098;
            font-weight: 700;
        }

        .divider::before,
        .divider::after {
            content: "";
            height: 1px;
            background: #e4e7ec;
        }

        .continue-btn {
            color: #555d68;
            background: transparent;
            border: 0;
        }

        .continue-btn:hover,
        .continue-btn:focus-visible {
            color: var(--j-red);
            background: #fff7f8;
            outline: none;
        }

        .terms-copy {
            margin-top: 32px;
            color: #2f3540;
            font-size: .95rem;
            line-height: 1.6;
        }

        .terms-copy a {
            color: var(--j-red);
            font-weight: 900;
            text-decoration: none;
        }

        .auth-form {
            max-width: 560px;
            margin: 0 auto;
            text-align: left;
        }

        .signup-form { max-width: 860px; }

        .signup-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px 18px;
        }

        .signup-section-title {
            grid-column: 1 / -1;
            color: var(--j-deep);
            font-weight: 900;
            margin: 8px 0 0;
            text-align: left;
        }

        .auth-form label {
            color: #2f3540;
            font-weight: 800;
        }

        .auth-form .form-control,
        .auth-form .form-select {
            border: 1px solid #dde2eb;
            border-radius: 10px;
            min-height: 48px;
            font-weight: 600;
        }

        .auth-form .form-control:focus,
        .auth-form .form-select:focus {
            border-color: #f0a4ad;
            box-shadow: 0 0 0 .2rem rgba(223, 0, 27, .12);
        }

        .password-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
        }

        .password-row .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .password-row button {
            border: 1px solid #dde2eb;
            border-left: 0;
            border-radius: 0 10px 10px 0;
            background: #fff;
            color: var(--j-red);
            font-weight: 900;
            padding: 0 14px;
        }

        .form-hint {
            color: var(--j-muted);
            font-size: .86rem;
            margin-top: 6px;
        }

        .form-footer-actions {
            grid-column: 1 / -1;
            display: grid;
            gap: 12px;
            margin-top: 6px;
        }

        @media (max-width: 700px) {
            .account-shell { padding: 14px; }
            .account-modal {
                max-height: calc(100vh - 28px);
                padding: 46px 20px 28px;
            }
            .modal-close-link { top: 14px; right: 14px; }
            .account-logo { margin-top: 8px; height: 72px; }
            .signup-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>
    <iframe class="home-background-frame" src="index.php" title="JolliBeep home background" tabindex="-1" aria-hidden="true"></iframe>
    <div class="home-background-dim" aria-hidden="true"></div>

    <main class="account-shell">
        <section class="account-modal <?= $initialPanel === 'register' ? 'register-mode' : ($initialPanel === 'login' ? 'login-mode' : ''); ?>" id="accountModal" aria-labelledby="accountTitle">
            <a class="modal-close-link" href="index.php" aria-label="Close account prompt">&times;</a>
            <img src="jollibeeplg.png" alt="JolliBeep" class="account-logo">

            <?php if ($isLoggedIn): ?>
                <div class="auth-panel active">
                    <h1 id="accountTitle">Hi, <?= htmlspecialchars($displayName); ?></h1>
                    <p>You are already signed in. Continue ordering, review your profile, or log out when you are done.</p>
                    <div class="account-actions">
                        <a class="register-btn" href="page5_jollibee_orderform.php">Continue Ordering</a>
                        <a class="continue-btn" href="page8_profile.php">View Profile</a>
                        <div class="divider">or</div>
                        <a class="continue-btn" href="logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="auth-panel <?= $initialPanel === 'choice' ? 'active' : ''; ?>" data-auth-panel="choice">
                    <h1 id="accountTitle">Create an account</h1>
                    <p>Creating an account and logging in unlocks JolliBeep features to make ordering faster and more convenient.</p>

                    <?php if ($error): ?>
                        <div class="login-alert"><?= htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <div class="account-actions">
                        <button type="button" class="register-btn" data-show-panel="register">Register</button>
                        <button type="button" class="login-text-btn" data-show-panel="login">Login</button>
                        <div class="divider">or</div>
                        <a href="index.php?guest=1" class="continue-btn">Continue as Guest</a>
                    </div>

                    <div class="terms-copy">
                        By continuing, you agree to our
                        <a href="page10_about.php">Terms &amp; Conditions</a>
                        and
                        <a href="page10_about.php">Privacy Notice</a>.
                    </div>
                </div>

                <div class="auth-panel <?= $initialPanel === 'login' ? 'active' : ''; ?>" data-auth-panel="login">
                    <h1>Welcome back</h1>
                    <p>Use your account to continue ordering, save your profile, and track receipts.</p>

                    <?php if ($error): ?>
                        <div class="login-alert"><?= htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form action="page4_login_result.php" method="post" class="auth-form">
                        <div class="mb-3">
                            <label for="login_username" class="form-label">Username</label>
                            <input type="text" id="login_username" name="username" class="form-control form-control-lg" autocomplete="username" required />
                        </div>
                        <div class="mb-4">
                            <label for="login_password" class="form-label">Password</label>
                            <div class="password-row">
                                <input type="password" id="login_password" name="password" class="form-control form-control-lg" autocomplete="current-password" required />
                                <button type="button" data-toggle-password="login_password">Show</button>
                            </div>
                        </div>
                        <div class="account-actions">
                            <button type="submit" class="submit-login-btn">Log In</button>
                            <button type="button" class="back-choice-btn" data-show-panel="choice">Back to account options</button>
                            <button type="button" class="login-text-btn" data-show-panel="register">Create Account</button>
                        </div>
                    </form>
                </div>

                <div class="auth-panel <?= $initialPanel === 'register' ? 'active' : ''; ?>" data-auth-panel="register">
                    <h1>Create Your JolliBeep Account</h1>
                    <p>Save your details once, then order faster next time.</p>

                    <form action="page2_registration_result.php" method="POST" class="auth-form signup-form" id="registerForm" novalidate>
                        <div class="signup-grid">
                            <div>
                                <label for="reg_name" class="form-label">Full Name</label>
                                <input type="text" id="reg_name" name="name" class="form-control" required />
                                <div class="invalid-feedback">Please enter your full name.</div>
                            </div>
                            <div>
                                <label for="reg_username" class="form-label">Username</label>
                                <input type="text" id="reg_username" name="username" class="form-control" autocomplete="username" required />
                                <div class="invalid-feedback">Username is required.</div>
                                <div id="username-status" class="form-hint"></div>
                            </div>
                            <div>
                                <label for="reg_email" class="form-label">Email</label>
                                <input type="email" id="reg_email" name="email" class="form-control" autocomplete="email" required />
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                            <div>
                                <label for="reg_mobile" class="form-label">Mobile Number</label>
                                <input type="text" id="reg_mobile" name="mobile" class="form-control" pattern="[0-9]{11}" title="Enter 11-digit mobile number" required />
                                <div class="invalid-feedback">Enter an 11-digit mobile number.</div>
                            </div>

                            <div class="signup-section-title">Saved Address</div>
                            <div>
                                <label for="region" class="form-label">Region</label>
                                <select id="region" name="region" class="form-select" required>
                                    <option value="">Select Region</option>
                                </select>
                                <div class="invalid-feedback">Please select your region.</div>
                            </div>
                            <div>
                                <label for="province" class="form-label">Province</label>
                                <select id="province" name="province" class="form-select" required>
                                    <option value="">Select Province</option>
                                </select>
                                <div class="invalid-feedback">Please select your province.</div>
                            </div>
                            <div>
                                <label for="city" class="form-label">City/Municipality</label>
                                <select id="city" name="city" class="form-select" required>
                                    <option value="">Select City</option>
                                </select>
                                <div class="invalid-feedback">Please select your city.</div>
                            </div>
                            <div>
                                <label for="barangay" class="form-label">Barangay</label>
                                <select id="barangay" name="barangay" class="form-select" required>
                                    <option value="">Select Barangay</option>
                                </select>
                                <div class="invalid-feedback">Please select your barangay.</div>
                            </div>

                            <div>
                                <label for="reg_gender" class="form-label">Gender</label>
                                <select id="reg_gender" name="gender" class="form-select" required>
                                    <option value="">Select your gender</option>
                                    <option>Male</option>
                                    <option>Female</option>
                                    <option>Other</option>
                                    <option>Prefer not to say</option>
                                </select>
                                <div class="invalid-feedback">Please select your gender.</div>
                            </div>
                            <div>
                                <label for="reg_dob" class="form-label">Date of Birth</label>
                                <input type="date" id="reg_dob" name="dob" class="form-control" required />
                                <div class="invalid-feedback">Please select your date of birth.</div>
                            </div>
                            <div class="signup-section-title">Security</div>
                            <div>
                                <label for="reg_password" class="form-label">Password</label>
                                <div class="password-row">
                                    <input type="password" id="reg_password" name="password" class="form-control" autocomplete="new-password" required />
                                    <button type="button" data-toggle-password="reg_password">Show</button>
                                </div>
                                <div class="form-hint">Must be at least 8 characters, include at least 1 number and 1 special character.</div>
                                <div class="invalid-feedback">Password must meet the requirements above.</div>
                            </div>
                            <div class="form-footer-actions">
                                <button type="submit" class="submit-login-btn">Submit Registration</button>
                                <button type="button" class="back-choice-btn" data-show-panel="choice">Back to account options</button>
                                <button type="button" class="login-text-btn" data-show-panel="login">Have an account? Login here</button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php if (!$isLoggedIn): ?>
        <script>
            const accountModal = document.getElementById('accountModal');
            const authPanels = [...document.querySelectorAll('[data-auth-panel]')];

            function showPanel(panelName) {
                authPanels.forEach(panel => panel.classList.toggle('active', panel.dataset.authPanel === panelName));
                accountModal.classList.toggle('register-mode', panelName === 'register');
                accountModal.classList.toggle('login-mode', panelName === 'login');
                accountModal.scrollTo({ top: 0, behavior: 'smooth' });
                window.setTimeout(() => {
                    const focusTarget = panelName === 'login'
                        ? document.getElementById('login_username')
                        : panelName === 'register'
                            ? document.getElementById('reg_name')
                            : document.querySelector('[data-show-panel="register"]');
                    focusTarget?.focus();
                }, 180);
            }

            document.querySelectorAll('[data-show-panel]').forEach(button => {
                button.addEventListener('click', () => showPanel(button.dataset.showPanel));
            });

            document.querySelectorAll('[data-toggle-password]').forEach(button => {
                button.addEventListener('click', () => {
                    const input = document.getElementById(button.dataset.togglePassword);
                    if (!input) return;
                    const showing = input.type === 'text';
                    input.type = showing ? 'password' : 'text';
                    button.textContent = showing ? 'Show' : 'Hide';
                });
            });

            const registerForm = document.getElementById('registerForm');
            const regPassword = document.getElementById('reg_password');
            const regUsername = document.getElementById('reg_username');
            const usernameStatus = document.getElementById('username-status');
            let usernameCheckTimeout;

            function setValidity(field, valid, message = '') {
                field.classList.toggle('is-valid', valid);
                field.classList.toggle('is-invalid', !valid);
                const feedback = field.closest('div')?.querySelector('.invalid-feedback');
                if (feedback && message) feedback.textContent = message;
            }

            regPassword?.addEventListener('input', () => {
                const val = regPassword.value;
                let msg = '';
                if (val.length < 8) msg += 'At least 8 characters. ';
                if (!/\d/.test(val)) msg += 'At least 1 number. ';
                if (!/[\W_]/.test(val)) msg += 'At least 1 special character. ';
                setValidity(regPassword, /^(?=.*\d)(?=.*[\W_]).{8,}$/.test(val), msg || 'Password must meet the requirements above.');
            });

            regUsername?.addEventListener('input', () => {
                const username = regUsername.value.trim();
                clearTimeout(usernameCheckTimeout);
                usernameStatus.textContent = '';
                if (!username) return;
                usernameCheckTimeout = setTimeout(() => {
                    fetch(`check_username.php?username=${encodeURIComponent(username)}`)
                        .then(response => response.text())
                        .then(result => {
                            const taken = result.toLowerCase().includes('taken') || result.toLowerCase().includes('exists');
                            usernameStatus.textContent = result;
                            usernameStatus.style.color = taken ? '#df001b' : '#138a36';
                        })
                        .catch(() => {
                            usernameStatus.textContent = 'Could not check username right now.';
                            usernameStatus.style.color = '#df001b';
                        });
                }, 350);
            });

            registerForm?.addEventListener('submit', event => {
                let valid = true;
                registerForm.querySelectorAll('[required]').forEach(field => {
                    const fieldValid = field.checkValidity();
                    field.classList.toggle('is-invalid', !fieldValid);
                    field.classList.toggle('is-valid', fieldValid);
                    if (!fieldValid) valid = false;
                });
                if (!/^(?=.*\d)(?=.*[\W_]).{8,}$/.test(regPassword.value)) {
                    setValidity(regPassword, false, 'Password must have at least 8 characters, 1 number, and 1 special character.');
                    valid = false;
                }
                if (!valid) {
                    event.preventDefault();
                    registerForm.querySelector('.is-invalid')?.focus();
                }
            });

            let regions = [], provinces = [], cities = [], barangays = [];
            function populate(id, data, valueKey, textKey) {
                const select = document.getElementById(id);
                if (!select) return;
                const label = select.querySelector('option')?.textContent || 'Select';
                select.innerHTML = `<option value="">${label}</option>`;
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item[valueKey];
                    option.textContent = item[textKey];
                    select.appendChild(option);
                });
            }
            function reset(id) {
                const select = document.getElementById(id);
                if (!select) return;
                const labels = { province: 'Select Province', city: 'Select City', barangay: 'Select Barangay' };
                select.innerHTML = `<option value="">${labels[id] || 'Select'}</option>`;
            }

            Promise.all([
                fetch('addresses/region.json').then(r => r.json()),
                fetch('addresses/province.json').then(r => r.json()),
                fetch('addresses/city.json').then(r => r.json()),
                fetch('addresses/barangay.json').then(r => r.json())
            ]).then(([rData, pData, cData, bData]) => {
                regions = rData; provinces = pData; cities = cData; barangays = bData;
                populate('region', regions, 'region_code', 'region_name');
            });

            document.getElementById('region')?.addEventListener('change', event => {
                reset('province'); reset('city'); reset('barangay');
                populate('province', provinces.filter(p => p.region_code === event.target.value), 'province_code', 'province_name');
            });
            document.getElementById('province')?.addEventListener('change', event => {
                reset('city'); reset('barangay');
                populate('city', cities.filter(c => c.province_code === event.target.value), 'city_code', 'city_name');
            });
            document.getElementById('city')?.addEventListener('change', event => {
                reset('barangay');
                populate('barangay', barangays.filter(b => b.city_code === event.target.value), 'brgy_code', 'brgy_name');
            });
        </script>
    <?php endif; ?>
</body>

</html>