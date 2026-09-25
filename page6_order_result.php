<?php
session_start();
require_once 'menu_data.php';
require_once 'stores_data.php';
require_once 'orders_repository.php';

function peso($amount)
{
    return 'PHP ' . number_format((float) $amount, 2);
}

function clean($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$error = '';
$summary = null;
$editCartItems = [];
$isFinalOrder = ($_POST['place_order'] ?? '') === '1';

if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($_SESSION['username'])) {
    $error = 'Please create an account or log in before placing an order.';
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    $payload = json_decode($_POST['order_payload'] ?? '', true);
    $customerName = trim($_POST['customer_name'] ?? '');
    $storeName = trim($_POST['store_name'] ?? '');
    $orderType = $_POST['order_type'] ?? 'Pickup';
    $paymentMethod = $_POST['payment_method'] ?? 'Cash';
    $notes = trim($_POST['notes'] ?? '');
    $deliveryBlockLot = trim($_POST['delivery_block_lot'] ?? '');
    $deliveryStreet = trim($_POST['delivery_street'] ?? '');
    $deliveryBarangay = trim($_POST['delivery_barangay'] ?? '');
    $deliveryLandmark = trim($_POST['delivery_landmark'] ?? '');
    $riderNotes = trim($_POST['rider_notes'] ?? '');
    $validStoreNames = array_column($STORES, 'name');

    if (!$customerName || !is_array($payload) || empty($payload['items'])) {
        $error = 'Your order is incomplete. Please go back and add at least one item.';
    } elseif (!in_array($storeName, $validStoreNames, true)) {
        $error = 'Please select a valid serving store for this order.';
    } else {
        $items = [];
        $subtotal = 0;

        foreach ($payload['items'] as $item) {
            $code = $item['code'] ?? '';
            $displayVariant = $item['variant'] ?? '';
            $baseVariant = $item['baseVariant'] ?? $displayVariant;
            $quantity = max(1, min(20, (int) ($item['quantity'] ?? 1)));

            if (!isset($MENU_ITEMS[$code]) || !isset($MENU_ITEMS[$code]['prices'][$baseVariant])) {
                $error = 'One of the selected menu items is no longer available.';
                break;
            }

            $lineExtrasTotal = 0;
            $lineExtras = [];
            foreach (($item['lineExtras'] ?? []) as $lineExtraName) {
                if (isset($EXTRAS[$lineExtraName])) {
                    $lineExtras[] = $lineExtraName;
                    $lineExtrasTotal += (float) $EXTRAS[$lineExtraName];
                }
            }

            $price = (float) $MENU_ITEMS[$code]['prices'][$baseVariant] + $lineExtrasTotal;
            $variant = $displayVariant ?: $baseVariant;
            if (!$displayVariant && $lineExtras) {
                $variant = $baseVariant . ' + ' . implode(', ', $lineExtras);
            }
            $lineTotal = $price * $quantity;
            $subtotal += $lineTotal;
            $editCartItems[] = [
                'code' => $code,
                'variant' => $variant,
                'baseVariant' => $baseVariant,
                'lineExtras' => $lineExtras,
                'price' => $price,
                'quantity' => $quantity,
            ];
            $items[] = [
                'name' => $MENU_ITEMS[$code]['name'],
                'image' => $MENU_ITEMS[$code]['image'] ?? '',
                'variant' => $variant,
                'quantity' => $quantity,
                'price' => $price,
                'line_total' => $lineTotal,
            ];
        }

        $extras = [];
        $extrasTotal = 0;
        foreach (($payload['extras'] ?? []) as $extra) {
            $extraName = $extra['name'] ?? '';
            if (isset($EXTRAS[$extraName])) {
                $extras[] = ['name' => $extraName, 'price' => (float) $EXTRAS[$extraName]];
                $extrasTotal += (float) $EXTRAS[$extraName];
            }
        }

        if (!$error) {
            $orderType = in_array($orderType, ['Pickup', 'Delivery'], true) ? $orderType : 'Pickup';
            $paymentMethod = in_array($paymentMethod, ['Cash', 'GCash', 'Card'], true) ? $paymentMethod : 'Cash';
            $deliveryDetails = [
                'block_lot' => $deliveryBlockLot,
                'street' => $deliveryStreet,
                'barangay' => $deliveryBarangay,
                'landmark' => $deliveryLandmark,
                'rider_notes' => $riderNotes,
            ];

            if ($orderType === 'Delivery') {
                $requiredDeliveryFields = [$deliveryBlockLot, $deliveryStreet, $deliveryBarangay];
                foreach ($requiredDeliveryFields as $fieldValue) {
                    if ($fieldValue === '') {
                        $error = 'Please complete the delivery address before placing your order.';
                        break;
                    }
                }
            } else {
                $deliveryDetails = [];
            }

            if (!$error) {
                $deliveryFee = $orderType === 'Delivery' ? 49 : 0;
                date_default_timezone_set("Asia/Manila");

                $summary = [
                    'order_no' => $isFinalOrder ? 'JB-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)) : 'Pending',
                    'date' => date("F d, Y h:i A"),
                    'customer_name' => $customerName,
                    'store_name' => $storeName,
                    'order_type' => $orderType,
                    'payment_method' => $paymentMethod,
                    'notes' => $notes,
                    'delivery_details' => $deliveryDetails,
                    'items' => $items,
                    'extras' => $extras,
                    'subtotal' => $subtotal,
                    'extras_total' => $extrasTotal,
                    'delivery_fee' => $deliveryFee,
                    'grand_total' => $subtotal + $extrasTotal + $deliveryFee,
                ];

                if ($isFinalOrder) {
                    appendOrder($summary, $_SESSION['user_id'] ?? null);
                }
            }
        }
    }
} else {
    $error = 'No order data received.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Order Summary</title>
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
            background: linear-gradient(180deg, #fff8e8, #fff);
            color: var(--j-ink);
        }

        .receipt-shell {
            max-width: 980px;
            margin: 0 auto;
            padding: 36px 18px 48px;
        }

        .receipt-hero {
            background: var(--j-red);
            color: #fff;
            border-radius: 8px;
            padding: 26px;
            display: flex;
            justify-content: space-between;
            gap: 20px;
            align-items: center;
            box-shadow: 0 16px 34px rgba(151, 17, 23, .18);
        }

        .receipt-hero h1 {
            font-family: 'Chewy', cursive;
            font-size: clamp(2.3rem, 5vw, 4rem);
            margin: 0;
        }

        .receipt-hero img {
            width: 110px;
            height: auto;
            background: #fff;
            border-radius: 8px;
            padding: 10px;
        }

        .receipt-card {
            background: #fff;
            border: 1px solid #f0d0d0;
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(99, 20, 20, .09);
            margin-top: 22px;
            overflow: hidden;
        }

        .receipt-meta {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 1px;
            background: #f3d3d3;
        }

        .meta-box {
            background: #fffafa;
            padding: 14px;
        }

        .meta-box span {
            display: block;
            color: #7a5050;
            font-size: .78rem;
            text-transform: uppercase;
            font-weight: 800;
        }

        .meta-box strong {
            color: var(--j-deep);
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th,
        .items-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f2dada;
            vertical-align: top;
        }

        .items-table th {
            color: var(--j-deep);
            font-size: .82rem;
            text-transform: uppercase;
            letter-spacing: 0;
            background: #fff8e8;
        }

        .text-end {
            text-align: right;
        }

        .receipt-item {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .receipt-item img {
            width: 82px;
            height: 82px;
            object-fit: contain;
            flex: 0 0 82px;
            border-radius: 8px;
            background: #fff;
        }

        .receipt-item div {
            min-width: 0;
        }

        .totals {
            margin-left: auto;
            max-width: 360px;
            padding: 18px 16px 22px;
            display: grid;
            gap: 8px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .grand-total {
            color: var(--j-red);
            font-size: 1.4rem;
            font-weight: 900;
            border-top: 1px dashed #e6b5b5;
            padding-top: 10px;
        }

        .notes {
            margin: 0 16px 18px;
            padding: 14px;
            background: #fff8e8;
            border-radius: 8px;
        }

        .delivery-summary {
            margin: 16px;
            padding: 16px;
            border: 1px solid #ffd0d0;
            border-radius: 10px;
            background: #fffafa;
        }

        .delivery-summary h2,
        .order-status h2 {
            color: var(--j-deep);
            font-size: 1.15rem;
            font-weight: 900;
            margin: 0 0 12px;
        }

        .delivery-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .delivery-detail {
            border: 1px solid #f2dada;
            border-radius: 8px;
            padding: 10px 12px;
            background: #fff;
        }

        .delivery-detail span {
            display: block;
            color: #7a5050;
            font-size: .78rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .delivery-detail strong {
            color: #242833;
        }

        .delivery-detail.full {
            grid-column: 1 / -1;
        }

        .order-status {
            margin-top: 20px;
            border: 1px solid #ffd0d0;
            border-radius: 10px;
            background: #fff;
            padding: 18px;
        }

        .status-current {
            color: var(--j-red);
            font-weight: 900;
            margin-bottom: 14px;
        }

        .status-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .status-anim {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--j-deep);
            font-weight: 900;
            margin-bottom: 10px;
        }

        .status-pan {
            width: 34px;
            height: 24px;
            border: 3px solid var(--j-red);
            border-top: 0;
            border-radius: 0 0 18px 18px;
            position: relative;
            animation: panShake .85s ease-in-out infinite;
        }

        .status-pan::before,
        .status-pan::after {
            content: "";
            position: absolute;
            left: 9px;
            width: 4px;
            height: 12px;
            border-radius: 999px;
            background: #ff9d00;
            top: -16px;
            animation: steam 1.2s ease-in-out infinite;
        }

        .status-pan::after {
            left: 20px;
            animation-delay: .25s;
        }

        @keyframes panShake {
            0%, 100% { transform: rotate(-2deg); }
            50% { transform: rotate(2deg) translateY(-1px); }
        }

        @keyframes steam {
            0% { opacity: .2; transform: translateY(5px) scale(.8); }
            50% { opacity: 1; }
            100% { opacity: 0; transform: translateY(-7px) scale(1.1); }
        }

        .status-timeline {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .status-step {
            border: 1px dashed #f0a4ad;
            border-radius: 10px;
            padding: 12px;
            color: #745252;
            font-weight: 800;
            background: #fffafa;
        }

        .status-step.active {
            border-style: solid;
            color: #0f7a39;
            background: #eaffef;
        }

        .status-step.done {
            border-style: solid;
            color: var(--j-deep);
            background: #fff8e8;
        }

        .action-row {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .btn-jolli {
            border: 0;
            border-radius: 8px;
            padding: 12px 18px;
            background: var(--j-red);
            color: #fff;
            text-decoration: none;
            font-weight: 800;
        }

        .btn-jolli.secondary {
            background: #fff;
            color: var(--j-red);
            border: 1px solid #e7a6a6;
        }

        .message {
            max-width: 640px;
            margin: 50px auto;
            background: #fff;
            border: 1px solid #f0d0d0;
            border-left: 8px solid var(--j-red);
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 12px 30px rgba(99, 20, 20, .09);
        }

        @media (max-width: 760px) {
            .receipt-hero {
                align-items: flex-start;
            }

            .receipt-hero img {
                width: 82px;
            }

            .receipt-meta {
                grid-template-columns: 1fr 1fr;
            }

            .delivery-grid,
            .status-timeline {
                grid-template-columns: 1fr;
            }

            .items-table th:nth-child(3),
            .items-table td:nth-child(3) {
                display: none;
            }
        }
    </style>
</head>

<body>
    <?php include 'menu-links.php'; ?>

    <main class="receipt-shell">
        <?php if ($summary): ?>
            <section class="receipt-hero">
                <div>
                    <h1><?= $isFinalOrder ? 'Order Confirmed' : 'Review Your Order'; ?></h1>
                    <p class="mb-0">
                        <?= $isFinalOrder
                            ? 'Thank you, ' . clean($summary['customer_name']) . '. Here is your official JolliBeep receipt.'
                            : 'Check your items, store, payment, and total before placing the order.'; ?>
                    </p>
                </div>
                <img src="jollibeeplg.png" alt="Jollibee logo">
            </section>

            <section class="receipt-card">
                <div class="receipt-meta">
                    <div class="meta-box"><span>Order No.</span><strong><?= clean($summary['order_no']); ?></strong></div>
                    <div class="meta-box"><span>Date</span><strong><?= clean($summary['date']); ?></strong></div>
                    <div class="meta-box"><span>Store</span><strong><?= clean($summary['store_name']); ?></strong></div>
                    <div class="meta-box"><span>Type</span><strong><?= clean($summary['order_type']); ?></strong></div>
                    <div class="meta-box"><span>Payment</span><strong><?= clean($summary['payment_method']); ?></strong></div>
                </div>

                <?php if ($summary['order_type'] === 'Delivery' && !empty($summary['delivery_details'])): ?>
                    <?php $delivery = $summary['delivery_details']; ?>
                    <div class="delivery-summary">
                        <h2>Delivery Address</h2>
                        <div class="delivery-grid">
                            <div class="delivery-detail"><span>Block / Lot / Unit</span><strong><?= clean($delivery['block_lot']); ?></strong></div>
                            <div class="delivery-detail"><span>Street / Subdivision</span><strong><?= clean($delivery['street']); ?></strong></div>
                            <div class="delivery-detail full"><span>Barangay / City / Province</span><strong><?= clean($delivery['barangay']); ?></strong></div>
                            <?php if (!empty($delivery['landmark'])): ?>
                                <div class="delivery-detail full"><span>Landmark</span><strong><?= clean($delivery['landmark']); ?></strong></div>
                            <?php endif; ?>
                            <?php if (!empty($delivery['rider_notes'])): ?>
                                <div class="delivery-detail full"><span>Notes for rider</span><strong><?= clean($delivery['rider_notes']); ?></strong></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($summary['items'] as $item): ?>
                            <tr>
                                <td>
                                    <div class="receipt-item">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="<?= clean($item['image']); ?>" alt="<?= clean($item['name']); ?>">
                                        <?php endif; ?>
                                        <div>
                                            <strong><?= clean($item['name']); ?></strong><br>
                                            <span class="text-muted"><?= clean($item['variant']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?= (int) $item['quantity']; ?></td>
                                <td class="text-end"><?= peso($item['price']); ?></td>
                                <td class="text-end"><?= peso($item['line_total']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php foreach ($summary['extras'] as $extra): ?>
                            <tr>
                                <td><strong><?= clean($extra['name']); ?></strong><br><span class="text-muted">Add-on</span></td>
                                <td>1</td>
                                <td class="text-end"><?= peso($extra['price']); ?></td>
                                <td class="text-end"><?= peso($extra['price']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($summary['notes']): ?>
                    <div class="notes"><strong>Notes:</strong> <?= clean($summary['notes']); ?></div>
                <?php endif; ?>

                <div class="totals">
                    <div class="total-row"><span>Subtotal</span><strong><?= peso($summary['subtotal']); ?></strong></div>
                    <div class="total-row"><span>Add-ons</span><strong><?= peso($summary['extras_total']); ?></strong></div>
                    <div class="total-row"><span>Delivery fee</span><strong><?= peso($summary['delivery_fee']); ?></strong></div>
                    <div class="total-row grand-total"><span>Total</span><span><?= peso($summary['grand_total']); ?></span></div>
                </div>
            </section>

            <?php if ($isFinalOrder): ?>
                <?php
                $statusSteps = $summary['order_type'] === 'Delivery'
                    ? ['Preparing food', "Rider's picking up", "Rider's on the way to you"]
                    : ['Preparing food', 'Ready for pickup'];
                ?>
                <section class="order-status" aria-label="Order status">
                    <div class="status-head">
                        <div>
                            <h2>Order Status</h2>
                            <div class="status-anim"><span class="status-pan" aria-hidden="true"></span><span id="statusMotionText">Preparing your food</span></div>
                            <div class="status-current">Current status: <span id="currentStatusText"><?= clean($statusSteps[0]); ?></span></div>
                        </div>
                        <button type="button" class="btn-jolli secondary" id="refreshStatusBtn">Refresh Status</button>
                    </div>
                    <div class="status-timeline">
                        <?php foreach ($statusSteps as $index => $step): ?>
                            <div class="status-step <?= $index === 0 ? 'active' : ''; ?>" data-status-index="<?= $index; ?>">
                                <?= clean($step); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <div class="action-row">
                <?php if ($isFinalOrder): ?>
                    <a href="page5_jollibee_orderform.php" class="btn-jolli">Place Another Order</a>
                    <button type="button" class="btn-jolli secondary" onclick="window.print()">Print Receipt</button>
                <?php else: ?>
                    <form method="post" action="page6_order_result.php" class="d-inline">
                        <input type="hidden" name="place_order" value="1">
                        <input type="hidden" name="order_payload" value="<?= clean($_POST['order_payload'] ?? ''); ?>">
                        <input type="hidden" name="customer_name" value="<?= clean($_POST['customer_name'] ?? ''); ?>">
                        <input type="hidden" name="store_name" value="<?= clean($_POST['store_name'] ?? ''); ?>">
                        <input type="hidden" name="order_type" value="<?= clean($_POST['order_type'] ?? 'Pickup'); ?>">
                        <input type="hidden" name="payment_method" value="<?= clean($_POST['payment_method'] ?? 'Cash'); ?>">
                        <input type="hidden" name="notes" value="<?= clean($_POST['notes'] ?? ''); ?>">
                        <input type="hidden" name="delivery_block_lot" value="<?= clean($_POST['delivery_block_lot'] ?? ''); ?>">
                        <input type="hidden" name="delivery_street" value="<?= clean($_POST['delivery_street'] ?? ''); ?>">
                        <input type="hidden" name="delivery_barangay" value="<?= clean($_POST['delivery_barangay'] ?? ''); ?>">
                        <input type="hidden" name="delivery_landmark" value="<?= clean($_POST['delivery_landmark'] ?? ''); ?>">
                        <input type="hidden" name="rider_notes" value="<?= clean($_POST['rider_notes'] ?? ''); ?>">
                        <button type="submit" class="btn-jolli">Place Order</button>
                    </form>
                    <button type="button" class="btn-jolli secondary" id="editOrderBtn">Edit Order</button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="message">
                <h1 class="h3 text-danger fw-bold">Order could not be completed</h1>
                <p><?= clean($error); ?></p>
                <a href="page5_jollibee_orderform.php" class="btn-jolli">Back to Order Page</a>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>
    <?php if ($summary): ?>
        <script>
            const editCartItems = <?= json_encode($editCartItems, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
            <?php if ($isFinalOrder): ?>
                localStorage.removeItem('jollibeep_cart');
                window.dispatchEvent(new CustomEvent('jollibeep-cart-updated'));
                const statusSteps = <?= json_encode($summary['order_type'] === 'Delivery'
                    ? ['Preparing food', "Rider's picking up", "Rider's on the way to you"]
                    : ['Preparing food', 'Ready for pickup'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
                let currentStatusIndex = 0;
                const currentStatusText = document.getElementById('currentStatusText');
                const statusMotionText = document.getElementById('statusMotionText');
                const refreshStatusBtn = document.getElementById('refreshStatusBtn');
                const statusLabels = {
                    'Preparing food': 'Preparing your food',
                    'Ready for pickup': 'Ready for pickup',
                    "Rider's picking up": "Rider is picking up your order",
                    "Rider's on the way to you": "Rider is on the way to you"
                };

                function paintOrderStatus() {
                    document.querySelectorAll('[data-status-index]').forEach(step => {
                        const stepIndex = Number(step.dataset.statusIndex);
                        step.classList.toggle('done', stepIndex < currentStatusIndex);
                        step.classList.toggle('active', stepIndex === currentStatusIndex);
                    });
                    const label = statusSteps[currentStatusIndex] || statusSteps[0];
                    currentStatusText.textContent = label;
                    statusMotionText.textContent = statusLabels[label] || label;
                    refreshStatusBtn.textContent = currentStatusIndex >= statusSteps.length - 1 ? 'Status Updated' : 'Refresh Status';
                }

                refreshStatusBtn?.addEventListener('click', () => {
                    currentStatusIndex = Math.min(currentStatusIndex + 1, statusSteps.length - 1);
                    paintOrderStatus();
                });

                paintOrderStatus();
            <?php endif; ?>

            document.getElementById('editOrderBtn')?.addEventListener('click', () => {
                localStorage.setItem('jollibeep_cart', JSON.stringify(editCartItems));
                window.dispatchEvent(new CustomEvent('jollibeep-cart-updated'));
                window.location.href = 'page5_jollibee_orderform.php';
            });
        </script>
    <?php endif; ?>
</body>

</html>
