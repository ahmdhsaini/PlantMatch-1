<?php
session_start();
include('db.php');

// 1. Security check
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$session_uname = $_SESSION['username'];
$userID = null;

// 2. Fetch UserID (Logic to handle capitalization automatically)
$user_sql = "SELECT * FROM `users`";
$all_users = mysqli_query($conn, $user_sql);

while ($row = mysqli_fetch_assoc($all_users)) {
    foreach ($row as $colName => $val) {
        if (strtolower($val) === strtolower($session_uname)) {
            foreach ($row as $key => $idVal) {
                if (strtolower($key) === 'userid' || strtolower($key) === 'id') {
                    $userID = $idVal;
                    break 2;
                }
            }
        }
    }
}

if (!$userID) {
    die("Error: Could not find your UserID in the database.");
}

// 3. Get Plant ID from URL
$plantID = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

if ($plantID !== '') {
    // 4. Check if already favorited
    $check_sql = "SELECT * FROM `favorite` WHERE `UserID` = '$userID' AND `PlantID` = '$plantID'";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) == 0) {
        // 5. THE FIX: Using 'dateSaved' as the column name
        $insert_sql = "INSERT INTO `favorite` (`UserID`, `PlantID`, `dateSaved`) 
                       VALUES ('$userID', '$plantID', NOW())";
        
        if (mysqli_query($conn, $insert_sql)) {
            // Success! Redirect back to the details page
            header("Location: user_plant_details.php?id=$plantID&msg=added");
        } else {
            // This will show if there is still a typo in the column names
            die("SQL Error: " . mysqli_error($conn));
        }
    } else {
        // Already in favorites
        header("Location: user_plant_details.php?id=$plantID&msg=exists");
    }
} else {
    header("Location: user_search.php");
}
exit();
?>