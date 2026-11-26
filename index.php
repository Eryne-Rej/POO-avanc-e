<?php
$appBase = defined("GOFILM_ADMIN_VIEW") ? "../" : "";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://images.unsplash.com">
    <link rel="stylesheet" href="<?= $appBase ?>src/output.css">
    <link rel="stylesheet" href="<?= $appBase ?>public/style/style.css">
    <title>GoFilm</title>
</head>
<body>
    <main class="app-shell">
        <section class="browse-panel" aria-label="Destinations">
            <?php require __DIR__ . "/Entities/nav.php"; ?>
            <?php if (defined("GOFILM_ADMIN_VIEW")) require __DIR__ . "/admin/panel.php"; ?>

            <section class="hero" aria-label="Destination mise en avant">
                <div class="hero-overlay">
                    <p>Le Seigneur des anneaux.</p>
                    <h1>Matamata, Nouvelle-Zélande</h1>
                </div>
                <form class="search-bar">
                    <label>
                        <span class="sr-only">Lieu</span>
                        <span>Lieu</span>
                        <input type="text" value="Matamata" aria-label="Lieu">
                    </label>
                    <label>
                        <span>Arrivée</span>
                        <input type="text" value="12/06/2026" aria-label="Date d'arrivée">
                    </label>
                    <label>
                        <span>Retour</span>
                        <input type="text" value="19/06/2026" aria-label="Date de retour">
                    </label>
                    <label>
                        <span>Adultes</span>
                        <input type="number" value="1" min="1" aria-label="Nombre d'adultes">
                    </label>
                    <button type="submit">Rechercher</button>
                </form>
            </section>

            <div class="section-heading">
                <h2>Voir les destinations</h2>
                <button class="filter-button" type="button" aria-label="Filtrer les destinations">
                    <span></span><span></span><span></span>
                </button>
            </div>

            <div class="destination-grid">
                <?php
                $destinations = require __DIR__ . "/Entities/destinations.php";
                foreach ($destinations as $destination):
                    [$id, $name, $film, $price, $rating, $photo] = $destination;
                ?>
                                <a class="destination-card" href="<?= $appBase ?>destination.php?<?= http_build_query(["id" => $id, "name" => $name, "film" => $film, "price" => $price, "photo" => $photo]) ?>">
                                    <?php
                                    $imageUrl = str_starts_with($photo, "local:")
                                        ? $appBase . "public/images/" . substr($photo, 6)
                                        : "https://images.unsplash.com/" . $photo . "?auto=format&fit=crop&w=400&q=80";
                                    ?>
                                    <img src="<?= htmlspecialchars($imageUrl) ?>" alt="<?= htmlspecialchars($name) ?>">
                                    <div class="destination-info">
                                        <h3><?= htmlspecialchars($name) ?> — <?= htmlspecialchars($film) ?></h3>
                                        <div>
                                            <span><?= htmlspecialchars($price) ?></span>
                                            <span class="stars" aria-label="Note <?= $rating ?> sur 5">★★★★☆</span>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
    </main>
    <script>
        const menuButton = document.querySelector('.menu-button');
        menuButton.addEventListener('click', () => {
            const open = menuButton.getAttribute('aria-expanded') === 'true';
            menuButton.setAttribute('aria-expanded', String(!open));
            document.querySelector('.browse-panel').classList.toggle('menu-open', !open);
        });

        document.querySelector('.search-bar').addEventListener('submit', event => event.preventDefault());
    </script>
</body>
</html>