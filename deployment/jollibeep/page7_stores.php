<?php
session_start();
require_once 'stores_data.php';
require_once 'db_connection.php';

$isGuest = empty($_SESSION['username']);
$registeredCity = trim((string) ($_SESSION['registered_city'] ?? ''));
if (!$registeredCity && !empty($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT city FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $registeredCity = trim((string) ($row['city'] ?? ''));
    }
    $stmt->close();
}

function cityNameFromCode($value)
{
    $value = (string) $value;
    if ($value === '') {
        return '';
    }

    $path = __DIR__ . DIRECTORY_SEPARATOR . 'addresses' . DIRECTORY_SEPARATOR . 'city.json';
    if (!is_file($path)) {
        return $value;
    }

    $citiesData = json_decode(file_get_contents($path), true);
    if (!is_array($citiesData)) {
        return $value;
    }

    foreach ($citiesData as $city) {
        if ((string) ($city['city_code'] ?? '') === $value) {
            return (string) ($city['city_name'] ?? $value);
        }
    }

    return $value;
}

$registeredCityLabel = cityNameFromCode($registeredCity);
if ($registeredCityLabel && !in_array($registeredCityLabel, array_column($STORES, 'city'), true)) {
    foreach (array_column($STORES, 'city') as $storeCity) {
        if (stripos($registeredCityLabel, $storeCity) !== false || stripos($storeCity, $registeredCityLabel) !== false) {
            $registeredCityLabel = $storeCity;
            break;
        }
    }
}

$cities = array_values(array_unique(array_column($STORES, 'city')));
sort($cities);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>JolliBeep Stores</title>
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

        .stores-shell {
            max-width: 1220px;
            margin: 0 auto;
            padding: 0 18px 48px;
        }

        .store-tabs {
            border-bottom: 1px solid #ececec;
            margin: 0 -18px 28px;
            padding: 20px max(18px, calc((100vw - 1220px) / 2 + 18px));
            display: flex;
            gap: 34px;
            background: #fff;
        }

        .store-tab {
            color: #222b38;
            text-decoration: none;
            font-weight: 900;
            font-size: 1.08rem;
            padding: 10px 0;
        }

        .store-tab.active {
            color: var(--j-red);
            border-bottom: 4px solid var(--j-red);
        }

        .stores-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 240px;
            gap: 22px;
            align-items: center;
            background: linear-gradient(135deg, #df001b, #b40016);
            color: #fff;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 24px;
            box-shadow: 0 16px 34px rgba(97, 0, 11, .16);
        }

        .stores-hero h1 {
            font-family: 'Chewy', cursive;
            font-size: clamp(2.4rem, 5vw, 4.5rem);
            margin: 0;
        }

        .stores-hero p {
            margin: 8px 0 0;
            max-width: 640px;
            font-weight: 700;
        }

        .stores-hero img {
            width: 100%;
            max-height: 180px;
            object-fit: contain;
            filter: drop-shadow(0 14px 22px rgba(76, 0, 8, .22));
        }

        .store-finder {
            background: #fff;
            border: 1px solid var(--j-line);
            border-radius: 12px;
            padding: 18px;
            display: grid;
            grid-template-columns: 1.3fr .8fr auto;
            gap: 12px;
            align-items: end;
            margin-bottom: 28px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
        }

        .location-status {
            grid-column: 1 / -1;
            display: none;
            border-radius: 10px;
            padding: 10px 12px;
            font-weight: 800;
            font-size: .92rem;
        }

        .location-status.info {
            display: block;
            background: #fff7e5;
            color: #7a4300;
        }

        .location-status.success {
            display: block;
            background: #eaffef;
            color: #176b2c;
        }

        .location-status.error {
            display: block;
            background: #fff0f1;
            color: #b50016;
        }

        .store-finder label {
            font-weight: 900;
            margin-bottom: 6px;
        }

        .store-finder input,
        .store-finder select {
            width: 100%;
            border: 1px solid #d9dee8;
            border-radius: 10px;
            padding: 12px 14px;
            font: inherit;
        }

        .store-finder button {
            border: 0;
            border-radius: 999px;
            background: var(--j-red);
            color: #fff;
            font-weight: 900;
            padding: 13px 22px;
            white-space: nowrap;
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 18px;
        }

        .section-heading h2 {
            margin: 0;
            font-size: clamp(1.7rem, 3vw, 2.3rem);
            font-weight: 900;
        }

        .store-count {
            color: var(--j-red);
            font-weight: 900;
        }

        .stores-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .store-card {
            border: 1px solid var(--j-line);
            border-radius: 12px;
            padding: 18px;
            background: #fff;
            display: grid;
            gap: 12px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .05);
        }

        .store-status {
            width: max-content;
            border-radius: 999px;
            background: #eaffef;
            color: #176b2c;
            padding: 5px 10px;
            font-weight: 900;
            font-size: .8rem;
        }

        .store-card h3 {
            margin: 0;
            color: var(--j-deep);
            font-size: 1.25rem;
            font-weight: 900;
        }

        .store-card p {
            margin: 0;
            color: var(--j-muted);
            line-height: 1.45;
        }

        .service-list {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .service-pill {
            border: 1px solid #ffd0d5;
            background: #fff7f8;
            color: var(--j-red);
            border-radius: 999px;
            padding: 5px 9px;
            font-size: .78rem;
            font-weight: 800;
        }

        .store-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 4px;
        }

        .store-actions a,
        .store-actions button {
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            font-weight: 900;
            text-decoration: none;
        }

        .order-store {
            background: var(--j-red);
            color: #fff;
            border: 0;
        }

        .details-store {
            background: #fff;
            color: var(--j-red);
            border: 1px solid #f0a4ad;
        }

        .empty-state {
            display: none;
            border: 1px dashed #d9dee8;
            border-radius: 12px;
            padding: 28px;
            text-align: center;
            color: var(--j-muted);
            font-weight: 700;
        }

        @media (max-width: 980px) {
            .stores-hero,
            .store-finder {
                grid-template-columns: 1fr;
            }

            .stores-hero img {
                max-width: 180px;
            }

            .stores-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .store-tabs {
                overflow-x: auto;
                margin-bottom: 20px;
            }

            .stores-grid {
                grid-template-columns: 1fr;
            }

            .store-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include 'menu-links.php'; ?>

    <main class="stores-shell">
        <section class="store-tabs" aria-label="Store sections">
            <a class="store-tab" href="page5_jollibee_orderform.php">Menu</a>
            <a class="store-tab active" href="page7_stores.php">Stores</a>
        </section>

        <section class="stores-hero">
            <div>
                <h1>Find a JolliBeep Store</h1>
                <p>Select your area, choose a nearby branch, then order your favorites for pickup or delivery.</p>
            </div>
            <img src="jollibeeplg.png" alt="JolliBeep mascot">
        </section>

        <section class="store-finder" aria-label="Find stores">
            <div>
                <label for="storeSearch">Search branch or address</label>
                <input type="search" id="storeSearch" placeholder="Search branch, street, city, or address..." autocomplete="off">
            </div>
            <div>
                <label for="cityFilter">Select your city</label>
                <select id="cityFilter">
                    <option value="All">All cities</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?= htmlspecialchars($city); ?>"><?= htmlspecialchars($city); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="button" id="nearMeBtn" data-registered-city="<?= htmlspecialchars($registeredCityLabel); ?>">Use My Area</button>
            <div class="location-status" id="locationStatus" role="status" aria-live="polite"></div>
        </section>

        <section>
            <div class="section-heading">
                <h2>Available Stores</h2>
                <span class="store-count" id="storeCount"><?= count($STORES); ?> stores</span>
            </div>

            <div class="stores-grid" id="storesGrid">
                <?php foreach ($STORES as $store): ?>
                    <article class="store-card" data-city="<?= htmlspecialchars($store['city']); ?>" data-search="<?= htmlspecialchars(strtolower($store['name'] . ' ' . $store['city'] . ' ' . $store['address'])); ?>">
                        <span class="store-status"><?= htmlspecialchars($store['status']); ?></span>
                        <h3><?= htmlspecialchars($store['name']); ?></h3>
                        <p><strong><?= htmlspecialchars($store['city']); ?></strong></p>
                        <p><?= htmlspecialchars($store['address']); ?></p>
                        <p><strong>Hours:</strong> <?= htmlspecialchars($store['hours']); ?></p>
                        <div class="service-list">
                            <?php foreach ($store['services'] as $service): ?>
                                <span class="service-pill"><?= htmlspecialchars($service); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <div class="store-actions">
                            <a class="order-store" href="page5_jollibee_orderform.php" <?= $isGuest ? 'data-requires-login' : ''; ?>>Order Here</a>
                            <button class="details-store" type="button" data-store="<?= htmlspecialchars($store['name']); ?>">Details</button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="empty-state" id="emptyState">No stores matched your search. Try another city or keyword.</div>
        </section>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        const storeSearch = document.getElementById('storeSearch');
        const cityFilter = document.getElementById('cityFilter');
        const storeCards = [...document.querySelectorAll('.store-card')];
        const storeCount = document.getElementById('storeCount');
        const emptyState = document.getElementById('emptyState');
        const nearMeBtn = document.getElementById('nearMeBtn');
        const locationStatus = document.getElementById('locationStatus');
        const registeredCity = nearMeBtn.dataset.registeredCity;

        const cityCoordinates = {
            'Manila': { lat: 14.5995, lng: 120.9842 },
            'Makati': { lat: 14.5547, lng: 121.0244 },
            'Pasig': { lat: 14.5764, lng: 121.0851 },
            'Quezon City': { lat: 14.6760, lng: 121.0437 },
            'Taguig': { lat: 14.5176, lng: 121.0509 },
        };

        function setLocationStatus(type, message) {
            locationStatus.className = `location-status ${type}`;
            locationStatus.textContent = message;
        }

        function distanceSquared(a, b) {
            return Math.pow(a.lat - b.lat, 2) + Math.pow(a.lng - b.lng, 2);
        }

        function nearestDemoCity(position) {
            const current = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };

            return Object.entries(cityCoordinates)
                .sort((left, right) => distanceSquared(current, left[1]) - distanceSquared(current, right[1]))[0][0];
        }

        function filterStores() {
            const query = storeSearch.value.trim().toLowerCase();
            const city = cityFilter.value;
            let visible = 0;

            storeCards.forEach(card => {
                const matchesCity = city === 'All' || card.dataset.city === city;
                const matchesQuery = !query || card.dataset.search.includes(query);
                const show = matchesCity && matchesQuery;
                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            storeCount.textContent = `${visible} ${visible === 1 ? 'store' : 'stores'}`;
            emptyState.style.display = visible ? 'none' : 'block';
        }

        storeSearch.addEventListener('input', filterStores);
        cityFilter.addEventListener('change', filterStores);

        nearMeBtn.addEventListener('click', () => {
            if (registeredCity && [...cityFilter.options].some(option => option.value === registeredCity)) {
                cityFilter.value = registeredCity;
                storeSearch.value = '';
                filterStores();
                setLocationStatus('success', `Using your registered account area: ${registeredCity}.`);
                return;
            }

            if (!navigator.geolocation) {
                setLocationStatus('error', 'No registered city matched our stores, and browser location is unavailable. Please select your city manually.');
                return;
            }

            nearMeBtn.disabled = true;
            nearMeBtn.textContent = 'Allow Location...';
            setLocationStatus('info', 'No matching registered city found. Your browser will ask permission to access your location.');

            navigator.geolocation.getCurrentPosition(
                position => {
                    const city = nearestDemoCity(position);
                    cityFilter.value = city;
                    storeSearch.value = '';
                    filterStores();
                    setLocationStatus('success', `Location access allowed. Showing stores near ${city}.`);
                    nearMeBtn.disabled = false;
                    nearMeBtn.textContent = 'Use My Area';
                },
                error => {
                    const denied = error.code === error.PERMISSION_DENIED;
                    setLocationStatus('error', denied
                        ? 'Location permission was denied. Please enable location access or choose a city manually.'
                        : 'Could not get your location right now. Please select your city manually.');
                    nearMeBtn.disabled = false;
                    nearMeBtn.textContent = 'Use My Area';
                },
                {
                    enableHighAccuracy: true,
                    timeout: 8000,
                    maximumAge: 300000
                }
            );
        });

        document.querySelectorAll('.details-store').forEach(button => {
            button.addEventListener('click', () => {
                alert(`${button.dataset.store} is open for selected services. Choose "Order Here" to start your cart.`);
            });
        });
    </script>
</body>

</html>
