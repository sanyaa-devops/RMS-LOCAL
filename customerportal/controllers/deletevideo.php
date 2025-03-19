<?php
require_once 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
   
if(isset($data['fullpath']) && $data){
    $filePath =  "{$videoLoc}/" .base64_decode($data['fullpath']);

    if (file_exists($filePath)) {
        // Attempt to delete the file
        if (unlink($filePath)) {
            echo json_encode(['success'=>true,'message'=>'success']);
        } else {
            echo json_encode(['success'=>false,'message'=>'unable to do so']);
           
        }
    } else {
        echo json_encode(['success'=>false,'message'=>'not found']);
    }
    exit;
}
}