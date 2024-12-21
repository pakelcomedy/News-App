<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection
include 'koneksi.php';

// Headers for allowing CORS and JSON response format
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Helper function to send JSON response
function send_response($status_code, $data) {
    http_response_code($status_code);
    echo json_encode($data);
    exit;
}

// Get the database connection
$conn = getKoneksi();

if (!$conn) {
    send_response(500, ["message" => "Database connection failed."]);
}

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['device_token']) || !isset($data['user_id'])) {
    send_response(400, ["message" => "Invalid input."]);
}

$user_id = $data['user_id'];
$device_token = $data['device_token'];

try {
    // Insert or update user device token
    $query = "INSERT INTO user (id, device_token, name) VALUES (:id, :device_token, :name)
    ON DUPLICATE KEY UPDATE device_token = :device_token";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':device_token', $device_token, PDO::PARAM_STR);
    $stmt->bindValue(':name', $data['name'] ?? 'Anonymous', PDO::PARAM_STR); // Gunakan nama default jika tidak disediakan


    if ($stmt->execute()) {
        send_response(200, ["message" => "Device token berhasil disimpan."]);
    } else {
        send_response(500, ["message" => "Gagal menyimpan device token."]);
    }
} catch (PDOException $e) {
    send_response(500, ["message" => "Database error: " . $e->getMessage()]);
}
?>