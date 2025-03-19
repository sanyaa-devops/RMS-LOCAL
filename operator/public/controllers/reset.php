<?php

include 'newdb.php';
header("Access-Control-Allow-Origin: *");  // Allow any origin (adjust if needed)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");  // Allow the necessary HTTP methods
header("Access-Control-Allow-Headers: Content-Type, Authorization");  // Allow Content-Type header and others if needed
header("Access-Control-Allow-Credentials: true");  // Include if needed for credentials

//$ok  = inputs("UPDATE `user` SET `balance` = '1000' , `fund` = '1000' WHERE `user`.`email` = 'simbu.vishwa@gmail.com'");

$ok  = inputs("UPDATE `user` SET `balance` = '1000' , `fund` = '1000' WHERE `user`.`email` = 'rajaeronautics@gmail.com'");
