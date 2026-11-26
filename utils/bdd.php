<?php

$db = 'mysql:host=localhost;dbname=gofilm;charset=utf8mb4';
$username = 'root';
$password = '';

try {
    $pdo = new PDO($db, $username, $password);
} catch (PDOException $e) {
    error_log('Erreur de connexion PDO : ' . $e->getMessage());
    $pdo = null;
}
