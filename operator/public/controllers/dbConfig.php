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
            'password' => '',
			'host_two' => 'localhost',
            'db_two' => 'sales_source',
            'user_two' => 'root',
            'port_two' => '3306',
            'password_two' => '',
        ];
    }
}
?>
