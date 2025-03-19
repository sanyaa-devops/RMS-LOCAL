<?php


header("Access-Control-Allow-Origin: http://127.0.0.1:5000"); // Change to your frontend's origin
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // Allow methods
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allow specific headers
header("Access-Control-Max-Age: 86400"); // Cache preflight response for 1 day


include 'newdb.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
  
    $postData = file_get_contents('php://input');
    $data = json_decode($postData, true);
   
    $filePath = addslashes($data['data']['fileLoc']);
    $config = "Update config_settings set `value` = '$filePath' where `id`='1' and `field`='videoLoc'";
    $configs = inputs($config); 
    echo json_encode(['status'=>"success"]);
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'GET'){
    $config = "SELECT value FROM config_settings WHERE id='1' and field='videoLoc'";
    $configs = outputs($config); 
    if($configs){
        $configs = $configs->fetch_object();
        echo json_encode(['status'=>1,"videoLocation"=>$configs->value]);
        exit;
    }else{
        echo json_encode(['status'=>1,"videoLocation"=>'']);
        exit;
    }
}
