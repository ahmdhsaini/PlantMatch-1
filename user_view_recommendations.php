<?php 
include('db.php');

// =========================================================================
// AJAX Controller Block (Handles adding/removing items to favorites or saving to garden)
// =========================================================================
if (isset($_GET['ajax_action'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = 0; 
    }
    $userID = $_SESSION['user_id'];
    $pID = isset($_GET['id']) ? trim($_GET['id']) : '';
    $action = $_GET['ajax_action'];

    if ($action === 'favorite' && $userID > 0 && $pID !== '') {
        $check_query = "SELECT * FROM favorite WHERE userID = ? AND plantID = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "is", $userID, $pID);
            mysqli_stmt_execute($stmt);
            $check_result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($check_result) == 0) {
                mysqli_stmt_close($stmt);
                $insert_query = "INSERT INTO favorite (userID, plantID, dateSaved) VALUES (?, ?, CURDATE())";
                $stmt_ins = mysqli_prepare($conn, $insert_query);
                if ($stmt_ins) {
                    mysqli_stmt_bind_param($stmt_ins, "is", $userID, $pID);
                    mysqli_stmt_execute($stmt_ins);
                    mysqli_stmt_close($stmt_ins);
                }
                echo "Added to Favorites!";
            } else {
                mysqli_stmt_close($stmt);
                echo "Already in Favorites.";
            }
        }
    } 

    if ($action === 'unfavorite' && $userID > 0 && $pID !== '') {
        $delete_query = "DELETE FROM favorite WHERE userID = ? AND plantID = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "is", $userID, $pID);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            echo "Removed from Favorites!";
        } else {
            echo "Error updating status.";
        }
    }
    exit; 
}

// Regular Page Load Block
include('private_header.php'); 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$userID = $_SESSION['user_id'];

// Fetch list of already favorited plant IDs for this user
$favorited_ids = [];
$fav_check_sql = "SELECT plantID FROM favorite WHERE userID = ?";
$fav_check_stmt = mysqli_prepare($conn, $fav_check_sql);
if ($fav_check_stmt) {
    mysqli_stmt_bind_param($fav_check_stmt, "i", $userID);
    mysqli_stmt_execute($fav_check_stmt);
    $fav_check_res = mysqli_stmt_get_result($fav_check_stmt);
    while ($fav_row = mysqli_fetch_assoc($fav_check_res)) {
        $favorited_ids[] = trim($fav_row['plantID']);
    }
    mysqli_stmt_close($fav_check_stmt);
}

// Helper label mappers for descriptive display grids
if (!function_exists('map_light_label')) {
    function map_light_label($val) {
        $f = (float)$val;
        if ($f <= 0.2) return "Low Light / Shade";
        if ($f <= 0.7) return "Partial Sun / Indoor";
        return "Full Direct Sun";
    }
}
if (!function_exists('map_water_label')) {
    function map_water_label($val) {
        $f = (float)$val;
        if ($f <= 0.2) return "Low (Drought Tol.)";
        if ($f <= 0.7) return "Moderate (Weekly)";
        return "High (Consistently Wet)";
    }
}

// Helper to normalize multi-format user inputs to standard 0.0 - 0.5 - 1.0 floats
if (!function_exists('map_input_to_val')) {
    function map_input_to_val($str) {
        $str = strtolower($str);
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
// MULTI-FEATURE MATCHING CORE ALGORITHM
// =========================================================================
$recommended_plants_list = [];

// Extract preference choices submitted by this specific user
$pref_sql = "SELECT houseType, gardeningLevel, availableSpace, lightCondition, growthRate, plantPreference, waterFreq, humidityNeed, matureHeight FROM preference WHERE userID = ? LIMIT 1";
$pref_stmt = mysqli_prepare($conn, $pref_sql);

if ($pref_stmt) {
    mysqli_stmt_bind_param($pref_stmt, "i", $userID);
    mysqli_stmt_execute($pref_stmt);
    $pref_res = mysqli_stmt_get_result($pref_stmt);
    $user_input = mysqli_fetch_assoc($pref_res);
    mysqli_stmt_close($pref_stmt);
    
    if ($user_input) {
        // Convert input form factors to computational scales
        $u_light_val    = map_input_to_val($user_input['lightCondition'] ?? '');
        $u_water_val    = map_input_to_val($user_input['waterFreq'] ?? '');
        $u_skill_val    = map_input_to_val($user_input['gardeningLevel'] ?? '');
        $u_space_val    = map_input_to_val($user_input['availableSpace'] ?? '');
        $u_growth_val   = map_input_to_val($user_input['growthRate'] ?? '');
        $u_humidity_val = map_input_to_val($user_input['humidityNeed'] ?? '');

        // Map categorical text selection to specific CSV column headers
        $user_pref = strtolower($user_input['plantPreference'] ?? '');
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
        } elseif (strpos($user_pref, 'succulent') !== false || strpos($user_pref, 'cacti') !== false || strpos($user_pref, 'cactus') !== false) {
            $category_col = 'Categories_Succulents & Cacti';
        } elseif (strpos($user_pref, 'tree') !== false) {
            $category_col = 'Categories_Trees';
        }

        // Map house type text attributes
        $user_house = strtolower($user_input['houseType'] ?? '');
        $house_col = '';
        if (strpos($user_house, 'high') !== false || strpos($user_house, 'apartment') !== false || strpos($user_house, 'flat') !== false) {
            $house_col = 'House_Type_High-rise (Apartment/Flat)';
        } elseif (strpos($user_house, 'landed') !== false || strpos($user_house, 'terrace') !== false || strpos($user_house, 'bungalow') !== false) {
            $house_col = 'House_Type_Landed (Terrace/Semi-D/Bungalow)';
        } elseif (strpos($user_house, 'tradisional') !== false || strpos($user_house, 'spacious') !== false || strpos($user_house, 'village') !== false) {
            $house_col = 'House_Type_Tradisional/Spacious (Village House)';
        }

        // Query all database data rows safely
        $all_plants_sql = "SELECT * FROM plantmatch_ultimate_complete";
        $plants_res = mysqli_query($conn, $all_plants_sql);

        if ($plants_res) {
            while ($row = mysqli_fetch_assoc($plants_res)) {
                $score = 0;

                // Light Compatibility (Max Weight: 20 pts)
                $p_light = (float)($row['Light_Condition'] ?? 0.5);
                $score += 20 * (1 - abs($p_light - $u_light_val));

                // Water Suitability (Max Weight: 20 pts)
                $p_water = (float)($row['Water_Frequency'] ?? 0.5);
                $score += 20 * (1 - abs($p_water - $u_water_val));

                // Selected Category Match (Max Weight: 20 pts)
                if (!empty($category_col) && isset($row[$category_col]) && (float)$row[$category_col] == 1.0) {
                    $score += 20;
                }

                // Care Skill Level Calibration (Max Weight: 10 pts)
                $p_skill = (float)($row['Gardening_Level'] ?? 0.5);
                $score += 10 * (1 - abs($p_skill - $u_skill_val));

                // Spatial Requirement Fit (Max Weight: 10 pts)
                $p_space = (float)($row['Space_Required'] ?? 0.5);
                $score += 10 * (1 - abs($p_space - $u_space_val));

                // Growth Rate Preference Alignment (Max Weight: 10 pts)
                $p_growth = (float)($row['Growth_Rate'] ?? 0.5);
                $score += 10 * (1 - abs($p_growth - $u_growth_val));

                // Humidity Calibration Profile (Max Weight: 10 pts)
                $p_humidity = (float)($row['Humidity_Need'] ?? 0.5);
                $score += 10 * (1 - abs($p_humidity - $u_humidity_val));

                // House Structural Type Factor (Bonus Weight: 5 pts)
                if (!empty($house_col) && isset($row[$house_col]) && (float)$row[$house_col] == 1.0) {
                    $score += 5;
                }

                // Normalize final outputs to an integer percentage out of 100%
                $final_pct = (int)round(($score / 105) * 100);
                $row['calculated_match_score'] = min(100, max(0, $final_pct));
                
                $recommended_plants_list[] = $row;
            }

            // Sort arrays descending by calculation compatibility scores
            usort($recommended_plants_list, function($a, $b) {
                if ($b['calculated_match_score'] === $a['calculated_match_score']) {
                    return $a['ID'] <=> $b['ID'];
                }
                return $b['calculated_match_score'] <=> $a['calculated_match_score'];
            });

            // Slice exactly the top 10 unique results
            $recommended_plants_list = array_slice($recommended_plants_list, 0, 10);
        }
    }
}

// Emergency random distribution if settings are empty
if (empty($recommended_plants_list)) {
    $fallback_sql = "SELECT * FROM plantmatch_ultimate_complete ORDER BY RAND() LIMIT 10";
    $fallback_res = mysqli_query($conn, $fallback_sql);
    if ($fallback_res) {
        while ($row = mysqli_fetch_assoc($fallback_res)) {
            $row['calculated_match_score'] = rand(70, 85);
            $recommended_plants_list[] = $row;
        }
    }
}

$ranking = 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Top 10 Personalized Plant Recommendations</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f5f8fa;
            color: #2d3748;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        
        /* Premium Soft Solid Blue Header Frame */
        .hero-banner-frame {
            background-color: #4a7ebb;
            color: #ffffff;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 20px rgba(74, 126, 187, 0.15);
            margin-bottom: 40px;
        }
        .hero-banner-frame h1 {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .hero-banner-frame p {
            color: #f0f4f8;
            font-size: 1.05rem;
            opacity: 0.95;
        }

        /* Modern Grid Layout Cards */
        .plant-card-frame {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        }
        .plant-card-frame:hover {
            transform: translateY(-5px);
            border-color: #b3cce6;
            box-shadow: 0 15px 30px rgba(74, 126, 187, 0.12);
        }
        
        /* Rank and Identity Badges */
        .rank-pill-badge {
            width: 38px; height: 38px;
            background: #4a7ebb;
            color: #ffffff; font-weight: 800; font-size: 0.95rem;
            border-radius: 12px; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 8px rgba(74, 126, 187, 0.2);
        }
        .sys-id-label {
            font-size: 0.72rem; font-weight: 700; color: #4a5568;
            background: #edf4fa; padding: 4px 12px; border-radius: 8px;
        }

        /* Circular Framed Botanical Image Container */
        .avatar-circle-wrapper {
            width: 120px; height: 120px;
            border-radius: 50%; overflow: hidden;
            margin: 0 auto; border: 4px solid #ffffff;
            box-shadow: 0 6px 16px rgba(0,0,0,0.06);
            background-color: #f7fafc;
        }
        .avatar-circle-wrapper img {
            width: 100%; height: 100%; object-fit: cover;
        }

        /* Match Progress Indicator Meter */
        .match-score-badge {
            background-color: #eaf2fa;
            color: #2b6cb0;
            font-weight: 800;
            font-size: 0.85rem;
            padding: 6px 14px;
            border-radius: 10px;
            display: inline-block;
            border: 1px solid #cce0f5;
        }
        .scientific-title-italics {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-style: italic; font-weight: 700; color: #1a2d42;
            font-size: 1.22rem; line-height: 1.3;
        }
        .common-title-bold {
            font-weight: 600; color: #606f7b; font-size: 0.9rem;
        }

        /* Specification Pills */
        .spec-inner-pill {
            background: #f7fafc; border: 1px solid #edf2f7;
            border-radius: 12px; padding: 8px 12px; height: 100%;
        }
        .spec-inner-pill .title-lbl {
            font-size: 0.65rem; font-weight: 700; color: #a0aec0;
            text-transform: uppercase; display: block; margin-bottom: 2px;
        }
        .spec-inner-pill .val-lbl {
            font-size: 0.8rem; font-weight: 700; color: #1a202c;
            display: block; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;
        }

        /* Action Buttons Layouts */
        .btn-success {
            background-color: #198754;
            border-color: #198754;
        }
        .btn-success:hover {
            background-color: #157347;
            border-color: #146c43;
        }
        .btn-circle { 
            width: 38px; height: 38px; border-radius: 50%; 
            display: inline-flex; align-items: center; justify-content: center; 
            transition: 0.2s; border: 1px solid #dee2e6; background: white; 
            font-size: 1rem;
        }
        .btn-circle:hover { transform: scale(1.08); }

        /* Custom States for the Dynamic Heart States */
        .btn-circle.btn-favorited-active {
            background-color: #dc3545 !important;
            border-color: #dc3545 !important;
            color: #ffffff !important;
        }
        .btn-circle.btn-not-favorited {
            background-color: #ffffff !important;
            border-color: #dc3545 !important;
            color: #dc3545 !important;
        }
    </style>
</head>
<body>

<div class="container mt-5 mb-5">
    
    <div class="hero-banner-frame shadow-sm">
        <div class="row align-items-center">
            <div class="col-md-12 text-center">
                <h1>Personalized Plant Recommendations</h1>
                <p class="mb-0 mt-2">Discover your top 10 ideal plant matches, strategically selected to thrive within your specific living space and environmental conditions.</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <?php if (!empty($recommended_plants_list)): ?>
            <?php foreach($recommended_plants_list as $row): 
                $pID = $row['ID'];
                $names = explode(',', $row['Common_Name'] ?? 'Unnamed Plant');
                $primary_common = trim($names[0]);
                
                $display_light = map_light_label($row['Light_Condition'] ?? 0.5);
                $display_water = map_water_label($row['Water_Frequency'] ?? 0.5);

                $imageSource = (!empty($row['Image_URL'])) ? $row['Image_URL'] : 'https://images.unsplash.com/photo-1545241047-6083a3684587?w=300';
                $isFavorited = in_array(trim($pID), $favorited_ids);
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="plant-card-frame">
                    <div class="card-body p-4 text-center d-flex flex-column h-100">
                        
                        <div class="d-flex align-items-center justify-content-between mb-3 w-100">
                            <div class="rank-pill-badge">#<?php echo $ranking++; ?></div>
                            <span class="sys-id-label">ID: <?php echo htmlspecialchars($pID); ?></span>
                        </div>
                        
                        <div class="mb-3">
                            <div class="avatar-circle-wrapper">
                                <img src="<?php echo $imageSource; ?>" alt="<?php echo htmlspecialchars($primary_common); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <span class="match-score-badge"><i class="bi bi-patch-check-fill me-1"></i> <?php echo $row['calculated_match_score']; ?>% Match Score</span>
                        </div>

                        <h5 class="scientific-title-italics mb-1 text-truncate w-100" title="<?php echo htmlspecialchars($row['Scientific_Name'] ?? 'Species Unknown'); ?>">
                            <?php echo htmlspecialchars($row['Scientific_Name'] ?? 'Species Unknown'); ?>
                        </h5>
                        <h6 class="common-title-bold mb-4 text-truncate w-100" title="<?php echo htmlspecialchars($primary_common); ?>">
                            <?php echo htmlspecialchars($primary_common); ?>
                        </h6>
                        
                        <div class="row g-2 mb-4 text-start mt-auto w-100 mx-0">
                            <div class="col-6 px-1">
                                <div class="spec-inner-pill">
                                    <span class="title-lbl">Sunlight</span>
                                    <span class="val-lbl" title="<?php echo htmlspecialchars($display_light); ?>"><?php echo htmlspecialchars($display_light); ?></span>
                                </div>
                            </div>
                            <div class="col-6 px-1">
                                <div class="spec-inner-pill">
                                    <span class="title-lbl">Water</span>
                                    <span class="val-lbl" title="<?php echo htmlspecialchars($display_water); ?>"><?php echo htmlspecialchars($display_water); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center justify-content-center gap-2 w-100 mt-2">
                            <a href="user_plant_details.php?id=<?php echo urlencode($pID); ?>" class="btn btn-success btn-sm rounded-pill px-4 fw-bold shadow-sm">View Details</a>                            
                            
                            <?php if ($isFavorited): ?>
                                <button onclick="ajaxAction('?id=<?php echo urlencode($pID); ?>&ajax_action=unfavorite')" class="btn btn-circle btn-favorited-active shadow-sm" title="Unfavorite Plant">
                                    <i class="bi bi-heart-fill"></i>
                                </button>
                            <?php else: ?>
                                <button onclick="ajaxAction('?id=<?php echo urlencode($pID); ?>&ajax_action=favorite')" class="btn btn-circle btn-not-favorited shadow-sm" title="Favorite Plant">
                                    <i class="bi bi-heart"></i>
                                </button>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-11 mx-auto text-center py-5 bg-white rounded-4 shadow-sm">
                <i class="bi bi-flower1 text-success display-1 mb-3"></i>
                <h4 class="fw-bold">No Botanical Matches Found</h4>
                <p class="text-muted mb-0">Please complete the Plant Match Quiz to generate your live recommendations.</p>
                <div class="mt-3">
                    <a href="user_recommendation_form.php" class="btn btn-success rounded-pill px-4 btn-sm">Take Quiz Now</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function ajaxAction(queryString) {
    // Uses direct relative routing to eliminate 404 handler conflicts
    fetch(queryString)
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not OK');
        }
        return response.text();
    })
    .then(data => { 
        alert(data.trim()); 
        location.reload(); 
    })
    .catch(error => { 
        alert('Error: Could not update operations.'); 
        console.error(error);
    });
}
</script>

<?php include('footer.php'); ?>
</body>
</html>