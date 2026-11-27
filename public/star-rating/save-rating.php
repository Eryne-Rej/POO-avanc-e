<?php

require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_id = filter_input(
        INPUT_POST,
        "product_id",
        FILTER_VALIDATE_INT
    );

    $rating = filter_input(
        INPUT_POST,
        "rating",
        FILTER_VALIDATE_INT
    );

    if (!$product_id || !$rating || $rating < 1 || $rating > 5) {
        die("Note invalide.");
    }

    $sql = "INSERT INTO ratings (product_id, rating)
            VALUES (:product_id, :rating)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":product_id" => $product_id,
        ":rating" => $rating
    ]);

    echo "Merci pour votre évaluation !";
}
?>