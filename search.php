<?php 
include('public_header.php'); 

// 1. Database Connection Configuration
$conn = mysqli_connect(getenv('DB_HOST') ?: "127.0.0.1", "root", "", "plantmatch_db");
$table = "plantmatch_ultimate_complete";
$limit = 12; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;
$keyword = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Absolute Grand Total Query for exact database inventory transparency
$grand_total_query = "SELECT COUNT(*) as grand_total FROM `$table`";
$grand_total_result = mysqli_query($conn, $grand_total_query);
$absolute_total = mysqli_fetch_assoc($grand_total_result)['grand_total'];

// 2. Count Records strictly for Pagination math (Fixed: Removed non-existent Categories column)
$count_query = "SELECT COUNT(*) as total FROM `$table` 
                WHERE `Common_Name` LIKE '%$keyword%' 
                OR `Scientific_Name` LIKE '%$keyword%'";
$count_result = mysqli_query($conn, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PlantMatch | Dynamic Database Explorer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        :root { 
            --active-neon: #00f2fe; 
            --active-violet: #7f00ff;
            --gradient-active: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%);
            --card-bg: #ffffff;
        }
        body { background-color: #f1f5f9; color: #334155; }
        
        .plant-card { transition: transform 0.3s ease, box-shadow 0.3s ease; border: none; border-radius: 16px; background: var(--card-bg); }
        .plant-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0, 242, 254, 0.15) !important; }
        
        .scientific-title { background: linear-gradient(45deg, var(--active-violet), #4facfe); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-style: italic; font-family: 'Georgia', serif; }
        .badge-category { background-color: rgba(0, 242, 254, 0.1); color: #0284c7; border: 1px solid rgba(0, 242, 254, 0.2); font-size: 0.7rem; }
        .card-footer { background-color: rgba(241, 245, 249, 0.6); border-top: 1px solid rgba(0,0,0,0.03); }

        /* --- Symmetrical Left/Right Active Radar Animation Matrix --- */
        .header-container-light {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .search-sonar-stage {
            position: relative;
            width: 80px;
            height: 80px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* Continuous circular radar ripple waves */
        .sonar-pulse-loop {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 2px solid var(--active-neon);
            opacity: 0;
            animation: radarPulseExpansion 2s cubic-bezier(0.25, 0.46, 0.45, 0.94) infinite;
        }
        .sonar-pulse-loop-delayed {
            animation-delay: 1s;
        }

        /* Orbiting track ring line */
        .radar-orbit-ring {
            position: absolute;
            width: 80%;
            height: 80%;
            border-radius: 50%;
            border: 1px dashed rgba(0, 242, 254, 0.4);
            animation: radarSweepRotate 4s linear infinite;
        }
        .radar-orbit-dot {
            position: absolute;
            top: -3px;
            left: 50%;
            width: 6px;
            height: 6px;
            background-color: var(--active-neon);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--active-neon);
        }

        /* Active Core Icons */
        .sonar-core-icon {
            font-size: 2rem;
            color: #0ea5e9;
            z-index: 3;
            animation: coreProximityPulse 1s ease-in-out infinite alternate;
        }

        /* Keyframe Calculations */
        @keyframes radarPulseExpansion {
            0% { transform: scale(0.3); opacity: 1; }
            80% { opacity: 0.4; }
            100% { transform: scale(1.2); opacity: 0; }
        }
        @keyframes radarSweepRotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        @keyframes coreProximityPulse {
            0% { transform: scale(0.9); filter: drop-shadow(0 0 2px rgba(14, 165, 233, 0.2)); }
            100% { transform: scale(1.05); filter: drop-shadow(0 0 10px rgba(0, 242, 254, 0.6)); }
        }

        /* Search input adjustments */
        .custom-search-btn {
            background: var(--gradient-active);
            border: none;
            color: #ffffff;
            transition: opacity 0.2s;
        }
        .custom-search-btn:hover {
            opacity: 0.9;
            color: #ffffff;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<div class="container mt-5 mb-auto pb-5">
    
    <div class="header-container-light p-4 mb-5">
        <div class="d-flex align-items-center justify-content-between flex-column flex-sm-row gap-3">
            
            <div class="search-sonar-stage">
                <div class="sonar-pulse-loop"></div>
                <div class="sonar-pulse-loop sonar-pulse-loop-delayed"></div>
                <div class="radar-orbit-ring">
                    <div class="radar-orbit-dot"></div>
                </div>
                <i class="bi bi-cpu-fill sonar-core-icon"></i>
            </div>

            <div class="text-center">
                <h1 class="display-5 fw-bold mb-1" style="color: #0f172a; letter-spacing: -1px;">Plant Explorer</h1>
                <p class="lead mb-0 small text-uppercase" style="color: #475569; letter-spacing: 2px;">
                    Browsing <?php echo ($keyword) ? "results for '".htmlspecialchars($keyword)."'" : "all records"; ?> 
                    (<span class="fw-bold" style="color: #0284c7;"><?php echo $absolute_total; ?></span> profiles verified)
                </p>
            </div>

            <div class="search-sonar-stage">
                <div class="sonar-pulse-loop" style="animation-delay: 0.5s;"></div>
                <div class="sonar-pulse-loop" style="animation-delay: 1.5s;"></div>
                <div class="radar-orbit-ring" style="animation-direction: reverse;">
                    <div class="radar-orbit-dot"></div>
                </div>
                <i class="bi bi-activity sonar-core-icon" style="color: #4facfe;"></i>
            </div>

        </div>
    </div>

    <div class="row justify-content-center mb-5">
        <div class="col-md-8">
            <form action="" method="GET">
                <div class="input-group input-group-lg shadow-sm border rounded-4 overflow-hidden bg-white p-1">
                    <span class="input-group-text bg-white border-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-0 bg-white" placeholder="Search biological criteria or common names..." value="<?php echo htmlspecialchars($keyword); ?>">
                    <button class="btn custom-search-btn px-5 fw-semibold rounded-3" type="submit">Search</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($keyword !== '' || isset($_GET['page'])): ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php 
            $query = "SELECT * FROM `$table` 
                      WHERE `Common_Name` LIKE '%$keyword%' 
                      OR `Scientific_Name` LIKE '%$keyword%' 
                      LIMIT $start, $limit";
            $result = mysqli_query($conn, $query);

            if (mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
                    $names = explode(',', $row['Common_Name']);
                    $primary_common = trim($names[0]);
                    $aka = (count($names) > 1) ? implode(', ', array_slice($names, 1)) : null;

                    // Reconstruct category label from one-hot encoded columns
                    $category_display = 'General';
                    $categories_map = [
                        'Categories_Climbers & Vines' => 'Climbers & Vines',
                        'Categories_Edibles & Herbs' => 'Edibles & Herbs',
                        'Categories_Ferns' => 'Ferns',
                        'Categories_Flowering Ornamentals' => 'Flowering Ornamentals',
                        'Categories_Foliage Plants' => 'Foliage Plants',
                        'Categories_Palms' => 'Palms',
                        'Categories_Shrubs' => 'Shrubs',
                        'Categories_Succulents & Cacti' => 'Succulents & Cacti',
                        'Categories_Trees' => 'Trees'
                    ];
                    foreach ($categories_map as $col_key => $label) {
                        if (isset($row[$col_key]) && (float)$row[$col_key] == 1.0) {
                            $category_display = $label;
                            break;
                        }
                    }

                    // Reconstruct house type label from one-hot encoded columns
                    $house_display = 'Any';
                    $house_map = [
                        'House_Type_High-rise (Apartment/Flat)' => 'High-rise',
                        'House_Type_Landed (Terrace/Semi-D/Bungalow)' => 'Landed',
                        'House_Type_Tradisional/Spacious (Village House)' => 'Traditional/Spacious'
                    ];
                    foreach ($house_map as $col_key => $label) {
                        if (isset($row[$col_key]) && (float)$row[$col_key] == 1.0) {
                            $house_display = $label;
                            break;
                        }
                    }

                    // =========================================================================
                    // TRANSLATION LAYER: DATA NORMALIZATION TRANSLATION (FROM PREVIOUS FILE)
                    // =========================================================================
                    
                    // 1. Level Mapping
                    $val_gardening = (float)($row['Gardening_Level'] ?? 0);
                    if ($val_gardening <= 0.2) { $display_gardening = "Beginner Friendly"; }
                    elseif ($val_gardening <= 0.6) { $display_gardening = "Intermediate Care"; }
                    else { $display_gardening = "Expert Needed"; }

                    // 2. Lighting Mapping
                    $val_light = (float)($row['Light_Condition'] ?? 0);
                    if ($val_light <= 0.2) { $display_light = "Low Light / Deep Shade"; }
                    elseif ($val_light <= 0.6) { $display_light = "Partial Sun / Bright Indoor"; }
                    else { $display_light = "Full Direct Sun"; }

                    // 3. Water Mapping
                    $val_water = (float)($row['Water_Frequency'] ?? 0);
                    if ($val_water <= 0.2) { $display_water = "Low (Drought Tolerant)"; }
                    elseif ($val_water <= 0.6) { $display_water = "Moderate (Weekly)"; }
                    else { $display_water = "High (Consistently Wet)"; }

                    // 4. Humidity Mapping
                    $val_humidity = (float)($row['Humidity_Need'] ?? 0);
                    if ($val_humidity <= 0.2) { $display_humidity = "Arid / Low Humidity"; }
                    elseif ($val_humidity <= 0.6) { $display_humidity = "Average Home Ambient"; }
                    else { $display_humidity = "Tropical / High Humidity"; }

                    // 5. Est. Peak Height Translation Logic
                    if (isset($row['Mature_Height_cm'])) {
                        $normalized_height = (float)$row['Mature_Height_cm'];
                        $real_height_cm = $normalized_height * 9000;
                        if ($real_height_cm >= 100) {
                            $display_height = round($real_height_cm / 100, 1) . " m";
                        } else {
                            $display_height = ($real_height_cm < 5) ? "5 cm" : round($real_height_cm, 0) . " cm";
                        }
                    } else {
                        $display_height = "N/A";
                    }
                    ?>
                    <div class="col">
                        <div class="card h-100 plant-card shadow-sm">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge badge-category text-uppercase"><?php echo htmlspecialchars($category_display); ?></span>
                                    <small class="text-muted fw-mono">#<?php echo $row['ID']; ?></small>
                                </div>

                                <h4 class="scientific-title mb-1"><?php echo htmlspecialchars($row['Scientific_Name']); ?></h4>
                                <h6 class="text-dark fw-bold mb-1"><?php echo htmlspecialchars($primary_common); ?></h6>

                                <?php if($aka): ?>
                                    <p class="text-muted small mb-3"><strong>Other Names:</strong> <?php echo htmlspecialchars($aka); ?></p>
                                <?php else: ?>
                                    <div class="mb-4"></div>
                                <?php endif; ?>

                                <hr class="my-3 opacity-5">

                                <div class="row g-3">
                                    <div class="col-6">
                                        <label class="text-muted d-block small uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">HOUSE TYPE</label>
                                        <span class="small fw-bold"><?php echo htmlspecialchars($house_display); ?></span>
                                    </div>
                                    <div class="col-6">
                                        <label class="text-muted d-block small" style="font-size: 0.65rem; letter-spacing: 1px;">LIGHTING</label>
                                        <span class="small fw-bold"><?php echo htmlspecialchars($display_light); ?></span>
                                    </div>
                                    <div class="col-6">
                                        <label class="text-muted d-block small" style="font-size: 0.65rem; letter-spacing: 1px;">CARE LEVEL</label>
                                        <span class="small fw-bold"><?php echo htmlspecialchars($display_gardening); ?></span>
                                    </div>
                                    <div class="col-6">
                                        <label class="text-muted d-block small" style="font-size: 0.65rem; letter-spacing: 1px;">EST. PEAK HEIGHT</label>
                                        <span class="small fw-bold text-info"><?php echo htmlspecialchars($display_height); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer px-4 py-3 border-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small text-muted"><i class="bi bi-droplet-half me-1 text-info"></i>Water: <?php echo htmlspecialchars($display_water); ?></span>
                                    <span class="small text-muted"><i class="bi bi-wind me-1 text-purple"></i>Humidity: <?php echo htmlspecialchars($display_humidity); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<div class='col-12 text-center py-5'><i class='bi bi-patch-question display-3 text-muted'></i><p class='mt-3 text-muted'>No biological database entries matched your search parameters.</p></div>";
            }
            ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav class="mt-5">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link shadow-sm border-0 rounded-circle me-2" href="?search=<?php echo urlencode($keyword); ?>&page=<?php echo $page - 1; ?>"><i class="bi bi-chevron-left"></i></a>
                    </li>
                    <?php
                    $start_loop = max(1, $page - 2);
                    $end_loop = min($total_pages, $page + 2);
                    for ($i = $start_loop; $i <= $end_loop; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link shadow-sm border-0 rounded-circle mx-1 <?php echo ($page == $i) ? '' : 'text-dark'; ?>" 
                               style="<?php echo ($page == $i) ? 'background: var(--gradient-active); color: white;' : ''; ?>"
                               href="?search=<?php echo urlencode($keyword); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link shadow-sm border-0 rounded-circle ms-2" href="?search=<?php echo urlencode($keyword); ?>&page=<?php echo $page + 1; ?>"><i class="bi bi-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 text-center py-5">
                <div class="bg-white p-5 rounded-4 shadow-sm border">
                    <i class="bi bi-search display-1 text-muted opacity-25"></i>
                    <h3 class="mt-3 fw-bold">Search our Dataset</h3>
                    <p class="text-muted mb-0">Enter a keyword above to engage active indexing matrices across cataloged species metrics.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include('footer.php'); ?>
</body>
</html>