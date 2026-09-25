<?php
session_start();
require_once 'menu_data.php';

$featuredItems = array_slice($MENU_ITEMS, 0, 6, true);
$categories = array_values(array_unique(array_column($MENU_ITEMS, 'category')));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>JolliBeep Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Chewy&family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --j-red: #df001b;
            --j-deep: #9b1117;
            --j-yellow: #ffd33d;
            --j-ink: #111827;
            --j-muted: #667085;
            --j-line: #e7eaf0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #fff;
            color: var(--j-ink);
        }

        .home-shell {
            max-width: 1240px;
            margin: 0 auto;
            padding: 34px 18px 52px;
        }

        .home-hero {
            min-height: 360px;
            border-radius: 14px;
            background: linear-gradient(135deg, #df001b, #b40016);
            color: #fff;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 28px;
            align-items: center;
            padding: clamp(28px, 5vw, 52px);
            box-shadow: 0 18px 44px rgba(97, 0, 11, .18);
        }

        .home-hero h1 {
            font-family: 'Chewy', cursive;
            font-size: clamp(3.1rem, 8vw, 6.2rem);
            margin: 0;
            line-height: .9;
        }

        .home-hero p {
            max-width: 620px;
            font-size: 1.08rem;
            font-weight: 700;
            margin: 18px 0 0;
        }

        .home-hero img {
            width: 100%;
            max-height: 270px;
            object-fit: contain;
            filter: drop-shadow(0 18px 26px rgba(76, 0, 8, .25));
        }

        .hero-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 26px;
        }

        .btn-home-primary,
        .btn-home-secondary,
        .btn-home-login,
        .btn-home-register {
            border-radius: 999px;
            padding: 13px 22px;
            font-weight: 900;
            text-decoration: none;
        }

        .btn-home-primary {
            background: #fff;
            color: var(--j-red);
        }

        .btn-home-secondary {
            border: 1px solid rgba(255, 255, 255, .55);
            color: #fff;
        }

        .btn-home-login {
            background: var(--j-yellow);
            color: #8f1017;
            border: 0;
            box-shadow: 0 10px 20px rgba(97, 0, 11, .16);
        }

        .btn-home-register {
            background: transparent;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, .72);
        }

        .btn-home-primary:hover,
        .btn-home-login:hover {
            color: var(--j-red);
            background: #fff7d0;
        }

        .btn-home-secondary:hover,
        .btn-home-register:hover {
            color: #8f1017;
            background: #fff;
            border-color: #fff;
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin: 42px 0 18px;
        }

        .section-heading h2 {
            font-size: clamp(1.8rem, 3vw, 2.4rem);
            font-weight: 900;
            margin: 0;
        }

        .category-row {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            padding-bottom: 6px;
        }

        .category-pill {
            flex: 0 0 auto;
            background: #fff7f8;
            border: 1px solid #ffd0d5;
            color: var(--j-red);
            border-radius: 999px;
            padding: 10px 14px;
            font-weight: 900;
            text-decoration: none;
        }

        .featured-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .featured-card {
            border: 1px solid var(--j-line);
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .05);
            display: block;
            color: inherit;
            text-decoration: none;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .featured-card:hover,
        .featured-card:focus {
            color: inherit;
            border-color: #f0a4ad;
            box-shadow: 0 16px 34px rgba(151, 17, 23, .14);
            transform: translateY(-3px);
        }

        .featured-card img {
            width: 100%;
            height: 190px;
            object-fit: contain;
            padding: 18px;
            border-bottom: 1px solid #f0f2f5;
        }

        .featured-body {
            padding: 16px;
        }

        .featured-body h3 {
            color: var(--j-deep);
            font-size: 1.05rem;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .featured-body p {
            color: var(--j-muted);
            margin: 0;
            font-size: .92rem;
        }

        @media (max-width: 820px) {
            .home-hero,
            .featured-grid {
                grid-template-columns: 1fr;
            }

            .home-hero img {
                max-width: 220px;
            }
        }
    </style>
</head>

<body>
    <?php include 'menu-links.php'; ?>

    <main class="home-shell">
        <section class="home-hero">
            <div>
                <h1>Bida ang saya sa JolliBeep</h1>
                <p>Order your crispy favorites, find nearby stores, and keep your account ready for faster checkout.</p>
                <div class="hero-actions">
                    <a class="btn-home-primary" href="page5_jollibee_orderform.php">Order Now</a>
                    <a class="btn-home-secondary" href="page7_stores.php">Find Stores</a>
                    <a class="btn-home-login" href="login.php">Login</a>
                    <a class="btn-home-register" href="login.php?mode=register">Register</a>
                </div>
            </div>
            <img src="jollibeeplg.png" alt="JolliBeep mascot">
        </section>

        <section>
            <div class="section-heading">
                <h2>Explore Menu</h2>
                <a class="category-pill" href="page5_jollibee_orderform.php">View All</a>
            </div>
            <div class="category-row">
                <?php foreach ($categories as $category): ?>
                    <a class="category-pill" href="page5_jollibee_orderform.php?category=<?= urlencode($category); ?>"><?= htmlspecialchars($category); ?></a>
                <?php endforeach; ?>
            </div>
        </section>

        <section>
            <div class="section-heading">
                <h2>Popular Picks</h2>
            </div>
            <div class="featured-grid">
                <?php foreach ($featuredItems as $code => $item): ?>
                    <a class="featured-card" href="page5_jollibee_orderform.php?category=<?= urlencode($item['category']); ?>&item=<?= urlencode($code); ?>" aria-label="View <?= htmlspecialchars($item['name']); ?> in the menu">
                        <img src="<?= htmlspecialchars($item['image']); ?>" alt="<?= htmlspecialchars($item['name']); ?>">
                        <div class="featured-body">
                            <h3><?= htmlspecialchars($item['name']); ?></h3>
                            <p><?= htmlspecialchars($item['description']); ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <?php include 'footer.php'; ?>
</body>

</html>
