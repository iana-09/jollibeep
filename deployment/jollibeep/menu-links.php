<?php
$navDisplayName = '';
$isCustomerLoggedIn = !empty($_SESSION['username']);
if (!empty($_SESSION['customer_name'])) {
    $navDisplayName = $_SESSION['customer_name'];
} elseif (!empty($_SESSION['username'])) {
    $navDisplayName = $_SESSION['username'];
}

if (!function_exists('navAddressCityName')) {
    function navAddressCityName($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $path = __DIR__ . DIRECTORY_SEPARATOR . 'addresses' . DIRECTORY_SEPARATOR . 'city.json';
        if (!is_file($path)) {
            return $value;
        }

        $cities = json_decode((string) file_get_contents($path), true);
        if (!is_array($cities)) {
            return $value;
        }

        foreach ($cities as $city) {
            if ((string) ($city['city_code'] ?? '') === $value || strcasecmp((string) ($city['city_name'] ?? ''), $value) === 0) {
                return (string) ($city['city_name'] ?? $value);
            }
        }

        return $value;
    }
}

$navSavedAddress = navAddressCityName($_SESSION['registered_city'] ?? '');
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=Chewy&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    body {
        padding-top: 78px;
        padding-bottom: 64px;
        margin: 0;
    }

    .navbar-custom {
        background: #df001b;
        box-shadow: 0 3px 12px rgba(99, 20, 20, .12);
        z-index: 1030;
        font-family: 'Poppins', sans-serif;
        min-height: 76px;
        padding-top: .45rem;
        padding-bottom: .45rem;
    }

    .navbar-brand {
        color: #fff !important;
        font-family: 'Chewy', cursive;
        font-size: 1.65rem;
        gap: 8px;
        line-height: 1;
    }

    .navbar-brand img {
        height: 48px;
        width: auto;
        background: #fff;
        border-radius: 10px;
        padding: 4px;
    }

    .address-picker-wrap {
        position: relative;
        margin-left: 28px;
    }

    .address-link {
        color: #fff;
        text-decoration: none;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: .55rem .9rem;
        border-radius: 12px;
        border: 0;
        background: transparent;
        max-width: 330px;
    }

    .address-link:hover,
    .address-link:focus,
    .address-link.active {
        background: rgba(255, 255, 255, .15);
        color: #fff;
    }

    .address-text {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .address-pin {
        width: 28px;
        height: 28px;
        display: inline-grid;
        place-items: center;
        background: #ff8a00;
        border-radius: 50%;
        color: #fff;
        flex: 0 0 auto;
    }

    .address-pin::before {
        content: "";
        width: 10px;
        height: 10px;
        border: 3px solid #fff;
        border-radius: 50%;
    }

    .address-chevron {
        width: 11px;
        height: 11px;
        border-right: 3px solid currentColor;
        border-bottom: 3px solid currentColor;
        transform: rotate(45deg);
        transition: transform .18s ease;
        flex: 0 0 auto;
    }

    .address-link.active .address-chevron {
        transform: rotate(225deg);
        margin-top: 6px;
    }

    .address-panel {
        position: fixed;
        top: 76px;
        left: clamp(12px, 13vw, 270px);
        width: min(920px, calc(100vw - 24px));
        background: #fff;
        border-radius: 0 0 14px 14px;
        padding: 30px;
        box-shadow: 0 22px 44px rgba(17, 24, 39, .24);
        z-index: 1042;
        display: none;
        grid-template-columns: minmax(0, 1fr) 76px;
        gap: 18px;
        align-items: center;
    }

    .address-panel.open {
        display: grid;
    }

    .address-search-box {
        border: 2px solid #ff4b62;
        border-radius: 12px;
        min-height: 68px;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 12px;
        padding: 0 18px;
        box-shadow: 0 0 0 8px #f6f6f6;
    }

    .address-search-box input {
        border: 0;
        outline: 0;
        min-width: 0;
        font: inherit;
        font-size: 1.15rem;
        font-weight: 600;
        color: #222b38;
    }

    .locate-me-btn {
        border: 0;
        background: transparent;
        color: #df001b;
        font-weight: 900;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        white-space: nowrap;
        padding: 8px 4px;
    }

    .locate-icon {
        width: 28px;
        height: 28px;
        border: 3px solid #df001b;
        border-radius: 50%;
        position: relative;
    }

    .locate-icon::before,
    .locate-icon::after {
        content: "";
        position: absolute;
        background: #df001b;
    }

    .locate-icon::before {
        width: 38px;
        height: 3px;
        left: -8px;
        top: 10px;
    }

    .locate-icon::after {
        width: 3px;
        height: 38px;
        left: 10px;
        top: -8px;
    }

    .address-submit {
        width: 76px;
        height: 76px;
        border: 0;
        border-radius: 50%;
        background: #df001b;
        color: #fff;
        font-size: 2.1rem;
        line-height: 1;
        font-weight: 900;
        display: inline-grid;
        place-items: center;
    }

    .address-status {
        grid-column: 1 / -1;
        color: #667085;
        font-weight: 700;
        font-size: .9rem;
        min-height: 1.3em;
    }

    .primary-nav-links {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        margin-left: 18px;
    }

    .navbar-custom .nav-link {
        color: #fff;
        font-weight: 800;
        border-radius: 8px;
        padding: .55rem .75rem;
        line-height: 1.1;
    }

    .navbar-custom .nav-link:hover,
    .navbar-custom .nav-link:focus {
        background: #ffd33d;
        color: #751013;
    }

    .nav-user-pill {
        background: rgba(255, 255, 255, .15);
        border: 1px solid rgba(255, 255, 255, .35);
        max-width: 220px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .order-now-link {
        background: #fff;
        color: #df001b !important;
        border-radius: 999px !important;
        width: 54px;
        height: 46px;
        padding: 0 !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 20px rgba(97, 0, 11, .18);
    }

    .order-now-link svg {
        width: 26px;
        height: 26px;
        stroke-width: 2.5;
    }

    .nav-cart-wrap {
        position: relative;
    }

    .cart-count-badge {
        position: absolute;
        top: -7px;
        right: -7px;
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        display: inline-grid;
        place-items: center;
        border-radius: 999px;
        background: #ffd33d;
        color: #751013;
        border: 2px solid #df001b;
        font-size: .72rem;
        font-weight: 900;
    }

    .cart-preview {
        position: fixed;
        top: 86px;
        right: 18px;
        width: min(360px, calc(100vw - 24px));
        max-height: calc(100vh - 108px);
        background: #fff;
        color: #1f2937;
        border: 1px solid #f1c9c9;
        border-radius: 10px;
        box-shadow: 0 18px 42px rgba(97, 0, 11, .18);
        z-index: 1045;
        overflow: hidden;
        opacity: 0;
        transform: translateY(-8px);
        pointer-events: none;
        transition: opacity .18s ease, transform .18s ease;
        font-family: 'Poppins', sans-serif;
    }

    .nav-cart-wrap:hover .cart-preview,
    .nav-cart-wrap:focus-within .cart-preview,
    .cart-preview.open {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }

    .cart-preview-header {
        padding: 14px 16px;
        background: #df001b;
        color: #fff;
    }

    .cart-preview-header strong {
        display: block;
        font-size: 1rem;
        font-weight: 900;
    }

    .cart-preview-header span {
        font-size: .82rem;
        font-weight: 800;
        color: #ffe1e5;
    }

    .cart-preview-body {
        padding: 14px 16px;
        display: grid;
        gap: 10px;
        max-height: min(280px, calc(100vh - 300px));
        overflow: auto;
    }

    .cart-preview-empty {
        border: 1px dashed #f1b5b9;
        border-radius: 8px;
        padding: 16px;
        color: #7b5656;
        text-align: center;
        font-weight: 700;
        background: #fffafa;
    }

    .cart-preview-item {
        display: grid;
        grid-template-columns: 54px 1fr;
        gap: 10px;
        align-items: center;
        border-bottom: 1px solid #f4dddd;
        padding-bottom: 10px;
    }

    .cart-preview-item img {
        width: 54px;
        height: 54px;
        object-fit: contain;
        border: 1px solid #f3dddd;
        border-radius: 8px;
        background: #fff;
        padding: 4px;
    }

    .cart-preview-item strong {
        display: block;
        color: #8f1017;
        font-size: .88rem;
        line-height: 1.25;
    }

    .cart-preview-item span {
        display: block;
        color: #667085;
        font-size: .78rem;
        font-weight: 700;
        margin-top: 2px;
    }

    .cart-preview-footer {
        border-top: 1px solid #f1d2d2;
        padding: 14px 16px;
        display: grid;
        gap: 10px;
        background: #fffafa;
    }

    .cart-preview-total {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        font-weight: 900;
        color: #8f1017;
    }

    .cart-preview-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .cart-preview-actions a {
        border-radius: 8px;
        min-height: 42px;
        font-weight: 900;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #f1b5b9;
        background: #fff;
        color: #df001b;
    }

    .cart-preview-actions .checkout-mini {
        background: #df001b;
        border-color: #df001b;
        color: #fff;
    }

    .guest-auth-overlay {
        position: fixed;
        inset: 0;
        background: rgba(17, 24, 39, .62);
        z-index: 2000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 18px;
    }

    .guest-auth-overlay.open {
        display: flex;
    }

    .guest-auth-modal {
        width: min(100%, 580px);
        background: #fff;
        border-radius: 14px;
        padding: 34px;
        text-align: center;
        box-shadow: 0 24px 60px rgba(0, 0, 0, .24);
        position: relative;
    }

    .guest-auth-close {
        position: absolute;
        top: 16px;
        right: 16px;
        width: 38px;
        height: 38px;
        border: 0;
        border-radius: 50%;
        background: #f6f7f9;
        color: #667085;
        font-size: 1.6rem;
        line-height: 1;
    }

    .guest-auth-logo {
        width: 138px;
        margin: 10px auto 22px;
        display: block;
    }

    .guest-auth-modal h2 {
        color: #222b38;
        font-weight: 900;
        font-size: 2rem;
        margin-bottom: 12px;
    }

    .guest-auth-modal p {
        color: #667085;
        font-weight: 600;
        line-height: 1.55;
        max-width: 440px;
        margin: 0 auto 24px;
    }

    .guest-auth-actions {
        display: grid;
        gap: 12px;
        max-width: 460px;
        margin: 0 auto;
    }

    .guest-auth-register,
    .guest-auth-login,
    .guest-auth-continue {
        min-height: 52px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        text-decoration: none;
    }

    .guest-auth-register {
        background: #df001b;
        color: #fff;
    }

    .guest-auth-login {
        color: #df001b;
        background: #fff;
        border: 1px solid #f1b5b9;
    }

    .guest-auth-divider {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        gap: 12px;
        color: #98a2b3;
        font-weight: 800;
        margin: 4px 0;
    }

    .guest-auth-divider::before,
    .guest-auth-divider::after {
        content: "";
        height: 1px;
        background: #e5e7eb;
    }

    .guest-auth-continue {
        border: 0;
        background: transparent;
        color: #667085;
        font-size: 1rem;
    }

    .navbar-toggler {
        border-color: rgba(255, 255, 255, .45);
    }

    .navbar-toggler-icon {
        filter: brightness(0) invert(1);
    }

    @media (max-width: 991.98px) {
        .navbar-custom .navbar-collapse {
            padding-top: .6rem;
        }

        .address-picker-wrap {
            margin-left: 0;
            width: 100%;
        }

        .address-link {
            margin-top: .5rem;
            width: 100%;
            justify-content: flex-start;
        }

        .primary-nav-links {
            display: block;
            margin-left: 0;
        }

        .navbar-custom .nav-link {
            padding: .7rem .8rem;
        }

        .navbar-nav {
            align-items: flex-start;
        }

        .nav-cart-wrap {
            width: 100%;
        }

        .order-now-link {
            margin-top: .35rem;
        }

        .nav-cart-wrap .cart-preview {
            position: static;
            display: none;
            width: 100%;
            max-height: 62vh;
            margin-top: 12px;
            opacity: 1;
            transform: none;
            pointer-events: auto;
            box-shadow: 0 14px 28px rgba(97, 0, 11, .18);
        }

        .nav-cart-wrap .cart-preview.open {
            display: block;
        }

        .cart-preview-actions {
            grid-template-columns: 1fr 1fr;
        }

        .address-panel {
            left: 12px;
            top: 74px;
            grid-template-columns: 1fr;
            padding: 18px;
        }

        .address-search-box {
            grid-template-columns: 1fr;
            padding: 14px;
        }

        .address-submit {
            width: 100%;
            height: 52px;
            border-radius: 999px;
        }
    }

    @media (max-width: 575.98px) {
        .cart-preview {
            top: 82px;
            right: 12px;
            max-height: calc(100vh - 104px);
        }

        .cart-preview-actions {
            grid-template-columns: 1fr;
        }
    }

    .beepy-widget {
        position: fixed;
        right: 22px;
        bottom: 82px;
        z-index: 1070;
        font-family: 'Poppins', sans-serif;
    }

    .beepy-launcher {
        position: relative;
        width: 68px;
        height: 68px;
        border: 0;
        border-radius: 50%;
        background: #df001b;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 18px 36px rgba(143, 16, 23, .24);
        cursor: pointer;
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .beepy-launcher:hover,
    .beepy-launcher:focus-visible {
        transform: translateY(-2px);
        box-shadow: 0 22px 42px rgba(143, 16, 23, .32);
        outline: none;
    }

    .beepy-launcher img {
        width: 46px;
        height: 46px;
        object-fit: contain;
        border-radius: 50%;
        background: #fff;
        padding: 4px;
    }

    .beepy-launcher-badge {
        position: absolute;
        top: -4px;
        right: -2px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #ffd43b;
        color: #8f1017;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .9rem;
        font-weight: 900;
        border: 2px solid #fff;
    }

    .beepy-panel {
        position: fixed;
        right: 22px;
        bottom: 162px;
        width: min(390px, calc(100vw - 28px));
        max-height: min(640px, calc(100vh - 188px));
        background: #fff;
        border: 1px solid #f0caca;
        border-radius: 18px;
        box-shadow: 0 24px 60px rgba(37, 17, 17, .2);
        overflow: hidden;
        display: none;
        flex-direction: column;
    }

    .beepy-panel.open {
        display: flex;
    }

    .beepy-header {
        background: #df001b;
        color: #fff;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .beepy-avatar {
        width: 42px;
        height: 42px;
        object-fit: contain;
        border-radius: 50%;
        background: #fff;
        padding: 4px;
        flex: 0 0 auto;
    }

    .beepy-title {
        flex: 1;
        line-height: 1.2;
    }

    .beepy-title strong {
        display: block;
        font-size: 1.05rem;
        font-weight: 900;
    }

    .beepy-title span {
        display: block;
        font-size: .78rem;
        font-weight: 700;
        opacity: .9;
    }

    .beepy-close {
        width: 38px;
        height: 38px;
        border: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, .14);
        color: #fff;
        font-size: 1.45rem;
        line-height: 1;
        cursor: pointer;
    }

    .beepy-messages {
        min-height: 220px;
        padding: 16px;
        overflow-y: auto;
        display: grid;
        align-content: start;
        gap: 10px;
        background: linear-gradient(180deg, #fff 0%, #fff7f7 100%);
    }

    .beepy-message {
        max-width: 88%;
        border-radius: 16px;
        padding: 10px 12px;
        font-size: .94rem;
        line-height: 1.45;
        box-shadow: 0 8px 16px rgba(16, 24, 40, .06);
    }

    .beepy-message.beepy {
        justify-self: start;
        background: #f4f6f8;
        color: #2b3038;
        border-bottom-left-radius: 5px;
    }

    .beepy-message.user {
        justify-self: end;
        background: #df001b;
        color: #fff;
        border-bottom-right-radius: 5px;
    }

    .beepy-quick-replies {
        padding: 0 16px 12px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        background: #fff7f7;
    }

    .beepy-chip {
        border: 1px solid #f0a7ae;
        border-radius: 999px;
        background: #fff;
        color: #df001b;
        padding: 8px 10px;
        font-size: .82rem;
        font-weight: 900;
        cursor: pointer;
    }

    .beepy-chip:hover,
    .beepy-chip:focus-visible {
        background: #df001b;
        color: #fff;
        outline: none;
    }

    .beepy-input-row {
        border-top: 1px solid #f1dada;
        padding: 12px;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 8px;
        background: #fff;
    }

    .beepy-input {
        min-width: 0;
        border: 1px solid #d6dce7;
        border-radius: 999px;
        padding: 10px 14px;
        font: inherit;
    }

    .beepy-send {
        border: 0;
        border-radius: 999px;
        background: #df001b;
        color: #fff;
        padding: 10px 16px;
        font-weight: 900;
        cursor: pointer;
    }

    @media (max-width: 575.98px) {
        .beepy-widget {
            right: 14px;
            bottom: 76px;
        }

        .beepy-panel {
            left: 12px;
            right: 12px;
            bottom: 150px;
            width: auto;
            max-height: calc(100vh - 166px);
        }
    }
</style>

<nav class="navbar navbar-expand-lg navbar-custom fixed-top" aria-label="Main navigation">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand d-flex align-items-center" href="index.php" aria-label="JolliBeep Home">
            <img src="jollibeeplg.png" alt="JolliBeep logo" />
            JolliBeep
        </a>

        <div class="address-picker-wrap d-none d-lg-inline-flex">
            <button type="button" class="address-link" id="addressPickerToggle" aria-expanded="false" aria-controls="addressPickerPanel">
                <span class="address-pin" aria-hidden="true"></span>
                <span class="address-text" id="addressPickerText"><?= htmlspecialchars($navSavedAddress ?: 'Select your address'); ?></span>
                <span class="address-chevron" aria-hidden="true"></span>
            </button>
            <div class="address-panel" id="addressPickerPanel" aria-hidden="true">
                <div class="address-search-box">
                    <input type="search" id="addressSearchInput" placeholder="Search for an address" value="<?= htmlspecialchars($navSavedAddress); ?>" autocomplete="street-address">
                    <button type="button" class="locate-me-btn" id="locateMeBtn">
                        Locate Me
                        <span class="locate-icon" aria-hidden="true"></span>
                    </button>
                </div>
                <button type="button" class="address-submit" id="addressSubmitBtn" aria-label="Use this address">›</button>
                <div class="address-status" id="addressPickerStatus"></div>
            </div>
        </div>

        <div class="primary-nav-links d-none d-lg-inline-flex">
            <a class="nav-link" href="index.php">Home</a>
            <a class="nav-link" href="page5_jollibee_orderform.php">Menu</a>
            <a class="nav-link" href="page10_about.php">About JolliBeep</a>
        </div>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
            aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
            <ul class="navbar-nav mb-2 mb-lg-0 gap-lg-1">
                <li class="nav-item d-lg-none">
                    <div class="address-picker-wrap">
                        <button type="button" class="address-link" id="addressPickerToggleMobile" aria-expanded="false" aria-controls="addressPickerPanel">
                            <span class="address-pin" aria-hidden="true"></span>
                            <span class="address-text" id="addressPickerTextMobile"><?= htmlspecialchars($navSavedAddress ?: 'Select your address'); ?></span>
                            <span class="address-chevron" aria-hidden="true"></span>
                        </button>
                    </div>
                </li>
                <li class="nav-item d-lg-none"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item d-lg-none"><a class="nav-link" href="page5_jollibee_orderform.php">Menu</a></li>
                <li class="nav-item d-lg-none"><a class="nav-link" href="page10_about.php">About JolliBeep</a></li>
                <li class="nav-item">
                    <?php if ($navDisplayName): ?>
                        <a class="nav-link nav-user-pill" href="page8_profile.php">Hi, <?= htmlspecialchars($navDisplayName); ?></a>
                    <?php else: ?>
                        <a class="nav-link" href="login.php">Register / Log in</a>
                    <?php endif; ?>
                </li>
                <li class="nav-item"><a class="nav-link" href="page7_stores.php">Stores</a></li>
                <li class="nav-item">
                    <div class="nav-cart-wrap">
                        <a class="nav-link order-now-link" href="page5_jollibee_orderform.php" aria-label="Open cart preview" id="navCartButton" aria-expanded="false">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <circle cx="9" cy="20" r="1.8"></circle>
                                <circle cx="18" cy="20" r="1.8"></circle>
                                <path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L21 8H7"></path>
                            </svg>
                            <span class="cart-count-badge" id="navCartCount">0</span>
                        </a>
                        <div class="cart-preview" id="navCartPreview" aria-live="polite">
                            <div class="cart-preview-header">
                                <strong>Your Cart</strong>
                                <span id="navCartSummary">No items yet</span>
                            </div>
                            <div class="cart-preview-body" id="navCartItems">
                                <div class="cart-preview-empty">Your cart preview will appear here.</div>
                            </div>
                            <div class="cart-preview-footer">
                                <div class="cart-preview-total">
                                    <span>Subtotal</span>
                                    <span id="navCartSubtotal">PHP 0.00</span>
                                </div>
                                <div class="cart-preview-actions">
                                    <a href="page5_jollibee_orderform.php">Edit Cart</a>
                                    <a class="checkout-mini" href="page5_jollibee_orderform.php#checkoutForm">Review</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="guest-auth-overlay" id="guestAuthOverlay" aria-hidden="true">
    <div class="guest-auth-modal" role="dialog" aria-modal="true" aria-labelledby="guestAuthTitle">
        <button type="button" class="guest-auth-close" data-close-guest-auth aria-label="Close account prompt">&times;</button>
        <img src="jollibeeplg.png" alt="JolliBeep" class="guest-auth-logo">
        <h2 id="guestAuthTitle">Create an account</h2>
        <p>Creating an account or logging in lets you add items to cart, choose a store, and complete pickup or delivery orders.</p>
        <div class="guest-auth-actions">
            <a href="login.php?mode=register" class="guest-auth-register">Register</a>
            <a href="login.php" class="guest-auth-login">Login</a>
            <div class="guest-auth-divider">or</div>
            <button type="button" class="guest-auth-continue" data-close-guest-auth>Continue as Guest</button>
        </div>
    </div>
</div>

<div class="beepy-widget">
    <section class="beepy-panel" id="beepyPanel" aria-hidden="true" aria-label="Beepy chat assistant">
        <header class="beepy-header">
            <img src="jollibeeplg.png" alt="" class="beepy-avatar">
            <div class="beepy-title">
                <strong>Beepy</strong>
                <span>JolliBeep assistant</span>
            </div>
            <button type="button" class="beepy-close" id="beepyClose" aria-label="Close Beepy chat">&times;</button>
        </header>
        <div class="beepy-messages" id="beepyMessages" aria-live="polite"></div>
        <div class="beepy-quick-replies" id="beepyQuickReplies">
            <button type="button" class="beepy-chip" data-beepy-prompt="How do I order?">How to order</button>
            <button type="button" class="beepy-chip" data-beepy-prompt="How can I track my order?">Track order</button>
            <button type="button" class="beepy-chip" data-beepy-prompt="Find stores near me">Find stores</button>
            <button type="button" class="beepy-chip" data-beepy-prompt="Login or sign up help">Account help</button>
        </div>
        <form class="beepy-input-row" id="beepyForm" autocomplete="off">
            <input class="beepy-input" id="beepyInput" type="text" placeholder="Type a message..." aria-label="Message Beepy">
            <button class="beepy-send" type="submit">Send</button>
        </form>
    </section>
    <button type="button" class="beepy-launcher" id="beepyLauncher" aria-controls="beepyPanel" aria-expanded="false" aria-label="Open Beepy chat">
        <img src="jollibeeplg.png" alt="">
        <span class="beepy-launcher-badge">?</span>
    </button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.JOLLIBEEP_LOGGED_IN = <?= $isCustomerLoggedIn ? 'true' : 'false'; ?>;

    function showGuestAuthModal() {
        const overlay = document.getElementById('guestAuthOverlay');
        if (!overlay) return;
        overlay.classList.add('open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeGuestAuthModal() {
        const overlay = document.getElementById('guestAuthOverlay');
        if (!overlay) return;
        overlay.classList.remove('open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('[data-close-guest-auth]').forEach(button => {
        button.addEventListener('click', closeGuestAuthModal);
    });

    document.getElementById('guestAuthOverlay')?.addEventListener('click', event => {
        if (event.target.id === 'guestAuthOverlay') closeGuestAuthModal();
    });

    document.addEventListener('click', event => {
        const lockedAction = event.target.closest('[data-requires-login]');
        if (!lockedAction || window.JOLLIBEEP_LOGGED_IN) return;
        event.preventDefault();
        showGuestAuthModal();
    });

    const ADDRESS_KEY = 'jollibeep_selected_address';
    const addressPanel = document.getElementById('addressPickerPanel');
    const addressInput = document.getElementById('addressSearchInput');
    const addressStatus = document.getElementById('addressPickerStatus');
    const addressTexts = [
        document.getElementById('addressPickerText'),
        document.getElementById('addressPickerTextMobile')
    ].filter(Boolean);
    const addressToggles = [
        document.getElementById('addressPickerToggle'),
        document.getElementById('addressPickerToggleMobile')
    ].filter(Boolean);

    function setAddressText(value) {
        const label = value?.trim() || 'Select your address';
        addressTexts.forEach(text => text.textContent = label);
        if (value?.trim()) localStorage.setItem(ADDRESS_KEY, value.trim());
        window.dispatchEvent(new CustomEvent('jollibeep-address-updated', { detail: { address: value?.trim() || '' } }));
    }

    function toggleAddressPanel(forceOpen) {
        const open = typeof forceOpen === 'boolean' ? forceOpen : !addressPanel?.classList.contains('open');
        addressPanel?.classList.toggle('open', open);
        addressPanel?.setAttribute('aria-hidden', open ? 'false' : 'true');
        addressToggles.forEach(toggle => {
            toggle.classList.toggle('active', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        if (open) setTimeout(() => addressInput?.focus(), 40);
    }

    addressToggles.forEach(toggle => {
        toggle.addEventListener('click', () => toggleAddressPanel());
    });

    document.getElementById('addressSubmitBtn')?.addEventListener('click', () => {
        const value = addressInput?.value.trim();
        if (!value) {
            addressStatus.textContent = 'Please type an address or use Locate Me.';
            return;
        }
        setAddressText(value);
        addressStatus.textContent = `Using address: ${value}`;
        setTimeout(() => toggleAddressPanel(false), 450);
    });

    addressInput?.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            document.getElementById('addressSubmitBtn')?.click();
        }
    });

    document.getElementById('locateMeBtn')?.addEventListener('click', () => {
        if (!navigator.geolocation) {
            addressStatus.textContent = 'Location is not available in this browser. Please type your address.';
            return;
        }

        addressStatus.textContent = 'Requesting location permission...';
        navigator.geolocation.getCurrentPosition(
            position => {
                const value = `Current location (${position.coords.latitude.toFixed(4)}, ${position.coords.longitude.toFixed(4)})`;
                if (addressInput) addressInput.value = value;
                setAddressText(value);
                addressStatus.textContent = 'Location detected. You can continue ordering.';
            },
            error => {
                addressStatus.textContent = error.code === error.PERMISSION_DENIED
                    ? 'Location permission denied. Please type your address.'
                    : 'Could not detect your location. Please type your address.';
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
        );
    });

    document.addEventListener('click', event => {
        if (!addressPanel?.classList.contains('open')) return;
        if (event.target.closest('.address-picker-wrap') || event.target.closest('#addressPickerPanel')) return;
        toggleAddressPanel(false);
    });

    const savedAddress = localStorage.getItem(ADDRESS_KEY);
    if (savedAddress && !<?= json_encode((bool) $navSavedAddress); ?>) {
        if (addressInput) addressInput.value = savedAddress;
        setAddressText(savedAddress);
    }

    const NAV_CART_KEY = 'jollibeep_cart';
    const navCartButton = document.getElementById('navCartButton');
    const navCartPreview = document.getElementById('navCartPreview');
    const navCartCount = document.getElementById('navCartCount');
    const navCartSummary = document.getElementById('navCartSummary');
    const navCartItems = document.getElementById('navCartItems');
    const navCartSubtotal = document.getElementById('navCartSubtotal');
    const pesoFormatter = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });

    function setCartPreviewOpen(open) {
        navCartPreview?.classList.toggle('open', open);
        navCartButton?.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    navCartButton?.addEventListener('click', event => {
        event.preventDefault();
        setCartPreviewOpen(!navCartPreview?.classList.contains('open'));
    });

    document.addEventListener('click', event => {
        if (!navCartPreview?.classList.contains('open')) return;
        if (event.target.closest('.nav-cart-wrap')) return;
        setCartPreviewOpen(false);
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') setCartPreviewOpen(false);
    });

    function readNavCart() {
        try {
            const cart = JSON.parse(localStorage.getItem(NAV_CART_KEY) || '[]');
            return Array.isArray(cart) ? cart : [];
        } catch {
            return [];
        }
    }

    function renderNavCart() {
        const cart = readNavCart();
        const count = cart.reduce((sum, item) => sum + (Number(item.quantity) || 0), 0);
        const subtotal = cart.reduce((sum, item) => sum + (Number(item.price) || 0) * (Number(item.quantity) || 0), 0);

        if (navCartCount) navCartCount.textContent = count;
        if (navCartSummary) navCartSummary.textContent = count ? `${count} ${count === 1 ? 'item' : 'items'} ready` : 'No items yet';
        if (navCartSubtotal) navCartSubtotal.textContent = pesoFormatter.format(subtotal);

        if (!navCartItems) return;
        if (!cart.length) {
            navCartItems.innerHTML = '<div class="cart-preview-empty">Your cart preview will appear here.</div>';
            return;
        }

        navCartItems.innerHTML = cart.slice(0, 4).map(item => `
            <div class="cart-preview-item">
                <img src="${item.image || 'jollibeeplg.png'}" alt="">
                <div>
                    <strong>${item.name || 'Menu item'}</strong>
                    <span>${item.variant || 'Solo'} x ${item.quantity || 1} · ${pesoFormatter.format((Number(item.price) || 0) * (Number(item.quantity) || 1))}</span>
                </div>
            </div>
        `).join('') + (cart.length > 4 ? `<div class="cart-preview-empty">+${cart.length - 4} more item(s)</div>` : '');
    }

    renderNavCart();
    window.addEventListener('storage', renderNavCart);
    window.addEventListener('jollibeep-cart-updated', renderNavCart);

    const beepyLauncher = document.getElementById('beepyLauncher');
    const beepyPanel = document.getElementById('beepyPanel');
    const beepyClose = document.getElementById('beepyClose');
    const beepyMessages = document.getElementById('beepyMessages');
    const beepyForm = document.getElementById('beepyForm');
    const beepyInput = document.getElementById('beepyInput');
    const beepyQuickReplies = document.getElementById('beepyQuickReplies');
    let beepyStarted = false;

    function addBeepyMessage(text, sender = 'beepy') {
        if (!beepyMessages) return;
        const bubble = document.createElement('div');
        bubble.className = `beepy-message ${sender}`;
        bubble.textContent = text;
        beepyMessages.appendChild(bubble);
        beepyMessages.scrollTop = beepyMessages.scrollHeight;
    }

    function getBeepyReply(message) {
        const text = message.toLowerCase();
        if (text.includes('order') || text.includes('menu') || text.includes('add to cart')) {
            return 'To order, choose your address, open Menu, preview an item, add it to cart, review your order, then press Place Order.';
        }
        if (text.includes('track') || text.includes('status') || text.includes('preparing') || text.includes('pickup') || text.includes('rider')) {
            return 'After placing an order, your receipt page shows the live flow: Preparing Food, Ready for Pickup, Rider Picking Up, Rider On The Way, then Delivered.';
        }
        if (text.includes('store') || text.includes('branch') || text.includes('near') || text.includes('address') || text.includes('location')) {
            return 'Use Select your address or the Stores page. JolliBeep suggests serving stores based on your saved city and shows distance when available.';
        }
        if (text.includes('login') || text.includes('sign') || text.includes('account') || text.includes('register')) {
            return 'Use Register / Log in at the top. Guests can browse, but creating an account unlocks cart checkout, saved address, profile, and order tracking.';
        }
        if (text.includes('profile') || text.includes('password') || text.includes('mobile') || text.includes('edit')) {
            return 'Open your profile from the Hi button. You can edit your saved details and hide or show sensitive fields like password and mobile number.';
        }
        if (text.includes('manager') || text.includes('controller') || text.includes('admin')) {
            return 'The controller side is private. A controller logs in to view orders by store, update statuses, assign riders, and schedule pickup or delivery.';
        }
        if (text.includes('cart') || text.includes('review') || text.includes('checkout')) {
            return 'Click the cart icon to preview items, then choose Review to confirm add-ons, serving store, pickup or delivery, payment, and rider notes.';
        }
        return 'I can help with ordering, cart review, stores, account/profile, delivery status, or controller-side order management. Try typing one of those topics.';
    }

    function openBeepyPanel() {
        if (!beepyPanel) return;
        beepyPanel.classList.add('open');
        beepyPanel.setAttribute('aria-hidden', 'false');
        beepyLauncher?.setAttribute('aria-expanded', 'true');
        if (!beepyStarted) {
            addBeepyMessage("Hi, I'm Beepy!");
            addBeepyMessage('How can I help you use JolliBeep today?');
            beepyStarted = true;
        }
        window.setTimeout(() => beepyInput?.focus(), 50);
    }

    function closeBeepyPanel() {
        beepyPanel?.classList.remove('open');
        beepyPanel?.setAttribute('aria-hidden', 'true');
        beepyLauncher?.setAttribute('aria-expanded', 'false');
    }

    function submitBeepyMessage(message) {
        const cleanMessage = message.trim();
        if (!cleanMessage) return;
        addBeepyMessage(cleanMessage, 'user');
        window.setTimeout(() => addBeepyMessage(getBeepyReply(cleanMessage)), 180);
    }

    beepyLauncher?.addEventListener('click', () => {
        if (beepyPanel?.classList.contains('open')) {
            closeBeepyPanel();
        } else {
            openBeepyPanel();
        }
    });

    beepyClose?.addEventListener('click', closeBeepyPanel);

    beepyForm?.addEventListener('submit', event => {
        event.preventDefault();
        submitBeepyMessage(beepyInput?.value || '');
        if (beepyInput) beepyInput.value = '';
    });

    beepyQuickReplies?.addEventListener('click', event => {
        const button = event.target.closest('[data-beepy-prompt]');
        if (!button) return;
        submitBeepyMessage(button.dataset.beepyPrompt || button.textContent || '');
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeBeepyPanel();
    });
</script>
