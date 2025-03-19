<?php
session_start();
require_once './controllers/config.php';
if(!$_SESSION || !$_SESSION['user_id']){
  session_destroy();
  header("location: {$baseurl}/login");
  exit;
}else{
    header("location: {$baseurl}/dashboard");
  exit;
}

