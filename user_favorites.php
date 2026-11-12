<?php 
// 1. BACKEND AJAX LOGIC: Handles deletion requests before anything else outputs
if (isset($_GET['action']) && $_GET['action'] === 'unfavorite' && isset($_GET['id'])) {
    session_start();
    include('db.php');
    
    if (isset($_SESSION['user_id'])) {
        $userID = mysqli_real_escape_string($conn, $_SESSION['user_id']);
        $plantID = mysqli_real_escape_string($conn, $_GET['id']);

        $query = "DELETE FROM favorite WHERE userID = '$userID' AND plantID = '$plantID'";
        if (mysqli_query($conn, $query)) {
            echo "success";
        } else {
            http_response_code(500);
            echo "error";
        }
    } else {
        http_response_code(401);
        echo "unauthorized";
    }
    exit; 
}

// 2. NORMAL PAGE DISPLAY LOGIC
include('private_header.php'); 
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$userID = $_SESSION['user_id'];

// Fetch ALL favorites explicitly assigning f.plantID to secure parameters
$sql = "SELECT f.dateSaved, f.plantID, p.* FROM favorite f 
        JOIN plantmatch_ultimate_complete p ON f.plantID = p.ID 
        WHERE f.userID = '$userID' 
        ORDER BY f.dateSaved DESC";
$result = mysqli_query($conn, $sql);

// Helper mappers to translate numeric CSV metrics into clean corporate labels
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; }
        .plant-card { transition: all 0.3s ease; border: none; border-radius: 20px; overflow: hidden; background: white; height: 100%; display: flex; flex-direction: column; position: relative; }
        .plant-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important; }
        .badge-custom { font-weight: 500; font-size: 0.7rem; padding: 4px 8px; border-radius: 6px; background-color: #f8f9fa; color: #104206; border: 1px solid #e9ecef; }
        .scientific-name { font-style: italic; color: #104206; font-family: 'Georgia', serif; }
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

        /* Top-Left Compare Checkbox Position Styling */
        .compare-check-container {
            position: absolute;
            top: 15px;
            left: 15px;
            z-index: 10;
        }
        .compare-box {
            width: 22px;
            height: 22px;
            cursor: pointer;
            accent-color: #104206;
        }

        /* Bottom Floating Compare Trigger Button Bar */
        .compare-floating-bar {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            display: none; /* Controlled via JS toggles */
            transition: bottom 0.1s ease-out;
        }
    </style>
</head>
<body>

<main class="py-5 min-vh-100">
    <div class="container">
        <div class="mb-5 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="fw-bold text-dark"><i class="bi bi-heart-fill text-danger me-2"></i> My Favorite Plants</h2>
                <p class="text-muted mb-0">A collection of plants you've liked while browsing. Check the boxes to select plants to compare.</p>
            </div>
        </div>

        <div class="row g-4" id="favorite-grid">
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): 
                    $pID = $row['plantID'] ?? $row['ID'];
                    $names = explode(',', $row['Common_Name'] ?? 'Unnamed Plant');
                    $primary_common = trim($names[0]);
                    
                    // Reconstruct text category label from the CSV one-hot encoded headers
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
                        if (isset($row[$col_key]) && (float)$row[$col_key] == 1.0) {
                            $category_display = $col_label;
                            break;
                        }
                    }

                    $display_light = map_light_label($row['Light_Condition'] ?? 0.5);
                    $display_water = map_water_label($row['Water_Frequency'] ?? 0.5);

                    if (!empty($row['Image_URL'])) {
                        $imageSource = $row['Image_URL'];
                    } else {
                        $imageSource = 'https://images.unsplash.com/photo-1545241047-6083a3684587?w=150'; 
                    }
                ?>
                <div class="col-md-6 col-xl-4 favorite-card-item" id="plant-item-<?php echo htmlspecialchars($pID); ?>">
                    <div class="card plant-card shadow-sm">
                        
                        <div class="compare-check-container d-flex align-items-center gap-1 bg-white px-2 py-1 rounded shadow-sm border">
                            <input type="checkbox" class="compare-box form-check-input m-0" value="<?php echo htmlspecialchars($pID); ?>" onchange="toggleCompareBar()">
                            <small class="fw-bold text-muted px-1" style="font-size: 0.65rem;">Compare</small>
                        </div>

                        <div class="card-body d-flex flex-column p-4 text-center">
                            
                            <div class="mb-3 mt-3">
                                <img src="<?php echo $imageSource; ?>" class="plant-img-circle" alt="<?php echo htmlspecialchars($primary_common); ?>">
                            </div>

                            <div class="text-start w-100 flex-grow-1 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge-custom text-uppercase"><?php echo htmlspecialchars($category_display); ?></span>
                                    <small class="text-muted" style="font-size: 0.7rem;"><?php echo date('M d, Y', strtotime($row['dateSaved'])); ?></small>
                                </div>
                                
                                <h5 class="scientific-name mb-1"><?php echo htmlspecialchars($row['Scientific_Name'] ?? 'Species Unknown'); ?></h5>
                                <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($primary_common); ?></h6>
                                
                                <div class="row g-2 my-3">
                                    <div class="col-12 mb-1">
                                        <small class="tech-label d-block">Lighting Requirement</small>
                                        <small class="fw-bold text-success"><?php echo htmlspecialchars($display_light); ?></small>
                                    </div>
                                    <div class="col-12">
                                        <small class="tech-label d-block">Watering Frequency</small>
                                        <small class="fw-bold text-dark"><?php echo htmlspecialchars($display_water); ?></small>
                                    </div>
                                </div>

                                <div class="mt-auto d-flex align-items-center gap-2">
                                    <a href="user_plant_details.php?id=<?php echo urlencode($pID); ?>" class="btn btn-outline-success btn-sm flex-grow-1 rounded-pill fw-bold">View Details</a>
                                    
                                    <button onclick="unfavoritePlant('<?php echo htmlspecialchars($pID); ?>')" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold">
                                        <i class="bi bi-heartbreak-fill me-1"></i> Remove
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5 text-muted" id="empty-message">
                    <i class="bi bi-heart text-muted display-1 opacity-50 d-block mb-3"></i>
                    You haven't added any favorites yet.
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<div class="compare-floating-bar" id="compareBar">
    <div class="d-flex gap-2 shadow-lg rounded-pill p-1 bg-white border">
        <button onclick="redirectToCompare()" class="btn text-white rounded-pill px-4 fw-bold d-flex align-items-center gap-2" style="background-color: #104206; border: none;">
            <i class="bi bi-arrow-left-right"></i>
            Compare Selected (<span id="compareCount">0</span>)
        </button>
        <button onclick="untickAll()" class="btn btn-danger rounded-pill px-3 fw-bold d-flex align-items-center gap-2">
            <i class="bi bi-x-circle"></i>
            Untick All
        </button>
    </div>
</div>

<script>
// Logic to handle floating bar visibility and calculate count metrics
function toggleCompareBar() {
    const checkedBoxes = document.querySelectorAll('.compare-box:checked');
    const compareBar = document.getElementById('compareBar');
    const compareCount = document.getElementById('compareCount');
    
    compareCount.textContent = checkedBoxes.length;
    
    if (checkedBoxes.length > 0) {
        compareBar.style.display = 'block';
        adjustCompareBarPosition(); // Dynamic layout position recalculation
    } else {
        compareBar.style.display = 'none';
    }
}

// Clears all selected checkboxes instantly
function untickAll() {
    const checkedBoxes = document.querySelectorAll('.compare-box:checked');
    checkedBoxes.forEach(box => {
        box.checked = false;
    });
    toggleCompareBar();
}

// Packages checked values and redirects safely to your comparison target page context
function redirectToCompare() {
    const checkedBoxes = document.querySelectorAll('.compare-box:checked');
    const plantIds = [];
    
    checkedBoxes.forEach(box => {
        plantIds.push(box.value);
    });
    
    window.location.href = `user_compare.php?ids=${encodeURIComponent(plantIds.join(','))}`;
}

function unfavoritePlant(plantId) {
    if(confirm('Remove this plant from your favorites?')) {
        const currentFile = window.location.pathname.split("/").pop();

        fetch(`${currentFile}?action=unfavorite&id=${encodeURIComponent(plantId)}`)
        .then(response => {
            if (response.ok) {
                const itemElement = document.getElementById(`plant-item-${plantId}`);
                if(itemElement) {
                    itemElement.remove();
                }
                
                toggleCompareBar(); 
                
                const remainingCards = document.querySelectorAll('.favorite-card-item');
                if(remainingCards.length === 0) {
                    document.getElementById('favorite-grid').innerHTML = `
                        <div class="col-12 text-center py-5 text-muted">
                            <i class="bi bi-heart text-muted display-1 opacity-50 d-block mb-3"></i>
                            You haven't added any favorites yet.
                        </div>`;
                }
            } else {
                alert('Could not update record inside database storage.');
            }
        })
        .catch(error => {
            alert('Something went wrong. Please try again.');
        });
    }
}

// Automatically realigns the floating bar position to stay perfectly above the footer context limits
function adjustCompareBarPosition() {
    const compareBar = document.getElementById('compareBar');
    if (!compareBar || compareBar.style.display === 'none') return;

    const scrollHeight = document.documentElement.scrollHeight;
    const scrollPosition = window.innerHeight + window.scrollY;
    
    const footer = document.querySelector('footer');
    if (footer) {
        const footerHeight = footer.offsetHeight;
        const distanceFromBottom = scrollHeight - scrollPosition;
        
        if (distanceFromBottom < footerHeight) {
            // Push button above footer bounds dynamically
            const newBottom = 30 + (footerHeight - distanceFromBottom);
            compareBar.style.bottom = `${newBottom}px`;
        } else {
            compareBar.style.bottom = '30px';
        }
    }
}

// Attach event tracking parameters for position adjustments
window.addEventListener('scroll', adjustCompareBarPosition);
window.addEventListener('resize', adjustCompareBarPosition);
</script>

<?php include('footer.php'); ?>
</body>
</html>