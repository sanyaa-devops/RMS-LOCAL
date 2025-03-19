<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true"); 

require_once 'config.php';
include_once 'newdb.php';
// include_once 'newdb_two.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    if (isset($data['username']) && isset($data['password'])) {
        $username = $data['username'];
        $password = $data['password'];
        /* if($username==='@infladminight.com' && $password=== 'admin134'){
			$_SESSION['user_id'] = 'admin1234';
			$_SESSION['user_name'] = 'Admin';
			echo json_encode(['success'=>true,'token'=>base64_encode('admin1234'),'message' => 'Login successful']);
		  exit;
		} */
		 $admin_sql = "SELECT customer_id , customer_name, password FROM operator WHERE email = '".addslashes($username)."' LIMIT 1;";
		 $admin_result = outputs($admin_sql);
		 if($admin_result){
			 $admin_result = getArray($admin_result);
			 if (!empty($admin_result)) {
				$admin = $admin_result[0];

				// Verify password
				if ($password === $admin['password']) { // Use password_verify() for hashed passwords
					$_SESSION['user_id'] = $admin['customer_id'];
					$_SESSION['user_name'] = $admin['customer_name'];
					echo json_encode([
						'success' => true,
						'token' => base64_encode($admin['customer_name']),
						'message' => 'Login successful'
					]);
					exit();
				}
			}
		}
        $result = callLogin($username, $password);
        if (isset($result['success']) && $result['success'] == "1") {
			$_SESSION['user_id'] = $result['customer_id'];
            $_SESSION['user_name'] = $result['customer_name']??"";
            echo json_encode(['success'=>true,'token'=>base64_encode($result['customer_id']),'message' => 'Login successful']);
			exit;
		}
		elseif (isset($result['success']) && $result['success'] == "2") {
            http_response_code(401);
            echo json_encode(['message' => 'Invalid credentials']);
			exit;
        }
		else{
			http_response_code(401);
            echo json_encode(['message' =>$result['msg'],'status'=>0]);
			exit;
		}
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid request']);
		exit;
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
	exit;
}

function getArray($result) : array {
    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            return [];
        }
    } else {
        return [];
    }
}


function callLogin($username, $password){
    $url = "https://store.inflightdubai.com/inflight/main/api.php";
    $postData = [
        'call' => 'loginCustomer',
        'email' => $username,
        'pass' => $password,
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Accept: application/json, text/javascript, */*; q=0.01",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36",
        "X-Requested-With: XMLHttpRequest",
        "Content-Type: application/x-www-form-urlencoded; charset=UTF-8"
    ]);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return false;
    }
    curl_close($ch);
    $response = json_decode($response, true);
    return $response;
    // if(isset($response['success']) && $response['success'] == true){
    //     return true;
    // }else{
    //     return false;
    // }
}
?>
