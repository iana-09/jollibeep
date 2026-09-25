<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'db_connection.php';

const ORDERS_FILE = __DIR__ . DIRECTORY_SEPARATOR . 'orders_data.json';

function useSupabaseOrders(): bool
{
    global $conn;
    return isset($conn) && $conn instanceof PgCompatConnection;
}

function decodeOrderRow(array $row): array
{
    foreach (['delivery_details', 'items', 'extras'] as $jsonField) {
        if (isset($row[$jsonField]) && is_string($row[$jsonField])) {
            $decoded = json_decode($row[$jsonField], true);
            $row[$jsonField] = is_array($decoded) ? $decoded : [];
        }
    }

    foreach (['subtotal', 'extras_total', 'delivery_fee', 'grand_total'] as $moneyField) {
        $row[$moneyField] = (float)($row[$moneyField] ?? 0);
    }

    $row['user_id'] = isset($row['user_id']) ? (int)$row['user_id'] : null;
    $row['rider_name'] = $row['rider_name'] ?? '';
    $row['scheduled_for'] = $row['scheduled_for'] ?? '';
    $row['delivery_details'] = $row['delivery_details'] ?? [];
    $row['items'] = $row['items'] ?? [];
    $row['extras'] = $row['extras'] ?? [];

    return $row;
}

function loadOrders()
{
    if (useSupabaseOrders()) {
        global $conn;
        $rows = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
        if (!$rows) {
            return [];
        }

        $orders = [];
        while ($row = $rows->fetch_assoc()) {
            $orders[] = decodeOrderRow($row);
        }
        return $orders;
    }

    if (!is_file(ORDERS_FILE)) {
        return [];
    }

    $orders = json_decode(file_get_contents(ORDERS_FILE), true);
    return is_array($orders) ? $orders : [];
}

function saveOrders(array $orders)
{
    file_put_contents(ORDERS_FILE, json_encode(array_values($orders), JSON_PRETTY_PRINT), LOCK_EX);
}

function appendOrder(array $summary, $userId = null)
{
    $order = [
        'order_no' => $summary['order_no'],
        'user_id' => $userId,
        'customer_name' => $summary['customer_name'],
        'store_name' => $summary['store_name'],
        'order_type' => $summary['order_type'],
        'payment_method' => $summary['payment_method'],
        'notes' => $summary['notes'],
        'delivery_details' => $summary['delivery_details'] ?? [],
        'items' => $summary['items'],
        'extras' => $summary['extras'],
        'subtotal' => $summary['subtotal'],
        'extras_total' => $summary['extras_total'],
        'delivery_fee' => $summary['delivery_fee'],
        'grand_total' => $summary['grand_total'],
        'status' => 'Pending',
        'scheduled_for' => '',
        'rider_name' => '',
        'created_at' => date('Y-m-d H:i:s'),
        'receipt_date' => $summary['date'],
    ];

    if (useSupabaseOrders()) {
        global $conn;
        $stmt = $conn->prepare("INSERT INTO orders
            (order_no, user_id, customer_name, store_name, order_type, payment_method, notes, delivery_details, items, extras, subtotal, extras_total, delivery_fee, grand_total, status, scheduled_for, rider_name, created_at, receipt_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?::jsonb, ?::jsonb, ?::jsonb, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON CONFLICT (order_no) DO NOTHING");

        $deliveryDetails = json_encode($order['delivery_details']);
        $items = json_encode($order['items']);
        $extras = json_encode($order['extras']);
        $stmt->bind_param(
            "sissssssssddddsssss",
            $order['order_no'],
            $order['user_id'],
            $order['customer_name'],
            $order['store_name'],
            $order['order_type'],
            $order['payment_method'],
            $order['notes'],
            $deliveryDetails,
            $items,
            $extras,
            $order['subtotal'],
            $order['extras_total'],
            $order['delivery_fee'],
            $order['grand_total'],
            $order['status'],
            $order['scheduled_for'],
            $order['rider_name'],
            $order['created_at'],
            $order['receipt_date']
        );
        $stmt->execute();
        return;
    }

    $orders = loadOrders();
    $orders[] = $order;
    saveOrders($orders);
}

function updateOrderManagement($orderNo, $status, $scheduledFor, $riderName = '')
{
    if (useSupabaseOrders()) {
        global $conn;
        $updatedAt = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE orders SET status = ?, scheduled_for = ?, rider_name = ?, updated_at = ? WHERE order_no = ?");
        $stmt->bind_param("sssss", $status, $scheduledFor, $riderName, $updatedAt, $orderNo);
        $stmt->execute();
        return;
    }

    $orders = loadOrders();
    foreach ($orders as &$order) {
        if (($order['order_no'] ?? '') === $orderNo) {
            $order['status'] = $status;
            $order['scheduled_for'] = $scheduledFor;
            $order['rider_name'] = $riderName;
            $order['updated_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    unset($order);
    saveOrders($orders);
}
?>
