<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection
include 'koneksi.php';

// Include the Firebase JWT library (make sure you installed firebase/php-jwt)
require 'vendor/autoload.php';  // Ensure you have installed dependencies via Composer

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

// Fetch latest news that hasn't been notified
$query = "SELECT * FROM berita WHERE is_notified = 0 ORDER BY created_at DESC";
$result = $conn->query($query);

if ($result->rowCount() > 0) {
    // Iterate over each news item
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $berita_id = $row['id'];
        $title = $row['title'];
        $body = $row['content'];
        $url = $row['url'];

        // Fetch users with 'Pembaca' role and valid device tokens
        $userQuery = "SELECT device_token FROM user WHERE role = 'Pembaca' AND device_token IS NOT NULL";
        $userResult = $conn->query($userQuery);

        if ($userResult->rowCount() > 0) {
            while ($user = $userResult->fetch(PDO::FETCH_ASSOC)) {
                $deviceToken = $user['device_token'];

                // Send notification to each device token
                $response = sendNotification($deviceToken, $title, $body, $url);
            }
        }

        // Mark the news as notified
        $updateQuery = "UPDATE berita SET is_notified = 1 WHERE id = $berita_id";
        if (!$conn->query($updateQuery)) {
            send_response(500, ["message" => "Failed to update news status."]);
        }
    }

    send_response(200, ["message" => "Notifikasi berhasil dikirim ke pembaca."]);
} else {
    send_response(404, ["message" => "Tidak ada berita baru."]);
}

// Function to send notification to FCM
function sendNotification($deviceToken, $title, $body, $url) {
    $accessToken = getAccessToken(); // Get the access token
    $fcmUrl = 'https://fcm.googleapis.com/v1/projects/notif-84895/messages:send';

    $notification = [
        'title' => $title,
        'body' => $body,
    ];

    // Move click_action to the data field
    $data = [
        'click_action' => $url,
    ];

    $fcmNotification = [
        'message' => [
            'token' => $deviceToken,
            'notification' => $notification,
            'data' => $data,  // Add data field here with click_action
        ],
    ];

    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json',
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fcmUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $result = curl_exec($ch);
    if ($result === FALSE) {
        $error_message = curl_error($ch);
        curl_close($ch);
        send_response(500, ["message" => "FCM Send Error: " . $error_message]);
    }

    curl_close($ch);

    // Decode the response
    $responseDecoded = json_decode($result, true);

    // Check if the response contains an error
    if (isset($responseDecoded['error'])) {
        send_response(500, ["message" => "FCM Error: " . json_encode($responseDecoded['error'])]);
    }

    return $responseDecoded;
}

// Function to get the access token from Firebase
function getAccessToken() {
    $serviceAccountPath = __DIR__ . '/service-account.json'; // Path to your service account JSON file

    // Load the service account credentials
    $key = json_decode(file_get_contents($serviceAccountPath), true);

    if (!$key) {
        send_response(500, ["message" => "Invalid service account JSON or file not found."]);
    }

    // Initialize Google Client
    $client = new Google_Client();
    $client->setAuthConfig($key);
    $client->addScope(Google_Service_FirebaseCloudMessaging::CLOUD_PLATFORM);
    $client->setApplicationName('Firebase Cloud Messaging Service');

    // Attempt to get the access token
    try {
        $accessTokenResponse = $client->fetchAccessTokenWithAssertion();
        
        if (isset($accessTokenResponse['access_token'])) {
            return $accessTokenResponse['access_token'];
        } else {
            // If the access token is not found, return a detailed error message
            throw new Exception('Access token retrieval failed. Response: ' . json_encode($accessTokenResponse));
        }
    } catch (Exception $e) {
        // Catch and display any exceptions
        send_response(500, ["message" => "Error obtaining access token: " . $e->getMessage()]);
    }
}