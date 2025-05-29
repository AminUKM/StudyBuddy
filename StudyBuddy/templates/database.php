<?php
$servername = "lrgs.ftsm.ukm.my";
$username = "a194789";
$password = "littlepinksheep";
$dbname = "a194789";

// Create connection
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //echo "Connection successful"; // For debugging
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit(); // Ensure the script stops if the connection fails
}
?>