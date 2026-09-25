<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>About JolliBeep</title>
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

        .about-shell {
            max-width: 1180px;
            margin: 0 auto;
            padding: 34px 18px 52px;
        }

        .about-hero {
            min-height: 340px;
            border-radius: 14px;
            background: linear-gradient(135deg, #df001b, #b40016);
            color: #fff;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 280px;
            gap: 28px;
            align-items: center;
            padding: clamp(28px, 5vw, 52px);
            box-shadow: 0 18px 44px rgba(97, 0, 11, .18);
        }

        .about-hero h1 {
            font-family: 'Chewy', cursive;
            font-size: clamp(3rem, 8vw, 6rem);
            line-height: .92;
            margin: 0;
        }

        .about-hero p {
            max-width: 650px;
            margin: 18px 0 0;
            font-size: 1.08rem;
            font-weight: 700;
        }

        .about-hero img {
            width: 100%;
            max-height: 250px;
            object-fit: contain;
            filter: drop-shadow(0 18px 26px rgba(76, 0, 8, .25));
        }

        .about-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-top: 26px;
        }

        .about-card {
            border: 1px solid var(--j-line);
            border-radius: 12px;
            background: #fff;
            padding: 22px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .05);
        }

        .about-card h2 {
            color: var(--j-deep);
            font-size: 1.25rem;
            font-weight: 900;
            margin-bottom: 10px;
        }

        .about-card p {
            color: var(--j-muted);
            line-height: 1.6;
            margin: 0;
        }

        .about-band {
            margin-top: 26px;
            border-radius: 12px;
            background: #fff7f8;
            border: 1px solid #ffd0d5;
            padding: 26px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 18px;
            align-items: center;
        }

        .about-band h2 {
            color: var(--j-deep);
            font-weight: 900;
            margin: 0 0 8px;
        }

        .about-band p {
            color: var(--j-muted);
            margin: 0;
        }

        .btn-about {
            background: var(--j-red);
            color: #fff;
            border-radius: 999px;
            padding: 13px 22px;
            font-weight: 900;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-about:hover {
            background: var(--j-deep);
            color: #fff;
        }

        @media (max-width: 820px) {
            .about-hero,
            .about-grid,
            .about-band {
                grid-template-columns: 1fr;
            }

            .about-hero img {
                max-width: 210px;
            }
        }
    </style>
</head>

<body>
    <?php include 'menu-links.php'; ?>

    <main class="about-shell">
        <section class="about-hero">
            <div>
                <h1>About JolliBeep</h1>
                <p>JolliBeep is a Jollibee-themed ordering system made for faster menu browsing, simple store finding, and easier checkout.</p>
            </div>
            <img src="jollibeeplg.png" alt="JolliBeep mascot">
        </section>

        <section class="about-grid">
            <article class="about-card">
                <h2>Fast Ordering</h2>
                <p>Browse categories, preview items, customize extras, and build your cart before reviewing your receipt.</p>
            </article>
            <article class="about-card">
                <h2>Store Finder</h2>
                <p>Use your saved profile area or browser location to find nearby JolliBeep stores for pickup or delivery.</p>
            </article>
            <article class="about-card">
                <h2>Account Ready</h2>
                <p>Your profile keeps your name, contact number, and saved address ready for smoother ordering next time.</p>
            </article>
        </section>

        <section class="about-band">
            <div>
                <h2>Bida ang saya, bilis ang order.</h2>
                <p>Start with the menu, choose your store, and enjoy a cleaner ordering flow.</p>
            </div>
            <a class="btn-about" href="page5_jollibee_orderform.php">Order Now</a>
        </section>
    </main>

    <?php include 'footer.php'; ?>
</body>

</html>
