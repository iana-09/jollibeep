<?php
include 'db_connection.php';

if (isset($_GET['username'])) {
    $username = trim($_GET['username']);

    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();

    echo ($count > 0) ? "taken" : "available";

    $stmt->close();
    $conn->close();
}
?>