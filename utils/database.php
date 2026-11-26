<?php

$host = getenv("GOFILM_DB_HOST") ?: "127.0.0.1";
$database = getenv("GOFILM_DB_NAME") ?: "gofilm";
$username = getenv("GOFILM_DB_USER") ?: "root";
$password = getenv("GOFILM_DB_PASSWORD");

return new PDO(
    "mysql:host={$host};dbname={$database};charset=utf8mb4",
    $username,
    $password === false ? "" : $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);
