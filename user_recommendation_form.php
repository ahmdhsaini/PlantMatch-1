<?php 
include('private_header.php'); 
include('db.php');

// Ensure session is started and userID is captured
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$userID = $_SESSION['user_id'];

// --- FETCH PREVIOUS PREFERENCES ---
$sql = "SELECT * FROM preference WHERE userID = '$userID' LIMIT 1";
$res = mysqli_query($conn, $sql);
$existing = mysqli_fetch_assoc($res);
?>

<style>
    /* Compact Card Styling */
    .option-card {
        cursor: pointer;
        border: 1.5px solid #e9ecef;
        border-radius: 10px;
        transition: all 0.2s ease;
        text-align: center;
        padding: 10px; 
        background: white;
        display: block;
        margin-bottom: 0;
    }
    .option-card:hover { border-color: #198754; }
    .btn-check:checked + .option-card {
        border-color: #198754;
        background-color: #f0fff4;
    }
    .option-card i { font-size: 1.2rem; display: block; margin-bottom: 5px; }
    
    .factor-title { font-size: 0.95rem; font-weight: 700; color: #333; margin-bottom: 10px; display: block; }
    .step-badge { font-size: 0.65rem; background: #e9ecef; color: #198754; padding: 2px 8px; border-radius: 5px; margin-bottom: 5px; display: inline-block; }
    
    .compact-section { padding: 15px; margin-bottom: 15px; border-radius: 12px; border: 1px solid #eee; background: #fff; }
    .form-select-sm, .btn-sm { font-size: 0.85rem; }
    
    .green-dropdown:focus {
        border-color: #198754 !important; 
        box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25) !important;
    }
    
    #hSlider::-webkit-slider-thumb { background-color: #198754 !important; }
    #hSlider::-moz-range-thumb     { background-color: #198754 !important; }
    #hSlider:focus { box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25) !important; }
</style>

<header class="py-4 bg-success text-white shadow-sm">
    <div class="container text-center">
        <h2 class="fw-bold mb-1">Plant Match Quiz</h2>
        <p class="small mb-0 opacity-75">Let's find your botanical match in 9 quick steps.</p>
    </div>
</header>

<main class="py-4 bg-light">
    <div class="container">
        <form action="user_process_recommendation_form.php" method="POST">
            <div class="row g-3">
                
                <div class="col-lg-6">
                    
                    <div class="compact-section shadow-sm">
                        <span class="step-badge">Factor 1</span>
                        <label class="factor-title">Living Environment</label>
                        <div class="row gx-2">
                            <?php 
                            $houseTypes = [
                                'High-rise (Apartment/Flat)' => 'layers-fill',
                                'Landed (Terrace/Semi-D/Bungalow)' => 'house-door',
                                'Tradisional/Spacious (Village House)' => 'signpost-2'
                            ];
                            
                            foreach($houseTypes as $val => $icon): 
                                $isChecked = (isset($existing['houseType']) && $existing['houseType'] == $val) ? 'checked' : '';
                            ?>
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="houseType" id="ht_<?php echo md5($val); ?>" value="<?php echo $val; ?>" required <?php echo $isChecked; ?>>
                                <label class="option-card w-100 text-center py-3" for="ht_<?php echo md5($val); ?>" style="min-height: 110px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                                    <i class="bi bi-<?php echo $icon; ?> mb-2" style="font-size: 1.5rem;"></i>
                                    <span class="fw-bold" style="font-size: 0.7rem; line-height: 1.2;">
                                        <?php echo str_replace(' (', '<br>(', $val); ?>
                                    </span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="compact-section shadow-sm">
                        <span class="step-badge">Factor 2</span>
                        <label class="factor-title">Your Experience</label>
                        <div class="row gx-2">
                            <?php 
                            $lvls = [
                                'Beginner' => 'flower1', 
                                'Moderate' => 'thermometer-half', 
                                'Expert'   => 'trophy'
                            ];
                            
                            foreach($lvls as $val => $icon): 
                                $isChecked = (isset($existing['gardeningLevel']) && $existing['gardeningLevel'] == $val) ? 'checked' : '';
                            ?>
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="gardeningLevel" id="skill_<?php echo $val; ?>" value="<?php echo $val; ?>" required <?php echo $isChecked; ?>>
                                <label class="option-card w-100 text-center" for="skill_<?php echo $val; ?>">
                                    <i class="bi bi-<?php echo $icon; ?> d-block mb-1" style="font-size: 1.2rem;"></i>
                                    <span class="small fw-bold d-block"><?php echo $val; ?></span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="compact-section shadow-sm">
                        <span class="step-badge">Factor 3</span>
                        <label class="factor-title">Available Space</label>
                        <div class="row gx-2">
                            <?php 
                            $spaces = [
                                'Small'  => 'house',      
                                'Medium' => 'house-door',   
                                'Large'  => 'bank'      
                            ];
                            
                            foreach($spaces as $label => $icon): 
                                $isChecked = (isset($existing['availableSpace']) && $existing['availableSpace'] == $label) ? 'checked' : '';
                            ?>
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="availableSpace" id="s_<?php echo $label; ?>" value="<?php echo $label; ?>" required <?php echo $isChecked; ?>>
                                <label class="option-card w-100 text-center py-2" for="s_<?php echo $label; ?>">
                                    <i class="bi bi-<?php echo $icon; ?> d-block mb-1" style="font-size: 1.2rem;"></i>
                                    <span class="small fw-bold"><?php echo $label; ?></span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="compact-section shadow-sm">
                        <span class="step-badge">Factor 4</span>
                        <label class="factor-title">Light Exposure</label>
                        <div class="row gx-2">
                            <?php $lights = ['Full Sun' => 'sun-fill', 'Partial Shade' => 'cloud-sun', 'Low Light' => 'brightness-low'];
                            foreach($lights as $val => $icon): 
                                $isChecked = (isset($existing['lightCondition']) && $existing['lightCondition'] == $val) ? 'checked' : '';
                            ?>
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="lightCondition" id="l_<?php echo md5($val); ?>" value="<?php echo $val; ?>" required <?php echo $isChecked; ?>>
                                <label class="option-card w-100" for="l_<?php echo md5($val); ?>">
                                    <i class="bi bi-<?php echo $icon; ?> text-warning"></i>
                                    <span class="small fw-bold"><?php echo $val; ?></span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="compact-section shadow-sm">
                        <span class="step-badge">Factor 5</span>
                        <label class="factor-title">Growth Speed Expectation</label>
                        <div class="row gx-2">
                            <?php 
                            $speeds = ['Slow' => 'hourglass', 'Moderate' => 'speedometer', 'Fast' => 'lightning-charge-fill'];
                            foreach($speeds as $val => $icon): 
                                $isChecked = (isset($existing['growthRate']) && $existing['growthRate'] == $val) ? 'checked' : '';
                            ?>
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="growthRate" id="g_<?php echo $val; ?>" value="<?php echo $val; ?>" required <?php echo $isChecked; ?>>
                                <label class="option-card w-100 text-center" for="g_<?php echo $val; ?>">
                                    <i class="bi bi-<?php echo $icon; ?> text-success"></i>
                                    <span class="small fw-bold"><?php echo $val; ?></span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>

                <div class="col-lg-6">
                    
                    <div class="compact-section shadow-sm">
                        <span class="step-badge">Factor 6</span>
                        <label class="factor-title">Preferred Plant Category</label>
                        <select class="form-select form-select-sm border-success rounded-3 green-dropdown" name="plantPreference" required>
                            <option value="" disabled <?php echo !isset($existing) ? 'selected' : ''; ?>>Choose Category...</option>
                            <?php 
                            $cats = ["Flowering Ornamentals", "Edibles & Herbs", "Climbers & Vines", "Trees", "Foliage Plants", "Ferns", "Palms", "Succulents & Cacti", "Shrubs"];
                            foreach($cats as $cat): 
                                $isSelected = (isset($existing['plantPreference']) && $existing['plantPreference'] == $cat) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $cat; ?>" <?php echo $isSelected; ?>><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="compact-section shadow-sm">
                        <span class="step-badge">Factor 7</span>
                        <label class="factor-title">Watering Commitment</label>
                        <div class="row gx-2">
                            <?php 
                            $wFreqs = [
                                'Daily'   => 'clock-history', 
                                'Weekly'  => 'calendar3', 
                                'Monthly' => 'calendar-check'
                            ];
                            
                            foreach($wFreqs as $wf => $icon): 
                                $isChecked = (isset($existing['waterFreq']) && $existing['waterFreq'] == $wf) ? 'checked' : '';
                            ?>
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="waterFreq" id="w_<?php echo $wf; ?>" value="<?php echo $wf; ?>" required <?php echo $isChecked; ?>>
                                <label class="option-card w-100 text-center py-3" for="w_<?php echo $wf; ?>">
                                    <i class="bi bi-<?php echo $icon; ?> d-block mb-2 text-dark" style="font-size: 1.5rem;"></i>
                                    <span class="small fw-bold d-block text-dark"><?php echo $wf; ?></span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="compact-section shadow-sm">
                        <span class="step-badge">Factor 8</span>
                        <label class="factor-title">Local Ambient Humidity Environment</label>
                        <div class="row gx-2">
                            <?php 
                            $humidities = ['Low' => 'moisture', 'Medium' => 'cloud-fog', 'High' => 'droplet-half'];
                            foreach($humidities as $val => $icon): 
                                $isChecked = (isset($existing['humidityNeed']) && $existing['humidityNeed'] == $val) ? 'checked' : '';
                            ?>
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="humidityNeed" id="h_<?php echo $val; ?>" value="<?php echo $val; ?>" required <?php echo $isChecked; ?>>
                                <label class="option-card w-100 text-center" for="h_<?php echo $val; ?>">
                                    <i class="bi bi-<?php echo $icon; ?> text-info"></i>
                                    <span class="small fw-bold"><?php echo $val; ?></span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="compact-section shadow-sm">
                        <span class="step-badge">Factor 9</span>
                        <?php $savedHeight = isset($existing['matureHeight']) ? $existing['matureHeight'] : 200; ?>
                        <label class="factor-title">Max Height: <span id="hLabel" class="text-success"><?php echo $savedHeight; ?></span>cm</label>
                        
                        <input type="range" class="form-range" name="matureHeight" min="20" max="1500" step="10" value="<?php echo $savedHeight; ?>" id="hSlider">
                        
                        <div class="d-flex justify-content-between x-small text-muted" style="font-size: 0.7rem;">
                            <span>20cm</span>
                            <span>1500cm</span>
                        </div>
                    </div>

                    <div class="mt-4 pt-2">
                        <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow-sm rounded-pill">
                            Find My Match <i class="bi bi-cpu-fill ms-2"></i>
                        </button>
                        <div class="text-center mt-2">
                            <a href="user_dashboard.php" class="text-muted small text-decoration-none">Back to Home</a>
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </div>
</main>

<script>
    const slider = document.getElementById('hSlider');
    const label = document.getElementById('hLabel');
    slider.oninput = function() { label.innerHTML = this.value; }
</script>

<?php include('footer.php'); ?>