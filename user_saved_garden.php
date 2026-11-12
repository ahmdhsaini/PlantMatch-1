<?php 
// 1. BACKEND AJAX LOGIC: Handles deletion requests before anything else outputs
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    session_start();
    include('db.php');
    
    if (isset($_SESSION['user_id'])) {
        $userID = mysqli_real_escape_string($conn, $_SESSION['user_id']);
        $plantID = mysqli_real_escape_string($conn, $_GET['id']);

        $query = "DELETE FROM save WHERE userID = '$userID' AND plantID = '$plantID'";
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

// 2. NORMAL PAGE LOADING LOGIC
include('private_header.php'); 
include('db.php');

if (!isset($_SESSION['user_id'])) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Please log in to view your garden.</div></div>";
    include('footer.php');
    exit;
}

$userID = $_SESSION['user_id'];

// Fetch saved plants
$sql = "SELECT s.saveTime, s.plantID, p.* FROM save s 
        JOIN plantmatch_ultimate_complete p ON s.plantID = p.ID 
        WHERE s.userID = '$userID' 
        ORDER BY s.saveTime DESC";
$result = mysqli_query($conn, $sql);
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

        /* 1. ADDED: Top-Left Compare Checkbox Styling */
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

        /* 2. ADDED: Floating Compare Bar Placement */
        .compare-floating-bar {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            display: none;
        }
    </style>
</head>
<body>

<main class="py-5 min-vh-100">
    <div class="container">
        <div class="mb-5">
            <h2 class="fw-bold" style="color: #104206;"><i class="bi bi-bookmark-check-fill me-2"></i> My Saved Garden</h2>
            <p class="text-muted">Plants you have saved for your personal garden collection. Check the boxes to select plants to compare.</p>
        </div>

        <div class="row g-4" id="garden-grid">
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): 
                    $names = explode(',', $row['Common_Name']);
                    $primary_common = trim($names[0]);
                    
                    if (!empty($row['Image_URL'])) {
                        $imageSource = $row['Image_URL'];
                    } else {
                        $imageSource = 'https://images.unsplash.com/photo-1545241047-6083a3684587?w=150'; 
                    }
                ?>
                <div class="col-md-6 col-xl-4 plant-card-item" id="plant-item-<?php echo $row['plantID']; ?>">
                    <div class="card plant-card shadow-sm">
                        
                        <div class="compare-check-container d-flex align-items-center gap-1 bg-white px-2 py-1 rounded shadow-sm border">
                            <input type="checkbox" class="compare-box form-check-input m-0" value="<?php echo $row['plantID']; ?>" onchange="toggleCompareBar()">
                            <small class="fw-bold text-muted px-1" style="font-size: 0.65rem;">Compare</small>
                        </div>

                        <div class="card-body d-flex flex-column p-4 text-center">
                            
                            <div class="mb-3 mt-3">
                                <img src="<?php echo $imageSource; ?>" class="plant-img-circle" alt="<?php echo htmlspecialchars($primary_common); ?>">
                            </div>

                            <div class="text-start w-100 flex-grow-1 d-flex flex-column">
                                <div class="mb-2">
                                    <span class="badge-custom text-uppercase"><?php echo $row['Categories']; ?></span>
                                </div>
                                
                                <h5 class="scientific-name mb-1"><?php echo $row['Scientific_Name']; ?></h5>
                                <h6 class="fw-bold text-dark mb-1"><?php echo $primary_common; ?></h6>
                                <p class="text-muted mb-3" style="font-size: 0.75rem;">
                                    <strong>Saved:</strong> <?php echo date('M d, Y', strtotime($row['saveTime'])); ?>
                                </p>
                                
                                <div class="row g-2 mb-4">
                                    <div class="col-12 mb-2">
                                        <small class="tech-label d-block">Gardening Level</small>
                                        <small class="fw-bold text-dark"><?php echo $row['Gardening_Level']; ?></small>
                                    </div>
                                    <div class="col-12">
                                        <small class="tech-label d-block">Watering Needs</small>
                                        <small class="fw-bold text-dark"><?php echo $row['Water_Frequency']; ?></small>
                                    </div>
                                </div>

                                <div class="mt-auto d-flex align-items-center gap-2">
                                    <a href="user_plant_details.php?id=<?php echo $row['plantID']; ?>" class="btn btn-outline-success btn-sm flex-grow-1 rounded-pill fw-bold">View Details</a>
                                    
                                    <button onclick="unsavePlant(<?php echo $row['plantID']; ?>)" class="btn btn-sm btn-danger rounded-pill px-3 fw-bold">
                                        <i class="bi bi-bookmark-x-fill me-1"></i> Unsave
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5 text-muted" id="empty-message">
                    <i class="bi bi-tree mb-3 d-block display-4 text-success opacity-50"></i>
                    Your garden is empty. Start adding some plants!
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<div class="compare-floating-bar" id="compareBar">
    <button onclick="redirectToCompare()" class="btn btn-lg text-white shadow-lg rounded-pill px-4 fw-bold d-flex align-items-center gap-2" style="background-color: #104206;">
        <i class="bi bi-arrow-left-right"></i>
        Compare Selected (<span id="compareCount">0</span>)
    </button>
</div>

<script>
// Monitors checkboxes and shows/hides the comparison button dynamically
function toggleCompareBar() {
    const checkedBoxes = document.querySelectorAll('.compare-box:checked');
    const compareBar = document.getElementById('compareBar');
    const compareCount = document.getElementById('compareCount');
    
    compareCount.textContent = checkedBoxes.length;
    
    if (checkedBoxes.length > 0) {
        compareBar.style.display = 'block';
    } else {
        compareBar.style.display = 'none';
    }
}

// 2. Redirects to user_compare.php with comma-separated plant IDs
function redirectToCompare() {
    const checkedBoxes = document.querySelectorAll('.compare-box:checked');
    const plantIds = [];
    
    checkedBoxes.forEach(box => {
        plantIds.push(box.value);
    });
    
    window.location.href = `user_compare.php?ids=${plantIds.join(',')}`;
}

function unsavePlant(plantId) {
    if(confirm('Are you sure you want to remove this plant from your garden?')) {
        const currentFile = window.location.pathname.split("/").pop();

        fetch(`${currentFile}?action=delete&id=${plantId}`)
        .then(response => {
            if (response.ok) {
                const itemElement = document.getElementById(`plant-item-${plantId}`);
                if(itemElement) {
                    itemElement.remove();
                }
                
                toggleCompareBar(); // Recalculate remaining selections
                
                const remainingCards = document.querySelectorAll('.plant-card-item');
                if(remainingCards.length === 0) {
                    document.getElementById('garden-grid').innerHTML = `
                        <div class="col-12 text-center py-5 text-muted">
                            <i class="bi bi-tree mb-3 d-block display-4 text-success opacity-50"></i>
                            Your garden is empty. Start adding some plants!
                        </div>`;
                }
            } else {
                alert('Could not delete the record from database.');
            }
        })
        .catch(error => {
            alert('Something went wrong. Please try again.');
        });
    }
}
</script>

<?php include('footer.php'); ?>
</body>
</html>