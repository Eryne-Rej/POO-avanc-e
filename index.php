<?php
$appBase = defined("GOFILM_ADMIN_VIEW") ? "../" : "";
require_once __DIR__ . "/utils/autoload.php";
require_once __DIR__ . "/Mappers/destinationMapper.php";
$publicSchemaReady = false;
if ($pdo instanceof PDO) {
    try {
        $columns = $pdo->query("SHOW COLUMNS FROM destination")->fetchAll(PDO::FETCH_COLUMN);
        $publicSchemaReady = count(array_diff(
            ["id", "location", "price", "image", "note", "operator"],
            $columns
        )) === 0;
    } catch (PDOException $exception) {
        error_log("GoFilm destination schema check failed: " . $exception->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?= $appBase ?>src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $appBase ?>public/style/style.css">
    <title>GoFilm</title>
</head>
<body class="container mx-auto pb-10">
    <?php require __DIR__ . "/Entities/nav.php"; ?>
    <?php if (defined("GOFILM_ADMIN_VIEW")) require __DIR__ . "/admin/panel.php"; ?>
    <?php require __DIR__ . "/Entities/hero.php"; ?>
    <?php if ($pdo instanceof PDO && $publicSchemaReady): ?>
        <?php require __DIR__ . "/Entities/destination_grid.php"; ?>
    <?php else: ?>
        <p class="admin-notice admin-notice-error" role="alert">
            La base gofilm ne correspond pas au schéma du fichier SQL fourni.
        </p>
    <?php endif; ?>
</body>
</html>