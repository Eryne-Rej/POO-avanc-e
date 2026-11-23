<?php require_once "utils/autoload.php"; ?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="public/style/style.css">
    <title>GoFilm — Destination</title>
</head>
<body>
    <?php
    $name = trim($_GET["name"] ?? "Matamata");
    $film = trim($_GET["film"] ?? "Le Seigneur des anneaux");
    $price = trim($_GET["price"] ?? "225 €");
    $photo = trim($_GET["photo"] ?? "");
    $destinationId = trim($_GET["id"] ?? "");

    $imageUrl = str_starts_with($photo, "local:")
        ? "public/images/" . substr($photo, 6)
        : ($photo !== ""
            ? "https://images.unsplash.com/" . $photo . "?auto=format&fit=crop&w=1200&q=85"
            : "public/images/matamata.webp");
    ?>

    <main class="destination-page">
        <?php require "Entities/nav.php"; ?>

        <section class="destination-heading">
            <a class="back-link" href="index.php" aria-label="Retour aux destinations">←</a>
            <h1><?= htmlspecialchars($name) ?>, <?= htmlspecialchars($film) ?></h1>
        </section>

        <div class="offers-list">
            <?php
            $commentsByDestination = [
                "paris" => [
                    ["Amélie Tours", 5, "Balade dans les cafés et escaliers vus dans le film.", "Paris semble sortir directement du film.", "4.9"],
                    ["Paris Cinéma", 4, "Circuit romantique autour du quartier de Montmartre.", "Guide passionné et itinéraire charmant.", "4.4"],
                    ["French Screen", 5, "Visite des lieux emblématiques d'Amélie.", "Une journée joyeuse et pleine de découvertes.", "4.7"]
                ],
                "mont-saint-michel" => [
                    ["Celtic Films", 5, "Découverte médiévale guidée du Mont-Saint-Michel.", "Les décors racontent une véritable légende.", "4.8"],
                    ["Normandie Ciné", 4, "Excursion avec accès aux points de vue du film.", "Superbe vue, organisation parfaite.", "4.5"]
                ],
                "louvre" => [
                    ["Louvre Legends", 5, "Visite des œuvres et lieux clés de Da Vinci Code.", "Les explications rendent l'enquête passionnante.", "4.9"],
                    ["Museum Screen", 4, "Circuit artistique inspiré du célèbre thriller.", "Bien rythmée et très instructive.", "4.6"]
                ],
                "etretat" => [
                    ["Falaises Voyages", 5, "Randonnée cinématographique sur les falaises.", "Les paysages sont encore plus beaux que dans Lupin.", "4.8"],
                    ["Normandy Screen", 4, "Excursion côtière avec guide spécialisé.", "Itinéraire varié et vues exceptionnelles.", "4.3"]
                ],
                "dubrovnik" => [
                    ["Kingsroad Travels", 5, "Visite des lieux de tournage de Port-Réal.", "Une immersion incroyable dans Westeros.", "4.9"],
                    ["Westeros Tours", 5, "Circuit complet dans les remparts et ruelles.", "Notre guide connaissait chaque scène.", "4.8"],
                    ["Croatia Ciné", 4, "Croisière et découverte des décors de la série.", "Magnifique, surtout au coucher du soleil.", "4.5"]
                ],
                "alnwick" => [
                    ["Wizard Tours", 5, "Journée dans le château d'Alnwick.", "On se croit vraiment à Poudlard.", "4.9"],
                    ["Magic Highlands", 5, "Circuit Harry Potter dans le Nord de l'Angleterre.", "Visites captivantes pour toute la famille.", "4.7"],
                    ["Britannia Ciné", 4, "Découverte du château et de ses jardins.", "Très beau lieu de tournage.", "4.4"]
                ],
                "skellig-michael" => [
                    ["Galaxy Islands", 5, "Voyage sur les îles ayant inspiré Ahch-To.", "Un décor sauvage et spectaculaire.", "4.9"],
                    ["Star Coast", 4, "Excursion maritime vers Skellig Michael.", "Une expérience unique sous réserve de météo favorable.", "4.6"]
                ],
                "central-park" => [
                    ["New York Movie Tours", 5, "Circuit des scènes tournées à Central Park.", "On retrouve tous les lieux du film.", "4.8"],
                    ["Manhattan Screen", 4, "Balade cinématographique avec guide francophone.", "Excellent rythme et bonnes anecdotes.", "4.5"]
                ]
            ];
            $companies = $commentsByDestination[$destinationId] ?? [
                ["Dream Tours", 5, "Voyage premium au cœur des lieux de tournage.", "Excellent accueil et visites très bien organisées.", "4.8"],
                ["Ciné Voyage", 4, "Circuit accompagné avec guide cinéphile.", "Une expérience agréable.", "4.3"]
            ];
            foreach ($companies as [$company, $rating, $description, $comment, $average]):
            ?>
                <article class="offer-card">
                    <div class="offer-main">
                        <div class="offer-name">
                            <strong><?= htmlspecialchars($company) ?></strong>
                            <span class="stars" aria-label="Note <?= $rating ?> sur 5"><?= str_repeat("★", $rating) ?><?= str_repeat("☆", 5 - $rating) ?></span>
                        </div>
                        <p><?= htmlspecialchars($description) ?></p>
                    </div>
                    <div class="offer-details">
                        <span><?= htmlspecialchars($price) ?></span>
                        <div class="offer-comment">
                            <strong><?= htmlspecialchars($average) ?>/5</strong>
                            <p>« <?= htmlspecialchars($comment) ?> »</p>
                        </div>
                        <a href="#" aria-label="Voir tous les avis <?= htmlspecialchars($company) ?>">Voir tous les avis</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
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
