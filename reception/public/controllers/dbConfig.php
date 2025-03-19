<?php
// DbConfig.php

class DbConfig {
    public $active;

    public function __construct() {
        $this->active = (object) [
            'host' => 'localhost',
            'db' => 'sales',
            'user' => 'root',
            'port' => '3306',
            'password' => ''
        ];
    }
}
?>
