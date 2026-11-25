<?php
$host = getenv('DB_HOST') ?: "127.0.0.1";
$user = "root";
$pass = ""; // Laragon default is empty
$dbname = "plantmatch_db";

// Create connection
$conn = mysqli_connect($host, $user, $pass, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
// echo "Connected successfully!"; // Uncomment this to test, then comment it back
?>