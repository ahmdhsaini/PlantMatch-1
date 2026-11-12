<?php
/**
 * Project: PlantMatch FYP
 * Developer: Ahmad Husaini
 * Description: Logic handler for searching plant attributes.
 */

// 1. Database Configuration
$host = "localhost";
$user = "root";
$pass = "";
$db_name = "plantmatch_db";

// 2. Establish Connection
$conn = mysqli_connect($host, $user, $pass, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// 3. Capture and Sanitize Search Input
// We use mysqli_real_escape_string to prevent SQL Injection
$search_query = "";
if (isset($_GET['search'])) {
    $search_query = mysqli_real_escape_string($conn, $_GET['search']);
}

/**
 * 4. Redirect Logic
 * Instead of sessions, we pass the keyword back via GET.
 * This allows the search.php page to be public and shareable.
 * We include the keyword in the URL.
 */
if (!empty($search_query)) {
    // Redirect with the search term
    header("Location: search.php?keyword=" . urlencode($search_query));
} else {
    // If search is empty, just go back to the main search page
    header("Location: search.php");
}

// 5. Close Connection
mysqli_close($conn);
exit();
?>