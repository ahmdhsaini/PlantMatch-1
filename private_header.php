<?php
session_start();

// Security: If session is empty, redirect to login page
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <title>PlantMatch - Dashboard</title>
        <link rel="icon" type="image/x-icon" href="assets/icon3.png" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
    </head>
    <body class="d-flex flex-column h-100">
        <main class="flex-shrink-0">
            <nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background-color: #104206;">
                <div class="container px-5">
                    <a class="navbar-brand fw-bold" href="user_dashboard.php">PlantMatch</a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                            <li class="nav-item ms-lg-3">
                                <span class="nav-link text-light fw-bold">
                                    Hi, <?php echo ucfirst(strtolower(htmlspecialchars($_SESSION['username']))); ?>
                                </span>
                            </li>
                            <li class="nav-item"><a class="nav-link text-white" href="user_dashboard.php">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link text-white" href="user_search.php">Search Plants</a></li>
                            <li class="nav-item"><a class="nav-link text-white" href="user_recommendation_form.php">Get Recommendations</a></li>
                            <li class="nav-item ms-lg-2">
                                <a class="btn btn-sm btn-outline-light" href="logout.php">Logout</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>