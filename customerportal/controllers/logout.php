<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true"); 

session_start();
require_once 'config.php';
if (isset($_SESSION['user_id'])) {
    // Unset and destroy the session
    $_SESSION = [];
    session_destroy();
    echo json_encode(['status'=>true,'message' => 'Logout successful']);
} else {
    http_response_code(400);
    echo json_encode(['status'=>false,'message' => 'No user is logged in']);
}
