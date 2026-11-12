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

// 2. Fetch UserID (The logic that we know works!)
$user_sql = "SELECT * FROM `users`";
$all_users = mysqli_query($conn, $user_sql);

while ($row = mysqli_fetch_assoc($all_users)) {
    foreach ($row as $colName => $val) {
        if (strtolower($val) === strtolower($session_uname)) {
            foreach ($row as $key => $idVal) {
                // Matching 'userid' or 'id' case-insensitively
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

// 3. Get Plant ID from the URL
$plantID = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

if ($plantID !== '') {
    // 4. Check if already saved to Garden
    // Column names: userID and plantID (from your description)
    $check_sql = "SELECT * FROM `save` WHERE `userID` = '$userID' AND `plantID` = '$plantID'";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) == 0) {
        // 5. INSERT INTO 'save' table
        // Column names: userID, plantID, saveTime
        $insert_sql = "INSERT INTO `save` (`userID`, `plantID`, `saveTime`) 
                       VALUES ('$userID', '$plantID', NOW())";
        
        if (mysqli_query($conn, $insert_sql)) {
            // Redirect back with a 'saved' message
            header("Location: user_plant_details.php?id=$plantID&msg=saved");
        } else {
            // Error reporting just in case
            die("SQL Error in 'save' table: " . mysqli_error($conn));
        }
    } else {
        // Already exists in Garden
        header("Location: user_plant_details.php?id=$plantID&msg=alreadysaved");
    }
} else {
    header("Location: user_search.php");
}
exit();
?>