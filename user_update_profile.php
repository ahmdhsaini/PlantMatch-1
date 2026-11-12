<?php
include('private_header.php');
include('db.php');

// Ensure session is active
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$userID = $_SESSION['user_id'];

// =========================================================================
// POST REQUEST PROCESSING LAYER
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    // Collect and sanitize user inputs
    $new_name  = mysqli_real_escape_string($conn, $_POST['username']);
    $new_email = mysqli_real_escape_string($conn, $_POST['email']);
    $new_pass  = $_POST['password'];

    if (!empty($new_pass)) {
        // Encrypt the new password before storage
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        // Uses 'name' to match your database schema
        $update_sql = "UPDATE users SET name = '$new_name', email = '$new_email', password = '$hashed_pass' WHERE userID = '$userID'";
    } else {
        // Update without changing the password
        // Uses 'name' to match your database schema
        $update_sql = "UPDATE users SET name = '$new_name', email = '$new_email' WHERE userID = '$userID'";
    }

    if (mysqli_query($conn, $update_sql)) {
        // Dynamically sync session data so the header/dashboard updates instantly
        $_SESSION['username'] = $new_name;
        
        echo "<script>
                alert('Profile successfully updated!');
                window.location.href = 'user_dashboard.php';
              </script>";
        exit();
    } else {
        echo "<script>alert('Error: Unable to update records in database.');</script>";
    }
}

// =========================================================================
// FETCH DATA LAYER (Removed profile_picture to prevent errors)
// =========================================================================
$user_sql = "SELECT userID, name, email, role FROM users WHERE userID = '$userID' LIMIT 1";
$user_res = mysqli_query($conn, $user_sql);
$userData = mysqli_fetch_assoc($user_res);
?>

<main class="py-5 bg-light min-vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-3 bg-white">
                    <div class="card-body p-5">
                        <div class="d-flex align-items-center mb-4 text-success border-bottom pb-3">
                            <i class="bi bi-person-gear fs-2 me-2"></i>
                            <h4 class="fw-bold mb-0">Update Profile Information</h4>
                        </div>

                        <form action="user_update_profile.php" method="POST" onsubmit="return confirm('Are you sure you want to update your profile information?');">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary">User ID Reference</label>
                                <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($userData['userID']); ?>" readonly>
                                <small class="text-muted">System assigned identifier indices cannot be altered.</small>
                            </div>

                            <div class="mb-3">
                                <label for="username" class="form-label fw-bold text-dark">Account Name</label>
                                <input type="text" name="username" id="username" class="form-control" 
                                       value="<?php echo htmlspecialchars($userData['name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold text-dark">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label fw-bold text-dark">New Password</label>
                                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••">
                                <small class="text-muted">Leave completely empty if you wish to retain your current active password configuration.</small>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="update_profile" class="btn btn-success fw-bold py-2 rounded-3">
                                    <i class="bi bi-check-circle me-1"></i> Save Updated Changes
                                </button>
                                <a href="user_dashboard.php" class="btn btn-outline-secondary py-2 rounded-3 fw-bold">
                                    Cancel & Return
                                </a>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include('footer.php'); ?>