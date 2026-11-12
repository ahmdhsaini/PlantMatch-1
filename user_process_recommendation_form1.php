<?php
session_start();
include('db.php');

// 1. Security Check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/**
 * MATHEMATICAL MODEL 1: WEIGHTED COSINE SIMILARITY
 */
function calculateCosineSimilarity($vecA, $vecB) {
    $dotProduct = 0;
    $sA = 0;
    $sB = 0;
    
    for ($i = 0; $i < count($vecA); $i++) {
        $dotProduct += ($vecA[$i] * $vecB[$i]);
        $sA += pow($vecA[$i], 2);
        $sB += pow($vecB[$i], 2);
    }
    
    $magnitude = sqrt($sA) * sqrt($sB);
    return ($magnitude == 0) ? 0 : ($dotProduct / $magnitude);
}

/**
 * MATHEMATICAL MODEL 2: EUCLIDEAN DISTANCE (Normalized)
 */
function calculateEuclideanScore($userVal, $itemVal) {
    $distance = abs($userVal - $itemVal);
    return 1 / (1 + $distance); 
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userID = $_SESSION['user_id'];

    // 2. LABEL ENCODING MAPS
    $houseMap = [
        'High-rise (Apartment/Flat)' => 1, 
        'Landed (Terrace/Semi-D/Bungalow)' => 2, 
        'Tradisional/Spacious (Village House)' => 3
    ];
    $levelMap = ['Beginner' => 1, 'Moderate' => 2, 'Expert' => 3];
    $spaceMap = ['Small' => 1, 'Medium' => 2, 'Large' => 3];
    $lightMap = ['Full Sun' => 3, 'Partial Shade' => 2, 'Low Light' => 1];
    $waterMap = ['Daily' => 3, 'Weekly' => 2, 'Monthly' => 1];
    
    $prefMap = [
        'Flowering Ornamentals' => 1, 
        'Edibles & Herbs' => 2, 
        'Climbers & Vines' => 3, 
        'Trees' => 4, 
        'Foliage Plants' => 5, 
        'Ferns' => 6, 
        'Palms' => 7, 
        'Succulents & Cacti' => 8, 
        'Shrubs' => 9
    ];

    $growthMap   = ['Slow' => 1, 'Moderate' => 2, 'Fast' => 3];
    $humidityMap = ['Low' => 1, 'Medium' => 2, 'High' => 3];

    // CRITICAL WEIGHTS: PRIORITIZE SURVIVAL METRICS OVER AESTHETICS
    $weights = [
        'houseType'       => 1.0,
        'gardeningLevel'  => 1.5, 
        'availableSpace'  => 1.0,
        'lightCondition'  => 2.5, // Primary survival factor
        'plantPreference' => 0.8, // Aesthetic choice
        'waterFreq'       => 1.8, // Routine factor
        'growthRate'      => 0.8, 
        'humidityNeed'    => 1.2  
    ];

    // 3. CAPTURE & CLEAN FORM DATA
    $h_raw = $_POST['houseType'];
    $l_raw = $_POST['gardeningLevel'];
    $s_raw = $_POST['availableSpace'];
    $sun_raw = $_POST['lightCondition'];
    $p_raw = $_POST['plantPreference'];
    $w_raw = $_POST['waterFreq'];
    $uHeight = intval($_POST['matureHeight']);
    
    $g_raw = $_POST['growthRate'];
    $hum_raw = $_POST['humidityNeed'];

    $h = mysqli_real_escape_string($conn, $h_raw);
    $l = mysqli_real_escape_string($conn, $l_raw);
    $s = mysqli_real_escape_string($conn, $s_raw);
    $sun = mysqli_real_escape_string($conn, $sun_raw);
    $p = mysqli_real_escape_string($conn, $p_raw);
    $w = mysqli_real_escape_string($conn, $w_raw);
    $g = mysqli_real_escape_string($conn, $g_raw);
    $hum = mysqli_real_escape_string($conn, $hum_raw);

    // 4. DATABASE: UPDATE USER PREFERENCES
    $sql_pref = "INSERT INTO preference (userID, houseType, gardeningLevel, availableSpace, lightCondition, plantPreference, waterFreq, matureHeight, growthRate, humidityNeed) 
                 VALUES ('$userID', '$h', '$l', '$s', '$sun', '$p', '$w', '$uHeight', '$g', '$hum')
                 ON DUPLICATE KEY UPDATE 
                 houseType='$h', gardeningLevel='$l', availableSpace='$s', lightCondition='$sun', plantPreference='$p', waterFreq='$w', matureHeight='$uHeight', growthRate='$g', humidityNeed='$hum'";
    mysqli_query($conn, $sql_pref);

    // 5. DATABASE: CLEAR PREVIOUS RUN RECOMMENDATIONS
    mysqli_query($conn, "DELETE FROM recommendation WHERE userID = '$userID'");

    // 6. HIGH-OPTIMIZATION DATA PRE-FETCHING (PREVENTS DATABASE BOTTLENECK)
    $savedPlants = [];
    $s_res = mysqli_query($conn, "SELECT plantID FROM save WHERE userID = '$userID'");
    while($rowS = mysqli_fetch_assoc($s_res)) { $savedPlants[] = $rowS['plantID']; }

    $favPlants = [];
    $f_res = mysqli_query($conn, "SELECT plantID FROM favorite WHERE userID = '$userID'");
    while($rowF = mysqli_fetch_assoc($f_res)) { $favPlants[] = $rowF['plantID']; }

    // Fetch personal feedback history
    $userRatings = [];
    $rating_res = mysqli_query($conn, "SELECT plantID, rating FROM feedback WHERE userID = '$userID'");
    if ($rating_res) {
        while($rowR = mysqli_fetch_assoc($rating_res)) { 
            $userRatings[$rowR['plantID']] = (int)$rowR['rating']; 
        }
    }

    // FINALIZED FIX: Pre-aggregate ALL global community averages in ONE single batch query
    $globalAverages = [];
    $global_res = mysqli_query($conn, "SELECT plantID, AVG(rating) as avg_rating FROM feedback GROUP BY plantID");
    if ($global_res) {
        while ($global_row = mysqli_fetch_assoc($global_res)) {
            if ($global_row['avg_rating'] !== null) {
                $globalAverages[$global_row['plantID']] = round($global_row['avg_rating']);
            }
        }
    }

    // 7. THE HYBRID RECOMMENDATION ENGINE
    $userVector = [
        ($houseMap[$h_raw] ?? 1) * $weights['houseType'],
        ($levelMap[$l_raw] ?? 1) * $weights['gardeningLevel'],
        ($spaceMap[$s_raw] ?? 1) * $weights['availableSpace'],
        ($lightMap[$sun_raw] ?? 1) * $weights['lightCondition'],
        ($prefMap[$p_raw] ?? 1) * $weights['plantPreference'],
        ($waterMap[$w_raw] ?? 1) * $weights['waterFreq'],
        ($growthMap[$g_raw] ?? 1) * $weights['growthRate'],
        ($humidityMap[$hum_raw] ?? 1) * $weights['humidityNeed']
    ];

    $plants = mysqli_query($conn, "SELECT * FROM plantmatch_ultimate_complete");
    $recommendationList = [];

    while ($row = mysqli_fetch_assoc($plants)) {
        $plantID = $row['ID'];

        $plantVector = [
            ($houseMap[$row['House_Type']] ?? 1) * $weights['houseType'],
            ($levelMap[$row['Gardening_Level']] ?? 1) * $weights['gardeningLevel'],
            ($spaceMap[$row['Space_Required']] ?? 1) * $weights['availableSpace'],
            ($lightMap[$row['Light_Condition']] ?? 1) * $weights['lightCondition'],
            ($prefMap[$row['Categories']] ?? 1) * $weights['plantPreference'],
            ($waterMap[$row['Water_Frequency']] ?? 1) * $weights['waterFreq'],
            ($growthMap[$row['Growth_Rate']] ?? 1) * $weights['growthRate'],
            ($humidityMap[$row['Humidity_Need']] ?? 1) * $weights['humidityNeed']
        ];

        // Part A: Weighted Cosine Similarity
        $cosineScore = calculateCosineSimilarity($userVector, $plantVector);

        // Part B: Euclidean Score (Normalized Height Component)
        $euclideanScore = calculateEuclideanScore($uHeight, $row['Mature_Height_cm']);

        // Part C: Hybrid Linear Mixture Calculation (80% Environment / 20% Dimension)
        $baseScore = ($cosineScore * 0.8) + ($euclideanScore * 0.2);

        // Part D: Interaction Adjustments
        $bonus = 0;
        if (in_array($plantID, $savedPlants)) { $bonus += 0.07; }
        if (in_array($plantID, $favPlants)) { $bonus += 0.03; }
        
        $interimScore = ($baseScore + $bonus) * 100;

        // Part E: Multi-Tier Feedback Intersection
        if (isset($userRatings[$plantID])) {
            // Priority 1: User's direct personal explicit historical rating
            $stars = $userRatings[$plantID];
            switch ($stars) {
                case 5: $interimScore += 15; break;
                case 4: $interimScore += 7;  break;
                case 3: $interimScore += 0;  break;
                case 2: $interimScore -= 15; break;
                case 1: $interimScore -= 35; break;
            }
        } elseif (isset($globalAverages[$plantID])) {
            // Priority 2: Cold-Start Protection using the Batch Pre-fetched Global Average
            $avgStars = $globalAverages[$plantID];
            if ($avgStars >= 4) { $interimScore += 5; }  
            if ($avgStars <= 2) { $interimScore -= 10; } 
        }

        // Apply strict boundary safeguards [0, 100]
        $finalScore = max(0, min($interimScore, 100));

        $recommendationList[] = [
            'id' => $plantID,
            'final_rank' => $finalScore 
        ];
    }

    // 8. GRACEFUL TIE-BREAKING & TOP 10 FILTERING
    usort($recommendationList, function($a, $b) {
        if (abs($b['final_rank'] - $a['final_rank']) < 0.0001) {
            return $a['id'] <=> $b['id']; // Ensures deterministic output if scores perfectly tie
        }
        return $b['final_rank'] <=> $a['final_rank'];
    });

    // Extract slice and batch write top records
    $top10 = array_slice($recommendationList, 0, 10);
    foreach ($top10 as $item) {
        $pID = $item['id'];
        $rank = $item['final_rank'];
        mysqli_query($conn, "INSERT INTO recommendation (userID, plantID, `rank`, recTime) VALUES ('$userID', '$pID', '$rank', NOW())");
    }

    header("Location: user_view_recommendations.php?status=updated");
    exit();
}
?>