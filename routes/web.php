<?php

return [
    'GET /' => function (): void {
        require dirname(__DIR__) . '/app/Views/home.php';
    },
];
