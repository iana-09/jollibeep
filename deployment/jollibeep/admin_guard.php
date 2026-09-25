<?php
require_once 'db_connection.php';

function ensureAdminColumn($conn)
{
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
    if ($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0");
    }
}

function getAdminCount($conn)
{
    ensureAdminColumn($conn);
    $result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE is_admin = 1");
    if ($result && $row = $result->fetch_assoc()) {
        return (int) $row['total'];
    }
    return 0;
}

function requireAdmin()
{
    if (empty($_SESSION['admin_logged_in'])) {
        header("Location: page12_admin_login.php?admin_required=1");
        exit();
    }
}
?>
