<?php

require_once("../utils/bdd.php");

try {
    $query = $pdo->prepare(
        "SELECT author, message, star_review, tour_operator_id	
         FROM review
         WHERE tour_operator_id = :tour_operator_id
         ORDER BY id DESC"
    );

    $tourOperatorId = filter_input(INPUT_POST, "tour_operator_id", FILTER_VALIDATE_INT);

    $query->execute([
        "tour_operator_id" => $tourOperatorId
    ]);

    $reviews = $query->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erreur lors du chargement des commentaires : " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        "erreur" => "Impossible de charger les commentaires."
    ]);
}