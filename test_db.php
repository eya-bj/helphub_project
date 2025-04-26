<?php
require 'db.php';          // ← your connection file

$q = $pdo->query("
    SELECT DATABASE() AS db,
           @@hostname  AS host,
           COUNT(*)    AS donors
    FROM   donor
");

header('Content-Type: text/plain');
print_r($q->fetch());
$q->closeCursor(); // Close cursor to free up resources
$pdo = null; // Close the connection
?>