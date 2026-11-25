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
// SELF-CONTAINED DELETE LOGIC
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['plantid'])) {
    $delete_plantid = mysqli_real_escape_string($conn, $_GET['plantid']);
    
    // Securely delete only the review that belongs to the currently logged-in user
    $delete_sql = "DELETE FROM feedback WHERE userID = '$userID' AND plantid = '$delete_plantid'";
    
    if (mysqli_query($conn, $delete_sql)) {
        // Refresh the page cleanly to get rid of the deleted item from the URL parameter
        echo "<script>window.location.href='user_dashboard.php';</script>";
        exit();
    }
}
// =========================================================================


// 1. Fetch User Profile Data (Joined with users table to safely get email)
$profile_sql = "SELECT p.*, u.email 
                FROM users u 
                LEFT JOIN preference p ON u.userID = p.userID 
                WHERE u.userID = '$userID' LIMIT 1";
$profile_res = mysqli_query($conn, $profile_sql);
$profile = mysqli_fetch_assoc($profile_res);

// 2. Fetch Activity Count
$activity_sql = "SELECT COUNT(*) as total FROM favorite WHERE userID = '$userID'";
$activity_res = mysqli_query($conn, $activity_sql);
$activity = mysqli_fetch_assoc($activity_res);

// Helper function to map multi-format text options to standardized 0.0, 0.5, 1.0 floats
if (!function_exists('map_input_to_val')) {
    function map_input_to_val($str) {
        $str = strtolower($str);
        // Answers that need an exact value, checked before the keyword lists below.
        // Matches how the plant table is encoded: Monthly 0.0 / Weekly 0.5 / Daily 1.0,
        // Low Light 0.0 / Partial 0.5 / Full Sun 1.0.
        if (strpos($str, 'partial') !== false) return 0.5;
        if (strpos($str, 'daily') !== false) return 1.0;
        if (strpos($str, 'weekly') !== false) return 0.5;
        if (strpos($str, 'monthly') !== false) return 0.0;
        if (strpos($str, 'low') !== false || strpos($str, 'slow') !== false || strpos($str, 'shade') !== false || strpos($str, 'beginner') !== false || strpos($str, 'small') !== false || strpos($str, 'limited') !== false || strpos($str, 'compact') !== false || strpos($str, 'infrequent') !== false || strpos($str, 'dry') !== false) {
            return 0.0;
        }
        if (strpos($str, 'high') !== false || strpos($str, 'fast') !== false || strpos($str, 'sun') !== false || strpos($str, 'advanced') !== false || strpos($str, 'expert') !== false || strpos($str, 'large') !== false || strpos($str, 'spacious') !== false || strpos($str, 'frequent') !== false || strpos($str, 'wet') !== false || strpos($str, 'humid') !== false) {
            return 1.0;
        }
        return 0.5; // Default middle-ground fallback
    }
}

// =========================================================================
// 3. LIVE IN-ENGINE ALGORITHM REPLACING PYTHON INVOCATIONS
// =========================================================================
$recommended_plants_list = [];

if ($profile && !empty($profile['houseType'])) {
    // Convert text parameters to match criteria values
    $u_light_val    = map_input_to_val($profile['lightCondition'] ?? '');
    $u_water_val    = map_input_to_val($profile['waterFreq'] ?? '');
    $u_skill_val    = map_input_to_val($profile['gardeningLevel'] ?? '');
    $u_space_val    = map_input_to_val($profile['availableSpace'] ?? '');
    $u_growth_val   = map_input_to_val($profile['growthRate'] ?? '');
    $u_humidity_val = map_input_to_val($profile['humidityNeed'] ?? '');

    // Safely extract plant preferences map to standard category binary columns
    $user_pref = strtolower($profile['plantPreference'] ?? '');
    $category_col = '';
    if (strpos($user_pref, 'climber') !== false || strpos($user_pref, 'vine') !== false) {
        $category_col = 'Categories_Climbers & Vines';
    } elseif (strpos($user_pref, 'edible') !== false || strpos($user_pref, 'herb') !== false) {
        $category_col = 'Categories_Edibles & Herbs';
    } elseif (strpos($user_pref, 'fern') !== false) {
        $category_col = 'Categories_Ferns';
    } elseif (strpos($user_pref, 'flower') !== false || strpos($user_pref, 'ornamental') !== false) {
        $category_col = 'Categories_Flowering Ornamentals';
    } elseif (strpos($user_pref, 'foliage') !== false || strpos($user_pref, 'leaf') !== false) {
        $category_col = 'Categories_Foliage Plants';
    } elseif (strpos($user_pref, 'palm') !== false) {
        $category_col = 'Categories_Palms';
    } elseif (strpos($user_pref, 'shrub') !== false) {
        $category_col = 'Categories_Shrubs';
    } elseif (strpos($user_pref, 'succulent') !== false || strpos($user_pref, 'cacti') !== false) {
        $category_col = 'Categories_Succulents & Cacti';
    } elseif (strpos($user_pref, 'tree') !== false) {
        $category_col = 'Categories_Trees';
    }

    // Process table attributes against preference vectors
    $all_plants_sql = "SELECT `ID`, `Scientific_Name`, `Common_Name`, `Gardening_Level`, `Space_Required`, `Light_Condition`, `Water_Frequency`, `Growth_Rate`, `Humidity_Need`, `Mature_Height_cm`, `Categories_Climbers & Vines`, `Categories_Edibles & Herbs`, `Categories_Ferns`, `Categories_Flowering Ornamentals`, `Categories_Foliage Plants`, `Categories_Palms`, `Categories_Shrubs`, `Categories_Succulents & Cacti`, `Categories_Trees`, `House_Type_High-rise (Apartment/Flat)`, `House_Type_Landed (Terrace/Semi-D/Bungalow)`, `House_Type_Tradisional/Spacious (Village House)` FROM plantmatch_ultimate_complete";
    $plants_res = mysqli_query($conn, $all_plants_sql);

    if ($plants_res) {
        while ($row = mysqli_fetch_assoc($plants_res)) {
            $score = 0;

            // Base Feature Quantization Metrics 
            $p_light = (float)($row['Light_Condition'] ?? 0.5);
            $score += 20 * (1 - abs($p_light - $u_light_val));

            $p_water = (float)($row['Water_Frequency'] ?? 0.5);
            $score += 20 * (1 - abs($p_water - $u_water_val));

            if (!empty($category_col) && isset($row[$category_col]) && (float)$row[$category_col] == 1.0) {
                $score += 20;
            }

            $p_skill = (float)($row['Gardening_Level'] ?? 0.5);
            $score += 10 * (1 - abs($p_skill - $u_skill_val));

            $p_space = (float)($row['Space_Required'] ?? 0.5);
            $score += 10 * (1 - abs($p_space - $u_space_val));

            $p_growth = (float)($row['Growth_Rate'] ?? 0.5);
            $score += 10 * (1 - abs($p_growth - $u_growth_val));

            $p_humidity = (float)($row['Humidity_Need'] ?? 0.5);
            $score += 10 * (1 - abs($p_humidity - $u_humidity_val));

            // Round values into final standard percentage bounds
            $final_pct = (int)round(($score / 100) * 100);
            $row['calculated_match_score'] = min(100, max(0, $final_pct));
            
            $recommended_plants_list[] = $row;
        }

        // Sort rows by structural compatibility ranking sequence
        usort($recommended_plants_list, function($a, $b) {
            return $b['calculated_match_score'] <=> $a['calculated_match_score'];
        });

        // Slice out the top 5 entries for the dashboard panel view layout
        $recommended_plants_list = array_slice($recommended_plants_list, 0, 5);
    }
}

// 4. Fetch Favorites (Purged references to table 'save')
$fav_sql = "SELECT f.*, p.Scientific_Name, p.Common_Name 
            FROM favorite f 
            JOIN plantmatch_ultimate_complete p ON f.plantID = p.ID 
            WHERE f.userID = '$userID' 
            ORDER BY f.dateSaved DESC LIMIT 4";
$fav_result = mysqli_query($conn, $fav_sql);
$fav_count = mysqli_num_rows($fav_result);

// 5. ADDITIONAL FUNCTION: Fetch User Feedback & Reviews (Limited to 5 rows for dashboard)
$feedback_sql = "SELECT fb.plantid, fb.comment, p.Scientific_Name, p.Common_Name, p.Image_URL 
                 FROM feedback fb 
                 JOIN plantmatch_ultimate_complete p ON fb.plantid = p.ID 
                 WHERE fb.userID = '$userID' 
                 ORDER BY fb.plantid DESC LIMIT 5";
$feedback_res = mysqli_query($conn, $feedback_sql);
$feedback_count = mysqli_num_rows($feedback_res);

// 6. Expanded Array of 10 Quick Plant Care Tips
$tips = [
    "Overwatering is the #1 killer of houseplants. Always check if the top 2 inches of soil are dry before watering!",
    "Wipe your plant's leaves with a damp cloth every few weeks so they can absorb light and 'breathe' efficiently.",
    "Yellow leaves often mean too much water, while crispy brown edges usually point to low room humidity.",
    "Rotate your pots 90 degrees every single week to ensure all sides get equal sunlight and grow evenly.",
    "Group plants with similar humidity needs together. They naturally transpire and create their own mini-climate!",
    "Terracotta pots are porous and breathe well, making them perfect for plants that hate soggy roots like succulents.",
    "Never use cold water straight from the tap; it can shock plant roots. Use room-temperature water instead.",
    "Prune dead or yellowing leaves quickly. This stops the plant from wasting energy trying to fix a dying leaf.",
    "If your pot has no drainage holes, keep the plant in its plastic nursery liner inside the decorative pot.",
    "Plants grow slower in winter/rainy seasons. Cut back your watering frequency and hold off on fertilizing then!"
];
$random_tip = $tips[array_rand($tips)];
?>

<style>
    .mini-card { border: none; border-radius: 15px; transition: 0.3s; background: #fff; }
    .mini-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important; }
    .plant-id-badge { font-size: 0.7rem; background: #e9ecef; padding: 2px 8px; border-radius: 4px; }
    .section-title { font-weight: 800; color: #104206; border-left: 5px solid #198754; padding-left: 15px; }
    
    .hover-lift:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(255, 193, 7, 0.3) !important;
        background-color: #ffca2c !important; 
    }
    .transition-all {
        transition: all 0.3s ease;
    }
    .profile-avatar {
        width: 48px; 
        height: 48px; 
        background-color: #198754;
        font-size: 1.4rem;
    }
    .dash-thumb {
        width: 45px;
        height: 45px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #dee2e6;
    }
</style>

<main class="py-5 bg-light min-vh-100">
    <div class="container px-5">
        
        <div class="container-fluid px-0 mb-5">
            <div class="position-relative rounded-4 overflow-hidden shadow-sm" 
                style="background: linear-gradient(120deg, #f8f9fa 0%, #e9ecef 100%); border: 1px solid #dee2e6;">
                
                <div class="position-absolute end-0 top-0 h-100 w-25 d-none d-lg-block" 
                    style="background: rgba(25, 135, 84, 0.05); clip-path: polygon(100% 0, 100% 100%, 0% 100%);">
                </div>

                <div class="row align-items-center g-0">
                    <div class="col-lg-8 p-4 p-md-5">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill fw-bold shadow-sm" style="font-size: 0.8rem;">
                                <i class="bi bi-person-check-fill me-1"></i> 
                                Welcome (<?php echo htmlspecialchars($_SESSION['username']); ?>)
                            </span>
                        </div>

                        <h1 class="display-6 fw-bold text-dark mb-2">
                            Ready to find your <span class="text-success">Perfect Match?</span>
                        </h1>
                        
                        <p class="text-muted fs-6 mb-0" style="max-width: 600px;">
                            We've prepared your environmental analysis. Let's find the plant that will actually survive in your space.
                        </p>
                    </div>

                    <div class="col-lg-4 p-4 p-md-5 text-lg-end">
                        <a href="user_recommendation_form.php" class="btn btn-warning btn-lg px-5 py-3 rounded-pill fw-bold shadow hover-lift transition-all border-0">
                            Get Match Now <i class="bi bi-arrow-right-short ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            
            <div class="col-md-4">
                
                <div class="card border-0 shadow-sm bg-white rounded-3 mb-4">
                    <div class="card-body p-4">
                        <h5 class="card-title fw-bold border-bottom pb-2 mb-3">Account Details</h5>
                        
                        <div class="d-flex align-items-center mb-4">
                            <div class="profile-avatar text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-0 fw-bold text-dark"><?php echo htmlspecialchars($_SESSION['username']); ?></h6>
                                <small class="text-muted">Garden Enthusiast</small>
                            </div>
                        </div>
                        
                        <p class="card-text mb-2 text-truncate">
                            <i class="bi bi-fingerprint text-muted me-2"></i> 
                            <strong>User ID:</strong> <span class="text-muted"><?php echo htmlspecialchars($userID); ?></span>
                        </p>
                        <p class="card-text mb-2 text-truncate">
                            <i class="bi bi-person-badge text-muted me-2"></i> 
                            <strong>Name:</strong> <span class="text-muted"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </p>
                        <p class="card-text mb-4 text-truncate">
                            <i class="bi bi-envelope-fill text-muted me-2"></i> 
                            <strong>Email:</strong> <span class="text-muted"><?php echo htmlspecialchars($profile['email'] ?? 'No email saved'); ?></span>
                        </p>
                        
                        <div class="row g-2 text-center pt-2 border-top">
                            <div class="col-12">
                                <div class="p-3 bg-light rounded-3 border-0 shadow-sm">
                                    <h5 class="fw-bold text-danger mb-0"><?php echo $fav_count; ?></h5>
                                    <small class="text-muted d-block" style="font-size: 0.75rem; font-weight: 500;">Saved Favorites Collection</small>
                                </div>
                            </div>
                            <div class="col-12 mt-2">
                                <a href="user_update_profile.php" class="btn btn-success btn-sm w-100 rounded-3 shadow-sm fw-bold py-2">
                                    <i class="bi bi-pencil-square me-1"></i> Update Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="card-title fw-bold border-bottom pb-2 mb-3">My Environment Profile</h5>
                        <?php if($profile && !empty($profile['houseType'])): ?>
                            <div class="profile-details">
                                <p class="card-text mb-2"><i class="bi bi-house-door-fill text-success me-2"></i> <strong>Home:</strong> <span class="text-muted"><?php echo htmlspecialchars($profile['houseType']); ?></span></p>
                                <p class="card-text mb-2"><i class="bi bi-brightness-high-fill text-warning me-2"></i> <strong>Light:</strong> <span class="text-muted"><?php echo htmlspecialchars($profile['lightCondition']); ?></span></p>
                                <p class="card-text mb-2"><i class="bi bi-person-badge-fill text-primary me-2"></i> <strong>Skill:</strong> <span class="text-muted"><?php echo htmlspecialchars($profile['gardeningLevel']); ?></span></p>
                                <p class="card-text mb-2"><i class="bi bi-arrows-fullscreen text-info me-2"></i> <strong>Space:</strong> <span class="text-muted"><?php echo htmlspecialchars($profile['availableSpace']); ?></span></p>
                                <p class="card-text mb-2"><i class="bi bi-speedometer text-success me-2"></i> <strong>Growth Speed:</strong> <span class="text-muted"><?php echo htmlspecialchars($profile['growthRate'] ?? 'Not Set'); ?></span></p>
                                <p class="card-text mb-2"><i class="bi bi-flower1 text-danger me-2"></i> <strong>Prefers:</strong> <span class="text-muted"><?php echo htmlspecialchars($profile['plantPreference']); ?></span></p>
                                <p class="card-text mb-2"><i class="bi bi-droplet-fill text-primary me-2"></i> <strong>Watering:</strong> <span class="text-muted"><?php echo htmlspecialchars($profile['waterFreq']); ?></span></p>
                                <p class="card-text mb-2"><i class="bi bi-cloud-fog text-info me-2"></i> <strong>Humidity:</strong> <span class="text-muted"><?php echo htmlspecialchars($profile['humidityNeed'] ?? 'Not Set'); ?></span></p>
                                <p class="card-text mb-3"><i class="bi bi-rulers text-secondary me-2"></i> <strong>Max Height:</strong> <span class="text-muted"><?php echo htmlspecialchars($profile['matureHeight'] ?? '0'); ?> cm</span></p>
                                <a href="user_recommendation_form.php" class="btn btn-outline-success btn-sm w-100 rounded-pill">Update Environment Profile</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-clipboard-x display-4 text-muted opacity-50"></i>
                                <p class="text-dark fw-bold mt-3 mb-1">No Existing Environmental Profile</p>
                                <p class="text-muted small px-2 mb-4">Complete your matching preferences to get fully customized plant suggestions.</p>
                                <a href="user_recommendation_form.php" class="btn btn-success btn-sm px-4 rounded-pill shadow-sm">Setup Profile</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card border-0 shadow-sm text-white rounded-3" style="background: linear-gradient(135deg, #198754 0%, #104206 100%);">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-2 text-warning">
                            <i class="bi bi-lightbulb-fill fs-5 me-2"></i>
                            <h6 class="mb-0 fw-bold text-uppercase tracking-wider" style="font-size: 0.8rem;">Daily Care Tip</h6>
                        </div>
                        <p class="card-text small lh-base mb-0 opacity-90">
                            "<?php echo $random_tip; ?>"
                        </p>
                    </div>
                </div>

            </div> 
            
            <div class="col-md-8">
                
                <!-- RECENT RECOMMENDATIONS CONTAINER (Always Visible) -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="section-title mb-0">Recent Recommendations</h4>
                    <?php if (!empty($recommended_plants_list)): ?>
                        <a href="user_view_recommendations.php" class="btn btn-sm btn-link text-success fw-bold text-decoration-none">View All <i class="bi bi-arrow-right"></i></a>
                    <?php endif; ?>
                </div>
                
                <div class="table-responsive bg-white p-4 rounded shadow-sm mb-5">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Plant ID</th>
                                <th>Plant Details</th>
                                <th>Match Compatibility</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recommended_plants_list)): ?>
                                <?php foreach($recommended_plants_list as $row): ?>
                                    <tr>
                                        <td><span class="text-muted small">#<?php echo $row['ID']; ?></span></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <?php 
                                                    $rec_img = (!empty($row['Image_URL'])) ? $row['Image_URL'] : 'https://images.unsplash.com/photo-1545241047-6083a3684587?w=80';
                                                ?>
                                                <img src="<?php echo $rec_img; ?>" class="dash-thumb" alt="plant image">
                                                <div>
                                                    <div class="fw-bold text-dark italic" style="font-size: 0.9rem;">
                                                        <em><?php echo htmlspecialchars($row['Scientific_Name'] ?? 'Species'); ?></em>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars(explode(',', $row['Common_Name'] ?? 'Plant')[0]); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                                $score = $row['calculated_match_score'];
                                                $badgeColor = ($score >= 85) ? 'bg-success' : (($score >= 65) ? 'bg-warning text-dark' : 'bg-danger');
                                            ?>
                                            <span class="badge <?php echo $badgeColor; ?> px-2 py-1"><?php echo $score; ?>% Match</span>
                                        </td>
                                        <td>
                                            <a href="user_plant_details.php?id=<?php echo $row['ID']; ?>" class="btn btn-sm btn-outline-success rounded-pill px-3">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted small">
                                        <i class="bi bi-chat-square-text d-block fs-3 mb-2 opacity-50"></i>
                                        <p class="mb-3">You have no custom recommendations yet. Please submit the match form.</p>
                                        <a href="user_recommendation_form.php" class="btn btn-sm btn-success rounded-pill px-4 fw-bold shadow-sm">
                                            <i class="bi bi-file-earmark-text me-1"></i> Complete Match Form
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="section-title mb-0">My Favorites</h4>
                    <?php if ($fav_count > 0): ?>
                        <a href="user_favorites.php" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold">
                            View All <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="row g-3 mb-5">
                    <?php if ($fav_count > 0): ?>
                        <?php mysqli_data_seek($fav_result, 0); ?>
                        <?php while($fav = mysqli_fetch_assoc($fav_result)): ?>
                        <div class="col-lg-6">
                            <div class="card mini-card shadow-sm">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="plant-id-badge">ID: <?php echo $fav['plantID']; ?></span>
                                        <i class="bi bi-heart-fill text-danger"></i>
                                    </div>
                                    <h6 class="fw-bold mt-2 mb-0"><?php echo htmlspecialchars(explode(',', $fav['Common_Name'])[0]); ?></h6>
                                    <p class="text-muted small fst-italic mb-2"><?php echo htmlspecialchars($fav['Scientific_Name']); ?></p>
                                    <a href="user_plant_details.php?id=<?php echo $fav['plantID']; ?>" class="btn btn-sm btn-link text-success p-0 text-decoration-none fw-bold">View Details</a>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12 text-muted small ps-3 mb-3">No favorite plants yet.</div>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="section-title mb-0">My Reviews & Feedback</h4>
                    <?php if ($feedback_count > 0): ?>
                        <a href="user_feedbacklist.php" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold">
                            View All <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="table-responsive bg-white p-4 rounded shadow-sm mb-5">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Plant</th>
                                <th>My Comment</th>
                                <th class="text-center" style="width: 160px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($feedback_count > 0): ?>
                                <?php while($fb = mysqli_fetch_assoc($feedback_res)): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <?php 
                                                    $img = (!empty($fb['Image_URL'])) ? $fb['Image_URL'] : 'https://images.unsplash.com/photo-1545241047-6083a3684587?w=80';
                                                ?>
                                                <img src="<?php echo $img; ?>" class="dash-thumb" alt="plant image">
                                                <div>
                                                    <span class="plant-id-badge d-inline-block mb-1">ID: <?php echo $fb['plantid']; ?></span>
                                                    <div class="fw-bold text-dark italic" style="font-size: 0.9rem;">
                                                        <em><?php echo htmlspecialchars($fb['Scientific_Name'] ?? 'Species'); ?></em>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars(explode(',', $fb['Common_Name'] ?? 'Plant')[0]); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <p class="mb-0 text-secondary small lh-sm" style="max-width: 320px; word-wrap: break-word;">
                                                <?php echo htmlspecialchars($fb['comment'] ?? 'No comment provided.'); ?>
                                            </p>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-inline-flex gap-1">
                                                <a href="user_plant_details.php?id=<?php echo urlencode($fb['plantid']); ?>" class="btn btn-sm btn-success rounded-pill px-3 fw-bold">View</a>
                                                <a href="user_dashboard.php?action=delete&plantid=<?php echo urlencode($fb['plantid']); ?>" 
                                                   class="btn btn-sm btn-danger rounded-pill px-3 fw-bold"
                                                   onclick="return confirm('Are you sure you want to delete this review?');">
                                                    Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted small">
                                        <i class="bi bi-chat-square-text d-block fs-3 mb-2 opacity-50"></i>
                                        You have not submitted any reviews or feedback yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div> 
        </div> 
    </div>
</main>

<?php include('footer.php'); ?>