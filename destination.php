<?php
require_once __DIR__ . "/utils/autoload.php";
require_once __DIR__ . "/Mappers/destinationMapper.php";
require_once __DIR__ . "/Mappers/operatorMapper.php";

$idInput = $_GET["id"] ?? null;
$destinationId = is_string($idInput) || is_int($idInput)
    ? filter_var($idInput, FILTER_VALIDATE_INT)
    : false;
$destination = null;
$operators = [];
$reviewsByOperator = [];
$pageError = null;

if ($destinationId === false || $destinationId === null || $destinationId < 1) {
    $pageError = "Identifiant de destination invalide.";
} elseif (!($pdo instanceof PDO)) {
    $pageError = "Connexion à la base de données impossible.";
} else {
    try {
        $destinationMapper = new destinationMapper($pdo);
        $destination = $destinationMapper->getInfosFromId($destinationId);
        if ($destination === null) {
            http_response_code(404);
            $pageError = "Cette destination n'existe pas.";
        } else {
            $operatorMapper = new operatorMapper($pdo);
            $operators = $operatorMapper->getOperatorFromId($destinationId);

            $reviewStatement = $pdo->prepare(
                "SELECT author, message FROM review WHERE tour_operator_id = ? ORDER BY id DESC"
            );
            foreach ($operators as $operator) {
                $reviewStatement->execute([(int) $operator["id"]]);
                $reviewsByOperator[(int) $operator["id"]] = $reviewStatement->fetchAll();
            }
        }
    } catch (PDOException $exception) {
        error_log("GoFilm destination data loading failed: " . $exception->getMessage());
        $pageError = "Les offres ne sont pas disponibles. Vérifiez le schéma de la base gofilm.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="./src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="public/style/style.css">
    <title>GoFilm — <?= htmlspecialchars($destination["location"] ?? "Destination") ?></title>
</head>
<body class="container mx-auto pb-10">
    <?php require __DIR__ . "/Entities/nav.php"; ?>

    <main class="destination-page">
        <?php if ($pageError !== null): ?>
            <p class="admin-notice admin-notice-error" role="alert"><?= htmlspecialchars($pageError) ?></p>
        <?php else: ?>
            <section class="destination-heading">
                <a class="back-link" href="index.php" aria-label="Retour aux destinations">←</a>
                <div>
                    <h1><?= htmlspecialchars($destination["location"]) ?></h1>
                    <p><?= (int) $destination["price"] ?> €</p>
                </div>
            </section>

            <section class="offers-list" aria-label="Tour-opérateurs associés">
                <?php if ($operators === []): ?>
                    <p class="empty-offers">Aucun tour-opérateur n'est actuellement associé à cette destination.</p>
                <?php else: ?>
                    <?php foreach ($operators as $operator): ?>
                        <?php
                        $rating = max(0, min(5, (int) $operator["grade_count"]));
                        $operatorUrl = filter_var($operator["link"], FILTER_VALIDATE_URL);
                        $urlScheme = is_string($operatorUrl) ? parse_url($operatorUrl, PHP_URL_SCHEME) : null;
                        if (!in_array($urlScheme, ["http", "https"], true)) {
                            $operatorUrl = false;
                        }
                        ?>
                        <article class="offer-card">
                            <div class="offer-main">
                                <div class="offer-name">
                                    <strong><?= htmlspecialchars($operator["name"]) ?></strong>
                                    <?php if ((int) $operator["is_premium"] === 1): ?>
                                        <span class="premium-badge">Premium</span>
                                    <?php endif; ?>
                                    <span class="stars" aria-label="Note <?= $rating ?> sur 5">
                                        <?= str_repeat("★", $rating) . str_repeat("☆", 5 - $rating) ?>
                                    </span>
                                </div>
                                <?php foreach ($reviewsByOperator[(int) $operator["id"]] ?? [] as $review): ?>
                                    <div class="review">
                                        <span><?= htmlspecialchars($review["author"]) ?></span>
                                        <p>« <?= htmlspecialchars($review["message"]) ?> »</p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="offer-details">
                                <span><?= (int) $destination["price"] ?> €</span>
                                <?php if ($operatorUrl !== false): ?>
                                    <a href="<?= htmlspecialchars($operatorUrl) ?>" target="_blank" rel="noopener noreferrer">
                                        Site du tour-opérateur
                                    </a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
