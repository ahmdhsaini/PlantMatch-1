<?php
session_start(); 
include('db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {
            // SUCCESS
            $_SESSION['user_id'] = $user['userID'];
            $_SESSION['username'] = $user['name']; // Using 'username' to match your dashboard plan

            // Redirect specifically to the User Dashboard
            header("Location: user_dashboard.php");
            exit();
        } else {
            echo "<script>alert('Incorrect password!'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Email not found!'); window.history.back();</script>";
    }
}
mysqli_close($conn);
?>