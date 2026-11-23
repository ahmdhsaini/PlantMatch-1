<?php
// user_process_recommendation_form.php
include('private_header.php');
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$userID = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Capture all 9 form factors securely
    $formData = [
        'houseType'        => $_POST['houseType'] ?? '',
        'gardeningLevel'   => $_POST['gardeningLevel'] ?? '',
        'availableSpace'   => $_POST['availableSpace'] ?? '',
        'lightCondition'   => $_POST['lightCondition'] ?? '',
        'growthRate'       => $_POST['growthRate'] ?? '',
        'plantPreference'  => $_POST['plantPreference'] ?? '',
        'waterFreq'        => $_POST['waterFreq'] ?? '',
        'humidityNeed'     => $_POST['humidityNeed'] ?? '',
        'matureHeight'     => $_POST['matureHeight'] ?? '200'
    ];

    // 2. Save or update quiz preferences in MySQL
    $stmt = $conn->prepare("INSERT INTO preference (userID, houseType, gardeningLevel, availableSpace, lightCondition, growthRate, plantPreference, waterFreq, humidityNeed, matureHeight) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        houseType=VALUES(houseType), gardeningLevel=VALUES(gardeningLevel), availableSpace=VALUES(availableSpace), lightCondition=VALUES(lightCondition), growthRate=VALUES(growthRate), plantPreference=VALUES(plantPreference), waterFreq=VALUES(waterFreq), humidityNeed=VALUES(humidityNeed), matureHeight=VALUES(matureHeight)");
    
    $stmt->bind_param("sssssssssi", $userID, $formData['houseType'], $formData['gardeningLevel'], $formData['availableSpace'], $formData['lightCondition'], $formData['growthRate'], $formData['plantPreference'], $formData['waterFreq'], $formData['humidityNeed'], $formData['matureHeight']);
    $stmt->execute();
    $stmt->close();

    // 3. BRIDGE ENGINE: Package the data for Python execution
    $jsonArgs = escapeshellarg(json_encode($formData));
    $pythonPath = "python"; // Use "python" or full environment path depending on your server configuration
    $command = "$pythonPath recommend.py " . escapeshellarg($userID) . " " . $jsonArgs . " 2>&1";

    // Run the Python script and catch the printed JSON array string
    $output = shell_exec($command);
    $recommendedIDs = json_decode($output, true);

    // If parsing fails, fall back to a safe default list of highly rated plant IDs
    if (!is_array($recommendedIDs)) {
        $recommendedIDs = [1, 2, 3, 4, 5, 6]; 
    }

    // 4. Store the recommendations into the session and redirect to the results page
    $_SESSION['recommended_plant_ids'] = $recommendedIDs;
    header("Location: user_view_recommendations.php");
    exit();
}