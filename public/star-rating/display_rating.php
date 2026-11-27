<?php

require_once "db.php";

$product_id = 1;

$sql = "SELECT AVG(rating) AS moyenne,
               COUNT(*) AS total
        FROM ratings
        WHERE product_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$product_id]);

$result = $stmt->fetch(PDO::FETCH_ASSOC);

$moyenne = $result['moyenne'] ?? 0;
$total = $result['total'];

?>

<h3>
    Note moyenne :
    <?= number_format((float)$moyenne, 1) ?>/5
</h3>

<p>
    <?= $total ?> avis
</p>