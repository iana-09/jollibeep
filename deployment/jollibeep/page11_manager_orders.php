<?php
session_start();
require_once 'admin_guard.php';
require_once 'stores_data.php';
require_once 'orders_repository.php';

ensureAdminColumn($conn);
requireAdmin();

date_default_timezone_set("Asia/Manila");

$statusOptions = ['Pending', 'Preparing Food', 'Ready for Pickup', "Rider's Picking Up", "Rider's On The Way", 'Delivered', 'Completed', 'Cancelled'];
$pickupStatusOptions = ['Pending', 'Preparing Food', 'Ready for Pickup', 'Completed', 'Cancelled'];
$deliveryStatusOptions = ['Pending', 'Preparing Food', "Rider's Picking Up", "Rider's On The Way", 'Delivered', 'Cancelled'];
$storeNames = array_column($STORES, 'name');
$message = '';

function cleanManager($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function pesoManager($amount)
{
    return 'PHP ' . number_format((float) $amount, 2);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderNo = trim($_POST['order_no'] ?? '');
    $status = $_POST['status'] ?? 'Pending';
    $scheduledFor = trim($_POST['scheduled_for'] ?? '');
    $riderName = trim($_POST['rider_name'] ?? '');

    if ($orderNo && in_array($status, $statusOptions, true)) {
        updateOrderManagement($orderNo, $status, $scheduledFor, $riderName);
        $message = 'Order ' . $orderNo . ' was updated.';
    }
}

$selectedStore = $_GET['store'] ?? '';
$selectedStatus = $_GET['status'] ?? '';
$orders = loadOrders();
usort($orders, function ($a, $b) {
    return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
});

$totalOrders = count($orders);
$pendingOrders = count(array_filter($orders, function ($order) {
    return ($order['status'] ?? 'Pending') === 'Pending';
}));
$activeOrders = count(array_filter($orders, function ($order) {
    return !in_array($order['status'] ?? 'Pending', ['Completed', 'Cancelled'], true);
}));
$totalSales = array_sum(array_map(function ($order) {
    return (float) ($order['grand_total'] ?? 0);
}, $orders));

$filteredOrders = array_values(array_filter($orders, function ($order) use ($selectedStore, $selectedStatus) {
    if ($selectedStore && ($order['store_name'] ?? '') !== $selectedStore) {
        return false;
    }
    if ($selectedStatus && ($order['status'] ?? '') !== $selectedStatus) {
        return false;
    }
    return true;
}));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>JolliBeep Order Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Chewy&family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --j-red: #df001b;
            --j-deep: #8f1017;
            --j-yellow: #ffd33d;
            --j-ink: #1f2937;
            --j-muted: #667085;
            --j-line: #e7eaf0;
            --j-soft: #fff5f5;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #fff;
            color: var(--j-ink);
        }

        .control-header {
            background: var(--j-red);
            color: #fff;
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 12px 24px;
            box-shadow: 0 3px 12px rgba(99, 20, 20, .12);
        }

        .control-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #fff;
            text-decoration: none;
            font-family: 'Chewy', cursive;
            font-size: 1.7rem;
        }

        .control-brand img {
            width: 50px;
            height: auto;
            background: #fff;
            border-radius: 10px;
            padding: 4px;
        }

        .control-header span {
            font-weight: 900;
        }

        .manager-shell {
            max-width: 1240px;
            margin: 0 auto;
            padding: 28px 18px 56px;
        }

        .admin-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
            padding: 14px 16px;
            background: #fff;
            border: 1px solid var(--j-line);
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(17, 24, 39, .05);
        }

        .admin-topbar strong {
            color: var(--j-deep);
        }

        .admin-logout {
            color: var(--j-red);
            border: 1px solid #f2b4b9;
            border-radius: 8px;
            padding: 10px 14px;
            font-weight: 900;
            text-decoration: none;
        }

        .admin-logout:hover,
        .admin-logout:focus {
            background: var(--j-soft);
            color: var(--j-deep);
        }

        .manager-hero {
            background: var(--j-red);
            color: #fff;
            border-radius: 12px;
            padding: 28px;
            display: flex;
            justify-content: space-between;
            gap: 20px;
            align-items: center;
            box-shadow: 0 16px 34px rgba(97, 0, 11, .16);
        }

        .manager-hero h1 {
            font-family: 'Chewy', cursive;
            font-size: clamp(2.4rem, 5vw, 4.6rem);
            margin: 0;
            letter-spacing: 0;
        }

        .manager-hero p {
            margin: 8px 0 0;
            font-weight: 800;
        }

        .manager-hero img {
            width: 120px;
            background: #fff;
            border-radius: 12px;
            padding: 10px;
        }

        .filter-card,
        .order-card {
            background: #fff;
            border: 1px solid var(--j-line);
            border-radius: 10px;
            box-shadow: 0 12px 26px rgba(17, 24, 39, .06);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-top: 18px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid var(--j-line);
            border-radius: 10px;
            padding: 16px;
            box-shadow: 0 10px 22px rgba(17, 24, 39, .05);
        }

        .stat-card span {
            display: block;
            color: var(--j-muted);
            font-weight: 900;
            font-size: .78rem;
            text-transform: uppercase;
        }

        .stat-card strong {
            display: block;
            color: var(--j-deep);
            font-size: 1.8rem;
            font-weight: 900;
            margin-top: 4px;
        }

        .filter-card {
            margin: 22px 0 28px;
            padding: 18px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 12px;
            align-items: end;
        }

        label {
            font-weight: 800;
            color: #202938;
            margin-bottom: 7px;
        }

        select,
        input {
            border: 1px solid #d6dce7;
            border-radius: 8px;
            padding: 12px 14px;
            font: inherit;
            width: 100%;
            min-height: 48px;
        }

        .btn-jolli {
            border: 0;
            background: var(--j-red);
            color: #fff;
            border-radius: 8px;
            padding: 12px 18px;
            font-weight: 900;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
        }

        .btn-jolli:hover,
        .btn-jolli:focus {
            background: #b90017;
            color: #fff;
        }

        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            margin-bottom: 16px;
        }

        .section-title h2 {
            font-weight: 900;
            margin: 0;
        }

        .order-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .order-card {
            padding: 18px;
        }

        .order-head {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            border-bottom: 1px solid var(--j-line);
            padding-bottom: 14px;
            margin-bottom: 14px;
        }

        .order-head h3 {
            color: var(--j-deep);
            font-weight: 900;
            margin: 0 0 4px;
            font-size: 1.25rem;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff2c6;
            color: #7a4a00;
            border-radius: 999px;
            padding: 6px 10px;
            font-weight: 900;
            white-space: nowrap;
            font-size: .86rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }

        .detail-box {
            background: #f8fafc;
            border: 1px solid #edf0f5;
            border-radius: 8px;
            padding: 10px;
        }

        .detail-box span {
            display: block;
            color: var(--j-muted);
            font-size: .78rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .detail-box strong {
            color: #111827;
            word-break: break-word;
        }

        .delivery-panel {
            border: 1px solid #ffd0d0;
            background: #fffafa;
            border-radius: 10px;
            padding: 12px;
            margin: 12px 0 14px;
        }

        .delivery-panel h4 {
            color: var(--j-deep);
            font-weight: 900;
            font-size: 1rem;
            margin: 0 0 10px;
        }

        .delivery-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .delivery-grid .full {
            grid-column: 1 / -1;
        }

        .manager-help {
            color: var(--j-muted);
            font-size: .86rem;
            margin-top: 8px;
        }

        .item-list {
            margin: 0;
            padding-left: 18px;
            color: #475467;
        }

        .item-list li {
            margin-bottom: 6px;
        }

        .manage-form {
            border-top: 1px solid var(--j-line);
            margin-top: 14px;
            padding-top: 14px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 10px;
            align-items: end;
        }

        .empty-state {
            border: 2px dashed #f3b3b8;
            background: var(--j-soft);
            color: var(--j-deep);
            border-radius: 12px;
            text-align: center;
            padding: 36px 18px;
            font-weight: 900;
        }

        .manager-alert {
            background: #ecfdf3;
            color: #067647;
            border-radius: 8px;
            padding: 12px 14px;
            font-weight: 800;
            margin-top: 16px;
        }

        @media (max-width: 900px) {
            .manager-hero {
                align-items: flex-start;
            }

            .manager-hero img {
                width: 86px;
            }

            .filter-grid,
            .order-grid,
            .manage-form,
            .stats-grid,
            .delivery-grid {
                grid-template-columns: 1fr;
            }

            .admin-topbar {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <header class="control-header">
        <a href="page11_manager_orders.php" class="control-brand">
            <img src="jollibeeplg.png" alt="JolliBeep logo">
            JolliBeep Control
        </a>
        <span>Controller Side</span>
    </header>

    <main class="manager-shell">
        <div class="admin-topbar">
            <div>
                <strong>Controller:</strong> <?= cleanManager($_SESSION['admin_name'] ?? 'System Admin'); ?>
                <span class="text-muted ms-2">System control side</span>
            </div>
            <a href="admin_logout.php" class="admin-logout">Logout Controller</a>
        </div>

        <section class="manager-hero">
            <div>
                <h1>Order Manager</h1>
                <p>View customer orders by store, update progress, and schedule pickup or delivery.</p>
            </div>
            <img src="jollibeeplg.png" alt="JolliBeep logo">
        </section>

        <section class="stats-grid" aria-label="Order control summary">
            <div class="stat-card"><span>Total Orders</span><strong><?= $totalOrders; ?></strong></div>
            <div class="stat-card"><span>Pending</span><strong><?= $pendingOrders; ?></strong></div>
            <div class="stat-card"><span>Active Queue</span><strong><?= $activeOrders; ?></strong></div>
            <div class="stat-card"><span>Total Sales</span><strong><?= pesoManager($totalSales); ?></strong></div>
        </section>

        <?php if ($message): ?>
            <div class="manager-alert"><?= cleanManager($message); ?></div>
        <?php endif; ?>

        <section class="filter-card" aria-label="Order filters">
            <form method="get" class="filter-grid">
                <div>
                    <label for="store">Store</label>
                    <select name="store" id="store">
                        <option value="">All stores</option>
                        <?php foreach ($storeNames as $storeName): ?>
                            <option value="<?= cleanManager($storeName); ?>" <?= $selectedStore === $storeName ? 'selected' : ''; ?>>
                                <?= cleanManager($storeName); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="">All statuses</option>
                        <?php foreach ($statusOptions as $status): ?>
                            <option value="<?= cleanManager($status); ?>" <?= $selectedStatus === $status ? 'selected' : ''; ?>>
                                <?= cleanManager($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-jolli">Filter Orders</button>
            </form>
        </section>

        <section>
            <div class="section-title">
                <h2>Customer Orders</h2>
                <strong><?= count($filteredOrders); ?> order<?= count($filteredOrders) === 1 ? '' : 's'; ?></strong>
            </div>

            <?php if (!$filteredOrders): ?>
                <div class="empty-state">No customer orders found yet.</div>
            <?php else: ?>
                <div class="order-grid">
                    <?php foreach ($filteredOrders as $order): ?>
                        <?php
                        $isDelivery = ($order['order_type'] ?? '') === 'Delivery';
                        $delivery = $order['delivery_details'] ?? [];
                        $orderStatusOptions = $isDelivery ? $deliveryStatusOptions : $pickupStatusOptions;
                        ?>
                        <article class="order-card">
                            <div class="order-head">
                                <div>
                                    <h3><?= cleanManager($order['order_no'] ?? 'Order'); ?></h3>
                                    <div><?= cleanManager($order['receipt_date'] ?? $order['created_at'] ?? ''); ?></div>
                                </div>
                                <span class="status-pill"><?= cleanManager($order['status'] ?? 'Pending'); ?></span>
                            </div>

                            <div class="detail-grid">
                                <div class="detail-box"><span>Customer</span><strong><?= cleanManager($order['customer_name'] ?? ''); ?></strong></div>
                                <div class="detail-box"><span>Store</span><strong><?= cleanManager($order['store_name'] ?? ''); ?></strong></div>
                                <div class="detail-box"><span>Type</span><strong><?= cleanManager($order['order_type'] ?? ''); ?></strong></div>
                                <div class="detail-box"><span>Total</span><strong><?= pesoManager($order['grand_total'] ?? 0); ?></strong></div>
                                <div class="detail-box"><span>Payment</span><strong><?= cleanManager($order['payment_method'] ?? ''); ?></strong></div>
                                <div class="detail-box"><span><?= $isDelivery ? 'Delivery ETA' : 'Pickup Time'; ?></span><strong><?= cleanManager($order['scheduled_for'] ?: 'Not scheduled'); ?></strong></div>
                                <?php if ($isDelivery): ?>
                                    <div class="detail-box"><span>Rider</span><strong><?= cleanManager(($order['rider_name'] ?? '') ?: 'Not assigned'); ?></strong></div>
                                <?php endif; ?>
                            </div>

                            <?php if ($isDelivery): ?>
                                <div class="delivery-panel">
                                    <h4>Delivery Handling</h4>
                                    <?php if ($delivery): ?>
                                        <div class="delivery-grid">
                                            <div class="detail-box"><span>Block / Lot / Unit</span><strong><?= cleanManager($delivery['block_lot'] ?? ''); ?></strong></div>
                                            <div class="detail-box"><span>Street / Subdivision</span><strong><?= cleanManager($delivery['street'] ?? ''); ?></strong></div>
                                            <div class="detail-box full"><span>Barangay / City / Province</span><strong><?= cleanManager($delivery['barangay'] ?? ''); ?></strong></div>
                                            <?php if (!empty($delivery['landmark'])): ?>
                                                <div class="detail-box full"><span>Landmark</span><strong><?= cleanManager($delivery['landmark']); ?></strong></div>
                                            <?php endif; ?>
                                            <?php if (!empty($delivery['rider_notes'])): ?>
                                                <div class="detail-box full"><span>Notes for Rider</span><strong><?= cleanManager($delivery['rider_notes']); ?></strong></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="manager-help">No delivery address details were captured for this older order. New delivery orders will show rider address details here.</div>
                                    <?php endif; ?>
                                    <div class="manager-help">Delivery flow: prepare food, assign rider, mark rider pickup, then mark on the way or delivered.</div>
                                </div>
                            <?php endif; ?>

                            <strong>Items</strong>
                            <ul class="item-list">
                                <?php foreach (($order['items'] ?? []) as $item): ?>
                                    <li>
                                        <?= (int) ($item['quantity'] ?? 1); ?>x
                                        <?= cleanManager($item['name'] ?? 'Item'); ?>
                                        <span>(<?= cleanManager($item['variant'] ?? ''); ?>)</span>
                                    </li>
                                <?php endforeach; ?>
                                <?php foreach (($order['extras'] ?? []) as $extra): ?>
                                    <li>Addon: <?= cleanManager($extra['name'] ?? ''); ?></li>
                                <?php endforeach; ?>
                            </ul>

                            <?php if (!empty($order['notes'])): ?>
                                <div class="detail-box mt-3"><span>Customer Notes</span><strong><?= cleanManager($order['notes']); ?></strong></div>
                            <?php endif; ?>

                            <form method="post" class="manage-form">
                                <input type="hidden" name="order_no" value="<?= cleanManager($order['order_no'] ?? ''); ?>">
                                <div>
                                    <label for="status-<?= cleanManager($order['order_no'] ?? ''); ?>">Status</label>
                                    <select name="status" id="status-<?= cleanManager($order['order_no'] ?? ''); ?>">
                                        <?php foreach ($orderStatusOptions as $status): ?>
                                            <option value="<?= cleanManager($status); ?>" <?= ($order['status'] ?? 'Pending') === $status ? 'selected' : ''; ?>>
                                                <?= cleanManager($status); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php if ($isDelivery): ?>
                                    <div>
                                        <label for="rider-<?= cleanManager($order['order_no'] ?? ''); ?>">Rider</label>
                                        <input type="text" name="rider_name" id="rider-<?= cleanManager($order['order_no'] ?? ''); ?>" value="<?= cleanManager($order['rider_name'] ?? ''); ?>" placeholder="Assign rider">
                                    </div>
                                <?php else: ?>
                                    <input type="hidden" name="rider_name" value="">
                                <?php endif; ?>
                                <div>
                                    <label for="schedule-<?= cleanManager($order['order_no'] ?? ''); ?>"><?= $isDelivery ? 'Delivery ETA' : 'Pickup Time'; ?></label>
                                    <input type="datetime-local" name="scheduled_for" id="schedule-<?= cleanManager($order['order_no'] ?? ''); ?>" value="<?= cleanManager($order['scheduled_for'] ?? ''); ?>">
                                </div>
                                <button type="submit" class="btn-jolli">Save</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>

</html>
