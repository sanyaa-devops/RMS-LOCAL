<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true"); 


include_once 'newdb.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    if (isset($data['username']) && isset($data['password'])) {
        $username = $data['username'];
        $password = $data['password'];
        //FETCH_ASSOC

        $query = "SELECT * FROM `operator` WHERE customer_id = '$username' OR `email` = '$username'";
        $result = outputs($query);
        $user = $result->fetch_assoc();
        if ($user && $password === $user['password']) {
            // Store user ID in session
            $_SESSION['user_id'] = $user['customer_id'];
            $_SESSION['user_name'] = $user['customer_name'];
            echo json_encode(['success'=>true,'token'=>base64_encode($user['customer_id']),'message' => 'Login successful']);
        } else {
            http_response_code(401);
            echo json_encode(['message' => 'Invalid credentials']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid request']);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>
