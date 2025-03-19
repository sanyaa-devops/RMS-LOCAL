<?php

include 'newdb.php';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];

$project_folder = "/inflightdubai/customerportal";

$baseurl = $protocol . $host . $project_folder;
$endpoint = $baseurl;


/* $baseurl='http://192.168.0.177/inflightdubai/customerportal';

$endpoint = 'http://192.168.0.177/inflightdubai/customerportal';
 */




/* $folderPath = getPaths('folderPath')['value']; */

$videoLoc = getPaths('videoLoc')['value'];


function getPaths($field){
    $query = "SELECT * FROM `config_settings` WHERE field = '$field' ";
    $result = outputs($query);
     $values  = $result->fetch_assoc();
     return $values??null;   
}

$adminAccess = [
    'customer_id'=>2,
    'email'=>'admin@inflight.com',
    'user_name'=>'admin@inflight.com',
    'password'=>'admin134'
];