<?php
session_start();

// Ensure user session exists
// (Removed the login requirement so guests without a session can chat too)
// if (!isset($_SESSION['user_id'])) {
//     echo json_encode(['error' => 'Unauthorized access']);
//     exit();
// }

// Groq API key: kept in config.php (git-ignored), never in the code.
// Copy config.example.php to config.php and put your own key there.
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}
if (!defined('GROK_API_KEY') || GROK_API_KEY === '') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Chatbot is not set up: add GROK_API_KEY to config.php']);
    exit();
}

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';

if (empty(trim($userMessage))) {
    echo json_encode(['error' => 'Message cannot be empty']);
    exit();
}

// Initialize session chat memory if it doesn't exist
if (!isset($_SESSION['chatbot_history'])) {
    $_SESSION['chatbot_history'] = [];
}

// Save user message to memory timeline
$_SESSION['chatbot_history'][] = [
    "role" => "user",
    "content" => $userMessage
];

// Keep the timeline compact (last 14 messages) to avoid breaking token limits
if (count($_SESSION['chatbot_history']) > 14) {
    $_SESSION['chatbot_history'] = array_slice($_SESSION['chatbot_history'], -14);
}

// =========================================================================
// TRANSLATION ENGINE: Converts numeric float metrics into readable words
// =========================================================================
function translate_light_condition($val) {
    $f = (float)$val;
    if ($f <= 0.2) return "Low Light / Shade";
    if ($f <= 0.7) return "Partial Sun / Indoor";
    return "Full Direct Sun";
}

function translate_water_frequency($val) {
    $f = (float)$val;
    if ($f <= 0.2) return "Low (Drought Tolerant)";
    if ($f <= 0.7) return "Moderate (Weekly Watering)";
    return "High (Consistently Wet Soil)";
}

function translate_gardening_level($val) {
    $f = (float)$val;
    if ($f <= 0.2) return "Beginner Friendly / Easy Care";
    if ($f <= 0.7) return "Intermediate / Moderate Care";
    return "Advanced / Expert Level Care";
}

function translate_generic_scale($val, $lowText, $midText, $highText) {
    $f = (float)$val;
    if ($f <= 0.2) return $lowText;
    if ($f <= 0.7) return $midText;
    return $highText;
}

// --- CONNECT TO LARAGON MYSQL DATABASE ---
$host = '127.0.0.1';
$db   = 'plantmatch_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Explicitly grab text descriptive features columns
    $sql = "SELECT Scientific_Name, Common_Name, Gardening_Level, Space_Required, Light_Condition, Water_Frequency, Growth_Rate, Humidity_Need
            FROM plantmatch_ultimate_complete
            LIMIT 150";

    $stmt = $pdo->query($sql);
    $plants = $stmt->fetchAll();

    // Name-only format: minimise tokens while covering more plants
    $plantContext = "";
    foreach ($plants as $plant) {
        $names = explode(',', $plant['Common_Name'] ?? '');
        $primary_common = trim($names[0]);
        $plantContext .= $primary_common . " (" . $plant['Scientific_Name'] . ")\n";
    }

} catch (\PDOException $e) {
    echo json_encode(['error' => 'Database integration error: ' . $e->getMessage()]);
    exit();
}

$url = "https://api.groq.com/openai/v1/chat/completions";

// Setup system rules matrix
$apiMessages = [
    [
        "role" => "system",
        "content" => "You are FloraBot, a friendly botanical AI assistant for the PlantMatch web platform. You remember prior conversation history seamlessly to hold contextual discussions.\n\nHere is our real descriptive plant inventory with values already mapped into descriptive text words:\n" . $plantContext . "\nWhen users ask questions, discuss these items using only their plain English description values. Never show or mention raw scores or float numbers like 0.0, 0.5, or 1.0. Keep your answers clear, natural, friendly, and concise.\n\nFORMAT RULES: Never use tables. When listing plants, use a numbered list. For each plant, put ONLY the plant's common name in bold (e.g. **Rose**) — nothing else bolded, no scientific name in the bold text. Follow with one or two sentences of relevant info. Keep the total response under 200 words."
    ]
];

// Append structural history logs to the Groq call payload
$apiMessages = array_merge($apiMessages, $_SESSION['chatbot_history']);

$data = [
    "model" => "openai/gpt-oss-120b",
    "messages" => $apiMessages,
    "temperature" => 0.5,
    "max_tokens" => 400
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . trim(GROK_API_KEY)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo json_encode(['error' => 'Server communication error: ' . curl_error($ch)]);
    curl_close($ch);
    exit();
}
curl_close($ch);

if ($httpCode === 200) {
    $responseData = json_decode($response, true);
    $reply = $responseData['choices'][0]['message']['content'] ?? "I couldn't process that response format.";

    // Save response to timeline memory
    $_SESSION['chatbot_history'][] = [
        "role" => "assistant",
        "content" => $reply
    ];

    echo json_encode(['reply' => $reply]);
} else {
    $errorDetails = json_decode($response, true);
    $errorMessage = $errorDetails['error']['message'] ?? 'Check your Groq API configuration parameters.';
    echo json_encode([
        'error' => 'Groq API Error ' . $httpCode . ': ' . $errorMessage
    ]);
}