<?php 
include('private_header.php'); 
include('db.php');

// 1. Get the comma-separated string of IDs from the URL
$raw_ids = isset($_GET['ids']) ? trim($_GET['ids']) : '';

$plants = [];
$error_message = null;

if (!empty($raw_ids)) {
    $id_array = explode(',', $raw_ids);
    
    // Maximum check enforcement (Max 5 items allowed)
    if (count($id_array) > 5) {
        $error_message = "You can only compare a maximum of 5 plants at the same time. Please reduce your selection.";
    } else {
        // Sanitize strings/integers to protect against SQL injections
        $clean_ids = array_map(function($val) use ($conn) {
            return "'" . mysqli_real_escape_string($conn, trim($val)) . "'";
        }, $id_array);
        
        $ids_string = implode(',', $clean_ids);
        
        if (!empty($ids_string)) {
            $sql = "SELECT * FROM `plantmatch_ultimate_complete` WHERE `ID` IN ($ids_string)";
            $result = mysqli_query($conn, $sql);
            
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $plants[] = $row;
                }
            }
        }
    }
} else {
    $error_message = "No plants selected for comparison. Please go back and pick your choices.";
}

// Data conversion helpers matching dataset metric systems
if (!function_exists('map_comparison_light')) {
    function map_comparison_light($val) {
        $f = (float)$val;
        if ($f <= 0.2) return "Low Light / Shade";
        if ($f <= 0.7) return "Partial Sun / Indoor";
        return "Full Direct Sun";
    }
}

if (!function_exists('map_comparison_water')) {
    function map_comparison_water($val) {
        $f = (float)$val;
        if ($f <= 0.2) return "Low (Drought Tolerant)";
        if ($f <= 0.7) return "Moderate (Weekly)";
        return "High (Consistently Wet)";
    }
}

if (!function_exists('map_comparison_level')) {
    function map_comparison_level($val) {
        $f = (float)$val;
        if ($f <= 0.3) return "Beginner Friendly";
        if ($f <= 0.7) return "Intermediate";
        return "Advanced / Expert";
    }
}

if (!function_exists('map_comparison_scale')) {
    function map_comparison_scale($val) {
        $f = (float)$val;
        if ($f <= 0.3) return "Low";
        if ($f <= 0.7) return "Moderate";
        return "High";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Plant Comparison Matrix</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; }
        
        .comparison-table th {
            background-color: #ffffff !important;
            color: #333333 !important;
            font-weight: bold;
            vertical-align: middle;
            border-bottom: 2px solid #dee2e6 !important;
        }
        
        .comparison-table td {
            vertical-align: middle;
            font-size: 0.95rem;
        }
        
        .attr-label {
            background-color: #f8f9fa !important;
            font-weight: bold;
            color: #495057;
            width: 180px;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
        
        .plant-img-thumb {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #f4f7f6;
            box-shadow: 0 4px 10px rgba(16, 66, 6, 0.15);
            background-color: #fff;
        }
        
        .scientific-title {
            font-style: italic;
            font-family: 'Georgia', serif;
            color: #104206;
        }
    </style>
</head>
<body>

<div class="container mt-5 mb-5">
    <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="fw-bold" style="color: #104206;"><i class="bi bi-arrow-left-right me-2"></i> Plant Comparison</h2>
            <p class="text-muted mb-0">Reviewing and analyzing the structural parameters of your selected specimens side-by-side.</p>
        </div>
        
        <button onclick="goBack()" class="btn btn-outline-success rounded-pill fw-bold">
            <i class="bi bi-arrow-left me-1"></i> Back to Previous Page
        </button>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-warning shadow-sm rounded-4 p-4">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-2 align-middle"></i>
            <span class="align-middle fw-bold"><?php echo $error_message; ?></span>
        </div>
    <?php else: ?>

        <div class="table-responsive bg-white shadow-sm rounded-4 p-2">
            <table class="table table-bordered comparison-table mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="attr-label">Plant Preview</th>
                        <?php foreach($plants as $plant): ?>
                            <th class="text-center" style="min-width: 220px;">
                                <?php 
                                if (!empty($plant['Image_URL'])) {
                                    $imgSrc = $plant['Image_URL'];
                                } else {
                                    $imgSrc = 'https://images.unsplash.com/photo-1545241047-6083a3684587?w=150';
                                }
                                ?>
                                <div class="my-2">
                                    <img src="<?php echo $imgSrc; ?>" class="plant-img-thumb" alt="Plant thumb">
                                </div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="attr-label">Plant Names</td>
                        <?php foreach($plants as $plant): 
                            $names = explode(',', $plant['Common_Name'] ?? 'Unnamed Plant');
                            $primary_common = trim($names[0]);
                            $aka = (count($names) > 1) ? implode(', ', array_slice($names, 1)) : null;
                        ?>
                            <td class="text-center py-3">
                                <h5 class="scientific-title mb-1 fs-5"><?php echo htmlspecialchars($plant['Scientific_Name'] ?? 'Species N/A'); ?></h5>
                                <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($primary_common); ?></h6>
                                <?php if($aka && trim($aka) !== ''): ?>
                                    <p class="text-muted mb-0" style="font-size: 0.75rem;"><strong>Other Names:</strong> <?php echo htmlspecialchars($aka); ?></p>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td class="attr-label">Category</td>
                        <?php foreach($plants as $plant): 
                            $category_display = 'General';
                            $category_cols = [
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
                            foreach ($category_cols as $col_key => $col_label) {
                                if (isset($plant[$col_key]) && (float)$plant[$col_key] == 1.0) {
                                    $category_display = $col_label;
                                    break;
                                }
                            }
                        ?>
                            <td class="text-center"><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($category_display); ?></span></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td class="attr-label">Gardening Level</td>
                        <?php foreach($plants as $plant): ?>
                            <td class="text-center fw-bold text-dark"><?php echo htmlspecialchars(map_comparison_level($plant['Gardening_Level'] ?? 0.5)); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td class="attr-label">Watering Schedule</td>
                        <?php foreach($plants as $plant): ?>
                            <td class="text-center text-primary"><i class="bi bi-droplet-fill small me-1"></i><?php echo htmlspecialchars(map_comparison_water($plant['Water_Frequency'] ?? 0.5)); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td class="attr-label">Light Condition</td>
                        <?php foreach($plants as $plant): ?>
                            <td class="text-center text-warning fw-bold"><i class="bi bi-brightness-high-fill small me-1"></i><?php echo htmlspecialchars(map_comparison_light($plant['Light_Condition'] ?? 0.5)); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td class="attr-label">Growth Rate</td>
                        <?php foreach($plants as $plant): ?>
                            <td class="text-center"><?php echo htmlspecialchars(map_comparison_scale($plant['Growth_Rate'] ?? 0.5)); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td class="attr-label">House Compatibility</td>
                        <?php foreach($plants as $plant): 
                            $types = [];
                            if (isset($plant['House_Type_High-rise (Apartment/Flat)']) && (float)$plant['House_Type_High-rise (Apartment/Flat)'] == 1.0) {
                                $types[] = "High-rise";
                            }
                            if (isset($plant['House_Type_Landed (Terrace/Semi-D/Bungalow)']) && (float)$plant['House_Type_Landed (Terrace/Semi-D/Bungalow)'] == 1.0) {
                                $types[] = "Landed";
                            }
                            if (isset($plant['House_Type_Tradisional/Spacious (Village House)']) && (float)$plant['House_Type_Tradisional/Spacious (Village House)'] == 1.0) {
                                $types[] = "Spacious/Traditional";
                            }
                            $house_display = !empty($types) ? implode(', ', $types) : 'Any Environment';
                        ?>
                            <td class="text-center text-secondary"><i class="bi bi-house-door small me-1"></i><?php echo htmlspecialchars($house_display); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td class="attr-label">Space Required</td>
                        <?php foreach($plants as $plant): ?>
                            <td class="text-center"><?php echo htmlspecialchars(map_comparison_scale($plant['Space_Required'] ?? 0.5)); ?> Space</td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td class="attr-label">Humidity Needed</td>
                        <?php foreach($plants as $plant): ?>
                            <td class="text-center text-info"><?php echo htmlspecialchars(map_comparison_scale($plant['Humidity_Need'] ?? 0.5)); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td class="attr-label">Mature Height Metric</td>
                        <?php foreach($plants as $plant): 
                            // Integrated exact normalization formula from user_plant_details.php
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
                        ?>
                            <td class="text-center fw-bold text-dark"><?php echo htmlspecialchars($display_height); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td class="attr-label">Actions</td>
                        <?php foreach($plants as $plant): ?>
                            <td class="text-center">
                                <a href="user_plant_details.php?id=<?php echo urlencode($plant['ID'] ?? ''); ?>" class="btn btn-sm btn-success rounded-pill px-3 fw-bold">View Profile</a>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>

    <?php endif; ?>
</div>

<script>
// Smart back function: uses the referrer history or falls back to standard user dashboard if history is empty
function goBack() {
    if (document.referrer && document.referrer.trim() !== "") {
        window.location.href = document.referrer;
    } else {
        window.history.back();
    }
}
</script>

<?php include('footer.php'); ?>
</body>
</html>