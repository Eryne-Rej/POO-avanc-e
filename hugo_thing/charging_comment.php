<?php

require_once("../utils/bdd.php");

try {
    $query = $pdo->prepare(
        "SELECT author, messages, star_review, tour_operator_id	
         FROM review
         ORDER BY id DESC"
    );

    $commentaires = $query->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(
        $commentaires,
        JSON_UNESCAPED_UNICODE
    );

} catch (PDOException $e) {
    error_log("Erreur lors du chargement des commentaires : " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        "erreur" => "Impossible de charger les commentaires."
    ]);
}