<?php
session_start();
require_once 'menu_data.php';
require_once 'stores_data.php';
require_once 'db_connection.php';

$isGuest = empty($_SESSION['username']);
$customerAddress = [
    'region' => '',
    'province' => '',
    'city' => '',
    'barangay' => '',
];

function orderAddressLabel($fileName, $codeKey, $nameKey, $value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $path = __DIR__ . DIRECTORY_SEPARATOR . 'addresses' . DIRECTORY_SEPARATOR . $fileName;
    if (!is_file($path)) {
        return $value;
    }

    $items = json_decode((string) file_get_contents($path), true);
    if (!is_array($items)) {
        return $value;
    }

    foreach ($items as $item) {
        if ((string) ($item[$codeKey] ?? '') === $value || strcasecmp((string) ($item[$nameKey] ?? ''), $value) === 0) {
            return (string) ($item[$nameKey] ?? $value);
        }
    }

    return $value;
}

if (!$isGuest && !empty($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT region, province, city, barangay FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $customerAddress = [
                'region' => orderAddressLabel('region.json', 'region_code', 'region_name', $row['region'] ?? ''),
                'province' => orderAddressLabel('province.json', 'province_code', 'province_name', $row['province'] ?? ''),
                'city' => orderAddressLabel('city.json', 'city_code', 'city_name', $row['city'] ?? ''),
                'barangay' => orderAddressLabel('barangay.json', 'brgy_code', 'brgy_name', $row['barangay'] ?? ''),
            ];
            $_SESSION['registered_city'] = $customerAddress['city'];
        }
        $stmt->close();
    }
}

if ($customerAddress['city'] === '' && !empty($_SESSION['registered_city'])) {
    $customerAddress['city'] = orderAddressLabel('city.json', 'city_code', 'city_name', $_SESSION['registered_city']);
}

function orderNormalize($value)
{
    $value = strtolower((string) $value);
    $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
    return trim((string) $value);
}

function cityCoordinates()
{
    return [
        'manila' => [14.5995, 120.9842],
        'makati' => [14.5547, 121.0244],
        'pasig' => [14.5764, 121.0851],
        'quezon city' => [14.6760, 121.0437],
        'taguig' => [14.5176, 121.0509],
        'mandaluyong' => [14.5794, 121.0359],
        'marikina' => [14.6507, 121.1029],
        'paranaque' => [14.4793, 121.0198],
        'pasay' => [14.5378, 121.0014],
        'las pinas' => [14.4445, 120.9939],
        'muntinlupa' => [14.4081, 121.0415],
        'caloocan' => [14.6507, 120.9668],
        'valenzuela' => [14.7011, 120.9830],
        'malabon' => [14.6680, 120.9567],
        'navotas' => [14.6667, 120.9417],
        'san juan' => [14.6042, 121.0292],
        'bacoor' => [14.4590, 120.9290],
        'imus' => [14.4297, 120.9367],
        'dasmarinas' => [14.3294, 120.9367],
        'carmona' => [14.3132, 121.0576],
        'general trias' => [14.3214, 120.9073],
        'tagaytay' => [14.1153, 120.9621],
        'trece martires' => [14.2800, 120.8667],
        'silang' => [14.2306, 120.9750],
        'kawit' => [14.4443, 120.9016],
        'naic' => [14.3200, 120.7667],
        'tanza' => [14.3944, 120.8531],
        'antipolo' => [14.6255, 121.1245],
        'angeles' => [15.1450, 120.5887],
        'baguio' => [16.4023, 120.5960],
        'batangas' => [13.7565, 121.0583],
        'cebu' => [10.3157, 123.8854],
        'davao' => [7.1907, 125.4553],
        'iloilo' => [10.7202, 122.5621],
        'bacolod' => [10.6765, 122.9509],
        'naga' => [13.6218, 123.1948],
        'legazpi' => [13.1391, 123.7438],
    ];
}

function distanceKmBetween($fromCity, $toCity)
{
    $coordinates = cityCoordinates();
    $fromKey = orderNormalize($fromCity);
    $toKey = orderNormalize($toCity);

    if (!isset($coordinates[$fromKey], $coordinates[$toKey])) {
        return null;
    }

    [$lat1, $lon1] = $coordinates[$fromKey];
    [$lat2, $lon2] = $coordinates[$toKey];
    $earthRadiusKm = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

    return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

function storeDistanceLabel($distanceKm)
{
    if ($distanceKm === null) {
        return '';
    }

    $distanceKm = max(0.8, (float) $distanceKm);
    return 'about ' . number_format($distanceKm, $distanceKm < 10 ? 1 : 0) . ' km away';
}

function storeSuggestionScore($store, $address)
{
    $storeCity = orderNormalize($store['city'] ?? '');
    $storeText = orderNormalize(($store['name'] ?? '') . ' ' . ($store['city'] ?? '') . ' ' . ($store['address'] ?? ''));
    $score = 0;

    $city = orderNormalize($address['city'] ?? '');
    $province = orderNormalize($address['province'] ?? '');
    $barangay = orderNormalize($address['barangay'] ?? '');

    if ($city !== '') {
        if ($storeCity === $city) {
            $score += 1000;
        } elseif (str_contains($storeText, $city) || str_contains($city, $storeCity)) {
            $score += 650;
        }
    }

    if ($province !== '' && str_contains($storeText, $province)) {
        $score += 300;
    }

    if ($barangay !== '') {
        foreach (array_filter(explode(' ', $barangay)) as $token) {
            if (strlen($token) >= 4 && str_contains($storeText, $token)) {
                $score += 80;
            }
        }
    }

    return $score;
}

$rankedStores = $STORES;
foreach ($rankedStores as $index => &$store) {
    $store['_score'] = storeSuggestionScore($store, $customerAddress);
    $store['_distance_km'] = distanceKmBetween($customerAddress['city'] ?? '', $store['city'] ?? '');
    if (($store['_distance_km'] ?? null) !== null) {
        $store['_score'] += max(0, 120 - (int) round($store['_distance_km'] * 3));
    }
    $store['_distance_label'] = storeDistanceLabel($store['_distance_km'] ?? null);
    $store['_index'] = $index;
}
unset($store);

usort($rankedStores, function ($a, $b) {
    if (($a['_score'] ?? 0) !== ($b['_score'] ?? 0)) {
        return ($b['_score'] ?? 0) <=> ($a['_score'] ?? 0);
    }

    if (($a['_distance_km'] ?? null) !== ($b['_distance_km'] ?? null)) {
        if (($a['_distance_km'] ?? null) === null) {
            return 1;
        }
        if (($b['_distance_km'] ?? null) === null) {
            return -1;
        }
        return ($a['_distance_km'] ?? 0) <=> ($b['_distance_km'] ?? 0);
    }

    return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
});

$suggestedStores = array_slice(array_values(array_filter($rankedStores, fn($store) => ($store['_score'] ?? 0) > 0)), 0, 8);
$suggestedNames = array_flip(array_map(fn($store) => $store['name'], $suggestedStores));
$otherStores = array_values(array_filter($rankedStores, fn($store) => !isset($suggestedNames[$store['name']])));
$addressParts = array_values(array_filter([$customerAddress['barangay'], $customerAddress['city'], $customerAddress['province']]));
$addressSummary = implode(', ', $addressParts);
$CATEGORY_TILES = [];
foreach ($MENU_ITEMS as $item) {
    if (!isset($CATEGORY_TILES[$item['category']])) {
        $CATEGORY_TILES[$item['category']] = $item['image'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>JolliBeep Order</title>
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
            background: #fff;
            color: var(--j-ink);
        }

        .order-shell {
            max-width: 1320px;
            margin: 0 auto;
            padding: 0 18px 96px;
        }

        .store-tabs {
            border-bottom: 1px solid #ececec;
            margin: 0 -18px 28px;
            padding: 20px max(18px, calc((100vw - 1320px) / 2 + 18px));
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

        .category-showcase {
            display: grid;
            grid-template-columns: repeat(6, minmax(130px, 1fr));
            gap: 22px;
            align-items: start;
            margin: 10px 0 80px;
        }

        .category-tile {
            border: 0;
            background: transparent;
            text-align: center;
            color: #1f2937;
            font-weight: 900;
            line-height: 1.25;
            padding: 0;
        }

        .category-tile img {
            width: 100%;
            height: 145px;
            object-fit: contain;
            margin-bottom: 12px;
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            margin-bottom: 22px;
        }

        .section-heading h1 {
            color: #020617;
            font-size: clamp(1.8rem, 3vw, 2.4rem);
            font-weight: 900;
            margin: 0;
        }

        .view-all {
            color: var(--j-red);
            border: 0;
            background: transparent;
            font-weight: 900;
            font-size: 1.05rem;
        }

        .menu-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 380px;
            gap: 24px;
            align-items: start;
        }

        .menu-toolbar,
        .cart-panel {
            background: #fff;
            border: 1px solid #f1d2d2;
            border-radius: 8px;
            box-shadow: 0 10px 26px rgba(99, 20, 20, .08);
            box-sizing: border-box;
        }

        .menu-toolbar {
            padding: 16px;
            margin-bottom: 16px;
            border-color: #e7eaf0;
            box-shadow: none;
        }

        .menu-search-wrap {
            position: relative;
            min-width: min(100%, 320px);
            flex: 1 1 320px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 8px;
        }

        .menu-search-wrap .form-control {
            min-width: 0;
        }

        .menu-search-button {
            border: 0;
            border-radius: 8px;
            background: var(--j-red);
            color: #fff;
            font-weight: 900;
            padding: 0 16px;
            min-height: 42px;
        }

        .menu-search-button:hover,
        .menu-search-button:focus {
            background: var(--j-deep);
        }

        .menu-search-suggestions {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            z-index: 30;
            background: #fff;
            border: 1px solid #f0b4b4;
            border-radius: 10px;
            box-shadow: 0 16px 34px rgba(99, 20, 20, .16);
            overflow: hidden;
            display: none;
        }

        .menu-search-suggestions.open {
            display: block;
        }

        .menu-suggestion {
            width: 100%;
            border: 0;
            background: #fff;
            display: grid;
            grid-template-columns: 48px 1fr;
            gap: 10px;
            align-items: center;
            text-align: left;
            padding: 10px 12px;
            color: #1f2937;
        }

        .menu-suggestion:hover,
        .menu-suggestion:focus,
        .menu-suggestion.active {
            background: #fff4f5;
            outline: 0;
        }

        .menu-suggestion img {
            width: 48px;
            height: 48px;
            object-fit: contain;
        }

        .menu-suggestion strong {
            display: block;
            color: var(--j-deep);
            font-size: .92rem;
            line-height: 1.25;
        }

        .menu-suggestion span {
            color: #667085;
            font-size: .78rem;
            font-weight: 700;
        }

        .menu-no-suggestion {
            padding: 14px;
            color: #7b5656;
            font-weight: 700;
            text-align: center;
        }

        .menu-empty-state {
            display: none;
            border: 1px dashed #f0b4b4;
            border-radius: 10px;
            padding: 24px;
            color: #7b5656;
            font-weight: 800;
            text-align: center;
            background: #fffafa;
            margin-top: 16px;
        }

        .menu-empty-state.show {
            display: block;
        }

        .category-tabs {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 4px;
        }

        .category-tab {
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #1f2937;
            border-radius: 999px;
            padding: 9px 14px;
            font-weight: 700;
            white-space: nowrap;
            transition: background .18s ease, color .18s ease, border-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }

        .category-tab:hover,
        .category-tab:focus-visible {
            border-color: var(--j-red);
            color: var(--j-red);
            box-shadow: 0 0 0 3px rgba(215, 25, 32, .1);
            transform: translateY(-1px);
            outline: none;
        }

        .category-tab.active {
            background: var(--j-red);
            border-color: var(--j-red);
            color: #fff;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .menu-card {
            background: #fff;
            border: 1px solid #e7eaf0;
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 100%;
            box-shadow: none;
            position: relative;
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease, background .2s ease;
        }

        .menu-card::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            pointer-events: none;
            box-shadow: inset 0 0 0 0 rgba(215, 25, 32, 0);
            transition: box-shadow .2s ease;
        }

        .menu-card:hover,
        .menu-card:focus-within {
            border-color: var(--j-red);
            background: linear-gradient(180deg, #fff 0%, #fffafa 100%);
            box-shadow: 0 0 0 3px rgba(215, 25, 32, .12), 0 18px 34px rgba(151, 17, 23, .16);
            transform: translateY(-4px);
        }

        .menu-card:hover::before,
        .menu-card:focus-within::before {
            box-shadow: inset 0 0 0 2px rgba(215, 25, 32, .28);
        }

        .menu-card.menu-card-highlight {
            border-color: var(--j-red);
            box-shadow: 0 0 0 3px rgba(215, 25, 32, .14), 0 16px 34px rgba(151, 17, 23, .14);
        }

        .menu-card img {
            width: 100%;
            height: 220px;
            object-fit: contain;
            background: #fff;
            padding: 22px;
            border-bottom: 1px solid #f0f2f5;
            transition: transform .2s ease, filter .2s ease, border-color .2s ease;
        }

        .menu-card:hover img,
        .menu-card:focus-within img {
            transform: scale(1.04);
            filter: drop-shadow(0 14px 18px rgba(151, 17, 23, .14));
            border-bottom-color: #ffd2d6;
        }

        .menu-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .menu-badge {
            width: max-content;
            max-width: 100%;
            background: #fff4d3;
            color: #7a3300;
            border-radius: 999px;
            padding: 4px 9px;
            font-size: .76rem;
            font-weight: 800;
        }

        .menu-body h3 {
            font-size: 1.12rem;
            font-weight: 800;
            margin: 0;
            color: #111827;
            transition: color .18s ease;
        }

        .menu-card:hover .menu-body h3,
        .menu-card:focus-within .menu-body h3 {
            color: var(--j-deep);
        }

        .menu-body p {
            margin: 0;
            color: #667085;
            font-size: .9rem;
            line-height: 1.35;
        }

        .variant-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
            margin-top: auto;
        }

        .variant-row select,
        .qty-control input,
        .customer-fields input,
        .customer-fields select,
        .customer-fields textarea {
            border: 1px solid #e4b3b3;
            border-radius: 8px;
            padding: 10px 12px;
            font: inherit;
        }

        .qty-control {
            display: grid;
            grid-template-columns: 34px 46px 34px;
            align-items: center;
        }

        .qty-control button {
            height: 40px;
            border: 0;
            background: #ffe5e5;
            color: var(--j-deep);
            font-weight: 900;
        }

        .qty-control input {
            height: 40px;
            text-align: center;
            border-radius: 0;
            border-left: 0;
            border-right: 0;
        }

        .add-btn,
        .preview-btn,
        .checkout-btn {
            border: 0;
            border-radius: 8px;
            background: var(--j-red);
            color: #fff;
            font-weight: 800;
            padding: 11px 16px;
            transition: background .18s ease, color .18s ease, border-color .18s ease, transform .18s ease, box-shadow .18s ease;
        }

        .add-btn:hover,
        .preview-btn:hover,
        .checkout-btn:hover {
            background: var(--j-deep);
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(151, 17, 23, .16);
        }

        .preview-btn {
            background: #fff;
            color: var(--j-red);
            border: 1px solid #f0a4ad;
        }

        .preview-btn:hover {
            background: #fff4f5;
            color: var(--j-red);
            border-color: var(--j-red);
        }

        .card-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .product-modal {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: none;
            background: #fff;
            overflow-y: auto;
        }

        .product-modal.open {
            display: block;
        }

        .product-modal-header {
            height: 86px;
            border-bottom: 1px solid #eceff3;
            display: flex;
            align-items: center;
            gap: 24px;
            padding: 0 clamp(18px, 5vw, 80px);
            background: #fff;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .modal-back {
            border: 0;
            background: transparent;
            font-size: 2rem;
            color: #667085;
            line-height: 1;
        }

        .product-modal-header h2 {
            margin: 0;
            color: #111827;
            font-weight: 900;
            font-size: clamp(1.5rem, 3vw, 2.2rem);
        }

        .product-modal-body {
            display: grid;
            grid-template-columns: minmax(320px, .95fr) minmax(320px, 1fr);
            gap: clamp(28px, 6vw, 80px);
            align-items: start;
            padding: clamp(28px, 6vw, 80px);
        }

        .product-preview-image {
            width: 100%;
            min-height: 480px;
            display: grid;
            place-items: center;
            background: #fff;
        }

        .product-preview-image img {
            width: 100%;
            max-height: 540px;
            object-fit: contain;
        }

        .product-details-panel {
            display: grid;
            gap: 22px;
        }

        .product-title-row {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: start;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 22px;
        }

        .product-title-row h3 {
            margin: 0;
            color: #111827;
            font-size: clamp(1.6rem, 3vw, 2.2rem);
            font-weight: 900;
        }

        .modal-price {
            color: #111827;
            font-size: 1.55rem;
            font-weight: 900;
            white-space: nowrap;
        }

        .option-group {
            border-bottom: 1px solid #f0f2f5;
            padding-bottom: 18px;
        }

        .option-group h4 {
            color: #858b98;
            font-size: 1.1rem;
            font-weight: 900;
            margin: 0 0 4px;
        }

        .option-group p {
            color: #b0b5bf;
            margin: 0 0 14px;
            font-weight: 700;
        }

        .option-list {
            display: grid;
            gap: 12px;
        }

        .option-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            color: #858b98;
            font-weight: 700;
        }

        .option-row input {
            width: 22px;
            height: 22px;
            accent-color: var(--j-red);
            margin-right: 10px;
        }

        .modal-quantity {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
        }

        .modal-quantity-control {
            display: grid;
            grid-template-columns: 44px 58px 44px;
            align-items: center;
        }

        .modal-quantity-control button {
            height: 44px;
            border: 0;
            background: #ffe5e8;
            color: var(--j-red);
            font-weight: 900;
            font-size: 1.25rem;
        }

        .modal-quantity-control input {
            height: 44px;
            border: 1px solid #f0a4ad;
            text-align: center;
            font-weight: 900;
        }

        .modal-add {
            border: 0;
            border-radius: 999px;
            background: var(--j-red);
            color: #fff;
            font-weight: 900;
            padding: 16px 24px;
            font-size: 1.05rem;
        }

        .cart-panel {
            position: sticky;
            top: 98px;
            padding: 18px;
            border-color: #e7eaf0;
            box-shadow: 0 12px 26px rgba(15, 23, 42, .08);
            max-height: calc(100vh - 128px);
            overflow-y: auto;
            overflow-x: hidden;
            overscroll-behavior: contain;
            scrollbar-width: thin;
            scrollbar-color: #f0a4ad #fff5f5;
        }

        .cart-panel::-webkit-scrollbar {
            width: 8px;
        }

        .cart-panel::-webkit-scrollbar-track {
            background: #fff5f5;
            border-radius: 999px;
        }

        .cart-panel::-webkit-scrollbar-thumb {
            background: #f0a4ad;
            border-radius: 999px;
        }

        .cart-panel h2 {
            font-family: 'Chewy', cursive;
            color: var(--j-red);
            font-size: 2rem;
            margin: 0 0 12px;
        }

        .cart-items {
            display: grid;
            gap: 10px;
            max-height: 300px;
            overflow: auto;
            padding-right: 4px;
        }

        .cart-item {
            border: 1px solid #f2d0d0;
            border-radius: 8px;
            padding: 10px;
            background: #fffafa;
        }

        .cart-item strong {
            display: block;
            color: var(--j-deep);
        }

        .cart-meta {
            color: #735050;
            font-size: .86rem;
        }

        .remove-item {
            border: 0;
            background: transparent;
            color: var(--j-red);
            font-weight: 800;
            padding: 0;
        }

        .extras-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            margin: 10px 0 14px;
        }

        .extra-pill {
            border: 1px solid #f2b6b6;
            border-radius: 8px;
            padding: 9px;
            cursor: pointer;
            font-size: .86rem;
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .extra-pill input {
            accent-color: var(--j-red);
        }

        .customer-fields {
            display: grid;
            gap: 10px;
            margin-top: 14px;
            min-width: 0;
            width: 100%;
        }

        .customer-fields input,
        .customer-fields select,
        .customer-fields textarea {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
            display: block;
        }

        .customer-fields textarea {
            resize: vertical;
        }

        .delivery-fields {
            border: 1px dashed #f0a4ad;
            border-radius: 10px;
            background: #fffafa;
            padding: 12px;
            display: grid;
            gap: 10px;
        }

        .delivery-fields[hidden] {
            display: none;
        }

        .delivery-fields__title {
            color: var(--j-deep);
            font-weight: 900;
            margin: 0;
        }

        .delivery-fields__hint {
            color: #745252;
            font-size: .86rem;
            margin: -4px 0 2px;
        }

        .delivery-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .delivery-grid .full {
            grid-column: 1 / -1;
        }

        .store-suggestion {
            border: 1px solid #ffd0d0;
            background: #fff8f8;
            border-radius: 8px;
            padding: 12px;
            display: grid;
            gap: 8px;
        }

        .store-suggestion__eyebrow {
            color: var(--j-red);
            font-weight: 900;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .store-suggestion__title {
            color: var(--j-deep);
            font-weight: 900;
            line-height: 1.25;
        }

        .store-suggestion__meta {
            color: #745252;
            font-size: .86rem;
            line-height: 1.35;
        }

        .suggestion-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .suggestion-chip {
            border: 1px solid #f0a4ad;
            background: #fff;
            color: var(--j-red);
            border-radius: 999px;
            padding: 7px 10px;
            font-weight: 800;
            font-size: .78rem;
        }

        .suggestion-chip:hover {
            background: var(--j-red);
            color: #fff;
        }

        .totals {
            border-top: 1px dashed #e3b5b5;
            margin-top: 14px;
            padding-top: 14px;
            display: grid;
            gap: 6px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .grand-total {
            font-size: 1.35rem;
            font-weight: 900;
            color: var(--j-red);
        }

        .empty-cart {
            border: 1px dashed #e3b5b5;
            border-radius: 8px;
            padding: 18px;
            color: #7b5656;
            text-align: center;
        }

        @media (max-width: 1020px) {
            .menu-layout {
                grid-template-columns: 1fr;
            }

            .category-showcase {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                margin-bottom: 42px;
            }

            .menu-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .cart-panel {
                position: static;
                margin-bottom: 56px;
                max-height: none;
                overflow: visible;
            }
        }

        @media (max-width: 720px) {
            .store-tabs {
                margin-bottom: 20px;
                overflow-x: auto;
            }

            .category-showcase,
            .menu-grid {
                grid-template-columns: 1fr;
            }

            .extras-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .category-tile img {
                height: 120px;
            }

            .cart-panel {
                padding: 16px;
                margin-bottom: 72px;
            }

            .cart-items {
                max-height: none;
            }

            .delivery-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .extras-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include 'menu-links.php'; ?>

    <main class="order-shell">
        <section class="store-tabs" aria-label="Store sections">
            <a class="store-tab active" href="page5_jollibee_orderform.php">Menu</a>
            <a class="store-tab" href="page7_stores.php">Stores</a>
        </section>

        <section class="category-showcase" aria-label="Menu categories">
            <?php foreach ($CATEGORY_TILES as $category => $image): ?>
                <button class="category-tile" type="button" data-category-jump="<?= htmlspecialchars($category); ?>">
                    <img src="<?= htmlspecialchars($image); ?>" alt="<?= htmlspecialchars($category); ?>">
                    <span><?= htmlspecialchars($category); ?></span>
                </button>
            <?php endforeach; ?>
        </section>

        <form method="post" action="page6_order_result.php" id="checkoutForm" class="menu-layout">
            <section>
                <div class="section-heading">
                    <h1>Most Popular</h1>
                    <button class="view-all" type="button" data-category-jump="All">View All</button>
                </div>
                <div class="menu-toolbar">
                    <div class="d-flex flex-column flex-md-row gap-3 justify-content-between">
                        <div class="menu-search-wrap">
                            <input type="search" id="menuSearch" class="form-control" placeholder="Search chicken, burger, spaghetti..." aria-label="Search menu" autocomplete="off">
                            <button type="button" class="menu-search-button" id="menuSearchButton">Search</button>
                            <div class="menu-search-suggestions" id="menuSearchSuggestions" role="listbox" aria-label="Menu search suggestions"></div>
                        </div>
                        <div class="category-tabs" id="categoryTabs">
                            <button class="category-tab active" type="button" data-category="All">All</button>
                            <?php foreach (array_unique(array_column($MENU_ITEMS, 'category')) as $category): ?>
                                <button class="category-tab" type="button" data-category="<?= htmlspecialchars($category); ?>"><?= htmlspecialchars($category); ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="menu-grid" id="menuGrid">
                    <?php foreach ($MENU_ITEMS as $code => $item): ?>
                        <article class="menu-card" data-code="<?= htmlspecialchars($code); ?>" data-category="<?= htmlspecialchars($item['category']); ?>" data-name="<?= htmlspecialchars(strtolower($item['name'])); ?>" data-search="<?= htmlspecialchars(strtolower($item['name'] . ' ' . $item['category'] . ' ' . $item['badge'] . ' ' . $item['description'])); ?>">
                            <img src="<?= htmlspecialchars($item['image']); ?>" alt="<?= htmlspecialchars($item['name']); ?>">
                            <div class="menu-body">
                                <span class="menu-badge"><?= htmlspecialchars($item['badge']); ?></span>
                                <h3><?= htmlspecialchars($item['name']); ?></h3>
                                <p><?= htmlspecialchars($item['description']); ?></p>
                                <div class="variant-row">
                                    <select aria-label="Choose variant">
                                        <?php foreach ($item['prices'] as $variant => $price): ?>
                                            <option value="<?= htmlspecialchars($variant); ?>" data-price="<?= (float) $price; ?>">
                                                <?= htmlspecialchars($variant); ?> - PHP <?= number_format($price, 2); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="qty-control">
                                        <button type="button" data-step="-1" aria-label="Decrease quantity">-</button>
                                        <input type="number" value="1" min="1" max="20" aria-label="Quantity">
                                        <button type="button" data-step="1" aria-label="Increase quantity">+</button>
                                    </div>
                                </div>
                                <div class="card-actions">
                                    <button type="button" class="preview-btn">Preview</button>
                                    <button type="button" class="add-btn" <?= $isGuest ? 'data-requires-login' : ''; ?>>Add to Cart</button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="menu-empty-state" id="menuEmptyState">No menu items matched your search.</div>
            </section>

            <aside class="cart-panel">
                <h2>Your Order</h2>
                <div id="cartItems" class="cart-items">
                    <div class="empty-cart">Your cart is waiting for something crispy.</div>
                </div>

                <label class="form-label fw-bold mt-3">Add-ons</label>
                <div class="extras-list">
                    <?php foreach ($EXTRAS as $extra => $price): ?>
                        <label class="extra-pill">
                            <input type="checkbox" class="extra-check" value="<?= htmlspecialchars($extra); ?>" data-price="<?= (float) $price; ?>">
                            <?= htmlspecialchars($extra); ?><br>
                            <strong>PHP <?= number_format($price, 2); ?></strong>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="customer-fields">
                    <input type="text" name="customer_name" value="<?= htmlspecialchars($_SESSION['customer_name'] ?? $_SESSION['username'] ?? 'Guest'); ?>" placeholder="Customer name" required>
                    <?php if (!empty($suggestedStores)): ?>
                        <div class="store-suggestion" aria-label="Suggested serving stores">
                            <div>
                                <div class="store-suggestion__eyebrow">Suggested near your address</div>
                                <div class="store-suggestion__title"><?= htmlspecialchars($suggestedStores[0]['name']); ?></div>
                                <div class="store-suggestion__meta">
                                    <?= htmlspecialchars($suggestedStores[0]['address']); ?><br>
                                    <?php if (!empty($suggestedStores[0]['_distance_label'])): ?>
                                        <?= htmlspecialchars($suggestedStores[0]['_distance_label']); ?> ·
                                    <?php endif; ?>
                                    Based on <?= htmlspecialchars($addressSummary ?: $suggestedStores[0]['city']); ?>.
                                </div>
                            </div>
                            <div class="suggestion-actions">
                                <?php foreach (array_slice($suggestedStores, 0, 3) as $store): ?>
                                    <button type="button" class="suggestion-chip" data-store-choice="<?= htmlspecialchars($store['name']); ?>">
                                        <?= htmlspecialchars($store['name']); ?><?= !empty($store['_distance_label']) ? ' · ' . htmlspecialchars($store['_distance_label']) : ''; ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php elseif (!$isGuest): ?>
                        <div class="store-suggestion">
                            <div class="store-suggestion__eyebrow">Serving store</div>
                            <div class="store-suggestion__meta">No exact branch matched your saved address yet. You can still choose any available store below.</div>
                        </div>
                    <?php endif; ?>
                    <select name="store_name" id="storeName" required>
                        <option value="">Choose serving store</option>
                        <?php if (!empty($suggestedStores)): ?>
                            <optgroup label="Suggested near <?= htmlspecialchars($customerAddress['city'] ?: 'your address'); ?>">
                                <?php foreach ($suggestedStores as $store): ?>
                                    <option value="<?= htmlspecialchars($store['name']); ?>">
                                        <?= htmlspecialchars($store['name'] . ' - ' . $store['city'] . (!empty($store['_distance_label']) ? ' (' . $store['_distance_label'] . ')' : '')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                        <optgroup label="All stores">
                            <?php foreach ($otherStores as $store): ?>
                                <option value="<?= htmlspecialchars($store['name']); ?>">
                                    <?= htmlspecialchars($store['name'] . ' - ' . $store['city'] . (!empty($store['_distance_label']) ? ' (' . $store['_distance_label'] . ')' : '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                    <select name="order_type" id="orderType" required>
                        <option value="Pickup">Pickup</option>
                        <option value="Delivery">Delivery</option>
                    </select>
                    <div class="delivery-fields" id="deliveryFields" hidden>
                        <p class="delivery-fields__title">Delivery details</p>
                        <p class="delivery-fields__hint">Add the details the rider needs on top of your saved account address.</p>
                        <div class="delivery-grid">
                            <input type="text" name="delivery_block_lot" id="deliveryBlockLot" maxlength="80" placeholder="Block / Lot / Unit / Floor" data-delivery-required>
                            <input type="text" name="delivery_street" id="deliveryStreet" maxlength="100" placeholder="Street / Subdivision" data-delivery-required>
                            <input class="full" type="text" name="delivery_barangay" id="deliveryBarangay" maxlength="120" value="<?= htmlspecialchars($addressSummary); ?>" placeholder="Barangay, city, province" data-delivery-required>
                            <input class="full" type="text" name="delivery_landmark" id="deliveryLandmark" maxlength="120" placeholder="Nearest landmark (optional)">
                            <textarea class="full" name="rider_notes" id="riderNotes" rows="3" maxlength="180" placeholder="Notes for rider, gate instructions, contact preference"></textarea>
                        </div>
                    </div>
                    <select name="payment_method" required>
                        <option value="Cash">Cash</option>
                        <option value="GCash">GCash</option>
                        <option value="Card">Card</option>
                    </select>
                    <textarea name="notes" rows="3" maxlength="180" placeholder="Order notes or special request"></textarea>
                </div>

                <div class="totals" aria-live="polite">
                    <div class="total-row"><span>Items</span><strong id="itemCount">0</strong></div>
                    <div class="total-row"><span>Subtotal</span><strong id="subtotal">PHP 0.00</strong></div>
                    <div class="total-row"><span>Add-ons</span><strong id="extrasTotal">PHP 0.00</strong></div>
                    <div class="total-row"><span>Delivery fee</span><strong id="deliveryFee">PHP 0.00</strong></div>
                    <div class="total-row grand-total"><span>Total</span><span id="grandTotal">PHP 0.00</span></div>
                </div>

                <input type="hidden" name="order_payload" id="orderPayload">
                <button type="submit" class="checkout-btn w-100 mt-3" <?= $isGuest ? 'data-requires-login' : ''; ?>>Review Order</button>
            </aside>
        </form>
    </main>

    <div class="product-modal" id="productModal" aria-hidden="true">
        <div class="product-modal-header">
            <button type="button" class="modal-back" id="modalClose" aria-label="Back to menu">←</button>
            <h2>Product details</h2>
        </div>
        <div class="product-modal-body">
            <div class="product-preview-image">
                <img id="modalImage" src="" alt="">
            </div>
            <div class="product-details-panel">
                <div class="product-title-row">
                    <h3 id="modalName"></h3>
                    <div class="modal-price" id="modalPrice">PHP 0.00</div>
                </div>

                <div class="option-group">
                    <h4>A: Meal Option*</h4>
                    <p>Select 1 option</p>
                    <div class="option-list" id="modalVariants"></div>
                </div>

                <div class="option-group">
                    <h4>B: Add-On: Extras</h4>
                    <p>Select up to 5 options</p>
                    <div class="option-list" id="modalExtras">
                        <?php foreach ($EXTRAS as $extra => $price): ?>
                            <label class="option-row">
                                <span><input type="checkbox" value="<?= htmlspecialchars($extra); ?>" data-price="<?= (float) $price; ?>"> <?= htmlspecialchars($extra); ?></span>
                                <strong>+ PHP <?= number_format($price, 2); ?></strong>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="modal-quantity">
                    <strong>Quantity</strong>
                    <div class="modal-quantity-control">
                        <button type="button" id="modalQtyMinus">-</button>
                        <input type="number" id="modalQty" value="1" min="1" max="20" aria-label="Preview quantity">
                        <button type="button" id="modalQtyPlus">+</button>
                    </div>
                </div>

                <button type="button" class="modal-add" id="modalAdd">Add to Cart</button>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        const menuData = <?= json_encode($MENU_ITEMS); ?>;
        const isGuest = <?= $isGuest ? 'true' : 'false'; ?>;
        const CART_KEY = 'jollibeep_cart';
        const cart = loadSavedCart();
        const cartItems = document.getElementById('cartItems');
        const formatter = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
        const storeNameSelect = document.getElementById('storeName');
        const orderTypeSelect = document.getElementById('orderType');
        const deliveryFields = document.getElementById('deliveryFields');
        const deliveryRequiredFields = [...deliveryFields.querySelectorAll('[data-delivery-required]')];

        document.querySelectorAll('[data-store-choice]').forEach(button => {
            button.addEventListener('click', () => {
                if (!storeNameSelect) return;

                storeNameSelect.value = button.dataset.storeChoice;
                storeNameSelect.dispatchEvent(new Event('change', { bubbles: true }));
                storeNameSelect.focus();
            });
        });

        function loadSavedCart() {
            try {
                const savedCart = JSON.parse(localStorage.getItem(CART_KEY) || '[]');
                if (!Array.isArray(savedCart)) return [];

                return savedCart
                    .filter(item => item && menuData[item.code])
                    .map(item => ({
                        code: item.code,
                        name: menuData[item.code].name,
                        image: menuData[item.code].image,
                        variant: item.variant || 'Solo',
                        baseVariant: item.baseVariant || item.variant || 'Solo',
                        lineExtras: Array.isArray(item.lineExtras) ? item.lineExtras : [],
                        price: Number(item.price) || Number(menuData[item.code].prices[item.baseVariant || item.variant || 'Solo']) || 0,
                        quantity: Math.min(20, Math.max(1, Number(item.quantity) || 1))
                    }));
            } catch (error) {
                return [];
            }
        }

        function saveCart() {
            localStorage.setItem(CART_KEY, JSON.stringify(cart));
            window.dispatchEvent(new CustomEvent('jollibeep-cart-updated'));
        }

        function clampQuantity(input) {
            const value = Number.parseInt(input.value, 10);
            input.value = Number.isFinite(value) ? Math.min(20, Math.max(1, value)) : 1;
        }

        function renderCart() {
            if (!cart.length) {
                cartItems.innerHTML = '<div class="empty-cart">Your cart is waiting for something crispy.</div>';
            } else {
                cartItems.innerHTML = cart.map((item, index) => `
                    <div class="cart-item">
                        <div class="d-flex justify-content-between gap-2">
                            <strong>${item.name}</strong>
                            <button class="remove-item" type="button" data-index="${index}">Remove</button>
                        </div>
                        <div class="cart-meta">${item.variant} x ${item.quantity} - ${formatter.format(item.price * item.quantity)}</div>
                    </div>
                `).join('');
            }
            updateTotals();
            saveCart();
        }

        function selectedExtras() {
            return [...document.querySelectorAll('.extra-check:checked')].map(extra => ({
                name: extra.value,
                price: Number(extra.dataset.price)
            }));
        }

        function addCartItem(item) {
            const existing = cart.find(cartItem => cartItem.code === item.code && cartItem.variant === item.variant);

            if (existing) {
                existing.quantity = Math.min(20, existing.quantity + item.quantity);
            } else {
                cart.push(item);
            }

            renderCart();
        }

        function updateTotals() {
            const subtotal = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
            const extras = selectedExtras();
            const extrasTotal = extras.reduce((sum, extra) => sum + extra.price, 0);
            const deliveryFee = orderTypeSelect.value === 'Delivery' ? 49 : 0;
            const itemCount = cart.reduce((sum, item) => sum + item.quantity, 0);
            const grandTotal = subtotal + extrasTotal + deliveryFee;

            document.getElementById('itemCount').textContent = itemCount;
            document.getElementById('subtotal').textContent = formatter.format(subtotal);
            document.getElementById('extrasTotal').textContent = formatter.format(extrasTotal);
            document.getElementById('deliveryFee').textContent = formatter.format(deliveryFee);
            document.getElementById('grandTotal').textContent = formatter.format(grandTotal);
            document.getElementById('orderPayload').value = JSON.stringify({ items: cart, extras });
        }

        function syncDeliveryFields() {
            const isDelivery = orderTypeSelect.value === 'Delivery';
            deliveryFields.hidden = !isDelivery;
            deliveryRequiredFields.forEach(field => {
                field.required = isDelivery;
                if (!isDelivery) {
                    field.setCustomValidity('');
                }
            });
            updateTotals();
        }

        function validateDeliveryFields() {
            if (orderTypeSelect.value !== 'Delivery') return true;
            const missingField = deliveryRequiredFields.find(field => !field.value.trim());
            if (!missingField) return true;

            missingField.setCustomValidity('Please complete this delivery detail.');
            missingField.reportValidity();
            missingField.setCustomValidity('');
            deliveryFields.scrollIntoView({ behavior: 'smooth', block: 'center' });
            missingField.focus();
            return false;
        }

        document.querySelectorAll('.menu-card').forEach(card => {
            const qtyInput = card.querySelector('input[type="number"]');

            card.querySelectorAll('[data-step]').forEach(button => {
                button.addEventListener('click', () => {
                    qtyInput.value = Number(qtyInput.value || 1) + Number(button.dataset.step);
                    clampQuantity(qtyInput);
                });
            });

            qtyInput.addEventListener('input', () => clampQuantity(qtyInput));

            card.querySelector('.add-btn').addEventListener('click', () => {
                if (isGuest) {
                    showGuestAuthModal();
                    return;
                }
                const code = card.dataset.code;
                const variantSelect = card.querySelector('select');
                const variant = variantSelect.value;
                const price = Number(variantSelect.selectedOptions[0].dataset.price);
                const quantity = Number(qtyInput.value);
                addCartItem({ code, name: menuData[code].name, image: menuData[code].image, variant, baseVariant: variant, price, quantity });
            });
        });

        const productModal = document.getElementById('productModal');
        const modalImage = document.getElementById('modalImage');
        const modalName = document.getElementById('modalName');
        const modalPrice = document.getElementById('modalPrice');
        const modalVariants = document.getElementById('modalVariants');
        const modalQty = document.getElementById('modalQty');
        const modalAdd = document.getElementById('modalAdd');
        let activePreviewCode = null;

        function checkedModalVariant() {
            return modalVariants.querySelector('input[name="modalVariant"]:checked');
        }

        function updateModalPrice() {
            const selectedVariant = checkedModalVariant();
            const basePrice = selectedVariant ? Number(selectedVariant.dataset.price) : 0;
            const extrasTotal = [...document.querySelectorAll('#modalExtras input:checked')]
                .reduce((sum, extra) => sum + Number(extra.dataset.price), 0);
            const quantity = Number(modalQty.value) || 1;
            modalPrice.textContent = formatter.format((basePrice + extrasTotal) * quantity);
        }

        function openProductPreview(code) {
            const item = menuData[code];
            activePreviewCode = code;
            modalImage.src = item.image;
            modalImage.alt = item.name;
            modalName.textContent = item.name;
            modalQty.value = 1;
            document.querySelectorAll('#modalExtras input').forEach(extra => extra.checked = false);
            modalVariants.innerHTML = Object.entries(item.prices).map(([variant, price], index) => `
                <label class="option-row">
                    <span>
                        <input type="radio" name="modalVariant" value="${variant}" data-price="${price}" ${index === 0 ? 'checked' : ''}>
                        ${variant}
                    </span>
                    <strong>${formatter.format(price)}</strong>
                </label>
            `).join('');
            productModal.classList.add('open');
            productModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            updateModalPrice();
        }

        function closeProductPreview() {
            productModal.classList.remove('open');
            productModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            activePreviewCode = null;
        }

        document.querySelectorAll('.preview-btn').forEach(button => {
            button.addEventListener('click', () => {
                openProductPreview(button.closest('.menu-card').dataset.code);
            });
        });

        document.getElementById('modalClose').addEventListener('click', closeProductPreview);
        productModal.addEventListener('click', event => {
            if (event.target === productModal) closeProductPreview();
        });

        modalVariants.addEventListener('change', updateModalPrice);
        document.getElementById('modalExtras').addEventListener('change', event => {
            const checkedExtras = document.querySelectorAll('#modalExtras input:checked');
            if (checkedExtras.length > 5) {
                event.target.checked = false;
                alert('You can select up to 5 extras only.');
            }
            updateModalPrice();
        });

        document.getElementById('modalQtyMinus').addEventListener('click', () => {
            modalQty.value = Math.max(1, Number(modalQty.value || 1) - 1);
            updateModalPrice();
        });

        document.getElementById('modalQtyPlus').addEventListener('click', () => {
            modalQty.value = Math.min(20, Number(modalQty.value || 1) + 1);
            updateModalPrice();
        });

        modalQty.addEventListener('input', () => {
            clampQuantity(modalQty);
            updateModalPrice();
        });

        modalAdd.addEventListener('click', () => {
            if (isGuest) {
                showGuestAuthModal();
                return;
            }
            const selectedVariant = checkedModalVariant();
            if (!activePreviewCode || !selectedVariant) return;

            const extras = [...document.querySelectorAll('#modalExtras input:checked')];
            const selectedExtras = extras.map(extra => extra.value);
            const extrasLabel = selectedExtras.length ? ` + ${selectedExtras.join(', ')}` : '';
            const variant = `${selectedVariant.value}${extrasLabel}`;
            const price = Number(selectedVariant.dataset.price) + extras.reduce((sum, extra) => sum + Number(extra.dataset.price), 0);
            const quantity = Number(modalQty.value) || 1;

            addCartItem({
                code: activePreviewCode,
                name: menuData[activePreviewCode].name,
                image: menuData[activePreviewCode].image,
                variant,
                baseVariant: selectedVariant.value,
                lineExtras: selectedExtras,
                price,
                quantity
            });
            closeProductPreview();
        });

        cartItems.addEventListener('click', event => {
            const button = event.target.closest('.remove-item');
            if (!button) return;
            cart.splice(Number(button.dataset.index), 1);
            renderCart();
        });

        document.querySelectorAll('.extra-check').forEach(extra => extra.addEventListener('change', updateTotals));
        orderTypeSelect.addEventListener('change', syncDeliveryFields);

        const tabs = document.querySelectorAll('.category-tab');
        const search = document.getElementById('menuSearch');
        const searchButton = document.getElementById('menuSearchButton');
        const searchSuggestions = document.getElementById('menuSearchSuggestions');
        const menuCards = [...document.querySelectorAll('.menu-card')];
        const menuGrid = document.getElementById('menuGrid');
        const menuEmptyState = document.getElementById('menuEmptyState');
        let activeCategory = 'All';
        let activeSuggestionIndex = -1;

        function normalizeSearchText(value) {
            return (value || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
        }

        function searchTokens(value) {
            return normalizeSearchText(value).split(/\s+/).filter(Boolean);
        }

        function tokenMatchesWord(token, word) {
            if (!token || !word) return false;
            return word === token || word.startsWith(token) || word.includes(token);
        }

        function tokenMatchesText(token, text) {
            const normalized = normalizeSearchText(text);
            if (normalized.includes(token)) return true;
            return normalized.split(/\s+/).some(word => tokenMatchesWord(token, word));
        }

        function menuMatches(query) {
            if (!query) return [];
            const tokens = searchTokens(query);

            return menuCards
                .map(card => {
                    const title = card.querySelector('h3')?.textContent || '';
                    const badge = card.querySelector('.menu-badge')?.textContent || '';
                    const description = card.querySelector('p')?.textContent || '';
                    const name = normalizeSearchText(title);
                    const category = normalizeSearchText(card.dataset.category || '');
                    const searchable = normalizeSearchText([title, category, badge, description].join(' '));
                    const allTokensMatch = tokens.every(token => tokenMatchesText(token, searchable));

                    if (!allTokensMatch) {
                        return null;
                    }

                    let score = 0;
                    if (name === query) score += 140;
                    if (name.startsWith(query)) score += 110;
                    if (name.includes(query)) score += 80;
                    if (category.includes(query)) score += 45;
                    if (searchable.includes(query)) score += 35;

                    tokens.forEach(token => {
                        if (tokenMatchesText(token, name)) score += 28;
                        if (tokenMatchesText(token, category)) score += 16;
                        if (name.split(/\s+/).some(word => word.startsWith(token))) score += 18;
                        if (searchable.split(/\s+/).some(word => word.startsWith(token))) score += 10;
                    });

                    if (activeCategory !== 'All' && card.dataset.category === activeCategory) score += 10;

                    return { card, score, name };
                })
                .filter(Boolean)
                .sort((a, b) => b.score - a.score || a.name.localeCompare(b.name));
        }

        function jumpToMenuCard(card) {
            if (!card) return;
            activeCategory = 'All';
            tabs.forEach(item => item.classList.toggle('active', item.dataset.category === 'All'));
            menuCards.forEach(item => item.style.display = item === card ? '' : 'none');
            search.value = card.querySelector('h3')?.textContent.trim() || search.value;
            closeSuggestions();
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            card.classList.add('menu-card-highlight');
            setTimeout(() => card.classList.remove('menu-card-highlight'), 2200);
        }

        function closeSuggestions() {
            activeSuggestionIndex = -1;
            searchSuggestions.classList.remove('open');
            searchSuggestions.innerHTML = '';
        }

        function renderSuggestions(matches, query) {
            activeSuggestionIndex = -1;

            if (!query) {
                closeSuggestions();
                return;
            }

            if (!matches.length) {
                searchSuggestions.innerHTML = '<div class="menu-no-suggestion">No menu items matched your search.</div>';
                searchSuggestions.classList.add('open');
                return;
            }

            searchSuggestions.innerHTML = matches.slice(0, 7).map(({ card }, index) => {
                const code = card.dataset.code;
                const item = menuData[code];
                const firstPrice = Object.values(item.prices)[0] || 0;
                return `
                    <button type="button" class="menu-suggestion" role="option" data-code="${code}" data-index="${index}">
                        <img src="${item.image}" alt="">
                        <span>
                            <strong>${item.name}</strong>
                            <span>${item.category} · ${formatter.format(firstPrice)}</span>
                        </span>
                    </button>
                `;
            }).join('');
            searchSuggestions.classList.add('open');
        }

        function filterMenu() {
            const query = normalizeSearchText(search.value);
            const matches = menuMatches(query);
            const matchedCards = new Set(matches.map(match => match.card));
            const orderedCards = query ? matches.map(match => match.card) : menuCards;
            let visibleCount = 0;

            orderedCards.forEach(card => menuGrid.appendChild(card));

            menuCards.forEach(card => {
                const matchesCategory = activeCategory === 'All' || card.dataset.category === activeCategory;
                const matchesSearch = !query || matchedCards.has(card);
                const shouldShow = matchesCategory && matchesSearch;
                card.hidden = !shouldShow;
                card.style.display = shouldShow ? '' : 'none';
                if (shouldShow) visibleCount++;
            });

            renderSuggestions(matches.filter(match => activeCategory === 'All' || match.card.dataset.category === activeCategory), query);
            menuEmptyState.classList.toggle('show', visibleCount === 0);
            return matches;
        }

        function submitMenuSearch() {
            const matches = filterMenu().filter(match => activeCategory === 'All' || match.card.dataset.category === activeCategory);
            if (matches.length) {
                closeSuggestions();
                matches[0].card.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(item => item.classList.remove('active'));
                tab.classList.add('active');
                activeCategory = tab.dataset.category;
                filterMenu();
            });
        });

        search.addEventListener('input', filterMenu);

        search.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                event.preventDefault();
            }

            const buttons = [...searchSuggestions.querySelectorAll('.menu-suggestion')];
            if (!buttons.length || !searchSuggestions.classList.contains('open')) {
                if (event.key === 'Enter') submitMenuSearch();
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                activeSuggestionIndex = (activeSuggestionIndex + 1) % buttons.length;
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                activeSuggestionIndex = (activeSuggestionIndex - 1 + buttons.length) % buttons.length;
            } else if (event.key === 'Enter') {
                event.preventDefault();
                buttons[Math.max(0, activeSuggestionIndex)].click();
                return;
            } else if (event.key === 'Escape') {
                closeSuggestions();
                return;
            } else {
                return;
            }

            buttons.forEach((button, index) => button.classList.toggle('active', index === activeSuggestionIndex));
            buttons[activeSuggestionIndex]?.scrollIntoView({ block: 'nearest' });
        });

        searchButton.addEventListener('click', submitMenuSearch);

        searchSuggestions.addEventListener('click', event => {
            const button = event.target.closest('.menu-suggestion');
            if (!button) return;
            jumpToMenuCard(document.querySelector(`.menu-card[data-code="${CSS.escape(button.dataset.code)}"]`));
        });

        document.addEventListener('click', event => {
            if (event.target.closest('.menu-search-wrap')) return;
            closeSuggestions();
        });

        document.querySelectorAll('[data-category-jump]').forEach(button => {
            button.addEventListener('click', () => {
                const targetCategory = button.dataset.categoryJump;
                const matchingTab = [...tabs].find(tab => tab.dataset.category === targetCategory);
                if (matchingTab) {
                    matchingTab.click();
                } else {
                    activeCategory = 'All';
                    tabs.forEach(item => item.classList.toggle('active', item.dataset.category === 'All'));
                    filterMenu();
                }
                document.getElementById('menuGrid').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        function applyMenuDeepLink() {
            const params = new URLSearchParams(window.location.search);
            const targetCategory = params.get('category');
            const targetItem = params.get('item');

            if (targetCategory) {
                const matchingTab = [...tabs].find(tab => tab.dataset.category === targetCategory);
                if (matchingTab) {
                    matchingTab.click();
                }
            }

            if (targetItem) {
                const card = document.querySelector(`.menu-card[data-code="${CSS.escape(targetItem)}"]`);
                if (card) {
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    card.classList.add('menu-card-highlight');
                    setTimeout(() => card.classList.remove('menu-card-highlight'), 2400);
                }
            } else if (targetCategory) {
                document.getElementById('menuGrid').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        document.getElementById('checkoutForm').addEventListener('submit', event => {
            updateTotals();
            if (isGuest) {
                event.preventDefault();
                showGuestAuthModal();
                return;
            }
            if (!cart.length) {
                event.preventDefault();
                alert('Please add at least one item before reviewing your order.');
                return;
            }
            if (!validateDeliveryFields()) {
                event.preventDefault();
            }
        });

        syncDeliveryFields();
        renderCart();
        applyMenuDeepLink();
    </script>
</body>

</html>
