<?php 
// 1. Enable Output Buffering to prevent "Headers Already Sent" redirect errors
ob_start();

include('private_header.php'); 
include('db.php'); 

// Ensure session exists for checking ownership limits safely
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 0; // Fallback token if missing
}
$current_logged_in_user = (int)$_SESSION['user_id'];

// 2. Safeguard Plant ID extraction from both GET and POST requests
$plantID = '';
if (isset($_GET['id'])) {
    $plantID = mysqli_real_escape_string($conn, $_GET['id']);
} elseif (isset($_POST['plantID'])) {
    $plantID = mysqli_real_escape_string($conn, $_POST['plantID']);
}

// ========================================================
// INLINE ACTIONS: HANDLED BEFORE CONTENT RENDERS (PRG PATTERN)
// ========================================================

// ACTION A: INLINE FAVORITE / UNFAVORITE CONTROLLER
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && ($_POST['action'] === 'favorite' || $_POST['action'] === 'unfavorite')) {
    if ($current_logged_in_user > 0 && $plantID !== '') {
        if ($_POST['action'] === 'favorite') {
            // Prevent duplicate entries by checking if record exists first (Table: favorite)
            $check_dup = "SELECT * FROM favorite WHERE userID = $current_logged_in_user AND plantID = '$plantID'";
            $dup_res = mysqli_query($conn, $check_dup);
            
            if (mysqli_num_rows($dup_res) == 0) {
                $fav_insert = "INSERT INTO favorite (userID, plantID) VALUES ($current_logged_in_user, '$plantID')";
                mysqli_query($conn, $fav_insert);
            }
            $_SESSION['flash_msg'] = 'added';
        } elseif ($_POST['action'] === 'unfavorite') {
            // Updated to use singular table name 'favorite'
            $fav_delete = "DELETE FROM favorite WHERE userID = $current_logged_in_user AND plantID = '$plantID'";
            mysqli_query($conn, $fav_delete);
            $_SESSION['flash_msg'] = 'unfavorited';
        }
    }
    header("Location: " . $_SERVER['SCRIPT_NAME'] . "?id=" . urlencode($plantID));
    exit();
}

// ACTION B: INLINE DELETE FEEDBACK CONTROLLER
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $feedbackID = isset($_POST['feedbackID']) ? (int)$_POST['feedbackID'] : 0;
    if ($feedbackID > 0 && $current_logged_in_user > 0) {
        $secure_delete_query = "DELETE FROM feedback WHERE feedbackID = $feedbackID AND userID = $current_logged_in_user";
        mysqli_query($conn, $secure_delete_query);
        
        $_SESSION['flash_msg'] = 'feedback_deleted';
        header("Location: " . $_SERVER['SCRIPT_NAME'] . "?id=" . urlencode($plantID));
        exit();
    }
}

// ACTION C: INLINE EDIT FEEDBACK CONTROLLER
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'update') {
    $feedbackID = (int)$_POST['feedbackID'];
    $rating = (int)$_POST['rating'];
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);

    if ($feedbackID > 0 && $current_logged_in_user > 0) {
        $secure_update_query = "UPDATE feedback 
                                SET rating = $rating, comment = '$comment', feedbackDate = NOW() 
                                WHERE feedbackID = $feedbackID AND userID = $current_logged_in_user";
        mysqli_query($conn, $secure_update_query);
        
        $_SESSION['flash_msg'] = 'feedback_updated';
        header("Location: " . $_SERVER['SCRIPT_NAME'] . "?id=" . urlencode($plantID));
        exit();
    }
}

// ACTION D: INLINE NEW FEEDBACK CONTROLLER
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'insert') {
    $rating = (int)$_POST['rating'];
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);

    if ($plantID !== '' && $current_logged_in_user > 0) {
        $secure_insert_query = "INSERT INTO feedback (plantID, userID, rating, comment, feedbackDate) 
                                VALUES ('$plantID', $current_logged_in_user, $rating, '$comment', NOW())";
        mysqli_query($conn, $secure_insert_query);
        
        $_SESSION['flash_msg'] = 'feedback_success';
        header("Location: " . $_SERVER['SCRIPT_NAME'] . "?id=" . urlencode($plantID));
        exit();
    }
}
// ========================================================

$plant = null;
if ($plantID !== '') {
    $query = "SELECT * FROM `plantmatch_ultimate_complete` WHERE `ID` = '$plantID'";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $plant = mysqli_fetch_assoc($result);
    }
}

if (!$plant) {
    echo "<div class='container mt-5'><div class='alert alert-danger shadow-sm'>Plant not found in dataset.</div></div>";
    include('footer.php');
    exit;
}

// CHECK IF THIS PLANT IS CURRENTLY SAVED BY USER (Table: favorite)
$is_favorited = false;
if ($current_logged_in_user > 0 && $plantID !== '') {
    $check_fav = "SELECT * FROM favorite WHERE userID = $current_logged_in_user AND plantID = '$plantID'";
    $fav_res = mysqli_query($conn, $check_fav);
    if ($fav_res && mysqli_num_rows($fav_res) > 0) {
        $is_favorited = true;
    }
}

// ========================================================
// TRANSLATION LAYER: NUMERICAL ML DATA -> REAL WORLD TEXT
// ========================================================

// 1. Resolve Categories from One-Hot Columns
$detected_category = "General Plant";
$category_options = [
    'Categories_Climbers & Vines' => 'Climbers & Vines',
    'Categories_Edibles & Herbs'  => 'Edibles & Herbs',
    'Categories_Ferns'            => 'Ferns',
    'Categories_Foliage Plants'   => 'Foliage Plants',
    'Categories_Palms'            => 'Palms',
    'Categories_Shrubs'           => 'Shrubs',
    'Categories_Succulents & Cacti'=> 'Succulents & Cacti',
    'Categories_Trees'            => 'Trees'
];
foreach ($category_options as $db_col => $display_text) {
    if (isset($plant[$db_col]) && (int)$plant[$db_col] === 1) {
        $detected_category = $display_text;
        break;
    }
}

// 2. Resolve Recommended House Type from One-Hot Columns
$detected_house = "Any Environment";
$house_options = [
    'House_Type_High-rise (Apartment/Flat)'           => 'High-rise Apartment',
    'House_Type_Landed (Terrace/Semi-D/Bungalow)'     => 'Landed Property',
    'House_Type_Tradisional/Spacious (Village House)' => 'Spacious Village Layout'
];
foreach ($house_options as $db_col => $display_text) {
    if (isset($plant[$db_col]) && (int)$plant[$db_col] === 1) {
        $detected_house = $display_text;
        break;
    }
}

// 3. Map Continuous Float Values to Descriptive Labels
function map_level_label($val) {
    $f = (float)$val;
    if ($f <= 0.2) return "Beginner Friendly";
    if ($f <= 0.6) return "Intermediate Care";
    return "Expert Needed";
}

function map_space_label($val) {
    $f = (float)$val;
    if ($f <= 0.2) return "Minimal (Desktop/Shelf)";
    if ($f <= 0.6) return "Moderate (Balcony/Room)";
    return "Spacious Yard Required";
}

function map_light_label($val) {
    $f = (float)$val;
    if ($f <= 0.2) return "Low Light / Deep Shade";
    if ($f <= 0.6) return "Partial Sun / Bright Indoor";
    return "Full Direct Sun";
}

function map_water_label($val) {
    $f = (float)$val;
    if ($f <= 0.2) return "Low (Drought Tolerant)";
    if ($f <= 0.6) return "Moderate (Weekly)";
    return "High (Consistently Wet)";
}

function map_growth_label($val) {
    $f = (float)$val;
    if ($f <= 0.2) return "Slow & Steady";
    if ($f <= 0.6) return "Moderate Growth Rate";
    return "Rapid Aggressive Grower";
}

function map_humidity_label($val) {
    $f = (float)$val;
    if ($f <= 0.2) return "Arid / Low Humidity";
    if ($f <= 0.6) return "Average Home Ambient";
    return "Tropical / High Humidity";
}

$display_gardening   = map_level_label($plant['Gardening_Level'] ?? 0);
$display_space       = map_space_label($plant['Space_Required'] ?? 0);
$display_light       = map_light_label($plant['Light_Condition'] ?? 0);
$display_water       = map_water_label($plant['Water_Frequency'] ?? 0);
$display_growth      = map_growth_label($plant['Growth_Rate'] ?? 0);
$display_humidity    = map_humidity_label($plant['Humidity_Need'] ?? 0);

if (isset($plant['Mature_Height_cm'])) {
    $normalized_height = (float)$plant['Mature_Height_cm'];
    $real_height_cm = $normalized_height * 9000;
    if ($real_height_cm >= 100) {
        $display_height = round($real_height_cm / 100, 1) . " m";
    } else {
        $display_height = ($real_height_cm < 5) ? "5 cm" : round($real_height_cm, 0) . " cm";
    }
} else {
    $display_height = "N/A";
}

$names = explode(',', $plant['Common_Name'] ?? 'Unnamed Plant');
$primary_name = trim($names[0]);
$other_names = (count($names) > 1) ? implode(', ', array_map('trim', array_slice($names, 1))) : null;

// FETCH FEEDBACK FROM OTHER USERS (Fixed syntax error here: changed ORDER FROM to ORDER BY)
$feedback_query = "SELECT f.*, u.name 
                   FROM feedback f 
                   LEFT JOIN users u ON f.userID = u.userID 
                   WHERE f.plantID = '$plantID' 
                   ORDER BY f.feedbackDate DESC";
$feedback_result = mysqli_query($conn, $feedback_query);

// Read data out safely into string for our Javascript browser alert popups
$alert_msg = '';
if (isset($_SESSION['flash_msg'])) {
    $alert_msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
} elseif (isset($_GET['msg'])) {
    $alert_msg = $_GET['msg'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($primary_name); ?> - Detail</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .sticky-image-container { 
            position: -webkit-sticky; 
            position: sticky; 
            top: 100px; 
            z-index: 1; 
        }
        .detail-card { border-radius: 25px; border: none; background: #ffffff; overflow: hidden; }
        .spec-icon-box { 
            width: 65px; height: 65px; display: flex; align-items: center; 
            justify-content: center; border-radius: 20px; margin-bottom: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .scin-title { font-family: 'Georgia', serif; font-style: italic; color: #104206; font-size: 2.4rem; line-height: 1.2; }
        .common-title { font-weight: 700; color: #444; letter-spacing: -0.5px; }
        .description-box { 
            background: #f9fdf9; 
            border-left: 6px solid #104206; 
            border-radius: 15px; 
            margin-top: 25px;
        }
        .category-label {
            color: #104206; font-weight: 800; text-transform: uppercase;
            font-size: 0.8rem; letter-spacing: 1.5px; display: block; margin-bottom: 8px;
        }
        .spec-label { font-size: 0.68rem; font-weight: 800; color: #888; text-transform: uppercase; display: block; margin-bottom: 3px; }
        .spec-value { font-weight: 700; color: #222; font-size: 0.95rem; }
        .comment-card { border-radius: 20px; border: none; background: #f8f9fa; margin-bottom: 15px; }
        .user-avatar { width: 45px; height: 45px; background: #104206; color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold; }
        .rating-stars { color: #ffc107; font-size: 0.85rem; }
        .action-icon-btn { border: none; background: transparent; padding: 2px 6px; font-size: 0.95rem; transition: color 0.2s; }
        .action-icon-btn.edit { color: #0d6efd; }
        .action-icon-btn.edit:hover { color: #0a58ca; }
        .action-icon-btn.delete { color: #dc3545; border: none; background: transparent; }
        .action-icon-btn.delete:hover { color: #b02a37; }
    </style>
</head>
<body>

<div class="container mt-5 mb-5">
    <div class="row g-5">
        <div class="col-lg-5">
            <div class="sticky-image-container">
                <div class="detail-card shadow-lg text-center" style="background: #f8f9fa;">
                    <?php 
                    $imageSource = (!empty($plant['Image_URL'])) ? $plant['Image_URL'] : 'https://images.unsplash.com/photo-1545241047-6083a3684587?w=800'; 
                    ?>
                    <img src="<?php echo $imageSource; ?>" 
                         class="img-fluid w-100" 
                         alt="<?php echo htmlspecialchars($primary_name); ?>" 
                         style="object-fit: cover; max-height: 500px; width: 100%;">
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <span class="category-label"><i class="bi bi-tag-fill me-1"></i> <?php echo htmlspecialchars($detected_category); ?></span>

            <h1 class="scin-title mb-1"><?php echo htmlspecialchars($plant['Scientific_Name'] ?? 'Unknown Species'); ?></h1>
            <h2 class="common-title h3 mb-2"><?php echo htmlspecialchars($primary_name); ?></h2>
            
            <div class="mb-3">
                <span class="badge bg-dark rounded-pill px-3 py-2">Plant ID: #<?php echo $plant['ID']; ?></span>
            </div>

            <?php if ($other_names): ?>
                <p class="text-muted mb-4"><i class="bi bi-info-circle me-1"></i> <strong>Other Names:</strong> <?php echo htmlspecialchars($other_names); ?></p>
            <?php endif; ?>

            <div class="description-box p-4 shadow-sm mb-5">
                <h5 class="fw-bold text-success"><i class="bi bi-stars"></i> Plant AI Overview</h5>
                <p class="mb-0 text-dark" style="line-height: 1.8; font-size: 1.05rem;">
                    The <strong><?php echo htmlspecialchars($primary_name); ?></strong> (<em><?php echo htmlspecialchars($plant['Scientific_Name'] ?? 'Species'); ?></em>) is a premium catalog choice within our <strong><?php echo htmlspecialchars($detected_category); ?></strong> classification. 
                    Perfect for <strong><?php echo htmlspecialchars($detected_house); ?></strong> residential setups, it is optimized to perform its best under <strong><?php echo htmlspecialchars($display_light); ?></strong> environments. 
                    This plant shows a <strong><?php echo htmlspecialchars($display_growth); ?></strong> behavior sequence and prefers a stable <strong><?php echo htmlspecialchars($display_water); ?></strong> irrigation track. 
                    It is rated perfectly for <strong><?php echo htmlspecialchars($display_gardening); ?></strong> tiers, demanding a <strong><?php echo htmlspecialchars($display_space); ?></strong> footprint while sustaining growth thresholds up to <strong><?php echo $display_height; ?></strong> at <strong><?php echo htmlspecialchars($display_humidity); ?></strong> values.
                </p>
            </div>

            <h5 class="fw-bold mb-4" style="color: #104206;">Plant Specifications Matrix</h5>
            
            <div class="row g-4 mb-4">
                <div class="col-md-4 col-6 text-center">
                    <div class="spec-icon-box bg-secondary bg-opacity-10 mx-auto">
                        <i class="bi bi-house-door-fill text-secondary fs-3"></i>
                    </div>
                    <span class="spec-label">House Context</span>
                    <span class="spec-value"><?php echo htmlspecialchars($detected_house); ?></span>
                </div>
                <div class="col-md-4 col-6 text-center">
                    <div class="spec-icon-box bg-primary bg-opacity-10 mx-auto">
                        <i class="bi bi-droplet-fill text-primary fs-3"></i>
                    </div>
                    <span class="spec-label">Water Requirement</span>
                    <span class="spec-value"><?php echo htmlspecialchars($display_water); ?></span>
                </div>
                <div class="col-md-4 col-6 text-center">
                    <div class="spec-icon-box bg-warning bg-opacity-10 mx-auto">
                        <i class="bi bi-brightness-high-fill text-warning fs-3"></i>
                    </div>
                    <span class="spec-label">Lighting Need</span>
                    <span class="spec-value"><?php echo htmlspecialchars($display_light); ?></span>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4 col-6 text-center">
                    <div class="spec-icon-box bg-success bg-opacity-10 mx-auto">
                        <i class="bi bi-mortarboard-fill text-success fs-3"></i>
                    </div>
                    <span class="spec-label">Skill Tier</span>
                    <span class="spec-value"><?php echo htmlspecialchars($display_gardening); ?></span>
                </div>
                <div class="col-md-4 col-6 text-center">
                    <div class="spec-icon-box bg-danger bg-opacity-10 mx-auto">
                        <i class="bi bi-speedometer2 text-danger fs-3"></i>
                    </div>
                    <span class="spec-label">Development Rate</span>
                    <span class="spec-value"><?php echo htmlspecialchars($display_growth); ?></span>
                </div>
                <div class="col-md-4 col-6 text-center">
                    <div class="spec-icon-box bg-info bg-opacity-10 mx-auto">
                        <i class="bi bi-arrows-fullscreen text-info fs-3"></i>
                    </div>
                    <span class="spec-label">Required Space</span>
                    <span class="spec-value"><?php echo htmlspecialchars($display_space); ?></span>
                </div>
            </div>

            <div class="row g-4 justify-content-center">
                <div class="col-md-4 col-6 text-center">
                    <div class="spec-icon-box mx-auto" style="background-color: #e0f2f1;">
                        <i class="bi bi-cloud-haze2-fill fs-3" style="color: #009688;"></i>
                    </div>
                    <span class="spec-label">Air Humidity</span>
                    <span class="spec-value"><?php echo htmlspecialchars($display_humidity); ?></span>
                </div>
                <div class="col-md-4 col-6 text-center">
                    <div class="spec-icon-box bg-dark bg-opacity-10 mx-auto">
                        <i class="bi bi-rulers text-dark fs-3"></i>
                    </div>
                    <span class="spec-label">Est. Peak Height</span>
                    <span class="spec-value"><?php echo $display_height; ?></span>
                </div>
            </div>

            <div class="mt-5 pt-4 border-top">
                <div class="d-flex flex-wrap gap-3 justify-content-end align-items-center"> 
                    
                    <form action="" method="POST" style="display:inline; margin:0; padding:0;">
                        <input type="hidden" name="plantID" value="<?php echo $plant['ID']; ?>">
                        <?php if ($is_favorited): ?>
                            <input type="hidden" name="action" value="unfavorite">
                            <button type="submit" class="btn btn-dark btn-lg px-4 rounded-pill shadow-sm fw-bold">
                                <i class="bi bi-heartbreak-fill me-2 text-danger"></i>Unfavorite
                            </button>
                        <?php else: ?>
                            <input type="hidden" name="action" value="favorite">
                            <button type="submit" class="btn btn-outline-danger btn-lg px-4 rounded-pill shadow-sm fw-bold">
                                <i class="bi bi-heart-fill me-2"></i>Favorite
                            </button>
                        <?php endif; ?>
                    </form>

                    <button class="btn btn-light btn-lg px-4 rounded-pill shadow-sm text-primary fw-bold" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                        <i class="bi bi-chat-left-dots-fill me-2"></i>Feedback
                    </button>
                </div>
            </div>

            <div class="mt-5 pt-5">
                <h4 class="fw-bold mb-4" style="color: #104206;"><i class="bi bi-people-fill me-2"></i>Community Feedback</h4>
                
                <?php if (mysqli_num_rows($feedback_result) > 0): ?>
                    <div class="feedback-list">
                        <?php while($row = mysqli_fetch_assoc($feedback_result)): ?>
                            <div class="card comment-card p-3 shadow-sm border-0">
                                <div class="d-flex align-items-start">
                                    <div class="user-avatar me-3">
                                        <?php echo strtoupper(substr($row['name'] ?? 'U', 0, 1)); ?>
                                    </div>
                                    <div class="w-100">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($row['name'] ?? 'Anonymous User'); ?></h6>
                                            <div class="d-flex align-items-center gap-2">
                                                <small class="text-muted" style="font-size: 0.75rem;">
                                                    <?php echo date('d M Y', strtotime($row['feedbackDate'])); ?>
                                                </small>
                                                
                                                <?php if ((int)$row['userID'] === $current_logged_in_user): ?>
                                                    <div class="ms-2 d-flex gap-1 align-items-center">
                                                        <button type="button" 
                                                                class="action-icon-btn edit" 
                                                                title="Edit review"
                                                                data-id="<?php echo $row['feedbackID']; ?>"
                                                                data-rating="<?php echo $row['rating']; ?>"
                                                                data-comment="<?php echo htmlspecialchars($row['comment']); ?>"
                                                                onclick="launchEditModal(this)">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </button>
                                                        
                                                        <form action="" method="POST" style="display:inline; margin:0; padding:0;" onsubmit="return confirm('Are you sure you want to delete your feedback? This action cannot be reversed.');">
                                                            <input type="hidden" name="plantID" value="<?php echo $plant['ID']; ?>">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="feedbackID" value="<?php echo $row['feedbackID']; ?>">
                                                            <button type="submit" class="action-icon-btn delete" title="Delete review">
                                                                 <i class="bi bi-trash3-fill"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="rating-stars mb-2">
                                            <?php for($i=1; $i<=5; $i++) echo ($i <= $row['rating']) ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>'; ?>
                                        </div>
                                        <p class="text-dark small mb-0"><?php echo nl2br(htmlspecialchars($row['comment'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 bg-light rounded-4">
                        <i class="bi bi-chat-dots text-muted fs-1"></i>
                        <p class="text-muted mt-2">No feedback yet. Be the first to share your thoughts!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="feedbackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
            <form action="" method="POST">
                <div class="modal-header border-0 mt-2">
                    <h5 class="modal-title fw-bold">Plant Feedback</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4">
                    <input type="hidden" name="action" value="insert">
                    <input type="hidden" name="plantID" value="<?php echo $plant['ID']; ?>">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small text-uppercase">Rate Experience</label>
                        <select name="rating" class="form-select border-0 bg-light rounded-3 py-2">
                            <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                            <option value="4">⭐⭐⭐⭐ Good</option>
                            <option value="3">⭐⭐⭐ Average</option>
                            <option value="2">⭐⭐ Poor</option>
                            <option value="1">⭐ Very Poor</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small text-uppercase">Your Comment</label>
                        <textarea name="comment" class="form-control border-0 bg-light rounded-3" rows="4" required placeholder="Tell us about your plant growth..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 mb-3 px-4">
                    <button type="submit" class="btn btn-success w-100 rounded-pill py-2 fw-bold">Submit Feedback</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editFeedbackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
            <form action="" method="POST">
                <div class="modal-header border-0 mt-2">
                    <h5 class="modal-title fw-bold">Modify Your Feedback</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="feedbackID" id="editFeedbackID">
                    <input type="hidden" name="plantID" value="<?php echo $plant['ID']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small text-uppercase">Update Rating</label>
                        <select name="rating" id="editFeedbackRating" class="form-select border-0 bg-light rounded-3 py-2">
                            <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                            <option value="4">⭐⭐⭐⭐ Good</option>
                            <option value="3">⭐⭐⭐ Average</option>
                            <option value="2">⭐⭐ Poor</option>
                            <option value="1">⭐ Very Poor</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small text-uppercase">Edit Comment</label>
                        <textarea name="comment" id="editFeedbackComment" class="form-control border-0 bg-light rounded-3" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 mb-3 px-4">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function launchEditModal(buttonElement) {
    const fID = buttonElement.getAttribute('data-id');
    const fRating = buttonElement.getAttribute('data-rating');
    const fComment = buttonElement.getAttribute('data-comment');
    
    document.getElementById('editFeedbackID').value = fID;
    document.getElementById('editFeedbackRating').value = fRating;
    document.getElementById('editFeedbackComment').value = fComment;
    
    const editModal = new bootstrap.Modal(document.getElementById('editFeedbackModal'));
    editModal.show();
}

// ==========================================
// 💻 NATIVE BROWSER WINDOW POPUP ALERT CONTROLLER
// ==========================================
document.addEventListener("DOMContentLoaded", function() {
    const alertCode = "<?php echo $alert_msg; ?>";
    
    if (alertCode !== "") {
        if (alertCode === 'saved') {
            alert("Saved to your garden successfully!");
        } else if (alertCode === 'added') {
            alert("Added to favorites!");
        } else if (alertCode === 'unfavorited') {
            alert("Removed from favorites.");
        } else if (alertCode === 'feedback_success') {
            alert("Thank you for your feedback!");
        } else if (alertCode === 'feedback_deleted') {
            alert("Feedback removed completely.");
        } else if (alertCode === 'feedback_updated') {
            alert("Your review has been successfully modified!");
        }
    }
});

// =========================================================================
// 🛡️ ADVANCED BROWSER HISTORY GUARD (FETCH WORKER FOR POST FORMS)
// This completely stops browser stack inflation on multiple actions!
// =========================================================================
document.addEventListener("submit", function(e) {
    if (e.defaultPrevented) return;
    
    const form = e.target;
    if (form.method && form.method.toLowerCase() === 'post') {
        e.preventDefault(); // Lock native navigation thread
        
        const buttons = form.querySelectorAll('button[type="submit"]');
        buttons.forEach(btn => btn.disabled = true);
        
        fetch(window.location.href, {
            method: 'POST',
            body: new FormData(form)
        })
        .then(() => {
            // Reloads current URL entry in place without appending history items
            window.location.reload();
        })
        .catch(err => {
            console.error("Submission error:", err);
            window.location.reload();
        });
    }
});

if (window.history.replaceState) {
    const cleanUrl = new URL(window.location.href);
    if (cleanUrl.searchParams.has('msg') || cleanUrl.searchParams.has('action') || cleanUrl.searchParams.has('feedbackID')) {
        cleanUrl.searchParams.delete('msg');
        cleanUrl.searchParams.delete('action');
        cleanUrl.searchParams.delete('feedbackID');
        window.history.replaceState({}, document.title, cleanUrl.toString());
    }
}

window.addEventListener('pageshow', function (event) {
    if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
        window.location.reload();
    }
});
</script>
</body>
</html>
<?php
ob_end_flush();
?>