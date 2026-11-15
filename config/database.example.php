<?php

return [
    'host' => '127.0.0.1',
    'port' => '3306',
    'dbname' => getenv('REPUBLICAIXA_DB') ?: 'republicaixa',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];
