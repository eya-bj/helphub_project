<?php
// Database connection parameters
$host = 'localhost';    // Database host
$dbname = 'helphub';    // Database name
$username = 'root';     // Database username
$password = '';         // Database password (default is empty for WAMP)
$charset = 'utf8mb4';   // Character set

// DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

// PDO options for better error handling
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Return associative arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                   // Use real prepared statements
];

try {
    // Create PDO instance
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Log the error and exit gracefully
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please contact the administrator.");
}
?>
