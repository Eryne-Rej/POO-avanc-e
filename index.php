<?php

require_once __DIR__ . "/utils/bdd.php";
require_once __DIR__ . "/Mappers/destinationMapper.php";

/**
 * Récupère un paramètre de recherche sous forme de chaîne.
 */
function gofilmQueryParam(string $name): string
{
    $value = $_GET[$name] ?? "";

    return is_string($value) ? trim($value) : "";
}

$destinationMapper = new destinationMapper($pdo);
$searchLocations = $destinationMapper->getLocations();

$searchLocation = gofilmQueryParam("arrivee");

if ($searchLocation !== "") {
    $destinationId = $destinationMapper->getIdFromLocation($searchLocation);

    if ($destinationId !== null) {
        header("Location: destination.php?id=" . $destinationId);
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="public/style/style.css">
    <link href="./src/output.css" rel="stylesheet">
    <title>GoFilm</title>
</head>
<body class="container mx-auto pb-10">
    <?php
        $navHomeLink = "index.php";
        require __DIR__ . "/Entities/nav.php";
        require __DIR__ . "/Entities/hero.php";
        require __DIR__ . "/Entities/destination_grid.php";
    ?>
</body>
</html>
