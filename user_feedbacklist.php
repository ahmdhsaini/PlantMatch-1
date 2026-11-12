<?php 
include('private_header.php'); 
include('db.php');

// Ensure session is started and userID is captured
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$userID = $_SESSION['user_id'];

// =========================================================================
// SELF-CONTAINED DELETE LOGIC FOR CARDS PAGE
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['plantid'])) {
    $delete_plantid = mysqli_real_escape_string($conn, $_GET['plantid']);
    
    // Securely delete only the review that belongs to the currently logged-in user
    $delete_sql = "DELETE FROM feedback WHERE userID = '$userID' AND plantid = '$delete_plantid'";
    
    if (mysqli_query($conn, $delete_sql)) {
        // Refresh the page cleanly to reflect structural deletions instantly
        echo "<script>window.location.href='user_feedbacklist.php';</script>";
        exit();
    }
}
// =========================================================================

// Fetch ALL reviews for this specific user (No Row Bounds)
$feedback_sql = "SELECT fb.plantid, fb.comment, p.Scientific_Name, p.Common_Name, p.Image_URL 
                 FROM feedback fb 
                 JOIN plantmatch_ultimate_complete p ON fb.plantid = p.ID 
                 WHERE fb.userID = '$userID' 
                 ORDER BY fb.plantid DESC";
$feedback_res = mysqli_query($conn, $feedback_sql);
$feedback_count = mysqli_num_rows($feedback_res);
?>

<style>
    .feedback-card {
        border: none;
        border-radius: 15px;
        transition: 0.3s;
        background: #fff;
        height: 100%;
    }
    .feedback-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
    }
    .plant-id-badge {
        font-size: 0.7rem;
        background: #e9ecef;
        padding: 2px 8px;
        border-radius: 4px;
    }
    .feedback-img {
        height: 200px;
        object-fit: cover;
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
        border-bottom: 1px solid #dee2e6;
    }
    .section-title {
        font-weight: 800;
        color: #104206;
        border-left: 5px solid #198754;
        padding-left: 15px;
    }
</style>

<main class="py-5 bg-light min-vh-100">
    <div class="container px-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <div>
                <a href="user_dashboard.php" class="text-success text-decoration-none small fw-bold mb-1 d-inline-block">
                    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                </a>
                <h2 class="section-title mb-0">All My Reviews & Feedback</h2>
            </div>
            <span class="badge bg-success px-3 py-2 rounded-pill fw-bold">
                Total Reviews: <?php echo $feedback_count; ?>
            </span>
        </div>

        <?php if ($feedback_count > 0): ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php while($fb = mysqli_fetch_assoc($feedback_res)): ?>
                    <div class="col">
                        <div class="card feedback-card shadow-sm d-flex flex-column h-100">
                            
                            <?php 
                                $img = (!empty($fb['Image_URL'])) ? $fb['Image_URL'] : 'https://images.unsplash.com/photo-1545241047-6083a3684587?w=400';
                            ?>
                            <img src="<?php echo $img; ?>" class="card-img-top feedback-img" alt="plant cover image">
                            
                            <div class="card-body d-flex flex-column flex-grow-1">
                                <div class="mb-2">
                                    <span class="plant-id-badge">ID: <?php echo $fb['plantid']; ?></span>
                                </div>
                                
                                <h5 class="card-title fw-bold text-dark mb-1">
                                    <?php echo htmlspecialchars(explode(',', $fb['Common_Name'] ?? 'Plant')[0]); ?>
                                </h5>
                                
                                <p class="text-muted small fst-italic mb-3">
                                    <em><?php echo htmlspecialchars($fb['Scientific_Name'] ?? 'Species Name'); ?></em>
                                </p>
                                
                                <div class="bg-light p-3 rounded-3 mb-3 flex-grow-1">
                                    <p class="mb-0 text-secondary small lh-base text-break" style="white-space: pre-line;">
                                        "<?php echo htmlspecialchars($fb['comment'] ?? 'No comment text provided.'); ?>"
                                    </p>
                                </div>
                                
                                <div class="d-flex gap-2 mt-auto pt-3 border-top">
                                    <a href="user_plant_details.php?id=<?php echo urlencode($fb['plantid']); ?>" 
                                       class="btn btn-sm btn-success rounded-pill px-3 fw-bold flex-grow-1">
                                       View Details
                                    </a>
                                    <a href="user_feedbacklist.php?action=delete&plantid=<?php echo urlencode($fb['plantid']); ?>" 
                                       class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold"
                                       onclick="return confirm('Are you sure you want to permanently delete this review?');">
                                       Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5 bg-white rounded shadow-sm border mt-4">
                <i class="bi bi-chat-left-dots display-3 text-muted opacity-50 d-block mb-3"></i>
                <h4 class="fw-bold text-dark">No Feedback History Found</h4>
                <p class="text-muted small mb-0 px-3">You haven't left comments or reviews on any matching plants yet.</p>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php include('footer.php'); ?>