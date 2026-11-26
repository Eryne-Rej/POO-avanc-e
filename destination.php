<?php
require_once __DIR__ . "/utils/autoload.php";

$destinationCatalog = require __DIR__ . "/Entities/destinations.php";
$destinationsById = [];
foreach ($destinationCatalog as $destination) {
    $destinationsById[$destination[0]] = $destination;
}

$destinationId = $_GET["id"] ?? "";
$destinationId = is_string($destinationId) ? $destinationId : "";
$selectedDestination = $destinationsById[$destinationId] ?? null;

$getString = static function (string $key, string $default): string {
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
};

if ($selectedDestination !== null) {
    [, $name, $film] = $selectedDestination;
} else {
    $name = $getString("name", "Matamata");
    $film = $getString("film", "Le Seigneur des anneaux");
}

$operators = [];
$databaseError = null;
try {
    $pdo = require __DIR__ . "/utils/database.php";
    if ($selectedDestination !== null) {
        $statement = $pdo->prepare(
            "SELECT
                tour_operator.id,
                tour_operator.name,
                tour_operator.link,
                tour_operator.grade_count,
                tour_operator.grade_total,
                tour_operator.is_premium,
                destination.price,
                (
                    SELECT review.author
                    FROM review
                    WHERE review.tour_operator_id = tour_operator.id
                    ORDER BY review.id DESC
                    LIMIT 1
                ) AS review_author,
                (
                    SELECT review.message
                    FROM review
                    WHERE review.tour_operator_id = tour_operator.id
                    ORDER BY review.id DESC
                    LIMIT 1
                ) AS review_message
            FROM destination
            INNER JOIN tour_operator ON tour_operator.id = destination.tour_operator_id
            WHERE destination.location = ?
            ORDER BY tour_operator.is_premium DESC, tour_operator.name"
        );
        $statement->execute([$selectedDestination[1]]);
        $operators = $statement->fetchAll();
    }
} catch (PDOException $exception) {
    error_log("GoFilm destination data loading failed: " . $exception->getMessage());
    $databaseError = "Les offres ne sont pas disponibles pour le moment.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="public/style/style.css">
    <title>GoFilm — <?= htmlspecialchars($name) ?></title>
</head>
<body>
    <main class="destination-page">
        <?php require __DIR__ . "/Entities/nav.php"; ?>

        <section class="destination-heading">
            <a class="back-link" href="index.php" aria-label="Retour aux destinations">←</a>
            <h1><?= htmlspecialchars($name) ?>, <?= htmlspecialchars($film) ?></h1>
        </section>

        <section class="offers-list" aria-label="Offres des tour-opérateurs">
            <?php if ($databaseError !== null): ?>
                <p class="admin-notice admin-notice-error" role="alert"><?= htmlspecialchars($databaseError) ?></p>
            <?php elseif ($operators === []): ?>
                <p class="empty-offers">Aucun tour-opérateur n'est actuellement associé à cette destination.</p>
            <?php else: ?>
                    <?php foreach ($operators as $operator): ?>
                        <?php
                        $gradeCount = (int) $operator["grade_count"];
                        $average = $gradeCount > 0
                            ? (int) $operator["grade_total"] / $gradeCount
                            : null;
                        $roundedRating = $average === null ? 0 : max(0, min(5, (int) round($average)));
                        $stars = str_repeat("★", $roundedRating) . str_repeat("☆", 5 - $roundedRating);
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
                                    <span class="stars" aria-label="<?= htmlspecialchars($average === null ? "Aucune note" : "Note " . number_format($average, 1, ",", "") . " sur 5") ?>">
                                        <?= $stars ?>
                                    </span>
                                </div>
                                <p>Offre proposée pour <?= htmlspecialchars($name) ?>.</p>
                                <?php if ($operator["review_message"] !== null): ?>
                                    <div class="review">
                                        <span>Avis de <?= htmlspecialchars($operator["review_author"]) ?></span>
                                        <p>« <?= htmlspecialchars($operator["review_message"]) ?> »</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="offer-details">
                                <span><?= number_format((int) $operator["price"], 0, ",", " ") ?> €</span>
                                <div class="offer-comment">
                                    <strong>
                                        <?= $average === null ? "Pas encore noté" : htmlspecialchars(number_format($average, 1, ",", "")) . "/5" ?>
                                    </strong>
                                    <?php if ($operator["review_message"] === null): ?>
                                        <p>Aucun avis pour le moment.</p>
                                    <?php endif; ?>
                                </div>
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
    </main>

    <script>
        const menuButton = document.querySelector('.menu-button');
        menuButton.addEventListener('click', () => {
            const open = menuButton.getAttribute('aria-expanded') === 'true';
            menuButton.setAttribute('aria-expanded', String(!open));
        });
    </script>
</body>
</html>
