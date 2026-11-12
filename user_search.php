<?php 
include('private_header.php'); 
include('db.php'); 

// 1. Configuration & Pagination Setup
$table = "plantmatch_ultimate_complete";
$limit = 12; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

// Helper function to safely read array keys regardless of database casing
if (!function_exists('get_db_value')) {
    function get_db_value($row, $key) {
        foreach ($row as $k => $v) {
            if (strtolower($k) === strtolower($key)) {
                return $v;
            }
        }
        return null;
    }
}

// 2. Data Mapping Helpers (Translating numeric database values into friendly text labels)
if (!function_exists('map_light_label')) {
    function map_light_label($val) {
        if (is_numeric($val)) {
            $f = (float)$val;
            if ($f <= 0.2) return "Low Light / Shade";
            if ($f <= 0.6) return "Partial Sun / Indoor";
            return "Full Direct Sun";
        }
        return !empty($val) ? $val : "Any";
    }
}

if (!function_exists('map_water_label')) {
    function map_water_label($val) {
        if (is_numeric($val)) {
            $f = (float)$val;
            if ($f <= 0.2) return "Low (Drought Tol.)";
            if ($f <= 0.6) return "Moderate (Weekly)";
            return "High (Consistently Wet)";
        }
        return !empty($val) ? $val : "Normal";
    }
}

if (!function_exists('map_growth_label')) {
    function map_growth_label($val) {
        if (is_numeric($val)) {
            $f = (float)$val;
            if ($f <= 0.2) return "Slow";
            if ($f <= 0.6) return "Moderate";
            return "Fast";
        }
        return !empty($val) ? $val : "Moderate";
    }
}

if (!function_exists('map_space_label')) {
    function map_space_label($val) {
        if (is_numeric($val)) {
            $f = (float)$val;
            if ($f <= 0.3) return "Small / Compact";
            if ($f <= 0.7) return "Medium Space";
            return "Large / High Clearance";
        }
        return !empty($val) ? $val : "Medium";
    }
}

// 3. Capture Filter Parameters
$keyword   = isset($_GET['keyword']) ? mysqli_real_escape_string($conn, trim($_GET['keyword'])) : '';
$f_cat     = isset($_GET['cat']) ? (array)$_GET['cat'] : [];
$f_water   = isset($_GET['water']) ? (array)$_GET['water'] : [];
$f_growth  = isset($_GET['growth']) ? (array)$_GET['growth'] : [];
$f_house   = isset($_GET['house']) ? (array)$_GET['house'] : [];
$f_light   = isset($_GET['light']) ? (array)$_GET['light'] : [];

// 4. Exact Filter Definitions Matching Your CSV Schema
$house_types = [
    'High-rise (Apartment/Flat)',
    'Landed (Terrace/Semi-D/Bungalow)',
    'Tradisional/Spacious (Village House)'
];
$categories = [
    'Climbers & Vines', 
    'Edibles & Herbs', 
    'Ferns', 
    'Foliage Plants', 
    'Palms', 
    'Shrubs', 
    'Succulents & Cacti', 
    'Trees'
];
$water_levels = ['Daily', 'Weekly', 'Monthly'];
$growth_levels = ['Slow', 'Moderate', 'Fast'];
$light_levels = ['Low Light', 'Partial Sun', 'Full Sun'];

// 5. Build the SQL "WHERE" Clause dynamically matching one-hot column encodings
$where_clauses = [];

if ($keyword !== '') {
    $search_conditions = [
        "`Common_Name` LIKE '%$keyword%'", 
        "`Scientific_Name` LIKE '%$keyword%'"
    ];
    
    // Check if user keywords contain House Types
    if (stripos($keyword, 'high-rise') !== false || stripos($keyword, 'high rise') !== false || stripos($keyword, 'apartment') !== false || stripos($keyword, 'flat') !== false) {
        $search_conditions[] = "`House_Type_High-rise (Apartment/Flat)` = 1";
    }
    if (stripos($keyword, 'landed') !== false || stripos($keyword, 'terrace') !== false || stripos($keyword, 'semi-d') !== false || stripos($keyword, 'bungalow') !== false) {
        $search_conditions[] = "`House_Type_Landed (Terrace/Semi-D/Bungalow)` = 1";
    }
    if (stripos($keyword, 'tradisional') !== false || stripos($keyword, 'traditional') !== false || stripos($keyword, 'spacious') !== false || stripos($keyword, 'village') !== false) {
        $search_conditions[] = "`House_Type_Tradisional/Spacious (Village House)` = 1";
    }
    
    // Check if user keywords contain Lighting Conditions
    if (stripos($keyword, 'low light') !== false || stripos($keyword, 'low-light') !== false || stripos($keyword, 'shade') !== false) {
        $search_conditions[] = "`Light_Condition` <= 0.2";
    } elseif (stripos($keyword, 'partial') !== false || stripos($keyword, 'indoor') !== false) {
        $search_conditions[] = "(`Light_Condition` > 0.2 AND `Light_Condition` <= 0.6)";
    } elseif (stripos($keyword, 'full sun') !== false || stripos($keyword, 'full-sun') !== false || stripos($keyword, 'direct') !== false) {
        $search_conditions[] = "`Light_Condition` > 0.6";
    } elseif (stripos($keyword, 'low') !== false) {
        $search_conditions[] = "`Light_Condition` <= 0.2";
    } elseif (stripos($keyword, 'sun') !== false) {
        $search_conditions[] = "`Light_Condition` > 0.2";
    }

    $where_clauses[] = "(" . implode(" OR ", $search_conditions) . ")";
}

// Category filter processing
if (!empty($f_cat)) {
    $cat_conditions = [];
    foreach ($f_cat as $cat) {
        $col_name = "Categories_" . $cat;
        $cat_conditions[] = "`" . mysqli_real_escape_string($conn, $col_name) . "` = 1";
    }
    if (!empty($cat_conditions)) {
        $where_clauses[] = "(" . implode(" OR ", $cat_conditions) . ")";
    }
}

// Water Level ranges translation
if (!empty($f_water)) {
    $water_conditions = [];
    foreach ($f_water as $w) {
        if ($w === 'Daily') {
            $water_conditions[] = "`Water_Frequency` > 0.6";
        } elseif ($w === 'Weekly') {
            $water_conditions[] = "(`Water_Frequency` > 0.2 AND `Water_Frequency` <= 0.6)";
        } elseif ($w === 'Monthly') {
            $water_conditions[] = "`Water_Frequency` <= 0.2";
        }
    }
    if (!empty($water_conditions)) {
        $where_clauses[] = "(" . implode(" OR ", $water_conditions) . ")";
    }
}

// Growth Speed ranges translation
if (!empty($f_growth)) {
    $growth_conditions = [];
    foreach ($f_growth as $g) {
        if ($g === 'Slow') {
            $growth_conditions[] = "`Growth_Rate` <= 0.2";
        } elseif ($g === 'Moderate') {
            $growth_conditions[] = "(`Growth_Rate` > 0.2 AND `Growth_Rate` <= 0.6)";
        } elseif ($g === 'Fast') {
            $growth_conditions[] = "`Growth_Rate` > 0.6";
        }
    }
    if (!empty($growth_conditions)) {
        $where_clauses[] = "(" . implode(" OR ", $growth_conditions) . ")";
    }
}

// House Type filter checkboxes processing
if (!empty($f_house)) {
    $house_conditions = [];
    foreach ($f_house as $house) {
        $col_name = "House_Type_" . $house;
        $house_conditions[] = "`" . mysqli_real_escape_string($conn, $col_name) . "` = 1";
    }
    if (!empty($house_conditions)) {
        $where_clauses[] = "(" . implode(" OR ", $house_conditions) . ")";
    }
}

// Lighting Need filter checkboxes processing
if (!empty($f_light)) {
    $light_conditions = [];
    foreach ($f_light as $l) {
        if ($l === 'Low Light') {
            $light_conditions[] = "`Light_Condition` <= 0.2";
        } elseif ($l === 'Partial Sun') {
            $light_conditions[] = "(`Light_Condition` > 0.2 AND `Light_Condition` <= 0.6)";
        } elseif ($l === 'Full Sun') {
            $light_conditions[] = "`Light_Condition` > 0.6";
        }
    }
    if (!empty($light_conditions)) {
        $where_clauses[] = "(" . implode(" OR ", $light_conditions) . ")";
    }
}

$final_where = (count($where_clauses) > 0) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// 6. Count Total Records
$count_query = "SELECT COUNT(*) as total FROM `$table` $final_where";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_records = $count_row['total'] ?? 0;
$total_pages = ceil($total_records / $limit);

// 7. Build Query String Helper for Pagination Links (Preserves Filters)
$url_params = $_GET;
unset($url_params['page']); 
$base_query_string = http_build_query($url_params);
$query_prefix = !empty($base_query_string) ? '&' . $base_query_string : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Explore Plants</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; }
        .filter-card { border-radius: 15px; border: none; background: white; }
        .plant-card { transition: all 0.3s ease; border: none; border-radius: 20px; overflow: hidden; background: white; height: 100%; display: flex; flex-direction: column; }
        .plant-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important; }
        
        /* UPDATED: Sidebar now has an internal scrollable system if contents exceed screen height */
        .sticky-sidebar { 
            position: -webkit-sticky; 
            position: sticky; 
            top: 20px; 
            max-height: calc(100vh - 40px); 
            overflow-y: auto; 
            padding-right: 4px;
        }
        .sticky-sidebar::-webkit-scrollbar { width: 5px; }
        .sticky-sidebar::-webkit-scrollbar-track { background: transparent; }
        .sticky-sidebar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        
        .badge-custom { font-weight: 500; font-size: 0.7rem; padding: 4px 8px; border-radius: 6px; background-color: #f8f9fa; color: #104206; border: 1px solid #e9ecef; }
        .scientific-name { font-style: italic; color: #104206; font-family: 'Georgia', serif; }
        
        .pagination .page-link { border: none; margin: 0 3px; border-radius: 8px !important; min-width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; color: #104206; font-weight: 600; box-shadow: 0 2px 5px rgba(0,0,0,0.05); background: white; }
        .pagination .page-item.active .page-link { background-color: #104206; color: white; }
        .pagination .page-item.disabled .page-link { color: #6c757d; background-color: #e9ecef; opacity: 0.6; pointer-events: none; }
        
        .tech-label { font-size: 0.65rem; letter-spacing: 0.5px; color: #6c757d; font-weight: bold; text-transform: uppercase; }

        .plant-img-circle {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #f8f9fa;
            box-shadow: 0 4px 10px rgba(16, 66, 6, 0.15);
            background-color: #f8f9fa;
        }

        .btn-circle {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            transition: 0.2s;
        }
        .btn-circle:hover { transform: scale(1.1); }
    </style>
</head>
<body>

<div class="container mt-4 mb-5">
    <div class="mb-4">
        <h2 class="fw-bold" style="color: #104206;">Explore Plants</h2>
        <p class="text-muted">Showing <?php echo htmlspecialchars($total_records); ?> species based on your selection.</p>
    </div>

    <div class="row">
        <div class="col-lg-3">
            <div class="sticky-sidebar">
                <div class="card filter-card shadow-sm p-3">
                    <form action="user_search.php" method="GET" id="filterForm">
                        <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>">
                        <h5 class="mb-3 fw-bold">Filters</h5>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase" style="color: #104206;">House Type</label>
                            <?php foreach($house_types as $ht): ?>
                                <div class="form-check small mb-1">
                                    <input class="form-check-input" type="checkbox" name="house[]" value="<?php echo htmlspecialchars($ht); ?>" onchange="this.form.submit()" <?php echo in_array($ht, $f_house) ? 'checked' : ''; ?>>
                                    <label class="form-check-label">
                                        <?php 
                                            if ($ht === 'High-rise (Apartment/Flat)') echo "High-rise Apartment";
                                            elseif ($ht === 'Landed (Terrace/Semi-D/Bungalow)') echo "Landed Property";
                                            else echo "Spacious Village House";
                                        ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Category</label>
                            <?php foreach($categories as $cat): ?>
                                <div class="form-check small mb-1">
                                    <input class="form-check-input" type="checkbox" name="cat[]" value="<?php echo htmlspecialchars($cat); ?>" onchange="this.form.submit()" <?php echo in_array($cat, $f_cat) ? 'checked' : ''; ?>>
                                    <label class="form-check-label"><?php echo htmlspecialchars($cat); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Lighting Need</label>
                            <?php foreach($light_levels as $ll): ?>
                                <div class="form-check small mb-1">
                                    <input class="form-check-input" type="checkbox" name="light[]" value="<?php echo htmlspecialchars($ll); ?>" onchange="this.form.submit()" <?php echo in_array($ll, $f_light) ? 'checked' : ''; ?>>
                                    <label class="form-check-label"><?php echo htmlspecialchars($ll); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Watering Need</label>
                            <?php foreach($water_levels as $w): ?>
                                <div class="form-check small mb-1">
                                    <input class="form-check-input" type="checkbox" name="water[]" value="<?php echo htmlspecialchars($w); ?>" onchange="this.form.submit()" <?php echo in_array($w, $f_water) ? 'checked' : ''; ?>>
                                    <label class="form-check-label"><?php echo htmlspecialchars($w); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Growth Speed</label>
                            <?php foreach($growth_levels as $g): ?>
                                <div class="form-check small mb-1">
                                    <input class="form-check-input" type="checkbox" name="growth[]" value="<?php echo htmlspecialchars($g); ?>" onchange="this.form.submit()" <?php echo in_array($g, $f_growth) ? 'checked' : ''; ?>>
                                    <label class="form-check-label"><?php echo htmlspecialchars($g); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <a href="user_search.php" class="btn btn-sm w-100 text-decoration-none text-success fw-bold border">Clear All Filters</a>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <form action="user_search.php" method="GET" class="mb-4">
                <?php foreach($f_cat as $cat): ?><input type="hidden" name="cat[]" value="<?php echo htmlspecialchars($cat); ?>"><?php endforeach; ?>
                <?php foreach($f_water as $w): ?><input type="hidden" name="water[]" value="<?php echo htmlspecialchars($w); ?>"><?php endforeach; ?>
                <?php foreach($f_growth as $g): ?><input type="hidden" name="growth[]" value="<?php echo htmlspecialchars($g); ?>"><?php endforeach; ?>
                <?php foreach($f_house as $house): ?><input type="hidden" name="house[]" value="<?php echo htmlspecialchars($house); ?>"><?php endforeach; ?>
                <?php foreach($f_light as $light): ?><input type="hidden" name="light[]" value="<?php echo htmlspecialchars($light); ?>"><?php endforeach; ?>
                
                <div class="input-group shadow-sm rounded-pill overflow-hidden border bg-white">
                    <input type="text" name="keyword" class="form-control border-0 py-3 px-4" placeholder="Search plants, house type or sunlight needs..." value="<?php echo htmlspecialchars($keyword); ?>">
                    <button class="btn btn-success px-4" type="submit" style="background-color: #104206;"><i class="bi bi-search"></i></button>
                </div>
            </form>

            <div class="row g-4">
                <?php 
                $query = "SELECT * FROM `$table` $final_where LIMIT $start, $limit";
                $result = mysqli_query($conn, $query);

                if ($result && mysqli_num_rows($result) > 0):
                    while($row = mysqli_fetch_assoc($result)):
                        // Extraction mapping matching CSV column structures
                        $db_common_name     = get_db_value($row, 'Common_Name') ?? 'Unnamed Plant';
                        $db_scientific_name = get_db_value($row, 'Scientific_Name') ?? 'Species Unknown';
                        
                        // Parse active categories flags from columns
                        $active_categories = [];
                        if ((get_db_value($row, 'Categories_Climbers & Vines') ?? 0) == 1) $active_categories[] = "Climbers & Vines";
                        if ((get_db_value($row, 'Categories_Edibles & Herbs') ?? 0) == 1) $active_categories[] = "Edibles & Herbs";
                        if ((get_db_value($row, 'Categories_Ferns') ?? 0) == 1) $active_categories[] = "Ferns";
                        if ((get_db_value($row, 'Categories_Foliage Plants') ?? 0) == 1) $active_categories[] = "Foliage Plants";
                        if ((get_db_value($row, 'Categories_Palms') ?? 0) == 1) $active_categories[] = "Palms";
                        if ((get_db_value($row, 'Categories_Shrubs') ?? 0) == 1) $active_categories[] = "Shrubs";
                        if ((get_db_value($row, 'Categories_Succulents & Cacti') ?? 0) == 1) $active_categories[] = "Succulents & Cacti";
                        if ((get_db_value($row, 'Categories_Trees') ?? 0) == 1) $active_categories[] = "Trees";
                        $display_category = !empty($active_categories) ? implode(', ', $active_categories) : 'Uncategorized';

                        // Parse active house type flags from columns
                        $active_house_types = [];
                        if ((get_db_value($row, 'House_Type_High-rise (Apartment/Flat)') ?? 0) == 1) $active_house_types[] = "High-rise (Apartment)";
                        if ((get_db_value($row, 'House_Type_Landed (Terrace/Semi-D/Bungalow)') ?? 0) == 1) $active_house_types[] = "Landed Property";
                        if ((get_db_value($row, 'House_Type_Tradisional/Spacious (Village House)') ?? 0) == 1) $active_house_types[] = "Spacious Village House";
                        $display_house = !empty($active_house_types) ? implode(', ', $active_house_types) : 'Any Environment';

                        $db_light           = get_db_value($row, 'Light_Condition') ?? '';
                        $db_water           = get_db_value($row, 'Water_Frequency') ?? '';
                        $db_growth          = get_db_value($row, 'Growth_Rate') ?? '';
                        $db_space           = get_db_value($row, 'Space_Required') ?? '';
                        $db_image           = get_db_value($row, 'Image_URL') ?? '';
                        $db_id              = get_db_value($row, 'Number') ?? get_db_value($row, 'ID') ?? 0;

                        $names = explode(',', $db_common_name);
                        $primary_common = trim($names[0]);
                        $aka = (count($names) > 1) ? implode(', ', array_slice($names, 1)) : null;

                        // Numerical translations matching original dataset constraints
                        $display_light  = map_light_label($db_light);
                        $display_water  = map_water_label($db_water);
                        $display_growth = map_growth_label($db_growth);
                        $display_space  = map_space_label($db_space);

                        // Original Image logic cleanly preserved
                        if (!empty($db_image)) {
                            $imageSource = $db_image;
                        } else {
                            $imageSource = 'https://images.unsplash.com/photo-1545241047-6083a3684587?w=150'; 
                        }
                ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card plant-card shadow-sm">
                        <div class="card-body d-flex flex-column p-4 text-center">
                            
                            <div class="mb-3">
                                <img src="<?php echo $imageSource; ?>" class="plant-img-circle" alt="<?php echo htmlspecialchars($primary_common); ?>">
                            </div>

                            <div class="text-start w-100 flex-grow-1 d-flex flex-column">
                                <div class="mb-2">
                                    <span class="badge-custom text-uppercase"><?php echo htmlspecialchars($display_category); ?></span>
                                </div>
                                
                                <h5 class="scientific-name mb-1"><?php echo htmlspecialchars($db_scientific_name); ?></h5>
                                <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($primary_common); ?></h6>
                                
                                <?php if($aka): ?>
                                    <p class="text-muted mb-3" style="font-size: 0.75rem;"><strong>Other Names:</strong> <?php echo htmlspecialchars($aka); ?></p>
                                <?php else: ?>
                                    <div class="mb-4"></div>
                                <?php endif; ?>
                                
                                <div class="row g-2 mb-4">
                                    <div class="col-12 mb-2">
                                        <small class="tech-label d-block">House Type</small>
                                        <small class="fw-bold text-dark"><?php echo htmlspecialchars($display_house); ?></small>
                                    </div>
                                    <div class="col-12">
                                        <small class="tech-label d-block">Lighting Need</small>
                                        <small class="fw-bold text-dark"><?php echo htmlspecialchars($display_light); ?></small>
                                    </div>
                                </div>

                                <div class="mt-auto">
                                    <div class="d-flex flex-wrap gap-1 mb-3">
                                        <span class="badge-custom text-primary"><i class="bi bi-droplet"></i> <?php echo htmlspecialchars($display_water); ?></span>
                                        <span class="badge-custom text-warning"><i class="bi bi-graph-up"></i> <?php echo htmlspecialchars($display_growth); ?></span>
                                        <span class="badge-custom text-info"><i class="bi bi-arrows-fullscreen"></i> <?php echo htmlspecialchars($display_space); ?></span>
                                    </div>
                                    
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="user_plant_details.php?id=<?php echo urlencode($db_id); ?>" class="btn btn-outline-success btn-sm flex-grow-1 rounded-pill fw-bold">View Details</a>
                                        
                                        <button onclick="ajaxAction('user_add_favorite.php?id=<?php echo urlencode($db_id); ?>', 'Added to Favorites!')" class="btn btn-outline-danger btn-circle shadow-sm">
                                            <i class="bi bi-heart-fill"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <?php endwhile; else: ?>
                    <div class="col-12 text-center py-5 text-muted">
                        <i class="bi bi-search display-4"></i>
                        <p class="mt-3 fs-5 fw-bold">No plants matched your criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-center mt-5">
                <nav aria-label="Page navigation">
                    <ul class="pagination mb-0 flex-wrap justify-content-center">
                        
                        <?php 
                        $max_visible_pages = 5;
                        $start_page = max(1, $page - floor($max_visible_pages / 2));
                        $end_page = min($total_pages, $start_page + $max_visible_pages - 1);
                        
                        if (($end_page - $start_page + 1) < $max_visible_pages) {
                            $start_page = max(1, $end_page - $max_visible_pages + 1);
                        }
                        ?>

                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link shadow-sm" href="user_search.php?page=1<?php echo $query_prefix; ?>" title="First Page"><i class="bi bi-chevron-double-left"></i></a>
                            </li>
                        <?php endif; ?>

                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link shadow-sm" href="user_search.php?page=<?php echo ($page - 1); ?><?php echo $query_prefix; ?>"><i class="bi bi-chevron-left"></i> Prev</a>
                        </li>

                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo ($page === $i) ? 'active' : ''; ?>">
                                <a class="page-link shadow-sm" href="user_search.php?page=<?php echo $i; ?><?php echo $query_prefix; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link shadow-sm" href="user_search.php?page=<?php echo ($page + 1); ?><?php echo $query_prefix; ?>">Next <i class="bi bi-chevron-right"></i></a>
                        </li>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link shadow-sm" href="user_search.php?page=<?php echo $total_pages; ?><?php echo $query_prefix; ?>" title="Last Page"><i class="bi bi-chevron-double-right"></i></a>
                            </li>
                        <?php endif; ?>

                    </ul>
                </nav>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
function ajaxAction(url, message) {
    fetch(url)
    .then(response => {
        alert(message);
    })
    .catch(error => {
        alert('Something went wrong. Please try again.');
    });
}
</script>

<?php include('footer.php'); ?>
</body>
</html>