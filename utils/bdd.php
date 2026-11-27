<?php

if (!isset($pdo) || !($pdo instanceof PDO)) {
    try {
        $pdo = require __DIR__ . "/database.php";
    } catch (PDOException $exception) {
        error_log("GoFilm database connection failed: " . $exception->getMessage());
        $pdo = null;
    }
}
