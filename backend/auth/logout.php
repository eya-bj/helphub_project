<?php
// Start session
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the homepage (index.php)
header("Location: ../../index.php?logout=success"); // Updated link
exit;
?>
