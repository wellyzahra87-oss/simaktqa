<?php
session_start();
if(isset($_SESSION['role'])) {
    if($_SESSION['role'] == 'Admin') {
        header("Location: dashboard_admin.php");
    } else {
        header("Location: dashboard_guru.php");
    }
} else {
    header("Location: login.php");
}
exit;
?>