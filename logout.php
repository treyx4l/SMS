<?php
// Destroy session and redirect to login
session_start();
$_SESSION = [];
session_unset();
session_destroy();

header('Location: /SMS/login.php');
exit;

