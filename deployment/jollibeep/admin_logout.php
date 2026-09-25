<?php
session_start();
unset($_SESSION['admin_logged_in'], $_SESSION['admin_user_id'], $_SESSION['admin_name']);
header("Location: page12_admin_login.php");
exit();
?>
