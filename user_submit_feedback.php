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

// 2. Fetch UserID (The logic that never fails us)
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
    die("Error: User session valid but UserID not found in database.");
}

// 3. Get Data from Form (POST)
// Note: Ensure your HTML form uses method="POST"
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $plantID = mysqli_real_escape_string($conn, $_POST['plantID']);
    $rating  = mysqli_real_escape_string($conn, $_POST['rating']);
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);

    // 4. INSERT INTO 'feedback' table
    // Columns: feedbackID (auto), userID, plantID, rating, comment, feedbackDate
    $insert_sql = "INSERT INTO `feedback` (`userID`, `plantID`, `rating`, `comment`, `feedbackDate`) 
                   VALUES ('$userID', '$plantID', '$rating', '$comment', NOW())";
    
    if (mysqli_query($conn, $insert_sql)) {
        header("Location: user_plant_details.php?id=$plantID&msg=feedback_success");
    } else {
        die("SQL Error in feedback: " . mysqli_error($conn));
    }
} else {
    header("Location: user_search.php");
}
exit();
?>