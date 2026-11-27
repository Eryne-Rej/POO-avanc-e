<?php
if (!defined("GOFILM_ADMIN_VIEW")) {
    define("GOFILM_ADMIN_VIEW", true);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$destinationCatalog = require __DIR__ . "/../Entities/destinations.php";
$destinationsById = [];
foreach ($destinationCatalog as $destination) {
    $destinationsById[$destination[0]] = $destination;
}

/**
 * Les opérateurs sont stockés dans destination.operator sous la forme "1,2".
 *
 * @return int[]
 */
function gofilmParseOperatorIds(string $value): array
{
    $ids = [];
    foreach (explode(",", $value) as $part) {
        $id = (int) trim($part);
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }

    return array_values($ids);
}

/**
 * Associe un opérateur aux destinations existantes correspondant aux lieux donnés.
 *
 * @param string[] $locations
 * @return array{attached: int, missing: string[]}
 */
function gofilmAttachOperator(PDO $pdo, array $locations, int $operatorId): array
{
    $selectDestination = $pdo->prepare("SELECT id, operator FROM destination WHERE location = ?");
    $updateDestination = $pdo->prepare("UPDATE destination SET operator = ? WHERE id = ?");

    $attached = 0;
    $missing = [];

    foreach ($locations as $location) {
        $selectDestination->execute([$location]);
        $rows = $selectDestination->fetchAll();
        if ($rows === []) {
            $missing[] = $location;
            continue;
        }

        foreach ($rows as $row) {
            $operatorIds = gofilmParseOperatorIds((string) $row["operator"]);
            if (in_array($operatorId, $operatorIds, true)) {
                continue;
            }
            $operatorIds[] = $operatorId;
            $updateDestination->execute([implode(",", $operatorIds), $row["id"]]);
            $attached++;
        }
    }

    return ["attached" => $attached, "missing" => $missing];
}

/**
 * Retire un opérateur de toutes les destinations qui lui sont associées.
 */
function gofilmDetachOperator(PDO $pdo, int $operatorId): void
{
    $destinations = $pdo->query("SELECT id, operator FROM destination WHERE operator <> ''")->fetchAll();
    $updateDestination = $pdo->prepare("UPDATE destination SET operator = ? WHERE id = ?");

    foreach ($destinations as $destination) {
        $operatorIds = gofilmParseOperatorIds((string) $destination["operator"]);
        if (!in_array($operatorId, $operatorIds, true)) {
            continue;
        }
        $operatorIds = array_values(array_filter($operatorIds, static fn (int $id): bool => $id !== $operatorId));
        $updateDestination->execute([implode(",", $operatorIds), $destination["id"]]);
    }
}

$adminMessage = $_SESSION["admin_message"] ?? null;
unset($_SESSION["admin_message"]);
$adminError = null;
$operators = [];
$pdo = null;

try {
    $pdo = require __DIR__ . "/../utils/database.php";
} catch (PDOException $exception) {
    error_log("GoFilm admin database connection failed: " . $exception->getMessage());
    $adminError = "Connexion à la base de données impossible. Vérifiez la configuration MySQL.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfToken = $_POST["csrf_token"] ?? "";
    if (!is_string($csrfToken) || !isset($_SESSION["admin_csrf_token"]) || !hash_equals($_SESSION["admin_csrf_token"], $csrfToken)) {
        $adminError = "La session a expiré. Rechargez la page puis réessayez.";
    } elseif ($pdo === null) {
        $adminError = "L'action est impossible tant que la base de données n'est pas disponible.";
    } else {
        try {
            $action = $_POST["action"] ?? "";
            if (!is_string($action)) {
                throw new InvalidArgumentException("Action invalide.");
            }

            if ($action === "create_operator") {
                $name = trim(is_string($_POST["name"] ?? null) ? $_POST["name"] : "");
                $link = trim(is_string($_POST["link"] ?? null) ? $_POST["link"] : "");
                $urlParts = parse_url($link);

                if ($name === "" || strlen($name) > 250) {
                    throw new InvalidArgumentException("Le nom est obligatoire et ne doit pas dépasser 250 caractères.");
                }
                if (strlen($link) > 300 || filter_var($link, FILTER_VALIDATE_URL) === false
                    || !in_array($urlParts["scheme"] ?? "", ["http", "https"], true)) {
                    throw new InvalidArgumentException("Saisissez une adresse web valide en HTTP ou HTTPS.");
                }

                $selectedIds = $_POST["destinations"] ?? [];
                if (!is_array($selectedIds)) {
                    throw new InvalidArgumentException("La sélection de destinations est invalide.");
                }
                $selectedDestinations = [];
                foreach ($selectedIds as $selectedId) {
                    if (!is_string($selectedId) || !isset($destinationsById[$selectedId])) {
                        throw new InvalidArgumentException("La sélection contient une destination inconnue.");
                    }
                    $selectedDestinations[$selectedId] = $destinationsById[$selectedId];
                }

                $pdo->beginTransaction();
                $insertOperator = $pdo->prepare(
                    "INSERT INTO tour_operator (name, link, grade_count, is_premium) VALUES (?, ?, 0, 0)"
                );
                $insertOperator->execute([$name, $link]);
                $operatorId = (int) $pdo->lastInsertId();

                $result = gofilmAttachOperator($pdo, array_column($selectedDestinations, 1), $operatorId);
                $pdo->commit();

                $_SESSION["admin_message"] = "Le tour-opérateur a été ajouté.";
                if ($result["missing"] !== []) {
                    $_SESSION["admin_message"] .= " Destinations introuvables : " . implode(", ", $result["missing"]) . ".";
                }
                header("Location: index.php");
                exit;
            }

            if ($action === "assign_destinations") {
                $operatorId = filter_var($_POST["operator_id"] ?? null, FILTER_VALIDATE_INT);
                $selectedIds = $_POST["destinations"] ?? [];
                if ($operatorId === false || $operatorId === null || $operatorId < 1 || !is_array($selectedIds)) {
                    throw new InvalidArgumentException("Sélectionnez un tour-opérateur et des destinations valides.");
                }
                if ($selectedIds === []) {
                    throw new InvalidArgumentException("Sélectionnez au moins une destination.");
                }

                $selectedDestinations = [];
                foreach ($selectedIds as $selectedId) {
                    if (!is_string($selectedId) || !isset($destinationsById[$selectedId])) {
                        throw new InvalidArgumentException("La sélection contient une destination inconnue.");
                    }
                    $selectedDestinations[$selectedId] = $destinationsById[$selectedId];
                }

                $pdo->beginTransaction();
                $operatorExists = $pdo->prepare("SELECT id FROM tour_operator WHERE id = ?");
                $operatorExists->execute([$operatorId]);
                if (!$operatorExists->fetch()) {
                    throw new InvalidArgumentException("Le tour-opérateur sélectionné n'existe pas.");
                }

                $result = gofilmAttachOperator($pdo, array_column($selectedDestinations, 1), $operatorId);
                $pdo->commit();

                $messages = [
                    $result["attached"] > 0
                        ? "{$result["attached"]} destination(s) ajoutée(s) au tour-opérateur."
                        : "Ces destinations sont déjà associées à ce tour-opérateur."
                ];
                if ($result["missing"] !== []) {
                    $messages[] = "Destinations introuvables : " . implode(", ", $result["missing"]) . ".";
                }
                $_SESSION["admin_message"] = implode(" ", $messages);
                header("Location: index.php");
                exit;
            }

            if ($action === "toggle_premium") {
                $operatorId = filter_var($_POST["operator_id"] ?? null, FILTER_VALIDATE_INT);
                if ($operatorId === false || $operatorId === null || $operatorId < 1) {
                    throw new InvalidArgumentException("Tour-opérateur invalide.");
                }
                $updatePremium = $pdo->prepare("UPDATE tour_operator SET is_premium = 1 - is_premium WHERE id = ?");
                $updatePremium->execute([$operatorId]);
                if ($updatePremium->rowCount() !== 1) {
                    throw new InvalidArgumentException("Le tour-opérateur sélectionné n'existe pas.");
                }
                $_SESSION["admin_message"] = "Le statut premium a été mis à jour.";
                header("Location: index.php");
                exit;
            }

            if ($action === "delete_operator") {
                $operatorId = filter_var($_POST["operator_id"] ?? null, FILTER_VALIDATE_INT);
                if ($operatorId === false || $operatorId === null || $operatorId < 1) {
                    throw new InvalidArgumentException("Tour-opérateur invalide.");
                }

                $pdo->beginTransaction();
                $findOperator = $pdo->prepare("SELECT id FROM tour_operator WHERE id = ? FOR UPDATE");
                $findOperator->execute([$operatorId]);
                if (!$findOperator->fetch()) {
                    throw new InvalidArgumentException("Le tour-opérateur sélectionné n'existe pas.");
                }

                $deleteReviews = $pdo->prepare("DELETE FROM review WHERE tour_operator_id = ?");
                $deleteReviews->execute([$operatorId]);
                gofilmDetachOperator($pdo, $operatorId);
                $deleteOperator = $pdo->prepare("DELETE FROM tour_operator WHERE id = ?");
                $deleteOperator->execute([$operatorId]);
                $pdo->commit();

                $_SESSION["admin_message"] = "Le tour-opérateur a été supprimé et retiré de ses destinations.";
                header("Location: index.php");
                exit;
            }

            throw new InvalidArgumentException("Action inconnue.");
        } catch (InvalidArgumentException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $adminError = $exception->getMessage();
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("GoFilm admin database action failed: " . $exception->getMessage());
            $adminError = "L'opération a échoué. Vérifiez la base de données et réessayez.";
        }
    }
}

if ($pdo !== null) {
    try {
        $operators = $pdo->query(
            "SELECT id, name, link, is_premium FROM tour_operator ORDER BY name"
        )->fetchAll();

        $operatorDestinations = $pdo->query(
            "SELECT location, operator FROM destination ORDER BY location"
        )->fetchAll();

        $locationsByOperator = [];
        foreach ($operatorDestinations as $operatorDestination) {
            foreach (gofilmParseOperatorIds((string) $operatorDestination["operator"]) as $operatorId) {
                $locationsByOperator[$operatorId][] = $operatorDestination["location"];
            }
        }

        foreach ($operators as &$operator) {
            $operator["destinations"] = $locationsByOperator[(int) $operator["id"]] ?? [];
        }
        unset($operator);
    } catch (PDOException $exception) {
        error_log("GoFilm admin data loading failed: " . $exception->getMessage());
        $adminError = "Impossible de charger les tour-opérateurs. Vérifiez la structure de la base de données.";
    }
}

if (!isset($_SESSION["admin_csrf_token"])) {
    $_SESSION["admin_csrf_token"] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../public/style/style.css">
    <link href="../src/output.css" rel="stylesheet">
    <title>GoFilm — Administration</title>
</head>
<body class="container mx-auto pb-10">
    <?php require_once __DIR__ . "/../Entities/nav.php"; ?>
    <main class="admin-page">
        <?php require __DIR__ . "/panel.php"; ?>
    </main>
</body>
</html>
