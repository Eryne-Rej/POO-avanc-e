<?php
if (!defined("GOFILM_ADMIN_VIEW")) {
    define("GOFILM_ADMIN_VIEW", true);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function gofilmParseOperatorIds(string $value): array
{
    $operatorIds = [];
    foreach (explode(",", $value) as $valuePart) {
        $operatorId = filter_var(trim($valuePart), FILTER_VALIDATE_INT);
        if ($operatorId !== false && $operatorId > 0) {
            $operatorIds[$operatorId] = $operatorId;
        }
    }

    return array_values($operatorIds);
}

function gofilmValidateDestinationIds($submittedIds, array $destinationsById): array
{
    if (!is_array($submittedIds)) {
        throw new InvalidArgumentException("La sélection de destinations est invalide.");
    }

    $destinationIds = [];
    foreach ($submittedIds as $submittedId) {
        $destinationId = filter_var($submittedId, FILTER_VALIDATE_INT);
        if ($destinationId === false || $destinationId === null || !isset($destinationsById[$destinationId])) {
            throw new InvalidArgumentException("La sélection contient une destination inconnue.");
        }
        $destinationIds[$destinationId] = $destinationId;
    }

    return array_values($destinationIds);
}

$adminMessage = $_SESSION["admin_message"] ?? null;
unset($_SESSION["admin_message"]);
$adminError = null;
$operators = [];
$destinations = [];
$destinationsById = [];
$pdo = null;
$databaseSchemaReady = false;

try {
    $pdo = require __DIR__ . "/../utils/database.php";
    $destinations = $pdo->query(
        "SELECT id, location, price, image, note, operator FROM destination ORDER BY id"
    )->fetchAll();
    foreach ($destinations as $destination) {
        $destinationsById[(int) $destination["id"]] = $destination;
    }

    $operators = $pdo->query(
        "SELECT id, name, link, grade_count, is_premium FROM tour_operator ORDER BY name"
    )->fetchAll();
    $databaseSchemaReady = true;
} catch (PDOException $exception) {
    error_log("GoFilm admin database loading failed: " . $exception->getMessage());
    $adminError = "La base « gofilm » ne correspond pas au schéma du fichier SQL fourni.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfToken = $_POST["csrf_token"] ?? "";
    if (!is_string($csrfToken) || !isset($_SESSION["admin_csrf_token"]) || !hash_equals($_SESSION["admin_csrf_token"], $csrfToken)) {
        $adminError = "La session a expiré. Rechargez la page puis réessayez.";
    } elseif ($pdo === null || !$databaseSchemaReady) {
        $adminError = "L'action est impossible tant que la base gofilm n'utilise pas le schéma attendu.";
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

                $insertOperator = $pdo->prepare(
                    "INSERT INTO tour_operator (name, link, grade_count, is_premium) VALUES (?, ?, 0, 0)"
                );
                $insertOperator->execute([$name, $link]);
                $_SESSION["admin_message"] = "Le tour-opérateur a été ajouté. Associez-lui des destinations depuis le second formulaire.";
                header("Location: index.php");
                exit;
            }

            if ($action === "assign_destinations") {
                $operatorId = filter_var($_POST["operator_id"] ?? null, FILTER_VALIDATE_INT);
                if ($operatorId === false || $operatorId === null || $operatorId < 1) {
                    throw new InvalidArgumentException("Sélectionnez un tour-opérateur valide.");
                }
                $destinationIds = gofilmValidateDestinationIds(
                    $_POST["destinations"] ?? [],
                    $destinationsById
                );
                if ($destinationIds === []) {
                    throw new InvalidArgumentException("Sélectionnez au moins une destination.");
                }

                $operatorExists = $pdo->prepare("SELECT id FROM tour_operator WHERE id = ?");
                $operatorExists->execute([$operatorId]);
                if (!$operatorExists->fetch()) {
                    throw new InvalidArgumentException("Le tour-opérateur sélectionné n'existe pas.");
                }

                $updateDestination = $pdo->prepare("UPDATE destination SET operator = ? WHERE id = ?");
                $addedCount = 0;
                foreach ($destinationIds as $destinationId) {
                    $assignedIds = gofilmParseOperatorIds($destinationsById[$destinationId]["operator"]);
                    if (in_array($operatorId, $assignedIds, true)) {
                        continue;
                    }
                    $assignedIds[] = $operatorId;
                    $updateDestination->execute([implode(",", $assignedIds), $destinationId]);
                    $addedCount++;
                }

                $_SESSION["admin_message"] = $addedCount > 0
                    ? "{$addedCount} destination(s) ajoutée(s) au tour-opérateur."
                    : "Ces destinations sont déjà associées à ce tour-opérateur.";
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

                $operatorExists = $pdo->prepare("SELECT id FROM tour_operator WHERE id = ?");
                $operatorExists->execute([$operatorId]);
                if (!$operatorExists->fetch()) {
                    throw new InvalidArgumentException("Le tour-opérateur sélectionné n'existe pas.");
                }

                $updateDestination = $pdo->prepare("UPDATE destination SET operator = ? WHERE id = ?");
                foreach ($destinations as $destination) {
                    $assignedIds = gofilmParseOperatorIds($destination["operator"]);
                    $remainingIds = array_values(array_diff($assignedIds, [$operatorId]));
                    if (count($remainingIds) !== count($assignedIds)) {
                        $updateDestination->execute([implode(",", $remainingIds), (int) $destination["id"]]);
                    }
                }

                $pdo->beginTransaction();
                $deleteReviews = $pdo->prepare("DELETE FROM review WHERE tour_operator_id = ?");
                $deleteReviews->execute([$operatorId]);
                $deleteOperator = $pdo->prepare("DELETE FROM tour_operator WHERE id = ?");
                $deleteOperator->execute([$operatorId]);
                $pdo->commit();

                $_SESSION["admin_message"] = "Le tour-opérateur a été supprimé de la base ainsi que ses avis et associations.";
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
            $adminError = "L'opération a échoué. Vérifiez la base gofilm et réessayez.";
        }
    }
}

if ($pdo !== null && $databaseSchemaReady) {
    try {
        $destinationNamesByOperator = [];
        foreach ($destinations as $destination) {
            foreach (gofilmParseOperatorIds($destination["operator"]) as $assignedOperatorId) {
                $destinationNamesByOperator[$assignedOperatorId][] = $destination["location"];
            }
        }
        foreach ($operators as &$operator) {
            $operator["destinations"] = $destinationNamesByOperator[(int) $operator["id"]] ?? [];
        }
        unset($operator);
    } catch (PDOException $exception) {
        error_log("GoFilm admin association loading failed: " . $exception->getMessage());
        $adminError = "Impossible de charger les associations des tour-opérateurs.";
    }
}

if (!isset($_SESSION["admin_csrf_token"])) {
    $_SESSION["admin_csrf_token"] = bin2hex(random_bytes(32));
}

require __DIR__ . "/../index.php";
